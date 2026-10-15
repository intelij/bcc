// Minimal PWA service worker. Deliberately caches ONLY the offline shell —
// billing/payment data is never cached on-device (financial privacy).
const SHELL = '/';

self.addEventListener('install', (e) => self.skipWaiting());
self.addEventListener('activate', (e) => self.clients.claim());
self.addEventListener('fetch', (e) => {
    // Network-first; never cache authenticated pages or API responses.
    if (e.request.method !== 'GET') return;
    e.respondWith(fetch(e.request).catch(() => caches.match(SHELL)));
});
