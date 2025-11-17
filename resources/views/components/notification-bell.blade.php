@php
    // ตรวจสอบ route prefix ปัจจุบัน (user/seller/admin)
    $routePrefix = '';
    if (request()->is('user/*') || request()->is('user')) {
        $routePrefix = 'user';
    } elseif (request()->is('seller/*') || request()->is('seller')) {
        $routePrefix = 'seller';
    } elseif (request()->is('admin/*') || request()->is('admin')) {
        $routePrefix = 'admin';
    } else {
        $routePrefix = 'user'; // default
    }
@endphp

{{-- Notification Bell Dropdown (แบบง่ายๆ เหมือนเมนูอื่นๆ) พร้อม Keyboard Navigation + Accessibility + Offline Support --}}
<div x-data="{
    open: false,
    loading: false,
    notifications: [],
    unreadCount: 0,
    routePrefix: '{{ $routePrefix }}',
    selectedIndex: -1,

    // 📡 Offline Support
    isOnline: navigator.onLine,
    cacheKey: 'notifications_cache_{{ $routePrefix }}',
    cacheExpiry: 5 * 60 * 1000, // 5 นาที

    // 🔔 WebSocket Support
    echoChannel: null,
    useWebSocket: false,

    async loadNotifications() {
        this.loading = true;

        // ตรวจสอบสถานะ offline
        if (!this.isOnline) {
            console.info('📴 Offline mode: Loading notifications from cache');
            this.loadFromCache();
            this.loading = false;
            return;
        }

        try {
            const response = await fetch(`/${this.routePrefix}/notifications/unread`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });
            if (response.ok) {
                const data = await response.json();
                this.notifications = data.notifications || [];
                this.unreadCount = data.unread_count || 0;

                // 💾 บันทึกลง cache
                this.saveToCache(data);
            } else {
                // หาก API ล้มเหลว ใช้ cache
                this.loadFromCache();
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            // หาก fetch ล้มเหลว ใช้ cache
            this.loadFromCache();
        }
        this.loading = false;
    },

    // 💾 บันทึกข้อมูลลง localStorage
    saveToCache(data) {
        try {
            const cache = {
                data: data,
                timestamp: Date.now()
            };
            localStorage.setItem(this.cacheKey, JSON.stringify(cache));
            console.debug('✅ Notifications cached successfully');
        } catch (error) {
            console.error('Failed to cache notifications:', error);
        }
    },

    // 📂 โหลดข้อมูลจาก localStorage
    loadFromCache() {
        try {
            const cached = localStorage.getItem(this.cacheKey);
            if (!cached) {
                console.info('No cached notifications found');
                return;
            }

            const cache = JSON.parse(cached);

            // ตรวจสอบว่า cache หมดอายุหรือยัง
            if (Date.now() - cache.timestamp > this.cacheExpiry) {
                console.info('⏰ Cache expired, removing...');
                localStorage.removeItem(this.cacheKey);
                return;
            }

            // โหลดข้อมูลจาก cache
            this.notifications = cache.data.notifications || [];
            this.unreadCount = cache.data.unread_count || 0;
            console.info('✅ Loaded notifications from cache');
        } catch (error) {
            console.error('Failed to load from cache:', error);
        }
    },

    async markAsRead(notificationId) {
        try {
            await fetch(`/${this.routePrefix}/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            await this.loadNotifications();
        } catch (error) {
            console.error('Error:', error);
        }
    },

    async markAllAsRead() {
        try {
            await fetch(`/${this.routePrefix}/notifications/read-all`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });
            await this.loadNotifications();
        } catch (error) {
            console.error('Error:', error);
        }
    },

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'เมื่อสักครู่';
        if (diff < 3600) return `${Math.floor(diff / 60)} นาทีที่แล้ว`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} ชั่วโมงที่แล้ว`;
        if (diff < 604800) return `${Math.floor(diff / 86400)} วันที่แล้ว`;

        return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' });
    },

    // 🎹 Keyboard Navigation Methods
    selectNext() {
        if (this.notifications.length === 0) return;
        this.selectedIndex = (this.selectedIndex + 1) % this.notifications.length;
        this.scrollToSelected();
    },

    selectPrevious() {
        if (this.notifications.length === 0) return;
        this.selectedIndex = this.selectedIndex <= 0
            ? this.notifications.length - 1
            : this.selectedIndex - 1;
        this.scrollToSelected();
    },

    markSelectedAsRead() {
        if (this.selectedIndex >= 0 && this.selectedIndex < this.notifications.length) {
            const notification = this.notifications[this.selectedIndex];
            this.markAsRead(notification.id);
        }
    },

    scrollToSelected() {
        // Scroll รายการที่เลือกให้เห็นใน viewport
        this.$nextTick(() => {
            const container = this.$refs.notificationList;
            const selected = container?.querySelector(`[data-index='${this.selectedIndex}']`);
            if (selected) {
                selected.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        });
    },

    closeDropdown() {
        this.open = false;
        this.selectedIndex = -1;
    },

    // 🔔 WebSocket Methods
    subscribeToWebSocket() {
        // ตรวจสอบว่ามี Echo และ user ID หรือไม่
        if (!window.Echo || !window.currentUser) {
            console.info('⚠️ Laravel Echo หรือ user ไม่พร้อม ใช้ HTTP polling');
            return false;
        }

        try {
            console.log('📡 Subscribing to WebSocket notifications...');

            // Subscribe to private channel
            this.echoChannel = window.Echo.private(`notifications.${window.currentUser.id}`);

            // Listen for notification events
            this.echoChannel.listen('NotificationSent', (event) => {
                console.log('📩 Real-time notification received:', event);
                this.handleWebSocketNotification(event.notification);
            });

            this.useWebSocket = true;
            console.log('✅ WebSocket notifications enabled');
            return true;

        } catch (error) {
            console.error('❌ WebSocket subscription failed:', error);
            console.info('↩️  Falling back to HTTP polling');
            return false;
        }
    },

    unsubscribeFromWebSocket() {
        if (this.echoChannel && window.Echo) {
            try {
                window.Echo.leave(`notifications.${window.currentUser?.id}`);
                this.echoChannel = null;
                this.useWebSocket = false;
                console.log('📭 Unsubscribed from WebSocket');
            } catch (error) {
                console.error('❌ Unsubscribe failed:', error);
            }
        }
    },

    handleWebSocketNotification(notification) {
        // เพิ่ม notification ใหม่ลงใน array
        this.notifications.unshift(notification);

        // เพิ่ม unread count
        if (!notification.is_read) {
            this.unreadCount++;
        }

        // บันทึกลง cache
        this.saveToCache({
            notifications: this.notifications,
            unread_count: this.unreadCount
        });

        // แสดง browser notification (ถ้า permission granted)
        this.showBrowserNotification(notification);
    },

    showBrowserNotification(notification) {
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                const notif = new Notification(notification.title || 'การแจ้งเตือน', {
                    body: notification.message,
                    icon: '/icons/notification-icon.png',
                    badge: '/icons/badge-icon.png',
                    tag: notification.id,
                    requireInteraction: notification.is_important || false,
                });

                // Click to open
                notif.onclick = () => {
                    window.focus();
                    if (notification.action_url) {
                        window.location.href = notification.action_url;
                    }
                    notif.close();
                };
            } catch (error) {
                console.debug('Browser notification not available:', error);
            }
        }
    }
}"
x-init="
    // โหลดข้อมูลครั้งแรก
    loadNotifications();

    // 🔔 พยายาม subscribe WebSocket
    setTimeout(() => {
        const wsEnabled = subscribeToWebSocket();

        // ถ้า WebSocket ล้มเหลว หรือไม่พร้อม ใช้ HTTP polling
        if (!wsEnabled) {
            console.info('↩️  Using HTTP polling (every 30s)');
            setInterval(() => loadNotifications(), 30000);
        } else {
            console.info('✅ Using WebSocket real-time notifications');
            // Fallback: HTTP polling ทุก 5 นาที เพื่อ sync
            setInterval(() => loadNotifications(), 5 * 60 * 1000);
        }
    }, 1000); // รอ Echo initialize 1 วินาที

    // 📡 ฟัง online/offline events
    window.addEventListener('online', () => {
        console.info('🌐 Connection restored');
        this.isOnline = true;
        this.loadNotifications(); // Sync เมื่อกลับมา online

        // Re-subscribe WebSocket ถ้าไม่ได้ใช้อยู่
        if (!this.useWebSocket) {
            setTimeout(() => this.subscribeToWebSocket(), 1000);
        }
    });

    window.addEventListener('offline', () => {
        console.info('📴 Connection lost');
        this.isOnline = false;
    });

    // Cleanup เมื่อ component ถูก destroy
    window.addEventListener('beforeunload', () => {
        this.unsubscribeFromWebSocket();
    });
