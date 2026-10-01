<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ huespedes: Object, filters: Object });
const showModal = ref(false);
const editing = ref(null);
const form = useForm({ nombre: '', email: '', telefono: '', documento: '', identificacion: null, nacionalidad: '', direccion: '', notas: '' });

const openCreate = () => { editing.value = null; form.reset(); showModal.value = true; };
const openEdit = (guest) => { editing.value = guest; form.nombre = guest.nombre; form.email = guest.email; form.telefono = guest.telefono; form.documento = guest.documento; form.identificacion = null; form.nacionalidad = guest.nacionalidad; form.direccion = guest.direccion; form.notas = guest.notas; showModal.value = true; };
const archivo = (event) => { form.identificacion = event.target.files?.[0] || null; };
const submit = () => {
    const done = () => { showModal.value = false; };
    const opciones = { forceFormData: true, onSuccess: done };
    if (editing.value) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('huespedes.update', editing.value.id), opciones);
    } else {
        form.post(route('huespedes.store'), opciones);
    }
};
const search = () => router.get(route('huespedes.index'), { q: props.filters.q || undefined }, { preserveState: true, replace: true });
const eliminar = (guest) => {
    if (window.confirm(`¿Eliminar a ${guest.nombre}? Solo se borra si no tiene reservaciones.`)) {
        router.delete(route('huespedes.destroy', guest.id));
    }
};
</script>

<template>
    <Head title="Huéspedes" />
    <AuthenticatedLayout>
        <template #header>Huéspedes</template>
        <div class="d-flex gap-2 mb-3">
            <input v-model="filters.q" class="form-control" placeholder="Buscar nombre, correo, documento" @keyup.enter="search" />
            <button class="btn btn-outline-secondary" @click="search">Buscar</button>
            <button class="btn btn-primary" @click="openCreate">Nuevo</button>
        </div>
        <div class="card"><div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Nombre</th><th>Correo</th><th>Teléfono</th><th>Documento</th><th>Identificación</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="guest in huespedes.data" :key="guest.id">
                        <td>{{ guest.nombre }}</td><td>{{ guest.email }}</td><td>{{ guest.telefono }}</td><td>{{ guest.documento }}</td>
                        <td><a v-if="guest.identificacion_url" :href="guest.identificacion_url" target="_blank" rel="noopener">Ver</a><span v-else class="text-muted">—</span></td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" @click="openEdit(guest)">Editar</button>
                            <button class="btn btn-sm btn-outline-danger" @click="eliminar(guest)">Eliminar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div></div>
        <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,.45)">
            <div class="modal-dialog"><form class="modal-content" @submit.prevent="submit">
                <div class="modal-header"><h5 class="modal-title">{{ editing ? 'Editar huésped' : 'Nuevo huésped' }}</h5></div>
                <div class="modal-body row g-2">
                    <div class="col-12"><input v-model="form.nombre" class="form-control" placeholder="Nombre" required /></div>
                    <div class="col-md-6"><input v-model="form.email" type="email" class="form-control" placeholder="Correo" /></div>
                    <div class="col-md-6"><input v-model="form.telefono" class="form-control" placeholder="Teléfono" /></div>
                    <div class="col-md-6"><input v-model="form.documento" class="form-control" placeholder="Documento" /></div>
                    <div class="col-12">
                        <label class="form-label">Identificación (foto o PDF)</label>
                        <input type="file" class="form-control" accept="image/*,.pdf" @change="archivo" />
                        <a v-if="editing?.identificacion_url" :href="editing.identificacion_url" target="_blank" rel="noopener" class="form-text">Ver archivo actual</a>
                    </div>
                    <div class="col-md-6"><input v-model="form.nacionalidad" class="form-control" placeholder="Nacionalidad" /></div>
                    <div class="col-12"><textarea v-model="form.notas" class="form-control" placeholder="Notas" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showModal = false">Cerrar</button>
                    <button class="btn btn-primary">Guardar</button>
                </div>
            </form></div>
        </div>
    </AuthenticatedLayout>
</template>
