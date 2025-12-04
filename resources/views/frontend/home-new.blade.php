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
    PLATFORM DEMO SECTION - สื่อการเรียนรู้ระบบ
================================================================ --}}
<section id="demo" class="py-20 lg:py-28 bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 relative overflow-hidden">
    {{-- Background Effects --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image:
            linear-gradient(to right, rgba(99, 102, 241, 0.15) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(99, 102, 241, 0.15) 1px, transparent 1px);
            background-size: 40px 40px;"></div>
    </div>
    <div class="absolute top-20 left-10 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
    <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>

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

        {{-- Demo Cards Grid --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8 mb-12">
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

        {{-- Features Grid - แต่ละ Card มีลิงก์ไปหน้าเอกสารละเอียด --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            {{-- Feature 1: All-in-One --}}
            <a href="{{ route('documents.all-in-one') }}" class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 block hover:border-blue-500 dark:hover:border-blue-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-layer-group text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    All-in-One Platform
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                    รวมทุกระบบไว้ในที่เดียว Affiliate, MLM, E-Commerce, AI Bot และอีก 20+ ระบบ ไม่ต้องซื้อแยก ประหยัดกว่า
                </p>
                <div class="flex items-center text-blue-600 dark:text-blue-400 font-medium text-sm group-hover:underline">
                    <span>อ่านเอกสารเพิ่มเติม</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            {{-- Feature 2: AI Powered --}}
            <a href="{{ route('documents.ai-automation') }}" class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 block hover:border-purple-500 dark:hover:border-purple-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-robot text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    AI-Powered Automation
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                    ระบบ AI อัจฉริยะ ช่วยตอบแชท วิเคราะห์ข้อมูล และทำงานอัตโนมัติ 24 ชั่วโมง ลดต้นทุน เพิ่มประสิทธิภาพ
                </p>
                <div class="flex items-center text-purple-600 dark:text-purple-400 font-medium text-sm group-hover:underline">
                    <span>อ่านเอกสารเพิ่มเติม</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            {{-- Feature 3: Blockchain --}}
            <a href="{{ route('documents.blockchain-tpix') }}" class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 block hover:border-amber-500 dark:hover:border-amber-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-link text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    Blockchain & TPIX Token
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                    รองรับ Cryptocurrency และ Token TPIX สำหรับระบบ Staking, Farming และการชำระเงินแบบ Web3
                </p>
                <div class="flex items-center text-amber-600 dark:text-amber-400 font-medium text-sm group-hover:underline">
                    <span>อ่าน Whitepaper</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            {{-- Feature 4: Multi-Currency Wallet --}}
            <a href="{{ route('documents.multi-currency-wallet') }}" class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 block hover:border-emerald-500 dark:hover:border-emerald-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-wallet text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    Multi-Currency Wallet
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                    กระเป๋าเงินหลายสกุล THB, USD, Crypto พร้อมระบบถอนเงินหลายช่องทาง Bank, PromptPay, Crypto
                </p>
                <div class="flex items-center text-emerald-600 dark:text-emerald-400 font-medium text-sm group-hover:underline">
                    <span>อ่านเอกสารเพิ่มเติม</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            {{-- Feature 5: MLM System --}}
            <a href="{{ route('documents.mlm-commission') }}" class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 block hover:border-pink-500 dark:hover:border-pink-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-pink-500 to-rose-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-sitemap text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    MLM & Commission System
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                    ระบบ Multi-Level Marketing ไม่จำกัดชั้น คำนวณคอมมิชชันอัตโนมัติ พร้อมรายงานละเอียด
                </p>
                <div class="flex items-center text-pink-600 dark:text-pink-400 font-medium text-sm group-hover:underline">
                    <span>อ่านเอกสารเพิ่มเติม</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            {{-- Feature 6: Security --}}
            <a href="{{ route('documents.enterprise-security') }}" class="group card-hover bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 block hover:border-cyan-500 dark:hover:border-cyan-500 transition-all">
                <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                    <i class="fas fa-shield-alt text-2xl text-white"></i>
                </div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3">
                    Enterprise Security
                </h3>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed mb-4">
                    ความปลอดภัยระดับ Enterprise พร้อม 2FA, SSL, IP Whitelist และ License System
                </p>
                <div class="flex items-center text-cyan-600 dark:text-cyan-400 font-medium text-sm group-hover:underline">
                    <span>อ่านเอกสารเพิ่มเติม</span>
                    <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>
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
