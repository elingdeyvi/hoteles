import { useRouter } from "vue-router";
import { puedeEditarOrdenEnPos, posEditQueryFromOrden } from "@/utils/ordenPosEdit";

/**
 * Navega a POS para editar la orden si aún está en recepción; si no, ejecuta fallback.
 */
export function useNavigatePosEdit() {
  const router = useRouter();

  async function irAEditarEnPos(orden, { fallback } = {}) {
    if (!orden) return false;
    if (puedeEditarOrdenEnPos(orden)) {
      await router.push({
        name: "ventas-pos-router",
        query: posEditQueryFromOrden(orden),
      });
      return true;
    }
    if (typeof fallback === "function") {
      fallback(orden);
    }
    return false;
  }

  return { irAEditarEnPos, puedeEditarOrdenEnPos };
}
