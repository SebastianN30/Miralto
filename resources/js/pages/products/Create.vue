<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2, Sparkles, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import * as ProductController from '@/actions/App/Http/Controllers/ProductController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { Category, Ingredient } from '@/types';
import { index } from '@/routes/products';

type Props = {
    categories: Pick<Category, 'id' | 'name'>[];
    ingredients: Pick<Ingredient, 'id' | 'name' | 'unit' | 'cost_per_unit'>[];
};

type IngredientLine = {
    ingredient_id: number;
    name: string;
    unit: string;
    cost_per_unit: number;
    quantity: number;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Productos', href: '/products' },
            { title: 'Nuevo producto', href: '/products/create' },
        ],
    },
});

// Form state
const name = ref('');
const categoryId = ref<number | null>(null);
const description = ref('');
const stock = ref<number | null>(null);
const isActive = ref(true);
const price = ref<number>(0);
const processing = ref(false);
const errors = ref<Partial<Record<string, string>>>({});

// Ingredients
const ingredientLines = ref<IngredientLine[]>([]);
const selectedIngredientId = ref<number | ''>('');

// Pricing
const targetMargin = ref(65);

// New ingredient dialog
const newIngOpen = ref(false);
const newIngProcessing = ref(false);
const newIngName = ref('');
const newIngUnit = ref('unidad');
const newIngCost = ref<number>(0);
const newIngErrors = ref<Partial<Record<string, string>>>({});

const UNITS = ['unidad', 'porción', 'kg', 'g', 'litros', 'ml', 'taza', 'cucharada', 'cucharadita'];

// Master ingredient list (reactive so we can push new ones)
const ingredientsMaster = ref(props.ingredients.map((i) => ({ ...i, cost_per_unit: Number(i.cost_per_unit) })));

const availableIngredients = computed(() =>
    ingredientsMaster.value.filter((i) => !ingredientLines.value.some((l) => l.ingredient_id === i.id)),
);

// Computed costs / suggested price
const ingredientCost = computed(() =>
    ingredientLines.value.reduce((sum, l) => sum + l.cost_per_unit * l.quantity, 0),
);

const suggestedPrice = computed(() => {
    if (ingredientCost.value <= 0 || targetMargin.value >= 100) return 0;
    return ingredientCost.value / (1 - targetMargin.value / 100);
});

function addIngredientLine() {
    if (!selectedIngredientId.value) return;
    const ing = ingredientsMaster.value.find((i) => i.id === Number(selectedIngredientId.value));
    if (!ing) return;
    ingredientLines.value.push({ ingredient_id: ing.id, name: ing.name, unit: ing.unit, cost_per_unit: ing.cost_per_unit, quantity: 1 });
    selectedIngredientId.value = '';
}

function removeIngredientLine(idx: number) {
    ingredientLines.value.splice(idx, 1);
}

function applySuggestedPrice() {
    price.value = Math.ceil(suggestedPrice.value / 100) * 100;
}

// Watch for newIngredient flash after creating one
const page = usePage<{ flash?: { newIngredient?: { id: number; name: string; unit: string; cost_per_unit: string } } }>();

watch(
    () => page.props.flash?.newIngredient,
    (fresh) => {
        if (!fresh) return;
        const ing = { id: fresh.id, name: fresh.name, unit: fresh.unit, cost_per_unit: Number(fresh.cost_per_unit) };
        if (!ingredientsMaster.value.some((i) => i.id === ing.id)) {
            ingredientsMaster.value.push(ing);
        }
        ingredientLines.value.push({ ...ing, quantity: 1 });
    },
);

function submitNewIngredient() {
    newIngErrors.value = {};
    if (!newIngName.value.trim()) { newIngErrors.value.name = 'El nombre es requerido.'; return; }
    if (newIngCost.value <= 0) { newIngErrors.value.cost_per_unit = 'El costo debe ser mayor a 0.'; return; }

    newIngProcessing.value = true;
    router.post(
        '/ingredients',
        { name: newIngName.value, unit: newIngUnit.value, cost_per_unit: newIngCost.value },
        {
            preserveState: true,
            onSuccess: () => {
                newIngOpen.value = false;
                newIngName.value = '';
                newIngUnit.value = 'unidad';
                newIngCost.value = 0;
            },
            onError: (e) => { newIngErrors.value = e; },
            onFinish: () => { newIngProcessing.value = false; },
        },
    );
}

function formatCOP(value: number): string {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(value);
}

function submit() {
    processing.value = true;
    errors.value = {};

    router.post(
        ProductController.store.url(),
        {
            name: name.value,
            category_id: categoryId.value,
            description: description.value || null,
            price: price.value,
            cost: ingredientCost.value > 0 ? ingredientCost.value : null,
            stock: stock.value,
            is_active: isActive.value,
            ingredients: ingredientLines.value.map((l) => ({ ingredient_id: l.ingredient_id, quantity: l.quantity })),
        },
        {
            onError: (e) => { errors.value = e; processing.value = false; },
            onFinish: () => { processing.value = false; },
        },
    );
}
</script>

