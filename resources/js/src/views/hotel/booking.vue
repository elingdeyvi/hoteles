<template>
  <div class="booking-public">
    <div class="booking-hero text-center mb-4">
      <img v-if="hotel.logo_url" :src="hotel.logo_url" alt="" class="booking-logo mb-2" />
      <h1 class="mb-1">{{ hotel.nombre || 'Reservar en línea' }}</h1>
      <p class="text-muted mb-0">Consulte disponibilidad y solicite su estadía</p>
    </div>

    <ul class="nav nav-pills booking-tabs justify-content-center mb-4">
      <li class="nav-item">
        <button type="button" class="nav-link" :class="{ active: tab === 'book' }" @click="tab = 'book'">Nueva reserva</button>
      </li>
      <li class="nav-item">
        <button type="button" class="nav-link" :class="{ active: tab === 'lookup' }" @click="tab = 'lookup'">Consultar estado</button>
      </li>
    </ul>

    <div v-if="tab === 'book'">
      <div class="card booking-card mb-3">
        <div class="card-body">
          <h5 class="card-title">1. Fechas</h5>
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label small">Entrada</label>
              <input v-model="dates.check_in" type="date" class="form-control" :min="minDate" @change="searchAvailability" />
            </div>
            <div class="col-md-4">
              <label class="form-label small">Salida</label>
              <input v-model="dates.check_out" type="date" class="form-control" :min="dates.check_in || minDate" @change="searchAvailability" />
            </div>
            <div class="col-md-4">
              <label class="form-label small">Huéspedes</label>
              <input v-model.number="dates.guests_count" type="number" min="1" :max="bookingRules.max_guests || 8" class="form-control" />
            </div>
          </div>
        </div>
      </div>

      <div v-if="loadingAvail" class="text-center text-muted py-3">Buscando disponibilidad…</div>

      <div v-else-if="availability.length" class="card booking-card mb-3">
        <div class="card-body">
          <h5 class="card-title">2. Tipo de habitación</h5>
          <div class="row g-3">
            <div v-for="item in availability" :key="item.room_type.id" class="col-md-6">
              <div
                class="room-type-option p-3"
                :class="{ selected: selectedTypeId === item.room_type.id, disabled: item.available_rooms < 1 }"
                @click="item.available_rooms > 0 && selectType(item)"
              >
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6 class="mb-1">{{ item.room_type.name }}</h6>
                    <p class="small text-muted mb-1">{{ item.room_type.description || 'Habitación confortable' }}</p>
                    <span class="badge bg-light text-dark">Cap. {{ item.room_type.capacity }} pers.</span>
                  </div>
                  <div class="text-end">
                    <div class="fw-bold text-primary">${{ Number(item.nightly_rate).toFixed(0) }}<small class="text-muted">/noche</small></div>
                    <div class="small">Total est.: <strong>${{ Number(item.estimated_total).toFixed(2) }}</strong></div>
                    <div v-if="paymentsEnabled" class="small text-muted">Anticipo ({{ depositPercent }}%): ${{ depositPreview(item.estimated_total) }}</div>
                  </div>
                </div>
                <div class="mt-2">
                  <span v-if="item.available_rooms > 0" class="badge bg-success-subtle text-success">Disponible ({{ item.available_rooms }})</span>
                  <span v-else class="badge bg-danger-subtle text-danger">Sin disponibilidad</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="selectedTypeId && !success" class="card booking-card mb-3">
        <div class="card-body">
          <h5 class="card-title">3. Sus datos</h5>
          <div class="row g-2">
            <div class="col-md-6">
              <input v-model="guest.nombre" class="form-control" placeholder="Nombre completo *" />
            </div>
            <div class="col-md-6">
              <input v-model="guest.email" type="email" class="form-control" placeholder="Correo electrónico *" />
            </div>
            <div class="col-md-6">
              <input v-model="guest.telefono" class="form-control" placeholder="Teléfono" />
            </div>
            <div class="col-md-6">
              <input v-model="guest.documento" class="form-control" placeholder="Documento de identidad" />
            </div>
            <div class="col-12">
              <textarea v-model="notes" class="form-control" rows="2" placeholder="Comentarios (opcional)"></textarea>
            </div>
          </div>
          <p v-if="error" class="text-danger small mt-2 mb-0">{{ error }}</p>
          <button class="btn btn-primary mt-3 w-100" :disabled="submitting" @click="submit">
            {{ submitting ? 'Enviando…' : 'Solicitar reserva' }}
          </button>
        </div>
      </div>

      <div v-if="success" class="card booking-card booking-success text-center">
        <div class="card-body py-4">
          <div class="text-success mb-2 fs-1">✓</div>
          <h4>{{ paymentPending ? 'Reserva registrada' : '¡Solicitud registrada!' }}</h4>
          <p class="mb-1">Folio: <strong>{{ success.folio }}</strong></p>
          <p v-if="success.online_reference" class="text-muted small">Ref. web: {{ success.online_reference }}</p>
          <p v-if="paymentPending" class="mb-2">
            Anticipo requerido: <strong>${{ Number(pendingPayment.amount).toFixed(2) }} {{ pendingPayment.currency?.toUpperCase() }}</strong>
          </p>
          <p class="text-muted">{{ confirmationNote }}</p>
          <div v-if="paymentPending" class="d-grid gap-2 mt-3">
            <button v-if="isDemoPayment" class="btn btn-success" :disabled="paying" @click="payDemo">
              {{ paying ? 'Procesando…' : 'Pagar anticipo (modo demo)' }}
            </button>
            <button v-else class="btn btn-success" :disabled="paying" @click="payStripe">
              {{ paying ? 'Redirigiendo…' : 'Pagar anticipo con tarjeta' }}
            </button>
          </div>
          <router-link to="/auth/login" class="btn btn-outline-primary btn-sm mt-3">Acceso personal del hotel</router-link>
        </div>
      </div>
    </div>

    <div v-else class="card booking-card">
      <div class="card-body">
        <h5 class="card-title">Estado de su reserva</h5>
        <div class="row g-2">
          <div class="col-md-6">
            <input v-model="lookup.folio" class="form-control" placeholder="Folio (ej. RES-…)" />
          </div>
          <div class="col-md-6">
            <input v-model="lookup.email" type="email" class="form-control" placeholder="Correo usado al reservar" />
          </div>
        </div>
        <p v-if="lookupError" class="text-danger small mt-2">{{ lookupError }}</p>
        <button class="btn btn-primary mt-3" :disabled="lookupLoading" @click="doLookup">
          {{ lookupLoading ? 'Consultando…' : 'Consultar' }}
        </button>
        <div v-if="lookupResult" class="alert alert-light border mt-3 mb-0">
          <p class="mb-1"><strong>{{ lookupResult.guest_name }}</strong> — {{ lookupResult.room_type?.name }}</p>
          <p class="mb-1 small">{{ lookupResult.check_in }} → {{ lookupResult.check_out }}</p>
          <span class="badge reservation-status" :class="lookupResult.status">{{ lookupResult.status }}</span>
          <span v-if="lookupResult.payment_status && lookupResult.payment_status !== 'not_required'" class="badge bg-warning-subtle text-dark ms-1">{{ lookupResult.payment_status }}</span>
          <span class="ms-2">Total est.: ${{ Number(lookupResult.estimated_total).toFixed(2) }}</span>
          <p v-if="lookupResult.deposit_amount" class="small mb-0 mt-1">Anticipo: ${{ Number(lookupResult.deposit_amount).toFixed(2) }}</p>
        </div>
      </div>
    </div>

    <p class="text-center mt-4 small text-muted">
      <router-link to="/auth/login">Iniciar sesión</router-link> (personal del hotel)
    </p>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { createBookingRepository } from '@/repositories/BookingRepository';

