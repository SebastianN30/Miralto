<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class DailySalesController extends Controller
{
    public function __invoke(): Response
    {
        $today = now()->toDateString();

        $orders = Order::where('status', 'paid')
            ->whereDate('updated_at', $today)
            ->with(['user:id,name', 'items'])
            ->latest('updated_at')
            ->get();

        $byCash = 0.0;
        $byTransfer = 0.0;
        $byCard = 0.0;
        $serviceTotal = 0.0;
        $serviceCount = 0;

        foreach ($orders as $order) {
            if ($order->hasSplitPayment()) {
                $amount1 = (float) $order->payment_amount_1;
                $amount2 = (float) $order->payment_amount_2;
                match ($order->payment_method) {
                    'cash' => $byCash += $amount1,
                    'transfer' => $byTransfer += $amount1,
                    'card' => $byCard += $amount1,
                    default => null,
                };
                match ($order->payment_method_2) {
                    'cash' => $byCash += $amount2,
                    'transfer' => $byTransfer += $amount2,
                    'card' => $byCard += $amount2,
                    default => null,
                };
            } else {
                $total = (float) $order->total;
                match ($order->payment_method) {
                    'cash' => $byCash += $total,
                    'transfer' => $byTransfer += $total,
                    'card' => $byCard += $total,
                    default => null,
                };
            }

            if ($order->service_charge && $order->service_charge_amount) {
                $serviceTotal += (float) $order->service_charge_amount;
                $serviceCount++;
            }
        }

        return Inertia::render('cash/DailySales', [
            'orders' => $orders,
            'stats' => [
                'grand_total' => round($byCash + $byTransfer + $byCard, 2),
                'by_cash' => round($byCash, 2),
                'by_transfer' => round($byTransfer, 2),
                'by_card' => round($byCard, 2),
                'service_total' => round($serviceTotal, 2),
                'service_count' => $serviceCount,
                'service_avg' => $serviceCount > 0 ? round($serviceTotal / $serviceCount, 2) : 0,
                'orders_count' => $orders->count(),
            ],
            'date' => $today,
        ]);
    }
}
