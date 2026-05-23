import api from '@/services/ApiService';

export function createBookingRepository(propertySlug = 'costa-azul') {
  const base = `/booking/${propertySlug}`;

  return {
    config() {
      return api.get(`${base}/config`).then((r) => r.data);
    },
    roomTypes() {
      return api.get(`${base}/room-types`).then((r) => r.data);
    },
    availability(params) {
      return api.get(`${base}/availability`, { params }).then((r) => r.data);
    },
    createReservation(payload) {
      return api.post(`${base}/reservations`, payload).then((r) => r.data);
    },
    lookup(params) {
      return api.get(`${base}/reservations/lookup`, { params }).then((r) => r.data);
    },
    checkout(payload) {
      return api.post(`${base}/payments/checkout`, payload).then((r) => r.data);
    },
    demoConfirm(payload) {
      return api.post(`${base}/payments/demo-confirm`, payload).then((r) => r.data);
    },
  };
}

export default createBookingRepository('costa-azul');
