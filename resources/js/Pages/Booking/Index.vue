<script setup>
import CompartirTicketModal from '@/Components/CompartirTicketModal.vue';
import { prepararReserva } from '@/utils/compartirTicket';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    property: Object,
    hotel: Object,
    roomTypes: Array,
    availability: Array,
    lookup: Object,
    filters: Object,
    booking: Object,
});
const page = usePage();
const compartirRef = ref(null);
const compartirError = ref('');

const compartirLookup = async () => {
    if (!props.lookup || props.lookup.missing) return;
    compartirError.value = '';
    try {
        const prep = await prepararReserva({
            folio: props.lookup.folio,
            status: props.lookup.status,
            check_in: props.lookup.check_in,
            check_out: props.lookup.check_out,
            estimated_total: props.lookup.estimated_total,
            guests_count: props.lookup.guests_count,
            guest_name: props.lookup.guest_name,
            room_type: props.lookup.room_type,
            room: props.lookup.room,
            telefono: props.lookup.telefono,
            huesped: { nombre: props.lookup.guest_name, telefono: props.lookup.telefono },
        }, {
            nombre: props.hotel?.nombre,
            nombre_corto: props.hotel?.nombre,
            telefono: props.hotel?.telefono,
            email: props.hotel?.email,
            color_primario: props.hotel?.color_primario,
        });
        compartirRef.value?.abrir(prep);
    } catch (error) {
        compartirError.value = error?.message || 'No se pudo preparar la reservación.';
    }
};
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const search = useForm({ check_in: props.filters.check_in || '', check_out: props.filters.check_out || '', room_type_id: props.filters.room_type_id || '' });
const reserve = useForm({
    room_type_id: '',
    check_in: props.filters.check_in || '',
    check_out: props.filters.check_out || '',
    guests_count: 1,
    notes: '',
    guest_nombre: '',
    guest_email: '',
    guest_telefono: '',
    guest_documento: '',
});
const lookupForm = useForm({ folio: props.filters.folio || '', email: props.filters.email || '' });
const pay = useForm({ folio: props.filters.folio || '', email: props.filters.email || '' });

let consultTimer = null;
watch(() => [search.check_in, search.check_out], () => {
    clearTimeout(consultTimer);
    consultTimer = setTimeout(consult, 300);
});

const selectedQuote = computed(() => (props.availability || []).find((row) => String(row.room_type.id) === String(reserve.room_type_id)));

const consult = () => {
    if (!search.check_in || !search.check_out || search.check_out <= search.check_in) return;
    router.get(route('booking.show', props.property.code), {
        check_in: search.check_in,
        check_out: search.check_out,
    }, { only: ['availability', 'filters'], preserveState: true, preserveScroll: true, replace: true });
};

const find = () => router.get(route('booking.show', props.property.code), {
    folio: lookupForm.folio,
    email: lookupForm.email,
}, { only: ['lookup', 'filters'], preserveState: true, preserveScroll: true, replace: true });

const choose = (row) => {
    reserve.room_type_id = row.room_type.id;
    reserve.check_in = search.check_in;
    reserve.check_out = search.check_out;
    reserve.clearErrors();
};

const submit = () => reserve.post(route('booking.store', props.property.code), { preserveScroll: true });
</script>

