import api from '@/services/ApiService';

const base = '/hotel';

export default {
  dashboard() {
    return api.get('/dashboard/resumen').then((r) => r.data);
  },
  roomTypes() {
    return api.get(`${base}/room-types`).then((r) => r.data);
  },
  saveRoomType(payload, id) {
    return id ? api.put(`${base}/room-types/${id}`, payload) : api.post(`${base}/room-types`, payload);
  },
  properties() {
    return api.get(`${base}/properties`).then((r) => r.data);
  },
  propertiesManage() {
    return api.get(`${base}/properties/manage`).then((r) => r.data);
  },
  saveProperty(payload, id) {
    return id ? api.put(`${base}/properties/${id}`, payload) : api.post(`${base}/properties`, payload);
  },
  deleteProperty(id) {
    return api.delete(`${base}/properties/${id}`);
  },
  rooms(params) {
    return api.get(`${base}/rooms`, { params }).then((r) => r.data);
  },
  saveRoom(payload, id) {
    return id ? api.put(`${base}/rooms/${id}`, payload) : api.post(`${base}/rooms`, payload);
  },
  rates(params) {
    return api.get(`${base}/rates`, { params }).then((r) => r.data);
  },
  saveRate(payload, id) {
    return id ? api.put(`${base}/rates/${id}`, payload) : api.post(`${base}/rates`, payload);
  },
  huespedes(params) {
    return api.get(`${base}/huespedes`, { params }).then((r) => r.data);
  },
  saveHuesped(payload, id) {
    return id ? api.put(`${base}/huespedes/${id}`, payload) : api.post(`${base}/huespedes`, payload);
  },
  reservations(params) {
    return api.get(`${base}/reservations`, { params }).then((r) => r.data);
  },
  availability(params) {
    return api.get(`${base}/reservations/availability`, { params }).then((r) => r.data);
  },
  planningCalendar(params) {
    return api.get(`${base}/planning/calendar`, { params }).then((r) => r.data);
  },
  planningRoomBoard(params) {
    return api.get(`${base}/planning/room-board`, { params }).then((r) => r.data);
  },
  saveReservation(payload, id) {
    return id ? api.put(`${base}/reservations/${id}`, payload) : api.post(`${base}/reservations`, payload);
  },
  cancelReservation(id) {
    return api.post(`${base}/reservations/${id}/cancel`);
  },
  confirmReservation(id) {
    return api.post(`${base}/reservations/${id}/confirm`);
  },
  checkIn(id, roomId) {
    return api.post(`${base}/reservations/${id}/check-in`, { room_id: roomId });
  },
  checkOut(id) {
    return api.post(`${base}/reservations/${id}/check-out`).then((r) => r.data.data);
  },
  folios(params) {
    return api.get(`${base}/folios`, { params }).then((r) => r.data);
  },
  folio(id) {
    return api.get(`${base}/folios/${id}`).then((r) => r.data);
  },
  addCharge(folioId, payload) {
    return api.post(`${base}/folios/${folioId}/charges`, payload);
  },
  addPayment(folioId, payload) {
    return api.post(`${base}/folios/${folioId}/payments`, payload);
  },
  closeFolio(folioId) {
    return api.post(`${base}/folios/${folioId}/close`);
  },
  async downloadInvoicePdf(folioId) {
    const response = await api.get(`${base}/folios/${folioId}/invoice-pdf`, { responseType: 'blob' });
    const blob = new Blob([response.data], { type: 'application/pdf' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `factura-folio-${folioId}.pdf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  },
  housekeepingBoard() {
    return api.get(`${base}/housekeeping/board`).then((r) => r.data);
  },
  updateRoomStatus(roomId, status) {
    return api.patch(`${base}/housekeeping/rooms/${roomId}/status`, { status });
  },
  reportOccupancy(params) {
    return api.get(`${base}/reports/occupancy`, { params }).then((r) => r.data);
  },
  reportRevenue(params) {
    return api.get(`${base}/reports/revenue`, { params }).then((r) => r.data);
  },
  reportArrivals(params) {
    return api.get(`${base}/reports/arrivals-departures`, { params }).then((r) => r.data);
  },
  reportPosSales(params) {
    return api.get(`${base}/reports/pos-sales`, { params }).then((r) => r.data);
  },
  async downloadReportCsv(path, params, filename) {
    const response = await api.get(`${base}/reports/${path}`, { params, responseType: 'blob' });
    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  },
  posCatalog(params) {
    return api.get(`${base}/pos/catalog`, { params }).then((r) => r.data);
  },
  posRoomsInHouse() {
    return api.get(`${base}/pos/rooms-in-house`).then((r) => r.data);
  },
  posChargeToFolio(payload) {
    return api.post(`${base}/pos/charge-to-folio`, payload).then((r) => r.data);
  },
  posAdminCatalog() {
    return api.get(`${base}/pos/admin/catalog`).then((r) => r.data);
  },
  posSaveProduct(payload, id) {
    return id ? api.put(`${base}/pos/admin/products/${id}`, payload) : api.post(`${base}/pos/admin/products`, payload);
  },
  posSaveCategory(payload) {
    return api.post(`${base}/pos/admin/categories`, payload);
  },
  posSaveOutlet(payload) {
    return api.post(`${base}/pos/admin/outlets`, payload);
  },
};
