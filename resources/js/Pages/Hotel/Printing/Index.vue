<script setup>
import TicketPrintModal from '@/Components/TicketPrintModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    modo: String,
    agenteEnLinea: Boolean,
    impresoras: Array,
    agentes: Array,
    trabajos: Array,
});

const ticketRef = ref(null);
const impresora = useForm({ nombre: 'Recepción', nombre_sistema: '', ip: '', puerto: 9100, ancho_papel: 80, es_default: true });
const token = useForm({ nombre: 'Recepción' });
const editando = ref(null);
const edicion = useForm({ nombre: '', nombre_sistema: '', ip: '', puerto: 9100, ancho_papel: 80, activo: true, es_default: false });

const probar = (id) => ticketRef.value?.solicitar(route('impresion.probar', id), 'Prueba de impresión');
const eliminarImpresora = (item) => {
    if (window.confirm(`¿Eliminar la impresora ${item.nombre}? Solo se borra si no tiene trabajos.`)) {
        router.delete(route('impresion.impresoras.destroy', item.id));
    }
};
const abrirEdicion = (item) => {
    editando.value = item.id;
    edicion.nombre = item.nombre;
    edicion.nombre_sistema = item.nombre_sistema || '';
    edicion.ip = item.ip || '';
    edicion.puerto = item.puerto || 9100;
    edicion.ancho_papel = item.ancho_papel || 80;
    edicion.activo = !!item.activo;
    edicion.es_default = !!item.es_default;
};
</script>

<template>
    <Head title="Impresión" />
    <AuthenticatedLayout>
        <template #header>Impresión</template>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="alert" :class="agenteEnLinea ? 'alert-success' : 'alert-warning'">
                    Modo <strong>{{ modo === 'queue' ? 'con agente (cola)' : 'directo en este servidor' }}</strong>.
                    <span v-if="modo === 'queue'">{{ agenteEnLinea ? 'Hay un agente en línea.' : 'No hay agente en línea: el ticket se imprime en el navegador.' }}</span>
                </div>
                <div class="card mb-3">
                    <div class="card-header">Impresoras</div>
                    <div class="card-body p-0 table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Nombre</th><th>Windows / IP</th><th>Papel</th><th></th></tr></thead>
                            <tbody>
                                <tr v-for="item in impresoras" :key="item.id">
                                    <td>{{ item.nombre }} <span v-if="item.es_default" class="badge text-bg-primary">Default</span></td>
                                    <td>{{ item.nombre_sistema || item.ip || 'Sin destino' }}</td>
                                    <td>{{ item.ancho_papel }} mm</td>
                                    <td class="text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="abrirEdicion(item)">Editar</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" @click="probar(item.id)">Probar</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" @click="eliminarImpresora(item)">Eliminar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <form v-if="editando" class="card mb-3" @submit.prevent="edicion.put(route('impresion.impresoras.update', editando))">
                    <div class="card-header">Editar impresora</div>
                    <div class="card-body row g-2">
                        <div class="col-md-6"><input v-model="edicion.nombre" class="form-control" placeholder="Nombre" required /></div>
                        <div class="col-md-6"><input v-model="edicion.nombre_sistema" class="form-control" placeholder="Nombre en Windows" /></div>
                        <div class="col-md-4"><input v-model="edicion.ip" class="form-control" placeholder="IP" /></div>
                        <div class="col-md-2"><input v-model="edicion.puerto" type="number" class="form-control" /></div>
                        <div class="col-md-3">
                            <select v-model="edicion.ancho_papel" class="form-select"><option :value="80">80 mm</option><option :value="58">58 mm</option></select>
                        </div>
                        <div class="col-md-3 form-check mt-2"><input id="activa" v-model="edicion.activo" class="form-check-input" type="checkbox" /><label class="form-check-label" for="activa">Activa</label></div>
                        <div class="col-12"><button class="btn btn-primary" :disabled="edicion.processing">Guardar</button></div>
                    </div>
                </form>
                <div class="card">
                    <div class="card-header">Cola reciente</div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>#</th><th>Tipo</th><th>Estado</th><th>Error</th></tr></thead>
                            <tbody>
                                <tr v-if="!trabajos.length"><td colspan="4" class="text-muted">Sin trabajos.</td></tr>
                                <tr v-for="job in trabajos" :key="job.id">
                                    <td>{{ job.id }}</td><td>{{ job.tipo }}</td><td>{{ job.estado }}</td><td>{{ job.error_mensaje }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <form class="card mb-3" @submit.prevent="impresora.post(route('impresion.impresoras.store'))">
                    <div class="card-header">Nueva impresora</div>
                    <div class="card-body">
                        <input v-model="impresora.nombre" class="form-control mb-2" placeholder="Nombre" required />
                        <input v-model="impresora.nombre_sistema" class="form-control mb-2" placeholder="Nombre en Windows (EPSON TM-T20)" />
                        <input v-model="impresora.ip" class="form-control mb-2" placeholder="IP de red, si aplica" />
                        <button class="btn btn-primary" :disabled="impresora.processing">Guardar</button>
                    </div>
                </form>
                <form class="card mb-3" @submit.prevent="token.post(route('impresion.token'))">
                    <div class="card-header">Agente local</div>
                    <div class="card-body">
                        <p class="small text-muted">En la PC de recepción: copie el token en <code>print-agent/.env</code> y ejecute <code>php print-agent/run.php</code>.</p>
                        <input v-model="token.nombre" class="form-control mb-2" placeholder="Nombre del agente" required />
                        <button class="btn btn-outline-primary" :disabled="token.processing">Generar token</button>
                        <ul class="list-group list-group-flush mt-3">
                            <li v-for="agente in agentes" :key="agente.id" class="list-group-item d-flex justify-content-between">
                                <span>{{ agente.nombre }}</span>
                                <span class="badge" :class="agente.en_linea ? 'text-bg-success' : 'text-bg-secondary'">{{ agente.en_linea ? 'En línea' : 'Fuera' }}</span>
                            </li>
                        </ul>
                    </div>
                </form>
            </div>
        </div>
        <TicketPrintModal ref="ticketRef" />
    </AuthenticatedLayout>
</template>
