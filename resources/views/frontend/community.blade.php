{{--
/**
 * หน้าประตูที่ 3: ชุมชน - เติบโตไปด้วยกัน ไม่ทิ้งใครไว้ข้างหลัง
 *
 * Storytelling Design จากมุมมองชุมชน:
 * - ปัญหา: ทำธุรกิจคนเดียวลำบาก ไม่มีทีม ไม่มีเครือข่าย
 * - วิธีแก้: Affiliate System, Knowledge Sharing, Community Events
 * - Give Back: ช่วยเหลือสังคม ส่วนหนึ่งของกำไร
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

@section('title', $pageTitle ?? 'สำหรับชุมชน - เติบโตไปด้วยกัน')

@push('styles')
<style>
@keyframes network-pulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 1; transform: scale(1.1); }
}

.animate-network-pulse {
    animation: network-pulse 3s ease-in-out infinite;
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

<div x-data="communityPageManager()" x-init="init()" class="relative overflow-hidden">

    {{-- ================================================================
        HERO SECTION - สำหรับชุมชน
    ================================================================ --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-green-950 via-black to-emerald-950">

        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0" style="background-image:
                radial-gradient(circle at 20% 50%, rgba(16, 185, 129, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 50%, rgba(5, 150, 105, 0.3) 0%, transparent 50%);">
            </div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">

            {{-- Breadcrumb --}}
            <div class="mb-8 flex items-center gap-3 text-gray-400">
                <a href="{{ route('home') }}" class="hover:text-green-400 transition-colors">
                    <i class="fas fa-home"></i> หน้าแรก
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-green-400">🚪 ประตูที่ 3: ชุมชน</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

                {{-- Left: Content --}}
                <div class="space-y-8">
                    <div class="inline-flex items-center gap-3 px-6 py-3 backdrop-blur-xl bg-green-600/20 border-2 border-green-500/30 rounded-full">
                        <span class="text-4xl">🚪</span>
                        <span class="text-lg font-bold text-green-400">ประตูที่ 3: ชุมชน</span>
                    </div>

                    <h1 class="text-5xl md:text-7xl font-black leading-tight">
                        <span class="block text-white mb-4">เติบโตไปด้วยกัน</span>
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-400">
                            ไม่ทิ้งใครไว้ข้างหลัง
                        </span>
                    </h1>

                    <p class="text-2xl text-gray-300 leading-relaxed">
                        แชร์ความรู้ สร้างเครือข่าย ช่วยเหลือกัน<br>
                        <span class="text-green-400 font-semibold">Together We Grow</span> 🌱
                    </p>

                    {{-- Key Stats --}}
                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center backdrop-blur-xl bg-white/5 rounded-2xl p-4 border border-white/10">
                            <div class="text-3xl font-black text-green-400 mb-1">12K+</div>
                            <div class="text-xs text-gray-400">สมาชิก</div>
                        </div>
                        <div class="text-center backdrop-blur-xl bg-white/5 rounded-2xl p-4 border border-white/10">
                            <div class="text-3xl font-black text-emerald-400 mb-1">฿2.4M</div>
                            <div class="text-xs text-gray-400">Give Back</div>
                        </div>
                        <div class="text-center backdrop-blur-xl bg-white/5 rounded-2xl p-4 border border-white/10">
                            <div class="text-3xl font-black text-teal-400 mb-1">48</div>
                            <div class="text-xs text-gray-400">Events/ปี</div>
                        </div>
                    </div>

                    {{-- CTA Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}"
                           class="group inline-flex items-center justify-center gap-3 px-10 py-5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold text-xl rounded-2xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                            <i class="fas fa-users"></i>
                            <span>เข้าร่วมชุมชน</span>
                            <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                        </a>

                        <button @click="scrollToSection('affiliate')"
                                class="px-10 py-5 backdrop-blur-xl bg-white/10 border-2 border-white/20 text-white font-bold text-xl rounded-2xl hover:bg-white/20 transition-all duration-300">
                            <i class="fas fa-handshake mr-2"></i>
                            Affiliate System
                        </button>
                    </div>
                </div>

                {{-- Right: Network Visualization --}}
                <div class="animate-float">
                    <div class="relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-500 rounded-3xl blur-3xl opacity-30"></div>

                        {{-- Network Card --}}
                        <div class="relative backdrop-blur-xl bg-white/10 border-2 border-green-500/30 rounded-3xl p-12 shadow-2xl">
                            <h3 class="text-2xl font-bold text-white mb-8 text-center">
                                <span class="text-4xl mb-2 block">🌍</span>
                                เครือข่ายชุมชน
                            </h3>

                            {{-- Network Nodes --}}
                            <div class="relative h-64">
                                {{-- Center (You) --}}
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-10">
                                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-green-400 to-emerald-400 flex items-center justify-center text-white text-2xl font-bold shadow-lg animate-pulse">
                                        คุณ
                                    </div>
                                </div>

                                {{-- Connected Nodes --}}
                                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-12 h-12 rounded-full bg-green-500/60 flex items-center justify-center animate-network-pulse">
                                    👤
                                </div>
                                <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-12 h-12 rounded-full bg-green-500/60 flex items-center justify-center animate-network-pulse" style="animation-delay: 0.5s;">
                                    👤
                                </div>
                                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-green-500/60 flex items-center justify-center animate-network-pulse" style="animation-delay: 1s;">
                                    👤
                                </div>
                                <div class="absolute right-0 top-1/2 -translate-y-1/2 w-12 h-12 rounded-full bg-green-500/60 flex items-center justify-center animate-network-pulse" style="animation-delay: 1.5s;">
                                    👤
                                </div>

                                {{-- Corner Nodes --}}
                                <div class="absolute top-8 left-8 w-10 h-10 rounded-full bg-emerald-500/40 flex items-center justify-center animate-network-pulse" style="animation-delay: 2s;">
                                    👤
                                </div>
                                <div class="absolute top-8 right-8 w-10 h-10 rounded-full bg-emerald-500/40 flex items-center justify-center animate-network-pulse" style="animation-delay: 2.5s;">
                                    👤
                                </div>
                                <div class="absolute bottom-8 left-8 w-10 h-10 rounded-full bg-emerald-500/40 flex items-center justify-center animate-network-pulse" style="animation-delay: 3s;">
                                    👤
                                </div>
                                <div class="absolute bottom-8 right-8 w-10 h-10 rounded-full bg-emerald-500/40 flex items-center justify-center animate-network-pulse" style="animation-delay: 3.5s;">
                                    👤
                                </div>

                                {{-- Connection Lines (SVG) --}}
                                <svg class="absolute inset-0 w-full h-full -z-10" xmlns="http://www.w3.org/2000/svg">
                                    <line x1="50%" y1="50%" x2="50%" y2="0%" stroke="rgba(16, 185, 129, 0.3)" stroke-width="2"/>
                                    <line x1="50%" y1="50%" x2="50%" y2="100%" stroke="rgba(16, 185, 129, 0.3)" stroke-width="2"/>
                                    <line x1="50%" y1="50%" x2="0%" y2="50%" stroke="rgba(16, 185, 129, 0.3)" stroke-width="2"/>
                                    <line x1="50%" y1="50%" x2="100%" y2="50%" stroke="rgba(16, 185, 129, 0.3)" stroke-width="2"/>
                                </svg>
                            </div>

                            {{-- Stats --}}
                            <div class="grid grid-cols-2 gap-4 mt-8">
                                <div class="backdrop-blur-xl bg-green-500/20 rounded-2xl p-4 border border-green-500/30 text-center">
                                    <p class="text-3xl font-black text-green-400 mb-1">12,450+</p>
                                    <p class="text-xs text-gray-300">สมาชิกในชุมชน</p>
                                </div>
                                <div class="backdrop-blur-xl bg-emerald-500/20 rounded-2xl p-4 border border-emerald-500/30 text-center">
                                    <p class="text-3xl font-black text-emerald-400 mb-1">98%</p>
                                    <p class="text-xs text-gray-300">Satisfaction</p>
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
                    ทำคนเดียว<span class="text-red-400">ลำบาก</span>ใช่ไหม?
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
                <div class="backdrop-blur-xl bg-white/5 border-2 border-red-500/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4">😔</div>
                    <h3 class="text-2xl font-bold text-white mb-4">ไม่มีทีม ไม่มีเครือข่าย</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ต้องทำทุกอย่างเอง เหนื่อย ท้อ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ไม่มีคนช่วย ไม่มีคนถาม</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>รู้สึกโดดเดี่ยว อยู่คนเดียว</span>
                        </li>
                    </ul>
                </div>

                <div class="backdrop-blur-xl bg-white/5 border-2 border-red-500/20 rounded-3xl p-8">
                    <div class="text-6xl mb-4">💔</div>
                    <h3 class="text-2xl font-bold text-white mb-4">ไม่มีคนแนะนำ</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ไม่รู้จะเรียนรู้จากไหน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ทำผิดพลาด ไม่มีคนบอก</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-times-circle text-red-400 mt-1"></i>
                            <span>ต้อง Trial & Error เสียเวลา</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="text-center">
                <div class="inline-block px-8 py-4 backdrop-blur-xl bg-gradient-to-r from-green-600/20 to-emerald-600/20 border-2 border-green-500/30 rounded-2xl">
                    <p class="text-2xl text-white font-semibold">
                        เราสร้าง<span class="text-green-400">ชุมชน</span>ที่คุณไม่โดดเดี่ยว
                    </p>
                </div>
            </div>

        </div>
    </section>

    {{-- ================================================================
        AFFILIATE SYSTEM SECTION
    ================================================================ --}}
    <section id="affiliate" class="relative py-32 bg-gradient-to-b from-gray-900 to-black">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-20">
                <h2 class="text-5xl md:text-7xl font-black text-white mb-6">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-400">Affiliate System</span>
                </h2>
                <p class="text-2xl text-gray-400">
                    แนะนำเพื่อน <span class="text-green-400 font-semibold">ได้ประโยชน์ทั้งสองฝ่าย</span>
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-20">
                <div class="space-y-6">
                    <h3 class="text-4xl font-black text-white">ทำไมต้อง Affiliate?</h3>

                    <div class="space-y-4">
                        <div class="backdrop-blur-xl bg-green-600/10 border-l-4 border-green-500 rounded-r-2xl p-6">
                            <h4 class="text-xl font-bold text-white mb-2 flex items-center gap-3">
                                <i class="fas fa-handshake text-green-400"></i>
                                <span>ช่วยเพื่อน ได้ผลตอบแทน</span>
                            </h4>
                            <p class="text-gray-300">แนะนำเพื่อนมาใช้ คุณได้คอมมิชชั่น เพื่อนก็ได้ใช้ระบบดีๆ</p>
                        </div>

                        <div class="backdrop-blur-xl bg-blue-600/10 border-l-4 border-blue-500 rounded-r-2xl p-6">
                            <h4 class="text-xl font-bold text-white mb-2 flex items-center gap-3">
                                <i class="fas fa-infinity text-blue-400"></i>
                                <span>Passive Income ต่อเนื่อง</span>
                            </h4>
                            <p class="text-gray-300">รับคอมมิชชั่นต่อเนื่อง จากยอดขายของ Downline</p>
                        </div>

                        <div class="backdrop-blur-xl bg-purple-600/10 border-l-4 border-purple-500 rounded-r-2xl p-6">
                            <h4 class="text-xl font-bold text-white mb-2 flex items-center gap-3">
                                <i class="fas fa-sitemap text-purple-400"></i>
                                <span>สร้างทีม สร้างเครือข่าย</span>
                            </h4>
                            <p class="text-gray-300">ไม่ต้องทำคนเดียว มีทีมช่วย มีเครือข่าย</p>
                        </div>
                    </div>
                </div>

                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8">
                    <h4 class="text-2xl font-bold text-white mb-6 text-center">
                        <i class="fas fa-money-bill-wave text-green-400 mr-2"></i>
                        ตัวอย่างรายได้
                    </h4>

                    <div class="space-y-6">
                        {{-- Level 1 --}}
                        <div class="p-6 bg-gradient-to-r from-green-600/20 to-emerald-600/20 rounded-2xl border border-green-500/30">
                            <div class="flex items-center justify-between mb-3">
                                <h5 class="text-lg font-bold text-white">Level 1 (Direct)</h5>
                                <span class="px-4 py-1 bg-green-600 rounded-full text-white text-sm font-bold">40%</span>
                            </div>
                            <p class="text-gray-300 text-sm mb-4">แนะนำเพื่อน 10 คน ซื้อ @฿1,000</p>
                            <div class="flex items-center justify-between">
                                <p class="text-green-400 text-xl font-bold">คุณได้: ฿4,000</p>
                                <p class="text-sm text-gray-400">(10 × ฿1,000 × 40%)</p>
                            </div>
                        </div>

                        {{-- Level 2-3 --}}
                        <div class="p-6 bg-gradient-to-r from-blue-600/20 to-cyan-600/20 rounded-2xl border border-blue-500/30">
                            <div class="flex items-center justify-between mb-3">
                                <h5 class="text-lg font-bold text-white">Level 2-3 (Indirect)</h5>
                                <span class="px-4 py-1 bg-blue-600 rounded-full text-white text-sm font-bold">20%</span>
                            </div>
                            <p class="text-gray-300 text-sm mb-4">Downline ของคุณแนะนำต่อ</p>
                            <div class="flex items-center justify-between">
                                <p class="text-blue-400 text-xl font-bold">คุณได้: ฿2,000+</p>
                                <p class="text-sm text-gray-400">ต่อเนื่อง</p>
                            </div>
                        </div>

                        {{-- Total --}}
                        <div class="pt-6 border-t border-white/20">
                            <div class="flex items-center justify-between">
                                <p class="text-white font-bold text-xl">รวมเดือนแรก:</p>
                                <p class="text-green-400 font-black text-3xl">฿6,000+</p>
                            </div>
                            <p class="text-green-400 text-sm mt-2 text-center">และจะเพิ่มขึ้นเรื่อยๆ ทุกเดือน!</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- ================================================================
        COMMUNITY FEATURES SECTION
    ================================================================ --}}
    <section class="relative py-32 bg-gradient-to-b from-black to-green-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-20">
                <h2 class="text-5xl md:text-7xl font-black text-white mb-6">
                    ชุมชนที่<span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-pink-400">ใส่ใจกัน</span>
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                {{-- Feature 1 --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:scale-105 transition-all">
                    <div class="text-6xl mb-6 text-center">📚</div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Knowledge Sharing</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li>• Wiki & Documentation</li>
                        <li>• Video Tutorials</li>
                        <li>• Case Studies</li>
                        <li>• Best Practices</li>
                    </ul>
                </div>

                {{-- Feature 2 --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:scale-105 transition-all">
                    <div class="text-6xl mb-6 text-center">🎓</div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Events & Workshops</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li>• Monthly Meetup</li>
                        <li>• Hands-on Workshop</li>
                        <li>• Networking Night</li>
                        <li>• Online Webinar</li>
                    </ul>
                </div>

                {{-- Feature 3 --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:scale-105 transition-all">
                    <div class="text-6xl mb-6 text-center">💬</div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Community Chat</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li>• Discord Server</li>
                        <li>• LINE Group</li>
                        <li>• Facebook Community</li>
                        <li>• Support 24/7</li>
                    </ul>
                </div>

                {{-- Feature 4 --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:scale-105 transition-all">
                    <div class="text-6xl mb-6 text-center">🏆</div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Rewards & Recognition</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li>• Top Performer Award</li>
                        <li>• Monthly Challenges</li>
                        <li>• Special Bonuses</li>
                        <li>• VIP Benefits</li>
                    </ul>
                </div>

                {{-- Feature 5 --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:scale-105 transition-all">
                    <div class="text-6xl mb-6 text-center">🤝</div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Mentorship Program</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li>• 1-on-1 Coaching</li>
                        <li>• Success Strategy</li>
                        <li>• Business Planning</li>
                        <li>• Problem Solving</li>
                    </ul>
                </div>

                {{-- Feature 6 --}}
                <div class="backdrop-blur-xl bg-white/5 border-2 border-white/10 rounded-3xl p-8 hover:scale-105 transition-all">
                    <div class="text-6xl mb-6 text-center">❤️</div>
                    <h3 class="text-2xl font-bold text-white mb-4 text-center">Give Back</h3>
                    <ul class="space-y-3 text-gray-300">
                        <li>• ช่วยเหลือสังคม</li>
                        <li>• โครงการการกุศล</li>
                        <li>• ทุนการศึกษา</li>
                        <li>• บริจาค 5% ของกำไร</li>
                    </ul>
                </div>

            </div>

        </div>
    </section>

    {{-- ================================================================
        FINAL CTA
    ================================================================ --}}
    <section class="relative py-32 overflow-hidden bg-gradient-to-br from-green-900 via-emerald-900 to-teal-900">
        <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">

            <h2 class="text-5xl md:text-7xl font-black mb-8 leading-tight">
                พร้อมเติบโต<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 to-orange-300">
                    ไปด้วยกัน
                </span>หรือยัง?
            </h2>

            <p class="text-2xl md:text-3xl mb-16 leading-relaxed">
                เข้าร่วม<span class="font-bold text-yellow-300">12,000+ คน</span>ที่เปลี่ยนชีวิต<br>
                <span class="text-xl text-white/70">ลงทะเบียนฟรี ไม่มีค่าใช้จ่าย</span>
            </p>

            <div class="mb-16">
                <a href="{{ route('register') }}"
                   class="group relative overflow-hidden inline-flex items-center gap-4 px-16 py-6 bg-white text-green-900 font-black text-2xl rounded-2xl shadow-2xl hover:shadow-white/50 transform hover:scale-110 transition-all duration-300">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-yellow-200/50 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                    <span class="relative z-10 flex items-center gap-3">
                        <i class="fas fa-users text-3xl"></i>
                        <span>เข้าร่วมชุมชนเลย!</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                    </span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                    <div class="text-4xl mb-2">🌱</div>
                    <p class="font-bold text-lg mb-1">Together We Grow</p>
                    <p class="text-sm text-white/70">เติบโตไปด้วยกัน</p>
                </div>
                <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                    <div class="text-4xl mb-2">💚</div>
                    <p class="font-bold text-lg mb-1">12K+ สมาชิก</p>
                    <p class="text-sm text-white/70">พร้อมต้อนรับคุณ</p>
                </div>
                <div class="backdrop-blur-xl bg-white/10 rounded-2xl p-6 border border-white/20">
                    <div class="text-4xl mb-2">✨</div>
                    <p class="font-bold text-lg mb-1">ฟรี 100%</p>
                    <p class="text-sm text-white/70">ไม่มีค่าธรรมเนียม</p>
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
function communityPageManager() {
    return {
        init() {
            console.log('🌍 Community Page initialized');
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

window.communityPageManager = communityPageManager;
</script>
@endpush
