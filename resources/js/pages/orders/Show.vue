<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Clock, History, Pencil, Plus, Scissors, Trash2, XCircle } from 'lucide-vue-next';
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
import type { Category, Order, OrderItem, Product, Wallet } from '@/types';
import { index } from '@/routes/orders';

type Props = { order: Order; categories: Category[]; wallets: Wallet[] };

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

// ── Pay dialog (with optional service charge) ─────────────────
const payDialogOpen = ref(false);
const payProcessing = ref(false);
const includeService = ref(false);
const serviceMode = ref<'percentage' | 'fixed'>('percentage');
const servicePercentage = ref(10);
const serviceFixedAmount = ref<number>(0);
const paymentMethod = ref<'cash' | 'transfer' | 'card'>(
    (props.order.payment_method as 'cash' | 'transfer' | 'card') ?? 'cash',
);
const selectedWalletId1 = ref<number | null>(null);

const itemsSubtotal = computed(() => Number(props.order.total));
const serviceAmount = computed(() => {
    if (!includeService.value) return 0;
    if (serviceMode.value === 'percentage') {
        return Math.round(itemsSubtotal.value * servicePercentage.value / 100);
    }
    return Math.round(serviceFixedAmount.value);
});
const grandTotal = computed(() => itemsSubtotal.value + serviceAmount.value);

