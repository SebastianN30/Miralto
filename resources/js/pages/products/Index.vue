<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Package, PackageCheck, PackageX, Trash2, RotateCcw, Search, Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import * as ProductController from '@/actions/App/Http/Controllers/ProductController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Category, PaginatedData, Product } from '@/types';
import { index, create } from '@/routes/products';

type Props = {
    products: PaginatedData<Product>;
    categories: Pick<Category, 'id' | 'name'>[];
    stats: {
        total: number;
        active: number;
        inactive: number;
        deleted: number;
    };
    filters: {
        search?: string;
        category_id?: string;
        status?: string;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Productos', href: '/products' }],
    },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

const search = ref(props.filters.search ?? '');
const categoryId = ref(props.filters.category_id ?? '');
const status = ref(props.filters.status ?? '');

let searchTimeout: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 400);
});

watch([categoryId, status], () => applyFilters());

function applyFilters() {
    router.get(
        ProductController.index.url(),
        { search: search.value, category_id: categoryId.value, status: status.value },
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

function confirmDelete(product: Product) {
    if (confirm(`¿Archivar el producto "${product.name}"? Podrás restaurarlo después.`)) {
        router.delete(ProductController.destroy.url({ product: product.id }));
    }
}

function restoreProduct(product: Product) {
    router.post(ProductController.restore.url({ id: product.id }));
}

function marginColor(margin: number | null): string {
    if (margin === null) return 'text-muted-foreground';
    if (margin >= 60) return 'text-green-600 dark:text-green-400';
    if (margin >= 40) return 'text-amber-600 dark:text-amber-400';
    return 'text-red-600 dark:text-red-400';
}

function calcMargin(product: Product): number | null {
    if (!product.cost || Number(product.cost) === 0) return null;
    return Math.round(((Number(product.price) - Number(product.cost)) / Number(product.price)) * 100);
}
</script>

<template>
    <Head title="Productos" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Stats -->
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-verde/10 p-2">
                        <Package class="size-5 text-miralto-verde" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total productos</p>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30">
                        <PackageCheck class="size-5 text-green-600" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Activos</p>
                        <p class="text-2xl font-bold">{{ stats.active }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-amber-100 p-2 dark:bg-amber-900/30">
                        <PackageX class="size-5 text-amber-600" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Inactivos</p>
                        <p class="text-2xl font-bold">{{ stats.inactive }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-red-100 p-2 dark:bg-red-900/30">
                        <Trash2 class="size-5 text-red-500" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Archivados</p>
                        <p class="text-2xl font-bold">{{ stats.deleted }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-wrap gap-2">
                <div class="relative flex-1 max-w-xs">
                    <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar producto…"
                        class="h-9 w-full rounded-md border border-input bg-background pl-9 pr-3 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                    />
                </div>

                <select
                    v-model="categoryId"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                >
                    <option value="">Todas las categorías</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>

                <select
                    v-model="status"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                >
                    <option value="">Todos</option>
                    <option value="active">Activos</option>
                    <option value="inactive">Inactivos</option>
                    <option value="deleted">Archivados</option>
                </select>
            </div>

            <Link :href="create()">
                <Button class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                    <Plus class="size-4" />
                    Nuevo producto
                </Button>
            </Link>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-sidebar-border/70">
            <table class="w-full text-sm">
                <thead class="border-b border-sidebar-border/70 bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Producto</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground sm:table-cell">Categoría</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Precio</th>
                        <th class="hidden px-4 py-3 text-right font-medium text-muted-foreground lg:table-cell">Costo</th>
                        <th class="hidden px-4 py-3 text-center font-medium text-muted-foreground lg:table-cell">Margen</th>
                        <th class="hidden px-4 py-3 text-center font-medium text-muted-foreground xl:table-cell">Stock</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Estado</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="products.data.length === 0">
                        <td colspan="8" class="px-4 py-12 text-center text-muted-foreground">
                            No se encontraron productos.
                        </td>
                    </tr>
                    <tr
                        v-for="product in products.data"
                        :key="product.id"
                        class="border-b border-sidebar-border/40 transition-colors last:border-0 hover:bg-muted/30"
                        :class="{ 'opacity-60': product.deleted_at }"
                    >
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ product.name }}</p>
                            <p v-if="product.description" class="truncate text-xs text-muted-foreground max-w-[200px]">{{ product.description }}</p>
                        </td>
                        <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">
                            {{ product.category?.name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-miralto-verde">
                            {{ formatCOP(product.price) }}
                        </td>
                        <td class="hidden px-4 py-3 text-right text-muted-foreground lg:table-cell">
                            {{ product.cost ? formatCOP(product.cost) : '—' }}
                        </td>
                        <td class="hidden px-4 py-3 text-center lg:table-cell">
                            <span :class="marginColor(calcMargin(product))" class="font-semibold">
                                {{ calcMargin(product) !== null ? `${calcMargin(product)}%` : '—' }}
                            </span>
                        </td>
                        <td class="hidden px-4 py-3 text-center xl:table-cell">
                            <span v-if="product.stock === null" class="text-muted-foreground">∞</span>
                            <span
                                v-else-if="product.stock === 0"
                                class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700 dark:bg-red-900/30 dark:text-red-400"
                            >Agotado</span>
                            <span
                                v-else-if="product.stock <= 5"
                                class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                            >{{ product.stock }}</span>
                            <span v-else class="text-muted-foreground">{{ product.stock }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Badge
                                v-if="product.deleted_at"
                                variant="outline"
                                class="border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400"
                            >Archivado</Badge>
                            <Badge
                                v-else-if="product.is_active"
                                variant="outline"
                                class="border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400"
                            >Activo</Badge>
                            <Badge
                                v-else
                                variant="outline"
                                class="border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                            >Inactivo</Badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <template v-if="product.deleted_at">
                                    <Button
                                        v-if="isAdmin"
                                        variant="ghost"
                                        size="sm"
                                        class="h-7 gap-1 px-2 text-xs text-miralto-verde hover:bg-miralto-verde/10"
                                        @click="restoreProduct(product)"
                                    >
                                        <RotateCcw class="size-3" />
                                        Restaurar
                                    </Button>
                                </template>
                                <template v-else>
                                    <Link :href="ProductController.edit.url({ product: product.id })">
                                        <Button variant="ghost" size="sm" class="h-7 px-2 text-xs">Editar</Button>
                                    </Link>
                                    <Button
                                        v-if="isAdmin"
                                        variant="ghost"
                                        size="sm"
                                        class="h-7 px-2 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        @click="confirmDelete(product)"
                                    >
                                        Archivar
                                    </Button>
                                </template>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="products.last_page > 1" class="flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
                Mostrando {{ products.from }} – {{ products.to }} de {{ products.total }} productos
            </p>
            <div class="flex gap-1">
                <template v-for="link in products.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm transition-colors"
                        :class="link.active ? 'bg-miralto-verde text-white' : 'border border-sidebar-border/70 hover:bg-muted'"
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
