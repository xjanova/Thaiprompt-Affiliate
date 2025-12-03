{{--
/**
 * หน้าแรกใหม่ - Professional & Trustworthy Design
 *
 * ออกแบบใหม่ทั้งหมดให้มีความน่าเชื่อถือ มืออาชีพ:
 * - สีสัน: Deep Blue + Purple + Gold (น่าเชื่อถือ)
 * - Layout: Clean, Modern, Enterprise-grade
 * - ข้อมูลตรงตามปัจจุบันของโครงการ
 *
 * V3 Technologies:
 * - Tailwind CSS (utility-first)
 * - Alpine.js (reactive)
 * - Smooth animations
 *
 * @version 1.0.0
 * @author Thaiprompt Team
 * @created 2025-12-03
 */
--}}

@extends('layouts.landing')

@section('title', 'TP-Affiliate Pro - ระบบ Affiliate Marketing ระดับ Enterprise')

@section('meta_description', 'TP-Affiliate Pro - แพลตฟอร์ม Affiliate Marketing ระดับ Enterprise ที่ครบครันที่สุด พร้อมระบบ AI, Blockchain, E-Commerce และ 20+ ระบบรองรับธุรกิจของคุณ')

@php
    // ดึงข้อมูลจากระบบ
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate Pro');
    $systemLogo = \App\Models\Setting::get('logo');

    // ดึง version จาก package.json
    $version = '3.310.0';
    $packageJsonPath = base_path('package.json');
    if (file_exists($packageJsonPath)) {
        $packageJson = json_decode(file_get_contents($packageJsonPath), true);
        if (isset($packageJson['version'])) {
            $version = $packageJson['version'];
        }
    }

    // สถิติโครงการ
    $stats = [
        'users' => \App\Models\User::count(),
        'affiliates' => \App\Models\MlmMember::count(),
        'transactions' => \App\Models\WalletTransaction::count(),
        'systems' => 20, // 20+ integrated systems
    ];
@endphp

@section('content')