function confirmPay() {
    payProcessing.value = true;
    router.patch(
        OrderController.update.url({ order: props.order.id }),
        {
            status: 'paid',
            service_charge: includeService.value,
            service_charge_percentage: (includeService.value && serviceMode.value === 'percentage') ? servicePercentage.value : null,
            service_charge_custom_amount: (includeService.value && serviceMode.value === 'fixed') ? serviceFixedAmount.value : null,
            payment_method: paymentMethod.value,
            payment_amount_1: grandTotal.value,
            payment_method_2: null,
            payment_amount_2: null,
            wallet_id_1: paymentMethod.value === 'transfer' ? selectedWalletId1.value : null,
            notes: props.order.notes,
        },
        {
            onFinish: () => { payProcessing.value = false; payDialogOpen.value = false; },
        },
    );
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

// ── Add items dialog ─────────────────────────────────────────
const addItemsOpen = ref(false);
const addItemsProcessing = ref(false);
const searchQuery = ref('');

type CartEntry = { product: Product; quantity: number };
const cart = ref<CartEntry[]>([]);
const selectedCategoryId = ref<number | null>(null);

const selectedCategory = computed(() =>
    props.categories.find((c) => c.id === selectedCategoryId.value) ?? props.categories[0] ?? null,
);

const allProducts = computed(() =>
    props.categories.flatMap((c) => c.active_products ?? []),
);

const visibleProducts = computed(() => {

    const q = searchQuery.value.trim().toLowerCase();
   
    if (q) {
        return allProducts.value.filter((p) => p.name.toLowerCase().includes(q));
    }
    return selectedCategory.value?.active_products ?? [];
});

function cartQuantity(productId: number): number {
    return cart.value.find((e) => e.product.id === productId)?.quantity ?? 0;
}

function addToCart(product: Product) {
    const entry = cart.value.find((e) => e.product.id === product.id);
    if (entry) {
        entry.quantity++;
    } else {
        cart.value.push({ product, quantity: 1 });
    }
}

function removeFromCart(product: Product) {
    const idx = cart.value.findIndex((e) => e.product.id === product.id);
    if (idx === -1) return;
    if (cart.value[idx].quantity > 1) {
        cart.value[idx].quantity--;
    } else {
        cart.value.splice(idx, 1);
    }
}

const cartTotal = computed(() =>
    cart.value.reduce((sum, e) => sum + Number(e.product.price) * e.quantity, 0),
);

function submitAddItems() {
    if (cart.value.length === 0) return;
    addItemsProcessing.value = true;
    router.post(
        `/orders/${props.order.id}/items`,
        { items: cart.value.map((e) => ({ product_id: e.product.id, quantity: e.quantity })) },
        {
            onSuccess: () => { addItemsOpen.value = false; cart.value = []; },
            onFinish: () => { addItemsProcessing.value = false; },
        },
    );
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
                <!-- Pay dialog -->
                <Dialog v-if="order.status === 'pending'" v-model:open="payDialogOpen">
                    <DialogTrigger as-child>
                        <Button class="gap-2 bg-green-600 text-white hover:bg-green-700">
                            <CheckCircle class="size-4" />Marcar como pagado
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-sm">
                        <DialogHeader>
                            <DialogTitle>Confirmar pago — Orden #{{ order.id }}</DialogTitle>
                            <DialogDescription>Revisa el total antes de registrar el pago.</DialogDescription>
                        </DialogHeader>

                        <div class="space-y-4 py-1">
                            <!-- Service charge toggle -->
                            <div
                                class="rounded-lg border transition-colors"
                                :class="includeService ? 'border-miralto-verde bg-miralto-verde/5' : 'border-sidebar-border/70'"
                            >
                                <div
                                    class="flex cursor-pointer items-center justify-between p-3"
                                    @click="includeService = !includeService"
                                >
                                    <div>
                                        <p class="text-sm font-medium">Cargo por servicio / propina</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ includeService
                                                ? serviceMode === 'percentage'
                                                    ? `${servicePercentage}% = ${formatCOP(serviceAmount)}`
                                                    : `Monto fijo ${formatCOP(serviceFixedAmount)}`
                                                : 'Toca para incluir' }}
                                        </p>
                                    </div>
                                    <div
                                        class="flex size-5 items-center justify-center rounded-full border-2 transition-colors"
                                        :class="includeService ? 'border-miralto-verde bg-miralto-verde' : 'border-muted-foreground/40'"
                                    >
                                        <CheckCircle v-if="includeService" class="size-3.5 text-white" />
                                    </div>
                                </div>
                                <div v-if="includeService" class="space-y-3 border-t border-miralto-verde/20 px-3 pb-3 pt-2">
                                    <!-- Mode toggle -->
                                    <div class="grid grid-cols-2 gap-1 rounded-md border border-input p-1 text-xs">
                                        <button
                                            type="button"
                                            class="rounded py-1.5 font-medium transition-colors"
                                            :class="serviceMode === 'percentage' ? 'bg-miralto-verde text-white' : 'text-muted-foreground hover:bg-muted'"
                                            @click.stop="serviceMode = 'percentage'"
                                        >Porcentaje</button>
                                        <button
                                            type="button"
                                            class="rounded py-1.5 font-medium transition-colors"
                                            :class="serviceMode === 'fixed' ? 'bg-miralto-verde text-white' : 'text-muted-foreground hover:bg-muted'"
                                            @click.stop="serviceMode = 'fixed'"
                                        >Valor fijo</button>
                                    </div>
                                    <!-- Percentage input -->
                                    <div v-if="serviceMode === 'percentage'" class="flex items-center gap-2">
                                        <input
                                            v-model.number="servicePercentage"
                                            type="number"
                                            min="1"
                                            max="100"
                                            step="1"
                                            class="h-8 w-20 rounded-md border border-input bg-background px-2 text-right text-sm focus:border-ring focus:outline-none"
                                            @click.stop
                                        />
                                        <span class="text-sm text-muted-foreground">%</span>
                                    </div>
                                    <!-- Fixed amount input -->
                                    <div v-else>
                                        <input
                                            v-model.number="serviceFixedAmount"
                                            type="number"
                                            min="0"
                                            step="100"
                                            placeholder="Ej: 5000"
                                            class="h-8 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                            @click.stop
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Breakdown -->
                            <div class="space-y-2 rounded-lg bg-muted/40 p-3 text-sm">
                                <div class="flex justify-between text-muted-foreground">
                                    <span>Subtotal</span>
                                    <span>{{ formatCOP(itemsSubtotal) }}</span>
                                </div>
                                <div v-if="includeService" class="flex justify-between text-miralto-marron">
                                    <span>Servicio ({{ servicePercentage }}%)</span>
                                    <span>+ {{ formatCOP(serviceAmount) }}</span>
                                </div>
                                <div class="flex justify-between border-t border-sidebar-border/50 pt-2 font-bold">
                                    <span>Total a cobrar</span>
                                    <span class="text-base text-miralto-verde">{{ formatCOP(grandTotal) }}</span>
                                </div>
                            </div>

                            <!-- Payment method -->
                            <div class="space-y-1.5">
                                <p class="text-sm font-medium">Método de pago</p>
                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        v-for="method in (['cash', 'transfer', 'card'] as const)"
                                        :key="method"
                                        class="rounded-lg border py-2 text-xs font-medium transition-colors"
                                        :class="paymentMethod === method
                                            ? 'border-miralto-verde bg-miralto-verde text-white'
                                            : 'border-sidebar-border/70 hover:bg-muted/50'"
                                        @click="paymentMethod = method; selectedWalletId1 = null"
                                    >
                                        {{ method === 'cash' ? 'Efectivo' : method === 'transfer' ? 'Transferencia' : 'Tarjeta' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Wallet selector (transfer only) -->
                            <div v-if="paymentMethod === 'transfer' && wallets.length > 0" class="space-y-1.5">
                                <p class="text-sm font-medium">Billetera de destino</p>
                                <select
                                    v-model="selectedWalletId1"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                >
                                    <option :value="null">Sin billetera específica</option>
                                    <option v-for="wallet in wallets" :key="wallet.id" :value="wallet.id">
                                        {{ wallet.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <DialogFooter>
                            <Button variant="ghost" @click="payDialogOpen = false">Cancelar</Button>
                            <Button
                                class="bg-green-600 text-white hover:bg-green-700"
                                :disabled="payProcessing"
                                @click="confirmPay"
                            >
                                {{ payProcessing ? 'Guardando…' : `Cobrar ${formatCOP(grandTotal)}` }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Button v-if="order.status === 'pending'" variant="outline" class="gap-2 text-destructive hover:bg-destructive/10 hover:text-destructive" @click="markAsCancelled">
                    <XCircle class="size-4" />Cancelar
                </Button>

                <!-- Add items dialog -->
                <Dialog v-if="order.status === 'pending'" v-model:open="addItemsOpen">
                    <DialogTrigger as-child>
                        <Button class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                            <Plus class="size-4" />Agregar productos
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Agregar productos — Orden #{{ order.id }}</DialogTitle>
                            <DialogDescription>Selecciona los productos a agregar. El total se recalculará automáticamente.</DialogDescription>
                        </DialogHeader>

                        <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-hidden">
                            <!-- Search -->
                            <input
                                v-model="searchQuery"
                                type="search"
                                placeholder="Buscar producto…"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none"
                            />
                            <!-- Category tabs (hidden when searching) -->
                            <div v-if="!searchQuery.trim()" class="flex gap-1.5 overflow-x-auto pb-1">
                                <button
                                    v-for="cat in categories"
                                    :key="cat.id"
                                    class="shrink-0 rounded-full px-3 py-1 text-xs font-medium transition-colors"
                                    :class="selectedCategory?.id === cat.id
                                        ? 'bg-miralto-verde text-white'
                                        : 'border border-sidebar-border/70 hover:bg-muted'"
                                    @click="selectedCategoryId = cat.id"
                                >
                                    {{ cat.name }}
                                </button>
                            </div>

                            <!-- Product grid -->
                            <div class="grid min-h-0 flex-1 grid-cols-2 gap-2 overflow-y-auto sm:grid-cols-3">
                                <button
                                    v-for="product in visibleProducts"
                                    :key="product.id"
                                    class="relative flex flex-col items-start gap-1 rounded-lg border p-3 text-left transition-all active:scale-95"
                                    :class="cartQuantity(product.id) > 0
                                        ? 'border-miralto-verde bg-miralto-verde/5'
                                        : 'border-sidebar-border/70 hover:bg-muted/50'"
                                    @click="addToCart(product)"
                                >
                                    <span class="text-sm font-medium leading-tight">{{ product.name }}</span>
                                    <span class="text-xs text-miralto-verde font-semibold">{{ formatCOP(product.price) }}</span>
                                    <span
                                        v-if="cartQuantity(product.id) > 0"
                                        class="absolute right-2 top-2 flex size-5 items-center justify-center rounded-full bg-miralto-verde text-[10px] font-bold text-white"
                                    >
                                        {{ cartQuantity(product.id) }}
                                    </span>
                                </button>
                                <div v-if="visibleProducts.length === 0" class="col-span-full py-6 text-center text-sm text-muted-foreground">
                                    Sin productos activos en esta categoría.
                                </div>
                            </div>

                            <!-- Cart -->
                            <div v-if="cart.length > 0" class="shrink-0 space-y-1.5 border-t border-sidebar-border/70 pt-3">
                                <p class="text-xs font-semibold text-muted-foreground">Carrito</p>
                                <div class="space-y-1 max-h-32 overflow-y-auto">
                                    <div
                                        v-for="entry in cart"
                                        :key="entry.product.id"
                                        class="flex items-center justify-between gap-2 rounded-md bg-muted/40 px-2 py-1.5"
                                    >
                                        <span class="min-w-0 truncate text-sm">{{ entry.product.name }}</span>
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <button
                                                class="flex size-5 items-center justify-center rounded border text-xs hover:bg-muted"
                                                @click.stop="removeFromCart(entry.product)"
                                            >−</button>
                                            <span class="w-4 text-center text-sm font-semibold">{{ entry.quantity }}</span>
                                            <button
                                                class="flex size-5 items-center justify-center rounded border text-xs hover:bg-muted"
                                                @click.stop="addToCart(entry.product)"
                                            >+</button>
                                            <span class="w-20 text-right text-xs text-muted-foreground">
                                                {{ formatCOP(Number(entry.product.price) * entry.quantity) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-between pt-1 text-sm font-bold">
                                    <span>Total a agregar</span>
                                    <span class="text-miralto-verde">{{ formatCOP(cartTotal) }}</span>
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="shrink-0">
                            <Button variant="ghost" @click="addItemsOpen = false; cart = []; searchQuery = ''">Cancelar</Button>
                            <Button
                                class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                                :disabled="cart.length === 0 || addItemsProcessing"
                                @click="submitAddItems"
                            >
                                {{ addItemsProcessing ? 'Guardando…' : `Agregar ${cart.length > 0 ? cart.reduce((s,e)=>s+e.quantity,0) + ' producto(s)' : ''}` }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

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

                <Link v-if="isAdmin || order.status !== 'paid'" :href="OrderController.edit.url({ order: order.id })">
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

                        <div v-if="order.service_charge" class="flex items-center justify-between text-miralto-marron">
                            <dt class="text-sm">
                                {{ order.service_charge_percentage !== null ? `Servicio (${order.service_charge_percentage}%)` : 'Propina' }}
                            </dt>
                            <dd class="text-sm font-medium">+ {{ formatCOP(order.service_charge_amount ?? 0) }}</dd>
                        </div>

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
