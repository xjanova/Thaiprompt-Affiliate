@extends('layouts.admin-v3')

@section('title', 'ใบแจ้งยอด')

@section('content')
<div class="space-y-6" x-data="{ language: 'th' }">
    <!-- Modern Header with Accounting Theme -->
    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-700 dark:from-emerald-900 dark:via-teal-900 dark:to-cyan-950 rounded-3xl shadow-2xl p-8">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,.1) 35px, rgba(255,255,255,.1) 70px);"></div>
        </div>

        <!-- Language Switcher Component -->
        <div class="absolute top-4 right-4 z-10">
            <div class="relative inline-block" x-data="{ open: false }">
                <button @click="open = !open"
                        class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-200 flex items-center gap-2 shadow-lg border border-white/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                    </svg>
                    <span x-text="{ th: 'ไทย 🇹🇭', en: 'English 🇬🇧', zh: '中文 🇨🇳', ja: '日本語 🇯🇵' }[language]"></span>
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                     class="absolute right-0 mt-2 w-48 rounded-xl shadow-2xl bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 overflow-hidden"
                     style="display: none;">
                    <button @click="language = 'th'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">ไทย 🇹🇭</button>
                    <button @click="language = 'en'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">English 🇬🇧</button>
                    <button @click="language = 'zh'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">中文 🇨🇳</button>
                    <button @click="language = 'ja'; open = false" class="w-full text-left px-4 py-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 text-gray-700 dark:text-gray-300 transition-colors">日本語 🇯🇵</button>
                </div>
            </div>
        </div>

        <div class="relative z-10">
            <!-- Back Button & Title -->
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.accounting.contacts.show', $contact) }}"
                       class="group p-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all duration-200 border border-white/20">
                        <svg class="w-6 h-6 text-white group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg" data-translate>ใบแจ้งยอด</h1>
                            <span class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl text-sm font-medium border border-white/20">
                                {{ $contact->name }}
                            </span>
                        </div>
                        <p class="text-white/90 text-lg font-medium" data-translate>Statement of Account</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-3">
                <button onclick="window.print()"
                        class="group px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate>พิมพ์</span>
                </button>
                <button onclick="exportPDF()"
                        class="group px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-700 hover:from-green-700 hover:to-emerald-800 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate>ส่งออก PDF</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Date Filter Card -->
    <div class="bg-gradient-to-br from-white to-gray-50 dark:from-slate-800 dark:to-slate-900 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white" data-translate>เลือกช่วงเวลา</h2>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>จากวันที่</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <input type="date" name="from_date" value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 transition-all">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2" data-translate>ถึงวันที่</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <input type="date" name="to_date" value="{{ request('to_date', now()->format('Y-m-d')) }}"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-emerald-500 dark:focus:ring-emerald-400 focus:border-emerald-500 transition-all">
                </div>
            </div>

            <div class="flex items-end">
                <button type="submit"
                        class="w-full px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                    <span data-translate>ค้นหา</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Statement Card -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border-2 border-gray-100 dark:border-slate-700 overflow-hidden">
        <!-- Statement Header -->
        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 p-8 border-b-2 border-emerald-200 dark:border-emerald-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left: Statement Info -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-3 bg-emerald-600 dark:bg-emerald-700 rounded-xl">
                            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white" data-translate>ใบแจ้งยอด</h2>
                    </div>
                    <div class="text-gray-700 dark:text-gray-300 space-y-2">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            <p class="font-medium">
                                <span data-translate>ระหว่างวันที่:</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold ml-2">
                                    {{ request('from_date', now()->startOfMonth()->format('d/m/Y')) }} - {{ request('to_date', now()->format('d/m/Y')) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right: Contact Info -->
                <div class="text-left md:text-right space-y-2">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $contact->name }}</h3>
                    @if($contact->company_name)
                    <p class="text-gray-700 dark:text-gray-300 font-medium">{{ $contact->company_name }}</p>
                    @endif
                    @if($contact->address)
                    <div class="text-gray-600 dark:text-gray-400 text-sm mt-3 space-y-1">
                        <div class="flex items-start justify-start md:justify-end gap-2">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            <div>
                                <p>{{ $contact->address }}</p>
                                <p>{{ $contact->district }} {{ $contact->province }} {{ $contact->postal_code }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    @if($contact->phone)
                    <div class="flex items-center justify-start md:justify-end gap-2 text-gray-600 dark:text-gray-400 text-sm">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                        </svg>
                        <p><span data-translate>โทร:</span> {{ $contact->phone }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>วันที่</th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>เลขที่เอกสาร</th>
                        <th class="px-6 py-4 text-left text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>รายละเอียด</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>เดบิต</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>เครดิต</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider" data-translate>คงเหลือ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Opening Balance -->
                    <tr class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 hover:from-emerald-100 hover:to-teal-100 dark:hover:from-emerald-900/30 dark:hover:to-teal-900/30 transition-colors">
                        <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white" colspan="3">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd"/>
                                </svg>
                                <span data-translate>ยอดยกมา</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-right text-gray-500 dark:text-gray-400">-</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-500 dark:text-gray-400">-</td>
                        <td class="px-6 py-4 text-sm text-right font-bold text-gray-900 dark:text-white">
                            ฿{{ number_format($openingBalance ?? 0, 2) }}
                        </td>
                    </tr>

                    @php
                        $runningBalance = $openingBalance ?? 0;
                    @endphp

                    @forelse($transactions ?? [] as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">
                            {{ $transaction->date->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-300">
                            {{ $transaction->document_number }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                            {{ $transaction->description }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">
                            @if($transaction->type === 'debit')
                                <span class="text-red-600 dark:text-red-400">฿{{ number_format($transaction->amount, 2) }}</span>
                            @else
                                <span class="text-gray-400 dark:text-gray-600">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold">
                            @if($transaction->type === 'credit')
                                <span class="text-green-600 dark:text-green-400">฿{{ number_format($transaction->amount, 2) }}</span>
                            @else
                                <span class="text-gray-400 dark:text-gray-600">-</span>
                            @endif
                        </td>
                        @php
                            if ($transaction->type === 'debit') {
                                $runningBalance += $transaction->amount;
                            } else {
                                $runningBalance -= $transaction->amount;
                            }
                        @endphp
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold {{ $runningBalance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            ฿{{ number_format($runningBalance, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"/>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 font-medium" data-translate>ไม่มีรายการในช่วงเวลาที่เลือก</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse

                    <!-- Closing Balance -->
                    <tr class="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 font-bold border-t-4 border-emerald-500 dark:border-emerald-600">
                        <td class="px-6 py-5 text-sm font-bold text-gray-900 dark:text-white" colspan="3">
                            <div class="flex items-center gap-2">
                                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                </svg>
                                <span data-translate>ยอดคงเหลือ</span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm text-right font-bold text-red-600 dark:text-red-400">
                            ฿{{ number_format($totalDebit ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-5 text-sm text-right font-bold text-green-600 dark:text-green-400">
                            ฿{{ number_format($totalCredit ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-5 text-lg text-right font-bold {{ $runningBalance > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            ฿{{ number_format($runningBalance, 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 px-6 py-4 border-t-2 border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <p><span data-translate>พิมพ์เมื่อ:</span> <span class="font-semibold text-gray-700 dark:text-gray-300">{{ now()->format('d/m/Y H:i:s') }}</span></p>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-500">Generated by TP-Affiliate System</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function exportPDF() {
    // Implementation for PDF export
    alert('ฟังก์ชันส่งออก PDF จะถูกพัฒนาในอนาคต');
}
</script>
@endpush
@endsection
