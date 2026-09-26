<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ properties: Array });
const showModal = ref(false);
const editing = ref(null);
const form = useForm({ code: '', name: '', phone: '', email: '', address: '', currency: 'MXN', timezone: 'America/Mexico_City', booking_enabled: true, is_active: true, sort_order: 0 });

const openCreate = () => { editing.value = null; form.reset(); form.booking_enabled = true; form.is_active = true; form.currency = 'MXN'; showModal.value = true; };
const openEdit = (item) => { editing.value = item; Object.assign(form, item); showModal.value = true; };
const submit = () => {
    const done = () => { showModal.value = false; };
    if (editing.value) form.put(route('propiedades.update', editing.value.code), { onSuccess: done });
    else form.post(route('propiedades.store'), { onSuccess: done });
};
</script>

<template>
    <Head title="Hoteles" />
    <AuthenticatedLayout>
        <template #header>Hoteles</template>
        <button class="btn btn-primary mb-3" @click="openCreate">Nuevo hotel</button>
        <div class="card"><div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Código</th><th>Nombre</th><th>Tipos</th><th>Reservas</th><th>Reserva web</th><th>Activo</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="item in properties" :key="item.id">
                        <td>{{ item.code }}</td><td>{{ item.name }}</td><td>{{ item.room_types_count }}</td><td>{{ item.reservations_count }}</td>
                        <td>{{ item.booking_enabled ? 'Sí' : 'No' }}</td><td>{{ item.is_active ? 'Sí' : 'No' }}</td>
                        <td>
                            <a class="btn btn-sm btn-outline-secondary" :href="route('booking.show', item.code)" target="_blank">Reservar</a>
                            <button class="btn btn-sm btn-outline-primary" @click="openEdit(item)">Editar</button>
                            <button class="btn btn-sm btn-outline-danger" @click="router.delete(route('propiedades.destroy', item.code))">Desactivar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div></div>
        <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,.45)">
            <div class="modal-dialog"><form class="modal-content" @submit.prevent="submit">
                <div class="modal-header"><h5 class="modal-title">Hotel</h5></div>
                <div class="modal-body row g-2">
                    <div class="col-md-8"><input v-model="form.name" class="form-control" placeholder="Nombre" required /></div>
                    <div class="col-md-4"><input v-model="form.code" class="form-control" placeholder="código-url" /></div>
                    <div class="col-md-6"><input v-model="form.phone" class="form-control" placeholder="Teléfono" /></div>
                    <div class="col-md-6"><input v-model="form.email" type="email" class="form-control" placeholder="Correo" /></div>
                    <div class="col-12"><textarea v-model="form.address" class="form-control" placeholder="Dirección" rows="2"></textarea></div>
                    <div class="col-12">
                        <div class="form-check form-check-inline"><input v-model="form.booking_enabled" class="form-check-input" type="checkbox" id="book" /><label class="form-check-label" for="book">Reserva en línea</label></div>
                        <div class="form-check form-check-inline"><input v-model="form.is_active" class="form-check-input" type="checkbox" id="act" /><label class="form-check-label" for="act">Activo</label></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" @click="showModal = false">Cerrar</button><button class="btn btn-primary">Guardar</button></div>
            </form></div>
        </div>
    </AuthenticatedLayout>
</template>
