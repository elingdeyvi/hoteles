<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <div>
        <p class="text-muted small mb-3">
            Actualice su nombre y correo electrónico. Estos datos se muestran en el sistema y en los tickets.
        </p>

        <form @submit.prevent="form.patch(route('profile.update'))">
            <div class="mb-3">
                <label for="name" class="form-label">Nombre</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.name }"
                    required
                    autocomplete="name"
                />
                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.email }"
                    required
                    autocomplete="username"
                />
                <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="alert alert-warning py-2">
                Su correo no está verificado.
                <Link :href="route('verification.send')" method="post" as="button" class="btn btn-link btn-sm p-0 align-baseline">
                    Reenviar correo de verificación
                </Link>
                <div v-if="status === 'verification-link-sent'" class="small text-success mt-1">
                    Se envió un nuevo enlace de verificación a su correo.
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" />
                    Guardar cambios
                </button>
                <span v-if="form.recentlySuccessful" class="text-success small">
                    <i class="fa-solid fa-check me-1" />Cambios guardados.
                </span>
            </div>
        </form>
    </div>
</template>
