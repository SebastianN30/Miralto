<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, SplitSquareHorizontal } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import * as OrderController from '@/actions/App/Http/Controllers/OrderController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Order } from '@/types';
import { index, show } from '@/routes/orders';

type Props = { order: Order };

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

const total = Number(props.order.total);

// Form state
const status = ref(props.order.status);
const paymentMethod = ref(props.order.payment_method ?? 'cash');
const useSplitPayment = ref(props.order.payment_method_2 !== null);
const paymentAmount1 = ref<number>(Number(props.order.payment_amount_1 ?? total));
const paymentMethod2 = ref(props.order.payment_method_2 ?? '');
const processing = ref(false);
const errors = ref<Partial<Record<string, string>>>({});

const paymentAmount2 = computed(() =>
    useSplitPayment.value ? Math.max(0, total - paymentAmount1.value) : 0,
);

const availableSecondMethods = computed(() =>
    PAYMENT_METHODS.filter((m) => m.value !== paymentMethod.value),
);

watch(useSplitPayment, (val) => {
    if (!val) { paymentMethod2.value = ''; }
    else { paymentAmount1.value = total; }
});

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
            payment_method: paymentMethod.value || null,
            payment_amount_1: useSplitPayment.value ? paymentAmount1.value : null,
            payment_method_2: useSplitPayment.value ? paymentMethod2.value : null,
            payment_amount_2: useSplitPayment.value ? paymentAmount2.value : null,
            notes: (document.getElementById('notes') as HTMLTextAreaElement)?.value ?? null,
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
                <p class="mt-0.5 text-sm text-muted-foreground">Total de la orden: <strong>{{ new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(total) }}</strong></p>
            </div>

            <div class="space-y-5 px-6 py-5">
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
                            :max="total"
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
