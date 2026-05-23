<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <div class="d-flex justify-content-between mb-3">
        <h4 class="mb-0">Catálogo POS</h4>
        <router-link to="/hotel/pos" class="btn btn-outline-primary btn-sm">Ir al POS</router-link>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <h6>Nuevo punto de venta</h6>
          <input v-model="newOutlet.name" class="form-control form-control-sm mb-1" placeholder="Nombre" />
          <input v-model="newOutlet.code" class="form-control form-control-sm mb-2" placeholder="Código" />
          <button class="btn btn-sm btn-primary" @click="saveOutlet">Agregar outlet</button>
        </div>
        <div class="col-md-4">
          <h6>Nueva categoría</h6>
          <select v-model="newCategory.pos_outlet_id" class="form-select form-select-sm mb-1">
            <option v-for="o in outlets" :key="o.id" :value="o.id">{{ o.name }}</option>
          </select>
          <input v-model="newCategory.name" class="form-control form-control-sm mb-2" placeholder="Nombre categoría" />
          <button class="btn btn-sm btn-primary" @click="saveCategory">Agregar categoría</button>
        </div>
        <div class="col-md-4">
          <h6>Nuevo producto</h6>
          <select v-model="newProduct.pos_category_id" class="form-select form-select-sm mb-1">
            <option v-for="c in allCategories" :key="c.id" :value="c.id">{{ c.label }}</option>
          </select>
          <input v-model="newProduct.name" class="form-control form-control-sm mb-1" placeholder="Nombre" />
          <input v-model.number="newProduct.price" type="number" step="0.01" class="form-control form-control-sm mb-2" placeholder="Precio" />
          <button class="btn btn-sm btn-primary" @click="saveProduct">Agregar producto</button>
        </div>
      </div>

      <div v-for="o in outlets" :key="o.id" class="mb-4">
        <h5>{{ o.name }} <small class="text-muted">({{ o.code }})</small></h5>
        <div v-for="cat in o.categories" :key="cat.id" class="ms-3 mb-2">
          <strong>{{ cat.name }}</strong>
          <table class="table table-sm mt-1">
            <tbody>
              <tr v-for="p in cat.products" :key="p.id">
                <td>{{ p.name }}</td>
                <td>${{ Number(p.price).toFixed(2) }}</td>
                <td><span class="badge" :class="p.is_active ? 'bg-success' : 'bg-secondary'">{{ p.is_active ? 'Activo' : 'Inactivo' }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import HotelRepository from '@/repositories/HotelRepository';

const outlets = ref([]);
const newOutlet = ref({ name: '', code: '' });
const newCategory = ref({ pos_outlet_id: null, name: '' });
const newProduct = ref({ pos_category_id: null, name: '', price: 0 });

const allCategories = computed(() => {
  const list = [];
  outlets.value.forEach((o) => {
    (o.categories || []).forEach((c) => {
      list.push({ id: c.id, label: `${o.name} / ${c.name}` });
    });
  });
  return list;
});

async function load() {
  const res = await HotelRepository.posAdminCatalog();
  outlets.value = res.data || [];
  newCategory.value.pos_outlet_id = outlets.value[0]?.id ?? null;
  newProduct.value.pos_category_id = allCategories.value[0]?.id ?? null;
}

async function saveOutlet() {
  await HotelRepository.posSaveOutlet(newOutlet.value);
  newOutlet.value = { name: '', code: '' };
  await load();
}

async function saveCategory() {
  await HotelRepository.posSaveCategory(newCategory.value);
  newCategory.value.name = '';
  await load();
}

async function saveProduct() {
  await HotelRepository.posSaveProduct(newProduct.value);
  newProduct.value = { pos_category_id: allCategories.value[0]?.id, name: '', price: 0 };
  await load();
}

onMounted(load);
</script>
