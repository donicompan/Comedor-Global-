/**
 * sw.js — Service Worker de Cardón POS
 *
 * Estrategia:
 *   - Páginas PHP → Network-first (datos siempre frescos)
 *   - Assets estáticos (CSS, JS, imágenes) → Cache-first
 *   - Si no hay red → devuelve lo que haya en caché
 */

const CACHE_NAME = 'cardon-pos-v1.0.0';

const STATIC_ASSETS = [
    'css/estiloPrincipal.css',
    'css/estilos.css',
    'css/estilos2.css',
    'css/estilosIndex.css',
    'css/estiloPrincipal.css',
    'img/LogoCardon.jpeg',
    'img/fondoCardon.jpeg',
    'img/fondoCardon2.jpeg',
    'img/paisaje.jpg',
];

// ── Instalación: pre-cachear assets estáticos ─────────────────────────
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// ── Activación: limpiar cachés viejos ────────────────────────────────
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ── Fetch: estrategia según tipo de recurso ───────────────────────────
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Solo interceptar peticiones del mismo origen
    if (url.origin !== location.origin) return;

    // Páginas PHP → Network-first
    if (url.pathname.endsWith('.php') || url.pathname.endsWith('/')) {
        event.respondWith(
            fetch(event.request)
                .catch(() => caches.match(event.request))
        );
        return;
    }

    // Assets estáticos → Cache-first
    event.respondWith(
        caches.match(event.request).then(cached => {
            if (cached) return cached;
            return fetch(event.request).then(response => {
                if (!response || response.status !== 200) return response;
                const clone = response.clone();
                caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
                return response;
            });
        })
    );
});
