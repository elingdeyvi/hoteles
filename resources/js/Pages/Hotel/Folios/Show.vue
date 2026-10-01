<script setup>
import CompartirTicketModal from '@/Components/CompartirTicketModal.vue';
import TicketPrintModal from '@/Components/TicketPrintModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { prepararFolio } from '@/utils/compartirTicket';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ folio: Object });
const page = usePage();
const cajaAbierta = computed(() => page.props.cajaAbierta);
const compartirRef = ref(null);
const ticketRef = ref(null);
const compartirError = ref('');
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

const compartir = async () => {
    compartirError.value = '';
    try {
        const prep = await prepararFolio(props.folio, page.props.empresa || {});
        compartirRef.value?.abrir(prep);
    } catch (error) {
        compartirError.value = error?.message || 'No se pudo preparar el ticket.';
    }
};
const charge = useForm({ concept: '', amount: '', charge_type: 'extra' });
const payment = useForm({ payment_method: 'efectivo', recibido: '', reference: '' });
const saldo = computed(() => Number(props.folio.balance || 0));
const cambio = computed(() => {
    if (payment.payment_method !== 'efectivo') return 0;
    return Math.max(0, Number(payment.recibido || 0) - saldo.value);
});
const efectivoCubre = computed(() => payment.payment_method !== 'efectivo' || Number(payment.recibido || 0) + 0.001 >= saldo.value);
const tipoCargo = { habitacion: 'Habitación', extra: 'Extra', pos: 'Producto' };
const quitarCargo = (item) => {
    if (item.charge_type === 'habitacion') return;
    const que = item.charge_type === 'pos' ? 'el producto' : 'el cargo extra';
    if (window.confirm(`¿Quitar ${que} "${item.concept}"?`)) {
        router.delete(route('folios.charges.destroy', { folio: props.folio.id, charge: item.id }));
    }
};
</script>

<template>
    <Head :title="folio.folio_number" />
    <AuthenticatedLayout>
        <template #header>Folio {{ folio.folio_number }}</template>
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between">
                        <span>{{ folio.stay?.reservation?.huesped?.nombre }} · Hab. {{ folio.stay?.room?.number }}</span>
                        <span>Saldo {{ money(folio.balance) }} · {{ folio.status }}</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>Concepto</th><th>Tipo</th><th>Importe</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="item in folio.charges" :key="item.id">
                                    <td>{{ item.concept }}</td>
                                    <td>{{ tipoCargo[item.charge_type] || item.charge_type }}</td>
                                    <td>{{ money(item.amount * item.quantity) }}</td>
                                    <td class="text-end">
                                        <button
                                            v-if="folio.status === 'abierto' && item.charge_type !== 'habitacion'"
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            @click="quitarCargo(item)"
                                        >Quitar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">Pagos</div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>Método</th><th>Referencia</th><th>Importe</th></tr></thead>
                            <tbody>
                                <tr v-for="item in folio.payments" :key="item.id">
                                    <td>{{ item.payment_method }}</td>
                                    <td>{{ item.reference }}<span v-if="Number(item.cambio) > 0"> · cambio {{ money(item.cambio) }}</span></td>
                                    <td>{{ money(item.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-4" v-if="folio.status === 'abierto'">
                <form class="card mb-3" @submit.prevent="charge.post(route('folios.charges', folio.id))">
                    <div class="card-header">Cargo extra</div>
                    <div class="card-body">
                        <input v-model="charge.concept" class="form-control mb-2" placeholder="Concepto" required />
                        <input v-model="charge.amount" type="number" step="0.01" min="0.01" class="form-control mb-2" placeholder="Importe" required />
                        <button class="btn btn-primary w-100">Agregar cargo</button>
                    </div>
                </form>
                <form v-if="saldo > 0" class="card mb-3" @submit.prevent="payment.post(route('folios.payments', folio.id))">
                    <div class="card-header">Pago</div>
                    <div class="card-body">
                        <div v-if="!cajaAbierta" class="alert alert-warning">
                            Debe aperturar la caja antes de cobrar.
                            <Link :href="route('caja.index')" class="alert-link">Abrir caja</Link>
                        </div>
                        <p class="mb-2">Saldo a cobrar <strong>{{ money(saldo) }}</strong></p>
                        <select v-model="payment.payment_method" class="form-select mb-2" :disabled="!cajaAbierta">
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                        <template v-if="payment.payment_method === 'efectivo'">
                            <label class="form-label">Efectivo recibido</label>
                            <input v-model="payment.recibido" type="number" step="0.01" min="0" class="form-control mb-2" required :disabled="!cajaAbierta" />
                            <div class="border rounded p-2 mb-2 text-center">
                                <div class="text-muted small">Cambio</div>
                                <div class="fs-3 fw-bold text-success">{{ money(cambio) }}</div>
                            </div>
                        </template>
                        <input v-model="payment.reference" class="form-control mb-2" placeholder="Referencia" :disabled="!cajaAbierta" />
                        <div v-if="payment.errors.caja || payment.errors.recibido" class="text-danger small mb-2">{{ payment.errors.caja || payment.errors.recibido }}</div>
                        <button class="btn btn-success w-100" :disabled="!cajaAbierta || !efectivoCubre">Cobrar y cerrar folio</button>
                    </div>
                </form>
                <form v-else class="card mb-3" @submit.prevent="router.post(route('folios.close', folio.id))">
                    <div class="card-body">
                        <p class="mb-2">El folio no tiene saldo.</p>
                        <button class="btn btn-dark w-100">Cerrar folio</button>
                    </div>
                </form>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-dark" @click="ticketRef?.solicitar(route('folios.imprimir', folio.id), 'Ticket de cuenta')">Imprimir</button>
                <button type="button" class="btn btn-success" @click="compartir">Compartir ticket</button>
                <a :href="route('folios.invoice', folio.id)" class="btn btn-outline-secondary">Descargar factura PDF</a>
                <div v-if="compartirError" class="alert alert-warning mb-0">{{ compartirError }}</div>
            </div>
        <CompartirTicketModal ref="compartirRef" />
        <TicketPrintModal ref="ticketRef" />
        </div>
    </AuthenticatedLayout>
</template>
