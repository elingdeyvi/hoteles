<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ apertura: Object, resumen: Object, cortes: Array });

const abrir = useForm({ fondo_inicial: 0, observaciones: '' });
const corte = useForm({ tipo: 'X', total_real: '', observaciones: '' });
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });

const enviarCorte = (tipo) => {
    corte.tipo = tipo;
    if (tipo === 'Z' && (corte.total_real === '' || corte.total_real === null)) {
        corte.setError('total_real', 'Indique el efectivo contado para el corte Z.');
        return;
    }
    const aviso = tipo === 'Z'
        ? 'El corte Z cierra la caja. No se podrá cobrar ni cargar consumos hasta una nueva apertura.'
        : 'El corte X deja la caja abierta y solo imprime el avance del turno.';
    if (window.confirm(aviso)) corte.post(route('caja.cortar'));
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
                        <p class="text-muted">Sin caja abierta no se cargan consumos ni se registran cobros.</p>
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
                            <div class="col-md-4"><div class="border rounded p-2"><div class="text-muted small">Consumos POS</div><strong>{{ money(resumen.total_consumos) }}</strong></div></div>
                            <div class="col-md-4"><div class="border rounded p-2"><div class="text-muted small">Cobros</div><strong>{{ money(resumen.total_cobros) }}</strong></div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small">Efectivo</div>{{ money(resumen.total_efectivo) }}</div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small">Tarjeta</div>{{ money(resumen.total_tarjeta) }}</div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small">Transferencia</div>{{ money(resumen.total_transferencia) }}</div></div>
                            <div class="col-md-3"><div class="border rounded p-2"><div class="text-muted small">Otros</div>{{ money(resumen.total_otros) }}</div></div>
                        </div>
                        <p class="mt-3 mb-0">Efectivo esperado en cajón: <strong>{{ money(resumen.total_esperado) }}</strong> (fondo + efectivo cobrado).</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <form class="card" @submit.prevent>
                    <div class="card-header">Cortes</div>
                    <div class="card-body">
                        <p class="small text-muted">El corte X informa y deja la caja abierta. El corte Z cierra el turno.</p>
                        <label class="form-label">Efectivo contado (obligatorio en Z)</label>
                        <input v-model="corte.total_real" type="number" min="0" step="0.01" class="form-control mb-2" />
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
            <div class="card-header">Historial de cortes</div>
            <div class="card-body p-0 table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Tipo</th><th>Fecha</th><th>Usuario</th><th>Consumos</th><th>Cobros</th><th>Esperado</th><th>Contado</th><th>Diferencia</th></tr></thead>
                    <tbody>
                        <tr v-for="item in cortes" :key="item.id">
                            <td><span class="badge" :class="item.tipo === 'Z' ? 'text-bg-danger' : 'text-bg-primary'">{{ item.tipo }}</span></td>
                            <td>{{ item.fecha_corte }}</td>
                            <td>{{ item.usuario }}</td>
                            <td>{{ money(item.total_consumos) }}</td>
                            <td>{{ money(item.total_cobros) }}</td>
                            <td>{{ money(item.total_esperado) }}</td>
                            <td>{{ item.total_real === null ? '—' : money(item.total_real) }}</td>
                            <td>{{ item.diferencia === null ? '—' : money(item.diferencia) }}</td>
                        </tr>
                        <tr v-if="!cortes.length"><td colspan="8" class="text-center text-muted py-3">Sin cortes</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
