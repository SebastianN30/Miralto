<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { KeyRound, Pencil, Plus, Trash2, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import * as EmployeeController from '@/actions/App/Http/Controllers/EmployeeController';
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
import type { Employee, EmployeeRole } from '@/types';
import { index } from '@/routes/employees';

type Props = {
    employees: Employee[];
    roles: Record<EmployeeRole, string>;
    stats: { total: number; active: number; inactive: number };
};

defineProps<Props>();

// Solo Mesero y Cocinero pueden tener usuario y clave
const LOGIN_ROLES: EmployeeRole[] = ['waiter', 'cook'];
const canHaveAccess = (role: EmployeeRole) => LOGIN_ROLES.includes(role);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Empleados', href: index() }],
    },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

// ── Create dialog ─────────────────────────────────────────────
const createOpen = ref(false);
const createProcessing = ref(false);
const createErrors = ref<Record<string, string>>({});
const emptyCreateForm = () => ({ name: '', position: '', role: 'other' as EmployeeRole, has_access: false, username: '', password: '' });
const createForm = ref(emptyCreateForm());

function submitCreate() {
    createProcessing.value = true;
    createErrors.value = {};
    router.post(
        EmployeeController.store.url(),
        {
            ...createForm.value,
            position: createForm.value.position || null,
            has_access: createForm.value.has_access && canHaveAccess(createForm.value.role),
        },
        {
            onSuccess: () => { createOpen.value = false; createForm.value = emptyCreateForm(); },
            onError: (e) => { createErrors.value = e; },
            onFinish: () => { createProcessing.value = false; },
        },
    );
}

// ── Edit dialog ───────────────────────────────────────────────
const editOpen = ref(false);
const editProcessing = ref(false);
const editErrors = ref<Record<string, string>>({});
const editForm = ref({ name: '', position: '', role: 'other' as EmployeeRole, is_active: true, has_access: false, username: '', password: '' });
const editingEmployee = ref<Employee | null>(null);

function openEdit(employee: Employee) {
    editingEmployee.value = employee;
    editForm.value = {
        name: employee.name,
        position: employee.position ?? '',
        role: employee.role,
        is_active: employee.is_active,
        has_access: employee.user?.is_active ?? false,
        username: employee.user?.username ?? '',
        password: '',
    };
    editErrors.value = {};
    editOpen.value = true;
}

function submitEdit() {
    if (!editingEmployee.value) return;
    editProcessing.value = true;
    editErrors.value = {};
    router.patch(
        EmployeeController.update.url({ employee: editingEmployee.value.id }),
        {
            ...editForm.value,
            position: editForm.value.position || null,
            has_access: editForm.value.has_access && canHaveAccess(editForm.value.role),
        },
        {
            onSuccess: () => { editOpen.value = false; },
            onError: (e) => { editErrors.value = e; },
            onFinish: () => { editProcessing.value = false; },
        },
    );
}

// ── Delete ────────────────────────────────────────────────────
function deleteEmployee(employee: Employee) {
    if (!confirm(`¿Eliminar al empleado "${employee.name}"? Esta acción no se puede deshacer.`)) return;
    router.delete(EmployeeController.destroy.url({ employee: employee.id }));
}
</script>

