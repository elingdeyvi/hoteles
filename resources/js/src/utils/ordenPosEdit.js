/**
 * Orden editable en POS: aún en recepción y sin avanzar de estatus.
 */
export function puedeEditarOrdenEnPos(orden) {
  return orden?.estatus === "pendiente" && orden?.current_step === "recepcion";
}

/** Query para router hacia ventas/pos. */
export function posEditQueryFromOrden(orden) {
  if (!orden) return {};
  if (orden.folio_unico) {
    return { edit_folio: String(orden.folio_unico) };
  }
  if (orden.id != null) {
    return { edit_id: String(orden.id) };
  }
  return {};
}
