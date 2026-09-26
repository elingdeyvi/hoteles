<script setup>
import { computed, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmar' },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirmar' },
    cancelLabel: { type: String, default: 'Cancelar' },
    /** primary | success | danger | warning */
    variant: { type: String, default: 'primary' },
    icon: { type: String, default: 'fa-solid fa-circle-question' },
    processing: { type: Boolean, default: false },
    /** Solo aviso: un botón de cerrar */
    alertOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'confirm']);

const btnClass = computed(() => {
    const map = {
        primary: 'btn-primary',
        success: 'btn-success',
        danger: 'btn-danger',
        warning: 'btn-warning',
    };
    return map[props.variant] || 'btn-primary';
});

const iconTone = computed(() => {
    const map = {
        primary: 'text-primary',
        success: 'text-success',
        danger: 'text-danger',
        warning: 'text-warning',
    };
    return map[props.variant] || 'text-primary';
});

const setBodyLock = (locked) => {
    document.body.classList.toggle('modal-open', locked);
    document.body.style.overflow = locked ? 'hidden' : '';
};

watch(() => props.show, (open) => setBodyLock(open), { immediate: true });
onUnmounted(() => setBodyLock(false));

const handleClose = () => {
    if (props.processing) return;
    emit('close');
};

const handleConfirm = () => {
    if (props.processing) return;
    emit('confirm');
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="modal fade show d-block confirm-modal"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            @keydown.esc.prevent="handleClose"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-body p-4">
                        <div class="d-flex gap-3 align-items-start">
                            <div class="confirm-modal__icon" :class="iconTone">
                                <i :class="icon"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="mb-2 fw-semibold">{{ title }}</h5>
                                <p v-if="message" class="mb-0 text-secondary" style="white-space: pre-line">{{ message }}</p>
                                <slot />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4 gap-2">
                        <template v-if="alertOnly">
                            <button type="button" class="btn btn-primary px-4" @click="handleClose">Entendido</button>
                        </template>
                        <template v-else>
                            <button type="button" class="btn btn-outline-secondary" :disabled="processing" @click="handleClose">
                                {{ cancelLabel }}
                            </button>
                            <button type="button" class="btn px-4" :class="btnClass" :disabled="processing" @click="handleConfirm">
                                <span v-if="processing" class="spinner-border spinner-border-sm me-1" />
                                {{ confirmLabel }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="show" class="modal-backdrop fade show" />
    </Teleport>
</template>

<style scoped>
.confirm-modal__icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.85rem;
    background: rgba(15, 23, 42, 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.confirm-modal .modal-content {
    border-radius: 1rem;
}
</style>
