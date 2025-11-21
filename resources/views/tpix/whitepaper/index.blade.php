<!DOCTYPE html>
<html lang="th" x-data="{ darkMode: false }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TPIX Whitepaper - Native Cryptocurrency with Its Own Blockchain for Thaiprompt Affiliate Ecosystem">
    <meta name="keywords" content="TPIX, Blockchain, Cryptocurrency, Whitepaper, Thai, Native Coin">
    <meta name="author" content="Thaiprompt Affiliate Team">

    <title>TPIX Whitepaper | Native Cryptocurrency</title>

    {{-- Tailwind CSS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Custom Styles --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Noto Sans Thai', 'Kanit', sans-serif;
        }

        /* Gradient backgrounds */
        .gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .gradient-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .gradient-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .gradient-gold {
            background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .glass-dark {
            background: rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Animated gradient background */
        @keyframes gradient-animation {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        .animated-gradient {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 400% 400%;
            animation: gradient-animation 15s ease infinite;
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Section fade-in animation */
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

        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Floating animation */
        @keyframes floating {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300">

    {{-- Fixed Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 glass dark:glass-dark no-print" x-data="{ mobileMenuOpen: false }">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                {{-- Logo --}}
                <div class="flex items-center space-x-3">
                    <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents(public_path('images/tpix-logo.png'))) }}"
                         alt="TPIX Logo"
                         class="w-12 h-12 rounded-full shadow-lg"
                         onerror="this.src='https://via.placeholder.com/48/667eea/FFFFFF?text=TPIX'">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white">TPIX</h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Whitepaper v{{ $data['version'] }}</p>
                    </div>
                </div>

                {{-- Desktop Menu --}}
                <div class="hidden lg:flex items-center space-x-1">
                    <a href="#overview" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-purple-600 dark:hover:text-purple-400 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50">
                        ภาพรวม
                    </a>
                    <a href="#use-cases" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-purple-600 dark:hover:text-purple-400 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50">
                        การใช้งาน
                    </a>
                    <a href="#tokenomics" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-purple-600 dark:hover:text-purple-400 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50">
                        Tokenomics
                    </a>
                    <a href="#roadmap" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-purple-600 dark:hover:text-purple-400 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50">
                        แผนงาน
                    </a>
                    <a href="{{ route('home') }}" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50">
                        หน้าแรก
                    </a>
                    <a href="https://wiki.thaiprompt.com" target="_blank" rel="noopener noreferrer" class="px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span>WIKI ไทยพร๊อมท์</span>
                    </a>
                </div>

                {{-- Actions --}}
                <div class="flex items-center space-x-2">
                    {{-- Mobile Menu Toggle --}}
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="lg:hidden p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        <svg x-show="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg x-show="mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    {{-- Dark Mode Toggle --}}
                    <button @click="darkMode = !darkMode"
                            class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>

                    {{-- Download PDF Button --}}
                    <a href="{{ route('tpix.whitepaper.pdf') }}"
                       class="hidden sm:flex px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition shadow-lg items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>ดาวน์โหลด PDF</span>
                    </a>
                </div>
            </div>

            {{-- Mobile Menu --}}
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="lg:hidden mt-4 py-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-col space-y-2">
                    <a href="#overview" @click="mobileMenuOpen = false" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-purple-600 dark:hover:text-purple-400 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50">
                        ภาพรวม
                    </a>
                    <a href="#use-cases" @click="mobileMenuOpen = false" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-purple-600 dark:hover:text-purple-400 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50">
                        การใช้งาน
                    </a>
                    <a href="#tokenomics" @click="mobileMenuOpen = false" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-purple-600 dark:hover:text-purple-400 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50">
                        Tokenomics
                    </a>
                    <a href="#roadmap" @click="mobileMenuOpen = false" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-purple-600 dark:hover:text-purple-400 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50">
                        แผนงาน
                    </a>
                    <a href="{{ route('home') }}" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50">
                        หน้าแรก
                    </a>
                    <a href="https://wiki.thaiprompt.com" target="_blank" rel="noopener noreferrer" class="px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition rounded-lg hover:bg-white/50 dark:hover:bg-gray-800/50 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span>WIKI ไทยพร๊อมท์</span>
                    </a>
                    <a href="{{ route('tpix.whitepaper.pdf') }}" class="sm:hidden px-3 py-2 text-sm font-medium bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700 transition shadow-lg flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>ดาวน์โหลด PDF</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden animated-gradient">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-20 left-10 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
            <div class="absolute top-40 right-10 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse" style="animation-delay: 2s;"></div>
            <div class="absolute bottom-20 left-1/2 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse" style="animation-delay: 4s;"></div>
        </div>

        <div class="container mx-auto px-4 text-center relative z-10 pt-20">
            {{-- Main Logo --}}
            <div class="mb-8 floating">
                <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents(public_path('images/tpix-logo.png'))) }}"
                     alt="TPIX Logo"
                     class="w-64 h-64 mx-auto drop-shadow-2xl"
                     onerror="this.src='https://via.placeholder.com/256/667eea/FFFFFF?text=TPIX'">
            </div>

            {{-- Title --}}
            <h1 class="text-6xl md:text-8xl font-black text-white mb-6 drop-shadow-lg">
                TPIX
            </h1>

            <p class="text-2xl md:text-4xl text-white font-light mb-4 drop-shadow-lg">
                Native Cryptocurrency
            </p>

            <p class="text-xl md:text-2xl text-white/90 mb-8 drop-shadow-lg">
                Blockchain ของตัวเอง สำหรับระบบนิเวศน์ Thaiprompt Affiliate
            </p>

            {{-- Key Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto mt-12">
                <div class="glass rounded-xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">7B</div>
                    <div class="text-sm text-white/80">Total Supply</div>
                </div>
                <div class="glass rounded-xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">2s</div>
                    <div class="text-sm text-white/80">Block Time</div>
                </div>
                <div class="glass rounded-xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">1,500</div>
                    <div class="text-sm text-white/80">TPS</div>
                </div>
                <div class="glass rounded-xl p-6">
                    <div class="text-3xl font-bold text-white mb-2">IBFT</div>
                    <div class="text-sm text-white/80">Consensus</div>
                </div>
            </div>

            {{-- Scroll Indicator --}}
            <div class="mt-16 no-print">
                <a href="#overview" class="inline-block animate-bounce">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    {{-- Main Content --}}
    <div class="bg-gray-50 dark:bg-gray-900 transition-colors duration-300">

        {{-- Table of Contents --}}
        <section id="toc" class="py-20 no-print">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-12 text-center">
                        สารบัญ
                    </h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        @php
                        $sections = [
                            ['id' => 'overview', 'title' => '1. ภาพรวมโครงการ', 'icon' => 'eye'],
                            ['id' => 'problem', 'title' => '2. ปัญหาและแนวทางแก้ไข', 'icon' => 'lightbulb'],
                            ['id' => 'technology', 'title' => '3. เทคโนโลยีและสถาปัตยกรรม', 'icon' => 'chip'],
                            ['id' => 'ecosystem', 'title' => '4. ระบบนิเวศน์ TPIX', 'icon' => 'globe'],
                            ['id' => 'tokenomics', 'title' => '5. โทเค็นโนมิกส์', 'icon' => 'chart-pie'],
                            ['id' => 'dex', 'title' => '6. Decentralized Exchange', 'icon' => 'arrows-exchange'],
                            ['id' => 'use-cases', 'title' => '7. Use Cases', 'icon' => 'cube'],
                            ['id' => 'roadmap', 'title' => '8. Roadmap', 'icon' => 'map'],
                            ['id' => 'team', 'title' => '9. ทีมและพันธมิตร', 'icon' => 'users'],
                            ['id' => 'conclusion', 'title' => '10. สรุป', 'icon' => 'check-circle'],
                        ];
                        @endphp

                        @foreach($sections as $section)
                        <a href="#{{ $section['id'] }}"
                           class="block p-4 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition hover:scale-105">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                                <span class="text-gray-900 dark:text-white font-medium">{{ $section['title'] }}</span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Overview Section --}}
        <section id="overview" class="py-20 bg-white dark:bg-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        ภาพรวมโครงการ
                    </h2>
                    <div class="h-1 w-32 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full mb-12"></div>

                    <div class="prose prose-lg dark:prose-invert max-w-none">
                        <p class="text-xl text-gray-700 dark:text-gray-300 leading-relaxed mb-8">
                            <strong>TPIX (Thaiprompt Affiliate Blockchain)</strong> เป็นระบบ blockchain ที่พัฒนาขึ้นเฉพาะ
                            สำหรับระบบนิเวศน์ของ Thaiprompt Affiliate โดยมีเหรียญ <strong>TPIX</strong> เป็น
                            <strong>Native Coin</strong> หลักของระบบ ซึ่งแตกต่างจาก Token บนเครือข่ายอื่น ๆ
                            เช่น Ethereum หรือ BSC
                        </p>

                        {{-- Comparison Table --}}
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-gray-700 dark:to-gray-600 rounded-2xl p-8 mb-8">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">เปรียบเทียบ TPIX กับ Token อื่น</h3>

                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="border-b-2 border-purple-300 dark:border-purple-600">
                                            <th class="py-3 px-4 text-left text-gray-900 dark:text-white">คุณสมบัติ</th>
                                            <th class="py-3 px-4 text-center text-gray-600 dark:text-gray-400">Token (ERC20)</th>
                                            <th class="py-3 px-4 text-center text-purple-600 dark:text-purple-400">TPIX Native Coin</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-700 dark:text-gray-300">
                                        <tr class="border-b border-gray-200 dark:border-gray-600">
                                            <td class="py-3 px-4 font-medium">Gas Fee</td>
                                            <td class="py-3 px-4 text-center">ต้องใช้ ETH/BNB ❌</td>
                                            <td class="py-3 px-4 text-center font-bold text-green-600 dark:text-green-400">ใช้ TPIX ✅</td>
                                        </tr>
                                        <tr class="border-b border-gray-200 dark:border-gray-600">
                                            <td class="py-3 px-4 font-medium">การควบคุม</td>
                                            <td class="py-3 px-4 text-center">ขึ้นกับ Ethereum/BSC ❌</td>
                                            <td class="py-3 px-4 text-center font-bold text-green-600 dark:text-green-400">ควบคุมเต็มที่ ✅</td>
                                        </tr>
                                        <tr class="border-b border-gray-200 dark:border-gray-600">
                                            <td class="py-3 px-4 font-medium">ค่าธรรมเนียม</td>
                                            <td class="py-3 px-4 text-center">สูง (ขึ้นกับ network) ❌</td>
                                            <td class="py-3 px-4 text-center font-bold text-green-600 dark:text-green-400">ต่ำ (กำหนดเอง) ✅</td>
                                        </tr>
                                        <tr class="border-b border-gray-200 dark:border-gray-600">
                                            <td class="py-3 px-4 font-medium">ความเร็ว</td>
                                            <td class="py-3 px-4 text-center">12-15 วินาที ⏱️</td>
                                            <td class="py-3 px-4 text-center font-bold text-green-600 dark:text-green-400">2 วินาที ⚡</td>
                                        </tr>
                                        <tr>
                                            <td class="py-3 px-4 font-medium">Smart Contracts</td>
                                            <td class="py-3 px-4 text-center">รองรับ ✅</td>
                                            <td class="py-3 px-4 text-center font-bold text-green-600 dark:text-green-400">รองรับ ✅</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Key Features Grid --}}
                        <div class="grid md:grid-cols-3 gap-6">
                            <div class="bg-white dark:bg-gray-700 rounded-xl p-6 shadow-lg hover:shadow-xl transition">
                                <div class="w-14 h-14 bg-gradient-to-br from-purple-600 to-pink-600 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Lightning Fast</h4>
                                <p class="text-gray-600 dark:text-gray-400">Block time เพียง 2 วินาที รองรับ ~1,500 TPS</p>
                            </div>

                            <div class="bg-white dark:bg-gray-700 rounded-xl p-6 shadow-lg hover:shadow-xl transition">
                                <div class="w-14 h-14 bg-gradient-to-br from-blue-600 to-cyan-600 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Secure & Decentralized</h4>
                                <p class="text-gray-600 dark:text-gray-400">IBFT Consensus (Proof of Stake) Byzantine Fault Tolerant</p>
                            </div>

                            <div class="bg-white dark:bg-gray-700 rounded-xl p-6 shadow-lg hover:shadow-xl transition">
                                <div class="w-14 h-14 bg-gradient-to-br from-green-600 to-teal-600 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Fixed Supply</h4>
                                <p class="text-gray-600 dark:text-gray-400">Total supply 7B TPIX ไม่สามารถสร้างเพิ่มได้</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        {{-- Problem & Solution Section --}}
        <section id="problem" class="py-20 bg-gradient-to-br from-gray-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        ปัญหาและแนวทางแก้ไข
                    </h2>
                    <div class="h-1 w-32 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full mb-12"></div>

                    {{-- Problems --}}
                    <div class="grid md:grid-cols-2 gap-8 mb-12">
                        <div>
                            <h3 class="text-3xl font-bold text-red-600 dark:text-red-400 mb-6">❌ ปัญหาของ Crypto ปัจจุบัน</h3>

                            <div class="space-y-4">
                                <div class="bg-white dark:bg-gray-700 rounded-xl p-6 shadow-lg">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">1. ค่า Gas Fee สูง</h4>
                                    <p class="text-gray-600 dark:text-gray-400">Token บน Ethereum ต้องใช้ ETH เป็นค่าแก๊ส ซึ่งมีราคาแพงมาก (บางครั้งถึง $50-100 ต่อ transaction)</p>
                                </div>

                                <div class="bg-white dark:bg-gray-700 rounded-xl p-6 shadow-lg">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">2. ไม่สามารถควบคุมระบบได้</h4>
                                    <p class="text-gray-600 dark:text-gray-400">Token บน BSC/Ethereum ต้องพึ่งพาเครือข่ายอื่น ไม่สามารถปรับแต่งกฎเกณฑ์หรือค่าธรรมเนียมได้</p>
                                </div>

                                <div class="bg-white dark:bg-gray-700 rounded-xl p-6 shadow-lg">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">3. ช้าและมีข้อจำกัด</h4>
                                    <p class="text-gray-600 dark:text-gray-400">Block time ช้า (12-15 วินาที) และมี TPS จำกัด ทำให้ไม่เหมาะกับการใช้งานจริง</p>
                                </div>

                                <div class="bg-white dark:bg-gray-700 rounded-xl p-6 shadow-lg">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">4. ไม่เหมาะกับองค์กร</h4>
                                    <p class="text-gray-600 dark:text-gray-400">Public blockchain มีข้อจำกัดด้านความเป็นส่วนตัวและการควบคุม ไม่เหมาะกับองค์กรที่ต้องการความยืดหยุ่น</p>
                                </div>
                            </div>
                        </div>

                        {{-- Solutions --}}
                        <div>
                            <h3 class="text-3xl font-bold text-green-600 dark:text-green-400 mb-6">✅ แนวทางแก้ไขด้วย TPIX</h3>

                            <div class="space-y-4">
                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 shadow-lg border-2 border-green-200 dark:border-green-700">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">1. ค่าธรรมเนียมต่ำมาก</h4>
                                    <p class="text-gray-600 dark:text-gray-400">ใช้ TPIX เป็นค่าแก๊ส ราคาเพียง ~0.000021 TPIX (~0.42 สตางค์ ถ้า TPIX = 20 บาท)</p>
                                </div>

                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 shadow-lg border-2 border-green-200 dark:border-green-700">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">2. ควบคุมได้เต็มที่</h4>
                                    <p class="text-gray-600 dark:text-gray-400">Blockchain ของตัวเอง ปรับแต่งได้ทุกอย่าง จาก consensus mechanism ไปจนถึงค่าธรรมเนียม</p>
                                </div>

                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 shadow-lg border-2 border-green-200 dark:border-green-700">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">3. รวดเร็วและ Scalable</h4>
                                    <p class="text-gray-600 dark:text-gray-400">Block time เพียง 2 วินาที รองรับ ~1,500 TPS เหมาะกับการใช้งานจริง</p>
                                </div>

                                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-6 shadow-lg border-2 border-green-200 dark:border-green-700">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2">4. เหมาะกับทุกองค์กร</h4>
                                    <p class="text-gray-600 dark:text-gray-400">สามารถปรับแต่งให้เหมาะกับความต้องการขององค์กร พร้อมความปลอดภัยระดับสูง</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Technology Section --}}
        <section id="technology" class="py-20 bg-white dark:bg-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        เทคโนโลยีและสถาปัตยกรรม
                    </h2>
                    <div class="h-1 w-32 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full mb-12"></div>

                    {{-- Blockchain Specifications --}}
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-gray-700 dark:to-gray-600 rounded-2xl p-8 mb-12">
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Blockchain Specifications</h3>

                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach([
                                ['label' => 'Chain ID', 'value' => '7000', 'icon' => 'hashtag'],
                                ['label' => 'Network Name', 'value' => 'TPIX Network', 'icon' => 'globe'],
                                ['label' => 'Native Coin', 'value' => 'TPIX', 'icon' => 'currency-dollar'],
                                ['label' => 'Total Supply', 'value' => '7,000,000,000', 'icon' => 'collection'],
                                ['label' => 'Decimals', 'value' => '18', 'icon' => 'calculator'],
                                ['label' => 'Block Time', 'value' => '2 seconds', 'icon' => 'clock'],
                                ['label' => 'Consensus', 'value' => 'IBFT', 'icon' => 'shield-check'],
                                ['label' => 'VM', 'value' => 'EVM', 'icon' => 'chip'],
                                ['label' => 'TPS', 'value' => '~1,500', 'icon' => 'lightning-bolt'],
                            ] as $spec)
                            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $spec['label'] }}</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $spec['value'] }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Technology Stack --}}
                    <div class="mb-12">
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">Technology Stack</h3>

                        <div class="grid md:grid-cols-2 gap-8">
                            @foreach($data['tech_stack'] as $category => $technologies)
                            <div class="bg-white dark:bg-gray-700 rounded-xl p-6 shadow-lg">
                                <h4 class="text-xl font-bold text-purple-600 dark:text-purple-400 mb-4 capitalize">
                                    {{ ucfirst(str_replace('_', ' ', $category)) }}
                                </h4>
                                <div class="space-y-3">
                                    @foreach($technologies as $name => $description)
                                    <div class="flex items-start space-x-3">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $name }}</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ $description }}</div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Architecture Diagram Placeholder --}}
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl p-12 text-center">
                        <div class="text-gray-500 dark:text-gray-400 mb-4">
                            <svg class="w-24 h-24 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                        </div>
                        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">System Architecture Diagram</h4>
                        <p class="text-gray-600 dark:text-gray-400">ดูรายละเอียดเพิ่มเติมใน docs/TPIX_BLOCKCHAIN.md</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Ecosystem Section with Mind Map --}}
        <section id="ecosystem" class="py-20 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        ระบบนิเวศน์ TPIX
                    </h2>
                    <div class="h-1 w-32 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full mb-12"></div>

                    {{-- Mind Map Canvas --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-2xl mb-12">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 text-center">
                            TPIX Ecosystem Mind Map
                        </h3>
                        <div id="ecosystem-mindmap" style="height: 600px;" class="border-2 border-gray-200 dark:border-gray-700 rounded-xl"></div>
                    </div>

                    {{-- Integration Points --}}
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">จุดเชื่อมต่อกับระบบต่าง ๆ</h3>

                    <div class="grid md:grid-cols-2 gap-8">
                        @foreach($data['integrations'] as $key => $integration)
                        <div class="bg-white dark:bg-gray-700 rounded-xl p-8 shadow-lg hover:shadow-2xl transition">
                            <div class="flex items-center space-x-4 mb-6">
                                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ $integration['title'] }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $integration['description'] }}</p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @foreach($integration['connections'] as $name => $desc)
                                <div class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $name }}</div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ $desc }}</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Tokenomics Section --}}
        <section id="tokenomics" class="py-20 bg-white dark:bg-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        โทเค็นโนมิกส์
                    </h2>
                    <div class="h-1 w-32 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full mb-12"></div>

                    <div class="grid lg:grid-cols-2 gap-12 items-center mb-12">
                        {{-- Token Distribution Chart --}}
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">การกระจาย Token</h3>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-2xl p-8">
                                <canvas id="tokenomicsChart" height="300"></canvas>
                            </div>
                        </div>

                        {{-- Distribution Table --}}
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">รายละเอียดการกระจาย</h3>
                            <div class="space-y-4">
                                @foreach($data['tokenomics']['distribution'] as $dist)
                                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-700 dark:to-gray-600 rounded-xl p-6">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-bold text-gray-900 dark:text-white">{{ $dist['name'] }}</span>
                                        <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $dist['percentage'] }}%</span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ number_format($dist['amount']) }} TPIX
                                    </div>
                                    <div class="mt-2 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-purple-600 to-pink-600 h-2 rounded-full transition-all duration-1000"
                                             style="width: {{ $dist['percentage'] }}%"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Total Supply Info --}}
                    <div class="bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-2xl p-8 text-center">
                        <div class="text-6xl font-black text-gray-900 dark:text-white mb-4">
                            7,000,000,000
                        </div>
                        <div class="text-2xl text-gray-700 dark:text-gray-300 mb-2">
                            Total Supply (Fixed)
                        </div>
                        <div class="text-gray-600 dark:text-gray-400">
                            ไม่สามารถสร้างเพิ่มได้ | Deflationary Economics
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- DEX Section --}}
        <section id="dex" class="py-20 bg-gradient-to-br from-green-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        Decentralized Exchange (DEX)
                    </h2>
                    <div class="h-1 w-32 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full mb-12"></div>

                    <div class="prose prose-lg dark:prose-invert max-w-none mb-12">
                        <p class="text-xl text-gray-700 dark:text-gray-300">
                            TPIX DEX เป็น Automated Market Maker (AMM) ที่ช่วยให้ผู้ใช้สามารถแลกเปลี่ยน tokens
                            และเพิ่ม liquidity เพื่อรับผลตอบแทนจากค่าธรรมเนียมการซื้อขาย
                        </p>
                    </div>

                    {{-- DEX Features --}}
                    <div class="grid md:grid-cols-3 gap-8 mb-12">
                        @foreach([
                            [
                                'title' => 'Token Swaps',
                                'description' => 'แลกเปลี่ยน tokens ด้วย AMM constant product formula (x * y = k)',
                                'icon' => 'arrows-exchange',
                                'features' => ['Instant swaps', 'Price impact calculation', 'Slippage protection']
                            ],
                            [
                                'title' => 'Liquidity Pools',
                                'description' => 'เพิ่ม liquidity เพื่อรับส่วนแบ่งค่าธรรมเนียม 0.3% จากทุก trade',
                                'icon' => 'chart-bar',
                                'features' => ['Add/Remove liquidity', 'LP token minting', 'Fee distribution']
                            ],
                            [
                                'title' => 'Yield Farming',
                                'description' => 'รับผลตอบแทนจากการให้บริการ liquidity พร้อมติดตาม impermanent loss',
                                'icon' => 'trending-up',
                                'features' => ['Pool APY calculation', 'TVL tracking', 'ROI monitoring']
                            ],
                        ] as $feature)
                        <div class="bg-white dark:bg-gray-700 rounded-xl p-8 shadow-lg hover:shadow-2xl transition">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-600 to-blue-600 rounded-xl flex items-center justify-center mb-6 mx-auto">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 text-center">{{ $feature['title'] }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-6 text-center">{{ $feature['description'] }}</p>
                            <ul class="space-y-2">
                                @foreach($feature['features'] as $item)
                                <li class="flex items-center space-x-2 text-sm">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300">{{ $item }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>

                    {{-- AMM Formula --}}
                    <div class="bg-gradient-to-br from-purple-100 to-blue-100 dark:from-purple-900/30 dark:to-blue-900/30 rounded-2xl p-8 text-center">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">AMM Formula</h3>
                        <div class="text-5xl font-black text-purple-600 dark:text-purple-400 mb-4">
                            x × y = k
                        </div>
                        <div class="text-gray-700 dark:text-gray-300 max-w-2xl mx-auto">
                            Constant Product Formula ที่รับประกันว่าผลคูณของ token reserves จะคงที่
                            ช่วยกำหนดราคาอัตโนมัติตามอุปสงค์และอุปทาน
                        </div>
                    </div>
                </div>
            </div>
        </section>


        {{-- Use Cases Section --}}
        <section id="use-cases" class="py-20 bg-white dark:bg-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        Use Cases
                    </h2>
                    <div class="h-1 w-32 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full mb-12"></div>

                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach($data['use_cases'] as $useCase)
                        <div class="bg-gradient-to-br from-white to-purple-50 dark:from-gray-700 dark:to-gray-600 rounded-2xl p-8 shadow-lg hover:shadow-2xl transition hover:scale-105">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-pink-600 rounded-xl flex items-center justify-center mb-6">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>

                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                {{ $useCase['title'] }}
                            </h3>

                            <p class="text-gray-600 dark:text-gray-300 mb-6">
                                {{ $useCase['description'] }}
                            </p>

                            <ul class="space-y-2">
                                @foreach($useCase['features'] as $feature)
                                <li class="flex items-start space-x-2">
                                    <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">{{ $feature }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Roadmap Section --}}
        <section id="roadmap" class="py-20 bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        Roadmap
                    </h2>
                    <div class="h-1 w-32 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full mb-12"></div>

                    {{-- Timeline --}}
                    <div class="relative">
                        {{-- Vertical Line --}}
                        <div class="absolute left-8 md:left-1/2 top-0 bottom-0 w-1 bg-gradient-to-b from-purple-600 to-pink-600 transform md:-translate-x-1/2"></div>

                        {{-- Roadmap Items --}}
                        <div class="space-y-12">
                            @foreach($data['roadmap'] as $index => $phase)
                            <div class="relative">
                                {{-- Timeline Dot --}}
                                <div class="absolute left-8 md:left-1/2 transform -translate-x-1/2 w-16 h-16 bg-gradient-to-br from-purple-600 to-pink-600 rounded-full flex items-center justify-center shadow-xl z-10 border-4 border-white dark:border-gray-900">
                                    @if($phase['status'] === 'completed')
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    @elseif($phase['status'] === 'in_progress')
                                    <svg class="w-8 h-8 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    @else
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    @endif
                                </div>

                                {{-- Content --}}
                                <div class="ml-24 md:ml-0 {{ $index % 2 === 0 ? 'md:pr-1/2 md:mr-16' : 'md:pl-1/2 md:ml-16' }}">
                                    <div class="bg-white dark:bg-gray-700 rounded-2xl p-8 shadow-lg hover:shadow-2xl transition">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                {{ $phase['phase'] }}
                                            </h3>
                                            <span class="px-4 py-1 rounded-full text-sm font-bold
                                                {{ $phase['status'] === 'completed' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : '' }}
                                                {{ $phase['status'] === 'in_progress' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' : '' }}
                                                {{ $phase['status'] === 'planned' ? 'bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-gray-300' : '' }}">
                                                {{ ucfirst($phase['quarter']) }}
                                            </span>
                                        </div>

                                        <ul class="space-y-2">
                                            @foreach($phase['milestones'] as $milestone)
                                            <li class="flex items-start space-x-3">
                                                <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                <span class="text-gray-700 dark:text-gray-300">{{ $milestone }}</span>
                                            </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Team Section --}}
        <section id="team" class="py-20 bg-white dark:bg-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gray-900 dark:text-white mb-6">
                        ทีมและพันธมิตร
                    </h2>
                    <div class="h-1 w-32 bg-gradient-to-r from-purple-600 to-pink-600 rounded-full mb-12"></div>

                    <div class="text-center mb-12">
                        <div class="inline-block bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-2xl px-12 py-8">
                            <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                                Thaiprompt Affiliate Team
                            </h3>
                            <p class="text-xl text-gray-700 dark:text-gray-300 max-w-3xl">
                                พัฒนาโดยทีมงานมืออาชีพจาก Thaiprompt Affiliate ด้วยประสบการณ์กว่า 5 ปีในด้าน
                                Blockchain, Web Development และ E-Commerce
                            </p>
                        </div>
                    </div>

                    {{-- Technology Partners --}}
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 text-center">Technology Partners</h3>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                        @foreach(['Polygon Edge', 'Laravel', 'Docker', 'Blockscout', 'Ethereum', 'Prometheus', 'Grafana', 'ethers.js'] as $tech)
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl p-8 flex items-center justify-center shadow hover:shadow-lg transition">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $tech }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Conclusion Section --}}
        <section id="conclusion" class="py-20 bg-gradient-to-br from-purple-600 to-pink-600 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto text-center text-white">
                    <h2 class="text-5xl font-bold mb-8">
                        สรุป
                    </h2>

                    <div class="text-xl leading-relaxed mb-12 space-y-6">
                        <p>
                            <strong>TPIX</strong> คือ Native Cryptocurrency ที่มี Blockchain ของตัวเอง
                            ซึ่งแก้ปัญหาของ cryptocurrency ปัจจุบันได้อย่างครบถ้วน ไม่ว่าจะเป็นค่าธรรมเนียมที่สูง
                            ความช้า หรือการไม่สามารถควบคุมระบบได้เต็มที่
                        </p>

                        <p>
                            ด้วย <strong>Total Supply 7,000,000,000 TPIX</strong> ที่ถูกกำหนดตายตัว
                            <strong>Block Time เพียง 2 วินาที</strong> และ <strong>IBFT Consensus</strong>
                            ทำให้ TPIX เหมาะสำหรับการใช้งานจริงในระบบนิเวศน์ Thaiprompt Affiliate
                        </p>

                        <p>
                            ระบบ DEX, Staking Pools, Token Management และการเชื่อมต่อกับระบบต่าง ๆ
                            ทำให้ TPIX เป็นมากกว่า cryptocurrency แต่เป็น <strong>Complete Blockchain Ecosystem</strong>
                            ที่พร้อมรองรับการเติบโตในอนาคต
                        </p>
                    </div>

                    {{-- Key Stats Summary --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
                        <div class="glass rounded-xl p-6">
                            <div class="text-4xl font-black mb-2">7B</div>
                            <div class="text-sm opacity-90">Total Supply</div>
                        </div>
                        <div class="glass rounded-xl p-6">
                            <div class="text-4xl font-black mb-2">2s</div>
                            <div class="text-sm opacity-90">Block Time</div>
                        </div>
                        <div class="glass rounded-xl p-6">
                            <div class="text-4xl font-black mb-2">1,500</div>
                            <div class="text-sm opacity-90">TPS</div>
                        </div>
                        <div class="glass rounded-xl p-6">
                            <div class="text-4xl font-black mb-2">∞</div>
                            <div class="text-sm opacity-90">Possibilities</div>
                        </div>
                    </div>

                    {{-- Call to Action --}}
                    <div class="space-y-4">
                        <h3 class="text-3xl font-bold mb-6">
                            พร้อมเป็นส่วนหนึ่งของ TPIX Ecosystem หรือยัง?
                        </h3>

                        <div class="flex flex-col md:flex-row items-center justify-center space-y-4 md:space-y-0 md:space-x-4">
                            <a href="#overview" class="px-8 py-4 bg-white text-purple-600 rounded-xl font-bold text-lg hover:bg-gray-100 transition shadow-lg">
                                อ่านเพิ่มเติม
                            </a>
                            <a href="{{ route('tpix.whitepaper.pdf') }}" class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-xl font-bold text-lg hover:bg-white/10 transition">
                                ดาวน์โหลด PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="bg-gray-900 text-white py-12 no-print">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <div class="grid md:grid-cols-3 gap-8 mb-8">
                        <div>
                            <h4 class="text-xl font-bold mb-4">TPIX Blockchain</h4>
                            <p class="text-gray-400 mb-4">
                                Native Cryptocurrency สำหรับระบบนิเวศน์ Thaiprompt Affiliate
                            </p>
                            <div class="flex items-center space-x-2">
                                <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents(public_path('images/tpix-logo.png'))) }}"
                                     alt="TPIX Logo"
                                     class="w-12 h-12 rounded-full"
                                     onerror="this.src='https://via.placeholder.com/48/667eea/FFFFFF?text=TPIX'">
                                <div>
                                    <div class="font-bold">TPIX</div>
                                    <div class="text-sm text-gray-400">v{{ $data['version'] }}</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xl font-bold mb-4">เอกสาร</h4>
                            <ul class="space-y-2 text-gray-400">
                                <li><a href="#overview" class="hover:text-white transition">ภาพรวม</a></li>
                                <li><a href="#technology" class="hover:text-white transition">เทคโนโลยี</a></li>
                                <li><a href="#tokenomics" class="hover:text-white transition">โทเค็นโนมิกส์</a></li>
                                <li><a href="#roadmap" class="hover:text-white transition">Roadmap</a></li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="text-xl font-bold mb-4">ติดต่อ</h4>
                            <ul class="space-y-2 text-gray-400">
                                <li>Email: support@tpix.com</li>
                                <li>Website: tpix.network</li>
                                <li>GitHub: github.com/tpix-blockchain</li>
                            </ul>
                        </div>
                    </div>

                    <div class="border-t border-gray-800 pt-8 text-center text-gray-400">
                        <p>&copy; {{ date('Y') }} TPIX Blockchain. All rights reserved.</p>
                        <p class="mt-2 text-sm">Version {{ $data['version'] }} | {{ $data['date'] }}</p>
                    </div>
                </div>
            </div>
        </footer>

    </div>

    {{-- Charts & Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <link href="https://unpkg.com/vis-network/styles/vis-network.min.css" rel="stylesheet" type="text/css" />

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tokenomics Pie Chart
            const tokenomicsCtx = document.getElementById('tokenomicsChart');
            if (tokenomicsCtx) {
                new Chart(tokenomicsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: {!! json_encode(array_column($data['tokenomics']['distribution'], 'name')) !!},
                        datasets: [{
                            data: {!! json_encode(array_column($data['tokenomics']['distribution'], 'percentage')) !!},
                            backgroundColor: [
                                'rgba(102, 126, 234, 0.8)',
                                'rgba(118, 75, 162, 0.8)',
                                'rgba(240, 147, 251, 0.8)',
                                'rgba(245, 87, 108, 0.8)',
                                'rgba(79, 172, 254, 0.8)',
                            ],
                            borderColor: [
                                'rgb(102, 126, 234)',
                                'rgb(118, 75, 162)',
                                'rgb(240, 147, 251)',
                                'rgb(245, 87, 108)',
                                'rgb(79, 172, 254)',
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    font: {
                                        size: 12,
                                        family: "'Noto Sans Thai', 'Kanit', sans-serif"
                                    },
                                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        return `${label}: ${value}%`;
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Ecosystem Mind Map
            const mindmapContainer = document.getElementById('ecosystem-mindmap');
            if (mindmapContainer) {
                const nodes = new vis.DataSet([
                    // Center node
                    { id: 1, label: 'TPIX\nBlockchain', level: 0, color: { background: '#667eea', border: '#764ba2' }, font: { color: '#fff', size: 20, bold: true } },

                    // Main systems
                    { id: 2, label: 'Affiliate\nSystem', level: 1, color: { background: '#f093fb' }, font: { color: '#fff', size: 16 } },
                    { id: 3, label: 'E-Commerce', level: 1, color: { background: '#4facfe' }, font: { color: '#fff', size: 16 } },
                    { id: 4, label: 'AI Bots', level: 1, color: { background: '#43e97b' }, font: { color: '#fff', size: 16 } },
                    { id: 5, label: 'Hotels', level: 1, color: { background: '#fa709a' }, font: { color: '#fff', size: 16 } },

                    // DEX & Features
                    { id: 6, label: 'DEX', level: 1, color: { background: '#f7971e' }, font: { color: '#fff', size: 16 } },
                    { id: 7, label: 'Staking', level: 1, color: { background: '#ffd200' }, font: { color: '#fff', size: 16 } },
                    { id: 8, label: 'Token\nFactory', level: 1, color: { background: '#30cfd0' }, font: { color: '#fff', size: 16 } },

                    // Sub-features
                    { id: 9, label: 'Rewards', level: 2, color: { background: '#ffecd2' } },
                    { id: 10, label: 'Commissions', level: 2, color: { background: '#ffecd2' } },
                    { id: 11, label: 'Payment', level: 2, color: { background: '#d4f1f9' } },
                    { id: 12, label: 'Cashback', level: 2, color: { background: '#d4f1f9' } },
                    { id: 13, label: 'Swaps', level: 2, color: { background: '#ffe0ac' } },
                    { id: 14, label: 'Liquidity', level: 2, color: { background: '#ffe0ac' } },
                ]);

                const edges = new vis.DataSet([
                    // Main connections
                    { from: 1, to: 2, width: 3 },
                    { from: 1, to: 3, width: 3 },
                    { from: 1, to: 4, width: 3 },
                    { from: 1, to: 5, width: 3 },
                    { from: 1, to: 6, width: 3 },
                    { from: 1, to: 7, width: 3 },
                    { from: 1, to: 8, width: 3 },

                    // Sub-connections
                    { from: 2, to: 9, width: 2, dashes: true },
                    { from: 2, to: 10, width: 2, dashes: true },
                    { from: 3, to: 11, width: 2, dashes: true },
                    { from: 3, to: 12, width: 2, dashes: true },
                    { from: 6, to: 13, width: 2, dashes: true },
                    { from: 6, to: 14, width: 2, dashes: true },
                ]);

                const data = { nodes: nodes, edges: edges };
                const options = {
                    layout: {
                        hierarchical: {
                            direction: 'UD',
                            sortMethod: 'directed',
                            nodeSpacing: 150,
                            levelSeparation: 200
                        }
                    },
                    physics: {
                        enabled: false
                    },
                    nodes: {
                        shape: 'box',
                        margin: 10,
                        borderWidth: 2,
                        borderWidthSelected: 4,
                        font: {
                            size: 14,
                            face: "'Noto Sans Thai', 'Kanit', sans-serif",
                            multi: true
                        },
                        shadow: true
                    },
                    edges: {
                        color: { color: '#667eea', highlight: '#764ba2' },
                        smooth: {
                            type: 'cubicBezier',
                            roundness: 0.5
                        },
                        shadow: true
                    },
                    interaction: {
                        hover: true,
                        tooltipDelay: 200,
                        zoomView: true,
                        dragView: true
                    }
                };

                const network = new vis.Network(mindmapContainer, data, options);

                // Fit to view
                network.once('stabilizationIterationsDone', function() {
                    network.fit();
                });
            }
        });
    </script>

</body>
</html>
