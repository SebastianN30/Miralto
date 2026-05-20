<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Wallet,
    TrendingUp,
    TrendingDown,
    ArrowDownLeft,
    ArrowUpRight,
    CircleDollarSign,
    Plus,
    Trash2,
    Pencil,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import * as WalletController from '@/actions/App/Http/Controllers/WalletController';
import * as WalletTransactionController from '@/actions/App/Http/Controllers/WalletTransactionController';
import { index } from '@/actions/App/Http/Controllers/WalletController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import type { Wallet as WalletModel, WalletTransaction, WalletTransactionType, WalletType } from '@/types';

type Totals = {
    current_balance: number;
    total_income: number;
    total_payments: number;
    total_expenses: number;
    total_inbound: number;
    total_outbound: number;
};

type Props = {
    wallet: WalletModel & { transactions: WalletTransaction[] };
    totals: Totals;
};

const props = defineProps<Props>();
const page = usePage<{ auth: { user: { role: string } } }>();
const isAdmin = computed(() => page.props.auth.user.role === 'admin');

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Billeteras', href: '/wallets' },
            { title: 'Detalle', href: '#' },
        ],
    },
});

// ---- Filtro de transacciones ----
const filterType = ref<WalletTransactionType | 'all'>('all');

const filteredTransactions = computed(() => {
    if (filterType.value === 'all') {
        return props.wallet.transactions;
    }
    return props.wallet.transactions.filter((t) => t.type === filterType.value);
});

// ---- Dialog nueva transacción ----
const showTxDialog = ref(false);
const processingTx = ref(false);
const txErrors = ref<Partial<Record<string, string>>>({});

const txForm = ref({
    type: 'payment' as WalletTransactionType,
    amount: 0,
    description: '',
    reference: '',
    transaction_date: new Date().toISOString().slice(0, 10),
});

function submitTransaction() {
    processingTx.value = true;
    txErrors.value = {};
    router.post(
        WalletTransactionController.store.url(props.wallet),
        {
            ...txForm.value,
            reference: txForm.value.reference || null,
        },
        {
            onError: (e) => {
                txErrors.value = e;
                processingTx.value = false;
            },
            onSuccess: () => {
                showTxDialog.value = false;
                txForm.value = {
                    type: 'payment',
                    amount: 0,
                    description: '',
                    reference: '',
                    transaction_date: new Date().toISOString().slice(0, 10),
                };
            },
            onFinish: () => {
                processingTx.value = false;
            },
        },
    );
}

// ---- Dialog editar billetera ----
const showEditDialog = ref(false);
const processingEdit = ref(false);
const editErrors = ref<Partial<Record<string, string>>>({});

const editForm = ref({
    name: props.wallet.name,
    type: props.wallet.type,
    account_identifier: props.wallet.account_identifier ?? '',
    initial_balance: Number(props.wallet.initial_balance),
    is_active: props.wallet.is_active,
    notes: props.wallet.notes ?? '',
});

function submitEdit() {
    processingEdit.value = true;
    editErrors.value = {};
    router.patch(
        WalletController.update.url(props.wallet),
        {
            ...editForm.value,
            account_identifier: editForm.value.account_identifier || null,
            notes: editForm.value.notes || null,
        },
        {
            onError: (e) => {
                editErrors.value = e;
                processingEdit.value = false;
            },
            onSuccess: () => {
                showEditDialog.value = false;
            },
            onFinish: () => {
                processingEdit.value = false;
            },
        },
    );
}

// ---- Eliminar transacción ----
function deleteTransaction(tx: WalletTransaction) {
    if (!confirm('¿Eliminar esta transacción? Esta acción no se puede deshacer.')) {
        return;
    }
    router.delete(`/wallets/transactions/${tx.id}`);
}

// ---- Helpers ----
function formatCOP(value: number | string): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function formatDate(dateStr: string | null | undefined): string {
    if (!dateStr) return '—';
    return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(dateStr.substring(0, 10) + 'T12:00:00'));
}

const walletTypeLabels: Record<WalletType, string> = {
    nubank: 'NUBANK / Llave',
    nequi: 'Nequi',
    daviplata: 'Daviplata',
    other: 'Otro',
};

const walletTypeColors: Record<WalletType, string> = {
    nubank: 'border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    nequi: 'border-pink-200 bg-pink-50 text-pink-700 dark:border-pink-800 dark:bg-pink-900/30 dark:text-pink-400',
    daviplata: 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    other: 'border-muted-foreground/30 bg-muted text-muted-foreground',
};

const txTypeLabels: Record<WalletTransactionType, string> = {
    income: 'Ingreso',
    payment: 'Pago recibido',
    expense: 'Egreso',
};

