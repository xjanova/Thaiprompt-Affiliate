@extends('layouts.admin-v3')

@section('title', 'รายละเอียดเจ้าของโรงแรม')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header with Back Button --}}
        <div class="mb-6">
            <a href="{{ route('admin.hotel-owners.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl shadow-lg transition-all border border-gray-200 dark:border-gray-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปรายการเจ้าของโรงแรม
            </a>
        </div>

        {{-- Owner Profile Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden mb-8">
            {{-- Header Gradient --}}
            <div class="relative h-32 bg-gradient-to-br from-purple-600 via-pink-600 to-rose-600">
                <div class="absolute inset-0 bg-black/10"></div>
            </div>

            {{-- Profile Content --}}
            <div class="relative px-8 pb-8">
                {{-- Avatar --}}
                <div class="flex items-end justify-between -mt-16 mb-6">
                    <div class="flex items-end">
                        <div class="h-32 w-32 rounded-2xl bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-4xl border-4 border-white dark:border-gray-800 shadow-xl">
                            {{ strtoupper(substr($hotelOwner->name, 0, 2)) }}
                        </div>
                        <div class="ml-6 mb-2">
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $hotelOwner->name }}</h1>
                            <p class="text-gray-500 dark:text-gray-400">เจ้าของโรงแรม #{{ $hotelOwner->id }}</p>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('admin.hotel-owners.edit', $hotelOwner->id) }}"
                           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            แก้ไขข้อมูล
                        </a>

                        {{-- Block/Unblock Button --}}
                        @if($hotelOwner->blocked_at)
                            <button onclick="alert('ฟังก์ชันยกเลิกบล็อกจะทำงานผ่าน AJAX')"
                                    class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                </svg>
                                ยกเลิกบล็อก
                            </button>
                        @else
                            <button onclick="alert('ฟังก์ชันบล็อกจะทำงานผ่าน AJAX')"
                                    class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                                บล็อกบัญชี
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Info Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {{-- Email --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">อีเมล</span>
                        </div>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $hotelOwner->email }}</p>
                    </div>

                    {{-- Phone --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">เบอร์โทรศัพท์</span>
                        </div>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $hotelOwner->phone ?? '-' }}</p>
                    </div>

                    {{-- Status --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">สถานะ</span>
                        </div>
                        @if($hotelOwner->blocked_at)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                ถูกบล็อก
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                ใช้งานปกติ
                            </span>
                        @endif
                    </div>

                    {{-- Joined Date --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm text-gray-500 dark:text-gray-400">วันที่เข้าร่วม</span>
                        </div>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $hotelOwner->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Managed Hotel --}}
        @if($hotelOwner->managedHotel)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <svg class="w-7 h-7 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    โรงแรมที่ดูแล
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Hotel Info --}}
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $hotelOwner->managedHotel->name }}</h3>
                            <p class="text-gray-600 dark:text-gray-400">{{ $hotelOwner->managedHotel->address }}</p>
                            <p class="text-gray-600 dark:text-gray-400">{{ $hotelOwner->managedHotel->city }}, {{ $hotelOwner->managedHotel->country }}</p>
                        </div>

                        <div class="flex items-center">
                            <span class="px-4 py-2 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 font-medium">
                                {{ ucfirst($hotelOwner->managedHotel->type) }}
                            </span>
                            @if($hotelOwner->managedHotel->is_active)
                                <span class="ml-3 px-4 py-2 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 font-medium">
                                    เปิดใช้งาน
                                </span>
                            @else
                                <span class="ml-3 px-4 py-2 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 font-medium">
                                    ปิดใช้งาน
                                </span>
                            @endif
                        </div>

                        <a href="{{ route('admin.hotels.show', $hotelOwner->managedHotel->id) }}"
                           class="inline-flex items-center px-6 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold transition-all">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                            ดูรายละเอียดโรงแรม
                        </a>
                    </div>

                    {{-- Hotel Statistics --}}
                    @if($statistics)
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Total Bookings --}}
                            <div class="p-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">การจองทั้งหมด</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($statistics['total_bookings']) }}</div>
                            </div>

                            {{-- Completed Bookings --}}
                            <div class="p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">เสร็จสิ้นแล้ว</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($statistics['completed_bookings']) }}</div>
                            </div>

                            {{-- Total Revenue --}}
                            <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">รายได้รวม</div>
                                <div class="text-xl font-bold text-gray-900 dark:text-white">฿{{ number_format($statistics['total_revenue'], 2) }}</div>
                            </div>

                            {{-- Average Rating --}}
                            <div class="p-4 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">คะแนนเฉลี่ย</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                                    <svg class="w-6 h-6 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    {{ $statistics['average_rating'] }}
                                </div>
                            </div>

                            {{-- Total Reviews --}}
                            <div class="p-4 bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-xl">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">รีวิวทั้งหมด</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($statistics['total_reviews']) }}</div>
                            </div>

                            {{-- Total Rooms --}}
                            <div class="p-4 bg-gradient-to-br from-rose-50 to-red-50 dark:from-rose-900/20 dark:to-red-900/20 rounded-xl">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ห้องพักทั้งหมด</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($statistics['total_rooms']) }}</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-12 mb-8">
                <div class="text-center">
                    <svg class="w-20 h-20 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีโรงแรมที่ดูแล</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">เจ้าของโรงแรมท่านนี้ยังไม่ได้รับมอบหมายโรงแรม</p>
                    <button onclick="alert('ฟังก์ชันกำหนดโรงแรมจะทำงานผ่าน AJAX')"
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        กำหนดโรงแรม
                    </button>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
