<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ users: Array, roles: Array, properties: Array });
const showModal = ref(false);
const editing = ref(null);
const form = useForm({ name: '', email: '', password: '', password_confirmation: '', role: '', estatus: 'activo', property_ids: [] });

const openCreate = () => { editing.value = null; form.reset(); form.estatus = 'activo'; form.property_ids = []; showModal.value = true; };
const openEdit = (user) => {
    editing.value = user;
    form.name = user.name; form.email = user.email; form.role = user.role; form.estatus = user.estatus;
    form.property_ids = [...(user.property_ids || [])]; form.password = ''; form.password_confirmation = '';
    showModal.value = true;
};
const submit = () => {
    const done = () => { showModal.value = false; };
    if (editing.value) form.put(route('users.update', editing.value.id), { onSuccess: done });
    else form.post(route('users.store'), { onSuccess: done });
};
</script>

<template>
    <Head title="Usuarios" />
    <AuthenticatedLayout>
        <template #header>Usuarios</template>
        <button class="btn btn-primary mb-3" @click="openCreate">Nuevo usuario</button>
        <div class="card"><div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estatus</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id">
                        <td>{{ user.name }}</td><td>{{ user.email }}</td><td>{{ user.role }}</td><td>{{ user.estatus }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" @click="openEdit(user)">Editar</button>
                            <button class="btn btn-sm btn-outline-danger" @click="router.delete(route('users.destroy', user.id))">Desactivar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div></div>
        <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,.45)">
            <div class="modal-dialog"><form class="modal-content" @submit.prevent="submit">
                <div class="modal-header"><h5 class="modal-title">Usuario</h5></div>
                <div class="modal-body row g-2">
                    <div class="col-md-6"><input v-model="form.name" class="form-control" placeholder="Nombre" required /></div>
                    <div class="col-md-6"><input v-model="form.email" type="email" class="form-control" placeholder="Correo" required /></div>
                    <div class="col-md-6"><select v-model="form.role" class="form-select" required><option value="">Rol</option><option v-for="role in roles" :key="role" :value="role">{{ role }}</option></select></div>
                    <div v-if="editing" class="col-md-6"><select v-model="form.estatus" class="form-select"><option value="activo">Activo</option><option value="inactivo">Inactivo</option><option value="suspendido">Suspendido</option></select></div>
                    <div class="col-md-6"><input v-model="form.password" type="password" class="form-control" :placeholder="editing ? 'Nueva contraseña' : 'Contraseña'" :required="!editing" /></div>
                    <div class="col-md-6"><input v-model="form.password_confirmation" type="password" class="form-control" placeholder="Confirmar" :required="!editing" /></div>
                    <div class="col-12">
                        <label class="form-label">Hoteles (el administrador ve todos)</label>
                        <select v-model="form.property_ids" class="form-select" multiple>
                            <option v-for="property in properties" :key="property.id" :value="property.id">{{ property.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" @click="showModal = false">Cerrar</button><button class="btn btn-primary">Guardar</button></div>
            </form></div>
        </div>
    </AuthenticatedLayout>
</template>
