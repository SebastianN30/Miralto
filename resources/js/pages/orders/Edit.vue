<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, SplitSquareHorizontal } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import * as OrderController from '@/actions/App/Http/Controllers/OrderController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Order, Wallet } from '@/types';
import { index, show } from '@/routes/orders';

type TableOption = { id: number; name: string; zone: string | null; capacity: number | null };
type EmployeeOption = { id: number; name: string; position: string | null };
type Props = { order: Order; wallets: Wallet[]; tables: TableOption[]; employees: EmployeeOption[] };

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Órdenes', href: index() },
            { title: 'Editar orden', href: '#' },
        ],
    },
});

const PAYMENT_METHODS = [
    { value: 'cash', label: 'Efectivo' },
    { value: 'transfer', label: 'Transferencia' },
    { value: 'card', label: 'Tarjeta' },
] as const;

const STATUS_OPTIONS = [
    { value: 'pending', label: 'Pendiente', dot: 'bg-amber-500' },
    { value: 'paid', label: 'Pagado', dot: 'bg-green-500' },
    { value: 'cancelled', label: 'Cancelado', dot: 'bg-red-500' },
] as const;

function formatCOP(value: number | string): string {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(value));
}

// ── Service charge ──────────────────────────────────────────
const serviceCharge = ref(props.order.service_charge);
const serviceMode = ref<'percentage' | 'fixed'>(
    props.order.service_charge && props.order.service_charge_percentage === null ? 'fixed' : 'percentage',
);
const servicePercentage = ref(Number(props.order.service_charge_percentage ?? 10));
const serviceFixedAmount = ref<number>(
    serviceMode.value === 'fixed' ? Number(props.order.service_charge_amount ?? 0) : 0,
);

const itemsSubtotal = computed(() => {
    let t = Number(props.order.total);
    if (props.order.service_charge && props.order.service_charge_amount) {
        t -= Number(props.order.service_charge_amount);
    }
    if (props.order.tax && props.order.tax_amount) {
        t -= Number(props.order.tax_amount);
    }
    return Math.round(t);
});
const computedServiceAmount = computed(() => {
    if (!serviceCharge.value) return 0;
    if (serviceMode.value === 'percentage') {
        return Math.round(itemsSubtotal.value * servicePercentage.value / 100);
    }
    return Math.round(serviceFixedAmount.value);
});

// ── Tax ──────────────────────────────────────────────────────
const tax = ref(props.order.tax);
const computedTaxAmount = computed(() => (tax.value ? Math.round(itemsSubtotal.value * 0.035) : 0));

const computedTotal = computed(() => itemsSubtotal.value + computedServiceAmount.value + computedTaxAmount.value);

// ── Payment ─────────────────────────────────────────────────
const status = ref(props.order.status);
const paymentMethod = ref(props.order.payment_method ?? 'cash');
const useSplitPayment = ref(props.order.payment_method_2 !== null);
const paymentAmount1 = ref<number>(Number(props.order.payment_amount_1 ?? computedTotal.value));
const paymentMethod2 = ref(props.order.payment_method_2 ?? '');
const selectedWalletId1 = ref<number | null>(null);
const selectedWalletId2 = ref<number | null>(null);

// ── Table ────────────────────────────────────────────────────
const selectedTableId = ref<number | null>(props.order.table_id);
const freeTableName = ref(props.order.table_id ? '' : (props.order.table_name ?? ''));

// ── Employee ─────────────────────────────────────────────────
const selectedEmployeeId = ref<number | null>(props.order.employee_id);

// ── Security key (for paid orders) ─────────────────────────
const securityKey = ref('');

const processing = ref(false);
const errors = ref<Partial<Record<string, string>>>({});

const paymentAmount2 = computed(() =>
    useSplitPayment.value ? Math.max(0, computedTotal.value - paymentAmount1.value) : 0,
);

const availableSecondMethods = computed(() =>
    PAYMENT_METHODS.filter((m) => m.value !== paymentMethod.value),
);

watch(useSplitPayment, (val) => {
    if (!val) {
        paymentMethod2.value = '';
        selectedWalletId2.value = null;
    } else {
        paymentAmount1.value = computedTotal.value;
    }
});

watch(paymentMethod, () => { selectedWalletId1.value = null; });
watch(paymentMethod2, () => { selectedWalletId2.value = null; });

