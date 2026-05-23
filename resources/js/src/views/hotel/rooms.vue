<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <div class="d-flex justify-content-between mb-3">
        <h4 class="mb-0">Habitaciones</h4>
        <button class="btn btn-primary btn-sm" @click="openForm()">Nueva habitación</button>
      </div>
      <table class="table table-hover">
        <thead><tr><th>Número</th><th>Tipo</th><th>Piso</th><th>Estado</th><th></th></tr></thead>
        <tbody>
          <tr v-for="r in items" :key="r.id">
            <td>{{ r.number }}</td>
            <td>{{ r.room_type?.name }}</td>
            <td>{{ r.floor }}</td>
            <td><span class="badge room-status-badge" :class="r.status">{{ r.status }}</span></td>
            <td><button class="btn btn-sm btn-outline-primary" @click="openForm(r)">Editar</button></td>
          </tr>
        </tbody>
      </table>
      <div v-if="showForm" class="card mt-3"><div class="card-body row g-2">
        <div class="col-md-3"><input v-model="form.number" class="form-control" placeholder="Número" /></div>
        <div class="col-md-3">
          <select v-model="form.room_type_id" class="form-select">
            <option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
        </div>
        <div class="col-md-2"><input v-model.number="form.floor" type="number" class="form-control" /></div>
        <div class="col-md-2">
          <select v-model="form.status" class="form-select">
            <option value="disponible">disponible</option>
            <option value="limpia">limpia</option>
            <option value="sucia">sucia</option>
            <option value="mantenimiento">mantenimiento</option>
          </select>
        </div>
        <div class="col-12"><button class="btn btn-primary btn-sm" @click="save">Guardar</button></div>
      </div></div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import HotelRepository from '@/repositories/HotelRepository';

const items = ref([]);
const types = ref([]);
const showForm = ref(false);
const form = ref({});

async function load() {
  const [rooms, rt] = await Promise.all([HotelRepository.rooms(), HotelRepository.roomTypes()]);
  items.value = rooms.data || [];
  types.value = rt.data || [];
}

function openForm(r = null) {
  form.value = r ? { ...r, room_type_id: r.room_type_id || r.room_type?.id } : { number: '', room_type_id: types.value[0]?.id, floor: 1, status: 'disponible' };
  showForm.value = true;
}

async function save() {
  await HotelRepository.saveRoom(form.value, form.value.id);
  showForm.value = false;
  await load();
}

onMounted(load);
</script>
