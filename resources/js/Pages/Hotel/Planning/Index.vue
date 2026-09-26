<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    date: { type: String, required: true },
    today: { type: String, required: true },
    board: { type: Object, required: true },
});

const boardDate = ref(props.date);
const board = ref(props.board);
const boardLoading = ref(false);
const selectedEvent = ref(null);
const calendarRef = ref(null);

const canSchedule = computed(() => boardDate.value >= props.today);

const statusLabel = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    check_in: 'Check-in',
    check_out: 'Check-out',
    cancelada: 'Cancelada',
};

const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

async function fetchEvents(info, successCallback, failureCallback) {
    try {
        const { data } = await window.axios.get(route('planning.calendar'), {
            params: {
                start: info.startStr.slice(0, 10),
                end: info.endStr.slice(0, 10),
            },
        });
        successCallback(data?.data || []);
    } catch (error) {
        failureCallback(error);
    }
}

async function loadBoard(date = boardDate.value) {
    boardLoading.value = true;
    boardDate.value = date;
    try {
        const { data } = await window.axios.get(route('planning.room-board'), {
            params: { date },
        });
        board.value = data?.data || board.value;
        const url = new URL(window.location.href);
        url.searchParams.set('date', date);
        window.history.replaceState({}, '', url);
    } finally {
        boardLoading.value = false;
    }
}

const agendar = (checkIn = boardDate.value) => {
    if (checkIn < props.today) return;
    const [y, m, d] = checkIn.split('-').map(Number);
    const next = new Date(Date.UTC(y, m - 1, d + 1)).toISOString().slice(0, 10);
    router.get(route('reservas.index'), {
        create: 1,
        check_in: checkIn,
        check_out: next,
    });
};

const calendarOptions = {
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    initialDate: props.date,
    locale: esLocale,
    height: 'auto',
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth',
    },
    buttonText: {
        today: 'Hoy',
        month: 'Mes',
    },
    events: fetchEvents,
    dateClick(info) {
        selectedEvent.value = null;
        const sameDay = boardDate.value === info.dateStr;
        loadBoard(info.dateStr);
        // Segundo clic en la misma fecha (hoy o futura) abre agenda.
        if (sameDay && info.dateStr >= props.today) {
            agendar(info.dateStr);
        }
    },
    eventClick(info) {
        selectedEvent.value = {
            id: info.event.id,
            title: info.event.title,
            extendedProps: info.event.extendedProps,
        };
        loadBoard(info.event.startStr.slice(0, 10));
    },
};

onMounted(() => {
    if (!board.value?.rooms) {
        loadBoard(boardDate.value);
    }
});
</script>

