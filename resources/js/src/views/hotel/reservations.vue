<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <div class="d-flex justify-content-between mb-3">
        <h4 class="mb-0">Reservaciones</h4>
        <button class="btn btn-primary btn-sm" @click="showForm = true">Nueva reserva</button>
      </div>
      <table class="table table-hover">
        <div class="mb-2">
          <select v-model="filterSource" class="form-select form-select-sm d-inline-block w-auto me-2" @change="load">
            <option value="">Todas las fuentes</option>
            <option value="web">Solo web</option>
            <option value="recepcion">Solo recepción</option>
          </select>
        </div>
        <thead><tr><th>Folio</th><th>Origen</th><th>Huésped</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th>Estado</th><th>Pago</th><th>Total</th><th></th></tr></thead>
        <tbody>
          <tr v-for="r in items" :key="r.id">
            <td>{{ r.folio }}</td>
            <td><span class="badge" :class="r.source === 'web' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle'">{{ r.source === 'web' ? 'Web' : 'Recepción' }}</span></td>
            <td>{{ r.huesped?.nombre }}</td>
            <td>{{ r.room_type?.name }}</td>
            <td>{{ r.check_in }}</td>
            <td>{{ r.check_out }}</td>
            <td><span class="badge reservation-status" :class="r.status">{{ r.status }}</span></td>
            <td>
              <span v-if="r.payment_status && r.payment_status !== 'not_required'" class="badge bg-light text-dark">{{ r.payment_status }}</span>
              <span v-else class="text-muted small">—</span>
            </td>
            <td>${{ Number(r.estimated_total).toFixed(2) }}</td>
            <td>
              <button v-if="r.source === 'web' && r.status === 'pendiente'" class="btn btn-sm btn-primary me-1" @click="confirm(r.id)">Confirmar</button>
              <router-link :to="{ name: 'hotel-checkin-router', params: { id: r.id } }" class="btn btn-sm btn-outline-success me-1" v-if="r.status === 'confirmada'">Check-in</router-link>
              <button v-if="r.status !== 'cancelada' && r.status !== 'check_out'" class="btn btn-sm btn-outline-danger" @click="cancel(r.id)">Cancelar</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="showForm" class="card mt-3"><div class="card-body">
        <div class="row g-2">
          <div class="col-md-3">
            <select v-model="form.huesped_id" class="form-select">
              <option v-for="h in huespedes" :key="h.id" :value="h.id">{{ h.nombre }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model="form.room_type_id" class="form-select" @change="checkAvailability">
              <option v-for="t in types" :key="t.id" :value="t.id">{{ t.name }}</option>
            </select>
          </div>
          <div class="col-md-2"><input v-model="form.check_in" type="date" class="form-control" @change="checkAvailability" /></div>
          <div class="col-md-2"><input v-model="form.check_out" type="date" class="form-control" @change="checkAvailability" /></div>
          <div class="col-md-2"><input v-model.number="form.guests_count" type="number" min="1" class="form-control" /></div>
        </div>
        <p v-if="availability" class="text-muted small mt-2">
          Disponibles: {{ availability.available_rooms }} — Estimado: ${{ Number(availability.estimated_total).toFixed(2) }}
        </p>
        <button class="btn btn-primary btn-sm mt-2" @click="save">Crear reserva</button>
      </div></div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import HotelRepository from '@/repositories/HotelRepository';

const items = ref([]);
const filterSource = ref('');
const huespedes = ref([]);
const types = ref([]);
const showForm = ref(false);
const availability = ref(null);
const form = ref({ huesped_id: null, room_type_id: null, check_in: '', check_out: '', guests_count: 1 });

async function load() {
  const [res, h, t] = await Promise.all([
    HotelRepository.reservations({ per_page: 50, source: filterSource.value || undefined }),
    HotelRepository.huespedes({ per_page: 100 }),
    HotelRepository.roomTypes(),
  ]);
  items.value = res.data?.data || [];
  huespedes.value = h.data?.data || h.data || [];
  types.value = t.data || [];
  form.value.huesped_id = huespedes.value[0]?.id;
  form.value.room_type_id = types.value[0]?.id;
}

async function checkAvailability() {
  if (!form.value.check_in || !form.value.check_out) return;
  const res = await HotelRepository.availability({
    check_in: form.value.check_in,
    check_out: form.value.check_out,
    room_type_id: form.value.room_type_id,
  });
  availability.value = (res.data || []).find((x) => x.room_type?.id === form.value.room_type_id) || res.data?.[0];
}

async function save() {
  await HotelRepository.saveReservation(form.value);
  showForm.value = false;
  await load();
}

async function cancel(id) {
  await HotelRepository.cancelReservation(id);
  await load();
}

async function confirm(id) {
  try {
    await HotelRepository.confirmReservation(id);
    await load();
  } catch (e) {
    alert(e.response?.data?.message || 'No se pudo confirmar.');
  }
}

onMounted(load);
</script>