<template>
    <Head :title="`Reservar ${hotel.nombre}`" />
    <div class="container py-4" :style="{ '--bs-primary': hotel.color_primario }">
        <div class="d-flex align-items-center gap-3 mb-4">
            <img v-if="hotel.logo_url" :src="hotel.logo_url" alt="" height="48" />
            <div>
                <h1 class="h3 mb-0">{{ hotel.nombre }}</h1>
                <div class="text-muted">{{ hotel.direccion }} · {{ hotel.telefono }}</div>
            </div>
        </div>
        <div v-if="page.props.flash?.success" class="alert alert-success">{{ page.props.flash.success }}</div>
        <div v-if="page.props.flash?.error" class="alert alert-danger">{{ page.props.flash.error }}</div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card mb-3"><div class="card-body">
                    <h5>Disponibilidad</h5>
                    <div class="row g-2">
                        <div class="col-md-4"><input v-model="search.check_in" type="date" class="form-control" /></div>
                        <div class="col-md-4"><input v-model="search.check_out" type="date" class="form-control" /></div>
                        <div class="col-md-4"><button type="button" class="btn btn-primary w-100" @click="consult">Consultar</button></div>
                    </div>
                    <div v-if="availability" class="mt-3">
                        <div v-for="row in availability" :key="row.room_type.id" class="border rounded p-2 mb-2 d-flex justify-content-between">
                            <div>
                                <strong>{{ row.room_type.name }}</strong>
                                <div class="small text-muted">{{ row.available_rooms }} disponibles · {{ money(row.nightly_rate) }} / noche</div>
                            </div>
                            <button type="button" class="btn btn-sm" :class="String(reserve.room_type_id) === String(row.room_type.id) ? 'btn-primary' : 'btn-outline-primary'" :disabled="row.available_rooms < 1" @click="choose(row)">{{ row.available_rooms < 1 ? 'Agotado' : 'Elegir' }}</button>
                        </div>
                    </div>
                </div></div>
                <form class="card" @submit.prevent="submit">
                    <div class="card-header">Solicitar reserva</div>
                    <div class="card-body row g-2">
                        <div v-if="Object.keys(reserve.errors).length" class="col-12">
                            <div class="alert alert-danger mb-0">
                                <div v-for="(message, field) in reserve.errors" :key="field">{{ message }}</div>
                            </div>
                        </div>
                        <div v-if="selectedQuote" class="col-12">
                            <div class="alert alert-info mb-0">Total estimado {{ money(selectedQuote.estimated_total) }} · {{ money(selectedQuote.nightly_rate) }} por noche</div>
                        </div>
                        <div class="col-md-6"><select v-model="reserve.room_type_id" class="form-select" required><option value="">Tipo de habitación</option><option v-for="type in roomTypes" :key="type.id" :value="type.id">{{ type.name }}</option></select></div>
                        <div class="col-md-3"><input v-model="reserve.check_in" type="date" class="form-control" required /></div>
                        <div class="col-md-3"><input v-model="reserve.check_out" type="date" class="form-control" required /></div>
                        <div class="col-md-6"><input v-model="reserve.guest_nombre" class="form-control" placeholder="Nombre" required /></div>
                        <div class="col-md-6"><input v-model="reserve.guest_email" type="email" class="form-control" placeholder="Correo" required /></div>
                        <div class="col-md-4"><input v-model="reserve.guest_telefono" class="form-control" placeholder="Teléfono" /></div>
                        <div class="col-md-4"><input v-model="reserve.guest_documento" class="form-control" placeholder="Documento" /></div>
                        <div class="col-md-4"><input v-model="reserve.guests_count" type="number" min="1" :max="booking.max_guests" class="form-control" /></div>
                        <div class="col-12"><textarea v-model="reserve.notes" class="form-control" placeholder="Notas" rows="2"></textarea></div>
                        <div class="col-12"><button class="btn btn-primary" :disabled="reserve.processing">Enviar solicitud</button></div>
                        <div class="col-12 small text-muted">{{ booking.confirmation_note }}</div>
                    </div>
                </form>
            </div>
            <div class="col-lg-5">
                <form class="card" @submit.prevent="find">
                    <div class="card-header">Consultar reserva</div>
                    <div class="card-body">
                        <input v-model="lookupForm.folio" class="form-control mb-2" placeholder="Folio" required />
                        <input v-model="lookupForm.email" type="email" class="form-control mb-2" placeholder="Correo" required />
                        <button class="btn btn-outline-primary">Buscar</button>
                        <div v-if="lookup?.missing" class="alert alert-warning mt-3 mb-0">No se encontró la reserva.</div>
                        <div v-else-if="lookup" class="mt-3">
                            <p class="mb-1"><strong>{{ lookup.folio }}</strong> · {{ lookup.status }}</p>
                            <p class="mb-1">{{ lookup.guest_name }} · {{ lookup.room_type }}</p>
                            <p class="mb-1">{{ lookup.check_in }} → {{ lookup.check_out }}</p>
                            <p class="mb-1">Total {{ money(lookup.estimated_total) }}</p>
                            <p v-if="lookup.payment_status">Pago: {{ lookup.payment_status }} <span v-if="lookup.deposit_amount">· anticipo {{ money(lookup.deposit_amount) }}</span></p>
                            <button type="button" class="btn btn-success" @click="compartirLookup">Compartir reserva</button>
                            <div v-if="compartirError" class="alert alert-warning mt-2 mb-0">{{ compartirError }}</div>
                            <button v-if="booking.payments.enabled && booking.payments.provider === 'demo' && lookup.payment_status === 'pending'" type="button" class="btn btn-success" @click="pay.folio = lookup.folio; pay.email = lookupForm.email; pay.post(route('booking.demo-pay', property.code))">Pagar anticipo demo</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <CompartirTicketModal ref="compartirRef" />
</template>
