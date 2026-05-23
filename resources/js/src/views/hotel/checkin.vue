<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <h4 class="mb-3">Check-in / Check-out</h4>
      <div v-if="loading" class="text-center py-4"><div class="spinner-border text-primary"></div></div>
      <template v-else-if="reservation">
        <p><strong>Folio:</strong> {{ reservation.folio }} — <strong>Huésped:</strong> {{ reservation.huesped?.nombre }}</p>
        <p><strong>Estado:</strong> {{ reservation.status }} — <strong>Tipo:</strong> {{ reservation.room_type?.name }}</p>
        <div v-if="reservation.status === 'confirmada'" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Asignar habitación</label>
            <select v-model="roomId" class="form-select">
              <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.number }} ({{ r.status }})</option>
            </select>
          </div>
          <div class="col-md-4">
            <button class="btn btn-success" @click="doCheckIn">Registrar check-in</button>
          </div>
        </div>
        <div v-else-if="reservation.status === 'check_in'" class="mt-3 d-flex flex-wrap gap-2">
          <button class="btn btn-warning" :disabled="checkingOut" @click="doCheckOut">Registrar check-out</button>
          <router-link :to="{ name: 'hotel-folio-router' }" class="btn btn-outline-primary">Ir a folios</router-link>
          <button
            v-if="folioId"
            type="button"
            class="btn btn-outline-secondary"
            :disabled="pdfLoading"
            @click="downloadInvoice"
          >
            Factura PDF
          </button>
        </div>
        <div v-else-if="reservation.status === 'check_out' && folioId" class="alert alert-success mt-3">
          Check-out completado.
          <button type="button" class="btn btn-sm btn-primary ms-2" :disabled="pdfLoading" @click="downloadInvoice">
            Descargar factura PDF
          </button>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import HotelRepository from '@/repositories/HotelRepository';
import api from '@/services/ApiService';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const reservation = ref(null);
const rooms = ref([]);
const roomId = ref(null);
const checkingOut = ref(false);
const pdfLoading = ref(false);

const folioId = computed(() => reservation.value?.stays?.[0]?.folio?.id ?? null);

async function load() {
  const id = route.params.id;
  const res = await api.get(`/hotel/reservations/${id}`);
  reservation.value = res.data.data;
  const r = await HotelRepository.rooms({ room_type_id: reservation.value.room_type_id });
  rooms.value = (r.data || []).filter((x) => x.status === 'disponible' || x.status === 'limpia');
  roomId.value = rooms.value[0]?.id;
  loading.value = false;
}

async function doCheckIn() {
  await HotelRepository.checkIn(reservation.value.id, roomId.value);
  router.push({ name: 'hotel-reservations-router' });
}

async function doCheckOut() {
  checkingOut.value = true;
  try {
    reservation.value = await HotelRepository.checkOut(reservation.value.id);
    if (folioId.value) {
      await HotelRepository.downloadInvoicePdf(folioId.value);
    }
  } finally {
    checkingOut.value = false;
  }
}

async function downloadInvoice() {
  if (!folioId.value) return;
  pdfLoading.value = true;
  try {
    await HotelRepository.downloadInvoicePdf(folioId.value);
  } finally {
    pdfLoading.value = false;
  }
}

onMounted(load);
</script>
