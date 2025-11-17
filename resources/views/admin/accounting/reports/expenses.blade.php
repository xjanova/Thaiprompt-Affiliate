@extends('layouts.admin-v3')

@section('title', 'รายงานค่าใช้จ่าย')

@section('content')
<div class="p-6">
    <!-- Header with Orange-Red Gradient -->
    <div class="mb-8 relative overflow-hidden bg-gradient-to-r from-orange-500 via-red-600 to-pink-600 rounded-3xl shadow-2xl p-8">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>

        <div class="relative flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.accounting.reports.index') }}"
                   class="p-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-2xl transition-all hover:scale-110 group">
                    <svg class="w-6 h-6 text-white group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                        </svg>
                        <span data-translate>รายงานค่าใช้จ่าย</span>
                    </h1>
                    <p class="text-orange-50 text-lg" data-translate>สรุปค่าใช้จ่ายและรายจ่าย</p>
                </div>
            </div>

            <!-- Language Switcher -->
            <div x-data="{ open: false }" class="relative">
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
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden z-50 border-2 border-orange-200 dark:border-orange-900"
                     style="display: none;">
                    <button class="w-full text-left px-4 py-3 hover:bg-orange-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇹🇭</span>
                        <span>ไทย</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-orange-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇬🇧</span>
                        <span>English</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-orange-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇨🇳</span>
                        <span>中文</span>
                    </button>
                    <button class="w-full text-left px-4 py-3 hover:bg-orange-50 dark:hover:bg-gray-700 transition flex items-center gap-2 text-gray-700 dark:text-gray-300">
                        <span class="text-xl">🇯🇵</span>
                        <span>日本語</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards with Gradients -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Total Expenses Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-orange-100 text-sm mb-1" data-translate>ค่าใช้จ่ายรวม</p>
                <p class="text-3xl font-bold">฿{{ number_format($stats['total_expenses'] ?? 0, 2) }}</p>
            </div>
        </div>

        <!-- Total Transactions Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
                <p class="text-blue-100 text-sm mb-1" data-translate>จำนวนรายการ</p>
                <p class="text-3xl font-bold">{{ number_format($stats['total_transactions'] ?? 0) }}</p>
            </div>
        </div>

        <!-- Average Expense Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-purple-100 text-sm mb-1" data-translate>ค่าเฉลี่ย/รายการ</p>
                <p class="text-3xl font-bold">฿{{ number_format($stats['average_expense'] ?? 0, 2) }}</p>
            </div>
        </div>

        <!-- Total Vendors Card -->
        <div class="relative overflow-hidden bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-3">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-red-100 text-sm mb-1" data-translate>จำนวนผู้ขาย</p>
                <p class="text-3xl font-bold">{{ number_format($stats['total_vendors'] ?? 0) }}</p>
            </div>
        </div>
    </div>

    <!-- Enhanced Filter Form -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>ตัวกรองข้อมูล</h3>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>ช่วงเวลา</label>
                <select name="period" class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition">
                    <option value="this_month" {{ request('period') === 'this_month' ? 'selected' : '' }} data-translate>เดือนนี้</option>
                    <option value="last_month" {{ request('period') === 'last_month' ? 'selected' : '' }} data-translate>เดือนที่แล้ว</option>
                    <option value="this_quarter" {{ request('period') === 'this_quarter' ? 'selected' : '' }} data-translate>ไตรมาสนี้</option>
                    <option value="this_year" {{ request('period') === 'this_year' ? 'selected' : '' }} data-translate>ปีนี้</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>จากวันที่</label>
                <input type="date" name="from_date" value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}"
                       class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>ถึงวันที่</label>
                <input type="date" name="to_date" value="{{ request('to_date', now()->format('Y-m-d')) }}"
                       class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>จัดกลุ่มตาม</label>
                <select name="group_by" class="w-full rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 transition">
                    <option value="category" {{ request('group_by') === 'category' ? 'selected' : '' }} data-translate>จัดกลุ่มตามประเภท</option>
                    <option value="vendor" {{ request('group_by') === 'vendor' ? 'selected' : '' }} data-translate>จัดกลุ่มตามผู้ขาย</option>
                    <option value="date" {{ request('group_by') === 'date' ? 'selected' : '' }} data-translate>จัดกลุ่มตามวันที่</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <span data-translate>ค้นหา</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Expenses by Category with Gradient Cards -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>ค่าใช้จ่ายแยกตามประเภท</h3>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($expensesByCategory ?? [] as $category)
            <div class="relative overflow-hidden bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl p-5 border-2 border-orange-200 dark:border-orange-800 hover:shadow-lg transition-all hover:-translate-y-1">
                <div class="text-center">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $category->name }}</div>
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400 mb-1">฿{{ number_format($category->amount, 2) }}</div>
                    <div class="inline-flex items-center gap-1 px-3 py-1 bg-orange-100 dark:bg-orange-900/50 rounded-full">
                        <svg class="w-3 h-3 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-xs font-semibold text-orange-600 dark:text-orange-400">{{ number_format($category->percentage, 1) }}%</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Enhanced Expenses Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="p-6 bg-gradient-to-r from-orange-500/10 to-red-500/10 dark:from-orange-900/20 dark:to-red-900/20 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-orange-500 to-red-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" data-translate>รายละเอียดค่าใช้จ่าย</h3>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>
                            @if(request('group_by') === 'vendor')
                                ผู้ขาย
                            @elseif(request('group_by') === 'date')
                                วันที่
                            @else
                                ประเภท
                            @endif
                            </span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>จำนวนรายการ</span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>ยอดรวม</span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>ภาษีมูลค่าเพิ่ม</span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>ยอดสุทธิ</span>
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <span data-translate>% จากยอดรวม</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($expensesData ?? [] as $item)
                    <tr class="hover:bg-orange-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-300">
                            {{ $item->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-300">
                            {{ number_format($item->count) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-300">
                            ฿{{ number_format($item->total_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-blue-600 dark:text-blue-400 font-medium">
                            ฿{{ number_format($item->tax ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-orange-600 dark:text-orange-400">
                            ฿{{ number_format($item->net_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-700 dark:text-gray-300">
                            {{ number_format($item->percentage, 2) }}%
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-full">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 font-medium" data-translate>ไม่พบข้อมูลค่าใช้จ่าย</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse

                    @if(isset($expensesData) && count($expensesData) > 0)
                    <tr class="bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold">
                        <td class="px-6 py-4 text-sm">
                            <span data-translate>รวมทั้งหมด</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            {{ number_format($expensesData->sum('count')) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            ฿{{ number_format($expensesData->sum('total_amount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            ฿{{ number_format($expensesData->sum('tax'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-xl">
                            ฿{{ number_format($expensesData->sum('net_amount'), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                            100.00%
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex flex-wrap gap-4 justify-end">
        <button onclick="window.print()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 hover:border-orange-500 dark:hover:border-orange-500 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all hover:shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            <span data-translate>พิมพ์</span>
        </button>
        <button onclick="exportExcel()"
                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl transition-all hover:shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span data-translate>ส่งออก Excel</span>
        </button>
    </div>
</div>

@push('scripts')
<script>
function exportExcel() {
    alert('ฟังก์ชันส่งออก Excel จะถูกพัฒนาในอนาคต');
}
</script>
@endpush
@endsection
