<template>
    <div>
        <!--  BEGIN NAVBAR  -->
        <div class="header-container fixed-top">
            <header class="header navbar navbar-expand-sm">
                <ul class="navbar-item theme-brand flex-row text-center">
                    <li class="nav-item theme-logo">
                        <router-link to="/">
                            <img
                                :src="logoUrl"
                                class="navbar-logo"
                                :alt="$t('brand.logo_alt')"
                                @error="onLogoError"
                            />
                        </router-link>
                    </li>
                    <li class="nav-item theme-text">
                        <router-link to="/" class="nav-link">{{ $t("brand.nav_title") }}</router-link>
                    </li>
                    <li class="nav-item ms-3" v-if="hotelProperties.length > 1">
                        <select
                            class="form-select form-select-sm property-select"
                            :value="propertyCurrentId"
                            @change="onPropertyChange($event.target.value)"
                        >
                            <option v-for="p in hotelProperties" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </li>
                </ul>
                <div class="d-none horizontal-menu">
                    <a href="javascript:void(0);" class="sidebarCollapse" data-placement="bottom" @click="$store.commit('toggleSideBar', !$store.state.is_show_sidebar)">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="24"
                            height="24"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="feather feather-menu"
                        >
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </a>
                </div>

                <div class="navbar-item flex-row ms-md-auto">
                    <div class="nav-item user-name me-3 d-flex align-items-center text-white" v-if="currentUser">
                        <span class="me-2">Bienvenido,</span>
                        <strong>{{ currentUser.name }}</strong>
                    </div>
                    <div class="dropdown nav-item user-profile-dropdown btn-group">
                        <a href="javascript:;" id="ddluser" data-bs-toggle="dropdown" aria-expanded="false" class="btn dropdown-toggle btn-icon-only user nav-link header-avatar-link">
                            <img :src="defaultUsers" alt="avatar" class="header-avatar-img" />
                        </a>
                        <ul class="dropdown-menu dropdown-menu-right m-0" aria-labelledby="ddluser">
                            <li role="presentation" class="dropdown-header" v-if="currentUser">
                                <div class="d-flex flex-column">
                                    <span class="fw-bold">{{ currentUser.name }}</span>
                                    <small class="text-muted">{{ currentUser.email }}</small>
                                    <small class="text-muted" v-if="currentUser.roles && currentUser.roles.length > 0">
                                        Rol: {{ currentUser.roles[0].name }}
                                    </small>
                                </div>
                            </li>
                            <li role="presentation"><hr class="dropdown-divider" /></li>
                            <li role="presentation">
                                <router-link to="/users/profile" class="dropdown-item">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather feather-user"
                                    >
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    Perfil
                                </router-link>
                            </li>
                            <li role="presentation">
                                <a
                                    v-on:click.stop="logout"
                                    class="dropdown-item center-login"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather feather-log-out"
                                    >
                                        <path
                                            d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"
                                        ></path>
                                        <polyline
                                            points="16 17 21 12 16 7"
                                        ></polyline>
                                        <line
                                            x1="21"
                                            y1="12"
                                            x2="9"
                                            y2="12"
                                        ></line>
                                    </svg>
                                    Cerrar
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
        </div>
        <!--  END NAVBAR  -->
        <!--  BEGIN NAVBAR  -->
        <div class="sub-header-container">
            <header class="header navbar navbar-expand-sm">
                <a href="javascript:void(0);" class="sidebarCollapse" data-placement="bottom" @click="$store.commit('toggleSideBar', !$store.state.is_show_sidebar)">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="feather feather-menu"
                    >
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </a>

                <div id="breadcrumb" class="vue-portal-target"></div>
            </header>
        </div>
        <!--  END NAVBAR  -->
        <!--  BEGIN TOPBAR (hotel)  -->
        <div class="topbar-nav header navbar" role="banner">
            <nav class="topbar">
                <ul class="list-unstyled menu-categories" id="topAccordion">
                    <li class="menu single-menu" v-if="can('dashboard.ver')">
                        <router-link to="/dashboard" class="dropdown-toggle">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-home"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                                <span>Dashboard</span>
                            </div>
                        </router-link>
                    </li>

                    <li class="menu single-menu" v-if="canAny(['recepcion.reservas', 'recepcion.huespedes'])">
                        <a href="javascript:;" class="dropdown-toggle">
                            <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-calendar"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg><span>Recepción</span></div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </a>
                        <ul class="collapse submenu list-unstyled">
                            <li v-if="can('recepcion.reservas')"><router-link to="/hotel/recepcion/planning">Planning</router-link></li>
                            <li v-if="can('recepcion.reservas')"><router-link to="/hotel/recepcion/reservas">Reservaciones</router-link></li>
                            <li v-if="can('recepcion.huespedes')"><router-link to="/hotel/recepcion/huespedes">Huéspedes</router-link></li>
                        </ul>
                    </li>

                    <li class="menu single-menu" v-if="can('pos.vender')">
                        <router-link to="/hotel/pos" class="dropdown-toggle">
                            <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-shopping-bag"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg><span>POS</span></div>
                        </router-link>
                    </li>

                    <li class="menu single-menu" v-if="canAny(['facturacion.folios', 'facturacion.pagos'])">
                        <router-link to="/hotel/facturacion/folios" class="dropdown-toggle">
                            <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-credit-card"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg><span>Facturación</span></div>
                        </router-link>
                    </li>

                    <li class="menu single-menu" v-if="can('housekeeping.gestionar')">
                        <router-link to="/hotel/housekeeping" class="dropdown-toggle">
                            <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-sun"><circle cx="12" cy="12" r="5"></circle></svg><span>Housekeeping</span></div>
                        </router-link>
                    </li>

                    <li class="menu single-menu" v-if="can('reportes.ver')">
                        <router-link to="/hotel/reportes" class="dropdown-toggle">
                            <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-bar-chart-2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg><span>Reportes</span></div>
                        </router-link>
                    </li>

                    <li class="menu single-menu" v-if="canAny(['hotel.configurar', 'administracion.usuarios', 'administracion.roles'])">
                        <a href="javascript:;" class="dropdown-toggle">
                            <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-settings"><circle cx="12" cy="12" r="3"></circle></svg><span>Administración</span></div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-chevron-down"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </a>
                        <ul class="collapse submenu list-unstyled">
                            <li v-if="can('hotel.configurar')"><router-link to="/hotel/config/tipos">Tipos habitación</router-link></li>
                            <li v-if="can('hotel.configurar')"><router-link to="/admin/configuracion-empresa">Datos del hotel</router-link></li>
                            <li v-if="can('pos.catalogo')"><router-link to="/hotel/pos/catalogo">Catálogo POS</router-link></li>
                            <li v-if="can('administracion.usuarios')"><router-link to="/users/lista">Usuarios</router-link></li>
                            <li v-if="can('administracion.roles')"><router-link to="/roles/lista">Roles</router-link></li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
        <loading
            v-model:active="isLoading"
            :can-cancel="false"
            :is-full-page="true"
        />
        <!--  END TOPBAR  -->
    </div>
