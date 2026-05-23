<template>
  <div class="layout-px-spacing">
    <div class="row layout-top-spacing">
      <div class="col-12">
        <div class="panel hotel-panel p-4">
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <h4 class="mb-0">Panel del hotel</h4>
            <span class="text-muted small">{{ todayLabel }}</span>
          </div>

          <div v-if="loading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>

          <template v-else>
            <p class="text-muted small text-uppercase fw-semibold mb-2">Operación hoy</p>
            <div class="row g-3 mb-4">
              <div class="col-6 col-md-4 col-xl-2" v-for="card in operationCards" :key="card.label">
                <div class="card hotel-stat-card h-100" :class="card.accent">
                  <div class="card-body py-3">
                    <p class="text-muted small mb-1">{{ card.label }}</p>
                    <h4 class="mb-0">{{ card.value }}</h4>
                    <p v-if="card.hint" class="small text-muted mb-0 mt-1">{{ card.hint }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="row g-4">
              <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h6 class="mb-0">Reservas web pendientes</h6>
                      <router-link
                        v-if="can('recepcion.reservas')"
                        to="/hotel/recepcion/reservas"
                        class="btn btn-sm btn-outline-primary"
                      >
                        Ver todas
                      </router-link>
                    </div>
                    <div v-if="!data.pending_web_list?.length" class="text-muted small py-3 text-center">
                      No hay solicitudes web por confirmar.
                    </div>
                    <div v-else class="table-responsive">
                      <table class="table table-sm table-hover mb-0">
                        <thead>
                          <tr>
                            <th>Folio</th>
                            <th>Huésped</th>
                            <th>Tipo</th>
                            <th>Entrada</th>
                            <th>Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="r in data.pending_web_list" :key="r.id">
                            <td>{{ r.folio }}</td>
                            <td>{{ r.huesped?.nombre }}</td>
                            <td>{{ r.room_type?.name }}</td>
                            <td>{{ r.check_in }}</td>
                            <td>${{ Number(r.estimated_total).toFixed(2) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                  <div class="card-body">
                    <h6 class="mb-3">Estado de habitaciones</h6>
                    <div class="d-flex flex-wrap gap-2">
                      <span
                        v-for="(count, status) in data.rooms_by_status"
                        :key="status"
                        class="badge room-status-badge"
                        :class="status"
                      >
                        {{ status }}: {{ count }}
                      </span>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                      {{ data.rooms_occupied }} / {{ data.rooms_total }} ocupadas
                    </p>
                  </div>
                </div>

                <div class="card border-0 shadow-sm">
                  <div class="card-body">
                    <h6 class="mb-3">POS hoy por outlet</h6>
                    <ul class="list-group list-group-flush" v-if="data.pos_by_outlet_today?.length">
                      <li
                        v-for="row in data.pos_by_outlet_today"
                        :key="row.outlet_name"
                        class="list-group-item d-flex justify-content-between px-0"
                      >
                        <span>{{ row.outlet_name }}</span>
                        <strong class="text-primary">${{ Number(row.total).toFixed(2) }}</strong>
                      </li>
                    </ul>
                    <p v-else class="text-muted small mb-0">Sin ventas POS registradas hoy.</p>
                    <router-link
                      v-if="can('reportes.ver')"
                      to="/hotel/reportes"
                      class="btn btn-sm btn-link px-0 mt-2"
                    >
                      Ir a reportes
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import HotelRepository from '@/repositories/HotelRepository';
import { usePermissions } from '@/composables/use-permissions';

const { can } = usePermissions();
const loading = ref(true);
const data = ref({});

const todayLabel = new Date().toLocaleDateString('es-MX', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric',
});

const operationCards = computed(() => [
  {
    label: 'Llegadas hoy',
    value: data.value.arrivals_today ?? 0,
    hint: null,
    accent: '',
  },
  {
    label: 'Salidas hoy',
    value: data.value.departures_today ?? 0,
    hint: null,
    accent: '',
  },
  {
    label: 'Ocupación',
    value: `${data.value.occupancy_percent ?? 0}%`,
    hint: `${data.value.rooms_occupied ?? 0} hab.`,
    accent: '',
  },
  {
    label: 'Web pendientes',
    value: data.value.pending_web_reservations ?? 0,
    hint: 'Por confirmar',
    accent: (data.value.pending_web_reservations ?? 0) > 0 ? 'border-warning' : '',
  },
  {
    label: 'POS hoy',
    value: `$${Number(data.value.pos_sales_today ?? 0).toFixed(2)}`,
    hint: `Mes: $${Number(data.value.pos_sales_month ?? 0).toFixed(0)}`,
    accent: '',
  },
  {
    label: 'Saldo folios',
    value: `$${Number(data.value.pending_balance ?? 0).toFixed(2)}`,
    hint: `${data.value.open_folios ?? 0} abiertos`,
    accent: (data.value.pending_balance ?? 0) > 0 ? 'border-danger' : '',
  },
]);

onMounted(async () => {
  try {
    const res = await HotelRepository.dashboard();
    data.value = res.data || {};
  } finally {
    loading.value = false;
  }
});
</script>