const route = useRoute();
const propertySlug = computed(() => route.params.propertySlug || 'costa-azul');
let BookingRepository = createBookingRepository(propertySlug.value);

watch(propertySlug, (slug) => {
  BookingRepository = createBookingRepository(slug);
  loadConfig();
});
const tab = ref('book');
const hotel = ref({});
const bookingRules = ref({ max_guests: 8, payments: {} });
const confirmationNote = ref('');
const availability = ref([]);
const loadingAvail = ref(false);
const selectedTypeId = ref(null);
const selectedTotal = ref(0);
const submitting = ref(false);
const paying = ref(false);
const error = ref('');
const success = ref(null);
const pendingPayment = ref(null);
const notes = ref('');

const dates = ref({ check_in: '', check_out: '', guests_count: 2 });
const guest = ref({ nombre: '', email: '', telefono: '', documento: '' });

const lookup = ref({ folio: '', email: '' });
const lookupResult = ref(null);
const lookupError = ref('');
const lookupLoading = ref(false);

const minDate = computed(() => {
  const d = new Date();
  d.setDate(d.getDate() + (bookingRules.value.min_advance_days || 0));
  return d.toISOString().slice(0, 10);
});

const paymentsEnabled = computed(() => !!bookingRules.value.payments?.enabled);
const depositPercent = computed(() => bookingRules.value.payments?.deposit_percent || 30);
const isDemoPayment = computed(() => bookingRules.value.payments?.provider === 'demo');
const paymentPending = computed(() => !!pendingPayment.value?.required);

