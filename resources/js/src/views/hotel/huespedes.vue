<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <div class="d-flex justify-content-between mb-3">
        <h4 class="mb-0">Huéspedes</h4>
        <button class="btn btn-primary btn-sm" @click="openForm()">Nuevo huésped</button>
      </div>
      <input v-model="q" class="form-control mb-3" placeholder="Buscar..." @input="load" />
      <table class="table table-hover">
        <thead><tr><th>Nombre</th><th>Email</th><th>Teléfono</th><th>Documento</th><th></th></tr></thead>
        <tbody>
          <tr v-for="h in items" :key="h.id">
            <td>{{ h.nombre }}</td><td>{{ h.email }}</td><td>{{ h.telefono }}</td><td>{{ h.documento }}</td>
            <td><button class="btn btn-sm btn-outline-primary" @click="openForm(h)">Editar</button></td>
          </tr>
        </tbody>
      </table>
      <div v-if="showForm" class="card mt-3"><div class="card-body row g-2">
        <div class="col-md-4"><input v-model="form.nombre" class="form-control" placeholder="Nombre *" /></div>
        <div class="col-md-4"><input v-model="form.email" class="form-control" placeholder="Email" /></div>
        <div class="col-md-4"><input v-model="form.telefono" class="form-control" placeholder="Teléfono" /></div>
        <div class="col-md-4"><input v-model="form.documento" class="form-control" placeholder="Documento" /></div>
        <div class="col-12"><button class="btn btn-primary btn-sm" @click="save">Guardar</button></div>
      </div></div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import HotelRepository from '@/repositories/HotelRepository';

const items = ref([]);
const q = ref('');
const showForm = ref(false);
const form = ref({});

async function load() {
  const res = await HotelRepository.huespedes({ q: q.value, per_page: 50 });
  items.value = res.data?.data || res.data || [];
}

function openForm(h = null) {
  form.value = h ? { ...h } : { nombre: '', email: '', telefono: '', documento: '' };
  showForm.value = true;
}

async function save() {
  await HotelRepository.saveHuesped(form.value, form.value.id);
  showForm.value = false;
  await load();
}

onMounted(load);
</script>