<template>
    <Head title="Empleados" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Stats -->
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-verde/10 p-2">
                        <Users class="size-5 text-miralto-verde" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total empleados</p>
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
            <h1 class="text-lg font-semibold">Gestión de empleados</h1>

            <Dialog v-if="isAdmin" v-model:open="createOpen">
                <DialogTrigger as-child>
                    <Button class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                        <Plus class="size-4" />Nuevo empleado
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-sm">
                    <DialogHeader>
                        <DialogTitle>Nuevo empleado</DialogTitle>
                    </DialogHeader>
                    <div class="space-y-4 py-2">
                        <div class="space-y-1.5">
                            <Label for="create-name">Nombre <span class="text-destructive">*</span></Label>
                            <input
                                id="create-name"
                                v-model="createForm.name"
                                type="text"
                                maxlength="100"
                                placeholder="Ej: Juan Pérez"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                @keydown.enter.prevent="submitCreate"
                            />
                            <InputError :message="createErrors.name" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="create-position">Cargo</Label>
                            <input
                                id="create-position"
                                v-model="createForm.position"
                                type="text"
                                maxlength="100"
                                placeholder="Ej: Mesero, Cocina…"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                @keydown.enter.prevent="submitCreate"
                            />
                            <InputError :message="createErrors.position" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="create-role">Rol <span class="text-destructive">*</span></Label>
                            <select
                                id="create-role"
                                v-model="createForm.role"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                            >
                                <option v-for="(label, value) in roles" :key="value" :value="value">{{ label }}</option>
                            </select>
                            <InputError :message="createErrors.role" />
                        </div>
                        <div v-if="canHaveAccess(createForm.role)" class="space-y-3 rounded-lg border border-miralto-verde/30 bg-miralto-verde/5 p-3">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors"
                                    :class="createForm.has_access ? 'bg-miralto-verde' : 'bg-muted-foreground/30'"
                                    @click="createForm.has_access = !createForm.has_access"
                                >
                                    <span class="inline-block size-4 rounded-full bg-white shadow transition-transform" :class="createForm.has_access ? 'translate-x-4' : 'translate-x-0.5'" />
                                </button>
                                <Label class="cursor-pointer" @click="createForm.has_access = !createForm.has_access">Tiene acceso al sistema</Label>
                            </div>
                            <template v-if="createForm.has_access">
                                <div class="space-y-1.5">
                                    <Label for="create-username">Usuario <span class="text-destructive">*</span></Label>
                                    <input
                                        id="create-username"
                                        v-model="createForm.username"
                                        type="text"
                                        maxlength="50"
                                        autocapitalize="none"
                                        autocomplete="off"
                                        placeholder="Ej: juan.perez"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                    />
                                    <InputError :message="createErrors.username" />
                                </div>
                                <div class="space-y-1.5">
                                    <Label for="create-password">Clave<span class="text-destructive"> *</span></Label>
                                    <input
                                        id="create-password"
                                        v-model="createForm.password"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Mínimo 8 caracteres"
                                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                    />
                                    <InputError :message="createErrors.password" />
                                </div>
                            </template>
                            <InputError :message="createErrors.has_access" />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" @click="createOpen = false">Cancelar</Button>
                        <Button
                            class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                            :disabled="createProcessing || !createForm.name.trim()"
                            @click="submitCreate"
                        >
                            {{ createProcessing ? 'Creando…' : 'Crear empleado' }}
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
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Empleado</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Rol</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground md:table-cell">Usuario</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground sm:table-cell">Cargo</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Órdenes</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Estado</th>
                        <th v-if="isAdmin" class="px-4 py-3 text-right font-medium text-muted-foreground">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="employees.length === 0">
                        <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
                            No hay empleados registrados. Crea el primero.
                        </td>
                    </tr>
                    <tr
                        v-for="employee in employees"
                        :key="employee.id"
                        class="border-b border-sidebar-border/30 transition-colors last:border-0 hover:bg-muted/20"
                        :class="{ 'opacity-60': !employee.is_active }"
                    >
                        <td class="px-4 py-3 font-medium">{{ employee.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ roles[employee.role] }}</td>
                        <td class="hidden px-4 py-3 md:table-cell">
                            <span v-if="employee.user?.username" class="inline-flex items-center gap-1 font-mono text-xs" :class="employee.user.is_active ? '' : 'text-muted-foreground line-through'">
                                <KeyRound class="size-3" />{{ employee.user.username }}
                            </span>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">
                            {{ employee.position ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center text-muted-foreground">{{ employee.orders_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-center">
                            <Badge
                                v-if="employee.is_active"
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
                                    @click="openEdit(employee)"
                                >
                                    <Pencil class="mr-1 size-3" />Editar
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 px-2 text-xs text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    :disabled="(employee.orders_count ?? 0) > 0"
                                    :title="(employee.orders_count ?? 0) > 0 ? 'Tiene órdenes asociadas; desactívalo' : 'Eliminar'"
                                    @click="deleteEmployee(employee)"
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
                    <DialogTitle>Editar empleado</DialogTitle>
                </DialogHeader>
                <div class="space-y-4 py-2">
                    <div class="space-y-1.5">
                        <Label for="edit-name">Nombre <span class="text-destructive">*</span></Label>
                        <input
                            id="edit-name"
                            v-model="editForm.name"
                            type="text"
                            maxlength="100"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                        />
                        <InputError :message="editErrors.name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="edit-position">Cargo</Label>
                        <input
                            id="edit-position"
                            v-model="editForm.position"
                            type="text"
                            maxlength="100"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                        />
                        <InputError :message="editErrors.position" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="edit-role">Rol <span class="text-destructive">*</span></Label>
                        <select
                            id="edit-role"
                            v-model="editForm.role"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                        >
                            <option v-for="(label, value) in roles" :key="value" :value="value">{{ label }}</option>
                        </select>
                        <InputError :message="editErrors.role" />
                    </div>
                    <div v-if="canHaveAccess(editForm.role)" class="space-y-3 rounded-lg border border-miralto-verde/30 bg-miralto-verde/5 p-3">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors"
                                :class="editForm.has_access ? 'bg-miralto-verde' : 'bg-muted-foreground/30'"
                                @click="editForm.has_access = !editForm.has_access"
                            >
                                <span class="inline-block size-4 rounded-full bg-white shadow transition-transform" :class="editForm.has_access ? 'translate-x-4' : 'translate-x-0.5'" />
                            </button>
                            <Label class="cursor-pointer" @click="editForm.has_access = !editForm.has_access">Tiene acceso al sistema</Label>
                        </div>
                        <template v-if="editForm.has_access">
                            <div class="space-y-1.5">
                                <Label for="edit-username">Usuario <span class="text-destructive">*</span></Label>
                                <input
                                    id="edit-username"
                                    v-model="editForm.username"
                                    type="text"
                                    maxlength="50"
                                    autocapitalize="none"
                                    autocomplete="off"
                                    placeholder="Ej: juan.perez"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="editErrors.username" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="edit-password">Clave<span v-if="!editingEmployee?.user" class="text-destructive"> *</span></Label>
                                <input
                                    id="edit-password"
                                    v-model="editForm.password"
                                    type="password"
                                    autocomplete="new-password"
                                    :placeholder="editingEmployee?.user ? 'Vacía = no cambiarla' : 'Mínimo 8 caracteres'"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="editErrors.password" />
                            </div>
                        </template>
                        <InputError :message="editErrors.has_access" />
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
