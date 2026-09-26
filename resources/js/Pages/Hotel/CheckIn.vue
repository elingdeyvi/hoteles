<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ reservation: Object, availableRooms: Array });
const form = useForm({ room_id: props.availableRooms[0]?.id || '' });
const day = (d) => (d ? String(d).slice(0, 10) : '');
const submit = () => form.post(route('reservas.check-in.store', props.reservation.id));
</script>

<template>
    <Head title="Check-in" />
    <AuthenticatedLayout>
        <template #header>Check-in {{ reservation.folio }}</template>
        <div class="card">
            <div class="card-body">
                <p><strong>{{ reservation.huesped?.nombre }}</strong> · {{ reservation.room_type?.name }}</p>
                <p>{{ day(reservation.check_in) }} → {{ day(reservation.check_out) }} · {{ reservation.guests_count }} huéspedes</p>
                <form class="row g-3" @submit.prevent="submit">
                    <div class="col-md-4">
                        <label class="form-label">Habitación disponible</label>
                        <select v-model="form.room_id" class="form-select" required>
                            <option v-for="room in availableRooms" :key="room.id" :value="room.id">{{ room.number }} · {{ room.status }}</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success" :disabled="form.processing || !availableRooms.length">Registrar check-in</button>
                        <p v-if="!availableRooms.length" class="text-danger mt-2">No hay habitaciones disponibles de este tipo.</p>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
