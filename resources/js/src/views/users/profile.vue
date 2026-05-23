<template>
    <div class="layout-px-spacing">
        <teleport to="#breadcrumb">
            <ul class="navbar-nav flex-row">
                <li>
                    <div class="page-header">
                        <nav class="breadcrumb-one" aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="javascript:;">Usuario</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><span>Mi Perfil</span></li>
                            </ol>
                        </nav>
                    </div>
                </li>
            </ul>
        </teleport>

        <div class="row layout-top-spacing">
            <!-- Información Principal del Usuario -->
            <div class="col-xl-4 col-lg-5 col-md-12 col-sm-12 layout-spacing">
                <div class="user-profile-card">
                    <div class="panel">
                        <div class="panel-body text-center">
                            <div class="user-avatar-section mb-4">
                                <div class="avatar-wrapper">
                                    <div class="avatar-initials">
                                        {{ getInitials(form.name) }}
                                    </div>
                                    <div class="avatar-status" :class="form.estatus === 'activo' ? 'status-online' : 'status-offline'"></div>
                                </div>
                            </div>
                            
                            <h4 class="mb-2">{{ form.name }}</h4>
                            <p class="text-muted mb-3">{{ form.email }}</p>
                            
                            <div class="user-role-badge mb-4">
                                <span class="badge badge-primary" :class="getRoleBadgeClass(form.role)">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user me-1">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    {{ form.role || 'Sin rol asignado' }}
                                </span>
                            </div>

                            <div class="user-stats mb-4">
                                <div class="row">
                                    <div class="col-6" v-if="estadisticas.boletosGenerados !== undefined">
                                        <div class="stat-item">
                                            <div class="stat-value text-primary">{{ estadisticas.boletosGenerados || 0 }}</div>
                                            <div class="stat-label">Boletos Generados</div>
                                        </div>
                                    </div>
                                    <div class="col-6" v-if="estadisticas.boletosValidados !== undefined">
                                        <div class="stat-item">
                                            <div class="stat-value text-success">{{ estadisticas.boletosValidados || 0 }}</div>
                                            <div class="stat-label">Boletos Validados</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="user-actions">
                                <button
                                    type="button"
                                    class="btn btn-primary btn-sm me-2"
                                    @click="openEditModal"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-3 me-1">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                                    </svg>
                                    Editar Perfil
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    @click="openPasswordModal"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-key me-1">
                                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path>
                                    </svg>
                                    Cambiar Contraseña
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Detallada -->
            <div class="col-xl-8 col-lg-7 col-md-12 col-sm-12 layout-spacing">
                <div class="user-details-card">
                    <div class="panel">
                        <div class="panel-heading">
                            <h5 class="mb-0">Información Personal</h5>
                        </div>
                        <div class="panel-body">
                            <div class="info-section">
                                <div class="info-item">
                                    <div class="info-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    </div>
                                    <div class="info-content">
                                        <label>Nombre Completo</label>
                                        <p>{{ form.name || 'No especificado' }}</p>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-mail">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                            <polyline points="22,6 12,13 2,6"></polyline>
                                        </svg>
                                    </div>
                                    <div class="info-content">
                                        <label>Correo Electrónico</label>
                                        <p>
                                            <a :href="`mailto:${form.email}`">{{ form.email || 'No especificado' }}</a>
                                        </p>
                                    </div>
                                </div>

                                <div class="info-item" v-if="form.celular">
                                    <div class="info-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-phone">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                        </svg>
                                    </div>
                                    <div class="info-content">
                                        <label>Teléfono</label>
                                        <p>
                                            <a :href="`tel:${form.celular}`">{{ form.celular }}</a>
                                        </p>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shield">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                        </svg>
                                    </div>
                                    <div class="info-content">
                                        <label>Rol</label>
                                        <p>
                                            <span class="badge" :class="getRoleBadgeClass(form.role)">
                                                {{ form.role || 'Sin rol asignado' }}
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-circle">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                        </svg>
                                    </div>
                                    <div class="info-content">
                                        <label>Estado</label>
                                        <p>
                                            <span class="badge" :class="form.estatus === 'activo' ? 'badge-success' : 'badge-danger'">
                                                {{ form.estatus === 'activo' ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="info-item" v-if="userData.created_at">
                                    <div class="info-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                    </div>
                                    <div class="info-content">
                                        <label>Miembro desde</label>
                                        <p>{{ formatDate(userData.created_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Edición -->
        <div
            class="modal fade"
            id="editProfileModal"
            tabindex="-1"
            role="dialog"
            aria-labelledby="editProfileModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProfileModalLabel">
                            {{ is_password ? 'Cambiar Contraseña' : 'Editar Perfil' }}
                        </h5>
                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <form ref="formComponent" @submit.stop.prevent="">
                            <!-- Formulario de Edición de Perfil -->
                            <div v-if="!is_password">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            :class="[is_entrada ? (!errors.name ? 'is-valid' : 'is-invalid') : '']"
                                            placeholder="Ingrese su nombre completo"
                                            v-model="form.name"
                                        />
                                        <div class="valid-feedback">¡Perfecto!</div>
                                        <div class="invalid-feedback">
                                            <ul class="mb-0">
                                                <li v-for="item in errors.name" :key="item">{{ item }}</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Correo Electrónico <span class="text-danger">*</span></label>
                                        <input
                                            type="email"
                                            class="form-control"
                                            :class="[is_entrada ? (!errors.email ? 'is-valid' : 'is-invalid') : '']"
                                            placeholder="correo@ejemplo.com"
                                            v-model="form.email"
                                        />
                                        <div class="valid-feedback">¡Perfecto!</div>
                                        <div class="invalid-feedback">
                                            <ul class="mb-0">
                                                <li v-for="item in errors.email" :key="item">{{ item }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Formulario de Cambio de Contraseña -->
                            <div v-if="is_password">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                                        <input
                                            type="password"
                                            class="form-control"
                                            :class="[is_entrada ? (!errors.password ? 'is-valid' : 'is-invalid') : '']"
                                            placeholder="Ingrese su nueva contraseña"
                                            v-model="form.password"
                                            autocomplete="new-password"
                                        />
                                        <div class="valid-feedback">¡Perfecto!</div>
                                        <div class="invalid-feedback">
                                            <ul class="mb-0">
                                                <li v-for="item in errors.password" :key="item">{{ item }}</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                        <input
                                            type="password"
                                            class="form-control"
                                            :class="[is_entrada ? (!errors.password_confirmation ? 'is-valid' : 'is-invalid') : '']"
                                            placeholder="Confirme su nueva contraseña"
                                            v-model="form.password_confirmation"
                                            autocomplete="new-password"
                                        />
                                        <div class="valid-feedback">¡Perfecto!</div>
                                        <div class="invalid-feedback">
                                            <ul class="mb-0">
                                                <li v-for="item in errors.password_confirmation" :key="item">{{ item }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="is_password ? updatePassword() : updateProfile()"
                            :disabled="isLoading"
                        >
                            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                            {{ is_password ? 'Actualizar Contraseña' : 'Guardar Cambios' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <loading
            v-model:active="isLoading"
            :can-cancel="false"
            :is-full-page="true"
        />
    </div>
</template>

<script setup>
    import '@/assets/sass/scrollspyNav.scss';
    import '@/assets/sass/users/user-profile.scss';
    import { useMeta } from '@/composables/use-meta';
    import * as UserRepository from '@/repositories/UserRepository';
    import { onMounted, ref, computed } from "vue";
    import Loading from 'vue-loading-overlay';
    import 'vue-loading-overlay/dist/css/index.css';
    import axios from "axios";

    useMeta({ title: 'Mi Perfil' });

    const isLoading = ref(false);
    const is_entrada = ref(false);
    const is_password = ref(false);
    const editedIndex = ref(-1);
    const formComponent = ref(null);
    const errors = ref({});
    const userData = ref({});
    const estadisticas = ref({
        ordenesCreadas: 0,
        ordenesProcesadas: 0
    });

    let editModal = null;

    const form = ref({
        name: null,
        email: null,
        password: null,
        password_confirmation: null,
        role: null,
        celular: null,
        estatus: 'activo'
    });

    const getInitials = (name) => {
        if (!name) return 'U';
        const parts = name.split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    };

    const getRoleBadgeClass = (role) => {
        const roleClasses = {
            'Administrador': 'badge-primary',
            'Recepcionista': 'badge-info',
            'Operador': 'badge-success'
        };
        return roleClasses[role] || 'badge-secondary';
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';
        const d = new Date(date);
        return d.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const obtener_user = async () => {
        try {
            isLoading.value = true;
            const response = await UserRepository.getuser();
            userData.value = response;
            
            form.value = {
                name: response.name,
                email: response.email,
                role: response.roles?.[0]?.name || null,
                celular: response.celular || null,
                estatus: response.estatus || 'activo',
                password: null,
                password_confirmation: null
            };
            
            editedIndex.value = response.id;

            // Cargar estadísticas si el usuario tiene relaciones
            if (response.ordenes_creadas_count !== undefined) {
                estadisticas.value.ordenesCreadas = response.ordenes_creadas_count;
            }
            if (response.ordenes_procesadas_count !== undefined) {
                estadisticas.value.ordenesProcesadas = response.ordenes_procesadas_count;
            }
        } catch (error) {
            console.error('Error obteniendo usuario:', error);
            showMessage('Error al cargar la información del usuario', 'error');
        } finally {
            isLoading.value = false;
        }
    };

    const openEditModal = () => {
        is_password.value = false;
        errors.value = {};
        form.value.password = null;
        form.value.password_confirmation = null;
        is_entrada.value = false;
        if (editModal) {
            editModal.show();
        }
    };

    const openPasswordModal = () => {
        is_password.value = true;
        errors.value = {};
        form.value.password = null;
        form.value.password_confirmation = null;
        is_entrada.value = false;
        if (editModal) {
            editModal.show();
        }
    };

    const updateProfile = async () => {
        try {
            isLoading.value = true;
            is_entrada.value = true;
            errors.value = {};

            const response = await UserRepository.updatePerfil({
                name: form.value.name,
                email: form.value.email
            });

            showMessage('Perfil actualizado correctamente', 'success');
            if (editModal) {
                editModal.hide();
            }
            await obtener_user();
        } catch (error) {
            if (axios.isAxiosError(error) && error.response?.data?.errors) {
                errors.value = error.response.data.errors;
            } else {
                showMessage('Error al actualizar el perfil', 'error');
            }
        } finally {
            isLoading.value = false;
        }
    };

    const updatePassword = async () => {
        try {
            isLoading.value = true;
            is_entrada.value = true;
            errors.value = {};

            const response = await UserRepository.passwordUser(
                {
                    password: form.value.password,
                    password_confirmation: form.value.password_confirmation
                },
                editedIndex.value
            );

            showMessage('Contraseña actualizada correctamente', 'success');
            if (editModal) {
                editModal.hide();
            }
            form.value.password = null;
            form.value.password_confirmation = null;
        } catch (error) {
            if (axios.isAxiosError(error) && error.response?.data?.errors) {
                errors.value = error.response.data.errors;
            } else {
                showMessage('Error al actualizar la contraseña', 'error');
            }
        } finally {
            isLoading.value = false;
        }
    };

    const showMessage = (msg = "", type = "success") => {
        const toast = window.Swal.mixin({
            toast: true,
            position: "top",
            showConfirmButton: false,
            timer: 3000,
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: "10px 20px",
        });
    };

    onMounted(async () => {
        await obtener_user();
        
        // Inicializar modal de Bootstrap
        const modalElement = document.getElementById('editProfileModal');
        if (modalElement) {
            editModal = new window.bootstrap.Modal(modalElement);
        }
    });
</script>

<style scoped>
.user-profile-card .panel {
    border-radius: 8px;
    box-shadow: 0 0 40px 0 rgb(94 92 154 / 6%);
}

.user-details-card .panel {
    border-radius: 8px;
    box-shadow: 0 0 40px 0 rgb(94 92 154 / 6%);
}

.panel-heading {
    padding: 20px;
    border-bottom: 1px solid #e0e6ed;
}

.panel-body {
    padding: 20px;
}

.avatar-wrapper {
    position: relative;
    display: inline-block;
    margin-bottom: 1rem;
}

.avatar-initials {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2.5rem;
    font-weight: 600;
    margin: 0 auto;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.avatar-status {
    position: absolute;
    bottom: 8px;
    right: 8px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 3px solid white;
}

.status-online {
    background-color: #1abc9c;
}

.status-offline {
    background-color: #e7515a;
}

.user-role-badge .badge {
    font-size: 0.9rem;
    padding: 8px 16px;
    display: inline-flex;
    align-items: center;
}

.user-stats {
    padding: 20px 0;
    border-top: 1px solid #e0e6ed;
    border-bottom: 1px solid #e0e6ed;
}

.stat-item {
    text-align: center;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.85rem;
    color: #888ea8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.user-actions {
    margin-top: 1.5rem;
}

.info-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.info-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    border-radius: 8px;
    transition: background-color 0.2s;
}

.info-item:hover {
    background-color: #f8f9fa;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background-color: #f1f2f3;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #515365;
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-content label {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #888ea8;
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.info-content p {
    margin: 0;
    color: #3b3f5c;
    font-size: 1rem;
}

.info-content a {
    color: #1b55e2;
    text-decoration: none;
}

.info-content a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .avatar-initials {
        width: 100px;
        height: 100px;
        font-size: 2rem;
    }

    .user-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .user-actions .btn {
        width: 100%;
    }
}
</style>
