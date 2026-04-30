<?php

namespace App\Observers;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\OrderLog;

class OrderObserver
{
    public function created(Order $order): void
    {
        $this->log($order, 'created', "Orden #{$order->id} creada");
    }

    public function updated(Order $order): void
    {
        // Status change is the most important event
        if ($order->wasChanged('status')) {
            $from = $order->getOriginal('status');
            $to = $order->status;

            $this->log($order, 'status_changed', "Estado: {$from} → {$to}", [
                'status' => ['from' => $from, 'to' => $to],
            ]);

            // Auto cash movements
            if ($to === 'paid') {
                $this->createSaleMovements($order);
            } elseif ($to === 'cancelled' && $from === 'paid') {
                $this->createRefundMovement($order);
            }

            return; // Don't double-log if status changed
        }

        // Track other meaningful changes
        $tracked = ['payment_method', 'payment_amount_1', 'payment_method_2', 'payment_amount_2', 'cash_register_id'];
        $relevant = collect($tracked)
            ->filter(fn ($field) => $order->wasChanged($field))
            ->mapWithKeys(fn ($field) => [
                $field => [
                    'from' => $order->getOriginal($field),
                    'to' => $order->{$field},
                ],
            ])
            ->all();

        if (! empty($relevant)) {
            $this->log($order, 'payment_updated', 'Información de pago actualizada', $relevant);
        }
    }

    public function deleted(Order $order): void
    {
        $this->log($order, 'deleted', "Orden #{$order->id} eliminada");
    }

    /**
     * @param  array<string, mixed>|null  $changes
     */
    private function log(Order $order, string $action, string $description, ?array $changes = null): void
    {
        OrderLog::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'changes' => $changes,
        ]);
    }

    private function activeRegister(Order $order): ?CashRegister
    {
        // Prefer the register the order is linked to (if it's still open)
        if ($order->cash_register_id) {
            $register = CashRegister::find($order->cash_register_id);
            if ($register && $register->isOpen()) {
                return $register;
            }
        }

        // Fall back to the latest open session
        return CashRegister::open()->latest('opened_at')->first();
    }

    private function createSaleMovements(Order $order): void
    {
        $register = $this->activeRegister($order);
        if (! $register) {
            return;
        }

        $userId = auth()->id() ?? $order->user_id;

        if ($order->hasSplitPayment()) {
            CashMovement::create([
                'cash_register_id' => $register->id,
                'user_id' => $userId,
                'order_id' => $order->id,
                'type' => 'sale',
                'payment_method' => $order->payment_method,
                'amount' => $order->payment_amount_1 ?? 0,
                'description' => "Venta orden #{$order->id} (parte 1)",
            ]);
            CashMovement::create([
                'cash_register_id' => $register->id,
                'user_id' => $userId,
                'order_id' => $order->id,
                'type' => 'sale',
                'payment_method' => $order->payment_method_2,
                'amount' => $order->payment_amount_2 ?? 0,
                'description' => "Venta orden #{$order->id} (parte 2)",
            ]);

            return;
        }

        CashMovement::create([
            'cash_register_id' => $register->id,
            'user_id' => $userId,
            'order_id' => $order->id,
            'type' => 'sale',
            'payment_method' => $order->payment_method,
            'amount' => $order->total,
            'description' => "Venta orden #{$order->id}",
        ]);
    }

    private function createRefundMovement(Order $order): void
    {
        $register = $this->activeRegister($order);
        if (! $register) {
            return;
        }

        CashMovement::create([
            'cash_register_id' => $register->id,
            'user_id' => auth()->id() ?? $order->user_id,
            'order_id' => $order->id,
            'type' => 'refund',
            'payment_method' => $order->payment_method,
            'amount' => $order->total,
            'description' => "Devolución orden #{$order->id} (cancelada)",
        ]);
    }
}