function submit() {
    if (useSplitPayment.value && !paymentMethod2.value) {
        errors.value = { payment_method_2: 'Selecciona el segundo método de pago.' };
        return;
    }
    processing.value = true;
    errors.value = {};

    router.patch(
        OrderController.update.url({ order: props.order.id }),
        {
            status: status.value,
            table_id: selectedTableId.value || null,
            table_name: selectedTableId.value ? null : freeTableName.value.trim() || null,
            employee_id: selectedEmployeeId.value,
            payment_method: paymentMethod.value || null,
            payment_amount_1: useSplitPayment.value ? paymentAmount1.value : null,
            payment_method_2: useSplitPayment.value ? paymentMethod2.value : null,
            payment_amount_2: useSplitPayment.value ? paymentAmount2.value : null,
            notes: (document.getElementById('notes') as HTMLTextAreaElement)?.value ?? null,
            service_charge: serviceCharge.value,
            service_charge_percentage: (serviceCharge.value && serviceMode.value === 'percentage') ? servicePercentage.value : null,
            service_charge_custom_amount: (serviceCharge.value && serviceMode.value === 'fixed') ? serviceFixedAmount.value : null,
            tax: tax.value,
            wallet_id_1: paymentMethod.value === 'transfer' ? selectedWalletId1.value : null,
            wallet_id_2: (useSplitPayment.value && paymentMethod2.value === 'transfer') ? selectedWalletId2.value : null,
            security_key: props.order.status === 'paid' ? securityKey.value : null,
        },
        {
            onError: (e) => { errors.value = e; processing.value = false; },
            onFinish: () => { processing.value = false; },
        },
    );
}
</script>

