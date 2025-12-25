<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TP-Affiliate - ระบบ Affiliate Marketing ครบวงจร</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /**
         * Lava Lamp Background Effect
         *
         * สร้าง animated RGB blobs ที่เคลื่อนไหวแบบ lava lamp
         * ใช้ CSS animations และ blur filters
         */

        /* พื้นหลัง lava lamp - fixed position */
        .lava-lamp-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Dark mode background */
        .dark .lava-lamp-bg {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        /* Blob แต่ละตัว */
        .lava-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.7;
            animation-timing-function: ease-in-out;
            animation-iteration-count: infinite;
            animation-direction: alternate;
        }

        /* Blob ขนาดและสีต่างๆ */
        .lava-blob-1 {
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 0, 255, 0.8) 0%, rgba(147, 51, 234, 0.6) 100%);
            top: -10%;
            left: -10%;
            animation: float-1 20s ease-in-out infinite;
        }

        .lava-blob-2 {
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.8) 0%, rgba(37, 99, 235, 0.6) 100%);
            top: 20%;
            right: -15%;
            animation: float-2 25s ease-in-out infinite;
        }

        .lava-blob-3 {
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.8) 0%, rgba(219, 39, 119, 0.6) 100%);
            bottom: -10%;
            left: 20%;
            animation: float-3 30s ease-in-out infinite;
        }

        .lava-blob-4 {
            width: 550px;
            height: 550px;
            background: radial-gradient(circle, rgba(34, 211, 238, 0.8) 0%, rgba(6, 182, 212, 0.6) 100%);
            bottom: 10%;
            right: 10%;
            animation: float-4 22s ease-in-out infinite;
        }

        .lava-blob-5 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(251, 146, 60, 0.8) 0%, rgba(249, 115, 22, 0.6) 100%);
            top: 40%;
            left: 40%;
            animation: float-5 28s ease-in-out infinite;
        }

        .lava-blob-6 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.8) 0%, rgba(139, 92, 246, 0.6) 100%);
            top: 60%;
            right: 30%;
            animation: float-6 24s ease-in-out infinite;
        }

        /* Animation keyframes สำหรับ blobs */
        @keyframes float-1 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(100px, 150px) scale(1.2);
            }
        }

        @keyframes float-2 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(-120px, 180px) scale(1.3);
            }
        }

        @keyframes float-3 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(150px, -100px) scale(1.1);
            }
        }

        @keyframes float-4 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(-100px, -120px) scale(1.25);
            }
        }

        @keyframes float-5 {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg) scale(1);
            }
            50% {
                transform: translate(80px, 100px) rotate(180deg) scale(1.15);
            }
        }

        @keyframes float-6 {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg) scale(1);
            }
            50% {
                transform: translate(-90px, 110px) rotate(-180deg) scale(1.2);
            }
        }

        /* Glass overlay layer */
        .glass-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            backdrop-filter: blur(100px);
            -webkit-backdrop-filter: blur(100px);
            pointer-events: none;
            z-index: 0;
        }

        /* Content container - scrollable */
        .content-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
        }

        /* Glassmorphism card effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        .dark .glass-card {
            background: rgba(17, 24, 39, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(139, 92, 246, 0.6);
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(139, 92, 246, 0.8);
        }
    </style>
</head>
<body class="overflow-x-hidden">

    <!-- ====================================== -->
    <!-- Lava Lamp Background (Fixed)         -->
    <!-- ====================================== -->
    <div class="lava-lamp-bg">
        <!-- Animated RGB Blobs -->
        <div class="lava-blob lava-blob-1"></div>
        <div class="lava-blob lava-blob-2"></div>
        <div class="lava-blob lava-blob-3"></div>
        <div class="lava-blob lava-blob-4"></div>
        <div class="lava-blob lava-blob-5"></div>
        <div class="lava-blob lava-blob-6"></div>
    </div>

    <!-- Glass Overlay Layer -->
    <div class="glass-overlay"></div>

    <!-- ====================================== -->
    <!-- Content Wrapper (Scrollable)         -->
    <!-- ====================================== -->
    <div class="content-wrapper">

        <!-- Header -->
        <header class="glass-card sticky top-0 z-50 border-b border-white/20 dark:border-white/10">
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-2xl font-bold text-white">TP</span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white drop-shadow-lg">TP-Affiliate</h1>
                            <p class="text-sm text-white/80">ระบบ Affiliate Marketing ครบวงจร</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        @guest
                            <a href="{{ route('login') }}" class="px-4 py-2 text-white/90 hover:text-white transition-all hover:scale-105">
                                เข้าสู่ระบบ
                            </a>
                            <a href="{{ route('register') }}" class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white rounded-xl transition-all shadow-lg hover:shadow-2xl hover:scale-105">
                                สมัครสมาชิก
                            </a>
                        @else
                            <a href="{{ route('user.dashboard') }}" class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-600 hover:to-pink-600 text-white rounded-xl transition-all shadow-lg hover:shadow-2xl hover:scale-105">
                                แดชบอร์ด
                            </a>
                        @endguest
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-20 md:py-32 text-center">
            <div class="max-w-4xl mx-auto">
                <!-- Main Title with Glass Effect -->
                <div class="glass-card rounded-3xl p-8 md:p-12 mb-8 animate-fade-in">
                    <h2 class="text-4xl md:text-6xl lg:text-7xl font-black text-white mb-6 leading-tight drop-shadow-2xl">
                        ยินดีต้อนรับสู่
                        <span class="block mt-2 bg-gradient-to-r from-yellow-300 via-pink-300 to-purple-300 bg-clip-text text-transparent">
                            TP-Affiliate
                        </span>
                    </h2>
                    <p class="text-xl md:text-2xl text-white/90 mb-8 leading-relaxed">
                        ระบบ Affiliate Marketing ครบวงจร<br class="hidden md:block">
                        พร้อมระบบ MLM, E-Commerce, AI Bots และอื่นๆ อีกมากมาย
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex items-center justify-center flex-wrap gap-4">
                        <a href="{{ route('storefront.index') }}"
                           class="group px-8 py-4 bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 hover:from-orange-600 hover:via-red-600 hover:to-pink-600 text-white rounded-2xl font-bold text-lg transition-all shadow-2xl hover:shadow-pink-500/50 hover:scale-110 flex items-center gap-3">
                            <svg class="w-6 h-6 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            🛒 ช้อปปิ้งเลย!
                        </a>
                        <a href="{{ route('register') }}"
                           class="px-8 py-4 bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 hover:from-blue-600 hover:via-purple-600 hover:to-pink-600 text-white rounded-2xl font-bold text-lg transition-all shadow-2xl hover:shadow-purple-500/50 hover:scale-110">
                            เริ่มต้นใช้งาน ฟรี!
                        </a>
                        <a href="#systems"
                           class="px-8 py-4 glass-card hover:bg-white/20 text-white rounded-2xl font-bold text-lg transition-all hover:scale-110">
                            ดูระบบทั้งหมด
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Systems Grid -->
        <section id="systems" class="container mx-auto px-4 py-16 md:py-24">
            <div class="text-center mb-16">
                <div class="glass-card inline-block rounded-3xl px-8 py-6">
                    <h3 class="text-3xl md:text-4xl font-black text-white mb-4 drop-shadow-lg">
                        ระบบทั้งหมดใน TP-Affiliate
                    </h3>
                    <p class="text-lg md:text-xl text-white/80">
                        เลือกระบบที่คุณต้องการเข้าใช้งาน
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-7xl mx-auto">

                <!-- Admin System -->
                <a href="{{ route('admin.dashboard') }}"
                   class="group glass-card p-8 rounded-3xl hover:bg-white/20 dark:hover:bg-white/10 transition-all hover:shadow-2xl hover:scale-105 hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <span class="px-4 py-1.5 bg-red-500/30 backdrop-blur-sm text-red-200 text-sm font-bold rounded-full border border-red-400/30">
                            Admin
                        </span>
                    </div>
                    <h4 class="text-2xl font-black text-white mb-3 group-hover:text-yellow-300 transition-colors">
                        ระบบแอดมิน
                    </h4>
                    <p class="text-white/80 mb-6 leading-relaxed">
                        จัดการระบบทั้งหมด ผู้ใช้ สินค้า คอมมิชชั่น และรายงาน
                    </p>
                    <div class="flex items-center text-yellow-300 font-bold group-hover:gap-4 transition-all">
                        <span>เข้าสู่ระบบ</span>
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                </a>

                <!-- User Dashboard -->
                <a href="{{ route('user.dashboard') }}"
                   class="group glass-card p-8 rounded-3xl hover:bg-white/20 dark:hover:bg-white/10 transition-all hover:shadow-2xl hover:scale-105 hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <span class="px-4 py-1.5 bg-green-500/30 backdrop-blur-sm text-green-200 text-sm font-bold rounded-full border border-green-400/30">
                            User
                        </span>
                    </div>
                    <h4 class="text-2xl font-black text-white mb-3 group-hover:text-green-300 transition-colors">
                        แดชบอร์ดผู้ใช้
                    </h4>
                    <p class="text-white/80 mb-6 leading-relaxed">
                        ดูรายได้ คอมมิชชั่น โครงสร้างทีม และจัดการบัญชีของคุณ
                    </p>
                    <div class="flex items-center text-green-300 font-bold group-hover:gap-4 transition-all">
                        <span>เข้าสู่ระบบ</span>
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                </a>

                <!-- Seller Dashboard -->
                <a href="{{ route('seller.dashboard') }}"
                   class="group glass-card p-8 rounded-3xl hover:bg-white/20 dark:hover:bg-white/10 transition-all hover:shadow-2xl hover:scale-105 hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <span class="px-4 py-1.5 bg-purple-500/30 backdrop-blur-sm text-purple-200 text-sm font-bold rounded-full border border-purple-400/30">
                            Seller
                        </span>
                    </div>
                    <h4 class="text-2xl font-black text-white mb-3 group-hover:text-purple-300 transition-colors">
                        ระบบผู้ขาย
                    </h4>
                    <p class="text-white/80 mb-6 leading-relaxed">
                        จัดการร้านค้า สินค้า คำสั่งซื้อ และรายงานยอดขาย
                    </p>
                    <div class="flex items-center text-purple-300 font-bold group-hover:gap-4 transition-all">
                        <span>เข้าสู่ระบบ</span>
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                </a>

                <!-- E-Commerce -->
                <a href="{{ route('storefront.index') }}"
                   class="group glass-card p-8 rounded-3xl hover:bg-white/20 dark:hover:bg-white/10 transition-all hover:shadow-2xl hover:scale-105 hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <span class="px-4 py-1.5 bg-blue-500/30 backdrop-blur-sm text-blue-200 text-sm font-bold rounded-full border border-blue-400/30">
                            Public
                        </span>
                    </div>
                    <h4 class="text-2xl font-black text-white mb-3 group-hover:text-cyan-300 transition-colors">
                        ร้านค้า E-Commerce
                    </h4>
                    <p class="text-white/80 mb-6 leading-relaxed">
                        ช้อปปิ้งสินค้าและบริการจากผู้ขายในระบบ
                    </p>
                    <div class="flex items-center text-cyan-300 font-bold group-hover:gap-4 transition-all">
                        <span>เข้าชมร้านค้า</span>
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                </a>

                <!-- AI Bots Marketplace -->
                <a href="{{ route('marketplace.index') }}"
                   class="group glass-card p-8 rounded-3xl hover:bg-white/20 dark:hover:bg-white/10 transition-all hover:shadow-2xl hover:scale-105 hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <span class="px-4 py-1.5 bg-orange-500/30 backdrop-blur-sm text-orange-200 text-sm font-bold rounded-full border border-orange-400/30">
                            Public
                        </span>
                    </div>
                    <h4 class="text-2xl font-black text-white mb-3 group-hover:text-orange-300 transition-colors">
                        AI Bots Marketplace
                    </h4>
                    <p class="text-white/80 mb-6 leading-relaxed">
                        เช่าและใช้งาน AI Bots สำหรับธุรกิจของคุณ
                    </p>
                    <div class="flex items-center text-orange-300 font-bold group-hover:gap-4 transition-all">
                        <span>ดู AI Bots</span>
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                </a>

                <!-- Documentation -->
                <a href="{{ route('wiki.index') }}"
                   class="group glass-card p-8 rounded-3xl hover:bg-white/20 dark:hover:bg-white/10 transition-all hover:shadow-2xl hover:scale-105 hover:-translate-y-2">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform">
                            <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <span class="px-4 py-1.5 bg-indigo-500/30 backdrop-blur-sm text-indigo-200 text-sm font-bold rounded-full border border-indigo-400/30">
                            Public
                        </span>
                    </div>
                    <h4 class="text-2xl font-black text-white mb-3 group-hover:text-indigo-300 transition-colors">
                        คู่มือและ Wiki
                    </h4>
                    <p class="text-white/80 mb-6 leading-relaxed">
                        เรียนรู้การใช้งานระบบและดูคำถามที่พบบ่อย
                    </p>
                    <div class="flex items-center text-indigo-300 font-bold group-hover:gap-4 transition-all">
                        <span>อ่านคู่มือ</span>
                        <svg class="w-5 h-5 ml-2 group-hover:translate-x-2 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </div>
                </a>

            </div>
        </section>

        <!-- Footer -->
        <footer class="glass-card border-t border-white/20 dark:border-white/10 mt-16">
            <div class="container mx-auto px-4 py-8">
                <div class="text-center text-white/80">
                    <p class="font-semibold">&copy; {{ date('Y') }} TP-Affiliate. All rights reserved.</p>
                    <p class="mt-2 text-sm text-white/60">Version {{ config('app.version', '3.80.2') }}</p>
                </div>
            </div>
        </footer>

    </div>

    <style>
        /* Fade in animation */
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.8s ease-out;
        }
    </style>

</body>
</html>
