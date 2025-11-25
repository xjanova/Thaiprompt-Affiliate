/**
 * Service Worker สำหรับ Service Booking Background Location Tracking
 *
 * ทำงาน:
 * 1. ติดตามตำแหน่ง GPS แม้ปิดแอพ/หน้าจอ
 * 2. ส่งตำแหน่งไปเซิร์ฟเวอร์ทุก 30 วินาที
 * 3. แสดง notification เมื่อมีเหตุการณ์สำคัญ
 * 4. Sync ข้อมูลเมื่อกลับมาออนไลน์
 */

const CACHE_NAME = 'service-booking-v1';
const LOCATION_SYNC_TAG = 'location-sync';
const BOOKING_CHECK_TAG = 'booking-check';

// URLs ที่ต้อง cache
const CACHE_URLS = [
    '/offline.html',
    '/images/marker-provider.svg',
    '/images/marker-user.svg',
    '/images/marker-destination.svg',
];

// ตัวแปรสำหรับเก็บข้อมูล
let activeBookingId = null;
let userType = null; // 'user' หรือ 'provider'
let locationWatchId = null;
let pendingLocations = [];

/**
 * Install event - cache static assets
 */
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installing...');

    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[ServiceWorker] Caching static assets');
            return cache.addAll(CACHE_URLS);
        })
    );

    // เปิดใช้งานทันที
    self.skipWaiting();
});

/**
 * Activate event - clean old caches
 */
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating...');

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        })
    );

    // Take control immediately
    self.clients.claim();
});

/**
 * Message event - รับข้อความจาก main thread
 */
self.addEventListener('message', (event) => {
    const { type, data } = event.data;

    switch (type) {
        case 'START_TRACKING':
            startLocationTracking(data.bookingId, data.userType, data.csrfToken);
            break;

        case 'STOP_TRACKING':
            stopLocationTracking();
            break;

        case 'UPDATE_BOOKING_STATUS':
            handleBookingStatusUpdate(data.status);
            break;

        case 'SYNC_LOCATIONS':
            syncPendingLocations(data.csrfToken);
            break;
    }
});

/**
 * Periodic sync - ทำงานเป็นระยะ (ถ้า browser รองรับ)
 */
self.addEventListener('periodicsync', (event) => {
    if (event.tag === LOCATION_SYNC_TAG) {
        event.waitUntil(syncPendingLocations());
    }

    if (event.tag === BOOKING_CHECK_TAG) {
        event.waitUntil(checkBookingStatus());
    }
});

/**
 * Background sync - sync เมื่อกลับมาออนไลน์
 */
self.addEventListener('sync', (event) => {
    if (event.tag === LOCATION_SYNC_TAG) {
        event.waitUntil(syncPendingLocations());
    }
});

/**
 * Push notification
 */
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};

    const options = {
        body: data.body || 'มีการอัพเดทจากระบบ',
        icon: '/images/icons/icon-192.png',
        badge: '/images/icons/badge-72.png',
        vibrate: [200, 100, 200],
        tag: data.tag || 'booking-notification',
        data: data,
        actions: data.actions || [],
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Service Booking', options)
    );
});

/**
 * Notification click
 */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const data = event.notification.data;
    let url = '/user/bookings';

    if (data && data.bookingId) {
        url = data.userType === 'provider'
            ? `/provider/bookings/${data.bookingId}/track`
            : `/user/bookings/${data.bookingId}/track`;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            // ถ้ามี window เปิดอยู่ ให้ focus
            for (const client of clientList) {
                if (client.url.includes('/bookings') && 'focus' in client) {
                    return client.focus();
                }
            }
            // ถ้าไม่มี ให้เปิด window ใหม่
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

/**
 * เริ่มติดตามตำแหน่ง
 */
function startLocationTracking(bookingId, type, csrfToken) {
    console.log('[ServiceWorker] Starting location tracking for booking:', bookingId);

    activeBookingId = bookingId;
    userType = type;

    // ใช้ Geolocation API
    if ('geolocation' in navigator) {
        locationWatchId = navigator.geolocation.watchPosition(
            (position) => {
                const locationData = {
                    bookingId: activeBookingId,
                    userType: userType,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    speed: position.coords.speed,
                    heading: position.coords.heading,
                    altitude: position.coords.altitude,
                    timestamp: new Date().toISOString(),
                    appState: 'background',
                    batteryLevel: null, // จะอัพเดทถ้ามี Battery API
                };

                // ลองส่งทันที
                sendLocation(locationData, csrfToken).catch(() => {
                    // ถ้าส่งไม่ได้ เก็บไว้ sync ทีหลัง
                    pendingLocations.push(locationData);
                    savePendingLocations();
                });
            },
            (error) => {
                console.error('[ServiceWorker] Geolocation error:', error);
            },
            {
                enableHighAccuracy: true,
                maximumAge: 10000,
                timeout: 30000,
            }
        );

        // ตั้ง interval สำรองสำหรับส่งทุก 30 วินาที
        setInterval(() => {
            if (activeBookingId) {
                getCurrentPositionAndSend(csrfToken);
            }
        }, 30000);
    }

    // แสดง notification ว่ากำลังติดตาม
    showTrackingNotification();
}

/**
 * หยุดติดตามตำแหน่ง
 */
function stopLocationTracking() {
    console.log('[ServiceWorker] Stopping location tracking');

    if (locationWatchId) {
        navigator.geolocation.clearWatch(locationWatchId);
        locationWatchId = null;
    }

    activeBookingId = null;
    userType = null;

    // ปิด notification
    self.registration.getNotifications({ tag: 'tracking-active' }).then((notifications) => {
        notifications.forEach((n) => n.close());
    });
}

/**
 * ดึงตำแหน่งปัจจุบันและส่ง
 */
function getCurrentPositionAndSend(csrfToken) {
    if (!('geolocation' in navigator)) return;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const locationData = {
                bookingId: activeBookingId,
                userType: userType,
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy,
                speed: position.coords.speed,
                heading: position.coords.heading,
                altitude: position.coords.altitude,
                timestamp: new Date().toISOString(),
                appState: document.visibilityState === 'visible' ? 'foreground' : 'background',
            };

            sendLocation(locationData, csrfToken);
        },
        (error) => console.error('[ServiceWorker] getCurrentPosition error:', error),
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

/**
 * ส่งตำแหน่งไปเซิร์ฟเวอร์
 */
async function sendLocation(locationData, csrfToken) {
    const endpoint = locationData.userType === 'provider'
        ? `/provider/bookings/${locationData.bookingId}/update-location`
        : `/api/v1/bookings/${locationData.bookingId}/update-location`;

    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            latitude: locationData.latitude,
            longitude: locationData.longitude,
            accuracy: locationData.accuracy,
            speed: locationData.speed,
            heading: locationData.heading,
            altitude: locationData.altitude,
            app_state: locationData.appState,
            source: 'gps',
        }),
    });

    if (!response.ok) {
        throw new Error('Failed to send location');
    }

    return response.json();
}