function depositPreview(total) {
  const pct = depositPercent.value;
  return Math.max(1, (Number(total) * pct) / 100).toFixed(2);
}

async function loadConfig() {
  const res = await BookingRepository.config();
  hotel.value = res.data?.hotel || {};
  bookingRules.value = res.data?.booking || {};
  confirmationNote.value = res.data?.booking?.confirmation_note || '';
}

async function searchAvailability() {
  if (!dates.value.check_in || !dates.value.check_out) return;
  loadingAvail.value = true;
  error.value = '';
  selectedTypeId.value = null;
  try {
    const res = await BookingRepository.availability({
      check_in: dates.value.check_in,
      check_out: dates.value.check_out,
    });
    availability.value = res.data || [];
  } catch (e) {
    availability.value = [];
    error.value = e.response?.data?.message || 'No se pudo consultar disponibilidad.';
  } finally {
    loadingAvail.value = false;
  }
}

function selectType(item) {
  selectedTypeId.value = item.room_type.id;
  selectedTotal.value = item.estimated_total;
}

async function submit() {
  error.value = '';
  if (!guest.value.nombre || !guest.value.email) {
    error.value = 'Complete nombre y correo.';
    return;
  }
  submitting.value = true;
  try {
    const res = await BookingRepository.createReservation({
      room_type_id: selectedTypeId.value,
      check_in: dates.value.check_in,
      check_out: dates.value.check_out,
      guests_count: dates.value.guests_count,
      notes: notes.value || null,
      guest: guest.value,
    });
    success.value = res.data;
    confirmationNote.value = res.message || confirmationNote.value;
    pendingPayment.value = res.payment || null;
  } catch (e) {
    const errs = e.response?.data?.errors;
    error.value = errs ? Object.values(errs).flat()[0] : (e.response?.data?.message || 'No se pudo crear la reserva.');
  } finally {
    submitting.value = false;
  }
}

async function payDemo() {
  if (!success.value?.folio || !guest.value.email) return;
  paying.value = true;
  error.value = '';
  try {
    const res = await BookingRepository.demoConfirm({
      folio: success.value.folio,
      email: guest.value.email,
    });
    success.value = { ...success.value, status: res.data?.status || 'confirmada' };
    pendingPayment.value = null;
    confirmationNote.value = res.message || 'Anticipo pagado. Reserva confirmada.';
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo registrar el pago.';
  } finally {
    paying.value = false;
  }
}

async function payStripe() {
  if (!success.value?.folio || !guest.value.email) return;
  paying.value = true;
  error.value = '';
  try {
    const res = await BookingRepository.checkout({
      folio: success.value.folio,
      email: guest.value.email,
    });
    if (res.data?.checkout_url) {
      window.location.href = res.data.checkout_url;
      return;
    }
    error.value = 'No se recibió URL de pago.';
  } catch (e) {
    error.value = e.response?.data?.message || 'No se pudo iniciar el pago.';
  } finally {
    paying.value = false;
  }
}

function handleReturnQuery() {
  const payment = route.query.payment;
  const folio = route.query.folio;
  if (!folio) return;

  lookup.value.folio = String(folio);
  tab.value = 'lookup';

  if (payment === 'success') {
    confirmationNote.value = 'Pago recibido. Consulte el estado de su reserva abajo.';
  } else if (payment === 'cancel') {
    lookupError.value = 'El pago fue cancelado. Puede consultar su reserva e intentar de nuevo.';
  }
}

async function doLookup() {
  lookupError.value = '';
  lookupResult.value = null;
  lookupLoading.value = true;
  try {
    const res = await BookingRepository.lookup({
      folio: lookup.value.folio.trim(),
      email: lookup.value.email.trim(),
    });
    lookupResult.value = res.data;
  } catch (e) {
    lookupError.value = e.response?.data?.message || 'Reserva no encontrada.';
  } finally {
    lookupLoading.value = false;
  }
}

onMounted(async () => {
  await loadConfig();
  handleReturnQuery();
});
</script>
