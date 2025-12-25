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
    // ดึงข้อมูลจาก SiteSetting (ที่แอดมินตั้งค่าใน admin/site-settings)
    $siteSettings = \App\Models\SiteSetting::getSetting();
    $appName = $siteSettings->site_name ?? 'TP-Affiliate Pro';
    $systemLogo = $siteSettings->logo;

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
    GLOBAL LAVA LAMP BACKGROUND - Fixed ไม่ scroll ตามเนื้อหา
================================================================ --}}
<div class="fixed inset-0 z-0 pointer-events-none">
    {{-- Base Gradient Background --}}
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-blue-950 to-purple-950">
        {{-- Grid Pattern --}}
        <div class="absolute inset-0 opacity-[0.03]"
             style="background-image: linear-gradient(rgba(255,255,255,.1) 1px, transparent 1px),
                                      linear-gradient(90deg, rgba(255,255,255,.1) 1px, transparent 1px);
                    background-size: 60px 60px;">
        </div>

        {{-- ✨ Firefly Container --}}
        <div id="fireflies" class="absolute inset-0 pointer-events-none" style="z-index: 1;"></div>

        {{-- Glass Overlay for Glassmorphism Effect (เบลอเบาๆ เพื่อให้เห็น blobs) --}}
        <div class="absolute inset-0 backdrop-blur-sm"></div>
    </div>
</div>

{{-- ================================================================
    HERO SECTION - Professional & Trustworthy
================================================================ --}}
<section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-20 z-10">

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
                    <a href="{{ route('storefront.index') }}"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600 text-white font-bold text-lg rounded-xl shadow-lg shadow-orange-500/30 hover:shadow-orange-500/40 transition-all">
                        <i class="fas fa-shopping-cart"></i>
                        🛒 ช้อปปิ้งเลย!
                    </a>
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
                    <div class="relative bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-xl border border-white/20 rounded-3xl p-8 lg:p-12 shadow-2xl animate-float">
                        {{-- Logo - ใหญ่และเท่ --}}
                        <div class="flex items-center justify-center mb-8">
                            @if($systemLogo)
                                <div class="relative group">
                                    {{-- Glow Effect Background --}}
                                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 rounded-3xl blur-2xl opacity-50 group-hover:opacity-75 transition-opacity duration-500 scale-110"></div>

                                    {{-- Logo Container --}}
                                    <div class="relative bg-gradient-to-br from-white/20 to-white/5 backdrop-blur-sm rounded-3xl p-6 lg:p-8 border border-white/30 shadow-2xl">
                                        <img src="{{ asset('storage/' . $systemLogo) }}"
                                             alt="{{ $appName }}"
                                             class="h-32 sm:h-40 lg:h-48 w-auto drop-shadow-2xl transition-transform duration-500 group-hover:scale-105"
                                             style="filter: drop-shadow(0 0 30px rgba(59, 130, 246, 0.3));">
                                    </div>

                                    {{-- Shine Effect --}}
                                    <div class="absolute top-0 left-0 right-0 h-1/2 bg-gradient-to-b from-white/20 to-transparent rounded-t-3xl pointer-events-none"></div>
                                </div>
                            @else
                                <div class="relative group">
                                    {{-- Glow Effect --}}
                                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-500 rounded-3xl blur-2xl opacity-60 group-hover:opacity-80 transition-opacity duration-500 scale-110"></div>

                                    {{-- Logo Placeholder --}}
                                    <div class="relative w-40 h-40 lg:w-52 lg:h-52 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-500 rounded-3xl flex items-center justify-center shadow-2xl transition-transform duration-500 group-hover:scale-105">
                                        <span class="text-white font-black text-6xl lg:text-7xl drop-shadow-lg">TP</span>
                                        {{-- Inner glow --}}
                                        <div class="absolute inset-4 bg-white/10 rounded-2xl"></div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- YouTube Video - Click to Play --}}
                        <div class="mb-8" x-data="{ playing: false }">
                            <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-white/20 group cursor-pointer"
                                 @click="playing = true">
                                {{-- Thumbnail --}}
                                <div class="aspect-video" x-show="!playing">
                                    <img src="https://img.youtube.com/vi/-GsrFb2tO1I/maxresdefault.jpg"
                                         alt="วิดีโอแนะนำ TP-Affiliate"
                                         class="w-full h-full object-cover">

                                    {{-- Overlay --}}
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent flex flex-col items-center justify-center">
                                        {{-- Play Button --}}
                                        <div class="w-20 h-20 bg-red-600 rounded-full flex items-center justify-center shadow-2xl
                                                    transform group-hover:scale-110 transition-all duration-300
                                                    group-hover:bg-red-500">
                                            <svg class="w-10 h-10 text-white ml-1" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M8 5v14l11-7z"/>
                                            </svg>
                                        </div>

                                        {{-- Invite Text --}}
                                        <p class="mt-4 text-white text-lg font-semibold drop-shadow-lg">
                                            ▶ คลิกเพื่อดูวิดีโอแนะนำ
                                        </p>
                                        <p class="text-white/70 text-sm mt-1">
                                            ทำความรู้จักกับระบบของเรา
                                        </p>
                                    </div>
                                </div>

                                {{-- Video iframe (loads when clicked) --}}
                                <div class="aspect-video" x-show="playing" x-cloak>
                                    <template x-if="playing">
                                        <iframe
                                            src="https://www.youtube.com/embed/-GsrFb2tO1I?autoplay=1&rel=0"
                                            title="TP-Affiliate Introduction"
                                            class="w-full h-full"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                            allowfullscreen>
                                        </iframe>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- App Name - แสดงเฉพาะเมื่อไม่มีโลโก้ --}}
                        @if(!$systemLogo)
                        <h2 class="text-2xl lg:text-3xl font-bold text-white text-center mb-2">
                            {{ $appName }}
                        </h2>
                        @endif

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
    SIGNUP TUTORIAL SECTION - วิธีสมัครสมาชิก