const txTypeConfig: Record<WalletTransactionType, { icon: typeof ArrowDownLeft; color: string; bg: string }> = {
    income: { icon: ArrowDownLeft, color: 'text-green-600 dark:text-green-400', bg: 'bg-green-100 dark:bg-green-900/30' },
    payment: { icon: CircleDollarSign, color: 'text-miralto-verde', bg: 'bg-miralto-verde/10' },
    expense: { icon: ArrowUpRight, color: 'text-destructive', bg: 'bg-destructive/10' },
};
</script>

<template>
    <Head :title="wallet.name" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <Wallet class="size-5 text-muted-foreground" />
                    <h1 class="text-xl font-bold">{{ wallet.name }}</h1>
                    <Badge variant="outline" :class="walletTypeColors[wallet.type]">
                        {{ walletTypeLabels[wallet.type] }}
                    </Badge>
                    <Badge
                        v-if="!wallet.is_active"
                        variant="outline"
                        class="border-destructive/30 bg-destructive/10 text-destructive"
                    >
                        Inactiva
                    </Badge>
                </div>
                <p v-if="wallet.account_identifier" class="font-mono text-sm text-muted-foreground">
                    {{ wallet.account_identifier }}
                </p>
            </div>

            <div class="flex gap-2">
                <!-- Editar billetera -->
                <Dialog v-model:open="showEditDialog">
                    <DialogTrigger as-child>
                        <Button variant="outline" size="sm" class="gap-1.5">
                            <Pencil class="size-3.5" />
                            Editar
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Editar billetera</DialogTitle>
                            <DialogDescription>Actualiza los datos de la billetera.</DialogDescription>
                        </DialogHeader>
                        <div class="space-y-3 py-1">
                            <div class="space-y-1.5">
                                <Label>Tipo</Label>
                                <select
                                    v-model="editForm.type"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                >
                                    <option value="nubank">NUBANK / Llave</option>
                                    <option value="nequi">Nequi</option>
                                    <option value="daviplata">Daviplata</option>
                                    <option value="other">Otro</option>
                                </select>
                                <InputError :message="editErrors.type" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Nombre</Label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="editErrors.name" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Número / Llave</Label>
                                <input
                                    v-model="editForm.account_identifier"
                                    type="text"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Saldo inicial (COP)</Label>
                                <input
                                    v-model.number="editForm.initial_balance"
                                    type="number"
                                    min="0"
                                    step="100"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="editErrors.initial_balance" />
                            </div>
                            <div class="flex items-center gap-2">
                                <input
                                    id="is_active"
                                    v-model="editForm.is_active"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                />
                                <Label for="is_active">Billetera activa</Label>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Notas</Label>
                                <textarea
                                    v-model="editForm.notes"
                                    rows="2"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:border-ring focus:outline-none"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="ghost" @click="showEditDialog = false">Cancelar</Button>
                            <Button
                                class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                                :disabled="processingEdit"
                                @click="submitEdit"
                            >
                                {{ processingEdit ? 'Guardando…' : 'Guardar cambios' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <!-- Nueva transacción -->
                <Dialog v-model:open="showTxDialog">
                    <DialogTrigger as-child>
                        <Button
                            class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90"
                            :disabled="!wallet.is_active"
                        >
                            <Plus class="size-4" />
                            Registrar movimiento
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Registrar movimiento</DialogTitle>
                            <DialogDescription>
                                Ingresa los datos del movimiento en <strong>{{ wallet.name }}</strong>.
                            </DialogDescription>
                        </DialogHeader>
                        <div class="space-y-3 py-1">
                            <div class="space-y-1.5">
                                <Label>Tipo de movimiento</Label>
                                <select
                                    v-model="txForm.type"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                >
                                    <option value="payment">Pago recibido (cliente pagó por aquí)</option>
                                    <option value="income">Ingreso (otro ingreso de dinero)</option>
                                    <option value="expense">Egreso (salida de dinero)</option>
                                </select>
                                <InputError :message="txErrors.type" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Monto (COP)</Label>
                                <input
                                    v-model.number="txForm.amount"
                                    type="number"
                                    min="0.01"
                                    step="100"
                                    placeholder="0"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="txErrors.amount" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Descripción</Label>
                                <input
                                    v-model="txForm.description"
                                    type="text"
                                    placeholder="Ej: Transferencia mesa 5, pago pedido #12…"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="txErrors.description" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Referencia (opcional)</Label>
                                <input
                                    v-model="txForm.reference"
                                    type="text"
                                    placeholder="Ej: REF-123456"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Fecha</Label>
                                <input
                                    v-model="txForm.transaction_date"
                                    type="date"
                                    class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                />
                                <InputError :message="txErrors.transaction_date" />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="ghost" @click="showTxDialog = false">Cancelar</Button>
                            <Button
                                class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                                :disabled="processingTx"
                                @click="submitTransaction"
                            >
                                {{ processingTx ? 'Guardando…' : 'Registrar' }}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>

        <!-- Resumen de saldo -->
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <p class="text-xs text-muted-foreground">Saldo actual</p>
                <p
                    class="mt-1 text-2xl font-bold"
                    :class="totals.current_balance >= 0 ? 'text-miralto-verde' : 'text-destructive'"
                >
                    {{ formatCOP(totals.current_balance) }}
                </p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    Inicial: {{ formatCOP(wallet.initial_balance) }}
                </p>
            </div>

            <div class="rounded-xl border border-green-200/60 bg-green-50/40 p-4 dark:border-green-800/40 dark:bg-green-900/10">
                <div class="flex items-center gap-1.5">
                    <TrendingUp class="size-3.5 text-green-600" />
                    <p class="text-xs text-muted-foreground">Pagos recibidos</p>
                </div>
                <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">{{ formatCOP(totals.total_payments) }}</p>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-1.5">
                    <ArrowDownLeft class="size-3.5 text-green-500" />
                    <p class="text-xs text-muted-foreground">Otros ingresos</p>
                </div>
                <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">{{ formatCOP(totals.total_income) }}</p>
            </div>

            <div class="rounded-xl border border-red-200/60 bg-red-50/40 p-4 dark:border-red-800/40 dark:bg-red-900/10">
                <div class="flex items-center gap-1.5">
                    <TrendingDown class="size-3.5 text-destructive" />
                    <p class="text-xs text-muted-foreground">Egresos</p>
                </div>
                <p class="mt-1 text-xl font-bold text-destructive">{{ formatCOP(totals.total_expenses) }}</p>
            </div>
        </div>

        <!-- Transacciones -->
        <div class="space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold">Movimientos</h2>
                <!-- Filtro -->
                <div class="flex gap-1">
                    <button
                        v-for="opt in (['all', 'payment', 'income', 'expense'] as const)"
                        :key="opt"
                        class="rounded-md px-3 py-1 text-xs transition-colors"
                        :class="filterType === opt
                            ? 'bg-miralto-verde text-white'
                            : 'border border-sidebar-border/70 hover:bg-muted'"
                        @click="filterType = opt"
                    >
                        {{ opt === 'all' ? 'Todos' : txTypeLabels[opt] }}
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-sidebar-border/70">
                <table class="w-full text-sm">
                    <thead class="border-b border-sidebar-border/70 bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Tipo</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Descripción</th>
                            <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground sm:table-cell">Referencia</th>
                            <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground md:table-cell">Fecha</th>
                            <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground lg:table-cell">Usuario</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Monto</th>
                            <th v-if="isAdmin" class="px-4 py-3 text-right font-medium text-muted-foreground"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="filteredTransactions.length === 0">
                            <td colspan="7" class="px-4 py-12 text-center text-muted-foreground">
                                Sin movimientos registrados.
                            </td>
                        </tr>
                        <tr
                            v-for="tx in filteredTransactions"
                            :key="tx.id"
                            class="border-b border-sidebar-border/40 transition-colors last:border-0 hover:bg-muted/30"
                        >
                            <td class="px-4 py-3">
                                <div
                                    class="flex w-fit items-center gap-1.5 rounded-full px-2.5 py-0.5"
                                    :class="txTypeConfig[tx.type].bg"
                                >
                                    <component
                                        :is="txTypeConfig[tx.type].icon"
                                        class="size-3.5"
                                        :class="txTypeConfig[tx.type].color"
                                    />
                                    <span class="text-xs font-medium" :class="txTypeConfig[tx.type].color">
                                        {{ txTypeLabels[tx.type] }}
                                    </span>
                                </div>
                            </td>
                            <td class="max-w-[200px] truncate px-4 py-3">{{ tx.description }}</td>
                            <td class="hidden px-4 py-3 font-mono text-xs text-muted-foreground sm:table-cell">
                                {{ tx.reference ?? '—' }}
                            </td>
                            <td class="hidden px-4 py-3 text-xs text-muted-foreground md:table-cell">
                                {{ formatDate(tx.transaction_date) }}
                            </td>
                            <td class="hidden px-4 py-3 text-xs text-muted-foreground lg:table-cell">
                                {{ tx.user?.name ?? '—' }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-semibold tabular-nums"
                                :class="tx.type === 'expense' ? 'text-destructive' : 'text-miralto-verde'"
                            >
                                {{ tx.type === 'expense' ? '−' : '+' }}{{ formatCOP(tx.amount) }}
                            </td>
                            <td v-if="isAdmin" class="px-4 py-3 text-right">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 w-7 p-0 text-muted-foreground hover:text-destructive"
                                    @click="deleteTransaction(tx)"
                                >
                                    <Trash2 class="size-3.5" />
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
