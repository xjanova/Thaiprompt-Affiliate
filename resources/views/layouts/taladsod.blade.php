{{--
    Layout หลักของตลาดสดไทยพร้อม (Thai Fresh Market)

    ธีมสีหลัก:
    - เขียว (#22C55E) - ปุ่มหลักและสีเด่น
    - ส้ม (#F97316) - ราคาและไฮไลท์
    - เหลือง (#EAB308) - ดาวรีวิวและส่วนลด
    - ครีม (#FEF3C7) - พื้นหลัง

    รองรับ: Dark Mode, Responsive, Alpine.js, Vite
--}}
<!DOCTYPE html>
<html lang="th" class="h-full scroll-smooth" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5, user-scalable=yes">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#22C55E">

    <title>@yield('title', 'ตลาดสดไทยพร้อม') - {{ config('app.name', 'ไทยพร้อม') }}</title>

    {{-- Meta Tags สำหรับ SEO --}}
    <meta name="description" content="@yield('meta_description', 'ตลาดสดออนไลน์ ค้นหาอาหารสดจากผู้ขายใกล้บ้านคุณ ผักสด ผลไม้ เนื้อสัตว์ อาหารทะเล')">
    <meta name="keywords" content="ตลาดสด, อาหารสด, ผักสด, ผลไม้, เนื้อสัตว์, อาหารทะเล, ออนไลน์, ใกล้บ้าน">
    @yield('meta')

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Google Fonts - Kanit สำหรับภาษาไทย --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- ตลาดสด JavaScript --}}
    @vite(['resources/js/taladsod/app.js'])

    {{-- Alpine.js x-cloak --}}
    <style>
        [x-cloak] { display: none !important; }

        /* ฟอนต์ Kanit สำหรับทั้งหน้า */
        body {
            font-family: 'Kanit', sans-serif;
        }

        /* สไตล์ Scrollbar แบบบาง */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #22C55E;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #16A34A;
        }

        /* Scrollbar แนวนอนซ่อนไว้ */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }
        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    @yield('styles')
