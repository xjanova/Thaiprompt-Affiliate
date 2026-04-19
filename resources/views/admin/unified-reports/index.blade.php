{{--
    ศูนย์รายงานรวม - หน้าหลัก
    แสดงรายการระบบทั้งหมดที่สามารถดูรายงานได้
--}}
@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'ศูนย์รายงานรวม')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        {{ $pageTitle ?? 'ศูนย์รายงานรวม' }}
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ $pageDescription ?? 'รายงานจากทุกระบบในที่เดียว พร้อมส่งออก Excel และกราฟวิเคราะห์' }}
                    </p>
                </div>

                {{-- Quick Actions --}}
                <div class="mt-4 md:mt-0 flex gap-3">
                    <a href="{{ route('admin.unified-reports.business-intelligence') }}"
                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600
                              hover:from-indigo-700 hover:to-purple-700 text-white font-medium rounded-lg
                              shadow-lg hover:shadow-xl transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Business Intelligence
                    </a>
                </div>
            </div>
        </div>

        {{-- System Cards Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($systems as $system)
                <a href="{{ route('admin.unified-reports.' . $system['id']) }}"
                   class="group relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl
                          transition-all duration-300 transform hover:-translate-y-1 overflow-hidden
                          border border-gray-100 dark:border-gray-700">
                    {{-- Gradient Top Border --}}
                    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

                    <div class="p-6">
                        {{-- Icon --}}
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-100 to-purple-100
                                    dark:from-indigo-900/50 dark:to-purple-900/50 flex items-center justify-center
                                    mb-4 group-hover:scale-110 transition-transform duration-300">
                            @switch($system['icon'])
                                @case('chart-bar')
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    @break
                                @case('users')
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    @break
                                @case('shopping-cart')
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    @break
                                @case('wallet')
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                    @break
                                @case('robot')
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    @break
                                @case('building')
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    @break
                                @case('cash-register')
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    @break
                                @case('bitcoin')
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    @break
                                @case('user-tie')
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    @break
                                @case('graduation-cap')
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l9-5-9-5-9 5 9 5z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                    </svg>
                                    @break
                                @default
                                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                            @endswitch
                        </div>

                        {{-- Title & Description --}}
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            {{ $system['name'] }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            {{ $system['description'] }}
                        </p>

                        {{-- Actions --}}
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $system['name_en'] }}
                            </span>
                            <span class="inline-flex items-center text-sm font-medium text-indigo-600 dark:text-indigo-400
                                         group-hover:text-indigo-800 dark:group-hover:text-indigo-300">
                                ดูรายงาน
                                <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </span>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    @if($system['available'])
                        <div class="absolute top-4 right-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                         bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                พร้อมใช้งาน
                            </span>
                        </div>
                    @else
                        <div class="absolute top-4 right-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                         bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                เร็วๆ นี้
                            </span>
                        </div>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Quick Stats Summary --}}
        <div class="mt-12 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-100 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                เครื่องมือด่วน
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                {{-- Export All --}}
                <a href="{{ route('admin.unified-reports.export', ['type' => 'executive']) }}"
                   class="flex flex-col items-center p-4 bg-gradient-to-br from-gray-50 to-gray-100
                          dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-lg transition-all
                          hover:scale-105 cursor-pointer">
                    <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">ส่งออก Excel</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">ภาพรวมทั้งหมด</span>
                </a>

                {{-- Compare Periods --}}
                <a href="{{ route('admin.unified-reports.business-intelligence') }}?compare=true"
                   class="flex flex-col items-center p-4 bg-gradient-to-br from-gray-50 to-gray-100
                          dark:from-gray-700 dark:to-gray-600 rounded-xl hover:shadow-lg transition-all
                          hover:scale-105 cursor-pointer">
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">เปรียบเทียบ</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">ช่วงเวลา</span>
                </a>

                {{-- Scheduled Reports --}}
                <div class="flex flex-col items-center p-4 bg-gradient-to-br from-gray-50 to-gray-100
                            dark:from-gray-700 dark:to-gray-600 rounded-xl opacity-60 cursor-not-allowed">
                    <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">ตั้งเวลา</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">เร็วๆ นี้</span>
                </div>

                {{-- Custom Report --}}
                <div class="flex flex-col items-center p-4 bg-gradient-to-br from-gray-50 to-gray-100
                            dark:from-gray-700 dark:to-gray-600 rounded-xl opacity-60 cursor-not-allowed">
                    <div class="w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">สร้างรายงาน</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">เร็วๆ นี้</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
