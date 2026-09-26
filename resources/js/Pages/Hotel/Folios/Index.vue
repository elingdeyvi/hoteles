<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ folios: Object, filters: Object });
const cajaAbierta = computed(() => usePage().props.cajaAbierta);
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const filter = () => router.get(route('folios.index'), { status: props.filters.status || undefined }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Folios" />
    <AuthenticatedLayout>
        <template #header>Folios</template>
        <div v-if="!cajaAbierta" class="alert alert-warning">
            La caja está cerrada. Puede consultar folios, pero el cobro pide una apertura.
            <Link :href="route('caja.index')" class="alert-link">Abrir caja</Link>
        </div>
        <div class="mb-3">
            <select v-model="filters.status" class="form-select w-auto" @change="filter">
                <option value="">Todos</option>
                <option value="abierto">Abiertos</option>
                <option value="cerrado">Cerrados</option>
            </select>
        </div>
        <div class="card"><div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Folio</th><th>Huésped</th><th>Habitación</th><th>Estado</th><th>Saldo</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="folio in folios.data" :key="folio.id">
                        <td>{{ folio.folio_number }}</td>
                        <td>{{ folio.stay?.reservation?.huesped?.nombre }}</td>
                        <td>{{ folio.stay?.room?.number }}</td>
                        <td>{{ folio.status }}</td>
                        <td>{{ money(folio.balance) }}</td>
                        <td><Link :href="route('folios.show', folio.id)" class="btn btn-sm btn-outline-primary">Abrir</Link></td>
                    </tr>
                </tbody>
            </table>
        </div></div>
    </AuthenticatedLayout>
</template>
