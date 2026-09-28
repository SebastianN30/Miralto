<script setup lang="ts">
import { Head, router, usePoll } from '@inertiajs/vue3';
import { CheckCheck, ChefHat, Circle, CircleCheck } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import * as KitchenController from '@/actions/App/Http/Controllers/KitchenController';
import { Button } from '@/components/ui/button';
import type { Order } from '@/types';

type Station = { id: number; name: string };
type Props = { orders: Order[]; stations: Station[] };

const props = defineProps<Props>();

// Refresca la cola cada 15 s para ver pedidos nuevos sin recargar
usePoll(15000, { only: ['orders'] });

// Reloj para los minutos de espera
const now = ref(Date.now());
let clock: ReturnType<typeof setInterval>;
onMounted(() => { clock = setInterval(() => { now.value = Date.now(); }, 30000); });
onBeforeUnmount(() => clearInterval(clock));

// null = todas las estaciones
const activeStation = ref<number | null>(null);

function stationItems(order: Order) {
    return (order.items ?? []).filter(
        (i) => activeStation.value === null || i.product?.printer_id === activeStation.value,
    );
}

const visibleOrders = computed(() =>
    props.orders.filter((o) => stationItems(o).some((i) => !i.prepared_at)),
);

function waitMinutes(order: Order): number {
    return Math.max(0, Math.floor((now.value - new Date(order.created_at).getTime()) / 60000));
}

function waitClass(minutes: number): string {
    if (minutes >= 20) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    if (minutes >= 10) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    return 'bg-muted text-muted-foreground';
}

function toggleItem(itemId: number) {
    router.patch(KitchenController.toggleItem.url({ item: itemId }), {}, { preserveScroll: true });
}

function markOrder(order: Order) {
    router.patch(
        KitchenController.markOrder.url({ order: order.id }),
        { printer_id: activeStation.value },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head title="Cocina" />

    <div class="flex flex-col gap-4 p-4">

        <!-- Station filter -->
        <div v-if="stations.length > 0" class="flex flex-wrap gap-2">
            <button
                type="button"
                class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                :class="activeStation === null
                    ? 'bg-miralto-verde text-white shadow-sm'
                    : 'border border-sidebar-border/70 bg-card hover:bg-muted'"
                @click="activeStation = null"
            >Todas</button>
            <button
                v-for="s in stations"
                :key="s.id"
                type="button"
                class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                :class="activeStation === s.id
                    ? 'bg-miralto-verde text-white shadow-sm'
                    : 'border border-sidebar-border/70 bg-card hover:bg-muted'"
                @click="activeStation = s.id"
            >{{ s.name }}</button>
        </div>

        <!-- Empty state -->
        <div
            v-if="visibleOrders.length === 0"
            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 py-20 text-muted-foreground"
        >
            <ChefHat class="mb-3 size-10 opacity-30" />
            <p class="text-sm">No hay pedidos por preparar.</p>
        </div>

        <!-- Order cards -->
        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="order in visibleOrders"
                :key="order.id"
                class="flex flex-col rounded-xl border border-sidebar-border/70 bg-card"
            >
                <div class="flex items-start justify-between gap-2 border-b border-sidebar-border/70 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-lg font-bold">{{ order.table_name ?? `Pedido #${order.id}` }}</p>
                        <p class="text-xs text-muted-foreground">
                            #{{ order.id }} · {{ order.user?.name ?? '—' }}
                        </p>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold" :class="waitClass(waitMinutes(order))">
                        {{ waitMinutes(order) }} min
                    </span>
                </div>

                <ul class="flex-1 divide-y divide-sidebar-border/40">
                    <li v-for="item in stationItems(order)" :key="item.id">
                        <button
                            type="button"
                            class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-muted/40 active:bg-muted"
                            @click="toggleItem(item.id)"
                        >
                            <CircleCheck v-if="item.prepared_at" class="mt-0.5 size-5 shrink-0 text-miralto-verde" />
                            <Circle v-else class="mt-0.5 size-5 shrink-0 text-muted-foreground/50" />
                            <div class="min-w-0 flex-1" :class="{ 'opacity-50': item.prepared_at }">
                                <p class="text-base font-medium leading-tight" :class="{ 'line-through': item.prepared_at }">
                                    <span class="font-bold text-miralto-marron">{{ item.quantity }}×</span>
                                    {{ item.product?.name ?? 'Producto eliminado' }}
                                </p>
                                <p v-if="item.notes" class="mt-0.5 text-sm font-medium text-amber-700 dark:text-amber-400">
                                    {{ item.notes }}
                                </p>
                            </div>
                        </button>
                    </li>
                </ul>

                <div v-if="order.notes" class="border-t border-sidebar-border/70 px-4 py-2 text-sm text-muted-foreground">
                    {{ order.notes }}
                </div>

                <div class="border-t border-sidebar-border/70 p-3">
                    <Button
                        class="w-full gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90"
                        @click="markOrder(order)"
                    >
                        <CheckCheck class="size-4" />
                        Todo listo
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
