<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft, ArrowDownCircle, ArrowUpCircle, ShoppingCart, Undo2,
    DoorOpen, DoorClosed, Plus, Trash2, Wallet, AlertCircle,
    CreditCard, Banknote, ArrowLeftRight,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import * as CashRegisterController from '@/actions/App/Http/Controllers/CashRegisterController';
import * as CashMovementController from '@/actions/App/Http/Controllers/CashMovementController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter,
    DialogHeader, DialogTitle, DialogTrigger,
} from '@/components/ui/dialog';
import type { CashMovement, CashMovementType, CashRegister } from '@/types';
import { index } from '@/routes/cash';

type Props = {
    register: CashRegister;
    totals: {
        opening_amount: number;
        closing_amount: number | null;
        total_sales_cash: number;
        total_income: number;
        total_expense: number;
        total_refund: number;
        sales_by_method: { cash: number; transfer: number; card: number };
        total_sales_other: number;
        expected_cash: number;
        difference: number | null;
        cash_in: number;
        cash_out: number;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Caja', href: '/cash' },
            { title: 'Detalle', href: '#' },
        ],
    },
});

// Add movement
const movDialog = ref(false);
const movType = ref<'income' | 'expense'>('expense');
const movAmount = ref<number>(0);
const movMethod = ref<'cash' | 'transfer' | 'card'>('cash');
const movDescription = ref('');
const movProcessing = ref(false);
const movErrors = ref<Partial<Record<string, string>>>({});

// Close register
const closeDialog = ref(false);
const closingAmount = ref<number>(0);
const closingNotes = ref('');
const closeProcessing = ref(false);
const closeErrors = ref<Partial<Record<string, string>>>({});

// Filter movements
const filterType = ref<'all' | CashMovementType>('all');
const filteredMovements = computed(() => {
    const movements = props.register.movements ?? [];
    if (filterType.value === 'all') return movements;
    return movements.filter((m) => m.type === filterType.value);
});

const isOpen = computed(() => props.register.status === 'open');

function formatCOP(value: number | string | null | undefined): string {
    if (value === null || value === undefined) return '—';
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(Number(value));
}

function formatDateTime(dateStr: string): string {
    return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
    }).format(new Date(dateStr));
}

function formatFullDate(dateStr: string): string {
    return new Intl.DateTimeFormat('es-CO', { dateStyle: 'full', timeStyle: 'short' }).format(new Date(dateStr));
}

const TYPE_CONFIG: Record<CashMovementType, { label: string; icon: typeof ShoppingCart; class: string; sign: '+' | '−' }> = {
    sale: { label: 'Venta', icon: ShoppingCart, class: 'text-miralto-verde', sign: '+' },
    income: { label: 'Ingreso', icon: ArrowDownCircle, class: 'text-green-600', sign: '+' },
    expense: { label: 'Egreso', icon: ArrowUpCircle, class: 'text-destructive', sign: '−' },
    refund: { label: 'Devolución', icon: Undo2, class: 'text-amber-600', sign: '−' },
};

const PAYMENT_LABELS: Record<string, string> = {
    cash: 'Efectivo', transfer: 'Transferencia', card: 'Tarjeta',
};

function submitMovement() {
    movProcessing.value = true;
    movErrors.value = {};
    router.post(
        CashMovementController.store.url({ cash: props.register.id }),
        {
            type: movType.value,
            payment_method: movMethod.value,
            amount: movAmount.value,
            description: movDescription.value,
        },
        {
            preserveScroll: true,
            onError: (e) => { movErrors.value = e; movProcessing.value = false; },
            onSuccess: () => {
                movDialog.value = false;
                movAmount.value = 0;
                movDescription.value = '';
                movType.value = 'expense';
            },
            onFinish: () => { movProcessing.value = false; },
        },
    );
}

function deleteMovement(movement: CashMovement) {
    if (!confirm(`¿Eliminar este movimiento de ${formatCOP(movement.amount)}?`)) return;
    router.delete(CashMovementController.destroy.url({ movement: movement.id }), { preserveScroll: true });
}

function submitClose() {
    closeProcessing.value = true;
    closeErrors.value = {};
    router.patch(
        CashRegisterController.close.url({ cash: props.register.id }),
        { closing_amount: closingAmount.value, closing_notes: closingNotes.value || null },
        {
            onError: (e) => { closeErrors.value = e; closeProcessing.value = false; },
            onSuccess: () => { closeDialog.value = false; },
            onFinish: () => { closeProcessing.value = false; },
        },
    );
}
</script>

