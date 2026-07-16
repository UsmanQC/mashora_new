const CACHE_VERSION = 'awaan-v4';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/doctor/manifest.webmanifest',
    '/images/pwa/icon-192-v3.png',
    '/images/pwa/icon-512-v3.png',
    '/images/pwa/icon-192-maskable-v3.png',
    '/images/pwa/icon-512-maskable-v3.png',
    '/images/favicon-awaan.png',
];

// Firebase web config is public (same values as the web app). Keep in sync with .env / config/push.php.
importScripts('https://www.gstatic.com/firebasejs/11.6.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/11.6.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: 'AIzaSyCZvzHslI0TkppJtmTInYrXMsw8sasd_ow',
    authDomain: 'awaan-66719.firebaseapp.com',
    projectId: 'awaan-66719',
    storageBucket: 'awaan-66719.firebasestorage.app',
    messagingSenderId: '894339707747',
    appId: '1:894339707747:web:05d6c3ab33d249fd1a9956',
});

firebase.messaging().onBackgroundMessage((payload) => {
    const title = payload.notification?.title || payload.data?.title || 'Awaan';
    const body = payload.notification?.body || payload.data?.body || '';
    const data = payload.data || {};

    return self.registration.showNotification(title, {
        body,
        data,
        icon: '/images/pwa/icon-192-v3.png',
        badge: '/images/pwa/icon-192-v3.png',
    });
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const data = event.notification.data || {};
    const target = data.click_url || data.action || data.link || '/patient';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(target);
            }

            return undefined;
        }),
    );
});

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_URLS)).then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys
                    .filter((key) => key.startsWith('awaan-') && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key)),
            ),
        ).then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => response)
                .catch(async () => {
                    const cache = await caches.open(STATIC_CACHE);
                    const offline = await cache.match(OFFLINE_URL);

                    return offline ?? Response.error();
                }),
        );

        return;
    }

    if (
        url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/images/')
        || url.pathname.endsWith('.webmanifest')
        || url.pathname.endsWith('.css')
        || url.pathname.endsWith('.js')
        || url.pathname.endsWith('.woff2')
    ) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) {
                    return cached;
                }

                return fetch(request).then((response) => {
                    if (!response.ok) {
                        return response;
                    }

                    const copy = response.clone();
                    caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));

                    return response;
                });
            }),
        );
    }
});
