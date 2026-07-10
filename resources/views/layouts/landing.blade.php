{{--
/**
 * Landing Page Layout - Professional & Trustworthy
 *
 * Layout สำหรับหน้าแรกและ Landing Pages ที่ต้องการ:
 * - ซ่อน Sidebar เสมอ (ใช้ Burger Menu แทน)
 * - Top Navigation Bar พร้อมลิงก์ไปส่วนสำคัญ
 * - ดีไซน์มืออาชีพ น่าเชื่อถือ
 * - Dark/Light mode support
 *
 * @version 1.0.0
 * @author Thaiprompt Team
 * @created 2025-12-03
 */
--}}
<!DOCTYPE html>
<html lang="th" x-data="landingPageManager()" :class="{ 'dark': darkMode }" x-init="init()">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // ดึงจาก SiteSetting (ที่แอดมินตั้งค่าใน admin/site-settings)
        $siteSettings = \App\Models\SiteSetting::getSetting();
        $appName = $siteSettings->site_name ?? 'TP-Affiliate Pro';
        $favicon = $siteSettings->favicon;
        $systemLogo = $siteSettings->logo;
    @endphp

    <title>@yield('title', 'หน้าแรก') - {{ $appName }}</title>

    {{-- Meta Tags for SEO --}}
    <meta name="description" content="@yield('meta_description', 'TP-Affiliate Pro - ระบบ Affiliate Marketing ระดับ Enterprise ที่ครบครันที่สุดในประเทศไทย')">
    <meta name="keywords" content="affiliate, marketing, mlm, e-commerce, thailand, ระบบ affiliate, การตลาด, ธุรกิจออนไลน์">
    <meta name="author" content="Thaiprompt">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('title', 'หน้าแรก') - {{ $appName }}">
    <meta property="og:description" content="@yield('meta_description', 'TP-Affiliate Pro - ระบบ Affiliate Marketing ระดับ Enterprise')">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ $systemLogo ? asset('storage/' . $systemLogo) : asset('images/og-image.png') }}">

    @if($favicon)
        <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $favicon) }}">
    @endif

    {{-- Google Fonts - Kanit (Thai) + Inter (English) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Kanit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /**
         * Landing Page Custom Styles
         * สไตล์เฉพาะสำหรับ Landing Page
         */

        /* Font Family */
        body {
            font-family: 'Kanit', 'Inter', sans-serif;
        }

        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #1e3a5f, #7c3aed);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #2d4a6f, #8b5cf6);
        }

        /* Prevent FOUC */
        [x-cloak] {
            display: none !important;
        }

        /* Custom Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(124, 58, 237, 0.3); }
            50% { box-shadow: 0 0 40px rgba(124, 58, 237, 0.6); }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-fade-in-down {
            animation: fadeInDown 0.8s ease-out forwards;
        }

        .animate-slide-in-left {
            animation: slideInLeft 0.8s ease-out forwards;
        }

        .animate-slide-in-right {
            animation: slideInRight 0.8s ease-out forwards;
        }

        .animate-float {
            animation: float 4s ease-in-out infinite;
        }

        .animate-pulse-glow {
            animation: pulse-glow 3s ease-in-out infinite;
        }

        /* Gradient Text */
        .gradient-text {
            background: linear-gradient(135deg, #1e3a5f 0%, #7c3aed 50%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .gradient-text-secondary {
            background: linear-gradient(135deg, #7c3aed 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Glass Effect */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-dark {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Primary Button Style */
        .btn-primary {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d4a6f 100%);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2d4a6f 0%, #3d5a7f 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(30, 58, 95, 0.4);
        }

        /* Secondary Button Style */
        .btn-secondary {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 100%);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.4);
        }

        /* Accent Button Style */
        .btn-accent {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: #1e3a5f;
            transition: all 0.3s ease;
        }

        .btn-accent:hover {
            background: linear-gradient(135deg, #fbbf24 0%, #fcd34d 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
        }

        /* Card Hover Effect */
        .card-hover {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
    </style>

    @stack('styles')

    {{-- Structured Data (Schema.org JSON-LD) — Organization + WebSite สำหรับ Google AI Overviews / Gemini --}}
    {!! render_global_structured_data() !!}
</head>
<body class="antialiased bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 transition-colors duration-300">

    {{-- ================================================================
        TOP NAVIGATION BAR - Fixed
    ================================================================ --}}
    <header class="fixed top-0 left-0 right-0 z-[100] transition-all duration-300"
            :class="scrolled ? 'glass-dark shadow-lg' : 'bg-transparent'"
            @scroll.window="scrolled = window.scrollY > 50">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">

                {{-- Logo - ใหญ่ขึ้นและเท่ --}}
                <a href="{{ route('home') }}" class="flex items-center gap-4 group">
                    @if($systemLogo)
                        <div class="relative">
                            <img src="{{ asset('storage/' . $systemLogo) }}"
                                 alt="{{ $appName }}"
                                 class="h-12 lg:h-16 w-auto transition-all duration-300 group-hover:scale-110 drop-shadow-lg">
                            {{-- Glow effect on hover --}}
                            <div class="absolute inset-0 bg-blue-500/20 rounded-full blur-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300 -z-10"></div>
                        </div>
                    @else
                        <div class="w-12 h-12 lg:w-16 lg:h-16 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-500 rounded-2xl flex items-center justify-center shadow-lg shadow-purple-500/30 transition-all duration-300 group-hover:scale-110 group-hover:shadow-purple-500/50">
                            <span class="text-white font-black text-xl lg:text-2xl">TP</span>
                        </div>
                        {{-- แสดงชื่อเฉพาะเมื่อไม่มีโลโก้ --}}
                        <span class="hidden sm:block text-xl lg:text-2xl font-bold gradient-text transition-transform duration-300 group-hover:translate-x-1">{{ $appName }}</span>
                    @endif
                </a>

                {{-- Desktop Navigation
                     ใช้ route('home') . '#anchor' เพื่อให้คลิกจากหน้าไหนก็ scroll ไปยัง section ของหน้า home ได้
                     section IDs ที่มีจริงในหน้า home ใหม่: #services / #wallet / #how / #demo / #asia --}}
                <div class="hidden lg:flex items-center gap-6 xl:gap-8">
                    <a href="{{ route('home') }}" class="text-slate-600 dark:text-slate-300 hover:text-blue-900 dark:hover:text-blue-400 font-medium transition-colors">
                        หน้าแรก
                    </a>
                    <a href="{{ route('home') }}#services" class="text-slate-600 dark:text-slate-300 hover:text-blue-900 dark:hover:text-blue-400 font-medium transition-colors">
                        บริการ
                    </a>
                    <a href="{{ route('home') }}#wallet" class="text-slate-600 dark:text-slate-300 hover:text-blue-900 dark:hover:text-blue-400 font-medium transition-colors">
                        Wallet
                    </a>
                    <a href="{{ route('home') }}#how" class="text-slate-600 dark:text-slate-300 hover:text-blue-900 dark:hover:text-blue-400 font-medium transition-colors">
                        วิธีใช้งาน
                    </a>
                    <a href="{{ route('home') }}#demo" class="text-slate-600 dark:text-slate-300 hover:text-blue-900 dark:hover:text-blue-400 font-medium transition-colors">
                        สื่อการเรียนรู้
                    </a>
                    <a href="{{ route('home') }}#asia" class="text-slate-600 dark:text-slate-300 hover:text-blue-900 dark:hover:text-blue-400 font-medium transition-colors">
                        เครือข่าย
                    </a>
                    <a href="{{ route('about') }}" class="text-slate-600 dark:text-slate-300 hover:text-blue-900 dark:hover:text-blue-400 font-medium transition-colors">
                        เกี่ยวกับ
                    </a>
                    <a href="{{ route('contact') }}" class="text-slate-600 dark:text-slate-300 hover:text-blue-900 dark:hover:text-blue-400 font-medium transition-colors">
                        ติดต่อ
                    </a>

                    {{-- ปุ่มดูดวง - เด่นชัด --}}
                    <a href="{{ route('horoscope.home') }}"
                       class="relative inline-flex items-center gap-1.5 px-4 py-2 text-sm font-bold rounded-xl bg-gradient-to-r from-violet-500 to-purple-600 hover:from-violet-600 hover:to-purple-700 text-white shadow-lg hover:shadow-xl hover:shadow-purple-500/30 transition-all duration-300 transform hover:scale-105"
                       title="ดูดวงออนไลน์ฟรี - ราศี ไพ่ทาโรต์ เลขศาสตร์ ทำนายฝัน">
                        <span class="text-base">🔮</span>
                        <span>ดูดวง</span>
                        <span class="absolute -top-1 -right-1 flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-400"></span>
                        </span>
                    </a>
                </div>

                {{-- Right Actions --}}
                <div class="flex items-center gap-3">
                    {{-- Auth Buttons (Desktop) --}}
                    <div class="hidden lg:flex items-center gap-3">
                        @auth
                            <a href="{{ route('user.dashboard') }}"
                               class="px-5 py-2.5 btn-primary text-white font-semibold rounded-xl">
                                <i class="fas fa-tachometer-alt mr-2"></i>
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-5 py-2.5 text-slate-700 dark:text-slate-200 hover:text-blue-900 dark:hover:text-blue-400 font-medium transition-colors">
                                เข้าสู่ระบบ
                            </a>
                            <a href="{{ route('register') }}"
                               class="px-5 py-2.5 btn-secondary text-white font-semibold rounded-xl">
                                ลงทะเบียนฟรี
                            </a>
                        @endauth
                    </div>

                    {{-- Burger Menu Button --}}
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <i class="fas fa-bars text-xl text-slate-700 dark:text-slate-200" x-show="!mobileMenuOpen"></i>
                        <i class="fas fa-times text-xl text-slate-700 dark:text-slate-200" x-show="mobileMenuOpen" x-cloak></i>
                    </button>
                </div>
            </div>
        </nav>
    </header>

    {{-- ================================================================
        MOBILE MENU - อยู่นอก header เพื่อหลีกเลี่ยง stacking context issues
    ================================================================ --}}

    {{-- Mobile/Burger Menu Overlay --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/50 z-[9998]"
         @click="mobileMenuOpen = false"
         x-cloak>
    </div>

    {{-- Mobile/Burger Menu Panel --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 bottom-0 w-80 max-w-full glass-dark z-[9999] overflow-y-auto"
         x-cloak>

        <div class="p-6">
            {{-- Menu Header --}}
            <div class="flex items-center justify-between mb-8">
                <span class="text-lg font-bold text-white">เมนู</span>
                <button @click="mobileMenuOpen = false"
                        class="p-2 rounded-lg hover:bg-slate-700 transition-colors">
                    <i class="fas fa-times text-xl text-white"></i>
                </button>
            </div>

            {{-- Menu Links — ใช้ route('home') . '#anchor' เพื่อให้คลิกจากหน้าไหนก็ทำงาน
                 section IDs ที่มีจริงในหน้า home: #services / #wallet / #how / #demo / #asia --}}
            <nav class="space-y-2">
                <a href="{{ route('home') }}" @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('home') ? 'bg-blue-900/30 text-white' : 'hover:bg-white/10 text-slate-200' }} font-medium transition-colors">
                    <i class="fas fa-home w-5 text-center"></i>
                    หน้าแรก
                </a>
                <a href="{{ route('home') }}#services" @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-slate-200 transition-colors">
                    <i class="fas fa-th-large w-5 text-center"></i>
                    บริการ
                </a>
                <a href="{{ route('home') }}#wallet" @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-slate-200 transition-colors">
                    <i class="fas fa-wallet w-5 text-center"></i>
                    Wallet
                </a>
                <a href="{{ route('home') }}#how" @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-slate-200 transition-colors">
                    <i class="fas fa-route w-5 text-center"></i>
                    วิธีใช้งาน
                </a>
                <a href="{{ route('home') }}#demo" @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-slate-200 transition-colors">
                    <i class="fas fa-play-circle w-5 text-center"></i>
                    สื่อการเรียนรู้
                </a>
                <a href="{{ route('home') }}#asia" @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-slate-200 transition-colors">
                    <i class="fas fa-globe-asia w-5 text-center"></i>
                    เครือข่ายเอเชีย
                </a>
                <a href="{{ route('about') }}" @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('about') ? 'bg-blue-900/30 text-white' : 'hover:bg-white/10 text-slate-200' }} transition-colors">
                    <i class="fas fa-info-circle w-5 text-center"></i>
                    เกี่ยวกับเรา
                </a>
                <a href="{{ route('contact') }}" @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('contact') ? 'bg-blue-900/30 text-white' : 'hover:bg-white/10 text-slate-200' }} transition-colors">
                    <i class="fas fa-envelope w-5 text-center"></i>
                    ติดต่อเรา
                </a>

                {{-- ดูดวงออนไลน์ --}}
                <a href="{{ route('horoscope.home') }}" @click="mobileMenuOpen = false"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl bg-gradient-to-r from-violet-600/30 to-purple-600/30 text-white font-medium transition-colors mt-2">
                    <span class="w-5 text-center text-lg">🔮</span>
                    ดูดวงออนไลน์ฟรี
                </a>

                {{-- Wiki Section --}}
                <div class="pt-4 border-t border-white/10">
                    <p class="px-4 py-2 text-xs uppercase tracking-wider text-slate-400 font-semibold">
                        ข้อมูลเพิ่มเติม
                    </p>
                    <a href="{{ route('wiki.index') }}" @click="mobileMenuOpen = false"
                       class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 text-slate-200 transition-colors">
                        <i class="fas fa-book w-5 text-center"></i>
                        คู่มือการใช้งาน
                    </a>
                </div>
            </nav>

            {{-- Auth Buttons --}}
            <div class="mt-8 pt-6 border-t border-white/10 space-y-3">
                @auth
                    <a href="{{ route('user.dashboard') }}"
                       class="flex items-center justify-center gap-2 w-full px-5 py-3 btn-primary text-white font-semibold rounded-xl">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center justify-center gap-2 w-full px-5 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl transition-colors">
                            <i class="fas fa-sign-out-alt"></i>
                            ออกจากระบบ
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="flex items-center justify-center gap-2 w-full px-5 py-3 bg-white/10 hover:bg-white/20 text-white font-semibold rounded-xl transition-colors">
                        <i class="fas fa-sign-in-alt"></i>
                        เข้าสู่ระบบ
                    </a>
                    <a href="{{ route('register') }}"
                       class="flex items-center justify-center gap-2 w-full px-5 py-3 btn-secondary text-white font-semibold rounded-xl">
                        <i class="fas fa-user-plus"></i>
                        ลงทะเบียนฟรี
                    </a>
                @endauth
            </div>

            {{-- App Info --}}
            <div class="mt-8 pt-6 border-t border-white/10">
                <div class="text-center text-slate-400 text-sm">
                    <p>{{ $appName }}</p>
                    <p class="mt-1">Version 3.310.0</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================
        MAIN CONTENT
    ================================================================ --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer is defined in individual pages (e.g., home-new.blade.php) --}}

    {{-- ================================================================
        SCRIPTS
    ================================================================ --}}
    <script>
        /**
         * Landing Page Manager
         * จัดการ state และ interactions ของ landing page
         */
        function landingPageManager() {
            return {
                // State
                mobileMenuOpen: false,
                scrolled: false,

                // Init
                init() {
                    // Check scroll position
                    this.scrolled = window.scrollY > 50;

                    console.log('🚀 Landing Page initialized');
                }
            };
        }
    </script>

    @stack('scripts')
</body>
</html>
