<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <h4 class="mb-3">Limpieza y mantenimiento</h4>
      <div class="row g-3">
        <div v-for="(rooms, status) in board" :key="status" class="col-md-4">
          <div class="card h-100">
            <div class="card-header text-capitalize">{{ status }} ({{ rooms.length }})</div>
            <div class="card-body">
              <div v-for="r in rooms" :key="r.id" class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                <div>
                  <strong>{{ r.number }}</strong>
                  <small class="d-block text-muted">{{ r.room_type?.name }} — Piso {{ r.floor }}</small>
                </div>
                <select class="form-select form-select-sm w-auto" :value="r.status" @change="updateStatus(r.id, $event.target.value)">
                  <option value="limpia">Limpia</option>
                  <option value="sucia">Sucia</option>
                  <option value="disponible">Disponible</option>
                  <option value="mantenimiento">Mantenimiento</option>
                  <option value="ocupada" disabled>ocupada</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import HotelRepository from '@/repositories/HotelRepository';

const board = ref({});

async function load() {
  const res = await HotelRepository.housekeepingBoard();
  board.value = res.data || {};
}

async function updateStatus(roomId, status) {
  await HotelRepository.updateRoomStatus(roomId, status);
  await load();
}

onMounted(load);
</script>
