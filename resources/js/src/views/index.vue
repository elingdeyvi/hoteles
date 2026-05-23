<template>
  <div class="layout-px-spacing">
    <div class="row layout-top-spacing">
      <div class="col-xl-12">
        <div class="panel hotel-panel p-5 text-center">
          <svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-primary mb-3">
            <path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path>
            <path d="M9 9h1"></path><path d="M9 13h1"></path><path d="M9 17h1"></path>
          </svg>
          <h2>Sistema de gestión hotelera</h2>
          <p class="text-muted lead">Recepción, reservas, facturación y housekeeping en un solo lugar.</p>
          <div class="row justify-content-center mt-4 g-3">
            <div class="col-md-3" v-if="can('recepcion.reservas')">
              <router-link to="/hotel/recepcion/planning" class="btn btn-primary w-100">Planning</router-link>
            </div>
            <div class="col-md-3" v-if="can('recepcion.reservas')">
              <router-link to="/hotel/recepcion/reservas" class="btn btn-outline-primary w-100">Reservaciones</router-link>
            </div>
            <div class="col-md-3" v-if="can('dashboard.ver')">
              <router-link to="/dashboard" class="btn btn-outline-primary w-100">Dashboard</router-link>
            </div>
            <div class="col-md-3" v-if="can('pos.vender')">
              <router-link to="/hotel/pos" class="btn btn-primary w-100">POS consumos</router-link>
            </div>
            <div class="col-md-3" v-if="can('housekeeping.gestionar')">
              <router-link to="/hotel/housekeeping" class="btn btn-outline-secondary w-100">Housekeeping</router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { usePermissions } from '@/composables/use-permissions';

const { can } = usePermissions();
</script>
