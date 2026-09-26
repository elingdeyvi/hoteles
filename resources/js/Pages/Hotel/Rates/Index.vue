<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ rates: Array, roomTypes: Array });
const showModal = ref(false);
const editing = ref(null);
const seasonFor = ref(null);
const form = useForm({ room_type_id: '', name: '', price: 0, is_weekend: false, is_active: true });
const season = useForm({ season_name: '', starts_on: '', ends_on: '', price_override: '', multiplier: 1, is_active: true });
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const day = (d) => (d ? String(d).slice(0, 10) : '');

const openCreate = () => { editing.value = null; form.reset(); form.is_active = true; showModal.value = true; };
const openEdit = (rate) => { editing.value = rate; form.name = rate.name; form.price = rate.price; form.is_weekend = rate.is_weekend; form.is_active = rate.is_active; showModal.value = true; };
const submit = () => {
    const done = () => { showModal.value = false; };
    if (editing.value) form.put(route('tarifas.update', editing.value.id), { onSuccess: done });
    else form.post(route('tarifas.store'), { onSuccess: done });
};
const saveSeason = () => season.post(route('tarifas.seasons.store', seasonFor.value), { onSuccess: () => { season.reset(); seasonFor.value = null; } });
const eliminarTarifa = (rate) => {
    if (window.confirm(`¿Eliminar la tarifa ${rate.name}? También se quitan sus temporadas.`)) {
        router.delete(route('tarifas.destroy', rate.id));
    }
};
const eliminarTemporada = (item) => {
    if (window.confirm(`¿Eliminar la temporada ${item.season_name}?`)) {
        router.delete(route('tarifas.seasons.destroy', item.id));
    }
};
</script>

<template>
    <Head title="Tarifas" />
    <AuthenticatedLayout>
        <template #header>Tarifas</template>
        <button class="btn btn-primary mb-3" @click="openCreate">Nueva tarifa</button>
        <div class="card"><div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Tipo</th><th>Nombre</th><th>Precio</th><th>Fin de semana</th><th>Temporadas</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="rate in rates" :key="rate.id">
                        <td>{{ rate.room_type?.name }}</td>
                        <td>{{ rate.name }}</td>
                        <td>{{ money(rate.price) }}</td>
                        <td>{{ rate.is_weekend ? 'Sí' : 'No' }}</td>
                        <td>
                            <div v-for="item in rate.season_rates" :key="item.id" class="d-flex align-items-center gap-2">
                                <span>{{ item.season_name }} ({{ day(item.starts_on) }} – {{ day(item.ends_on) }})</span>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="eliminarTemporada(item)">Eliminar</button>
                            </div>
                            <button class="btn btn-sm btn-link" @click="seasonFor = rate.id">Agregar temporada</button>
                        </td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary" @click="openEdit(rate)">Editar</button>
                            <button class="btn btn-sm btn-outline-danger ms-1" @click="eliminarTarifa(rate)">Eliminar</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div></div>
        <div v-if="showModal" class="modal d-block" style="background: rgba(0,0,0,.45)">
            <div class="modal-dialog"><form class="modal-content" @submit.prevent="submit">
                <div class="modal-header"><h5 class="modal-title">Tarifa</h5></div>
                <div class="modal-body row g-2">
                    <div v-if="!editing" class="col-12"><select v-model="form.room_type_id" class="form-select" required><option value="">Tipo</option><option v-for="type in roomTypes" :key="type.id" :value="type.id">{{ type.name }}</option></select></div>
                    <div class="col-md-6"><input v-model="form.name" class="form-control" placeholder="Nombre" required /></div>
                    <div class="col-md-6"><input v-model="form.price" type="number" step="0.01" min="0" class="form-control" required /></div>
                    <div class="col-12"><div class="form-check"><input v-model="form.is_weekend" class="form-check-input" type="checkbox" id="weekend" /><label class="form-check-label" for="weekend">Fin de semana</label></div></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" @click="showModal = false">Cerrar</button><button class="btn btn-primary">Guardar</button></div>
            </form></div>
        </div>
        <div v-if="seasonFor" class="modal d-block" style="background: rgba(0,0,0,.45)">
            <div class="modal-dialog"><form class="modal-content" @submit.prevent="saveSeason">
                <div class="modal-header"><h5 class="modal-title">Temporada</h5></div>
                <div class="modal-body row g-2">
                    <div class="col-12"><input v-model="season.season_name" class="form-control" placeholder="Nombre" required /></div>
                    <div class="col-md-6"><input v-model="season.starts_on" type="date" class="form-control" required /></div>
                    <div class="col-md-6"><input v-model="season.ends_on" type="date" class="form-control" required /></div>
                    <div class="col-md-6"><input v-model="season.price_override" type="number" step="0.01" class="form-control" placeholder="Precio fijo" /></div>
                    <div class="col-md-6"><input v-model="season.multiplier" type="number" step="0.01" class="form-control" placeholder="Multiplicador" /></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" @click="seasonFor = null">Cerrar</button><button class="btn btn-primary">Guardar</button></div>
            </form></div>
        </div>
    </AuthenticatedLayout>
</template>
