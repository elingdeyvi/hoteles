<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ summary: Object });
const cajaAbierta = computed(() => usePage().props.cajaAbierta);

const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const day = (d) => (d ? String(d).slice(0, 10) : '');
</script>

<template>
    <Head title="Tablero" />
    <AuthenticatedLayout>
        <template #header>Tablero</template>
        <div class="row g-3">
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Llegadas hoy</div><h3>{{ summary.arrivals_today }}</h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Salidas hoy</div><h3>{{ summary.departures_today }}</h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Ocupación</div><h3>{{ summary.occupancy_percent }}%</h3><small>{{ summary.rooms_occupied }}/{{ summary.rooms_total }}</small></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Saldo folios abiertos</div><h3>{{ money(summary.pending_balance) }}</h3><small>{{ summary.open_folios }} folios</small></div></div></div>
            <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">POS hoy</div><h3>{{ money(summary.pos_sales_today) }}</h3><small>Mes {{ money(summary.pos_sales_month) }}</small></div></div></div>
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
