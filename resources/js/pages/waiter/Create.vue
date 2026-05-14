<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Minus, Plus, ShoppingCart, Trash2, MessageSquarePlus, Utensils } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import * as WaiterController from '@/actions/App/Http/Controllers/WaiterController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Category } from '@/types';
import { index } from '@/routes/waiter';

type Props = { categories: Category[] };

type CartItem = {
    product_id: number;
    name: string;
    price: number;
    quantity: number;
    notes: string;
    showNotes: boolean;
};

const props = defineProps<Props>();

const tableName = ref('');
const orderNotes = ref('');
const cart = ref<CartItem[]>([]);
const activeCategory = ref<number | null>(props.categories[0]?.id ?? null);
const processing = ref(false);
const errors = ref<Partial<Record<string, string>>>({});

const total = computed(() => cart.value.reduce((s, i) => s + i.price * i.quantity, 0));
const itemCount = computed(() => cart.value.reduce((s, i) => s + i.quantity, 0));

const activeProducts = computed(() => {
    if (!activeCategory.value) return [];
    return props.categories.find((c) => c.id === activeCategory.value)?.active_products ?? [];
});

function addToCart(product: { id: number; name: string; price: string }) {
    const existing = cart.value.find((i) => i.product_id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        cart.value.push({
            product_id: product.id,
            name: product.name,
            price: Number(product.price),
            quantity: 1,
            notes: '',
            showNotes: false,
        });
    }
}

function increment(item: CartItem) { item.quantity++; }

function decrement(item: CartItem) {
    if (item.quantity > 1) item.quantity--;
    else removeItem(item.product_id);
}

function removeItem(productId: number) {
    cart.value = cart.value.filter((i) => i.product_id !== productId);
}

function formatCOP(value: number): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(value);
}

function submit() {
    if (!tableName.value.trim()) {
        errors.value = { table_name: 'Indica el nombre/número de la mesa.' };
        return;
    }
    if (cart.value.length === 0) {
        errors.value = { items: 'Agrega al menos un producto.' };
        return;
    }

    processing.value = true;
    errors.value = {};

    router.post(
        WaiterController.store.url(),
        {
            table_name: tableName.value,
            notes: orderNotes.value || null,
            items: cart.value.map((i) => ({
                product_id: i.product_id,
                quantity: i.quantity,
                notes: i.notes || null,
            })),
        },
        {
            onError: (e) => { errors.value = e; processing.value = false; },
            onFinish: () => { processing.value = false; },
        },
    );
}
</script>

