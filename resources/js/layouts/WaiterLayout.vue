<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ClipboardList, Plus, LogOut } from 'lucide-vue-next';
import { computed } from 'vue';
import * as AuthenticatedSessionController from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { Toaster } from '@/components/ui/sonner';
import { index as waiterIndex, create as waiterCreate } from '@/routes/waiter';

const page = usePage<{ auth: { user: { name: string } } }>();
const userName = computed(() => page.props.auth.user.name);
const currentPath = computed(() => page.url);

function logout() {
    router.post(AuthenticatedSessionController.destroy.url());
}
</script>

<template>
    <div class="flex min-h-svh flex-col bg-miralto-beige/40">

        <!-- Top bar -->
        <header class="sticky top-0 z-20 border-b border-sidebar-border/70 bg-card/95 backdrop-blur">
            <div class="mx-auto flex h-14 max-w-3xl items-center justify-between gap-3 px-4">
                <Link :href="waiterIndex()" class="flex items-center gap-2">
                    <img src="/storage/miralto_logo.png" alt="Miralto" class="size-8 object-contain" />
                    <div class="leading-tight">
                        <p class="text-sm font-bold text-miralto-verde">Miralto</p>
                        <p class="text-[10px] uppercase tracking-wider text-muted-foreground">Modo mesero</p>
                    </div>
                </Link>
                <div class="flex items-center gap-2">
                    <span class="hidden text-xs text-muted-foreground sm:inline">{{ userName }}</span>
                    <button
                        class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-destructive"
                        title="Cerrar sesión"
                        @click="logout"
                    >
                        <LogOut class="size-4" />
                    </button>
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main class="mx-auto w-full max-w-3xl flex-1 pb-20">
            <slot />
        </main>

        <!-- Bottom tab bar (mobile-first) -->
        <nav class="fixed bottom-0 left-0 right-0 z-20 border-t border-sidebar-border/70 bg-card/95 backdrop-blur">
            <div class="mx-auto flex max-w-3xl">
                <Link
                    :href="waiterIndex()"
                    class="flex flex-1 flex-col items-center gap-1 py-2.5 text-xs transition-colors"
                    :class="currentPath === '/waiter' || currentPath.startsWith('/waiter/') && !currentPath.includes('create')
                        ? 'text-miralto-verde'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    <ClipboardList class="size-5" />
                    Pedidos
                </Link>
                <Link
                    :href="waiterCreate()"
                    class="flex flex-1 flex-col items-center gap-1 py-2.5 text-xs transition-colors"
                    :class="currentPath === '/waiter/create'
                        ? 'text-miralto-verde'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    <Plus class="size-5" />
                    Nuevo pedido
                </Link>
            </div>
        </nav>

        <Toaster />
    </div>
</template>
