<template>
  <div class="layout-px-spacing">
    <div class="panel hotel-panel p-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
          <h4 class="mb-0">POS — Consumos a habitación</h4>
          <p class="text-muted small mb-0">Restaurante, bar y tienda cargados al folio del huésped</p>
        </div>
        <router-link v-if="canCatalog" to="/hotel/pos/catalogo" class="btn btn-outline-secondary btn-sm">Administrar catálogo</router-link>
      </div>

      <div v-if="loading" class="text-center py-5"><div class="spinner-border text-primary"></div></div>

      <div v-else class="row g-3">
        <div class="col-lg-8">
          <ul class="nav nav-tabs mb-3">
            <li v-for="o in outlets" :key="o.id" class="nav-item">
              <button type="button" class="nav-link" :class="{ active: outletId === o.id }" @click="outletId = o.id">{{ o.name }}</button>
            </li>
          </ul>
          <div v-if="currentOutlet" class="row g-2">
            <div v-for="cat in currentOutlet.categories" :key="cat.id" class="col-12">
              <h6 class="text-muted small text-uppercase mb-2">{{ cat.name }}</h6>
              <div class="row g-2">
                <div v-for="p in cat.products" :key="p.id" class="col-6 col-md-4 col-xl-3">
                  <button type="button" class="btn btn-light w-100 text-start pos-product-btn" @click="addToCart(p)">
                    <span class="d-block fw-semibold">{{ p.name }}</span>
                    <span class="text-primary">${{ Number(p.price).toFixed(2) }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <label class="form-label">Cargar a habitación (folio abierto)</label>
              <select v-model="folioId" class="form-select mb-2">
                <option :value="null">Seleccione habitación…</option>
                <option v-for="r in rooms" :key="r.folio_id" :value="r.folio_id">
                  Hab. {{ r.room_number }} — {{ r.huesped }} ({{ r.folio_number }})
                </option>
              </select>
              <p v-if="selectedRoom" class="small text-muted mb-3">
                Saldo actual: <strong>${{ Number(selectedRoom.balance).toFixed(2) }}</strong>
              </p>
              <p v-if="!rooms.length" class="alert alert-warning small py-2">No hay huéspedes con check-in y folio abierto.</p>

              <h6 class="mb-2">Carrito</h6>
              <ul v-if="cart.length" class="list-group list-group-flush mb-3">
                <li v-for="(item, idx) in cart" :key="idx" class="list-group-item px-0 d-flex justify-content-between align-items-center">
                  <div>
                    {{ item.name }}
                    <div class="btn-group btn-group-sm ms-1">
                      <button type="button" class="btn btn-outline-secondary" @click="item.qty = Math.max(1, item.qty - 1)">−</button>
                      <span class="btn btn-outline-secondary disabled">{{ item.qty }}</span>
                      <button type="button" class="btn btn-outline-secondary" @click="item.qty++">+</button>
                    </div>
                  </div>
                  <div class="text-end">
                    <div>${{ (item.price * item.qty).toFixed(2) }}</div>
                    <button type="button" class="btn btn-link btn-sm text-danger p-0" @click="cart.splice(idx, 1)">Quitar</button>
                  </div>
                </li>
              </ul>
              <p v-else class="text-muted small">Sin productos en el carrito.</p>

              <div class="d-flex justify-content-between fw-bold mb-3">
                <span>Total</span>
                <span>${{ cartTotal.toFixed(2) }}</span>
              </div>

              <button
                type="button"
                class="btn btn-primary w-100"
                :disabled="!folioId || !cart.length || submitting"
                @click="submit"
              >
                {{ submitting ? 'Cargando…' : 'Cargar al folio' }}
              </button>
              <button type="button" class="btn btn-outline-secondary w-100 mt-2" :disabled="!cart.length" @click="cart = []">
                Vaciar carrito
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import HotelRepository from '@/repositories/HotelRepository';
import { usePermissions } from '@/composables/use-permissions';

const { hasPermission } = usePermissions();
const canCatalog = computed(() => hasPermission('pos.catalogo'));

const loading = ref(true);
const submitting = ref(false);
const outlets = ref([]);
const rooms = ref([]);
const outletId = ref(null);
const folioId = ref(null);
const cart = ref([]);

const currentOutlet = computed(() => outlets.value.find((o) => o.id === outletId.value));
const selectedRoom = computed(() => rooms.value.find((r) => r.folio_id === folioId.value));
const cartTotal = computed(() => cart.value.reduce((s, i) => s + i.price * i.qty, 0));

function addToCart(product) {
  const existing = cart.value.find((c) => c.product_id === product.id);
  if (existing) {
    existing.qty += 1;
    return;
  }
  cart.value.push({
    product_id: product.id,
    name: product.name,
    price: Number(product.price),
    qty: 1,
  });
}

async function load() {
  loading.value = true;
  try {
    const [cat, rms] = await Promise.all([HotelRepository.posCatalog(), HotelRepository.posRoomsInHouse()]);
    outlets.value = cat.data || [];
    rooms.value = rms.data || [];
    outletId.value = outlets.value[0]?.id ?? null;
    if (rooms.value.length && !folioId.value) {
      folioId.value = rooms.value[0].folio_id;
    }
  } finally {
    loading.value = false;
  }
}

async function submit() {
  submitting.value = true;
  try {
    await HotelRepository.posChargeToFolio({
      folio_id: folioId.value,
      lines: cart.value.map((c) => ({ product_id: c.product_id, quantity: c.qty })),
    });
    cart.value = [];
    const rms = await HotelRepository.posRoomsInHouse();
    rooms.value = rms.data || [];
    alert('Consumos cargados al folio correctamente.');
  } catch (e) {
    const msg = e.response?.data?.message || e.response?.data?.errors?.folio?.[0] || 'No se pudo cargar al folio.';
    alert(msg);
  } finally {
    submitting.value = false;
  }
}

watch(outletId, () => {
  cart.value = [];
});

onMounted(load);
</script>

<style scoped>
.pos-product-btn {
  border: 1px solid rgba(30, 95, 138, 0.15);
  min-height: 64px;
}
.pos-product-btn:hover {
  border-color: #1e5f8a;
  background: #e8f1f8;
}
</style>