================================================================ --}}
<section id="signup-tutorial" class="py-20 lg:py-28 bg-white/5 relative overflow-hidden z-10">
    {{-- Background Effects - โปร่งใสเพื่อให้เห็น lava lamp ด้านหลัง --}}
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image:
            linear-gradient(to right, rgba(16, 185, 129, 0.15) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(16, 185, 129, 0.15) 1px, transparent 1px);
            background-size: 40px 40px;"></div>
    </div>
    <div class="absolute top-20 right-10 w-96 h-96 bg-green-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10"></div>
    <div class="absolute bottom-20 left-10 w-96 h-96 bg-emerald-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-12 lg:mb-16">
            <div class="inline-flex items-center gap-2 px-6 py-3 bg-green-500/20 backdrop-blur-sm rounded-full border border-green-500/30 mb-8">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <span class="text-green-300 font-semibold">สมัครสมาชิกฟรี!</span>
            </div>

            <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-green-400 via-emerald-400 to-teal-400">
                    วิธีสมัครสมาชิก
                </span>
            </h2>
            <p class="text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed">
                เลือกวิธีสมัครที่สะดวกสำหรับคุณ ไม่ว่าจะผ่านเว็บหรือ LINE ก็ทำได้ง่ายๆ
                <br class="hidden md:block">
                <span class="text-green-300 font-medium">ใช้เวลาไม่ถึง 2 นาที!</span>
            </p>
        </div>

        {{-- Signup Methods Cards --}}
        <div class="grid md:grid-cols-2 gap-8 lg:gap-10 mb-12">
            {{-- Card 1: สมัครผ่านเว็บ --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 hover:border-green-500/30 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openSignupTutorial === 'function') openSignupTutorial('signup-web')">
                <div class="flex items-start gap-6">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform shadow-lg shadow-blue-500/20">
                        <i class="fas fa-globe text-4xl text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-2xl font-bold text-white mb-3">สมัครผ่านเว็บ</h3>
                        <p class="text-slate-400 mb-4">กรอกข้อมูลผ่านฟอร์มบนเว็บไซต์ สะดวก ครบถ้วน ทำได้จากทุกอุปกรณ์</p>
                        <div class="flex flex-wrap gap-3 mb-4">
                            <span class="px-3 py-1.5 bg-blue-500/20 text-blue-300 rounded-full text-sm font-medium">
                                <i class="fas fa-clock mr-1"></i>2 นาที
                            </span>
                            <span class="px-3 py-1.5 bg-purple-500/20 text-purple-300 rounded-full text-sm font-medium">
                                <i class="fas fa-list-ol mr-1"></i>4 ขั้นตอน
                            </span>
                        </div>
                        <div class="flex items-center text-green-400 font-medium group-hover:text-green-300">
                            <span>ดูขั้นตอน</span>
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 2: สมัครผ่าน LINE --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-8 hover:bg-white/10 hover:border-[#06C755]/30 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openSignupTutorial === 'function') openSignupTutorial('signup-line')">
                <div class="flex items-start gap-6">
                    <div class="w-20 h-20 bg-gradient-to-br from-[#06C755] to-[#00B900] rounded-2xl flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform shadow-lg shadow-green-500/20">
                        <svg class="w-12 h-12" viewBox="0 0 24 24" fill="white">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-2xl font-bold text-white mb-3">สมัครผ่าน LINE</h3>
                        <p class="text-slate-400 mb-4">ใช้บัญชี LINE ที่มีอยู่แล้ว สะดวก รวดเร็ว ปลอดภัย รับแจ้งเตือนทาง LINE</p>
                        <div class="flex flex-wrap gap-3 mb-4">
                            <span class="px-3 py-1.5 bg-green-500/20 text-green-300 rounded-full text-sm font-medium">
                                <i class="fas fa-bolt mr-1"></i>เร็วที่สุด
                            </span>
                            <span class="px-3 py-1.5 bg-green-500/20 text-green-300 rounded-full text-sm font-medium">
                                <i class="fas fa-list-ol mr-1"></i>3 ขั้นตอน
                            </span>
                        </div>
                        <div class="flex items-center text-[#06C755] font-medium group-hover:text-green-400">
                            <span>ดูขั้นตอน</span>
                            <i class="fas fa-arrow-right ml-2 group-hover:translate-x-2 transition-transform"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="text-center">
            <p class="text-slate-400 mb-6">พร้อมเริ่มต้นแล้ว?</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white font-bold text-lg rounded-xl shadow-lg shadow-green-500/30 hover:shadow-green-500/40 transition-all">
                    <i class="fas fa-rocket"></i>
                    สมัครสมาชิกเลย!
                </a>
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 hover:bg-white/20 border border-white/20 text-white font-semibold text-lg rounded-xl transition-all">
                    <i class="fas fa-sign-in-alt"></i>
                    เข้าสู่ระบบ
                </a>
            </div>
            {{-- Link to detailed documentation --}}
            <p class="mt-6 text-slate-400">
                <a href="{{ route('documents.how-to-register') }}"
                   class="text-green-400 hover:text-green-300 underline underline-offset-4 transition-colors">
                    <i class="fas fa-book-open mr-1"></i>
                    อ่านคู่มือการสมัครฉบับละเอียด
                </a>
            </p>
        </div>
    </div>
</section>

{{-- ================================================================
    APP DOWNLOAD SECTION - ดาวน์โหลดแอป TP Ultra
================================================================ --}}
@if($siteSettings->app_download_enabled ?? true)
<section id="app-download" class="py-20 lg:py-28 bg-white/5 relative overflow-hidden z-10">
    {{-- Background Effects - โปร่งใสเพื่อให้เห็น lava lamp ด้านหลัง --}}
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image:
            linear-gradient(to right, rgba(6, 182, 212, 0.15) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(6, 182, 212, 0.15) 1px, transparent 1px);
            background-size: 40px 40px;"></div>
    </div>
    <div class="absolute top-20 right-10 w-96 h-96 bg-cyan-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10"></div>
    <div class="absolute bottom-20 left-10 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-12 lg:mb-16">
            <div class="inline-flex items-center gap-2 px-6 py-3 bg-cyan-500/20 backdrop-blur-sm rounded-full border border-cyan-500/30 mb-8">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-cyan-500"></span>
                </span>
                <span class="text-cyan-300 font-semibold">ดาวน์โหลดแอปฟรี!</span>
            </div>

            <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 via-blue-400 to-purple-400">
                    {{ $siteSettings->app_name ?? 'TP Ultra' }}
                </span>
            </h2>
            <p class="text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed">
                {{ $siteSettings->app_description ?? 'แอปพลิเคชั่นสำหรับจัดการธุรกิจ Affiliate ทุกที่ทุกเวลา' }}
            </p>
        </div>

        {{-- App Showcase --}}
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            {{-- Left: App Icon & Visual --}}
            <div class="flex justify-center animate-fade-in-up">
                <div class="relative group">
                    {{-- Glow Effect --}}
                    <div class="absolute inset-0 bg-gradient-to-r from-cyan-500 via-blue-500 to-purple-500 rounded-3xl blur-2xl opacity-50 group-hover:opacity-75 transition-opacity duration-500 scale-110"></div>

                    {{-- App Icon Container --}}
                    <div class="relative bg-gradient-to-br from-white/20 to-white/5 backdrop-blur-sm rounded-3xl p-8 lg:p-12 border border-white/30 shadow-2xl">
                        <img src="{{ $siteSettings->app_icon_url ?? asset('images/tp-ultra-icon.png') }}"
                             alt="{{ $siteSettings->app_name ?? 'TP Ultra' }}"
                             class="w-48 h-48 lg:w-64 lg:h-64 rounded-3xl shadow-2xl transition-transform duration-500 group-hover:scale-105"
                             style="filter: drop-shadow(0 0 30px rgba(6, 182, 212, 0.3));">

                        {{-- App Name Badge --}}
                        <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 px-6 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 rounded-full shadow-lg">
                            <span class="text-white font-bold text-lg">{{ $siteSettings->app_name ?? 'TP Ultra' }}</span>
                        </div>
                    </div>

                    {{-- Floating Elements --}}
                    <div class="absolute -top-4 -right-4 w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center shadow-lg animate-bounce">
                        <i class="fas fa-mobile-alt text-white text-2xl"></i>
                    </div>
                    <div class="absolute -bottom-4 -left-4 w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-500 rounded-full flex items-center justify-center shadow-lg animate-pulse">
                        <i class="fas fa-star text-white text-lg"></i>
                    </div>
                </div>
            </div>

            {{-- Right: Download Options --}}
            <div class="space-y-6 animate-fade-in-up" style="animation-delay: 0.2s;">
                {{-- Features List --}}
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="flex items-center gap-3 bg-white/5 rounded-xl p-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-white"></i>
                        </div>
                        <span class="text-white font-medium">ดูคอมมิชชั่น</span>
                    </div>
                    <div class="flex items-center gap-3 bg-white/5 rounded-xl p-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <span class="text-white font-medium">จัดการทีม</span>
                    </div>
                    <div class="flex items-center gap-3 bg-white/5 rounded-xl p-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-wallet text-white"></i>
                        </div>
                        <span class="text-white font-medium">ถอนเงิน</span>
                    </div>
                    <div class="flex items-center gap-3 bg-white/5 rounded-xl p-4">
                        <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-bell text-white"></i>
                        </div>
                        <span class="text-white font-medium">แจ้งเตือน</span>
                    </div>
                </div>

                {{-- Download Buttons --}}
                <div class="space-y-4">
                    {{-- APK Direct Download --}}
                    @if($siteSettings->app_apk_enabled ?? true)
                    <a href="{{ $siteSettings->app_apk_url ?? '#' }}"
                       class="group flex items-center gap-4 w-full px-6 py-4 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 rounded-2xl shadow-lg shadow-cyan-500/30 hover:shadow-cyan-500/50 transition-all duration-300 {{ empty($siteSettings->app_apk_url) ? 'opacity-60 cursor-not-allowed' : '' }}"
                       {{ empty($siteSettings->app_apk_url) ? 'onclick=return false;' : '' }}>
                        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fab fa-android text-3xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-white/70 text-sm">ดาวน์โหลดไฟล์</p>
                            <p class="text-white font-bold text-lg">APK สำหรับ Android</p>
                        </div>
                        <div class="flex items-center gap-2 text-white">
                            @if(empty($siteSettings->app_apk_url))
                                <span class="text-sm opacity-70">กำลังเตรียมลิงก์...</span>
                            @else
                                <i class="fas fa-download text-xl group-hover:animate-bounce"></i>
                            @endif
                        </div>
                    </a>
                    @endif

                    {{-- Google Play Store --}}
                    @if($siteSettings->app_playstore_enabled ?? false)
                    <a href="{{ $siteSettings->app_playstore_url ?? '#' }}"
                       target="_blank"
                       class="group flex items-center gap-4 w-full px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 rounded-2xl shadow-lg shadow-green-500/30 hover:shadow-green-500/50 transition-all duration-300">
                        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fab fa-google-play text-3xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-white/70 text-sm">ดาวน์โหลดจาก</p>
                            <p class="text-white font-bold text-lg">Google Play Store</p>
                        </div>
                        <i class="fas fa-external-link-alt text-white text-xl group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    @else
                    <div class="flex items-center gap-4 w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl cursor-not-allowed">
                        <div class="w-14 h-14 bg-white/10 rounded-xl flex items-center justify-center">
                            <i class="fab fa-google-play text-3xl text-white/50"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-white/50 text-sm">ดาวน์โหลดจาก</p>
                            <p class="text-white/70 font-bold text-lg">Google Play Store</p>
                        </div>
                        <span class="px-3 py-1 bg-amber-500/20 text-amber-300 rounded-full text-sm font-medium">
                            เร็วๆ นี้
                        </span>
                    </div>
                    @endif

                    {{-- Apple App Store --}}
                    @if($siteSettings->app_appstore_enabled ?? false)
                    <a href="{{ $siteSettings->app_appstore_url ?? '#' }}"
                       target="_blank"
                       class="group flex items-center gap-4 w-full px-6 py-4 bg-gradient-to-r from-gray-700 to-gray-800 hover:from-gray-600 hover:to-gray-700 rounded-2xl shadow-lg shadow-gray-500/30 hover:shadow-gray-500/50 transition-all duration-300">
                        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                            <i class="fab fa-apple text-3xl text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-white/70 text-sm">ดาวน์โหลดจาก</p>
                            <p class="text-white font-bold text-lg">App Store</p>
                        </div>
                        <i class="fas fa-external-link-alt text-white text-xl group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    @else
                    <div class="flex items-center gap-4 w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl cursor-not-allowed">
                        <div class="w-14 h-14 bg-white/10 rounded-xl flex items-center justify-center">
                            <i class="fab fa-apple text-3xl text-white/50"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-white/50 text-sm">ดาวน์โหลดจาก</p>
                            <p class="text-white/70 font-bold text-lg">App Store (iOS)</p>
                        </div>
                        <span class="px-3 py-1 bg-amber-500/20 text-amber-300 rounded-full text-sm font-medium">
                            เร็วๆ นี้
                        </span>
                    </div>
                    @endif
                </div>

                {{-- Note --}}
                <p class="text-center text-slate-400 text-sm mt-6">
                    <i class="fas fa-shield-alt text-green-400 mr-2"></i>
                    แอปปลอดภัย 100% ไม่มีไวรัส ไม่มีโฆษณา
                </p>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ================================================================
    PLATFORM DEMO SECTION - สื่อการเรียนรู้ระบบ
================================================================ --}}
<section id="demo" class="py-20 lg:py-28 bg-white/5 relative overflow-hidden z-10">
    {{-- Background Effects - โปร่งใสเพื่อให้เห็น lava lamp ด้านหลัง --}}
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image:
            linear-gradient(to right, rgba(99, 102, 241, 0.15) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(99, 102, 241, 0.15) 1px, transparent 1px);
            background-size: 40px 40px;"></div>
    </div>
    <div class="absolute top-20 left-10 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10"></div>
    <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-10"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center mb-12 lg:mb-16">
            <div class="inline-flex items-center gap-2 px-6 py-3 bg-white/10 backdrop-blur-sm rounded-full border border-white/20 mb-8">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                <span class="text-white font-semibold">สื่อการเรียนรู้ระบบ</span>
            </div>

            <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6">
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-white via-indigo-200 to-purple-200">
                    เรียนรู้ {{ $appName }}
                </span>
            </h2>
            <p class="text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed">
                สไลด์นำเสนอระบบแบบครบถ้วน สวยงาม มืออาชีพ พร้อมใช้งานทันที
                <br class="hidden md:block">
                <span class="text-indigo-300 font-medium">เหมาะสำหรับนักลงทุน ผู้อยากร่วม และผู้สนใจทุกท่าน</span>
            </p>
        </div>

        {{-- Demo Cards Grid - 9 Cards = 3x3 Layout --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 mb-12">
            {{-- Card 1: System Overview --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 lg:p-8 hover:bg-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('system-overview')">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-desktop text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">ภาพรวมระบบ</h3>
                <p class="text-slate-400 mb-4">เรียนรู้ระบบทั้งหมดใน {{ $appName }} รวมถึง Affiliate, MLM, E-Commerce และระบบอื่นๆ</p>
                <div class="flex items-center text-indigo-400 font-medium group-hover:text-indigo-300">
                    <span>ดูสไลด์</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>

            {{-- Card 2: MLM Plans --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 lg:p-8 hover:bg-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('mlm-plans')">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-sitemap text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">แผนการตลาด MLM</h3>
                <p class="text-slate-400 mb-4">เรียนรู้แผน Unilevel, Binary และระบบคอมมิชชั่นหลายชั้นที่ยืดหยุ่น</p>
                <div class="flex items-center text-purple-400 font-medium group-hover:text-purple-300">
                    <span>ดูสไลด์</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>

            {{-- Card 3: TPIX Token --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 lg:p-8 hover:bg-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('tpix-token')">
                <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform overflow-hidden">
                    <img src="{{ asset('images/tpix-logo.svg') }}" alt="TPIX" class="w-12 h-12 object-contain">
                </div>
                <h3 class="text-xl font-bold text-white mb-3">TPIX Token</h3>
                <p class="text-slate-400 mb-4">เรียนรู้ TPIX Token ที่มี Blockchain ของตัวเอง Staking, DEX และ Tokenomics</p>
                <div class="flex items-center text-amber-400 font-medium group-hover:text-amber-300">
                    <span>ดูสไลด์</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>

            {{-- Card 4: AI & Automation --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 lg:p-8 hover:bg-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('ai-automation')">
                <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-robot text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">AI & Automation</h3>
                <p class="text-slate-400 mb-4">เรียนรู้ระบบ AI Chatbot, LINE Bot และระบบอัตโนมัติที่ช่วยประหยัดเวลา</p>
                <div class="flex items-center text-emerald-400 font-medium group-hover:text-emerald-300">
                    <span>ดูสไลด์</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>

            {{-- Card 5: Academy & ปลดแอกธุรกิจ --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 lg:p-8 hover:bg-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('academy-freedom')">
                <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-orange-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-graduation-cap text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Academy & ปลดแอกธุรกิจ</h3>
                <p class="text-slate-400 mb-4">เรียนรู้ระบบ Academy ออนไลน์และอิสรภาพจากแพลตฟอร์มต่างชาติ</p>
                <div class="flex items-center text-rose-400 font-medium group-hover:text-rose-300">
                    <span>ดูสไลด์</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>

            {{-- Card 6: Ecosystem & Vision --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 lg:p-8 hover:bg-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('ecosystem-vision')">
                <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-violet-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-globe text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Ecosystem & Vision</h3>
                <p class="text-slate-400 mb-4">ระบบนิเวศครบวงจรและ Roadmap การพัฒนา 2025-2027</p>
                <div class="flex items-center text-indigo-400 font-medium group-hover:text-indigo-300">
                    <span>ดูสไลด์</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>

            {{-- Card 7: พลิกเกมส์ธุรกิจ --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 lg:p-8 hover:bg-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('game-changer')">
                <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-chess-king text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">พลิกเกมส์ธุรกิจ</h3>
                <p class="text-slate-400 mb-4">โอกาสการลงทุนที่แตกต่าง หลากหลายช่องทางรายได้</p>
                <div class="flex items-center text-amber-400 font-medium group-hover:text-amber-300">
                    <span>ดูสไลด์</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>

            {{-- Card 8: E-Commerce Empire --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 lg:p-8 hover:bg-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('ecommerce-empire')">
                <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-shopping-bag text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">E-Commerce Empire</h3>
                <p class="text-slate-400 mb-4">ระบบ E-Commerce ครบวงจร จาก Marketplace ถึง Food Passport</p>
                <div class="flex items-center text-cyan-400 font-medium group-hover:text-cyan-300">
                    <span>ดูสไลด์</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>

            {{-- Card 9: Community & Partnership --}}
            <div class="group bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl p-6 lg:p-8 hover:bg-white/10 hover:border-white/20 transition-all duration-300 cursor-pointer"
                 onclick="if(typeof openPresentationWithTopic === 'function') openPresentationWithTopic('community')">
                <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-rose-600 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-users text-3xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">Community & Partnership</h3>
                <p class="text-slate-400 mb-4">ชุมชนผู้ใช้ พันธมิตรธุรกิจ และบริการสนับสนุน</p>
                <div class="flex items-center text-pink-400 font-medium group-hover:text-pink-300">
                    <span>ดูสไลด์</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </div>

        {{-- Main CTA Button --}}
        <div class="text-center">
            <div class="relative inline-block">
                <div class="absolute -inset-1 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl blur-lg opacity-75 animate-pulse"></div>
                <button onclick="if(typeof openPresentationFullscreen === 'function') openPresentationFullscreen()"
                        class="relative inline-flex items-center gap-3 px-10 py-5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xl font-bold rounded-2xl shadow-2xl hover:shadow-indigo-500/50 transition-all duration-300 transform hover:scale-105 group">
                    <i class="fas fa-play-circle text-2xl group-hover:scale-110 transition-transform"></i>
                    <span>เปิดสไลด์นำเสนอ</span>
                    <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                </button>
            </div>

            {{-- Features List --}}
            <div class="flex flex-wrap items-center justify-center gap-6 mt-8 text-white/70 text-sm">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-emerald-400"></i>
                    <span>เต็มจอ Fullscreen</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-emerald-400"></i>
                    <span>Auto-play อัจฉริยะ</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-emerald-400"></i>
                    <span>รองรับ Keyboard</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-emerald-400"></i>
                    <span>ตั้งค่าได้หลากหลาย</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Include Presentation Slides Modal (แค่ส่วน modal และ scripts ไม่ซ้ำกับ section ด้านบน) --}}
@include('components.presentation-slides-modal')

{{-- ================================================================
    FEATURES SECTION - จุดเด่นของระบบ
================================================================ --}}
<section id="features" class="py-16 lg:py-20 bg-white/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16 lg:mb-20">
            <span class="inline-block px-4 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold mb-4">
                คุณสมบัติเด่น
            </span>
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6">
                ทำไมต้องเลือก <span class="gradient-text">{{ $appName }}</span>
            </h2>
            <p class="text-lg text-slate-200 leading-relaxed">
                แพลตฟอร์มที่ออกแบบมาเพื่อธุรกิจของคุณโดยเฉพาะ พร้อมเทคโนโลยีล่าสุดและระบบรักษาความปลอดภัยระดับ Enterprise
            </p>
        </div>

        {{-- Features Grid - แต่ละ Card มีลิงก์ไปหน้าเอกสารละเอียด --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {{-- Feature 1: All-in-One --}}
            <a href="{{ route('documents.all-in-one') }}" class="group card-hover bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 block hover:border-blue-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-layer-group text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">
                    All-in-One Platform
                </h3>
                <p class="text-slate-200 leading-relaxed mb-4">
                    รวมทุกระบบไว้ในที่เดียว Affiliate, MLM, E-Commerce, AI Bot และอีก 20+ ระบบ ไม่ต้องซื้อแยก ประหยัดกว่า
                </p>
                <div class="flex items-center text-blue-400 font-medium text-sm group-hover:underline">
                    <span>อ่านเอกสารเพิ่มเติม</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            {{-- Feature 2: AI Powered --}}
            <a href="{{ route('documents.ai-automation') }}" class="group card-hover bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 block hover:border-purple-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-robot text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">
                    AI-Powered Automation
                </h3>
                <p class="text-slate-200 leading-relaxed mb-4">
                    ระบบ AI อัจฉริยะ ช่วยตอบแชท วิเคราะห์ข้อมูล และทำงานอัตโนมัติ 24 ชั่วโมง ลดต้นทุน เพิ่มประสิทธิภาพ
                </p>
                <div class="flex items-center text-purple-400 font-medium text-sm group-hover:underline">
                    <span>อ่านเอกสารเพิ่มเติม</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            {{-- Feature 3: Blockchain --}}
            <a href="{{ route('documents.blockchain-tpix') }}" class="group card-hover bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 block hover:border-amber-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-link text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">
                    Blockchain & TPIX Token
                </h3>
                <p class="text-slate-200 leading-relaxed mb-4">
                    รองรับ Cryptocurrency และ Token TPIX สำหรับระบบ Staking, Farming และการชำระเงินแบบ Web3
                </p>
                <div class="flex items-center text-amber-400 font-medium text-sm group-hover:underline">
                    <span>อ่าน Whitepaper</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            {{-- Feature 4: Multi-Currency Wallet --}}
            <a href="{{ route('documents.multi-currency-wallet') }}" class="group card-hover bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 block hover:border-emerald-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-wallet text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">
                    Multi-Currency Wallet
                </h3>
                <p class="text-slate-200 leading-relaxed mb-4">
                    กระเป๋าเงินหลายสกุล THB, USD, Crypto พร้อมระบบถอนเงินหลายช่องทาง Bank, PromptPay, Crypto
                </p>
                <div class="flex items-center text-emerald-400 font-medium text-sm group-hover:underline">
                    <span>อ่านเอกสารเพิ่มเติม</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            {{-- Feature 5: MLM System --}}
            <a href="{{ route('documents.mlm-commission') }}" class="group card-hover bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 block hover:border-pink-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-sitemap text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">
                    MLM & Commission System
                </h3>
                <p class="text-slate-200 leading-relaxed mb-4">
                    ระบบ Multi-Level Marketing ไม่จำกัดชั้น คำนวณคอมมิชชันอัตโนมัติ พร้อมรายงานละเอียด
                </p>
                <div class="flex items-center text-pink-400 font-medium text-sm group-hover:underline">
                    <span>อ่านเอกสารเพิ่มเติม</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            {{-- Feature 6: Security --}}
            <a href="{{ route('documents.enterprise-security') }}" class="group card-hover bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-8 block hover:border-cyan-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-shield-alt text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-3">
                    Enterprise Security
                </h3>
                <p class="text-slate-200 leading-relaxed mb-4">
                    ความปลอดภัยระดับ Enterprise พร้อม 2FA, SSL, IP Whitelist และ License System
                </p>
                <div class="flex items-center text-cyan-400 font-medium text-sm group-hover:underline">
                    <span>อ่านเอกสารเพิ่มเติม</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>
        </div>
    </div>
</section>

{{-- ================================================================
    NAVIGATION SECTION - ทางเลือกสำหรับคุณ (V3 Design)
    🎨 3D + Glassmorphism + Premium Animations
================================================================ --}}
<section id="navigation" class="relative py-24 lg:py-32 overflow-hidden z-10">
    {{-- Background --}}
    <div class="absolute inset-0 bg-white/5"></div>

    {{-- Background Pattern --}}
    <div class="absolute inset-0 opacity-[0.03]"
         style="background-image: radial-gradient(circle, currentColor 1px, transparent 1px);
                background-size: 40px 40px;">
    </div>

    {{-- Floating Gradient Orbs --}}
    <div class="absolute top-20 -left-32 w-96 h-96 bg-gradient-to-r from-blue-400/30 to-cyan-400/30 rounded-full blur-3xl"></div>
    <div class="absolute bottom-20 -right-32 w-96 h-96 bg-gradient-to-r from-purple-400/30 to-pink-400/30 rounded-full blur-3xl"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-r from-amber-400/10 to-orange-400/10 rounded-full blur-3xl"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center max-w-4xl mx-auto mb-16 lg:mb-20">
            {{-- Badge --}}
            <div class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500/10 to-purple-500/10 backdrop-blur-sm rounded-full border border-blue-500/20 mb-8">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                </span>
                <span class="text-sm font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                    เลือกเส้นทางของคุณ
                </span>
            </div>

            {{-- Title --}}
            <h2 class="text-4xl md:text-5xl lg:text-6xl font-black mb-6">
                <span class="block text-white mb-2">คุณต้องการ</span>
                <span class="block bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">
                    อะไรวันนี้?
                </span>
            </h2>

            {{-- Description --}}
            <p class="text-xl text-slate-200 leading-relaxed">
                เลือกบริการที่ตอบโจทย์คุณ ทุกอย่างอยู่ในแพลตฟอร์มเดียว
                <br class="hidden md:block">
                <span class="text-purple-400 font-semibold">ครบจบในที่เดียว ไม่ต้องไปหาที่อื่น!</span>
            </p>
        </div>

        {{-- Navigation Grid - 7 Main Categories --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 lg:gap-8">

            {{-- ============================================================
                1. 💼 หางาน/ทำงาน
            ============================================================ --}}
            <div class="group relative" x-data="{ expanded: false }">
                {{-- Main Card --}}
                <div class="relative bg-white/80 backdrop-blur-xl border-2 border-slate-200/50 rounded-3xl p-6 lg:p-8
                            hover:border-blue-500/50
                            hover:shadow-2xl hover:shadow-blue-500/20
                            transform hover:-translate-y-2 hover:scale-[1.02]
                            transition-all duration-500 cursor-pointer"
                     @click="expanded = !expanded">

                    {{-- Glow Effect --}}
                    <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>

                    {{-- Content --}}
                    <div class="relative">
                        {{-- Icon --}}
                        <div class="w-16 h-16 lg:w-20 lg:h-20 mx-auto mb-6 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center
                                    shadow-lg shadow-blue-500/30
                                    transform group-hover:scale-110 group-hover:rotate-6
                                    transition-all duration-500">
                            <i class="fas fa-briefcase text-3xl lg:text-4xl text-white drop-shadow-lg"></i>
                        </div>

                        {{-- Title --}}
                        <h3 class="text-xl lg:text-2xl font-black text-white mb-3 text-center">
                            หางาน / ทำงาน
                        </h3>

                        {{-- Description --}}
                        <p class="text-sm text-slate-200 text-center mb-4">
                            โอกาสการงานหลากหลาย เริ่มต้นสร้างรายได้วันนี้
                        </p>

                        {{-- Sub Items --}}
                        <div class="space-y-2" x-show="expanded" x-collapse>
                            <div class="block px-4 py-3 bg-white/5 rounded-xl cursor-not-allowed opacity-75">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-500">🚴 ไรเดอร์ / Delivery</span>
                                    <span class="px-2 py-1 bg-amber-500/20 text-amber-700 rounded-full text-xs font-bold">
                                        เร็วๆ นี้
                                    </span>
                                </div>
                            </div>
                            <div class="block px-4 py-3 bg-white/5 rounded-xl cursor-not-allowed opacity-75">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-500">💼 นักขาย / Sales</span>
                                    <span class="px-2 py-1 bg-amber-500/20 text-amber-700 rounded-full text-xs font-bold">
                                        เร็วๆ นี้
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('storefront.index') }}" class="block px-4 py-3 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">🏪 ขายของออนไลน์</span>
                            </a>
                        </div>

                        {{-- Expand Button --}}
                        <button class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-bold text-blue-400 hover:text-blue-700 transition-colors">
                            <span x-text="expanded ? 'ซ่อน' : 'ดูทั้งหมด'">ดูทั้งหมด</span>
                            <i class="fas fa-chevron-down transform transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                2. 🤖 ตลาดบอท/โปรแกรม
            ============================================================ --}}
            <div class="group relative" x-data="{ expanded: false }">
                <div class="relative bg-white/80 backdrop-blur-xl border-2 border-slate-200/50 rounded-3xl p-6 lg:p-8
                            hover:border-purple-500/50
                            hover:shadow-2xl hover:shadow-purple-500/20
                            transform hover:-translate-y-2 hover:scale-[1.02]
                            transition-all duration-500 cursor-pointer"
                     @click="expanded = !expanded">

                    <div class="absolute -inset-1 bg-gradient-to-r from-purple-500 to-pink-500 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>

                    <div class="relative">
                        <div class="w-16 h-16 lg:w-20 lg:h-20 mx-auto mb-6 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center
                                    shadow-lg shadow-purple-500/30
                                    transform group-hover:scale-110 group-hover:rotate-6
                                    transition-all duration-500">
                            <i class="fas fa-robot text-3xl lg:text-4xl text-white drop-shadow-lg"></i>
                        </div>

                        <h3 class="text-xl lg:text-2xl font-black text-white mb-3 text-center">
                            ตลาดบอท/โปรแกรม
                        </h3>

                        <p class="text-sm text-slate-200 text-center mb-4">
                            AI, บอท และซอฟต์แวร์ช่วยธุรกิจ
                        </p>

                        <div class="space-y-2" x-show="expanded" x-collapse>
                            <a href="{{ route('marketplace.v3.category', 'chatbot') }}" class="block px-4 py-3 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">🤖 AI Chatbot</span>
                            </a>
                            <a href="{{ route('marketplace.v3.category', 'trading') }}" class="block px-4 py-3 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">📈 Trading Bot</span>
                            </a>
                            <a href="{{ route('marketplace.v3.category', 'software') }}" class="block px-4 py-3 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">💻 ซอฟต์แวร์/โปรแกรม</span>
                            </a>
                            <a href="{{ route('marketplace.v3.index') }}" class="block px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-500 rounded-xl hover:from-purple-700 hover:to-pink-600 transition-colors shadow-lg">
                                <span class="text-sm font-bold text-white">🏬 ดูทั้งหมด →</span>
                            </a>
                        </div>

                        <button class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-bold text-purple-400 hover:text-purple-700 transition-colors">
                            <span x-text="expanded ? 'ซ่อน' : 'ดูทั้งหมด'">ดูทั้งหมด</span>
                            <i class="fas fa-chevron-down transform transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                3. 💰 ลงทุน
            ============================================================ --}}
            <div class="group relative" x-data="{ expanded: false }">
                <div class="relative bg-white/80 backdrop-blur-xl border-2 border-slate-200/50 rounded-3xl p-6 lg:p-8
                            hover:border-amber-500/50
                            hover:shadow-2xl hover:shadow-amber-500/20
                            transform hover:-translate-y-2 hover:scale-[1.02]
                            transition-all duration-500 cursor-pointer"
                     @click="expanded = !expanded">

                    <div class="absolute -inset-1 bg-gradient-to-r from-amber-500 to-orange-500 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>

                    <div class="relative">
                        <div class="w-16 h-16 lg:w-20 lg:h-20 mx-auto mb-6 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center
                                    shadow-lg shadow-amber-500/30
                                    transform group-hover:scale-110 group-hover:rotate-6
                                    transition-all duration-500">
                            <i class="fas fa-coins text-3xl lg:text-4xl text-white drop-shadow-lg"></i>
                        </div>

                        <h3 class="text-xl lg:text-2xl font-black text-white mb-3 text-center">
                            ลงทุน
                        </h3>

                        <p class="text-sm text-slate-200 text-center mb-4">
                            โอกาสลงทุนและสร้างรายได้แบบ passive
                        </p>

                        <div class="space-y-2" x-show="expanded" x-collapse>
                            <div class="block px-4 py-3 bg-white/5 rounded-xl cursor-not-allowed opacity-75">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-500">💎 TPIX Token</span>
                                    <span class="px-2 py-1 bg-amber-500/20 text-amber-700 rounded-full text-xs font-bold">
                                        เร็วๆ นี้
                                    </span>
                                </div>
                            </div>
                            <div class="block px-4 py-3 bg-white/5 rounded-xl cursor-not-allowed opacity-75">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-500">🌾 Staking / Farming</span>
                                    <span class="px-2 py-1 bg-amber-500/20 text-amber-700 rounded-full text-xs font-bold">
                                        เร็วๆ นี้
                                    </span>
                                </div>
                            </div>
                            <div class="block px-4 py-3 bg-white/5 rounded-xl cursor-not-allowed opacity-75">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-500">₿ Cryptocurrency</span>
                                    <span class="px-2 py-1 bg-amber-500/20 text-amber-700 rounded-full text-xs font-bold">
                                        เร็วๆ นี้
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-bold text-amber-400 hover:text-amber-700 transition-colors">
                            <span x-text="expanded ? 'ซ่อน' : 'ดูทั้งหมด'">ดูทั้งหมด</span>
                            <i class="fas fa-chevron-down transform transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                4. 🛒 ช็อปปิ้ง
            ============================================================ --}}
            <div class="group relative" x-data="{ expanded: false }">
                <div class="relative bg-white/80 backdrop-blur-xl border-2 border-slate-200/50 rounded-3xl p-6 lg:p-8
                            hover:border-pink-500/50
                            hover:shadow-2xl hover:shadow-pink-500/20
                            transform hover:-translate-y-2 hover:scale-[1.02]
                            transition-all duration-500 cursor-pointer"
                     @click="expanded = !expanded">

                    <div class="absolute -inset-1 bg-gradient-to-r from-pink-500 to-rose-500 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>

                    <div class="relative">
                        <div class="w-16 h-16 lg:w-20 lg:h-20 mx-auto mb-6 bg-gradient-to-br from-pink-500 to-rose-500 rounded-2xl flex items-center justify-center
                                    shadow-lg shadow-pink-500/30
                                    transform group-hover:scale-110 group-hover:rotate-6
                                    transition-all duration-500">
                            <i class="fas fa-shopping-cart text-3xl lg:text-4xl text-white drop-shadow-lg"></i>
                        </div>

                        <h3 class="text-xl lg:text-2xl font-black text-white mb-3 text-center">
                            ช็อปปิ้ง
                        </h3>

                        <p class="text-sm text-slate-200 text-center mb-4">
                            ซื้อสินค้าออนไลน์ คุณภาพดี ราคาดี
                        </p>

                        <div class="space-y-2" x-show="expanded" x-collapse>
                            <a href="{{ route('storefront.index') }}" class="block px-4 py-3 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">🏪 ร้านค้าออนไลน์</span>
                            </a>
                            <a href="{{ route('marketplace.v3.index') }}" class="block px-4 py-3 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">🏬 Unified Marketplace</span>
                            </a>
                            <div class="block px-4 py-3 bg-white/5 rounded-xl cursor-not-allowed opacity-75">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-500">🍔 Food Passport</span>
                                    <span class="px-2 py-1 bg-amber-500/20 text-amber-700 rounded-full text-xs font-bold">
                                        เร็วๆ นี้
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-bold text-pink-400 hover:text-pink-700 transition-colors">
                            <span x-text="expanded ? 'ซ่อน' : 'ดูทั้งหมด'">ดูทั้งหมด</span>
                            <i class="fas fa-chevron-down transform transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                5. 🎮 เกม/ดูดวง
            ============================================================ --}}
            <div class="group relative" x-data="{ expanded: false }">
                <div class="relative bg-white/80 backdrop-blur-xl border-2 border-slate-200/50 rounded-3xl p-6 lg:p-8
                            hover:border-violet-500/50
                            hover:shadow-2xl hover:shadow-violet-500/20
                            transform hover:-translate-y-2 hover:scale-[1.02]
                            transition-all duration-500 cursor-pointer"
                     @click="expanded = !expanded">

                    <div class="absolute -inset-1 bg-gradient-to-r from-violet-500 to-purple-500 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>

                    <div class="relative">
                        <div class="w-16 h-16 lg:w-20 lg:h-20 mx-auto mb-6 bg-gradient-to-br from-violet-500 to-purple-500 rounded-2xl flex items-center justify-center
                                    shadow-lg shadow-violet-500/30
                                    transform group-hover:scale-110 group-hover:rotate-6
                                    transition-all duration-500">
                            <i class="fas fa-gamepad text-3xl lg:text-4xl text-white drop-shadow-lg"></i>
                        </div>

                        <h3 class="text-xl lg:text-2xl font-black text-white mb-3 text-center">
                            เกม / ดูดวง
                        </h3>

                        <p class="text-sm text-slate-200 text-center mb-4">
                            สนุกสนาน ผ่อนคลาย ทำนายดวงชะตา
                        </p>

                        <div class="space-y-2" x-show="expanded" x-collapse>
                            <a href="{{ route('tarot.index') }}" class="block px-4 py-3 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">🔮 ดูไพ่ทาโรต์</span>
                            </a>
                            <a href="{{ route('games.index') }}" class="block px-4 py-3 bg-white/10 backdrop-blur-sm rounded-xl hover:bg-white/20 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">🎮 เกมส์ออนไลน์</span>
                            </a>
                            <div class="block px-4 py-3 bg-white/5 rounded-xl cursor-not-allowed opacity-75">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-500">🎰 Tournament</span>
                                    <span class="px-2 py-1 bg-amber-500/20 text-amber-700 rounded-full text-xs font-bold">
                                        เร็วๆ นี้
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-bold text-violet-600 hover:text-violet-700 transition-colors">
                            <span x-text="expanded ? 'ซ่อน' : 'ดูทั้งหมด'">ดูทั้งหมด</span>
                            <i class="fas fa-chevron-down transform transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                6. 🏨 บริการ
            ============================================================ --}}
            <div class="group relative" x-data="{ expanded: false }">
                <div class="relative bg-white/80 backdrop-blur-xl border-2 border-slate-200/50 rounded-3xl p-6 lg:p-8
                            hover:border-cyan-500/50
                            hover:shadow-2xl hover:shadow-cyan-500/20
                            transform hover:-translate-y-2 hover:scale-[1.02]
                            transition-all duration-500 cursor-pointer"
                     @click="expanded = !expanded">

                    <div class="absolute -inset-1 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>

                    <div class="relative">
                        <div class="w-16 h-16 lg:w-20 lg:h-20 mx-auto mb-6 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-2xl flex items-center justify-center
                                    shadow-lg shadow-cyan-500/30
                                    transform group-hover:scale-110 group-hover:rotate-6
                                    transition-all duration-500">
                            <i class="fas fa-concierge-bell text-3xl lg:text-4xl text-white drop-shadow-lg"></i>
                        </div>

                        <h3 class="text-xl lg:text-2xl font-black text-white mb-3 text-center">
                            บริการ
                        </h3>

                        <p class="text-sm text-slate-200 text-center mb-4">
                            บริการครบวงจรสำหรับทุกความต้องการ
                        </p>

                        <div class="space-y-2" x-show="expanded" x-collapse>
                            <a href="{{ route('hotels.index') }}" class="block px-4 py-3 bg-slate-100 rounded-xl hover:bg-cyan-100 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">🏨 จองโรงแรม</span>
                            </a>
                            <div class="block px-4 py-3 bg-white/5 rounded-xl cursor-not-allowed opacity-75">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-500">🌳 Carbon Credit</span>
                                    <span class="px-2 py-1 bg-amber-500/20 text-amber-700 rounded-full text-xs font-bold">
                                        เร็วๆ นี้
                                    </span>
                                </div>
                            </div>
                            <div class="block px-4 py-3 bg-white/5 rounded-xl cursor-not-allowed opacity-75">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-500">💼 POS System</span>
                                    <span class="px-2 py-1 bg-amber-500/20 text-amber-700 rounded-full text-xs font-bold">
                                        เร็วๆ นี้
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-bold text-cyan-400 hover:text-cyan-700 transition-colors">
                            <span x-text="expanded ? 'ซ่อน' : 'ดูทั้งหมด'">ดูทั้งหมด</span>
                            <i class="fas fa-chevron-down transform transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ============================================================
                7. 🎓 เรียนรู้
            ============================================================ --}}
            <div class="group relative" x-data="{ expanded: false }">
                <div class="relative bg-white/80 backdrop-blur-xl border-2 border-slate-200/50 rounded-3xl p-6 lg:p-8
                            hover:border-emerald-500/50
                            hover:shadow-2xl hover:shadow-emerald-500/20
                            transform hover:-translate-y-2 hover:scale-[1.02]
                            transition-all duration-500 cursor-pointer"
                     @click="expanded = !expanded">

                    <div class="absolute -inset-1 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-3xl blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-500"></div>

                    <div class="relative">
                        <div class="w-16 h-16 lg:w-20 lg:h-20 mx-auto mb-6 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-2xl flex items-center justify-center
                                    shadow-lg shadow-emerald-500/30
                                    transform group-hover:scale-110 group-hover:rotate-6
                                    transition-all duration-500">
                            <i class="fas fa-graduation-cap text-3xl lg:text-4xl text-white drop-shadow-lg"></i>
                        </div>

                        <h3 class="text-xl lg:text-2xl font-black text-white mb-3 text-center">
                            เรียนรู้
                        </h3>

                        <p class="text-sm text-slate-200 text-center mb-4">
                            ความรู้และทรัพยากรเพื่อพัฒนาตัวเอง
                        </p>

                        <div class="space-y-2" x-show="expanded" x-collapse>
                            <a href="{{ route('wiki.index') }}" class="block px-4 py-3 bg-slate-100 rounded-xl hover:bg-emerald-100 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">📚 Wiki / คู่มือ</span>
                            </a>
                            <a href="{{ route('documents.how-to-register') }}" class="block px-4 py-3 bg-slate-100 rounded-xl hover:bg-emerald-100 transition-colors">
                                <span class="text-sm font-semibold text-slate-100">📖 วิธีใช้งาน</span>
                            </a>
                            <div class="block px-4 py-3 bg-white/5 rounded-xl cursor-not-allowed opacity-75">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-500">🎓 Academy LMS</span>
                                    <span class="px-2 py-1 bg-amber-500/20 text-amber-700 rounded-full text-xs font-bold">
                                        เร็วๆ นี้
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button class="mt-4 w-full flex items-center justify-center gap-2 text-sm font-bold text-emerald-400 hover:text-emerald-700 transition-colors">
                            <span x-text="expanded ? 'ซ่อน' : 'ดูทั้งหมด'">ดูทั้งหมด</span>
                            <i class="fas fa-chevron-down transform transition-transform duration-300" :class="expanded ? 'rotate-180' : ''"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>

        {{-- Bottom CTA --}}
        <div class="mt-16 text-center">
            <p class="text-slate-200 mb-6">
                ไม่เจออะไรที่ต้องการ?
            </p>
            <a href="{{ route('contact') }}"
               class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700
                      text-white font-bold rounded-2xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50
                      transform hover:scale-105 transition-all duration-300">
                <i class="fas fa-headset"></i>
                <span>ติดต่อเราเลย!</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

{{-- ================================================================
    STATS SECTION - สถิติโครงการ
================================================================ --}}
<section id="stats" class="py-24 lg:py-32 bg-white/5 relative overflow-hidden z-10">
    {{-- Background Pattern - โปร่งใสเพื่อให้เห็น lava lamp ด้านหลัง --}}
    <div class="absolute inset-0 opacity-5"
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
    DYNAMIC SECTIONS - From Homepage Manager
    สามารถแก้ไขผ่าน Admin Panel ที่ /admin/homepage-manager
================================================================ --}}
@if(isset($sections) && $sections->count() > 0)
    @include('frontend.partials.homepage-sections', ['sections' => $sections])
@endif

{{-- ================================================================
    CTA SECTION - Call to Action
================================================================ --}}
<section class="py-12 lg:py-16 bg-white/5">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        {{-- Icon --}}
        <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-600 to-purple-600 rounded-3xl flex items-center justify-center mb-8 animate-pulse-glow">
            <i class="fas fa-rocket text-4xl text-white"></i>
        </div>

        {{-- Headline --}}
        <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-6">
            พร้อมที่จะเริ่มต้นหรือยัง?
        </h2>
        <p class="text-lg lg:text-xl text-slate-200 mb-10 max-w-2xl mx-auto">
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
               class="inline-flex items-center justify-center gap-2 px-10 py-4 bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold text-lg rounded-xl transition-colors">
                <i class="fas fa-headset"></i>
                ติดต่อทีมขาย
            </a>
        </div>

        {{-- Trust Badges --}}
        <div class="mt-12 flex flex-wrap items-center justify-center gap-8 text-slate-500">
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

{{-- ================================================================
    FOOTER - Professional Enterprise Footer
================================================================ --}}
<footer class="bg-slate-900 text-white relative overflow-hidden">
    {{-- Decorative Background --}}
    <div class="absolute inset-0 opacity-5">
        <div class="absolute inset-0" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 30px 30px;"></div>
    </div>
    <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-blue-500/10 to-purple-500/10 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-purple-500/10 to-pink-500/10 rounded-full blur-3xl"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Main Footer Content --}}
        <div class="py-16 lg:py-20 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
            {{-- Column 1: About / Logo --}}
            <div class="lg:col-span-1">
                <div class="mb-6">
                    @if($systemLogo)
                        <img src="{{ Storage::url($systemLogo) }}" alt="{{ $appName }}" class="h-12 w-auto">
                    @else
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                            {{ $appName }}
                        </h3>
                    @endif
                </div>
                <p class="text-slate-400 text-sm leading-relaxed mb-6">
                    แพลตฟอร์ม Affiliate Marketing ระดับ Enterprise ที่ครบครันที่สุด พร้อมระบบ AI, Blockchain และ 20+ ระบบรองรับธุรกิจของคุณ
                </p>
                {{-- Trust Badges --}}
                <div class="flex flex-wrap gap-3">
                    <div class="px-3 py-1.5 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400 text-xs font-semibold">
                        <i class="fas fa-shield-check mr-1"></i> SSL Secure
                    </div>
                    <div class="px-3 py-1.5 bg-blue-500/10 border border-blue-500/20 rounded-lg text-blue-400 text-xs font-semibold">
                        <i class="fas fa-award mr-1"></i> Enterprise
                    </div>
                </div>
            </div>

            {{-- Column 2: Products --}}
            <div>
                <h4 class="text-white font-bold text-lg mb-6">ผลิตภัณฑ์</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('documents.mlm-commission') }}" class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-chevron-right text-xs text-blue-400"></i> ระบบ Affiliate
                    </a></li>
                    <li><a href="{{ route('marketplace.v3.category', 'chatbot') }}" class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-chevron-right text-xs text-blue-400"></i> AI Bot Marketplace
                    </a></li>
                    <li><a href="#app-download" class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-chevron-right text-xs text-blue-400"></i> Mobile App
                    </a></li>
                    <li><a href="{{ route('storefront.index') }}" class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-chevron-right text-xs text-blue-400"></i> E-Commerce
                    </a></li>
                    <li><a href="{{ route('documents.blockchain-tpix') }}" class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-chevron-right text-xs text-blue-400"></i> Blockchain / TPIX
                    </a></li>
                </ul>
            </div>

            {{-- Column 3: Resources --}}
            <div>
                <h4 class="text-white font-bold text-lg mb-6">ทรัพยากร</h4>
                <ul class="space-y-3">
                    <li><a href="{{ route('about') }}" class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-chevron-right text-xs text-purple-400"></i> เกี่ยวกับเรา
                    </a></li>
                    <li><a href="{{ route('contact') }}" class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-chevron-right text-xs text-purple-400"></i> ติดต่อเรา
                    </a></li>
                    <li><a href="{{ route('wiki.index') }}" class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-chevron-right text-xs text-purple-400"></i> คู่มือการใช้งาน
                    </a></li>
                    <li><a href="{{ route('documents.how-to-register') }}" class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-chevron-right text-xs text-purple-400"></i> วิธีการสมัครสมาชิก
                    </a></li>
                    <li><a href="{{ route('wiki.index') }}" class="text-slate-400 hover:text-white transition-colors text-sm flex items-center gap-2">
                        <i class="fas fa-chevron-right text-xs text-purple-400"></i> เอกสารประกอบ
                    </a></li>
                </ul>
            </div>

            {{-- Column 4: Support & Contact --}}
            <div>
                <h4 class="text-white font-bold text-lg mb-6">ติดต่อ & สนับสนุน</h4>
                <ul class="space-y-3 mb-6">
                    <li class="text-slate-400 text-sm flex items-start gap-2">
                        <i class="fas fa-headset text-green-400 mt-1"></i>
                        <div>
                            <div class="text-white font-semibold">24/7 Support</div>
                            <div class="text-xs">พร้อมช่วยเหลือตลอด 24 ชั่วโมง</div>
                        </div>
                    </li>
                    <li class="text-slate-400 text-sm flex items-start gap-2">
                        <i class="fas fa-envelope text-blue-400 mt-1"></i>
                        <div>
                            <div class="text-white font-semibold">Email</div>
                            <a href="mailto:support@thaiprompt.com" class="text-xs hover:text-white transition-colors">support@thaiprompt.com</a>
                        </div>
                    </li>
                    <li class="text-slate-400 text-sm flex items-start gap-2">
                        <i class="fab fa-line text-green-400 mt-1"></i>
                        <div>
                            <div class="text-white font-semibold">LINE Official</div>
                            <div class="text-xs">@thaiprompt</div>
                        </div>
                    </li>
                </ul>

                {{-- Social Media --}}
                <div>
                    <h5 class="text-white font-semibold text-sm mb-3">ติดตามเรา</h5>
                    <div class="flex gap-3">
                        <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-blue-600 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-blue-400 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-green-500 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-line"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-gradient-to-r hover:from-purple-500 hover:to-pink-500 rounded-lg flex items-center justify-center transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Divider --}}
        <div class="border-t border-slate-800"></div>

        {{-- Bottom Footer --}}
        <div class="py-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="text-slate-400 text-sm text-center md:text-left">
                <p>© {{ date('Y') }} {{ $appName }}. All rights reserved.</p>
                <p class="text-xs mt-1">Version {{ $version }} | Made with <i class="fas fa-heart text-red-500"></i> in Thailand</p>
            </div>

            {{-- Footer Links --}}
            <div class="flex flex-wrap items-center justify-center gap-6 text-slate-400 text-sm">
                <a href="{{ route('page.show', 'privacy-policy') }}" class="hover:text-white transition-colors">นโยบายความเป็นส่วนตัว</a>
                <a href="{{ route('page.show', 'terms-of-service') }}" class="hover:text-white transition-colors">เงื่อนไขการใช้งาน</a>
                <a href="{{ route('cookie-policy') }}" class="hover:text-white transition-colors">นโยบายคุกกี้</a>
            </div>
        </div>
    </div>
</footer>

@endsection

@push('styles')
<style>
/**
 * Fireflies Background Effect - หิ่งห้อยเรืองแสง
 * (แบบเดียวกับหน้า Login)
 */

/* ✨ Firefly Effect */
.firefly {
    position: fixed;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    pointer-events: none;
    z-index: 1;
}

.firefly::before,
.firefly::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    transform-origin: center center;
}

.firefly::before {
    background: radial-gradient(circle, rgba(129, 140, 248, 0.9) 0%, rgba(99, 102, 241, 0.6) 40%, transparent 70%);
    animation: fireflyGlow 2s ease-in-out infinite alternate;
}

.firefly::after {
    background: radial-gradient(circle, rgba(255, 255, 255, 1) 0%, rgba(199, 210, 254, 0.8) 30%, transparent 60%);
    width: 4px;
    height: 4px;
    left: 1px;
    top: 1px;
    animation: fireflyCore 1.5s ease-in-out infinite alternate;
}

@keyframes fireflyGlow {
    0% { transform: scale(1); opacity: 0.3; }
    100% { transform: scale(2.5); opacity: 0.8; }
}

@keyframes fireflyCore {
    0% { opacity: 0.8; }
    100% { opacity: 1; }
}
</style>
@endpush

@push('scripts')
<script>
/**
 * สร้างเอฟเฟคหิ่งห้อยเรืองแสง (แบบหน้า Login)
 */
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('fireflies');
    if (!container) return;

    // จำนวนหิ่งห้อย
    const isMobile = window.innerWidth < 768;
    const fireflyCount = isMobile ? 15 : 25;

    // สร้างหิ่งห้อยแต่ละตัว
    for (let i = 0; i < fireflyCount; i++) {
        createFirefly(container, i);
    }
});

