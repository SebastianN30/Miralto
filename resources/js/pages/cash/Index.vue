<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Wallet, DoorOpen, DoorClosed, TrendingUp, Plus, ArrowRight } from 'lucide-vue-next';
import { ref } from 'vue';
import * as CashRegisterController from '@/actions/App/Http/Controllers/CashRegisterController';
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
import type { CashRegister, PaginatedData } from '@/types';
import { index, show } from '@/routes/cash';

type Props = {
    registers: PaginatedData<CashRegister>;
    openRegister: { id: number; opened_at: string; opening_amount: string } | null;
    stats: {
        total_sessions: number;
        open_sessions: number;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Caja', href: '/cash' }],
    },
});

const openDialog = ref(false);
const openingAmount = ref<number>(0);
const openingNotes = ref('');
const processing = ref(false);
const errors = ref<Partial<Record<string, string>>>({});

function formatCOP(value: number | string): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(Number(value));
}

function formatDate(dateStr: string): string {
    return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
    }).format(new Date(dateStr));
}

function submitOpen() {
    processing.value = true;
    errors.value = {};
    router.post(
        CashRegisterController.store.url(),
        { opening_amount: openingAmount.value, opening_notes: openingNotes.value || null },
        {
            onError: (e) => { errors.value = e; processing.value = false; },
            onSuccess: () => { openDialog.value = false; openingAmount.value = 0; openingNotes.value = ''; },
            onFinish: () => { processing.value = false; },
        },
    );
}
</script>

<template>
    <Head title="Caja" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Stats -->
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-verde/10 p-2">
                        <Wallet class="size-5 text-miralto-verde" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total cajas</p>
                        <p class="text-2xl font-bold">{{ stats.total_sessions }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30">
                        <DoorOpen class="size-5 text-green-600" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Cajas abiertas</p>
                        <p class="text-2xl font-bold">{{ stats.open_sessions }}</p>
                    </div>
                </div>
            </div>

            <!-- Open register quick access -->
            <Link
                v-if="openRegister"
                :href="show({ cash: openRegister.id })"
                class="flex items-center justify-between rounded-xl border border-miralto-verde/30 bg-miralto-verde/5 p-4 transition-colors hover:bg-miralto-verde/10"
            >
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-verde/20 p-2">
                        <TrendingUp class="size-5 text-miralto-verde" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Caja activa</p>
                        <p class="font-bold text-miralto-verde">{{ formatCOP(openRegister.opening_amount) }}</p>
                    </div>
                </div>
                <ArrowRight class="size-4 text-miralto-verde" />
            </Link>
            <div v-else class="rounded-xl border border-dashed border-amber-300 bg-amber-50/50 p-4 dark:border-amber-700 dark:bg-amber-900/10">
                <p class="text-xs text-amber-700 dark:text-amber-400">Sin caja activa</p>
                <p class="text-sm text-muted-foreground">Abre una caja para empezar</p>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold">Historial de cajas</h1>

            <Dialog v-model:open="openDialog">
                <DialogTrigger as-child>
                    <Button :disabled="!!openRegister" class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                        <Plus class="size-4" />
                        Abrir caja
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Abrir nueva caja</DialogTitle>
                        <DialogDescription>Registra el monto inicial de efectivo en caja.</DialogDescription>
                    </DialogHeader>
                    <div class="space-y-3 py-1">
                        <div class="space-y-1.5">
                            <Label>Monto de apertura (COP)</Label>
                            <input
                                v-model.number="openingAmount"
                                type="number"
                                min="0"
                                step="100"
                                placeholder="0"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            />
                            <InputError :message="errors.opening_amount" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Notas (opcional)</Label>
                            <textarea
                                v-model="openingNotes"
                                rows="2"
                                placeholder="Observaciones de apertura…"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:border-ring focus:outline-none"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" @click="openDialog = false">Cancelar</Button>
                        <Button
                            class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                            :disabled="processing"
                            @click="submitOpen"
                        >
                            {{ processing ? 'Abriendo…' : 'Abrir caja' }}
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
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">#</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Apertura</th>
                        <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground sm:table-cell">Usuario</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Inicial</th>
                        <th class="hidden px-4 py-3 text-right font-medium text-muted-foreground lg:table-cell">Ventas efectivo</th>
                        <th class="hidden px-4 py-3 text-right font-medium text-muted-foreground xl:table-cell">Otros</th>
                        <th class="hidden px-4 py-3 text-right font-medium text-muted-foreground lg:table-cell">Egresos</th>
                        <th class="px-4 py-3 text-center font-medium text-muted-foreground">Estado</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="registers.data.length === 0">
                        <td colspan="9" class="px-4 py-12 text-center text-muted-foreground">
                            Aún no hay cajas registradas.
                        </td>
                    </tr>
                    <tr
                        v-for="reg in registers.data"
                        :key="reg.id"
                        class="border-b border-sidebar-border/40 transition-colors last:border-0 hover:bg-muted/30"
                    >
                        <td class="px-4 py-3 font-mono text-xs text-muted-foreground">#{{ reg.id }}</td>
                        <td class="px-4 py-3 text-xs text-muted-foreground">{{ formatDate(reg.opened_at) }}</td>
                        <td class="hidden px-4 py-3 sm:table-cell">{{ reg.user?.name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-medium">{{ formatCOP(reg.opening_amount) }}</td>
                        <td class="hidden px-4 py-3 text-right text-miralto-verde lg:table-cell">
                            {{ formatCOP(reg.total_sales_cash ?? 0) }}
                        </td>
                        <td class="hidden px-4 py-3 text-right text-miralto-marron xl:table-cell">
                            {{ formatCOP(reg.total_sales_other ?? 0) }}
                        </td>
                        <td class="hidden px-4 py-3 text-right text-destructive lg:table-cell">
                            {{ formatCOP(reg.total_expense ?? 0) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <Badge
                                v-if="reg.status === 'open'"
                                variant="outline"
                                class="gap-1 border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400"
                            >
                                <DoorOpen class="size-3" />
                                Abierta
                            </Badge>
                            <Badge
                                v-else
                                variant="outline"
                                class="gap-1 border-muted-foreground/30 bg-muted text-muted-foreground"
                            >
                                <DoorClosed class="size-3" />
                                Cerrada
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="show({ cash: reg.id })">
                                <Button variant="ghost" size="sm" class="h-7 px-2 text-xs">Ver</Button>
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="registers.last_page > 1" class="flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
                Mostrando {{ registers.from }} – {{ registers.to }} de {{ registers.total }}
            </p>
            <div class="flex gap-1">
                <template v-for="link in registers.links" :key="link.label">
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