<template>
    <Head title="Nuevo producto" />

    <div class="flex flex-col gap-6 p-4">
        <Link :href="index()" class="flex w-fit items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
            <ArrowLeft class="size-4" />
            Volver a productos
        </Link>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">

            <!-- ── Left: info + insumos ─────────────────────────── -->
            <div class="flex flex-col gap-4 lg:col-span-3">

                <!-- Info básica -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5">
                    <h2 class="mb-4 font-semibold">Información del producto</h2>

                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <Label for="name">Nombre *</Label>
                            <input
                                id="name"
                                v-model="name"
                                type="text"
                                placeholder="Ej. Bandeja Paisa"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="space-y-1.5">
                            <Label>Categoría</Label>
                            <select
                                v-model="categoryId"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                            >
                                <option :value="null">Sin categoría</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <InputError :message="errors.category_id" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="description">Descripción</Label>
                            <textarea
                                id="description"
                                v-model="description"
                                rows="3"
                                placeholder="Descripción del plato…"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                            />
                        </div>
                    </div>
                </div>

                <!-- Insumos -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold">Insumos</h2>
                            <p class="text-xs text-muted-foreground">El costo total se calculará automáticamente</p>
                        </div>

                        <!-- Nuevo insumo dialog -->
                        <Dialog v-model:open="newIngOpen">
                            <DialogTrigger as-child>
                                <Button variant="outline" size="sm" class="gap-1.5 text-xs">
                                    <Plus class="size-3.5" />
                                    Nuevo insumo
                                </Button>
                            </DialogTrigger>
                            <DialogContent class="sm:max-w-sm">
                                <DialogHeader>
                                    <DialogTitle>Nuevo insumo</DialogTitle>
                                    <DialogDescription>Agrega un insumo al catálogo del restaurante.</DialogDescription>
                                </DialogHeader>
                                <div class="space-y-3 py-1">
                                    <div class="space-y-1.5">
                                        <Label>Nombre</Label>
                                        <input
                                            v-model="newIngName"
                                            type="text"
                                            placeholder="Ej. Fríjoles"
                                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                        />
                                        <InputError :message="newIngErrors.name" />
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label>Unidad</Label>
                                        <select
                                            v-model="newIngUnit"
                                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                                        >
                                            <option v-for="u in UNITS" :key="u" :value="u">{{ u }}</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label>Costo por unidad (COP)</Label>
                                        <input
                                            v-model.number="newIngCost"
                                            type="number"
                                            min="0"
                                            step="100"
                                            placeholder="0"
                                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                        />
                                        <InputError :message="newIngErrors.cost_per_unit" />
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button variant="ghost" @click="newIngOpen = false">Cancelar</Button>
                                    <Button
                                        class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                                        :disabled="newIngProcessing"
                                        @click="submitNewIngredient"
                                    >
                                        {{ newIngProcessing ? 'Guardando…' : 'Guardar insumo' }}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <!-- Ingredient selector -->
                    <div class="mb-3 flex gap-2">
                        <select
                            v-model="selectedIngredientId"
                            class="h-9 flex-1 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                        >
                            <option value="">— Seleccionar insumo —</option>
                            <option v-for="ing in availableIngredients" :key="ing.id" :value="ing.id">
                                {{ ing.name }} ({{ ing.unit }}) · {{ formatCOP(ing.cost_per_unit) }}/{{ ing.unit }}
                            </option>
                        </select>
                        <Button variant="outline" size="sm" :disabled="!selectedIngredientId" @click="addIngredientLine">
                            <Plus class="size-4" />
                        </Button>
                    </div>

                    <!-- Ingredient lines -->
                    <div v-if="ingredientLines.length" class="space-y-2">
                        <div
                            v-for="(line, idx) in ingredientLines"
                            :key="line.ingredient_id"
                            class="flex items-center gap-2 rounded-lg border border-sidebar-border/50 bg-muted/20 p-2.5"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">{{ line.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatCOP(line.cost_per_unit) }} / {{ line.unit }}</p>
                            </div>
                            <input
                                v-model.number="line.quantity"
                                type="number"
                                min="0.001"
                                step="0.1"
                                class="h-8 w-20 rounded-md border border-input bg-background px-2 text-center text-sm focus:border-ring focus:outline-none"
                            />
                            <span class="text-xs text-muted-foreground">{{ line.unit }}</span>
                            <span class="w-24 text-right text-sm font-semibold text-miralto-marron">
                                {{ formatCOP(line.cost_per_unit * line.quantity) }}
                            </span>
                            <button class="text-muted-foreground transition-colors hover:text-destructive" @click="removeIngredientLine(idx)">
                                <Trash2 class="size-4" />
                            </button>
                        </div>

                        <!-- Cost total -->
                        <div class="flex items-center justify-between rounded-lg bg-miralto-beige/60 px-3 py-2">
                            <span class="text-sm font-medium text-muted-foreground">Costo total de insumos</span>
                            <span class="font-bold text-miralto-marron">{{ formatCOP(ingredientCost) }}</span>
                        </div>
                    </div>

                    <div v-else class="rounded-lg border border-dashed border-sidebar-border/60 py-8 text-center text-sm text-muted-foreground">
                        Sin insumos — el precio se ingresa manualmente
                    </div>
                </div>
            </div>

            <!-- ── Right: estado + precios ──────────────────────── -->
            <div class="flex flex-col gap-4 lg:col-span-2">

                <!-- Estado y stock -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5">
                    <h2 class="mb-4 font-semibold">Estado y stock</h2>
                    <div class="space-y-4">
                        <label class="flex cursor-pointer items-center justify-between rounded-lg border border-sidebar-border/70 p-3">
                            <div>
                                <p class="text-sm font-medium">Producto activo</p>
                                <p class="text-xs text-muted-foreground">Disponible para órdenes</p>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="isActive"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none"
                                :class="isActive ? 'bg-miralto-verde' : 'bg-muted-foreground/30'"
                                @click="isActive = !isActive"
                            >
                                <span
                                    class="pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-lg transition-transform"
                                    :class="isActive ? 'translate-x-5' : 'translate-x-0'"
                                />
                            </button>
                        </label>

                        <div class="space-y-1.5">
                            <Label for="stock">Stock (opcional)</Label>
                            <input
                                id="stock"
                                v-model.number="stock"
                                type="number"
                                min="0"
                                placeholder="Dejar vacío = ilimitado"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none"
                            />
                        </div>
                    </div>
                </div>

                <!-- Precios -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5">
                    <h2 class="mb-4 font-semibold">Precio de venta</h2>

                    <div class="space-y-4">
                        <!-- Suggested price panel (shown when ingredients present) -->
                        <template v-if="ingredientLines.length > 0">
                            <div class="rounded-lg border border-miralto-verde/20 bg-miralto-verde/5 p-3 space-y-3">
                                <div class="flex items-center gap-2">
                                    <Sparkles class="size-4 text-miralto-verde" />
                                    <span class="text-sm font-medium text-miralto-verde">Precio sugerido</span>
                                </div>

                                <div class="space-y-1">
                                    <label class="text-xs text-muted-foreground">Margen de ganancia objetivo: <strong>{{ targetMargin }}%</strong></label>
                                    <input
                                        v-model.number="targetMargin"
                                        type="range"
                                        min="10"
                                        max="90"
                                        step="1"
                                        class="w-full accent-miralto-verde"
                                    />
                                    <div class="flex justify-between text-xs text-muted-foreground">
                                        <span>10%</span><span>90%</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between rounded-md bg-miralto-verde/10 px-3 py-2">
                                    <span class="text-sm text-muted-foreground">
                                        = Costo / (1 − {{ targetMargin }}%)
                                    </span>
                                    <span class="text-lg font-bold text-miralto-verde">{{ formatCOP(suggestedPrice) }}</span>
                                </div>

                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="w-full gap-1.5 border-miralto-verde/30 text-miralto-verde hover:bg-miralto-verde/10"
                                    @click="applySuggestedPrice"
                                >
                                    <ChevronRight class="size-3.5" />
                                    Aplicar precio sugerido
                                </Button>
                            </div>
                        </template>

                        <div class="space-y-1.5">
                            <Label for="price">Precio de venta (COP) *</Label>
                            <input
                                id="price"
                                v-model.number="price"
                                type="number"
                                min="0"
                                step="100"
                                placeholder="0"
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-base font-semibold focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                            />
                            <InputError :message="errors.price" />
                            <p v-if="price > 0" class="text-xs text-muted-foreground">
                                {{ formatCOP(price) }}
                            </p>
                        </div>

                        <!-- Manual cost (only shown when no ingredients) -->
                        <div v-if="ingredientLines.length === 0" class="space-y-1.5">
                            <Label for="cost">Costo estimado (COP)</Label>
                            <input
                                id="cost"
                                type="number"
                                min="0"
                                step="100"
                                placeholder="Opcional"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none"
                                @change="(e) => { const val = (e.target as HTMLInputElement).valueAsNumber; }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <Button
                        class="flex-1 bg-miralto-verde text-white hover:bg-miralto-verde/90"
                        :disabled="processing"
                        @click="submit"
                    >
                        {{ processing ? 'Guardando…' : 'Crear producto' }}
                    </Button>
                    <Link :href="index()">
                        <Button variant="ghost">Cancelar</Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
