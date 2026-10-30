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

    {{-- ธีมโนวา: แถบหัวอยู่ในเลย์เอาต์ (เรนเดอร์หลัง stack) จึงโหลดสไตล์ตรงนี้เอง --}}
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@400;500;600;700&family=Trirong:wght@600;700&family=Cinzel:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('theme-nova/nova.css') }}?v={{ is_file(public_path('theme-nova/nova.css')) ? filemtime(public_path('theme-nova/nova.css')) : 1 }}">
    <link rel="stylesheet" href="{{ asset('theme-nova/nova-tw.css') }}?v={{ is_file(public_path('theme-nova/nova-tw.css')) ? filemtime(public_path('theme-nova/nova-tw.css')) : 1 }}">
    @stack('styles')

    {{-- Structured Data (Schema.org JSON-LD) — Organization + WebSite สำหรับ Google AI Overviews / Gemini --}}
    {!! render_global_structured_data() !!}
</head>
<body class="antialiased bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 transition-colors duration-300 nv-tw">

    {{-- ธีมโนวา: แถบหัวชุดเดียวกับหน้าสาธารณะอื่น — แบบทึบลอยติดบน (หน้าในเลย์เอาต์นี้เว้นที่ด้านบนไว้ให้แถบหัวแบบลอยอยู่แล้ว) --}}
    <x-nova.header :solid="true" :fixed="true" :search="true" :active="request()->is('horoscope*') ? 'fortune' : ''" />

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
