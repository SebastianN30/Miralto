<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Bienvenido de vuelta',
        description: 'Ingresa tus credenciales para acceder al sistema',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Iniciar sesión" />

    <div
        v-if="status"
        class="mb-4 rounded-md bg-green-50 px-3 py-2 text-center text-sm font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-2">
            <Label for="email">Usuario o correo</Label>
            <Input
                id="email"
                type="text"
                name="email"
                required
                autofocus
                :tabindex="1"
                autocomplete="username"
                autocapitalize="none"
                placeholder="usuario o usuario@miralto.com"
            />
            <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <Label for="password">Contraseña</Label>
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-xs text-miralto-verde hover:underline"
                    :tabindex="5"
                >
                    ¿Olvidaste tu contraseña?
                </TextLink>
            </div>
            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="2"
                autocomplete="current-password"
                placeholder="••••••••"
            />
            <InputError :message="errors.password" />
        </div>

        <Label for="remember" class="flex items-center gap-2.5 text-sm font-normal text-muted-foreground">
            <Checkbox id="remember" name="remember" :tabindex="3" />
            <span>Mantener sesión iniciada</span>
        </Label>

        <Button
            type="submit"
            class="mt-2 w-full bg-miralto-verde text-white hover:bg-miralto-verde/90"
            :tabindex="4"
            :disabled="processing"
            data-test="login-button"
        >
            <Spinner v-if="processing" />
            {{ processing ? 'Ingresando…' : 'Ingresar' }}
        </Button>
    </Form>
</template>