<template>
    <Head :title="`Caja #${register.id}`" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Link :href="index()" class="flex w-fit items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
                <ArrowLeft class="size-4" />
                Volver al historial
            </Link>

            <div class="flex flex-wrap gap-2">
                <Dialog v-if="isOpen" v-model:open="movDialog">
                    <DialogTrigger as-child>
                        <Button variant="outline" class="gap-2">
                            <Plus class="size-4" />
                            Movimiento
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Nuevo movimiento de caja</DialogTitle>
                            <DialogDescription>Registra un ingreso o egreso manual.</DialogDescription>
                        </DialogHeader>
                        <div class="space-y-3 py-1">
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    class="flex items-center justify-center gap-2 rounded-lg border py-3 text-sm font-medium transition-colors"
                                    :class="movType === 'income'
                                        ? 'border-green-500 bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'border-sidebar-border/70 hover:bg-muted'"
                                    @click="movType = 'income'"
                                >
                                    <ArrowDownCircle class="size-4" />
                                    Ingreso
                                </button>
                                <button
                                    type="button"
                                    class="flex items-center justify-center gap-2 rounded-lg border py-3 text-sm font-medium transition-colors"
                                    :class="movType === 'expense'
                                        ? 'border-destructive/50 bg-destructive/10 text-destructive'
                                        : 'border-sidebar-border/70 hover:bg-muted'"
                                    @click="movType = 'expense'"
                                >
                                    <ArrowUpCircle class="size-4" />
                                    Egreso
                                </button>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Monto (COP)</Label>
                                <input
                                    v-model.number="movAmount"
                                    type="number"
                                    min="0"
                                    step="100"
                                    placeholder="0"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="movErrors.amount" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Método</Label>
                                <select
                                    v-model="movMethod"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                >
                                    <option value="cash">Efectivo</option>
                                    <option value="transfer">Transferencia</option>
                                    <option value="card">Tarjeta</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Descripción</Label>
                                <input
                                    v-model="movDescription"
                                    type="text"
                                    placeholder="Ej. Compra de gas"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="movErrors.description" />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="ghost" @click="movDialog = false">Cancelar</Button>
                            <Button
                                class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                                :disabled="movProcessing"
                                @click="submitMovement"
                            >
                                {{ movProcessing ? 'Guardando…' : 'Registrar' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <Dialog v-if="isOpen" v-model:open="closeDialog">
                    <DialogTrigger as-child>
                        <Button class="gap-2 bg-miralto-marron text-white hover:bg-miralto-marron/90">
                            <DoorClosed class="size-4" />
                            Cerrar caja
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Cerrar caja</DialogTitle>
                            <DialogDescription>Cuenta el efectivo final y registra el cierre.</DialogDescription>
                        </DialogHeader>
                        <div class="space-y-3 py-1">
                            <div class="rounded-lg bg-miralto-beige/50 p-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Efectivo esperado:</span>
                                    <span class="font-bold text-miralto-verde">{{ formatCOP(totals.expected_cash) }}</span>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    = Apertura + Ingresos efectivo − Egresos efectivo
                                </p>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Efectivo contado en caja (COP)</Label>
                                <input
                                    v-model.number="closingAmount"
                                    type="number"
                                    min="0"
                                    step="100"
                                    placeholder="0"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="closeErrors.closing_amount" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Notas de cierre (opcional)</Label>
                                <textarea
                                    v-model="closingNotes"
                                    rows="2"
                                    placeholder="Observaciones…"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:border-ring focus:outline-none"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="ghost" @click="closeDialog = false">Cancelar</Button>
                            <Button
                                class="bg-miralto-marron text-white hover:bg-miralto-marron/90"
                                :disabled="closeProcessing"
                                @click="submitClose"
                            >
                                {{ closeProcessing ? 'Cerrando…' : 'Cerrar caja' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>

        <!-- Status banner + summary -->
        <div class="rounded-xl border border-sidebar-border/70 bg-card p-5">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-3">
                    <div
                        class="rounded-lg p-2"
                        :class="isOpen ? 'bg-green-100 dark:bg-green-900/30' : 'bg-muted'"
                    >
                        <component :is="isOpen ? DoorOpen : DoorClosed"
                            class="size-5"
                            :class="isOpen ? 'text-green-600' : 'text-muted-foreground'"
                        />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Caja #{{ register.id }}</p>
                        <p class="text-lg font-bold">{{ register.user?.name ?? '—' }}</p>
                    </div>
                </div>
                <Badge
                    :class="isOpen
                        ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400'
                        : 'border-muted-foreground/30 bg-muted text-muted-foreground'"
                    variant="outline"
                >
                    {{ isOpen ? 'Abierta' : 'Cerrada' }}
                </Badge>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3 text-sm md:grid-cols-4">
                <div>
                    <p class="text-xs text-muted-foreground">Apertura</p>
                    <p class="font-medium">{{ formatFullDate(register.opened_at) }}</p>
                </div>
                <div v-if="register.closed_at">
                    <p class="text-xs text-muted-foreground">Cierre</p>
                    <p class="font-medium">{{ formatFullDate(register.closed_at) }}</p>
                </div>
                <div v-if="register.opening_notes" class="col-span-2 md:col-span-2">
                    <p class="text-xs text-muted-foreground">Notas apertura</p>
                    <p class="text-sm italic">{{ register.opening_notes }}</p>
                </div>
                <div v-if="register.closing_notes" class="col-span-2 md:col-span-2">
                    <p class="text-xs text-muted-foreground">Notas cierre</p>
                    <p class="text-sm italic">{{ register.closing_notes }}</p>
                </div>
            </div>
        </div>

        <!-- Stats grid -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <p class="text-xs text-muted-foreground">Apertura</p>
                <p class="mt-1 text-xl font-bold">{{ formatCOP(totals.opening_amount) }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-1.5">
                    <Banknote class="size-3.5 text-miralto-verde" />
                    <p class="text-xs text-muted-foreground">Ventas en efectivo</p>
                </div>
                <p class="mt-1 text-xl font-bold text-miralto-verde">{{ formatCOP(totals.total_sales_cash) }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-1.5">
                    <ArrowDownCircle class="size-3.5 text-green-600" />
                    <p class="text-xs text-muted-foreground">Ingresos</p>
                </div>
                <p class="mt-1 text-xl font-bold text-green-600">{{ formatCOP(totals.total_income) }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-1.5">
                    <ArrowUpCircle class="size-3.5 text-destructive" />
                    <p class="text-xs text-muted-foreground">Egresos</p>
                </div>
                <p class="mt-1 text-xl font-bold text-destructive">{{ formatCOP(totals.total_expense) }}</p>
            </div>
        </div>

        <!-- Cash summary -->
        <div class="rounded-xl border border-miralto-verde/30 bg-miralto-verde/5 p-5">
            <div class="flex items-center gap-2">
                <Wallet class="size-5 text-miralto-verde" />
                <h2 class="font-semibold text-miralto-verde">Resumen de efectivo</h2>
            </div>
            <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                <div>
                    <p class="text-xs text-muted-foreground">Efectivo esperado</p>
                    <p class="text-lg font-bold text-miralto-verde">{{ formatCOP(totals.expected_cash) }}</p>
                    <p class="text-xs text-muted-foreground">Apertura + ingresos − egresos</p>
                </div>
                <div v-if="totals.closing_amount !== null">
                    <p class="text-xs text-muted-foreground">Efectivo declarado</p>
                    <p class="text-lg font-bold">{{ formatCOP(totals.closing_amount) }}</p>
                </div>
                <div v-if="totals.difference !== null">
                    <p class="text-xs text-muted-foreground">Diferencia</p>
                    <p
                        class="flex items-center gap-1.5 text-lg font-bold"
                        :class="totals.difference === 0 ? 'text-miralto-verde' : (totals.difference > 0 ? 'text-green-600' : 'text-destructive')"
                    >
                        <AlertCircle v-if="totals.difference !== 0" class="size-4" />
                        {{ totals.difference > 0 ? '+' : '' }}{{ formatCOP(totals.difference) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Other payment methods (informational, not part of physical cash) -->
        <div v-if="totals.total_sales_other > 0 || totals.sales_by_method.transfer > 0 || totals.sales_by_method.card > 0"
            class="rounded-xl border border-sidebar-border/70 bg-card p-5"
        >
            <div class="flex items-center gap-2">
                <ArrowLeftRight class="size-5 text-miralto-marron" />
                <div>
                    <h2 class="font-semibold">Ventas por otros métodos</h2>
                    <p class="text-xs text-muted-foreground">No afectan el efectivo en caja</p>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-lg border border-sidebar-border/50 bg-muted/20 p-3">
                    <div class="flex items-center gap-1.5">
                        <ArrowLeftRight class="size-3.5 text-blue-600" />
                        <p class="text-xs text-muted-foreground">Transferencia</p>
                    </div>
                    <p class="mt-1 text-lg font-bold text-blue-700 dark:text-blue-400">
                        {{ formatCOP(totals.sales_by_method.transfer) }}
                    </p>
                </div>
                <div class="rounded-lg border border-sidebar-border/50 bg-muted/20 p-3">
                    <div class="flex items-center gap-1.5">
                        <CreditCard class="size-3.5 text-purple-600" />
                        <p class="text-xs text-muted-foreground">Tarjeta (datáfono)</p>
                    </div>
                    <p class="mt-1 text-lg font-bold text-purple-700 dark:text-purple-400">
                        {{ formatCOP(totals.sales_by_method.card) }}
                    </p>
                </div>
                <div class="rounded-lg border border-miralto-marron/20 bg-miralto-marron/5 p-3">
                    <p class="text-xs text-muted-foreground">Total no-efectivo</p>
                    <p class="mt-1 text-lg font-bold text-miralto-marron">
                        {{ formatCOP(totals.total_sales_other) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Movements -->
        <div class="rounded-xl border border-sidebar-border/70 bg-card">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-sidebar-border/70 px-5 py-3">
                <h2 class="font-semibold">Movimientos</h2>
                <select
                    v-model="filterType"
                    class="h-8 rounded-md border border-input bg-background px-2 text-xs focus:border-ring focus:outline-none"
                >
                    <option value="all">Todos</option>
                    <option value="sale">Ventas</option>
                    <option value="income">Ingresos</option>
                    <option value="expense">Egresos</option>
                    <option value="refund">Devoluciones</option>
                </select>
            </div>

            <div v-if="filteredMovements.length === 0" class="px-5 py-8 text-center text-sm text-muted-foreground">
                No hay movimientos en esta categoría.
            </div>

            <div v-else class="divide-y divide-sidebar-border/40">
                <div
                    v-for="mov in filteredMovements"
                    :key="mov.id"
                    class="flex items-center gap-3 px-5 py-3"
                >
                    <component
                        :is="TYPE_CONFIG[mov.type].icon"
                        class="size-4"
                        :class="TYPE_CONFIG[mov.type].class"
                    />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium">{{ mov.description }}</p>
                            <span class="text-xs text-muted-foreground">
                                {{ TYPE_CONFIG[mov.type].label }}
                                <template v-if="mov.payment_method">· {{ PAYMENT_LABELS[mov.payment_method] }}</template>
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDateTime(mov.created_at) }}
                            <template v-if="mov.user">· {{ mov.user.name }}</template>
                            <template v-if="mov.order_id">· Orden #{{ mov.order_id }}</template>
                        </p>
                    </div>
                    <span class="font-bold tabular-nums" :class="TYPE_CONFIG[mov.type].class">
                        {{ TYPE_CONFIG[mov.type].sign }}{{ formatCOP(mov.amount) }}
                    </span>
                    <button
                        v-if="isOpen && (mov.type === 'income' || mov.type === 'expense')"
                        class="text-muted-foreground transition-colors hover:text-destructive"
                        @click="deleteMovement(mov)"
                    >
                        <Trash2 class="size-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Linked orders -->
        <div v-if="register.orders && register.orders.length > 0" class="rounded-xl border border-sidebar-border/70 bg-card">
            <div class="border-b border-sidebar-border/70 px-5 py-3">
                <h2 class="font-semibold">Órdenes registradas en esta caja ({{ register.orders.length }})</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-muted/30">
                    <tr>
                        <th class="px-5 py-2 text-left font-medium text-muted-foreground">#</th>
                        <th class="px-5 py-2 text-left font-medium text-muted-foreground">Cliente</th>
                        <th class="hidden px-5 py-2 text-left font-medium text-muted-foreground sm:table-cell">Fecha</th>
                        <th class="px-5 py-2 text-right font-medium text-muted-foreground">Total</th>
                        <th class="px-5 py-2 text-center font-medium text-muted-foreground">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in register.orders" :key="order.id" class="border-t border-sidebar-border/30">
                        <td class="px-5 py-2 font-mono text-xs text-muted-foreground">#{{ order.id }}</td>
                        <td class="px-5 py-2">{{ order.user?.name ?? '—' }}</td>
                        <td class="hidden px-5 py-2 text-xs text-muted-foreground sm:table-cell">
                            {{ formatDateTime(order.created_at) }}
                        </td>
                        <td class="px-5 py-2 text-right font-semibold">{{ formatCOP(order.total) }}</td>
                        <td class="px-5 py-2 text-center">
                            <Badge variant="outline" class="text-xs">{{ order.status }}</Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
