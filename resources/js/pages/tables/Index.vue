<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, UtensilsCrossed } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import * as TableController from '@/actions/App/Http/Controllers/TableController';
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
import type { Table } from '@/types';
import { index } from '@/routes/tables';

type TableWithCount = Table & { orders_count: number };

type Props = {
    tables: TableWithCount[];
    stats: { total: number; active: number; inactive: number };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mesas', href: index() }],
    },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

// ── Create dialog ─────────────────────────────────────────────
const createOpen = ref(false);
const createProcessing = ref(false);
const createErrors = ref<Record<string, string>>({});
const createForm = ref({ name: '', capacity: '', zone: '' });

function submitCreate() {
    createProcessing.value = true;
    createErrors.value = {};
    router.post(
        TableController.store.url(),
        { ...createForm.value, capacity: createForm.value.capacity || null },
        {
            onSuccess: () => { createOpen.value = false; createForm.value = { name: '', capacity: '', zone: '' }; },
            onError: (e) => { createErrors.value = e; },
            onFinish: () => { createProcessing.value = false; },
        },
    );
}

// ── Edit dialog ───────────────────────────────────────────────
const editOpen = ref(false);
const editProcessing = ref(false);
const editErrors = ref<Record<string, string>>({});
const editForm = ref({ name: '', capacity: '', zone: '', is_active: true });
const editingTable = ref<TableWithCount | null>(null);

function openEdit(table: TableWithCount) {
    editingTable.value = table;
    editForm.value = {
        name: table.name,
        capacity: table.capacity ? String(table.capacity) : '',
        zone: table.zone ?? '',
        is_active: table.is_active,
    };
    editErrors.value = {};
    editOpen.value = true;
}

function submitEdit() {
    if (!editingTable.value) return;
    editProcessing.value = true;
    editErrors.value = {};
    router.patch(
        TableController.update.url({ table: editingTable.value.id }),
        { ...editForm.value, capacity: editForm.value.capacity || null },
        {
            onSuccess: () => { editOpen.value = false; },
            onError: (e) => { editErrors.value = e; },
            onFinish: () => { editProcessing.value = false; },
        },
    );
}

// ── Delete ────────────────────────────────────────────────────
function deleteTable(table: TableWithCount) {
    if (!confirm(`¿Eliminar la mesa "${table.name}"? Esta acción no se puede deshacer.`)) return;
    router.delete(TableController.destroy.url({ table: table.id }));
}
</script>

<template>
    <Head title="Mesas" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-verde/10 p-2">
                        <UtensilsCrossed class="size-5 text-miralto-verde" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total mesas</p>
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
            <h1 class="text-lg font-semibold">Gestión de mesas</h1>

            <Dialog v-if="isAdmin" v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                        <Plus class="size-4" />Nueva mesa
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Nueva mesa</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="create-name">Nombre <span class="text-destructive">*</span></Label>
                            <input
                                id="create-name"
                                v-model="createForm.name"
                                type="text"
                                placeholder="Ej: Mesa 1, Terraza A…"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                @keydown.enter.prevent="submitCreate"
                            />
                            <InputError :message="createErrors.name" />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <Label for="create-capacity">Capacidad</Label>
                                <input
                                    id="create-capacity"
                                    v-model="createForm.capacity"
                                    type="number"
                                    min="1"
                                    max="99"
                                    placeholder="Ej: 4"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="createErrors.capacity" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="create-zone">Zona</Label>
                                <input
                                    id="create-zone"
                                    v-model="createForm.zone"
                                    type="text"
                                    placeholder="Ej: Terraza"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" @click="createOpen = false">Cancelar</Button>
                        <Button
                            class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                            :disabled="createProcessing || !createForm.name.trim()"
                            @click="submitCreate"
                        >
                            {{ createProcessing ? 'Creando…' : 'Crear mesa' }}
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
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Mesa</th>
                        <th class="hidden px-4 py-3 text-center font-medium text-muted-foreground sm:table-cell">Capacidad</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground md:table-cell">Zona</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Pedidos activos</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Estado</th>
                        <th v-if="isAdmin" class="px-4 py-3 text-right font-medium text-muted-foreground">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="tables.length === 0">
                        <td colspan="6" class="px-4 py-12 text-center text-muted-foreground">
                            No hay mesas registradas. Crea la primera.
                        </td>
                    </tr>
                    <tr
                        v-for="table in tables"
                        :key="table.id"
                        class="border-b border-sidebar-border/30 transition-colors last:border-0 hover:bg-muted/20"
                        :class="{ 'opacity-60': !table.is_active }"
                    >
                        <td class="px-4 py-3 font-medium">{{ table.name }}</td>
                        <td class="hidden px-4 py-3 text-center text-muted-foreground sm:table-cell">
                            {{ table.capacity ? `${table.capacity} personas` : '—' }}
                        </td>
                        <td class="hidden px-4 py-3 text-muted-foreground md:table-cell">
                            {{ table.zone ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span
                                class="inline-flex size-7 items-center justify-center rounded-full text-sm font-semibold"
                                :class="table.orders_count > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'text-muted-foreground'"
                            >
                                {{ table.orders_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Badge
                                v-if="table.is_active"
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
                                    @click="openEdit(table)"
                                >
                                    <Pencil class="mr-1 size-3" />Editar
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    :disabled="table.orders_count > 0"
                                    :title="table.orders_count > 0 ? 'Tiene pedidos pendientes' : 'Eliminar'"
                                    @click="deleteTable(table)"
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
                    <DialogTitle>Editar mesa</DialogTitle>
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
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="edit-capacity">Capacidad</Label>
                            <input
                                id="edit-capacity"
                                v-model="editForm.capacity"
                                type="number"
                                min="1"
                                max="99"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            />
                            <InputError :message="editErrors.capacity" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="edit-zone">Zona</Label>
                            <input
                                id="edit-zone"
                                v-model="editForm.zone"
                                type="text"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            />
                        </div>
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
