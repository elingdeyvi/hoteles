<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <h4 class="mb-3">Reportes</h4>

      <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
          <button type="button" class="nav-link" :class="{ active: tab === 'general' }" @click="tab = 'general'">General</button>
        </li>
        <li class="nav-item">
          <button type="button" class="nav-link" :class="{ active: tab === 'pos' }" @click="tab = 'pos'; loadPos()">POS consumos</button>
        </li>
      </ul>

      <div v-show="tab === 'general'">
        <div class="row g-2 mb-3">
          <div class="col-md-3"><input v-model="from" type="date" class="form-control" /></div>
          <div class="col-md-3"><input v-model="to" type="date" class="form-control" /></div>
          <div class="col-md-2"><button class="btn btn-primary w-100" @click="loadGeneral">Actualizar</button></div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" :disabled="exporting" @click="exportRevenue">Exportar CSV</button>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <h6>Ingresos por día (pagos)</h6>
            <ul class="list-group">
              <li v-for="row in revenue.by_day" :key="row.day" class="list-group-item d-flex justify-content-between">
                <span>{{ row.day }}</span><span>${{ Number(row.total).toFixed(2) }}</span>
              </li>
              <li v-if="!revenue.by_day?.length" class="list-group-item text-muted">Sin datos</li>
            </ul>
          </div>
          <div class="col-md-6">
            <h6>Ingresos por tipo de habitación</h6>
            <ul class="list-group">
              <li v-for="row in revenue.by_room_type" :key="row.room_type" class="list-group-item d-flex justify-content-between">
                <span>{{ row.room_type }}</span><span>${{ Number(row.total).toFixed(2) }}</span>
              </li>
              <li v-if="!revenue.by_room_type?.length" class="list-group-item text-muted">Sin datos</li>
            </ul>
          </div>
        </div>
        <hr />
        <h6>Llegadas y salidas — {{ today }}</h6>
        <div class="row">
          <div class="col-md-6">
            <p class="fw-bold">Llegadas</p>
            <ul><li v-for="a in arrivals" :key="a.id">{{ a.huesped?.nombre }} — {{ a.room_type?.name }}</li></ul>
            <p v-if="!arrivals.length" class="text-muted small">Ninguna</p>
          </div>
          <div class="col-md-6">
            <p class="fw-bold">Salidas</p>
            <ul><li v-for="d in departures" :key="d.id">{{ d.huesped?.nombre }} — Hab. {{ d.room?.number }}</li></ul>
            <p v-if="!departures.length" class="text-muted small">Ninguna</p>
          </div>
        </div>
      </div>

      <div v-show="tab === 'pos'">
        <div class="row g-2 mb-3 align-items-end">
          <div class="col-md-2">
            <label class="form-label small mb-0">Desde</label>
            <input v-model="posFrom" type="date" class="form-control" />
          </div>
          <div class="col-md-2">
            <label class="form-label small mb-0">Hasta</label>
            <input v-model="posTo" type="date" class="form-control" />
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-0">Outlet</label>
            <select v-model="posOutletId" class="form-select">
              <option :value="null">Todos</option>
              <option v-for="o in posOutlets" :key="o.id" :value="o.id">{{ o.name }}</option>
            </select>
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary w-100" :disabled="posLoading" @click="loadPos">
              {{ posLoading ? 'Cargando…' : 'Actualizar' }}
            </button>
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100" :disabled="exporting" @click="exportPos">Exportar CSV</button>
          </div>
        </div>

        <div v-if="posReport.summary" class="row g-3 mb-4">
          <div class="col-md-4">
            <div class="card hotel-stat-card h-100">
              <div class="card-body">
                <div class="text-muted small">Ventas POS</div>
                <div class="fs-4 fw-bold text-primary">${{ Number(posReport.summary.total_sales).toFixed(2) }}</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card h-100 border">
              <div class="card-body">
                <div class="text-muted small">Líneas de cargo</div>
                <div class="fs-4 fw-bold">{{ posReport.summary.lines_count }}</div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card h-100 border">
              <div class="card-body">
                <div class="text-muted small">Ticket promedio</div>
                <div class="fs-4 fw-bold">${{ Number(posReport.summary.average_ticket).toFixed(2) }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <h6>Ventas por día</h6>
            <table class="table table-sm table-hover">
              <thead><tr><th>Día</th><th class="text-end">Líneas</th><th class="text-end">Total</th></tr></thead>
              <tbody>
                <tr v-for="row in posReport.by_day" :key="row.day">
                  <td>{{ row.day }}</td>
                  <td class="text-end">{{ row.lines_count }}</td>
                  <td class="text-end">${{ Number(row.total).toFixed(2) }}</td>
                </tr>
                <tr v-if="!posReport.by_day?.length"><td colspan="3" class="text-muted">Sin ventas en el periodo</td></tr>
              </tbody>
            </table>
          </div>
          <div class="col-md-6 mb-3">
            <h6>Ventas por outlet</h6>
            <table class="table table-sm table-hover">
              <thead><tr><th>Outlet</th><th class="text-end">Líneas</th><th class="text-end">Total</th></tr></thead>
              <tbody>
                <tr v-for="row in posReport.by_outlet" :key="row.outlet_id">
                  <td>{{ row.outlet_name }}</td>
                  <td class="text-end">{{ row.lines_count }}</td>
                  <td class="text-end">${{ Number(row.total).toFixed(2) }}</td>
                </tr>
                <tr v-if="!posReport.by_outlet?.length"><td colspan="3" class="text-muted">Sin datos</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <h6 class="mt-2">Productos más vendidos</h6>
        <table class="table table-sm table-hover">
          <thead><tr><th>Producto</th><th>Outlet</th><th class="text-end">Unidades</th><th class="text-end">Total</th></tr></thead>
          <tbody>
            <tr v-for="row in posReport.top_products" :key="row.product_id">
              <td>{{ row.product_name }}</td>
              <td>{{ row.outlet_name }}</td>
              <td class="text-end">{{ row.units }}</td>
              <td class="text-end">${{ Number(row.total).toFixed(2) }}</td>
            </tr>
            <tr v-if="!posReport.top_products?.length"><td colspan="4" class="text-muted">Sin productos</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import HotelRepository from '@/repositories/HotelRepository';

const tab = ref('general');
const from = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10));
const to = ref(new Date().toISOString().slice(0, 10));
const today = ref(new Date().toISOString().slice(0, 10));
const revenue = ref({ by_day: [], by_room_type: [] });
const arrivals = ref([]);
const departures = ref([]);

