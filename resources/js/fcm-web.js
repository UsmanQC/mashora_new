const FCM_SDK_VERSION = '11.6.0';

function readCookie(name) {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1')}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

function csrfHeaders() {
    const xsrf = readCookie('XSRF-TOKEN');

    return {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
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

    const existing = await navigator.serviceWorker.getRegistration('/');

    if (existing) {
        return existing;
    }

    return navigator.serviceWorker.register('/sw.js', { scope: '/' });
}

async function registerDeviceToken(registerUrl, token) {
    const response = await fetch(registerUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: csrfHeaders(),
        body: JSON.stringify({ device_token: token }),
    });

    if (!response.ok) {
        throw new Error(`Device token registration failed (${response.status})`);
    }
}

/**
 * Request notification permission, obtain an FCM web token, and save it on the server.
 */
export async function initFcmWebPush() {
    const config = fcmConfig();

    if (!config || !('Notification' in window)) {
        return;
    }

    try {
        const permission = Notification.permission === 'granted'
            ? 'granted'
            : await Notification.requestPermission();

        if (permission !== 'granted') {
            return;
        }

        const firebaseMessaging = await loadFirebaseMessaging(config.firebase);

        if (!firebaseMessaging) {
            return;
        }

        const registration = await ensureServiceWorker();

        if (!registration) {
            return;
        }

        const token = await firebaseMessaging.getToken(firebaseMessaging.messaging, {
            vapidKey: config.vapidKey,
            serviceWorkerRegistration: registration,
        });

        if (!token || token.length < 10) {
            return;
        }

        const storageKey = `awaan_fcm_token_${config.portal}`;

        if (window.localStorage.getItem(storageKey) === token) {
            return;
        }

        await registerDeviceToken(config.registerUrl, token);
        window.localStorage.setItem(storageKey, token);
    } catch (error) {
        console.warn('FCM web push registration skipped:', error);
    }
}
