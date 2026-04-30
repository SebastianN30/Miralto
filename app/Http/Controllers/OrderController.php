<?php

namespace App\Http\Controllers;

use App\Http\Requests\SplitOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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

        return Inertia::render('orders/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $openRegister = CashRegister::open()->latest('opened_at')->first();

        $order = Order::create([
            'user_id' => $request->user()->id,
            'cash_register_id' => $openRegister?->id,
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

        return Inertia::render('orders/Show', [
            'order' => $order,
        ]);
    }

    public function edit(Order $order): Response
    {
        return Inertia::render('orders/Edit', [
            'order' => $order,
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $order->update($request->validated());

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