<template>
    <Head title="Nuevo pedido" />

    <div class="flex flex-col gap-4 p-4">

        <!-- Back -->
        <Link :href="index()" class="flex w-fit items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
            <ArrowLeft class="size-4" />
            Volver
        </Link>

        <!-- Table name -->
        <div class="space-y-1.5">
            <Label for="table">Mesa *</Label>
            <input
                id="table"
                v-model="tableName"
                type="text"
                placeholder="Ej. Mesa 5, Terraza 2, Don Carlos…"
                class="h-11 w-full rounded-lg border border-input bg-card px-3 text-base font-medium placeholder:text-muted-foreground focus:border-miralto-verde focus:outline-none focus:ring-2 focus:ring-miralto-verde/20"
            />
            <InputError :message="errors.table_name" />
        </div>

        <!-- Cart (sticky if items) -->
        <div v-if="cart.length > 0" class="sticky top-14 z-10 -mx-4 border-b border-sidebar-border/70 bg-card/95 px-4 py-3 backdrop-blur">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <ShoppingCart class="size-5 text-miralto-verde" />
                        <span class="absolute -right-2 -top-2 flex size-4 items-center justify-center rounded-full bg-miralto-verde text-[10px] font-bold text-white">
                            {{ itemCount }}
                        </span>
                    </div>
                    <span class="text-sm text-muted-foreground">Total</span>
                </div>
                <p class="text-lg font-bold text-miralto-verde">{{ formatCOP(total) }}</p>
            </div>

            <!-- Cart items list -->
            <div class="mt-3 max-h-72 space-y-1.5 overflow-y-auto">
                <div
                    v-for="item in cart"
                    :key="item.product_id"
                    class="rounded-lg border border-sidebar-border/40 bg-muted/20 p-2"
                >
                    <div class="flex items-center gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ item.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatCOP(item.price) }} c/u</p>
                        </div>
                        <button
                            class="flex size-6 items-center justify-center rounded border border-sidebar-border/70 transition-colors hover:bg-muted"
                            @click="decrement(item)"
                        >
                            <Minus class="size-3" />
                        </button>
                        <span class="w-6 text-center text-sm font-bold">{{ item.quantity }}</span>
                        <button
                            class="flex size-6 items-center justify-center rounded border border-sidebar-border/70 transition-colors hover:bg-muted"
                            @click="increment(item)"
                        >
                            <Plus class="size-3" />
                        </button>
                        <span class="w-20 shrink-0 text-right text-sm font-semibold">{{ formatCOP(item.price * item.quantity) }}</span>
                        <button class="text-muted-foreground transition-colors hover:text-destructive" @click="removeItem(item.product_id)">
                            <Trash2 class="size-3.5" />
                        </button>
                    </div>

                    <!-- Per-item notes -->
                    <button
                        v-if="!item.showNotes && !item.notes"
                        class="mt-1.5 flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-miralto-marron"
                        @click="item.showNotes = true"
                    >
                        <MessageSquarePlus class="size-3" />
                        Agregar nota
                    </button>
                    <input
                        v-if="item.showNotes || item.notes"
                        v-model="item.notes"
                        type="text"
                        placeholder="Ej. sin cebolla, término medio…"
                        class="mt-1.5 h-7 w-full rounded border border-miralto-marron/30 bg-miralto-marron/5 px-2 text-xs italic placeholder:text-muted-foreground focus:border-miralto-marron focus:outline-none"
                    />
                </div>
            </div>
        </div>

        <!-- Category tabs -->
        <div class="flex flex-wrap gap-2">
            <button
                v-for="cat in categories"
                :key="cat.id"
                class="rounded-full px-4 py-1.5 text-sm font-medium transition-colors"
                :class="activeCategory === cat.id
                    ? 'bg-miralto-verde text-white shadow-sm'
                    : 'border border-sidebar-border/70 bg-card hover:bg-muted'"
                @click="activeCategory = cat.id"
            >
                {{ cat.name }}
            </button>
        </div>

        <!-- Products grid -->
        <div v-if="activeProducts.length > 0" class="grid grid-cols-2 gap-2 sm:grid-cols-3">
            <button
                v-for="product in activeProducts"
                :key="product.id"
                class="flex flex-col items-start gap-1 rounded-xl border border-sidebar-border/70 bg-card p-3 text-left transition-all hover:border-miralto-verde/50 hover:shadow-sm active:scale-95"
                @click="addToCart(product)"
            >
                <span class="line-clamp-2 text-sm font-medium leading-tight">{{ product.name }}</span>
                <span class="mt-auto text-base font-bold text-miralto-marron">{{ formatCOP(Number(product.price)) }}</span>
            </button>
        </div>
        <div v-else class="flex flex-col items-center gap-2 rounded-xl border border-dashed border-sidebar-border/70 py-10 text-muted-foreground">
            <Utensils class="size-7 opacity-40" />
            <p class="text-sm">Sin productos en esta categoría</p>
        </div>

        <!-- Order-level notes -->
        <div class="space-y-1.5">
            <Label for="orderNotes">Nota general del pedido (opcional)</Label>
            <textarea
                id="orderNotes"
                v-model="orderNotes"
                rows="2"
                placeholder="Observación general…"
                class="w-full rounded-lg border border-input bg-card px-3 py-2 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
            />
        </div>

        <InputError v-if="errors.items" :message="errors.items" />

        <!-- Submit (sticky bottom area, accounting for tab bar) -->
        <div class="sticky bottom-16 -mx-4 border-t border-sidebar-border/70 bg-card/95 px-4 py-3 backdrop-blur">
            <Button
                class="h-12 w-full gap-2 bg-miralto-verde text-base font-semibold text-white hover:bg-miralto-verde/90"
                :disabled="processing || cart.length === 0"
                @click="submit"
            >
                <ShoppingCart class="size-5" />
                {{ processing ? 'Enviando…' : `Enviar pedido · ${formatCOP(total)}` }}
            </Button>
        </div>
    </div>
</template>
