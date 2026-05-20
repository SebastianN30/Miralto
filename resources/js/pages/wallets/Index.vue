<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Wallet, Plus, TrendingUp, TrendingDown, ArrowRight, CircleDollarSign } from 'lucide-vue-next';
import { ref } from 'vue';
import * as WalletController from '@/actions/App/Http/Controllers/WalletController';
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
import type { WalletType, Wallet as WalletModel } from '@/types';
import { show } from '@/actions/App/Http/Controllers/WalletController';

type Props = {
    wallets: (WalletModel & { current_balance: number })[];
    stats: {
        total_wallets: number;
        active_wallets: number;
        total_balance: number;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Billeteras', href: '/wallets' }],
    },
});

const showAddDialog = ref(false);
const processing = ref(false);
const errors = ref<Partial<Record<string, string>>>({});

const form = ref({
    name: '',
    type: 'nequi' as WalletType,
    account_identifier: '',
    initial_balance: 0,
    notes: '',
});

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

const walletBgAccents: Record<WalletType, string> = {
    nubank: 'border-purple-200/60 bg-purple-50/40 dark:border-purple-800/40 dark:bg-purple-900/10',
    nequi: 'border-pink-200/60 bg-pink-50/40 dark:border-pink-800/40 dark:bg-pink-900/10',
    daviplata: 'border-orange-200/60 bg-orange-50/40 dark:border-orange-800/40 dark:bg-orange-900/10',
    other: 'border-sidebar-border/70 bg-card',
};

function formatCOP(value: number | string): string {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value));
}

function submitAdd() {
    processing.value = true;
    errors.value = {};
    router.post(
        WalletController.store.url(),
        {
            ...form.value,
            account_identifier: form.value.account_identifier || null,
            notes: form.value.notes || null,
        },
        {
            onError: (e) => {
                errors.value = e;
                processing.value = false;
            },
            onSuccess: () => {
                showAddDialog.value = false;
                form.value = { name: '', type: 'nequi', account_identifier: '', initial_balance: 0, notes: '' };
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Billeteras" />

    <div class="flex flex-col gap-6 p-4">

        <!-- Stats -->
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-verde/10 p-2">
                        <Wallet class="size-5 text-miralto-verde" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Total billeteras</p>
                        <p class="text-2xl font-bold">{{ stats.total_wallets }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30">
                        <CircleDollarSign class="size-5 text-green-600" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Activas</p>
                        <p class="text-2xl font-bold">{{ stats.active_wallets }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-sidebar-border/70 bg-card p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-miralto-verde/10 p-2">
                        <TrendingUp class="size-5 text-miralto-verde" />
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Saldo total</p>
                        <p class="text-2xl font-bold text-miralto-verde">{{ formatCOP(stats.total_balance) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="flex items-center justify-between">
            <h1 class="text-lg font-semibold">Billeteras virtuales</h1>

            <Dialog v-model:open="showAddDialog">
                <DialogTrigger as-child>
                    <Button class="gap-2 bg-miralto-verde text-white hover:bg-miralto-verde/90">
                        <Plus class="size-4" />
                        Nueva billetera
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Nueva billetera</DialogTitle>
                        <DialogDescription>Agrega una cuenta de pago digital para rastrear movimientos.</DialogDescription>
                    </DialogHeader>
                    <div class="space-y-3 py-1">
                        <div class="space-y-1.5">
                            <Label>Tipo de billetera</Label>
                            <select
                                v-model="form.type"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            >
                                <option value="nubank">NUBANK / Llave</option>
                                <option value="nequi">Nequi</option>
                                <option value="daviplata">Daviplata</option>
                                <option value="other">Otro</option>
                            </select>
                            <InputError :message="errors.type" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Nombre</Label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Ej: NUBANK Llave Principal"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Número / Llave (opcional)</Label>
                            <input
                                v-model="form.account_identifier"
                                type="text"
                                placeholder="Ej: 311 234 5678"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            />
                            <InputError :message="errors.account_identifier" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Saldo inicial (COP)</Label>
                            <input
                                v-model.number="form.initial_balance"
                                type="number"
                                min="0"
                                step="100"
                                placeholder="0"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm focus:border-ring focus:outline-none"
                            />
                            <InputError :message="errors.initial_balance" />
                        </div>
                        <div class="space-y-1.5">
                            <Label>Notas (opcional)</Label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Observaciones…"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:border-ring focus:outline-none"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="ghost" @click="showAddDialog = false">Cancelar</Button>
                        <Button
                            class="bg-miralto-verde text-white hover:bg-miralto-verde/90"
                            :disabled="processing"
                            @click="submitAdd"
                        >
                            {{ processing ? 'Guardando…' : 'Crear billetera' }}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <!-- Wallets grid -->
        <div v-if="wallets.length === 0" class="rounded-xl border border-dashed border-sidebar-border/70 p-12 text-center text-muted-foreground">
            Aún no hay billeteras registradas. Crea una para empezar.
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <a
                v-for="wallet in wallets"
                :key="wallet.id"
                :href="show(wallet).url"
                class="group flex flex-col gap-4 rounded-xl border p-5 transition-all hover:shadow-md"
                :class="walletBgAccents[wallet.type]"
            >
                <!-- Header -->
                <div class="flex items-start justify-between">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <Wallet class="size-4 text-muted-foreground" />
                            <span class="font-semibold">{{ wallet.name }}</span>
                        </div>
                        <p v-if="wallet.account_identifier" class="font-mono text-xs text-muted-foreground">
                            {{ wallet.account_identifier }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
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
                </div>

                <!-- Balance -->
                <div>
                    <p class="text-xs text-muted-foreground">Saldo actual</p>
                    <p
                        class="text-3xl font-bold"
                        :class="wallet.current_balance >= 0 ? 'text-miralto-verde' : 'text-destructive'"
                    >
                        {{ formatCOP(wallet.current_balance) }}
                    </p>
                </div>

                <!-- Stats row -->
                <div class="flex items-center justify-between border-t border-inherit pt-3">
                    <div class="flex items-center gap-1.5 text-xs text-green-600 dark:text-green-400">
                        <TrendingUp class="size-3.5" />
                        <span>{{ formatCOP(Number(wallet.initial_balance) + Number(wallet.total_inbound ?? 0)) }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-destructive">
                        <TrendingDown class="size-3.5" />
                        <span>{{ formatCOP(wallet.total_outbound ?? 0) }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-xs text-muted-foreground transition-colors group-hover:text-foreground">
                        <span>Ver detalle</span>
                        <ArrowRight class="size-3" />
                    </div>
                </div>
            </a>
        </div>
    </div>
</template>
