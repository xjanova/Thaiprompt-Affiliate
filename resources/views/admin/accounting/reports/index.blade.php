@extends('layouts.admin')

@section('title', 'รายงาน')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-emerald-50 via-teal-50 to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6">
    <!-- Language Switcher -->
    <div class="flex justify-end mb-6" x-data="{ open: false }">
        <div class="relative">
            <button @click="open = !open"
                    class="flex items-center gap-2 px-4 py-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl border-2 border-emerald-200 dark:border-emerald-700 hover:border-emerald-400 dark:hover:border-emerald-500 transition-all shadow-lg">
                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span class="font-medium text-gray-700 dark:text-gray-300" data-translate>ภาษา</span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
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
                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border-2 border-emerald-100 dark:border-emerald-900 overflow-hidden z-50">
                <button onclick="switchLanguage('th')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors border-b border-gray-100 dark:border-gray-700">
                    <span class="text-2xl">🇹🇭</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">ไทย</span>
                </button>
                <button onclick="switchLanguage('en')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors border-b border-gray-100 dark:border-gray-700">
                    <span class="text-2xl">🇬🇧</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">English</span>
                </button>
                <button onclick="switchLanguage('zh')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors border-b border-gray-100 dark:border-gray-700">
                    <span class="text-2xl">🇨🇳</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">中文</span>
                </button>
                <button onclick="switchLanguage('ja')" class="w-full px-4 py-3 text-left hover:bg-emerald-50 dark:hover:bg-emerald-900/20 flex items-center gap-3 transition-colors">
                    <span class="text-2xl">🇯🇵</span>
                    <span class="font-medium text-gray-700 dark:text-gray-300">日本語</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="mb-8 relative overflow-hidden bg-gradient-to-r from-emerald-500 via-teal-600 to-cyan-600 rounded-3xl shadow-2xl p-8">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-40 h-40 bg-teal-400/10 rounded-full blur-3xl"></div>

        <div class="relative">
            <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span data-translate>รายงาน</span>
            </h1>
            <p class="text-emerald-50 text-lg" data-translate>รายงานทางการเงินและบัญชี</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto space-y-8">
        <!-- Report Categories -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Profit-Loss Report -->
            <a href="{{ route('admin.accounting.reports.profit-loss') }}"
               class="relative group overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-4 bg-white/20 backdrop-blur-sm rounded-2xl group-hover:scale-110 transition-transform">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <svg class="w-6 h-6 text-white/80 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2" data-translate>รายงานกำไร-ขาดทุน</h3>
                    <p class="text-green-50" data-translate>แสดงรายได้และค่าใช้จ่าย</p>
                </div>
            </a>

            <!-- Balance Sheet Report -->
            <a href="{{ route('admin.accounting.reports.balance-sheet') }}"
               class="relative group overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-4 bg-white/20 backdrop-blur-sm rounded-2xl group-hover:scale-110 transition-transform">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                            </svg>
                        </div>
                        <svg class="w-6 h-6 text-white/80 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2" data-translate>งบดุล</h3>
                    <p class="text-blue-50" data-translate>สินทรัพย์ หนี้สิน และทุน</p>
                </div>
            </a>

            <!-- Cash Flow Report -->
            <a href="{{ route('admin.accounting.reports.cash-flow') }}"
               class="relative group overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-4 bg-white/20 backdrop-blur-sm rounded-2xl group-hover:scale-110 transition-transform">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </div>
                        <svg class="w-6 h-6 text-white/80 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2" data-translate>กระแสเงินสด</h3>
                    <p class="text-purple-50" data-translate>การไหลเข้า-ออกของเงินสด</p>
                </div>
            </a>

            <!-- Sales Report -->
            <a href="{{ route('admin.accounting.reports.sales') }}"
               class="relative group overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-4 bg-white/20 backdrop-blur-sm rounded-2xl group-hover:scale-110 transition-transform">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <svg class="w-6 h-6 text-white/80 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2" data-translate>รายงานยอดขาย</h3>
                    <p class="text-emerald-50" data-translate>สรุปยอดขายและลูกค้า</p>
                </div>
            </a>

            <!-- Expenses Report -->
            <a href="{{ route('admin.accounting.reports.expenses') }}"
               class="relative group overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-4 bg-white/20 backdrop-blur-sm rounded-2xl group-hover:scale-110 transition-transform">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                            </svg>
                        </div>
                        <svg class="w-6 h-6 text-white/80 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2" data-translate>รายงานค่าใช้จ่าย</h3>
                    <p class="text-orange-50" data-translate>สรุปค่าใช้จ่ายทั้งหมด</p>
                </div>
            </a>

            <!-- Tax Report -->
            <a href="{{ route('admin.accounting.reports.tax') }}"
               class="relative group overflow-hidden bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-500"></div>
                <div class="relative p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="p-4 bg-white/20 backdrop-blur-sm rounded-2xl group-hover:scale-110 transition-transform">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <svg class="w-6 h-6 text-white/80 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2" data-translate>รายงานภาษี</h3>
                    <p class="text-red-50" data-translate>ภาษีขาย ภาษีซื้อ และภงด.</p>
                </div>
            </a>
        </div>

        <!-- Quick Stats -->
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-2xl shadow-2xl p-8 border-2 border-emerald-100 dark:border-emerald-900">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <span data-translate>ภาพรวมเดือนนี้</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Revenue Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-900/20 dark:to-emerald-800/20 rounded-xl p-6 border-2 border-green-200 dark:border-green-800">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-xl">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div class="flex items-center gap-1 text-sm font-semibold {{ ($stats['revenue_change'] ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }}">
                            @if(($stats['revenue_change'] ?? 0) > 0)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            @endif
                            {{ number_format(abs($stats['revenue_change'] ?? 0), 1) }}%
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>รายได้</div>
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        ฿{{ number_format($stats['revenue'] ?? 0, 2) }}
                    </div>
                </div>

                <!-- Expenses Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-orange-50 to-red-100 dark:from-orange-900/20 dark:to-red-800/20 rounded-xl p-6 border-2 border-orange-200 dark:border-orange-800">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-3 bg-orange-100 dark:bg-orange-900/50 rounded-xl">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path>
                            </svg>
                        </div>
                        <div class="flex items-center gap-1 text-sm font-semibold {{ ($stats['expenses_change'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                            @if(($stats['expenses_change'] ?? 0) > 0)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                            @endif
                            {{ number_format(abs($stats['expenses_change'] ?? 0), 1) }}%
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>ค่าใช้จ่าย</div>
                    <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                        ฿{{ number_format($stats['expenses'] ?? 0, 2) }}
                    </div>
                </div>

                <!-- Profit Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-800/20 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-800">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-sm font-semibold text-gray-600 dark:text-gray-400">
                            {{ number_format($stats['profit_margin'] ?? 0, 1) }}%
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>กำไรสุทธิ</div>
                    <div class="text-3xl font-bold {{ ($stats['profit'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        ฿{{ number_format($stats['profit'] ?? 0, 2) }}
                    </div>
                </div>

                <!-- Receivables Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-red-50 to-pink-100 dark:from-red-900/20 dark:to-pink-800/20 rounded-xl p-6 border-2 border-red-200 dark:border-red-800">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-3 bg-red-100 dark:bg-red-900/50 rounded-xl">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-sm font-semibold text-red-600 dark:text-red-400">
                            {{ number_format($stats['overdue_invoices'] ?? 0) }}
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>ลูกหนี้คงค้าง</div>
                    <div class="text-3xl font-bold text-red-600 dark:text-red-400">
                        ฿{{ number_format($stats['receivables'] ?? 0, 2) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-2" data-translate>
                        รายการเกินกำหนด
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
