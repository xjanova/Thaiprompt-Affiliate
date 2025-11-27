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
        currentCategory: 'story',
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

        // รายการหมวดหมู่ครบถ้วน 17 หมวด
        categories: [
            { id: 'story', icon: '🇹🇭', label: 'เรื่องราว ThaiPrompt', color: 'from-amber-500 to-orange-600', highlight: true },
            { id: 'overview', icon: '🚀', label: 'ภาพรวม & สรุปฟีเจอร์', color: 'from-blue-600 to-cyan-600' },
            { id: 'technology', icon: '⚙️', label: 'เทคโนโลยี & สถาปัตยกรรม', color: 'from-slate-600 to-gray-700' },
            { id: 'mlm-affiliate', icon: '💎', label: 'MLM & Affiliate', color: 'from-purple-600 to-pink-600',
              submenu: [
                { id: 'mlm-binary', label: '🌳 Binary System' },
                { id: 'mlm-unilevel', label: '🎯 Unilevel System' },
                { id: 'mlm-commission', label: '💰 Commission' },
                { id: 'mlm-rank', label: '👑 Rank & Bonus' },
                { id: 'mlm-genealogy', label: '📊 Genealogy' }
              ]
            },
            { id: 'ecommerce', icon: '🛒', label: 'E-Commerce', color: 'from-orange-600 to-amber-600' },
            { id: 'pos', icon: '🖥️', label: 'POS System', color: 'from-blue-600 to-indigo-600' },
            { id: 'ai-bot', icon: '🤖', label: 'AI & Bot System', color: 'from-violet-600 to-purple-600',
              submenu: [
                { id: 'ai-chatbot', label: '🧠 AI Chatbot' },
                { id: 'ai-creation', label: '🎨 AI Image/Video' },
                { id: 'ai-line', label: '💬 LINE AI Bot' }
              ]
            },
            { id: 'crypto', icon: '₿', label: 'TPIX & Blockchain', color: 'from-yellow-600 to-amber-600' },
            { id: 'payment', icon: '💳', label: 'Payment Gateway', color: 'from-green-600 to-emerald-600' },
            { id: 'hotel', icon: '🏨', label: 'Hotel Management', color: 'from-red-600 to-rose-600' },
            { id: 'hrm', icon: '👥', label: 'HR Management', color: 'from-cyan-600 to-blue-600' },
            { id: 'accounting', icon: '📊', label: 'ระบบบัญชี', color: 'from-emerald-600 to-teal-600' },
            { id: 'software', icon: '💻', label: 'Software Sales', color: 'from-indigo-600 to-violet-600' },
            { id: 'vendor', icon: '🏪', label: 'Vendor Management', color: 'from-pink-600 to-rose-600' },
            { id: 'academy', icon: '🎓', label: 'Academy & Learning', color: 'from-teal-600 to-cyan-600' },
            { id: 'security', icon: '🔒', label: 'Security & Compliance', color: 'from-red-700 to-rose-700' },
            { id: 'support', icon: '🎫', label: 'Support Center', color: 'from-blue-500 to-cyan-500' }
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
                            :class="[
                                currentCategory === category.id ?
                                    'bg-gradient-to-r ' + category.color + ' text-white shadow-lg scale-105' :
                                    (category.highlight ?
                                        'bg-gradient-to-r from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 text-amber-800 dark:text-amber-300 border-2 border-amber-300 dark:border-amber-700 hover:from-amber-200 hover:to-orange-200 dark:hover:from-amber-900/50 dark:hover:to-orange-900/50' :
                                        'bg-gray-100 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700')
                            ]"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition-all duration-300 transform hover:scale-105 hover:shadow-md relative"
                        >
                            {{-- Pulse animation for highlighted items --}}
                            <span x-show="category.highlight && currentCategory !== category.id" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-ping"></span>
                            <span x-show="category.highlight && currentCategory !== category.id" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></span>
                            <span class="text-xl" x-text="category.icon"></span>
                            <span class="flex-1 text-left text-sm" x-text="category.label"></span>
                            <span x-show="category.highlight" class="text-xs px-2 py-0.5 bg-red-500 text-white rounded-full font-bold">HOT</span>
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

                    {{-- 🇹🇭 เรื่องราว ThaiPrompt - หน้าหลัก --}}
                    <div x-show="currentCategory === 'story'">
                        @include('frontend.wiki.content.story')
                    </div>

                    {{-- ภาพรวม & สรุปฟีเจอร์ - ใช้ content file ที่ละเอียดกว่า --}}
                    <div x-show="currentCategory === 'overview'" style="display: none;">
                        @include('frontend.wiki.content.overview')
                    </div>

                    {{-- เทคโนโลยี & สถาปัตยกรรม --}}
                    <div x-show="currentCategory === 'technology'" style="display: none;">
                        @include('frontend.wiki.content.technology')
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

                    {{-- MLM & Affiliate System - ใช้ content file ที่ละเอียดกว่า --}}
                    <div x-show="currentCategory === 'mlm-affiliate' || currentCategory === 'mlm-binary' || currentCategory === 'mlm-unilevel' || currentCategory === 'mlm-commission' || currentCategory === 'mlm-rank' || currentCategory === 'mlm-genealogy'" style="display: none;">
                        @include('frontend.wiki.content.mlm-affiliate')
                    </div>

                    {{-- AI & Bot System - ใช้ content file ที่ละเอียดกว่า --}}
                    <div x-show="currentCategory === 'ai-bot' || currentCategory === 'ai-chatbot' || currentCategory === 'ai-creation' || currentCategory === 'ai-line'" style="display: none;">
                        @include('frontend.wiki.content.ai-bot')
                    </div>

                    {{-- E-Commerce - ใช้ content file ที่ละเอียดกว่า --}}
                    <div x-show="currentCategory === 'ecommerce'" style="display: none;">
                        @include('frontend.wiki.content.ecommerce')
                    </div>

                    {{-- POS System --}}
                    <div x-show="currentCategory === 'pos'" style="display: none;">
                        @include('frontend.wiki.content.pos')
                    </div>

                    {{-- Hotel Management - ใช้ content file ที่ละเอียดกว่า --}}
                    <div x-show="currentCategory === 'hotel'" style="display: none;">
                        @include('frontend.wiki.content.hotel')
                    </div>

                    {{-- TPIX Blockchain & Crypto - ใช้ content file ที่ละเอียดกว่า --}}
                    <div x-show="currentCategory === 'crypto'" style="display: none;">
                        @include('frontend.wiki.content.crypto')
                    </div>

                    {{-- Payment Gateway --}}
                    <div x-show="currentCategory === 'payment'" style="display: none;">
                        @include('frontend.wiki.content.payment')
                    </div>

                    {{-- HR Management --}}
                    <div x-show="currentCategory === 'hrm'" style="display: none;">
                        @include('frontend.wiki.content.hrm')
                    </div>

                    {{-- Accounting System --}}
                    <div x-show="currentCategory === 'accounting'" style="display: none;">
                        @include('frontend.wiki.content.accounting')
                    </div>

                    {{-- Software Sales --}}
                    <div x-show="currentCategory === 'software'" style="display: none;">
                        @include('frontend.wiki.content.software')
                    </div>

                    {{-- Vendor Management --}}
                    <div x-show="currentCategory === 'vendor'" style="display: none;">
                        @include('frontend.wiki.content.vendor')
                    </div>

                    {{-- Academy & Learning - ใช้ content file ที่ละเอียดกว่า --}}
                    <div x-show="currentCategory === 'academy'" style="display: none;">
                        @include('frontend.wiki.content.academy')
                    </div>

                    {{-- Security & Compliance --}}
                    <div x-show="currentCategory === 'security'" style="display: none;">
                        @include('frontend.wiki.content.security')
                    </div>

                    {{-- Support Center --}}
                    <div x-show="currentCategory === 'support'" style="display: none;">
                        @include('frontend.wiki.content.support')
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
