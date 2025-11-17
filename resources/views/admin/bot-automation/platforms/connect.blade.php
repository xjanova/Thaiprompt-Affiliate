@extends('layouts.admin-v3')

@section('title', 'เชื่อมต่อแพลตฟอร์ม')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Language Switcher Component -->
    <div class="absolute top-0 right-0 z-10 mt-4 mr-4">
        <div class="relative inline-block" x-data="{ open: false }">
            <button
                @click="open = !open"
                class="flex items-center gap-2 px-4 py-2 glass-fusion dark:bg-gray-800 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl shadow-sm hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span x-text="language === 'th' ? 'ไทย' : language === 'en' ? 'English' : language === 'zh' ? '中文' : '日本語'" class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300"></span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="open"
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-48 glass-fusion dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 dark:border-gray-700 py-2 z-50" border border-white/20 dark:border-white/10
                style="display: none;"
            >
                <button @click="language = 'th'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇹🇭</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">ไทย (Thai)</span>
                </button>
                <button @click="language = 'en'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇬🇧</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">English</span>
                </button>
                <button @click="language = 'zh'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇨🇳</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">中文 (Chinese)</span>
                </button>
                <button @click="language = 'ja'; open = false" class="w-full px-4 py-2 text-left hover:bg-gray-100/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 flex items-center gap-3">
                    <span class="text-xl">🇯🇵</span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300">日本語 (Japanese)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>เชื่อมต่อแพลตฟอร์มใหม่</h1>
            <a href="{{ route('admin.bot-automation.platforms.index') }}"
               class="flex items-center gap-2 px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-xl transition shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span data-translate>กลับไปที่แพลตฟอร์ม</span>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Platform Selection -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>เลือกแพลตฟอร์ม</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Facebook Messenger -->
                            <div class="relative group cursor-pointer border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-400 rounded-xl p-6 text-center transition-all hover:shadow-xl transform hover:-translate-y-1"
                                 onclick="selectPlatform('facebook')">
                                <i class="fab fa-facebook-messenger text-6xl text-blue-600 mb-4 group-hover:scale-110 transition-transform"></i>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" data-translate>Facebook Messenger</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-3" data-translate>เชื่อมต่อ Facebook Messenger ของคุณเพื่อทำการสนทนาอัตโนมัติ</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    <span data-translate>ยอดนิยม</span>
                                </span>
                            </div>

                            <!-- LINE -->
                            <div class="relative group cursor-pointer border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:border-green-500 dark:hover:border-green-400 rounded-xl p-6 text-center transition-all hover:shadow-xl transform hover:-translate-y-1"
                                 onclick="selectPlatform('line')">
                                <i class="fab fa-line text-6xl text-green-600 mb-4 group-hover:scale-110 transition-transform"></i>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" data-translate>LINE</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-3" data-translate>เชื่อมต่อบัญชี LINE ของคุณสำหรับระบบบอทอัตโนมัติ</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <span data-translate>แนะนำ</span>
                                </span>
                            </div>

                            <!-- Telegram -->
                            <div class="relative group cursor-pointer border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:border-cyan-500 dark:hover:border-cyan-400 rounded-xl p-6 text-center transition-all hover:shadow-xl transform hover:-translate-y-1"
                                 onclick="selectPlatform('telegram')">
                                <i class="fab fa-telegram text-6xl text-cyan-600 mb-4 group-hover:scale-110 transition-transform"></i>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" data-translate>Telegram</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-3" data-translate>เชื่อมต่อบอท Telegram ของคุณสำหรับส่งข้อความอัตโนมัติ</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200">
                                    <span data-translate>ตั้งค่าง่าย</span>
                                </span>
                            </div>

                            <!-- Slack -->
                            <div class="relative group cursor-pointer border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:border-purple-500 dark:hover:border-purple-400 rounded-xl p-6 text-center transition-all hover:shadow-xl transform hover:-translate-y-1"
                                 onclick="selectPlatform('slack')">
                                <i class="fab fa-slack text-6xl text-purple-600 mb-4 group-hover:scale-110 transition-transform"></i>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" data-translate>Slack</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-3" data-translate>เชื่อมต่อเวิร์คสเปซ Slack ของคุณสำหรับระบบอัตโนมัติในทีม</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                    <span data-translate>องค์กร</span>
                                </span>
                            </div>

                            <!-- WhatsApp Business -->
                            <div class="relative group cursor-pointer border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:border-green-500 dark:hover:border-green-400 rounded-xl p-6 text-center transition-all hover:shadow-xl transform hover:-translate-y-1"
                                 onclick="selectPlatform('whatsapp')">
                                <i class="fab fa-whatsapp text-6xl text-green-600 mb-4 group-hover:scale-110 transition-transform"></i>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" data-translate>WhatsApp Business</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-3" data-translate>เชื่อมต่อ WhatsApp Business API สำหรับบริการลูกค้า</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                    <span data-translate>ธุรกิจ</span>
                                </span>
                            </div>

                            <!-- Discord -->
                            <div class="relative group cursor-pointer border-2 border-gray-200 dark:border-gray-700 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-400 rounded-xl p-6 text-center transition-all hover:shadow-xl transform hover:-translate-y-1"
                                 onclick="selectPlatform('discord')">
                                <i class="fab fa-discord text-6xl text-indigo-600 mb-4 group-hover:scale-110 transition-transform"></i>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" data-translate>Discord</h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-3" data-translate>เชื่อมต่อเซิร์ฟเวอร์ Discord ของคุณสำหรับระบบอัตโนมัติชุมชน</p>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    <span data-translate>เกม</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Connection Form (Hidden by default) -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg hidden" border border-white/20 dark:border-white/10 id="connectionForm">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>รายละเอียดการเชื่อมต่อ</h2>
                    </div>
                    <div class="p-6">
                        <form action="{{ route('admin.bot-automation.platforms.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="platform_type" id="platform_type">

                            <!-- Platform Name -->
                            <div class="mb-6">
                                <label for="platform_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>ชื่อแพลตฟอร์ม</span> <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-transparent transition @error('platform_name') border-red-500 @enderror"
                                       id="platform_name"
                                       name="platform_name"
                                       value="{{ old('platform_name') }}"
                                       required>
                                @error('platform_name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- API Key -->
                            <div class="mb-6">
                                <label for="api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>คีย์ API</span> <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-transparent transition @error('api_key') border-red-500 @enderror"
                                       id="api_key"
                                       name="api_key"
                                       value="{{ old('api_key') }}"
                                       required>
                                @error('api_key')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400" data-translate>ป้อนคีย์ API ของแพลตฟอร์มของคุณ</p>
                            </div>

                            <!-- API Secret -->
                            <div class="mb-6">
                                <label for="api_secret" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>ซีเครท API</span>
                                </label>
                                <input type="password"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-transparent transition @error('api_secret') border-red-500 @enderror"
                                       id="api_secret"
                                       name="api_secret"
                                       value="{{ old('api_secret') }}">
                                @error('api_secret')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400" data-translate>ป้อนซีเครท API ของแพลตฟอร์มของคุณ (หากจำเป็น)</p>
                            </div>

                            <!-- Webhook URL -->
                            <div class="mb-6">
                                <label for="webhook_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>URL Webhook</span>
                                </label>
                                <div class="relative">
                                    <input type="url"
                                           class="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 dark:border-gray-600 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl @error('webhook_url') border-red-500 @enderror"
                                           id="webhook_url"
                                           name="webhook_url"
                                           value="{{ old('webhook_url', url('/webhook/bot')) }}"
                                           readonly>
                                    <button type="button"
                                            onclick="copyWebhookUrl()"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:text-gray-300 dark:text-gray-400 dark:hover:text-gray-300 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                                @error('webhook_url')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400" data-translate>ใช้ URL นี้ในการตั้งค่า webhook ของแพลตฟอร์ม</p>
                            </div>

                            <!-- Description -->
                            <div class="mb-6">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    <span data-translate>คำอธิบาย</span>
                                </label>
                                <textarea
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-gray-600 glass-fusion dark:bg-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-gray-500 focus:border-transparent transition @error('description') border-red-500 @enderror"
                                    id="description"
                                    name="description"
                                    rows="4">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Auto Connect Checkbox -->
                            <div class="mb-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                           class="w-5 h-5 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded focus:ring-gray-500 dark:focus:ring-gray-400 dark:bg-gray-700"
                                           id="auto_connect"
                                           name="auto_connect"
                                           value="1"
                                           {{ old('auto_connect') ? 'checked' : '' }}>
                                    <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-gray-300" data-translate>เชื่อมต่ออัตโนมัติหลังจากบันทึก</span>
                                </label>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-3">
                                <button type="submit"
                                        class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-gray-700 to-slate-700 hover:from-gray-800 hover:to-slate-800 text-white rounded-xl transition shadow-md hover:shadow-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    <span data-translate>เชื่อมต่อแพลตฟอร์ม</span>
                                </button>
                                <button type="button"
                                        onclick="cancelConnection()"
                                        class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 dark:text-gray-300 rounded-xl transition">
                                    <span data-translate>ยกเลิก</span>
                                </button>
                                <button type="button"
                                        onclick="testConnection()"
                                        class="flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-xl transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span data-translate>ทดสอบการเชื่อมต่อ</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Setup Guide -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>คู่มือการตั้งค่า</h2>
                    </div>
                    <div class="p-6">
                        <div id="setupGuide" class="text-gray-600 dark:text-gray-400 dark:text-gray-400">
                            <p data-translate>เลือกแพลตฟอร์มเพื่อดูคำแนะนำการตั้งค่า</p>
                        </div>
                    </div>
                </div>

                <!-- Help Resources -->
                <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg" border border-white/20 dark:border-white/10>
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 dark:border-gray-700">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>ต้องการความช่วยเหลือ?</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <a href="#" class="flex items-center gap-3 p-3 text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 rounded-xl transition">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <span class="text-sm font-medium" data-translate>เอกสาร</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 p-3 text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 rounded-xl transition">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-sm font-medium" data-translate>วิดีโอสอน</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 p-3 text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 rounded-xl transition">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm font-medium" data-translate>คำถามที่พบบ่อย</span>
                            </a>
                            <a href="#" class="flex items-center gap-3 p-3 text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700 rounded-xl transition">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <span class="text-sm font-medium" data-translate>ติดต่อฝ่ายสนับสนุน</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Google Translate Script -->
<script>
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            includedLanguages: 'th,en,zh-CN,ja',
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, 'google_translate_element');
    }
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<div id="google_translate_element" style="display: none;"></div>

