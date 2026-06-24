const PWA_SCOPES = ['/patient', '/doctor'];

if ('serviceWorker' in navigator) {
    const pathname = document.location.pathname;
    const isPwaScope = PWA_SCOPES.some((scope) => pathname === scope || pathname.startsWith(`${scope}/`));

    if (isPwaScope) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
        });
    }
}
