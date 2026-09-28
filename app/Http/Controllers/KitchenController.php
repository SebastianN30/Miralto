<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Printer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KitchenController extends Controller
{
    /** Cola de preparación: órdenes pendientes con al menos un ítem sin marcar, la más antigua primero. */
    public function index(): Response
    {
        $orders = Order::where('status', 'pending')
            ->whereHas('items', fn ($q) => $q->whereNull('prepared_at'))
            ->with(['user:id,name', 'items.product:id,name,printer_id'])
            ->oldest()
            ->get();

        return Inertia::render('kitchen/Index', [
            'orders' => $orders,
            'stations' => Printer::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Alterna un ítem entre listo / pendiente. */
    public function toggleItem(OrderItem $item): RedirectResponse
    {
        if ($item->order->status !== 'pending') {
            return back()->withErrors(['item' => 'La orden ya no está pendiente.']);
        }

        $item->update(['prepared_at' => $item->prepared_at ? null : now()]);

        return back();
    }

    /** Marca como listos los ítems pendientes de la orden (opcionalmente solo los de una estación). */
    public function markOrder(Request $request, Order $order): RedirectResponse
    {
        $request->validate(['printer_id' => ['nullable', 'integer']]);

        if ($order->status !== 'pending') {
            return back()->withErrors(['order' => 'La orden ya no está pendiente.']);
        }

        $order->items()
            ->whereNull('prepared_at')
            ->when($request->filled('printer_id'), fn ($q) => $q->whereHas(
                'product',
                fn ($p) => $p->where('printer_id', $request->integer('printer_id')),
            ))
            ->update(['prepared_at' => now()]);

        return back();
    }
}
