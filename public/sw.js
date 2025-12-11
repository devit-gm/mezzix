const CACHE_NAME = 'mezzix-v3';
const CACHE_STATIC = 'mezzix-static-v3';
const CACHE_DYNAMIC = 'mezzix-dynamic-v3';
const CACHE_API = 'mezzix-api-v3';

const STATIC_ASSETS = [
    '/css/app.css',
    '/js/app.js',
    '/images/logo.png',
    '/manifest.json'
];

// Configuración de estrategias de caché
const CACHE_STRATEGIES = {
    static: ['css', 'js', 'fonts', 'woff2', 'woff', 'ttf', 'eot'],
    images: ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'],
    api: ['/api/', '/ajax/'],
    networkFirst: ['/fichas', '/mesas', '/productos', '/familias', '/proveedores', '/albaranes'],
    cacheFirst: ['/images/', '/fonts/', '/css/', '/js/']
};

// IndexedDB para cola de sincronización
const DB_NAME = 'mezzix-sync';
const DB_VERSION = 1;
const STORE_NAME = 'pending-requests';

// Abrir/crear base de datos IndexedDB
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                const objectStore = db.createObjectStore(STORE_NAME, {
                    keyPath: 'id',
                    autoIncrement: true
                });
                objectStore.createIndex('timestamp', 'timestamp', { unique: false });
                objectStore.createIndex('url', 'url', { unique: false });
            }
        };
    });
}

// Guardar petición pendiente en IndexedDB
async function savePendingRequest(request, data) {
    try {
        const db = await openDB();
        const transaction = db.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);

        const requestData = {
            url: request.url,
            method: request.method,
            headers: Array.from(request.headers.entries()),
            body: data,
            timestamp: Date.now()
        };

        store.add(requestData);

        return new Promise((resolve, reject) => {
            transaction.oncomplete = () => resolve();
            transaction.onerror = () => reject(transaction.error);
        });
    } catch (error) {
        console.error('[SW] Error guardando petición pendiente:', error);
    }
}

// Obtener todas las peticiones pendientes
async function getPendingRequests() {
    try {
        const db = await openDB();
        const transaction = db.transaction([STORE_NAME], 'readonly');
        const store = transaction.objectStore(STORE_NAME);

        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('[SW] Error obteniendo peticiones pendientes:', error);
        return [];
    }
}

// Eliminar petición pendiente
async function deletePendingRequest(id) {
    try {
        const db = await openDB();
        const transaction = db.transaction([STORE_NAME], 'readwrite');
        const store = transaction.objectStore(STORE_NAME);
        store.delete(id);

        return new Promise((resolve, reject) => {
            transaction.oncomplete = () => resolve();
            transaction.onerror = () => reject(transaction.error);
        });
    } catch (error) {
        console.error('[SW] Error eliminando petición pendiente:', error);
    }
}

// Sincronizar peticiones pendientes
async function syncPendingRequests() {
    const pendingRequests = await getPendingRequests();

    for (const req of pendingRequests) {
        try {
            const headers = new Headers();
            req.headers.forEach(([key, value]) => {
                headers.append(key, value);
            });

            const response = await fetch(req.url, {
                method: req.method,
                headers: headers,
                body: req.body
            });

            if (response.ok) {
                await deletePendingRequest(req.id);
                console.log('[SW] Petición sincronizada:', req.url);
            }
        } catch (error) {
            console.error('[SW] Error sincronizando petición:', error);
        }
    }

    // Notificar al cliente sobre el estado de sincronización
    const clients = await self.clients.matchAll();
    const remaining = await getPendingRequests();
    clients.forEach(client => {
        client.postMessage({
            type: 'SYNC_COMPLETE',
            pendingCount: remaining.length
        });
    });
}

// Determinar estrategia de caché según URL
function getCacheStrategy(url) {
    const pathname = new URL(url).pathname;
    const extension = pathname.split('.').pop().toLowerCase();

    // Estáticos (CSS, JS, fonts)
    if (CACHE_STRATEGIES.static.includes(extension)) {
        return 'cache-first';
    }

    // Imágenes
    if (CACHE_STRATEGIES.images.includes(extension)) {
        return 'cache-first';
    }

    // APIs
    if (CACHE_STRATEGIES.api.some(api => pathname.includes(api))) {
        return 'network-first';
    }

    // Rutas específicas
    if (CACHE_STRATEGIES.networkFirst.some(route => pathname.includes(route))) {
        return 'network-first';
    }

    if (CACHE_STRATEGIES.cacheFirst.some(route => pathname.startsWith(route))) {
        return 'cache-first';
    }

    return 'network-first';
}

// Estrategia Cache First
async function cacheFirst(request) {
    const cache = await caches.open(CACHE_STATIC);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return new Response('Offline', { status: 503 });
    }
}

