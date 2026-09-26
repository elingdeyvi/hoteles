import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

const syncCsrfFromMeta = () => {
    const meta = document.head?.querySelector('meta[name="csrf-token"]');
    if (meta?.content) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = meta.content;
    }
};

syncCsrfFromMeta();

// Mantener el token al día en cada petición (Inertia no actualiza el meta al navegar).
window.axios.interceptors.request.use((config) => {
    syncCsrfFromMeta();

    // Preferir cookie XSRF de Laravel si está disponible.
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    if (match?.[1]) {
        config.headers['X-XSRF-TOKEN'] = decodeURIComponent(match[1]);
    }

    return config;
});