const posFrom = ref(from.value);
const posTo = ref(to.value);
const posOutletId = ref(null);
const posOutlets = ref([]);
const posReport = ref({ summary: null, by_day: [], by_outlet: [], top_products: [] });
const posLoading = ref(false);
const exporting = ref(false);

async function loadGeneral() {
  const [rev, ad] = await Promise.all([
    HotelRepository.reportRevenue({ from: from.value, to: to.value }),
    HotelRepository.reportArrivals({ date: today.value }),
  ]);
  revenue.value = rev.data || { by_day: [], by_room_type: [] };
  arrivals.value = ad.data?.arrivals || [];
  departures.value = ad.data?.departures || [];
}

async function loadPos() {
  posLoading.value = true;
  try {
    const res = await HotelRepository.reportPosSales({
      from: posFrom.value,
      to: posTo.value,
      outlet_id: posOutletId.value || undefined,
    });
    const data = res.data || {};
    posReport.value = data;
    posOutlets.value = data.outlets || posOutlets.value;
  } catch (e) {
    console.error(e);
  } finally {
    posLoading.value = false;
  }
}

async function exportRevenue() {
  exporting.value = true;
  try {
    await HotelRepository.downloadReportCsv(
      'revenue/export',
      { from: from.value, to: to.value },
      `ingresos_${from.value}_${to.value}.csv`
    );
  } catch (e) {
    alert('No se pudo exportar el reporte.');
  } finally {
    exporting.value = false;
  }
}

async function exportPos() {
  exporting.value = true;
  try {
    await HotelRepository.downloadReportCsv(
      'pos-sales/export',
      { from: posFrom.value, to: posTo.value, outlet_id: posOutletId.value || undefined },
      `pos-ventas_${posFrom.value}_${posTo.value}.csv`
    );
  } catch (e) {
    alert('No se pudo exportar el reporte POS.');
  } finally {
    exporting.value = false;
  }
}

onMounted(loadGeneral);
</script>