// Estrategia Network First
async function networkFirst(request) {
    const cache = await caches.open(CACHE_DYNAMIC);

    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }

        // Si es una navegación y falla, mostrar página básica
        if (request.mode === 'navigate') {
            return new Response(`
                <!DOCTYPE html>
                <html lang="es">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Sin Conexión - Mezzix</title>
                    <style>
                        body { 
                            font-family: Arial, sans-serif; 
                            display: flex; 
                            justify-content: center; 
                            align-items: center; 
                            height: 100vh; 
                            margin: 0; 
                            background: #f5f5f5; 
                            color: #333;
                        }
                        .offline-container { 
                            text-align: center; 
                            padding: 2rem; 
                            background: white; 
                            border-radius: 8px; 
                            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        }
                        h1 { color: #dc3545; }
                        .icon { font-size: 4rem; margin-bottom: 1rem; }
                        button { 
                            padding: 10px 20px; 
                            font-size: 16px; 
                            cursor: pointer; 
                            background: #007bff; 
                            color: white; 
                            border: none; 
                            border-radius: 4px; 
                            margin-top: 1rem;
                        }
                    </style>
                </head>
                <body>
                    <div class="offline-container">
                        <div class="icon">📡❌</div>
                        <h1>Sin Conexión</h1>
                        <p>No hay conexión a Internet.</p>
                        <p>Por favor, verifica tu conexión y vuelve a intentar.</p>
                        <button onclick="location.reload()">Reintentar</button>
                    </div>
                </body>
                </html>
            `, {
                status: 503,
                headers: { 'Content-Type': 'text/html; charset=utf-8' }
            });
        }

        return new Response('Offline', { status: 503 });
    }
}

// Instalación del Service Worker
self.addEventListener('install', event => {
    console.log('[SW] Instalando Service Worker v3');
    event.waitUntil(
        caches.open(CACHE_STATIC)
            .then(cache => {
                console.log('[SW] Cacheando recursos estáticos');
                return cache.addAll(STATIC_ASSETS)
                    .catch(err => console.warn('[SW] Error cacheando algunos recursos:', err));
            })
            .then(() => self.skipWaiting())
    );
});

// Activación del Service Worker
self.addEventListener('activate', event => {
    console.log('[SW] Activando Service Worker v3');
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (![CACHE_NAME, CACHE_STATIC, CACHE_DYNAMIC, CACHE_API].includes(cacheName)) {
                        console.log('[SW] Eliminando cache antiguo:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
            .then(() => self.clients.claim())
    );
});

// Fetch
self.addEventListener('fetch', event => {
    const { request } = event;

    // Solo GET requests se cachean
    if (request.method !== 'GET') {
        // POST, PUT, DELETE - intentar enviar, si falla guardar en cola
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(request.method)) {
            event.respondWith(
                fetch(request.clone())
                    .catch(async () => {
                        // Guardar en cola para sincronización posterior
                        const data = await request.clone().text();
                        await savePendingRequest(request, data);

                        return new Response(JSON.stringify({
                            success: false,
                            message: 'Operación guardada. Se sincronizará cuando haya conexión.',
                            queued: true
                        }), {
                            status: 202,
                            headers: { 'Content-Type': 'application/json' }
                        });
                    })
            );
        }
        return;
    }

    // Ignorar peticiones externas
    if (!request.url.startsWith(self.location.origin)) {
        return;
    }

    const pathname = new URL(request.url).pathname;

    // NUNCA cachear rutas dinámicas críticas
    const noCacheRoutes = ['/login', '/logout', '/csrf-token', '/', '/home'];

    if (noCacheRoutes.includes(pathname)) {
        event.respondWith(fetch(request));
        return;
    }

    // Aplicar estrategia según tipo de recurso
    const strategy = getCacheStrategy(request.url);

    if (strategy === 'cache-first') {
        event.respondWith(cacheFirst(request));
    } else {
        event.respondWith(networkFirst(request));
    }
});

// Background Sync
self.addEventListener('sync', event => {
    console.log('[SW] Sincronización en segundo plano');
    if (event.tag === 'sync-requests') {
        event.waitUntil(syncPendingRequests());
    }
});

// Manejo de mensajes desde el cliente
self.addEventListener('message', event => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data && event.data.type === 'SYNC_NOW') {
        syncPendingRequests();
    }

    if (event.data && event.data.type === 'GET_PENDING_COUNT') {
        getPendingRequests().then(requests => {
            event.ports[0].postMessage({
                type: 'PENDING_COUNT',
                count: requests.length
            });
        });
    }
});

// Notificaciones push
self.addEventListener('push', event => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Mezzix';
    const options = {
        body: data.body || 'Nueva notificación',
        icon: '/images/logo.png',
        badge: '/images/badge.png',
        data: data
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Click en notificación
self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url || '/')
    );
});

console.log('[SW] Service Worker v3 cargado');