<script>
// ฟังก์ชันแปลภาษา
document.addEventListener('alpine:init', () => {
    Alpine.data('translationController', () => ({
        language: 'th',
        init() {
            this.$watch('language', value => this.translatePage(value));
        },
        translatePage(targetLang) {
            const elements = document.querySelectorAll('[data-translate]');

            if (targetLang === 'th') {
                // รีเซ็ตกลับเป็นภาษาไทย
                location.reload();
                return;
            }

            elements.forEach(element => {
                const textToTranslate = element.textContent.trim();
                if (!textToTranslate) return;

                // เก็บข้อความต้นฉบับ
                if (!element.dataset.originalText) {
                    element.dataset.originalText = textToTranslate;
                }

                // แปลภาษา
                this.translateText(textToTranslate, 'th', targetLang)
                    .then(translatedText => {
                        element.textContent = translatedText;
                    })
                    .catch(error => {
                        console.error('Translation error:', error);
                    });
            });
        },
        async translateText(text, sourceLang, targetLang) {
            try {
                const response = await fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=${sourceLang}&tl=${targetLang}&dt=t&q=${encodeURIComponent(text)}`);
                const data = await response.json();
                return data[0][0][0];
            } catch (error) {
                console.error('Translation API error:', error);
                return text;
            }
        }
    }));
});

// Setup guides สำหรับแต่ละแพลตฟอร์ม
const setupGuides = {
    facebook: `
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3" data-translate>การตั้งค่า Facebook Messenger</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
            <li data-translate>สร้าง Facebook App</li>
            <li data-translate>เพิ่มผลิตภัณฑ์ Messenger</li>
            <li data-translate>สร้าง Page Access Token</li>
            <li data-translate>กำหนดค่า webhook</li>
            <li data-translate>สมัครรับเหตุการณ์หน้าเพจ</li>
        </ol>
    `,
    line: `
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3" data-translate>การตั้งค่า LINE</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
            <li data-translate>สร้างบัญชี LINE Developers</li>
            <li data-translate>สร้างช่อง Messaging API</li>
            <li data-translate>รับ Channel Access Token</li>
            <li data-translate>ตั้งค่า URL webhook</li>
            <li data-translate>เปิดใช้งาน webhook</li>
        </ol>
    `,
    telegram: `
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3" data-translate>การตั้งค่า Telegram</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
            <li data-translate>พูดคุยกับ @BotFather บน Telegram</li>
            <li data-translate>สร้างบอทใหม่</li>
            <li data-translate>รับ Bot Token</li>
            <li data-translate>ตั้งค่า URL webhook (ตัวเลือก)</li>
            <li data-translate>เริ่มรับข้อความ</li>
        </ol>
    `,
    slack: `
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3" data-translate>การตั้งค่า Slack</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
            <li data-translate>สร้าง Slack App</li>
            <li data-translate>เพิ่ม Bot Token Scopes</li>
            <li data-translate>ติดตั้งไปยัง Workspace</li>
            <li data-translate>รับ Bot User OAuth Token</li>
            <li data-translate>กำหนดค่า Event Subscriptions</li>
        </ol>
    `,
    whatsapp: `
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3" data-translate>การตั้งค่า WhatsApp Business</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
            <li data-translate>ลงทะเบียน WhatsApp Business API</li>
            <li data-translate>รับข้อมูลรับรอง API</li>
            <li data-translate>ยืนยันธุรกิจของคุณ</li>
            <li data-translate>กำหนดค่า webhook</li>
            <li data-translate>ทดสอบการเชื่อมต่อ</li>
        </ol>
    `,
    discord: `
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3" data-translate>การตั้งค่า Discord</h3>
        <ol class="list-decimal list-inside space-y-2 text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">
            <li data-translate>สร้าง Discord Application</li>
            <li data-translate>เพิ่มบอทไปยัง Application</li>
            <li data-translate>คัดลอก Bot Token</li>
            <li data-translate>เชิญบอทเข้าสู่เซิร์ฟเวอร์ของคุณ</li>
            <li data-translate>กำหนดค่าสิทธิ์</li>
        </ol>
    `
};

// ฟังก์ชันเลือกแพลตฟอร์ม
function selectPlatform(platform) {
    // ลบคลาส selected จากการ์ดทั้งหมด
    document.querySelectorAll('[onclick^="selectPlatform"]').forEach(card => {
        card.classList.remove('border-blue-500', 'border-green-500', 'border-cyan-500', 'border-purple-500', 'border-indigo-500');
        card.classList.add('border-gray-200 dark:border-gray-700', 'dark:border-gray-700');
    });

    // เพิ่มคลาส selected ให้การ์ดที่คลิก
    event.currentTarget.classList.remove('border-gray-200 dark:border-gray-700', 'dark:border-gray-700');
    const colorMap = {
        facebook: 'border-blue-500',
        line: 'border-green-500',
        telegram: 'border-cyan-500',
        slack: 'border-purple-500',
        whatsapp: 'border-green-500',
        discord: 'border-indigo-500'
    };
    event.currentTarget.classList.add(colorMap[platform]);

    // ตั้งค่า platform type
    document.getElementById('platform_type').value = platform;

    // แสดงฟอร์มการเชื่อมต่อ
    document.getElementById('connectionForm').classList.remove('hidden');

    // อัพเดทคู่มือการตั้งค่า
    document.getElementById('setupGuide').innerHTML = setupGuides[platform] || '<p class="text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>ไม่มีคู่มือการตั้งค่า</p>';

    // เลื่อนไปยังฟอร์ม
    document.getElementById('connectionForm').scrollIntoView({ behavior: 'smooth' });
}

// ฟังก์ชันยกเลิกการเชื่อมต่อ
function cancelConnection() {
    document.getElementById('connectionForm').classList.add('hidden');
    document.querySelectorAll('[onclick^="selectPlatform"]').forEach(card => {
        card.classList.remove('border-blue-500', 'border-green-500', 'border-cyan-500', 'border-purple-500', 'border-indigo-500');
        card.classList.add('border-gray-200 dark:border-gray-700', 'dark:border-gray-700');
    });
    document.getElementById('setupGuide').innerHTML = '<p class="text-gray-600 dark:text-gray-400 dark:text-gray-400" data-translate>เลือกแพลตฟอร์มเพื่อดูคำแนะนำการตั้งค่า</p>';
}

// ฟังก์ชันทดสอบการเชื่อมต่อ
function testConnection() {
    alert('กำลังทดสอบการเชื่อมต่อ... ฟีเจอร์นี้อยู่ระหว่างการพัฒนา');
}

// ฟังก์ชันคัดลอก webhook URL
function copyWebhookUrl() {
    const webhookInput = document.getElementById('webhook_url');
    webhookInput.select();
    document.execCommand('copy');

    // แสดงข้อความแจ้งเตือน
    alert('คัดลอก URL Webhook แล้ว!');
}
</script>
@endsection
