<script setup>
import { computed, nextTick, onUnmounted, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: String, default: 'md' },
    processing: { type: Boolean, default: false },
    submitLabel: { type: String, default: 'Guardar' },
    cancelLabel: { type: String, default: 'Cancelar' },
    hideFooter: { type: Boolean, default: false },
    stack: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const dialogClass = computed(() => {
    const map = {
        sm: 'modal-sm',
        md: '',
        lg: 'modal-lg',
        xl: 'modal-xl',
    };
    return map[props.size] || '';
});

const setBodyLock = (locked) => {
    if (locked) {
        document.body.classList.add('modal-open');
        document.body.style.overflow = 'hidden';
        return;
    }
    nextTick(() => {
        if (!document.querySelector('.pos-modal')) {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }
    });
};

watch(
    () => props.show,
    (open) => setBodyLock(open),
    { immediate: true },
);

onUnmounted(() => setBodyLock(false));

/**
 * Los escáneres de barras actúan como teclado y envían Enter al final.
 * Eso no debe guardar el formulario; solo el botón Guardar.
 */
const onEnterKey = (e) => {
    const tag = e.target?.tagName;
    if (tag === 'TEXTAREA') return;
    if (tag === 'INPUT' || tag === 'SELECT') {
        e.preventDefault();
    }
};

const handleSubmit = () => {
    if (props.processing) return;
    emit('submit');
};

const handleClose = () => {
    if (props.processing) return;
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="modal fade show d-block pos-modal"
            :class="{ 'pos-modal-stack': stack }"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
        >
            <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" :class="dialogClass">
                <!-- form como modal-content: header/footer fijos y body con scroll -->
                <form
                    class="modal-content"
                    @submit.prevent="handleSubmit"
                    @keydown.enter="onEnterKey"
                >
                    <div class="modal-header">
                        <h5 class="modal-title">{{ title }}</h5>
                        <button
                            type="button"
                            class="btn-close"
                            aria-label="Cerrar"
                            :disabled="processing"
                            @click="handleClose"
                        />
                    </div>
                    <div class="modal-body">
                        <slot />
                    </div>
                    <div v-if="!hideFooter" class="modal-footer">
                        <slot name="footer">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                :disabled="processing"
                                @click="handleClose"
                            >
                                {{ cancelLabel }}
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="processing">
                                <span v-if="processing" class="spinner-border spinner-border-sm me-1" />
                                {{ submitLabel }}
                            </button>
                        </slot>
                    </div>
                </form>
            </div>
        </div>
        <div v-if="show" class="modal-backdrop fade show pos-modal-backdrop" :class="{ 'pos-modal-stack': stack }" />
    </Teleport>
</template>
