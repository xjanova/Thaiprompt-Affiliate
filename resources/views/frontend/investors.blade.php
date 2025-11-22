{{--
/**
 * หน้าประตูที่ 1: นักลงทุน - การลงทุนที่เชื่อมกับชีวิตจริง
 *
 * Storytelling Design จากมุมมองนักลงทุน:
 * - ปัญหา: การลงทุนซับซ้อน มีความเสี่ยง ไม่รู้จะเริ่มอย่างไร
 * - วิธีแก้: ระบบลงทุนที่ใครก็เข้าถึงได้ อัตโนมัติ โปร่งใส
 * - เชื่อมโยงชีวิต: ครอบครัว อนาคต เวลา
 * - พาไปสู่: การลงทะเบียน
 *
 * V3 Technologies:
 * - Tailwind CSS (pure utility-first)
 * - Alpine.js (reactive components)
 * - GSAP (premium animations)
 * - Glassmorphism + Particles
 *
 * @version 1.0.0
 * @author Thaiprompt Team
 * @created 2025-11-22
 */
--}}

@extends('layouts.guest')

@section('title', $pageTitle ?? 'สำหรับนักลงทุน - การลงทุนที่เชื่อมกับชีวิตจริง')

@push('styles')
<style>
/**
 * Custom Animations สำหรับหน้านักลงทุน
 */

/* Floating Animation */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

/* Glow Effect */
@keyframes glow-purple {
    0%, 100% { box-shadow: 0 0 40px rgba(168, 85, 247, 0.4); }
    50% { box-shadow: 0 0 60px rgba(168, 85, 247, 0.6); }
}

.animate-glow-purple {
    animation: glow-purple 3s ease-in-out infinite;
}

