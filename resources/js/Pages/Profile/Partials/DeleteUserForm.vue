<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <div>
        <p class="text-muted small mb-3">
            Al eliminar su cuenta se borrarán de forma permanente sus datos de acceso.
            Esta acción no se puede deshacer.
        </p>

        <button type="button" class="btn btn-outline-danger" @click="confirmUserDeletion">
            <i class="fa-solid fa-trash me-1" />Eliminar cuenta
        </button>

        <div
            v-if="confirmingUserDeletion"
            class="modal fade show d-block"
            tabindex="-1"
            style="background: rgba(0, 0, 0, 0.5)"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmar eliminación</h5>
                        <button type="button" class="btn-close" @click="closeModal" />
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            ¿Está seguro de eliminar su cuenta? Ingrese su contraseña para confirmar.
                        </p>
                        <label for="password_delete" class="form-label">Contraseña</label>
                        <input
                            id="password_delete"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.password }"
                            placeholder="Contraseña actual"
                            @keyup.enter="deleteUser"
                        />
                        <div v-if="form.errors.password" class="invalid-feedback">
                            {{ form.errors.password }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeModal">Cancelar</button>
                        <button
                            type="button"
                            class="btn btn-danger"
                            :disabled="form.processing"
                            @click="deleteUser"
                        >
                            Sí, eliminar cuenta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