{{-- ================================================================
    HERO SECTION - Professional & Trustworthy
================================================================ --}}
<section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-20">
    {{-- Background Gradient --}}
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-blue-950 to-purple-950">
        {{-- Grid Pattern --}}
        <div class="absolute inset-0 opacity-[0.03]"
             style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px),
                                      linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px);
                    background-size: 60px 60px;">
        </div>

        {{-- Gradient Orbs --}}
        <div class="absolute top-1/4 -left-32 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 -right-32 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-blue-900/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            {{-- Left Content --}}
            <div class="text-center lg:text-left animate-fade-in-up">
                {{-- Badge --}}
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 border border-white/20 backdrop-blur-sm mb-8">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    <span class="text-sm font-medium text-slate-300">Enterprise-Grade Solution</span>
                    <span class="text-xs text-slate-400">v{{ $version }}</span>
                </div>

                {{-- Headline --}}
                <h1 class="text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-bold text-white leading-tight mb-6">
                    <span class="block">ระบบ Affiliate</span>
                    <span class="block gradient-text-secondary">ระดับ Enterprise</span>
                    <span class="block text-2xl md:text-3xl lg:text-4xl font-normal text-slate-300 mt-4">
                        ที่ครบครันที่สุดในประเทศไทย
                    </span>
                </h1>

                {{-- Description --}}
                <p class="text-lg lg:text-xl text-slate-400 leading-relaxed mb-10 max-w-xl mx-auto lg:mx-0">
                    แพลตฟอร์ม All-in-One สำหรับธุรกิจ Affiliate, MLM, E-Commerce
                    พร้อมระบบ AI, Blockchain และ 20+ ระบบรองรับทุกความต้องการ
                </p>

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-10">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 btn-accent font-bold text-lg rounded-xl shadow-lg shadow-amber-500/20 hover:shadow-amber-500/30">
                        <i class="fas fa-rocket"></i>
                        เริ่มต้นใช้งานฟรี
                    </a>
                    <a href="#features"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-lg rounded-xl transition-all">
                        <i class="fas fa-play-circle"></i>
                        ดูรายละเอียด
                    </a>
                </div>

                {{-- Trust Indicators --}}
                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-6 text-sm text-slate-400">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-shield-alt text-green-400"></i>
                        <span>ปลอดภัย 100%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-headset text-blue-400"></i>
                        <span>Support 24/7</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-certificate text-amber-400"></i>
                        <span>มาตรฐานสากล</span>
                    </div>
                </div>
            </div>

            {{-- Right - Hero Visual --}}
            <div class="relative animate-fade-in-up" style="animation-delay: 0.2s;">
                <div class="relative">
                    {{-- Main Card --}}
                    <div class="relative bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-xl border border-white/20 rounded-3xl p-8 lg:p-10 shadow-2xl animate-float">
                        {{-- Logo --}}
                        <div class="flex items-center justify-center mb-8">
                            @if($systemLogo)
                                <img src="{{ asset($systemLogo) }}"
                                     alt="{{ $appName }}"
                                     class="h-24 lg:h-32 w-auto drop-shadow-2xl">
                            @else
                                <div class="w-24 h-24 lg:w-32 lg:h-32 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-500 rounded-3xl flex items-center justify-center shadow-2xl">
                                    <span class="text-white font-black text-4xl lg:text-5xl">TP</span>
                                </div>
                            @endif
                        </div>

                        {{-- App Name --}}
                        <h2 class="text-2xl lg:text-3xl font-bold text-white text-center mb-2">
                            {{ $appName }}
                        </h2>
                        <p class="text-slate-400 text-center mb-8">
                            Enterprise Affiliate Platform
                        </p>

                        {{-- Quick Stats --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/5 rounded-xl p-4 text-center">
                                <div class="text-2xl font-bold text-white">{{ number_format($stats['users']) }}+</div>
                                <div class="text-sm text-slate-400">ผู้ใช้งาน</div>
                            </div>
                            <div class="bg-white/5 rounded-xl p-4 text-center">
                                <div class="text-2xl font-bold text-white">{{ $stats['systems'] }}+</div>
                                <div class="text-sm text-slate-400">ระบบรองรับ</div>
                            </div>
                            <div class="bg-white/5 rounded-xl p-4 text-center">
                                <div class="text-2xl font-bold text-white">99.9%</div>
                                <div class="text-sm text-slate-400">Uptime</div>
                            </div>
                            <div class="bg-white/5 rounded-xl p-4 text-center">
                                <div class="text-2xl font-bold text-white">24/7</div>
                                <div class="text-sm text-slate-400">Support</div>
                            </div>
                        </div>
                    </div>

                    {{-- Decorative Elements --}}
                    <div class="absolute -top-4 -right-4 w-24 h-24 bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl blur-sm opacity-50"></div>
                    <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full blur-sm opacity-40"></div>
                </div>
            </div>
        </div>

        {{-- Scroll Indicator --}}
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 animate-bounce">
            <a href="#features" class="flex flex-col items-center text-slate-400 hover:text-white transition-colors">
                <span class="text-sm mb-2">เลื่อนลง</span>
                <i class="fas fa-chevron-down"></i>
            </a>
        </div>
    </div>
</section>

{{-- ================================================================
    FEATURES SECTION - จุดเด่นของระบบ
================================================================ --}}
<section id="features" class="py-24 lg:py-32 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16 lg:mb-20">
            <span class="inline-block px-4 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-sm font-semibold mb-4">
                คุณสมบัติเด่น
            </span>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 dark:text-white mb-6">
                ทำไมต้องเลือก <span class="gradient-text">{{ $appName }}</span>
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400 leading-relaxed">
                แพลตฟอร์มที่ออกแบบมาเพื่อธุรกิจของคุณโดยเฉพาะ พร้อมเทคโนโลยีล่าสุดและระบบรักษาความปลอดภัยระดับ Enterprise
            </p>
        </div>

        {{-- Features Grid --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {{-- Feature 1: All-in-One --}}
            <div class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-layer-group text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    All-in-One Platform
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    รวมทุกระบบไว้ในที่เดียว Affiliate, MLM, E-Commerce, AI Bot และอีก 20+ ระบบ ไม่ต้องซื้อแยก ประหยัดกว่า
                </p>
            </div>

            {{-- Feature 2: AI Powered --}}
            <div class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-robot text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    AI-Powered Automation
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    ระบบ AI อัจฉริยะ ช่วยตอบแชท วิเคราะห์ข้อมูล และทำงานอัตโนมัติ 24 ชั่วโมง ลดต้นทุน เพิ่มประสิทธิภาพ
                </p>
            </div>

            {{-- Feature 3: Blockchain --}}
            <div class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8">
                <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-link text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    Blockchain & TPIX Token
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    รองรับ Cryptocurrency และ Token TPIX สำหรับระบบ Staking, Farming และการชำระเงินแบบ Web3
                </p>
            </div>

            {{-- Feature 4: Multi-Currency Wallet --}}
            <div class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8">
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-wallet text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    Multi-Currency Wallet
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    กระเป๋าเงินหลายสกุล THB, USD, Crypto พร้อมระบบถอนเงินหลายช่องทาง Bank, PromptPay, Crypto
                </p>
            </div>

            {{-- Feature 5: MLM System --}}
            <div class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8">
                <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-sitemap text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    MLM & Commission System
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    ระบบ Multi-Level Marketing ไม่จำกัดชั้น คำนวณคอมมิชชันอัตโนมัติ พร้อมรายงานละเอียด
                </p>
            </div>

            {{-- Feature 6: Security --}}
            <div class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8">
                <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-shield-alt text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    Enterprise Security
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">
                    ความปลอดภัยระดับ Enterprise พร้อม 2FA, SSL, IP Whitelist และ License System
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ================================================================
    SYSTEMS SECTION - ระบบทั้งหมด
================================================================ --}}
<section id="systems" class="py-24 lg:py-32 bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="inline-block px-4 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full text-sm font-semibold mb-4">
                20+ ระบบครบวงจร
            </span>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 dark:text-white mb-6">
                ระบบทั้งหมดใน <span class="gradient-text-secondary">แพลตฟอร์มเดียว</span>
            </h2>
            <p class="text-lg text-slate-600 dark:text-slate-400">
                ไม่ต้องซื้อหลายระบบ ไม่ต้อง Integrate ซับซ้อน ทุกอย่างทำงานร่วมกันอย่างลงตัว
            </p>
        </div>

        {{-- Systems Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 lg:gap-6">
            @php
                $systems = [
                    ['icon' => 'fa-sitemap', 'name' => 'ระบบ Affiliate/MLM', 'color' => 'from-purple-500 to-purple-600'],
                    ['icon' => 'fa-shopping-cart', 'name' => 'E-Commerce', 'color' => 'from-pink-500 to-rose-500'],
                    ['icon' => 'fa-store', 'name' => 'Marketplace', 'color' => 'from-orange-500 to-amber-500'],
                    ['icon' => 'fa-wallet', 'name' => 'Digital Wallet', 'color' => 'from-green-500 to-emerald-500'],
                    ['icon' => 'fa-robot', 'name' => 'AI Chatbot', 'color' => 'from-blue-500 to-cyan-500'],
                    ['icon' => 'fab fa-line', 'name' => 'LINE Bot', 'color' => 'from-green-400 to-green-500'],
                    ['icon' => 'fa-coins', 'name' => 'TPIX Token', 'color' => 'from-yellow-500 to-amber-500'],
                    ['icon' => 'fa-hotel', 'name' => 'Hotel Booking', 'color' => 'from-red-500 to-pink-500'],
                    ['icon' => 'fa-cash-register', 'name' => 'POS System', 'color' => 'from-indigo-500 to-purple-500'],
                    ['icon' => 'fa-graduation-cap', 'name' => 'Academy LMS', 'color' => 'from-teal-500 to-cyan-500'],
                    ['icon' => 'fa-users-cog', 'name' => 'HRM System', 'color' => 'from-slate-500 to-gray-600'],
                    ['icon' => 'fa-file-invoice-dollar', 'name' => 'Accounting', 'color' => 'from-emerald-500 to-green-600'],
                    ['icon' => 'fa-seedling', 'name' => 'Food Passport', 'color' => 'from-lime-500 to-green-500'],
                    ['icon' => 'fa-chart-line', 'name' => 'Trading Bot', 'color' => 'from-blue-600 to-indigo-600'],
                    ['icon' => 'fa-gamepad', 'name' => 'Games', 'color' => 'from-fuchsia-500 to-pink-500'],
                    ['icon' => 'fa-magic', 'name' => 'Tarot Reading', 'color' => 'from-violet-500 to-purple-600'],
                    ['icon' => 'fa-qrcode', 'name' => 'QR/Barcode', 'color' => 'from-gray-600 to-slate-700'],
                    ['icon' => 'fa-laptop-code', 'name' => 'Software Sales', 'color' => 'from-cyan-500 to-blue-500'],
                    ['icon' => 'fa-chart-pie', 'name' => 'Analytics', 'color' => 'from-rose-500 to-red-500'],
                    ['icon' => 'fa-cogs', 'name' => 'และอีกมากมาย...', 'color' => 'from-slate-400 to-slate-500'],
                ];
            @endphp

            @foreach($systems as $system)
                <div class="group card-hover bg-white dark:bg-slate-800 rounded-xl p-4 lg:p-5 text-center border border-slate-200 dark:border-slate-700">
                    <div class="w-12 h-12 lg:w-14 lg:h-14 mx-auto bg-gradient-to-br {{ $system['color'] }} rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                        <i class="{{ str_starts_with($system['icon'], 'fab') ? '' : 'fas ' }}{{ $system['icon'] }} text-xl text-white"></i>
                    </div>
                    <h4 class="text-sm lg:text-base font-semibold text-slate-800 dark:text-white">
                        {{ $system['name'] }}
                    </h4>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ================================================================
    STATS SECTION - สถิติโครงการ
================================================================ --}}
<section id="stats" class="py-24 lg:py-32 bg-gradient-to-br from-blue-950 via-purple-950 to-slate-900 relative overflow-hidden">
    {{-- Background Pattern --}}
    <div class="absolute inset-0 opacity-10"
         style="background-image: radial-gradient(circle, white 1px, transparent 1px);
                background-size: 30px 30px;">
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="inline-block px-4 py-1.5 bg-white/10 text-white rounded-full text-sm font-semibold mb-4">
                ตัวเลขที่พิสูจน์ความน่าเชื่อถือ
            </span>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6">
                ข้อมูลจริง ไม่ใช่คำโฆษณา
            </h2>
            <p class="text-lg text-slate-300">
                ตัวเลขเหล่านี้มาจากระบบจริง อัพเดทแบบ Real-time
            </p>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
            {{-- Stat 1: Users --}}
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 lg:p-8 text-center">
                <div class="text-4xl lg:text-5xl xl:text-6xl font-bold text-white mb-2">
                    {{ number_format($stats['users']) }}+
                </div>
                <div class="text-slate-300 font-medium">ผู้ใช้ที่เชื่อใจ</div>
                <div class="mt-4 flex items-center justify-center gap-1 text-green-400 text-sm">
                    <i class="fas fa-arrow-up"></i>
                    <span>เพิ่มขึ้นทุกวัน</span>
                </div>
            </div>

            {{-- Stat 2: Affiliates --}}
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 lg:p-8 text-center">
                <div class="text-4xl lg:text-5xl xl:text-6xl font-bold text-white mb-2">
                    {{ number_format($stats['affiliates']) }}+
                </div>
                <div class="text-slate-300 font-medium">พันธมิตร Affiliate</div>
                <div class="mt-4 flex items-center justify-center gap-1 text-green-400 text-sm">
                    <i class="fas fa-network-wired"></i>
                    <span>เครือข่ายแน่น</span>
                </div>
            </div>

            {{-- Stat 3: Systems --}}
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 lg:p-8 text-center">
                <div class="text-4xl lg:text-5xl xl:text-6xl font-bold text-white mb-2">
                    20+
                </div>
                <div class="text-slate-300 font-medium">ระบบรองรับ</div>
                <div class="mt-4 flex items-center justify-center gap-1 text-amber-400 text-sm">
                    <i class="fas fa-cubes"></i>
                    <span>All-in-One</span>
                </div>
            </div>

            {{-- Stat 4: Uptime --}}
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-6 lg:p-8 text-center">
                <div class="text-4xl lg:text-5xl xl:text-6xl font-bold text-white mb-2">
                    99.9%
                </div>
                <div class="text-slate-300 font-medium">Uptime SLA</div>
                <div class="mt-4 flex items-center justify-center gap-1 text-blue-400 text-sm">
                    <i class="fas fa-server"></i>
                    <span>พร้อมใช้ 24/7</span>
                </div>
            </div>
        </div>

        {{-- Tech Stack --}}
        <div class="mt-16 pt-16 border-t border-white/10">
            <p class="text-center text-slate-400 mb-8">เทคโนโลยีที่เราใช้</p>
            <div class="flex flex-wrap items-center justify-center gap-8 lg:gap-12">
                <div class="text-slate-400 hover:text-white transition-colors">
                    <i class="fab fa-laravel text-4xl"></i>
                    <span class="block text-xs mt-1">Laravel 11</span>
                </div>
                <div class="text-slate-400 hover:text-white transition-colors">
                    <i class="fab fa-php text-4xl"></i>
                    <span class="block text-xs mt-1">PHP 8.1+</span>
                </div>
                <div class="text-slate-400 hover:text-cyan-400 transition-colors">
                    <i class="fab fa-react text-4xl"></i>
                    <span class="block text-xs mt-1">Tailwind CSS</span>
                </div>
                <div class="text-slate-400 hover:text-white transition-colors">
                    <i class="fab fa-js text-4xl"></i>
                    <span class="block text-xs mt-1">Alpine.js</span>
                </div>
                <div class="text-slate-400 hover:text-orange-400 transition-colors">
                    <i class="fab fa-ethereum text-4xl"></i>
                    <span class="block text-xs mt-1">Blockchain</span>
                </div>
                <div class="text-slate-400 hover:text-blue-400 transition-colors">
                    <i class="fas fa-database text-4xl"></i>
                    <span class="block text-xs mt-1">MySQL 8</span>
                </div>
                <div class="text-slate-400 hover:text-red-400 transition-colors">
                    <i class="fas fa-bolt text-4xl"></i>
                    <span class="block text-xs mt-1">Redis</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================================================================
    CTA SECTION - Call to Action
================================================================ --}}
<section class="py-24 lg:py-32 bg-white dark:bg-slate-900">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        {{-- Icon --}}
        <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-600 to-purple-600 rounded-3xl flex items-center justify-center mb-8 animate-pulse-glow">
            <i class="fas fa-rocket text-4xl text-white"></i>
        </div>

        {{-- Headline --}}
        <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 dark:text-white mb-6">
            พร้อมที่จะเริ่มต้นหรือยัง?
        </h2>
        <p class="text-lg lg:text-xl text-slate-600 dark:text-slate-400 mb-10 max-w-2xl mx-auto">
            เริ่มต้นใช้งาน {{ $appName }} ได้ทันที ลงทะเบียนฟรี ไม่มีค่าใช้จ่ายเริ่มต้น
            พร้อมทีมซัพพอร์ตช่วยเหลือคุณตลอด 24 ชั่วโมง
        </p>

        {{-- CTA Buttons --}}
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}"
               class="inline-flex items-center justify-center gap-2 px-10 py-4 btn-secondary text-white font-bold text-lg rounded-xl shadow-lg shadow-purple-500/20 hover:shadow-purple-500/30">
                <i class="fas fa-user-plus"></i>
                ลงทะเบียนฟรี
            </a>
            <a href="{{ route('contact') }}"
               class="inline-flex items-center justify-center gap-2 px-10 py-4 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-white font-semibold text-lg rounded-xl transition-colors">
                <i class="fas fa-headset"></i>
                ติดต่อทีมขาย
            </a>
        </div>

        {{-- Trust Badges --}}
        <div class="mt-12 flex flex-wrap items-center justify-center gap-8 text-slate-500 dark:text-slate-400">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500"></i>
                <span>ทดลองใช้ฟรี</span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500"></i>
                <span>ไม่ต้องใส่บัตรเครดิต</span>
            </div>
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-green-500"></i>
                <span>ยกเลิกได้ทุกเมื่อ</span>
            </div>
        </div>
    </div>
</section>

@endsection
