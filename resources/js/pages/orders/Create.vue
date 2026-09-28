<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Minus, Plus, ShoppingCart, Trash2, SplitSquareHorizontal } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import * as OrderController from '@/actions/App/Http/Controllers/OrderController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import type { Category } from '@/types';
import { index, create } from '@/routes/orders';

type TableOption = { id: number; name: string; zone: string | null; capacity: number | null };
type EmployeeOption = { id: number; name: string; position: string | null };
type Props = { categories: Category[]; tables: TableOption[]; employees: EmployeeOption[] };

type CartItem = {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
};

type FormErrors = Partial<Record<string, string>>;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Órdenes', href: index() },
            { title: 'Nueva orden', href: create() },
        ],
    },
});

const PAYMENT_METHODS = [
    { value: 'cash', label: 'Efectivo' },
    { value: 'transfer', label: 'Transf.' },
    { value: 'card', label: 'Tarjeta' },
] as const;

// Cart state
const cart = ref<CartItem[]>([]);
const activeCategory = ref<number | null>(props.categories[0]?.id ?? null);

// Payment state
const paymentMethod = ref<string>('cash');
const useSplitPayment = ref(false);
const paymentAmount1 = ref<number>(0);
const paymentMethod2 = ref<string>('');
const paymentAmount2 = computed(() =>
    useSplitPayment.value ? Math.max(0, total.value - paymentAmount1.value) : 0,
);

// Form state
const selectedTableId = ref<number | null>(null);
const freeTableName = ref('');
const selectedEmployeeId = ref<number | null>(null);
const notes = ref('');
const processing = ref(false);
const errors = ref<FormErrors>({});

// Computed totals
const total = computed(() =>
    cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0),
);
const cartItemCount = computed(() =>
    cart.value.reduce((sum, item) => sum + item.quantity, 0),
);

// When total changes, reset payment amount 1 to total
watch(total, (val) => {
    paymentAmount1.value = val;
});

// When split payment is toggled off, reset secondary method
watch(useSplitPayment, (val) => {
    if (!val) {
        paymentMethod2.value = '';
    } else {
        paymentAmount1.value = total.value;
    }
});

// Cart operations
function addToCart(product: { id: number; name: string; price: string }) {
    const existing = cart.value.find((i) => i.product_id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({ product_id: product.id, name: product.name, price: Number(product.price), quantity: 1 });
    }
}

function increment(item: CartItem) { item.quantity++; }

function decrement(item: CartItem) {
    if (item.quantity > 1) { item.quantity--; } else { removeFromCart(item.product_id); }
}

function removeFromCart(productId: number) {
    cart.value = cart.value.filter((i) => i.product_id !== productId);
}

function submit() {
    if (cart.value.length === 0) {
        errors.value = { items: 'Debes agregar al menos un producto.' };
        return;
    }
    if (useSplitPayment.value && !paymentMethod2.value) {
        errors.value = { payment_method_2: 'Selecciona el segundo método de pago.' };
        return;
    }

    processing.value = true;
    errors.value = {};

    router.post(
        OrderController.store.url(),
        {
            table_id: selectedTableId.value || null,
            table_name: selectedTableId.value ? null : freeTableName.value.trim() || null,
            employee_id: selectedEmployeeId.value,
            items: cart.value.map((i) => ({ product_id: i.product_id, quantity: i.quantity })),
            payment_method: paymentMethod.value || null,
            payment_amount_1: useSplitPayment.value ? paymentAmount1.value : null,
            payment_method_2: useSplitPayment.value ? paymentMethod2.value : null,
            payment_amount_2: useSplitPayment.value ? paymentAmount2.value : null,
            notes: notes.value || null,
        },
        {
            onError: (e) => { errors.value = e; processing.value = false; },
            onFinish: () => { processing.value = false; },
        },
    );
}

function formatCOP(value: number): string {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
}

const searchQuery = ref('');

const activeProducts = computed(() => {
    // Productos base de la categoría activa
    const products =
        props.categories.find((c) => c.id === activeCategory.value)
            ?.active_products ?? [];

    // Texto buscado
    const q = searchQuery.value.trim().toLowerCase();

    // Si no hay búsqueda, devolver todo
    if (!q) return products;

    // Filtrar
    return products.filter((p) => {
        return (
            p.name.toLowerCase().includes(q) ||
            (p.description?.toLowerCase().includes(q) ?? false)
        );
    });
});

const availableSecondMethods = computed(() =>
    PAYMENT_METHODS.filter((m) => m.value !== paymentMethod.value),
);

</script>

