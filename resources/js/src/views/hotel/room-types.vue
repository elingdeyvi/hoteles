<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Tipos de habitación</h4>
        <button class="btn btn-primary btn-sm" @click="openForm()">Nuevo tipo</button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead><tr><th>Nombre</th><th>Código</th><th>Capacidad</th><th>Precio base</th><th>Habitaciones</th><th></th></tr></thead>
          <tbody>
            <tr v-for="t in items" :key="t.id">
              <td>{{ t.name }}</td><td>{{ t.code }}</td><td>{{ t.capacity }}</td>
              <td>${{ Number(t.base_price).toFixed(2) }}</td><td>{{ t.rooms_count }}</td>
              <td><button class="btn btn-sm btn-outline-primary" @click="openForm(t)">Editar</button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="showForm" class="card mt-3">
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-4"><input v-model="form.name" class="form-control" placeholder="Nombre" /></div>
            <div class="col-md-2"><input v-model.number="form.capacity" type="number" class="form-control" placeholder="Capacidad" /></div>
            <div class="col-md-2"><input v-model.number="form.base_price" type="number" step="0.01" class="form-control" placeholder="Precio base" /></div>
            <div class="col-md-4"><input v-model="form.description" class="form-control" placeholder="Descripción" /></div>
          </div>
          <div class="mt-2">
            <button class="btn btn-primary btn-sm me-2" @click="save">Guardar</button>
            <button class="btn btn-outline-secondary btn-sm" @click="showForm = false">Cancelar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import HotelRepository from '@/repositories/HotelRepository';

const items = ref([]);
const showForm = ref(false);
const form = ref({ id: null, name: '', capacity: 2, base_price: 0, description: '' });

async function load() {
  const res = await HotelRepository.roomTypes();
  items.value = res.data || [];
}

function openForm(item = null) {
  form.value = item ? { ...item } : { id: null, name: '', capacity: 2, base_price: 0, description: '' };
  showForm.value = true;
}

async function save() {
  await HotelRepository.saveRoomType(form.value, form.value.id);
  showForm.value = false;
  await load();
}

onMounted(load);
</script>
