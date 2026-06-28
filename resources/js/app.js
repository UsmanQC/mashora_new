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

const PATIENT_LOADER_MIN_VISIBLE_MS = 450;

function initPatientPortalNavLoader() {
    const loader = document.querySelector('[data-patient-portal-loader]');
    if (!loader || loader.dataset.loaderBound === '1') {
        return;
    }

    loader.dataset.loaderBound = '1';

    let shownAt = 0;
    let hideTimer = null;

    const show = () => {
        if (hideTimer !== null) {
            clearTimeout(hideTimer);
            hideTimer = null;
        }

        shownAt = Date.now();
        loader.classList.remove('hidden');
        loader.classList.add('flex');
        loader.setAttribute('aria-busy', 'true');
    };

    const hide = () => {
        const delay = Math.max(0, PATIENT_LOADER_MIN_VISIBLE_MS - (Date.now() - shownAt));

        hideTimer = setTimeout(() => {
            loader.classList.add('hidden');
            loader.classList.remove('flex');
            loader.setAttribute('aria-busy', 'false');
            hideTimer = null;
        }, delay);
    };

    document.addEventListener('livewire:navigating', show);
    document.addEventListener('livewire:navigated', hide);

    if (document.readyState !== 'complete') {
        show();
        window.addEventListener('load', () => hide(), { once: true });
    }

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            hide();
        }
    });
}

if (document.querySelector('[data-patient-portal-loader]')) {
    initPatientPortalNavLoader();
}

document.addEventListener('DOMContentLoaded', initPatientPortalNavLoader);
document.addEventListener('livewire:init', initPatientPortalNavLoader);
