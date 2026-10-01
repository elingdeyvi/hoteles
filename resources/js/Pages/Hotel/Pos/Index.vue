<script setup>
import TicketPrintModal from '@/Components/TicketPrintModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ outlets: Array, roomsInHouse: Array });
const outletId = ref(props.outlets[0]?.id || null);
const cart = ref([]);
const destino = ref('publico');
const ticketRef = ref(null);
const form = useForm({
    destino: 'publico',
    folio_id: props.roomsInHouse[0]?.folio_id || '',
    payment_method: 'efectivo',
    recibido: '',
    lines: [],
});
const page = usePage();
const cajaAbierta = computed(() => page.props.cajaAbierta);
const venta = computed(() => page.props.flash?.venta_publica || null);
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

const outlet = computed(() => props.outlets.find((item) => item.id === outletId.value) || props.outlets[0]);
const total = computed(() => cart.value.reduce((sum, line) => sum + line.price * line.quantity, 0));
const cambio = computed(() => {
    if (form.payment_method !== 'efectivo') return 0;
    return Math.max(0, Number(form.recibido || 0) - total.value);
});
const efectivoCubre = computed(() => form.payment_method !== 'efectivo' || Number(form.recibido || 0) + 0.001 >= total.value);

const cargoDe = (product) => {
    const base = Number(product.price || 0);
    if (!product.aplicar_iva) return base;
    return Math.round(base * (1 + Number(product.iva_porcentaje || 0) / 100) * 100) / 100;
};
const sinStock = (product) => product.controla_inventario && Number(product.stock_actual) < 1;
const add = (product) => {
    if (sinStock(product)) return;
    const existing = cart.value.find((line) => line.product_id === product.id);
    const tope = product.controla_inventario ? Number(product.stock_actual) : 99;
    if (existing) existing.quantity = Math.min(tope, existing.quantity + 1);
    else cart.value.push({ product_id: product.id, name: product.name, price: cargoDe(product), quantity: 1 });
};

const submit = () => {
    form.destino = destino.value;
    form.lines = cart.value.map((line) => ({ product_id: line.product_id, quantity: line.quantity }));
    form.post(route('pos.vender'), { onSuccess: () => { cart.value = []; form.recibido = ''; } });
};
</script>

<template>
    <Head title="Venta al público" />
    <AuthenticatedLayout>
        <template #header>Venta al público</template>
        <div v-if="!cajaAbierta" class="alert alert-warning">
            Debe aperturar la caja antes de cobrar.
            <Link :href="route('caja.index')" class="alert-link">Abrir caja</Link>
        </div>
        <div v-if="venta" class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>
                {{ venta.numero }} cobrada. Total {{ money(venta.total) }}.
                <strong v-if="Number(venta.cambio) > 0">Cambio {{ money(venta.cambio) }}.</strong>
            </span>
            <button type="button" class="btn btn-dark btn-sm" @click="ticketRef?.solicitar(route('pos.ventas.imprimir', venta.id), 'Ticket de venta')">Imprimir ticket</button>
        </div>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="btn-group mb-3">
                    <button v-for="item in outlets" :key="item.id" class="btn" :class="outlet?.id === item.id ? 'btn-primary' : 'btn-outline-primary'" @click="outletId = item.id">{{ item.name }}</button>
                </div>
                <div v-for="category in outlet?.categories || []" :key="category.id" class="mb-3">
                    <h6>{{ category.name }}</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button v-for="product in category.products" :key="product.id" class="btn btn-outline-dark" :disabled="sinStock(product)" @click="add(product)">
                            {{ product.name }}<br />
                            <small>{{ money(cargoDe(product)) }}<span v-if="product.controla_inventario"> · {{ sinStock(product) ? 'Agotado' : `Exist. ${Number(product.stock_actual)}` }}</span></small>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <form class="card" @submit.prevent="submit">
                    <div class="card-header">Cobro</div>
                    <div class="card-body">
                        <div class="btn-group w-100 mb-3">
                            <button type="button" class="btn" :class="destino === 'publico' ? 'btn-success' : 'btn-outline-success'" @click="destino = 'publico'">Público</button>
                            <button type="button" class="btn" :class="destino === 'habitacion' ? 'btn-primary' : 'btn-outline-primary'" @click="destino = 'habitacion'">Habitación</button>
                        </div>
                        <select v-if="destino === 'habitacion'" v-model="form.folio_id" class="form-select mb-3" required>
                            <option value="">Seleccione folio</option>
                            <option v-for="room in roomsInHouse" :key="room.folio_id" :value="room.folio_id">
                                Hab. {{ room.room_number }} · {{ room.huesped }} · {{ room.folio_number }}
                            </option>
                        </select>
                        <div v-if="destino === 'habitacion' && !roomsInHouse.length" class="text-muted mb-2">No hay huéspedes en casa con folio abierto.</div>
                        <ul class="list-group mb-3">
                            <li v-for="line in cart" :key="line.product_id" class="list-group-item d-flex justify-content-between">
                                <span>{{ line.name }} × {{ line.quantity }}</span>
                                <span>{{ money(line.price * line.quantity) }}</span>
                            </li>
                        </ul>
                        <div class="d-flex justify-content-between mb-3"><strong>Total</strong><strong>{{ money(total) }}</strong></div>
                        <template v-if="destino === 'publico'">
                            <select v-model="form.payment_method" class="form-select mb-2">
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                            <template v-if="form.payment_method === 'efectivo'">
                                <label class="form-label">Efectivo recibido</label>
                                <input v-model="form.recibido" type="number" step="0.01" min="0" class="form-control mb-2" required />
                                <div class="border rounded p-2 mb-3 text-center">
                                    <div class="text-muted small">Cambio</div>
                                    <div class="fs-3 fw-bold text-success">{{ money(cambio) }}</div>
                                </div>
                            </template>
                        </template>
                        <button class="btn btn-success w-100" :disabled="!cart.length || form.processing || !cajaAbierta || (destino === 'publico' && !efectivoCubre)">
                            {{ destino === 'publico' ? 'Cobrar' : 'Cargar a la habitación' }}
                        </button>
                        <div v-if="form.errors.caja || form.errors.stock || form.errors.lines || form.errors.recibido" class="text-danger small mt-2">
                            {{ form.errors.caja || form.errors.stock || form.errors.lines || form.errors.recibido }}
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <TicketPrintModal ref="ticketRef" />
    </AuthenticatedLayout>
</template>
