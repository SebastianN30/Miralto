<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddWaiterItemsRequest;
use App\Http\Requests\SplitOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Table;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Order::with(['user:id,name', 'items'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->orWhere('id', $request->search);
        }

        $orders = $query->paginate(15)->withQueryString();

        return Inertia::render('orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'search']),
            'stats' => [
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'paid' => Order::where('status', 'paid')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
                'revenue' => Order::where('status', 'paid')->sum('total'),
            ],
        ]);
    }

    public function create(): Response
    {
        $categories = Category::with(['activeProducts' => fn ($q) => $q->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $tables = Table::active()->orderBy('name')->get(['id', 'name', 'zone', 'capacity']);

        return Inertia::render('orders/Create', [
            'categories' => $categories,
            'tables' => $tables,
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $openRegister = CashRegister::open()->latest('opened_at')->first();

        $tableId = $request->table_id;
        $tableName = $tableId
            ? Table::find($tableId)?->name
            : $request->table_name;

        $order = Order::create([
            'user_id' => $request->user()->id,
            'cash_register_id' => $openRegister?->id,
            'table_id' => $tableId,
            'table_name' => $tableName,
            'total' => 0,
            'status' => 'pending',
            'payment_method' => $request->payment_method,
            'payment_amount_1' => $request->payment_amount_1,
            'payment_method_2' => $request->payment_method_2,
            'payment_amount_2' => $request->payment_amount_2,
            'notes' => $request->notes,
        ]);

        $total = 0;

        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $subtotal = round($product->price * $item['quantity'], 2);
            $total += $subtotal;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'subtotal' => $subtotal,
            ]);
        }

        $order->update(['total' => $total]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "Orden #{$order->id} creada exitosamente."]);

        return to_route('orders.show', $order);
    }

    public function show(Order $order): Response
    {
        $order->load(['user:id,name,email', 'items.product.category', 'logs.user:id,name']);

        $categories = Category::with(['activeProducts' => fn ($q) => $q->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $wallets = Wallet::active()->orderBy('name')->get(['id', 'name', 'type']);

        return Inertia::render('orders/Show', [
            'order' => $order,
            'categories' => $categories,
            'wallets' => $wallets,
        ]);
    }

    public function addItems(AddWaiterItemsRequest $request, Order $order): RedirectResponse
    {
        if ($order->status !== 'pending') {
            return back()->withErrors(['items' => 'Solo se pueden agregar productos a órdenes pendientes.']);
        }

        foreach ($request->items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $subtotal = round($product->price * $item['quantity'], 2);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'subtotal' => $subtotal,
                'notes' => $item['notes'] ?? null,
            ]);
        }

        $order->recalculateTotal();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Productos agregados a la orden.']);

        return back();
    }

    public function edit(Order $order): Response
    {
        $wallets = Wallet::active()->orderBy('name')->get(['id', 'name', 'type']);

        return Inertia::render('orders/Edit', [
            'order' => $order,
            'wallets' => $wallets,
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        // Only admins can modify already-paid orders
        if ($order->status === 'paid' && ! $request->user()->isAdmin()) {
            abort(403, 'Solo los administradores pueden modificar órdenes pagadas.');
        }

        // Security key required to modify already-paid orders
        if ($order->status === 'paid' && $request->input('security_key') !== 'MiraltoSTP') {
            return back()->withErrors(['security_key' => 'Clave de seguridad incorrecta.']);
        }

        $data = $request->validated();
        $previousStatus = $order->status;
        $subtotal = (float) $order->items()->sum('subtotal');

        if (! empty($data['service_charge'])) {
            if (! empty($data['service_charge_percentage'])) {
                $pct = max(1.0, min(100.0, (float) $data['service_charge_percentage']));
                $serviceAmount = round($subtotal * ($pct / 100), 2);
                $data['service_charge_percentage'] = $pct;
            } else {
                $serviceAmount = max(0.0, (float) ($data['service_charge_custom_amount'] ?? 0));
                $data['service_charge_percentage'] = null;
            }
            $data['service_charge'] = true;
            $data['service_charge_amount'] = $serviceAmount;
            $data['total'] = round($subtotal + $serviceAmount, 2);
        } else {
            $data['service_charge'] = false;
            $data['service_charge_percentage'] = null;
            $data['service_charge_amount'] = null;
            $data['total'] = $subtotal;
        }

        $walletId1 = $data['wallet_id_1'] ?? null;
        $walletId2 = $data['wallet_id_2'] ?? null;

        $order->update($data);

        $userId = $request->user()->id;

        // Sync wallet transactions when a paid order's payment details are updated (paid → paid)
        if ($previousStatus === 'paid' && $order->status === 'paid') {
            $correctionRef = "COR-ORD-{$order->id}";

            // Create reversal expense on each old wallet before removing the original payment records
            $oldPayments = WalletTransaction::where('order_id', $order->id)->where('type', 'payment')->get();
            foreach ($oldPayments as $oldTx) {
                WalletTransaction::create([
                    'wallet_id' => $oldTx->wallet_id,
                    'user_id' => $userId,
                    'order_id' => null,
                    'type' => 'expense',
                    'amount' => $oldTx->amount,
                    'description' => "Reversión pago orden #{$order->id} (corrección)",
                    'reference' => $correctionRef,
                    'transaction_date' => now()->toDateString(),
                ]);
            }

            $oldPayments->each->delete();

            if ($order->payment_method === 'transfer' && $walletId1) {
                $amount = $order->hasSplitPayment()
                    ? (float) $order->payment_amount_1
                    : (float) $order->total;
                WalletTransaction::create([
                    'wallet_id' => $walletId1,
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'type' => 'payment',
                    'amount' => $amount,
                    'description' => "Pago orden #{$order->id} (corrección)",
                    'reference' => $correctionRef,
                    'transaction_date' => now()->toDateString(),
                ]);
            }

            if ($order->payment_method_2 === 'transfer' && $walletId2) {
                WalletTransaction::create([
                    'wallet_id' => $walletId2,
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'type' => 'payment',
                    'amount' => (float) $order->payment_amount_2,
                    'description' => "Pago orden #{$order->id} segundo método (corrección)",
                    'reference' => $correctionRef,
                    'transaction_date' => now()->toDateString(),
                ]);
            }
        }

        // Auto wallet transaction when transitioning pending → paid with transfer
        if ($previousStatus !== 'paid' && $order->status === 'paid') {
            if ($order->payment_method === 'transfer' && $walletId1) {
                $amount = $order->hasSplitPayment()
                    ? (float) $order->payment_amount_1
                    : (float) $order->total;
                WalletTransaction::create([
                    'wallet_id' => $walletId1,
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'type' => 'payment',
                    'amount' => $amount,
                    'description' => "Pago orden #{$order->id}",
                    'transaction_date' => now()->toDateString(),
                ]);
            }

            if ($order->payment_method_2 === 'transfer' && $walletId2) {
                WalletTransaction::create([
                    'wallet_id' => $walletId2,
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'type' => 'payment',
                    'amount' => (float) $order->payment_amount_2,
                    'description' => "Pago orden #{$order->id} (segundo método)",
                    'transaction_date' => now()->toDateString(),
                ]);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Orden #{$order->id} actualizada."]);

        return to_route('orders.show', $order);
    }

    public function destroy(Order $order): RedirectResponse
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Solo los administradores pueden eliminar órdenes.');
        }

        $order->items()->delete();
        $order->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Orden eliminada.']);

        return to_route('orders.index');
    }

    public function split(SplitOrderRequest $request, Order $order): RedirectResponse
    {
        // Validate all requested items belong to this order
        $orderItemIds = $order->items()->pluck('id')->all();
        foreach ($request->items as $item) {
            if (! in_array($item['order_item_id'], $orderItemIds)) {
                abort(422, 'Item no pertenece a esta orden.');
            }
        }

        // Create the new sub-order
        $newOrder = Order::create([
            'user_id' => $order->user_id,
            'total' => 0,
            'status' => 'pending',
            'notes' => "Dividida de la orden #{$order->id}",
        ]);

        foreach ($request->items as $splitItem) {
            $orderItem = OrderItem::find($splitItem['order_item_id']);
            $splitQty = (int) $splitItem['quantity'];

            // Cap quantity to what's actually in the item
            $splitQty = min($splitQty, $orderItem->quantity);

            $price = (float) $orderItem->price;

            if ($splitQty >= $orderItem->quantity) {
                // Move the entire item to the new order
                $orderItem->update([
                    'order_id' => $newOrder->id,
                    'subtotal' => round($price * $orderItem->quantity, 2),
                ]);
            } else {
                // Partial move: create new item, reduce original
                OrderItem::create([
                    'order_id' => $newOrder->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => $splitQty,
                    'price' => $price,
                    'subtotal' => round($price * $splitQty, 2),
                ]);

                $remainingQty = $orderItem->quantity - $splitQty;
                $orderItem->update([
                    'quantity' => $remainingQty,
                    'subtotal' => round($price * $remainingQty, 2),
                ]);
            }
        }

        $order->recalculateTotal();
        $newOrder->recalculateTotal();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Orden dividida. Nueva orden #{$newOrder->id} creada."]);

        return to_route('orders.show', $newOrder);
    }
}
