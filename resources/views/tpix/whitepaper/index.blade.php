<!DOCTYPE html>
<html lang="th" x-data="{ darkMode: false }" :class="{ 'dark': darkMode }" x-init="$store.language.init()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TPIX Whitepaper - Native Cryptocurrency with Its Own Blockchain for Thaiprompt Affiliate Ecosystem">
    <meta name="keywords" content="TPIX, Blockchain, Cryptocurrency, Whitepaper, Thai, Native Coin">
    <meta name="author" content="Thaiprompt Affiliate Team">

    <title>TPIX Whitepaper | Native Cryptocurrency</title>

    {{-- Tailwind CSS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Custom Styles --}}
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        /* Alpine.js x-cloak */
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Modern Gradient Backgrounds */
        .gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }

        .gradient-secondary {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .gradient-accent {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #ec77ab 100%);
        }

        .gradient-glow {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            box-shadow: 0 0 40px rgba(102, 126, 234, 0.6);
        }

        /* Text Gradients */
        .text-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .text-gradient-pink {
            background: linear-gradient(135deg, #f093fb 0%, #ec77ab 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }

        .dark .glass {
            background: rgba(31, 41, 55, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
        }

        .glass-blue {
            background: rgba(102, 126, 234, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(102, 126, 234, 0.3);
        }

        .dark .glass-blue {
            background: rgba(102, 126, 234, 0.15);
            border: 1px solid rgba(102, 126, 234, 0.4);
        }

        /* 3D Card Effects */
        .card-3d {
            transform: perspective(1000px) rotateX(0deg) rotateY(0deg);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-3d:hover {
            transform: perspective(1000px) rotateX(2deg) rotateY(-2deg) translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(102, 126, 234, 0.4);
        }

        .dark .card-3d:hover {
            box-shadow: 0 25px 50px -12px rgba(147, 51, 234, 0.4);
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
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #4facfe);
            background-size: 400% 400%;
            animation: gradient-animation 15s ease infinite;
        }

        /* Glow Effect */
        .glow-blue {
            text-shadow: 0 0 10px rgba(102, 126, 234, 0.8),
                         0 0 20px rgba(102, 126, 234, 0.6),
                         0 0 30px rgba(102, 126, 234, 0.4);
        }

        .glow-purple {
            text-shadow: 0 0 10px rgba(118, 75, 162, 0.8),
                         0 0 20px rgba(118, 75, 162, 0.6),
                         0 0 30px rgba(118, 75, 162, 0.4);
        }

        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
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

        /* Pulse animation */
        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(102, 126, 234, 0.4);
            }
            50% {
                box-shadow: 0 0 40px rgba(102, 126, 234, 0.8);
            }
        }

        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
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
<body class="bg-white dark:bg-gray-900 transition-colors duration-300"
      x-data="{ showTranslateToast: false, translateToastMessage: '', translateToastLang: '' }"
      @language-changed.window="
        showTranslateToast = true;
        translateToastMessage = 'กำลังแปลเป็น ' + $event.detail.language + '...';
        translateToastLang = $event.detail.code;
      "
      @translation-complete.window="
        translateToastMessage = 'แปลเป็น ' + $event.detail.language + ' เรียบร้อย ✓';
        setTimeout(() => showTranslateToast = false, 2000);
      ">

    {{-- Toast Notification สำหรับการแปลภาษา --}}
    <div x-show="showTranslateToast"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-4 right-4 z-[9999] no-print">
        <div class="glass-blue px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 min-w-[300px]">
            <div class="flex-shrink-0 text-blue-600 dark:text-blue-400">
                <svg x-show="translateToastMessage.includes('กำลัง')" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <svg x-show="!translateToastMessage.includes('กำลัง')" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div class="flex-1">
                <p class="font-medium text-gray-900 dark:text-white no-translate" x-text="translateToastMessage"></p>
            </div>
        </div>
    </div>

    {{-- Fixed Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 glass no-print" x-data="{ mobileMenuOpen: false }">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                {{-- Logo --}}
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-2xl gradient-primary flex items-center justify-center shadow-2xl shadow-blue-500/50">
                        <span class="text-2xl font-black text-white">T</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gradient">TPIX</h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Whitepaper v{{ $data['version'] }}</p>
                    </div>
                </div>

                {{-- Desktop Menu --}}
                <div class="hidden lg:flex items-center space-x-1">
                    <a href="#overview" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        ภาพรวม
                    </a>
                    <a href="#use-cases" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        การใช้งาน
                    </a>
                    <a href="#tokenomics" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        Tokenomics
                    </a>
                    <a href="#roadmap" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        แผนงาน
                    </a>
                    <a href="{{ route('home') }}" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        หน้าแรก
                    </a>
                    <a href="{{ route('wiki.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span>WIKI</span>
                    </a>
                </div>

                {{-- Actions --}}
                <div class="flex items-center space-x-2">
                    {{-- Mobile Menu Toggle --}}
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="lg:hidden p-2 rounded-lg glass-blue text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                        <svg x-show="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg x-show="mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    {{-- Language Switcher --}}
                    <div x-data="{ langOpen: false }" class="relative">
                        <button @click="langOpen = !langOpen"
                                type="button"
                                class="p-2 rounded-lg glass-blue text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition relative"
                                :title="$store.language.getCurrentLanguage().nativeName">
                            <span class="text-lg no-translate" x-text="$store.language.getCurrentLanguage().flag"></span>
                            <div x-show="$store.language.isTranslating"
                                 class="absolute -top-1 -right-1 w-3 h-3 bg-gradient-primary rounded-full animate-ping"></div>
                            <div x-show="$store.language.isTranslating"
                                 class="absolute -top-1 -right-1 w-3 h-3 bg-blue-600 rounded-full"></div>
                        </button>

                        <div x-show="langOpen"
                             @click.outside="langOpen = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-2"
                             class="absolute right-0 mt-2 w-56 glass rounded-2xl shadow-2xl overflow-hidden z-50"
                             x-cloak>
                            <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xs font-bold text-gray-900 dark:text-white no-translate">
                                        <i class="fas fa-globe mr-1"></i>
                                        เลือกภาษา
                                    </h3>
                                    <button @click="$store.language.clearCache()"
                                            type="button"
                                            class="p-1 rounded hover:bg-blue-100 dark:hover:bg-blue-900/30 transition text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                                            title="รีเซ็ต">
                                        <i class="fas fa-sync text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="py-1 max-h-80 overflow-y-auto">
                                <template x-for="lang in $store.language.languages" :key="lang.code">
                                    <button @click="$store.language.setLanguage(lang.code); langOpen = false"
                                            type="button"
                                            class="w-full flex items-center gap-2 px-3 py-2 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition"
                                            :class="$store.language.current === lang.code ? 'bg-blue-100 dark:bg-blue-900/30' : ''">
                                        <span class="text-lg flex-shrink-0 no-translate" x-text="lang.flag"></span>
                                        <div class="flex-1 min-w-0 text-left">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white no-translate" x-text="lang.nativeName"></p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 no-translate" x-text="lang.name"></p>
                                        </div>
                                        <i x-show="$store.language.current === lang.code"
                                           class="fas fa-check text-blue-600 dark:text-blue-400 text-sm"></i>
                                    </button>
                                </template>
                            </div>

                            <div class="px-3 py-2 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                                    <span class="no-translate">
                                        <i class="fas fa-language mr-1"></i>
                                        Google Translate
                                    </span>
                                    <span class="no-translate">
                                        <span x-text="$store.language.languages.length"></span> ภาษา
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Dark Mode Toggle --}}
                    <button @click="darkMode = !darkMode"
                            class="p-2 rounded-lg glass-blue text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>

                    {{-- Download PDF Button --}}
                    <a href="{{ route('tpix.whitepaper.pdf') }}"
                       class="hidden sm:flex px-4 py-2 gradient-primary text-white rounded-xl hover:shadow-2xl hover:scale-105 transition shadow-lg items-center space-x-2 font-semibold">
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
                    <a href="#overview" @click="mobileMenuOpen = false" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        ภาพรวม
                    </a>
                    <a href="#use-cases" @click="mobileMenuOpen = false" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        การใช้งาน
                    </a>
                    <a href="#tokenomics" @click="mobileMenuOpen = false" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        Tokenomics
                    </a>
                    <a href="#roadmap" @click="mobileMenuOpen = false" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        แผนงาน
                    </a>
                    <a href="{{ route('home') }}" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                        หน้าแรก
                    </a>
                    <a href="{{ route('wiki.index') }}" class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 flex items-center space-x-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span>WIKI</span>
                    </a>
                    <a href="{{ route('tpix.whitepaper.pdf') }}" class="sm:hidden px-3 py-2 text-sm font-medium gradient-primary text-white rounded-xl hover:shadow-xl transition shadow-lg flex items-center space-x-2 font-semibold">
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
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse"></div>
            <div class="absolute top-40 right-10 w-72 h-72 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse" style="animation-delay: 2s;"></div>
            <div class="absolute bottom-20 left-1/2 w-72 h-72 bg-pink-500 rounded-full mix-blend-multiply filter blur-xl animate-pulse" style="animation-delay: 4s;"></div>
        </div>

        <div class="container mx-auto px-4 text-center relative z-10 pt-20">
            {{-- Main Logo --}}
            <div class="mb-8 floating">
                <div class="w-64 h-64 mx-auto rounded-3xl gradient-glow flex items-center justify-center pulse-glow">
                    <span class="text-9xl font-black text-white">T</span>
                </div>
            </div>

            {{-- Title --}}
            <h1 class="text-6xl md:text-8xl font-black text-white glow-blue mb-6 drop-shadow-2xl">
                TPIX
            </h1>

            <p class="text-2xl md:text-4xl text-white/90 font-light mb-4 drop-shadow-lg">
                Native Cryptocurrency
            </p>

            <p class="text-xl md:text-2xl text-white/80 mb-8 drop-shadow-lg">
                Blockchain ของตัวเอง สำหรับระบบนิเวศน์ Thaiprompt Affiliate
            </p>

            {{-- Key Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto mt-12">
                <div class="glass rounded-2xl p-6 hover:scale-105 transform transition-all duration-300 shadow-2xl shadow-blue-500/20">
                    <div class="text-3xl font-bold text-gradient mb-2">7B</div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">Total Supply</div>
                </div>
                <div class="glass rounded-2xl p-6 hover:scale-105 transform transition-all duration-300 shadow-2xl shadow-purple-500/20">
                    <div class="text-3xl font-bold text-gradient mb-2">2s</div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">Block Time</div>
                </div>
                <div class="glass rounded-2xl p-6 hover:scale-105 transform transition-all duration-300 shadow-2xl shadow-pink-500/20">
                    <div class="text-3xl font-bold text-gradient mb-2">1,500</div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">TPS</div>
                </div>
                <div class="glass rounded-2xl p-6 hover:scale-105 transform transition-all duration-300 shadow-2xl shadow-blue-500/20">
                    <div class="text-3xl font-bold text-gradient mb-2">IBFT</div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">Consensus</div>
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
    <div class="bg-gradient-to-b from-white to-gray-50 dark:from-gray-900 dark:to-gray-800 transition-colors duration-300">

        {{-- Table of Contents --}}
        <section id="toc" class="py-20 no-print">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <h2 class="text-4xl font-bold text-gradient mb-12 text-center">
                        สารบัญ
                    </h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        @php
                        $sections = [
                            ['id' => 'overview', 'title' => '1. ภาพรวมโครงการ', 'icon' => 'eye', 'color' => 'blue'],
                            ['id' => 'problem', 'title' => '2. ปัญหาและแนวทางแก้ไข', 'icon' => 'lightbulb', 'color' => 'purple'],
                            ['id' => 'technology', 'title' => '3. เทคโนโลยีและสถาปัตยกรรม', 'icon' => 'chip', 'color' => 'pink'],
                            ['id' => 'ecosystem', 'title' => '4. ระบบนิเวศน์ TPIX', 'icon' => 'globe', 'color' => 'blue'],
                            ['id' => 'tokenomics', 'title' => '5. โทเค็นโนมิกส์', 'icon' => 'chart-pie', 'color' => 'purple'],
                            ['id' => 'dex', 'title' => '6. Decentralized Exchange', 'icon' => 'arrows-exchange', 'color' => 'pink'],
                            ['id' => 'use-cases', 'title' => '7. Use Cases', 'icon' => 'cube', 'color' => 'blue'],
                            ['id' => 'roadmap', 'title' => '8. Roadmap', 'icon' => 'map', 'color' => 'purple'],
                            ['id' => 'team', 'title' => '9. ทีมและพันธมิตร', 'icon' => 'users', 'color' => 'pink'],
                            ['id' => 'conclusion', 'title' => '10. สรุป', 'icon' => 'check-circle', 'color' => 'blue'],
                        ];
                        @endphp

                        @foreach($sections as $section)
                        <a href="#{{ $section['id'] }}"
                           class="block p-4 glass rounded-2xl shadow-lg hover:shadow-2xl card-3d border-l-4 border-{{ $section['color'] }}-500">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 rounded-xl gradient-primary flex items-center justify-center flex-shrink-0 shadow-lg shadow-{{ $section['color'] }}-500/50">
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
        <section id="overview" class="py-20 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gradient mb-6">
                        ภาพรวมโครงการ
                    </h2>

                    <div class="prose prose-lg dark:prose-invert max-w-none">
                        <p class="text-xl text-gray-700 dark:text-gray-300 leading-relaxed mb-8">
                            {{ $data['full_name'] }} ({{ $data['short_name'] }}) เป็น Native Cryptocurrency ที่มี Blockchain ของตัวเอง
                            ออกแบบมาเพื่อรองรับระบบนิเวศน์ Thaiprompt Affiliate ด้วยความเร็วสูง ค่าธรรมเนียมต่ำ และความปลอดภัยระดับสูง
                        </p>
                    </div>

                    {{-- Key Features Grid --}}
                    <div class="grid md:grid-cols-3 gap-6 mt-12">
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl shadow-blue-500/10 dark:shadow-purple-500/20 border-t-4 border-gradient-primary">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-400/50 mb-4">
                                <i class="fas fa-bolt text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gradient mb-2">
                                Lightning Fast
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">Block time {{ $data['blockchain']['block_time'] }}, {{ $data['blockchain']['tps'] }}</p>
                        </div>

                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl shadow-blue-500/10 dark:shadow-purple-500/20 border-t-4 border-gradient-primary">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg shadow-purple-400/50 mb-4">
                                <i class="fas fa-shield-alt text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gradient mb-2">
                                Secure & Decentralized
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">{{ $data['blockchain']['consensus'] }} Consensus</p>
                        </div>

                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl shadow-blue-500/10 dark:shadow-purple-500/20 border-t-4 border-gradient-primary">
                            <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg shadow-pink-400/50 mb-4">
                                <i class="fas fa-coins text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gradient mb-2">
                                Fixed Supply
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">{{ $data['blockchain']['total_supply'] }} TPIX</p>
                        </div>
                    </div>

                    {{-- Blockchain Details --}}
                    <div class="glass rounded-2xl p-8 shadow-2xl mt-12">
                        <h3 class="text-3xl font-bold text-gradient mb-6">รายละเอียด Blockchain</h3>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-lg gradient-primary flex items-center justify-center">
                                    <i class="fas fa-network-wired text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Network</p>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $data['blockchain']['name'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-lg gradient-primary flex items-center justify-center">
                                    <i class="fas fa-link text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Chain ID</p>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $data['blockchain']['chain_id'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-lg gradient-primary flex items-center justify-center">
                                    <i class="fas fa-cube text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Virtual Machine</p>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $data['blockchain']['vm'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-lg gradient-primary flex items-center justify-center">
                                    <i class="fas fa-decimal text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Decimals</p>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $data['blockchain']['decimals'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-lg gradient-primary flex items-center justify-center">
                                    <i class="fas fa-check-double text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Finality</p>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $data['blockchain']['finality'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-lg gradient-primary flex items-center justify-center">
                                    <i class="fas fa-database text-white"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Native Coin</p>
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $data['blockchain']['native_coin'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Problem Section --}}
        <section id="problem" class="py-20 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-900 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gradient mb-6">
                        ปัญหาและแนวทางแก้ไข
                    </h2>

                    <div class="space-y-6">
                        {{-- ปัญหา 1 --}}
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 rounded-xl gradient-primary flex items-center justify-center flex-shrink-0 shadow-lg">
                                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                        ค่า Gas Fee สูง
                                    </h3>
                                    <p class="text-gray-700 dark:text-gray-300">Token บน Ethereum ต้องใช้ ETH ในการทำธุรกรรม ซึ่งมีราคาแพงมาก ($50-100/tx ในช่วงเวลาที่เครือข่ายคับคั่ง)</p>
                                </div>
                            </div>
                        </div>

                        {{-- ปัญหา 2 --}}
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 rounded-xl gradient-primary flex items-center justify-center flex-shrink-0 shadow-lg">
                                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                        ความเร็วช้า
                                    </h3>
                                    <p class="text-gray-700 dark:text-gray-300">Blockchain แบบ Public มักมีปัญหาความเร็ว Block time นาน และ TPS ต่ำ ทำให้การทำธุรกรรมช้า</p>
                                </div>
                            </div>
                        </div>

                        {{-- ปัญหา 3 --}}
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 rounded-xl gradient-primary flex items-center justify-center flex-shrink-0 shadow-lg">
                                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                        ไม่มีการควบคุมโทเค็น
                                    </h3>
                                    <p class="text-gray-700 dark:text-gray-300">Token บน Blockchain อื่นไม่สามารถควบคุม Tokenomics, การกระจาย, หรือ Utility ได้อย่างเต็มที่</p>
                                </div>
                            </div>
                        </div>

                        {{-- ปัญหา 4 --}}
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl">
                            <div class="flex items-start space-x-4">
                                <div class="w-12 h-12 rounded-xl gradient-primary flex items-center justify-center flex-shrink-0 shadow-lg">
                                    <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                        ขาดระบบนิเวศน์
                                    </h3>
                                    <p class="text-gray-700 dark:text-gray-300">Token ส่วนใหญ่ไม่มีระบบนิเวศน์ที่สมบูรณ์ ขาด DEX, Bridge, Staking และ DApps ที่เชื่อมโยงกัน</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Solutions --}}
                    <div class="mt-12">
                        <h3 class="text-3xl font-bold text-gradient mb-6">แนวทางแก้ไขด้วย TPIX</h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            {{-- Solution 1 --}}
                            <div class="glass rounded-2xl p-6 card-3d shadow-xl border-l-4 border-green-500">
                                <div class="flex items-start space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <i class="fas fa-check text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                            ค่าธรรมเนียมต่ำมาก
                                        </h4>
                                        <p class="text-gray-600 dark:text-gray-400">ใช้ TPIX เป็น Native Coin ทำให้ค่าธรรมเนียมต่ำกว่า ~99% เมื่อเทียบกับ Ethereum</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Solution 2 --}}
                            <div class="glass rounded-2xl p-6 card-3d shadow-xl border-l-4 border-green-500">
                                <div class="flex items-start space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <i class="fas fa-check text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                            ความเร็วสูง
                                        </h4>
                                        <p class="text-gray-600 dark:text-gray-400">Block time เพียง {{ $data['blockchain']['block_time'] }} และ {{ $data['blockchain']['tps'] }}</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Solution 3 --}}
                            <div class="glass rounded-2xl p-6 card-3d shadow-xl border-l-4 border-green-500">
                                <div class="flex items-start space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <i class="fas fa-check text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                            ควบคุมได้เต็มที่
                                        </h4>
                                        <p class="text-gray-600 dark:text-gray-400">Blockchain ของตัวเอง ทำให้ควบคุม Tokenomics, Distribution และ Features ได้ 100%</p>
                                    </div>
                                </div>
                            </div>

                            {{-- Solution 4 --}}
                            <div class="glass rounded-2xl p-6 card-3d shadow-xl border-l-4 border-green-500">
                                <div class="flex items-start space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                                        <i class="fas fa-check text-white"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                            ระบบนิเวศน์สมบูรณ์
                                        </h4>
                                        <p class="text-gray-600 dark:text-gray-400">มี DEX, Bridge, Staking, NFT Marketplace และ DApps ครบครันในระบบเดียว</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Technology Section --}}
        <section id="technology" class="py-20 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gradient mb-6">
                        เทคโนโลยีและสถาปัตยกรรม
                    </h2>

                    {{-- Tech Stack by Category --}}
                    @foreach($data['tech_stack'] as $category => $technologies)
                    <div class="mb-12">
                        <h3 class="text-3xl font-bold text-gradient mb-6">
                            {{ ucfirst(str_replace('_', ' ', $category)) }}
                        </h3>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($technologies as $name => $description)
                            <div class="glass rounded-2xl p-6 card-3d shadow-2xl hover:scale-105 transform transition-all duration-300">
                                <div class="w-14 h-14 rounded-xl gradient-accent flex items-center justify-center mb-4 shadow-lg shadow-purple-500/50">
                                    @if($category === 'blockchain')
                                        <i class="fas fa-cube text-white text-2xl"></i>
                                    @elseif($category === 'backend')
                                        <i class="fas fa-server text-white text-2xl"></i>
                                    @elseif($category === 'smart_contracts')
                                        <i class="fas fa-file-contract text-white text-2xl"></i>
                                    @elseif($category === 'frontend')
                                        <i class="fas fa-desktop text-white text-2xl"></i>
                                    @elseif($category === 'devops')
                                        <i class="fas fa-cogs text-white text-2xl"></i>
                                    @else
                                        <i class="fas fa-code text-white text-2xl"></i>
                                    @endif
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                    {{ $name }}
                                </h4>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $description }}</p>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach

                    {{-- Architecture Overview --}}
                    <div class="glass rounded-2xl p-8 shadow-2xl mt-12">
                        <h3 class="text-3xl font-bold text-gradient mb-6">สถาปัตยกรรมระบบ</h3>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="w-20 h-20 rounded-2xl gradient-primary mx-auto mb-4 flex items-center justify-center shadow-2xl">
                                    <i class="fas fa-layer-group text-white text-3xl"></i>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Layer 1</h4>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $data['blockchain']['name'] }} Blockchain</p>
                            </div>
                            <div class="text-center">
                                <div class="w-20 h-20 rounded-2xl gradient-accent mx-auto mb-4 flex items-center justify-center shadow-2xl">
                                    <i class="fas fa-exchange-alt text-white text-3xl"></i>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">DeFi Layer</h4>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">DEX, Staking, Bridge</p>
                            </div>
                            <div class="text-center">
                                <div class="w-20 h-20 rounded-2xl gradient-secondary mx-auto mb-4 flex items-center justify-center shadow-2xl">
                                    <i class="fas fa-rocket text-white text-3xl"></i>
                                </div>
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Application Layer</h4>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">DApps, NFT, Smart Contracts</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Ecosystem Section --}}
        <section id="ecosystem" class="py-20 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-900 dark:to-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gradient mb-6">
                        ระบบนิเวศน์ TPIX
                    </h2>

                    <p class="text-xl text-gray-700 dark:text-gray-300 mb-12">
                        TPIX Blockchain เชื่อมโยงกับระบบนิเวศน์ Thaiprompt Affiliate ที่มีผู้ใช้งานกว่า 100,000+ คน พร้อมด้วย DApps และบริการต่างๆ มากมาย
                    </p>

                    {{-- Integrations --}}
                    @if(isset($data['integrations']) && count($data['integrations']) > 0)
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                        @foreach($data['integrations'] as $integration)
                        <div class="glass rounded-2xl p-6 card-3d shadow-2xl border-t-4 border-purple-500">
                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center mb-4 shadow-lg shadow-purple-500/50">
                                <i class="fas fa-plug text-white text-xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                {{ is_array($integration) ? $integration['name'] : $integration }}
                            </h3>
                            @if(is_array($integration) && isset($integration['description']))
                            <p class="text-gray-600 dark:text-gray-400 text-sm">{{ $integration['description'] }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Ecosystem Components --}}
                    <div class="grid md:grid-cols-2 gap-6">
                        {{-- Component 1 --}}
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-t-4 border-purple-500">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center mb-4 shadow-lg shadow-purple-500/50">
                                <i class="fas fa-users text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                Affiliate Network
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">เครือข่าย Affiliate กว่า 100,000+ คน ใช้ TPIX สำหรับคอมมิชชั่น โบนัส และรางวัล</p>
                        </div>

                        {{-- Component 2 --}}
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-t-4 border-purple-500">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center mb-4 shadow-lg shadow-purple-500/50">
                                <i class="fas fa-store text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                E-Commerce Marketplace
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">ซื้อขายสินค้า บริการ และ Digital Products ด้วย TPIX</p>
                        </div>

                        {{-- Component 3 --}}
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-t-4 border-purple-500">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center mb-4 shadow-lg shadow-purple-500/50">
                                <i class="fas fa-robot text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                AI Bot Marketplace
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">ซื้อขาย AI Chatbots, LINE Bots และ Automation Tools</p>
                        </div>

                        {{-- Component 4 --}}
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-t-4 border-purple-500">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center mb-4 shadow-lg shadow-purple-500/50">
                                <i class="fas fa-hotel text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                Hotel Booking System
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">จองโรงแรมด้วย TPIX พร้อมรับส่วนลดพิเศษ</p>
                        </div>

                        {{-- Component 5 --}}
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-t-4 border-purple-500">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center mb-4 shadow-lg shadow-purple-500/50">
                                <i class="fas fa-coins text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                Staking & Rewards
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">Stake TPIX เพื่อรับรางวัลและสิทธิพิเศษต่างๆ</p>
                        </div>

                        {{-- Component 6 --}}
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-t-4 border-purple-500">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center mb-4 shadow-lg shadow-purple-500/50">
                                <i class="fas fa-image text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                NFT Marketplace
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">ซื้อขาย NFTs, Digital Art และ Collectibles</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Tokenomics Section --}}
        <section id="tokenomics" class="py-20 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gradient mb-6">
                        โทเค็นโนมิกส์
                    </h2>

                    {{-- Key Metrics --}}
                    <div class="grid md:grid-cols-4 gap-6 mb-12">
                        <div class="glass rounded-2xl p-6 text-center card-3d shadow-2xl hover:scale-105 transform transition-all duration-300">
                            <div class="text-4xl font-black text-gradient mb-2">
                                {{ number_format($data['tokenomics']['total_supply'] / 1000000000, 1) }}B
                            </div>
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Total Supply
                            </div>
                        </div>
                        <div class="glass rounded-2xl p-6 text-center card-3d shadow-2xl hover:scale-105 transform transition-all duration-300">
                            <div class="text-4xl font-black text-gradient mb-2">
                                {{ $data['blockchain']['block_time'] }}
                            </div>
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Block Time
                            </div>
                        </div>
                        <div class="glass rounded-2xl p-6 text-center card-3d shadow-2xl hover:scale-105 transform transition-all duration-300">
                            <div class="text-4xl font-black text-gradient mb-2">
                                {{ str_replace(' transactions/second', '', $data['blockchain']['tps']) }}
                            </div>
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                TPS
                            </div>
                        </div>
                        <div class="glass rounded-2xl p-6 text-center card-3d shadow-2xl hover:scale-105 transform transition-all duration-300">
                            <div class="text-4xl font-black text-gradient mb-2">
                                {{ $data['blockchain']['decimals'] }}
                            </div>
                            <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                Decimals
                            </div>
                        </div>
                    </div>

                    {{-- Distribution Chart --}}
                    <div class="glass rounded-2xl p-8 shadow-2xl mb-12">
                        <h3 class="text-3xl font-bold text-gradient mb-6">การกระจายโทเค็น</h3>
                        <div class="aspect-video">
                            <canvas id="tokenDistributionChart"></canvas>
                        </div>
                    </div>

                    {{-- Distribution Details --}}
                    <div class="grid md:grid-cols-2 gap-6 mb-12">
                        @foreach($data['tokenomics']['distribution'] as $item)
                        <div class="glass rounded-2xl p-6 card-3d shadow-xl border-l-4 border-blue-500">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $item['name'] }}
                                </h4>
                                <span class="text-2xl font-black text-gradient">
                                    {{ $item['percentage'] }}%
                                </span>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-2">
                                {{ number_format($item['amount']) }} TPIX
                            </p>
                            @if(isset($item['description']))
                            <p class="text-gray-500 dark:text-gray-500 text-xs">
                                {{ $item['description'] }}
                            </p>
                            @endif
                        </div>
                        @endforeach
                    </div>

                    {{-- Supply Info --}}
                    <div class="glass rounded-2xl p-8 shadow-2xl">
                        <h3 class="text-3xl font-bold text-gradient mb-6">ข้อมูล Supply</h3>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-gradient mb-2">
                                    {{ number_format($data['tokenomics']['total_supply']) }}
                                </div>
                                <p class="text-gray-600 dark:text-gray-400">Total Supply</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">จำนวนทั้งหมด (Fixed)</p>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-gradient mb-2">
                                    0
                                </div>
                                <p class="text-gray-600 dark:text-gray-400">Inflation Rate</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">ไม่มีการพิมพ์เพิ่ม</p>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-gradient mb-2">
                                    100%
                                </div>
                                <p class="text-gray-600 dark:text-gray-400">Transparency</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-1">โปร่งใสทุกธุรกรรม</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- DEX Section --}}
        <section id="dex" class="py-20 bg-gradient-to-r from-pink-50 to-blue-50 dark:from-gray-800 dark:to-gray-900 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gradient mb-6">
                        {{ $data['features']['dex']['title'] }}
                    </h2>

                    <p class="text-xl text-gray-700 dark:text-gray-300 mb-12">
                        Decentralized Exchange (DEX) บน TPIX Blockchain ให้คุณซื้อขายโทเค็นได้โดยตรง ไม่ผ่านตัวกลาง ปลอดภัย โปร่งใส และค่าธรรมเนียมต่ำ
                    </p>

                    {{-- DEX Features --}}
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($data['features']['dex']['items'] as $item)
                        <div class="glass rounded-2xl p-6 card-3d shadow-2xl hover:scale-105 transform transition-all duration-300">
                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-pink-500 to-red-600 flex items-center justify-center mb-4 shadow-lg shadow-pink-500/50">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                            <p class="text-lg font-medium text-gray-900 dark:text-white">{{ $item }}</p>
                        </div>
                        @endforeach
                    </div>

                    {{-- Additional DEX Info --}}
                    <div class="grid md:grid-cols-2 gap-6 mt-12">
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-l-4 border-pink-500">
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-pink-500 to-red-600 flex items-center justify-center mb-4 shadow-lg shadow-pink-500/50">
                                <i class="fas fa-exchange-alt text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                Swap & Trade
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                แลกเปลี่ยน TPIX กับโทเค็นอื่นๆ ได้ทันที ด้วย Automated Market Maker (AMM)
                            </p>
                        </div>

                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-l-4 border-pink-500">
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-pink-500 to-red-600 flex items-center justify-center mb-4 shadow-lg shadow-pink-500/50">
                                <i class="fas fa-water text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                Liquidity Pools
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                เพิ่มสภาพคล่องและรับรางวัลจากค่าธรรมเนียมการซื้อขาย
                            </p>
                        </div>

                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-l-4 border-pink-500">
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-pink-500 to-red-600 flex items-center justify-center mb-4 shadow-lg shadow-pink-500/50">
                                <i class="fas fa-seedling text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                Yield Farming
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                Stake LP tokens เพื่อรับรางวัลเพิ่มเติม และสร้างรายได้แบบ passive
                            </p>
                        </div>

                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-l-4 border-pink-500">
                            <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-pink-500 to-red-600 flex items-center justify-center mb-4 shadow-lg shadow-pink-500/50">
                                <i class="fas fa-bridge text-white text-2xl"></i>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                Cross-Chain Bridge
                            </h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                เชื่อมต่อกับ Blockchain อื่นๆ เพื่อรองรับการซื้อขายข้าม chain
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Use Cases Section --}}
        <section id="use-cases" class="py-20 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gradient mb-6">
                        Use Cases
                    </h2>

                    <div class="space-y-6">
                        @foreach($data['use_cases'] as $case)
                        <div class="glass rounded-2xl p-8 card-3d shadow-2xl border-l-4 border-blue-500">
                            <div class="flex items-start space-x-4">
                                <div class="w-16 h-16 rounded-2xl gradient-primary flex items-center justify-center flex-shrink-0 shadow-lg shadow-blue-500/50">
                                    <i class="fas fa-{{ $case['icon'] }} text-white text-2xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                        {{ $case['title'] }}
                                    </h3>
                                    <p class="text-gray-700 dark:text-gray-300 mb-4">{{ $case['description'] }}</p>
                                    @if(isset($case['features']) && count($case['features']) > 0)
                                    <ul class="space-y-2">
                                        @foreach($case['features'] as $feature)
                                        <li class="flex items-center space-x-2 text-gray-600 dark:text-gray-400">
                                            <i class="fas fa-check-circle text-green-500"></i>
                                            <span>{{ $feature }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Roadmap Section --}}
        <section id="roadmap" class="py-20 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gradient mb-12">
                        Roadmap (2023-2026)
                    </h2>

                    {{-- Timeline --}}
                    <div class="relative">
                        <div class="absolute left-1/2 transform -translate-x-1/2 h-full w-1 gradient-primary"></div>

                        @foreach($data['roadmap'] as $index => $phase)
                        <div class="mb-12 flex justify-center relative">
                            <div class="w-full md:w-5/12 {{ $index % 2 === 0 ? 'md:text-right md:mr-auto md:pr-8' : 'md:ml-auto md:pl-8' }}">
                                <div class="glass rounded-2xl p-6 card-3d shadow-2xl">
                                    <div class="inline-block px-4 py-1 rounded-full gradient-primary text-white text-sm font-bold mb-3">
                                        {{ $phase['quarter'] }}
                                    </div>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        {{ $phase['phase'] }}
                                    </h3>
                                    <div class="inline-block px-3 py-1 rounded-lg {{ $phase['status'] === 'completed' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : ($phase['status'] === 'in_progress' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400') }} text-xs font-semibold mb-4">
                                        {{ $phase['status'] === 'completed' ? '✓ เสร็จสมบูรณ์' : ($phase['status'] === 'in_progress' ? '⏳ กำลังดำเนินการ' : '📋 วางแผน') }}
                                    </div>
                                    <ul class="space-y-2 {{ $index % 2 === 0 ? 'md:text-right' : '' }}">
                                        @foreach($phase['milestones'] as $milestone)
                                        <li class="flex items-center space-x-2 text-gray-600 dark:text-gray-400 {{ $index % 2 === 0 ? 'md:flex-row-reverse md:space-x-reverse' : '' }}">
                                            <i class="fas fa-check-circle text-blue-500 flex-shrink-0"></i>
                                            <span>{{ $milestone }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            {{-- Timeline Dot --}}
                            <div class="absolute left-1/2 transform -translate-x-1/2 w-8 h-8 rounded-full gradient-glow border-4 border-white dark:border-gray-900"></div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- Team Section --}}
        <section id="team" class="py-20 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <h2 class="text-5xl font-bold text-gradient mb-12 text-center">
                        ทีมและพันธมิตร
                    </h2>

                    {{-- Core Team --}}
                    <div class="grid md:grid-cols-3 gap-8 mb-16">
                        <div class="glass rounded-2xl p-6 text-center card-3d shadow-2xl hover:scale-105 transform transition-all duration-300">
                            <div class="w-24 h-24 rounded-full gradient-primary mx-auto mb-4 flex items-center justify-center shadow-2xl shadow-blue-500/50">
                                <i class="fas fa-user-tie text-white text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                                Core Team
                            </h3>
                            <p class="text-blue-600 dark:text-blue-400 font-medium mb-2">
                                Blockchain Developers
                            </p>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                ทีมพัฒนา Blockchain ที่มีประสบการณ์ด้าน Layer 1, Smart Contracts และ DeFi
                            </p>
                        </div>

                        <div class="glass rounded-2xl p-6 text-center card-3d shadow-2xl hover:scale-105 transform transition-all duration-300">
                            <div class="w-24 h-24 rounded-full gradient-accent mx-auto mb-4 flex items-center justify-center shadow-2xl shadow-purple-500/50">
                                <i class="fas fa-users text-white text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                                Business Team
                            </h3>
                            <p class="text-blue-600 dark:text-blue-400 font-medium mb-2">
                                Strategy & Partnerships
                            </p>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                ทีมพัฒนาธุรกิจและหาพันธมิตรเพื่อขยาย Ecosystem
                            </p>
                        </div>

                        <div class="glass rounded-2xl p-6 text-center card-3d shadow-2xl hover:scale-105 transform transition-all duration-300">
                            <div class="w-24 h-24 rounded-full gradient-secondary mx-auto mb-4 flex items-center justify-center shadow-2xl shadow-blue-500/50">
                                <i class="fas fa-shield-alt text-white text-3xl"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                                Security Team
                            </h3>
                            <p class="text-blue-600 dark:text-blue-400 font-medium mb-2">
                                Smart Contract Auditors
                            </p>
                            <p class="text-gray-600 dark:text-gray-400 text-sm">
                                ทีม Security ตรวจสอบและรักษาความปลอดภัยของ Smart Contracts
                            </p>
                        </div>
                    </div>

                    {{-- Advisors & Partners --}}
                    <div class="glass rounded-2xl p-8 shadow-2xl mb-12">
                        <h3 class="text-3xl font-bold text-gradient mb-6 text-center">ที่ปรึกษา</h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 rounded-xl gradient-primary flex items-center justify-center shadow-lg">
                                    <i class="fas fa-graduation-cap text-white text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">Blockchain Advisors</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm">ผู้เชี่ยวชาญด้าน Blockchain และ Cryptocurrency</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="w-16 h-16 rounded-xl gradient-accent flex items-center justify-center shadow-lg">
                                    <i class="fas fa-balance-scale text-white text-2xl"></i>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">Legal Advisors</h4>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm">ที่ปรึกษากฎหมายด้าน Crypto และ Fintech</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Partners --}}
                    <div class="glass rounded-2xl p-8 shadow-2xl">
                        <h3 class="text-3xl font-bold text-gradient mb-6 text-center">พันธมิตรของเรา</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div class="glass rounded-xl p-4 text-center hover:scale-105 transform transition-all duration-300">
                                <div class="w-16 h-16 rounded-xl gradient-primary mx-auto mb-3 flex items-center justify-center shadow-lg">
                                    <i class="fas fa-exchange-alt text-white text-2xl"></i>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white">Exchanges</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Trading Platforms</p>
                            </div>
                            <div class="glass rounded-xl p-4 text-center hover:scale-105 transform transition-all duration-300">
                                <div class="w-16 h-16 rounded-xl gradient-accent mx-auto mb-3 flex items-center justify-center shadow-lg">
                                    <i class="fas fa-wallet text-white text-2xl"></i>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white">Wallets</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Wallet Providers</p>
                            </div>
                            <div class="glass rounded-xl p-4 text-center hover:scale-105 transform transition-all duration-300">
                                <div class="w-16 h-16 rounded-xl gradient-secondary mx-auto mb-3 flex items-center justify-center shadow-lg">
                                    <i class="fas fa-code text-white text-2xl"></i>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white">DApps</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Application Partners</p>
                            </div>
                            <div class="glass rounded-xl p-4 text-center hover:scale-105 transform transition-all duration-300">
                                <div class="w-16 h-16 rounded-xl gradient-primary mx-auto mb-3 flex items-center justify-center shadow-lg">
                                    <i class="fas fa-handshake text-white text-2xl"></i>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white">Communities</p>
                                <p class="text-xs text-gray-600 dark:text-gray-400">Community Partners</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Conclusion Section --}}
        <section id="conclusion" class="py-20 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-gray-800 dark:to-gray-900 page-break">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <h2 class="text-5xl font-bold text-gradient mb-6 text-center">
                        สรุป
                    </h2>

                    <div class="glass rounded-3xl p-12 shadow-2xl text-center">
                        <p class="text-xl text-gray-700 dark:text-gray-300 leading-relaxed mb-8">
                            TPIX เป็น Native Cryptocurrency ที่มี Blockchain ของตัวเอง ออกแบบมาเพื่อแก้ปัญหาค่าธรรมเนียมสูง ความเร็วช้า
                            และข้อจำกัดของ Token บน Blockchain อื่นๆ ด้วยความเร็วสูง ({{ $data['blockchain']['tps'] }} TPS),
                            ค่าธรรมเนียมต่ำมาก และระบบนิเวศน์ที่สมบูรณ์ TPIX จะเป็นหัวใจหลักของระบบ Thaiprompt Affiliate
                            ที่มีผู้ใช้งานกว่า 100,000+ คน และเติบโตอย่างต่อเนื่อง
                        </p>

                        <div class="grid md:grid-cols-3 gap-6 mb-8">
                            <div>
                                <div class="text-4xl font-black text-gradient mb-2">{{ number_format($data['tokenomics']['total_supply'] / 1000000000, 1) }}B</div>
                                <p class="text-gray-600 dark:text-gray-400 font-medium">Total Supply</p>
                            </div>
                            <div>
                                <div class="text-4xl font-black text-gradient mb-2">{{ $data['blockchain']['block_time'] }}</div>
                                <p class="text-gray-600 dark:text-gray-400 font-medium">Block Time</p>
                            </div>
                            <div>
                                <div class="text-4xl font-black text-gradient mb-2">{{ str_replace(' transactions/second', '', $data['blockchain']['tps']) }}</div>
                                <p class="text-gray-600 dark:text-gray-400 font-medium">TPS</p>
                            </div>
                        </div>

                        {{-- Call to Action --}}
                        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                            <a href="{{ route('home') }}"
                               class="px-8 py-4 gradient-primary text-white rounded-xl font-bold shadow-2xl shadow-blue-500/50 hover:scale-105 transform transition-all duration-300">
                                เริ่มต้นใช้งาน TPIX
                            </a>
                            <a href="{{ route('wiki.index') }}"
                               class="px-8 py-4 glass-blue text-blue-600 dark:text-blue-400 rounded-xl font-bold hover:scale-105 transform transition-all duration-300">
                                ศึกษาเพิ่มเติมใน WIKI
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Footer --}}
        <footer class="py-12 bg-gray-900 text-white">
            <div class="container mx-auto px-4">
                <div class="text-center">
                    <div class="flex items-center justify-center space-x-3 mb-4">
                        <div class="w-10 h-10 rounded-xl gradient-primary flex items-center justify-center">
                            <span class="text-lg font-black text-white">T</span>
                        </div>
                        <span class="text-2xl font-bold text-gradient-pink">TPIX</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Native Cryptocurrency for Thaiprompt Affiliate Ecosystem
                    </p>
                    <div class="flex justify-center space-x-6 mb-6">
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-twitter text-2xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-telegram text-2xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-discord text-2xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition">
                            <i class="fab fa-github text-2xl"></i>
                        </a>
                    </div>
                    <p class="text-sm text-gray-500">
                        &copy; {{ date('Y') }} Thaiprompt Affiliate. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>

    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    {{-- Charts Initialization --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Token Distribution Chart
            const distributionCtx = document.getElementById('tokenDistributionChart');
            if (distributionCtx) {
                // Extract labels and percentages from distribution data
                const distributionData = @json($data['tokenomics']['distribution']);
                const labels = distributionData.map(item => item.name);
                const percentages = distributionData.map(item => item.percentage);

                new Chart(distributionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: percentages,
                            backgroundColor: [
                                'rgba(102, 126, 234, 0.8)',
                                'rgba(118, 75, 162, 0.8)',
                                'rgba(240, 147, 251, 0.8)',
                                'rgba(79, 172, 254, 0.8)',
                                'rgba(236, 119, 171, 0.8)',
                            ],
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 14,
                                        family: "'Inter', sans-serif"
                                    },
                                    color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const item = distributionData[context.dataIndex];
                                        return label + ': ' + value + '% (' + item.amount.toLocaleString() + ' TPIX)';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>

    {{-- Google Translate Element --}}
    <script type="text/javascript">
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'th',
                includedLanguages: 'th,en,zh-CN,zh-TW,ja,ko,vi,my,lo,km,id,ms,tl,es,fr,de,it,pt,ru,ar',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
        }
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

    {{-- Hidden Google Translate Element (ซ่อนไว้ เพราะใช้ custom UI) --}}
    <div id="google_translate_element" style="display: none;"></div>

</body>
</html>
