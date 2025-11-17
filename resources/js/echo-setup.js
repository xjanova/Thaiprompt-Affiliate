/**
 * Laravel Echo Setup
 *
 * Real-time WebSocket notifications ด้วย Laravel Echo + Pusher
 *
 * @version 1.0.0
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// ทำให้ Pusher พร้อมใช้งานใน global scope
window.Pusher = Pusher;

/**
 * Initialize Laravel Echo
 */
function initEcho() {
    // ตรวจสอบว่ามี configuration หรือไม่
    if (!window.echoConfig) {
        console.warn('⚠️ Laravel Echo config not found');
        return null;
    }

    const config = window.echoConfig;

    console.log('📡 Initializing Laravel Echo...');
    console.log('Broadcasting driver:', config.driver);

    try {
        // สร้าง Echo instance
        window.Echo = new Echo({
            broadcaster: config.driver || 'pusher',
            key: config.key,
            cluster: config.cluster || 'ap1',
            wsHost: config.wsHost,
            wsPort: config.wsPort || 6001,
            wssPort: config.wssPort || 6001,
            forceTLS: config.forceTLS !== false,
            encrypted: config.encrypted !== false,
            disableStats: true,
            enabledTransports: ['ws', 'wss'],

            // Authorization
            authEndpoint: config.authEndpoint || '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                }
            }
        });

        console.log('✅ Laravel Echo initialized successfully');
        return window.Echo;

    } catch (error) {
        console.error('❌ Failed to initialize Laravel Echo:', error);
        return null;
    }
}

/**
 * Subscribe to notifications channel
 *
 * @param {number} userId - User ID
 * @param {function} callback - Callback function เมื่อได้รับ notification
 * @returns {object|null} - Channel subscription
 */
function subscribeToNotifications(userId, callback) {
    if (!window.Echo) {
        console.warn('⚠️ Laravel Echo not initialized');
        return null;
    }

    console.log(`📬 Subscribing to notifications for user ${userId}...`);

    try {
        // Subscribe to private channel
        const channel = window.Echo.private(`notifications.${userId}`);

        // Listen for notification events
        channel.listen('NotificationSent', (event) => {
            console.log('📩 New notification received:', event);

            if (typeof callback === 'function') {
                callback(event.notification);
            }
        });

        console.log('✅ Subscribed to notifications channel');
        return channel;

    } catch (error) {
        console.error('❌ Failed to subscribe to notifications:', error);
        return null;
    }
}

/**
 * Unsubscribe from notifications channel
 *
 * @param {number} userId - User ID
 */
function unsubscribeFromNotifications(userId) {
    if (!window.Echo) {
        return;
    }

    console.log(`📭 Unsubscribing from notifications for user ${userId}...`);

    try {
        window.Echo.leave(`notifications.${userId}`);
        console.log('✅ Unsubscribed from notifications channel');
    } catch (error) {
        console.error('❌ Failed to unsubscribe:', error);
    }
}

/**
 * Subscribe to immediate notification channel (toast)
 *
 * @param {number} userId - User ID
 * @param {function} callback - Callback function
 * @returns {object|null} - Channel subscription
 */
function subscribeToImmediateNotifications(userId, callback) {
    if (!window.Echo) {
        console.warn('⚠️ Laravel Echo not initialized');
        return null;
    }

    console.log(`📬 Subscribing to immediate notifications for user ${userId}...`);

    try {
        const channel = window.Echo.private(`notifications.immediate.${userId}`);

        channel.listen('ImmediateNotificationSent', (event) => {
            console.log('🔔 Immediate notification received:', event);

            if (typeof callback === 'function') {
                callback(event.notifications);
            }

            // Trigger immediate notification popup event
            window.dispatchEvent(new CustomEvent('show-immediate-notification', {
                detail: { notifications: event.notifications }
            }));
        });

        console.log('✅ Subscribed to immediate notifications channel');
        return channel;

    } catch (error) {
        console.error('❌ Failed to subscribe to immediate notifications:', error);
        return null;
    }
}

/**
 * Check Echo connection status
 *
 * @returns {string} - Connection status
 */
function getConnectionStatus() {
    if (!window.Echo || !window.Echo.connector) {
        return 'disconnected';
    }

    const pusher = window.Echo.connector.pusher;

    if (!pusher) {
        return 'disconnected';
    }

    // Pusher connection states: initialized, connecting, connected, unavailable, failed, disconnected
    return pusher.connection.state;
}

/**
 * Listen to connection events
 */
function listenToConnectionEvents() {
    if (!window.Echo || !window.Echo.connector) {
        return;
    }

    const pusher = window.Echo.connector.pusher;

    if (!pusher) {
        return;
    }

    pusher.connection.bind('connecting', () => {
        console.log('🔄 WebSocket connecting...');
    });

    pusher.connection.bind('connected', () => {
        console.log('🌐 WebSocket connected');
    });

    pusher.connection.bind('unavailable', () => {
        console.warn('⚠️ WebSocket unavailable');
    });

    pusher.connection.bind('failed', () => {
        console.error('❌ WebSocket connection failed');
    });

    pusher.connection.bind('disconnected', () => {
        console.log('📴 WebSocket disconnected');
    });
}

/**
 * Disconnect Echo
 */
function disconnectEcho() {
    if (window.Echo && window.Echo.disconnect) {
        console.log('📴 Disconnecting Laravel Echo...');
        window.Echo.disconnect();
    }
}

// Export functions
export {
    initEcho,
    subscribeToNotifications,
    unsubscribeFromNotifications,
    subscribeToImmediateNotifications,
    getConnectionStatus,
    listenToConnectionEvents,
    disconnectEcho
};

// Auto-initialize if config exists
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (window.echoConfig) {
            const echo = initEcho();
            if (echo) {
                listenToConnectionEvents();
            }
        }
    });
} else {
    if (window.echoConfig) {
        const echo = initEcho();
        if (echo) {
            listenToConnectionEvents();
        }
    }
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    disconnectEcho();
});

console.log('✅ Laravel Echo Setup loaded');
