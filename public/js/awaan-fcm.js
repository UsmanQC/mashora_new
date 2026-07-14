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

function pushEnableBanner() {
    return document.getElementById('awaan-push-enable');
}

function showPushEnableBanner() {
    const el = pushEnableBanner();
    if (!el) {
        return;
    }
    el.style.display = '';
    el.classList?.remove?.('hidden');
}

function hidePushEnableBanner() {
    const el = pushEnableBanner();
    if (!el) {
        return;
    }
    el.style.display = 'none';
}

async function loadFirebaseMessaging(firebaseConfig) {
    const [
        { initializeApp },
        { getMessaging, getToken, onMessage, isSupported },
    ] = await Promise.all([
        import(`https://www.gstatic.com/firebasejs/${FCM_SDK_VERSION}/firebase-app.js`),
        import(`https://www.gstatic.com/firebasejs/${FCM_SDK_VERSION}/firebase-messaging.js`),
    ]);

    if (!(await isSupported())) {
        console.warn('FCM web push is not supported in this browser.');

        return null;
    }

    const app = initializeApp(firebaseConfig);
    const messaging = getMessaging(app);

    // Foreground: browser will not auto-show system notifications without this.
    onMessage(messaging, (payload) => {
        const title = payload.notification?.title || payload.data?.title || 'Awaan';
        const body = payload.notification?.body || payload.data?.body || '';

        if (Notification.permission === 'granted') {
            new Notification(title, {
                body,
                icon: '/images/pwa/icon-192-v3.png',
                data: payload.data || {},
            });
        }
    });

    return {
        messaging,
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

async function obtainAndRegisterToken() {
    if (!window.isSecureContext) {
        throw new Error('Push requires HTTPS (or localhost).');
    }

    const config = fcmConfig();

    if (!config) {
        throw new Error('FCM web config missing (window.__AWAAN_FCM__).');
    }

    if (!('Notification' in window)) {
        throw new Error('Notification API not available.');
    }

    const firebaseMessaging = await loadFirebaseMessaging(config.firebase);

    if (!firebaseMessaging) {
        throw new Error('Firebase messaging is not supported here.');
    }

    const registration = await ensureServiceWorker();

    if (!registration) {
        throw new Error('Service worker registration failed.');
    }

    const token = await firebaseMessaging.getToken(firebaseMessaging.messaging, {
        vapidKey: config.vapidKey,
        serviceWorkerRegistration: registration,
    });

    if (!token || token.length < 10) {
        throw new Error('FCM getToken returned an empty token.');
    }

    await registerDeviceToken(config.registerUrl, token);
    window.localStorage.setItem(`awaan_fcm_token_${config.portal}`, token);
    console.info('FCM device token registered.');
    hidePushEnableBanner();

    return token;
}

async function enableAwaanPushNotifications() {
    try {
        const permission = Notification.permission === 'granted'
            ? 'granted'
            : await Notification.requestPermission();

        if (permission !== 'granted') {
            console.warn('Notification permission not granted:', permission);
            showPushEnableBanner();

            return false;
        }

        await obtainAndRegisterToken();

        return true;
    } catch (error) {
        console.warn('FCM web push registration failed:', error);
        showPushEnableBanner();

        return false;
    }
}

async function initFcmWebPush() {
    if (!window.isSecureContext || !fcmConfig() || !('Notification' in window)) {
        return;
    }

    if (Notification.permission === 'granted') {
        try {
            await obtainAndRegisterToken();
        } catch (error) {
            console.warn('FCM token sync failed:', error);
            showPushEnableBanner();
        }

        return;
    }

    showPushEnableBanner();
}

window.enableAwaanPushNotifications = enableAwaanPushNotifications;
window.initFcmWebPush = initFcmWebPush;

if (document.readyState === 'complete') {
    initFcmWebPush();
} else {
    window.addEventListener('load', () => {
        initFcmWebPush();
    });
}
