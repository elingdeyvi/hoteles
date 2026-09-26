<script setup>
import axios from 'axios';
import { nextTick, ref } from 'vue';

const abierto = ref(false);
const lineas = ref([]);
const aviso = ref('');
const codigo = ref('');
const titulo = ref('Ticket');
const imprimiendo = ref(false);

const solicitar = async (url, nombre = 'Ticket') => {
    titulo.value = nombre;
    imprimiendo.value = true;
    aviso.value = '';
    try {
        const { data } = await axios.post(url);
        lineas.value = data.lineas || [];
        aviso.value = data.message || '';
        codigo.value = data.codigo || '';
        abierto.value = true;
        if (codigo.value === 'sin_agente' || codigo.value === 'sin_impresora') {
            await nextTick();
            window.print();
        }
    } catch (error) {
        lineas.value = [];
        codigo.value = 'error';
        aviso.value = error.response?.data?.message || 'No se pudo imprimir el ticket.';
        abierto.value = true;
    } finally {
        imprimiendo.value = false;
    }
};

const esteEquipo = () => window.print();

defineExpose({ solicitar, imprimiendo });
</script>

<template>
    <Teleport to="body">
        <div v-if="abierto" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45); z-index: 2200">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ titulo }}</h5>
                        <button type="button" class="btn-close" aria-label="Cerrar" @click="abierto = false"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="codigo === 'en_cola'" class="alert alert-success py-2">Ticket en cola. El agente local lo imprimirá en la térmica.</div>
                        <div v-else-if="codigo === 'impreso'" class="alert alert-success py-2">{{ aviso }}</div>
                        <div v-else-if="aviso" class="alert alert-warning py-2">{{ aviso }}</div>
                        <pre class="hotel-ticket-print">{{ lineas.join('\n') }}</pre>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="abierto = false">Cerrar</button>
                        <button type="button" class="btn btn-primary" @click="esteEquipo">Imprimir en este equipo</button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style>
.hotel-ticket-print {
    width: 46ch;
    margin: 0 auto;
    padding: 12px 8px;
    background: #fff;
    color: #000;
    border: 1px solid #ddd;
    font-family: "Courier New", Courier, monospace;
    font-size: 13px;
    line-height: 1.35;
    white-space: pre;
}
@media print {
    body * { visibility: hidden !important; }
    .hotel-ticket-print, .hotel-ticket-print * { visibility: visible !important; }
    .hotel-ticket-print {
        position: absolute;
        left: 0;
        top: 0;
        border: 0;
        width: 80mm;
    }
}
</style>
