<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ productos: Array, movimientos: Array, resumen: Object });

const form = useForm({
    pos_product_id: '',
    tipo: 'entrada',
    motivo: 'compra',
    cantidad: 1,
    destino: 'bodega',
    observaciones: '',
});

const motivos = {
    entrada: [
        { value: 'compra', label: 'Compra' },
        { value: 'ajuste', label: 'Ajuste' },
    ],
    salida: [
        { value: 'merma', label: 'Merma' },
        { value: 'ajuste', label: 'Ajuste' },
    ],
};

const etiqueta = { compra: 'Compra', ajuste: 'Ajuste', merma: 'Merma', venta: 'Venta', devolucion: 'Devolución' };
const estado = { ok: 'text-bg-success', bajo: 'text-bg-warning', sin_stock: 'text-bg-danger', sin_control: 'text-bg-secondary' };
const estadoTexto = { ok: 'Con existencia', bajo: 'Bajo mínimo', sin_stock: 'Sin existencia', sin_control: 'Sin control' };

const cambiarTipo = () => {
    form.motivo = form.tipo === 'entrada' ? 'compra' : 'merma';
};

const fecha = (valor) => (valor ? String(valor).replace('T', ' ').slice(0, 16) : '');
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
</script>

<template>
    <Head title="Inventario" />
    <AuthenticatedLayout>
        <template #header>Inventario</template>
        <div class="row g-3 mb-3">
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Productos</div><h3>{{ resumen.total }}</h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Con control</div><h3>{{ resumen.con_control }}</h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Bajo mínimo</div><h3 class="text-warning">{{ resumen.bajo }}</h3></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Sin existencia</div><h3 class="text-danger">{{ resumen.sin_stock }}</h3></div></div></div>
        </div>

        <form class="card mb-3" @submit.prevent="form.post(route('inventario.store'))">
            <div class="card-header">Movimiento</div>
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Producto</label>
                    <select v-model="form.pos_product_id" class="form-select" required>
                        <option value="">Seleccione</option>
                        <option v-for="item in productos" :key="item.id" :value="item.id">{{ item.outlet }} · {{ item.name }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select v-model="form.tipo" class="form-select" @change="cambiarTipo">
                        <option value="entrada">Entrada</option>
                        <option value="salida">Salida</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Motivo</label>
                    <select v-model="form.motivo" class="form-select">
                        <option v-for="item in motivos[form.tipo]" :key="item.value" :value="item.value">{{ item.label }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cantidad</label>
                    <input v-model="form.cantidad" type="number" min="0.01" step="0.01" class="form-control" required />
                </div>
                <div class="col-md-2" v-if="form.tipo === 'entrada'">
                    <label class="form-label">Destino</label>
                    <select v-model="form.destino" class="form-select">
                        <option value="bodega">Bodega</option>
                        <option value="exhibicion">Exhibición</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" :disabled="form.processing">Registrar</button>
                </div>
                <div class="col-12">
                    <input v-model="form.observaciones" class="form-control" placeholder="Observaciones" />
                </div>
            </div>
        </form>

        <div class="card mb-3">
            <div class="card-header">Existencias</div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Producto</th><th>Punto</th><th>Costo</th><th>Precio</th><th>Ganancia</th><th>Bodega</th><th>Exhibición</th><th>Existencia</th><th>Mínimo</th><th>Estado</th></tr></thead>
                    <tbody>
                        <tr v-for="item in productos" :key="item.id">
                            <td>{{ item.name }}</td>
                            <td>{{ item.outlet }}</td>
                            <td>{{ money(item.costo) }}</td>
                            <td>{{ money(item.price) }}</td>
                            <td>{{ money(item.ganancia) }}</td>
                            <td>{{ item.controla_inventario ? item.bodega : '—' }}</td>
                            <td>{{ item.controla_inventario ? item.exhibicion : '—' }}</td>
                            <td>{{ item.controla_inventario ? item.stock_actual : '—' }}</td>
                            <td>{{ item.controla_inventario ? item.stock_minimo : '—' }}</td>
                            <td><span class="badge" :class="estado[item.estado]">{{ estadoTexto[item.estado] }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Últimos movimientos</div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Motivo</th><th>Cantidad</th><th>Quedó</th></tr></thead>
                    <tbody>
                        <tr v-for="item in movimientos" :key="item.id">
                            <td>{{ fecha(item.fecha_movimiento) }}</td>
                            <td>{{ item.producto?.name }}</td>
                            <td>{{ item.tipo === 'entrada' ? 'Entrada' : 'Salida' }}</td>
                            <td>{{ etiqueta[item.motivo] || item.motivo }}</td>
                            <td>{{ item.cantidad }}</td>
                            <td>{{ item.stock_nuevo }}</td>
                        </tr>
                        <tr v-if="!movimientos.length"><td colspan="6" class="text-muted p-3">Sin movimientos.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
