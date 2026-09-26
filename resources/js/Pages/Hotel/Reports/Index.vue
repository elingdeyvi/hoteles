<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({ filters: Object, occupancy: Array, revenue: Object, arrivals: Array, departures: Array, pos: Object, cortes: Array });
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const day = (d) => (d ? String(d).slice(0, 10) : '');
const apply = () => router.get(route('reportes.index'), props.filters, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Reportes" />
    <AuthenticatedLayout>
        <template #header>Reportes</template>
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-md-2"><label class="form-label">Desde</label><input v-model="filters.from" type="date" class="form-control" /></div>
            <div class="col-md-2"><label class="form-label">Hasta</label><input v-model="filters.to" type="date" class="form-control" /></div>
            <div class="col-md-2"><label class="form-label">Día llegadas</label><input v-model="filters.date" type="date" class="form-control" /></div>
            <div class="col-md-2"><button class="btn btn-primary" @click="apply">Consultar</button></div>
            <div class="col-md-4 text-end">
                <a class="btn btn-outline-secondary" :href="route('reportes.ingresos.export', filters)">CSV ingresos</a>
                <a class="btn btn-outline-secondary" :href="route('reportes.pos.export', filters)">CSV POS</a>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6"><div class="card"><div class="card-header">Ocupación (llegadas)</div><ul class="list-group list-group-flush"><li v-for="row in occupancy" :key="row.day" class="list-group-item d-flex justify-content-between"><span>{{ row.day }}</span><strong>{{ row.total }}</strong></li></ul></div></div>
            <div class="col-md-6"><div class="card"><div class="card-header">Ingresos</div><ul class="list-group list-group-flush"><li v-for="row in revenue.by_day" :key="row.day" class="list-group-item d-flex justify-content-between"><span>{{ row.day }}</span><strong>{{ money(row.total) }}</strong></li></ul></div></div>
            <div class="col-md-6"><div class="card"><div class="card-header">Llegadas {{ filters.date }}</div><ul class="list-group list-group-flush"><li v-for="item in arrivals" :key="item.id" class="list-group-item">{{ item.folio }} · {{ item.huesped?.nombre }} · {{ day(item.check_in) }}</li></ul></div></div>
            <div class="col-md-6"><div class="card"><div class="card-header">Salidas</div><ul class="list-group list-group-flush"><li v-for="item in departures" :key="item.id" class="list-group-item">{{ item.folio }} · {{ item.huesped?.nombre }}</li></ul></div></div>
            <div class="col-12"><div class="card"><div class="card-header">POS {{ money(pos?.summary?.total_sales) }}</div><ul class="list-group list-group-flush"><li v-for="row in pos?.by_outlet || []" :key="row.outlet_id" class="list-group-item d-flex justify-content-between"><span>{{ row.outlet_name }}</span><strong>{{ money(row.total) }}</strong></li></ul></div></div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">Cortes de caja</div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Fecha</th><th>Tipo</th><th>Cobros</th><th>Esperado</th><th>Contado</th><th>Diferencia</th></tr></thead>
                            <tbody>
                                <tr v-for="corte in cortes" :key="corte.id">
                                    <td>{{ corte.fecha_corte }}</td>
                                    <td>{{ corte.tipo }}</td>
                                    <td>{{ money(corte.total_cobros) }}</td>
                                    <td>{{ money(corte.total_esperado) }}</td>
                                    <td>{{ corte.tipo === 'Z' ? money(corte.total_real) : '—' }}</td>
                                    <td>{{ corte.tipo === 'Z' ? money(corte.diferencia) : '—' }}</td>
                                </tr>
                                <tr v-if="!cortes?.length"><td colspan="6" class="text-muted p-3">Sin cortes en el periodo.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
