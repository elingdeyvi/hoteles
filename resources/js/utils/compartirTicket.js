const STATUS = {
    pendiente: 'Pendiente',
    confirmada: 'Confirmada',
    check_in: 'Check-in',
    check_out: 'Check-out',
    cancelada: 'Cancelada',
    abierto: 'Abierto',
    cerrado: 'Cerrado',
};

export function formatTelefonoWhatsApp(telefono, codigoPais = '52') {
    const digitos = String(telefono || '').replace(/\D/g, '');
    if (!digitos) return '';
    if (digitos.length === 10) return `${codigoPais}${digitos}`;
    if (digitos.length === 12 && digitos.startsWith(codigoPais)) return digitos;
    if (digitos.length === 13 && digitos.startsWith(codigoPais)) return digitos;
    return digitos;
}

export function money(n) {
    return Number(n || 0).toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
}

function day(value) {
    if (!value) return '—';
    return String(value).slice(0, 10);
}

function wrapLine(ctx, text, maxWidth) {
    const words = String(text || '').split(' ');
    const lines = [];
    let current = '';
    words.forEach((word) => {
        const next = current ? `${current} ${word}` : word;
        if (ctx.measureText(next).width > maxWidth && current) {
            lines.push(current);
            current = word;
        } else {
            current = next;
        }
    });
    if (current) lines.push(current);
    return lines.length ? lines : [''];
}

export function renderizarTicket(lineas, { titulo, subtitulo, color = '#1a365d' } = {}) {
    const width = 520;
    const pad = 32;
    const font = '18px Arial, sans-serif';
    const measure = document.createElement('canvas').getContext('2d');
    measure.font = font;
    const wrapped = lineas.flatMap((line) => (line === '---' ? ['---'] : wrapLine(measure, line, width - pad * 2)));
    const headerH = 108;
    const lineH = 30;
    const height = headerH + 28 + wrapped.length * lineH + 36;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    ctx.fillStyle = color;
    ctx.fillRect(0, 0, width, headerH);
    ctx.fillStyle = '#ffffff';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.font = '700 26px Arial, sans-serif';
    ctx.fillText(titulo || 'Hotel', width / 2, 42);
    ctx.font = '16px Arial, sans-serif';
    ctx.fillText(subtitulo || '', width / 2, 78);
    ctx.textAlign = 'left';
    ctx.textBaseline = 'top';
    ctx.font = font;
    let y = headerH + 24;
    wrapped.forEach((line) => {
        if (line === '---') {
            ctx.strokeStyle = '#d0d5dd';
            ctx.beginPath();
            ctx.moveTo(pad, y + 10);
            ctx.lineTo(width - pad, y + 10);
            ctx.stroke();
        } else {
            ctx.fillStyle = '#111827';
            ctx.fillText(line, pad, y);
        }
        y += lineH;
    });

    return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => {
            if (!(blob instanceof Blob) || blob.size < 1) {
                reject(new Error('No se pudo generar la imagen del ticket.'));
                return;
            }
            resolve(blob);
        }, 'image/png');
    });
}

export function prepararCompartirTicketImagen(ticketBlob, folio, options = {}) {
    const codigo = String(folio || '').trim() || 'ticket';
    const fileName = `${options.prefijo || 'ticket'}-${codigo}.png`;
    const mensaje = String(options.mensaje || '').trim() || `Ticket ${codigo}`;
    const title = String(options.title || '').trim() || `Ticket ${codigo}`;
    const pngBlob = ticketBlob instanceof Blob && ticketBlob.type === 'image/png'
        ? ticketBlob
        : new Blob([ticketBlob], { type: 'image/png' });
    const file = new File([pngBlob], fileName, { type: 'image/png' });
    const shareData = { files: [file], title, text: mensaje };
    const secure = typeof window !== 'undefined' && !!window.isSecureContext;
    const canShareFiles = !!(
        typeof navigator.share === 'function'
        && typeof navigator.canShare === 'function'
        && secure
        && navigator.canShare({ files: [file] })
    );
    const canUseSystemShare = typeof navigator.share === 'function' && secure;

    return {
        ok: true,
        fileName,
        pngBlob,
        shareData,
        canShareFiles,
        canUseSystemShare,
        mensaje,
        telefono: options.telefono || '',
        titulo: title,
    };
}

export function descargarBlob(blob, fileName) {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = fileName;
    anchor.click();
    URL.revokeObjectURL(url);
}

export function abrirWhatsAppWeb(telefono, mensaje) {
    const texto = encodeURIComponent(mensaje || '');
    const numero = formatTelefonoWhatsApp(telefono);
    const url = numero ? `https://wa.me/${numero}?text=${texto}` : `https://wa.me/?text=${texto}`;
    window.open(url, '_blank', 'noopener,noreferrer');
}

export async function compartirConGestoSistema(shareData) {
    if (!navigator.share) {
        return { ok: false, reason: 'unsupported' };
    }
    try {
        await navigator.share(shareData);
        return { ok: true, method: 'share' };
    } catch (error) {
        if (error?.name === 'AbortError') {
            return { ok: false, reason: 'cancelled' };
        }
        return { ok: false, reason: 'share_failed', error };
    }
}

