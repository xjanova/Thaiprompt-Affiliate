@php
    $enabled = \App\Models\CookieSetting::get('cookie_banner_enabled', true);
    $title = \App\Models\CookieSetting::get('cookie_banner_title', 'เราใช้คุกกี้เพื่อปรับปรุงประสบการณ์ของคุณ');
    $description = \App\Models\CookieSetting::get('cookie_banner_description', 'เราใช้คุกกี้เพื่อวิเคราะห์การใช้งานเว็บไซต์ ปรับปรุงประสบการณ์ของคุณ และแสดงเนื้อหาที่เหมาะสม คุณสามารถจัดการการตั้งค่าคุกกี้ได้ตามต้องการ');
    $policyUrl = \App\Models\CookieSetting::get('cookie_policy_url', '/cookie-policy');
@endphp

@if($enabled)
<div
    x-data="cookieConsent()"
    x-show="!hasConsent"
    x-cloak
    class="fixed bottom-0 left-0 right-0 z-50 p-4 md:p-6 bg-white dark:bg-gray-900 shadow-2xl border-t-4 border-indigo-600"
    style="display: none;"
>
    <div class="container mx-auto max-w-7xl">
        <!-- Simple View -->
        <div x-show="!showDetails" class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $title }}
                    </h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ $description }}
                </p>
                <a href="{{ $policyUrl }}" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800 underline mt-1 inline-block">
                    อ่านนโยบายคุกกี้
                </a>
            </div>

            <div class="flex flex-wrap gap-3">
                <button
                    @click="showDetails = true"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                >
                    ตั้งค่าคุกกี้
                </button>
                <button
                    @click="acceptAll()"
                    class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-lg hover:shadow-xl"
                >
                    ยอมรับทั้งหมด
                </button>
            </div>
        </div>

        <!-- Detailed Settings -->
        <div x-show="showDetails" class="space-y-4" style="display: none;">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    ตั้งค่าคุกกี้
                </h3>
                <button
                    @click="showDetails = false"
                    class="text-gray-500 hover:text-gray-700"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Cookie Categories -->
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <!-- Necessary Cookies -->
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                                คุกกี้ที่จำเป็น
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                คุกกี้เหล่านี้จำเป็นสำหรับการทำงานของเว็บไซต์ และไม่สามารถปิดได้
                            </p>
                        </div>
                        <div class="ml-4">
                            <span class="text-sm text-gray-500 dark:text-gray-400">เปิดอยู่เสมอ</span>
                        </div>
                    </div>
                </div>

                <!-- Analytics Cookies -->
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                                คุกกี้วิเคราะห์
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                ช่วยให้เราเข้าใจว่าผู้เยี่ยมชมใช้งานเว็บไซต์อย่างไร เพื่อปรับปรุงประสบการณ์ของคุณ
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="preferences.analytics" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Marketing Cookies -->
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                                คุกกี้การตลาด
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                ใช้เพื่อติดตามผู้เยี่ยมชมในเว็บไซต์และแสดงโฆษณาที่เกี่ยวข้อง
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="preferences.marketing" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Preferences Cookies -->
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1">
                                คุกกี้การตั้งค่า
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                จดจำการตั้งค่าของคุณ เช่น ภาษา และการปรับแต่งอื่นๆ
                            </p>
                        </div>
                        <div class="ml-4">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="preferences.preferences" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button
                    @click="rejectAll()"
                    class="px-6 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                >
                    ปฏิเสธทั้งหมด
                </button>
                <button
                    @click="savePreferences()"
                    class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-lg"
                >
                    บันทึกการตั้งค่า
                </button>
                <button
                    @click="acceptAll()"
                    class="px-6 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors shadow-lg"
                >
                    ยอมรับทั้งหมด
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function cookieConsent() {
    return {
        hasConsent: false,
        showDetails: false,
        preferences: {
            necessary: true,
            analytics: false,
            marketing: false,
            preferences: false,
        },

        init() {
            // Check if user has already consented
            const consent = localStorage.getItem('cookie_consent');
            if (consent) {
                this.hasConsent = true;
                this.preferences = JSON.parse(consent);
                this.initTracking();
            }
        },

        acceptAll() {
            this.preferences = {
                necessary: true,
                analytics: true,
                marketing: true,
                preferences: true,
            };
            this.saveConsent();
        },

        rejectAll() {
            this.preferences = {
                necessary: true,
                analytics: false,
                marketing: false,
                preferences: false,
            };
            this.saveConsent();
        },

        savePreferences() {
            this.saveConsent();
        },

        async saveConsent() {
            try {
                const response = await fetch('/api/cookie-consent', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.preferences),
                });

                if (response.ok) {
                    localStorage.setItem('cookie_consent', JSON.stringify(this.preferences));
                    this.hasConsent = true;
                    this.initTracking();
                }
            } catch (error) {
                console.error('Error saving cookie consent:', error);
            }
        },

        initTracking() {
            if (this.preferences.analytics) {
                // Start page tracking
                this.trackPageView();

                // Track page visibility
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        this.trackPageView();
                    }
                });

                // Track before unload
                window.addEventListener('beforeunload', () => {
                    this.trackPageView();
                });
            }
        },

        async trackPageView() {
            if (!this.preferences.analytics) return;

            try {
                await fetch('/api/cookie-track-page', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        page_url: window.location.href,
                    }),
                });
            } catch (error) {
                console.error('Error tracking page view:', error);
            }
        },
    };
}

// Global tracking functions
window.trackKeyword = async function(keyword) {
    const consent = JSON.parse(localStorage.getItem('cookie_consent') || '{}');
    if (!consent.analytics) return;

    try {
        await fetch('/api/cookie-track-keyword', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ keyword }),
        });
    } catch (error) {
        console.error('Error tracking keyword:', error);
    }
};

window.trackProduct = async function(productData) {
    const consent = JSON.parse(localStorage.getItem('cookie_consent') || '{}');
    if (!consent.analytics) return;

    try {
        await fetch('/api/cookie-track-product', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(productData),
        });
    } catch (error) {
        console.error('Error tracking product:', error);
    }
};
</script>

<style>
    [x-cloak] { display: none !important; }
</style>
@endif
