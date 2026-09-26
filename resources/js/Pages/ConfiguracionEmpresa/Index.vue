<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { POS_PALETTES, applyPosTheme } from '@/composables/useTheme';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ config: Object });
const palettes = POS_PALETTES;

const form = useForm({
    nombre_empresa: props.config?.nombre_empresa || '',
    nombre_corto: props.config?.nombre_corto || '',
    nombre_largo: props.config?.nombre_largo || '',
    rfc: props.config?.rfc || '',
    telefono: props.config?.telefono || '',
    email: props.config?.email || '',
    direccion: props.config?.direccion || '',
    ciudad: props.config?.ciudad || '',
    estado: props.config?.estado || '',
    color_primario: props.config?.color_primario || '#1a365d',
    color_secundario: props.config?.color_secundario || '#64748b',
    tema_modo: props.config?.tema_modo || 'claro',
    logo: null,
});

const selectedPaletteId = computed(() => {
    const match = palettes.find(
        (palette) =>
            palette.color_primario.toLowerCase() === String(form.color_primario).toLowerCase()
            && palette.color_secundario.toLowerCase() === String(form.color_secundario).toLowerCase(),
    );
    return match?.id ?? null;
});

const previewTheme = () => {
    applyPosTheme({
        color_primario: form.color_primario,
        color_secundario: form.color_secundario,
        tema_modo: form.tema_modo,
    });
};

const applyPalette = (palette) => {
    form.color_primario = palette.color_primario;
    form.color_secundario = palette.color_secundario;
    previewTheme();
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('configuracion-empresa.update'), { forceFormData: true });
};
</script>

<template>
    <Head title="Empresa" />
    <AuthenticatedLayout>
        <template #header>Configuración de empresa</template>
        <form @submit.prevent="submit">
            <div class="card card-body mb-3">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nombre</label><input v-model="form.nombre_empresa" class="form-control" required /></div>
                    <div class="col-md-3"><label class="form-label">Nombre corto</label><input v-model="form.nombre_corto" class="form-control" /></div>
                    <div class="col-md-3"><label class="form-label">RFC</label><input v-model="form.rfc" class="form-control" /></div>
                    <div class="col-md-6"><label class="form-label">Nombre largo</label><input v-model="form.nombre_largo" class="form-control" /></div>
                    <div class="col-md-3"><label class="form-label">Teléfono</label><input v-model="form.telefono" class="form-control" /></div>
                    <div class="col-md-3"><label class="form-label">Correo</label><input v-model="form.email" type="email" class="form-control" /></div>
                    <div class="col-12"><label class="form-label">Dirección</label><textarea v-model="form.direccion" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-6"><label class="form-label">Logo</label><input type="file" class="form-control" accept="image/*" @input="form.logo = $event.target.files[0]" /></div>
                    <div class="col-12" v-if="config?.logo_url"><img :src="config.logo_url" alt="" height="48" /></div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">Apariencia</div>
                <div class="card-body">
                    <label class="form-label">Paleta del sistema</label>
                    <p class="text-muted small mb-3">El color primario tiñe botones y el menú activo. El secundario colorea el menú lateral.</p>
                    <div class="row g-3 mb-4">
                        <div v-for="palette in palettes" :key="palette.id" class="col-6 col-md-4 col-lg-2">
                            <button
                                type="button"
                                class="palette-card w-100 text-start"
                                :class="{ active: selectedPaletteId === palette.id }"
                                @click="applyPalette(palette)"
                            >
                                <div class="palette-swatches">
                                    <span :style="{ background: palette.color_primario }" />
                                    <span :style="{ background: palette.color_secundario }" />
                                </div>
                                <div class="fw-semibold small">{{ palette.nombre }}</div>
                                <div class="text-muted" style="font-size: 0.7rem">{{ palette.descripcion }}</div>
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Color primario (acentos)</label>
                            <div class="input-group">
                                <input v-model="form.color_primario" type="color" class="form-control form-control-color" @input="previewTheme" />
                                <input v-model="form.color_primario" type="text" class="form-control" @change="previewTheme" />
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Color secundario (menú)</label>
                            <div class="input-group">
                                <input v-model="form.color_secundario" type="color" class="form-control form-control-color" @input="previewTheme" />
                                <input v-model="form.color_secundario" type="text" class="form-control" @change="previewTheme" />
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Tema</label>
                            <select v-model="form.tema_modo" class="form-select" @change="previewTheme">
                                <option value="claro">Claro</option>
                                <option value="oscuro">Oscuro</option>
                                <option value="sistema">Según el sistema</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">La vista previa se aplica al instante. Guarda para que quede en este hotel.</p>
                </div>
            </div>

            <button class="btn btn-primary" :disabled="form.processing">Guardar</button>
        </form>
    </AuthenticatedLayout>
</template>
