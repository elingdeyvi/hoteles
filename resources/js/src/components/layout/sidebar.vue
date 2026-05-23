<template>
  <div class="sidebar-wrapper sidebar-theme">
    <nav ref="menu" id="sidebar">
      <div class="shadow-bottom"></div>
      <perfect-scrollbar class="list-unstyled menu-categories" tag="ul" :options="{ wheelSpeed: 0.5, minScrollbarLength: 40, maxScrollbarLength: 300, suppressScrollX: true }">
        <li class="menu" v-if="can('dashboard.ver')">
          <router-link to="/dashboard" class="dropdown-toggle" @click="toggleMobileMenu">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-home"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
              <span>Dashboard</span>
            </div>
          </router-link>
        </li>

        <li class="menu" v-if="canAny(['recepcion.reservas', 'recepcion.huespedes', 'recepcion.checkin'])">
          <a class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#recepcion">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-bell"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path></svg>
              <span>Recepción</span>
            </div>
            <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg></div>
          </a>
          <ul id="recepcion" class="collapse submenu list-unstyled">
            <li v-if="can('recepcion.reservas')"><router-link to="/hotel/recepcion/planning" @click="toggleMobileMenu">Planning (calendario)</router-link></li>
            <li v-if="can('recepcion.reservas')"><router-link to="/hotel/recepcion/reservas" @click="toggleMobileMenu">Reservaciones</router-link></li>
            <li v-if="can('recepcion.huespedes')"><router-link to="/hotel/recepcion/huespedes" @click="toggleMobileMenu">Huéspedes</router-link></li>
          </ul>
        </li>

        <li class="menu" v-if="can('pos.vender')">
          <router-link to="/hotel/pos" @click="toggleMobileMenu">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-shopping-bag"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
              <span>POS consumos</span>
            </div>
          </router-link>
        </li>

        <li class="menu" v-if="canAny(['facturacion.folios', 'facturacion.pagos'])">
          <router-link to="/hotel/facturacion/folios" @click="toggleMobileMenu">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-credit-card"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
              <span>Facturación</span>
            </div>
          </router-link>
        </li>

        <li class="menu" v-if="can('housekeeping.gestionar')">
          <router-link to="/hotel/housekeeping" @click="toggleMobileMenu">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-sun"><circle cx="12" cy="12" r="5"></circle></svg>
              <span>Housekeeping</span>
            </div>
          </router-link>
        </li>

        <li class="menu" v-if="can('hotel.configurar')">
          <a class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#configHotel">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-settings"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
              <span>Configuración hotel</span>
            </div>
            <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg></div>
          </a>
          <ul id="configHotel" class="collapse submenu list-unstyled">
            <li><router-link to="/hotel/config/propiedades" @click="toggleMobileMenu">Hoteles / propiedades</router-link></li>
            <li><router-link to="/hotel/config/tipos" @click="toggleMobileMenu">Tipos de habitación</router-link></li>
            <li><router-link to="/hotel/config/habitaciones" @click="toggleMobileMenu">Habitaciones</router-link></li>
            <li><router-link to="/hotel/config/tarifas" @click="toggleMobileMenu">Tarifas</router-link></li>
            <li><router-link to="/admin/configuracion-empresa" @click="toggleMobileMenu">Datos del hotel</router-link></li>
          </ul>
        </li>

        <li class="menu" v-if="can('reportes.ver')">
          <router-link to="/hotel/reportes" @click="toggleMobileMenu">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-bar-chart-2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
              <span>Reportes</span>
            </div>
          </router-link>
        </li>

        <li class="menu" v-if="canAny(['administracion.usuarios', 'administracion.roles'])">
          <a class="dropdown-toggle" data-bs-toggle="collapse" data-bs-target="#admin">
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
              <span>Administración</span>
            </div>
            <div><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg></div>
          </a>
          <ul id="admin" class="collapse submenu list-unstyled">
            <li v-if="can('administracion.usuarios')"><router-link to="/users/lista" @click="toggleMobileMenu">Usuarios</router-link></li>
            <li v-if="can('administracion.roles')"><router-link to="/roles/lista" @click="toggleMobileMenu">Roles</router-link></li>
          </ul>
        </li>
      </perfect-scrollbar>
    </nav>
  </div>
</template>

<script setup>
import { usePermissions } from '@/composables/use-permissions';
import { useStore } from 'vuex';

const store = useStore();
const { can, canAny } = usePermissions();

function toggleMobileMenu() {
  if (window.innerWidth < 991) {
    store.commit('toggleSideBar', !store.state.is_show_sidebar);
  }
}
</script>
