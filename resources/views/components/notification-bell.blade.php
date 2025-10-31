<div class="relative" x-data="notificationBell()" x-init="init()">
    <!-- Bell Icon -->
    <button
        @click="toggleDropdown()"
        class="relative p-2 text-gray-600 hover:text-gray-800 dark:text-gray-300 dark:hover:text-white focus:outline-none"
        title="การแจ้งเตือน"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        <!-- Unread Count Badge -->
        <span
            x-show="unreadCount > 0"
            x-text="unreadCount > 99 ? '99+' : unreadCount"
            class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
        ></span>
    </button>

    <!-- Dropdown -->
    <div
        x-show="isOpen"
        @click.away="closeDropdown()"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 z-50 w-96 mt-2 bg-white dark:bg-gray-800 rounded-lg shadow-xl border dark:border-gray-700 max-h-[32rem] overflow-hidden"
        style="display: none;"
    >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white">การแจ้งเตือน</h3>
            <button
                @click="markAllAsRead()"
                x-show="unreadCount > 0"
                class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            >
                อ่านทั้งหมด
            </button>
        </div>

        <!-- Notifications List -->
        <div class="overflow-y-auto max-h-96">
            <template x-if="loading">
                <div class="flex items-center justify-center py-8">
                    <svg class="animate-spin h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </template>

            <template x-if="!loading && notifications.length === 0">
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="mt-2">ไม่มีการแจ้งเตือน</p>
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <div
                    @click="markAsRead(notification.id)"
                    :class="{'bg-blue-50 dark:bg-blue-900/20': !notification.is_read}"
                    class="px-4 py-3 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
                >
                    <div class="flex items-start">
                        <!-- Icon -->
                        <div
                            :class="'text-' + notification.color + '-500'"
                            class="flex-shrink-0 mr-3 text-2xl"
                            x-text="notification.icon"
                        ></div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <p
                                    class="text-sm font-medium text-gray-900 dark:text-white truncate"
                                    x-text="notification.title"
                                ></p>
                                <span
                                    x-show="notification.is_important"
                                    class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"
                                >
                                    สำคัญ
                                </span>
                            </div>
                            <p
                                class="text-sm text-gray-600 dark:text-gray-400"
                                x-text="notification.message"
                            ></p>
                            <div class="flex items-center mt-2 text-xs text-gray-500 dark:text-gray-500">
                                <span x-text="formatDate(notification.created_at)"></span>
                                <template x-if="notification.action_url">
                                    <a
                                        :href="notification.action_url"
                                        x-text="notification.action_text"
                                        class="ml-3 text-blue-600 hover:text-blue-800 dark:text-blue-400"
                                        @click.stop
                                    ></a>
                                </template>
                            </div>
                        </div>

                        <!-- Unread Indicator -->
                        <div
                            x-show="!notification.is_read"
                            class="flex-shrink-0 ml-2 w-2 h-2 bg-blue-600 rounded-full"
                        ></div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t dark:border-gray-700">
            <a
                href="{{ route('user.notifications.index') }}"
                class="block text-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
            >
                ดูการแจ้งเตือนทั้งหมด
            </a>
        </div>
    </div>
</div>

<script>
function notificationBell() {
    return {
        isOpen: false,
        loading: false,
        notifications: [],
        unreadCount: 0,

        init() {
            this.loadNotifications();
            this.checkImmediateNotifications();

            // Poll for new notifications every 30 seconds
            setInterval(() => {
                this.loadNotifications();
                this.checkImmediateNotifications();
            }, 30000);
        },

        async checkImmediateNotifications() {
            try {
                const response = await fetch('{{ route("user.notifications.immediate") }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                if (data.notifications && data.notifications.length > 0) {
                    // Dispatch event for immediate notifications popup
                    window.dispatchEvent(new CustomEvent('show-immediate-notification', {
                        detail: { notifications: data.notifications }
                    }));
                }
            } catch (error) {
                console.error('Error checking immediate notifications:', error);
            }
        },

        toggleDropdown() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.loadNotifications();
            }
        },

        closeDropdown() {
            this.isOpen = false;
        },

        async loadNotifications() {
            try {
                const response = await fetch('{{ route("user.notifications.unread") }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                this.notifications = data.notifications;
                this.unreadCount = data.unread_count;
            } catch (error) {
                console.error('Error loading notifications:', error);
            }
        },

        async markAsRead(notificationId) {
            try {
                await fetch(`/user/notifications/${notificationId}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                // Reload notifications
                this.loadNotifications();
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        },

        async markAllAsRead() {
            try {
                await fetch('{{ route("user.notifications.read-all") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                // Reload notifications
                this.loadNotifications();
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000); // seconds

            if (diff < 60) return 'เมื่อสักครู่';
            if (diff < 3600) return `${Math.floor(diff / 60)} นาทีที่แล้ว`;
            if (diff < 86400) return `${Math.floor(diff / 3600)} ชั่วโมงที่แล้ว`;
            if (diff < 604800) return `${Math.floor(diff / 86400)} วันที่แล้ว`;

            return date.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
        }
    }
}
</script>
