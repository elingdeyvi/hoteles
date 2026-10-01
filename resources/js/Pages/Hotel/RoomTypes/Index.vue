<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ roomTypes: Array });
const showModal = ref(false);
const editing = ref(null);
const form = useForm({ name: '', code: '', description: '', capacity: 2, amenities: '', base_price: 0, hourly_price: 0, extra_person_price: 0, is_active: true });
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

const openCreate = () => { editing.value = null; form.reset(); form.is_active = true; showModal.value = true; };
const openEdit = (item) => {
    editing.value = item;
    form.name = item.name; form.code = item.code; form.description = item.description; form.capacity = item.capacity;
    form.amenities = (item.amenities || []).join(', '); form.base_price = item.base_price;
    form.hourly_price = item.hourly_price || 0; form.extra_person_price = item.extra_person_price || 0; form.is_active = item.is_active;
    showModal.value = true;
};
const submit = () => {
    const done = () => { showModal.value = false; };
    if (editing.value) form.put(route('tipos.update', editing.value.id), { onSuccess: done });
    else form.post(route('tipos.store'), { onSuccess: done });
};
const eliminar = (item) => {
    if (window.confirm(`¿Eliminar el tipo ${item.name}? Solo se borra si no tiene habitaciones, tarifas ni reservas.`)) {
        router.delete(route('tipos.destroy', item.id));
    }
};
</script>

<template>
    <Head title="Tipos de habitación" />
    <AuthenticatedLayout>
        <template #header>Tipos de habitación</template>
        <button class="btn btn-primary mb-3" @click="openCreate">Nuevo tipo</button>
        <div class="card"><div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Código</th><th>Nombre</th><th>Capacidad</th><th>Noche</th><th>Hora</th><th>Extra</th><th>Habitaciones</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in roomTypes" :key="item.id">
                        <td>{{ item.code }}</td><td>{{ item.name }}</td><td>{{ item.capacity }}</td><td>{{ money(item.base_price) }}</td><td>{{ money(item.hourly_price) }}</td><td>{{ money(item.extra_person_price) }}</td><td>{{ item.rooms_count }}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" @click="openEdit(item)">Editar</button>
                            <button class="btn btn-sm btn-outline-danger" @click="eliminar(item)">Eliminar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div></div>
        <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,.45)">
            <div class="modal-dialog"><form class="modal-content" @submit.prevent="submit">
                <div class="modal-header"><h5 class="modal-title">Tipo de habitación</h5></div>
                <div class="modal-body row g-2">
                    <div class="col-md-8"><input v-model="form.name" class="form-control" placeholder="Nombre" required /></div>
                    <div class="col-md-4"><input v-model="form.code" class="form-control" placeholder="Código" /></div>
                    <div class="col-md-4"><input v-model="form.capacity" type="number" min="1" class="form-control" placeholder="Capacidad" /></div>
                    <div class="col-md-4"><input v-model="form.base_price" type="number" step="0.01" min="0" class="form-control" placeholder="Precio por noche" required /></div>
                    <div class="col-md-4"><input v-model="form.hourly_price" type="number" step="0.01" min="0" class="form-control" placeholder="Precio por hora" /></div>
                    <div class="col-md-4"><input v-model="form.extra_person_price" type="number" step="0.01" min="0" class="form-control" placeholder="Persona extra" /></div>
                    <div class="col-md-4"><div class="form-check mt-2"><input v-model="form.is_active" class="form-check-input" type="checkbox" id="active" /><label class="form-check-label" for="active">Activo</label></div></div>
                    <div class="col-12"><input v-model="form.amenities" class="form-control" placeholder="Amenidades separadas por coma" /></div>
                    <div class="col-12"><textarea v-model="form.description" class="form-control" placeholder="Descripción" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" @click="showModal = false">Cerrar</button><button class="btn btn-primary">Guardar</button></div>
            </form></div>
        </div>
    </AuthenticatedLayout>
</template>