/* Number Counter Animation */
@keyframes pulse-scale {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.animate-pulse-scale {
    animation: pulse-scale 2s ease-in-out infinite;
}

/* Smooth Scrolling */
html {
    scroll-behavior: smooth;
}
</style>
@endpush

@section('content')

{{-- Alpine.js Main Component --}}
<div x-data="investorsPageManager()" x-init="init()" class="relative overflow-hidden">

    {{-- ================================================================
        HERO SECTION - สำหรับนักลงทุน
    ================================================================ --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-purple-950 via-black to-pink-950">

        {{-- Background Mesh --}}
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0" style="background-image:
                radial-gradient(circle at 20% 50%, rgba(168, 85, 247, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(236, 72, 153, 0.3) 0%, transparent 50%);">
            </div>
        </div>

        {{-- Main Content --}}
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">

            {{-- Breadcrumb --}}
            <div class="mb-8 flex items-center gap-3 text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-purple-400 transition-colors">
                    <i class="fas fa-home"></i> หน้าแรก
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-purple-400">🚪 ประตูที่ 1: นักลงทุน</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

                {{-- Left: Content --}}
                <div class="space-y-8">
                    {{-- Badge --}}
                    <div class="inline-flex items-center gap-3 px-6 py-3 backdrop-blur-xl bg-purple-600/20 border-2 border-purple-500/30 rounded-full">
                        <span class="text-4xl">🚪</span>
                        <span class="text-lg font-bold text-purple-400">ประตูที่ 1: นักลงทุน</span>
                    </div>

                    {{-- Headline --}}
                    <h1 class="text-5xl md:text-7xl font-black leading-tight">
                        <span class="block text-white mb-4">การลงทุนที่</span>
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">
                            เชื่อมกับชีวิตจริง
                        </span>
                    </h1>

                    {{-- Subheadline --}}
                    <p class="text-2xl text-gray-300 leading-relaxed">
                        ไม่ต้องมีเงินมาก ไม่ต้องมีความรู้ก่อน<br>
                        <span class="text-purple-400 font-semibold">ใครก็ลงทุนได้</span> เริ่มต้นง่าย ได้ผลจริง
                    </p>

                    {{-- Key Stats --}}
                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center backdrop-blur-xl bg-white/5 rounded-2xl p-4 border border-white/10">
                            <div class="text-3xl font-black text-purple-400 mb-1">245%</div>
                            <div class="text-xs text-gray-400">เฉลี่ย ROI/ปี</div>
                        </div>
                        <div class="text-center backdrop-blur-xl bg-white/5 rounded-2xl p-4 border border-white/10">
                            <div class="text-3xl font-black text-pink-400 mb-1">24/7</div>
                            <div class="text-xs text-gray-400">อัตโนมัติ</div>
                        </div>
                        <div class="text-center backdrop-blur-xl bg-white/5 rounded-2xl p-4 border border-white/10">
                            <div class="text-3xl font-black text-orange-400 mb-1">100%</div>
                            <div class="text-xs text-gray-400">โปร่งใส</div>
                        </div>
                    </div>

                    {{-- CTA Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}"
                           class="group inline-flex items-center justify-center gap-3 px-10 py-5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold text-xl rounded-2xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                            <i class="fas fa-rocket"></i>
                            <span>เริ่มลงทุนวันนี้</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </a>

                        <button @click="scrollToSection('how-it-works')"
                                class="px-10 py-5 backdrop-blur-xl bg-white/10 border-2 border-white/20 text-white font-bold text-xl rounded-2xl hover:bg-white/20 transition-all duration-300">
                            <i class="fas fa-book-open mr-2"></i>
                            เรียนรู้เพิ่มเติม
                        </button>
                    </div>
                </div>

                {{-- Right: Dashboard Preview --}}
                <div class="animate-float">
                    <div class="relative">
                        {{-- Glow Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500 rounded-3xl blur-3xl opacity-30"></div>

                        {{-- Dashboard Card --}}
                        <div class="relative backdrop-blur-xl bg-white/10 border-2 border-white/20 rounded-3xl p-8 shadow-2xl animate-glow-purple">
                            <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                                <span class="text-4xl">💰</span>
                                <span>Dashboard นักลงทุน</span>
                            </h3>

                            {{-- Portfolio Value --}}
                            <div class="mb-6 p-6 bg-gradient-to-br from-purple-600/30 to-pink-600/30 rounded-2xl border border-purple-500/30">
                                <p class="text-sm text-gray-300 mb-2">มูลค่าพอร์ตโฟลิโอ</p>
                                <p class="text-5xl font-black text-white mb-2">฿428,650</p>
                                <p class="text-green-400 flex items-center gap-2">
                                    <i class="fas fa-arrow-up"></i>
                                    <span>+245% ตั้งแต่เริ่มลงทุน</span>
                                </p>
                            </div>

                            {{-- Stats Grid --}}
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="backdrop-blur-xl bg-purple-500/20 rounded-2xl p-4 border border-purple-500/30">
                                    <p class="text-xs text-gray-400 mb-1">รายได้เดือนนี้</p>
                                    <p class="text-2xl font-black text-purple-400">฿32,100</p>
                                    <p class="text-xs text-green-400 mt-1">↗ +18%</p>
                                </div>
                                <div class="backdrop-blur-xl bg-pink-500/20 rounded-2xl p-4 border border-pink-500/30">
                                    <p class="text-xs text-gray-400 mb-1">ถอนได้ทันที</p>
                                    <p class="text-2xl font-black text-pink-400">฿28,450</p>
                                    <p class="text-xs text-blue-400 mt-1">Liquid</p>
                                </div>
                            </div>

                            {{-- Progress to Goal --}}
                            <div>
                                <div class="flex justify-between text-sm text-gray-300 mb-2">
                                    <span>เป้าหมายปีนี้: ฿500,000</span>
                                    <span class="font-bold text-purple-400">85.7%</span>
                                </div>
                                <div class="h-4 bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 rounded-full transition-all duration-1000"
                                         style="width: 85.7%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
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
        PROBLEM SECTION - ปัญหาที่นักลงทุนเจอ
    ================================================================ --}}
    <section id="problem" class="relative py-32 bg-gradient-to-b from-black to-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-20">
                <h2 class="text-4xl md:text-6xl font-black text-white mb-6">
                    คุณเคยรู้สึก<span class="text-red-400">แบบนี้</span>ไหม?
                </h2>
                <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                    ปัญหาที่นักลงทุนหน้าใหม่มักพบเจอ
                </p>
            </div>

            {{-- Problems Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">

                {{-- Problem 1 --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-red-500/20 rounded-3xl p-8 hover:border-red-500/50 transition-all duration-300">
                    <div class="text-6xl mb-4">😰</div>
                    <h3 class="text-2xl font-bold text-white mb-4">ไม่รู้จะเริ่มต้นอย่างไร</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>มีเงินเก็บน้อย กลัวเสี่ยง</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ไม่มีความรู้ด้านการลงทุน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>กลัวโดนหลอก โดนโกง</span>
                        </li>
                    </ul>
                </div>

                {{-- Problem 2 --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-red-500/20 rounded-3xl p-8 hover:border-red-500/50 transition-all duration-300">
                    <div class="text-6xl mb-4">😣</div>
                    <h3 class="text-2xl font-bold text-white mb-4">ซับซ้อนเกินไป</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ศัพท์เทคนิคเยอะ งง</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ต้องติดตามตลาดตลอดเวลา</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ไม่มีเวลาเรียนรู้</span>
                        </li>
                    </ul>
                </div>

                {{-- Problem 3 --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-red-500/20 rounded-3xl p-8 hover:border-red-500/50 transition-all duration-300">
                    <div class="text-6xl mb-4">😔</div>
                    <h3 class="text-2xl font-bold text-white mb-4">ผลตอบแทนไม่แน่นอน</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ธนาคารดอกเบี้ยต่ำ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>หุ้นขึ้นลงบ่อย เสี่ยงสูง</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>Crypto ผันผวนมาก</span>
                        </li>
                    </ul>
                </div>

                {{-- Problem 4 --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-red-500/20 rounded-3xl p-8 hover:border-red-500/50 transition-all duration-300">
                    <div class="text-6xl mb-4">😞</div>
                    <h3 class="text-2xl font-bold text-white mb-4">ไม่โปร่งใส</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ไม่รู้ว่าเงินไปไหน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ค่าธรรมเนียมซ่อนเร้น</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ถอนเงินยาก มีเงื่อนไข</span>
                        </li>
                    </ul>
                </div>

            </div>

            {{-- Transition --}}
            <div class="text-center">
                <div class="inline-block px-8 py-4 backdrop-blur-xl bg-gradient-to-r from-purple-600/20 to-pink-600/20 border-2 border-purple-500/30 rounded-2xl">
                    <p class="text-2xl text-white font-semibold">
                        แต่ถ้าเราบอกว่า... <span class="text-purple-400">มีวิธีที่ดีกว่า</span>
                    </p>
                </div>
            </div>

        </div>
    </section>

    {{-- ================================================================
        HOW IT WORKS SECTION - เราแก้ปัญหาอย่างไร
    ================================================================ --}}
    <section id="how-it-works" class="relative py-32 bg-gradient-to-b from-gray-900 to-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-20">
                <h2 class="text-5xl md:text-7xl font-black text-white mb-6">
                    เรา<span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">แก้ปัญหา</span>ทุกอย่าง
                </h2>
                <p class="text-2xl text-gray-400">
                    ด้วยระบบลงทุนที่ออกแบบมาให้<span class="text-purple-400 font-semibold">ใครก็ทำได้</span>
                </p>
            </div>

            {{-- Solutions Grid --}}
            <div class="space-y-24">

                {{-- Solution 1: ง่าย ไม่ซับซ้อน --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div class="space-y-6">
                        <div class="inline-block px-6 py-3 bg-green-600/20 border-2 border-green-500/30 rounded-full">
                            <span class="text-green-400 font-bold">✅ แก้ปัญหา 1</span>
                        </div>

                        <h3 class="text-4xl font-black text-white">ง่าย ไม่ซับซ้อน</h3>

                        <p class="text-xl text-gray-300 leading-relaxed">
                            ไม่ต้องมีความรู้ก่อน เริ่มต้นได้ใน<span class="text-purple-400 font-semibold">3 ขั้นตอน</span>
                        </p>

                        <ul class="space-y-4">
                            <li class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-purple-600 flex items-center justify-center text-white font-bold">
                                    1
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-white mb-1">ลงทะเบียนฟรี</h4>
                                    <p class="text-gray-400">ใช้เวลาแค่ 2 นาที ไม่มีค่าธรรมเนียม</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-purple-600 flex items-center justify-center text-white font-bold">
                                    2
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-white mb-1">เลือกแพ็กเกจ</h4>
                                    <p class="text-gray-400">เริ่มต้นแค่ 1,000 บาท มี 5 แพ็กเกจให้เลือก</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 rounded-full bg-purple-600 flex items-center justify-center text-white font-bold">
                                    3
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-white mb-1">รับผลตอบแทน</h4>
                                    <p class="text-gray-400">ระบบอัตโนมัติ ดูผลได้ทุกวัน</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8">
                        <div class="text-center mb-6">
                            <div class="text-8xl mb-4">🎯</div>
                            <h4 class="text-2xl font-bold text-white">กระบวนการง่าย 3 ขั้นตอน</h4>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-4 p-4 bg-purple-600/20 rounded-2xl border border-purple-500/30">
                                <i class="fas fa-user-plus text-3xl text-purple-400"></i>
                                <div class="flex-1">
                                    <p class="font-bold text-white">ลงทะเบียน</p>
                                    <p class="text-sm text-gray-400">2 นาที</p>
                                </div>
                                <i class="fas fa-check-circle text-2xl text-green-400"></i>
                            </div>

                            <div class="flex items-center justify-center text-purple-400">
                                <i class="fas fa-arrow-down text-2xl"></i>
                            </div>

                            <div class="flex items-center gap-4 p-4 bg-pink-600/20 rounded-2xl border border-pink-500/30">
                                <i class="fas fa-box text-3xl text-pink-400"></i>
                                <div class="flex-1">
                                    <p class="font-bold text-white">เลือกแพ็กเกจ</p>
                                    <p class="text-sm text-gray-400">เริ่ม ฿1,000</p>
                                </div>
                                <i class="fas fa-check-circle text-2xl text-green-400"></i>
                            </div>

                            <div class="flex items-center justify-center text-pink-400">
                                <i class="fas fa-arrow-down text-2xl"></i>
                            </div>

                            <div class="flex items-center gap-4 p-4 bg-green-600/20 rounded-2xl border border-green-500/30">
                                <i class="fas fa-chart-line text-3xl text-green-400"></i>
                                <div class="flex-1">
                                    <p class="font-bold text-white">รับผลตอบแทน</p>
                                    <p class="text-sm text-gray-400">24/7 อัตโนมัติ</p>
                                </div>
                                <i class="fas fa-infinity text-2xl text-green-400"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Solution 2: โปร่งใส ปลอดภัย --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div class="order-2 lg:order-1 backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8">
                        <h4 class="text-2xl font-bold text-white mb-6 text-center">
                            <i class="fas fa-shield-alt text-blue-400 mr-2"></i>
                            ระบบความปลอดภัย 5 ชั้น
                        </h4>

                        <div class="space-y-4">
                            <div class="flex items-center gap-4 p-4 bg-blue-600/10 rounded-xl border border-blue-500/20">
                                <i class="fas fa-lock text-2xl text-blue-400"></i>
                                <div>
                                    <p class="font-bold text-white">เข้ารหัส SSL 256-bit</p>
                                    <p class="text-sm text-gray-400">ข้อมูลปลอดภัย 100%</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 p-4 bg-purple-600/10 rounded-xl border border-purple-500/20">
                                <i class="fas fa-database text-2xl text-purple-400"></i>
                                <div>
                                    <p class="font-bold text-white">Blockchain Transparency</p>
                                    <p class="text-sm text-gray-400">ตรวจสอบได้ทุก Transaction</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 p-4 bg-green-600/10 rounded-xl border border-green-500/20">
                                <i class="fas fa-user-shield text-2xl text-green-400"></i>
                                <div>
                                    <p class="font-bold text-white">2FA Authentication</p>
                                    <p class="text-sm text-gray-400">ยืนยันตัวตน 2 ชั้น</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 p-4 bg-pink-600/10 rounded-xl border border-pink-500/20">
                                <i class="fas fa-file-contract text-2xl text-pink-400"></i>
                                <div>
                                    <p class="font-bold text-white">Smart Contract</p>
                                    <p class="text-sm text-gray-400">อัตโนมัติ ไม่มีการแทรกแซง</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 p-4 bg-orange-600/10 rounded-xl border border-orange-500/20">
                                <i class="fas fa-hands-helping text-2xl text-orange-400"></i>
                                <div>
                                    <p class="font-bold text-white">Insurance Fund</p>
                                    <p class="text-sm text-gray-400">กองทุนสำรองป้องกันความเสี่ยง</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="order-1 lg:order-2 space-y-6">
                        <div class="inline-block px-6 py-3 bg-green-600/20 border-2 border-green-500/30 rounded-full">
                            <span class="text-green-400 font-bold">✅ แก้ปัญหา 2</span>
                        </div>

                        <h3 class="text-4xl font-black text-white">โปร่งใส ปลอดภัย 100%</h3>

                        <p class="text-xl text-gray-300 leading-relaxed">
                            ทุก Transaction <span class="text-purple-400 font-semibold">บันทึกบน Blockchain</span> ตรวจสอบได้ทุกเมื่อ
                        </p>

                        <div class="backdrop-blur-xl bg-purple-900/20 border-l-4 border-purple-500 rounded-r-2xl p-6">
                            <p class="text-white font-semibold mb-3">💡 ทำไมต้องโปร่งใส?</p>
                            <ul class="space-y-2 text-gray-300">
                                <li>• คุณมีสิทธิ์รู้ว่าเงินคุณไปไหน</li>
                                <li>• ไม่มีค่าธรรมเนียมซ่อนเร้น</li>
                                <li>• ถอนเงินได้ทันที ไม่มีเงื่อนไข</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Solution 3: ผลตอบแทนมั่นคง --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <div class="space-y-6">
                        <div class="inline-block px-6 py-3 bg-green-600/20 border-2 border-green-500/30 rounded-full">
                            <span class="text-green-400 font-bold">✅ แก้ปัญหา 3</span>
                        </div>

                        <h3 class="text-4xl font-black text-white">ผลตอบแทนมั่นคง สูง</h3>

                        <p class="text-xl text-gray-300 leading-relaxed">
                            เฉลี่ย <span class="text-green-400 font-bold text-3xl">245% ROI/ปี</span> จากระบบหลากหลาย
                        </p>

                        <div class="space-y-4">
                            <div class="p-6 bg-gradient-to-r from-purple-600/20 to-pink-600/20 rounded-2xl border border-purple-500/30">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-lg font-bold text-white">MLM Commission</h4>
                                    <span class="px-4 py-1 bg-purple-600 rounded-full text-white text-sm font-bold">40%</span>
                                </div>
                                <p class="text-gray-300 text-sm">แนะนำเพื่อน รับคอมมิชชั่นต่อเนื่อง</p>
                            </div>

                            <div class="p-6 bg-gradient-to-r from-blue-600/20 to-cyan-600/20 rounded-2xl border border-blue-500/30">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-lg font-bold text-white">Staking Rewards</h4>
                                    <span class="px-4 py-1 bg-blue-600 rounded-full text-white text-sm font-bold">120%</span>
                                </div>
                                <p class="text-gray-300 text-sm">ฝาก TPIX Token รับดอกเบี้ย</p>
                            </div>

                            <div class="p-6 bg-gradient-to-r from-green-600/20 to-emerald-600/20 rounded-2xl border border-green-500/30">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-lg font-bold text-white">Marketplace Income</h4>
                                    <span class="px-4 py-1 bg-green-600 rounded-full text-white text-sm font-bold">85%</span>
                                </div>
                                <p class="text-gray-300 text-sm">ขายสินค้า/บริการ สร้างรายได้</p>
                            </div>
                        </div>
                    </div>

                    <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8">
                        <h4 class="text-2xl font-bold text-white mb-8 text-center">
                            <i class="fas fa-chart-bar text-green-400 mr-2"></i>
                            การเติบโตของพอร์ต
                        </h4>

                        {{-- Growth Chart Visualization --}}
                        <div class="space-y-6">
                            {{-- Year 1 --}}
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-gray-300">ปีที่ 1</span>
                                    <span class="font-bold text-green-400">฿100,000</span>
                                </div>
                                <div class="h-12 bg-gray-800 rounded-lg overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-end pr-4"
                                         style="width: 33%">
                                        <span class="text-white font-bold text-sm">+100%</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Year 2 --}}
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-gray-300">ปีที่ 2</span>
                                    <span class="font-bold text-green-400">฿245,000</span>
                                </div>
                                <div class="h-12 bg-gray-800 rounded-lg overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 flex items-center justify-end pr-4"
                                         style="width: 66%">
                                        <span class="text-white font-bold text-sm">+145%</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Year 3 --}}
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-gray-300">ปีที่ 3</span>
                                    <span class="font-bold text-green-400">฿428,650</span>
                                </div>
                                <div class="h-12 bg-gray-800 rounded-lg overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-end pr-4"
                                         style="width: 100%">
                                        <span class="text-white font-bold text-sm">+328%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 p-4 bg-green-600/10 border border-green-500/30 rounded-xl text-center">
                            <p class="text-sm text-gray-400 mb-1">เงินลงทุนเริ่มต้น</p>
                            <p class="text-3xl font-black text-white">฿50,000</p>
                            <p class="text-green-400 text-sm mt-1">กลายเป็น ฿428,650 ใน 3 ปี</p>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>

    {{-- ================================================================
        LIFE CONNECTION SECTION - เชื่อมโยงกับชีวิตจริง
    ================================================================ --}}
    <section class="relative py-32 bg-gradient-to-b from-black to-purple-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-20">
                <h2 class="text-5xl md:text-7xl font-black text-white mb-6">
                    มันเชื่อมกับ<span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">ชีวิตคุณ</span>อย่างไร?
                </h2>
                <p class="text-2xl text-gray-400">
                    ไม่ใช่แค่ตัวเลข แต่เป็น<span class="text-purple-400 font-semibold">อนาคตของคุณ</span>
                </p>
            </div>

            {{-- Life Connections Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

                {{-- 1. ครอบครัว --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:bg-white/10 hover:scale-105 transition-all duration-300">
                    <div class="text-7xl mb-6 text-center">👨‍👩‍👧‍👦</div>
                    <h3 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-red-400 mb-4 text-center">
                        ครอบครัว
                    </h3>
                    <ul class="space-y-4 text-gray-300">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-heart text-pink-400 mt-1"></i>
                            <span>สร้างรายได้เสริม พาครอบครัวเที่ยว</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-home text-pink-400 mt-1"></i>
                            <span>เก็บเงินซื้อบ้าน ทำบุญพ่อแม่</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-graduation-cap text-pink-400 mt-1"></i>
                            <span>เก็บค่าเรียนลูก ให้อนาคตที่ดี</span>
                        </li>
                    </ul>
                </div>

                {{-- 2. อนาคต --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:bg-white/10 hover:scale-105 transition-all duration-300">
                    <div class="text-7xl mb-6 text-center">🚀</div>
                    <h3 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-blue-400 mb-4 text-center">
                        อนาคต
                    </h3>
                    <ul class="space-y-4 text-gray-300">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-shield-alt text-purple-400 mt-1"></i>
                            <span>เตรียมเงินก้อนสำหรับเกษียณ</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-hospital text-purple-400 mt-1"></i>
                            <span>กองทุนสำรองฉุกเฉิน ไม่ต้องกังวล</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-chart-line text-purple-400 mt-1"></i>
                            <span>สร้างความมั่นคงทางการเงิน</span>
                        </li>
                    </ul>
                </div>

                {{-- 3. เวลา --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:bg-white/10 hover:scale-105 transition-all duration-300">
                    <div class="text-7xl mb-6 text-center">⏰</div>
                    <h3 class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400 mb-4 text-center">
                        เวลา
                    </h3>
                    <ul class="space-y-4 text-gray-300">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-clock text-blue-400 mt-1"></i>
                            <span>ระบบทำงานอัตโนมัติ 24/7</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-cocktail text-blue-400 mt-1"></i>
                            <span>มีเวลากับคนที่คุณรัก</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <i class="fas fa-bed text-blue-400 mt-1"></i>
                            <span>หลับสบาย เงินทำงานให้คุณ</span>
                        </li>
                    </ul>
                </div>

            </div>

            {{-- Real Life Story --}}
            <div class="mt-20 max-w-4xl mx-auto">
                <div class="backdrop-blur-xl bg-gradient-to-r from-purple-900/30 to-pink-900/30 border-2 border-purple-500/30 rounded-3xl p-12">
                    <div class="text-center mb-8">
                        <div class="text-6xl mb-4">💭</div>
                        <h3 class="text-3xl font-bold text-white">เรื่องจริงจากนักลงทุน</h3>
                    </div>

                    <blockquote class="text-xl text-gray-200 leading-relaxed mb-6 italic">
                        "ผมเริ่มลงทุนด้วยเงินแค่ 10,000 บาท ตอนแรกก็กลัว ไม่มั่นใจ แต่พอเห็นระบบโปร่งใส ดูผลได้ทุกวัน ผมเริ่มมั่นใจขึ้น
                        ตอนนี้ 1 ปีผ่านไป พอร์ตผมโต 245% แล้วครับ เอาเงินไปซื้อของขวัญวันเกิดลูกสาว พาครอบครัวเที่ยวทะเล ชีวิตดีขึ้นมากครับ"
                    </blockquote>

                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-3xl">
                            👨
                        </div>
                        <div>
                            <p class="font-bold text-white">คุณสมชาย ธุรกิจส่วนตัว</p>
                            <p class="text-sm text-gray-400">เริ่มลงทุน: ม.ค. 2024 | ROI: 245%</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ================================================================
        FINAL CTA SECTION
    ================================================================ --}}
    <section class="relative py-32 overflow-hidden bg-gradient-to-br from-purple-900 via-pink-900 to-orange-900">

        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">

            <h2 class="text-5xl md:text-7xl font-black mb-8 leading-tight">
                พร้อมเริ่มต้น<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">
                    ลงทุนเพื่ออนาคต
                </span>แล้วหรือยัง?
            </h2>

            <p class="text-2xl md:text-3xl mb-16 text-white/90 leading-relaxed">
                เริ่มต้นได้ง่ายๆ แค่ <span class="font-bold text-yellow-300">1,000 บาท</span><br>
                <span class="text-xl text-white/70">ลงทะเบียนฟรี ไม่มีค่าธรรมเนียมแอบแฝง</span>
            </p>

            {{-- CTA Button --}}
            <div class="mb-16">
                <a href="{{ route('register') }}"
                   class="group relative overflow-hidden inline-flex items-center gap-4 px-16 py-6 bg-white text-purple-900 font-black text-2xl rounded-2xl shadow-2xl hover:shadow-white/50 transform hover:scale-110 transition-all duration-300">
                    {{-- Shine Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-yellow-200/50 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>

                    <span class="relative z-10 flex items-center gap-3">
                        <i class="fas fa-rocket text-3xl"></i>
                        <span>เริ่มลงทุนวันนี้เลย!</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                    </span>
                </a>
            </div>

            {{-- Trust Badges --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                    <div class="text-4xl mb-2">🔒</div>
                    <p class="font-bold text-lg mb-1">ปลอดภัย 100%</p>
                    <p class="text-sm text-white/70">เข้ารหัส SSL + Blockchain</p>
                </div>
                <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                    <div class="text-4xl mb-2">⚡</div>
                    <p class="font-bold text-lg mb-1">ถอนได้ทันที</p>
                    <p class="text-sm text-white/70">ไม่มีเงื่อนไขซ่อนเร้น</p>
                </div>
                <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                    <div class="text-4xl mb-2">💎</div>
                    <p class="font-bold text-lg mb-1">ROI สูง 245%</p>
                    <p class="text-sm text-white/70">เฉลี่ยต่อปี</p>
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
 * Investors Page Manager
 *
 * จัดการ animations และ interactivity สำหรับหน้านักลงทุน
 */
function investorsPageManager() {
    return {
        // ----------------
        // LIFECYCLE
        // ----------------
        init() {
            console.log('💰 Investors Page initialized');
            this.initGSAPAnimations();
        },

        // ----------------
        // METHODS
        // ----------------

        /**
         * เริ่มต้น GSAP animations
         */
        initGSAPAnimations() {
            gsap.registerPlugin(ScrollTrigger);

            // Parallax effect
            gsap.to('.animate-float', {
                y: -30,
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
window.investorsPageManager = investorsPageManager;
</script>
@endpush
