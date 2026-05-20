<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Banknote, CreditCard, HandCoins, ShoppingBag, Smartphone, TrendingUp } from 'lucide-vue-next';
import type { Order } from '@/types';
import { index as cashIndex } from '@/routes/cash';
import { show as orderShow } from '@/routes/orders';

type DailyStats = {
    grand_total: number;
    by_cash: number;
    by_transfer: number;
    by_card: number;
    service_total: number;
    service_count: number;
    service_avg: number;
    orders_count: number;
};

type Props = { orders: Order[]; stats: DailyStats; date: string };

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Caja', href: cashIndex() },
            { title: 'Ventas del día', href: '#' },
        ],
    },
});

const paymentLabels: Record<string, string> = {
    cash: 'Efectivo',
    transfer: 'Transferencia',
    card: 'Tarjeta',
};

function formatCOP(value: number | string): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function formatDate(dateStr: string): string {
    return new Intl.DateTimeFormat('es-CO', { dateStyle: 'full' }).format(new Date(dateStr + 'T00:00:00'));
}
</script>

<template>
    <Head title="Ventas del día" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Header -->
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold">Ventas del día</h1>
                <p class="text-sm capitalize text-muted-foreground">{{ formatDate(date) }}</p>
            </div>
            <span class="w-fit rounded-full bg-miralto-verde/10 px-3 py-1 text-sm font-semibold text-miralto-verde">
                {{ stats.orders_count }} orden(es) cobrada(s)
            </span>
        </div>

        <!-- Payment breakdown -->
        <div>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Ingresos por método de pago</h2>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                    <div class="mb-2 flex items-center gap-2 text-muted-foreground">
                        <Banknote class="size-4" />
                        <span class="text-xs font-medium">Efectivo</span>
                    </div>
                    <p class="text-xl font-bold text-miralto-verde">{{ formatCOP(stats.by_cash) }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                    <div class="mb-2 flex items-center gap-2 text-muted-foreground">
                        <Smartphone class="size-4" />
                        <span class="text-xs font-medium">Transferencia</span>
                    </div>
                    <p class="text-xl font-bold text-miralto-verde">{{ formatCOP(stats.by_transfer) }}</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                    <div class="mb-2 flex items-center gap-2 text-muted-foreground">
                        <CreditCard class="size-4" />
                        <span class="text-xs font-medium">Tarjeta</span>
                    </div>
                    <p class="text-xl font-bold text-miralto-verde">{{ formatCOP(stats.by_card) }}</p>
                </div>
                <div class="rounded-xl border border-miralto-verde/30 bg-miralto-verde/5 p-4">
                    <div class="mb-2 flex items-center gap-2 text-miralto-verde">
                        <TrendingUp class="size-4" />
                        <span class="text-xs font-semibold">Total general</span>
                    </div>
                    <p class="text-xl font-bold text-miralto-verde">{{ formatCOP(stats.grand_total) }}</p>
                </div>
            </div>
        </div>

        <!-- Service charge summary -->
        <div>
            <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Servicio / Propina</h2>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-miralto-marron/30 bg-miralto-beige/40 p-4">
                    <div class="mb-2 flex items-center gap-2 text-miralto-marron">
                        <HandCoins class="size-4" />
                        <span class="text-xs font-medium">Total recaudado</span>
                    </div>
                    <p class="text-xl font-bold text-miralto-marron">{{ formatCOP(stats.service_total) }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">en {{ stats.service_count }} orden(es)</p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                    <div class="mb-2 flex items-center gap-2 text-muted-foreground">
                        <ShoppingBag class="size-4" />
                        <span class="text-xs font-medium">Órdenes con servicio</span>
                    </div>
                    <p class="text-xl font-bold">{{ stats.service_count }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        de {{ stats.orders_count }} total
                        <template v-if="stats.orders_count > 0">
                            ({{ Math.round(stats.service_count / stats.orders_count * 100) }}%)
                        </template>
                    </p>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                    <div class="mb-2 flex items-center gap-2 text-muted-foreground">
                        <TrendingUp class="size-4" />
                        <span class="text-xs font-medium">Promedio por orden</span>
                    </div>
                    <p class="text-xl font-bold">{{ formatCOP(stats.service_avg) }}</p>
                </div>
            </div>
        </div>

        <!-- Orders table -->
        <div class="overflow-hidden rounded-xl border border-sidebar-border/70">
            <div class="border-b border-sidebar-border/70 bg-muted/30 px-4 py-3">
                <h2 class="font-semibold">Órdenes cobradas hoy</h2>
            </div>

            <div v-if="orders.length === 0" class="py-12 text-center text-sm text-muted-foreground">
                No hay órdenes cobradas hoy todavía.
            </div>

            <table v-else class="w-full text-sm">
                <thead class="border-b border-sidebar-border/40">
                    <tr>
                        <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">#</th>
                        <th class="hidden px-4 py-2.5 text-left font-medium text-muted-foreground sm:table-cell">Mesero</th>
                        <th class="hidden px-4 py-2.5 text-left font-medium text-muted-foreground md:table-cell">Mesa</th>
                        <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Método</th>
                        <th class="hidden px-4 py-2.5 text-right font-medium text-muted-foreground sm:table-cell">Servicio</th>
                        <th class="px-4 py-2.5 text-right font-medium text-muted-foreground">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="order in orders"
                        :key="order.id"
                        class="border-b border-sidebar-border/30 last:border-0 hover:bg-muted/20"
                    >
                        <td class="px-4 py-3">
                            <Link :href="orderShow({ order: order.id })" class="font-mono font-semibold text-miralto-verde hover:underline">
                                #{{ order.id }}
                            </Link>
                        </td>
                        <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">{{ order.user?.name ?? '—' }}</td>
                        <td class="hidden px-4 py-3 text-muted-foreground md:table-cell">{{ order.table_name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span v-if="!order.payment_method_2">
                                {{ paymentLabels[order.payment_method!] ?? '—' }}
                            </span>
                            <span v-else class="text-xs">
                                {{ paymentLabels[order.payment_method!] }} +
                                {{ paymentLabels[order.payment_method_2] }}
                            </span>
                        </td>
                        <td class="hidden px-4 py-3 text-right sm:table-cell">
                            <span v-if="order.service_charge" class="text-miralto-marron">
                                {{ order.service_charge_percentage !== null
                                    ? `${order.service_charge_percentage}%`
                                    : 'Fijo' }}
                                · {{ formatCOP(order.service_charge_amount ?? 0) }}
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-miralto-verde">
                            {{ formatCOP(order.total) }}
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t border-sidebar-border/70 bg-muted/20">
                        <td colspan="4" class="px-4 py-3 text-right font-semibold">Total</td>
                        <td class="hidden px-4 py-3 text-right font-semibold text-miralto-marron sm:table-cell">
                            {{ formatCOP(stats.service_total) }}
                        </td>
                        <td class="px-4 py-3 text-right text-base font-bold text-miralto-verde">
                            {{ formatCOP(stats.grand_total) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
