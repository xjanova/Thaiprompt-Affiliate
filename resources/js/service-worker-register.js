/**
 * Service Worker Registration
 *
 * จัดการการ register และ update Service Worker
 *
 * @version 1.0.0
 */

/**
 * ตรวจสอบว่า browser รองรับ Service Worker หรือไม่
 */
function isServiceWorkerSupported() {
    return 'serviceWorker' in navigator;
}

/**
 * Register Service Worker
 */
async function registerServiceWorker() {
    if (!isServiceWorkerSupported()) {
        console.warn('⚠️ Service Worker ไม่รองรับในบราว์เซอร์นี้');
        return null;
    }

    try {
        console.log('📦 กำลัง register Service Worker...');

        const registration = await navigator.serviceWorker.register('/service-worker.js', {
            scope: '/'
        });

        console.log('✅ Service Worker registered successfully:', registration.scope);

        // ตรวจสอบ update
        registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            console.log('🔄 Service Worker update found');

            newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    console.log('✅ Service Worker updated, reload required');

                    // แจ้งเตือนผู้ใช้ว่ามี update (optional)
                    if (confirm('มีการอัพเดทใหม่ ต้องการโหลดหน้าใหม่หรือไม่?')) {
                        newWorker.postMessage({ action: 'skipWaiting' });
                        window.location.reload();
                    }
                }
            });
        });

        // ฟัง controller change
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            console.log('🔄 Service Worker controller changed');
            window.location.reload();
        });

        return registration;

    } catch (error) {
        console.error('❌ Service Worker registration failed:', error);
        return null;
    }
}

/**
 * Unregister Service Worker (สำหรับ debugging)
 */
async function unregisterServiceWorker() {
    if (!isServiceWorkerSupported()) {
        return false;
    }

    try {
        const registrations = await navigator.serviceWorker.getRegistrations();

        for (const registration of registrations) {
            await registration.unregister();
            console.log('🗑️ Service Worker unregistered');
        }

        return true;

    } catch (error) {
        console.error('❌ Failed to unregister Service Worker:', error);
        return false;
    }
}

/**
 * ขอ permission สำหรับ notifications
 */
async function requestNotificationPermission() {
    if (!('Notification' in window)) {
        console.warn('⚠️ Browser ไม่รองรับ Notifications');
        return 'denied';
    }

    if (Notification.permission === 'granted') {
        return 'granted';
    }

    if (Notification.permission !== 'denied') {
        const permission = await Notification.requestPermission();
        console.log('🔔 Notification permission:', permission);
        return permission;
    }

    return Notification.permission;
}

/**
 * Background Sync Registration
 */
async function registerBackgroundSync() {
    if (!isServiceWorkerSupported()) {
        return false;
    }

    try {
        const registration = await navigator.serviceWorker.ready;

        if ('sync' in registration) {
            await registration.sync.register('sync-notifications');
            console.log('✅ Background sync registered');
            return true;
        } else {
            console.warn('⚠️ Background Sync ไม่รองรับในบราว์เซอร์นี้');
            return false;
        }

    } catch (error) {
        console.error('❌ Background sync registration failed:', error);
        return false;
    }
}

/**
 * ล้าง cache (สำหรับ debugging)
 */
async function clearServiceWorkerCache() {
    if (!isServiceWorkerSupported()) {
        return false;
    }

    try {
        const registration = await navigator.serviceWorker.ready;

        if (registration.active) {
            registration.active.postMessage({ action: 'clearCache' });
            console.log('🗑️ Cache clear request sent to Service Worker');
            return true;
        }

        return false;

    } catch (error) {
        console.error('❌ Failed to clear cache:', error);
        return false;
    }
}

/**
 * Initialize Service Worker
 * เรียกใช้เมื่อ page load
 */
async function initServiceWorker() {
    // รอให้ page load เสร็จก่อน
    if (document.readyState === 'loading') {
        window.addEventListener('DOMContentLoaded', init);
    } else {
        await init();
    }

    async function init() {
        // Register Service Worker
        const registration = await registerServiceWorker();

        if (!registration) {
            return;
        }

        // ขอ permission สำหรับ notifications (optional)
        // await requestNotificationPermission();

        // Register background sync
        await registerBackgroundSync();

        console.log('✅ Service Worker initialization complete');
    }
}

// Auto-initialize เมื่อ script โหลด
initServiceWorker();

// Export functions สำหรับใช้งานภายนอก
if (typeof window !== 'undefined') {
    window.ServiceWorkerManager = {
        register: registerServiceWorker,
        unregister: unregisterServiceWorker,
        requestNotificationPermission: requestNotificationPermission,
        registerBackgroundSync: registerBackgroundSync,
        clearCache: clearServiceWorkerCache,
        isSupported: isServiceWorkerSupported,
    };
}

console.log('✅ Service Worker Manager loaded');
