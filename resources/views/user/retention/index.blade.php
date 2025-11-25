@extends('layouts.user-arrow-x')

@section('title', 'ระบบรักษายอด - Membership Retention')

@section('content')
{{-- หน้าแดชบอร์ดระบบรักษายอด - V3 Tailwind + Alpine.js --}}
<div class="min-h-screen p-4 md:p-6 lg:p-8"
     x-data="retentionDashboard()"
     x-init="init()">

    {{-- Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl md:rounded-3xl mb-6 md:mb-8
                bg-gradient-to-br from-purple-600 via-pink-500 to-orange-500
                dark:from-purple-900 dark:via-pink-800 dark:to-orange-800
                p-6 md:p-8 lg:p-10 shadow-2xl">

        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-20 -right-20 w-40 h-40 md:w-60 md:h-60
                        bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-20 -left-20 w-40 h-40 md:w-60 md:h-60
                        bg-white/10 rounded-full blur-3xl animate-pulse"
                 style="animation-delay: 1s;"></div>
        </div>

        {{-- Content --}}
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-6">
            <div class="flex items-center gap-4">
                {{-- Icon --}}
                <div class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur-xl
                            rounded-2xl flex items-center justify-center
                            shadow-lg border border-white/20">
                    <i class="fas fa-heartbeat text-white text-3xl md:text-4xl animate-pulse"></i>
                </div>

                {{-- Title --}}
                <div>
                    <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white drop-shadow-lg">
                        ระบบรักษายอด
                    </h1>
                    <p class="text-white/80 text-sm md:text-base mt-1">
                        Membership Retention System
                    </p>
                </div>
            </div>

            {{-- Action Button --}}
            <a href="{{ route('user.retention.how-it-works') }}"
               class="inline-flex items-center gap-3 px-6 py-3 bg-white/20 backdrop-blur-xl
                      border border-white/30 rounded-xl text-white font-semibold
                      hover:bg-white/30 transition-all duration-300
                      shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-book-open"></i>
                <span>คู่มือการใช้งาน</span>
            </a>
        </div>
    </div>

    {{-- Life Power Widget --}}
    <div class="mb-6 md:mb-8">
        @include('components.life-power-widget-v3')
    </div>

    {{-- Statistics Cards --}}
    <div class="mb-6 md:mb-8">
        <div class="flex items-center gap-3 mb-4 md:mb-6">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-blue-500 to-purple-600
                        rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-chart-line text-white text-lg md:text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                    สถิติการรักษายอด
                </h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm">ภาพรวมประสิทธิภาพของคุณ</p>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
            {{-- Consecutive Months --}}
            <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl
                        shadow-lg hover:shadow-2xl transition-all duration-300
                        border border-gray-100 dark:border-gray-700
                        transform hover:-translate-y-1">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-blue-500 to-purple-600
                            transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <div class="p-4 md:p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600
                                    rounded-xl flex items-center justify-center shadow-lg
                                    transform group-hover:scale-110 group-hover:rotate-3 transition-all">
                            <i class="fas fa-calendar-check text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-1">
                        {{ $statistics['consecutive_months'] }}
                    </div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">เดือนติดต่อกัน</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">Consecutive Months</div>
                </div>
            </div>

            {{-- Achieved Count --}}
            <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl
                        shadow-lg hover:shadow-2xl transition-all duration-300
                        border border-gray-100 dark:border-gray-700
                        transform hover:-translate-y-1">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-green-500 to-emerald-600
                            transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <div class="p-4 md:p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600
                                    rounded-xl flex items-center justify-center shadow-lg
                                    transform group-hover:scale-110 group-hover:rotate-3 transition-all">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-1">
                        {{ $statistics['achieved_count'] }}
                    </div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">ผ่านเกณฑ์</div>
                    <div class="mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-green-100 dark:bg-green-900/30
                                     text-green-700 dark:text-green-400 text-xs font-semibold rounded-lg">
                            <i class="fas fa-arrow-up"></i>
                            เยี่ยมมาก!
                        </span>
                    </div>
                </div>
            </div>

            {{-- Failed Count --}}
            <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl
                        shadow-lg hover:shadow-2xl transition-all duration-300
                        border border-gray-100 dark:border-gray-700
                        transform hover:-translate-y-1">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-500 to-pink-600
                            transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <div class="p-4 md:p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-600
                                    rounded-xl flex items-center justify-center shadow-lg
                                    transform group-hover:scale-110 group-hover:rotate-3 transition-all">
                            <i class="fas fa-times-circle text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-1">
                        {{ $statistics['failed_count'] }}
                    </div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">ไม่ผ่านเกณฑ์</div>
                    <div class="mt-2">
                        @if($statistics['failed_count'] > 0)
                            <a href="{{ route('user.retention.repair') }}"
                               class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 dark:bg-amber-900/30
                                      text-amber-700 dark:text-amber-400 text-xs font-semibold rounded-lg
                                      hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-colors">
                                <i class="fas fa-tools"></i>
                                ซ่อมสิทธิ์
                            </a>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700
                                         text-gray-600 dark:text-gray-400 text-xs font-semibold rounded-lg">
                                <i class="fas fa-shield-alt"></i>
                                ปลอดภัย
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Repair Count --}}
            <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl
                        shadow-lg hover:shadow-2xl transition-all duration-300
                        border border-gray-100 dark:border-gray-700
                        transform hover:-translate-y-1">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-500 to-orange-600
                            transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                <div class="p-4 md:p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600
                                    rounded-xl flex items-center justify-center shadow-lg
                                    transform group-hover:scale-110 group-hover:rotate-3 transition-all">
                            <i class="fas fa-tools text-white text-xl"></i>
                        </div>
                    </div>
                    <div class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-1">
                        {{ $statistics['repair_count'] }}
                    </div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">ซ่อมสิทธิ์แล้ว</div>
                    <div class="mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-amber-100 dark:bg-amber-900/30
                                     text-amber-700 dark:text-amber-400 text-xs font-semibold rounded-lg">
                            <i class="fas fa-wrench"></i>
                            {{ $statistics['repair_count'] }} ครั้ง
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mb-6 md:mb-8">
        <div class="flex items-center gap-3 mb-4 md:mb-6">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-gradient-to-br from-pink-500 to-rose-600
                        rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-rocket text-white text-lg md:text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                    ดำเนินการด่วน
                </h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm">เครื่องมือช่วยเหลือของคุณ</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
            {{-- Repair --}}
            <a href="{{ route('user.retention.repair') }}"
               class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl
                      shadow-lg hover:shadow-2xl transition-all duration-300
                      border border-gray-100 dark:border-gray-700
                      transform hover:-translate-y-1 p-6">
                <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-amber-500 to-orange-600
                            transform origin-bottom scale-y-0 group-hover:scale-y-100 transition-transform duration-300"></div>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600
                                rounded-2xl flex items-center justify-center shadow-lg
                                transform group-hover:scale-110 group-hover:-rotate-3 transition-all">
                        <i class="fas fa-tools text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">ซ่อมสิทธิ์</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            ซื้อซ่อมเดือนที่ไม่ผ่านเกณฑ์เพื่อกู้คืนสิทธิ์
                        </p>
                        <span class="inline-flex items-center gap-1 mt-2 px-2 py-1
                                     bg-amber-100 dark:bg-amber-900/30
                                     text-amber-700 dark:text-amber-400
                                     text-xs font-semibold rounded-lg">
                            <i class="fas fa-wrench"></i>
                            Repair Rights
                        </span>
                    </div>
                    <div class="text-gray-300 dark:text-gray-600 group-hover:text-amber-500
                                transform group-hover:translate-x-2 transition-all">
                        <i class="fas fa-arrow-right text-2xl"></i>
                    </div>
                </div>
            </a>

            {{-- Advance Renewal --}}
            <a href="{{ route('user.retention.advance-renewal') }}"
               class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl
                      shadow-lg hover:shadow-2xl transition-all duration-300
                      border border-gray-100 dark:border-gray-700
                      transform hover:-translate-y-1 p-6">
                <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-green-500 to-emerald-600
                            transform origin-bottom scale-y-0 group-hover:scale-y-100 transition-transform duration-300"></div>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600
                                rounded-2xl flex items-center justify-center shadow-lg
                                transform group-hover:scale-110 group-hover:-rotate-3 transition-all">
                        <i class="fas fa-plus-circle text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">เติมวันล่วงหน้า</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            ซื้อสิทธิ์ล่วงหน้าพร้อมส่วนลดพิเศษ 10%
                        </p>
                        <span class="inline-flex items-center gap-1 mt-2 px-2 py-1
                                     bg-green-100 dark:bg-green-900/30
                                     text-green-700 dark:text-green-400
                                     text-xs font-semibold rounded-lg">
                            <i class="fas fa-percentage"></i>
                            10% Discount
                        </span>
                    </div>
                    <div class="text-gray-300 dark:text-gray-600 group-hover:text-green-500
                                transform group-hover:translate-x-2 transition-all">
                        <i class="fas fa-arrow-right text-2xl"></i>
                    </div>
                </div>
            </a>

            {{-- How It Works --}}
            <a href="{{ route('user.retention.how-it-works') }}"
               class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl
                      shadow-lg hover:shadow-2xl transition-all duration-300
                      border border-gray-100 dark:border-gray-700
                      transform hover:-translate-y-1 p-6">
                <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-blue-500 to-cyan-600
                            transform origin-bottom scale-y-0 group-hover:scale-y-100 transition-transform duration-300"></div>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-600
                                rounded-2xl flex items-center justify-center shadow-lg
                                transform group-hover:scale-110 group-hover:-rotate-3 transition-all">
                        <i class="fas fa-book-open text-white text-2xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">คู่มือการใช้งาน</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            เรียนรู้วิธีการทำงานของระบบรักษายอด
                        </p>
                        <span class="inline-flex items-center gap-1 mt-2 px-2 py-1
                                     bg-blue-100 dark:bg-blue-900/30
                                     text-blue-700 dark:text-blue-400
                                     text-xs font-semibold rounded-lg">
                            <i class="fas fa-graduation-cap"></i>
                            Learn More
                        </span>
                    </div>
                    <div class="text-gray-300 dark:text-gray-600 group-hover:text-blue-500
                                transform group-hover:translate-x-2 transition-all">
                        <i class="fas fa-arrow-right text-2xl"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- History Timeline --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden
                border border-gray-100 dark:border-gray-700">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-purple-600 via-pink-500 to-orange-500
                    dark:from-purple-900 dark:via-pink-800 dark:to-orange-800
                    p-6 md:p-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white/20 backdrop-blur-xl rounded-xl
                                flex items-center justify-center border border-white/20">
                        <i class="fas fa-history text-white text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-xl md:text-2xl font-bold text-white">ประวัติการรักษายอด</h3>
                        <p class="text-white/80 text-sm">บันทึกผลการดำเนินงาน</p>
                    </div>
                </div>

                {{-- Filter Tabs --}}
                <div class="flex bg-white/10 backdrop-blur-xl rounded-xl p-1 border border-white/20">
                    <button @click="filter = 'all'"
                            :class="filter === 'all' ? 'bg-white/25 text-white' : 'text-white/70 hover:text-white'"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm transition-all">
                        <i class="fas fa-list"></i>
                        <span class="hidden sm:inline">ทั้งหมด</span>
                    </button>
                    <button @click="filter = 'achieved'"
                            :class="filter === 'achieved' ? 'bg-white/25 text-white' : 'text-white/70 hover:text-white'"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm transition-all">
                        <i class="fas fa-check"></i>
                        <span class="hidden sm:inline">ผ่าน</span>
                    </button>
                    <button @click="filter = 'failed'"
                            :class="filter === 'failed' ? 'bg-white/25 text-white' : 'text-white/70 hover:text-white'"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg font-medium text-sm transition-all">
                        <i class="fas fa-times"></i>
                        <span class="hidden sm:inline">ไม่ผ่าน</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Timeline Content --}}
        <div class="p-6 md:p-8">
            @forelse($history as $index => $record)
                <div x-show="filter === 'all' || filter === '{{ $record->status }}'"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-y-4"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     class="flex gap-4 md:gap-6 mb-6 last:mb-0">

                    {{-- Timeline Marker --}}
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-lg
                                    @if($record->status === 'achieved')
                                        bg-gradient-to-br from-green-500 to-emerald-600
                                    @elseif($record->status === 'failed')
                                        bg-gradient-to-br from-red-500 to-pink-600
                                    @else
                                        bg-gradient-to-br from-amber-500 to-orange-600
                                    @endif">
                            @if($record->status === 'achieved')
                                <i class="fas fa-check text-white text-lg"></i>
                            @elseif($record->status === 'failed')
                                <i class="fas fa-times text-white text-lg"></i>
                            @else
                                <i class="fas fa-clock text-white text-lg"></i>
                            @endif
                        </div>
                        @if($index < count($history) - 1)
                            <div class="w-0.5 flex-1 mt-2 bg-gradient-to-b from-gray-300 to-transparent
                                        dark:from-gray-600"></div>
                        @endif
                    </div>

                    {{-- Timeline Content --}}
                    <div class="flex-1 pb-6">
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 md:p-6
                                    border border-gray-200 dark:border-gray-700
                                    hover:shadow-lg transition-shadow">
                            {{-- Header --}}
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                                <div class="flex items-center gap-2 text-gray-900 dark:text-white font-semibold">
                                    <i class="fas fa-calendar text-purple-500"></i>
                                    <span>{{ $record->period_month }}</span>
                                </div>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold
                                             @if($record->status === 'achieved')
                                                 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400
                                             @elseif($record->status === 'failed')
                                                 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400
                                             @else
                                                 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400
                                             @endif">
                                    @if($record->status === 'achieved')
                                        <i class="fas fa-check-circle"></i>
                                        ผ่านเกณฑ์
                                    @elseif($record->status === 'failed')
                                        <i class="fas fa-times-circle"></i>
                                        ไม่ผ่านเกณฑ์
                                    @else
                                        <i class="fas fa-clock"></i>
                                        รอดำเนินการ
                                    @endif
                                </span>
                            </div>

                            {{-- Points Display --}}
                            <div class="flex flex-wrap gap-3 md:gap-4">
                                {{-- Target --}}
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-xl px-4 py-3 shadow-sm">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600
                                                rounded-lg flex items-center justify-center">
                                        <i class="fas fa-bullseye text-white"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">เป้าหมาย</div>
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ number_format($record->required_points, 0) }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Earned --}}
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-xl px-4 py-3 shadow-sm">
                                    <div class="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600
                                                rounded-lg flex items-center justify-center">
                                        <i class="fas fa-star text-white"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">ทำได้</div>
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ number_format($record->earned_points, 0) }}
                                        </div>
                                    </div>
                                </div>

                                {{-- Percentage --}}
                                <div class="flex items-center gap-3 bg-white dark:bg-gray-800 rounded-xl px-4 py-3 shadow-sm">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600
                                                rounded-lg flex items-center justify-center">
                                        <i class="fas fa-percentage text-white"></i>
                                    </div>
                                    <div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase">เปอร์เซ็นต์</div>
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ number_format(($record->earned_points / max($record->required_points, 1)) * 100, 1) }}%
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            @if($record->status === 'achieved')
                                <div class="mt-4">
                                    <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-green-500 to-emerald-600 rounded-full relative"
                                             style="width: {{ min(100, ($record->earned_points / max($record->required_points, 1)) * 100) }}%">
                                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent
                                                        animate-pulse"></div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Achievement Date --}}
                            @if($record->achieved_at)
                                <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700
                                            text-sm text-gray-600 dark:text-gray-400">
                                    <i class="fas fa-trophy text-amber-500"></i>
                                    <span>บรรลุเมื่อ: {{ $record->achieved_at->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                {{-- Empty State --}}
                <div class="text-center py-16">
                    <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full
                                flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-inbox text-4xl text-gray-400 dark:text-gray-500"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300 mb-2">
                        ยังไม่มีประวัติการรักษายอด
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        เริ่มต้นการรักษายอดของคุณวันนี้!
                    </p>
                    <a href="{{ route('user.retention.how-it-works') }}"
                       class="inline-flex items-center gap-2 px-6 py-3
                              bg-gradient-to-r from-purple-600 to-pink-600
                              text-white font-semibold rounded-xl
                              shadow-lg hover:shadow-xl
                              transform hover:scale-105 transition-all">
                        <i class="fas fa-book-open"></i>
                        <span>เรียนรู้เพิ่มเติม</span>
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
/**
 * Alpine.js component สำหรับ retention dashboard
 * จัดการ filter และ loading data
 */
function retentionDashboard() {
    return {
        filter: 'all',
        loading: false,

        init() {
            // เริ่มต้น component
            console.log('Retention Dashboard initialized');
        },

        /**
         * กรองประวัติตามสถานะ
         */
        filterHistory(status) {
            this.filter = status;
        }
    };
}
</script>
@endsection
