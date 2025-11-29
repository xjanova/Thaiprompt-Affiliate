/**
 * TP-POS Service Worker
 *
 * รองรับ Offline-First PWA สำหรับระบบ POS
 * - Cache static assets
 * - Cache API responses
 * - Background sync for transactions
 * - Push notifications
 *
 * @version 1.0.0
 */

const CACHE_VERSION = 'v1.0.0';
const STATIC_CACHE = `pos-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `pos-dynamic-${CACHE_VERSION}`;
const API_CACHE = `pos-api-${CACHE_VERSION}`;
const IMAGE_CACHE = `pos-images-${CACHE_VERSION}`;

// ไฟล์ที่ต้อง cache ทันทีเมื่อติดตั้ง
const STATIC_ASSETS = [
    '/pos/cashier',
    '/pos/session/open',
    '/pos-manifest.json',
    '/build/assets/app.css',
    '/build/assets/app.js',
    '/images/pos/icon-192x192.png',
    '/images/pos/icon-512x512.png',
    '/images/pos/offline.png',
    '/fonts/sarabun-regular.woff2',
    '/fonts/sarabun-bold.woff2'
];

// API endpoints ที่ต้อง cache
const API_ROUTES = [
    '/pos/products/search',
    '/pos/api/display/settings',
    '/pos/api/display/advertisements'
];

// ============================================
// INSTALL EVENT
// ============================================
self.addEventListener('install', (event) => {
    console.log('[POS-SW] Installing Service Worker...');

    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[POS-SW] Pre-caching static assets');
                // ใช้ addAll แต่ไม่ให้ fail ถ้าบางไฟล์ไม่มี
                return Promise.allSettled(
                    STATIC_ASSETS.map(url =>
                        cache.add(url).catch(err => {
                            console.warn(`[POS-SW] Failed to cache: ${url}`, err);
                        })
                    )
                );
            })
            .then(() => {
                console.log('[POS-SW] Static assets cached successfully');
                return self.skipWaiting();
            })
    );
});

// ============================================
// ACTIVATE EVENT
// ============================================
self.addEventListener('activate', (event) => {
    console.log('[POS-SW] Activating Service Worker...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => {
                            // ลบ cache เก่าที่ไม่ใช่ version ปัจจุบัน
                            return name.startsWith('pos-') &&
                                   name !== STATIC_CACHE &&
                                   name !== DYNAMIC_CACHE &&
                                   name !== API_CACHE &&
                                   name !== IMAGE_CACHE;
                        })
                        .map((name) => {
                            console.log(`[POS-SW] Deleting old cache: ${name}`);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                console.log('[POS-SW] Service Worker activated');
                return self.clients.claim();
            })
    );
});

// ============================================
// FETCH EVENT - Network Strategy
// ============================================
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // ข้าม requests ที่ไม่ใช่ HTTP/HTTPS
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // ข้าม requests ไปยัง external domains (ยกเว้น CDN ที่ต้องใช้)
    if (url.origin !== self.location.origin) {
        // อนุญาต CDN ที่ใช้
        const allowedCDNs = [
            'unpkg.com',
            'cdn.jsdelivr.net',
            'fonts.googleapis.com',
            'fonts.gstatic.com'
        ];

        if (!allowedCDNs.some(cdn => url.hostname.includes(cdn))) {
            return;
        }
    }

    // เลือก strategy ตามประเภท request
    if (request.method === 'POST') {
        // POST requests - ใช้ Background Sync
        event.respondWith(handlePostRequest(request));
    } else if (url.pathname.startsWith('/pos/api/') || url.pathname.startsWith('/api/')) {
        // API requests - Network First with Cache Fallback
        event.respondWith(networkFirstStrategy(request, API_CACHE));
    } else if (url.pathname.match(/\.(png|jpg|jpeg|gif|svg|webp|ico)$/i)) {
        // Images - Cache First
        event.respondWith(cacheFirstStrategy(request, IMAGE_CACHE));
    } else if (url.pathname.match(/\.(css|js|woff2?|ttf|eot)$/i)) {
        // Static assets - Cache First
        event.respondWith(cacheFirstStrategy(request, STATIC_CACHE));
    } else if (url.pathname.startsWith('/pos/')) {
        // POS pages - Network First (for fresh data)
        event.respondWith(networkFirstStrategy(request, DYNAMIC_CACHE));
    } else {
        // อื่นๆ - Network First
        event.respondWith(networkFirstStrategy(request, DYNAMIC_CACHE));
    }
});

// ============================================
// CACHING STRATEGIES
// ============================================

/**
 * Cache First Strategy
 * ดึงจาก cache ก่อน ถ้าไม่มีค่อยไป network
 */
async function cacheFirstStrategy(request, cacheName) {
    const cache = await caches.open(cacheName);
    const cachedResponse = await cache.match(request);

    if (cachedResponse) {
        // มีใน cache - ใช้เลย แล้วอัพเดท cache ใน background
        fetchAndCache(request, cacheName);
        return cachedResponse;
    }

    // ไม่มีใน cache - ดึงจาก network
    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('[POS-SW] Cache First failed:', error);
        return new Response('Offline', { status: 503 });
    }
}

/**
 * Network First Strategy
 * ดึงจาก network ก่อน ถ้า fail ค่อยใช้ cache
 */
async function networkFirstStrategy(request, cacheName) {
    const cache = await caches.open(cacheName);

    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.log('[POS-SW] Network failed, trying cache:', request.url);

        const cachedResponse = await cache.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        // ไม่มีใน cache - ส่ง offline page สำหรับ HTML requests
        if (request.headers.get('Accept')?.includes('text/html')) {
            return getOfflinePage();
        }

        // ส่ง offline response สำหรับ API
        if (request.url.includes('/api/')) {
            return new Response(
                JSON.stringify({
                    success: false,
                    offline: true,
                    message: 'คุณกำลังใช้งานแบบ Offline',
                    cached_at: new Date().toISOString()
                }),
                {
                    status: 200,
                    headers: { 'Content-Type': 'application/json' }
                }
            );
        }

        return new Response('Offline', { status: 503 });
    }
}

/**
 * Background fetch and cache update
 */
async function fetchAndCache(request, cacheName) {
    try {
        const response = await fetch(request);

        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response);
        }
    } catch (error) {
        // ไม่ต้องทำอะไร - แค่อัพเดท cache ใน background
    }
}

/**
 * Handle POST requests with Background Sync
 */
async function handlePostRequest(request) {
    try {
        const response = await fetch(request.clone());
        return response;
    } catch (error) {
        // Network failed - queue for background sync
        if (request.url.includes('/pos/checkout') ||
            request.url.includes('/pos/transactions')) {

            // Clone request data
            const data = await request.clone().json().catch(() => ({}));

            // Store in IndexedDB for sync
            await storeForSync('transactions', {
                url: request.url,
                method: request.method,
                body: data,
                timestamp: Date.now()
            });

            // Register background sync
            if ('sync' in self.registration) {
                await self.registration.sync.register('sync-transactions');
            }

            return new Response(
                JSON.stringify({
                    success: true,
                    offline: true,
                    queued: true,
                    message: 'รายการถูกบันทึกไว้ จะส่งเมื่อกลับ online'
                }),
                {
                    status: 202,
                    headers: { 'Content-Type': 'application/json' }
                }
            );
        }

        throw error;
    }
}

/**
 * Get offline fallback page
 */
async function getOfflinePage() {
    const cache = await caches.open(STATIC_CACHE);
    const offlineResponse = await cache.match('/pos/offline');

    if (offlineResponse) {
        return offlineResponse;
    }

    // ถ้าไม่มี offline page - สร้าง basic HTML
    return new Response(`
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Offline - TP-POS</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Sarabun', sans-serif;
                    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    text-align: center;
                    padding: 20px;
                }
                .container {
                    background: rgba(255,255,255,0.05);
                    backdrop-filter: blur(20px);
                    border-radius: 24px;
                    padding: 48px;
                    max-width: 400px;
                }
                .icon {
                    font-size: 64px;
                    margin-bottom: 24px;
                }
                h1 {
                    font-size: 24px;
                    margin-bottom: 16px;
                    color: #f59e0b;
                }
                p {
                    color: rgba(255,255,255,0.7);
                    margin-bottom: 24px;
                }
                button {
                    background: linear-gradient(135deg, #22c55e, #16a34a);
                    color: white;
                    border: none;
                    padding: 16px 32px;
                    font-size: 16px;
                    border-radius: 12px;
                    cursor: pointer;
                    transition: all 0.3s;
                }
                button:hover {
                    transform: scale(1.05);
                }
                .status {
                    margin-top: 24px;
                    padding: 12px;
                    background: rgba(59, 130, 246, 0.2);
                    border-radius: 8px;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">📡</div>
                <h1>ไม่มีการเชื่อมต่ออินเทอร์เน็ต</h1>
                <p>คุณสามารถดำเนินการขายต่อได้ ข้อมูลจะถูกซิงค์เมื่อกลับมา online</p>
                <button onclick="window.location.reload()">ลองใหม่อีกครั้ง</button>
                <div class="status">
                    💾 ข้อมูลที่บันทึกไว้จะส่งอัตโนมัติ
                </div>
            </div>
        </body>
        </html>
    `, {
        status: 200,
        headers: { 'Content-Type': 'text/html; charset=utf-8' }
    });
}

// ============================================
// BACKGROUND SYNC
// ============================================
self.addEventListener('sync', (event) => {
    console.log('[POS-SW] Background sync triggered:', event.tag);

    if (event.tag === 'sync-transactions') {
        event.waitUntil(syncTransactions());
    } else if (event.tag === 'sync-inventory') {
        event.waitUntil(syncInventory());
    }
});

/**
 * Sync queued transactions
 */
async function syncTransactions() {
    console.log('[POS-SW] Syncing transactions...');

    try {
        const pendingItems = await getFromSync('transactions');

        for (const item of pendingItems) {
            try {
                const response = await fetch(item.url, {
                    method: item.method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Offline-Sync': 'true'
                    },
                    body: JSON.stringify(item.body)
                });

                if (response.ok) {
                    await removeFromSync('transactions', item.id);
                    console.log('[POS-SW] Transaction synced:', item.id);

                    // แจ้ง client ว่า sync สำเร็จ
                    notifyClients({
                        type: 'SYNC_SUCCESS',
                        data: { id: item.id }
                    });
                }
            } catch (error) {
                console.error('[POS-SW] Failed to sync transaction:', item.id, error);
            }
        }
    } catch (error) {
        console.error('[POS-SW] Sync failed:', error);
    }
}

/**
 * Sync inventory updates
 */
async function syncInventory() {
    console.log('[POS-SW] Syncing inventory...');
    // คล้ายกับ syncTransactions
}

// ============================================
// INDEXEDDB HELPERS
// ============================================
const DB_NAME = 'pos-offline-db';
const DB_VERSION = 1;

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            if (!db.objectStoreNames.contains('sync-queue')) {
                const store = db.createObjectStore('sync-queue', { keyPath: 'id', autoIncrement: true });
                store.createIndex('type', 'type', { unique: false });
                store.createIndex('timestamp', 'timestamp', { unique: false });
            }
        };
    });
}

async function storeForSync(type, data) {
    const db = await openDB();
    const tx = db.transaction('sync-queue', 'readwrite');
    const store = tx.objectStore('sync-queue');

    store.add({
        type,
        data,
        timestamp: Date.now()
    });

    return new Promise((resolve, reject) => {
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

async function getFromSync(type) {
    const db = await openDB();
    const tx = db.transaction('sync-queue', 'readonly');
    const store = tx.objectStore('sync-queue');
    const index = store.index('type');

    return new Promise((resolve, reject) => {
        const request = index.getAll(type);
        request.onsuccess = () => resolve(request.result || []);
        request.onerror = () => reject(request.error);
    });
}

async function removeFromSync(type, id) {
    const db = await openDB();
    const tx = db.transaction('sync-queue', 'readwrite');
    const store = tx.objectStore('sync-queue');

    store.delete(id);

    return new Promise((resolve, reject) => {
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

// ============================================
// PUSH NOTIFICATIONS
// ============================================
self.addEventListener('push', (event) => {
    console.log('[POS-SW] Push notification received');

    const data = event.data?.json() || {
        title: 'TP-POS',
        body: 'มีการแจ้งเตือนใหม่',
        icon: '/images/pos/icon-192x192.png'
    };

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: data.icon || '/images/pos/icon-192x192.png',
            badge: '/images/pos/badge-72x72.png',
            vibrate: [100, 50, 100],
            tag: data.tag || 'pos-notification',
            renotify: true,
            actions: data.actions || [],
            data: data.data || {}
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    console.log('[POS-SW] Notification clicked');

    event.notification.close();

    const url = event.notification.data?.url || '/pos/cashier';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // ถ้ามี window เปิดอยู่ - focus
                for (const client of clientList) {
                    if (client.url.includes('/pos/') && 'focus' in client) {
                        return client.focus();
                    }
                }
                // ถ้าไม่มี - เปิดใหม่
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// ============================================
// CLIENT COMMUNICATION
// ============================================
self.addEventListener('message', (event) => {
    console.log('[POS-SW] Message received:', event.data);

    const { type, payload } = event.data;

    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;

        case 'CACHE_PRODUCTS':
            cacheProducts(payload.products);
            break;

        case 'CLEAR_CACHE':
            clearAllCaches();
            break;

        case 'GET_CACHE_STATUS':
            getCacheStatus().then(status => {
                event.source.postMessage({
                    type: 'CACHE_STATUS',
                    data: status
                });
            });
            break;
    }
});

async function notifyClients(message) {
    const clientList = await clients.matchAll({ type: 'window' });

    for (const client of clientList) {
        client.postMessage(message);
    }
}

async function cacheProducts(products) {
    const cache = await caches.open(API_CACHE);

    // Cache each product
    for (const product of products) {
        const response = new Response(JSON.stringify(product), {
            headers: { 'Content-Type': 'application/json' }
        });
        await cache.put(`/pos/products/${product.id}`, response);
    }

    console.log(`[POS-SW] Cached ${products.length} products`);
}

async function clearAllCaches() {
    const cacheNames = await caches.keys();

    await Promise.all(
        cacheNames
            .filter(name => name.startsWith('pos-'))
            .map(name => caches.delete(name))
    );

    console.log('[POS-SW] All caches cleared');
}

async function getCacheStatus() {
    const cacheNames = await caches.keys();
    const status = {};

    for (const name of cacheNames.filter(n => n.startsWith('pos-'))) {
        const cache = await caches.open(name);
        const keys = await cache.keys();
        status[name] = {
            count: keys.length,
            urls: keys.map(k => k.url)
        };
    }

    return status;
}

// ============================================
// PERIODIC SYNC (for auto-updates)
// ============================================
self.addEventListener('periodicsync', (event) => {
    console.log('[POS-SW] Periodic sync:', event.tag);

    if (event.tag === 'update-products') {
        event.waitUntil(updateProductCache());
    } else if (event.tag === 'sync-pending') {
        event.waitUntil(syncTransactions());
    }
});

async function updateProductCache() {
    try {
        const response = await fetch('/pos/api/products/all');
        const products = await response.json();

        await cacheProducts(products.data || []);

        notifyClients({
            type: 'PRODUCTS_UPDATED',
            data: { count: products.data?.length || 0 }
        });
    } catch (error) {
        console.error('[POS-SW] Failed to update product cache:', error);
    }
}

console.log('[POS-SW] Service Worker loaded');
