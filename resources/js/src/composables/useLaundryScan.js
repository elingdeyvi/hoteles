import { ref } from "vue";
import * as LaundryScanRepository from "@/repositories/LaundryScanRepository";

/**
 * Estado local para escaneos TIBU (POST /laundry/scans).
 */
export function useLaundryScan() {
  const loading = ref(false);
  const error = ref(null);
  const validationErrors = ref(null);
  /** Último payload útil de la API (típicamente `data` del cuerpo). */
  const lastData = ref(null);

  const reset = () => {
    error.value = null;
    validationErrors.value = null;
    lastData.value = null;
  };

  /**
   * @param {{ qrOrFolio: string, areaCode: string }} input
   * @returns {Promise<object>} response.data del backend
   */
  const submitScan = async (input) => {
    loading.value = true;
    error.value = null;
    validationErrors.value = null;
    lastData.value = null;
    try {
      const body = await LaundryScanRepository.create({
        qrOrFolio: input.qrOrFolio,
        areaCode: input.areaCode,
      });
      lastData.value = body?.data ?? null;
      return body;
    } catch (e) {
      const res = e.response?.data;
      error.value =
        res?.message ||
        e.message ||
        "No se pudo registrar el escaneo.";
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
    lastData,
    submitScan,
    reset,
  };
}
