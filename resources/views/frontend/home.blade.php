{{--
/**
 * หน้าแรก Thaiprompt - 3 Doors to Success
 *
 * Storytelling Design: ทำไมต้องเป็น Thaiprompt?
 * เราไม่ได้แค่มีเครื่องมือ - เราแก้ปัญหาที่แท้จริง
 *
 * 3 ประตูสู่ความสำเร็จ:
 * - 🚪 ประตูที่ 1: นักลงทุน - การลงทุนที่เชื่อมกับชีวิตจริง
 * - 🚪 ประตูที่ 2: นักพัฒนา - เปลี่ยน Code เป็นรายได้
 * - 🚪 ประตูที่ 3: ชุมชน - เติบโตไปด้วยกัน ไม่ทิ้งใครไว้ข้างหลัง
 *
 * V3 Technologies:
 * - Tailwind CSS (pure utility-first)
 * - Alpine.js (reactive components)
 * - GSAP (premium animations)
 * - Three.js (3D effects)
 * - Glassmorphism + Particles
 *
 * @version 4.0.0
 * @author Thaiprompt Team
 * @created 2025-11-22
 */
--}}

@extends('layouts.guest')

@section('title', 'Thaiprompt - เราไม่ได้แค่มีเครื่องมือ เราแก้ปัญหาที่แท้จริง')

@push('styles')
<style>
/**
 * Custom Animations & Styles
 */

/* 3D Perspective */
.perspective-2000 {
    perspective: 2000px;
}

/* Floating Animation */
@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-30px) rotate(2deg); }
}

@keyframes float-delayed {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(-2deg); }
}

.animate-float {
    animation: float 8s ease-in-out infinite;
}

.animate-float-delayed {
    animation: float-delayed 10s ease-in-out infinite;
}

/* Glow Effect */
@keyframes glow {
    0%, 100% {
        box-shadow: 0 0 40px rgba(168, 85, 247, 0.4),
                    0 0 80px rgba(236, 72, 153, 0.2);
    }
    50% {
        box-shadow: 0 0 60px rgba(168, 85, 247, 0.6),
                    0 0 120px rgba(236, 72, 153, 0.4);
    }
}

.animate-glow {
    animation: glow 3s ease-in-out infinite;
}

/* Pulse Ring */
@keyframes pulse-ring {
    0% {
        transform: scale(0.8);
        opacity: 1;
    }
    100% {
        transform: scale(2);
        opacity: 0;
    }
}

