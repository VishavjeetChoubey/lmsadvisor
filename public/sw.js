/* LMSAdvisor Service Worker v2 */
const CACHE_NAME = 'lmsadvisor-v2';

// Only cache student-facing static assets — never admin routes
const STATIC_ASSETS = [
  '/lmsadvisor-dev/public/assets/css/app.css',
  '/lmsadvisor-dev/public/assets/js/app.js',
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(STATIC_ASSETS).catch(() => {}))
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

self.addEventListener('fetch', event => {
  const url = event.request.url;

  // Never intercept admin routes, API calls, or non-GET requests
  if (
    event.request.method !== 'GET' ||
    url.includes('/admin/') ||
    url.includes('/api/') ||
    url.includes('/login') ||
    url.includes('/logout')
  ) {
    return; // Let browser handle normally
  }

  // For static assets: cache-first
  if (url.includes('/assets/')) {
    event.respondWith(
      caches.match(event.request).then(cached => cached || fetch(event.request))
    );
    return;
  }

  // For everything else (student pages): network-first, no caching
  // This prevents the SW from returning stale HTML pages
  event.respondWith(fetch(event.request));
});
