import { createRouter, createWebHistory } from 'vue-router';
import store from '../store';

const routes = [
  {
    path: '/',
    name: 'Home',
    component: () => import('../views/index.vue'),
  },
  {
    path: '/dashboard',
    name: 'hotel-dashboard-router',
    component: () => import('../views/hotel/dashboard.vue'),
    meta: { permission: 'dashboard.ver' },
  },
  {
    path: '/reservar',
    redirect: '/reservar/costa-azul',
  },
  {
    path: '/reservar/:propertySlug',
    component: () => import('@/layouts/auth-layout.vue'),
    children: [
      {
        path: '',
        name: 'booking-public-router',
        component: () => import('../views/hotel/booking.vue'),
        meta: { layout: 'auth', public: true },
      },
    ],
  },
  {
    path: '/auth/login',
    component: () => import('@/layouts/auth-layout.vue'),
    children: [
      {
        path: '',
        name: 'login-route',
        component: () => import('@/views/auth/login.vue'),
        meta: { layout: 'auth' },
      },
      {
        path: '/auth/pass-recovery-boxed',
        name: 'pass-recovery-boxed',
        component: () => import('@/views/auth/pass_recovery_boxed.vue'),
        meta: { layout: 'auth' },
      },
      {
        path: '/auth/changepw/:uuid',
        name: 'router-changepw-externo',
        component: () => import('@/views/users/changepw.vue'),
        meta: { layout: 'auth' },
      },
    ],
  },
  {
    path: '/users/profile',
    name: 'users-profile-router',
    component: () => import('@/views/users/profile.vue'),
  },
  {
    path: '/users/lista',
    name: 'users-lista-router',
    component: () => import('@/views/users/index.vue'),
    meta: { permission: 'administracion.usuarios' },
  },
  {
    path: '/hotel/config/propiedades',
    name: 'hotel-properties-router',
    component: () => import('../views/hotel/properties.vue'),
    meta: { permission: 'hotel.configurar' },
  },
  {
    path: '/hotel/config/tipos',
    name: 'hotel-room-types-router',
    component: () => import('../views/hotel/room-types.vue'),
    meta: { permission: 'hotel.configurar' },
  },
  {
    path: '/hotel/config/habitaciones',
    name: 'hotel-rooms-router',
    component: () => import('../views/hotel/rooms.vue'),
    meta: { permission: 'hotel.configurar' },
  },
  {
    path: '/hotel/config/tarifas',
    name: 'hotel-rates-router',
    component: () => import('../views/hotel/rates.vue'),
    meta: { permission: 'hotel.configurar' },
  },
  {
    path: '/hotel/recepcion/huespedes',
    name: 'hotel-huespedes-router',
    component: () => import('../views/hotel/huespedes.vue'),
    meta: { permission: 'recepcion.huespedes' },
  },
  {
    path: '/hotel/recepcion/reservas',
    name: 'hotel-reservations-router',
    component: () => import('../views/hotel/reservations.vue'),
    meta: { permission: 'recepcion.reservas' },
  },
  {
    path: '/hotel/recepcion/planning',
    name: 'hotel-planning-router',
    component: () => import('../views/hotel/planning.vue'),
    meta: { permission: 'recepcion.reservas' },
  },
  {
    path: '/hotel/recepcion/checkin/:id',
    name: 'hotel-checkin-router',
    component: () => import('../views/hotel/checkin.vue'),
    meta: { permission: 'recepcion.checkin' },
  },
  {
    path: '/hotel/pos',
    name: 'hotel-pos-router',
    component: () => import('../views/hotel/pos.vue'),
    meta: { permission: 'pos.vender' },
  },
  {
    path: '/hotel/pos/catalogo',
    name: 'hotel-pos-catalog-router',
    component: () => import('../views/hotel/pos-catalog.vue'),
    meta: { permission: 'pos.catalogo' },
  },
  {
    path: '/hotel/facturacion/folios',
    name: 'hotel-folio-router',
    component: () => import('../views/hotel/folios.vue'),
    meta: { permission: ['facturacion.folios', 'facturacion.pagos'] },
  },
  {
    path: '/hotel/housekeeping',
    name: 'hotel-housekeeping-router',
    component: () => import('../views/hotel/housekeeping.vue'),
    meta: { permission: 'housekeeping.gestionar' },
  },
  {
    path: '/hotel/reportes',
    name: 'hotel-reports-router',
    component: () => import('../views/hotel/reports.vue'),
    meta: { permission: 'reportes.ver' },
  },
  {
    path: '/roles/lista',
    name: 'roles-lista-router',
    component: () => import('@/views/roles/index.vue'),
    meta: { permission: 'administracion.roles' },
  },
  {
    path: '/admin/configuracion-empresa',
    name: 'admin-config-empresa-router',
    component: () => import('@/views/admin/configuracion-empresa.vue'),
    meta: { permission: ['hotel.configurar', 'administracion.usuarios'] },
  },
];

