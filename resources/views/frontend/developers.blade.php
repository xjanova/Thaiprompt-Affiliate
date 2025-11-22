{{--
/**
 * หน้าประตูที่ 2: นักพัฒนา - เปลี่ยน Code เป็นรายได้
 *
 * Storytelling Design จากมุมมองนักพัฒนา:
 * - ปัญหา: มี Skill แต่หาเงินยาก ขายผลงานไม่เป็น รายได้ไม่สม่ำเสมอ
 * - วิธีแก้: Marketplace พร้อม Revenue Share 70% Community แน่น
 * - Passive Income: ขายได้ต่อเนื่อง แม้ไม่ทำงาน
 * - พาไปสู่: การลงทะเบียน
 *
 * V3 Technologies:
 * - Tailwind CSS + Alpine.js + GSAP
 *
 * @version 1.0.0
 * @created 2025-11-22
 */
--}}

@extends('layouts.guest')

@section('title', $pageTitle ?? 'สำหรับนักพัฒนา - เปลี่ยน Code เป็นรายได้')

@push('styles')
<style>
@keyframes code-typing {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 1; }
}

.animate-code-typing {
    animation: code-typing 2s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

html { scroll-behavior: smooth; }
</style>
@endpush

@section('content')

<div x-data="developersPageManager()" x-init="init()" class="relative overflow-hidden">

    {{-- ================================================================
        HERO SECTION - สำหรับนักพัฒนา
    ================================================================ --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-blue-950 via-black to-cyan-950">

        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0" style="background-image:
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(6, 182, 212, 0.3) 0%, transparent 50%);">
            </div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">

            {{-- Breadcrumb --}}
            <div class="mb-8 flex items-center gap-3 text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-blue-400 transition-colors">
                    <i class="fas fa-home"></i> หน้าแรก
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-blue-400">🚪 ประตูที่ 2: นักพัฒนา</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

                {{-- Left: Content --}}
                <div class="space-y-8">
                    <div class="inline-flex items-center gap-3 px-6 py-3 backdrop-blur-xl bg-blue-600/20 border-2 border-blue-500/30 rounded-full">
                        <span class="text-4xl">🚪</span>
                        <span class="text-lg font-bold text-blue-400">ประตูที่ 2: นักพัฒนา</span>
                    </div>

                    <h1 class="text-5xl md:text-7xl font-black leading-tight">
                        <span class="block text-white mb-4">เปลี่ยน</span>
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400">
                            Code เป็นรายได้
                        </span>
                    </h1>

                    <p class="text-2xl text-gray-300 leading-relaxed">
                        ขายผลงานได้ทันที ได้ถึง <span class="text-blue-400 font-bold">70% Revenue Share</span><br>
                        <span class="text-cyan-400 font-semibold">Passive Income</span> ต่อเนื่อง แม้ไม่ทำงาน
                    </p>

                    {{-- Key Stats --}}
                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center backdrop-blur-xl bg-white/5 rounded-2xl p-4 border border-white/10">
                            <div class="text-3xl font-black text-blue-400 mb-1">70%</div>
                            <div class="text-xs text-gray-400">Revenue Share</div>
                        </div>
                        <div class="text-center backdrop-blur-xl bg-white/5 rounded-2xl p-4 border border-white/10">
                            <div class="text-3xl font-black text-cyan-400 mb-1">15K+</div>
                            <div class="text-xs text-gray-400">ลูกค้ารอซื้อ</div>
                        </div>
                        <div class="text-center backdrop-blur-xl bg-white/5 rounded-2xl p-4 border border-white/10">
                            <div class="text-3xl font-black text-green-400 mb-1">24/7</div>
                            <div class="text-xs text-gray-400">ขายต่อเนื่อง</div>
                        </div>
                    </div>

                    {{-- CTA Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}"
                           class="group inline-flex items-center justify-center gap-3 px-10 py-5 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold text-xl rounded-2xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                            <i class="fas fa-code"></i>
                            <span>เริ่มขายผลงาน</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </a>

                        <button @click="scrollToSection('marketplace')"
                                class="px-10 py-5 backdrop-blur-xl bg-white/10 border-2 border-white/20 text-white font-bold text-xl rounded-2xl hover:bg-white/20 transition-all duration-300">
                            <i class="fas fa-store mr-2"></i>
                            ดู Marketplace
                        </button>
                    </div>
                </div>

                {{-- Right: Code Editor Preview --}}
                <div class="animate-float">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-3xl blur-3xl opacity-30"></div>

                        {{-- Code Editor Card --}}
                        <div class="relative backdrop-blur-xl bg-gray-900 border-2 border-blue-500/30 rounded-3xl overflow-hidden shadow-2xl">
                            {{-- Window Controls --}}
                            <div class="flex items-center gap-2 px-4 py-3 bg-gray-800 border-b border-gray-700">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                <span class="ml-4 text-sm text-gray-400">my-awesome-plugin.js</span>
                            </div>

                            {{-- Code Content --}}
                            <div class="p-6 font-mono text-sm">
                                <code class="text-green-400 block">
                                    <span class="text-purple-400">const</span> <span class="text-blue-400">myPlugin</span> = {<br>
                                    &nbsp;&nbsp;name: <span class="text-orange-400">'Awesome Plugin'</span>,<br>
                                    &nbsp;&nbsp;version: <span class="text-yellow-400">'2.0.0'</span>,<br>
                                    &nbsp;&nbsp;sales: <span class="text-yellow-400">1,247</span>, <span class="text-gray-500">// 🔥 Best Seller!</span><br>
                                    &nbsp;&nbsp;revenue: <span class="text-orange-400">'฿348,160'</span>,<br>
                                    &nbsp;&nbsp;rating: <span class="text-yellow-400">4.9</span> <span class="text-gray-500">⭐⭐⭐⭐⭐</span><br>
                                    };
                                </code>

                                <div class="mt-6 p-4 bg-green-900/20 border border-green-500/30 rounded-lg">
                                    <p class="text-green-400 text-xs mb-2">💰 Your Earnings (70% Revenue Share):</p>
                                    <p class="text-white text-2xl font-bold">฿243,712</p>
                                    <p class="text-green-400 text-xs mt-1">↗ +32% this month</p>
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
        PROBLEM SECTION
    ================================================================ --}}
    <section id="problem" class="relative py-32 bg-gradient-to-b from-black to-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-20">
                <h2 class="text-4xl md:text-6xl font-black text-white mb-6">
                    นักพัฒนามักพบ<span class="text-red-400">ปัญหาเหล่านี้</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
                <div class="backdrop-blur-xl bg-white/5 border-2 border-red-500/20 rounded-3xl p-6 hover:border-red-500/50 transition-all">
                    <div class="text-5xl mb-4">😣</div>
                    <h3 class="text-xl font-bold text-white mb-3">หาลูกค้ายาก</h3>
                    <p class="text-gray-400 text-sm">Freelance แต่ไม่มีงาน ไม่รู้จะหาจากไหน</p>
                </div>

                <div class="backdrop-blur-xl bg-white/5 border-2 border-red-500/20 rounded-3xl p-6 hover:border-red-500/50 transition-all">
                    <div class="text-5xl mb-4">💸</div>
                    <h3 class="text-xl font-bold text-white mb-3">รายได้ไม่สม่ำเสมอ</h3>
                    <p class="text-gray-400 text-sm">เดือนนี้มีงาน เดือนหน้าว่าง</p>
                </div>

                <div class="backdrop-blur-xl bg-white/5 border-2 border-red-500/20 rounded-3xl p-6 hover:border-red-500/50 transition-all">
                    <div class="text-5xl mb-4">🤝</div>
                    <h3 class="text-xl font-bold text-white mb-3">ขายผลงานไม่เป็น</h3>
                    <p class="text-gray-400 text-sm">เขียนโค้ดเก่ง แต่ขายไม่เป็น</p>
                </div>

                <div class="backdrop-blur-xl bg-white/5 border-2 border-red-500/20 rounded-3xl p-6 hover:border-red-500/50 transition-all">
                    <div class="text-5xl mb-4">😓</div>
                    <h3 class="text-xl font-bold text-white mb-3">ทำงานคนเดียว</h3>
                    <p class="text-gray-400 text-sm">ไม่มี Community ไม่มีคนช่วย</p>
                </div>
            </div>

            <div class="text-center">
                <div class="inline-block px-8 py-4 backdrop-blur-xl bg-gradient-to-r from-blue-600/20 to-cyan-600/20 border-2 border-blue-500/30 rounded-2xl">
                    <p class="text-2xl text-white font-semibold">
                        เราแก้<span class="text-blue-400">ทุกปัญหา</span>ให้คุณ
                    </p>
                </div>
            </div>

        </div>
    </section>

    {{-- ================================================================
        MARKETPLACE SECTION
    ================================================================ --}}
    <section id="marketplace" class="relative py-32 bg-gradient-to-b from-gray-900 to-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-20">
                <h2 class="text-5xl md:text-7xl font-black text-white mb-6">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-cyan-400">Marketplace</span> พร้อมแล้ว
                </h2>
                <p class="text-2xl text-gray-400">
                    มีลูกค้า <span class="text-blue-400 font-semibold">15,000+ คน</span> รอซื้อผลงานคุณ
                </p>
            </div>

            {{-- What You Can Sell --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
                <div class="backdrop-blur-xl bg-white/5 border-2 border-blue-500/20 rounded-3xl p-8 hover:border-blue-500/50 hover:scale-105 transition-all">
                    <div class="text-6xl mb-4">🧩</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Plugins & Extensions</h3>
                    <ul class="space-y-2 text-gray-300 text-sm">
                        <li>• WordPress Plugins</li>
                        <li>• Chrome Extensions</li>
                        <li>• VS Code Extensions</li>
                        <li>• NPM Packages</li>
                    </ul>
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <p class="text-blue-400 font-bold">ราคาเฉลี่ย: ฿2,500</p>
                        <p class="text-green-400 text-sm">ขายได้ต่อเนื่อง</p>
                    </div>
                </div>

                <div class="backdrop-blur-xl bg-white/5 border-2 border-cyan-500/20 rounded-3xl p-8 hover:border-cyan-500/50 hover:scale-105 transition-all">
                    <div class="text-6xl mb-4">🎨</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Themes & Templates</h3>
                    <ul class="space-y-2 text-gray-300 text-sm">
                        <li>• WordPress Themes</li>
                        <li>• HTML Templates</li>
                        <li>• React Components</li>
                        <li>• Tailwind Templates</li>
                    </ul>
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <p class="text-cyan-400 font-bold">ราคาเฉลี่ย: ฿4,500</p>
                        <p class="text-green-400 text-sm">Best Seller!</p>
                    </div>
                </div>

                <div class="backdrop-blur-xl bg-white/5 border-2 border-purple-500/20 rounded-3xl p-8 hover:border-purple-500/50 hover:scale-105 transition-all">
                    <div class="text-6xl mb-4">⚙️</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Scripts & Tools</h3>
                    <ul class="space-y-2 text-gray-300 text-sm">
                        <li>• Automation Scripts</li>
                        <li>• CLI Tools</li>
                        <li>• APIs & Webhooks</li>
                        <li>• Boilerplates</li>
                    </ul>
                    <div class="mt-6 pt-6 border-t border-white/10">
                        <p class="text-purple-400 font-bold">ราคาเฉลี่ย: ฿1,800</p>
                        <p class="text-green-400 text-sm">High Demand</p>
                    </div>
                </div>
            </div>

            {{-- Revenue Share --}}
            <div class="max-w-4xl mx-auto backdrop-blur-xl bg-gradient-to-r from-blue-900/30 to-cyan-900/30 border-2 border-blue-500/30 rounded-3xl p-12">
                <div class="text-center mb-8">
                    <h3 class="text-4xl font-black text-white mb-4">
                        ได้ถึง <span class="text-green-400">70%</span> Revenue Share
                    </h3>
                    <p class="text-xl text-gray-300">สูงที่สุดในตลาด!</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Ours --}}
                    <div class="p-6 bg-green-600/20 border-2 border-green-500/50 rounded-2xl">
                        <div class="text-center mb-4">
                            <div class="text-4xl mb-2">✅</div>
                            <h4 class="text-2xl font-bold text-white">Thaiprompt</h4>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-gray-300 text-sm mb-2">ขายได้ ฿10,000</p>
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-12 bg-gray-800 rounded-lg overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500 flex items-center justify-center text-white font-bold"
                                             style="width: 70%">
                                            ฿7,000
                                        </div>
                                    </div>
                                    <span class="text-green-400 font-bold">70%</span>
                                </div>
                            </div>

                            <ul class="space-y-2 text-sm text-gray-300">
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-400"></i>
                                    <span>ไม่มีค่าธรรมเนียมแอบแฝง</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-400"></i>
                                    <span>รับเงินทันที ไม่ต้องรอ</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-check text-green-400"></i>
                                    <span>Community Support ฟรี</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    {{-- Others --}}
                    <div class="p-6 bg-red-600/10 border-2 border-red-500/20 rounded-2xl opacity-60">
                        <div class="text-center mb-4">
                            <div class="text-4xl mb-2">❌</div>
                            <h4 class="text-2xl font-bold text-white">แพลตฟอร์มอื่น</h4>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-gray-300 text-sm mb-2">ขายได้ ฿10,000</p>
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-12 bg-gray-800 rounded-lg overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-red-500 to-orange-500 flex items-center justify-center text-white font-bold"
                                             style="width: 30%">
                                            ฿3,000
                                        </div>
                                    </div>
                                    <span class="text-red-400 font-bold">30%</span>
                                </div>
                            </div>

                            <ul class="space-y-2 text-sm text-gray-400 line-through">
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-times text-red-400"></i>
                                    <span>มีค่าธรรมเนียมซ่อนเร้น</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-times text-red-400"></i>
                                    <span>รอรับเงิน 30-60 วัน</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <i class="fas fa-times text-red-400"></i>
                                    <span>ต้องจ่ายค่า Support</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ================================================================
        COMMUNITY SECTION
    ================================================================ --}}
    <section class="relative py-32 bg-gradient-to-b from-black to-blue-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-20">
                <h2 class="text-5xl md:text-7xl font-black text-white mb-6">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">Community</span> นักพัฒนา
                </h2>
                <p class="text-2xl text-gray-400">
                    ไม่ต้องทำคนเดียว มี<span class="text-purple-400 font-semibold">เพื่อนร่วมทาง</span>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 text-center">
                    <div class="text-6xl mb-4">💬</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Discord Community</h3>
                    <p class="text-gray-300 mb-6">แลกเปลี่ยนความรู้ แชร์ประสบการณ์</p>
                    <div class="inline-block px-6 py-3 bg-purple-600/20 border border-purple-500/30 rounded-full">
                        <p class="text-purple-400 font-bold">2,400+ สมาชิก</p>
                    </div>
                </div>

                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 text-center">
                    <div class="text-6xl mb-4">📚</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Knowledge Base</h3>
                    <p class="text-gray-300 mb-6">เอกสาร Tutorial แนะนำทุกอย่าง</p>
                    <div class="inline-block px-6 py-3 bg-blue-600/20 border border-blue-500/30 rounded-full">
                        <p class="text-blue-400 font-bold">500+ บทความ</p>
                    </div>
                </div>

                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 text-center">
                    <div class="text-6xl mb-4">🎓</div>
                    <h3 class="text-2xl font-bold text-white mb-4">Workshop & Meetup</h3>
                    <p class="text-gray-300 mb-6">งาน Offline พบปะกันจริง</p>
                    <div class="inline-block px-6 py-3 bg-green-600/20 border border-green-500/30 rounded-full">
                        <p class="text-green-400 font-bold">ทุกเดือน</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ================================================================
        FINAL CTA
    ================================================================ --}}
    <section class="relative py-32 overflow-hidden bg-gradient-to-br from-blue-900 via-cyan-900 to-purple-900">
        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">

            <h2 class="text-5xl md:text-7xl font-black mb-8 leading-tight">
                พร้อมเปลี่ยน<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">
                    Code เป็นรายได้
                </span>แล้วหรือยัง?
            </h2>

            <p class="text-2xl md:text-3xl mb-16 leading-relaxed">
                เริ่มต้นฟรี ไม่มีค่าธรรมเนียม<br>
                <span class="text-xl text-white/70">ขายได้ ถึงได้เงิน</span>
            </p>

            <div class="mb-16">
                <a href="{{ route('register') }}"
                   class="group relative overflow-hidden inline-flex items-center gap-4 px-16 py-6 bg-white text-blue-900 font-black text-2xl rounded-2xl shadow-2xl hover:shadow-white/50 transform hover:scale-110 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-yellow-200/50 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                    <span class="relative z-10 flex items-center gap-3">
                        <i class="fas fa-code text-3xl"></i>
                        <span>เริ่มขายผลงานเลย!</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                    </span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                    <div class="text-4xl mb-2">💰</div>
                    <p class="font-bold text-lg mb-1">70% Revenue Share</p>
                    <p class="text-sm text-white/70">สูงสุดในตลาด</p>
                </div>
                <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                    <div class="text-4xl mb-2">👥</div>
                    <p class="font-bold text-lg mb-1">15K+ ลูกค้า</p>
                    <p class="text-sm text-white/70">รอซื้อผลงาน</p>
                </div>
                <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                    <div class="text-4xl mb-2">🚀</div>
                    <p class="font-bold text-lg mb-1">Passive Income</p>
                    <p class="text-sm text-white/70">ขายต่อเนื่อง</p>
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
function developersPageManager() {
    return {
        init() {
            console.log('💻 Developers Page initialized');
            this.initGSAPAnimations();
        },

        initGSAPAnimations() {
            gsap.registerPlugin(ScrollTrigger);

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

window.developersPageManager = developersPageManager;
</script>
@endpush
