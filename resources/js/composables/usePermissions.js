import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();
    const permissions = computed(() => page.props.auth?.permissions ?? []);
    const roles = computed(() => page.props.auth?.roles ?? []);

    const can = (perm) => permissions.value.includes(perm);
    const hasRole = (role) => roles.value.includes(role);

    return { permissions, roles, can, hasRole };
}
