<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ outlets: Array, roomsInHouse: Array });
const outletId = ref(props.outlets[0]?.id || null);
const cart = ref([]);
const form = useForm({ folio_id: props.roomsInHouse[0]?.folio_id || '', lines: [] });
const page = usePage();
const cajaAbierta = computed(() => page.props.cajaAbierta);
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

const outlet = computed(() => props.outlets.find((item) => item.id === outletId.value) || props.outlets[0]);
const total = computed(() => cart.value.reduce((sum, line) => sum + line.price * line.quantity, 0));

const add = (product) => {
    const existing = cart.value.find((line) => line.product_id === product.id);
    if (existing) existing.quantity += 1;
    else cart.value.push({ product_id: product.id, name: product.name, price: Number(product.price), quantity: 1 });
};

const submit = () => {
    form.lines = cart.value.map((line) => ({ product_id: line.product_id, quantity: line.quantity }));
    form.post(route('pos.charge'), { onSuccess: () => { cart.value = []; } });
};
</script>

<template>
    <Head title="POS" />
    <AuthenticatedLayout>
        <template #header>POS consumos</template>
        <div v-if="!cajaAbierta" class="alert alert-warning">
            Debe aperturar la caja antes de cargar consumos.
            <Link :href="route('caja.index')" class="alert-link">Abrir caja</Link>
        </div>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="btn-group mb-3">
                    <button v-for="item in outlets" :key="item.id" class="btn" :class="outlet?.id === item.id ? 'btn-primary' : 'btn-outline-primary'" @click="outletId = item.id">{{ item.name }}</button>
                </div>
                <div v-for="category in outlet?.categories || []" :key="category.id" class="mb-3">
                    <h6>{{ category.name }}</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <button v-for="product in category.products" :key="product.id" class="btn btn-outline-dark" @click="add(product)">
                            {{ product.name }}<br /><small>{{ money(product.price) }}</small>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <form class="card" @submit.prevent="submit">
                    <div class="card-header">Cuenta de habitación</div>
                    <div class="card-body">
                        <select v-model="form.folio_id" class="form-select mb-3" required>
                            <option value="">Seleccione folio</option>
                            <option v-for="room in roomsInHouse" :key="room.folio_id" :value="room.folio_id">
                                Hab. {{ room.room_number }} · {{ room.huesped }} · {{ room.folio_number }}
                            </option>
                        </select>
                        <div v-if="!roomsInHouse.length" class="text-muted mb-2">No hay huéspedes en casa con folio abierto.</div>
                        <ul class="list-group mb-3">
                            <li v-for="line in cart" :key="line.product_id" class="list-group-item d-flex justify-content-between">
                                <span>{{ line.name }} × {{ line.quantity }}</span>
                                <span>{{ money(line.price * line.quantity) }}</span>
                            </li>
                        </ul>
                        <div class="d-flex justify-content-between mb-3"><strong>Total</strong><strong>{{ money(total) }}</strong></div>
                        <button class="btn btn-success w-100" :disabled="!cart.length || form.processing || !cajaAbierta">Cargar al folio</button>
                        <div v-if="form.errors.caja" class="text-danger small mt-2">{{ form.errors.caja }}</div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
