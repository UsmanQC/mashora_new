const FCM_SDK_VERSION = '11.6.0';

function readCookie(name) {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1')}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

function csrfHeaders() {
    const xsrf = readCookie('XSRF-TOKEN');
    const meta = document.querySelector('meta[name="csrf-token"]');
    const csrf = meta instanceof HTMLMetaElement ? meta.content : null;

    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
    };
}

function fcmConfig() {
    const config = window.__AWAAN_FCM__;

    if (!config?.enabled || !config.vapidKey || !config.registerUrl || !config.firebase?.apiKey) {
        return null;
    }

    return config;
}

async function loadFirebaseMessaging(firebaseConfig) {
    const [{ initializeApp }, { getMessaging, getToken, isSupported }] = await Promise.all([
        import(`https://www.gstatic.com/firebasejs/${FCM_SDK_VERSION}/firebase-app.js`),
        import(`https://www.gstatic.com/firebasejs/${FCM_SDK_VERSION}/firebase-messaging.js`),
    ]);

    if (!(await isSupported())) {
        console.warn('FCM web push is not supported in this browser.');

        return null;
    }

    const app = initializeApp(firebaseConfig);

    return {
        messaging: getMessaging(app),
        getToken,
    };
}

async function ensureServiceWorker() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }

    let registration = await navigator.serviceWorker.getRegistration('/');

    if (!registration) {
        registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
    }

    await navigator.serviceWorker.ready;

    return registration;
}

async function registerDeviceToken(registerUrl, token) {
    const response = await fetch(registerUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: csrfHeaders(),
        body: JSON.stringify({ device_token: token }),
    });

    if (!response.ok) {
        const body = await response.text();

        throw new Error(`Device token registration failed (${response.status}): ${body}`);
    }
}

/**
 * Request notification permission, obtain an FCM web token, and save it on the server.
 */
export async function initFcmWebPush() {
    if (!window.isSecureContext) {
        console.warn('FCM web push requires HTTPS (or localhost). Current page is not a secure context.');

        return;
    }

    const config = fcmConfig();

    if (!config) {
        console.warn('FCM web push config missing (window.__AWAAN_FCM__). Check FIREBASE_WEB_* env and config cache.');

        return;
    }

    if (!('Notification' in window)) {
        console.warn('This browser has no Notification API.');

        return;
    }

    try {
        const permission = Notification.permission === 'granted'
            ? 'granted'
            : await Notification.requestPermission();

        if (permission !== 'granted') {
            console.warn('Notification permission not granted:', permission);

            return;
        }

        const firebaseMessaging = await loadFirebaseMessaging(config.firebase);

        if (!firebaseMessaging) {
            return;
        }

        const registration = await ensureServiceWorker();

        if (!registration) {
            console.warn('Service worker registration failed.');

            return;
        }

        const token = await firebaseMessaging.getToken(firebaseMessaging.messaging, {
            vapidKey: config.vapidKey,
            serviceWorkerRegistration: registration,
        });

        if (!token || token.length < 10) {
            console.warn('FCM getToken returned an empty token.');

            return;
        }

        // Always POST — localStorage can be stale vs DB (different server / cleared table).
        await registerDeviceToken(config.registerUrl, token);
        window.localStorage.setItem(`awaan_fcm_token_${config.portal}`, token);
        console.info('FCM device token registered.');
    } catch (error) {
        console.warn('FCM web push registration skipped:', error);
    }
}
