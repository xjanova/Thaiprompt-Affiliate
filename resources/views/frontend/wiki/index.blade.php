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
        currentLanguage: 'th',
        languageMenuOpen: false,
        langMenuPosition: { top: 0, left: 0 },

        // รายการภาษาที่รองรับ (ฟรี, ใช้ Google Translate Widget)
        availableLanguages: [
            { code: 'th', name: 'ไทย', flag: '🇹🇭' },
            { code: 'en', name: 'English', flag: '🇺🇸' },
            { code: 'zh-CN', name: '中文', flag: '🇨🇳' },
            { code: 'ja', name: '日本語', flag: '🇯🇵' }
        ],

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

        // เปลี่ยนภาษา (ใช้แนวทางเดียวกับหน้า Admin)
        switchLanguage(langCode) {
            var self = this;
            self.currentLanguage = langCode;
            self.languageMenuOpen = false;

            console.log('🌐 Switching to language: ' + langCode);

            if (langCode === 'th') {
                // กลับไปภาษาไทยต้นฉบับ - reload page
                console.log('🔄 Resetting to Thai (reloading...)');
                location.reload();
            } else {
                // แปลเป็นภาษาอื่น - ใช้ Google Translate API
                console.log('🔄 Translating to: ' + langCode);
                self.translateWikiPage(langCode);
            }
        },

        // แปลภาษาทั้งหน้า Wiki (ตามแนวทางหน้า Admin)
        translateWikiPage(targetLang) {
            var self = this;

            // หา elements ที่ต้องการแปล
            var elements = document.querySelectorAll('h1, h2, h3, h4, h5, h6, p, li, button, a, span:not(.notranslate), div.prose');

            var translatedCount = 0;
            var totalElements = elements.length;

            console.log('📝 Found ' + totalElements + ' elements to translate');

            elements.forEach(function(element) {
                var text = element.textContent.trim();

                // ข้าม element ที่ไม่มี text หรือมีแต่ emoji/symbols
                if (!text || text.length < 2 || /^[\d\s\W]+$/.test(text)) {
                    return;
                }

                // แปลด้วย Google Translate API (ฟรี, ไม่ต้อง API key)
                fetch('https://translate.googleapis.com/translate_a/single?client=gtx&sl=th&tl=' + targetLang + '&dt=t&q=' + encodeURIComponent(text))
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (data && data[0] && data[0][0]) {
                            element.textContent = data[0][0][0];
                            translatedCount++;

                            if (translatedCount % 10 === 0) {
                                console.log('✅ Translated ' + translatedCount + '/' + totalElements + ' elements');
                            }
                        }
                    })
                    .catch(function(error) {
                        console.error('❌ Translation error:', error);
                    });
            });

            console.log('✨ Translation started for ' + totalElements + ' elements');
        },

        // ดึงข้อมูลภาษาปัจจุบัน
        getCurrentLanguage() {
            var self = this;
            var found = this.availableLanguages.find(function(lang) {
                return lang.code === self.currentLanguage;
            });
            return found || this.availableLanguages[0];
        },

        // เปิด language menu และคำนวณตำแหน่ง
        toggleLanguageMenu() {
            var self = this;
            this.languageMenuOpen = !this.languageMenuOpen;

            if (this.languageMenuOpen) {
                this.$nextTick(function() {
                    var button = self.$refs.langButton;
                    if (button) {
                        var rect = button.getBoundingClientRect();
                        self.langMenuPosition = {
                            top: rect.bottom + 8,
                            left: rect.left
                        };
                    }
                });
            }
        },

        // Initialize
        init() {
            var self = this;

            // เริ่มต้นที่ภาษาไทยเสมอ
            self.currentLanguage = 'th';
            console.log('✅ Wiki initialized - Language: Thai');

            // Scroll progress tracking
            window.addEventListener('scroll', function() {
                self.updateScrollProgress();
            });
            self.updateScrollProgress();
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
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-2xl backdrop-blur-sm">
                        📚
                    </div>
                    <div class="flex-1">
                        <h2 class="text-xl font-bold">คู่มือการใช้งาน</h2>
                        <p class="text-sm text-white/80">TP-Affiliate v{{ $stats['version'] ?? '3.0' }}</p>
                    </div>
                </div>

                {{-- Language Selector - ระบบแปลภาษาฟรี --}}
                <div class="relative">
                    <button
                        @click="toggleLanguageMenu()"
                        x-ref="langButton"
                        class="w-full flex items-center justify-between gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-lg transition-all duration-300 border border-white/20"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-xl" x-text="getCurrentLanguage().flag"></span>
                            <span class="text-sm font-medium" x-text="getCurrentLanguage().name"></span>
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-300" :class="languageMenuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
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

                    {{-- MLM & Affiliate System --}}
                    <div x-show="currentCategory === 'mlm-affiliate' || currentCategory === 'mlm-binary' || currentCategory === 'mlm-unilevel' || currentCategory === 'mlm-commission' || currentCategory === 'mlm-rank' || currentCategory === 'mlm-genealogy'" style="display: none;">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-purple-600 via-pink-600 to-fuchsia-600 bg-clip-text text-transparent mb-6">
                            💎 ระบบ MLM & Affiliate Marketing
                        </h1>

                        <div class="prose prose-lg dark:prose-invert max-w-none space-y-8">
                            <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border-l-4 border-purple-600 p-6 rounded-r-xl">
                                <h3 class="text-xl font-bold text-purple-900 dark:text-purple-100 mb-3">🌟 ภาพรวมระบบ MLM</h3>
                                <p class="text-gray-700 dark:text-gray-300 mb-4">
                                    ระบบ MLM (Multi-Level Marketing) ของ Thaiprompt Affiliate รองรับทั้ง <strong>Binary</strong>, <strong>Unilevel</strong>, และ <strong>Hybrid</strong> พร้อมระบบคอมมิชชั่นที่ซับซ้อนและยืดหยุ่น
                                </p>
                            </div>

                            {{-- Binary System --}}
                            <div class="border-2 border-purple-200 dark:border-purple-800 rounded-xl p-6">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                                    <span class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-600 text-white rounded-xl flex items-center justify-center">🌳</span>
                                    Binary System (ระบบทวิภาค)
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300 mb-4">
                                    โครงสร้างแบบ 2 ขา (Left & Right) ที่สมดุลและเหมาะกับการสร้างทีมขนาดใหญ่
                                </p>
                                <div class="grid md:grid-cols-2 gap-4 mt-4">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                        <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-2">✅ จุดเด่น</h4>
                                        <ul class="text-sm space-y-1 text-gray-700 dark:text-gray-300">
                                            <li>• Spillover จากอัพไลน์</li>
                                            <li>• Balanced commissions</li>
                                            <li>• ทีมเติบโตเร็ว</li>
                                        </ul>
                                    </div>
                                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                                        <h4 class="font-bold text-purple-900 dark:text-purple-100 mb-2">📊 Commission Types</h4>
                                        <ul class="text-sm space-y-1 text-gray-700 dark:text-gray-300">
                                            <li>• Direct Referral Bonus</li>
                                            <li>• Pairing Bonus</li>
                                            <li>• Matching Bonus</li>
                                        </ul>
                                    </div>
                                </div>
                                @auth
                                <a href="{{ route('user.mlm.genealogy') }}?type=binary" class="inline-block mt-4 px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition-colors">
                                    ดู Binary Tree →
                                </a>
                                @endauth
                            </div>

                            {{-- Unilevel System --}}
                            <div class="border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                                    <span class="w-10 h-10 bg-gradient-to-br from-blue-600 to-cyan-600 text-white rounded-xl flex items-center justify-center">🎯</span>
                                    Unilevel System (ระบบชั้นเดียว)
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300 mb-4">
                                    โครงสร้างแบบไม่จำกัดขา เหมาะกับการสร้างเครือข่ายกว้าง
                                </p>
                                <div class="grid md:grid-cols-2 gap-4 mt-4">
                                    <div class="bg-cyan-50 dark:bg-cyan-900/20 p-4 rounded-lg">
                                        <h4 class="font-bold text-cyan-900 dark:text-cyan-100 mb-2">✨ ความยืดหยุ่น</h4>
                                        <ul class="text-sm space-y-1 text-gray-700 dark:text-gray-300">
                                            <li>• ไม่จำกัดจำนวนดาวน์ไลน์</li>
                                            <li>• กำหนด Level ได้ถึง 10+ ชั้น</li>
                                            <li>• % Commission ต่างกันแต่ละ Level</li>
                                        </ul>
                                    </div>
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                        <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-2">💰 ตัวอย่าง Commission</h4>
                                        <ul class="text-sm space-y-1 text-gray-700 dark:text-gray-300">
                                            <li>• Level 1: 10%</li>
                                            <li>• Level 2: 5%</li>
                                            <li>• Level 3-5: 3%</li>
                                        </ul>
                                    </div>
                                </div>
                                @auth
                                <a href="{{ route('user.mlm.genealogy') }}?type=unilevel" class="inline-block mt-4 px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                                    ดู Unilevel Tree →
                                </a>
                                @endauth
                            </div>

                            {{-- Rank & Bonus System --}}
                            <div class="border-2 border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                                    <span class="w-10 h-10 bg-gradient-to-br from-yellow-600 to-amber-600 text-white rounded-xl flex items-center justify-center">👑</span>
                                    Rank & Bonus System (ระบบยศและโบนัส)
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300 mb-4">
                                    ระบบยศที่กระตุ้นให้สมาชิกทำงานและเติบโตไปพร้อมกัน
                                </p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                                    <div class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 p-3 rounded-lg text-center border-2 border-gray-300 dark:border-gray-600">
                                        <div class="text-2xl mb-1">🥉</div>
                                        <div class="font-bold text-sm">Bronze</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-600 dark:to-gray-700 p-3 rounded-lg text-center border-2 border-gray-400 dark:border-gray-500">
                                        <div class="text-2xl mb-1">🥈</div>
                                        <div class="font-bold text-sm">Silver</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-yellow-200 to-yellow-400 dark:from-yellow-700 dark:to-yellow-800 p-3 rounded-lg text-center border-2 border-yellow-500 dark:border-yellow-600">
                                        <div class="text-2xl mb-1">🥇</div>
                                        <div class="font-bold text-sm">Gold</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-purple-300 to-purple-500 dark:from-purple-700 dark:to-purple-900 p-3 rounded-lg text-center border-2 border-purple-600 dark:border-purple-800">
                                        <div class="text-2xl mb-1">💎</div>
                                        <div class="font-bold text-sm">Diamond</div>
                                    </div>
                                </div>
                                @auth
                                <a href="{{ route('user.ranks.dashboard') }}" class="inline-block mt-4 px-6 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-semibold transition-colors">
                                    ดูความคืบหน้ายศ →
                                </a>
                                @endauth
                            </div>

                            @auth
                            <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white p-6 rounded-2xl shadow-xl">
                                <h4 class="text-xl font-bold mb-3">🚀 เริ่มต้นสร้างทีม MLM ของคุณวันนี้!</h4>
                                <p class="mb-4 opacity-90">
                                    ใช้ลิงก์แนะนำของคุณเพื่อเชิญเพื่อนร่วมทีมและรับคอมมิชชั่น
                                </p>
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('user.mlm.dashboard') }}" class="px-6 py-2 bg-white text-purple-600 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                                        MLM Dashboard
                                    </a>
                                    <a href="{{ route('user.mlm.referral') }}" class="px-6 py-2 bg-purple-800 hover:bg-purple-900 text-white rounded-lg font-semibold transition-colors">
                                        ลิงก์แนะนำ
                                    </a>
                                    <a href="{{ route('user.mlm.team') }}" class="px-6 py-2 bg-pink-700 hover:bg-pink-800 text-white rounded-lg font-semibold transition-colors">
                                        ดูทีม
                                    </a>
                                </div>
                            </div>
                            @endauth
                        </div>
                    </div>

                    {{-- AI & Bot System --}}
                    <div x-show="currentCategory === 'ai-bot' || currentCategory === 'ai-chatbot' || currentCategory === 'ai-creation' || currentCategory === 'ai-line'" style="display: none;">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 bg-clip-text text-transparent mb-6">
                            🤖 ระบบ AI & Bot Automation
                        </h1>

                        <div class="prose prose-lg dark:prose-invert max-w-none space-y-8">
                            <div class="bg-gradient-to-br from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 border-l-4 border-violet-600 p-6 rounded-r-xl">
                                <p class="text-gray-700 dark:text-gray-300 mb-0">
                                    ระบบ AI ที่ครบครัน ตั้งแต่ Chatbot, การสร้างภาพ/วิดีโอ, ไปจนถึงการเชื่อมต่อ LINE OA
                                </p>
                            </div>

                            {{-- AI Chatbot --}}
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="bg-white dark:bg-gray-800 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <span>🧠</span> AI Chatbot
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                                        ระบบ chatbot อัจฉริยะที่ตอบคำถามลูกค้าอัตโนมัติ 24/7
                                    </p>
                                    <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                        <li>✅ รองรับหลายภาษา (TH/EN)</li>
                                        <li>✅ เรียนรู้จากการสนทนา</li>
                                        <li>✅ เชื่อมต่อ Knowledge Base</li>
                                        <li>✅ Analytics & Reports</li>
                                    </ul>
                                </div>

                                <div class="bg-white dark:bg-gray-800 border-2 border-purple-200 dark:border-purple-800 rounded-xl p-6">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <span>🎨</span> AI Image/Video Generation
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                                        สร้างภาพและวิดีโอด้วย AI ในไม่กี่วินาที
                                    </p>
                                    <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                        <li>🎨 Text to Image</li>
                                        <li>🎬 Text to Video</li>
                                        <li>🖼️ Image Enhancement</li>
                                        <li>✂️ Background Removal</li>
                                    </ul>
                                    @auth
                                    <a href="{{ route('user.ai-gen.index') }}" class="inline-block mt-3 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-semibold transition-colors">
                                        ทดลองใช้ AI Gen →
                                    </a>
                                    @endauth
                                </div>
                            </div>

                            {{-- LINE AI Bot --}}
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-500 dark:border-green-700 rounded-xl p-6">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                                    <span class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-500 text-white rounded-xl flex items-center justify-center">💬</span>
                                    LINE OA AI Bot Integration
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300 mb-4">
                                    เชื่อมต่อ AI Chatbot กับ LINE Official Account ของคุณ
                                </p>
                                <div class="grid md:grid-cols-3 gap-4">
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg">
                                        <div class="text-2xl mb-2">📱</div>
                                        <h4 class="font-bold text-sm mb-1">Auto Reply</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">ตอบข้อความอัตโนมัติ</p>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg">
                                        <div class="text-2xl mb-2">🎯</div>
                                        <h4 class="font-bold text-sm mb-1">Broadcast</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">ส่งข้อความหมู่</p>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg">
                                        <div class="text-2xl mb-2">📊</div>
                                        <h4 class="font-bold text-sm mb-1">Analytics</h4>
                                        <p class="text-xs text-gray-600 dark:text-gray-400">วิเคราะห์การใช้งาน</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- E-Commerce & POS --}}
                    <div x-show="currentCategory === 'ecommerce'" style="display: none;">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 bg-clip-text text-transparent mb-6">
                            🛒 ระบบ E-Commerce & POS
                        </h1>

                        <div class="prose prose-lg dark:prose-invert max-w-none space-y-8">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="bg-white dark:bg-gray-800 border-2 border-orange-200 dark:border-orange-800 rounded-xl p-6">
                                    <h3 class="text-xl font-bold mb-4">🏪 ระบบร้านค้าออนไลน์</h3>
                                    <ul class="space-y-2 text-sm">
                                        <li>✅ จัดการสินค้าไม่จำกัด</li>
                                        <li>✅ หมวดหมู่และ Tags</li>
                                        <li>✅ Variants (สี, ไซส์)</li>
                                        <li>✅ Stock Management</li>
                                        <li>✅ โปรโมชั่น & คูปอง</li>
                                        <li>✅ รีวิวและเรตติ้ง</li>
                                    </ul>
                                </div>

                                <div class="bg-white dark:bg-gray-800 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6">
                                    <h3 class="text-xl font-bold mb-4">💳 ระบบ POS</h3>
                                    <ul class="space-y-2 text-sm">
                                        <li>✅ ขายหน้าร้านแบบ Real-time</li>
                                        <li>✅ รองรับ Barcode Scanner</li>
                                        <li>✅ หลายช่องทางชำระเงิน</li>
                                        <li>✅ พิมพ์ใบเสร็จอัตโนมัติ</li>
                                        <li>✅ รายงานยอดขายรายวัน</li>
                                        <li>✅ จัดการพนักงานขาย</li>
                                    </ul>
                                    @auth
                                    <a href="{{ route('seller.pos.terminal') }}" class="inline-block mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors">
                                        เปิด POS Terminal →
                                    </a>
                                    @endauth
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-600 p-6 rounded-r-xl">
                                <h4 class="font-bold text-green-900 dark:text-green-100 mb-2">💡 Tips: การเชื่อมต่อ MLM</h4>
                                <p class="text-gray-700 dark:text-gray-300 text-sm">
                                    ระบบ E-Commerce เชื่อมต่อกับ MLM โดยอัตโนมัติ ทุกการขายจะสร้างคอมมิชชั่นให้อัพไลน์ตามโครงสร้างทีม
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Hotel Management --}}
                    <div x-show="currentCategory === 'hotel'" style="display: none;">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-red-600 via-rose-600 to-pink-600 bg-clip-text text-transparent mb-6">
                            🏨 ระบบจัดการโรงแรม
                        </h1>

                        <div class="prose prose-lg dark:prose-invert max-w-none space-y-8">
                            <div class="grid md:grid-cols-3 gap-6">
                                <div class="bg-white dark:bg-gray-800 border-2 border-red-200 dark:border-red-800 rounded-xl p-6">
                                    <div class="text-3xl mb-3">🛏️</div>
                                    <h3 class="text-lg font-bold mb-2">Room Management</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">จัดการห้องพัก ประเภทห้อง และราคา</p>
                                </div>

                                <div class="bg-white dark:bg-gray-800 border-2 border-orange-200 dark:border-orange-800 rounded-xl p-6">
                                    <div class="text-3xl mb-3">📅</div>
                                    <h3 class="text-lg font-bold mb-2">Booking System</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">ระบบจองห้องพัก Check-in/Check-out</p>
                                </div>

                                <div class="bg-white dark:bg-gray-800 border-2 border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
                                    <div class="text-3xl mb-3">📊</div>
                                    <h3 class="text-lg font-bold mb-2">Channel Manager</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">เชื่อมต่อ OTA (Agoda, Booking.com)</p>
                                </div>
                            </div>

                            @auth
                            <div class="bg-gradient-to-r from-red-600 to-rose-600 text-white p-6 rounded-2xl">
                                <h4 class="text-xl font-bold mb-3">🎯 เริ่มต้นจัดการโรงแรม</h4>
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('hotels.bookings.index') }}" class="px-6 py-2 bg-white text-red-600 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                                        ดูการจอง
                                    </a>
                                    @if(auth()->user()->is_hotel_admin)
                                    <a href="{{ route('hotel-admin.dashboard') }}" class="px-6 py-2 bg-red-800 hover:bg-red-900 text-white rounded-lg font-semibold transition-colors">
                                        จัดการโรงแรม
                                    </a>
                                    @endif
                                </div>
                            </div>
                            @endauth
                        </div>
                    </div>

                    {{-- TPIX Blockchain & Crypto --}}
                    <div x-show="currentCategory === 'crypto'" style="display: none;">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-yellow-600 via-amber-600 to-orange-600 bg-clip-text text-transparent mb-6">
                            ₿ TPIX Blockchain & Cryptocurrency
                        </h1>

                        <div class="prose prose-lg dark:prose-invert max-w-none space-y-8">
                            <div class="bg-gradient-to-r from-yellow-50 via-amber-50 to-orange-50 dark:from-yellow-900/20 dark:via-amber-900/20 dark:to-orange-900/20 border-2 border-yellow-500 dark:border-yellow-700 rounded-xl p-6">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3">
                                    <span class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 text-white rounded-xl flex items-center justify-center text-2xl">🪙</span>
                                    TPIX Token - Native Blockchain
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300 mb-4">
                                    <strong>TPIX (ThaiPrompt Index)</strong> เป็น Native Blockchain Token ของระบบ ใช้สำหรับ:
                                </p>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg">
                                        <h4 class="font-bold mb-2">💰 Payment & Transactions</h4>
                                        <ul class="text-sm space-y-1 text-gray-700 dark:text-gray-300">
                                            <li>• ชำระค่าสินค้าและบริการ</li>
                                            <li>• โอนเงินระหว่างผู้ใช้</li>
                                            <li>• ค่าธรรมเนียมต่ำ (Gas Fee)</li>
                                        </ul>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800 p-4 rounded-lg">
                                        <h4 class="font-bold mb-2">🎁 Rewards & Staking</h4>
                                        <ul class="text-sm space-y-1 text-gray-700 dark:text-gray-300">
                                            <li>• MLM Commission ใน TPIX</li>
                                            <li>• Staking APY สูง</li>
                                            <li>• Governance Rights</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            {{-- TPIX Features --}}
                            <div class="grid md:grid-cols-3 gap-6">
                                <div class="bg-white dark:bg-gray-800 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6">
                                    <div class="text-4xl mb-3">💱</div>
                                    <h3 class="text-lg font-bold mb-2">TPIX DEX</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        Decentralized Exchange สำหรับแลกเปลี่ยน TPIX
                                    </p>
                                    @auth
                                    <a href="{{ route('user.dex.swap') }}" class="text-xs px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors inline-block">
                                        เปิด DEX →
                                    </a>
                                    @endauth
                                </div>

                                <div class="bg-white dark:bg-gray-800 border-2 border-purple-200 dark:border-purple-800 rounded-xl p-6">
                                    <div class="text-4xl mb-3">🔒</div>
                                    <h3 class="text-lg font-bold mb-2">TPIX Staking</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        Stake TPIX รับดอกเบี้ยสูง พร้อม Rewards
                                    </p>
                                    @auth
                                    <a href="{{ route('user.staking.index') }}" class="text-xs px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition-colors inline-block">
                                        Stake →
                                    </a>
                                    @endauth
                                </div>

                                <div class="bg-white dark:bg-gray-800 border-2 border-green-200 dark:border-green-800 rounded-xl p-6">
                                    <div class="text-4xl mb-3">💧</div>
                                    <h3 class="text-lg font-bold mb-2">Liquidity Pools</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        เพิ่ม Liquidity รับ LP Tokens และ Rewards
                                    </p>
                                    @auth
                                    <a href="{{ route('user.dex.pools') }}" class="text-xs px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors inline-block">
                                        Pools →
                                    </a>
                                    @endauth
                                </div>
                            </div>

                            {{-- Tokenomics --}}
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 border-2 border-gray-300 dark:border-gray-700 rounded-xl p-6">
                                <h3 class="text-xl font-bold mb-4">📊 TPIX Tokenomics</h3>
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="font-bold text-sm mb-3 text-gray-700 dark:text-gray-300">Token Information</h4>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Token Name:</span> <strong>ThaiPrompt Index</strong></div>
                                            <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Symbol:</span> <strong>TPIX</strong></div>
                                            <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Decimals:</span> <strong>18</strong></div>
                                            <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Chain:</span> <strong>TPIX Blockchain</strong></div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm mb-3 text-gray-700 dark:text-gray-300">Distribution</h4>
                                        <div class="space-y-2 text-sm">
                                            <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">MLM Rewards:</span> <strong>40%</strong></div>
                                            <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Staking:</span> <strong>20%</strong></div>
                                            <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Liquidity:</span> <strong>20%</strong></div>
                                            <div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Team & Dev:</span> <strong>20%</strong></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @auth
                            <div class="bg-gradient-to-r from-yellow-600 to-orange-600 text-white p-6 rounded-2xl">
                                <h4 class="text-xl font-bold mb-3">🚀 เริ่มต้นใช้งาน TPIX</h4>
                                <div class="flex flex-wrap gap-3">
                                    <a href="{{ route('user.tpix.wallet') }}" class="px-6 py-2 bg-white text-yellow-600 rounded-lg font-semibold hover:bg-gray-100 transition-colors">
                                        TPIX Wallet
                                    </a>
                                    <a href="{{ route('user.tokens.index') }}" class="px-6 py-2 bg-yellow-800 hover:bg-yellow-900 text-white rounded-lg font-semibold transition-colors">
                                        Token Marketplace
                                    </a>
                                </div>
                            </div>
                            @endauth
                        </div>
                    </div>

                    {{-- Wallet System --}}
                    <div x-show="currentCategory === 'wallet'" style="display: none;">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 bg-clip-text text-transparent mb-6">
                            💳 ระบบกระเป๋าเงิน
                        </h1>

                        <div class="prose prose-lg dark:prose-invert max-w-none space-y-8">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="bg-white dark:bg-gray-800 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6">
                                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                                        <span>💵</span> กระเป๋าเงิน THB
                                    </h3>
                                    <ul class="space-y-2 text-sm">
                                        <li>✅ ฝาก-ถอนเงินบาท</li>
                                        <li>✅ โอนระหว่างผู้ใช้</li>
                                        <li>✅ PromptPay QR Code</li>
                                        <li>✅ Bank Transfer</li>
                                        <li>✅ Credit/Debit Card</li>
                                    </ul>
                                    @auth
                                    <a href="{{ route('user.wallet.index') }}" class="inline-block mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors">
                                        เปิดกระเป๋า THB →
                                    </a>
                                    @endauth
                                </div>

                                <div class="bg-white dark:bg-gray-800 border-2 border-orange-200 dark:border-orange-800 rounded-xl p-6">
                                    <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                                        <span>₿</span> กระเป๋า Crypto
                                    </h3>
                                    <ul class="space-y-2 text-sm">
                                        <li>✅ รองรับ TPIX, BTC, ETH, USDT</li>
                                        <li>✅ แลกเปลี่ยน Crypto</li>
                                        <li>✅ Send/Receive</li>
                                        <li>✅ Trading History</li>
                                        <li>✅ Multi-Chain Support</li>
                                    </ul>
                                    @auth
                                    <a href="{{ route('user.crypto-wallet.index') }}" class="inline-block mt-3 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-semibold transition-colors">
                                        เปิดกระเป๋า Crypto →
                                    </a>
                                    @endauth
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-600 p-6 rounded-r-xl">
                                <h4 class="font-bold text-green-900 dark:text-green-100 mb-2">🔒 ความปลอดภัย</h4>
                                <p class="text-gray-700 dark:text-gray-300 text-sm">
                                    ระบบ Wallet มีการเข้ารหัสแบบ AES-256, Two-Factor Authentication, และ Transaction Verification
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Academy & Learning --}}
                    <div x-show="currentCategory === 'academy'" style="display: none;">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-teal-600 via-cyan-600 to-blue-600 bg-clip-text text-transparent mb-6">
                            🎓 Academy & Learning Center
                        </h1>

                        <div class="prose prose-lg dark:prose-invert max-w-none space-y-8">
                            <p class="text-gray-700 dark:text-gray-300">
                                ศูนย์การเรียนรู้ออนไลน์ สำหรับอบรมการใช้งานระบบ MLM, E-Commerce, และ Crypto
                            </p>

                            <div class="grid md:grid-cols-3 gap-6">
                                <div class="bg-white dark:bg-gray-800 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6">
                                    <div class="text-3xl mb-3">📚</div>
                                    <h3 class="text-lg font-bold mb-2">Courses</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">คอร์สเรียนออนไลน์</p>
                                </div>

                                <div class="bg-white dark:bg-gray-800 border-2 border-purple-200 dark:border-purple-800 rounded-xl p-6">
                                    <div class="text-3xl mb-3">🎬</div>
                                    <h3 class="text-lg font-bold mb-2">Video Tutorials</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">วิดีโออบรม</p>
                                </div>

                                <div class="bg-white dark:bg-gray-800 border-2 border-green-200 dark:border-green-800 rounded-xl p-6">
                                    <div class="text-3xl mb-3">📝</div>
                                    <h3 class="text-lg font-bold mb-2">Certificates</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">ใบรับรองออนไลน์</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- API & Integration --}}
                    <div x-show="currentCategory === 'api'" style="display: none;">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-slate-600 via-gray-600 to-zinc-600 bg-clip-text text-transparent mb-6">
                            🔌 API & Integration
                        </h1>

                        <div class="prose prose-lg dark:prose-invert max-w-none space-y-8">
                            <div class="bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-800 dark:to-slate-900 border-l-4 border-gray-600 p-6 rounded-r-xl">
                                <p class="text-gray-700 dark:text-gray-300">
                                    REST API สำหรับเชื่อมต่อกับระบบภายนอก รองรับ OAuth 2.0 และ API Keys
                                </p>
                            </div>

                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="bg-white dark:bg-gray-800 border-2 border-blue-200 dark:border-blue-800 rounded-xl p-6">
                                    <h3 class="text-lg font-bold mb-3">📡 Endpoints</h3>
                                    <ul class="text-sm space-y-2">
                                        <li><code class="text-xs bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded">/api/v1/user</code> - ข้อมูลผู้ใช้</li>
                                        <li><code class="text-xs bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded">/api/v1/mlm</code> - MLM Data</li>
                                        <li><code class="text-xs bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded">/api/v1/products</code> - สินค้า</li>
                                        <li><code class="text-xs bg-gray-100 dark:bg-gray-900 px-2 py-1 rounded">/api/v1/wallet</code> - กระเป๋าเงิน</li>
                                    </ul>
                                </div>

                                <div class="bg-white dark:bg-gray-800 border-2 border-green-200 dark:border-green-800 rounded-xl p-6">
                                    <h3 class="text-lg font-bold mb-3">🔐 Authentication</h3>
                                    <ul class="text-sm space-y-2">
                                        <li>✅ OAuth 2.0</li>
                                        <li>✅ API Keys</li>
                                        <li>✅ JWT Tokens</li>
                                        <li>✅ Rate Limiting</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FAQ & Troubleshooting --}}
                    <div x-show="currentCategory === 'faq'" style="display: none;">
                        <h1 class="text-4xl font-black bg-gradient-to-r from-pink-600 via-fuchsia-600 to-purple-600 bg-clip-text text-transparent mb-6">
                            ❓ FAQ & Troubleshooting
                        </h1>

                        <div class="prose prose-lg dark:prose-invert max-w-none space-y-6">
                            <div class="space-y-4">
                                {{-- FAQ Item --}}
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                                    <h3 class="text-lg font-bold mb-2 text-blue-600 dark:text-blue-400">🔹 ลืมรหัสผ่านทำอย่างไร?</h3>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        คลิก "ลืมรหัสผ่าน" ที่หน้า Login แล้วกรอกอีเมลที่ลงทะเบียนไว้ ระบบจะส่งลิงก์รีเซ็ตรหัสผ่านไปให้
                                    </p>
                                </div>

                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                                    <h3 class="text-lg font-bold mb-2 text-purple-600 dark:text-purple-400">🔹 คอมมิชชั่นจ่ายเมื่อไหร่?</h3>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        คอมมิชชั่นจะคำนวณทันทีที่ดาวน์ไลน์ทำรายการ และจ่ายอัตโนมัติทุกวันที่ 1 และ 15 ของเดือน
                                    </p>
                                </div>

                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                                    <h3 class="text-lg font-bold mb-2 text-green-600 dark:text-green-400">🔹 ถอนเงินขั้นต่ำเท่าไหร่?</h3>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        ถอนเงินบาท ขั้นต่ำ 100 บาท / ถอน TPIX ขั้นต่ำ 10 TPIX
                                    </p>
                                </div>

                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                                    <h3 class="text-lg font-bold mb-2 text-red-600 dark:text-red-400">🔹 ติดปัญหาการใช้งานติดต่อที่ไหน?</h3>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                        ติดต่อได้ 3 ช่องทาง:
                                    </p>
                                    <div class="flex flex-wrap gap-2">
                                        @auth
                                        <a href="{{ route('user.tickets.create') }}" class="text-xs px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors">
                                            🎫 Support Ticket
                                        </a>
                                        @endauth
                                        <a href="https://line.me/R/ti/p/@thaiprompt" target="_blank" class="text-xs px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors">
                                            💬 LINE OA
                                        </a>
                                        <a href="mailto:support@thaiprompt.com" class="text-xs px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-semibold transition-colors">
                                            📧 Email
                                        </a>
                                    </div>
                                </div>
                            </div>
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

    {{-- Language Dropdown Menu - Teleport to body เพื่อหลีกเลี่ยง overflow ของ sidebar --}}
    <template x-teleport="body">
        <div
            x-show="languageMenuOpen"
            @click.outside="languageMenuOpen = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
            :style="`top: ${langMenuPosition.top}px; left: ${langMenuPosition.left}px;`"
            class="fixed bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden min-w-[250px]"
            style="z-index: 9999; display: none;"
        >
            <div class="py-2">
                <template x-for="lang in availableLanguages" :key="lang.code">
                    <button
                        @click="switchLanguage(lang.code)"
                        class="w-full flex items-center gap-3 px-4 py-2 text-sm transition-colors"
                        :class="currentLanguage === lang.code ?
                            'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-semibold' :
                            'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'"
                    >
                        <span class="text-xl" x-text="lang.flag"></span>
                        <span x-text="lang.name"></span>
                        <svg x-show="currentLanguage === lang.code" class="w-4 h-4 ml-auto text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20" style="display: none;">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </template>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 bg-blue-50 dark:bg-blue-900/20 px-4 py-2">
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    🌐 ระบบแปลภาษาฟรีโดย Google Translate
                </p>
            </div>
        </div>
    </template>
</div>

@push('scripts')
{{-- Wiki Translation System - ใช้ Google Translate API (ฟรี, ไม่ต้อง API key) --}}
<script>
/**
 * ระบบแปลภาษาสำหรับ Wiki
 *
 * ใช้แนวทางเดียวกับหน้า Admin:
 * - Alpine.js สำหรับ state management
 * - Google Translate API (gtx client) สำหรับแปลภาษา
 * - ไม่ใช้ cookie, ไม่ reload page
 *
 * รองรับ: ไทย, English, 中文 (ZH-CN), 日本語 (JA)
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ Wiki Translation System initialized');
    console.log('📝 Using Google Translate API (fetch-based approach)');
    console.log('🌐 Supported languages: TH, EN, ZH-CN, JA');
});
</script>
@endpush

@endsection
