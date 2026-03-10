/**
 * Service Worker สำหรับ TP-Affiliate PWA
 *
 * คุณสมบัติ:
 * - Cache static assets (CSS, JS, fonts, images)
 * - Cache navigation pages สำหรับ offline
 * - Network-first สำหรับ API requests
 * - Offline fallback page
 * - Push notifications support
 * - Background sync
 *
 * @version 2.0.0 - อัพเกรดเป็น full PWA
 */

const CACHE_VERSION = 'v2';
const STATIC_CACHE = `tp-static-${CACHE_VERSION}`;
const PAGES_CACHE = `tp-pages-${CACHE_VERSION}`;
const API_CACHE = `tp-api-${CACHE_VERSION}`;

/** Static assets ที่ต้อง pre-cache ตอน install */
const PRECACHE_ASSETS = [
    '/offline',
    '/images/logo.svg',
    '/favicon.ico',
];

/** URL patterns สำหรับ API caching */
const API_PATTERNS = [
    '/user/notifications/unread',
    '/seller/notifications/unread',
    '/admin/notifications/unread',
];

/** Cache expiry สำหรับ API responses */
const API_CACHE_EXPIRY = 5 * 60 * 1000; // 5 นาที

/** Cache expiry สำหรับ pages */
const PAGE_CACHE_EXPIRY = 30 * 60 * 1000; // 30 นาที

/** Max items ใน dynamic caches */
const MAX_CACHE_ITEMS = 50;

/**
 * Install event - pre-cache static assets
 */
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('SW: บาง assets pre-cache ไม่ได้:', err);
            });
        })
    );
    self.skipWaiting();
});

/**
 * Activate event - ลบ cache เก่า
 */
self.addEventListener('activate', (event) => {
    const validCaches = [STATIC_CACHE, PAGES_CACHE, API_CACHE];

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => !validCaches.includes(name))
                    .map((name) => caches.delete(name))
            );
        })
    );

    return self.clients.claim();
});

/**
 * Fetch event - จัดการ caching strategies
 */
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // ข้าม non-GET requests
    if (request.method !== 'GET') return;

    // ข้าม chrome-extension, ws, etc.
    if (!url.protocol.startsWith('http')) return;

    // ข้าม third-party requests (fonts.googleapis, cdnjs, etc.) - ใช้ browser cache ปกติ
    if (url.origin !== self.location.origin) {
        // Cache Google Fonts stylesheets/files เท่านั้น
        if (url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com') {
            event.respondWith(staleWhileRevalidate(request, STATIC_CACHE));
        }
        return;
    }

    // API requests - Network first + cache fallback
    if (isApiRequest(url)) {
        event.respondWith(networkFirst(request, API_CACHE, API_CACHE_EXPIRY));
        return;
    }

    // Static assets (css, js, images, fonts) - Cache first
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
        return;
    }

    // Navigation requests (HTML pages) - Network first + offline fallback
    if (request.mode === 'navigate') {
        event.respondWith(networkFirstWithOffline(request));
        return;
    }
});

/**
 * ตรวจสอบว่าเป็น API request หรือไม่
 */
function isApiRequest(url) {
    return API_PATTERNS.some((pattern) => url.pathname.includes(pattern))
        || url.pathname.startsWith('/api/');
}

/**
 * ตรวจสอบว่าเป็น static asset หรือไม่
 */
function isStaticAsset(url) {
    return url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/images/')
        || url.pathname.startsWith('/icons/')
        || url.pathname.match(/\.(css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|ico|webp)$/);
}

/**
 * Cache-First Strategy - สำหรับ static assets
 * ดึงจาก cache ก่อน ถ้าไม่มีค่อย fetch จาก network
 */
async function cacheFirst(request, cacheName) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return new Response('', { status: 408 });
    }
}

/**
 * Network-First Strategy - สำหรับ API requests
 */
