/**
 * Paletas predefinidas del sistema.
 * primary → acentos, botones, links activos
 * secondary → menú lateral / barra
 */
export const POS_PALETTES = [
    {
        id: 'azul_corporativo',
        nombre: 'Azul corporativo',
        color_primario: '#0070c0',
        color_secundario: '#1e293b',
        descripcion: 'Marca POS negocios',
    },
    {
        id: 'slate',
        nombre: 'Slate profesional',
        color_primario: '#334155',
        color_secundario: '#0f172a',
        descripcion: 'Grises sobrios',
    },
    {
        id: 'verde',
        nombre: 'Verde negocio',
        color_primario: '#0f766e',
        color_secundario: '#134e4a',
        descripcion: 'Teal / confianza',
    },
    {
        id: 'indigo',
        nombre: 'Índigo',
        color_primario: '#4f46e5',
        color_secundario: '#1e1b4b',
        descripcion: 'Moderno',
    },
    {
        id: 'granate',
        nombre: 'Granate',
        color_primario: '#b91c1c',
        color_secundario: '#450a0a',
        descripcion: 'Alto contraste',
    },
    {
        id: 'ambar',
        nombre: 'Ámbar',
        color_primario: '#d97706',
        color_secundario: '#1c1917',
        descripcion: 'Cálido',
    },
];

/** Oscurece un hex (#rrggbb) aproximadamente. */
export function shadeHex(hex, percent = -15) {
    const raw = (hex || '#0070c0').replace('#', '');
    if (raw.length !== 6) return hex;
    const num = parseInt(raw, 16);
    let r = (num >> 16) & 0xff;
    let g = (num >> 8) & 0xff;
    let b = num & 0xff;
    const t = percent < 0 ? 0 : 255;
    const p = Math.abs(percent) / 100;
    r = Math.round((t - r) * p + r);
    g = Math.round((t - g) * p + g);
    b = Math.round((t - b) * p + b);
    return `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
}

/**
 * Aplica la paleta de empresa al documento (menú, barra, botones).
 */
export function applyPosTheme(empresa = {}) {
    const root = document.documentElement;
    const primary = empresa.color_primario || '#0070c0';
    const secondary = empresa.color_secundario || '#1e293b';
    const primaryDark = shadeHex(primary, -18);
    const primarySoft = shadeHex(primary, 78);

    root.style.setProperty('--pos-primary', primary);
    root.style.setProperty('--pos-primary-dark', primaryDark);
    root.style.setProperty('--pos-primary-soft', primarySoft);
    root.style.setProperty('--pos-secondary', secondary);
    root.style.setProperty('--pos-sidebar-bg', secondary);
    root.style.setProperty('--pos-sidebar-hover', shadeHex(secondary, 12));
    root.style.setProperty('--pos-navbar-bg', '#ffffff');
    root.style.setProperty('--pos-navbar-border', shadeHex(secondary, 82));

    const modo = empresa.tema_modo || 'claro';
    let dark = modo === 'oscuro';
    if (modo === 'sistema' && window.matchMedia) {
        dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
    root.style.colorScheme = dark ? 'dark' : 'light';
    root.style.setProperty('--pos-navbar-bg', dark ? shadeHex(secondary, 8) : '#ffffff');
}
