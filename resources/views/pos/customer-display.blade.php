<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Customer Display - {{ $device->device_name }}</title>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Echo for WebSocket -->
    @vite(['resources/js/echo.js'])

    <style>
        /* ============================================
         * POS Customer Display - Modern V3 Styles
         * ใช้ Tailwind เป็นหลัก เพิ่ม custom animations
         * ============================================ */

        /* Background gradient animation */
        .animated-gradient {
            background: linear-gradient(-45deg, #667eea, #764ba2, #6B8DD6, #8E37D7);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Glassmorphism card */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-card-dark {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* 3D Card effect */
        .card-3d {
            transform-style: preserve-3d;
            transition: transform 0.3s ease;
        }

        .card-3d:hover {
            transform: translateZ(10px) rotateX(2deg);
        }

        /* Fade animations */
        .fade-enter {
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-exit {
            animation: fadeOut 0.3s ease-in forwards;
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }

        /* Item slide in */
        .item-slide {
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Pulse glow effect */
        .pulse-glow {
            animation: pulseGlow 2s ease-in-out infinite;
        }

        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 20px rgba(255, 255, 255, 0.3); }
            50% { box-shadow: 0 0 40px rgba(255, 255, 255, 0.6); }
        }

        /* Total amount shine effect */
        .shine-effect {
            position: relative;
            overflow: hidden;
        }

        .shine-effect::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { left: -100%; }
            50%, 100% { left: 100%; }
        }

        /* Floating animation for icons */
        .float-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Advertisement transitions */
        .ad-transition-enter {
            animation: adFadeIn 0.6s ease-out;
        }

        @keyframes adFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Success checkmark animation */
        .success-check {
            animation: successPop 0.5s ease-out;
        }

        @keyframes successPop {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* Responsive text sizing */
        .text-responsive-xl {
            font-size: clamp(1.5rem, 4vw, 3rem);
        }

        .text-responsive-2xl {
            font-size: clamp(2rem, 5vw, 4rem);
        }

        .text-responsive-3xl {
            font-size: clamp(2.5rem, 6vw, 5rem);
        }
    </style>
</head>
<body class="animated-gradient min-h-screen text-white overflow-hidden">
    <!-- Main Container with Alpine.js -->
    <div class="h-screen flex flex-col" x-data="customerDisplay()" x-init="init()">

        <!-- Header Bar -->
        <header class="glass-card border-b border-white/20 px-4 md:px-8 py-4 md:py-6">
            <div class="flex items-center justify-between">
                <!-- Store Info -->
                <div class="flex items-center gap-3 md:gap-6">
                    <template x-if="store.logo">
                        <img :src="store.logo" alt="Logo" class="h-12 md:h-16 w-auto rounded-xl shadow-lg">
                    </template>
                    <template x-if="!store.logo">
                        <div class="w-12 h-12 md:w-16 md:h-16 glass-card rounded-2xl flex items-center justify-center float-animation">
                            <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </template>
                    <div>
                        <h1 class="text-xl md:text-3xl font-bold tracking-tight" x-text="store.name"></h1>
                        <p class="text-sm md:text-base text-white/70" x-text="device.name"></p>
                    </div>
                </div>

                <!-- Clock -->
                <div class="text-right">
                    <div class="text-3xl md:text-5xl font-bold tracking-wider" x-text="currentTime"></div>
                    <div class="text-sm md:text-lg text-white/70" x-text="currentDate"></div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 flex items-center justify-center p-4 md:p-8 overflow-hidden">

            <!-- ========================================
                 MODE: IDLE / WELCOME SCREEN
                 แสดงเมื่อไม่มีรายการ
                 ======================================== -->
            <template x-if="displayMode === 'idle'">
                <div class="text-center fade-enter w-full max-w-4xl">
                    <!-- Welcome Message or Advertisement Rotation -->
                    <template x-if="advertisements.length > 0 && adsEnabled">
                        <!-- Advertisement Display -->
                        <div class="ad-transition-enter">
                            <template x-if="currentAd && currentAd.type === 'image'">
                                <div class="relative rounded-3xl overflow-hidden shadow-2xl mb-8">
                                    <img :src="currentAd.image_url"
                                         :alt="currentAd.title"
                                         class="w-full max-h-[60vh] object-contain mx-auto">
                                    <div x-show="currentAd.title" class="absolute bottom-0 left-0 right-0 glass-card-dark p-4 md:p-6">
                                        <h3 class="text-xl md:text-2xl font-bold" x-text="currentAd.title"></h3>
                                        <p class="text-white/80" x-text="currentAd.description"></p>
                                    </div>
                                </div>
                            </template>

                            <template x-if="currentAd && currentAd.type === 'video'">
                                <div class="rounded-3xl overflow-hidden shadow-2xl mb-8">
                                    <video :src="currentAd.video_url"
                                           autoplay
                                           muted
                                           loop
                                           playsinline
                                           class="w-full max-h-[60vh] object-contain mx-auto">
                                    </video>
                                </div>
                            </template>

                            <template x-if="currentAd && currentAd.type === 'promotion'">
                                <div class="glass-card rounded-3xl p-8 md:p-12 max-w-2xl mx-auto pulse-glow">
                                    <div class="text-6xl md:text-8xl mb-6">🎁</div>
                                    <h3 class="text-responsive-xl font-bold mb-4" x-text="currentAd.title"></h3>
                                    <template x-if="currentAd.discount_percentage">
                                        <div class="text-responsive-3xl font-black text-yellow-300 mb-4">
                                            <span x-text="currentAd.discount_percentage + '%'"></span> OFF
                                        </div>
                                    </template>
                                    <template x-if="currentAd.discount_amount && !currentAd.discount_percentage">
                                        <div class="text-responsive-3xl font-black text-yellow-300 mb-4">
                                            ลด ฿<span x-text="formatNumber(currentAd.discount_amount)"></span>
                                        </div>
                                    </template>
                                    <template x-if="currentAd.promotion_code">
                                        <div class="inline-block glass-card px-6 py-3 rounded-xl mt-4">
                                            <span class="text-white/70">รหัส: </span>
                                            <span class="font-mono font-bold text-xl" x-text="currentAd.promotion_code"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="currentAd && currentAd.type === 'html'">
                                <div class="glass-card rounded-3xl p-8 max-w-4xl mx-auto"
                                     x-html="currentAd.html_content">
                                </div>
                            </template>

                            <!-- Ad Progress Indicator -->
                            <div class="flex justify-center gap-2 mt-6" x-show="advertisements.length > 1">
                                <template x-for="(ad, idx) in advertisements" :key="ad.id">
                                    <div class="h-2 rounded-full transition-all duration-300"
                                         :class="idx === currentAdIndex ? 'w-8 bg-white' : 'w-2 bg-white/40'">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Default Welcome (no ads) -->
                    <template x-if="advertisements.length === 0 || !adsEnabled">
                        <div>
                            <div class="w-24 h-24 md:w-32 md:h-32 glass-card rounded-full flex items-center justify-center mx-auto mb-6 md:mb-8 pulse-glow float-animation">
                                <svg class="w-12 h-12 md:w-16 md:h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-responsive-2xl font-bold mb-4">ยินดีต้อนรับ</h2>
                            <p class="text-xl md:text-2xl text-white/80">กรุณารอสักครู่ พนักงานกำลังให้บริการ</p>

                            <template x-if="settings.customer_display_message">
                                <div class="mt-8 md:mt-12 glass-card rounded-2xl p-6 md:p-8 max-w-2xl mx-auto">
                                    <p class="text-lg md:text-xl" x-text="settings.customer_display_message"></p>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <!-- ========================================
                 MODE: CART
                 แสดงรายการสินค้าในตะกร้า
                 ======================================== -->
            <template x-if="displayMode === 'cart'">
                <div class="w-full max-w-5xl fade-enter">
                    <!-- Items List -->
                    <div class="glass-card rounded-3xl p-6 md:p-8 mb-6 max-h-[45vh] overflow-y-auto custom-scrollbar">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl md:text-2xl font-bold flex items-center gap-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                รายการสินค้า
                            </h3>
                            <span class="glass-card px-4 py-2 rounded-xl text-sm">
                                <span x-text="items.length"></span> รายการ
                            </span>
                        </div>

                        <div class="space-y-3 md:space-y-4">
                            <template x-for="(item, index) in items" :key="index">
                                <div class="glass-card-dark rounded-2xl p-4 md:p-6 flex items-center justify-between item-slide card-3d">
                                    <div class="flex items-center gap-4 md:gap-6 flex-1 min-w-0">
                                        <div class="text-2xl md:text-3xl font-bold text-white/60 w-12 text-center flex-shrink-0"
                                             x-text="item.quantity + 'x'"></div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-lg md:text-2xl font-bold truncate" x-text="item.name"></h4>
                                            <div class="flex items-center gap-3 mt-1">
                                                <p class="text-sm md:text-lg text-white/70"
                                                   x-text="'฿' + formatNumber(item.price) + ' / ชิ้น'"></p>
                                                <!-- PV/Points Badge -->
                                                <template x-if="item.pv > 0">
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-500/30 text-cyan-300 border border-cyan-400/30">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                        <span x-text="item.pv + ' PV'"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-4">
                                        <div class="text-xl md:text-3xl font-bold"
                                             x-text="'฿' + formatNumber(item.quantity * item.price)"></div>
                                        <!-- Total PV for this item -->
                                        <template x-if="item.pv > 0">
                                            <div class="text-sm text-cyan-400 mt-1"
                                                 x-text="'+' + (item.pv * item.quantity) + ' PV'"></div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Total Section -->
                    <div class="glass-card rounded-3xl p-6 md:p-8">
                        <div class="space-y-3 md:space-y-4">
                            <!-- Subtotal -->
                            <div class="flex items-center justify-between text-lg md:text-2xl">
                                <span class="text-white/80">ยอดรวม</span>
                                <span class="font-bold" x-text="'฿' + formatNumber(subtotal)"></span>
                            </div>

                            <!-- Discount -->
                            <div x-show="discount > 0" class="flex items-center justify-between text-lg md:text-2xl text-emerald-400">
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    ส่วนลด
                                </span>
                                <span class="font-bold" x-text="'-฿' + formatNumber(discount)"></span>
                            </div>

                            <!-- Tax -->
                            <div x-show="settings.tax_enabled && tax > 0" class="flex items-center justify-between text-base md:text-xl text-white/70">
                                <span x-text="'ภาษี (' + settings.tax_percentage + '%)'"></span>
                                <span x-text="'฿' + formatNumber(tax)"></span>
                            </div>

                            <!-- Service Charge -->
                            <div x-show="settings.service_charge_enabled && serviceCharge > 0" class="flex items-center justify-between text-base md:text-xl text-white/70">
                                <span x-text="'ค่าบริการ (' + settings.service_charge_percentage + '%)'"></span>
                                <span x-text="'฿' + formatNumber(serviceCharge)"></span>
                            </div>

                            <!-- Grand Total -->
                            <div class="pt-4 md:pt-6 mt-4 md:mt-6 border-t-2 border-white/30">
                                <div class="flex items-center justify-between">
                                    <span class="text-2xl md:text-3xl font-bold">รวมทั้งสิ้น</span>
                                    <div class="shine-effect rounded-xl px-6 py-2 glass-card">
                                        <span class="text-3xl md:text-5xl font-black text-yellow-300"
                                              x-text="'฿' + formatNumber(total)"></span>
                                    </div>
                                </div>

                                <!-- Total PV/Points earned -->
                                <div x-show="totalPv > 0" class="mt-4 flex items-center justify-center gap-4">
                                    <div class="glass-card-dark rounded-xl px-4 py-2 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <span class="text-cyan-400 font-bold" x-text="'รับ ' + totalPv + ' PV'"></span>
                                    </div>
                                    <div x-show="totalPoints > 0" class="glass-card-dark rounded-xl px-4 py-2 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm2.5 3a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm6.207.293a1 1 0 00-1.414 0l-6 6a1 1 0 101.414 1.414l6-6a1 1 0 000-1.414zM12.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-amber-400 font-bold" x-text="'รับ ' + totalPoints + ' คะแนน'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- ========================================
                 MODE: COMPLETED / THANK YOU
                 แสดงเมื่อทำรายการเสร็จ
                 ======================================== -->
            <template x-if="displayMode === 'completed'">
                <div class="text-center fade-enter w-full max-w-3xl">
                    <!-- Success Animation -->
                    <div class="w-32 h-32 md:w-40 md:h-40 glass-card rounded-full flex items-center justify-center mx-auto mb-8 success-check pulse-glow">
                        <svg class="w-16 h-16 md:w-20 md:h-20 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>

                    <h2 class="text-responsive-2xl font-bold mb-4">ขอบคุณที่ใช้บริการ</h2>

                    <!-- Transaction Summary -->
                    <div class="glass-card rounded-3xl p-6 md:p-8 mb-8">
                        <div class="grid grid-cols-2 gap-4 md:gap-6 text-left">
                            <div>
                                <p class="text-white/60 text-sm md:text-base">ยอดรวม</p>
                                <p class="text-xl md:text-2xl font-bold" x-text="'฿' + formatNumber(transaction.totalAmount)"></p>
                            </div>
                            <div>
                                <p class="text-white/60 text-sm md:text-base">รับเงิน</p>
                                <p class="text-xl md:text-2xl font-bold" x-text="'฿' + formatNumber(transaction.amountPaid)"></p>
                            </div>
                            <div x-show="transaction.changeAmount > 0" class="col-span-2">
                                <p class="text-white/60 text-sm md:text-base">เงินทอน</p>
                                <p class="text-3xl md:text-4xl font-black text-yellow-300" x-text="'฿' + formatNumber(transaction.changeAmount)"></p>
                            </div>
                        </div>

                        <!-- PV/Points Earned Display -->
                        <div x-show="transaction.totalPv > 0 || transaction.totalPoints > 0" class="mt-6 pt-6 border-t border-white/20">
                            <p class="text-white/60 text-sm mb-3">คุณได้รับ</p>
                            <div class="flex items-center justify-center gap-4 flex-wrap">
                                <div x-show="transaction.totalPv > 0" class="glass-card-dark rounded-xl px-5 py-3 flex items-center gap-2 float-animation">
                                    <svg class="w-6 h-6 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    <span class="text-xl font-bold text-cyan-400" x-text="'+' + transaction.totalPv + ' PV'"></span>
                                </div>
                                <div x-show="transaction.totalPoints > 0" class="glass-card-dark rounded-xl px-5 py-3 flex items-center gap-2 float-animation" style="animation-delay: 0.2s;">
                                    <svg class="w-6 h-6 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm2.5 3a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm6.207.293a1 1 0 00-1.414 0l-6 6a1 1 0 101.414 1.414l6-6a1 1 0 000-1.414zM12.5 10a1.5 1.5 0 100 3 1.5 1.5 0 000-3z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-xl font-bold text-amber-400" x-text="'+' + transaction.totalPoints + ' คะแนน'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <template x-if="transaction.customerName">
                        <p class="text-xl md:text-2xl text-white/80">
                            ขอบคุณคุณ <span class="font-bold" x-text="transaction.customerName"></span>
                        </p>
                    </template>

                    <p class="text-lg text-white/60 mt-4">โปรดรับใบเสร็จจากพนักงาน</p>
                </div>
            </template>

        </main>

        <!-- Footer Bar -->
        <footer class="glass-card border-t border-white/20 px-4 md:px-8 py-3 md:py-4">
            <div class="flex flex-wrap items-center justify-between text-xs md:text-sm text-white/70 gap-2">
                <div class="flex items-center gap-2" x-show="store.phone">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    <span x-text="store.phone"></span>
                </div>
                <div class="flex items-center gap-2" x-show="store.address">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span class="truncate max-w-xs" x-text="store.address"></span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"></path>
                    </svg>
                    <span>ขอบคุณที่ใช้บริการ</span>
                </div>

                <!-- Connection Status -->
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full"
                         :class="isConnected ? 'bg-emerald-400' : 'bg-red-400'"></div>
                    <span x-text="isConnected ? 'เชื่อมต่อแล้ว' : 'ไม่ได้เชื่อมต่อ'"></span>
                </div>
            </div>
        </footer>
    </div>

    <script>
        /**
         * Alpine.js Customer Display Component
         *
         * ระบบแสดงผลหน้าจอลูกค้าสำหรับ POS
         * รองรับ WebSocket real-time และ Advertisement rotation
         */
        function customerDisplay() {
            return {
                // ========================================
                // State
                // ========================================

                // Device & Store Info
                deviceCode: '{{ $device->device_code }}',
                device: {
                    id: {{ $device->id }},
                    name: '{{ $device->device_name }}',
                    code: '{{ $device->device_code }}',
                },
                store: {
                    id: {{ $device->store->id ?? 'null' }},
                    name: '{{ $device->store->name ?? "ร้านค้า" }}',
                    logo: '{{ $device->store->logo ? asset($device->store->logo) : "" }}',
                    phone: '{{ $device->store->phone ?? "" }}',
                    address: '{{ $device->store->address ?? "" }}',
                },

                // Settings
                settings: {
                    customer_display_message: '{{ $settings->customer_display_message ?? "" }}',
                    customer_display_ads_enabled: {{ ($settings->customer_display_ads_enabled ?? false) ? 'true' : 'false' }},
                    ad_rotation_seconds: {{ $settings->ad_rotation_seconds ?? 10 }},
                    tax_enabled: {{ ($settings->tax_enabled ?? false) ? 'true' : 'false' }},
                    tax_percentage: {{ $settings->tax_percentage ?? 7 }},
                    tax_inclusive: {{ ($settings->tax_inclusive ?? true) ? 'true' : 'false' }},
                    service_charge_enabled: {{ ($settings->service_charge_enabled ?? false) ? 'true' : 'false' }},
                    service_charge_percentage: {{ $settings->service_charge_percentage ?? 0 }},
                },

                // Display Mode: idle, cart, payment, completed
                displayMode: 'idle',

                // Cart Data
                items: [],
                subtotal: 0,
                discount: 0,
                tax: 0,
                serviceCharge: 0,
                total: 0,
                totalPv: 0,
                totalPoints: 0,

                // Transaction Data (for completed screen)
                transaction: {
                    id: null,
                    totalAmount: 0,
                    amountPaid: 0,
                    changeAmount: 0,
                    paymentMethod: '',
                    totalItems: 0,
                    customerName: null,
                    totalPv: 0,
                    totalPoints: 0,
                },

                // Advertisement Rotation
                advertisements: [],
                currentAdIndex: 0,
                currentAd: null,
                adsEnabled: {{ ($settings->customer_display_ads_enabled ?? false) ? 'true' : 'false' }},
                adRotationInterval: null,

                // Clock
                currentTime: '',
                currentDate: '',

                // Connection Status
                isConnected: false,

                // Idle timeout
                idleTimeout: null,
                idleDelay: 30000, // 30 วินาที ก่อนกลับไป idle

                // ========================================
                // Initialization
                // ========================================

                async init() {
                    // เริ่มนาฬิกา
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);

                    // โหลดโฆษณา
                    await this.loadAdvertisements();

                    // เริ่ม rotation โฆษณา
                    this.startAdRotation();

                    // ตั้งค่า WebSocket listeners
                    this.setupWebSocket();

                    // ตั้งค่า localStorage listener (fallback)
                    this.setupLocalStorageListener();

                    // ส่ง heartbeat
                    this.sendHeartbeat();
                    setInterval(() => this.sendHeartbeat(), 30000);

                    console.log('🖥️ Customer Display initialized for device:', this.deviceCode);
                },

                // ========================================
                // Clock
                // ========================================

                updateClock() {
                    const now = new Date();
                    this.currentTime = now.toLocaleTimeString('th-TH', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                    this.currentDate = now.toLocaleDateString('th-TH', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                },

                // ========================================
                // WebSocket Setup
                // ========================================

                setupWebSocket() {
                    // ตรวจสอบว่า Echo พร้อมใช้งานหรือไม่
                    if (typeof window.Echo === 'undefined') {
                        console.warn('⚠️ Laravel Echo not available, using localStorage fallback');
                        return;
                    }

                    try {
                        // Subscribe to public channel
                        const channel = window.Echo.channel(`pos-display.${this.deviceCode}`);

                        // Listen for cart updates
                        channel.listen('.cart.updated', (data) => {
                            console.log('📥 Cart updated:', data);
                            this.handleCartUpdate(data);
                        });

                        // Listen for transaction completed
                        channel.listen('.transaction.completed', (data) => {
                            console.log('✅ Transaction completed:', data);
                            this.handleTransactionCompleted(data);
                        });

                        // Listen for display clear
                        channel.listen('.display.clear', (data) => {
                            console.log('🧹 Display clear:', data);
                            this.handleDisplayClear(data);
                        });

                        this.isConnected = true;
                        console.log('✅ WebSocket connected to channel:', `pos-display.${this.deviceCode}`);

                    } catch (error) {
                        console.error('❌ WebSocket connection failed:', error);
                        this.isConnected = false;
                    }
                },

                // ========================================
                // LocalStorage Fallback
                // ========================================

                setupLocalStorageListener() {
                    window.addEventListener('storage', (e) => {
                        if (e.key === 'pos_customer_display') {
                            try {
                                const data = JSON.parse(e.newValue);
                                if (data.action === 'update') {
                                    this.handleCartUpdate(data);
                                } else if (data.action === 'clear') {
                                    this.handleDisplayClear(data);
                                } else if (data.action === 'completed') {
                                    this.handleTransactionCompleted(data);
                                }
                            } catch (error) {
                                console.error('❌ Error parsing localStorage data:', error);
                            }
                        }
                    });
                },

                // ========================================
                // Event Handlers
                // ========================================

                handleCartUpdate(data) {
                    this.items = data.items || [];
                    this.subtotal = parseFloat(data.subtotal) || 0;
                    this.discount = parseFloat(data.discount) || 0;
                    this.tax = parseFloat(data.tax) || 0;
                    this.serviceCharge = parseFloat(data.serviceCharge) || 0;
                    this.total = parseFloat(data.total) || 0;

                    // คำนวณ PV และ Points รวม
                    this.totalPv = parseFloat(data.totalPv) || this.items.reduce((sum, item) => {
                        return sum + ((parseFloat(item.pv) || 0) * (item.quantity || 1));
                    }, 0);
                    this.totalPoints = parseFloat(data.totalPoints) || this.items.reduce((sum, item) => {
                        return sum + ((parseFloat(item.points) || 0) * (item.quantity || 1));
                    }, 0);

                    // เปลี่ยนเป็น cart mode ถ้ามีสินค้า
                    if (this.items.length > 0) {
                        this.displayMode = 'cart';
                        this.resetIdleTimeout();
                        this.stopAdRotation();
                    } else {
                        this.goToIdle();
                    }
                },

                handleTransactionCompleted(data) {
                    this.transaction = {
                        id: data.transactionId,
                        totalAmount: parseFloat(data.totalAmount) || 0,
                        amountPaid: parseFloat(data.amountPaid) || 0,
                        changeAmount: parseFloat(data.changeAmount) || 0,
                        paymentMethod: data.paymentMethod,
                        totalItems: data.totalItems,
                        customerName: data.customerName,
                        totalPv: parseFloat(data.totalPv) || this.totalPv || 0,
                        totalPoints: parseFloat(data.totalPoints) || this.totalPoints || 0,
                    };

                    this.displayMode = 'completed';

                    // กลับไป idle หลัง 10 วินาที
                    setTimeout(() => {
                        this.goToIdle();
                    }, 10000);
                },

                handleDisplayClear(data) {
                    if (data.showThankYou) {
                        this.displayMode = 'completed';
                        setTimeout(() => {
                            this.goToIdle();
                        }, (data.thankYouDuration || 5) * 1000);
                    } else {
                        this.goToIdle();
                    }
                },

                goToIdle() {
                    this.displayMode = 'idle';
                    this.items = [];
                    this.subtotal = 0;
                    this.discount = 0;
                    this.tax = 0;
                    this.serviceCharge = 0;
                    this.total = 0;
                    this.totalPv = 0;
                    this.totalPoints = 0;
                    this.startAdRotation();
                },

                resetIdleTimeout() {
                    if (this.idleTimeout) {
                        clearTimeout(this.idleTimeout);
                    }
                    this.idleTimeout = setTimeout(() => {
                        if (this.displayMode === 'cart' && this.items.length === 0) {
                            this.goToIdle();
                        }
                    }, this.idleDelay);
                },

                // ========================================
                // Advertisement Rotation
                // ========================================

                async loadAdvertisements() {
                    try {
                        const response = await fetch(`/pos/api/display/advertisements?device_code=${this.deviceCode}`);
                        const result = await response.json();

                        if (result.success) {
                            this.advertisements = result.data || [];
                            if (this.advertisements.length > 0) {
                                this.currentAd = this.advertisements[0];
                            }
                            console.log('📢 Loaded', this.advertisements.length, 'advertisements');
                        }
                    } catch (error) {
                        console.error('❌ Failed to load advertisements:', error);
                    }
                },

                startAdRotation() {
                    if (!this.adsEnabled || this.advertisements.length === 0) return;

                    // หยุด interval เดิมก่อน
                    this.stopAdRotation();

                    const rotationSeconds = this.settings.ad_rotation_seconds || 10;

                    this.adRotationInterval = setInterval(() => {
                        // เปลี่ยนโฆษณาเฉพาะตอนอยู่ใน idle mode
                        if (this.displayMode === 'idle') {
                            this.nextAd();
                        }
                    }, rotationSeconds * 1000);
                },

                stopAdRotation() {
                    if (this.adRotationInterval) {
                        clearInterval(this.adRotationInterval);
                        this.adRotationInterval = null;
                    }
                },

                nextAd() {
                    if (this.advertisements.length === 0) return;

                    this.currentAdIndex = (this.currentAdIndex + 1) % this.advertisements.length;
                    this.currentAd = this.advertisements[this.currentAdIndex];

                    // บันทึก view count
                    this.recordAdView(this.currentAd.id);
                },

                async recordAdView(adId) {
                    try {
                        await fetch(`/pos/api/display/advertisements/${adId}/view`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                    } catch (error) {
                        // Silent fail for analytics
                    }
                },

                // ========================================
                // Heartbeat
                // ========================================

                async sendHeartbeat() {
                    try {
                        await fetch('/pos/api/display/heartbeat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ device_code: this.deviceCode })
                        });
                    } catch (error) {
                        // Silent fail
                    }
                },

                // ========================================
                // Utilities
                // ========================================

                formatNumber(num) {
                    return parseFloat(num).toLocaleString('th-TH', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            };
        }
    </script>
</body>
</html>
