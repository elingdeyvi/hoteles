import { ref } from "vue";
import * as PublicTrackingRepository from "@/repositories/PublicTrackingRepository";
import * as OrderPresenceRepository from "@/repositories/OrderPresenceRepository";

/**
 * Seguimiento de orden: público por folio y presencia por área (autenticado).
 */
export function useOrderTracking() {
  const loading = ref(false);
  const error = ref(null);
  const validationErrors = ref(null);
  /** Datos públicos (sin PII) cuando se usa loadPublicByFolio. */
  const publicData = ref(null);
  /** Datos de presencia cuando se usa loadPresenceByOrderId. */
  const presenceData = ref(null);

  const reset = () => {
    error.value = null;
    validationErrors.value = null;
    publicData.value = null;
    presenceData.value = null;
  };

  /**
   * GET /public/tracking/{folio} — sin autenticación requerida (token opcional).
   */
  const loadPublicByFolio = async (folio) => {
    loading.value = true;
    error.value = null;
    validationErrors.value = null;
    publicData.value = null;
    try {
      const body = await PublicTrackingRepository.getByFolio(folio);
      publicData.value = body?.data ?? null;
      return body;
    } catch (e) {
      const res = e.response?.data;
      const status = e.response?.status;
      error.value =
        res?.message ||
        (status === 404 ? "Orden no encontrada." : e.message) ||
        "Error al consultar el seguimiento.";
      validationErrors.value = res?.errors ?? null;
      throw e;
    } finally {
      loading.value = false;
    }
  };

  /**
   * GET /laundry/orders/{id}/presence — requiere sesión y permisos.
   */
  const loadPresenceByOrderId = async (orderId) => {
    loading.value = true;
    error.value = null;
    validationErrors.value = null;
    presenceData.value = null;
    try {
      const body = await OrderPresenceRepository.getPresenceByOrderId(orderId);
      presenceData.value = body?.data ?? null;
      return body;
    } catch (e) {
      const res = e.response?.data;
      error.value =
        res?.message ||
        e.message ||
        "No se pudo cargar la presencia de la orden.";
      validationErrors.value = res?.errors ?? null;
      throw e;
    } finally {
      loading.value = false;
    }
  };

  return {
    loading,
    error,
    validationErrors,
    publicData,
    presenceData,
    loadPublicByFolio,
    loadPresenceByOrderId,
    reset,
  };
}
