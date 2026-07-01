const CACHE_VERSION = 'awaan-v2';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/doctor/manifest.webmanifest',
    '/images/pwa/icon-192.png',
    '/images/pwa/icon-512.png',
    '/images/favicon-awaan.png',
];

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
