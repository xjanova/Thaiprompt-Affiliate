@extends('layouts.admin')

@section('title', 'เชื่อมต่อ FlowAccount')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Alpine.js Language Switcher -->
        <div x-data="{ open: false, currentLang: '🇹🇭 ไทย' }" class="flex justify-end mb-6">
            <div class="relative">
                <button @click="open = !open"
                        class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all border border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentLang"></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open"
                     @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                    <a href="#" @click.prevent="currentLang = '🇹🇭 ไทย'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">
                        🇹🇭 ไทย
                    </a>
                    <a href="#" @click.prevent="currentLang = '🇬🇧 English'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">
                        🇬🇧 English
                    </a>
                    <a href="#" @click.prevent="currentLang = '🇨🇳 中文'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">
                        🇨🇳 中文
                    </a>
                    <a href="#" @click.prevent="currentLang = '🇯🇵 日本語'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-700">
                        🇯🇵 日本語
                    </a>
                </div>
            </div>
        </div>

        <!-- Gradient Header with Back Button -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-cyan-600 to-teal-600 rounded-3xl shadow-2xl p-8 mb-8">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative">
                <div class="flex items-center mb-4">
                    <a href="{{ route('admin.accounting.flowaccount.index') }}"
                       class="mr-4 p-3 bg-white/20 backdrop-blur-sm rounded-xl hover:bg-white/30 transition-all group">
                        <svg class="w-6 h-6 text-white group-hover:transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2" data-translate>
                            เชื่อมต่อ FlowAccount
                        </h1>
                        <p class="text-blue-100" data-translate>
                            กรอกข้อมูล API Credentials ของคุณเพื่อเชื่อมต่อกับ FlowAccount
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 mb-8 border border-gray-200 dark:border-gray-700">
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-blue-500/10 to-cyan-500/10 rounded-full blur-3xl"></div>

            <form action="{{ route('admin.accounting.flowaccount.connect') }}" method="POST" class="relative space-y-6">
                @csrf

                <!-- Client ID Field -->
                <div>
                    <label for="client_id" class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <span data-translate>Client ID</span>
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input
                        type="text"
                        name="client_id"
                        id="client_id"
                        required
                        class="w-full px-5 py-4 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                        placeholder="กรอก Client ID ของคุณ"
                        data-translate
                        value="{{ old('client_id') }}"
                    >
                    @error('client_id')
                        <div class="mt-2 flex items-start space-x-2 text-red-600 dark:text-red-400">
                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm">{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Client Secret Field -->
                <div>
                    <label for="client_secret" class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <span data-translate>Client Secret</span>
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input
                        type="password"
                        name="client_secret"
                        id="client_secret"
                        required
                        class="w-full px-5 py-4 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all"
                        placeholder="กรอก Client Secret ของคุณ"
                        data-translate
                        value="{{ old('client_secret') }}"
                    >
                    @error('client_secret')
                        <div class="mt-2 flex items-start space-x-2 text-red-600 dark:text-red-400">
                            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm">{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.accounting.flowaccount.index') }}"
                       class="px-8 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-400 dark:hover:border-gray-500 transition-all"
                       data-translate>
                        ยกเลิก
                    </a>
                    <button
                        type="submit"
                        class="relative overflow-hidden group px-8 py-3 bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-2xl transition-all transform hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-700 via-cyan-700 to-teal-700 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <span data-translate>เชื่อมต่อ FlowAccount</span>
                        </div>
                    </button>
                </div>
            </form>
        </div>

        <!-- Instructions Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-600 rounded-2xl shadow-2xl p-8 mb-6">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white" data-translate>
                        วิธีการหา API Credentials
                    </h3>
                </div>

                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center mr-4 shadow-lg">
                            <span class="text-white font-bold text-sm">1</span>
                        </div>
                        <div class="flex-1 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <p class="text-white" data-translate>
                                เข้าสู่ระบบ <a href="https://developers.flowaccount.com" target="_blank" class="underline hover:text-blue-200 font-semibold">FlowAccount Developer Portal</a>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-green-400 to-emerald-500 rounded-lg flex items-center justify-center mr-4 shadow-lg">
                            <span class="text-white font-bold text-sm">2</span>
                        </div>
                        <div class="flex-1 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <p class="text-white" data-translate>
                                ไปที่เมนู "Applications" หรือ "My Apps"
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-lg flex items-center justify-center mr-4 shadow-lg">
                            <span class="text-white font-bold text-sm">3</span>
                        </div>
                        <div class="flex-1 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <p class="text-white" data-translate>
                                สร้าง Application ใหม่ หรือเลือก Application ที่มีอยู่
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-purple-400 to-pink-500 rounded-lg flex items-center justify-center mr-4 shadow-lg">
                            <span class="text-white font-bold text-sm">4</span>
                        </div>
                        <div class="flex-1 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <p class="text-white" data-translate>
                                คัดลอก <strong class="font-bold">Client ID</strong> และ <strong class="font-bold">Client Secret</strong>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-red-400 to-rose-500 rounded-lg flex items-center justify-center mr-4 shadow-lg">
                            <span class="text-white font-bold text-sm">5</span>
                        </div>
                        <div class="flex-1 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <p class="text-white mb-2" data-translate>
                                ตั้งค่า <strong class="font-bold">Redirect URI</strong> เป็น:
                            </p>
                            <code class="block bg-gray-900/50 text-green-300 px-4 py-3 rounded-lg text-sm font-mono break-all">{{ config('services.flowaccount.redirect_uri', url('/admin/accounting/flowaccount/callback')) }}</code>
                        </div>
                    </div>

                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-indigo-400 to-violet-500 rounded-lg flex items-center justify-center mr-4 shadow-lg">
                            <span class="text-white font-bold text-sm">6</span>
                        </div>
                        <div class="flex-1 bg-white/10 backdrop-blur-sm rounded-xl p-4">
                            <p class="text-white" data-translate>
                                นำ Client ID และ Client Secret มากรอกในฟอร์มด้านบน
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Notice Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-yellow-400 via-orange-400 to-red-500 rounded-2xl shadow-2xl p-6">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative flex items-start">
                <div class="flex-shrink-0 w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center mr-4">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-xl font-bold text-white mb-2" data-translate>
                        ความปลอดภัย
                    </h4>
                    <p class="text-white/95" data-translate>
                        ข้อมูล API Credentials ของคุณจะถูกเก็บอย่างปลอดภัยและเข้ารหัสในระบบ
                        เราจะไม่แชร์ข้อมูลนี้กับบุคคลที่สาม
                    </p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Alpine.js -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
