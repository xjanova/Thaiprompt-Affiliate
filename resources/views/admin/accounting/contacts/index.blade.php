@extends('layouts.admin')

@section('title', 'ผู้ติดต่อ')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl shadow-lg" role="alert">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-2 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow-lg" role="alert">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Modern Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 dark:from-emerald-900 dark:via-teal-900 dark:to-cyan-950 rounded-3xl shadow-2xl p-8">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.1) 35px, rgba(255,255,255,.1) 70px);"></div>
        </div>

        <div class="relative">
            <!-- Language Switcher -->
            <div class="absolute top-0 right-0 z-10">
                <div class="relative inline-block" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-200 border border-white/30 flex items-center gap-2">
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
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border-2 border-white/20 dark:border-slate-700 overflow-hidden z-50">
                        <button @click="language = 'th'; open = false"
                                class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                                :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': language === 'th' }">
                            <span class="text-2xl">🇹🇭</span>
                            <span class="font-semibold text-gray-900 dark:text-white">ไทย</span>
                        </button>
                        <button @click="language = 'en'; open = false"
                                class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                                :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': language === 'en' }">
                            <span class="text-2xl">🇬🇧</span>
                            <span class="font-semibold text-gray-900 dark:text-white">English</span>
                        </button>
                        <button @click="language = 'zh'; open = false"
                                class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                                :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': language === 'zh' }">
                            <span class="text-2xl">🇨🇳</span>
                            <span class="font-semibold text-gray-900 dark:text-white">中文</span>
                        </button>
                        <button @click="language = 'ja'; open = false"
                                class="w-full px-4 py-3 text-left hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-200 flex items-center gap-3"
                                :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': language === 'ja' }">
                            <span class="text-2xl">🇯🇵</span>
                            <span class="font-semibold text-gray-900 dark:text-white">日本語</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 w-full pt-12 sm:pt-0">
                <div class="flex items-center gap-6">
                    <!-- Contacts Icon -->
                    <div class="relative">
                        <div class="w-24 h-24 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl flex items-center justify-center shadow-2xl transform hover:scale-110 transition-all duration-300 animate-pulse">
                            <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <!-- Status Light -->
                        <div class="absolute -top-1 -right-1 w-6 h-6 bg-green-400 rounded-full border-4 border-white dark:border-gray-900 animate-ping"></div>
                        <div class="absolute -top-1 -right-1 w-6 h-6 bg-green-400 rounded-full border-4 border-white dark:border-gray-900"></div>
                    </div>

                    <div>
                        <h2 class="text-4xl font-bold text-white flex items-center gap-3">
                            <span data-translate>ผู้ติดต่อ</span>
                        </h2>
                        <p class="text-emerald-100 mt-2 text-lg" data-translate>จัดการลูกค้าและผู้ขาย</p>
                        <div class="flex gap-3 mt-3">
                            <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-xs font-bold rounded-lg border border-white/30" data-translate>
                                👥 ผู้ติดต่อทั้งหมด
                            </span>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->hasPermission('accounting.create_contacts') || auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.accounting.contacts.create') }}"
                   class="group flex items-center gap-2 bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white px-6 py-3 rounded-xl border-2 border-white/30 font-semibold transition-all duration-200 hover:scale-105 shadow-lg">
                    <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span data-translate>เพิ่มผู้ติดต่อ</span>
                </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-2" data-translate>ทั้งหมด</div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total'] ?? 0) }}</div>
            <div class="mt-2 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                </svg>
                <span data-translate>ผู้ติดต่อทั้งหมด</span>
            </div>
        </div>

        <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-blue-100 dark:border-blue-900 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-2" data-translate>ลูกค้า</div>
            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($stats['customers'] ?? 0) }}</div>
            <div class="mt-2 flex items-center gap-1 text-xs text-blue-600 dark:text-blue-400">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
                <span data-translate>ลูกค้าที่ใช้งาน</span>
            </div>
        </div>

        <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-orange-100 dark:border-orange-900 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-2" data-translate>ผู้ขาย</div>
            <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($stats['vendors'] ?? 0) }}</div>
            <div class="mt-2 flex items-center gap-1 text-xs text-orange-600 dark:text-orange-400">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                </svg>
                <span data-translate>ผู้ขายที่ใช้งาน</span>
            </div>
        </div>

        <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-green-100 dark:border-green-900 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-2" data-translate>ใช้งานอยู่</div>
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['active'] ?? 0) }}</div>
            <div class="mt-2 flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span data-translate>สถานะใช้งาน</span>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/>
            </svg>
            <span data-translate>ค้นหาและกรองข้อมูล</span>
        </h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="ค้นหาชื่อ, อีเมล, เบอร์โทร..."
                   data-translate-placeholder
                   class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400">

            <select name="type" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400">
                <option value="" data-translate>ประเภททั้งหมด</option>
                <option value="customer" {{ request('type') === 'customer' ? 'selected' : '' }} data-translate>ลูกค้า</option>
                <option value="vendor" {{ request('type') === 'vendor' ? 'selected' : '' }} data-translate>ผู้ขาย</option>
                <option value="both" {{ request('type') === 'both' ? 'selected' : '' }} data-translate>ทั้งสองอย่าง</option>
            </select>

            <select name="status" class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400">
                <option value="" data-translate>สถานะทั้งหมด</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }} data-translate>ใช้งาน</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }} data-translate>ไม่ใช้งาน</option>
            </select>

            <button type="submit" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white px-6 py-2.5 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span data-translate>ค้นหา</span>
            </button>
        </form>
    </div>

    <!-- Contacts Table -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider" data-translate>ชื่อ</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider" data-translate>ประเภท</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider" data-translate>อีเมล</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider" data-translate>โทรศัพท์</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider" data-translate>ยอดค้างชำระ</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider" data-translate>สถานะ</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider" data-translate>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($contacts ?? [] as $contact)
                    <tr class="hover:bg-gradient-to-r hover:from-emerald-50 hover:to-teal-50 dark:hover:from-emerald-900/10 dark:hover:to-teal-900/10 transition-all duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="{{ route('admin.accounting.contacts.show', $contact) }}"
                               class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-300 font-semibold hover:underline">
                                {{ $contact->name }}
                            </a>
                            @if($contact->company_name)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $contact->company_name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($contact->is_customer && $contact->is_vendor)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                    <span data-translate>ทั้งสองอย่าง</span>
                                </span>
                            @elseif($contact->is_customer)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                    <span data-translate>ลูกค้า</span>
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"/>
                                    </svg>
                                    <span data-translate>ผู้ขาย</span>
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $contact->email ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $contact->phone ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            @if($contact->balance > 0)
                                <span class="text-red-600 dark:text-red-400 font-bold">฿{{ number_format($contact->balance, 2) }}</span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">฿0.00</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                {{ $contact->is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}">
                                <span data-translate>{{ $contact->is_active ? 'ใช้งาน' : 'ไม่ใช้งาน' }}</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex items-center justify-center gap-3">
                                <a href="{{ route('admin.accounting.contacts.show', $contact) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 transition-colors" title="ดูรายละเอียด">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @if(auth()->user()->hasPermission('accounting.edit_contacts') || auth()->user()->isSuperAdmin())
                                <a href="{{ route('admin.accounting.contacts.edit', $contact) }}"
                                   class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-300 transition-colors" title="แก้ไข">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermission('accounting.delete_contacts') || auth()->user()->isSuperAdmin())
                                <form action="{{ route('admin.accounting.contacts.destroy', $contact) }}"
                                      method="POST" class="inline"
                                      onsubmit="return confirm('ยืนยันการลบผู้ติดต่อนี้?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors" title="ลบ">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-500 dark:text-gray-400">
                                <svg class="w-16 h-16 mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="text-lg font-medium" data-translate>ไม่พบข้อมูลผู้ติดต่อ</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if(isset($contacts) && $contacts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750">
            {{ $contacts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
