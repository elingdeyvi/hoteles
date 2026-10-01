<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ outlets: Array });
const outlet = useForm({ name: '', code: '', is_active: true });
const category = useForm({ pos_outlet_id: '', name: '', sort_order: 0, is_active: true });
const product = useForm({ pos_category_id: '', name: '', sku: '', price: 0, iva_porcentaje: 16, aplicar_iva: false, costo: 0, piezas_caja: 1, bodega: 0, exhibicion: 0, controla_inventario: true, stock_actual: 0, stock_minimo: 0, is_active: true });
const precio = useForm({ name: '', sku: '', price: 0, iva_porcentaje: 16, aplicar_iva: false, costo: 0, piezas_caja: 1, is_active: true });
const editando = ref(null);
const abrirPrecio = (prod) => {
    editando.value = prod.id;
    precio.name = prod.name;
    precio.sku = prod.sku || '';
    precio.price = Number(prod.price);
    precio.iva_porcentaje = Number(prod.iva_porcentaje ?? 16);
    precio.aplicar_iva = !!prod.aplicar_iva;
    precio.costo = Number(prod.costo || 0);
    precio.piezas_caja = Number(prod.piezas_caja || 1);
    precio.is_active = prod.is_active !== false;
};
const guardarPrecio = (prod) => {
    precio.put(route('pos.products.update', prod.id), { onSuccess: () => { editando.value = null; } });
};
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
const cargoDe = (prod) => {
    const base = Number(prod.price || 0);
    if (!prod.aplicar_iva) return base;
    return Math.round(base * (1 + Number(prod.iva_porcentaje || 0) / 100) * 100) / 100;
};
const quitar = (mensaje, url) => {
    if (window.confirm(mensaje)) router.delete(url);
};
</script>

