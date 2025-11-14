@extends('layouts.admin')

@section('title', 'รีวิวตลาดบอท')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8 px-4 sm:px-6 lg:px-8">
    {{-- Gradient Header สีส้ม/เหลือง --}}
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 via-amber-600 to-yellow-700 dark:from-orange-900 dark:via-amber-900 dark:to-yellow-950 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>

        <div class="relative flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center border-2 border-white/30 animate-pulse">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
                <div>
                    <h1 data-translate class="text-4xl font-bold text-white mb-2">รีวิวตลาดบอท</h1>
                    <p data-translate class="text-orange-100 text-lg">จัดการรีวิวและคะแนนจากผู้ใช้งาน</p>
                </div>
            </div>

            
            <div class="flex items-center gap-3">
                {{-- Language Switcher --}}
                <div class="relative inline-block" x-data="{ open: false }">
                    <button @click="open = !open" class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-xl hover:bg-white/30 transition-all duration-200 border border-white/30 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                        <span data-translate>ภาษา</span>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden z-50">
                        <a href="/lang/th" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇹🇭</span> <span data-translate>ไทย</span>
                        </a>
                        <a href="/lang/en" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇬🇧</span> English
                        </a>
                        <a href="/lang/zh" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇨🇳</span> 中文
                        </a>
                        <a href="/lang/ja" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <span class="mr-2">🇯🇵</span> 日本語
                        </a>
                    </div>
                </div>
<a href="{{ route('admin.bot-automation.marketplace.index') }}"
               class="inline-flex items-center px-6 py-3 bg-white hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 text-orange-600 dark:text-orange-400 font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปตลาด
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- จำนวนรีวิวทั้งหมด --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-orange-100 dark:border-orange-900/30 hover:border-orange-300 dark:hover:border-orange-700 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p data-translate class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">รีวิวทั้งหมด</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalReviews ?? '0' }}</h3>
                    <div class="mt-2 flex items-center text-xs">
                        <span data-translate class="text-orange-600 dark:text-orange-400 font-medium">รีวิวทั้งหมด</span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 dark:from-orange-600 dark:to-amber-700 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- คะแนนเฉลี่ย --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-green-100 dark:border-green-900/30 hover:border-green-300 dark:hover:border-green-700 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p data-translate class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">คะแนนเฉลี่ย</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($avgRating ?? 0, 1) }}/5.0</h3>
                    <div class="mt-2 flex items-center text-xs">
                        <span data-translate class="text-green-600 dark:text-green-400 font-medium">คะแนนเฉลี่ย</span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-700 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- รอการอนุมัติ --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-yellow-100 dark:border-yellow-900/30 hover:border-yellow-300 dark:hover:border-yellow-700 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p data-translate class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">รอการอนุมัติ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $pendingReviews ?? '0' }}</h3>
                    <div class="mt-2 flex items-center text-xs">
                        <span data-translate class="text-yellow-600 dark:text-yellow-400 font-medium">รอดำเนินการ</span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-yellow-500 to-amber-600 dark:from-yellow-600 dark:to-amber-700 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- เดือนนี้ --}}
        <div class="group bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-blue-100 dark:border-blue-900/30 hover:border-blue-300 dark:hover:border-blue-700 transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p data-translate class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">รีวิวเดือนนี้</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $monthlyReviews ?? '0' }}</h3>
                    <div class="mt-2 flex items-center text-xs">
                        <span data-translate class="text-blue-600 dark:text-blue-400 font-medium">รีวิวใหม่</span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-600 dark:from-blue-600 dark:to-cyan-700 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        {{-- Card Header --}}
        <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-gray-800 dark:to-gray-800 px-6 py-4 border-b border-orange-200 dark:border-gray-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                <svg class="w-6 h-6 mr-3 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
                จัดการรีวิว
            </h2>
        </div>

        {{-- Filters --}}
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" id="searchReviews"
                           class="pl-10 w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300"
                           placeholder="ค้นหารีวิว..." data-translate-placeholder>
                </div>
                <select id="filterRating"
                        class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300">
                    <option data-translate>คะแนนทั้งหมด</option>
                    <option value="5">5 ดาว</option>
                    <option value="4">4 ดาว</option>
                    <option value="3">3 ดาว</option>
                    <option value="2">2 ดาว</option>
                    <option value="1">1 ดาว</option>
                </select>
                <select id="filterStatus"
                        class="rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-orange-500 dark:focus:border-orange-400 focus:ring focus:ring-orange-200 dark:focus:ring-orange-800 transition-all duration-300">
                    <option data-translate>สถานะทั้งหมด</option>
                    <option data-translate>รอดำเนินการ</option>
                    <option data-translate>อนุมัติแล้ว</option>
                    <option data-translate>ปฏิเสธ</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ID
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            รายการ
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ใช้
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            คะแนน
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            รีวิว
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            วันที่
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($reviews ?? [] as $review)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                            #{{ $review->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                            {{ $review->listing_title ?? 'ไม่ระบุ' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ $review->user_name ?? 'ไม่ระบุ' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-1">
                                @for($i = 0; $i < 5; $i++)
                                    <svg class="w-4 h-4 {{ $i < ($review->rating ?? 0) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs">
                            <div class="line-clamp-2">{{ Str::limit($review->comment ?? 'ไม่มีความคิดเห็น', 50) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($review->status == 'approved')
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                    อนุมัติแล้ว
                                </span>
                            @elseif($review->status == 'rejected')
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                    ปฏิเสธ
                                </span>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                    รอดำเนินการ
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                            {{ isset($review->created_at) ? $review->created_at->format('d M Y') : 'ไม่ระบุ' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                @if($review->status == 'pending')
                                <button onclick="approveReview({{ $review->id }})"
                                        class="inline-flex items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition-all duration-300 shadow-sm hover:shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                                <button onclick="rejectReview({{ $review->id }})"
                                        class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-lg transition-all duration-300 shadow-sm hover:shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                @endif
                                <button onclick="viewReview({{ $review->id }})"
                                        class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition-all duration-300 shadow-sm hover:shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                    </svg>
                                </div>
                                <p data-translate class="text-lg font-medium text-gray-500 dark:text-gray-400">ไม่พบรีวิว</p>
                                <p data-translate class="text-sm text-gray-400 dark:text-gray-500 mt-1">รอรีวิวจากผู้ใช้งาน</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if(isset($reviews) && method_exists($reviews, 'links'))
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
            {{ $reviews->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Scripts --}}
<script>
function approveReview(id) {
    if (confirm('คุณต้องการอนุมัติรีวิวนี้ใช่หรือไม่?')) {
        // ส่งคำขออนุมัติ
        console.log('กำลังอนุมัติรีวิว ID:', id);
        // เพิ่มโค้ดสำหรับส่ง API request ได้ที่นี่
    }
}

function rejectReview(id) {
    if (confirm('คุณต้องการปฏิเสธรีวิวนี้ใช่หรือไม่?')) {
        // ส่งคำขอปฏิเสธ
        console.log('กำลังปฏิเสธรีวิว ID:', id);
        // เพิ่มโค้ดสำหรับส่ง API request ได้ที่นี่
    }
}

function viewReview(id) {
    // ดูรายละเอียดรีวิว
    console.log('กำลังดูรีวิว ID:', id);
    // เพิ่มโค้ดสำหรับแสดง modal หรือ redirect ได้ที่นี่
}
</script>
@endsection
