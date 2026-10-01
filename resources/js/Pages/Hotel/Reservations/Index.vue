<script setup>
import CompartirTicketModal from '@/Components/CompartirTicketModal.vue';
import QuickCreateModal from '@/Components/QuickCreateModal.vue';
import TicketPrintModal from '@/Components/TicketPrintModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { prepararReserva } from '@/utils/compartirTicket';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    reservations: Object,
    huespedes: Array,
    roomTypes: Array,
    rooms: Array,
    filters: Object,
    availability: Array,
});

const showModal = ref(false);
const showHuespedModal = ref(false);
const guestList = ref([...(props.huespedes || [])]);
const huespedFields = [
    { key: 'nombre', label: 'Nombre', required: true },
    { key: 'email', label: 'Correo', type: 'email' },
    { key: 'telefono', label: 'Teléfono' },
    { key: 'documento', label: 'Documento' },
    { key: 'nacionalidad', label: 'Nacionalidad' },
    { key: 'notas', label: 'Notas', type: 'textarea' },
];

watch(
    () => props.huespedes,
    (list) => {
        guestList.value = [...(list || [])];
    },
);
const localFilters = ref({
    status: props.filters?.status || '',
    source: props.filters?.source || '',
    from: props.filters?.from || '',
    to: props.filters?.to || '',
});
const fechaHotel = (dias = 0) => {
    const hoy = new Intl.DateTimeFormat('en-CA', {
        timeZone: 'America/Mexico_City',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date());
    if (!dias) return hoy;
    const [anio, mes, dia] = hoy.split('-').map(Number);
    return new Date(Date.UTC(anio, mes - 1, dia + dias)).toISOString().slice(0, 10);
};

const editingId = ref(null);
const form = useForm({
    huesped_id: '',
    room_type_id: '',
    room_id: '',
    check_in: fechaHotel(0),
    check_out: fechaHotel(1),
    modalidad: 'noche',
    hora_entrada: '15:00',
    hora_salida: '12:00',
    horas: 3,
    guests_count: 1,
    personas_extra: 0,
    requiere_factura: false,
    notes: '',
});

const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const day = (d) => (d ? String(d).slice(0, 10) : '');
const labels = { pendiente: 'Pendiente', confirmada: 'Confirmada', check_in: 'Check-in', check_out: 'Check-out', cancelada: 'Cancelada' };
const page = usePage();
const compartirRef = ref(null);
const ticketRef = ref(null);
const compartirError = ref('');

const compartir = async (item) => {
    compartirError.value = '';
    try {
        const prep = await prepararReserva(item, page.props.empresa || {});
        compartirRef.value?.abrir(prep);
    } catch (error) {
        compartirError.value = error?.message || 'No se pudo preparar la reservación.';
    }
};

const quote = computed(() => (props.availability || []).find((row) => String(row.room_type.id) === String(form.room_type_id)));

const selectableRooms = computed(() => {
    const ofType = props.rooms.filter((room) => !form.room_type_id || String(room.room_type_id) === String(form.room_type_id));
    if (!quote.value) return ofType;
    const ids = (quote.value.room_ids || []).map(String);
    return ofType.filter((room) => ids.includes(String(room.id)));
});

const search = () => {
    router.get(route('reservas.index'), {
        status: localFilters.value.status || undefined,
        source: localFilters.value.source || undefined,
        from: localFilters.value.from || undefined,
        to: localFilters.value.to || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

const diaSiguiente = (fecha) => {
    const [y, m, d] = String(fecha).split('-').map(Number);
    return new Date(Date.UTC(y, m - 1, d + 1)).toISOString().slice(0, 10);
};

let consultTimer = null;
const consult = () => {
    if (!form.check_in) return;
    const salida = form.modalidad === 'horas' ? diaSiguiente(form.check_in) : form.check_out;
    if (!salida || (form.modalidad !== 'horas' && salida <= form.check_in)) return;
    router.get(route('reservas.index'), {
        status: localFilters.value.status || undefined,
        source: localFilters.value.source || undefined,
        from: localFilters.value.from || undefined,
        to: localFilters.value.to || undefined,
        avail_in: form.check_in,
        avail_out: salida,
        modalidad: form.modalidad,
        horas: form.horas,
        personas_extra: form.personas_extra,
        exclude: editingId.value || undefined,
    }, {
        only: ['availability'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

watch(() => [form.check_in, form.check_out, form.modalidad, form.horas, form.personas_extra, form.room_type_id], () => {
    clearTimeout(consultTimer);
    consultTimer = setTimeout(consult, 250);
});

watch(selectableRooms, (rooms) => {
    if (form.room_id && !rooms.some((room) => String(room.id) === String(form.room_id))) {
        form.room_id = '';
    }
});

const openCreate = (opts = {}) => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.modalidad = 'noche';
    form.hora_entrada = '15:00';
    form.hora_salida = '12:00';
    form.horas = 3;
    form.guests_count = 1;
    form.personas_extra = 0;
    form.requiere_factura = false;
    form.check_in = opts.check_in || fechaHotel(0);
    form.check_out = opts.check_out || fechaHotel(1);
    if (form.check_out <= form.check_in) {
        const [y, m, d] = form.check_in.split('-').map(Number);
        form.check_out = new Date(Date.UTC(y, m - 1, d + 1)).toISOString().slice(0, 10);
    }
    showModal.value = true;
    consult();
};

if (props.filters?.create) {
    openCreate({
        check_in: props.filters.check_in || undefined,
        check_out: props.filters.check_out || undefined,
    });
}

const horaCorta = (valor) => (valor ? String(valor).slice(0, 5) : '');

const openEdit = (item) => {
    editingId.value = item.id;
    form.clearErrors();
    form.huesped_id = item.huesped_id;
    form.room_type_id = item.room_type_id;
    form.room_id = item.room_id || '';
    form.check_in = day(item.check_in);
    form.check_out = item.modalidad === 'horas' ? diaSiguiente(item.check_in) : day(item.check_out);
    form.modalidad = item.modalidad || 'noche';
    form.hora_entrada = horaCorta(item.hora_entrada) || '15:00';
    form.hora_salida = horaCorta(item.hora_salida) || '12:00';
    form.horas = item.horas || 3;
    form.guests_count = item.guests_count || 1;
    form.personas_extra = item.personas_extra || 0;
    form.requiere_factura = !!item.requiere_factura;
    form.notes = item.notes || '';
    showModal.value = true;
    consult();
};

const submit = () => {
    const url = editingId.value ? route('reservas.update', editingId.value) : route('reservas.store');
    form.transform((data) => ({
        ...data,
        room_id: data.room_id || null,
        huesped_id: data.huesped_id || null,
        room_type_id: data.room_type_id || null,
        check_out: data.modalidad === 'horas' ? data.check_in : data.check_out,
        ...(editingId.value ? { _method: 'put' } : {}),
    })).post(url, {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
};

const onHuespedCreated = (guest) => {
    const entry = {
        id: guest.id,
        nombre: guest.nombre,
        email: guest.email ?? null,
    };
    if (!guestList.value.some((g) => String(g.id) === String(entry.id))) {
        guestList.value = [...guestList.value, entry].sort((a, b) =>
            String(a.nombre).localeCompare(String(b.nombre), 'es'),
        );
    }
    form.huesped_id = entry.id;
    showHuespedModal.value = false;
};
</script>

<template>
    <Head title="Reservas" />
    <AuthenticatedLayout>
        <template #header>Reservas</template>
        <div class="card mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select v-model="localFilters.status" class="form-select" @change="search">
                        <option value="">Todos</option>
                        <option v-for="(label, key) in labels" :key="key" :value="key">{{ label }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Origen</label>
                    <select v-model="localFilters.source" class="form-select" @change="search">
                        <option value="">Todos</option>
                        <option value="recepcion">Recepción</option>
                        <option value="web">Web</option>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Desde</label><input v-model="localFilters.from" type="date" class="form-control" @change="search" /></div>
                <div class="col-md-2"><label class="form-label">Hasta</label><input v-model="localFilters.to" type="date" class="form-control" @change="search" /></div>
                <div class="col-md-4 text-end"><button class="btn btn-primary" type="button" @click="openCreate">Nueva reserva</button></div>
                <div v-if="compartirError" class="col-12"><div class="alert alert-warning mb-0">{{ compartirError }}</div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Folio</th><th>Huésped</th><th>Tipo</th><th>Hab.</th><th>Entrada</th><th>Salida</th><th>Estado</th><th>Total</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="item in reservations.data" :key="item.id">
                            <td>{{ item.folio }}</td>
                            <td>{{ item.huesped?.nombre }}</td>
                            <td>{{ item.room_type?.name }}</td>
                            <td>{{ item.room?.number || '—' }}</td>
                            <td>{{ day(item.check_in) }} <small v-if="item.hora_entrada" class="text-muted">{{ String(item.hora_entrada).slice(0, 5) }}</small></td>
                            <td>
                                <template v-if="item.modalidad === 'horas'">{{ item.horas }} h</template>
                                <template v-else>{{ day(item.check_out) }}</template>
                                <small v-if="item.hora_salida" class="text-muted"> {{ String(item.hora_salida).slice(0, 5) }}</small>
                            </td>
                            <td><span class="badge text-bg-secondary">{{ labels[item.status] || item.status }}</span></td>
                            <td>
                                {{ money(item.estimated_total) }}
                                <span v-if="item.requiere_factura" class="badge text-bg-info ms-1">Factura</span>
                            </td>
                            <td class="text-nowrap">
                                <button v-if="['confirmada','pendiente'].includes(item.status)" type="button" class="btn btn-sm btn-outline-primary" @click="openEdit(item)">Editar</button>
                                <button type="button" class="btn btn-sm btn-outline-dark" @click="ticketRef?.solicitar(route('reservas.imprimir', item.id), 'Reservación')">Imprimir</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="compartir(item)">Compartir</button>
                                <Link v-if="['confirmada','pendiente'].includes(item.status)" :href="route('reservas.check-in', item.id)" class="btn btn-sm btn-success">Entrada</Link>
                                <Link v-if="item.status === 'pendiente'" :href="route('reservas.confirm', item.id)" method="post" as="button" class="btn btn-sm btn-outline-primary">Confirmar</Link>
                                <Link v-if="item.status === 'check_in'" :href="route('reservas.check-out', item.id)" method="post" as="button" class="btn btn-sm btn-warning">Salida</Link>
                                <Link v-if="!['check_in','cancelada','check_out'].includes(item.status)" :href="route('reservas.cancel', item.id)" method="post" as="button" class="btn btn-sm btn-outline-danger">Cancelar</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45); z-index: 2000">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <form class="modal-content" @submit.prevent="submit">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ editingId ? 'Editar reserva' : 'Nueva reserva' }}</h5>
                            <button type="button" class="btn-close" @click="showModal = false"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div v-if="Object.keys(form.errors).length" class="col-12">
                                <div class="alert alert-danger mb-0">
                                    <div v-for="(message, field) in form.errors" :key="field">{{ message }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Huésped</label>
                                <div class="input-group">
                                    <select v-model="form.huesped_id" class="form-select" :class="{ 'is-invalid': form.errors.huesped_id }" required>
                                        <option value="">Seleccione</option>
                                        <option v-for="guest in guestList" :key="guest.id" :value="guest.id">{{ guest.nombre }}</option>
                                    </select>
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary"
                                        title="Agregar huésped"
                                        @click="showHuespedModal = true"
                                    >
                                        <i class="fa-solid fa-user-plus"></i>
                                        <span class="d-none d-sm-inline ms-1">Nuevo</span>
                                    </button>
                                </div>
                                <div class="form-text">Si no está en la lista, agréguelo aquí.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipo</label>
                                <select v-model="form.room_type_id" class="form-select" :class="{ 'is-invalid': form.errors.room_type_id }" required>
                                    <option value="">Seleccione</option>
                                    <option v-for="type in roomTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Modalidad</label>
                                <select v-model="form.modalidad" class="form-select">
                                    <option value="noche">Por noche</option>
                                    <option value="horas">Por horas</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Entrada</label>
                                <input v-model="form.check_in" type="date" class="form-control" :class="{ 'is-invalid': form.errors.check_in }" required />
                            </div>
                            <div class="col-md-4" v-if="form.modalidad !== 'horas'">
                                <label class="form-label">Salida</label>
                                <input v-model="form.check_out" type="date" class="form-control" :class="{ 'is-invalid': form.errors.check_out }" required />
                            </div>
                            <div class="col-md-4" v-else>
                                <label class="form-label">Horas</label>
                                <input v-model="form.horas" type="number" min="1" max="24" class="form-control" :class="{ 'is-invalid': form.errors.horas }" required />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hora de entrada</label>
                                <input v-model="form.hora_entrada" type="time" class="form-control" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hora de salida</label>
                                <input v-model="form.hora_salida" type="time" class="form-control" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Huéspedes</label>
                                <input v-model="form.guests_count" type="number" min="1" class="form-control" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Personas extra</label>
                                <input v-model="form.personas_extra" type="number" min="0" class="form-control" />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Habitación</label>
                                <select v-model="form.room_id" class="form-select" :class="{ 'is-invalid': form.errors.room_id }" :disabled="!form.check_in || !form.check_out">
                                    <option value="">Asignar en check-in</option>
                                    <option v-for="room in selectableRooms" :key="room.id" :value="room.id">{{ room.number }}</option>
                                </select>
                            </div>
                            <div class="col-12" v-if="quote">
                                <div class="alert mb-0" :class="quote.available_rooms ? 'alert-info' : 'alert-warning'">
                                    {{ quote.room_type.name }}: {{ quote.available_rooms }} disponibles ·
                                    {{ money(quote.nightly_rate) }} {{ form.modalidad === 'horas' ? 'por hora' : 'por noche' }}
                                    <span v-if="Number(quote.monto_extra) > 0"> · extra {{ money(quote.monto_extra) }}</span>
                                    · total {{ money(quote.estimated_total) }}
                                </div>
                            </div>
                            <div class="col-12" v-else-if="form.check_in && form.check_out && form.check_out > form.check_in && availability">
                                <ul class="list-group">
                                    <li v-for="row in availability" :key="row.room_type.id" class="list-group-item d-flex justify-content-between">
                                        <button type="button" class="btn btn-link p-0" @click="form.room_type_id = row.room_type.id">{{ row.room_type.name }}</button>
                                        <span>{{ row.available_rooms }} disp. · {{ money(row.estimated_total) }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input id="factura" v-model="form.requiere_factura" class="form-check-input" type="checkbox" />
                                    <label class="form-check-label" for="factura">Pide factura</label>
                                </div>
                                <div class="form-text">Queda marcada en la reserva. El timbrado ante el SAT se hace aparte, con certificados y un PAC.</div>
                            </div>
                            <div class="col-12"><label class="form-label">Notas</label><textarea v-model="form.notes" class="form-control" rows="2"></textarea></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showModal = false">Cerrar</button>
                            <button class="btn btn-primary" :disabled="form.processing || (quote && quote.available_rooms < 1)">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
        <QuickCreateModal
            :show="showHuespedModal"
            title="Nuevo huésped"
            route-name="huespedes.store"
            :fields="huespedFields"
            @close="showHuespedModal = false"
            @created="onHuespedCreated"
        />
        <CompartirTicketModal ref="compartirRef" />
        <TicketPrintModal ref="ticketRef" />
    </AuthenticatedLayout>
</template>
