<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <div>
        <p class="text-muted small mb-3">
            Elija una contraseña segura (mínimo 8 caracteres). Deberá usar la contraseña actual para confirmar el cambio.
        </p>

        <form @submit.prevent="updatePassword">
            <div class="mb-3">
                <label for="current_password" class="form-label">Contraseña actual</label>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.current_password }"
                    autocomplete="current-password"
                    required
                />
                <div v-if="form.errors.current_password" class="invalid-feedback">
                    {{ form.errors.current_password }}
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Nueva contraseña</label>
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.password }"
                    autocomplete="new-password"
                    required
                />
                <div v-if="form.errors.password" class="invalid-feedback">
                    {{ form.errors.password }}
                </div>
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirmar nueva contraseña</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.password_confirmation }"
                    autocomplete="new-password"
                    required
                />
                <div v-if="form.errors.password_confirmation" class="invalid-feedback">
                    {{ form.errors.password_confirmation }}
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" />
                    Actualizar contraseña
                </button>
                <span v-if="form.recentlySuccessful" class="text-success small">
                    <i class="fa-solid fa-check me-1" />Contraseña actualizada correctamente.
                </span>
            </div>
        </form>
    </div>
</template>
