<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Clock, History, Pencil, Scissors, Trash2, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import * as OrderController from '@/actions/App/Http/Controllers/OrderController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Order, OrderItem } from '@/types';
import { index } from '@/routes/orders';

type Props = { order: Order };

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Órdenes', href: index() },
            { title: 'Detalle de orden', href: '#' },
        ],
    },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

// ── Formatting helpers ──────────────────────────────────────
function formatCOP(value: number | string): string {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(value));
}

function formatDate(dateStr: string): string {
    return new Intl.DateTimeFormat('es-CO', { dateStyle: 'full', timeStyle: 'short' }).format(new Date(dateStr));
}

// ── Status / payment labels ──────────────────────────────────
const statusConfig: Record<string, { label: string; icon: typeof Clock; class: string }> = {
    pending: { label: 'Pendiente', icon: Clock, class: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-400' },
    paid: { label: 'Pagado', icon: CheckCircle, class: 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400' },
    cancelled: { label: 'Cancelado', icon: XCircle, class: 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400' },
};

const paymentLabels: Record<string, string> = { cash: 'Efectivo', transfer: 'Transferencia', card: 'Tarjeta' };

const currentStatus = computed(() => statusConfig[props.order.status]);
const hasSplitPayment = computed(() => props.order.payment_method_2 !== null);

// ── Quick actions ────────────────────────────────────────────
function markAsPaid() {
    router.patch(OrderController.update.url({ order: props.order.id }), {
        status: 'paid',
        payment_method: props.order.payment_method ?? 'cash',
        payment_amount_1: props.order.payment_amount_1,
        payment_method_2: props.order.payment_method_2,
        payment_amount_2: props.order.payment_amount_2,
        notes: props.order.notes,
    });
}

function markAsCancelled() {
    if (!confirm('¿Cancelar esta orden?')) return;
    router.patch(OrderController.update.url({ order: props.order.id }), {
        status: 'cancelled',
        payment_method: null,
        payment_amount_1: null,
        payment_method_2: null,
        payment_amount_2: null,
        notes: props.order.notes,
    });
}

function deleteOrder() {
    if (!confirm(`¿Eliminar definitivamente la orden #${props.order.id}?`)) return;
    router.delete(OrderController.destroy.url({ order: props.order.id }));
}

// ── Split order dialog ───────────────────────────────────────
type SplitEntry = { order_item_id: number; quantity: number; max: number; product_name: string; price: number };

const splitOpen = ref(false);
const splitProcessing = ref(false);

const splitItems = ref<SplitEntry[]>(
    (props.order.items ?? []).map((item: OrderItem) => ({
        order_item_id: item.id,
        quantity: 0,
        max: item.quantity,
        product_name: item.product?.name ?? `Item #${item.id}`,
        price: Number(item.price),
    })),
);

const splitTotal = computed(() =>
    splitItems.value.reduce((sum, i) => sum + i.price * i.quantity, 0),
);

const splitSelected = computed(() => splitItems.value.filter((i) => i.quantity > 0));

function submitSplit() {
    if (splitSelected.value.length === 0) return;
    splitProcessing.value = true;

    router.post(
        `/orders/${props.order.id}/split`,
        { items: splitSelected.value.map((i) => ({ order_item_id: i.order_item_id, quantity: i.quantity })) },
        {
            onFinish: () => { splitProcessing.value = false; splitOpen.value = false; },
        },
    );
}
</script>

<template>
    <Head :title="`Orden #${order.id}`" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Back + actions -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Link :href="index()" class="flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
                <ArrowLeft class="size-4" />
                Volver a órdenes
            </Link>

            <div class="flex flex-wrap gap-2">
                <Button v-if="order.status === 'pending'" class="gap-2 bg-green-600 text-white hover:bg-green-700" @click="markAsPaid">
                    <CheckCircle class="size-4" />Marcar como pagado
                </Button>
                <Button v-if="order.status === 'pending'" variant="outline" class="gap-2 text-destructive hover:bg-destructive/10 hover:text-destructive" @click="markAsCancelled">
                    <XCircle class="size-4" />Cancelar
                </Button>

                <!-- Split order dialog -->
                <Dialog v-model:open="splitOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline" class="gap-2" :disabled="(order.items?.length ?? 0) === 0">
                            <Scissors class="size-4" />Dividir orden
                        </Button>
                    </DialogTrigger>

                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Dividir orden #{{ order.id }}</DialogTitle>
                            <DialogDescription>
                                Selecciona cuántas unidades de cada plato separar. Se creará una nueva orden pendiente.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-2 py-2">
                            <div
                                v-for="entry in splitItems"
                                :key="entry.order_item_id"
                                class="flex items-center gap-3 rounded-lg border border-sidebar-border/50 p-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ entry.product_name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatCOP(entry.price) }} · disponibles: {{ entry.max }}</p>
                                </div>

                                <!-- Quantity selector -->
                                <div class="flex items-center gap-1.5">
                                    <button
                                        class="flex size-7 items-center justify-center rounded border border-sidebar-border/70 text-sm font-medium transition-colors hover:bg-muted disabled:opacity-40"
                                        :disabled="entry.quantity === 0"
                                        @click="entry.quantity = Math.max(0, entry.quantity - 1)"
                                    >−</button>
                                    <span class="w-6 text-center text-sm font-semibold">{{ entry.quantity }}</span>
                                    <button
                                        class="flex size-7 items-center justify-center rounded border border-sidebar-border/70 text-sm font-medium transition-colors hover:bg-muted disabled:opacity-40"
                                        :disabled="entry.quantity >= entry.max"
                                        @click="entry.quantity = Math.min(entry.max, entry.quantity + 1)"
                                    >+</button>
                                </div>
                            </div>
                        </div>

                        <div v-if="splitSelected.length > 0" class="rounded-lg bg-miralto-beige/50 px-3 py-2 text-sm">
                            <span class="text-muted-foreground">Total nueva orden:</span>
                            <span class="ml-2 font-bold text-miralto-verde">{{ formatCOP(splitTotal) }}</span>
                        </div>

                        <DialogFooter>
                            <Button variant="ghost" @click="splitOpen = false">Cancelar</Button>
                            <Button
                                class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                                :disabled="splitSelected.length === 0 || splitProcessing"
                                @click="submitSplit"
                            >
                                {{ splitProcessing ? 'Dividiendo…' : 'Confirmar división' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Link :href="OrderController.edit.url({ order: order.id })">
                    <Button variant="outline" class="gap-2"><Pencil class="size-4" />Editar</Button>
                </Link>

                <!-- Admin-only delete -->
                <Button
                    v-if="isAdmin"
                    variant="ghost"
                    class="gap-2 text-destructive hover:bg-destructive/10"
                    @click="deleteOrder"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

            <!-- Items table (2/3) -->
            <div class="overflow-hidden rounded-xl border border-sidebar-border/70 lg:col-span-2">
                <div class="flex items-center justify-between border-b border-sidebar-border/70 bg-muted/30 px-4 py-3">
                    <h2 class="font-semibold">Productos</h2>
                    <span class="text-sm text-muted-foreground">{{ order.items?.length ?? 0 }} ítem(s)</span>
                </div>

                <table class="w-full text-sm">
                    <thead class="border-b border-sidebar-border/40">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Producto</th>
                            <th class="hidden px-4 py-2.5 text-left font-medium text-muted-foreground sm:table-cell">Categoría</th>
                            <th class="px-4 py-2.5 text-center font-medium text-muted-foreground">Cant.</th>
                            <th class="px-4 py-2.5 text-right font-medium text-muted-foreground">P. Unit.</th>
                            <th class="px-4 py-2.5 text-right font-medium text-muted-foreground">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in order.items" :key="item.id" class="border-b border-sidebar-border/30 last:border-0">
                            <td class="px-4 py-3 font-medium">{{ item.product?.name ?? 'Producto eliminado' }}</td>
                            <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">{{ item.product?.category?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">{{ item.quantity }}</td>
                            <td class="px-4 py-3 text-right text-muted-foreground">{{ formatCOP(item.price) }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ formatCOP(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-sidebar-border/70 bg-muted/20">
                            <td colspan="4" class="px-4 py-3 text-right font-semibold">Total</td>
                            <td class="px-4 py-3 text-right text-lg font-bold text-miralto-verde">{{ formatCOP(order.total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Details sidebar (1/3) -->
            <div class="flex flex-col gap-4">

                <!-- Order summary -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                    <h3 class="mb-3 font-semibold">Resumen</h3>

                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">Número</dt>
                            <dd class="font-mono font-semibold">#{{ order.id }}</dd>
                        </div>

                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">Estado</dt>
                            <dd>
                                <Badge :class="currentStatus.class" variant="outline">
                                    <component :is="currentStatus.icon" class="size-3" />
                                    {{ currentStatus.label }}
                                </Badge>
                            </dd>
                        </div>

                        <!-- Payment info: single or split -->
                        <template v-if="hasSplitPayment">
                            <div class="space-y-1.5 border-t border-sidebar-border/40 pt-3">
                                <p class="text-xs font-medium text-muted-foreground">Pago dividido</p>
                                <div class="flex items-center justify-between rounded-md bg-muted/40 px-2 py-1.5 text-xs">
                                    <span>{{ paymentLabels[order.payment_method!] }}</span>
                                    <span class="font-semibold">{{ formatCOP(order.payment_amount_1!) }}</span>
                                </div>
                                <div class="flex items-center justify-between rounded-md bg-muted/40 px-2 py-1.5 text-xs">
                                    <span>{{ paymentLabels[order.payment_method_2!] }}</span>
                                    <span class="font-semibold">{{ formatCOP(order.payment_amount_2!) }}</span>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex items-center justify-between">
                                <dt class="text-muted-foreground">Pago</dt>
                                <dd>{{ order.payment_method ? paymentLabels[order.payment_method] : '—' }}</dd>
                            </div>
                        </template>

                        <div class="flex items-center justify-between border-t border-sidebar-border/40 pt-3">
                            <dt class="font-semibold">Total</dt>
                            <dd class="text-base font-bold text-miralto-verde">{{ formatCOP(order.total) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Meta info -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                    <h3 class="mb-3 font-semibold">Información</h3>
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Registrado por</dt>
                            <dd class="mt-0.5 font-medium">{{ order.user?.name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Fecha de creación</dt>
                            <dd class="mt-0.5">{{ formatDate(order.created_at) }}</dd>
                        </div>
                        <div v-if="order.notes">
                            <dt class="text-muted-foreground">Notas</dt>
                            <dd class="mt-0.5 rounded-md bg-muted/50 p-2 text-xs italic">{{ order.notes }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Audit log -->
                <div v-if="order.logs && order.logs.length > 0" class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <History class="size-4 text-muted-foreground" />
                        <h3 class="font-semibold">Historial</h3>
                    </div>
                    <ol class="relative ml-2 space-y-3 border-l border-sidebar-border/60 pl-4">
                        <li v-for="log in order.logs" :key="log.id" class="text-sm">
                            <span class="absolute -left-1.5 mt-1.5 size-3 rounded-full border-2 border-background bg-miralto-verde" />
                            <p class="font-medium">{{ log.description }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(log.created_at) }}
                                <template v-if="log.user">· {{ log.user.name }}</template>
                            </p>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</template>
