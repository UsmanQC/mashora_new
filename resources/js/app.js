if ('serviceWorker' in navigator && document.location.pathname.startsWith('/patient')) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
