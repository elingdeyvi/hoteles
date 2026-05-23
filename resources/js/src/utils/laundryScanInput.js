/**
 * Alineado con {@see \App\Support\LaundryScanInput::normalize} (PHP).
 * Si el lector pega una URL de seguimiento, devuelve solo el folio.
 */
export function normalizeLaundryScanInput(raw) {
  const s = String(raw ?? "").trim();
  if (!s) return "";

  const seg = s.match(/\/seguimiento\/([^/?#]+)/i);
  if (seg?.[1]) {
    try {
      return decodeURIComponent(seg[1]);
    } catch {
      return seg[1];
    }
  }

  const fu = s.match(/[?&]folio_unico=([^&=#]+)/i);
  if (fu?.[1]) {
    try {
      return decodeURIComponent(fu[1]);
    } catch {
      return fu[1];
    }
  }

  const fo = s.match(/[?&]folio=([^&=#]+)/i);
  if (fo?.[1]) {
    try {
      return decodeURIComponent(fo[1]);
    } catch {
      return fo[1];
    }
  }

  return s;
}

/** Contenido del QR para operación (escanear / laundry scans): codigo_qr de la orden o folio. */
export function ticketQrPayload(orden) {
  if (!orden || typeof orden !== "object") return "";
  const cq = String(orden.codigo_qr ?? "").trim();
  if (cq) return cq;
  return String(orden.folio_unico ?? "").trim();
}
