<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps({ outlets: Array });
const outlet = useForm({ name: '', code: '', is_active: true });
const category = useForm({ pos_outlet_id: '', name: '', sort_order: 0, is_active: true });
const product = useForm({ pos_category_id: '', name: '', sku: '', price: 0, is_active: true });
const money = (n) => Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
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
                <input v-model="product.price" type="number" step="0.01" min="0" class="form-control mb-2" required />
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
                    <ul class="mb-0">
                        <li v-for="prod in cat.products" :key="prod.id" class="d-flex justify-content-between">
                            <span>{{ prod.name }} — {{ money(prod.price) }}</span>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0" @click="quitar(`¿Eliminar ${prod.name}? Solo se borra si no tiene consumos.`, route('pos.products.destroy', prod.id))">Eliminar</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