<template>
    <Head title="Nueva orden" />

    <div class="flex h-full flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Nueva Orden</h1>

        <!-- Search -->
        <input
            v-model="searchQuery"
            type="search"
            placeholder="Buscar producto…"
            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none"
        />

        <div class="grid flex-1 grid-cols-1 gap-4 lg:grid-cols-5">

            <!-- ── Left: Product catalog ──────────────────────────── -->
            <div class="flex flex-col gap-3 lg:col-span-3">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                        :class="activeCategory === cat.id
                            ? 'bg-miralto-verde text-white shadow-sm'
                            : 'border border-sidebar-border/70 bg-card hover:bg-muted'"
                        @click="activeCategory = cat.id"
                    >{{ cat.name }}</button>
                </div>

                <div v-if="activeProducts.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <button
                        v-for="product in activeProducts"
                        :key="product.id"
                        class="group relative flex flex-col items-start gap-1 rounded-xl border border-sidebar-border/70 bg-card p-3 text-left transition-all hover:border-miralto-verde/50 hover:shadow-md"
                        @click="addToCart(product)"
                    >
                        <div class="absolute top-2 right-2 flex size-6 items-center justify-center rounded-full bg-miralto-verde/10 opacity-0 transition-opacity group-hover:opacity-100">
                            <Plus class="size-3 text-miralto-verde" />
                        </div>
                        <span class="line-clamp-2 text-sm font-medium leading-tight">{{ product.name }}</span>
                        <span class="text-base font-bold text-miralto-marron">{{ formatCOP(Number(product.price)) }}</span>
                        <span v-if="product.description" class="line-clamp-2 text-xs text-muted-foreground">{{ product.description }}</span>
                    </button>
                </div>

                <div v-else class="flex flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 py-12 text-muted-foreground">
                    <ShoppingCart class="mb-2 size-8 opacity-30" />
                    <p class="text-sm">No hay productos en esta categoría.</p>
                </div>
            </div>

            <!-- ── Right: Cart + checkout ─────────────────────────── -->
            <div class="flex flex-col gap-4 lg:col-span-2">

                <!-- Cart -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card">
                    <div class="flex items-center justify-between border-b border-sidebar-border/70 px-4 py-3">
                        <div class="flex items-center gap-2">
                            <ShoppingCart class="size-4 text-miralto-verde" />
                            <span class="font-medium">Orden actual</span>
                        </div>
                        <span v-if="cartItemCount > 0" class="flex size-5 items-center justify-center rounded-full bg-miralto-verde text-xs font-bold text-white">{{ cartItemCount }}</span>
                    </div>

                    <div class="flex flex-col divide-y divide-sidebar-border/40">
                        <div v-if="cart.length === 0" class="flex flex-col items-center justify-center py-8 text-muted-foreground">
                            <ShoppingCart class="mb-2 size-7 opacity-30" />
                            <p class="text-sm">Selecciona productos del catálogo</p>
                        </div>

                        <div v-for="item in cart" :key="item.product_id" class="flex items-center gap-3 px-4 py-2.5">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ item.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatCOP(item.price) }} c/u</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button class="flex size-6 items-center justify-center rounded border border-sidebar-border/70 text-muted-foreground transition-colors hover:bg-muted" @click="decrement(item)">
                                    <Minus class="size-3" />
                                </button>
                                <span class="w-7 text-center text-sm font-semibold">{{ item.quantity }}</span>
                                <button class="flex size-6 items-center justify-center rounded border border-sidebar-border/70 text-muted-foreground transition-colors hover:bg-muted" @click="increment(item)">
                                    <Plus class="size-3" />
                                </button>
                            </div>
                            <span class="w-20 text-right text-sm font-semibold">{{ formatCOP(item.price * item.quantity) }}</span>
                            <button class="text-muted-foreground transition-colors hover:text-destructive" @click="removeFromCart(item.product_id)">
                                <Trash2 class="size-3.5" />
                            </button>
                        </div>
                    </div>

                    <div v-if="cart.length > 0" class="flex items-center justify-between border-t border-sidebar-border/70 px-4 py-3">
                        <span class="font-semibold">Total</span>
                        <span class="text-lg font-bold text-miralto-verde">{{ formatCOP(total) }}</span>
                    </div>
                </div>

                <!-- Checkout form -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                    <h3 class="mb-3 font-medium">Datos del pago</h3>

                    <div class="space-y-4">
                        <!-- Primary payment method -->
                        <div>
                            <label class="mb-1.5 block text-sm text-muted-foreground">
                                {{ useSplitPayment ? 'Primer método' : 'Método de pago' }}
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="method in PAYMENT_METHODS"
                                    :key="method.value"
                                    type="button"
                                    class="rounded-lg border py-2 text-sm font-medium transition-colors"
                                    :class="paymentMethod === method.value
                                        ? 'border-miralto-verde bg-miralto-verde/10 text-miralto-verde'
                                        : 'border-sidebar-border/70 hover:bg-muted'"
                                    @click="paymentMethod = method.value"
                                >{{ method.label }}</button>
                            </div>
                        </div>

                        <!-- Split payment toggle -->
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors"
                            :class="useSplitPayment
                                ? 'border-miralto-marron/50 bg-miralto-marron/10 text-miralto-marron'
                                : 'border-sidebar-border/70 text-muted-foreground hover:bg-muted'"
                            @click="useSplitPayment = !useSplitPayment"
                        >
                            <SplitSquareHorizontal class="size-4" />
                            {{ useSplitPayment ? 'Cancelar división de pago' : 'Dividir pago entre dos métodos' }}
                        </button>

                        <!-- Split payment section -->
                        <div v-if="useSplitPayment" class="space-y-3 rounded-lg border border-miralto-marron/20 bg-miralto-beige/30 p-3">
                            <!-- Amount method 1 -->
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted-foreground">
                                    Monto con {{ PAYMENT_METHODS.find(m => m.value === paymentMethod)?.label ?? paymentMethod }}
                                </label>
                                <input
                                    v-model.number="paymentAmount1"
                                    type="number"
                                    :min="0"
                                    :max="total"
                                    step="100"
                                    class="h-8 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    Resta: <span :class="paymentAmount2 < 0 ? 'text-destructive font-semibold' : ''">{{ formatCOP(paymentAmount2) }}</span>
                                </p>
                            </div>

                            <!-- Second payment method -->
                            <div>
                                <label class="mb-1 block text-xs font-medium text-muted-foreground">Segundo método</label>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <button
                                        v-for="method in availableSecondMethods"
                                        :key="method.value"
                                        type="button"
                                        class="rounded-lg border py-1.5 text-sm font-medium transition-colors"
                                        :class="paymentMethod2 === method.value
                                            ? 'border-miralto-marron bg-miralto-marron/10 text-miralto-marron'
                                            : 'border-sidebar-border/70 hover:bg-muted'"
                                        @click="paymentMethod2 = method.value"
                                    >{{ method.label }}: {{ formatCOP(paymentAmount2) }}</button>
                                </div>
                                <InputError v-if="errors.payment_method_2" :message="errors.payment_method_2" />
                            </div>
                        </div>

                        <!-- Table selector -->
                        <div>
                            <label class="mb-1.5 block text-sm text-muted-foreground" for="table_name">Mesa (opcional)</label>
                            <div v-if="tables.length > 0" class="mb-2 flex flex-wrap gap-1.5">
                                <button
                                    v-for="t in tables"
                                    :key="t.id"
                                    type="button"
                                    class="rounded-md border px-2.5 py-1 text-xs font-medium transition-colors"
                                    :class="selectedTableId === t.id
                                        ? 'border-miralto-verde bg-miralto-verde/10 text-miralto-verde'
                                        : 'border-sidebar-border/70 hover:border-miralto-verde/40'"
                                    @click="selectedTableId = selectedTableId === t.id ? null : t.id"
                                >
                                    {{ t.name }}<span v-if="t.zone" class="opacity-60"> · {{ t.zone }}</span>
                                </button>
                            </div>
                            <input
                                v-if="selectedTableId === null"
                                id="table_name"
                                v-model="freeTableName"
                                type="text"
                                maxlength="100"
                                placeholder="Ej. P1, C2…"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                            />
                            <InputError v-if="errors.table_name" :message="errors.table_name" />
                        </div>

                        <!-- Employee -->
                        <div v-if="employees.length > 0">
                            <label class="mb-1.5 block text-sm text-muted-foreground" for="employee_id">Empleado (opcional)</label>
                            <select
                                id="employee_id"
                                v-model="selectedEmployeeId"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                            >
                                <option :value="null">No es de empleado</option>
                                <option v-for="e in employees" :key="e.id" :value="e.id">
                                    {{ e.name }}<template v-if="e.position"> · {{ e.position }}</template>
                                </option>
                            </select>
                            <InputError v-if="errors.employee_id" :message="errors.employee_id" />
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="mb-1.5 block text-sm text-muted-foreground" for="notes">Notas (opcional)</label>
                            <textarea
                                id="notes"
                                v-model="notes"
                                rows="2"
                                placeholder="Observaciones sobre la orden…"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                            />
                        </div>

                        <InputError v-if="errors.items" :message="errors.items" />

                        <Button
                            class="w-full gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90"
                            :disabled="processing || cart.length === 0"
                            @click="submit"
                        >
                            <ShoppingCart class="size-4" />
                            {{ processing ? 'Creando orden…' : `Crear orden · ${formatCOP(total)}` }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
