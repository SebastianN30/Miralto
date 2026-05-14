<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Clock, CheckCircle, ArrowRight, Utensils } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Order } from '@/types';
import { create, show } from '@/routes/waiter';

type Props = {
    orders: Order[];
    pendingCount: number;
};

defineProps<Props>();

function formatCOP(value: number | string): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(Number(value));
}

function formatTime(dateStr: string): string {
    return new Intl.DateTimeFormat('es-CO', {
        hour: '2-digit', minute: '2-digit',
    }).format(new Date(dateStr));
}
</script>

<template>
    <Head title="Pedidos" />

    <div class="flex flex-col gap-4 p-4">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">Pedidos</h1>
                <p class="text-sm text-muted-foreground">
                    {{ pendingCount }} {{ pendingCount === 1 ? 'pendiente' : 'pendientes' }}
                </p>
            </div>
            <Link :href="create()">
                <Button class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                    <Plus class="size-4" />
                    Nuevo
                </Button>
            </Link>
        </div>

        <!-- Empty state -->
        <div v-if="orders.length === 0" class="rounded-xl border border-dashed border-sidebar-border/70 bg-card py-16 text-center">
            <Utensils class="mx-auto mb-3 size-10 text-muted-foreground/40" />
            <p class="text-sm font-medium">No hay pedidos aún</p>
            <p class="mb-4 text-xs text-muted-foreground">Crea el primer pedido del día</p>
            <Link :href="create()">
                <Button class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                    <Plus class="size-4" />
                    Crear pedido
                </Button>
            </Link>
        </div>

        <!-- Orders list -->
        <div v-else class="flex flex-col gap-2">
            <Link
                v-for="order in orders"
                :key="order.id"
                :href="show({ order: order.id })"
                class="group flex items-center gap-3 rounded-xl border border-sidebar-border/70 bg-card p-3 transition-all hover:border-miralto-verde/50 hover:shadow-sm"
            >
                <!-- Status icon -->
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg"
                    :class="order.status === 'pending'
                        ? 'bg-amber-100 dark:bg-amber-900/30'
                        : 'bg-green-100 dark:bg-green-900/30'"
                >
                    <Clock v-if="order.status === 'pending'" class="size-5 text-amber-600" />
                    <CheckCircle v-else class="size-5 text-green-600" />
                </div>

                <!-- Main info -->
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="font-semibold">
                            {{ order.table_name ?? `Pedido #${order.id}` }}
                        </p>
                        <Badge
                            v-if="order.status === 'pending'"
                            variant="outline"
                            class="border-amber-200 bg-amber-50 text-[10px] text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                        >Pendiente</Badge>
                        <Badge
                            v-else
                            variant="outline"
                            class="border-green-200 bg-green-50 text-[10px] text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400"
                        >Pagado</Badge>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        #{{ order.id }} · {{ order.items?.length ?? 0 }} item(s) · {{ formatTime(order.created_at) }}
                    </p>
                </div>

                <!-- Total + arrow -->
                <div class="flex items-center gap-2">
                    <p class="text-right font-bold tabular-nums text-miralto-verde">
                        {{ formatCOP(order.total) }}
                    </p>
                    <ArrowRight class="size-4 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-miralto-verde" />
                </div>
            </Link>
        </div>
    </div>
</template>
