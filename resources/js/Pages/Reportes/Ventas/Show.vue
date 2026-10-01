<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

Chart.register(...registerables);

const props = defineProps({
    tipo: { type: String, required: true },
    meta: { type: Object, required: true },
    filtros: { type: Object, required: true },
    cajas: { type: Array, default: () => [] },
    huespedes: { type: Array, default: () => [] },
    vendedores: { type: Array, default: () => [] },
    metodosPago: { type: Array, default: () => [] },
    productos: { type: Array, default: () => [] },
    resultado: { type: Object, required: true },
    exportUrl: { type: String, required: true },
});

const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const formatCell = (col, row) => {
    const val = row[col.key];
    if (['total', 'ticket_promedio', 'venta', 'costo', 'utilidad'].includes(col.key)) return money(val);
    return val ?? '';
};

const idOrEmpty = (v) => (v === '' || v === null || v === undefined ? '' : String(v));
const formFromFiltros = (f) => ({
    fecha_inicio: f.fecha_inicio,
    fecha_fin: f.fecha_fin,
    tipo_cargo: f.tipo_cargo || '',
    caja_id: idOrEmpty(f.caja_id),
    huesped_id: idOrEmpty(f.huesped_id),
    user_id: idOrEmpty(f.user_id),
    producto_id: idOrEmpty(f.producto_id),
    metodo_pago: f.metodo_pago || '',
});

const form = ref(formFromFiltros(props.filtros));
const busquedaProducto = ref('');

watch(() => props.filtros, (f) => { form.value = formFromFiltros(f); }, { deep: true });

const productosFiltrados = computed(() => {
    const q = busquedaProducto.value.trim().toLowerCase();
    if (!q) return props.productos;
    return props.productos.filter((p) => String(p.name || '').toLowerCase().includes(q) || String(p.sku || '').toLowerCase().includes(q));
});

const queryParams = () => ({
    fecha_inicio: form.value.fecha_inicio,
    fecha_fin: form.value.fecha_fin,
    tipo_cargo: form.value.tipo_cargo || undefined,
    caja_id: form.value.caja_id || undefined,
    huesped_id: form.value.huesped_id || undefined,
    user_id: form.value.user_id || undefined,
    producto_id: form.value.producto_id || undefined,
    metodo_pago: form.value.metodo_pago || undefined,
});

const aplicar = () => {
    router.get(route('reportes.ventas.show', props.tipo), queryParams(), { preserveState: true, preserveScroll: true, replace: true });
};

const limpiar = () => {
    form.value = { ...formFromFiltros(props.filtros), tipo_cargo: '', caja_id: '', huesped_id: '', user_id: '', producto_id: '', metodo_pago: '' };
    busquedaProducto.value = '';
    aplicar();
};

const chartType = computed(() => {
    if (['por_tipo', 'por_metodo_pago', 'por_caja'].includes(props.tipo)) return 'doughnut';
    if (['por_producto', 'por_cliente', 'por_vendedor', 'por_utilidad'].includes(props.tipo)) return 'bar-h';
    return 'bar';
});

const canvasRef = ref(null);
let chartInstance = null;
const destroyChart = () => { chartInstance?.destroy(); chartInstance = null; };

const renderChart = () => {
    destroyChart();
    if (!canvasRef.value) return;
    const labels = props.resultado.chart?.labels || [];
    const totales = props.resultado.chart?.totales || [];
    const cantidades = props.resultado.chart?.cantidades || [];
    if (!labels.length) return;
    const color = getComputedStyle(document.documentElement).getPropertyValue('--pos-primary').trim() || '#1a365d';
    const isDoughnut = chartType.value === 'doughnut';
    const isHorizontal = chartType.value === 'bar-h';
    chartInstance = new Chart(canvasRef.value, {
        type: isDoughnut ? 'doughnut' : 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Total',
                data: totales,
                backgroundColor: isDoughnut ? ['#1a365d', '#0f766e', '#d97706', '#b91c1c', '#4f46e5', '#64748b', '#0891b2', '#ca8a04'] : color,
                borderWidth: 0,
                borderRadius: isDoughnut ? 0 : 6,
                maxBarThickness: isHorizontal ? 26 : 40,
            }],
        },
        options: {
            indexAxis: isHorizontal ? 'y' : 'x',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: isDoughnut, position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => ` ${money(ctx.raw)}` } },
            },
            scales: isDoughnut ? {} : {
                x: isHorizontal ? { beginAtZero: true } : {},
                y: !isHorizontal ? { beginAtZero: true } : {},
            },
        },
    });
};

onMounted(renderChart);
watch(() => props.resultado, renderChart, { deep: true });
onBeforeUnmount(destroyChart);

const columns = computed(() => props.resultado.columns || []);
const rows = computed(() => props.resultado.rows || []);
const resumen = computed(() => props.resultado.resumen || { total: 0, cantidad: 0, ticket_promedio: 0 });
const highlight = (campo) => ({ por_producto: 'producto', por_cliente: 'huesped', por_metodo_pago: 'metodo', por_vendedor: 'vendedor', por_caja: 'caja', por_tipo: 'tipo' }[props.tipo] === campo);
</script>

