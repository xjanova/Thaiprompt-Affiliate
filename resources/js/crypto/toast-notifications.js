/**
 * Toast Notification System
 * Provides user-friendly alerts and error messages
 */

export class ToastNotification {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Create toast container if it doesn't exist
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'fixed top-4 right-4 z-[9999] space-y-2';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    }

    /**
     * Show a toast notification
     * @param {string} message - The message to display
     * @param {string} type - Type of notification (success, error, warning, info)
     * @param {number} duration - How long to show the toast (ms)
     */
    show(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `transform transition-all duration-300 ease-in-out translate-x-0 opacity-100 max-w-md w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden`;

        const colors = {
            success: {
                bg: 'bg-green-500',
                icon: '✓',
                iconBg: 'bg-green-100 dark:bg-green-900',
                iconText: 'text-green-600 dark:text-green-300'
            },
            error: {
                bg: 'bg-red-500',
                icon: '✕',
                iconBg: 'bg-red-100 dark:bg-red-900',
                iconText: 'text-red-600 dark:text-red-300'
            },
            warning: {
                bg: 'bg-yellow-500',
                icon: '⚠',
                iconBg: 'bg-yellow-100 dark:bg-yellow-900',
                iconText: 'text-yellow-600 dark:text-yellow-300'
            },
            info: {
                bg: 'bg-blue-500',
                icon: 'ℹ',
                iconBg: 'bg-blue-100 dark:bg-blue-900',
                iconText: 'text-blue-600 dark:text-blue-300'
            }
        };

        const style = colors[type] || colors.info;

        toast.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="${style.iconBg} rounded-lg p-2">
                            <span class="${style.iconText} text-xl font-bold">${style.icon}</span>
                        </div>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            ${message}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button onclick="this.closest('.transform').remove()" class="inline-flex text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none">
                            <span class="text-xl">×</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="h-1 ${style.bg}">
                <div class="h-full bg-gradient-to-r from-transparent to-white/50 animate-progress"></div>
            </div>
        `;

        this.container.appendChild(toast);

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }

        return toast;
    }

    success(message, duration = 5000) {
        return this.show(message, 'success', duration);
    }

    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }

    warning(message, duration = 5000) {
        return this.show(message, 'warning', duration);
    }

    info(message, duration = 5000) {
        return this.show(message, 'info', duration);
    }

    /**
     * Show a confirmation dialog
     * @param {string} message - The message to display
     * @param {Function} onConfirm - Callback when confirmed
     * @param {Function} onCancel - Callback when cancelled
     */
    confirm(message, onConfirm, onCancel = null) {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[9999] overflow-y-auto bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80 transition-opacity';

        const dialog = document.createElement('div');
        dialog.className = 'flex items-center justify-center min-h-screen px-4';
        dialog.innerHTML = `
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl">
                <div class="flex items-start mb-4">
                    <div class="flex-shrink-0">
                        <div class="bg-yellow-100 dark:bg-yellow-900 rounded-lg p-2">
                            <span class="text-yellow-600 dark:text-yellow-300 text-2xl">⚠</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                            ยืนยันการทำงาน
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            ${message}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button id="cancel-btn" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        ยกเลิก
                    </button>
                    <button id="confirm-btn" class="px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg hover:from-amber-600 hover:to-orange-600 transition">
                        ยืนยัน
                    </button>
                </div>
            </div>
        `;

        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        const confirmBtn = dialog.querySelector('#confirm-btn');
        const cancelBtn = dialog.querySelector('#cancel-btn');

        confirmBtn.addEventListener('click', () => {
            if (onConfirm) onConfirm();
            overlay.remove();
        });

        cancelBtn.addEventListener('click', () => {
            if (onCancel) onCancel();
            overlay.remove();
        });

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                if (onCancel) onCancel();
                overlay.remove();
            }
        });
    }
}

// Add CSS animation for progress bar
const style = document.createElement('style');
style.textContent = `
    @keyframes progress {
        from { width: 100%; }
        to { width: 0%; }
    }
    .animate-progress {
        animation: progress 5s linear forwards;
    }
`;
document.head.appendChild(style);

// Export singleton instance
export const toast = new ToastNotification();