const router = createRouter({
  history: createWebHistory(),
  linkExactActiveClass: 'active',
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { left: 0, top: 0 };
  },
});

router.beforeEach(async (to, from, next) => {
  const token = window.localStorage.getItem('token');

  if (to.meta?.layout === 'auth') {
    store.commit('setLayout', 'auth');
  } else {
    store.commit('setLayout', 'app');
  }

  if (to.meta?.public) {
    next();
    return;
  }

  if (to.name !== 'login-route' && !token) {
    next('/auth/login');
    return;
  }

  if (token && to.name === 'login-route') {
    store.commit('setLayout', 'app');
    next('/');
    return;
  }

  if (to.meta?.permission && token) {
    try {
      const { usePermissions } = await import('@/composables/use-permissions');
      const { loadPermissions, hasPermission, hasAnyPermission } = usePermissions();
      await loadPermissions();
      const required = to.meta.permission;
      const hasAccess = Array.isArray(required)
        ? hasAnyPermission(required)
        : hasPermission(required);
      if (!hasAccess) {
        if (hasPermission('pos.vender')) {
          next({ name: 'hotel-pos-router', replace: true });
          return;
        }
        next({ name: 'Home', replace: true });
        return;
      }
    } catch (e) {
      if (import.meta.env.DEV) console.error(e);
    }
  }

  if (to.name === 'Home' && token) {
    try {
      const { usePermissions } = await import('@/composables/use-permissions');
      const { loadPermissions, hasPermission, hasAnyPermission } = usePermissions();
      await loadPermissions();

      if (hasPermission('dashboard.ver')) {
        next({ name: 'hotel-dashboard-router', replace: true });
        return;
      }
      if (hasPermission('pos.vender') && !hasAnyPermission([
        'recepcion.reservas',
        'recepcion.huespedes',
        'housekeeping.gestionar',
        'hotel.configurar',
        'facturacion.folios',
        'facturacion.pagos',
        'reportes.ver',
      ])) {
        next({ name: 'hotel-pos-router', replace: true });
        return;
      }
      if (hasPermission('recepcion.reservas')) {
        next({ name: 'hotel-reservations-router', replace: true });
        return;
      }
      if (hasPermission('housekeeping.gestionar')) {
        next({ name: 'hotel-housekeeping-router', replace: true });
        return;
      }
      if (hasPermission('hotel.configurar')) {
        next({ name: 'hotel-room-types-router', replace: true });
        return;
      }
      if (hasAnyPermission(['facturacion.folios', 'facturacion.pagos'])) {
        next({ name: 'hotel-folio-router', replace: true });
        return;
      }
      next({ name: 'users-profile-router', replace: true });
      return;
    } catch (e) {
      if (import.meta.env.DEV) console.error(e);
    }
  }

  next();
});

export default router;
