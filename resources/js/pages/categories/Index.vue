<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Tag, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import * as CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { Category } from '@/types';
import { index } from '@/routes/categories';

type CategoryWithCount = Category & { products_count: number };

type Props = {
    categories: CategoryWithCount[];
    stats: { total: number; active: number; inactive: number };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Categorías', href: index() }],
    },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

// ── Create dialog ─────────────────────────────────────────────
const createOpen = ref(false);
const createProcessing = ref(false);
const createErrors = ref<Record<string, string>>({});
const createForm = ref({ name: '', description: '' });

function submitCreate() {
    createProcessing.value = true;
    createErrors.value = {};
    router.post(
        CategoryController.store.url(),
        createForm.value,
        {
            onSuccess: () => { createOpen.value = false; createForm.value = { name: '', description: '' }; },
            onError: (e) => { createErrors.value = e; },
            onFinish: () => { createProcessing.value = false; },
        },
    );
}

// ── Edit dialog ───────────────────────────────────────────────
const editOpen = ref(false);
const editProcessing = ref(false);
const editErrors = ref<Record<string, string>>({});
const editForm = ref({ name: '', description: '', is_active: true });
const editingCategory = ref<CategoryWithCount | null>(null);

function openEdit(category: CategoryWithCount) {
    editingCategory.value = category;
    editForm.value = {
        name: category.name,
        description: category.description ?? '',
        is_active: category.is_active,
    };
    editErrors.value = {};
    editOpen.value = true;
}

function submitEdit() {
    if (!editingCategory.value) return;
    editProcessing.value = true;
    editErrors.value = {};
    router.patch(
        CategoryController.update.url({ category: editingCategory.value.id }),
        editForm.value,
        {
            onSuccess: () => { editOpen.value = false; },
            onError: (e) => { editErrors.value = e; },
            onFinish: () => { editProcessing.value = false; },
        },
    );
}

// ── Delete ────────────────────────────────────────────────────
function deleteCategory(category: CategoryWithCount) {
    if (category.products_count > 0) {
        alert(`No puedes eliminar "${category.name}" porque tiene ${category.products_count} producto(s) asociado(s). Desactívala primero.`);
        return;
    }
    if (!confirm(`¿Eliminar la categoría "${category.name}"? Esta acción no se puede deshacer.`)) return;
    router.delete(CategoryController.destroy.url({ category: category.id }));
}
</script>

<template>
    <Head title="Categorías" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-verde/10 p-2">
                        <Tag class="size-5 text-miralto-verde" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total</p>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div>
                    <p class="text-xs text-muted-foreground">Activas</p>
                    <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
                </div>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div>
                    <p class="text-xs text-muted-foreground">Inactivas</p>
                    <p class="text-2xl font-bold text-amber-600">{{ stats.inactive }}</p>
                </div>
            </div>
        </div>

        <!-- Header row -->
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold">Categorías de productos</h1>

            <Dialog v-if="isAdmin" v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                        <Plus class="size-4" />Nueva categoría
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Nueva categoría</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="create-name">Nombre <span class="text-destructive">*</span></Label>
                            <input
                                id="create-name"
                                v-model="createForm.name"
                                type="text"
                                placeholder="Ej: Entradas, Parrilla…"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                @keydown.enter.prevent="submitCreate"
                            />
                            <InputError :message="createErrors.name" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="create-desc">Descripción</Label>
                            <textarea
                                id="create-desc"
                                v-model="createForm.description"
                                rows="2"
                                placeholder="Descripción opcional…"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:border-ring focus:outline-none"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" @click="createOpen = false">Cancelar</Button>
                        <Button
                            class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                            :disabled="createProcessing || !createForm.name.trim()"
                            @click="submitCreate"
                        >
                            {{ createProcessing ? 'Creando…' : 'Crear categoría' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-sidebar-border/70">
            <table class="w-full text-sm">
                <thead class="border-b border-sidebar-border/70 bg-muted/40">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Categoría</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground md:table-cell">Descripción</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Productos</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Estado</th>
                        <th v-if="isAdmin" class="px-4 py-3 text-right font-medium text-muted-foreground">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="categories.length === 0">
                        <td colspan="5" class="px-4 py-12 text-center text-muted-foreground">
                            No hay categorías. Crea la primera.
                        </td>
                    </tr>
                    <tr
                        v-for="cat in categories"
                        :key="cat.id"
                        class="border-b border-sidebar-border/30 transition-colors last:border-0 hover:bg-muted/20"
                        :class="{ 'opacity-60': !cat.is_active }"
                    >
                        <td class="px-4 py-3 font-medium">{{ cat.name }}</td>
                        <td class="hidden px-4 py-3 text-muted-foreground md:table-cell">
                            {{ cat.description ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span
                                class="inline-flex size-7 items-center justify-center rounded-full text-sm font-semibold"
                                :class="cat.products_count > 0 ? 'bg-miralto-verde/10 text-miralto-verde' : 'text-muted-foreground'"
                            >
                                {{ cat.products_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Badge
                                v-if="cat.is_active"
                                variant="outline"
                                class="border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400"
                            >Activa</Badge>
                            <Badge
                                v-else
                                variant="outline"
                                class="border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                            >Inactiva</Badge>
                        </td>
                        <td v-if="isAdmin" class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs"
                                    @click="openEdit(cat)"
                                >
                                    <Pencil class="mr-1 size-3" />Editar
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    :disabled="cat.products_count > 0"
                                    :title="cat.products_count > 0 ? 'Tiene productos asociados' : 'Eliminar'"
                                    @click="deleteCategory(cat)"
                                >
                                    <Trash2 class="size-3" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Edit dialog -->
        <Dialog v-model:open="editOpen">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Editar categoría</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="edit-name">Nombre <span class="text-destructive">*</span></Label>
                        <input
                            id="edit-name"
                            v-model="editForm.name"
                            type="text"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                        />
                        <InputError :message="editErrors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="edit-desc">Descripción</Label>
                        <textarea
                            id="edit-desc"
                            v-model="editForm.description"
                            rows="2"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:border-ring focus:outline-none"
                        />
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors"
                            :class="editForm.is_active ? 'bg-miralto-verde' : 'bg-muted-foreground/30'"
                            @click="editForm.is_active = !editForm.is_active"
                        >
                            <span
                                class="inline-block size-4 translate-x-0.5 rounded-full bg-white shadow transition-transform"
                                :class="editForm.is_active ? 'translate-x-4' : 'translate-x-0.5'"
                            />
                        </button>
                        <Label class="cursor-pointer" @click="editForm.is_active = !editForm.is_active">
                            {{ editForm.is_active ? 'Activa' : 'Inactiva' }}
                        </Label>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="ghost" @click="editOpen = false">Cancelar</Button>
                    <Button
                        class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                        :disabled="editProcessing || !editForm.name.trim()"
                        @click="submitEdit"
                    >
                        {{ editProcessing ? 'Guardando…' : 'Guardar cambios' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