</template>

<script setup>
    import { onMounted, ref, reactive, computed } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useStore } from 'vuex';
    import { useConfiguracionEmpresaStore } from '@/store/ConfiguracionEmpresaStore';
    import * as AuthRepository from "@/repositories/AuthRepository";
    import * as UserRepository from "@/repositories/UserRepository";
    import { usePermissions } from '@/composables/use-permissions';
    import Loading from "vue-loading-overlay";
    import "vue-loading-overlay/dist/css/index.css";
    // Logo por defecto como asset importado: Vite lo sirve con URL válida y evita que Laravel devuelva HTML
    import defaultLogoAsset from '@/assets/images/logo.png';
    import defaultUsers from '@/assets/images/user-avtar.svg';

    import { useProperty } from '@/composables/use-property';

    const store = useStore();
    const { properties: hotelProperties, currentId: propertyCurrentId, loadProperties, setProperty, initFromStorage } = useProperty();
    // Logo: siempre nuestro ref (nunca refs de Pinia) para evitar error _s si el store no está listo
    const storeLogoUrlRef = ref(defaultLogoAsset);
    let configuracionEmpresaStore = null;
    try {
        configuracionEmpresaStore = useConfiguracionEmpresaStore();
    } catch (e) {
        console.warn('Header: ConfiguracionEmpresa store no disponible, usando logo por defecto.', e);
    }

    // Logo: solo leemos nuestro ref; cache-bust si la URL viene del backend
    const logoUrl = computed(() => {
        const url = storeLogoUrlRef.value;
        if (!url || typeof url !== 'string') return defaultLogoAsset;
        if ((url.startsWith('/storage/') || url.startsWith('http')) && configuracionEmpresaStore?.configuracion) {
            const sep = url.indexOf('?') >= 0 ? '&' : '?';
            const v = configuracionEmpresaStore.configuracion.updated_at;
            return url + sep + 'v=' + (v ? String(v).replace(/\D/g, '').slice(0, 14) : Date.now());
        }
        return url;
    });

    const logoErrorCount = ref(0);
    const onLogoError = (e) => {
        if (!e?.target) return;
        // Evitar bucle: no volver a asignar la misma URL que ya falló
        if (e.target.src && e.target.src === defaultLogoAsset) {
            logoErrorCount.value += 1;
            if (logoErrorCount.value > 1) return;
        }
        e.target.src = defaultLogoAsset;
    };

    const isLoading = ref(false);
    const currentUser = ref(null);
    const { loadPermissions, hasPermission, hasAnyPermission } = usePermissions();
    const permissionsLoaded = ref(false);

    const selectedLang = ref(null);
    const countryList = ref(store.state.countryList);

    const i18n = reactive(useI18n());

    // Funciones helper para verificar permisos
    const can = (permission) => {
        if (!permissionsLoaded.value) return false;
        return hasPermission(permission);
    };
    
    const canAny = (permissions) => {
        if (!permissionsLoaded.value) return false;
        return hasAnyPermission(permissions);
    };

    const loadCurrentUser = async () => {
        try {
            const token = window.localStorage.getItem("token");
            if (token) {
                const userData = await UserRepository.getuser();
                currentUser.value = userData;
            }
        } catch (error) {
            console.error('Error loading current user:', error);
        }
    };

    const onPropertyChange = (id) => {
        setProperty(id);
        window.location.reload();
    };

    onMounted(async () => {
        initFromStorage();
        selectedLang.value = window.$appSetting.toggleLanguage();
        toggleMode();
        await loadCurrentUser();
        if (window.localStorage.getItem('token')) {
            await loadProperties();
        }
        await loadPermissions();
        permissionsLoaded.value = true;
        // Cargar configuración de empresa (logo, etc.) y actualizar nuestro ref con el valor, no con refs de Pinia
        if (configuracionEmpresaStore) {
            try {
                await configuracionEmpresaStore.cargarConfiguracion();
            } catch (e) {
                await configuracionEmpresaStore.cargarConfiguracionPublica().catch(() => {});
            }
            try {
                const url = configuracionEmpresaStore.getLogoUrl;
                if (url && typeof url === 'string') storeLogoUrlRef.value = url;
            } catch (_) {}
        }
    });

    const toggleMode = (mode) => {
        window.$appSetting.toggleMode(mode);
    };

    const changeLanguage = (item) => {
        selectedLang.value = item;
        i18n.locale = item.code;
        window.$appSetting.toggleLanguage(item);
    };
    const logout = async () => {
        try {
            isLoading.value = true;
            if (window.localStorage.getItem("token")) {
                try {
                    await AuthRepository.logoutCurrentToken();
                } catch (_) {
                    /* red aun si la API falla */
                }
            }
            localStorage.removeItem("token");
            location.reload("/");
        } catch (error) {
            console.log(error);
        } finally {
            isLoading.value = false;
        }
    };
</script>

<style scoped>
.header-container .header-avatar-link .header-avatar-img {
    filter: brightness(0) invert(1);
}
</style>
