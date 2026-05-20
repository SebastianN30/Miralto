<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Building2, Mail, Pencil, Phone, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import * as SupplierController from '@/actions/App/Http/Controllers/SupplierController';
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
import type { Supplier } from '@/types';
import { index } from '@/routes/suppliers';

type Props = {
    suppliers: Supplier[];
    stats: { total: number; active: number; inactive: number };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Proveedores', href: index() }],
    },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

type SupplierForm = { name: string; contact_name: string; phone: string; email: string; notes: string };

function emptyForm(): SupplierForm {
    return { name: '', contact_name: '', phone: '', email: '', notes: '' };
}

// ── Create dialog ─────────────────────────────────────────────
const createOpen = ref(false);
const createProcessing = ref(false);
const createErrors = ref<Record<string, string>>({});
const createForm = ref<SupplierForm>(emptyForm());

function submitCreate() {
    createProcessing.value = true;
    createErrors.value = {};
    router.post(
        SupplierController.store.url(),
        createForm.value,
        {
            onSuccess: () => { createOpen.value = false; createForm.value = emptyForm(); },
            onError: (e) => { createErrors.value = e; },
            onFinish: () => { createProcessing.value = false; },
        },
    );
}

// ── Edit dialog ───────────────────────────────────────────────
const editOpen = ref(false);
const editProcessing = ref(false);
const editErrors = ref<Record<string, string>>({});
const editForm = ref<SupplierForm & { is_active: boolean }>({ ...emptyForm(), is_active: true });
const editingSupplier = ref<Supplier | null>(null);

function openEdit(supplier: Supplier) {
    editingSupplier.value = supplier;
    editForm.value = {
        name: supplier.name,
        contact_name: supplier.contact_name ?? '',
        phone: supplier.phone ?? '',
        email: supplier.email ?? '',
        notes: supplier.notes ?? '',
        is_active: supplier.is_active,
    };
    editErrors.value = {};
    editOpen.value = true;
}

function submitEdit() {
    if (!editingSupplier.value) return;
    editProcessing.value = true;
    editErrors.value = {};
    router.patch(
        SupplierController.update.url({ supplier: editingSupplier.value.id }),
        editForm.value,
        {
            onSuccess: () => { editOpen.value = false; },
            onError: (e) => { editErrors.value = e; },
            onFinish: () => { editProcessing.value = false; },
        },
    );
}

// ── Delete ────────────────────────────────────────────────────
function deleteSupplier(supplier: Supplier) {
    if (!confirm(`¿Eliminar el proveedor "${supplier.name}"? Esta acción no se puede deshacer.`)) return;
    router.delete(SupplierController.destroy.url({ supplier: supplier.id }));
}
</script>

<template>
    <Head title="Proveedores" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-verde/10 p-2">
                        <Building2 class="size-5 text-miralto-verde" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total</p>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div>
                    <p class="text-xs text-muted-foreground">Activos</p>
                    <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
                </div>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div>
                    <p class="text-xs text-muted-foreground">Inactivos</p>
                    <p class="text-2xl font-bold text-amber-600">{{ stats.inactive }}</p>
                </div>
            </div>
        </div>

        <!-- Header row -->
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold">Gestión de proveedores</h1>

            <Dialog v-if="isAdmin" v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                        <Plus class="size-4" />Nuevo proveedor
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Nuevo proveedor</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="c-name">Nombre <span class="text-destructive">*</span></Label>
                            <input
                                id="c-name"
                                v-model="createForm.name"
                                type="text"
                                placeholder="Ej: Distribuidora El Campo"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            />
                            <InputError :message="createErrors.name" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="c-contact">Contacto</Label>
                            <input
                                id="c-contact"
                                v-model="createForm.contact_name"
                                type="text"
                                placeholder="Nombre del contacto"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <Label for="c-phone">Teléfono</Label>
                                <input
                                    id="c-phone"
                                    v-model="createForm.phone"
                                    type="tel"
                                    placeholder="Ej: 300 123 4567"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="c-email">Correo</Label>
                                <input
                                    id="c-email"
                                    v-model="createForm.email"
                                    type="email"
                                    placeholder="correo@proveedor.com"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="createErrors.email" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label for="c-notes">Notas</Label>
                            <textarea
                                id="c-notes"
                                v-model="createForm.notes"
                                rows="2"
                                placeholder="Días de entrega, condiciones de pago…"
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
                            {{ createProcessing ? 'Creando…' : 'Crear proveedor' }}
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
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Proveedor</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground sm:table-cell">Contacto</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground md:table-cell">Teléfono</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground lg:table-cell">Correo</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Estado</th>
                        <th v-if="isAdmin" class="px-4 py-3 text-right font-medium text-muted-foreground">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="suppliers.length === 0">
                        <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                            No hay proveedores registrados. Agrega el primero.
                        </td>
                    </tr>
                    <tr
                        v-for="supplier in suppliers"
                        :key="supplier.id"
                        class="border-b border-sidebar-border/30 transition-colors last:border-0 hover:bg-muted/20"
                        :class="{ 'opacity-60': !supplier.is_active }"
                    >
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ supplier.name }}</p>
                            <p v-if="supplier.notes" class="mt-0.5 truncate text-xs text-muted-foreground max-w-[220px]">
                                {{ supplier.notes }}
                            </p>
                        </td>
                        <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">
                            {{ supplier.contact_name ?? '—' }}
                        </td>
                        <td class="hidden px-4 py-3 md:table-cell">
                            <a
                                v-if="supplier.phone"
                                :href="`tel:${supplier.phone}`"
                                class="flex items-center gap-1 text-muted-foreground hover:text-foreground"
                            >
                                <Phone class="size-3" />{{ supplier.phone }}
                            </a>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="hidden px-4 py-3 lg:table-cell">
                            <a
                                v-if="supplier.email"
                                :href="`mailto:${supplier.email}`"
                                class="flex items-center gap-1 text-muted-foreground hover:text-foreground"
                            >
                                <Mail class="size-3" />{{ supplier.email }}
                            </a>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Badge
                                v-if="supplier.is_active"
                                variant="outline"
                                class="border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400"
                            >Activo</Badge>
                            <Badge
                                v-else
                                variant="outline"
                                class="border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-400"
                            >Inactivo</Badge>
                        </td>
                        <td v-if="isAdmin" class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs"
                                    @click="openEdit(supplier)"
                                >
                                    <Pencil class="mr-1 size-3" />Editar
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    @click="deleteSupplier(supplier)"
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
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar proveedor</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="e-name">Nombre <span class="text-destructive">*</span></Label>
                        <input
                            id="e-name"
                            v-model="editForm.name"
                            type="text"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                        />
                        <InputError :message="editErrors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="e-contact">Contacto</Label>
                        <input
                            id="e-contact"
                            v-model="editForm.contact_name"
                            type="text"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="e-phone">Teléfono</Label>
                            <input
                                id="e-phone"
                                v-model="editForm.phone"
                                type="tel"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="e-email">Correo</Label>
                            <input
                                id="e-email"
                                v-model="editForm.email"
                                type="email"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            />
                            <InputError :message="editErrors.email" />
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="e-notes">Notas</Label>
                        <textarea
                            id="e-notes"
                            v-model="editForm.notes"
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
                                class="inline-block size-4 rounded-full bg-white shadow transition-transform"
                                :class="editForm.is_active ? 'translate-x-4' : 'translate-x-0.5'"
                            />
                        </button>
                        <Label class="cursor-pointer" @click="editForm.is_active = !editForm.is_active">
                            {{ editForm.is_active ? 'Activo' : 'Inactivo' }}
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
