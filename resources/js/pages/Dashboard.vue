<script setup lang="ts">
    import { Head } from '@inertiajs/vue3';
    import {
        TrendingUp, TrendingDown, ShoppingBag, DollarSign, Receipt,
        ArrowUp, ArrowDown, Calendar, Trophy, AlertTriangle, Target, PackageX,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title, Tooltip, Legend,
    BarElement, CategoryScale, LinearScale,
    LineElement, PointElement, Filler,
} from 'chart.js';
import { dashboard } from '@/routes';

ChartJS.register(
    Title, Tooltip, Legend,
    BarElement, CategoryScale, LinearScale,
    LineElement, PointElement, Filler,
);

type SaleDay = { date: string; label: string; total: number; orders: number };
type ProductRow = { id: number; name: string; units: number; revenue: number };
type LowStockProduct = { id: number; name: string; stock: number };

type Props = {
    kpis: {
        revenue_today: number;
        revenue_yesterday: number;
        revenue_week: number;
        orders_today: number;
        avg_ticket: number;
        change_vs_yesterday: number | null;
    };
    salesByDay: SaleDay[];
    topProducts: ProductRow[];
    bottomProducts: ProductRow[];
    projection: {
        current_revenue: number;
        projection: number;
        daily_average: number;
        days_elapsed: number;
        days_in_month: number;
        last_month_revenue: number;
        vs_last_month_pct: number | null;
        month_label: string;
    };
    lowStockProducts: LowStockProduct[];
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

function formatCOP(value: number): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(value);
}

function formatCompactCOP(value: number): string {
    if (value >= 1_000_000) return `$${(value / 1_000_000).toFixed(1)}M`;
    if (value >= 1_000) return `$${(value / 1_000).toFixed(0)}K`;
    return `$${value.toFixed(0)}`;
}

// ── Sales by day chart (bar) ────────────────────────────────
const salesChartData = computed(() => ({
    labels: props.salesByDay.map((d) => d.label),
    datasets: [
        {
            label: 'Ventas',
            data: props.salesByDay.map((d) => d.total),
            backgroundColor: 'rgba(45, 85, 45, 0.85)',
            borderColor: 'rgb(45, 85, 45)',
            borderWidth: 0,
            borderRadius: 6,
            maxBarThickness: 32,
        },
    ],
}));

const salesChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx: { parsed: { y: number } }) => formatCOP(ctx.parsed.y),
            },
            backgroundColor: 'rgba(0, 0, 0, 0.85)',
            padding: 10,
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: 'rgb(120, 113, 108)', font: { size: 11 } } },
        y: {
            grid: { color: 'rgba(120, 113, 108, 0.15)' },
            ticks: { color: 'rgb(120, 113, 108)', font: { size: 11 }, callback: (v: number | string) => formatCompactCOP(Number(v)) },
            beginAtZero: true,
        },
    },
};

// ── Projection chart (line) ─────────────────────────────────
const projectionChartData = computed(() => {
    const dailyAvg = props.projection.daily_average;
    const labels: string[] = [];
    const actual: (number | null)[] = [];
    const projected: (number | null)[] = [];

    let cumulative = 0;
    for (let day = 1; day <= props.projection.days_in_month; day++) {
        labels.push(day.toString());

        if (day <= props.projection.days_elapsed) {
            cumulative += dailyAvg;
            actual.push(cumulative);
            projected.push(null);
        } else {
            if (actual[day - 2] !== null && projected[day - 2] === null) {
                projected[day - 2] = actual[day - 2];
            }
            cumulative += dailyAvg;
            actual.push(null);
            projected.push(cumulative);
        }
    }

    return {
        labels,
        datasets: [
            {
                label: 'Real',
                data: actual,
                borderColor: 'rgb(45, 85, 45)',
                backgroundColor: 'rgba(45, 85, 45, 0.15)',
                fill: true,
                tension: 0.3,
                pointRadius: 2,
            },
            {
                label: 'Proyección',
                data: projected,
                borderColor: 'rgb(120, 70, 45)',
                backgroundColor: 'rgba(120, 70, 45, 0.1)',
                borderDash: [6, 4],
                fill: true,
                tension: 0.3,
                pointRadius: 0,
            },
        ],
    };
});

const projectionChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'top' as const, labels: { font: { size: 11 } } },
        tooltip: {
            callbacks: {
                label: (ctx: { dataset: { label?: string }; parsed: { y: number } }) =>
                    `${ctx.dataset.label}: ${formatCOP(ctx.parsed.y)}`,
            },
            backgroundColor: 'rgba(0, 0, 0, 0.85)',
        },
    },
    scales: {
        x: { grid: { display: false }, ticks: { color: 'rgb(120, 113, 108)', font: { size: 10 } } },
        y: {
            grid: { color: 'rgba(120, 113, 108, 0.1)' },
            ticks: { color: 'rgb(120, 113, 108)', font: { size: 10 }, callback: (v: number | string) => formatCompactCOP(Number(v)) },
            beginAtZero: true,
        },
    },
};

const totalRevenue14d = computed(() => props.salesByDay.reduce((s, d) => s + d.total, 0));
const maxUnits = computed(() => Math.max(...props.topProducts.map((p) => p.units), 1));
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-6 p-4">

        <!-- ── KPI cards ─────────────────────────────────── -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-muted-foreground">Ventas hoy</p>
                    <DollarSign class="size-4 text-miralto-verde" />
                </div>
                <p class="mt-1 text-2xl font-bold text-miralto-verde">{{ formatCOP(kpis.revenue_today) }}</p>
                <p v-if="kpis.change_vs_yesterday !== null" class="mt-1 flex items-center gap-1 text-xs">
                    <ArrowUp v-if="kpis.change_vs_yesterday >= 0" class="size-3 text-green-600" />
                    <ArrowDown v-else class="size-3 text-destructive" />
                    <span :class="kpis.change_vs_yesterday >= 0 ? 'text-green-600' : 'text-destructive'" class="font-semibold">
                        {{ Math.abs(kpis.change_vs_yesterday) }}%
                    </span>
                    <span class="text-muted-foreground">vs ayer</span>
                </p>
                <p v-else class="mt-1 text-xs text-muted-foreground">Sin datos previos</p>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-muted-foreground">Esta semana</p>
                    <Calendar class="size-4 text-miralto-marron" />
                </div>
                <p class="mt-1 text-2xl font-bold">{{ formatCOP(kpis.revenue_week) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Acumulado semanal</p>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-muted-foreground">Órdenes hoy</p>
                    <ShoppingBag class="size-4 text-miralto-verde" />
                </div>
                <p class="mt-1 text-2xl font-bold">{{ kpis.orders_today }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Total del día</p>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-muted-foreground">Ticket promedio</p>
                    <Receipt class="size-4 text-miralto-marron" />
                </div>
                <p class="mt-1 text-2xl font-bold">{{ formatCOP(kpis.avg_ticket) }}</p>
                <p class="mt-1 text-xs text-muted-foreground">Histórico</p>
            </div>
        </div>

        <!-- ── Sales chart (last 14 days) ─────────────────── -->
        <div class="rounded-xl border border-sidebar-border/70 bg-card p-5">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold">Ventas de los últimos 14 días</h2>
                    <p class="text-xs text-muted-foreground">
                        Total: <span class="font-semibold text-miralto-verde">{{ formatCOP(totalRevenue14d) }}</span>
                    </p>
                </div>
                <TrendingUp class="size-5 text-miralto-verde" />
            </div>
            <div class="h-64">
                <Bar :data="salesChartData" :options="salesChartOptions" />
            </div>
        </div>

        <!-- ── Monthly projection ─────────────────────────── -->
        <div class="rounded-xl border border-sidebar-border/70 bg-card p-5">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold capitalize">Proyección — {{ projection.month_label }}</h2>
                    <p class="text-xs text-muted-foreground">
                        Día {{ projection.days_elapsed }} de {{ projection.days_in_month }}
                    </p>
                </div>
                <Target class="size-5 text-miralto-marron" />
            </div>

            <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                <div class="rounded-lg bg-muted/40 p-3">
                    <p class="text-xs text-muted-foreground">Acumulado del mes</p>
                    <p class="text-lg font-bold">{{ formatCOP(projection.current_revenue) }}</p>
                </div>
                <div class="rounded-lg bg-miralto-verde/10 p-3">
                    <p class="text-xs text-muted-foreground">Proyección al cierre</p>
                    <p class="text-lg font-bold text-miralto-verde">{{ formatCOP(projection.projection) }}</p>
                </div>
                <div class="rounded-lg bg-muted/40 p-3">
                    <p class="text-xs text-muted-foreground">Promedio diario</p>
                    <p class="text-lg font-bold">{{ formatCOP(projection.daily_average) }}</p>
                </div>
                <div class="rounded-lg bg-muted/40 p-3">
                    <p class="text-xs text-muted-foreground">Mes anterior</p>
                    <p class="text-lg font-bold">{{ formatCOP(projection.last_month_revenue) }}</p>
                    <p v-if="projection.vs_last_month_pct !== null" class="mt-0.5 flex items-center gap-1 text-xs">
                        <span
                            :class="projection.vs_last_month_pct >= 0 ? 'text-green-600' : 'text-destructive'"
                            class="font-semibold"
                        >
                            {{ projection.vs_last_month_pct >= 0 ? '+' : '' }}{{ projection.vs_last_month_pct }}%
                        </span>
                        <span class="text-muted-foreground">vs proyectado</span>
                    </p>
                </div>
            </div>

            <div class="h-56">
                <Line :data="projectionChartData" :options="projectionChartOptions" />
            </div>
        </div>

        <!-- ── Low stock alert ───────────────────────────── -->
        <div v-if="lowStockProducts.length > 0" class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="mb-3 flex items-center gap-2">
                <PackageX class="size-5 text-amber-600" />
                <h2 class="font-semibold text-amber-800 dark:text-amber-300">Stock bajo — {{ lowStockProducts.length }} producto(s)</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <div
                    v-for="p in lowStockProducts"
                    :key="p.id"
                    class="flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm"
                    :class="p.stock === 0
                        ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300'
                        : 'border-amber-200 bg-white text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300'"
                >
                    <span class="font-medium">{{ p.name }}</span>
                    <span
                        class="rounded-full px-1.5 py-0.5 text-xs font-bold"
                        :class="p.stock === 0 ? 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300'"
                    >{{ p.stock === 0 ? 'Agotado' : p.stock }}</span>
                </div>
            </div>
        </div>

        <!-- ── Top / Bottom products ──────────────────────── -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            <!-- Top -->
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-5">
                <div class="mb-4 flex items-center gap-2">
                    <Trophy class="size-5 text-miralto-verde" />
                    <h2 class="font-semibold">Productos más vendidos</h2>
                </div>

                <div v-if="topProducts.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                    Aún no hay datos de ventas.
                </div>

                <div v-else class="space-y-3">
                    <div v-for="(p, idx) in topProducts" :key="p.id" class="space-y-1">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-miralto-verde/10 text-xs font-bold text-miralto-verde">
                                    {{ idx + 1 }}
                                </span>
                                <span class="truncate font-medium">{{ p.name }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                <span class="font-semibold text-foreground">{{ p.units }}</span>
                                <span class="hidden sm:inline">·</span>
                                <span class="hidden text-miralto-verde sm:inline">{{ formatCOP(p.revenue) }}</span>
                            </div>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-miralto-verde transition-all"
                                :style="{ width: `${(p.units / maxUnits) * 100}%` }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom -->
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-5">
                <div class="mb-4 flex items-center gap-2">
                    <AlertTriangle class="size-5 text-amber-600" />
                    <h2 class="font-semibold">Productos con menos ventas</h2>
                </div>

                <div v-if="bottomProducts.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                    Sin productos activos.
                </div>

                <div v-else class="overflow-hidden rounded-lg border border-sidebar-border/40">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/30 text-xs">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-muted-foreground">Producto</th>
                                <th class="px-3 py-2 text-right font-medium text-muted-foreground">Unidades</th>
                                <th class="hidden px-3 py-2 text-right font-medium text-muted-foreground sm:table-cell">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in bottomProducts" :key="p.id" class="border-t border-sidebar-border/30">
                                <td class="px-3 py-2 font-medium">{{ p.name }}</td>
                                <td class="px-3 py-2 text-right">
                                    <span
                                        :class="p.units === 0 ? 'text-destructive font-semibold' : 'text-muted-foreground'"
                                    >
                                        {{ p.units }}
                                    </span>
                                </td>
                                <td class="hidden px-3 py-2 text-right text-muted-foreground sm:table-cell">
                                    {{ p.revenue > 0 ? formatCOP(p.revenue) : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
