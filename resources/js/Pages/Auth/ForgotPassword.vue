<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar contraseña" />

        <div class="login-form-header">
            <h2>Recuperar contraseña</h2>
            <p>Te enviaremos un enlace para restablecerla</p>
        </div>

        <div v-if="status" class="alert alert-success py-2">{{ status }}</div>

        <form class="login-form" @submit.prevent="submit">
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

            <button
                type="submit"
                class="btn btn-login w-100 mb-3"
                :disabled="form.processing"
            >
                Enviar enlace
            </button>

            <div class="text-center">
                <Link :href="route('login')" class="login-forgot">Volver al inicio de sesión</Link>
            </div>
        </form>
    </GuestLayout>
</template>
