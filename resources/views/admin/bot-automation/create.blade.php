@extends('layouts.admin-v3')

@section('title', 'สร้างบอทอัตโนมัติ')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Header with Futuristic Design -->
    <div class="relative overflow-hidden bg-gradient-to-br from-cyan-500 via-purple-600 to-indigo-700 dark:from-cyan-900 dark:via-purple-900 dark:to-indigo-950 rounded-3xl shadow-2xl p-8">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.1) 35px, rgba(255,255,255,.1) 70px);"></div>
        </div>

        <!-- Language Switcher -->
        <div class="absolute top-0 right-0 z-10">
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open"
                        class="px-4 py-2 glass-fusion backdrop-blur-sm text-white rounded-xl hover:glass-fusion transition-all duration-200 border border-white/30 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    <span x-text="language === 'th' ? 'ไทย' : (language === 'en' ? 'English' : (language === 'zh' ? '中文' : (language === 'ja' ? '日本語' : language)))"></span>
                    <svg class="w-4 h-4" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open"
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 mt-2 w-48 glass-fusion dark:bg-slate-800 rounded-xl shadow-2xl border-2 border-white/20 dark:border-slate-700 overflow-hidden z-50" border border-white/20 dark:border-white/10 border border-white/20 dark:border-white/10>
                    <button @click="language = 'th'; open = false"
                            class="w-full px-4 py-3 text-left hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                            :class="{ 'bg-cyan-50 dark:bg-cyan-900/20': language === 'th' }">
                        <span class="text-2xl">🇹🇭</span>
                        <span class="font-semibold text-gray-900 dark:text-white">ไทย</span>
                    </button>
                    <button @click="language = 'en'; open = false"
                            class="w-full px-4 py-3 text-left hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                            :class="{ 'bg-cyan-50 dark:bg-cyan-900/20': language === 'en' }">
                        <span class="text-2xl">🇬🇧</span>
                        <span class="font-semibold text-gray-900 dark:text-white">English</span>
                    </button>
                    <button @click="language = 'zh'; open = false"
                            class="w-full px-4 py-3 text-left hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                            :class="{ 'bg-cyan-50 dark:bg-cyan-900/20': language === 'zh' }">
                        <span class="text-2xl">🇨🇳</span>
                        <span class="font-semibold text-gray-900 dark:text-white">中文</span>
                    </button>
                    <button @click="language = 'ja'; open = false"
                            class="w-full px-4 py-3 text-left hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                            :class="{ 'bg-cyan-50 dark:bg-cyan-900/20': language === 'ja' }">
                        <span class="text-2xl">🇯🇵</span>
                        <span class="font-semibold text-gray-900 dark:text-white">日本語</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-6">
                <div class="w-20 h-20 glass-fusion backdrop-blur-sm rounded-2xl flex items-center justify-center border-2 border-white/30 animate-pulse" border border-white/20 dark:border-white/10 border border-white/20 dark:border-white/10>
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-4xl font-bold text-white" data-translate>สร้างบอทอัตโนมัติใหม่</h2>
                    <p class="text-cyan-100 mt-2" data-translate>ตั้งค่าบอทอัจฉริยะเพื่อทำงานอัตโนมัติตลอด 24/7</p>
                </div>
            </div>
            <a href="{{ route('admin.bot-automation.index') }}"
               class="inline-flex items-center px-6 py-3 glass-fusion backdrop-blur-sm text-white font-semibold rounded-xl hover:glass-fusion transition-all duration-200 border border-white/30">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span data-translate>กลับ</span>
            </a>
        </div>
    </div>

    <form action="{{ route('admin.bot-automation.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Basic Information -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10 border border-white/20 dark:border-white/10>
            <div class="bg-gradient-to-r from-cyan-500 to-purple-600 dark:from-cyan-900 dark:to-purple-900 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span data-translate>ข้อมูลพื้นฐาน</span>
                </h3>
            </div>

            <div class="p-6 space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-2">
                        <span data-translate>ชื่อบอท</span> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all duration-200"
                           placeholder="เช่น บอทโพสต์ทุกวัน, บอทตอบลูกค้า">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-2">
                        <span data-translate>คำอธิบาย</span>
                    </label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all duration-200"
                              placeholder="อธิบายความสามารถและวัตถุประสงค์ของบอท">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Automation Type -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10 border border-white/20 dark:border-white/10>
            <div class="bg-gradient-to-r from-purple-500 to-pink-600 dark:from-purple-900 dark:to-pink-900 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span data-translate>ประเภทของบอท</span>
                </h3>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Scheduled Post -->
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="automation_type" value="scheduled_post" class="peer sr-only" {{ old('automation_type') === 'scheduled_post' ? 'checked' : '' }} required>
                        <div class="p-6 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-cyan-500 peer-checked:bg-gradient-to-br peer-checked:from-cyan-50 peer-checked:to-purple-50 dark:peer-checked:from-cyan-900/20 dark:peer-checked:to-purple-900/20 hover:border-cyan-300 transition-all duration-200 peer-checked:shadow-lg">
                            <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-purple-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2" data-translate>โพสต์ตามกำหนดเวลา</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>โพสต์เนื้อหาอัตโนมัติตามเวลาที่กำหนด</p>
                        </div>
                    </label>

                    <!-- Customer Support -->
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="automation_type" value="customer_support" class="peer sr-only" {{ old('automation_type') === 'customer_support' ? 'checked' : '' }}>
                        <div class="p-6 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-cyan-500 peer-checked:bg-gradient-to-br peer-checked:from-cyan-50 peer-checked:to-purple-50 dark:peer-checked:from-cyan-900/20 dark:peer-checked:to-purple-900/20 hover:border-cyan-300 transition-all duration-200 peer-checked:shadow-lg">
                            <div class="w-12 h-12 style="background: var(--arrow-x-success-gradient)" rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2" data-translate>ซัพพอร์ตลูกค้า</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>ตอบคำถามลูกค้าอัตโนมัติด้วย AI</p>
                        </div>
                    </label>

                    <!-- Sales Assistant -->
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="automation_type" value="sales_assistant" class="peer sr-only" {{ old('automation_type') === 'sales_assistant' ? 'checked' : '' }}>
                        <div class="p-6 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-cyan-500 peer-checked:bg-gradient-to-br peer-checked:from-cyan-50 peer-checked:to-purple-50 dark:peer-checked:from-cyan-900/20 dark:peer-checked:to-purple-900/20 hover:border-cyan-300 transition-all duration-200 peer-checked:shadow-lg">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2" data-translate>ผู้ช่วยขาย</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>ช่วยปิดการขายและติดตามลูกค้า</p>
                        </div>
                    </label>

                    <!-- Engagement -->
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="automation_type" value="engagement" class="peer sr-only" {{ old('automation_type') === 'engagement' ? 'checked' : '' }}>
                        <div class="p-6 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-cyan-500 peer-checked:bg-gradient-to-br peer-checked:from-cyan-50 peer-checked:to-purple-50 dark:peer-checked:from-cyan-900/20 dark:peer-checked:to-purple-900/20 hover:border-cyan-300 transition-all duration-200 peer-checked:shadow-lg">
                            <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2" data-translate>เพิ่มการมีส่วนร่วม</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>กระตุ้นการมีส่วนร่วมของผู้ใช้</p>
                        </div>
                    </label>

                    <!-- Analytics -->
                    <label class="relative cursor-pointer group">
                        <input type="radio" name="automation_type" value="analytics" class="peer sr-only" {{ old('automation_type') === 'analytics' ? 'checked' : '' }}>
                        <div class="p-6 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-cyan-500 peer-checked:bg-gradient-to-br peer-checked:from-cyan-50 peer-checked:to-purple-50 dark:peer-checked:from-cyan-900/20 dark:peer-checked:to-purple-900/20 hover:border-cyan-300 transition-all duration-200 peer-checked:shadow-lg">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <h4 class="font-bold text-gray-900 dark:text-white mb-2" data-translate>วิเคราะห์ข้อมูล</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>รายงานและวิเคราะห์ข้อมูลอัตโนมัติ</p>
                        </div>
                    </label>
                </div>
                @error('automation_type')
                    <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Trigger Configuration -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10 border border-white/20 dark:border-white/10>
            <div class="bg-gradient-to-r from-indigo-500 to-blue-600 dark:from-indigo-900 dark:to-blue-900 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span data-translate>ตั้งค่าทริกเกอร์</span>
                </h3>
            </div>

            <div class="p-6 space-y-6">
                <!-- Trigger Type -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                        <span data-translate>ประเภททริกเกอร์</span> <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="trigger_type" value="schedule" class="peer sr-only" {{ old('trigger_type') === 'schedule' ? 'checked' : '' }} required>
                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 hover:border-indigo-300 transition-all duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-gray-900 dark:text-white" data-translate>ตามกำหนดเวลา</h5>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>ทำงานตามตารางเวลา</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="trigger_type" value="event" class="peer sr-only" {{ old('trigger_type') === 'event' ? 'checked' : '' }}>
                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 hover:border-indigo-300 transition-all duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 style="background: var(--arrow-x-success-gradient)" rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-gray-900 dark:text-white" data-translate>เมื่อมีเหตุการณ์</h5>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>ทำงานเมื่อเกิดเหตุการณ์</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="trigger_type" value="webhook" class="peer sr-only" {{ old('trigger_type') === 'webhook' ? 'checked' : '' }}>
                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 hover:border-indigo-300 transition-all duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 style="background: var(--arrow-x-warning-gradient)" rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-gray-900 dark:text-white">Webhook</h5>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>รับข้อมูลจากภายนอก</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="trigger_type" value="manual" class="peer sr-only" {{ old('trigger_type') === 'manual' ? 'checked' : '' }}>
                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 hover:border-indigo-300 transition-all duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-gray-900 dark:text-white" data-translate>ด้วยตนเอง</h5>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>กดเริ่มด้วยตนเอง</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                    @error('trigger_type')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Schedule Type (shown when trigger_type is schedule) -->
                <div id="schedule-options" class="hidden">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-2">
                        <span data-translate>รูปแบบตารางเวลา</span>
                    </label>
                    <select name="schedule_type" class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200">
                        <option value="">เลือกรูปแบบ</option>
                        <option value="once" {{ old('schedule_type') === 'once' ? 'selected' : '' }}>ครั้งเดียว</option>
                        <option value="hourly" {{ old('schedule_type') === 'hourly' ? 'selected' : '' }}>ทุกชั่วโมง</option>
                        <option value="daily" {{ old('schedule_type') === 'daily' ? 'selected' : '' }}>ทุกวัน</option>
                        <option value="weekly" {{ old('schedule_type') === 'weekly' ? 'selected' : '' }}>ทุกสัปดาห์</option>
                        <option value="monthly" {{ old('schedule_type') === 'monthly' ? 'selected' : '' }}>ทุกเดือน</option>
                        <option value="cron" {{ old('schedule_type') === 'cron' ? 'selected' : '' }}>กำหนดเอง (Cron)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Content Configuration -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10 border border-white/20 dark:border-white/10>
            <div class="bg-gradient-to-r from-pink-500 to-rose-600 dark:from-pink-900 dark:to-rose-900 px-6 py-4">
                <h3 class="text-xl font-bold text-white flex items-center">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span data-translate>ตั้งค่าเนื้อหา</span>
                </h3>
            </div>

            <div class="p-6 space-y-6">
                <!-- Content Source -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-3">
                        <span data-translate>แหล่งเนื้อหา</span> <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="content_source" value="custom" class="peer sr-only" {{ old('content_source') === 'custom' ? 'checked' : '' }} required>
                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-pink-500 peer-checked:bg-pink-50 dark:peer-checked:bg-pink-900/20 hover:border-pink-300 transition-all duration-200 h-full">
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                    </div>
                                    <h5 class="font-bold text-gray-900 dark:text-white mb-1" data-translate>เนื้อหาที่กำหนดเอง</h5>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>เขียนเนื้อหาเอง</p>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="content_source" value="template" class="peer sr-only" {{ old('content_source') === 'template' ? 'selected' : '' }}>
                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-pink-500 peer-checked:bg-pink-50 dark:peer-checked:bg-pink-900/20 hover:border-pink-300 transition-all duration-200 h-full">
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                        </svg>
                                    </div>
                                    <h5 class="font-bold text-gray-900 dark:text-white mb-1" data-translate>จากเทมเพลต</h5>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>ใช้เทมเพลตที่มี</p>
                                </div>
                            </div>
                        </label>

                        <label class="relative cursor-pointer">
                            <input type="radio" name="content_source" value="ai_generated" class="peer sr-only" {{ old('content_source') === 'ai_generated' ? 'checked' : '' }}>
                            <div class="p-4 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 rounded-xl peer-checked:border-pink-500 peer-checked:bg-pink-50 dark:peer-checked:bg-pink-900/20 hover:border-pink-300 transition-all duration-200 h-full">
                                <div class="text-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-3 animate-pulse">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                        </svg>
                                    </div>
                                    <h5 class="font-bold text-gray-900 dark:text-white mb-1" data-translate>สร้างด้วย AI</h5>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400" data-translate>ให้ AI สร้างเนื้อหา</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    @error('content_source')
                        <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Custom Content -->
                <div id="custom-content" class="hidden">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-2">
                        <span data-translate>เนื้อหาที่กำหนดเอง</span>
                    </label>
                    <textarea name="custom_content" rows="6"
                              class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-all duration-200"
                              placeholder="พิมพ์เนื้อหาที่ต้องการให้บอทโพสต์">{{ old('custom_content') }}</textarea>
                </div>

                <!-- AI Prompt -->
                <div id="ai-prompt" class="hidden">
                    <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 mb-2">
                        <span data-translate>คำสั่งสำหรับ AI</span>
                    </label>
                    <textarea name="ai_generation_prompt" rows="4"
                              class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-all duration-200"
                              placeholder="เช่น สร้างเนื้อหาเกี่ยวกับ... หรือเขียนโพสต์ที่...">{{ old('ai_generation_prompt') }}</textarea>
                    <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 dark:text-gray-400 mt-2" data-translate>บอก AI ว่าต้องการให้สร้างเนื้อหาแบบไหน</p>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden" border border-white/20 dark:border-white/10 border border-white/20 dark:border-white/10>
            <div class="p-6">
                <label class="flex items-center cursor-pointer group">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}
                           class="w-6 h-6 text-cyan-600 border-2 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
                    <span class="ml-3 text-base font-bold text-gray-900 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors duration-200" data-translate>
                        เปิดใช้งานบอททันทีหลังสร้าง
                    </span>
                </label>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.bot-automation.index') }}"
               class="px-8 py-3 bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 dark:bg-slate-700 text-gray-700 dark:text-gray-300 dark:text-gray-300 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-slate-600 transition-all duration-200 shadow-md hover:shadow-lg">
                <span data-translate>ยกเลิก</span>
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-gradient-to-r from-cyan-600 to-purple-600 text-white font-bold rounded-xl hover:from-cyan-700 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span data-translate>สร้างบอทอัตโนมัติ</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Trigger type change handler
    const triggerTypeInputs = document.querySelectorAll('input[name="trigger_type"]');
    const scheduleOptions = document.getElementById('schedule-options');

    triggerTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value === 'schedule') {
                scheduleOptions.classList.remove('hidden');
            } else {
                scheduleOptions.classList.add('hidden');
            }
        });
    });

    // Check initial state
    const checkedTrigger = document.querySelector('input[name="trigger_type"]:checked');
    if (checkedTrigger && checkedTrigger.value === 'schedule') {
        scheduleOptions.classList.remove('hidden');
    }

    // Content source change handler
    const contentSourceInputs = document.querySelectorAll('input[name="content_source"]');
    const customContent = document.getElementById('custom-content');
    const aiPrompt = document.getElementById('ai-prompt');

    contentSourceInputs.forEach(input => {
        input.addEventListener('change', function() {
            customContent.classList.add('hidden');
            aiPrompt.classList.add('hidden');

            if (this.value === 'custom') {
                customContent.classList.remove('hidden');
            } else if (this.value === 'ai_generated') {
                aiPrompt.classList.remove('hidden');
            }
        });
    });

    // Check initial state
    const checkedContentSource = document.querySelector('input[name="content_source"]:checked');
    if (checkedContentSource) {
        if (checkedContentSource.value === 'custom') {
            customContent.classList.remove('hidden');
        } else if (checkedContentSource.value === 'ai_generated') {
            aiPrompt.classList.remove('hidden');
        }
    }
});
</script>
@endpush
@endsection
