<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    filtros: { type: Object, default: () => ({ productos: [], usuarios: [] }) },
});

const catalogos = props.filtros;
const tab = ref('generales');
const loading = ref(false);
const data = ref([]);
const resumen = ref(null);
const filtroForm = ref({ fecha_desde: '', fecha_hasta: '', tipo_movimiento: '', producto_id: '', usuario_id: '' });
const motivos = { compra: 'Compra', ajuste: 'Ajuste', merma: 'Merma', venta: 'Venta', devolucion: 'Devolución' };

const tabs = [
    { id: 'generales', label: 'Generales', route: 'generales' },
    { id: 'por-producto', label: 'Por producto', route: 'por-producto' },
    { id: 'por-tipo', label: 'Por tipo', route: 'por-tipo' },
    { id: 'por-usuario', label: 'Por usuario', route: 'por-usuario' },
    { id: 'bajo-stock', label: 'Bajo stock', route: 'bajo-stock' },
    { id: 'resumen', label: 'Resumen', route: 'resumen-general' },
];

const cargar = async () => {
    loading.value = true;
    resumen.value = null;
    data.value = [];
    const current = tabs.find((t) => t.id === tab.value);
    try {
        const { data: resp } = await window.axios.get(route(`reportes.inventario.${current.route}`), { params: filtroForm.value });
        if (tab.value === 'resumen') {
            resumen.value = resp.data;
        } else {
            const payload = resp.data;
            data.value = Array.isArray(payload) ? payload : (payload.data ?? payload);
        }
    } catch (e) {
        alert('Error al generar el reporte: ' + (e.response?.data?.error || e.message));
    } finally {
        loading.value = false;
    }
};

const cambiarTab = (id) => { tab.value = id; cargar(); };
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const fmtFecha = (f) => (f ? String(f).replace('T', ' ').slice(0, 16) : '—');
cargar();
</script>

<template>
    <Head title="Reporte de movimientos" />
    <AuthenticatedLayout>
        <template #header>Reporte de movimientos de inventario</template>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2"><label class="form-label">Desde</label><input v-model="filtroForm.fecha_desde" type="date" class="form-control form-control-sm" /></div>
                    <div class="col-md-2"><label class="form-label">Hasta</label><input v-model="filtroForm.fecha_hasta" type="date" class="form-control form-control-sm" /></div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select v-model="filtroForm.tipo_movimiento" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="entrada">Entradas</option>
                            <option value="salida">Salidas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Producto</label>
                        <select v-model="filtroForm.producto_id" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option v-for="p in catalogos.productos" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-3"><button class="btn btn-sm btn-primary w-100" @click="cargar"><i class="fa-solid fa-chart-line me-1"></i>Generar</button></div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs">
                    <li v-for="t in tabs" :key="t.id" class="nav-item">
                        <a href="#" class="nav-link" :class="{ active: tab === t.id }" @click.prevent="cambiarTab(t.id)">{{ t.label }}</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div v-if="loading" class="text-center py-4"><span class="spinner-border"></span></div>
                <template v-else>
                    <div v-if="tab === 'resumen' && resumen" class="row g-3">
                        <div class="col-md-4"><div class="card text-bg-primary"><div class="card-body"><h6>Productos con control</h6><h3>{{ resumen.productos?.total ?? 0 }}</h3></div></div></div>
                        <div class="col-md-4"><div class="card text-bg-danger"><div class="card-body"><h6>Bajo stock</h6><h3>{{ resumen.productos?.bajo_stock ?? 0 }}</h3></div></div></div>
                        <div class="col-md-4"><div class="card text-bg-success"><div class="card-body"><h6>Valor inventario</h6><h3>{{ money(resumen.inventario?.valor_total) }}</h3></div></div></div>
                        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Movimientos</h6><h3>{{ resumen.movimientos?.total ?? 0 }}</h3></div></div></div>
                        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Entradas</h6><h3 class="text-success">{{ resumen.movimientos?.entradas ?? 0 }}</h3></div></div></div>
                        <div class="col-md-4"><div class="card"><div class="card-body"><h6>Salidas</h6><h3 class="text-danger">{{ resumen.movimientos?.salidas ?? 0 }}</h3></div></div></div>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead v-if="tab === 'generales'"><tr><th>Fecha</th><th>Tipo</th><th>Motivo</th><th>Producto</th><th>Cantidad</th><th>Usuario</th></tr></thead>
                            <thead v-else-if="tab === 'por-producto'"><tr><th>Producto</th><th>Entradas</th><th>Salidas</th><th>Saldo</th><th>Movs.</th></tr></thead>
                            <thead v-else-if="tab === 'por-tipo'"><tr><th>Movimiento</th><th>Motivo</th><th>Cantidad</th><th>Movs.</th></tr></thead>
                            <thead v-else-if="tab === 'por-usuario'"><tr><th>Usuario</th><th>Entradas</th><th>Salidas</th><th>Movs.</th></tr></thead>
                            <thead v-else-if="tab === 'bajo-stock'"><tr><th>Código</th><th>Producto</th><th>Stock actual</th><th>Stock mín.</th></tr></thead>
                            <tbody>
                                <template v-if="tab === 'generales'">
                                    <tr v-for="(r, i) in data" :key="i">
                                        <td>{{ fmtFecha(r.fecha_movimiento) }}</td>
                                        <td>{{ r.tipo === 'entrada' ? 'Entrada' : 'Salida' }}</td>
                                        <td>{{ motivos[r.motivo] || r.motivo }}</td>
                                        <td>{{ r.producto?.name }}</td>
                                        <td>{{ r.cantidad }}</td>
                                        <td>{{ r.usuario?.name }}</td>
                                    </tr>
                                </template>
                                <template v-else-if="tab === 'por-producto'">
                                    <tr v-for="(r, i) in data" :key="i"><td>{{ r.producto_nombre }}</td><td>{{ r.total_entradas }}</td><td>{{ r.total_salidas }}</td><td>{{ r.saldo_movimiento }}</td><td>{{ r.total_movimientos }}</td></tr>
                                </template>
                                <template v-else-if="tab === 'por-tipo'">
                                    <tr v-for="(r, i) in data" :key="i"><td>{{ r.tipo_movimiento === 'entrada' ? 'Entrada' : 'Salida' }}</td><td>{{ motivos[r.tipo_nombre] || r.tipo_nombre }}</td><td>{{ r.total_cantidad }}</td><td>{{ r.total_movimientos }}</td></tr>
                                </template>
                                <template v-else-if="tab === 'por-usuario'">
                                    <tr v-for="(r, i) in data" :key="i"><td>{{ r.usuario_nombre }}</td><td>{{ r.total_entradas }}</td><td>{{ r.total_salidas }}</td><td>{{ r.total_movimientos }}</td></tr>
                                </template>
                                <template v-else-if="tab === 'bajo-stock'">
                                    <tr v-for="(r, i) in data" :key="i"><td><code>{{ r.codigo }}</code></td><td>{{ r.nombre }}</td><td>{{ r.stock_actual }}</td><td>{{ r.stock_minimo }}</td></tr>
                                </template>
                                <tr v-if="!data.length && tab !== 'resumen'"><td colspan="6" class="text-center text-muted py-3">Sin resultados</td></tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
