@extends('layouts.admin-v3')

@section('title', 'รายละเอียดการจอง #' . $booking->booking_number)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6">
    <!-- Language Switcher -->
    <div class="flex justify-end mb-4" x-data="{ open: false }">
        <div class="relative">
            <button @click="open = !open" class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                </svg>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">ภาษา</span>
            </button>
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇹🇭</span>
                    <span class="text-gray-700 dark:text-gray-200">ไทย</span>
                </a>
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇬🇧</span>
                    <span class="text-gray-700 dark:text-gray-200">English</span>
                </a>
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇨🇳</span>
                    <span class="text-gray-700 dark:text-gray-200">中文</span>
                </a>
                <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                    <span class="text-2xl mr-3">🇯🇵</span>
                    <span class="text-gray-700 dark:text-gray-200">日本語</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Gradient Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 rounded-3xl shadow-2xl p-8 mb-8">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>

        <div class="relative flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                    <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <span data-translate>การจอง</span> #{{ $booking->booking_number }}
                    </h1>
                    <p class="text-emerald-100" data-translate>รายละเอียดการจองโรงแรม</p>
                </div>
            </div>

            <a href="{{ route('admin.hotels.bookings.index') }}"
               class="relative overflow-hidden group px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center shadow-lg">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span data-translate>กลับ</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Booking Details Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span data-translate>รายละเอียดการจอง</span>
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="space-y-4">
                            <div class="p-4 bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>โรงแรม</p>
                                <a href="{{ route('admin.hotels.show', $booking->roomType->hotel->id) }}" class="text-lg font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                                    {{ $booking->roomType->hotel->name }}
                                </a>
                            </div>

                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>ประเภทห้อง</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $booking->roomType->name }}</p>
                            </div>

                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>จำนวนห้อง</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $booking->rooms_count }} <span data-translate>ห้อง</span></p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2" data-translate>เช็คอิน</p>
                                <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ $booking->check_in_date->format('d/m/Y') }}</p>
                            </div>

                            <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2" data-translate>เช็คเอาท์</p>
                                <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ $booking->check_out_date->format('d/m/Y') }}</p>
                            </div>

                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>จำนวนคืน</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $booking->nights }} <span data-translate>คืน</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span data-translate>ผู้จอง</span>
                                </h3>
                                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <p class="text-gray-900 dark:text-white font-medium">{{ $booking->user->name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->user->email }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->user->phone }}</p>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    <span data-translate>ผู้เข้าพัก</span>
                                </h3>
                                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <p class="text-gray-900 dark:text-white font-medium">{{ $booking->guest_name }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->guest_email }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $booking->guest_phone }}</p>
                                </div>
                                <div class="flex gap-3">
                                    <div class="flex-1 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                        <p class="text-xs text-gray-600 dark:text-gray-400" data-translate>ผู้ใหญ่</p>
                                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $booking->adults }}</p>
                                    </div>
                                    <div class="flex-1 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                                        <p class="text-xs text-gray-600 dark:text-gray-400" data-translate>เด็ก</p>
                                        <p class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ $booking->children }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($booking->special_requests)
                        <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-2" data-translate>คำขอพิเศษ</p>
                            <p class="text-gray-700 dark:text-gray-300">{{ $booking->special_requests }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Payment Information Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span data-translate>ข้อมูลการชำระเงิน</span>
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400" data-translate>ค่าห้องพัก</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">฿{{ number_format($booking->room_total, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400">
                                <span data-translate>ภาษี</span> ({{ $booking->tax_rate }}%)
                            </span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">฿{{ number_format($booking->tax_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400" data-translate>ค่าบริการ</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">฿{{ number_format($booking->service_charge, 2) }}</span>
                        </div>
                        @if($booking->discount_amount > 0)
                        <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400" data-translate>ส่วนลด</span>
                            <span class="text-lg font-semibold text-green-600 dark:text-green-400">-฿{{ number_format($booking->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between items-center py-3 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl px-4 mt-3">
                            <span class="text-lg font-bold text-gray-900 dark:text-white" data-translate>ยอดรวมทั้งสิ้น</span>
                            <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">฿{{ number_format($booking->total_amount, 2) }}</span>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>วิธีชำระเงิน</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ ucfirst($booking->payment_method) }}</p>
                            </div>
                            @if($booking->payment_status == 'paid')
                            <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Transaction ID</p>
                                <p class="text-sm font-mono font-semibold text-green-700 dark:text-green-400">{{ $booking->transaction_id }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    <span data-translate>ชำระเมื่อ:</span> {{ $booking->paid_at?->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Affiliate Info Card -->
            @if($booking->affiliate)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-pink-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span data-translate>ข้อมูล Affiliate</span>
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl border border-purple-200 dark:border-purple-800">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Affiliate</p>
                            <p class="text-lg font-semibold text-purple-600 dark:text-purple-400">{{ $booking->affiliate->name }}</p>
                        </div>
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>ค่าคอมมิชชั่น</p>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($booking->commission_amount, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar (1/3) -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span data-translate>สถานะ</span>
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2" data-translate>สถานะการจอง</p>
                        @php
                            $statusMap = [
                                'pending' => ['bg' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border-amber-300', 'label' => 'รอชำระเงิน'],
                                'confirmed' => ['bg' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-300', 'label' => 'ยืนยันแล้ว'],
                                'checked_in' => ['bg' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border-blue-300', 'label' => 'เช็คอินแล้ว'],
                                'checked_out' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 border-purple-300', 'label' => 'เช็คเอาท์แล้ว'],
                                'cancelled' => ['bg' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-300', 'label' => 'ยกเลิกแล้ว'],
                                'completed' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 border-emerald-300', 'label' => 'เสร็จสิ้น']
                            ];
                            $status = $statusMap[$booking->status] ?? ['bg' => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 border-gray-300', 'label' => $booking->status];
                        @endphp
                        <span class="inline-flex px-4 py-2 rounded-xl text-sm font-bold {{ $status['bg'] }} border-2 {{ $status['border'] ?? '' }}">
                            {{ $status['label'] }}
                        </span>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2" data-translate>สถานะการชำระเงิน</p>
                        <span class="inline-flex px-4 py-2 rounded-xl text-sm font-bold {{ $booking->payment_status == 'paid' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-2 border-green-300' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border-2 border-amber-300' }}">
                            {{ $booking->payment_status == 'paid' ? 'ชำระแล้ว' : 'รอชำระ' }}
                        </span>
                    </div>

                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1" data-translate>สร้างเมื่อ</p>
                        <p class="text-gray-900 dark:text-white font-semibold">{{ $booking->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-cyan-500 to-blue-600 px-6 py-4">
                    <h2 class="text-xl font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                        <span data-translate>การจัดการ</span>
                    </h2>
                </div>
                <div class="p-6 space-y-3">
                    @if($booking->status == 'confirmed')
                        <button onclick="updateStatus('checked_in')" class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Check In
                        </button>
                    @endif

                    @if($booking->status == 'checked_in')
                        <button onclick="updateStatus('checked_out')" class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Check Out
                        </button>
                    @endif

                    @if($booking->status == 'checked_out')
                        <button onclick="updateStatus('completed')" class="w-full px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span data-translate>ทำรายการสำเร็จ</span>
                        </button>
                    @endif

                    @if(in_array($booking->status, ['pending', 'confirmed']))
                        <button onclick="cancelBooking()" class="w-full px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span data-translate>ยกเลิกการจอง</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * อัพเดทสถานะการจอง
 *
 * @param {string} status - สถานะใหม่ที่ต้องการอัพเดท
 */
function updateStatus(status) {
    if (confirm('คุณต้องการเปลี่ยนสถานะ?')) {
        fetch('/admin/hotels/bookings/{{ $booking->id }}/update-status', {
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
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        });
    }
}

/**
 * ยกเลิกการจอง
 */
function cancelBooking() {
    if (confirm('คุณต้องการยกเลิกการจองนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        fetch('/admin/hotels/bookings/{{ $booking->id }}/cancel', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'เกิดข้อผิดพลาด');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
        });
    }
}
</script>
@endsection
