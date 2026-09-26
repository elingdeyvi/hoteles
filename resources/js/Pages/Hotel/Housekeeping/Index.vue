<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({ rooms: Array });
const statuses = ['disponible', 'limpia', 'sucia', 'mantenimiento', 'ocupada'];
const tone = { disponible: 'success', limpia: 'info', sucia: 'warning', mantenimiento: 'secondary', ocupada: 'danger' };
const update = (room, status) => router.patch(route('housekeeping.status', room.id), { status }, { preserveScroll: true });
</script>

<template>
    <Head title="Housekeeping" />
    <AuthenticatedLayout>
        <template #header>Housekeeping</template>
        <div class="row g-3">
            <div v-for="room in rooms" :key="room.id" class="col-md-4 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0">{{ room.number }}</h5>
                            <span class="badge" :class="`text-bg-${tone[room.status] || 'secondary'}`">{{ room.status }}</span>
                        </div>
                        <p class="text-muted mb-2">{{ room.room_type?.name }} · Piso {{ room.floor }}</p>
                        <select class="form-select form-select-sm" :value="room.status" @change="update(room, $event.target.value)">
                            <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
