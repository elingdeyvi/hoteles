<template>
  <div class="layout-px-spacing">
    <div class="row layout-top-spacing g-3">
      <div class="col-xl-8">
        <div class="panel hotel-panel p-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
              <h4 class="mb-0">Planning de reservas</h4>
              <p class="text-muted small mb-0">Calendario mensual de ocupación</p>
            </div>
            <router-link to="/hotel/recepcion/reservas" class="btn btn-primary btn-sm">Nueva reserva</router-link>
          </div>
          <FullCalendar ref="calendarRef" :options="calendarOptions" />
          <div class="d-flex flex-wrap gap-3 mt-3 small">
            <span><span class="legend-dot" style="background:#1e5f8a"></span> Confirmada</span>
            <span><span class="legend-dot" style="background:#22c55e"></span> Check-in</span>
            <span><span class="legend-dot" style="background:#64748b"></span> Pendiente</span>
          </div>
        </div>
      </div>
      <div class="col-xl-4">
        <div class="panel hotel-panel p-4">
          <h5 class="mb-3">Ocupación del día</h5>
          <input v-model="boardDate" type="date" class="form-control mb-3" @change="loadBoard" />
          <div v-if="boardLoading" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>
          <template v-else-if="board">
            <div class="row g-2 mb-3 text-center">
              <div class="col-4"><div class="hotel-stat-card p-2 rounded"><small class="text-muted">Total</small><div class="fw-bold">{{ board.summary?.total_rooms }}</div></div></div>
              <div class="col-4"><div class="hotel-stat-card p-2 rounded"><small class="text-muted">Ocupadas</small><div class="fw-bold text-danger">{{ board.summary?.occupied }}</div></div></div>
              <div class="col-4"><div class="hotel-stat-card p-2 rounded"><small class="text-muted">Libres</small><div class="fw-bold text-success">{{ board.summary?.available }}</div></div></div>
            </div>
            <div class="list-group list-group-flush" style="max-height: 420px; overflow-y: auto">
              <div
                v-for="row in board.rooms"
                :key="row.room.id"
                class="list-group-item px-0 d-flex justify-content-between align-items-start"
              >
                <div>
                  <strong>{{ row.room.number }}</strong>
                  <small class="d-block text-muted">{{ row.room.room_type?.name }} · Piso {{ row.room.floor }}</small>
                  <small v-if="row.reservation" class="text-primary">{{ row.reservation.huesped?.nombre }}</small>
                </div>
                <span class="badge room-status-badge" :class="row.occupied ? 'ocupada' : row.room.status">
                  {{ row.occupied ? 'ocupada' : row.room.status }}
                </span>
              </div>
            </div>
            <div v-if="board.unassigned_reservations?.length" class="mt-3">
              <p class="small fw-bold mb-1">Sin habitación asignada</p>
              <ul class="small mb-0 ps-3">
                <li v-for="r in board.unassigned_reservations" :key="r.id">{{ r.huesped?.nombre }} ({{ r.folio }})</li>
              </ul>
            </div>
          </template>
        </div>
        <div v-if="selectedEvent" class="panel hotel-panel p-3 mt-3">
          <h6 class="mb-2">Reserva seleccionada</h6>
          <p class="small mb-1"><strong>Folio:</strong> {{ selectedEvent.extendedProps?.folio }}</p>
          <p class="small mb-1"><strong>Estado:</strong> {{ selectedEvent.extendedProps?.status }}</p>
          <p class="small mb-2"><strong>Total est.:</strong> ${{ Number(selectedEvent.extendedProps?.estimated_total || 0).toFixed(2) }}</p>
          <router-link
            v-if="selectedEvent.extendedProps?.status === 'confirmada'"
            :to="{ name: 'hotel-checkin-router', params: { id: selectedEvent.id } }"
            class="btn btn-sm btn-success"
          >
            Ir a check-in
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import HotelRepository from '@/repositories/HotelRepository';

const calendarRef = ref(null);
const boardDate = ref(new Date().toISOString().slice(0, 10));
const board = ref(null);
const boardLoading = ref(false);
const selectedEvent = ref(null);

async function fetchEvents(info, successCallback, failureCallback) {
  try {
    const res = await HotelRepository.planningCalendar({
      start: info.startStr.slice(0, 10),
      end: info.endStr.slice(0, 10),
    });
    successCallback(res.data || []);
  } catch (e) {
    failureCallback(e);
  }
}

const calendarOptions = {
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locale: 'es',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth',
  },
  height: 'auto',
  events: fetchEvents,
  eventClick(info) {
    selectedEvent.value = {
      id: info.event.id,
      title: info.event.title,
      extendedProps: info.event.extendedProps,
    };
    boardDate.value = info.event.startStr.slice(0, 10);
    loadBoard();
  },
  dateClick(info) {
    boardDate.value = info.dateStr;
    loadBoard();
    selectedEvent.value = null;
  },
};

async function loadBoard() {
  boardLoading.value = true;
  try {
    const res = await HotelRepository.planningRoomBoard({ date: boardDate.value });
    board.value = res.data;
  } finally {
    boardLoading.value = false;
  }
}

onMounted(loadBoard);
</script>

<style scoped>
.legend-dot {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  margin-right: 4px;
}
</style>
