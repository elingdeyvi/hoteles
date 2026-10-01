<script setup>
import { computed, onMounted, reactive, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { sesionAviso } from '@/sesionAviso';
import { usePermissions } from '@/composables/usePermissions';
import { applyPosTheme } from '@/composables/useTheme';

const page = usePage();
const user = computed(() => page.props.auth.user);
const empresa = computed(() => page.props.empresa ?? {});
const appName = computed(() => empresa.value.nombre_corto || empresa.value.nombre || page.props.appName || 'Hotel');
const logoUrl = computed(() => empresa.value.logo_mark_url || empresa.value.logo_url || '/favicon.ico');
const cajaAbierta = computed(() => page.props.cajaAbierta);
const homeHref = computed(() => {
    if (show('dashboard.ver')) return route('dashboard');
    if (show('caja.operar') && !cajaAbierta.value) return route('caja.index');
    if (show('pos.vender')) return route('pos.index');
    if (show('housekeeping.gestionar')) return route('housekeeping.index');
    return route('profile.edit');
});
const primaryHex = computed(() => (empresa.value.color_primario || '#1a365d').replace('#', ''));
const avatarUrl = computed(
    () => `https://ui-avatars.com/api/?name=${encodeURIComponent(user.value?.name || 'U')}&background=${primaryHex.value}&color=fff`,
);
const currentProperty = computed(() => page.props.currentProperty);
const properties = computed(() => page.props.properties ?? []);
const flash = computed(() => page.props.flash ?? {});

const { can, hasRole } = usePermissions();
const show = (perm) => hasRole('Administrador') || can(perm);

const groups = computed(() => ({
    recepcion: show('recepcion.reservas') || show('recepcion.huespedes') || show('recepcion.checkin'),
    operacion: show('facturacion.folios') || show('housekeeping.gestionar') || show('pos.vender') || show('pos.catalogo') || show('caja.operar'),
    reportes: show('reportes.ver'),
    config: show('hotel.configurar'),
    admin: show('administracion.usuarios') || show('administracion.roles'),
}));

const open = reactive({ recepcion: false, operacion: false, reportes: false, config: false, admin: false });

const isActive = (names) => {
    const current = route().current();
    return names.some((name) => current === name || current?.startsWith(`${name}.`) || current?.startsWith(name));
};

const initOpenGroups = () => {
    open.recepcion = isActive(['reservas', 'huespedes', 'planning']);
    open.operacion = isActive(['folios', 'housekeeping', 'pos', 'caja', 'inventario']);
    open.reportes = isActive(['reportes']);
    open.config = isActive(['propiedades', 'tipos', 'habitaciones', 'tarifas', 'configuracion-empresa', 'impresion']);
    open.admin = isActive(['users', 'roles']);
};

const toggle = (key) => {
    open[key] = !open[key];
};

const toggleSidebar = () => {
    document.body.classList.toggle('sidebar-collapse');
};

const switchProperty = (event) => {
    router.post(route('propiedad.switch'), { property_id: event.target.value }, { preserveScroll: true });
};

onMounted(() => {
    document.body.classList.add('layout-fixed', 'sidebar-expand-lg', 'bg-body-tertiary');
    initOpenGroups();
    applyPosTheme(empresa.value);
});

watch(() => page.url, initOpenGroups);
watch(() => [empresa.value.tema_modo, empresa.value.color_primario, empresa.value.color_secundario], () => applyPosTheme(empresa.value));
</script>

<template>
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body border-bottom">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click.prevent="toggleSidebar"><i class="fa-solid fa-bars"></i></a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <Link :href="homeHref" class="nav-link">Inicio</Link>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center">
                    <li v-if="show('caja.operar')" class="nav-item me-2">
                        <Link :href="route('caja.index')" class="nav-link py-1">
                            <span class="badge" :class="cajaAbierta ? 'text-bg-success' : 'text-bg-warning'">
                                {{ cajaAbierta ? 'Caja abierta' : 'Caja cerrada' }}
                            </span>
                        </Link>
                    </li>
                    <li v-if="properties.length" class="nav-item me-2">
                        <select class="form-select form-select-sm" :value="currentProperty?.id" @change="switchProperty">
                            <option v-for="property in properties" :key="property.id" :value="property.id">
                                {{ property.name }}
                            </option>
                        </select>
                    </li>
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <img :src="avatarUrl" class="user-image rounded-circle shadow" alt="" width="32" height="32" />
                            <span class="d-none d-md-inline ms-1">{{ user?.name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <li class="user-header text-bg-primary">
                                <p>{{ user?.name }}<small>{{ user?.email }}</small></p>
                            </li>
                            <li class="user-footer">
                                <Link :href="route('profile.edit')" class="btn btn-default btn-flat">Perfil</Link>
                                <Link :href="route('logout')" method="post" as="button" class="btn btn-default btn-flat float-end">Salir</Link>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <aside class="app-sidebar shadow" data-bs-theme="dark">
            <div class="sidebar-brand">
                <Link :href="homeHref" class="brand-link">
                    <img v-if="logoUrl" :src="logoUrl" :alt="appName" class="brand-image rounded" />
                    <span class="brand-text">{{ appName }}</span>
                </Link>
            </div>
            <div class="sidebar-wrapper">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column">
                        <li v-if="show('dashboard.ver')" class="nav-item">
                            <Link :href="route('dashboard')" class="nav-link" :class="{ active: isActive(['dashboard']) }">
                                <i class="nav-icon fa-solid fa-gauge"></i>
                                <p>Tablero</p>
                            </Link>
                        </li>

                        <li v-if="groups.recepcion" class="nav-item">
                            <a href="#" class="nav-link" @click.prevent="toggle('recepcion')">
                                <i class="nav-icon fa-solid fa-bell-concierge"></i>
                                <p>Recepción <i class="nav-arrow fa-solid" :class="open.recepcion ? 'fa-angle-down' : 'fa-angle-left'"></i></p>
                            </a>
                            <ul class="nav nav-treeview" :style="{ display: open.recepcion ? 'block' : 'none' }">
                                <li v-if="show('recepcion.reservas')" class="nav-item">
                                    <Link :href="route('reservas.index')" class="nav-link" :class="{ active: isActive(['reservas']) }"><i class="nav-icon fa-solid fa-calendar-check"></i><p>Reservas</p></Link>
                                </li>
                                <li v-if="show('recepcion.reservas')" class="nav-item">
                                    <Link :href="route('planning.index')" class="nav-link" :class="{ active: isActive(['planning']) }"><i class="nav-icon fa-solid fa-table-cells"></i><p>Planeación</p></Link>
                                </li>
                                <li v-if="show('recepcion.huespedes')" class="nav-item">
                                    <Link :href="route('huespedes.index')" class="nav-link" :class="{ active: isActive(['huespedes']) }"><i class="nav-icon fa-solid fa-user-group"></i><p>Huéspedes</p></Link>
                                </li>
                            </ul>
                        </li>

                        <li v-if="groups.operacion" class="nav-item">
                            <a href="#" class="nav-link" @click.prevent="toggle('operacion')">
                                <i class="nav-icon fa-solid fa-hotel"></i>
                                <p>Operación <i class="nav-arrow fa-solid" :class="open.operacion ? 'fa-angle-down' : 'fa-angle-left'"></i></p>
                            </a>
                            <ul class="nav nav-treeview" :style="{ display: open.operacion ? 'block' : 'none' }">
                                <li v-if="show('caja.operar')" class="nav-item">
                                    <Link :href="route('caja.index')" class="nav-link" :class="{ active: isActive(['caja']) }">
                                        <i class="nav-icon fa-solid fa-cash-register"></i>
                                        <p>Caja <span v-if="!cajaAbierta" class="badge text-bg-warning">Cerrada</span></p>
                                    </Link>
                                </li>
                                <li v-if="show('facturacion.folios')" class="nav-item">
                                    <Link :href="route('folios.index')" class="nav-link" :class="{ active: isActive(['folios']) }"><i class="nav-icon fa-solid fa-file-invoice-dollar"></i><p>Folios</p></Link>
                                </li>
                                <li v-if="show('housekeeping.gestionar')" class="nav-item">
                                    <Link :href="route('housekeeping.index')" class="nav-link" :class="{ active: isActive(['housekeeping']) }"><i class="nav-icon fa-solid fa-broom"></i><p>Limpieza</p></Link>
                                </li>
                                <li v-if="show('pos.vender')" class="nav-item">
                                    <Link :href="route('pos.index')" class="nav-link" :class="{ active: isActive(['pos.index', 'pos.charge', 'pos.vender']) }"><i class="nav-icon fa-solid fa-cash-register"></i><p>Venta al público</p></Link>
                                </li>
                                <li v-if="show('pos.catalogo')" class="nav-item">
                                    <Link :href="route('pos.catalog')" class="nav-link" :class="{ active: isActive(['pos.catalog']) }"><i class="nav-icon fa-solid fa-tags"></i><p>Catálogo POS</p></Link>
                                </li>
                                <li v-if="show('pos.catalogo')" class="nav-item">
                                    <Link :href="route('inventario.index')" class="nav-link" :class="{ active: isActive(['inventario']) }"><i class="nav-icon fa-solid fa-boxes-stacked"></i><p>Inventario</p></Link>
                                </li>
                            </ul>
                        </li>

                        <li v-if="groups.reportes" class="nav-item">
                            <a href="#" class="nav-link" @click.prevent="toggle('reportes')">
                                <i class="nav-icon fa-solid fa-chart-pie"></i>
                                <p>Reportes <i class="nav-arrow fa-solid" :class="open.reportes ? 'fa-angle-down' : 'fa-angle-left'"></i></p>
                            </a>
                            <ul class="nav nav-treeview" :style="{ display: open.reportes ? 'block' : 'none' }">
                                <li class="nav-item">
                                    <Link :href="route('reportes.ventas.index')" class="nav-link" :class="{ active: isActive(['reportes.ventas']) }"><i class="nav-icon fa-solid fa-chart-column"></i><p>Ventas</p></Link>
                                </li>
                                <li class="nav-item">
                                    <Link :href="route('reportes.inventario.index')" class="nav-link" :class="{ active: isActive(['reportes.inventario']) }"><i class="nav-icon fa-solid fa-chart-line"></i><p>Inventario</p></Link>
                                </li>
                                <li class="nav-item">
                                    <Link :href="route('reportes.index')" class="nav-link" :class="{ active: route().current() === 'reportes.index' }"><i class="nav-icon fa-solid fa-bed"></i><p>Operación</p></Link>
                                </li>
                            </ul>
                        </li>

                        <li v-if="groups.config" class="nav-item">
                            <a href="#" class="nav-link" @click.prevent="toggle('config')">
                                <i class="nav-icon fa-solid fa-sliders"></i>
                                <p>Configuración <i class="nav-arrow fa-solid" :class="open.config ? 'fa-angle-down' : 'fa-angle-left'"></i></p>
                            </a>
                            <ul class="nav nav-treeview" :style="{ display: open.config ? 'block' : 'none' }">
                                <li class="nav-item"><Link :href="route('propiedades.index')" class="nav-link" :class="{ active: isActive(['propiedades']) }"><p>Hoteles</p></Link></li>
                                <li class="nav-item"><Link :href="route('tipos.index')" class="nav-link" :class="{ active: isActive(['tipos']) }"><p>Tipos de habitación</p></Link></li>
                                <li class="nav-item"><Link :href="route('habitaciones.index')" class="nav-link" :class="{ active: isActive(['habitaciones']) }"><p>Habitaciones</p></Link></li>
                                <li class="nav-item"><Link :href="route('tarifas.index')" class="nav-link" :class="{ active: isActive(['tarifas']) }"><p>Tarifas</p></Link></li>
                                <li class="nav-item"><Link :href="route('configuracion-empresa.index')" class="nav-link" :class="{ active: isActive(['configuracion-empresa']) }"><p>Empresa</p></Link></li>
                                <li class="nav-item"><Link :href="route('impresion.index')" class="nav-link" :class="{ active: isActive(['impresion']) }"><p>Impresión</p></Link></li>
                            </ul>
                        </li>

                        <li v-if="groups.admin" class="nav-item">
                            <a href="#" class="nav-link" @click.prevent="toggle('admin')">
                                <i class="nav-icon fa-solid fa-user-shield"></i>
                                <p>Administración <i class="nav-arrow fa-solid" :class="open.admin ? 'fa-angle-down' : 'fa-angle-left'"></i></p>
                            </a>
                            <ul class="nav nav-treeview" :style="{ display: open.admin ? 'block' : 'none' }">
                                <li v-if="show('administracion.usuarios')" class="nav-item">
                                    <Link :href="route('users.index')" class="nav-link" :class="{ active: isActive(['users']) }"><p>Usuarios</p></Link>
                                </li>
                                <li v-if="show('administracion.roles')" class="nav-item">
                                    <Link :href="route('roles.index')" class="nav-link" :class="{ active: isActive(['roles']) }"><p>Roles</p></Link>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="app-main">
            <div class="app-content-header" v-if="$slots.header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6"><h3 class="mb-0"><slot name="header" /></h3></div>
                    </div>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
                    <div v-if="sesionAviso.texto" class="alert alert-warning">{{ sesionAviso.texto }}</div>
                    <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
                    <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
