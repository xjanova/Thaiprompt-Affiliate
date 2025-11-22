{{--
    TPIX Pricing Calculator Component

    แสดงราคาค่าบริการแบบ Real-time พร้อม Breakdown

    Props:
    - config (TpixConfiguration): Configuration object
    - features (array): Selected features
    - showBreakdown (bool): แสดงรายละเอียดการคำนวณ
--}}

@props([
    'config' => null,
    'features' => [],
    'showBreakdown' => true,
])

@php
    $pricingService = app(\App\Services\TPIX\TpixPricingService::class);
    $pricing = $config ? $pricingService->calculateTotalPrice($config) : null;
@endphp

<div x-data="pricingCalculator({{ json_encode($pricing) }})" class="space-y-4">

    {{-- Total Price Card --}}
    <div class="p-6 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl border-2 border-purple-300 dark:border-purple-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"></path>
                </svg>
                ค่าบริการรวม
            </h3>
            <button @click="showBreakdown = !showBreakdown" class="text-purple-600 dark:text-purple-400 hover:underline text-sm font-medium">
                <span x-text="showBreakdown ? 'ซ่อนรายละเอียด' : 'แสดงรายละเอียด'"></span>
            </button>
        </div>

        {{-- Total Amount --}}
        <div class="flex items-end justify-between">
            <div>
                <div class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600">
                    <span x-text="formatPrice(total)"></span>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    ≈ $<span x-text="formatUSD(total)"></span> USD
                </div>
            </div>

            {{-- Discount Badge --}}
            <div x-show="discount > 0" class="px-3 py-1 bg-green-500 text-white rounded-full text-sm font-semibold">
                -<span x-text="formatPrice(discount)"></span> ส่วนลด
            </div>
        </div>
    </div>

    {{-- Breakdown Details --}}
    <div x-show="showBreakdown" x-collapse x-cloak>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 space-y-4">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                </svg>
                รายละเอียดการคำนวณ
            </h4>

            {{-- Base Deployment --}}
            <template x-if="breakdown.base_deployment">
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900 dark:text-white" x-text="breakdown.base_deployment.label"></div>
                            <div x-show="breakdown.base_deployment.included" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span>รวม: </span>
                                <template x-for="item in breakdown.base_deployment.included" :key="item">
                                    <span class="mr-2" x-text="item"></span>
                                </template>
                            </div>
                        </div>
                        <div class="text-lg font-bold text-purple-600" x-text="formatPrice(breakdown.base_deployment.price)"></div>
                    </div>
                </div>
            </template>

            {{-- Contract Features --}}
            <template x-if="breakdown.contract_features">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                    <div class="flex justify-between items-start mb-3">
                        <div class="font-semibold text-gray-900 dark:text-white" x-text="breakdown.contract_features.label"></div>
                        <div class="text-lg font-bold text-blue-600" x-text="formatPrice(breakdown.contract_features.price)"></div>
                    </div>
                    <div class="space-y-1">
                        <template x-for="(price, feature) in breakdown.contract_features.items" :key="feature">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400 capitalize" x-text="feature.replace('_', ' ')"></span>
                                <span class="text-gray-900 dark:text-white font-medium" x-text="formatPrice(price)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Access Control --}}
            <template x-if="breakdown.access_control">
                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                    <div class="flex justify-between items-center">
                        <div class="font-semibold text-gray-900 dark:text-white" x-text="breakdown.access_control.label"></div>
                        <div class="text-lg font-bold text-green-600" x-text="formatPrice(breakdown.access_control.price)"></div>
                    </div>
                </div>
            </template>

            {{-- Upgradeable --}}
            <template x-if="breakdown.upgradeable">
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl">
                    <div class="flex justify-between items-center">
                        <div class="font-semibold text-gray-900 dark:text-white" x-text="breakdown.upgradeable.label"></div>
                        <div class="text-lg font-bold text-yellow-600" x-text="formatPrice(breakdown.upgradeable.price)"></div>
                    </div>
                </div>
            </template>

            {{-- DEX Integration --}}
            <template x-if="breakdown.dex_integration">
                <div class="p-4 bg-pink-50 dark:bg-pink-900/20 rounded-xl">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900 dark:text-white" x-text="breakdown.dex_integration.label"></div>
                            <div x-show="breakdown.dex_integration.included" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <template x-for="item in breakdown.dex_integration.included" :key="item">
                                    <span class="mr-2" x-text="item"></span>
                                </template>
                            </div>
                        </div>
                        <div class="text-lg font-bold text-pink-600" x-text="formatPrice(breakdown.dex_integration.price)"></div>
                    </div>
                </div>
            </template>

            {{-- Premium Services --}}
            <template x-if="breakdown.premium_services">
                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                    <div class="flex justify-between items-start mb-3">
                        <div class="font-semibold text-gray-900 dark:text-white" x-text="breakdown.premium_services.label"></div>
                        <div class="text-lg font-bold text-purple-600" x-text="formatPrice(breakdown.premium_services.price)"></div>
                    </div>
                    <div class="space-y-1">
                        <template x-for="(price, service) in breakdown.premium_services.items" :key="service">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400 capitalize" x-text="service.replace('_', ' ')"></span>
                                <span class="text-gray-900 dark:text-white font-medium" x-text="formatPrice(price)"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Subtotal --}}
            <div class="pt-4 border-t border-gray-200 dark:border-gray-600">
                <div class="flex justify-between items-center text-gray-700 dark:text-gray-300">
                    <span class="font-medium">Subtotal</span>
                    <span class="font-bold text-lg" x-text="formatPrice(subtotal)"></span>
                </div>
            </div>

            {{-- Discount --}}
            <template x-if="discount > 0">
                <div class="flex justify-between items-center text-green-600">
                    <div class="flex items-center gap-2">
                        <span class="font-medium">ส่วนลด</span>
                        <span class="text-sm" x-text="'(' + breakdown.discount.percentage + '%)'"></span>
                    </div>
                    <span class="font-bold text-lg">-<span x-text="formatPrice(discount)"></span></span>
                </div>
            </template>

            {{-- Total --}}
            <div class="pt-4 border-t-2 border-purple-300 dark:border-purple-700">
                <div class="flex justify-between items-center">
                    <span class="text-xl font-bold text-gray-900 dark:text-white">ยอดรวมสุทธิ</span>
                    <span class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600" x-text="formatPrice(total)"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Info --}}
    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-r-xl">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <div class="text-sm text-blue-900 dark:text-blue-100">
                <p class="font-semibold mb-1">ข้อมูลการชำระเงิน</p>
                <ul class="space-y-1 text-blue-800 dark:text-blue-200">
                    <li>• ชำระด้วย TPIX tokens ผ่าน Web3 Wallet</li>
                    <li>• ค่า Gas Fee แยกจากค่าบริการ</li>
                    <li>• คืนเงิน 80% หาก Deploy ล้มเหลว</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Pricing Calculator Alpine Component
 */
function pricingCalculator(initialData) {
    return {
        // State
        breakdown: initialData?.breakdown || {},
        subtotal: initialData?.subtotal || 0,
        discount: initialData?.discount || 0,
        total: initialData?.total || 0,
        showBreakdown: true,

        // Constants
        TPIX_USD_RATE: 0.5, // Mock: 1 TPIX = $0.5

        /**
         * Format price
         */
        formatPrice(amount) {
            return new Intl.NumberFormat('th-TH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount) + ' TPIX';
        },

        /**
         * Format USD equivalent
         */
        formatUSD(tpixAmount) {
            const usdAmount = tpixAmount * this.TPIX_USD_RATE;
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(usdAmount);
        },

        /**
         * Update pricing dynamically
         */
        updatePricing(newData) {
            this.breakdown = newData.breakdown;
            this.subtotal = newData.subtotal;
            this.discount = newData.discount;
            this.total = newData.total;
        }
    };
}
</script>
