<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({ date: String, events: Array, board: Array, unassigned: Array });
const day = (d) => (d ? String(d).slice(0, 10) : '');
const changeDate = (event) => router.get(route('planning.index'), { date: event.target.value }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Planning" />
    <AuthenticatedLayout>
        <template #header>Planning</template>
        <div class="mb-3"><input :value="date" type="date" class="form-control w-auto" @change="changeDate" /></div>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">Ocupación del día</div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Habitación</th><th>Estado</th><th>Huésped</th><th>Folio</th></tr></thead>
                            <tbody>
                                <tr v-for="row in board" :key="row.room.id">
                                    <td>{{ row.room.number }} · {{ row.room.room_type?.name }}</td>
                                    <td>{{ row.occupied ? 'Ocupada' : row.room.status }}</td>
                                    <td>{{ row.reservation?.huesped?.nombre || '—' }}</td>
                                    <td>{{ row.reservation?.folio || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="unassigned?.length" class="alert alert-warning mt-3">
                    Reservas sin habitación: <span v-for="item in unassigned" :key="item.id" class="me-2">{{ item.folio }}</span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header">Reservas del mes</div>
                    <ul class="list-group list-group-flush">
                        <li v-for="item in events" :key="item.id" class="list-group-item">
                            <strong>{{ item.folio }}</strong> {{ item.huesped?.nombre }}
                            <div class="small text-muted">{{ day(item.check_in) }} → {{ day(item.check_out) }} · {{ item.status }}</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
