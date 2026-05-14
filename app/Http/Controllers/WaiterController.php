<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddWaiterItemsRequest;
use App\Http\Requests\StoreWaiterOrderRequest;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WaiterController extends Controller
{
    public function index(): Response
    {
        $orders = Order::with(['user:id,name', 'items.product:id,name,printer_id'])
            ->whereIn('status', ['pending', 'paid'])
            ->latest()
            ->limit(50)
            ->get();

        return Inertia::render('waiter/Index', [
            'orders' => $orders,
            'pendingCount' => $orders->where('status', 'pending')->count(),
        ]);
    }

    public function create(): Response
    {
        $categories = Category::with(['activeProducts' => fn ($q) => $q->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('waiter/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreWaiterOrderRequest $request): RedirectResponse
    {
        $openRegister = CashRegister::open()->latest('opened_at')->first();

        $order = Order::create([
            'user_id' => $request->user()->id,
            'cash_register_id' => $openRegister?->id,
            'table_name' => $request->table_name,
            'total' => 0,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        $this->insertItems($order, $request->items);

        $order->recalculateTotal();

        return to_route('waiter.show', $order);
    }

    public function show(Order $order): Response
    {
        $order->load([
            'user:id,name',
            'items.product:id,name,printer_id',
        ]);

        // Group items by printer for the kitchen/bar ticket overview
        $itemsByPrinter = $order->items->groupBy(fn (OrderItem $i) => $i->product?->printer_id ?? 'default')
            ->map(fn ($group) => $group->values())
            ->toArray();

        // Load categories so the waiter can add more items without re-fetching
        $categories = Category::with(['activeProducts' => fn ($q) => $q->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('waiter/Show', [
            'order' => $order,
            'itemsByPrinter' => $itemsByPrinter,
            'categories' => $categories,
        ]);
    }

    /**
     * Add items to an existing order. Items can never be removed by the waiter.
     */
    public function addItems(AddWaiterItemsRequest $request, Order $order): RedirectResponse
    {
        if ($order->status !== 'pending') {
            return back()->withErrors(['items' => 'Solo se pueden agregar productos a pedidos pendientes.']);
        }

        $this->insertItems($order, $request->items);

        $order->recalculateTotal();

        return to_route('waiter.show', $order);
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, notes?: string|null}>  $items
     */
    private function insertItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
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
    }
}