/**
 * สร้างหิ่งห้อยตัวเดียว
 */
function createFirefly(container, index) {
    const firefly = document.createElement('div');
    firefly.className = 'firefly';

    // สุ่มตำแหน่งเริ่มต้น
    const startX = Math.random() * 100;
    const startY = 100 + Math.random() * 20;

    firefly.style.left = startX + 'vw';
    firefly.style.bottom = -startY + 'px';

    // สุ่มค่า animation
    const duration = 15 + Math.random() * 25;
    const delay = Math.random() * 10;
    const drift = -50 + Math.random() * 100;
    const scale = 0.6 + Math.random() * 0.8;

    firefly.style.transform = `scale(${scale})`;

    // สุ่มสี
    const colors = [
        'rgba(129, 140, 248, 0.9)', // indigo
        'rgba(167, 139, 250, 0.9)', // purple
        'rgba(96, 165, 250, 0.9)',  // blue
        'rgba(192, 132, 252, 0.9)', // violet
        'rgba(129, 230, 217, 0.8)', // teal
    ];
    const color = colors[Math.floor(Math.random() * colors.length)];

    firefly.style.setProperty('--duration', duration + 's');
    firefly.style.setProperty('--delay', delay + 's');
    firefly.style.setProperty('--drift', drift + 'px');
    firefly.style.setProperty('--glow-color', color);

    // สร้าง keyframe animation
    const animationName = `fireflyFloat_${index}`;
    const keyframes = `
        @keyframes ${animationName} {
            0% {
                bottom: -20px;
                left: ${startX}vw;
                opacity: 0;
            }
            10% {
                opacity: ${0.5 + Math.random() * 0.5};
            }
            30% {
                left: calc(${startX}vw + ${drift * 0.3}px);
            }
            50% {
                left: calc(${startX}vw + ${drift}px);
            }
            70% {
                left: calc(${startX}vw + ${drift * 0.7}px);
            }
            90% {
                opacity: ${0.3 + Math.random() * 0.4};
            }
            100% {
                bottom: 110vh;
                left: calc(${startX}vw + ${drift * 0.5}px);
                opacity: 0;
            }
        }
    `;

    // เพิ่ม keyframes เข้า stylesheet
    const style = document.createElement('style');
    style.textContent = keyframes;
    document.head.appendChild(style);

    // ใช้ animation
    firefly.style.animation = `${animationName} ${duration}s ${delay}s ease-in-out infinite`;

    container.appendChild(firefly);
}
</script>
@endpush
