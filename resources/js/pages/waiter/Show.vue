<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Minus, ShoppingCart, Printer, Lock, MessageSquare } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import * as WaiterController from '@/actions/App/Http/Controllers/WaiterController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger,
} from '@/components/ui/dialog';
import type { Category, Order, OrderItem } from '@/types';
import { index } from '@/routes/waiter';

type Props = {
    order: Order;
    itemsByPrinter: Record<string, OrderItem[]>;
    categories: Category[];
};

type AddCartItem = {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    notes: string;
};

const props = defineProps<Props>();

const activeCategory = ref<number | null>(props.categories[0]?.id ?? null);
const addDialog = ref(false);
const addCart = ref<AddCartItem[]>([]);
const processing = ref(false);

function formatCOP(value: number | string): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(Number(value));
}

function formatDate(dateStr: string): string {
    return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
    }).format(new Date(dateStr));
}

const isPending = computed(() => props.order.status === 'pending');

const printerGroups = computed(() => {
    return Object.entries(props.itemsByPrinter).map(([printerId, items]) => ({
        printerId,
        label: printerId === 'default' ? 'Impresora por defecto' : `Impresora #${printerId}`,
        items,
    }));
});

const addCartTotal = computed(() => addCart.value.reduce((s, i) => s + i.price * i.quantity, 0));

const activeProducts = computed(() => {
    if (!activeCategory.value) return [];
    return props.categories.find((c) => c.id === activeCategory.value)?.active_products ?? [];
});

function addToAddCart(product: { id: number; name: string; price: string }) {
    const existing = addCart.value.find((i) => i.product_id === product.id);
    if (existing) existing.quantity++;
    else addCart.value.push({
        product_id: product.id,
        name: product.name,
        price: Number(product.price),
        quantity: 1,
        notes: '',
    });
}

function increment(item: AddCartItem) { item.quantity++; }
function decrement(item: AddCartItem) {
    if (item.quantity > 1) item.quantity--;
    else addCart.value = addCart.value.filter((i) => i.product_id !== item.product_id);
}

function submitAdd() {
    if (addCart.value.length === 0) return;
    processing.value = true;
    router.post(
        WaiterController.addItems.url({ order: props.order.id }),
        {
            items: addCart.value.map((i) => ({
                product_id: i.product_id,
                quantity: i.quantity,
                notes: i.notes || null,
            })),
        },
        {
            onSuccess: () => {
                addDialog.value = false;
                addCart.value = [];
            },
            onFinish: () => { processing.value = false; },
        },
    );
}
</script>