<template>
    <Head :title="`Editar orden #${order.id}`" />

    <div class="flex flex-col gap-6 p-4">
        <Link :href="show({ order: order.id })" class="flex w-fit items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground">
            <ArrowLeft class="size-4" />
            Volver a la orden
        </Link>

        <div class="mx-auto w-full max-w-lg rounded-xl border border-sidebar-border/70 bg-card">
            <div class="border-b border-sidebar-border/70 px-6 py-4">
                <h1 class="font-semibold">Editar Orden <span class="font-mono text-muted-foreground">#{{ order.id }}</span></h1>
                <p class="mt-0.5 text-sm text-muted-foreground">Total de la orden: <strong>{{ formatCOP(computedTotal) }}</strong></p>
            </div>

            <div class="space-y-5 px-6 py-5">
                <!-- Security key — only for already-paid orders -->
                <div v-if="order.status === 'paid'" class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20">
                    <p class="mb-2 text-sm font-medium text-amber-700 dark:text-amber-400">
                        Esta orden ya fue pagada. Ingresa la clave de seguridad para modificarla.
                    </p>
                    <input
                        v-model="securityKey"
                        type="password"
                        placeholder="Clave de seguridad"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                    />
                    <InputError :message="errors.security_key" />
                </div>

                <!-- Status -->
                <div class="space-y-2">
                    <Label>Estado de la orden</Label>
                    <div class="grid grid-cols-3 gap-2">
                        <label
                            v-for="opt in STATUS_OPTIONS"
                            :key="opt.value"
                            class="flex cursor-pointer flex-col items-center gap-1.5 rounded-lg border p-3 text-sm font-medium transition-colors"
                            :class="status === opt.value
                                ? 'border-miralto-verde bg-miralto-verde/10 text-miralto-verde'
                                : 'border-sidebar-border/70 hover:bg-muted'"
                        >
                            <input type="radio" name="status" :value="opt.value" :checked="status === opt.value" class="sr-only" @change="status = opt.value" />
                            <span class="size-2.5 rounded-full" :class="opt.dot" />
                            {{ opt.label }}
                        </label>
                    </div>
                    <InputError :message="errors.status" />
                </div>

                <!-- Table -->
                <div class="space-y-2">
                    <Label for="table_name">Mesa (opcional)</Label>
                    <div v-if="tables.length > 0" class="flex flex-wrap gap-1.5">
                        <button
                            v-for="t in tables"
                            :key="t.id"
                            type="button"
                            class="rounded-md border px-2.5 py-1 text-xs font-medium transition-colors"
                            :class="selectedTableId === t.id
                                ? 'border-miralto-verde bg-miralto-verde/10 text-miralto-verde'
                                : 'border-sidebar-border/70 hover:border-miralto-verde/40'"
                            @click="selectedTableId = selectedTableId === t.id ? null : t.id"
                        >
                            {{ t.name }}<span v-if="t.zone" class="opacity-60"> · {{ t.zone }}</span>
                        </button>
                    </div>
                    <input
                        v-if="selectedTableId === null"
                        id="table_name"
                        v-model="freeTableName"
                        type="text"
                        maxlength="100"
                        placeholder="Ej. P1, C2…"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                    />
                    <InputError :message="errors.table_name" />
                </div>

                <!-- Employee -->
                <div v-if="employees.length > 0" class="space-y-2">
                    <Label for="employee_id">Empleado (opcional)</Label>
                    <select
                        id="employee_id"
                        v-model="selectedEmployeeId"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm text-foreground focus:border-ring focus:outline-none"
                    >
                        <option :value="null">No es de empleado</option>
                        <option v-for="e in employees" :key="e.id" :value="e.id">
                            {{ e.name }}<template v-if="e.position"> · {{ e.position }}</template>
                        </option>
                    </select>
                    <InputError :message="errors.employee_id" />
                </div>

                <!-- Primary payment method -->
                <div class="space-y-2">
                    <Label>{{ useSplitPayment ? 'Primer método de pago' : 'Método de pago' }}</Label>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="method in PAYMENT_METHODS"
                            :key="method.value"
                            type="button"
                            class="rounded-lg border py-2 text-sm font-medium transition-colors"
                            :class="paymentMethod === method.value
                                ? 'border-miralto-verde bg-miralto-verde/10 text-miralto-verde'
                                : 'border-sidebar-border/70 hover:bg-muted'"
                            @click="paymentMethod = method.value"
                        >{{ method.label }}</button>
                    </div>
                </div>

                <!-- Wallet selector for first transfer method -->
                <div v-if="paymentMethod === 'transfer' && wallets.length > 0" class="space-y-1.5">
                    <label class="text-xs font-medium text-muted-foreground">Billetera de destino</label>
                    <select
                        v-model="selectedWalletId1"
                        class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                    >
                        <option :value="null">Sin billetera específica</option>
                        <option v-for="wallet in wallets" :key="wallet.id" :value="wallet.id">
                            {{ wallet.name }}
                        </option>
                    </select>
                </div>

                <!-- Split payment toggle -->
                <button
                    type="button"
                    class="flex w-full items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors"
                    :class="useSplitPayment
                        ? 'border-miralto-marron/50 bg-miralto-marron/10 text-miralto-marron'
                        : 'border-sidebar-border/70 text-muted-foreground hover:bg-muted'"
                    @click="useSplitPayment = !useSplitPayment"
                >
                    <SplitSquareHorizontal class="size-4" />
                    {{ useSplitPayment ? 'Cancelar división de pago' : 'Dividir pago entre dos métodos' }}
                </button>

                <!-- Split payment details -->
                <div v-if="useSplitPayment" class="space-y-3 rounded-lg border border-miralto-marron/20 bg-miralto-beige/30 p-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">
                            Monto con {{ PAYMENT_METHODS.find(m => m.value === paymentMethod)?.label ?? paymentMethod }}
                        </label>
                        <input
                            v-model.number="paymentAmount1"
                            type="number"
                            :min="0"
                            :max="computedTotal"
                            step="100"
                            class="h-8 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                        />
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Resta: {{ new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(paymentAmount2) }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Segundo método</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            <button
                                v-for="method in availableSecondMethods"
                                :key="method.value"
                                type="button"
                                class="rounded-lg border py-1.5 text-sm font-medium transition-colors"
                                :class="paymentMethod2 === method.value
                                    ? 'border-miralto-marron bg-miralto-marron/10 text-miralto-marron'
                                    : 'border-sidebar-border/70 hover:bg-muted'"
                                @click="paymentMethod2 = method.value"
                            >{{ method.label }}: {{ new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(paymentAmount2) }}</button>
                        </div>
                        <InputError v-if="errors.payment_method_2" :message="errors.payment_method_2" />
                    </div>

                    <!-- Wallet selector for second transfer method -->
                    <div v-if="paymentMethod2 === 'transfer' && wallets.length > 0" class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground">Billetera destino (segundo método)</label>
                        <select
                            v-model="selectedWalletId2"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                        >
                            <option :value="null">Sin billetera específica</option>
                            <option v-for="wallet in wallets" :key="wallet.id" :value="wallet.id">
                                {{ wallet.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Service charge -->
                <div class="space-y-2">
                    <Label>Cargo por servicio / propina</Label>
                    <div
                        class="rounded-lg border transition-colors"
                        :class="serviceCharge ? 'border-miralto-verde bg-miralto-verde/5' : 'border-sidebar-border/70'"
                    >
                        <div class="flex cursor-pointer items-center justify-between p-3" @click="serviceCharge = !serviceCharge">
                            <div>
                                <p class="text-sm font-medium">Incluir cargo por servicio</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ serviceCharge
                                        ? serviceMode === 'percentage'
                                            ? `${servicePercentage}% = ${formatCOP(computedServiceAmount)} → Total: ${formatCOP(computedTotal)}`
                                            : `Monto fijo ${formatCOP(serviceFixedAmount)} → Total: ${formatCOP(computedTotal)}`
                                        : 'Toca para activar' }}
                                </p>
                            </div>
                            <div
                                class="flex size-5 items-center justify-center rounded-full border-2 transition-colors"
                                :class="serviceCharge ? 'border-miralto-verde bg-miralto-verde' : 'border-muted-foreground/40'"
                            >
                                <CheckCircle v-if="serviceCharge" class="size-3.5 text-white" />
                            </div>
                        </div>
                        <div v-if="serviceCharge" class="space-y-3 border-t border-miralto-verde/20 px-3 pb-3 pt-2">
                            <!-- Mode toggle -->
                            <div class="grid grid-cols-2 gap-1 rounded-md border border-input p-1 text-xs">
                                <button
                                    type="button"
                                    class="rounded py-1.5 font-medium transition-colors"
                                    :class="serviceMode === 'percentage' ? 'bg-miralto-verde text-white' : 'text-muted-foreground hover:bg-muted'"
                                    @click.stop="serviceMode = 'percentage'"
                                >Porcentaje</button>
                                <button
                                    type="button"
                                    class="rounded py-1.5 font-medium transition-colors"
                                    :class="serviceMode === 'fixed' ? 'bg-miralto-verde text-white' : 'text-muted-foreground hover:bg-muted'"
                                    @click.stop="serviceMode = 'fixed'"
                                >Valor fijo</button>
                            </div>
                            <!-- Percentage input -->
                            <div v-if="serviceMode === 'percentage'" class="flex items-center gap-2">
                                <input
                                    v-model.number="servicePercentage"
                                    type="number"
                                    min="1"
                                    max="100"
                                    step="1"
                                    class="h-8 w-20 rounded-md border border-input bg-background px-2 text-right text-sm focus:border-ring focus:outline-none"
                                    @click.stop
                                />
                                <span class="text-sm text-muted-foreground">%</span>
                            </div>
                            <!-- Fixed amount input -->
                            <div v-else>
                                <input
                                    v-model.number="serviceFixedAmount"
                                    type="number"
                                    min="0"
                                    step="100"
                                    placeholder="Ej: 5000"
                                    class="h-8 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                                    @click.stop
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tax -->
                <div class="space-y-2">
                    <Label>Impuesto</Label>
                    <div
                        class="cursor-pointer rounded-lg border transition-colors"
                        :class="tax ? 'border-miralto-verde bg-miralto-verde/5' : 'border-sidebar-border/70'"
                        @click="tax = !tax"
                    >
                        <div class="flex items-center justify-between p-3">
                            <div>
                                <p class="text-sm font-medium">Impuesto (3.5%)</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ tax
                                        ? `Incluir impuesto del 3.5% → Total: ${formatCOP(computedTotal)}`
                                        : 'Toca para activar' }}
                                </p>
                            </div>
                            <div
                                class="flex size-5 items-center justify-center rounded-full border-2 transition-colors"
                                :class="tax ? 'border-miralto-verde bg-miralto-verde' : 'border-muted-foreground/40'"
                            >
                                <CheckCircle v-if="tax" class="size-3.5 text-white" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">Notas (opcional)</Label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        :value="order.notes ?? ''"
                        placeholder="Observaciones sobre la orden…"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                    />
                    <InputError :message="errors.notes" />
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 border-t border-sidebar-border/40 pt-4">
                    <Button
                        class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                        :disabled="processing"
                        @click="submit"
                    >
                        {{ processing ? 'Guardando…' : 'Guardar cambios' }}
                    </Button>
                    <Link :href="show({ order: order.id })">
                        <Button variant="ghost" type="button">Cancelar</Button>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
