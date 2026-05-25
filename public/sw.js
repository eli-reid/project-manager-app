const CACHE_NAME = 'project-manager-app-v2';
const OFFLINE_URL = '/offline.html';

const APP_SHELL = [
    OFFLINE_URL,
    '/manifest.json',
    '/favicon.ico',
    '/favicon.svg',
    '/apple-touch-icon.png',
];

const STATIC_PATTERNS = [
    /\/build\/assets\/.*\.(css|js)$/,
    /\/livewire\/livewire\.js$/,
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => Promise.allSettled(APP_SHELL.map((url) => cache.add(url))))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);
    const shouldCacheStatic = STATIC_PATTERNS.some((pattern) => pattern.test(url.pathname));

    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .catch(() => caches.match(OFFLINE_URL))
        );

        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => {
            if (cached && shouldCacheStatic) {
                return cached;
            }

            return fetch(event.request)
                .then((response) => {
                    if (response.status === 200 && (shouldCacheStatic || url.pathname.startsWith('/build/'))) {
                        const responseClone = response.clone();

                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseClone));
                    }

                    return response;
                })
                .catch(() => cached);
        })
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    let payload = {};

    try {
        payload = event.data.json() ?? {};
    } catch {
        payload = {
            title: 'Project Manager',
            body: event.data.text() ?? '',
        };
    }

    const title = payload.title ?? 'Project Manager';
    const options = {
        body: payload.body ?? '',
        icon: payload.icon ?? '/icon-192.png',
        badge: payload.badge ?? '/icon-192.png',
        tag: payload.tag ?? 'project-manager-notification',
        data: {
            url: payload.url ?? '/',
            ...payload.data,
        },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url ?? '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                for (const client of windowClients) {
                    if ('focus' in client && client.url.includes(self.location.origin)) {
                        client.navigate(targetUrl);

                        return client.focus();
                    }
                }

                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }

                return undefined;
            })
    );
});