</head>
<body class="h-full bg-gradient-to-b from-amber-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-950 dark:to-gray-900 text-gray-900 dark:text-gray-100 antialiased">

    {{-- ===== Sticky Navbar ===== --}}
    <nav x-data="{ mobileMenuOpen: false, userMenuOpen: false }"
         class="sticky top-0 z-50 bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-green-100 dark:border-gray-800 shadow-sm">

        {{-- แถบสีเขียวด้านบน --}}
        <div class="h-1 bg-gradient-to-r from-green-500 via-yellow-400 to-orange-500"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                {{-- โลโก้ --}}
                <div class="flex-shrink-0">
                    <a href="{{ route('taladsod.home') }}" class="flex items-center gap-2 group">
                        <span class="text-2xl group-hover:animate-swing transition-transform">&#x1F3EA;</span>
                        <span class="text-lg sm:text-xl font-bold bg-gradient-to-r from-green-600 to-green-500 bg-clip-text text-transparent">
                            ตลาดสดไทยพร้อม
                        </span>
                    </a>
                </div>

                {{-- ช่องค้นหา (ซ่อนบน mobile) --}}
                <div class="hidden md:flex flex-1 max-w-lg mx-6">
                    <form action="{{ route('taladsod.search') }}" method="GET" class="w-full relative">
                        <input type="text"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="ค้นหาสินค้า... ผักสด ผลไม้ เนื้อหมู"
                               class="w-full pl-10 pr-24 py-2.5 rounded-full border border-green-200 dark:border-gray-600 bg-green-50/50 dark:bg-gray-800 focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition-all placeholder-gray-400 dark:placeholder-gray-500">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i class="fas fa-search text-green-500"></i>
                        </div>
                        <button type="submit"
                                class="absolute inset-y-0 right-0 px-4 bg-green-500 hover:bg-green-600 text-white rounded-r-full text-sm font-medium transition-colors">
                            ค้นหา
                        </button>
                    </form>
                </div>

                {{-- ปุ่ม GPS (ซ่อนบน mobile) --}}
                <div class="hidden md:flex items-center" x-data="geolocation()">
                    <button @click="getLocation()"
                            :disabled="loading"
                            class="flex items-center gap-1.5 px-3 py-2 text-sm text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-gray-800 rounded-lg transition-colors"
                            :class="{ 'animate-pulse': loading }">
                        <i class="fas fa-location-dot" :class="lat ? 'text-green-500' : 'text-gray-400'"></i>
                        <span x-text="loading ? 'กำลังค้นหา...' : (lat ? 'ตำแหน่งปัจจุบัน' : 'ระบุตำแหน่ง')"></span>
                    </button>
                </div>

                {{-- เมนูหลัก (Desktop) --}}
                <div class="hidden lg:flex items-center gap-1">
                    <a href="{{ route('taladsod.home') }}"
                       class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('taladsod.home') ? 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/30' : 'text-gray-600 dark:text-gray-300 hover:text-green-600 hover:bg-green-50 dark:hover:text-green-400 dark:hover:bg-gray-800' }}">
                        <i class="fas fa-home mr-1"></i> หน้าแรก
                    </a>
                    <a href="{{ route('taladsod.search') }}"
                       class="px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('taladsod.search') ? 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/30' : 'text-gray-600 dark:text-gray-300 hover:text-green-600 hover:bg-green-50 dark:hover:text-green-400 dark:hover:bg-gray-800' }}">
                        <i class="fas fa-magnifying-glass mr-1"></i> ค้นหา
                    </a>
                    <a href="{{ route('taladsod.search', ['view' => 'categories']) }}"
                       class="px-3 py-2 text-sm font-medium rounded-lg transition-colors text-gray-600 dark:text-gray-300 hover:text-green-600 hover:bg-green-50 dark:hover:text-green-400 dark:hover:bg-gray-800">
                        <i class="fas fa-grid-2 mr-1"></i> หมวดหมู่
                    </a>
                </div>

                {{-- ปุ่ม Dark Mode + Auth (Desktop) --}}
                <div class="hidden md:flex items-center gap-2">
                    {{-- ปุ่มสลับ Dark Mode --}}
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                            class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            :title="darkMode ? 'โหมดสว่าง' : 'โหมดมืด'">
                        <i class="fas fa-sun text-yellow-500" x-show="darkMode" x-cloak></i>
                        <i class="fas fa-moon text-gray-500" x-show="!darkMode"></i>
                    </button>

                    {{-- Auth Links --}}
                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    @click.away="open = false"
                                    class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800 rounded-lg transition-colors">
                                <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                                    <i class="fas fa-user text-green-600 dark:text-green-400 text-xs"></i>
                                </div>
                                <span class="hidden xl:inline">{{ Auth::user()->name }}</span>
                                <i class="fas fa-chevron-down text-xs transition-transform" :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            {{-- Dropdown Menu --}}
                            <div x-show="open"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg ring-1 ring-black/5 dark:ring-white/10 py-1 z-50">
                                <a href="{{ route('taladsod.seller.dashboard') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-store w-5 text-center text-green-500"></i> แดชบอร์ดผู้ขาย
                                </a>
                                <a href="{{ route('taladsod.orders') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-700 transition-colors">
                                    <i class="fas fa-box w-5 text-center text-orange-500"></i> คำสั่งซื้อของฉัน
                                </a>
                                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-700 transition-colors">
                                        <i class="fas fa-sign-out-alt w-5 text-center"></i> ออกจากระบบ
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}"
                           class="px-4 py-2 text-sm font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-gray-800 rounded-lg transition-colors">
                            เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('register') }}"
                           class="px-4 py-2 text-sm font-medium text-white bg-green-500 hover:bg-green-600 rounded-lg transition-colors shadow-sm">
                            สมัครสมาชิก
                        </a>
                    @endauth
                </div>

                {{-- ปุ่ม Hamburger (Mobile) --}}
                <div class="flex md:hidden items-center gap-2">
                    {{-- ปุ่ม Dark Mode บน Mobile --}}
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                            class="p-2 rounded-lg text-gray-500 dark:text-gray-400">
                        <i class="fas fa-sun text-yellow-500" x-show="darkMode" x-cloak></i>
                        <i class="fas fa-moon" x-show="!darkMode"></i>
                    </button>
                    <button @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <i class="fas fa-bars text-xl" x-show="!mobileMenuOpen"></i>
                        <i class="fas fa-times text-xl" x-show="mobileMenuOpen" x-cloak></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="mobileMenuOpen"
             x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             @click.away="mobileMenuOpen = false"
             class="md:hidden bg-white dark:bg-gray-900 border-b border-green-100 dark:border-gray-800 shadow-lg">
            <div class="px-4 py-3 space-y-2">

                {{-- ช่องค้นหาบน Mobile --}}
                <form action="{{ route('taladsod.search') }}" method="GET" class="relative">
                    <input type="text"
                           name="q"
                           value="{{ request('q') }}"
                           placeholder="ค้นหาสินค้า..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-full border border-green-200 dark:border-gray-600 bg-green-50/50 dark:bg-gray-800 focus:ring-2 focus:ring-green-500 text-sm">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center">
                        <i class="fas fa-search text-green-500"></i>
                    </div>
                </form>

                {{-- เมนูลิงก์ --}}
                <a href="{{ route('taladsod.home') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('taladsod.home') ? 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/30' : 'text-gray-600 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800' }}">
                    <i class="fas fa-home w-5 text-center"></i> หน้าแรก
                </a>
                <a href="{{ route('taladsod.search') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('taladsod.search') ? 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/30' : 'text-gray-600 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800' }}">
                    <i class="fas fa-magnifying-glass w-5 text-center"></i> ค้นหา
                </a>
                <a href="{{ route('taladsod.search', ['view' => 'categories']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800">
                    <i class="fas fa-th-large w-5 text-center"></i> หมวดหมู่
                </a>

                <div class="border-t border-gray-100 dark:border-gray-700 my-2"></div>

                {{-- Auth Links บน Mobile --}}
                @auth
                    <a href="{{ route('taladsod.seller.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800">
                        <i class="fas fa-store w-5 text-center text-green-500"></i> แดชบอร์ดผู้ขาย
                    </a>
                    <a href="{{ route('taladsod.orders') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-800">
                        <i class="fas fa-box w-5 text-center text-orange-500"></i> คำสั่งซื้อของฉัน
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-gray-800">
                            <i class="fas fa-sign-out-alt w-5 text-center"></i> ออกจากระบบ
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-gray-800">
                        <i class="fas fa-sign-in-alt w-5 text-center"></i> เข้าสู่ระบบ
                    </a>
                    <a href="{{ route('register') }}"
                       class="flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-sm font-medium text-white bg-green-500 hover:bg-green-600 transition-colors">
                        <i class="fas fa-user-plus"></i> สมัครสมาชิก
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    {{-- ===== Flash Messages ===== --}}
    @if(session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-20 left-1/2 -translate-x-1/2 z-50 max-w-md w-full mx-4">
            <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 rounded-xl px-4 py-3 shadow-lg flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500 text-lg"></i>
                <span class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</span>
                <button @click="show = false" class="ml-auto text-green-500 hover:text-green-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="fixed top-20 left-1/2 -translate-x-1/2 z-50 max-w-md w-full mx-4">
            <div class="bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 rounded-xl px-4 py-3 shadow-lg flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                <span class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</span>
                <button @click="show = false" class="ml-auto text-red-500 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- ===== เนื้อหาหลัก ===== --}}
    <main class="min-h-screen">
        @yield('content')
    </main>

    {{-- ===== Footer ===== --}}
    <footer class="bg-gray-900 dark:bg-black text-gray-300 mt-16">
        {{-- แถบไล่สีด้านบน --}}
        <div class="h-1 bg-gradient-to-r from-green-500 via-yellow-400 to-orange-500"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

                {{-- ข้อมูลแบรนด์ --}}
                <div class="lg:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-2xl">&#x1F3EA;</span>
                        <span class="text-lg font-bold text-white">ตลาดสดไทยพร้อม</span>
                    </div>
                    <p class="text-sm text-gray-400 leading-relaxed mb-4">
                        แพลตฟอร์มตลาดสดออนไลน์ เชื่อมต่อผู้ซื้อกับผู้ขายในละแวกใกล้เคียง สินค้าสดใหม่ทุกวัน ส่งตรงจากชาวสวนและเกษตรกรไทย
                    </p>
                    <div class="flex items-center gap-3">
                        <a href="#" class="w-9 h-9 rounded-full bg-gray-800 hover:bg-green-500 flex items-center justify-center transition-colors">
                            <i class="fab fa-facebook-f text-sm"></i>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-full bg-gray-800 hover:bg-green-500 flex items-center justify-center transition-colors">
                            <i class="fab fa-line text-sm"></i>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-full bg-gray-800 hover:bg-green-500 flex items-center justify-center transition-colors">
                            <i class="fab fa-instagram text-sm"></i>
                        </a>
                    </div>
                </div>

                {{-- ลิงก์ด่วน --}}
                <div>
                    <h3 class="text-white font-semibold mb-4">ลิงก์ด่วน</h3>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <a href="{{ route('taladsod.home') }}" class="hover:text-green-400 transition-colors flex items-center gap-2">
                                <i class="fas fa-chevron-right text-xs text-green-500"></i> หน้าแรก
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('taladsod.search') }}" class="hover:text-green-400 transition-colors flex items-center gap-2">
                                <i class="fas fa-chevron-right text-xs text-green-500"></i> ค้นหาสินค้า
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('taladsod.register-seller') }}" class="hover:text-green-400 transition-colors flex items-center gap-2">
                                <i class="fas fa-chevron-right text-xs text-green-500"></i> สมัครเป็นผู้ขาย
                            </a>
                        </li>
                        <li>
                            <a href="#" class="hover:text-green-400 transition-colors flex items-center gap-2">
                                <i class="fas fa-chevron-right text-xs text-green-500"></i> เกี่ยวกับเรา
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- หมวดหมู่สินค้า --}}
                <div>
                    <h3 class="text-white font-semibold mb-4">หมวดหมู่ยอดนิยม</h3>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <a href="{{ route('taladsod.search', ['category' => 'vegetables']) }}" class="hover:text-green-400 transition-colors flex items-center gap-2">
                                <span>&#x1F96C;</span> ผักสด
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('taladsod.search', ['category' => 'fruits']) }}" class="hover:text-green-400 transition-colors flex items-center gap-2">
                                <span>&#x1F34E;</span> ผลไม้
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('taladsod.search', ['category' => 'meat']) }}" class="hover:text-green-400 transition-colors flex items-center gap-2">
                                <span>&#x1F969;</span> เนื้อสัตว์
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('taladsod.search', ['category' => 'seafood']) }}" class="hover:text-green-400 transition-colors flex items-center gap-2">
                                <span>&#x1F990;</span> อาหารทะเล
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- ติดต่อเรา --}}
                <div>
                    <h3 class="text-white font-semibold mb-4">ติดต่อเรา</h3>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-2">
                            <i class="fab fa-line text-green-400 mt-0.5"></i>
                            <div>
                                <div class="text-gray-400">LINE Official</div>
                                <a href="#" class="text-green-400 hover:text-green-300 font-medium">@taladsod-thaiprompt</a>
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-envelope text-green-400 mt-0.5"></i>
                            <div>
                                <div class="text-gray-400">อีเมล</div>
                                <a href="mailto:support@thaiprompt.online" class="text-green-400 hover:text-green-300 font-medium">support@thaiprompt.online</a>
                            </div>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-phone text-green-400 mt-0.5"></i>
                            <div>
                                <div class="text-gray-400">โทรศัพท์</div>
                                <span class="font-medium">02-XXX-XXXX</span>
                            </div>
                        </li>
                    </ul>

                    {{-- ปุ่มเพิ่มเพื่อน LINE --}}
                    <a href="#"
                       class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors">
                        <i class="fab fa-line text-lg"></i> เพิ่มเพื่อน LINE
                    </a>
                </div>
            </div>

            {{-- Copyright --}}
            <div class="border-t border-gray-800 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-500">
                    &copy; {{ date('Y') }} ตลาดสดไทยพร้อม สงวนลิขสิทธิ์ทุกประการ
                </p>
                <div class="flex items-center gap-4 text-sm text-gray-500">
                    <a href="#" class="hover:text-green-400 transition-colors">นโยบายความเป็นส่วนตัว</a>
                    <a href="#" class="hover:text-green-400 transition-colors">ข้อกำหนดการใช้งาน</a>
                </div>
            </div>
        </div>
    </footer>

    {{-- ===== ปุ่มลอย: กลับด้านบน ===== --}}
    <div x-data="{ showScroll: false }"
         @scroll.window="showScroll = window.scrollY > 500"
         class="fixed bottom-6 right-6 z-40">
        <button x-show="showScroll"
                x-cloak
                x-transition
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="w-12 h-12 bg-green-500 hover:bg-green-600 text-white rounded-full shadow-lg flex items-center justify-center transition-all hover:scale-110">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div>

    {{-- ===== Scripts เพิ่มเติม ===== --}}
    @yield('scripts')
</body>
</html>