<template>
    <Head title="Catálogo POS" />
    <AuthenticatedLayout>
        <template #header>Catálogo POS</template>
        <div class="row g-3 mb-3">
            <form class="col-md-4 card card-body" @submit.prevent="outlet.post(route('pos.outlets.store'), { onSuccess: () => outlet.reset() })">
                <h6>Nuevo punto de venta</h6>
                <input v-model="outlet.name" class="form-control mb-2" placeholder="Nombre" required />
                <input v-model="outlet.code" class="form-control mb-2" placeholder="código" required />
                <button class="btn btn-primary">Crear</button>
            </form>
            <form class="col-md-4 card card-body" @submit.prevent="category.post(route('pos.categories.store'), { onSuccess: () => category.reset() })">
                <h6>Nueva categoría</h6>
                <select v-model="category.pos_outlet_id" class="form-select mb-2" required>
                    <option value="">Punto de venta</option>
                    <option v-for="item in outlets" :key="item.id" :value="item.id">{{ item.name }}</option>
                </select>
                <input v-model="category.name" class="form-control mb-2" placeholder="Nombre" required />
                <button class="btn btn-primary">Crear</button>
            </form>
            <form class="col-md-4 card card-body" @submit.prevent="product.post(route('pos.products.store'), { onSuccess: () => product.reset() })">
                <h6>Nuevo producto</h6>
                <select v-model="product.pos_category_id" class="form-select mb-2" required>
                    <option value="">Categoría</option>
                    <template v-for="item in outlets" :key="item.id">
                        <option v-for="cat in item.categories" :key="cat.id" :value="cat.id">{{ item.name }} · {{ cat.name }}</option>
                    </template>
                </select>
                <input v-model="product.name" class="form-control mb-2" placeholder="Nombre" required />
                <div class="row g-2 mb-2">
                    <div class="col"><input v-model="product.price" type="number" step="0.01" min="0" class="form-control" placeholder="Precio base" required /></div>
                    <div class="col"><input v-model="product.costo" type="number" step="0.01" min="0" class="form-control" placeholder="Costo" /></div>
                </div>
                <div class="row g-2 mb-2 align-items-center">
                    <div class="col"><input v-model="product.iva_porcentaje" type="number" step="0.01" min="0" max="100" class="form-control" placeholder="IVA %" /></div>
                    <div class="col">
                        <div class="form-check">
                            <input id="iva-nuevo" v-model="product.aplicar_iva" class="form-check-input" type="checkbox" />
                            <label class="form-check-label" for="iva-nuevo">Aplicar IVA</label>
                        </div>
                    </div>
                </div>
                <p class="small mb-2">Cargo al cliente: <strong>{{ money(cargoDe(product)) }}</strong> · utilidad {{ money(cargoDe(product) - Number(product.costo || 0)) }}</p>
                <input v-model="product.piezas_caja" type="number" min="1" class="form-control mb-2" placeholder="Piezas por caja" />
                <div class="form-check mb-2">
                    <input id="controla" v-model="product.controla_inventario" class="form-check-input" type="checkbox" />
                    <label class="form-check-label" for="controla">Controla inventario</label>
                </div>
                <div v-if="product.controla_inventario" class="row g-2 mb-2">
                    <div class="col"><input v-model="product.bodega" type="number" step="0.01" min="0" class="form-control" placeholder="Bodega" /></div>
                    <div class="col"><input v-model="product.exhibicion" type="number" step="0.01" min="0" class="form-control" placeholder="Exhibición" /></div>
                    <div class="col"><input v-model="product.stock_minimo" type="number" step="0.01" min="0" class="form-control" placeholder="Mínimo" /></div>
                </div>
                <button class="btn btn-primary">Crear</button>
            </form>
        </div>
        <div v-for="item in outlets" :key="item.id" class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>{{ item.name }} <small class="text-muted">{{ item.code }}</small></span>
                <button type="button" class="btn btn-sm btn-outline-danger" @click="quitar(`¿Eliminar el punto de venta ${item.name}? Solo se borra si no tiene categorías.`, route('pos.outlets.destroy', item.id))">Eliminar</button>
            </div>
            <div class="card-body">
                <div v-for="cat in item.categories" :key="cat.id" class="mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>{{ cat.name }}</strong>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="quitar(`¿Eliminar la categoría ${cat.name}? Solo se borra si no tiene productos.`, route('pos.categories.destroy', cat.id))">Eliminar</button>
                    </div>
                    <ul class="list-unstyled mb-0">
                        <li v-for="prod in cat.products" :key="prod.id" class="d-flex justify-content-between align-items-center gap-2 py-1">
                            <template v-if="editando === prod.id">
                                <input v-model="precio.name" class="form-control form-control-sm" />
                                <input v-model="precio.price" type="number" step="0.01" min="0" class="form-control form-control-sm" style="max-width: 110px" placeholder="Precio" />
                                <input v-model="precio.iva_porcentaje" type="number" step="0.01" min="0" class="form-control form-control-sm" style="max-width: 80px" placeholder="IVA" />
                                <div class="form-check mb-0"><input v-model="precio.aplicar_iva" class="form-check-input" type="checkbox" /><label class="form-check-label small">IVA</label></div>
                                <input v-model="precio.costo" type="number" step="0.01" min="0" class="form-control form-control-sm" style="max-width: 110px" placeholder="Costo" />
                                <button type="button" class="btn btn-sm btn-primary" @click="guardarPrecio(prod)">Guardar</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="editando = null">Cancelar</button>
                            </template>
                            <template v-else>
                                <span>{{ prod.name }} — cargo {{ money(cargoDe(prod)) }} <small class="text-muted">base {{ money(prod.price) }}<span v-if="prod.aplicar_iva"> + IVA {{ Number(prod.iva_porcentaje) }}%</span> · costo {{ money(prod.costo) }} · utilidad {{ money(cargoDe(prod) - Number(prod.costo || 0)) }}</small> <small v-if="prod.controla_inventario" class="text-muted">bodega {{ Number(prod.bodega || 0) }} · exhib. {{ Number(prod.exhibicion || 0) }}</small></span>
                                <span>
                                    <button type="button" class="btn btn-sm btn-link p-0 me-2" @click="abrirPrecio(prod)">Editar precio</button>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="quitar(`¿Eliminar ${prod.name}? Solo se borra si no tiene consumos.`, route('pos.products.destroy', prod.id))">Eliminar</button>
                                </span>
                            </template>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
