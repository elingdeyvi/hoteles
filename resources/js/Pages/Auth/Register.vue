<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Registro" />

        <p class="login-box-msg">Registrar una cuenta nueva</p>

        <form @submit.prevent="submit">
            <div class="input-group mb-3">
                <input
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    placeholder="Nombre"
                    required
                    autofocus
                    autocomplete="name"
                />
                <div class="input-group-text"><i class="fa-solid fa-user"></i></div>
            </div>
            <InputError class="mb-2" :message="form.errors.name" />

            <div class="input-group mb-3">
                <input
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    placeholder="Email"
                    required
                    autocomplete="username"
                />
                <div class="input-group-text"><i class="fa-solid fa-envelope"></i></div>
            </div>
            <InputError class="mb-2" :message="form.errors.email" />

            <div class="input-group mb-3">
                <input
                    v-model="form.password"
                    type="password"
                    class="form-control"
                    placeholder="Contraseña"
                    required
                    autocomplete="new-password"
                />
                <div class="input-group-text"><i class="fa-solid fa-lock"></i></div>
            </div>
            <InputError class="mb-2" :message="form.errors.password" />

            <div class="input-group mb-3">
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    class="form-control"
                    placeholder="Confirmar contraseña"
                    required
                    autocomplete="new-password"
                />
                <div class="input-group-text"><i class="fa-solid fa-lock"></i></div>
            </div>

            <div class="row">
                <div class="col-8">
                    <Link :href="route('login')">Ya tengo cuenta</Link>
                </div>
                <div class="col-4">
                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                        :disabled="form.processing"
                    >
                        Crear
                    </button>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