.animate-pulse-ring {
    animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Door Opening Animation */
@keyframes door-open {
    from {
        transform: perspective(1000px) rotateY(-90deg);
        opacity: 0;
    }
    to {
        transform: perspective(1000px) rotateY(0deg);
        opacity: 1;
    }
}

.door-enter {
    animation: door-open 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

/* Gradient Text Animation */
@keyframes gradient-shift {
    0%, 100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient-shift 5s ease infinite;
}

/* Particle Animation */
@keyframes particle-float {
    0% {
        transform: translateY(0) translateX(0);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-100vh) translateX(50px);
        opacity: 0;
    }
}

.particle {
    animation: particle-float linear infinite;
}

/* Smooth Scrolling */
html {
    scroll-behavior: smooth;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 12px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #a855f7, #ec4899);
    border-radius: 6px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #9333ea, #db2777);
}
</style>
@endpush

@section('content')

{{-- Alpine.js Main Component --}}
<div x-data="homepageStorytellingManager()" x-init="init()" class="relative overflow-hidden">

    {{-- ================================================================
        HERO SECTION - อลังการ ล้ำสมัย สไตล์ Futuristic
    ================================================================ --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-purple-950 via-black to-pink-950">

        {{-- Animated Background Mesh --}}
        <div class="absolute inset-0 opacity-30">
            <div class="absolute inset-0" style="background-image:
                radial-gradient(circle at 20% 50%, rgba(168, 85, 247, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(236, 72, 153, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 50% 100%, rgba(251, 146, 60, 0.2) 0%, transparent 50%);">
            </div>
        </div>

        {{-- Grid Pattern --}}
        <div class="absolute inset-0 opacity-20"
             style="background-image: linear-gradient(rgba(168, 85, 247, 0.1) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(168, 85, 247, 0.1) 1px, transparent 1px);
                    background-size: 50px 50px;">
        </div>

        {{-- Floating Particles --}}
        <div class="absolute inset-0 pointer-events-none">
            <template x-for="(particle, index) in particles" :key="index">
                <div class="particle absolute w-1 h-1 bg-purple-400 rounded-full"
                     :style="`left: ${particle.x}%;
                              animation-duration: ${particle.duration}s;
                              animation-delay: ${particle.delay}s;`">
                </div>
            </template>
        </div>

        {{-- Main Content --}}
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 text-center">

            {{-- Logo Container (3D Floating) --}}
            <div class="mb-16 perspective-2000"
                 x-data="{ mouseX: 0, mouseY: 0 }"
                 @mousemove.window="mouseX = ($event.clientX / window.innerWidth - 0.5) * 20; mouseY = ($event.clientY / window.innerHeight - 0.5) * 20">

                <div class="inline-block animate-float relative">
                    {{-- Glow Rings --}}
                    <div class="absolute inset-0 animate-pulse-ring">
                        <div class="w-full h-full rounded-full border-4 border-purple-500/30"></div>
                    </div>
                    <div class="absolute inset-0 animate-pulse-ring" style="animation-delay: 1s;">
                        <div class="w-full h-full rounded-full border-4 border-pink-500/30"></div>
                    </div>

                    {{-- Logo Card --}}
                    <div class="relative p-12 md:p-16 backdrop-blur-2xl bg-white/5 border-2 border-white/10 rounded-[3rem] shadow-2xl animate-glow"
                         :style="`transform: rotateY(${mouseX}deg) rotateX(${-mouseY}deg)`">

                        @if(\App\Models\Setting::get('logo'))
                            <img src="{{ asset(\App\Models\Setting::get('logo')) }}"
                                 alt="Thaiprompt Logo"
                                 class="w-32 h-32 md:w-48 md:h-48 lg:w-64 lg:h-64 mx-auto object-contain drop-shadow-[0_0_60px_rgba(168,85,247,0.8)]"
                                 loading="eager">
                        @else
                            {{-- Fallback Logo --}}
                            <div class="w-32 h-32 md:w-48 md:h-48 lg:w-64 lg:h-64 mx-auto bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-3xl flex items-center justify-center shadow-2xl">
                                <span class="text-6xl md:text-8xl lg:text-9xl font-black text-white drop-shadow-2xl">TP</span>
                            </div>
                        @endif

                        {{-- Floating Badge --}}
                        <div class="absolute -top-4 -right-4 px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full text-white text-sm font-bold shadow-lg animate-pulse">
                            ✨ V4.0
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hero Headline --}}
            <div class="space-y-8 mb-12">
                <h1 class="text-5xl md:text-7xl lg:text-8xl font-black leading-tight">
                    <span class="block text-white mb-4 drop-shadow-2xl">
                        เรา<span class="underline decoration-wavy decoration-yellow-400">ไม่ได้</span>แค่มีเครื่องมือ
                    </span>
                    <span class="block text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-pink-400 to-orange-400 animate-gradient">
                        เราแก้ปัญหาที่แท้จริง
                    </span>
                </h1>

                <p class="text-xl md:text-3xl lg:text-4xl text-gray-300 max-w-5xl mx-auto leading-relaxed font-light">
                    ปัญหาของคุณคือ<span class="text-pink-400 font-semibold">ปัญหาของเรา</span><br>
                    เราจะพาคุณไปถึง<span class="text-purple-400 font-semibold">จุดหมาย</span>ด้วยกัน
                </p>
            </div>

            {{-- CTA Buttons --}}
            <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-20">
                <button @click="scrollToSection('doors')"
                        class="group relative overflow-hidden px-12 py-6 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 text-white font-bold text-xl rounded-2xl shadow-2xl hover:shadow-purple-500/50 transform hover:scale-105 transition-all duration-300">
                    {{-- Shine Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>

                    <span class="relative z-10 flex items-center gap-3">
                        <i class="fas fa-door-open text-2xl"></i>
                        <span>เปิดประตูสู่ความสำเร็จ</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                    </span>
                </button>

                <a href="{{ route('about') }}"
                   class="px-12 py-6 backdrop-blur-xl bg-white/10 border-2 border-white/20 text-white font-bold text-xl rounded-2xl hover:bg-white/20 transition-all duration-300">
                    <i class="fas fa-book-open mr-2"></i>
                    เรื่องราวของเรา
                </a>
            </div>

            {{-- Trust Indicators --}}
            <div class="grid grid-cols-3 gap-8 max-w-3xl mx-auto">
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400 mb-2">
                        15K+
                    </div>
                    <div class="text-sm text-gray-400">ผู้ใช้ที่เชื่อใจ</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-orange-400 mb-2">
                        ฿50M+
                    </div>
                    <div class="text-sm text-gray-400">จ่ายจริง</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl md:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-purple-400 mb-2">
                        24/7
                    </div>
                    <div class="text-sm text-gray-400">พร้อมช่วยเหลือ</div>
                </div>
            </div>

            {{-- Scroll Indicator --}}
            <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
                <button @click="scrollToSection('problem')"
                        class="flex flex-col items-center gap-2 text-gray-400 hover:text-white transition-colors">
                    <span class="text-sm">เลื่อนลง</span>
                    <div class="w-8 h-12 border-2 border-gray-400 rounded-full flex items-start justify-center p-2">
                        <div class="w-1 h-3 bg-gray-400 rounded-full animate-pulse"></div>
                    </div>
                </button>
            </div>
        </div>
    </section>

    {{-- ================================================================
        PROBLEM SECTION - คุณเคยประสบปัญหาเหล่านี้ไหม?
    ================================================================ --}}
    <section id="problem" class="relative py-32 bg-gradient-to-b from-black to-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Section Header --}}
            <div class="text-center mb-20"
                 x-intersect="$el.classList.add('door-enter')">
                <h2 class="text-4xl md:text-6xl font-black text-white mb-6">
                    คุณเคยประสบ<span class="text-transparent bg-clip-text bg-gradient-to-r from-red-500 to-pink-500">ปัญหาเหล่านี้</span>ไหม?
                </h2>
                <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                    หลายคนเหมือนคุณ กำลังเผชิญกับปัญหาเดียวกัน<br>
                    และเรา<span class="text-purple-400 font-semibold">มีคำตอบ</span>
                </p>
            </div>

            {{-- Problem Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">

                {{-- Problem 1: นักลงทุน --}}
                <div class="group relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:bg-white/10 transition-all duration-300 hover:scale-105"
                     x-intersect.once="$el.classList.add('door-enter')"
                     style="animation-delay: 0.1s;">
                    <div class="text-6xl mb-6">😰</div>
                    <h3 class="text-2xl font-bold text-white mb-4">
                        การลงทุนซับซ้อนเกินไป
                    </h3>
                    <p class="text-gray-400 mb-6">
                        อยากลงทุนเพื่ออนาคต แต่ไม่รู้จะเริ่มต้นอย่างไร มีเงินน้อย กลัวเสี่ยง ไม่มีความรู้
                    </p>
                    <div class="flex items-center gap-2 text-red-400 font-semibold">
                        <i class="fas fa-times-circle"></i>
                        <span>ปัญหาที่พบบ่อย</span>
                    </div>
                </div>

                {{-- Problem 2: นักพัฒนา --}}
                <div class="group relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:bg-white/10 transition-all duration-300 hover:scale-105"
                     x-intersect.once="$el.classList.add('door-enter')"
                     style="animation-delay: 0.2s;">
                    <div class="text-6xl mb-6">😣</div>
                    <h3 class="text-2xl font-bold text-white mb-4">
                        มี Skill แต่หาเงินยาก
                    </h3>
                    <p class="text-gray-400 mb-6">
                        เขียนโค้ดเก่ง ทำงาน Freelance แต่หาลูกค้าไม่ได้ ขายผลงานไม่เป็น รายได้ไม่สม่ำเสมอ
                    </p>
                    <div class="flex items-center gap-2 text-red-400 font-semibold">
                        <i class="fas fa-times-circle"></i>
                        <span>ปัญหาที่พบบ่อย</span>
                    </div>
                </div>

                {{-- Problem 3: ชุมชน --}}
                <div class="group relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:bg-white/10 transition-all duration-300 hover:scale-105"
                     x-intersect.once="$el.classList.add('door-enter')"
                     style="animation-delay: 0.3s;">
                    <div class="text-6xl mb-6">😔</div>
                    <h3 class="text-2xl font-bold text-white mb-4">
                        ทำธุรกิจคนเดียวลำบาก
                    </h3>
                    <p class="text-gray-400 mb-6">
                        ไม่มีทีม ไม่มีเครือข่าย ต้องทำทุกอย่างเอง เหนื่อย ท้อ อยากมีคนช่วย
                    </p>
                    <div class="flex items-center gap-2 text-red-400 font-semibold">
                        <i class="fas fa-times-circle"></i>
                        <span>ปัญหาที่พบบ่อย</span>
                    </div>
                </div>
            </div>

            {{-- Transition to Solution --}}
            <div class="text-center">
                <div class="inline-block px-8 py-4 backdrop-blur-xl bg-gradient-to-r from-purple-600/20 to-pink-600/20 border-2 border-purple-500/30 rounded-2xl">
                    <p class="text-2xl text-white font-semibold">
                        แต่ถ้าเรา<span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-orange-400">บอกคุณ</span>ว่า...
                    </p>
                    <p class="text-xl text-gray-300 mt-2">
                        มี<span class="text-purple-400 font-bold">ทางออก</span>ที่ดีกว่า และ<span class="text-pink-400 font-bold">ง่ายกว่า</span>ที่คุณคิด
                    </p>
                </div>
            </div>

        </div>
    </section>

    {{-- ================================================================
        3 DOORS SECTION - ทางแก้ปัญหา 3 ประตู (Landing Page)
    ================================================================ --}}
    <section id="doors" class="relative py-32 bg-gradient-to-b from-gray-900 to-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Section Header --}}
            <div class="text-center mb-24"
                 x-intersect.once="$el.classList.add('door-enter')">
                <h2 class="text-5xl md:text-7xl font-black text-white mb-6">
                    3 ประตูสู่<span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-pink-400 to-orange-400">ความสำเร็จ</span>
                </h2>
                <p class="text-2xl text-gray-400 max-w-4xl mx-auto">
                    เลือกประตูของคุณ หรือเปิดทั้ง 3 ประตูได้ในเวลาเดียวกัน<br>
                    เพราะที่นี่ <span class="text-purple-400 font-semibold">โอกาสไม่มีขีดจำกัด</span>
                </p>
            </div>

            {{-- 3 Door Cards Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                {{-- Door 1: นักลงทุน --}}
                <a href="{{ route('frontend.investors') }}"
                   class="group relative block perspective-2000"
                   x-intersect.once="$el.classList.add('door-enter')"
                   style="animation-delay: 0.1s;">

                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500 rounded-3xl blur-3xl opacity-0 group-hover:opacity-40 transition-opacity duration-500"></div>

                    {{-- Card --}}
                    <div class="relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 shadow-2xl
                                hover:bg-white/10 hover:border-purple-500/50
                                transform group-hover:scale-105 group-hover:-translate-y-2
                                transition-all duration-500">

                        {{-- Door Icon --}}
                        <div class="text-7xl mb-6 text-center animate-float group-hover:scale-110 transition-transform duration-300">
                            💰
                        </div>

                        {{-- Door Number Badge --}}
                        <div class="inline-flex items-center gap-2 px-4 py-2 backdrop-blur-xl bg-purple-600/20 border border-purple-500/30 rounded-full mb-4">
                            <span class="text-2xl">🚪</span>
                            <span class="text-sm font-bold text-purple-400">ประตูที่ 1</span>
                        </div>

                        {{-- Title --}}
                        <h3 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400 mb-4 leading-tight">
                            สำหรับนักลงทุน
                        </h3>

                        {{-- Description --}}
                        <p class="text-lg text-gray-300 mb-6 leading-relaxed">
                            การลงทุนที่<span class="text-purple-400 font-semibold">เชื่อมกับชีวิตจริง</span>
                            เริ่มต้นง่าย ไม่ต้องมีเงินมาก
                        </p>

                        {{-- Key Points --}}
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-purple-400 mt-1"></i>
                                <span>ระบบอัตโนมัติ ทำงาน 24/7</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-purple-400 mt-1"></i>
                                <span>ROI สูง มั่นคง โปร่งใส</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-purple-400 mt-1"></i>
                                <span>สร้างรายได้เสริมครอบครัว</span>
                            </li>
                        </ul>

                        {{-- CTA --}}
                        <div class="flex items-center justify-between text-purple-400 font-bold group-hover:text-purple-300 transition-colors">
                            <span>เรียนรู้เพิ่มเติม</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </div>

                        {{-- Hover Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 pointer-events-none rounded-3xl"></div>
                    </div>
                </a>

                {{-- Door 2: นักพัฒนา --}}
                <a href="{{ route('frontend.developers') }}"
                   class="group relative block perspective-2000"
                   x-intersect.once="$el.classList.add('door-enter')"
                   style="animation-delay: 0.2s;">

                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-3xl blur-3xl opacity-0 group-hover:opacity-40 transition-opacity duration-500"></div>

                    {{-- Card --}}
                    <div class="relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 shadow-2xl
                                hover:bg-white/10 hover:border-blue-500/50
                                transform group-hover:scale-105 group-hover:-translate-y-2
                                transition-all duration-500">

                        {{-- Door Icon --}}
                        <div class="text-7xl mb-6 text-center animate-float-delayed group-hover:scale-110 transition-transform duration-300">
                            💻
                        </div>

                        {{-- Door Number Badge --}}
                        <div class="inline-flex items-center gap-2 px-4 py-2 backdrop-blur-xl bg-blue-600/20 border border-blue-500/30 rounded-full mb-4">
                            <span class="text-2xl">🚪</span>
                            <span class="text-sm font-bold text-blue-400">ประตูที่ 2</span>
                        </div>

                        {{-- Title --}}
                        <h3 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400 mb-4 leading-tight">
                            สำหรับนักพัฒนา
                        </h3>

                        {{-- Description --}}
                        <p class="text-lg text-gray-300 mb-6 leading-relaxed">
                            เปลี่ยน<span class="text-blue-400 font-semibold">Code เป็นรายได้</span>
                            ขายผลงานได้ทันที มี Marketplace พร้อม
                        </p>

                        {{-- Key Points --}}
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-blue-400 mt-1"></i>
                                <span>ได้ถึง 70% Revenue Share</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-blue-400 mt-1"></i>
                                <span>Passive Income ต่อเนื่อง</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-blue-400 mt-1"></i>
                                <span>Community นักพัฒนาแน่น</span>
                            </li>
                        </ul>

                        {{-- CTA --}}
                        <div class="flex items-center justify-between text-blue-400 font-bold group-hover:text-blue-300 transition-colors">
                            <span>เรียนรู้เพิ่มเติม</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </div>

                        {{-- Hover Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 pointer-events-none rounded-3xl"></div>
                    </div>
                </a>

                {{-- Door 3: ชุมชน --}}
                <a href="{{ route('frontend.community') }}"
                   class="group relative block perspective-2000"
                   x-intersect.once="$el.classList.add('door-enter')"
                   style="animation-delay: 0.3s;">

                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-500 rounded-3xl blur-3xl opacity-0 group-hover:opacity-40 transition-opacity duration-500"></div>

                    {{-- Card --}}
                    <div class="relative backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 shadow-2xl
                                hover:bg-white/10 hover:border-green-500/50
                                transform group-hover:scale-105 group-hover:-translate-y-2
                                transition-all duration-500">

                        {{-- Door Icon --}}
                        <div class="text-7xl mb-6 text-center animate-float group-hover:scale-110 transition-transform duration-300">
                            🌍
                        </div>

                        {{-- Door Number Badge --}}
                        <div class="inline-flex items-center gap-2 px-4 py-2 backdrop-blur-xl bg-green-600/20 border border-green-500/30 rounded-full mb-4">
                            <span class="text-2xl">🚪</span>
                            <span class="text-sm font-bold text-green-400">ประตูที่ 3</span>
                        </div>

                        {{-- Title --}}
                        <h3 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-400 mb-4 leading-tight">
                            สำหรับชุมชน
                        </h3>

                        {{-- Description --}}
                        <p class="text-lg text-gray-300 mb-6 leading-relaxed">
                            <span class="text-green-400 font-semibold">เติบโตไปด้วยกัน</span>
                            แชร์ความรู้ สร้างเครือข่าย ช่วยเหลือกัน
                        </p>

                        {{-- Key Points --}}
                        <ul class="space-y-3 mb-8">
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span>Affiliate System ได้ทั้ง 2 ฝ่าย</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span>เรียนรู้ฟรี แลกเปลี่ยนความรู้</span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span>ช่วยเหลือสังคมร่วมกัน</span>
                            </li>
                        </ul>

                        {{-- CTA --}}
                        <div class="flex items-center justify-between text-green-400 font-bold group-hover:text-green-300 transition-colors">
                            <span>เรียนรู้เพิ่มเติม</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </div>

                        {{-- Hover Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000 pointer-events-none rounded-3xl"></div>
                    </div>
</a>

            </div>

            {{-- Bottom CTA --}}
            <div class="text-center mt-20"
                 x-intersect.once="$el.classList.add('door-enter')">
                <p class="text-xl text-gray-400 mb-6">
                    ยังไม่แน่ใจว่าประตูไหนเหมาะกับคุณ?
                </p>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center gap-3 px-12 py-5 bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 hover:from-purple-700 hover:via-pink-700 hover:to-orange-700 text-white font-bold text-xl rounded-2xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                    <i class="fas fa-rocket"></i>
                    <span>ลงทะเบียนฟรี เปิดทั้ง 3 ประตูได้เลย!</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>

        </div>
    </section>

    {{-- ================================================================
        FINAL CTA SECTION
    ================================================================ --}}
    <section class="relative py-32 overflow-hidden bg-gradient-to-br from-purple-900 via-pink-900 to-orange-900">

        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-20"
             style="background-image: radial-gradient(circle, white 1px, transparent 1px);
                    background-size: 40px 40px;">
        </div>

        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">

            <div x-intersect.once="$el.classList.add('door-enter')">
                <h2 class="text-5xl md:text-7xl font-black mb-8 leading-tight">
                    พร้อมที่จะเปิดประตู<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">
                        สู่ความสำเร็จ
                    </span>แล้วหรือยัง?
                </h2>

                <p class="text-2xl md:text-3xl mb-16 text-white/90 leading-relaxed">
                    เข้าร่วมกับผู้ใช้นับพันที่<span class="font-bold text-yellow-300">เปลี่ยนชีวิต</span>ด้วย Thaiprompt<br>
                    <span class="text-xl text-white/70">วันนี้คือวันที่ดีที่สุดที่จะเริ่มต้น</span>
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-16">
                    <a href="{{ route('register') }}"
                       class="group relative overflow-hidden px-16 py-6 bg-white text-purple-900 font-black text-2xl rounded-2xl shadow-2xl hover:shadow-white/50 transform hover:scale-110 transition-all duration-300">
                        {{-- Shine Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-yellow-200/50 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>

                        <span class="relative z-10 flex items-center gap-3">
                            <i class="fas fa-rocket text-3xl"></i>
                            <span>ลงทะเบียนฟรีเลย!</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </span>
                    </a>

                    <a href="#doors"
                       class="px-16 py-6 backdrop-blur-xl bg-white/10 border-2 border-white/30 font-bold text-2xl rounded-2xl hover:bg-white/20 transition-all duration-300">
                        <i class="fas fa-book-open mr-2"></i>
                        อ่านเพิ่มเติม
                    </a>
                </div>

                {{-- Final Trust Badges --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-3xl mx-auto">
                    <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                        <div class="text-4xl mb-2">🔒</div>
                        <p class="font-bold text-lg mb-1">ปลอดภัย 100%</p>
                        <p class="text-sm text-white/70">เข้ารหัส SSL</p>
                    </div>
                    <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                        <div class="text-4xl mb-2">⚡</div>
                        <p class="font-bold text-lg mb-1">รองรับ 24/7</p>
                        <p class="text-sm text-white/70">ทีมพร้อมช่วย</p>
                    </div>
                    <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                        <div class="text-4xl mb-2">💎</div>
                        <p class="font-bold text-lg mb-1">มาตรฐานสากล</p>
                        <p class="text-sm text-white/70">คุณภาพรับรอง</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

</div>

@endsection

@push('scripts')
{{-- GSAP for advanced animations --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>

<script>
/**
 * Homepage Storytelling Manager
 *
 * จัดการ animations และ interactivity ทั้งหมด
 */
function homepageStorytellingManager() {
    return {
        // ----------------
        // STATE
        // ----------------
        particles: [],
        isVisible: false,

        // ----------------
        // LIFECYCLE
        // ----------------
        init() {
            console.log('🎨 Homepage Storytelling V4.0 initialized');

            // Generate particles
            this.generateParticles();

            // Initialize GSAP animations
            this.initGSAPAnimations();

            // Show content
            setTimeout(() => {
                this.isVisible = true;
            }, 100);
        },

        // ----------------
        // METHODS
        // ----------------

        /**
         * สร้าง floating particles
         */
        generateParticles() {
            const particleCount = 30;

            for (let i = 0; i < particleCount; i++) {
                this.particles.push({
                    x: Math.random() * 100,
                    duration: 15 + Math.random() * 10,
                    delay: Math.random() * 5,
                });
            }
        },

        /**
         * เริ่มต้น GSAP animations
         */
        initGSAPAnimations() {
            gsap.registerPlugin(ScrollTrigger);

            // Parallax effect for hero section
            gsap.to('.animate-float', {
                y: -50,
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
         * Scroll ไปยัง section
         */
        scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    };
}

// Export to global
window.homepageStorytellingManager = homepageStorytellingManager;
</script>
@endpush