"
@keydown.escape.window="if (open) closeDropdown()"
@keydown.arrow-down.prevent="if (open) selectNext()"
@keydown.arrow-up.prevent="if (open) selectPrevious()"
@keydown.enter.prevent="if (open && selectedIndex >= 0) markSelectedAsRead()"
class="relative">

    <!-- Bell Button พร้อม Accessibility -->
    <button
        id="notifications-button"
        @click="open = !open; if(open) loadNotifications()"
        :aria-expanded="open.toString()"
        aria-haspopup="true"
        aria-label="การแจ้งเตือน"
        :aria-label="unreadCount > 0 ? `การแจ้งเตือน ${unreadCount} รายการที่ยังไม่ได้อ่าน` : 'การแจ้งเตือน'"
        class="relative p-2 text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 rounded-lg transition-all"
        title="การแจ้งเตือน">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        <!-- Unread Badge -->
        <span
            x-show="unreadCount > 0"
            x-text="unreadCount > 99 ? '99+' : unreadCount"
            aria-live="polite"
            :aria-label="`${unreadCount} รายการที่ยังไม่ได้อ่าน`"
            class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
        ></span>

        <!-- Offline Indicator -->
        <span
            x-show="!isOnline"
            title="ออフไลน์ - แสดงข้อมูลแคช"
            aria-label="ออฟไลน์"
            class="absolute -bottom-1 -right-1 w-3 h-3 bg-gray-400 border-2 border-white dark:border-gray-800 rounded-full"
        ></span>
    </button>

    <!-- Dropdown พร้อม Accessibility -->
    <div
        x-show="open"
        @click.away="closeDropdown()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        role="menu"
        aria-labelledby="notifications-button"
        aria-orientation="vertical"
        class="absolute right-0 z-50 w-96 mt-2 bg-white dark:bg-gray-800 rounded-lg shadow-xl border dark:border-gray-700 max-h-[32rem] overflow-hidden"
        style="display: none;">

        <!-- Header -->
        <div class="px-4 py-3 border-b dark:border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">การแจ้งเตือน</h3>
                <button
                    @click="markAllAsRead()"
                    x-show="unreadCount > 0"
                    class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    อ่านทั้งหมด
                </button>
            </div>

            <!-- Offline Warning -->
            <div x-show="!isOnline" class="flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs text-gray-600 dark:text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"></path>
                </svg>
                <span>📴 ออฟไลน์ - แสดงข้อมูลจากแคช</span>
            </div>

            <!-- WebSocket Status -->
            <div x-show="isOnline && useWebSocket" class="flex items-center gap-2 px-3 py-2 bg-green-100 dark:bg-green-900/30 rounded-lg text-xs text-green-700 dark:text-green-400">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                </span>
                <span>🔔 Real-time WebSocket</span>
            </div>
        </div>

        <!-- Notifications List พร้อม Keyboard Navigation Support -->
        <div class="overflow-y-auto max-h-96" x-ref="notificationList">
            <!-- Loading -->
            <template x-if="loading">
                <div class="flex items-center justify-center py-8">
                    <svg class="animate-spin h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </template>

            <!-- Empty State -->
            <template x-if="!loading && notifications.length === 0">
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="mt-2">ไม่มีการแจ้งเตือน</p>
                </div>
            </template>

            <!-- Notification Items พร้อม Keyboard Navigation + Accessibility -->
            <template x-for="(notification, index) in notifications" :key="notification.id">
                <div
                    @click="markAsRead(notification.id)"
                    :data-index="index"
                    :class="{
                        'bg-blue-50 dark:bg-blue-900/20': !notification.is_read,
                        'ring-2 ring-blue-500 ring-inset': selectedIndex === index
                    }"
                    role="menuitem"
                    :tabindex="selectedIndex === index ? 0 : -1"
                    :aria-selected="(selectedIndex === index).toString()"
                    class="px-4 py-3 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-all focus:outline-none">
                    <div class="flex items-start">
                        <!-- Icon -->
                        <div
                            :class="'text-' + notification.color + '-500'"
                            class="flex-shrink-0 mr-3 text-2xl"
                            aria-hidden="true"
                            x-text="notification.icon"></div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                   x-text="notification.title"></p>
                                <span
                                    x-show="notification.is_important"
                                    aria-label="การแจ้งเตือนสำคัญ"
                                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                    สำคัญ
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400"
                               x-text="notification.message"></p>
                            <div class="flex items-center mt-2 text-xs text-gray-500 dark:text-gray-400">
                                <span x-text="formatDate(notification.created_at)"></span>
                                <template x-if="notification.action_url">
                                    <a :href="notification.action_url"
                                       x-text="notification.action_text"
                                       class="ml-3 text-blue-600 hover:text-blue-800 dark:text-blue-400 hover:underline focus:outline-none focus:underline"
                                       @click.stop></a>
                                </template>
                            </div>
                        </div>

                        <!-- Unread Indicator -->
                        <div
                            x-show="!notification.is_read"
                            aria-label="ยังไม่ได้อ่าน"
                            class="flex-shrink-0 ml-2 w-2 h-2 bg-blue-600 rounded-full"></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t dark:border-gray-700">
            <a :href="`/${routePrefix}/notifications`"
               class="block text-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                ดูการแจ้งเตือนทั้งหมด
            </a>
        </div>
    </div>
</div>
