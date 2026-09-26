<script setup>
import FormModal from '@/Components/FormModal.vue';
import { onMounted, reactive, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    routeName: { type: String, required: true },
    /** @type {{ key: string, label: string, type?: string, required?: boolean, options?: Array<{value:any,label:string}>, placeholder?: string }[]} */
    fields: { type: Array, default: () => [] },
    defaults: { type: Object, default: () => ({}) },
    /** Prefijo para código auto si el campo codigo queda vacío */
    codigoPrefix: { type: String, default: '' },
});

const emit = defineEmits(['close', 'created']);

const processing = ref(false);
const errors = reactive({});
const form = reactive({});

const resetForm = () => {
    Object.keys(form).forEach((k) => delete form[k]);
    Object.keys(errors).forEach((k) => delete errors[k]);
    props.fields.forEach((f) => {
        const fromDefaults = props.defaults[f.key];
        form[f.key] =
            fromDefaults !== undefined && fromDefaults !== null
                ? fromDefaults
                : f.type === 'checkbox'
                  ? true
                  : '';
    });
    Object.entries(props.defaults).forEach(([k, v]) => {
        if (!(k in form)) form[k] = v;
    });
};

// Con v-if el modal monta ya abierto: hay que aplicar defaults al montar.
onMounted(() => {
    if (props.show) resetForm();
});

watch(
    () => props.show,
    (open) => {
        if (open) resetForm();
    },
);

const submit = async () => {
    if (processing.value) return;
    Object.keys(errors).forEach((k) => delete errors[k]);
    processing.value = true;
    try {
        const payload = { ...form, activo: true };

        // Código vacío: no enviar; el backend genera uno único (CatalogCodigo).
        if (!String(payload.codigo || '').trim()) {
            delete payload.codigo;
        }

        if (payload.hex && !String(payload.hex).startsWith('#')) {
            payload.hex = `#${payload.hex}`;
        }
        Object.keys(payload).forEach((k) => {
            if (payload[k] === '') payload[k] = null;
        });
        // logo no aplica en alta rápida; evitar reglas de imagen.
        delete payload.logo;

        const { data } = await window.axios.post(route(props.routeName), payload, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const created = data?.data ?? data;
        if (!created?.id) {
            errors._general = data?.message || 'Se guardó, pero no se recibió el registro creado.';
            return;
        }

        emit('created', created);
        emit('close');
    } catch (e) {
        const resp = e.response?.data;
        const respErrors = resp?.errors || {};
        Object.entries(respErrors).forEach(([k, v]) => {
            errors[k] = Array.isArray(v) ? v[0] : v;
        });
        if (!Object.keys(respErrors).length) {
            errors._general =
                resp?.message ||
                (e.response?.status === 419
                    ? 'La sesión expiró. Recargue la página e intente de nuevo.'
                    : 'No se pudo guardar.');
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <FormModal
        :show="show"
        :title="title"
        size="md"
        stack
        :processing="processing"
        submit-label="Guardar"
        @close="emit('close')"
        @submit="submit"
    >
        <div v-if="errors._general" class="alert alert-danger py-2">{{ errors._general }}</div>
        <div v-for="field in fields" :key="field.key" class="mb-3">
            <label v-if="field.type !== 'checkbox'" class="form-label">
                {{ field.label }}
                <span v-if="field.required" class="text-danger">*</span>
            </label>

            <select
                v-if="field.type === 'select'"
                v-model="form[field.key]"
                class="form-select"
                :class="{ 'is-invalid': errors[field.key] }"
                :required="field.required"
            >
                <option value="">— Seleccione —</option>
                <option v-for="opt in field.options || []" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                </option>
            </select>

            <textarea
                v-else-if="field.type === 'textarea'"
                v-model="form[field.key]"
                class="form-control"
                rows="2"
                :class="{ 'is-invalid': errors[field.key] }"
                :placeholder="field.placeholder"
            />

            <div v-else-if="field.type === 'checkbox'" class="form-check">
                <input
                    :id="`qc_${field.key}`"
                    v-model="form[field.key]"
                    type="checkbox"
                    class="form-check-input"
                />
                <label class="form-check-label" :for="`qc_${field.key}`">{{ field.label }}</label>
            </div>

            <input
                v-else
                v-model="form[field.key]"
                :type="field.type || 'text'"
                class="form-control"
                :class="{ 'is-invalid': errors[field.key] }"
                :required="field.required"
                :placeholder="field.placeholder"
                :maxlength="field.maxlength"
            />

            <div v-if="errors[field.key]" class="invalid-feedback d-block">{{ errors[field.key] }}</div>
            <div v-else-if="field.key === 'codigo' && !field.required" class="form-text">
                Si lo deja vacío se genera automáticamente.
            </div>
        </div>
    </FormModal>
</template>