<template>
    <Head title="Planning" />
    <AuthenticatedLayout>
        <template #header>Planning</template>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card hotel-planning-calendar">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <strong>Calendario de ocupación</strong>
                            <div class="small text-muted">Clic en una fecha para ver el día · desde hoy puede agendar</div>
                        </div>
                        <button
                            type="button"
                            class="btn btn-primary btn-sm"
                            :disabled="!canSchedule"
                            @click="agendar()"
                        >
                            <i class="fa-solid fa-plus me-1"></i>
                            Agendar {{ boardDate }}
                        </button>
                    </div>
                    <div class="card-body">
                        <FullCalendar ref="calendarRef" :options="calendarOptions" />
                        <div class="d-flex flex-wrap gap-3 mt-3 small text-muted">
                            <span><span class="legend-dot" style="background:#1e5f8a"></span> Confirmada</span>
                            <span><span class="legend-dot" style="background:#22c55e"></span> Check-in</span>
                            <span><span class="legend-dot" style="background:#64748b"></span> Pendiente</span>
                            <span><span class="legend-dot" style="background:#c4a35a"></span> Check-out</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Ocupación del día</span>
                        <input
                            :value="boardDate"
                            type="date"
                            class="form-control form-control-sm w-auto"
                            @change="loadBoard($event.target.value)"
                        />
                    </div>
                    <div class="card-body">
                        <div v-if="boardLoading" class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                        </div>
                        <template v-else>
                            <div class="row g-2 mb-3 text-center">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="small text-muted">Total</div>
                                        <strong>{{ board.summary?.total_rooms ?? 0 }}</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="small text-muted">Ocupadas</div>
                                        <strong class="text-danger">{{ board.summary?.occupied ?? 0 }}</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="small text-muted">Libres</div>
                                        <strong class="text-success">{{ board.summary?.available ?? 0 }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div v-if="canSchedule" class="d-grid mb-3">
                                <button type="button" class="btn btn-outline-primary" @click="agendar()">
                                    Nueva reserva para {{ boardDate }}
                                </button>
                            </div>
                            <div v-else class="alert alert-secondary py-2 small">
                                Fechas anteriores a hoy solo consulta. Elija hoy o una fecha futura para agendar.
                            </div>

                            <div class="list-group list-group-flush planning-board-list">
                                <div
                                    v-for="row in board.rooms"
                                    :key="row.room.id"
                                    class="list-group-item px-0 d-flex justify-content-between align-items-start"
                                >
                                    <div>
                                        <strong>{{ row.room.number }}</strong>
                                        <small class="d-block text-muted">
                                            {{ row.room.room_type?.name }}
                                            <span v-if="row.room.floor != null"> · Piso {{ row.room.floor }}</span>
                                        </small>
                                        <small v-if="row.reservation" class="text-primary">
                                            {{ row.reservation.huesped?.nombre }} · {{ row.reservation.folio }}
                                        </small>
                                    </div>
                                    <span
                                        class="badge"
                                        :class="row.occupied ? 'text-bg-danger' : 'text-bg-success'"
                                    >
                                        {{ row.occupied ? 'Ocupada' : (row.room.status || 'libre') }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="board.unassigned_reservations?.length" class="mt-3">
                                <p class="small fw-semibold mb-1">Sin habitación asignada</p>
                                <ul class="small mb-0 ps-3">
                                    <li v-for="r in board.unassigned_reservations" :key="r.id">
                                        {{ r.huesped?.nombre || 'Huésped' }} ({{ r.folio }})
                                    </li>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>

                <div v-if="selectedEvent" class="card">
                    <div class="card-header">Reserva seleccionada</div>
                    <div class="card-body">
                        <p class="small mb-1"><strong>Folio:</strong> {{ selectedEvent.extendedProps?.folio }}</p>
                        <p class="small mb-1"><strong>Huésped:</strong> {{ selectedEvent.title }}</p>
                        <p class="small mb-1">
                            <strong>Estado:</strong>
                            {{ statusLabel[selectedEvent.extendedProps?.status] || selectedEvent.extendedProps?.status }}
                        </p>
                        <p class="small mb-1">
                            <strong>Estancia:</strong>
                            {{ selectedEvent.extendedProps?.check_in }} → {{ selectedEvent.extendedProps?.check_out }}
                        </p>
                        <p class="small mb-3">
                            <strong>Total est.:</strong> {{ money(selectedEvent.extendedProps?.estimated_total) }}
                        </p>
                        <Link
                            v-if="['confirmada', 'pendiente'].includes(selectedEvent.extendedProps?.status)"
                            :href="route('reservas.check-in', selectedEvent.id)"
                            class="btn btn-sm btn-success"
                        >
                            Ir a check-in
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.legend-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: 4px;
}

.planning-board-list {
    max-height: 420px;
    overflow-y: auto;
}

.hotel-planning-calendar :deep(.fc) {
    --fc-border-color: #dee2e6;
    --fc-today-bg-color: rgba(30, 95, 138, 0.08);
}

.hotel-planning-calendar :deep(.fc-daygrid-day) {
    cursor: pointer;
}

.hotel-planning-calendar :deep(.fc-daygrid-day:hover) {
    background: rgba(13, 110, 253, 0.06);
}

.hotel-planning-calendar :deep(.fc-event) {
    cursor: pointer;
    font-size: 0.75rem;
}
</style>
