/**
 * Enlaces a WhatsApp Web / app (wa.me) para avisar al cliente.
 */

const DEFAULT_COUNTRY_CODE = "52";

/**
 * @param {string|null|undefined} raw
 * @param {string} [defaultCountry]
 * @returns {string|null} Solo dígitos con código de país (ej. 529936328613)
 */
export function normalizeWhatsAppPhone(raw, defaultCountry = DEFAULT_COUNTRY_CODE) {
  if (raw == null || String(raw).trim() === "") return null;
  let digits = String(raw).replace(/\D/g, "");
  if (!digits) return null;

  if (digits.length === 10 && defaultCountry) {
    digits = `${defaultCountry}${digits}`;
  } else if (digits.length === 11 && digits.startsWith("0") && defaultCountry) {
    digits = `${defaultCountry}${digits.slice(1)}`;
  }

  return digits.length >= 10 ? digits : null;
}

/**
 * @param {string} phoneDigits
 * @param {string} message
 * @returns {string}
 */
export function buildWhatsAppWebUrl(phoneDigits, message) {
  const phone = normalizeWhatsAppPhone(phoneDigits, "");
  if (!phone) return "";
  const text = encodeURIComponent(message || "");
  return `https://wa.me/${phone}${text ? `?text=${text}` : ""}`;
}

/**
 * Mensaje estándar: pedido listo para recoger (alineado con correo lista_cliente).
 *
 * @param {object} order
 * @param {{ trackingBaseUrl?: string, locale?: string }} [opts]
 */
export function buildOrderReadyWhatsAppMessage(order, opts = {}) {
  const nombre = order?.cliente_nombre || order?.cliente?.nombre || "cliente";
  const folio = order?.folio_unico || "";
  const servicio = order?.tipo_servicio || "";
  const total = Number(order?.total ?? 0).toFixed(2);
  const locale = opts.locale || "es";
  const trackingUrl =
    opts.trackingBaseUrl && folio
      ? `${String(opts.trackingBaseUrl).replace(/\/$/, "")}/seguimiento/${encodeURIComponent(folio)}`
      : "";

  if (locale.startsWith("en")) {
    let msg = `Hello ${nombre},\n\nYour laundry order is ready for pickup.\n\n`;
    msg += `Order: ${folio}\n`;
    if (servicio) msg += `Service: ${servicio}\n`;
    msg += `Total: $${total}\n`;
    if (trackingUrl) msg += `\nTrack your order: ${trackingUrl}\n`;
    msg += `\nThank you for choosing us.`;
    return msg;
  }

  let msg = `Hola ${nombre},\n\nTu pedido ya está listo para recoger.\n\n`;
  msg += `Folio: ${folio}\n`;
  if (servicio) msg += `Servicio: ${servicio}\n`;
  msg += `Total: $${total}\n`;
  if (trackingUrl) msg += `\nSeguimiento: ${trackingUrl}\n`;
  msg += `\nGracias por tu preferencia.`;
  return msg;
}

/**
 * @param {object} row — fila del historial (orden + cliente)
 * @returns {boolean}
 */
export function canNotifyOrderReadyWhatsApp(row) {
  if (!row || row.estatus !== "terminado") return false;
  return Boolean(normalizeWhatsAppPhone(row.cliente?.telefono));
}

/**
 * Abre WhatsApp Web con el mensaje de pedido listo.
 *
 * @param {object} row
 * @param {{ trackingBaseUrl?: string, locale?: string }} [opts]
 * @returns {boolean} false si no hay teléfono válido
 */
export function openOrderReadyWhatsApp(row, opts = {}) {
  const phone = row?.cliente?.telefono;
  const digits = normalizeWhatsAppPhone(phone);
  if (!digits) return false;
  const message = buildOrderReadyWhatsAppMessage(row, opts);
  const url = buildWhatsAppWebUrl(digits, message);
  if (!url) return false;
  window.open(url, "_blank", "noopener,noreferrer");
  return true;
}
