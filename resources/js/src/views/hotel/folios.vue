<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <h4 class="mb-3">Folios y pagos</h4>
      <div class="row">
        <div class="col-md-5">
          <table class="table table-sm table-hover">
            <thead><tr><th>Folio</th><th>Huésped</th><th>Saldo</th><th></th></tr></thead>
            <tbody>
              <tr v-for="f in folios" :key="f.id" @click="select(f)" class="cursor-pointer">
                <td>{{ f.folio_number }}</td>
                <td>{{ f.stay?.reservation?.huesped?.nombre }}</td>
                <td>${{ Number(f.balance).toFixed(2) }}</td>
                <td><span class="badge" :class="f.status === 'abierto' ? 'folio-status-abierto' : 'folio-status-cerrado'">{{ f.status }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="active" class="col-md-7">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <h5 class="mb-0">{{ active.folio_number }}</h5>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-primary" :disabled="pdfLoading" @click="downloadPdf">
                {{ pdfLoading ? 'Generando…' : 'Descargar factura PDF' }}
              </button>
              <button
                v-if="active.status === 'abierto' && Number(active.balance) === 0"
                type="button"
                class="btn btn-primary"
                :disabled="closing"
                @click="closeFolio"
              >
                Cerrar folio
              </button>
            </div>
          </div>
          <p>Saldo: <strong>${{ Number(active.balance).toFixed(2) }}</strong></p>
          <h6>Cargos</h6>
          <ul class="list-group mb-3">
            <li v-for="c in active.charges" :key="c.id" class="list-group-item d-flex justify-content-between">
              <span>{{ c.concept }}</span><span>${{ Number(c.amount).toFixed(2) }}</span>
            </li>
          </ul>
          <h6 v-if="active.payments?.length">Pagos</h6>
          <ul v-if="active.payments?.length" class="list-group mb-3">
            <li v-for="p in active.payments" :key="p.id" class="list-group-item d-flex justify-content-between small">
              <span>{{ p.payment_method }} {{ p.reference ? `(${p.reference})` : '' }}</span>
              <span>${{ Number(p.amount).toFixed(2) }}</span>
            </li>
          </ul>
          <div v-if="active.status === 'abierto'" class="row g-2 mb-3">
            <div class="col-md-5"><input v-model="charge.concept" class="form-control form-control-sm" placeholder="Concepto" /></div>
            <div class="col-md-3"><input v-model.number="charge.amount" type="number" class="form-control form-control-sm" /></div>
            <div class="col-md-4"><button class="btn btn-sm btn-outline-primary" @click="addCharge">Agregar cargo</button></div>
          </div>
          <div v-if="active.status === 'abierto'" class="row g-2">
            <div class="col-md-3">
              <select v-model="payment.payment_method" class="form-select form-select-sm">
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="transferencia">Transferencia</option>
              </select>
            </div>
            <div class="col-md-3"><input v-model.number="payment.amount" type="number" class="form-control form-control-sm" /></div>
            <div class="col-md-4"><button class="btn btn-sm btn-success" @click="addPayment">Registrar pago</button></div>
          </div>
          <p v-if="active.status === 'abierto' && Number(active.balance) > 0" class="text-muted small mt-2 mb-0">
            Registra pagos hasta saldar el folio para poder cerrarlo o hacer check-out.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import HotelRepository from '@/repositories/HotelRepository';

const folios = ref([]);
const active = ref(null);
const charge = ref({ concept: '', amount: 0 });
const payment = ref({ payment_method: 'efectivo', amount: 0 });
const pdfLoading = ref(false);
const closing = ref(false);

async function load() {
  const res = await HotelRepository.folios({ per_page: 50 });
  folios.value = res.data?.data || [];
}

async function select(f) {
  const res = await HotelRepository.folio(f.id);
  active.value = res.data;
  payment.value.amount = Math.max(0, Number(active.value.balance));
}

async function addCharge() {
  await HotelRepository.addCharge(active.value.id, charge.value);
  charge.value = { concept: '', amount: 0 };
  await select({ id: active.value.id });
  await load();
}

async function addPayment() {
  await HotelRepository.addPayment(active.value.id, payment.value);
  await select({ id: active.value.id });
  await load();
}

async function closeFolio() {
  closing.value = true;
  try {
    await HotelRepository.closeFolio(active.value.id);
    await select({ id: active.value.id });
    await load();
    await downloadPdf();
  } finally {
    closing.value = false;
  }
}

async function downloadPdf() {
  pdfLoading.value = true;
  try {
    await HotelRepository.downloadInvoicePdf(active.value.id);
  } finally {
    pdfLoading.value = false;
  }
}

onMounted(load);
</script>
