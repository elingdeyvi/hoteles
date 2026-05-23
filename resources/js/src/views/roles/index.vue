<template>
    <div class="layout-px-spacing">
        <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
                <div class="panel p-3">
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 col-sm-12">
                            <div class="text-center titulo-dorado">
                                <h3 class="pull-left">Roles y Permisos</h3>
                                <button
                                    class="btn btn-black pull-right m-1"
                                    @click="refrescar"
                                >
                                    Refrescar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div v-if="isLoading" class="text-center p-5">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                            <div v-else>
                                <div v-for="role in roles" :key="role.id" class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">{{ role.name }}</h5>
                                        <button
                                            class="btn btn-sm btn-primary"
                                            @click="toggleEdit(role.id)"
                                        >
                                            {{ editingRole === role.id ? 'Cancelar' : 'Editar Permisos' }}
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div v-if="editingRole === role.id">
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Permisos disponibles:</label>
                                                    <div class="row">
                                                        <div 
                                                            v-for="permission in allPermissions" 
                                                            :key="permission.id"
                                                            class="col-md-4 mb-2"
                                                        >
                                                            <div class="form-check">
                                                                <input
                                                                    class="form-check-input"
                                                                    type="checkbox"
                                                                    :value="permission.name"
                                                                    :id="`perm-${role.id}-${permission.id}`"
                                                                    :checked="isPermissionAssigned(role, permission.name)"
                                                                    @change="togglePermission(role.id, permission.name)"
                                                                />
                                                                <label 
                                                                    class="form-check-label" 
                                                                    :for="`perm-${role.id}-${permission.id}`"
                                                                >
                                                                    {{ formatPermissionName(permission.name) }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <button
                                                    class="btn btn-success"
                                                    @click="savePermissions(role.id)"
                                                    :disabled="saving"
                                                >
                                                    {{ saving ? 'Guardando...' : 'Guardar Cambios' }}
                                                </button>
                                            </div>
                                        </div>
                                        <div v-else>
                                            <p class="text-muted mb-2">
                                                <strong>Permisos asignados ({{ role.permissions?.length || 0 }}):</strong>
                                            </p>
                                            <div v-if="role.permissions && role.permissions.length > 0">
                                                <span
                                                    v-for="(permission, index) in role.permissions"
                                                    :key="permission.id"
                                                    class="badge bg-primary me-2 mb-2"
                                                >
                                                    {{ formatPermissionName(permission.name) }}
                                                </span>
                                            </div>
                                            <p v-else class="text-muted">No hay permisos asignados</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useMeta } from "@/composables/use-meta";
import * as RoleRepository from "@/repositories/RoleRepository";
import Swal from "sweetalert2";

useMeta({ title: 'Roles y Permisos' });

const roles = ref([]);
const allPermissions = ref([]);
const isLoading = ref(true);
const editingRole = ref(null);
const rolePermissions = ref({});
const saving = ref(false);

const formatPermissionName = (name) => {
    return name
        .split('.')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' / ');
};

const isPermissionAssigned = (role, permissionName) => {
    if (!rolePermissions.value[role.id]) {
        rolePermissions.value[role.id] = role.permissions?.map(p => p.name) || [];
    }
    return rolePermissions.value[role.id].includes(permissionName);
};

const togglePermission = (roleId, permissionName) => {
    if (!rolePermissions.value[roleId]) {
        rolePermissions.value[roleId] = [];
    }
    const index = rolePermissions.value[roleId].indexOf(permissionName);
    if (index > -1) {
        rolePermissions.value[roleId].splice(index, 1);
    } else {
        rolePermissions.value[roleId].push(permissionName);
    }
};

const toggleEdit = (roleId) => {
    if (editingRole.value === roleId) {
        editingRole.value = null;
        // Reset permissions to original state
        const role = roles.value.find(r => r.id === roleId);
        if (role) {
            rolePermissions.value[roleId] = role.permissions?.map(p => p.name) || [];
        }
    } else {
        editingRole.value = roleId;
        const role = roles.value.find(r => r.id === roleId);
        if (role) {
            rolePermissions.value[roleId] = role.permissions?.map(p => p.name) || [];
        }
    }
};

const savePermissions = async (roleId) => {
    try {
        saving.value = true;
        const permissions = rolePermissions.value[roleId] || [];
        await RoleRepository.updateRolePermissions(roleId, permissions);
        
        // Update the role in the list
        const role = roles.value.find(r => r.id === roleId);
        if (role) {
            // Reload roles to get updated permissions
            await loadRoles();
        }
        
        editingRole.value = null;
        
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: 'Permisos actualizados correctamente',
            timer: 2000,
            showConfirmButton: false
        });
    } catch (error) {
        console.error('Error saving permissions:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron actualizar los permisos',
        });
    } finally {
        saving.value = false;
    }
};

const loadRoles = async () => {
    try {
        isLoading.value = true;
        const rolesData = await RoleRepository.getRoles();
        roles.value = rolesData.data || rolesData;
        
        // Initialize role permissions
        roles.value.forEach(role => {
            rolePermissions.value[role.id] = role.permissions?.map(p => p.name) || [];
        });
    } catch (error) {
        console.error('Error loading roles:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar los roles',
        });
    } finally {
        isLoading.value = false;
    }
};

const loadPermissions = async () => {
    try {
        const permissionsData = await RoleRepository.getPermissions();
        allPermissions.value = permissionsData.data || permissionsData;
    } catch (error) {
        console.error('Error loading permissions:', error);
    }
};

const refrescar = async () => {
    await loadRoles();
    await loadPermissions();
};

onMounted(async () => {
    await loadRoles();
    await loadPermissions();
});
</script>

<style scoped>
.card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.badge {
    font-size: 0.85rem;
    padding: 0.5em 0.75em;
}

.form-check-label {
    font-size: 0.9rem;
    cursor: pointer;
}
</style>