import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

export const sesionAviso = reactive({ texto: '' });

router.on('error', (event) => {
    const mensaje = event.detail?.errors?.sesion?.[0];
    if (mensaje) {
        sesionAviso.texto = mensaje;
    }
});

router.on('success', () => {
    sesionAviso.texto = '';
});
