<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <Head title="Perfil" />

    <AuthenticatedLayout>
        <template #header>
            <h3 class="mb-0">Perfil</h3>
        </template>
        <template #breadcrumb>
            <li class="breadcrumb-item"><Link :href="route('dashboard')">Inicio</Link></li>
            <li class="breadcrumb-item active">Perfil</li>
        </template>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Información del perfil</h3>
                    </div>
                    <div class="card-body">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                        />
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Cambiar contraseña</h3>
                    </div>
                    <div class="card-body">
                        <UpdatePasswordForm />
                    </div>
                </div>

                <div class="card card-outline card-danger mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Eliminar cuenta</h3>
                    </div>
                    <div class="card-body">
                        <DeleteUserForm />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
