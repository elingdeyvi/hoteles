<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    reportes: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Reportes de ventas" />
    <AuthenticatedLayout>
        <template #header>Reportes de ventas</template>
        <p class="text-muted mb-3">
            Elige un reporte. En cada uno puedes filtrar por fechas, producto, huésped, método de pago, caja y usuario, y exportar a CSV.
        </p>
        <div class="row g-3">
            <div v-for="r in reportes" :key="r.key" class="col-md-6 col-lg-4">
                <Link :href="r.url" class="card h-100 text-decoration-none text-body shadow-sm report-card">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <span class="report-icon"><i class="fa-solid" :class="r.icono"></i></span>
                            <div>
                                <h5 class="mb-1">{{ r.titulo }}</h5>
                                <p class="text-muted small mb-0">{{ r.descripcion }}</p>
                            </div>
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.report-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
}
.report-card:hover {
    border-color: var(--pos-primary, #1a365d);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    transform: translateY(-2px);
}
.report-icon {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #e8f3fb;
    color: var(--pos-primary, #1a365d);
    flex-shrink: 0;
}
</style>
