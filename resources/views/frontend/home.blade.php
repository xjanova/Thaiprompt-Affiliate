{{--
/**
 * หน้าแรก Thaiprompt - Homepage V3
 *
 * แนวคิด: Storytelling through 3 Doors (3 ประตู)
 * - ประตูที่ 1: นักลงทุน (Investors)
 * - ประตูที่ 2: นักพัฒนา (Developers)
 * - ประตูที่ 3: นักการตลาด & ชุมชน (Community)
 *
 * V3 Standards:
 * - Tailwind CSS (pure utility-first)
 * - Alpine.js (lightweight interactivity)
 * - GSAP (premium animations)
 * - Glassmorphism + 3D effects
 * - Dark/Light mode support
 * - Mobile-first responsive
 *
 * @version 3.0.0
 * @author Thaiprompt Team
 */
--}}

@extends('layouts.app')

@section('title', 'Thaiprompt - เปลี่ยนโอกาสให้เป็นผลกำไร')

@push('styles')
<style>
    /**
     * Custom animations สำหรับหน้า homepage
     */
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }

    @keyframes glow {
        0%, 100% { box-shadow: 0 0 20px rgba(139, 92, 246, 0.5); }
        50% { box-shadow: 0 0 40px rgba(236, 72, 153, 0.8), 0 0 60px rgba(139, 92, 246, 0.6); }
    }

    @keyframes slideInFromLeft {
        from {
            opacity: 0;
            transform: translateX(-100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideInFromRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .animate-float {
        animation: float 6s ease-in-out infinite;
    }

    .animate-glow {
        animation: glow 3s ease-in-out infinite;
    }

    .perspective-1000 {
        perspective: 1000px;
    }

    .rotate-y-3 {
        transform: rotateY(3deg);
    }

    /* Glassmorphism utilities */
    .glass-card {
        @apply backdrop-blur-xl bg-white/10 dark:bg-gray-900/10 border border-white/20 dark:border-gray-700/30;
    }

    /* 3D Card hover effect */
    .card-3d:hover {
        transform: translateY(-10px) rotateX(5deg) rotateY(5deg);
    }
</style>
@endpush

@section('content')

{{-- Alpine.js Data Component --}}
<div x-data="homepageManager()" x-init="init()" class="overflow-hidden">

    {{-- ========================================
        HERO SECTION - การเล่าเรื่องเริ่มต้น
    ======================================== --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden
                    bg-gradient-to-br from-purple-900 via-pink-800 to-orange-700
                    dark:from-purple-950 dark:via-pink-950 dark:to-orange-950">

        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10"
             style="background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.2) 1px, transparent 1px);
                    background-size: 40px 40px;">
        </div>

        {{-- Animated Gradient Orbs --}}
        <div class="absolute top-0 left-0 w-96 h-96 bg-purple-500/30 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-pink-500/30 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 w-64 h-64 bg-orange-500/30 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>

        {{-- Hero Content --}}
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">

            {{-- Logo Animation --}}
            <div class="mb-12 animate-float">
                <div class="inline-block p-8 glass-card rounded-3xl shadow-2xl transform hover:scale-110 transition-all duration-500">
                    <div class="w-24 h-24 md:w-32 md:h-32 bg-gradient-to-br from-purple-400 to-pink-400 rounded-2xl flex items-center justify-center">
                        <span class="text-5xl md:text-6xl">🌟</span>
                    </div>
                </div>
            </div>

            {{-- Main Headline - Storytelling --}}
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-black mb-6 leading-tight"
                x-data="{ visible: false }"
                x-intersect="visible = true"
                x-show="visible"
                x-transition:enter="transition ease-out duration-1000"
                x-transition:enter-start="opacity-0 transform translate-y-12"
                x-transition:enter-end="opacity-100 transform translate-y-0">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300 drop-shadow-lg">
                    เรา<span class="underline decoration-wavy decoration-pink-400">ไม่ได้</span>แค่มีเครื่องมือ
                </span>
                <br>
                <span class="text-white drop-shadow-2xl">
                    เราแก้<span class="text-yellow-300">ปัญหา</span>ที่แท้จริง
                </span>
            </h1>

            {{-- Sub Headline --}}
            <p class="text-xl md:text-2xl lg:text-3xl mb-12 text-white/90 max-w-4xl mx-auto leading-relaxed"
               x-data="{ visible: false }"
               x-intersect="visible = true"
               x-show="visible"
               x-transition:enter="transition ease-out duration-1000 delay-300"
               x-transition:enter-start="opacity-0"
               x-transition:enter-end="opacity-100">
                เปลี่ยน<span class="font-bold text-yellow-300">โอกาส</span>ให้เป็น<span class="font-bold text-pink-300">ผลกำไร</span><br>
                เชื่อมต่อ<span class="font-bold text-purple-300">ชุมชน</span>สร้าง<span class="font-bold text-orange-300">อนาคต</span>ที่ยั่งยืน
            </p>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-16"
                 x-data="{ visible: false }"
                 x-intersect="visible = true"
                 x-show="visible"
                 x-transition:enter="transition ease-out duration-1000 delay-500"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100">

                {{-- Primary CTA --}}
                <button @click="scrollToSection('doors')"
                        class="group relative px-8 py-4 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500
                               text-white font-bold text-lg rounded-xl
                               shadow-lg hover:shadow-2xl
                               transform hover:scale-105
                               transition-all duration-300 overflow-hidden">
                    {{-- Shine Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent
                                -translate-x-full group-hover:translate-x-full
                                transition-transform duration-1000"></div>

                    <span class="relative z-10 flex items-center gap-2">
                        <span>เริ่มต้นเลย</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </span>
                </button>

                {{-- Secondary CTA --}}
                <button @click="scrollToSection('doors')"
                        class="px-8 py-4 glass-card text-white font-semibold text-lg rounded-xl
                               hover:bg-white/20 dark:hover:bg-gray-800/30
                               transform hover:scale-105
                               transition-all duration-300">
                    เรียนรู้เพิ่มเติม
                </button>
            </div>

            {{-- Scroll Indicator --}}
            <div class="animate-bounce">
                <svg class="w-8 h-8 mx-auto text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </div>
        </div>
    </section>

    {{-- ========================================
        3 DOORS SECTION - 3 ประตูสู่โอกาส
    ======================================== --}}
    <section id="doors" class="py-24 bg-gray-50 dark:bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Section Header --}}
            <div class="text-center mb-20"
                 x-data="{ visible: false }"
                 x-intersect="visible = true"
                 x-show="visible"
                 x-transition:enter="transition ease-out duration-1000"
                 x-transition:enter-start="opacity-0 transform translate-y-12"
                 x-transition:enter-end="opacity-100 transform translate-y-0">

                <h2 class="text-4xl md:text-5xl lg:text-6xl font-black mb-6
                           text-transparent bg-clip-text bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600
                           dark:from-purple-400 dark:via-pink-400 dark:to-orange-400">
                    3 ประตูสู่โอกาสแห่งความสำเร็จ
                </h2>

                <p class="text-xl md:text-2xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                    เลือกเส้นทางของคุณ แต่ละประตูเปิดสู่โลกแห่งโอกาสที่ไม่มีขีดจำกัด
                </p>
            </div>

            {{-- ========================================
                ประตูที่ 1: นักลงทุน (INVESTORS DOOR)
            ======================================== --}}
            <div class="mb-32"
                 x-data="{ visible: false, hovered: false }"
                 x-intersect="visible = true"
                 @mouseenter="hovered = true"
                 @mouseleave="hovered = false">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

                    {{-- Left: Content --}}
                    <div x-show="visible"
                         x-transition:enter="transition ease-out duration-1000"
                         x-transition:enter-start="opacity-0 transform -translate-x-20"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         class="order-2 lg:order-1">

                        {{-- Door Number --}}
                        <div class="inline-flex items-center gap-3 mb-6 px-4 py-2 glass-card rounded-full">
                            <span class="text-3xl">🚪</span>
                            <span class="text-sm font-bold text-purple-600 dark:text-purple-400">ประตูที่ 1</span>
                        </div>

                        <h3 class="text-4xl md:text-5xl font-black mb-6
                                   text-transparent bg-clip-text bg-gradient-to-r from-purple-600 to-pink-600
                                   dark:from-purple-400 dark:to-pink-400">
                            สำหรับนักลงทุน:<br>
                            เงินทำงานแทนคุณ
                        </h3>

                        <div class="space-y-4 text-lg text-gray-700 dark:text-gray-300 mb-8">
                            <p class="leading-relaxed">
                                <span class="font-bold text-purple-600 dark:text-purple-400">คุณเคยรู้สึกไหม</span>
                                ว่าการลงทุนมันซับซ้อนเกินไป? ไม่รู้จะเริ่มต้นอย่างไร?
                            </p>

                            <p class="leading-relaxed">
                                <span class="font-bold text-pink-600 dark:text-pink-400">เราเข้าใจ</span>
                                เพราะเราเคยเป็นเช่นนั้น นั่นคือเหตุผลที่เราสร้างระบบที่
                                <span class="underline decoration-wavy decoration-purple-400">ใครก็ลงทุนได้</span>
                            </p>

                            <div class="bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30
                                        border-l-4 border-purple-500 dark:border-purple-400
                                        rounded-r-xl p-6 my-6">
                                <p class="font-semibold text-gray-800 dark:text-gray-200 mb-2">💡 ทำไมต้องเป็นเรา?</p>
                                <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 dark:text-purple-400 mt-1">✓</span>
                                        <span><strong>เริ่มต้นง่าย</strong> - ไม่ต้องมีประสบการณ์</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 dark:text-purple-400 mt-1">✓</span>
                                        <span><strong>รายได้ต่อเนื่อง</strong> - ระบบทำงานอัตโนมัติ 24/7</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 dark:text-purple-400 mt-1">✓</span>
                                        <span><strong>โปร่งใส</strong> - ติดตามผลตอบแทนแบบ real-time</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-purple-600 dark:text-purple-400 mt-1">✓</span>
                                        <span><strong>เชื่อมชีวิต</strong> - ลงทุนในสิ่งที่สร้างคุณค่าให้สังคม</span>
                                    </li>
                                </ul>
                            </div>

                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                🎯 เปลี่ยนเงินสดให้เป็นกระแสรายได้ที่ไม่หยุดไหล
                            </p>
                        </div>

                        {{-- CTA Button --}}
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center gap-2 px-8 py-4
                                  bg-gradient-to-r from-purple-600 to-pink-600
                                  hover:from-purple-700 hover:to-pink-700
                                  text-white font-bold text-lg rounded-xl
                                  shadow-lg hover:shadow-2xl
                                  transform hover:scale-105
                                  transition-all duration-300">
                            <span>เริ่มลงทุนวันนี้</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                    </div>

                    {{-- Right: Visual --}}
                    <div x-show="visible"
                         x-transition:enter="transition ease-out duration-1000 delay-300"
                         x-transition:enter-start="opacity-0 transform translate-x-20"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         class="order-1 lg:order-2">

                        <div class="perspective-1000">
                            <div class="relative transform-gpu transition-all duration-500"
                                 :class="hovered && 'scale-105 rotate-y-3'">

                                {{-- Glow Effect --}}
                                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500 rounded-3xl blur-2xl opacity-30"></div>

                                {{-- Card --}}
                                <div class="relative glass-card rounded-3xl p-8 shadow-2xl">
                                    <div class="text-center">
                                        <div class="text-8xl mb-6 animate-float">💰</div>
                                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Dashboard นักลงทุน</h4>

                                        {{-- Stats Grid --}}
                                        <div class="grid grid-cols-2 gap-4 mb-6">
                                            <div class="bg-purple-100 dark:bg-purple-900/30 rounded-xl p-4">
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">รายได้รวม</p>
                                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">฿128,450</p>
                                            </div>
                                            <div class="bg-pink-100 dark:bg-pink-900/30 rounded-xl p-4">
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ROI</p>
                                                <p class="text-2xl font-bold text-pink-600 dark:text-pink-400">+245%</p>
                                            </div>
                                        </div>

                                        {{-- Progress Bar --}}
                                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-2 overflow-hidden">
                                            <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-full rounded-full"
                                                 style="width: 75%"></div>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">เป้าหมายปีนี้ 75% สำเร็จแล้ว</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================================
                ประตูที่ 2: นักพัฒนา (DEVELOPERS DOOR)
            ======================================== --}}
            <div class="mb-32"
                 x-data="{ visible: false, hovered: false }"
                 x-intersect="visible = true"
                 @mouseenter="hovered = true"
                 @mouseleave="hovered = false">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

                    {{-- Left: Visual --}}
                    <div x-show="visible"
                         x-transition:enter="transition ease-out duration-1000"
                         x-transition:enter-start="opacity-0 transform -translate-x-20"
                         x-transition:enter-end="opacity-100 transform translate-x-0">

                        <div class="perspective-1000">
                            <div class="relative transform-gpu transition-all duration-500"
                                 :class="hovered && 'scale-105 rotate-y-3'">

                                {{-- Glow Effect --}}
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-3xl blur-2xl opacity-30"></div>

                                {{-- Card --}}
                                <div class="relative glass-card rounded-3xl p-8 shadow-2xl">
                                    <div class="text-center">
                                        <div class="text-8xl mb-6 animate-float" style="animation-delay: 0.5s;">💻</div>
                                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Developer Tools</h4>

                                        {{-- Code Preview --}}
                                        <div class="bg-gray-900 rounded-xl p-4 text-left mb-4 overflow-hidden">
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                            </div>
                                            <code class="text-sm text-green-400 font-mono">
                                                <span class="text-purple-400">const</span> <span class="text-blue-400">revenue</span> = <span class="text-yellow-400">await</span> <span class="text-cyan-400">affiliate</span>.<span class="text-green-400">getEarnings</span>();<br>
                                                <span class="text-gray-500">// Real-time API access</span><br>
                                                <span class="text-purple-400">console</span>.<span class="text-green-400">log</span>(<span class="text-orange-400">`฿${revenue}`</span>);
                                            </code>
                                        </div>

                                        {{-- Features List --}}
                                        <div class="space-y-2 text-sm">
                                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                                <span class="text-blue-500">✓</span>
                                                <span>REST API & GraphQL</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                                <span class="text-cyan-500">✓</span>
                                                <span>SDKs สำหรับทุกภาษา</span>
                                            </div>
                                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                                <span class="text-blue-500">✓</span>
                                                <span>Webhooks & Real-time</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Content --}}
                    <div x-show="visible"
                         x-transition:enter="transition ease-out duration-1000 delay-300"
                         x-transition:enter-start="opacity-0 transform translate-x-20"
                         x-transition:enter-end="opacity-100 transform translate-x-0">

                        {{-- Door Number --}}
                        <div class="inline-flex items-center gap-3 mb-6 px-4 py-2 glass-card rounded-full">
                            <span class="text-3xl">🚪</span>
                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">ประตูที่ 2</span>
                        </div>

                        <h3 class="text-4xl md:text-5xl font-black mb-6
                                   text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-600
                                   dark:from-blue-400 dark:to-cyan-400">
                            สำหรับนักพัฒนา:<br>
                            Code เป็นเงิน
                        </h3>

                        <div class="space-y-4 text-lg text-gray-700 dark:text-gray-300 mb-8">
                            <p class="leading-relaxed">
                                <span class="font-bold text-blue-600 dark:text-blue-400">คุณเคยฝันไหม</span>
                                ที่จะสร้างผลงานและได้รับผลตอบแทนจากทุกการใช้งาน?
                            </p>

                            <p class="leading-relaxed">
                                <span class="font-bold text-cyan-600 dark:text-cyan-400">ที่นี่คุณทำได้</span>
                                พัฒนา plugins, themes, หรือ integrations แล้วขายผ่านระบบของเรา
                                <span class="underline decoration-wavy decoration-blue-400">รับเงินทุกครั้งที่มีคนซื้อ</span>
                            </p>

                            <div class="bg-gradient-to-r from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30
                                        border-l-4 border-blue-500 dark:border-blue-400
                                        rounded-r-xl p-6 my-6">
                                <p class="font-semibold text-gray-800 dark:text-gray-200 mb-2">🛠️ เราให้อะไรนักพัฒนา?</p>
                                <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-600 dark:text-blue-400 mt-1">✓</span>
                                        <span><strong>API ครบครัน</strong> - REST, GraphQL, Webhooks</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-600 dark:text-blue-400 mt-1">✓</span>
                                        <span><strong>Marketplace</strong> - ขายผลงานของคุณได้ทันที</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-600 dark:text-blue-400 mt-1">✓</span>
                                        <span><strong>Revenue Share</strong> - ได้ส่วนแบ่ง 70% จากยอดขาย</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-blue-600 dark:text-blue-400 mt-1">✓</span>
                                        <span><strong>Community</strong> - ชุมชนนักพัฒนาที่พร้อมช่วยเหลือ</span>
                                    </li>
                                </ul>
                            </div>

                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                🚀 เปลี่ยนทักษะการเขียนโค้ดให้เป็นรายได้ต่อเนื่อง
                            </p>
                        </div>

                        {{-- CTA Button --}}
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center gap-2 px-8 py-4
                                  bg-gradient-to-r from-blue-600 to-cyan-600
                                  hover:from-blue-700 hover:to-cyan-700
                                  text-white font-bold text-lg rounded-xl
                                  shadow-lg hover:shadow-2xl
                                  transform hover:scale-105
                                  transition-all duration-300">
                            <span>เริ่มพัฒนาวันนี้</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- ========================================
                ประตูที่ 3: นักการตลาด & ชุมชน (COMMUNITY DOOR)
            ======================================== --}}
            <div class="mb-32"
                 x-data="{ visible: false, hovered: false }"
                 x-intersect="visible = true"
                 @mouseenter="hovered = true"
                 @mouseleave="hovered = false">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">

                    {{-- Left: Content --}}
                    <div x-show="visible"
                         x-transition:enter="transition ease-out duration-1000"
                         x-transition:enter-start="opacity-0 transform -translate-x-20"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         class="order-2 lg:order-1">

                        {{-- Door Number --}}
                        <div class="inline-flex items-center gap-3 mb-6 px-4 py-2 glass-card rounded-full">
                            <span class="text-3xl">🚪</span>
                            <span class="text-sm font-bold text-green-600 dark:text-green-400">ประตูที่ 3</span>
                        </div>

                        <h3 class="text-4xl md:text-5xl font-black mb-6
                                   text-transparent bg-clip-text bg-gradient-to-r from-green-600 to-emerald-600
                                   dark:from-green-400 dark:to-emerald-400">
                            สำหรับชุมชน:<br>
                            เติบโตไปด้วยกัน
                        </h3>

                        <div class="space-y-4 text-lg text-gray-700 dark:text-gray-300 mb-8">
                            <p class="leading-relaxed">
                                <span class="font-bold text-green-600 dark:text-green-400">ธุรกิจไม่ใช่แค่ตัวเลข</span>
                                มันคือการสร้างความสัมพันธ์ สร้างคุณค่าให้คนรอบข้าง
                            </p>

                            <p class="leading-relaxed">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">เราเชื่อว่า</span>
                                ความสำเร็จที่แท้จริงคือเมื่อทุกคนในชุมชนเติบโตไปด้วยกัน
                                <span class="underline decoration-wavy decoration-green-400">ไม่ทิ้งใครไว้ข้างหลัง</span>
                            </p>

                            <div class="bg-gradient-to-r from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30
                                        border-l-4 border-green-500 dark:border-green-400
                                        rounded-r-xl p-6 my-6">
                                <p class="font-semibold text-gray-800 dark:text-gray-200 mb-2">🌱 เราเกี่ยวพันชุมชนอย่างไร?</p>
                                <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                                    <li class="flex items-start gap-2">
                                        <span class="text-green-600 dark:text-green-400 mt-1">✓</span>
                                        <span><strong>ระบบ Affiliate</strong> - แนะนำเพื่อน ได้ประโยชน์ทั้งสองฝ่าย</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-green-600 dark:text-green-400 mt-1">✓</span>
                                        <span><strong>Knowledge Sharing</strong> - แลกเปลี่ยนประสบการณ์และเทคนิค</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-green-600 dark:text-green-400 mt-1">✓</span>
                                        <span><strong>Community Events</strong> - workshop, meetup, networking</span>
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="text-green-600 dark:text-green-400 mt-1">✓</span>
                                        <span><strong>Give Back</strong> - ส่วนหนึ่งของกำไรไปช่วยเหลือสังคม</span>
                                    </li>
                                </ul>
                            </div>

                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                🤝 สร้างเครือข่ายที่แข็งแกร่ง มั่นคง ยั่งยืน
                            </p>
                        </div>

                        {{-- CTA Button --}}
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center gap-2 px-8 py-4
                                  bg-gradient-to-r from-green-600 to-emerald-600
                                  hover:from-green-700 hover:to-emerald-700
                                  text-white font-bold text-lg rounded-xl
                                  shadow-lg hover:shadow-2xl
                                  transform hover:scale-105
                                  transition-all duration-300">
                            <span>เข้าร่วมชุมชน</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>
                    </div>

                    {{-- Right: Visual --}}
                    <div x-show="visible"
                         x-transition:enter="transition ease-out duration-1000 delay-300"
                         x-transition:enter-start="opacity-0 transform translate-x-20"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         class="order-1 lg:order-2">

                        <div class="perspective-1000">
                            <div class="relative transform-gpu transition-all duration-500"
                                 :class="hovered && 'scale-105 rotate-y-3'">

                                {{-- Glow Effect --}}
                                <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-500 rounded-3xl blur-2xl opacity-30"></div>

                                {{-- Card --}}
                                <div class="relative glass-card rounded-3xl p-8 shadow-2xl">
                                    <div class="text-center">
                                        <div class="text-8xl mb-6 animate-float" style="animation-delay: 1s;">🌍</div>
                                        <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Community Impact</h4>

                                        {{-- Stats Grid --}}
                                        <div class="grid grid-cols-2 gap-4 mb-6">
                                            <div class="bg-green-100 dark:bg-green-900/30 rounded-xl p-4">
                                                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mb-1">12,450+</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">สมาชิกในชุมชน</p>
                                            </div>
                                            <div class="bg-emerald-100 dark:bg-emerald-900/30 rounded-xl p-4">
                                                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mb-1">฿2.4M</p>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">ช่วยเหลือสังคม</p>
                                            </div>
                                        </div>

                                        {{-- Community Icons --}}
                                        <div class="flex justify-center gap-2 flex-wrap">
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-emerald-400 flex items-center justify-center text-white text-xl">
                                                🧑‍💼
                                            </div>
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-cyan-400 flex items-center justify-center text-white text-xl">
                                                👨‍💻
                                            </div>
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-white text-xl">
                                                👩‍🎨
                                            </div>
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-400 to-red-400 flex items-center justify-center text-white text-xl">
                                                🧑‍🏫
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================================
        FINAL CTA SECTION - เชิญชวนสุดท้าย
    ======================================== --}}
    <section class="relative py-24 overflow-hidden
                    bg-gradient-to-br from-purple-900 via-pink-800 to-orange-700
                    dark:from-purple-950 dark:via-pink-950 dark:to-orange-950">

        {{-- Background Effects --}}
        <div class="absolute inset-0 opacity-20"
             style="background-image: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
                    background-size: 30px 30px;">
        </div>

        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

            <div x-data="{ visible: false }"
                 x-intersect="visible = true"
                 x-show="visible"
                 x-transition:enter="transition ease-out duration-1000"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100">

                <h2 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-6 leading-tight">
                    พร้อมที่จะ<span class="text-yellow-300">เริ่มต้น</span>แล้วหรือยัง?
                </h2>

                <p class="text-xl md:text-2xl text-white/90 mb-12 leading-relaxed">
                    เลือกประตูของคุณ เริ่มต้นการเดินทางสู่ความสำเร็จ<br>
                    <span class="text-pink-300 font-semibold">วันนี้คือวันที่ดีที่สุด</span>
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="{{ route('register') }}"
                       class="group relative px-12 py-5 bg-white text-purple-900 font-bold text-xl rounded-xl
                              shadow-2xl hover:shadow-3xl
                              transform hover:scale-105
                              transition-all duration-300 overflow-hidden">
                        {{-- Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-yellow-200/30 to-transparent
                                    -translate-x-full group-hover:translate-x-full
                                    transition-transform duration-1000"></div>

                        <span class="relative z-10 flex items-center gap-2">
                            <span>สมัครสมาชิกฟรี</span>
                            <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </span>
                    </a>

                    <a href="#doors"
                       class="px-12 py-5 glass-card text-white font-semibold text-xl rounded-xl
                              hover:bg-white/20 dark:hover:bg-gray-800/30
                              transform hover:scale-105
                              transition-all duration-300">
                        อ่านเพิ่มเติม
                    </a>
                </div>

                {{-- Trust Indicators --}}
                <div class="mt-12 grid grid-cols-3 gap-6 max-w-2xl mx-auto">
                    <div class="text-center">
                        <p class="text-3xl font-bold text-white mb-1">12,450+</p>
                        <p class="text-sm text-white/80">สมาชิกใช้งาน</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-white mb-1">฿50M+</p>
                        <p class="text-sm text-white/80">จ่ายคอมมิชชัน</p>
                    </div>
                    <div class="text-center">
                        <p class="text-3xl font-bold text-white mb-1">24/7</p>
                        <p class="text-sm text-white/80">ระบบทำงาน</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<script>
/**
 * Homepage Manager - Alpine.js Component
 *
 * จัดการ interactivity และ animations สำหรับหน้า homepage
 *
 * @returns {object} Alpine component object
 */
function homepageManager() {
    return {
        // ----------------
        // State
        // ----------------
        currentDoor: null,
        scrollProgress: 0,

        // ----------------
        // Lifecycle
        // ----------------

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('🎉 Homepage initialized - V3 Coding Standards');

            // เริ่มต้น GSAP animations
            this.initScrollAnimations();

            // เริ่มต้น smooth scroll
            this.initSmoothScroll();

            // Listen for scroll
            window.addEventListener('scroll', () => {
                this.updateScrollProgress();
            });
        },

        // ----------------
        // Methods
        // ----------------

        /**
         * เริ่มต้น scroll animations ด้วย GSAP
         */
        initScrollAnimations() {
            // Register ScrollTrigger plugin
            gsap.registerPlugin(ScrollTrigger);

            // Parallax effect สำหรับ background orbs
            gsap.to('.animate-pulse', {
                y: -100,
                opacity: 0.5,
                scrollTrigger: {
                    trigger: 'body',
                    start: 'top top',
                    end: 'bottom top',
                    scrub: true
                }
            });

            console.log('✅ GSAP animations initialized');
        },

        /**
         * เริ่มต้น smooth scroll
         */
        initSmoothScroll() {
            // Enable smooth scrolling สำหรับทุก anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => {
                    e.preventDefault();
                    const target = document.querySelector(anchor.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        },

        /**
         * อัพเดท scroll progress
         */
        updateScrollProgress() {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            this.scrollProgress = (winScroll / height) * 100;
        },

        /**
         * Scroll ไปยัง section ที่ระบุ
         *
         * @param {string} sectionId - ID ของ section
         */
        scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        },

        /**
         * เปิดประตู (Door)
         *
         * @param {number} doorNumber - หมายเลขประตู (1-3)
         */
        openDoor(doorNumber) {
            this.currentDoor = doorNumber;
            console.log(`🚪 เปิดประตูที่ ${doorNumber}`);

            // Dispatch event
            this.$dispatch('door-opened', { door: doorNumber });
        }
    };
}

// Export for global use
window.homepageManager = homepageManager;
</script>
@endpush
