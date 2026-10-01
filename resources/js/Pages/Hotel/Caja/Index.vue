<script setup>
import TicketPrintModal from '@/Components/TicketPrintModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ apertura: Object, resumen: Object, cortes: Array, movimientos: Array });

const abrir = useForm({ fondo_inicial: 0, observaciones: '' });
const corte = useForm({ tipo: 'X', total_real: '', observaciones: '', conteo: {} });
const movimiento = useForm({ tipo: 'ingreso', concepto: '', monto: '', referencia: '' });
const ticketRef = ref(null);
const denominaciones = [
    ['b1000', '1000', 1000], ['b500', '500', 500], ['b200', '200', 200], ['b100', '100', 100],
    ['b50', '50', 50], ['b20', '20', 20], ['m20', '20', 20], ['m10', '10', 10],
    ['m5', '5', 5], ['m2', '2', 2], ['m1', '1', 1], ['c50', '0.50', 0.5], ['c20', '0.20', 0.2], ['c10', '0.10', 0.1],
];
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const contado = computed(() => denominaciones.reduce((sum, [clave, , valor]) => sum + Number(corte.conteo[clave] || 0) * valor, 0));

const enviarCorte = (tipo) => {
    corte.tipo = tipo;
    if (contado.value > 0) corte.total_real = contado.value.toFixed(2);
    if (tipo === 'Z' && (corte.total_real === '' || corte.total_real === null)) {
        corte.setError('total_real', 'Indique el efectivo contado para el corte Z.');
        return;
    }
    const aviso = tipo === 'Z'
        ? 'El corte Z cierra la caja. No se podrá cobrar hasta una nueva apertura.'
        : 'El corte X deja la caja abierta.';
    if (window.confirm(aviso)) corte.post(route('caja.cortar'));
};
const quitarMovimiento = (item) => {
    if (window.confirm('¿Quitar este movimiento?')) router.delete(route('caja.movimientos.destroy', item.id));
};
</script>

