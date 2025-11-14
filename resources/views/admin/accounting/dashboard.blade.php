@extends('layouts.admin')

@section('title', 'ระบบบัญชี - Dashboard')

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

    <!-- Modern Header with Accounting Theme -->
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
                    <!-- Animated Accounting Icon -->
                    <div class="relative">
                        <div class="w-24 h-24 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-2xl flex items-center justify-center shadow-2xl transform hover:scale-110 transition-all duration-300 animate-pulse">
                            <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <!-- Status Light -->
                        <div class="absolute -top-1 -right-1 w-6 h-6 bg-green-400 rounded-full border-4 border-white dark:border-gray-900 animate-ping"></div>
                        <div class="absolute -top-1 -right-1 w-6 h-6 bg-green-400 rounded-full border-4 border-white dark:border-gray-900"></div>
                    </div>

                    <div>
                        <h2 class="text-4xl font-bold text-white flex items-center gap-3">
                            <span data-translate>ระบบบัญชี</span>
                        </h2>
                        <p class="text-emerald-100 mt-2 text-lg" data-translate>ระบบบัญชีครบวงจร เชื่อมต่อกับ FlowAccount</p>
                        <div class="flex gap-3 mt-3">
                            <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-xs font-bold rounded-lg border border-white/30" data-translate>
                                📊 แดชบอร์ด
                            </span>
                            <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-xs font-bold rounded-lg border border-white/30" data-translate>
                                💰 การเงิน
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Invoices -->
        <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium" data-translate>ใบแจ้งหนี้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['total_invoices']) }}</p>
                    <p class="text-sm text-orange-600 dark:text-orange-400 mt-1 flex items-center gap-1">
                        <span data-translate>รอชำระ:</span> {{ $stats['pending_invoices'] }}
                    </p>
                </div>
                <div class="text-5xl opacity-80 group-hover:scale-110 transition-transform">📄</div>
            </div>
        </div>

        <!-- Total Expenses -->
        <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium" data-translate>รายจ่ายทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['total_expenses']) }}</p>
                </div>
                <div class="text-5xl opacity-80 group-hover:scale-110 transition-transform">💰</div>
            </div>
        </div>

        <!-- Total Contacts -->
        <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium" data-translate>ผู้ติดต่อ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['total_contacts']) }}</p>
                </div>
                <div class="text-5xl opacity-80 group-hover:scale-110 transition-transform">👥</div>
            </div>
        </div>

        <!-- Total Products -->
        <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium" data-translate>สินค้า/บริการ</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($stats['total_products']) }}</p>
                </div>
                <div class="text-5xl opacity-80 group-hover:scale-110 transition-transform">📦</div>
            </div>
        </div>
    </div>

    <!-- Financial Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-700 dark:to-emerald-800 rounded-2xl shadow-xl p-6 text-white hover:shadow-2xl transition-shadow">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 20px, rgba(255,255,255,.1) 20px, rgba(255,255,255,.1) 40px);"></div>
            </div>
            <div class="relative">
                <p class="text-sm opacity-90 font-medium" data-translate>รายรับเดือนนี้</p>
                <p class="text-3xl font-bold mt-2">฿{{ number_format($financial['monthly_revenue'], 2) }}</p>
                <div class="mt-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs opacity-90" data-translate>รายได้</span>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-red-500 to-pink-600 dark:from-red-700 dark:to-pink-800 rounded-2xl shadow-xl p-6 text-white hover:shadow-2xl transition-shadow">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 20px, rgba(255,255,255,.1) 20px, rgba(255,255,255,.1) 40px);"></div>
            </div>
            <div class="relative">
                <p class="text-sm opacity-90 font-medium" data-translate>รายจ่ายเดือนนี้</p>
                <p class="text-3xl font-bold mt-2">฿{{ number_format($financial['monthly_expenses'], 2) }}</p>
                <div class="mt-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12 13a1 1 0 100 2h5a1 1 0 001-1V9a1 1 0 10-2 0v2.586l-4.293-4.293a1 1 0 00-1.414 0L8 9.586 3.707 5.293a1 1 0 00-1.414 1.414l5 5a1 1 0 001.414 0L11 9.414 14.586 13H12z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs opacity-90" data-translate>ค่าใช้จ่าย</span>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-purple-600 dark:from-blue-700 dark:to-purple-800 rounded-2xl shadow-xl p-6 text-white hover:shadow-2xl transition-shadow">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 20px, rgba(255,255,255,.1) 20px, rgba(255,255,255,.1) 40px);"></div>
            </div>
            <div class="relative">
                <p class="text-sm opacity-90 font-medium" data-translate>กำไรเดือนนี้</p>
                <p class="text-3xl font-bold mt-2">฿{{ number_format($financial['monthly_profit'], 2) }}</p>
                <div class="mt-2 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-xs opacity-90" data-translate>กำไรสุทธิ</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Outstanding -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6 hover:shadow-2xl transition-shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                </svg>
                <span data-translate>ลูกหนี้คงค้าง</span>
            </h3>
            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400">฿{{ number_format($financial['outstanding_receivables'], 2) }}</p>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6 hover:shadow-2xl transition-shadow">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                <span data-translate>เจ้าหนี้คงค้าง</span>
            </h3>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400">฿{{ number_format($financial['outstanding_payables'], 2) }}</p>
        </div>
    </div>

    <!-- Recent Invoices & Expenses -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Invoices -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                </svg>
                <span data-translate>ใบแจ้งหนี้ล่าสุด</span>
            </h3>
            @if($recentInvoices->count() > 0)
                <div class="space-y-3">
                    @foreach($recentInvoices as $invoice)
                    <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-md transition-all">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $invoice->document_number }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->contact->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($invoice->total_amount, 2) }}</p>
                            <span class="text-xs px-2 py-1 rounded-full font-medium
                                @if($invoice->status === 'paid') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                @elseif($invoice->status === 'overdue') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                @else bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 @endif">
                                {{ $invoice->status }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8" data-translate>ยังไม่มีใบแจ้งหนี้</p>
            @endif
            <a href="{{ route('admin.accounting.invoices.index') }}" class="block mt-4 text-center text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium transition-colors">
                <span data-translate>ดูทั้งหมด</span> →
            </a>
        </div>

        <!-- Recent Expenses -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                </svg>
                <span data-translate>รายจ่ายล่าสุด</span>
            </h3>
            @if($recentExpenses->count() > 0)
                <div class="space-y-3">
                    @foreach($recentExpenses as $expense)
                    <div class="flex items-center justify-between p-3 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-md transition-all">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $expense->document_number }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $expense->contact->name ?? 'ไม่ระบุ' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($expense->total_amount, 2) }}</p>
                            <span class="text-xs px-2 py-1 rounded-full font-medium
                                @if($expense->status === 'paid') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                @else bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 @endif">
                                {{ $expense->status }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 dark:text-gray-400 text-center py-8" data-translate>ยังไม่มีรายจ่าย</p>
            @endif
            <a href="{{ route('admin.accounting.expenses.index') }}" class="block mt-4 text-center text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-medium transition-colors">
                <span data-translate>ดูทั้งหมด</span> →
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
            </svg>
            <span data-translate>การดำเนินการด่วน</span>
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('admin.accounting.invoices.create') }}" class="group flex flex-col items-center justify-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200 border-2 border-green-100 dark:border-green-800">
                <i class="fas fa-plus-circle text-3xl text-green-600 dark:text-green-400 mb-2 group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium text-gray-900 dark:text-white text-center" data-translate>สร้างใบแจ้งหนี้</span>
            </a>

            <a href="{{ route('admin.accounting.expenses.create') }}" class="group flex flex-col items-center justify-center p-4 bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200 border-2 border-red-100 dark:border-red-800">
                <i class="fas fa-plus-circle text-3xl text-red-600 dark:text-red-400 mb-2 group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium text-gray-900 dark:text-white text-center" data-translate>บันทึกรายจ่าย</span>
            </a>

            <a href="{{ route('admin.accounting.contacts.create') }}" class="group flex flex-col items-center justify-center p-4 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200 border-2 border-blue-100 dark:border-blue-800">
                <i class="fas fa-user-plus text-3xl text-blue-600 dark:text-blue-400 mb-2 group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium text-gray-900 dark:text-white text-center" data-translate>เพิ่มผู้ติดต่อ</span>
            </a>

            <a href="{{ route('admin.accounting.reports.index') }}" class="group flex flex-col items-center justify-center p-4 bg-gradient-to-br from-purple-50 to-fuchsia-50 dark:from-purple-900/20 dark:to-fuchsia-900/20 rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200 border-2 border-purple-100 dark:border-purple-800">
                <i class="fas fa-chart-bar text-3xl text-purple-600 dark:text-purple-400 mb-2 group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium text-gray-900 dark:text-white text-center" data-translate>รายงาน</span>
            </a>
        </div>
    </div>
</div>
@endsection
