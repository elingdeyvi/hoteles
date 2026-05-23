<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
          <h4 class="mb-0">Hoteles / propiedades</h4>
          <p class="text-muted small mb-0">Cada hotel tiene inventario, reservas y booking independiente.</p>
        </div>
        <button class="btn btn-primary btn-sm" @click="openForm()">Nuevo hotel</button>
      </div>

      <p v-if="error" class="text-danger small">{{ error }}</p>

      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Código (URL)</th>
              <th>Reservas web</th>
              <th>Activo</th>
              <th>Tipos hab.</th>
              <th>Reservas</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in items" :key="p.id">
              <td>{{ p.name }}</td>
              <td>
                <code>{{ p.code }}</code>
                <a :href="`/reservar/${p.code}`" target="_blank" class="small ms-1">/reservar/{{ p.code }}</a>
              </td>
              <td>
                <span class="badge" :class="p.booking_enabled ? 'bg-success-subtle text-success' : 'bg-secondary-subtle'">
                  {{ p.booking_enabled ? 'Sí' : 'No' }}
                </span>
              </td>
              <td>
                <span class="badge" :class="p.is_active ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger'">
                  {{ p.is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td>{{ p.room_types_count ?? 0 }}</td>
              <td>{{ p.reservations_count ?? 0 }}</td>
              <td class="text-nowrap">
                <button class="btn btn-sm btn-outline-primary me-1" @click="openForm(p)">Editar</button>
                <button class="btn btn-sm btn-outline-danger" :disabled="p.id === currentPropertyId" @click="remove(p)">
                  {{ p.is_active ? 'Desactivar' : 'Eliminar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="showForm" class="card mt-3 border-primary">
        <div class="card-body">
          <h6 class="card-title">{{ form.id ? 'Editar hotel' : 'Nuevo hotel' }}</h6>
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label small">Nombre *</label>
              <input v-model="form.name" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label small">Código URL *</label>
              <input v-model="form.code" class="form-control" placeholder="costa-azul" :disabled="!!form.id" />
              <div v-if="!form.id" class="form-text">Solo minúsculas y guiones. Ej: playa-norte</div>
            </div>
            <div class="col-md-2">
              <label class="form-label small">Moneda</label>
              <input v-model="form.currency" class="form-control" maxlength="3" />
            </div>
            <div class="col-md-3">
              <label class="form-label small">Correo</label>
              <input v-model="form.email" type="email" class="form-control" />
            </div>
            <div class="col-md-3">
              <label class="form-label small">Teléfono</label>
              <input v-model="form.phone" class="form-control" />
            </div>
            <div class="col-md-5">
              <label class="form-label small">Dirección</label>
              <input v-model="form.address" class="form-control" />
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <div class="form-check">
                <input v-model="form.booking_enabled" class="form-check-input" type="checkbox" id="bookingEnabled" />
                <label class="form-check-label small" for="bookingEnabled">Booking web</label>
              </div>
            </div>
            <div class="col-md-2 d-flex align-items-end" v-if="form.id">
              <div class="form-check">
                <input v-model="form.is_active" class="form-check-input" type="checkbox" id="isActive" />
                <label class="form-check-label small" for="isActive">Activo</label>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary btn-sm me-2" :disabled="saving" @click="save">
              {{ saving ? 'Guardando…' : 'Guardar' }}
            </button>
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
import { useProperty } from '@/composables/use-property';

const { currentId: currentPropertyId } = useProperty();

const items = ref([]);
const showForm = ref(false);
const saving = ref(false);
const error = ref('');
const form = ref(emptyForm());

function emptyForm() {
  return {
    id: null,
    name: '',
    code: '',
    currency: 'mxn',
    phone: '',
    email: '',
    address: '',
    booking_enabled: true,
    is_active: true,
  };
}

async function load() {
  error.value = '';
  const res = await HotelRepository.propertiesManage();
  items.value = res.data || [];
}

function openForm(item = null) {
  form.value = item
    ? {
        id: item.id,
        name: item.name,
        code: item.code,
        currency: item.currency || 'mxn',
        phone: item.phone || '',
        email: item.email || '',
        address: item.address || '',
        booking_enabled: !!item.booking_enabled,
        is_active: !!item.is_active,
      }
    : emptyForm();
  showForm.value = true;
}

async function save() {
  if (!form.value.name?.trim()) {
    error.value = 'El nombre es obligatorio.';
    return;
  }
  if (!form.value.id && !form.value.code?.trim()) {
    error.value = 'El código URL es obligatorio.';
    return;
  }
  saving.value = true;
  error.value = '';
  try {
    const payload = { ...form.value };
    delete payload.id;
    await HotelRepository.saveProperty(payload, form.value.id);
    showForm.value = false;
    await load();
  } catch (e) {
    const errs = e.response?.data?.errors;
    error.value = errs ? Object.values(errs).flat()[0] : (e.response?.data?.message || 'No se pudo guardar.');
  } finally {
    saving.value = false;
  }
}

async function remove(p) {
  if (!confirm(`¿Desactivar o eliminar el hotel «${p.name}»?`)) return;
  error.value = '';
  try {
    await HotelRepository.deleteProperty(p.id);
    await load();
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo eliminar.';
  }
}

onMounted(load);
</script>
