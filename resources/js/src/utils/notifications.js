/**
 * Utilidades para mostrar notificaciones al usuario
 */

/**
 * Muestra un mensaje de notificación al usuario
 * @param {string} message - El mensaje a mostrar
 * @param {string} type - El tipo de notificación ('success', 'error', 'warning', 'info')
 * @param {number} duration - Duración en milisegundos (opcional, por defecto 3000)
 */
export function showMessage(message, type = 'info', duration = 3000) {
    // Verificar si existe SweetAlert2
    if (typeof Swal !== 'undefined') {
        const iconMap = {
            success: 'success',
            error: 'error',
            warning: 'warning',
            info: 'info'
        };

        Swal.fire({
            icon: iconMap[type] || 'info',
            title: getTitleByType(type),
            text: message,
            timer: duration,
            timerProgressBar: true,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    } else {
        // Fallback a alert nativo si SweetAlert2 no está disponible
        console.log(`[${type.toUpperCase()}] ${message}`);
        alert(message);
    }
}

/**
 * Muestra un mensaje de confirmación
 * @param {string} message - El mensaje a mostrar
 * @param {string} title - El título del diálogo
 * @returns {Promise<boolean>} - True si el usuario confirma, false si cancela
 */
export function showConfirm(message, title = 'Confirmar') {
    return new Promise((resolve) => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí',
                cancelButtonText: 'No'
            }).then((result) => {
                resolve(result.isConfirmed);
            });
        } else {
            // Fallback a confirm nativo
            resolve(confirm(message));
        }
    });
}

/**
 * Muestra un mensaje de éxito
 * @param {string} message - El mensaje a mostrar
 * @param {number} duration - Duración en milisegundos
 */
export function showSuccess(message, duration = 3000) {
    showMessage(message, 'success', duration);
}

/**
 * Muestra un mensaje de error
 * @param {string} message - El mensaje a mostrar
 * @param {number} duration - Duración en milisegundos
 */
export function showError(message, duration = 5000) {
    showMessage(message, 'error', duration);
}

/**
 * Muestra un mensaje de advertencia
 * @param {string} message - El mensaje a mostrar
 * @param {number} duration - Duración en milisegundos
 */
export function showWarning(message, duration = 4000) {
    showMessage(message, 'warning', duration);
}

/**
 * Muestra un mensaje informativo
 * @param {string} message - El mensaje a mostrar
 * @param {number} duration - Duración en milisegundos
 */
export function showInfo(message, duration = 3000) {
    showMessage(message, 'info', duration);
}

/**
 * Obtiene el título apropiado según el tipo de notificación
 * @param {string} type - El tipo de notificación
 * @returns {string} - El título correspondiente
 */
function getTitleByType(type) {
    const titles = {
        success: 'Éxito',
        error: 'Error',
        warning: 'Advertencia',
        info: 'Información'
    };
    return titles[type] || 'Notificación';
}

/**
 * Muestra un loading/indicador de carga
 * @param {string} message - El mensaje a mostrar
 */
export function showLoading(message = 'Cargando...') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
}

/**
 * Oculta el loading/indicador de carga
 */
export function hideLoading() {
    if (typeof Swal !== 'undefined') {
        Swal.close();
    }
}
