@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Alpine.js Language Switcher -->
        <div x-data="{ open: false, currentLang: '🇹🇭 ไทย' }" class="flex justify-end mb-6">
            <div class="relative">
                <button @click="open = !open" class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all border border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentLang"></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                    <a href="#" @click.prevent="currentLang = '🇹🇭 ไทย'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">🇹🇭 ไทย</a>
                    <a href="#" @click.prevent="currentLang = '🇬🇧 English'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">🇬🇧 English</a>
                    <a href="#" @click.prevent="currentLang = '🇨🇳 中文'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">🇨🇳 中文</a>
                    <a href="#" @click.prevent="currentLang = '🇯🇵 日本語'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">🇯🇵 日本語</a>
                </div>
            </div>
        </div>

        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-pink-600 to-rose-600 rounded-3xl shadow-2xl p-8 mb-8">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2" data-translate>จัดการการจองโรงแรม</h1>
                        <p class="text-purple-100" data-translate>ระบบจัดการการจองและห้องพัก</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.hotels.bookings.calendar') }}"
                       class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center shadow-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span data-translate>ปฏิทิน</span>
                    </a>
                    <a href="{{ route('admin.hotels.bookings.analytics') }}"
                       class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center shadow-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span data-translate>รายงาน</span>
                    </a>
                    <a href="{{ route('admin.hotels.bookings.create') }}"
                       class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center shadow-lg">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span data-translate>จองแบบ Manual</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl inline-block mb-2">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <p class="text-blue-100 text-sm mb-1" data-translate>การจองทั้งหมด</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total']) }}</p>
                </div>
            </div>

            <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl inline-block mb-2">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-amber-100 text-sm mb-1" data-translate>รอชำระเงิน</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['pending']) }}</p>
                </div>
            </div>

            <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl inline-block mb-2">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-green-100 text-sm mb-1" data-translate>ยืนยันแล้ว</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['confirmed']) }}</p>
                </div>
            </div>

            <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl inline-block mb-2">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-purple-100 text-sm mb-1" data-translate>รายได้รวม</p>
                    <p class="text-3xl font-bold">฿{{ number_format($stats['total_revenue']) }}</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <form method="GET" action="{{ route('admin.hotels.bookings.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div class="md:col-span-2 relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all"
                               placeholder="ค้นหาเลขที่จอง, ชื่อผู้จอง..." data-translate-placeholder>
                    </div>

                    <select name="status"
                            class="px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">
                        <option value="all" data-translate>สถานะทั้งหมด</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }} data-translate>รอชำระเงิน</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }} data-translate>ยืนยันแล้ว</option>
                        <option value="checked_in" {{ request('status') == 'checked_in' ? 'selected' : '' }} data-translate>เช็คอินแล้ว</option>
                        <option value="checked_out" {{ request('status') == 'checked_out' ? 'selected' : '' }} data-translate>เช็คเอาท์แล้ว</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }} data-translate>ยกเลิกแล้ว</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }} data-translate>เสร็จสิ้น</option>
                    </select>

                    <select name="hotel_id"
                            class="px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">
                        <option value="" data-translate>โรงแรมทั้งหมด</option>
                        @foreach($hotels as $hotel)
                            <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}>
                                {{ $hotel->name }}
                            </option>
                        @endforeach
                    </select>

                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">

                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">

                    <button type="submit"
                            class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg flex items-center justify-center md:col-span-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <!-- Bookings Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 px-8 py-6">
                <h2 class="text-2xl font-bold text-white" data-translate>รายการจอง</h2>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>เลขที่จอง</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>โรงแรม</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ผู้จอง</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>เช็คอิน</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>เช็คเอาท์</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>คืน</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ยอดเงิน</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>สถานะ</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ชำระเงิน</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($bookings as $booking)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ $booking->booking_number }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $booking->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $booking->hotel->name }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $booking->roomType->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $booking->guest_name }}</span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $booking->guest_email }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $booking->check_in_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $booking->check_out_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="inline-flex px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 rounded-full text-xs font-bold">
                                            {{ $booking->number_of_nights }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                            ฿{{ number_format($booking->total_amount) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $statusMap = [
                                                'pending' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300', 'label' => 'รอชำระเงิน'],
                                                'confirmed' => ['bg' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300', 'label' => 'ยืนยันแล้ว'],
                                                'checked_in' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300', 'label' => 'เช็คอินแล้ว'],
                                                'checked_out' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300', 'label' => 'เช็คเอาท์แล้ว'],
                                                'cancelled' => ['bg' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300', 'label' => 'ยกเลิกแล้ว'],
                                                'completed' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300', 'label' => 'เสร็จสิ้น']
                                            ];
                                            $status = $statusMap[$booking->status] ?? ['bg' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300', 'label' => $booking->status];
                                        @endphp
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $status['bg'] }}">
                                            {{ $status['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $paymentMap = [
                                                'paid' => ['bg' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300', 'label' => 'ชำระแล้ว'],
                                                'pending' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300', 'label' => 'รอชำระ'],
                                                'failed' => ['bg' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300', 'label' => 'ล้มเหลว'],
                                                'refunded' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300', 'label' => 'คืนเงินแล้ว']
                                            ];
                                            $payment = $paymentMap[$booking->payment_status] ?? ['bg' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300', 'label' => $booking->payment_status];
                                        @endphp
                                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $payment['bg'] }}">
                                            {{ $payment['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="{{ route('admin.hotels.bookings.show', $booking->id) }}"
                                               class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                                               title="ดูรายละเอียด">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                            @if($booking->status === 'confirmed')
                                                <button onclick="updateStatus({{ $booking->id }}, 'checked_in')"
                                                        class="p-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors"
                                                        title="เช็คอิน">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                                    </svg>
                                                </button>
                                            @endif
                                            @if($booking->status === 'checked_in')
                                                <button onclick="updateStatus({{ $booking->id }}, 'checked_out')"
                                                        class="p-2 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors"
                                                        title="เช็คเอาท์">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                                    </svg>
                                                </button>
                                            @endif
                                            @if(in_array($booking->status, ['pending', 'confirmed']))
                                                <button onclick="cancelBooking({{ $booking->id }})"
                                                        class="p-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                                        title="ยกเลิก">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                            </div>
                                            <p class="text-gray-500 dark:text-gray-400 text-lg font-medium" data-translate>ไม่พบข้อมูลการจอง</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $bookings->links() }}
                </div>
            </div>
        </div>

    </div>
</div>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

@push('scripts')
<script>
function updateStatus(bookingId, status) {
    if (confirm('คุณต้องการเปลี่ยนสถานะการจองนี้?')) {
        fetch(`/admin/hotels/bookings/${bookingId}/update-status`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
            }
        });
    }
}

function cancelBooking(bookingId) {
    const reason = prompt('กรุณาระบุเหตุผลในการยกเลิก:');
    if (reason !== null) {
        fetch(`/admin/hotels/bookings/${bookingId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
            }
        });
    }
}
</script>
@endpush
@endsection