function cierre(empresa = {}) {
    const lineas = ['Gracias por su preferencia. Quedamos a sus órdenes.'];
    if (empresa.telefono) lineas.push(`Tel: ${empresa.telefono}`);
    if (empresa.email) lineas.push(`Correo: ${empresa.email}`);
    return lineas;
}

export function mensajeReserva(reservation, empresa = {}) {
    const hotel = empresa.nombre || empresa.nombre_corto || 'el hotel';
    const huesped = reservation.huesped?.nombre || reservation.guest_name || 'Huésped';
    const folio = reservation.folio || '';
    const lineas = [
        `Estimado(a) ${huesped}:`,
        '',
        `Le compartimos la confirmación de su reserva con *${hotel}*.`,
        '',
        `*Folio:* ${folio}`,
        `*Entrada:* ${day(reservation.check_in)}`,
        `*Salida:* ${day(reservation.check_out)}`,
        `*Tipo:* ${reservation.room_type?.name || reservation.room_type || '—'}`,
        `*Habitación:* ${reservation.room?.number || 'Por asignar'}`,
        `*Estado:* ${STATUS[reservation.status] || reservation.status || '—'}`,
        `*Total estimado:* ${money(reservation.estimated_total)}`,
        '',
        'Adjunto encontrará la imagen de la reservación.',
        '',
        ...cierre(empresa),
    ];
    return lineas.join('\n');
}

export function mensajeFolio(folio, empresa = {}) {
    const hotel = empresa.nombre || empresa.nombre_corto || 'el hotel';
    const huesped = folio.stay?.reservation?.huesped?.nombre || 'Huésped';
    const lineas = [
        `Estimado(a) ${huesped}:`,
        '',
        `Le compartimos el ticket de su cuenta con *${hotel}*.`,
        '',
        `*Folio:* ${folio.folio_number}`,
        `*Habitación:* ${folio.stay?.room?.number || '—'}`,
        `*Saldo:* ${money(folio.balance)}`,
        `*Estado:* ${STATUS[folio.status] || folio.status || '—'}`,
        '',
        'Adjunto encontrará la imagen del ticket.',
        '',
        ...cierre(empresa),
    ];
    return lineas.join('\n');
}

export async function prepararReserva(reservation, empresa = {}) {
    const folio = reservation.folio || 'reserva';
    const color = empresa.color_primario || '#1a365d';
    const blob = await renderizarTicket([
        `Folio: ${folio}`,
        `Huésped: ${reservation.huesped?.nombre || reservation.guest_name || '—'}`,
        '---',
        `Entrada: ${day(reservation.check_in)}`,
        `Salida: ${day(reservation.check_out)}`,
        `Tipo: ${reservation.room_type?.name || reservation.room_type || '—'}`,
        `Habitación: ${reservation.room?.number || 'Por asignar'}`,
        `Huéspedes: ${reservation.guests_count || 1}`,
        `Estado: ${STATUS[reservation.status] || reservation.status || '—'}`,
        '---',
        `Total estimado: ${money(reservation.estimated_total)}`,
    ], {
        titulo: empresa.nombre_corto || empresa.nombre || 'Hotel',
        subtitulo: 'Reservación',
        color,
    });

    return prepararCompartirTicketImagen(blob, folio, {
        prefijo: 'reserva',
        title: `Reserva ${folio}`,
        mensaje: mensajeReserva(reservation, empresa),
        telefono: reservation.huesped?.telefono || reservation.telefono || '',
    });
}

export async function prepararFolio(folio, empresa = {}) {
    const codigo = folio.folio_number || 'folio';
    const cargos = (folio.charges || []).map((item) => `${item.concept}: ${money(Number(item.amount) * Number(item.quantity || 1))}`);
    const pagos = (folio.payments || []).map((item) => `${item.payment_method}: ${money(item.amount)}`);
    const blob = await renderizarTicket([
        `Folio: ${codigo}`,
        `Huésped: ${folio.stay?.reservation?.huesped?.nombre || '—'}`,
        `Habitación: ${folio.stay?.room?.number || '—'}`,
        `Reserva: ${folio.stay?.reservation?.folio || '—'}`,
        '---',
        'Cargos',
        ...(cargos.length ? cargos : ['Sin cargos']),
        '---',
        'Pagos',
        ...(pagos.length ? pagos : ['Sin pagos']),
        '---',
        `Saldo: ${money(folio.balance)}`,
        `Estado: ${STATUS[folio.status] || folio.status || '—'}`,
    ], {
        titulo: empresa.nombre_corto || empresa.nombre || 'Hotel',
        subtitulo: 'Ticket de cuenta',
        color: empresa.color_primario || '#1a365d',
    });

    return prepararCompartirTicketImagen(blob, codigo, {
        prefijo: 'ticket',
        title: `Ticket ${codigo}`,
        mensaje: mensajeFolio(folio, empresa),
        telefono: folio.stay?.reservation?.huesped?.telefono || '',
    });
}
