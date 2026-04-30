<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative grid min-h-svh lg:grid-cols-2">

        <!-- ── Left: brand panel (decorative) ─────────────────── -->
        <div class="relative hidden h-full flex-col overflow-hidden bg-miralto-verde p-10 text-miralto-blanco lg:flex">
            <!-- subtle radial accent -->
            <div
                class="pointer-events-none absolute inset-0 opacity-20"
                style="background: radial-gradient(circle at 30% 20%, rgb(240,235,210) 0%, transparent 60%), radial-gradient(circle at 80% 80%, rgb(120,70,45) 0%, transparent 50%);"
            />

            <!-- top: logo + brand -->
            <Link :href="home()" class="relative z-10 flex items-center gap-3 text-lg font-semibold">
                <img src="/storage/miralto_logo.png" alt="Miralto" class="size-10 object-contain" />
                <span class="text-xl tracking-wide">Miralto</span>
            </Link>

            <!-- center: large logo + tagline -->
            <div class="relative z-10 my-auto flex flex-col items-center text-center">
                <img
                    src="/storage/miralto_logo.png"
                    alt="Miralto"
                    class="mb-6 size-40 object-contain drop-shadow-lg"
                />
                <h2 class="text-3xl font-bold tracking-tight">Restaurante Campestre</h2>
                <p class="mt-3 max-w-sm text-sm text-miralto-beige/80">
                    Sabores de la tierra, atención de la casa.
                </p>
            </div>

            <!-- bottom: footer note -->
            <p class="relative z-10 text-xs text-miralto-beige/60">
                © {{ new Date().getFullYear() }} Miralto · Sistema interno de gestión
            </p>
        </div>

        <!-- ── Right: form panel ──────────────────────────────── -->
        <div class="flex flex-col items-center justify-center bg-background p-6 md:p-10">
            <div class="w-full max-w-sm">

                <!-- mobile-only logo -->
                <div class="mb-8 flex flex-col items-center gap-3 lg:hidden">
                    <img src="/storage/miralto_logo.png" alt="Miralto" class="size-16 object-contain" />
                    <span class="text-lg font-semibold text-miralto-verde">Miralto</span>
                </div>

                <div class="mb-6 flex flex-col gap-2 text-center lg:text-left">
                    <h1 v-if="title" class="text-2xl font-semibold tracking-tight">{{ title }}</h1>
                    <p v-if="description" class="text-sm text-muted-foreground">{{ description }}</p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