<template>
    <Head title="Caja" />
    <AuthenticatedLayout>
        <template #header>Caja</template>

        <div v-if="!apertura" class="row g-3">
            <div class="col-lg-5">
                <form class="card" @submit.prevent="abrir.post(route('caja.abrir'))">
                    <div class="card-header">Apertura obligatoria</div>
                    <div class="card-body">
                        <p class="text-muted">Sin caja abierta no se venden productos ni se registran cobros.</p>
                        <label class="form-label">Fondo inicial</label>
                        <input v-model="abrir.fondo_inicial" type="number" min="0" step="0.01" class="form-control mb-2" required />
                        <div v-if="abrir.errors.fondo_inicial" class="text-danger small mb-2">{{ abrir.errors.fondo_inicial }}</div>
                        <textarea v-model="abrir.observaciones" class="form-control mb-3" rows="2" placeholder="Observaciones"></textarea>
                        <button class="btn btn-primary" :disabled="abrir.processing">Aperturar caja</button>
                    </div>
                </form>
            </div>
        </div>

        <div v-else class="row g-3">
            <div class="col-lg-7">
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between">
                        <span>Turno abierto</span>
                        <span>{{ apertura.fecha_apertura }} · {{ apertura.usuario }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4"><div class="border rounded p-2"><div class="text-muted small">Fondo</div><strong>{{ money(resumen.fondo_inicial) }}</strong></div></div>
                            <div class="col-md-4"><div class="border rounded p-2"><div class="text-muted small">Ventas</div><strong>{{ money(resumen.total_consumos) }}</strong></div></div>
                            <div class="col-md-4"><div class="border rounded p-2"><div class="text-muted small">Cobros</div><strong>{{ money(resumen.total_cobros) }}</strong></div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small">Efectivo</div>{{ money(resumen.total_efectivo) }}</div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small">Tarjeta</div>{{ money(resumen.total_tarjeta) }}</div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small">Transferencia</div>{{ money(resumen.total_transferencia) }}</div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small">Otros</div>{{ money(resumen.total_otros) }}</div></div>
                            <div class="col-md-6"><div class="border rounded p-2 text-success"><div class="text-muted small">Ingresos</div><strong>{{ money(resumen.total_ingresos) }}</strong></div></div>
                            <div class="col-md-6"><div class="border rounded p-2 text-danger"><div class="text-muted small">Egresos</div><strong>{{ money(resumen.total_egresos) }}</strong></div></div>
                        </div>
                        <p class="mt-3 mb-0">Efectivo esperado en cajón: <strong>{{ money(resumen.total_esperado) }}</strong> (fondo + efectivo + ingresos − egresos).</p>
                    </div>
                </div>

                <form class="card mb-3" @submit.prevent="movimiento.post(route('caja.movimientos.store'), { onSuccess: () => movimiento.reset() })">
                    <div class="card-header">Ingreso o egreso</div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select v-model="movimiento.tipo" class="form-select">
                                    <option value="ingreso">Ingreso</option>
                                    <option value="egreso">Egreso</option>
                                </select>
                            </div>
                            <div class="col-md-4"><input v-model="movimiento.concepto" class="form-control" placeholder="Concepto" required /></div>
                            <div class="col-md-2"><input v-model="movimiento.monto" type="number" min="0.01" step="0.01" class="form-control" placeholder="Monto" required /></div>
                            <div class="col-md-3"><input v-model="movimiento.referencia" class="form-control" placeholder="Referencia" /></div>
                        </div>
                        <button class="btn mt-2" :class="movimiento.tipo === 'ingreso' ? 'btn-success' : 'btn-danger'" :disabled="movimiento.processing">Registrar</button>
                    </div>
                </form>
            </div>
            <div class="col-lg-5">
                <form class="card" @submit.prevent>
                    <div class="card-header">Corte de caja</div>
                    <div class="card-body">
                        <p class="small text-muted">El corte X informa y deja la caja abierta. El corte Z cierra el turno. Cuente el efectivo; el total se calcula solo.</p>
                        <div class="row g-2 mb-2">
                            <div v-for="[clave, etiqueta] in denominaciones" :key="clave" class="col-4">
                                <label class="form-label small mb-0">${{ etiqueta }}</label>
                                <input v-model="corte.conteo[clave]" type="number" min="0" step="1" class="form-control form-control-sm" />
                            </div>
                        </div>
                        <p class="mb-2">Contado: <strong>{{ money(contado || corte.total_real) }}</strong></p>
                        <input v-model="corte.total_real" type="number" min="0" step="0.01" class="form-control mb-2" placeholder="O escriba el efectivo contado" />
                        <div v-if="corte.errors.total_real" class="text-danger small mb-2">{{ corte.errors.total_real }}</div>
                        <textarea v-model="corte.observaciones" class="form-control mb-3" rows="2" placeholder="Observaciones"></textarea>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary" :disabled="corte.processing" @click="enviarCorte('X')">Corte X</button>
                            <button type="button" class="btn btn-danger" :disabled="corte.processing" @click="enviarCorte('Z')">Corte Z</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Ingresos y egresos</div>
            <div class="card-body p-0 table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Concepto</th><th>Usuario</th><th class="text-end">Monto</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="item in movimientos" :key="item.id">
                            <td>{{ item.fecha }}</td>
                            <td><span class="badge" :class="item.tipo === 'ingreso' ? 'text-bg-success' : 'text-bg-danger'">{{ item.tipo === 'ingreso' ? 'Ingreso' : 'Egreso' }}</span></td>
                            <td>{{ item.concepto }} <small class="text-muted">{{ item.referencia }}</small></td>
                            <td>{{ item.usuario }}</td>
                            <td class="text-end" :class="item.tipo === 'ingreso' ? 'text-success' : 'text-danger'">{{ item.tipo === 'egreso' ? '-' : '' }}{{ money(item.monto) }}</td>
                            <td class="text-end"><button v-if="item.abierta" type="button" class="btn btn-sm btn-outline-danger" @click="quitarMovimiento(item)">Quitar</button></td>
                        </tr>
                        <tr v-if="!movimientos.length"><td colspan="6" class="text-center text-muted py-3">Sin movimientos</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Historial de cortes</div>
            <div class="card-body p-0 table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Tipo</th><th>Fecha</th><th>Usuario</th><th>Ventas</th><th>Ingresos</th><th>Egresos</th><th>Esperado</th><th>Contado</th><th>Diferencia</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="item in cortes" :key="item.id">
                            <td><span class="badge" :class="item.tipo === 'Z' ? 'text-bg-danger' : 'text-bg-primary'">{{ item.tipo }}</span></td>
                            <td>{{ item.fecha_corte }}</td>
                            <td>{{ item.usuario }}</td>
                            <td>{{ money(item.total_consumos) }}</td>
                            <td>{{ money(item.total_ingresos) }}</td>
                            <td>{{ money(item.total_egresos) }}</td>
                            <td>{{ money(item.total_esperado) }}</td>
                            <td>{{ item.total_real === null ? '—' : money(item.total_real) }}</td>
                            <td>{{ item.diferencia === null ? '—' : money(item.diferencia) }}</td>
                            <td><button type="button" class="btn btn-sm btn-outline-dark" @click="ticketRef?.solicitar(route('caja.cortes.imprimir', item.id), 'Corte ' + item.tipo)">Imprimir</button></td>
                        </tr>
                        <tr v-if="!cortes.length"><td colspan="10" class="text-center text-muted py-3">Sin cortes</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <TicketPrintModal ref="ticketRef" />
    </AuthenticatedLayout>
</template>
