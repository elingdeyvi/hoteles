<script setup>
import { ref } from 'vue';
import { abrirWhatsAppWeb, compartirConGestoSistema, descargarBlob } from '@/utils/compartirTicket';

const vacio = () => ({
    visible: false,
    compartiendo: false,
    titulo: 'Compartir',
    fileName: '',
    shareData: null,
    pngBlob: null,
    previewUrl: '',
    canShareFiles: false,
    canUseSystemShare: false,
    telefono: '',
    mensaje: '',
    error: '',
});

const modal = ref(vacio());
const contextoSeguro = typeof window !== 'undefined' && window.isSecureContext;

function abrir(prep) {
    if (modal.value.previewUrl) URL.revokeObjectURL(modal.value.previewUrl);
    modal.value = {
        ...vacio(),
        visible: true,
        titulo: prep.titulo || 'Compartir',
        fileName: prep.fileName,
        shareData: prep.shareData,
        pngBlob: prep.pngBlob,
        previewUrl: URL.createObjectURL(prep.pngBlob),
        canShareFiles: !!prep.canShareFiles,
        canUseSystemShare: !!prep.canUseSystemShare,
        telefono: prep.telefono || '',
        mensaje: prep.mensaje || '',
    };
}

function cerrar() {
    if (modal.value.previewUrl) URL.revokeObjectURL(modal.value.previewUrl);
    modal.value = vacio();
}

function descargar() {
    if (!modal.value.pngBlob || !modal.value.fileName) return;
    descargarBlob(modal.value.pngBlob, modal.value.fileName);
}

function whatsapp() {
    if (modal.value.pngBlob && modal.value.fileName) {
        descargarBlob(modal.value.pngBlob, modal.value.fileName);
    }
    const texto = [modal.value.mensaje, '', `(Imagen adjunta: ${modal.value.fileName})`].join('\n');
    abrirWhatsAppWeb(modal.value.telefono, texto);
    cerrar();
}

async function compartirWindows() {
    if (!modal.value.shareData) return;
    modal.value.compartiendo = true;
    modal.value.error = '';
    try {
        let res = { ok: false };
        if (modal.value.canShareFiles) {
            res = await compartirConGestoSistema(modal.value.shareData);
        }
        if (!res.ok && res.reason !== 'cancelled') {
            res = await compartirConGestoSistema({
                title: modal.value.shareData.title || 'Ticket',
                text: modal.value.mensaje,
            });
            if (res.ok) descargar();
        }
        if (res.ok || res.reason === 'cancelled') {
            cerrar();
            return;
        }
        modal.value.error = 'No se pudo abrir Compartir. El sitio debe estar en HTTPS o en localhost. Puede descargar la imagen o enviarla por WhatsApp.';
    } finally {
        modal.value.compartiendo = false;
    }
}

defineExpose({ abrir });
</script>

<template>
    <Teleport to="body">
        <div v-if="modal.visible" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,.45); z-index: 2100">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ modal.titulo }}</h5>
                        <button type="button" class="btn-close" aria-label="Cerrar" @click="cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p v-if="modal.canUseSystemShare" class="mb-2">
                            Pulse <strong>Compartir</strong> para abrir el menú de Windows y enviar la imagen con el mensaje (WhatsApp, correo, etc.).
                        </p>
                        <div v-else class="alert alert-warning py-2 small mb-2">
                            <template v-if="!contextoSeguro">
                                El menú <strong>Compartir de Windows</strong> solo funciona en HTTPS o en localhost.
                            </template>
                            <template v-else>
                                Este navegador no abre el menú Compartir. Use Chrome o Edge en Windows, o descargue la imagen y envíela por WhatsApp.
                            </template>
                        </div>
                        <div v-if="modal.error" class="alert alert-warning py-2 small">{{ modal.error }}</div>
                        <label class="form-label small fw-semibold mb-1">Mensaje para el cliente</label>
                        <textarea class="form-control form-control-sm mb-3" rows="8" readonly :value="modal.mensaje"></textarea>
                        <img v-if="modal.previewUrl" :src="modal.previewUrl" alt="Ticket" class="img-fluid border rounded d-block mx-auto" style="max-height: 320px; background: #fff" />
                    </div>
                    <div class="modal-footer flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary" @click="cerrar">Cancelar</button>
                        <button v-if="modal.canUseSystemShare" type="button" class="btn btn-success" :disabled="modal.compartiendo || !modal.shareData" @click="compartirWindows">
                            {{ modal.compartiendo ? 'Abriendo…' : 'Compartir' }}
                        </button>
                        <template v-else>
                            <button type="button" class="btn btn-outline-primary" @click="descargar">Descargar imagen</button>
                            <button type="button" class="btn btn-success" @click="whatsapp">WhatsApp</button>
                        </template>
                        <button v-if="modal.canUseSystemShare" type="button" class="btn btn-outline-primary" @click="descargar">Descargar imagen</button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
