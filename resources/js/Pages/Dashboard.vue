<script setup>
import HotelChart from '@/Components/HotelChart.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ summary: Object, series: Array, filters: Object });
const cajaAbierta = computed(() => usePage().props.cajaAbierta);

const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const day = (d) => (d ? String(d).slice(0, 10) : '');
const estadoHabitacion = {
    disponible: 'Disponible',
    limpia: 'Limpia',
    sucia: 'Sucia',
    mantenimiento: 'Mantenimiento',
    ocupada: 'Ocupada',
};

const consultar = () => router.get(route('dashboard'), props.filters, { preserveState: true, replace: true });

const labels = computed(() => (props.series || []).map((row) => row.day.slice(5)));
const barras = computed(() => [
    { label: 'Llegadas', data: (props.series || []).map((row) => row.arrivals), backgroundColor: '#1a365d' },
    { label: 'Ingresos', data: (props.series || []).map((row) => row.revenue), backgroundColor: '#c4a35a' },
    { label: 'POS', data: (props.series || []).map((row) => row.pos), backgroundColor: '#64748b' },
]);
const estados = computed(() => Object.entries(props.summary.rooms_by_status || {}));
const donaLabels = computed(() => estados.value.map(([key]) => estadoHabitacion[key] || key));
const donaData = computed(() => [{
    data: estados.value.map(([, total]) => total),
    backgroundColor: ['#22c55e', '#0ea5e9', '#eab308', '#64748b', '#dc2626', '#1a365d'],
}]);
</script>

<template>
    <Head title="Tablero" />
    <AuthenticatedLayout>
        <template #header>Tablero</template>
        <form class="row g-2 align-items-end mb-3" @submit.prevent="consultar">
            <div class="col-md-2">
                <label class="form-label">Desde</label>
                <input v-model="filters.from" type="date" class="form-control" />
            </div>
            <div class="col-md-2">
                <label class="form-label">Hasta</label>
                <input v-model="filters.to" type="date" class="form-control" />
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">Consultar</button>
            </div>
        </form>
        <div class="row g-3">
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Llegadas hoy</div><h3>{{ summary.arrivals_today }}</h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Salidas hoy</div><h3>{{ summary.departures_today }}</h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Ocupación</div><h3>{{ summary.occupancy_percent }}%</h3><small>{{ summary.rooms_occupied }}/{{ summary.rooms_total }}</small></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Saldo folios abiertos</div><h3>{{ money(summary.pending_balance) }}</h3><small>{{ summary.open_folios }} folios</small></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">POS hoy</div><h3>{{ money(summary.pos_sales_today) }}</h3><small>En el rango {{ money(summary.range_pos) }}</small></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Ingresos del rango</div><h3>{{ money(summary.range_revenue) }}</h3><small>{{ summary.range_arrivals }} llegadas</small></div></div></div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted">Caja</div>
                        <h3 v-if="cajaAbierta">Abierta</h3>
                        <h3 v-else>Cerrada</h3>
                        <small v-if="cajaAbierta">Fondo {{ money(cajaAbierta.fondo_inicial) }} · {{ cajaAbierta.fecha_apertura }}</small>
                        <small v-else>Hace falta apertura para consumos y cobros.</small>
                        <div class="mt-2"><Link :href="route('caja.index')" class="btn btn-sm btn-outline-primary">{{ cajaAbierta ? 'Ver turno' : 'Abrir caja' }}</Link></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">Llegadas, ingresos y POS</div>
                    <div class="card-body">
                        <HotelChart :labels="labels" :datasets="barras" />
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Habitaciones</div>
                    <div class="card-body">
                        <HotelChart type="doughnut" :labels="donaLabels" :datasets="donaData" />
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Reservas web pendientes</div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Folio</th><th>Huésped</th><th>Llegada</th><th>Total</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="item in summary.pending_web_list" :key="item.id">
                                    <td>{{ item.folio }}</td>
                                    <td>{{ item.huesped?.nombre }}</td>
                                    <td>{{ day(item.check_in) }}</td>
                                    <td>{{ money(item.estimated_total) }}</td>
                                    <td><Link :href="route('reservas.index', { status: 'pendiente' })" class="btn btn-sm btn-outline-primary">Ver</Link></td>
                                </tr>
                                <tr v-if="!summary.pending_web_list?.length"><td colspan="5" class="text-muted p-3">Sin solicitudes web pendientes.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