<template>
    <Head :title="order.table_name ?? `Pedido #${order.id}`" />

    <div class="flex flex-col gap-4 p-4">

        <!-- Back -->
        <Link :href="index()" class="flex w-fit items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
            <ArrowLeft class="size-4" />
            Volver a pedidos
        </Link>

        <!-- Header card -->
        <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Mesa</p>
                    <h1 class="text-xl font-bold">{{ order.table_name ?? `Pedido #${order.id}` }}</h1>
                    <p class="text-xs text-muted-foreground">
                        #{{ order.id }} · {{ formatDate(order.created_at) }}
                    </p>
                </div>
                <Badge
                    v-if="isPending"
                    variant="outline"
                    class="border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                >Pendiente</Badge>
                <Badge
                    v-else-if="order.status === 'paid'"
                    variant="outline"
                    class="border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400"
                >Pagado</Badge>
                <Badge
                    v-else
                    variant="outline"
                    class="border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400"
                >Cancelado</Badge>
            </div>

            <div v-if="order.notes" class="mt-3 rounded-md bg-muted/50 p-2 text-xs italic">
                <MessageSquare class="mr-1 inline-block size-3" />
                {{ order.notes }}
            </div>
        </div>

        <!-- Items grouped by printer -->
        <div v-for="group in printerGroups" :key="group.printerId" class="rounded-xl border border-sidebar-border/70 bg-card">
            <div class="flex items-center gap-2 border-b border-sidebar-border/70 px-4 py-2.5">
                <Printer class="size-4 text-miralto-marron" />
                <h2 class="text-sm font-semibold">{{ group.label }}</h2>
                <span class="ml-auto text-xs text-muted-foreground">{{ group.items.length }} ítem(s)</span>
            </div>
            <div class="divide-y divide-sidebar-border/30">
                <div v-for="item in group.items" :key="item.id" class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-miralto-verde/10 text-xs font-bold text-miralto-verde">
                            {{ item.quantity }}
                        </span>
                        <p class="flex-1 text-sm font-medium">{{ item.product?.name ?? 'Producto' }}</p>
                        <span class="text-sm font-semibold tabular-nums">{{ formatCOP(item.subtotal) }}</span>
                    </div>
                    <p v-if="item.notes" class="mt-1 ml-8 rounded-md bg-miralto-marron/5 px-2 py-1 text-xs italic text-miralto-marron">
                        <MessageSquare class="mr-1 inline-block size-3" />
                        {{ item.notes }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Total -->
        <div class="rounded-xl border border-miralto-verde/30 bg-miralto-verde/5 p-4">
            <div class="flex items-center justify-between">
                <span class="font-semibold">Total</span>
                <span class="text-xl font-bold text-miralto-verde">{{ formatCOP(order.total) }}</span>
            </div>
        </div>

        <!-- Closed-state notice -->
        <div v-if="!isPending" class="flex items-start gap-2 rounded-lg border border-sidebar-border/70 bg-muted/30 p-3 text-xs text-muted-foreground">
            <Lock class="size-4 shrink-0" />
            <p>Este pedido ya no está pendiente. No se pueden agregar productos.</p>
        </div>

        <!-- Add more items dialog -->
        <Dialog v-if="isPending" v-model:open="addDialog">
            <DialogTrigger as-child>
                <Button
                    class="sticky bottom-16 h-12 w-full gap-2 bg-miralto-verde text-base font-semibold text-white hover:bg-miralto-verde/90"
                >
                    <Plus class="size-5" />
                    Agregar productos
                </Button>
            </DialogTrigger>
            <DialogContent class="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Agregar a {{ order.table_name ?? `pedido #${order.id}` }}</DialogTitle>
                    <DialogDescription>
                        Solo puedes agregar productos. Si te equivocas, cancela el pedido completo desde administración.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-3">
                    <!-- Category tabs -->
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            class="rounded-full px-3 py-1 text-xs font-medium transition-colors"
                            :class="activeCategory === cat.id
                                ? 'bg-miralto-verde text-white'
                                : 'border border-sidebar-border/70 hover:bg-muted'"
                            @click="activeCategory = cat.id"
                        >
                            {{ cat.name }}
                        </button>
                    </div>

                    <!-- Products -->
                    <div v-if="activeProducts.length > 0" class="grid max-h-56 grid-cols-2 gap-1.5 overflow-y-auto sm:grid-cols-3">
                        <button
                            v-for="product in activeProducts"
                            :key="product.id"
                            class="flex flex-col items-start gap-0.5 rounded-lg border border-sidebar-border/70 bg-card p-2 text-left transition-all hover:border-miralto-verde/50 active:scale-95"
                            @click="addToAddCart(product)"
                        >
                            <span class="line-clamp-2 text-xs font-medium leading-tight">{{ product.name }}</span>
                            <span class="text-xs font-bold text-miralto-marron">{{ formatCOP(Number(product.price)) }}</span>
                        </button>
                    </div>

                    <!-- Cart preview -->
                    <div v-if="addCart.length > 0" class="space-y-1.5 rounded-lg bg-miralto-beige/40 p-2">
                        <div
                            v-for="item in addCart"
                            :key="item.product_id"
                            class="space-y-1 rounded-md bg-card p-2"
                        >
                            <div class="flex items-center gap-2">
                                <p class="min-w-0 flex-1 truncate text-sm font-medium">{{ item.name }}</p>
                                <button class="flex size-6 items-center justify-center rounded border border-sidebar-border/70" @click="decrement(item)">
                                    <Minus class="size-3" />
                                </button>
                                <span class="w-6 text-center text-sm font-bold">{{ item.quantity }}</span>
                                <button class="flex size-6 items-center justify-center rounded border border-sidebar-border/70" @click="increment(item)">
                                    <Plus class="size-3" />
                                </button>
                                <span class="w-16 text-right text-xs font-semibold">{{ formatCOP(item.price * item.quantity) }}</span>
                            </div>
                            <input
                                v-model="item.notes"
                                type="text"
                                placeholder="Nota (opcional)…"
                                class="h-7 w-full rounded border border-sidebar-border/40 bg-background px-2 text-xs italic focus:border-miralto-marron focus:outline-none"
                            />
                        </div>
                        <div class="flex items-center justify-between border-t border-sidebar-border/40 px-2 pt-2 text-sm">
                            <span class="text-muted-foreground">Subtotal a agregar</span>
                            <span class="font-bold text-miralto-verde">{{ formatCOP(addCartTotal) }}</span>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="ghost" @click="addDialog = false">Cancelar</Button>
                    <Button
                        class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                        :disabled="addCart.length === 0 || processing"
                        @click="submitAdd"
                    >
                        <ShoppingCart class="size-4" />
                        {{ processing ? 'Agregando…' : 'Confirmar' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
