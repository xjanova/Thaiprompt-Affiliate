@extends('layouts.app')

@section('title', 'คู่มือและความช่วยเหลือ - Thaiprompt Affiliate')

@section('content')
@php
    // สีหลักของระบบ
    $primaryColor = \App\Models\Setting::get('primary_color', '#3B82F6');
    $secondaryColor = \App\Models\Setting::get('secondary_color', '#8B5CF6');
    $accentColor = \App\Models\Setting::get('accent_color', '#EC4899');
@endphp

{{-- 📚 WIKI KNOWLEDGE BASE - V3 VERSION (Tailwind + Alpine.js) --}}
<div
    x-data="{
        currentCategory: 'overview',
        mobileMenuOpen: false,
        searchQuery: '',
        loading: false,
        scrollProgress: 0,

        // รายการหมวดหมู่
        categories: [
            { id: 'overview', icon: '🚀', label: 'ภาพรวม & สรุปฟีเจอร์', color: 'from-blue-600 to-cyan-600' },
            { id: 'getting-started', icon: '🎯', label: 'เริ่มต้นใช้งาน', color: 'from-green-600 to-emerald-600' },
            { id: 'mlm-affiliate', icon: '💎', label: 'MLM & Affiliate', color: 'from-purple-600 to-pink-600',
              submenu: [
                { id: 'mlm-binary', label: '🌳 Binary System' },
                { id: 'mlm-unilevel', label: '🎯 Unilevel System' },
                { id: 'mlm-commission', label: '💰 Commission' },
                { id: 'mlm-rank', label: '👑 Rank & Bonus' },
                { id: 'mlm-genealogy', label: '📊 Genealogy' }
              ]
            },
            { id: 'ai-bot', icon: '🤖', label: 'AI & Bot System', color: 'from-violet-600 to-purple-600',
              submenu: [
                { id: 'ai-chatbot', label: '🧠 AI Chatbot' },
                { id: 'ai-creation', label: '🎨 AI Image/Video' },
                { id: 'ai-line', label: '💬 LINE AI Bot' }
              ]
            },
            { id: 'ecommerce', icon: '🛒', label: 'E-Commerce & POS', color: 'from-orange-600 to-amber-600' },
            { id: 'hotel', icon: '🏨', label: 'Hotel Management', color: 'from-red-600 to-rose-600' },
            { id: 'crypto', icon: '₿', label: 'Crypto & Blockchain', color: 'from-yellow-600 to-amber-600' },
            { id: 'wallet', icon: '💳', label: 'Wallet System', color: 'from-indigo-600 to-blue-600' },
            { id: 'academy', icon: '🎓', label: 'Academy & Learning', color: 'from-teal-600 to-cyan-600' },
            { id: 'api', icon: '🔌', label: 'API & Integration', color: 'from-slate-600 to-gray-600' },
            { id: 'faq', icon: '❓', label: 'FAQ & Troubleshooting', color: 'from-pink-600 to-fuchsia-600' }
        ],

        // เปลี่ยนหมวดหมู่
        changeCategory(categoryId) {
            this.currentCategory = categoryId;
            this.mobileMenuOpen = false;
            this.loadContent(categoryId);

            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        // โหลดเนื้อหา
        async loadContent(categoryId) {
            this.loading = true;

            try {
                // Simulate loading for demo
                await new Promise(resolve => setTimeout(resolve, 300));

                // TODO: Implement actual AJAX content loading
                // const response = await fetch(`/wiki/content/${categoryId}`);
                // const data = await response.json();

                this.loading = false;
            } catch (error) {
                console.error('Error loading content:', error);
                this.loading = false;
            }
        },

        // คำนวณ progress bar การอ่าน
        updateScrollProgress() {
            const winScroll = document.documentElement.scrollTop || document.body.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            this.scrollProgress = (winScroll / height) * 100;
        },

        // Initialize
        init() {
            window.addEventListener('scroll', () => this.updateScrollProgress());
            this.updateScrollProgress();
        }
    }"
    x-init="init()"
    class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"
