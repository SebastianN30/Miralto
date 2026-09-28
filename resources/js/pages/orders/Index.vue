<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Plus, Search, ShoppingBag, Clock, CheckCircle, XCircle, TrendingUp } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import * as OrderController from '@/actions/App/Http/Controllers/OrderController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Order, PaginatedData } from '@/types';
import { index, create } from '@/routes/orders';

type Props = {
    orders: PaginatedData<Order>;
    employees: { id: number; name: string; is_active: boolean }[];
    filters: {
        status?: string;
        search?: string;
        employee?: string;
    };
    stats: {
        total: number;
        pending: number;
        paid: number;
        cancelled: number;
        revenue: number;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Órdenes', href: index() },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const employeeFilter = ref(props.filters.employee ?? '');

let searchTimeout: ReturnType<typeof setTimeout>;
const page = usePage();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

watch(statusFilter, () => applyFilters());
watch(employeeFilter, () => applyFilters());

function applyFilters() {
    router.get(
        OrderController.index.url(),
        { search: search.value, status: statusFilter.value, employee: employeeFilter.value },
        { preserveState: true, replace: true },
    );
}

function formatCOP(value: number | string): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function formatDate(dateStr: string): string {
    return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(dateStr));
}

function confirmDelete(order: Order) {
    if (confirm(`¿Eliminar la orden #${order.id}? Esta acción no se puede deshacer.`)) {
        router.delete(OrderController.destroy.url({ order: order.id }));
    }
}

const statusLabels: Record<string, string> = {
    pending: 'Pendiente',
    paid: 'Pagado',
    cancelled: 'Cancelado',
};

const paymentLabels: Record<string, string> = {
    cash: 'Efectivo',
    transfer: 'Transferencia',
    card: 'Tarjeta',
};
</script>

<template>
    <Head title="Órdenes" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Stats -->
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-verde/10 p-2">
                        <ShoppingBag class="size-5 text-miralto-verde" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total órdenes</p>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/30">
                        <Clock class="size-5 text-amber-600" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Pendientes</p>
                        <p class="text-2xl font-bold">{{ stats.pending }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30">
                        <CheckCircle class="size-5 text-green-600" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Pagadas</p>
                        <p class="text-2xl font-bold">{{ stats.paid }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-marron/10 p-2">
                        <TrendingUp class="size-5 text-miralto-marron" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Ingresos</p>
                        <p class="text-lg font-bold leading-tight">{{ formatCOP(stats.revenue) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 gap-2">
                <!-- Search -->
                <div class="relative flex-1 max-w-xs">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por cliente o #ID…"
                        class="h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                    />
                </div>

                <!-- Status filter -->
                <select
                    v-model="statusFilter"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                >
                    <option value="">Todos los estados</option>
                    <option value="pending">Pendientes</option>
                    <option value="paid">Pagadas</option>
                    <option value="cancelled">Canceladas</option>
                </select>

                <!-- Employee filter -->
                <select
                    v-model="employeeFilter"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                >
                    <option value="">Todas las órdenes</option>
                    <option value="any">Solo de empleados</option>
                    <option v-for="e in employees" :key="e.id" :value="String(e.id)">
                        {{ e.name }}{{ e.is_active ? '' : ' (inactivo)' }}
                    </option>
                </select>
            </div>

            <Link :href="create()">
                <Button class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                    <Plus class="size-4" />
                    Nueva orden
                </Button>
            </Link>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-sidebar-border/70">
            <table class="w-full text-sm">
                <thead class="border-b border-sidebar-border/70 bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">#</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Cliente</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Mesa</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground md:table-cell">Empleado</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground sm:table-cell">Productos</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Total</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Estado</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground lg:table-cell">Pago</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground lg:table-cell">Fecha</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-if="orders.data.length === 0"
                    >
                        <td colspan="10" class="px-4 py-12 text-center text-muted-foreground">
                            No se encontraron órdenes.
                        </td>
                    </tr>
                    <tr
                        v-for="order in orders.data"
                        :key="order.id"
                        class="border-b border-sidebar-border/40 transition-colors last:border-0 hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-mono text-xs text-muted-foreground">#{{ order.id }}</td>
                        <td class="px-4 py-3 font-medium">{{ order.user?.name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ order.table_name ?? '—' }}</td>
                        <td class="hidden px-4 py-3 md:table-cell">
                            <Badge
                                v-if="order.employee"
                                variant="outline"
                                class="border-miralto-marron/30 bg-miralto-marron/10 text-miralto-marron"
                            >{{ order.employee.name }}</Badge>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">
                            {{ order.items?.length ?? 0 }} ítem(s)
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">{{ formatCOP(order.total) }}</td>
                        <td class="px-4 py-3 text-center">
                            <Badge
                                :class="{
                                    'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-400': order.status === 'pending',
                                    'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400': order.status === 'paid',
                                    'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400': order.status === 'cancelled',
                                }"
                                variant="outline"
                            >
                                {{ statusLabels[order.status] }}
                            </Badge>
                        </td>
                        <td class="hidden px-4 py-3 text-muted-foreground lg:table-cell">
                            {{ order.payment_method ? paymentLabels[order.payment_method] : '—' }}
                        </td>
                        <td class="hidden px-4 py-3 text-xs text-muted-foreground lg:table-cell">
                            {{ formatDate(order.created_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Link :href="OrderController.show.url({ order: order.id })">
                                    <Button variant="ghost" size="sm" class="h-7 px-2 text-xs">Ver</Button>
                                </Link>
                                <Link :href="OrderController.edit.url({ order: order.id })">
                                    <Button variant="ghost" size="sm" class="h-7 px-2 text-xs">Editar</Button>
                                </Link>
                                <Button
                                    v-if="isAdmin"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    @click="confirmDelete(order)"
                                >
                                    Eliminar
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="orders.last_page > 1" class="flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
                Mostrando {{ orders.from }} – {{ orders.to }} de {{ orders.total }} órdenes
            </p>
            <div class="flex gap-1">
                <template v-for="link in orders.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm transition-colors"
                        :class="link.active
                            ? 'bg-miralto-verde text-white'
                            : 'border border-sidebar-border/70 hover:bg-muted'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
