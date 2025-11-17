<!-- Immediate Notification Popup (Floating/Toast Style) พร้อม Keyboard Navigation + Accessibility -->
<div x-data="immediateNotificationPopup()" x-init="init()" class="fixed inset-0 pointer-events-none z-[9999]">
    <!-- Container for notifications - positioned at top right -->
    <div class="fixed top-4 right-4 flex flex-col gap-3 pointer-events-auto max-w-sm w-full">
        <template x-for="(notification, index) in notifications" :key="notification.id">
            <div
                x-show="notification.visible"
                @keydown.escape="dismissNotification(index)"
                x-transition:enter="transform transition ease-out duration-300"
                x-transition:enter-start="translate-x-full opacity-0"
                x-transition:enter-end="translate-x-0 opacity-100"
                x-transition:leave="transform transition ease-in duration-200"
                x-transition:leave-start="translate-x-0 opacity-100"
                x-transition:leave-end="translate-x-full opacity-0"
                :role="notification.priority === 'urgent' || notification.priority === 'high' ? 'alert' : 'status'"
                :aria-live="notification.priority === 'urgent' ? 'assertive' : 'polite'"
                aria-atomic="true"
                :class="{
                    'border-red-500': notification.priority === 'urgent',
                    'border-orange-500': notification.priority === 'high',
                    'border-blue-500': notification.priority === 'normal',
                    'border-gray-500': notification.priority === 'low'
                }"
                class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl border-l-4 overflow-hidden focus-within:ring-2 focus-within:ring-blue-500"
                style="display: none;"
            >
                <!-- Content -->
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <!-- Icon -->
                        <div class="flex-shrink-0 text-3xl" aria-hidden="true" x-text="notification.icon"></div>

                        <!-- Text Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white" x-text="notification.title"></h4>
                                <button
                                    @click="dismissNotification(index)"
                                    aria-label="ปิดการแจ้งเตือน"
                                    title="ปิด (Esc)"
                                    class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded p-0.5 transition-all"
                                >
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2" x-text="notification.message"></p>

                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="notification.created_at"></span>

                                <template x-if="notification.action_url">
                                    <a
                                        :href="notification.action_url"
                                        :aria-label="`${notification.action_text || 'ดูเพิ่มเติม'} - เปิดในแท็บใหม่`"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 hover:underline focus:outline-none focus:underline focus:ring-2 focus:ring-blue-500 rounded px-1 transition-all"
                                        x-text="notification.action_text || 'ดูเพิ่มเติม'"
                                    ></a>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar พร้อม Accessibility -->
                <div class="h-1 bg-gray-200 dark:bg-gray-700" role="progressbar" :aria-valuenow="notification.progress" aria-valuemin="0" aria-valuemax="100" aria-label="เวลาที่เหลือก่อนปิดอัตโนมัติ">
                    <div
                        class="h-full transition-all duration-100 ease-linear"
                        :class="{
                            'bg-red-500': notification.priority === 'urgent',
                            'bg-orange-500': notification.priority === 'high',
                            'bg-blue-500': notification.priority === 'normal',
                            'bg-gray-500': notification.priority === 'low'
                        }"
                        :style="'width: ' + notification.progress + '%'"
                    ></div>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function immediateNotificationPopup() {
    return {
        notifications: [],
        autoDismissTime: 8000, // 8 seconds

        init() {
            // Listen for immediate notification events
            window.addEventListener('show-immediate-notification', (event) => {
                const { notifications } = event.detail;
                notifications.forEach(notification => {
                    this.showNotification(notification);
                });
            });
        },

        showNotification(notification) {
            // Add notification to array
            const notif = {
                ...notification,
                visible: true,
                progress: 100,
                timer: null
            };

            this.notifications.push(notif);

            // Auto dismiss after specified time
            const index = this.notifications.length - 1;
            this.startAutoDismiss(index);

            // Play notification sound (optional)
            this.playNotificationSound();
        },

        startAutoDismiss(index) {
            const notification = this.notifications[index];
            const interval = 100; // Update every 100ms
            const steps = this.autoDismissTime / interval;
            const decrement = 100 / steps;

            notification.timer = setInterval(() => {
                notification.progress -= decrement;

                if (notification.progress <= 0) {
                    this.dismissNotification(index);
                }
            }, interval);
        },

        dismissNotification(index) {
            const notification = this.notifications[index];

            if (notification && notification.timer) {
                clearInterval(notification.timer);
            }

            if (notification) {
                notification.visible = false;

                // Remove from array after transition
                setTimeout(() => {
                    this.notifications.splice(index, 1);
                }, 300);
            }
        },

        playNotificationSound() {
            try {
                // Simple notification sound using Web Audio API
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.value = 800;
                oscillator.type = 'sine';

                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.3);
            } catch (error) {
                // Silently fail if audio is not supported
                console.debug('Notification sound not available');
            }
        }
    }
}
</script>

<style>
/* Additional styles for smooth animations */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
</style>