>
    {{-- Reading Progress Bar - ติดด้านบน --}}
    <div class="fixed top-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 z-50">
        <div
            class="h-full bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 transition-all duration-300 shadow-lg shadow-blue-500/50"
            :style="`width: ${scrollProgress}%`"
        ></div>
    </div>

    {{-- Mobile Menu Toggle Button --}}
    <button
        @click="mobileMenuOpen = !mobileMenuOpen"
        class="lg:hidden fixed bottom-6 right-6 z-40 w-14 h-14 bg-gradient-to-br from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 transform hover:scale-110 active:scale-95"
        aria-label="เปิด/ปิดเมนู"
    >
        <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <svg x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>

    {{-- Main Grid Layout --}}
    <div class="max-w-[1920px] mx-auto p-4 lg:p-8 grid lg:grid-cols-[320px_1fr] gap-6 lg:gap-8">

        {{-- Sidebar Navigation --}}
        <aside
            x-show="mobileMenuOpen || window.innerWidth >= 1024"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-x-full"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-full"
            class="lg:sticky lg:top-24 lg:max-h-[calc(100vh-8rem)] overflow-y-auto
                   fixed lg:relative inset-0 lg:inset-auto z-30 lg:z-auto
                   bg-white/95 dark:bg-gray-800/95 lg:bg-white lg:dark:bg-gray-800
                   backdrop-blur-xl lg:backdrop-blur-none
                   lg:rounded-2xl lg:shadow-xl lg:border lg:border-gray-200 lg:dark:border-gray-700"
        >
            {{-- Sidebar Header --}}
            <div class="sticky top-0 bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 text-white p-6 rounded-t-2xl lg:rounded-2xl shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-2xl backdrop-blur-sm">
                        📚
                    </div>
                    <div>
                        <h2 class="text-xl font-bold">คู่มือการใช้งาน</h2>
                        <p class="text-sm text-white/80">TP-Affiliate v{{ $stats['version'] ?? '3.0' }}</p>
                    </div>
                </div>
            </div>

            {{-- Navigation Menu --}}
            <nav class="p-4 space-y-2">
                <template x-for="category in categories" :key="category.id">
                    <div>
                        {{-- Main Menu Item --}}
                        <button
                            @click="changeCategory(category.id)"
                            :class="currentCategory === category.id ?
                                'bg-gradient-to-r ' + category.color + ' text-white shadow-lg scale-105' :
                                'bg-gray-100 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-md"
                        >
                            <span class="text-xl" x-text="category.icon"></span>
                            <span class="flex-1 text-left text-sm" x-text="category.label"></span>
                            <svg x-show="category.submenu && currentCategory === category.id" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Submenu --}}
                        <div
                            x-show="category.submenu && currentCategory === category.id"
                            x-transition
                            class="mt-2 ml-4 space-y-1"
                            style="display: none;"
                        >
                            <template x-for="item in category.submenu" :key="item.id">
                                <a
                                    @click.prevent="changeCategory(item.id)"
                                    href="#"
                                    class="block px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                    x-text="item.label"
                                ></a>
                            </template>
                        </div>
                    </div>
                </template>
            </nav>

            {{-- Quick Links - การช่วยเหลือด่วน --}}
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                    <span>⚡</span> ลิงก์ด่วน
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('user.tickets.create') }}" class="block px-3 py-2 text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors">
                        🎫 สร้าง Support Ticket
                    </a>
                    <a href="{{ route('user.dashboard') }}" class="block px-3 py-2 text-xs bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-colors">
                        📊 กลับสู่แดชบอร์ด
                    </a>
                    <a href="{{ route('user.mlm.dashboard') }}" class="block px-3 py-2 text-xs bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300 rounded-lg hover:bg-pink-100 dark:hover:bg-pink-900/40 transition-colors">
                        💎 MLM Dashboard
                    </a>
                </div>
            </div>
        </aside>

        {{-- Main Content Area --}}
        <main class="min-h-screen">
            {{-- Search Bar --}}
            <div class="mb-6">
                <div class="relative">
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="🔍 ค้นหาในคู่มือ... (เช่น MLM, Wallet, API)"
                        class="w-full px-6 py-4 pr-12 bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-2xl
                               focus:border-blue-500 dark:focus:border-blue-400 focus:ring-4 focus:ring-blue-500/20
                               text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400
                               transition-all duration-300 shadow-lg"
                    >
                    <button class="absolute right-4 top-1/2 -translate-y-1/2 w-8 h-8 bg-gradient-to-br from-blue-600 to-purple-600 text-white rounded-lg flex items-center justify-center hover:scale-110 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Content Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">

                {{-- Loading State --}}
                <div x-show="loading" class="flex flex-col items-center justify-center py-32" style="display: none;">
                    <div class="w-16 h-16 border-4 border-gray-200 dark:border-gray-700 border-t-blue-600 rounded-full animate-spin"></div>
                    <p class="mt-4 text-gray-600 dark:text-gray-400 font-semibold">กำลังโหลดเนื้อหา...</p>
                </div>

                {{-- Content Area --}}
                <div x-show="!loading" class="p-8 lg:p-12">

                    {{-- ภาพรวม & สรุปฟีเจอร์ --}}
                    <div x-show="currentCategory === 'overview'">
                        <div class="mb-8 pb-8 border-b border-gray-200 dark:border-gray-700">
                            <h1 class="text-4xl font-black bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent mb-4">
                                🚀 ภาพรวมระบบ Thaiprompt Affiliate
                            </h1>
                            <p class="text-xl text-gray-600 dark:text-gray-400">
                                แพลตฟอร์ม All-in-One สำหรับธุรกิจ MLM, E-Commerce, AI, และ Blockchain
                            </p>
                        </div>

                        {{-- ระบบสถิติ --}}
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 p-6 rounded-2xl border border-blue-200 dark:border-blue-800">
                                <div class="text-3xl font-black text-blue-600 dark:text-blue-400">{{ number_format($stats['total_users'] ?? 0) }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">👥 ผู้ใช้งาน</div>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 p-6 rounded-2xl border border-purple-200 dark:border-purple-800">
                                <div class="text-3xl font-black text-purple-600 dark:text-purple-400">{{ number_format($stats['total_affiliates'] ?? 0) }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">💎 Affiliate Members</div>
                            </div>
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 p-6 rounded-2xl border border-green-200 dark:border-green-800">
                                <div class="text-3xl font-black text-green-600 dark:text-green-400">{{ number_format($stats['database_models'] ?? 0) }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">📦 Models</div>
                            </div>
                            <div class="bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 p-6 rounded-2xl border border-orange-200 dark:border-orange-800">
                                <div class="text-3xl font-black text-orange-600 dark:text-orange-400">{{ number_format($stats['http_controllers'] ?? 0) }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">🎮 Controllers</div>
                            </div>
                        </div>

                        {{-- ฟีเจอร์หลัก --}}
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3">
                            <span class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 text-white rounded-xl flex items-center justify-center text-xl">✨</span>
                            ฟีเจอร์หลักของระบบ
                        </h2>

                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {{-- MLM & Affiliate --}}
                            <div class="group bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 p-6 rounded-2xl border-2 border-purple-200 dark:border-purple-800 hover:border-purple-400 dark:hover:border-purple-600 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer"
                                 @click="changeCategory('mlm-affiliate')">
                                <div class="text-4xl mb-4">💎</div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">MLM & Affiliate</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    ระบบ MLM แบบ Binary, Unilevel และ Hybrid พร้อม Commission ที่ซับซ้อน
                                </p>
                                <div class="flex items-center gap-2 text-purple-600 dark:text-purple-400 text-sm font-semibold group-hover:gap-3 transition-all">
                                    อ่านเพิ่มเติม <span>→</span>
                                </div>
                            </div>

                            {{-- AI & Bot System --}}
                            <div class="group bg-gradient-to-br from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 p-6 rounded-2xl border-2 border-violet-200 dark:border-violet-800 hover:border-violet-400 dark:hover:border-violet-600 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer"
                                 @click="changeCategory('ai-bot')">
                                <div class="text-4xl mb-4">🤖</div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">AI & Bot System</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    AI Chatbot, Image/Video Generation, LINE AI Bot Integration
                                </p>
                                <div class="flex items-center gap-2 text-violet-600 dark:text-violet-400 text-sm font-semibold group-hover:gap-3 transition-all">
                                    อ่านเพิ่มเติม <span>→</span>
                                </div>
                            </div>

                            {{-- E-Commerce & POS --}}
                            <div class="group bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 p-6 rounded-2xl border-2 border-orange-200 dark:border-orange-800 hover:border-orange-400 dark:hover:border-orange-600 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer"
                                 @click="changeCategory('ecommerce')">
                                <div class="text-4xl mb-4">🛒</div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">E-Commerce & POS</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    ระบบร้านค้าออนไลน์และ POS ครบวงจร พร้อมการจัดการสินค้า
                                </p>
                                <div class="flex items-center gap-2 text-orange-600 dark:text-orange-400 text-sm font-semibold group-hover:gap-3 transition-all">
                                    อ่านเพิ่มเติม <span>→</span>
                                </div>
                            </div>

                            {{-- Crypto & Blockchain --}}
                            <div class="group bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 p-6 rounded-2xl border-2 border-yellow-200 dark:border-yellow-800 hover:border-yellow-400 dark:hover:border-yellow-600 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer"
                                 @click="changeCategory('crypto')">
                                <div class="text-4xl mb-4">₿</div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Crypto & Blockchain</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    TPIX Token, DEX, Staking, และ Crypto Wallet Integration
                                </p>
                                <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400 text-sm font-semibold group-hover:gap-3 transition-all">
                                    อ่านเพิ่มเติม <span>→</span>
                                </div>
                            </div>

                            {{-- Hotel Management --}}
                            <div class="group bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 p-6 rounded-2xl border-2 border-red-200 dark:border-red-800 hover:border-red-400 dark:hover:border-red-600 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer"
                                 @click="changeCategory('hotel')">
                                <div class="text-4xl mb-4">🏨</div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Hotel Management</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    ระบบจองโรงแรม การจัดการห้องพัก และ Channel Management
                                </p>
                                <div class="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm font-semibold group-hover:gap-3 transition-all">
                                    อ่านเพิ่มเติม <span>→</span>
                                </div>
                            </div>

                            {{-- Wallet System --}}
                            <div class="group bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 p-6 rounded-2xl border-2 border-indigo-200 dark:border-indigo-800 hover:border-indigo-400 dark:hover:border-indigo-600 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer"
                                 @click="changeCategory('wallet')">
                                <div class="text-4xl mb-4">💳</div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Wallet System</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    กระเป๋าเงิน THB และ Crypto พร้อมระบบฝาก-ถอน-โอนที่ปลอดภัย
                                </p>
                                <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 text-sm font-semibold group-hover:gap-3 transition-all">
                                    อ่านเพิ่มเติม <span>→</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- เริ่มต้นใช้งาน --}}
                    <div x-show="currentCategory === 'getting-started'" style="display: none;">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 bg-clip-text text-transparent mb-6">
                            🎯 เริ่มต้นใช้งาน Thaiprompt Affiliate
                        </h1>

                        <div class="prose prose-lg dark:prose-invert max-w-none">
                            <h2>ขั้นตอนการเริ่มต้น</h2>

                            <div class="grid gap-6 mt-6">
                                <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-600 p-6 rounded-r-xl">
                                    <h3 class="text-xl font-bold text-blue-900 dark:text-blue-100 mb-2">1️⃣ สมัครสมาชิก</h3>
                                    <p class="text-gray-700 dark:text-gray-300">
                                        ลงทะเบียนบัญชีใหม่ด้วยอีเมลหรือเชื่อมต่อผ่าน LINE OA
                                    </p>
                                    <a href="{{ route('register') }}" class="inline-block mt-3 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                                        สมัครสมาชิก →
                                    </a>
                                </div>

                                <div class="bg-purple-50 dark:bg-purple-900/20 border-l-4 border-purple-600 p-6 rounded-r-xl">
                                    <h3 class="text-xl font-bold text-purple-900 dark:text-purple-100 mb-2">2️⃣ ยืนยันตัวตน (KYC)</h3>
                                    <p class="text-gray-700 dark:text-gray-300">
                                        อัพโหลดเอกสารยืนยันตัวตนเพื่อปลดล็อคฟีเจอร์ทั้งหมด
                                    </p>
                                    @auth
                                    <a href="{{ route('user.kyc.index') }}" class="inline-block mt-3 px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition-colors">
                                        ยืนยันตัวตน →
                                    </a>
                                    @endauth
                                </div>

                                <div class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-600 p-6 rounded-r-xl">
                                    <h3 class="text-xl font-bold text-green-900 dark:text-green-100 mb-2">3️⃣ เริ่มต้นทำ MLM</h3>
                                    <p class="text-gray-700 dark:text-gray-300">
                                        เลือกแผน MLM และเริ่มสร้างทีมของคุณ
                                    </p>
                                    @auth
                                    <a href="{{ route('user.mlm.dashboard') }}" class="inline-block mt-3 px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors">
                                        MLM Dashboard →
                                    </a>
                                    @endauth
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Default content for other categories --}}
                    <div x-show="currentCategory !== 'overview' && currentCategory !== 'getting-started'" style="display: none;">
                        <div class="text-center py-20">
                            <div class="text-6xl mb-6">🚧</div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">กำลังพัฒนา</h2>
                            <p class="text-gray-600 dark:text-gray-400 mb-8">
                                เนื้อหาส่วนนี้กำลังอยู่ระหว่างการพัฒนา กรุณากลับมาตรวจสอบอีกครั้งในภายหลัง
                            </p>
                            <button
                                @click="changeCategory('overview')"
                                class="px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg"
                            >
                                กลับสู่หน้าภาพรวม
                            </button>
                        </div>
                    </div>

                </div>

                {{-- Footer Help Section --}}
                <div class="bg-gradient-to-r from-blue-50 via-purple-50 to-pink-50 dark:from-blue-900/20 dark:via-purple-900/20 dark:to-pink-900/20 border-t border-gray-200 dark:border-gray-700 p-8">
                    <div class="text-center">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                            💬 ยังมีคำถามหรือต้องการความช่วยเหลือ?
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            ทีมงานของเรายินดีให้บริการตลอด 24/7
                        </p>
                        <div class="flex flex-wrap justify-center gap-4">
                            <a href="{{ route('user.tickets.create') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                                🎫 สร้าง Support Ticket
                            </a>
                            <a href="{{ route('user.mlm.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                                💎 ไปที่ MLM Dashboard
                            </a>
                            <a href="{{ route('user.dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 shadow-lg">
                                📊 กลับสู่แดชบอร์ด
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
@endsection
