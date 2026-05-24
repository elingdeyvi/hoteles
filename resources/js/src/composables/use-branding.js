const DEFAULT_FAVICON = '/favicon.svg';

export function applyDocumentFavicon(href = DEFAULT_FAVICON) {
    const url = href || DEFAULT_FAVICON;
    let link = document.querySelector("link[rel*='icon']");
    if (!link) {
        link = document.createElement('link');
        link.rel = 'shortcut icon';
        document.head.appendChild(link);
    }
    link.href = url;
    link.type = url.endsWith('.svg') ? 'image/svg+xml' : 'image/png';
}

export function applyBookingTheme(primary, secondary) {
    const root = document.documentElement;
    if (primary) {
        root.style.setProperty('--booking-primary', primary);
    }
    if (secondary) {
        root.style.setProperty('--booking-secondary', secondary);
    }
}

export function clearBookingTheme() {
    document.documentElement.style.removeProperty('--booking-primary');
    document.documentElement.style.removeProperty('--booking-secondary');
}
