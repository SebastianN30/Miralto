<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = CarbonImmutable::today();
        $startOfMonth = $today->startOfMonth();
        $startOfLastMonth = $today->subMonth()->startOfMonth();
        $endOfLastMonth = $today->subMonth()->endOfMonth();

        return Inertia::render('Dashboard', [
            'kpis' => $this->kpis($today),
            'salesByDay' => $this->salesByDay(14),
            'topProducts' => $this->topProducts(8),
            'bottomProducts' => $this->bottomProducts(8),
            'projection' => $this->monthlyProjection($today, $startOfMonth, $startOfLastMonth, $endOfLastMonth),
            'lowStockProducts' => $this->lowStockProducts(),
        ]);
    }

    /**
     * Headline KPIs for the top of the dashboard.
     *
     * @return array<string, mixed>
     */
    private function kpis(CarbonImmutable $today): array
    {
        $revenueToday = (float) Order::where('status', 'paid')
            ->whereDate('created_at', $today)
            ->sum('total');

        $revenueYesterday = (float) Order::where('status', 'paid')
            ->whereDate('created_at', $today->subDay())
            ->sum('total');

        $ordersToday = Order::whereDate('created_at', $today)->count();

        $revenueWeek = (float) Order::where('status', 'paid')
            ->where('created_at', '>=', $today->startOfWeek())
            ->sum('total');

        $avgTicket = (float) Order::where('status', 'paid')->avg('total') ?? 0;

        return [
            'revenue_today' => $revenueToday,
            'revenue_yesterday' => $revenueYesterday,
            'revenue_week' => $revenueWeek,
            'orders_today' => $ordersToday,
            'avg_ticket' => $avgTicket,
            'change_vs_yesterday' => $revenueYesterday > 0
                ? round((($revenueToday - $revenueYesterday) / $revenueYesterday) * 100, 1)
                : null,
        ];
    }

    /**
     * Active products with stock at or below 5 units.
     *
     * @return array<int, array{id: int, name: string, stock: int}>
     */
    private function lowStockProducts(): array
    {
        return Product::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNotNull('stock')
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->orderBy('name')
            ->get(['id', 'name', 'stock'])
            ->map(fn ($p) => [
                'id' => (int) $p->id,
                'name' => $p->name,
                'stock' => (int) $p->stock,
            ])
            ->all();
    }

    /**
     * Daily sales for the last N days, including days with zero sales.
     *
     * @return array<int, array{date: string, label: string, total: float, orders: int}>
     */
    private function salesByDay(int $days): array
    {
        $today = CarbonImmutable::today();
        $start = $today->subDays($days - 1);

        $rows = Order::where('status', 'paid')
            ->whereBetween('created_at', [$start->startOfDay(), $today->endOfDay()])
            ->selectRaw('DATE(created_at) as date, SUM(total) as total, COUNT(*) as orders')
            ->groupBy('date')
            ->pluck('total', 'date');

        $orderCounts = Order::where('status', 'paid')
            ->whereBetween('created_at', [$start->startOfDay(), $today->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $output = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->addDays($i);
            $key = $day->toDateString();

            $output[] = [
                'date' => $key,
                'label' => $day->isoFormat('ddd D MMM'),
                'total' => (float) ($rows[$key] ?? 0),
                'orders' => (int) ($orderCounts[$key] ?? 0),
            ];
        }

        return $output;
    }

    /**
     * Top N products by units sold (only paid orders).
     *
     * @return array<int, array{id: int, name: string, units: int, revenue: float}>
     */
    private function topProducts(int $limit): array
    {
        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'paid')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as units'),
                DB::raw('SUM(order_items.subtotal) as revenue'),
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('units')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'units' => (int) $row->units,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    /**
     * Bottom N active products by units sold — includes products with 0 sales.
     *
     * @return array<int, array{id: int, name: string, units: int, revenue: float}>
     */
    private function bottomProducts(int $limit): array
    {
        return Product::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->where('orders.status', '=', 'paid');
            })
            ->select(
                'products.id',
                'products.name',
                DB::raw('COALESCE(SUM(CASE WHEN orders.id IS NOT NULL THEN order_items.quantity ELSE 0 END), 0) as units'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.id IS NOT NULL THEN order_items.subtotal ELSE 0 END), 0) as revenue'),
            )
            ->groupBy('products.id', 'products.name')
            ->orderBy('units')
            ->orderBy('products.name')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'units' => (int) $row->units,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    /**
     * Project this month's revenue based on the daily run-rate so far,
     * and compare against last month's actual revenue.
     *
     * @return array<string, mixed>
     */
    private function monthlyProjection(
        CarbonImmutable $today,
        CarbonImmutable $startOfMonth,
        CarbonImmutable $startOfLastMonth,
        CarbonImmutable $endOfLastMonth,
    ): array {
        $currentRevenue = (float) Order::where('status', 'paid')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('total');

        $lastMonthRevenue = (float) Order::where('status', 'paid')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total');

        $daysElapsed = max(1, $today->day);
        $daysInMonth = $today->daysInMonth;
        $dailyAverage = $currentRevenue / $daysElapsed;
        $projection = $dailyAverage * $daysInMonth;

        $vsLastMonth = $lastMonthRevenue > 0
            ? round((($projection - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : null;

        return [
            'current_revenue' => $currentRevenue,
            'projection' => $projection,
            'daily_average' => $dailyAverage,
            'days_elapsed' => $daysElapsed,
            'days_in_month' => $daysInMonth,
            'last_month_revenue' => $lastMonthRevenue,
            'vs_last_month_pct' => $vsLastMonth,
            'month_label' => $today->isoFormat('MMMM YYYY'),
        ];
    }
}
