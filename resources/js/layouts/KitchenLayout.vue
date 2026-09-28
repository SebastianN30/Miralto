<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { LogOut } from 'lucide-vue-next';
import { computed } from 'vue';
import * as AuthenticatedSessionController from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import { Toaster } from '@/components/ui/sonner';

const page = usePage<{ auth: { user: { name: string } } }>();
const userName = computed(() => page.props.auth.user.name);

function logout() {
    router.post(AuthenticatedSessionController.destroy.url());
}
</script>

<template>
    <div class="flex min-h-svh flex-col bg-miralto-beige/40">
        <header class="sticky top-0 z-20 border-b border-sidebar-border/70 bg-card/95 backdrop-blur">
            <div class="mx-auto flex h-14 max-w-6xl items-center justify-between gap-3 px-4">
                <div class="flex items-center gap-2">
                    <img src="/storage/miralto_logo.png" alt="Miralto" class="size-8 object-contain" />
                    <div class="leading-tight">
                        <p class="text-sm font-bold text-miralto-verde">Miralto</p>
                        <p class="text-[10px] uppercase tracking-wider text-muted-foreground">Cocina</p>
                    </div>
                </div>
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

        <main class="mx-auto w-full max-w-6xl flex-1">
            <slot />
        </main>

        <Toaster />
    </div>
</template>
