// Service worker "Planera urlopów" - celowo bardzo prosty.
// Cache'uje wyłącznie statyczne, niezmienne zasoby (CSS, biblioteka
// FullCalendar, ikony). Wszystkie strony PHP (dynamiczne, zależne od
// sesji/zalogowanego użytkownika) idą zawsze prosto do sieci - bez cache'u,
// żeby nigdy nie pokazać nieaktualnych/cudzych danych offline.
const CACHE_NAME = 'urlopy-static-v1';
const STATIC_ASSETS = [
  'assets/css/style.css',
  'assets/vendor/fullcalendar.min.js',
  'assets/img/icon-192.png',
  'assets/img/icon-512.png',
  'manifest.json',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)).catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((names) => Promise.all(
      names.filter((name) => name !== CACHE_NAME).map((name) => caches.delete(name))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  const isStatic = STATIC_ASSETS.some((path) => url.pathname.endsWith('/' + path));
  if (!isStatic || event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request).then((cached) => {
      const network = fetch(event.request).then((response) => {
        if (response.ok) {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
        }
        return response;
      }).catch(() => cached);
      return cached || network;
    })
  );
});
