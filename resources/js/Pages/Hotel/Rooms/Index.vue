<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ rooms: Array, roomTypes: Array, filters: Object });
const showModal = ref(false);
const editing = ref(null);
const form = useForm({ room_type_id: '', number: '', floor: 1, status: 'disponible', notes: '', is_active: true });
const statuses = ['disponible', 'ocupada', 'sucia', 'limpia', 'mantenimiento'];

const openCreate = () => { editing.value = null; form.reset(); form.is_active = true; form.status = 'disponible'; showModal.value = true; };
const openEdit = (room) => { editing.value = room; Object.assign(form, { room_type_id: room.room_type_id, number: room.number, floor: room.floor, status: room.status, notes: room.notes, is_active: room.is_active }); showModal.value = true; };
const submit = () => {
    const done = () => { showModal.value = false; };
    if (editing.value) form.put(route('habitaciones.update', editing.value.id), { onSuccess: done });
    else form.post(route('habitaciones.store'), { onSuccess: done });
};
const filter = () => router.get(route('habitaciones.index'), { status: props.filters.status || undefined, room_type_id: props.filters.room_type_id || undefined }, { preserveState: true, replace: true });
const puedeEliminar = computed(() => {
    const auth = usePage().props.auth || {};
    return (auth.roles || []).includes('Administrador') || (auth.permissions || []).includes('hotel.configurar');
});
const eliminar = (room) => {
    if (window.confirm(`¿Eliminar la habitación ${room.number}? Solo se borra si no tiene reservas ni estancias.`)) {
        router.delete(route('habitaciones.destroy', room.id));
    }
};
</script>

<template>
    <Head title="Habitaciones" />
    <AuthenticatedLayout>
        <template #header>Habitaciones</template>
        <div class="d-flex gap-2 mb-3">
            <select v-model="filters.status" class="form-select w-auto" @change="filter"><option value="">Estado</option><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></select>
            <select v-model="filters.room_type_id" class="form-select w-auto" @change="filter"><option value="">Tipo</option><option v-for="type in roomTypes" :key="type.id" :value="type.id">{{ type.name }}</option></select>
            <button class="btn btn-primary" @click="openCreate">Nueva</button>
        </div>
        <div class="card"><div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Número</th><th>Tipo</th><th>Piso</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="room in rooms" :key="room.id">
                        <td>{{ room.number }}</td><td>{{ room.room_type?.name }}</td><td>{{ room.floor }}</td><td>{{ room.status }}</td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" @click="openEdit(room)">Editar</button>
                            <button v-if="puedeEliminar" class="btn btn-sm btn-outline-danger ms-1" @click="eliminar(room)">Eliminar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div></div>
        <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,.45)">
            <div class="modal-dialog"><form class="modal-content" @submit.prevent="submit">
                <div class="modal-header"><h5 class="modal-title">Habitación</h5></div>
                <div class="modal-body row g-2">
                    <div class="col-md-4"><input v-model="form.number" class="form-control" placeholder="Número" required /></div>
                    <div class="col-md-4"><select v-model="form.room_type_id" class="form-select" required><option value="">Tipo</option><option v-for="type in roomTypes" :key="type.id" :value="type.id">{{ type.name }}</option></select></div>
                    <div class="col-md-4"><input v-model="form.floor" type="number" class="form-control" placeholder="Piso" /></div>
                    <div class="col-md-6"><select v-model="form.status" class="form-select"><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></select></div>
                    <div class="col-12"><textarea v-model="form.notes" class="form-control" placeholder="Notas" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" @click="showModal = false">Cerrar</button><button class="btn btn-primary">Guardar</button></div>
            </form></div>
        </div>
    </AuthenticatedLayout>
</template>