<template>
    <Head :title="meta.titulo" />
    <AuthenticatedLayout>
        <template #header>{{ meta.titulo }}</template>
        <div class="mb-3">
            <Link :href="route('reportes.ventas.index')" class="small">Todos los reportes de ventas</Link>
        </div>
        <div class="card mb-3">
            <div class="card-header py-2"><h3 class="card-title mb-0 small fw-semibold">Filtros</h3></div>
            <div class="card-body">
                <form class="row g-2 align-items-end" @submit.prevent="aplicar">
                    <div class="col-6 col-md-2"><label class="form-label mb-0 small">Desde</label><input v-model="form.fecha_inicio" type="date" class="form-control form-control-sm" required /></div>
                    <div class="col-6 col-md-2"><label class="form-label mb-0 small">Hasta</label><input v-model="form.fecha_fin" type="date" class="form-control form-control-sm" required /></div>
                    <div class="col-6 col-md-2" :class="{ 'filter-focus': highlight('tipo') }">
                        <label class="form-label mb-0 small">Tipo de cargo</label>
                        <select v-model="form.tipo_cargo" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="habitacion">Habitación</option>
                            <option value="extra">Extra</option>
                            <option value="pos">Producto</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2" :class="{ 'filter-focus': highlight('caja') }">
                        <label class="form-label mb-0 small">Caja</label>
                        <select v-model="form.caja_id" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <option v-for="c in cajas" :key="c.id" :value="String(c.id)">{{ c.nombre }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2" :class="{ 'filter-focus': highlight('huesped') }">
                        <label class="form-label mb-0 small">Huésped</label>
                        <select v-model="form.huesped_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option v-for="h in huespedes" :key="h.id" :value="String(h.id)">{{ h.nombre }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2" :class="{ 'filter-focus': highlight('vendedor') }">
                        <label class="form-label mb-0 small">Usuario</label>
                        <select v-model="form.user_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option v-for="u in vendedores" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2" :class="{ 'filter-focus': highlight('metodo') }">
                        <label class="form-label mb-0 small">Método de pago</label>
                        <select v-model="form.metodo_pago" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option v-for="m in metodosPago" :key="m" :value="m">{{ m }}</option>
                        </select>
                    </div>
                    <div class="col-md-4" :class="{ 'filter-focus': highlight('producto') }">
                        <label class="form-label mb-0 small">Producto</label>
                        <div class="input-group input-group-sm">
                            <input v-model="busquedaProducto" type="search" class="form-control" placeholder="Buscar…" style="max-width: 7rem" />
                            <select v-model="form.producto_id" class="form-select">
                                <option value="">Todos</option>
                                <option v-for="p in productosFiltrados" :key="p.id" :value="String(p.id)">{{ p.sku ? p.sku + ' — ' : '' }}{{ p.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-2"><button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Filtrar</button></div>
                    <div class="col-6 col-md-2"><button type="button" class="btn btn-sm btn-outline-secondary w-100" @click="limpiar">Limpiar</button></div>
                    <div class="col-6 col-md-2"><a :href="exportUrl" class="btn btn-sm btn-outline-success w-100"><i class="fa-solid fa-file-csv me-1"></i> Exportar CSV</a></div>
                </form>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4"><div class="small-box text-bg-primary mb-0"><div class="inner"><h3>{{ money(tipo === 'por_utilidad' ? resumen.venta : resumen.total) }}</h3><p>{{ tipo === 'por_utilidad' ? 'Venta' : 'Total del periodo' }}</p></div></div></div>
            <div class="col-md-4"><div class="small-box text-bg-info mb-0"><div class="inner"><h3>{{ tipo === 'por_utilidad' ? money(resumen.total) : resumen.cantidad }}</h3><p>{{ tipo === 'por_utilidad' ? 'Utilidad' : 'Registros' }}</p></div></div></div>
            <div class="col-md-4"><div class="small-box text-bg-success mb-0"><div class="inner"><h3>{{ tipo === 'por_utilidad' ? Number(resumen.ticket_promedio || 0).toFixed(1) + '%' : money(resumen.ticket_promedio) }}</h3><p>{{ tipo === 'por_utilidad' ? 'Margen' : 'Ticket promedio' }}</p></div></div></div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Gráfica</h3></div>
            <div class="card-body">
                <div style="height: 320px"><canvas ref="canvasRef"></canvas></div>
                <div v-if="!(resultado.chart?.labels || []).length" class="text-center text-muted py-4">Sin datos para graficar en el periodo seleccionado.</div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Detalle</h3>
                <span class="small text-muted">{{ rows.length }} filas</span>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm table-striped mb-0 align-middle">
                    <thead><tr><th v-for="col in columns" :key="col.key">{{ col.label }}</th></tr></thead>
                    <tbody>
                        <tr v-for="(row, idx) in rows" :key="idx"><td v-for="col in columns" :key="col.key">{{ formatCell(col, row) }}</td></tr>
                        <tr v-if="!rows.length"><td :colspan="columns.length || 1" class="text-center text-muted py-4">Sin resultados</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.filter-focus :deep(.form-select),
.filter-focus :deep(.form-control),
.filter-focus .form-select,
.filter-focus .form-control,
.filter-focus .input-group {
    border-color: var(--pos-primary, #1a365d);
    box-shadow: 0 0 0 0.15rem rgba(26, 54, 93, 0.15);
}
.filter-focus .form-label { color: var(--pos-primary, #1a365d); font-weight: 600; }
</style>