async function networkFirst(request, cacheName, expiry) {
    try {
        const response = await fetch(request);

        if (response.ok) {
            const cache = await caches.open(cacheName);
            const headers = new Headers(response.headers);
            headers.set('sw-cached-at', Date.now().toString());
            const cachedResponse = new Response(response.clone().body, {
                status: response.status,
                statusText: response.statusText,
                headers,
            });
            cache.put(request, cachedResponse);
            trimCache(cacheName, MAX_CACHE_ITEMS);
        }

        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) {
            const cachedAt = parseInt(cached.headers.get('sw-cached-at') || '0');
            if (Date.now() - cachedAt < expiry) {
                return cached;
            }
        }

        return new Response(
            JSON.stringify({
                success: false,
                message: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ (offline)',
                data: null,
            }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}

/**
 * Network-First + Offline Fallback - สำหรับ navigation
 */
async function networkFirstWithOffline(request) {
    try {
        const response = await fetch(request);

        if (response.ok) {
            // Cache หน้าที่โหลดสำเร็จ
            const cache = await caches.open(PAGES_CACHE);
            cache.put(request, response.clone());
            trimCache(PAGES_CACHE, MAX_CACHE_ITEMS);
        }

        return response;
    } catch (error) {
        // ลองหาจาก cache
        const cached = await caches.match(request);
        if (cached) return cached;

        // แสดงหน้า offline
        const offlinePage = await caches.match('/offline');
        if (offlinePage) return offlinePage;

        return new Response(
            '<html><body style="display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#111827;color:white;font-family:sans-serif;text-align:center"><div><h1 style="font-size:48px;margin-bottom:16px">📴</h1><h2>ไม่มีอินเทอร์เน็ต</h2><p style="color:#9ca3af">กรุณาเชื่อมต่ออินเทอร์เน็ตแล้วลองใหม่</p><button onclick="location.reload()" style="margin-top:24px;padding:12px 32px;background:#8B5CF6;color:white;border:none;border-radius:12px;font-size:16px;cursor:pointer">ลองใหม่</button></div></body></html>',
            { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
        );
    }
}

/**
 * Stale-While-Revalidate Strategy - สำหรับ fonts
 */
async function staleWhileRevalidate(request, cacheName) {
    const cached = await caches.match(request);

    const fetchPromise = fetch(request).then((response) => {
        if (response.ok) {
            const cache = caches.open(cacheName);
            cache.then((c) => c.put(request, response.clone()));
        }
        return response;
    }).catch(() => cached);

    return cached || fetchPromise;
}

/**
 * จำกัดจำนวน items ใน cache
 */
async function trimCache(cacheName, maxItems) {
    const cache = await caches.open(cacheName);
    const keys = await cache.keys();
    if (keys.length > maxItems) {
        await cache.delete(keys[0]);
        trimCache(cacheName, maxItems);
    }
}

/**
 * Background Sync
 */
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-notifications') {
        event.waitUntil(syncData('notifications'));
    }
});

async function syncData(type) {
    if (type === 'notifications') {
        const promises = API_PATTERNS.filter((u) => u.includes('notifications')).map((url) =>
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
            })
                .then((r) => {
                    if (r.ok) return caches.open(API_CACHE).then((c) => c.put(url, r.clone()));
                })
                .catch(() => {})
        );
        await Promise.all(promises);
    }
}

/**
 * Push Notification Handler
 */
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};

    const options = {
        body: data.message || 'คุณมีการแจ้งเตือนใหม่',
        icon: '/images/pwa/icon-192x192.png',
        badge: '/images/pwa/icon-72x72.png',
        vibrate: [200, 100, 200],
        tag: data.id || 'notification',
        requireInteraction: data.is_important || false,
        data: { url: data.action_url || '/user/dashboard' },
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'TP-Affiliate', options)
    );
});

/**
 * Notification Click Handler
 */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const urlToOpen = event.notification.data?.url || '/user/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if (client.url.includes(urlToOpen) && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

/**
 * Message Handler
 */
self.addEventListener('message', (event) => {
    if (event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }
    if (event.data.action === 'clearCache') {
        event.waitUntil(
            Promise.all([
                caches.delete(STATIC_CACHE),
                caches.delete(PAGES_CACHE),
                caches.delete(API_CACHE),
            ])
        );
    }
});
