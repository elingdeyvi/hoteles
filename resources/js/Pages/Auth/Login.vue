<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <div class="login-form-header">
            <h2>Iniciar sesión</h2>
            <p>Ingresa tus credenciales para continuar</p>
        </div>

        <div v-if="status" class="alert alert-success py-2">{{ status }}</div>

        <form class="login-form" @submit.prevent="submit" autocomplete="off">
            <div class="mb-3">
                <label for="email" class="form-label">Correo</label>
                <div class="input-group input-group-lg login-input">
                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.email }"
                        placeholder="correo@empresa.com"
                        required
                        autofocus
                        autocomplete="username"
                    />
                </div>
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <div class="input-group input-group-lg login-input">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.password }"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    />
                    <button
                        type="button"
                        class="input-group-text login-toggle-password"
                        :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        @click="showPassword = !showPassword"
                    >
                        <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                    </button>
                </div>
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input
                        id="remember"
                        v-model="form.remember"
                        class="form-check-input"
                        type="checkbox"
                    />
                    <label class="form-check-label" for="remember">Recordarme</label>
                </div>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="login-forgot"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <button
                type="submit"
                class="btn btn-login w-100"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" />
                Entrar
            </button>
        </form>
    </GuestLayout>
</template>
