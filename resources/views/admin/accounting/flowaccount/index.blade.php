@extends('layouts.admin-v3')

@section('title', 'FlowAccount Integration')

@section('content')
<div class="p-6">
    <!-- Header with Blue-Teal Gradient -->
    <div class="mb-8 relative overflow-hidden bg-gradient-to-r from-blue-600 via-cyan-600 to-teal-600 rounded-3xl shadow-2xl p-8">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative">
            <div class="flex items-center gap-4 mb-3">
                <div class="p-4 bg-white/20 backdrop-blur-sm rounded-2xl">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white" data-translate>FlowAccount Integration</h1>
                    <p class="text-blue-50 text-lg" data-translate>เชื่อมต่อและซิงค์ข้อมูลกับ FlowAccount</p>
                </div>
            </div>

            <!-- Language Switcher -->
            <div x-data="{ open: false }" class="absolute top-4 right-4">
                <button @click="open = !open"
                        class="flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl text-white transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                    <span class="font-medium">ภาษา</span>
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
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden z-50 border-2 border-blue-200 dark:border-blue-900"
                     style="display: none;">
                    <button class="w-full text-left px-4 py-3 hover:bg-blue-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇹🇭</span>
                        <span>ไทย</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-blue-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇬🇧</span>
                        <span>English</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-blue-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇨🇳</span>
                        <span>中文</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-blue-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇯🇵</span>
                        <span>日本語</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Connection Status Card -->
    <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-8 border-2 {{ ($connected ?? false) ? 'border-green-500' : 'border-gray-300 dark:border-gray-600' }}">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-center gap-6">
                @if($connected ?? false)
                    <div class="relative">
                        <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-xl">
                            <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="absolute -top-2 -right-2">
                            <span class="relative flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-green-500"></span>
                            </span>
                        </div>
                    </div>
                @else
                    <div class="w-20 h-20 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 rounded-2xl flex items-center justify-center shadow-xl">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                @endif

                <div>
                    <h3 class="text-2xl font-bold {{ ($connected ?? false) ? 'text-green-600 dark:text-green-400' : 'text-gray-700 dark:text-gray-300' }}" data-translate>
                        @if($connected ?? false)
                            เชื่อมต่อแล้ว
                        @else
                            ยังไม่ได้เชื่อมต่อ
                        @endif
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mt-1" data-translate>
                        @if($connected ?? false)
                            <span class="font-medium">บัญชี:</span> {{ $accountEmail ?? '-' }}
                        @else
                            กรุณาเชื่อมต่อบัญชี FlowAccount ของคุณ
                        @endif
                    </p>
                </div>
            </div>

            <div>
                @if($connected ?? false)
                    <form action="{{ route('admin.accounting.flowaccount.disconnect') }}" method="POST" onsubmit="return confirm('ยืนยันการยกเลิกการเชื่อมต่อ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <span data-translate>ยกเลิกการเชื่อมต่อ</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('admin.accounting.flowaccount.connect.form') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-cyan-600 hover:from-blue-600 hover:to-cyan-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        <span data-translate>เชื่อมต่อ FlowAccount</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if($connected ?? false)
    <!-- Sync Stats Cards with Gradients -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Last Sync Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl inline-flex mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-indigo-100 text-sm mb-1" data-translate>ซิงค์ล่าสุด</p>
                <p class="text-xl font-bold">{{ $lastSync ?? 'ไม่เคย' }}</p>
            </div>
        </div>

        <!-- Invoices Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl inline-flex mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <p class="text-blue-100 text-sm mb-1" data-translate>ใบแจ้งหนี้</p>
                <p class="text-3xl font-bold">{{ number_format($syncStats['invoices'] ?? 0) }}</p>
            </div>
        </div>

        <!-- Expenses Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl inline-flex mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="text-orange-100 text-sm mb-1" data-translate>รายจ่าย</p>
                <p class="text-3xl font-bold">{{ number_format($syncStats['expenses'] ?? 0) }}</p>
            </div>
        </div>

        <!-- Contacts Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl inline-flex mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <p class="text-purple-100 text-sm mb-1" data-translate>ผู้ติดต่อ</p>
                <p class="text-3xl font-bold">{{ number_format($syncStats['contacts'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    <!-- Sync Actions -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 mb-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white" data-translate>การซิงค์ข้อมูล</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <form action="{{ route('admin.accounting.flowaccount.sync', 'invoices') }}" method="POST">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span data-translate>ซิงค์ใบแจ้งหนี้</span>
                </button>
            </form>

            <form action="{{ route('admin.accounting.flowaccount.sync', 'expenses') }}" method="POST">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span data-translate>ซิงค์รายจ่าย</span>
                </button>
            </form>

            <form action="{{ route('admin.accounting.flowaccount.sync', 'contacts') }}" method="POST">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span data-translate>ซิงค์ผู้ติดต่อ</span>
                </button>
            </form>
        </div>

        <div>
            <form action="{{ route('admin.accounting.flowaccount.sync', 'all') }}" method="POST">
                @csrf
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span data-translate>ซิงค์ทั้งหมด</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Sync Settings -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-6">
            <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white" data-translate>การตั้งค่า</h3>
        </div>

        <form action="{{ route('admin.accounting.flowaccount.settings') }}" method="POST" class="space-y-6">
            @csrf

            <div class="flex items-start">
                <div class="flex items-center h-5">
                    <input type="checkbox" name="auto_sync" value="1" {{ ($settings['auto_sync'] ?? false) ? 'checked' : '' }}
                           class="w-5 h-5 rounded border-2 border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700">
                </div>
                <div class="ml-3">
                    <label class="text-base font-medium text-gray-900 dark:text-gray-300" data-translate>เปิดใช้งานการซิงค์อัตโนมัติ</label>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" data-translate>ระบบจะซิงค์ข้อมูลอัตโนมัติตามช่วงเวลาที่กำหนด</p>
                </div>
            </div>

            <div>
                <label class="block text-base font-medium text-gray-900 dark:text-gray-300 mb-3" data-translate>
                    ช่วงเวลาการซิงค์อัตโนมัติ (นาที)
                </label>
                <select name="sync_interval" class="w-full md:w-80 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition px-4 py-3">
                    <option value="15" {{ ($settings['sync_interval'] ?? 60) == 15 ? 'selected' : '' }}>15 นาที</option>
                    <option value="30" {{ ($settings['sync_interval'] ?? 60) == 30 ? 'selected' : '' }}>30 นาที</option>
                    <option value="60" {{ ($settings['sync_interval'] ?? 60) == 60 ? 'selected' : '' }}>1 ชั่วโมง</option>
                    <option value="180" {{ ($settings['sync_interval'] ?? 60) == 180 ? 'selected' : '' }}>3 ชั่วโมง</option>
                    <option value="360" {{ ($settings['sync_interval'] ?? 60) == 360 ? 'selected' : '' }}>6 ชั่วโมง</option>
                </select>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    <span data-translate>บันทึกการตั้งค่า</span>
                </button>
            </div>
        </form>
    </div>
    @endif

    @if(!($connected ?? false))
    <!-- Setup Instructions -->
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl p-8 border-2 border-blue-200 dark:border-blue-800">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-blue-200/30 dark:bg-blue-800/30 rounded-full blur-3xl"></div>
        <div class="relative">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-300" data-translate>
                    วิธีการเชื่อมต่อ FlowAccount
                </h3>
            </div>

            <ol class="space-y-4">
                <li class="flex gap-4" data-translate>
                    <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">1</div>
                    <div class="flex-1 text-blue-900 dark:text-blue-300 pt-1">คลิกปุ่ม "เชื่อมต่อ FlowAccount" ด้านบน</div>
                </li>
                <li class="flex gap-4" data-translate>
                    <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">2</div>
                    <div class="flex-1 text-blue-900 dark:text-blue-300 pt-1">กรอก Client ID และ Client Secret จาก FlowAccount Developer Portal</div>
                </li>
                <li class="flex gap-4" data-translate>
                    <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">3</div>
                    <div class="flex-1 text-blue-900 dark:text-blue-300 pt-1">เข้าสู่ระบบ FlowAccount ของคุณเพื่ออนุญาตการเข้าถึง</div>
                </li>
                <li class="flex gap-4" data-translate>
                    <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">4</div>
                    <div class="flex-1 text-blue-900 dark:text-blue-300 pt-1">อนุญาตให้แอพพลิเคชันเข้าถึงข้อมูลบัญชีของคุณ</div>
                </li>
                <li class="flex gap-4" data-translate>
                    <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">5</div>
                    <div class="flex-1 text-blue-900 dark:text-blue-300 pt-1">รอการเชื่อมต่อเสร็จสมบูรณ์</div>
                </li>
                <li class="flex gap-4" data-translate>
                    <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center text-white font-bold shadow-lg">6</div>
                    <div class="flex-1 text-blue-900 dark:text-blue-300 pt-1">เริ่มซิงค์ข้อมูลระหว่างระบบ</div>
                </li>
            </ol>
        </div>
    </div>
    @endif
</div>
@endsection
