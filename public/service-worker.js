/**
 * Service Worker สำหรับ Offline Notifications
 *
 * คุณสมบัติ:
 * - Cache API responses สำหรับ notifications
 * - Serve cached data เมื่อ offline
 * - Background sync เมื่อ connection กลับมา
 * - Push notifications support
 *
 * @version 1.0.0
 */

const CACHE_NAME = 'tp-affiliate-notifications-v1';
const CACHE_EXPIRY = 5 * 60 * 1000; // 5 นาที

// URL patterns ที่ต้อง cache
const NOTIFICATION_URLS = [
    '/user/notifications/unread',
    '/seller/notifications/unread',
    '/admin/notifications/unread',
];

/**
 * Install event - เตรียม cache
 */
self.addEventListener('install', (event) => {
    console.log('📦 Service Worker: Installing...');

    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('✅ Service Worker: Cache opened');
            return cache;
        })
    );

    // Force activate ทันที
    self.skipWaiting();
});

/**
 * Activate event - ลบ cache เก่า
 */
self.addEventListener('activate', (event) => {
    console.log('🔄 Service Worker: Activating...');

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('🗑️ Service Worker: Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );

    // Take control ทันที
    return self.clients.claim();
});

/**
 * Fetch event - Intercept requests และใช้ cache strategy
 */
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // ตรวจสอบว่าเป็น notification request หรือไม่
    const isNotificationRequest = NOTIFICATION_URLS.some(pattern =>
        url.pathname.includes(pattern)
    );

    if (isNotificationRequest && event.request.method === 'GET') {
        event.respondWith(
            networkFirstStrategy(event.request)
        );
    }
});

/**
 * Network-First Strategy
 * พยายาม fetch จาก network ก่อน หากล้มเหลวใช้ cache
 *
 * @param {Request} request
 * @returns {Promise<Response>}
 */
async function networkFirstStrategy(request) {
    try {
        // พยายาม fetch จาก network
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            // บันทึกลง cache พร้อม timestamp
            const cache = await caches.open(CACHE_NAME);
            const clonedResponse = networkResponse.clone();

            // เพิ่ม timestamp header
            const headers = new Headers(clonedResponse.headers);
            headers.append('sw-cached-at', Date.now().toString());

            const responseWithTimestamp = new Response(clonedResponse.body, {
                status: clonedResponse.status,
                statusText: clonedResponse.statusText,
                headers: headers
            });

            cache.put(request, responseWithTimestamp);
            console.log('💾 Service Worker: Cached response for', request.url);
        }

        return networkResponse;

    } catch (error) {
        console.log('📴 Service Worker: Network failed, trying cache...', error);

        // หาก network ล้มเหลว ลอง cache
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            // ตรวจสอบว่า cache หมดอายุหรือยัง
            const cachedAt = cachedResponse.headers.get('sw-cached-at');

            if (cachedAt) {
                const age = Date.now() - parseInt(cachedAt);

                if (age < CACHE_EXPIRY) {
                    console.log('✅ Service Worker: Serving from cache (age: ' + Math.round(age / 1000) + 's)');
                    return cachedResponse;
                } else {
                    console.log('⏰ Service Worker: Cache expired, removing...');
                    await caches.delete(request);
                }
            } else {
                // ถ้าไม่มี timestamp ให้ใช้ cache ได้
                return cachedResponse;
            }
        }

        // ถ้าไม่มี cache หรือ cache หมดอายุ
        console.error('❌ Service Worker: No cache available');
        return new Response(
            JSON.stringify({
                success: false,
                message: 'ไม่สามารถโหลดการแจ้งเตือนได้ (offline)',
                notifications: [],
                unread_count: 0
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' }
            }
        );
    }
}

/**
 * Background Sync - Sync เมื่อ connection กลับมา
 */
self.addEventListener('sync', (event) => {
    console.log('🔄 Service Worker: Background sync triggered');

    if (event.tag === 'sync-notifications') {
        event.waitUntil(syncNotifications());
    }
});

/**
 * Sync notifications เมื่อ online
 */
async function syncNotifications() {
    try {
        console.log('🔄 Syncing notifications...');

        // Fetch ข้อมูลใหม่สำหรับทุก route prefix
        const syncPromises = NOTIFICATION_URLS.map(url => {
            return fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            }).then(response => {
                if (response.ok) {
                    return caches.open(CACHE_NAME).then(cache => {
                        cache.put(url, response.clone());
                    });
                }
            }).catch(error => {
                console.error('Failed to sync:', url, error);
            });
        });

        await Promise.all(syncPromises);
        console.log('✅ Notifications synced successfully');

    } catch (error) {
        console.error('❌ Sync failed:', error);
        throw error;
    }
}

/**
 * Push Notification Handler (สำหรับอนาคต)
 */
self.addEventListener('push', (event) => {
    console.log('📬 Service Worker: Push notification received');

    const data = event.data ? event.data.json() : {};

    const options = {
        body: data.message || 'คุณมีการแจ้งเตือนใหม่',
        icon: '/icons/notification-icon.png',
        badge: '/icons/badge-icon.png',
        vibrate: [200, 100, 200],
        tag: data.id || 'notification',
        requireInteraction: data.is_important || false,
        data: {
            url: data.action_url || '/',
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'การแจ้งเตือน', options)
    );
});

/**
 * Notification Click Handler
 */
self.addEventListener('notificationclick', (event) => {
    console.log('🖱️ Service Worker: Notification clicked');

    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // ถ้ามี tab เปิดอยู่แล้ว ให้ focus
            for (const client of clientList) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }

            // ถ้าไม่มี ให้เปิด tab ใหม่
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});

/**
 * Message Handler - รับคำสั่งจาก client
 */
self.addEventListener('message', (event) => {
    console.log('💬 Service Worker: Message received', event.data);

    if (event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }

    if (event.data.action === 'clearCache') {
        event.waitUntil(
            caches.delete(CACHE_NAME).then(() => {
                console.log('🗑️ Cache cleared');
            })
        );
    }
});

console.log('✅ Service Worker: Loaded successfully');
