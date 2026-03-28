// Al-Mutlak WMS — Service Worker
// Enables PWA "Add to Home Screen" on iOS Safari and Android Chrome.
// Strategy: Network-first for all pages (always fresh content from server).

const CACHE_NAME = 'almutlak-wms-v1';

// Assets to pre-cache for offline splash screen only
const PRECACHE_ASSETS = [
  '/system/pwa-icon-192.png',
  '/system/pwa-icon-512.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(PRECACHE_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    )
  );
  self.clients.claim();
});

// Network-first: always load fresh from server, fall back to cache only if offline
self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Cache successful responses for icons/images only
        if (response.ok && event.request.url.match(/\.(png|jpg|jpeg|gif|ico|svg)$/)) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
        }
        return response;
      })
      .catch(() => caches.match(event.request))
  );
});
