<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <h4 class="mb-3">Tarifas</h4>
      <button class="btn btn-primary btn-sm mb-3" @click="showForm = true">Nueva tarifa</button>
      <table class="table table-hover">
        <thead><tr><th>Tipo</th><th>Nombre</th><th>Precio</th><th>Fin de semana</th></tr></thead>
        <tbody>
          <tr v-for="r in items" :key="r.id">
            <td>{{ r.room_type?.name }}</td>
            <td>{{ r.name }}</td>
            <td>${{ Number(r.price).toFixed(2) }}</td>
            <td>{{ r.is_weekend ? 'Sí' : 'No' }}</td>
          </tr>
        </tbody>
      </table>
      <div v-if="showForm" class="card mt-3"><div class="card-body row g-2">
        <div class="col-md-3">
          <select v-model="form.room_type_id" class="form-select">
            <option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
          </select>
        </div>
        <div class="col-md-3"><input v-model="form.name" class="form-control" placeholder="Nombre tarifa" /></div>
        <div class="col-md-2"><input v-model.number="form.price" type="number" class="form-control" /></div>
        <div class="col-md-2 form-check pt-2">
          <input id="wk" v-model="form.is_weekend" type="checkbox" class="form-check-input" />
          <label for="wk" class="form-check-label">Fin de semana</label>
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
const form = ref({ room_type_id: null, name: 'Estándar', price: 0, is_weekend: false });

async function load() {
  const [rates, rt] = await Promise.all([HotelRepository.rates(), HotelRepository.roomTypes()]);
  items.value = rates.data || [];
  types.value = rt.data || [];
  form.value.room_type_id = types.value[0]?.id;
}

async function save() {
  await HotelRepository.saveRate(form.value);
  showForm.value = false;
  await load();
}

onMounted(load);
</script>