/**
 * Sync ตำแหน่งที่รอส่ง
 */
async function syncPendingLocations(csrfToken) {
    if (pendingLocations.length === 0) return;

    console.log('[ServiceWorker] Syncing', pendingLocations.length, 'pending locations');

    const locationsToSync = [...pendingLocations];
    pendingLocations = [];

    for (const location of locationsToSync) {
        try {
            await sendLocation(location, csrfToken);
        } catch (error) {
            // ถ้าส่งไม่ได้ เก็บกลับไป
            pendingLocations.push(location);
        }
    }

    savePendingLocations();
}

/**
 * บันทึก pending locations ลง IndexedDB
 */
function savePendingLocations() {
    // ใช้ IndexedDB หรือ localStorage
    try {
        const data = JSON.stringify(pendingLocations);
        // ใช้ cache API เก็บชั่วคราว
        caches.open('pending-locations').then((cache) => {
            cache.put('/pending-locations.json', new Response(data));
        });
    } catch (error) {
        console.error('[ServiceWorker] Error saving pending locations:', error);
    }
}

/**
 * โหลด pending locations จาก storage
 */
async function loadPendingLocations() {
    try {
        const cache = await caches.open('pending-locations');
        const response = await cache.match('/pending-locations.json');
        if (response) {
            const data = await response.json();
            pendingLocations = data || [];
        }
    } catch (error) {
        console.error('[ServiceWorker] Error loading pending locations:', error);
    }
}

/**
 * ตรวจสอบสถานะ booking
 */
async function checkBookingStatus() {
    if (!activeBookingId) return;

    try {
        const response = await fetch(`/api/v1/bookings/${activeBookingId}/live-tracking`);
        const data = await response.json();

        if (data.success) {
            const status = data.data.booking.status;

            // ถ้างานเสร็จหรือยกเลิก ให้หยุดติดตาม
            if (['completed', 'cancelled'].includes(status)) {
                stopLocationTracking();
                showCompletionNotification(status);
            }
        }
    } catch (error) {
        console.error('[ServiceWorker] Error checking booking status:', error);
    }
}

/**
 * แสดง notification ว่ากำลังติดตาม
 */
function showTrackingNotification() {
    self.registration.showNotification('กำลังติดตามตำแหน่ง', {
        body: 'ระบบกำลังแชร์ตำแหน่งของคุณกับอีกฝ่าย',
        icon: '/images/icons/icon-192.png',
        badge: '/images/icons/badge-72.png',
        tag: 'tracking-active',
        ongoing: true,
        requireInteraction: true,
        actions: [
            { action: 'stop', title: 'หยุดแชร์' },
            { action: 'open', title: 'เปิดแอพ' },
        ],
    });
}

/**
 * แสดง notification เมื่องานเสร็จ
 */
function showCompletionNotification(status) {
    const title = status === 'completed' ? 'บริการเสร็จสิ้น!' : 'การจองถูกยกเลิก';
    const body = status === 'completed'
        ? 'ขอบคุณที่ใช้บริการ กรุณาให้คะแนนผู้ให้บริการ'
        : 'การจองของคุณถูกยกเลิก';

    self.registration.showNotification(title, {
        body: body,
        icon: '/images/icons/icon-192.png',
        badge: '/images/icons/badge-72.png',
        tag: 'booking-complete',
        vibrate: [200, 100, 200, 100, 200],
        data: { bookingId: activeBookingId, userType: userType },
    });
}

/**
 * Handle booking status update จาก main thread
 */
function handleBookingStatusUpdate(status) {
    if (['completed', 'cancelled'].includes(status)) {
        stopLocationTracking();
    }
}

// โหลด pending locations เมื่อเริ่มต้น
loadPendingLocations();
