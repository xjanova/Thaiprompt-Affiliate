@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 p-6">
    <!-- Gradient Header -->
    <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-indigo-600 to-purple-600 rounded-3xl shadow-2xl p-8 mb-8">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2" data-translate>รายละเอียดห้องพัก</h1>
                        <p class="text-blue-100">{{ $room->name }}</p>
                        <p class="text-blue-200 text-sm">{{ $hotel->name }}</p>
                    </div>
                </div>

                <!-- Language Switcher -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="px-4 py-2 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition text-white font-semibold flex items-center">
                        <span class="mr-2">🇹🇭</span>
                        <span data-translate>ภาษา</span>
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open"
                         @click.away="open = false"
                         x-transition
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg py-2 z-50">
                        <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="mr-2">🇹🇭</span>
                            <span>ไทย</span>
                        </a>
                        <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="mr-2">🇬🇧</span>
                            <span>English</span>
                        </a>
                        <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="mr-2">🇨🇳</span>
                            <span>中文</span>
                        </a>
                        <a href="#" class="flex items-center px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <span class="mr-2">🇯🇵</span>
                            <span>日本語</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex space-x-3">
                <a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition text-white font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span data-translate>กลับ</span>
                </a>
                <a href="{{ route('admin.hotels.rooms.edit', [$hotel->id, $room->id]) }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition text-white font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span data-translate>แก้ไข</span>
                </a>
                <a href="{{ route('admin.hotels.rooms.availability', [$hotel->id, $room->id]) }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition text-white font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span data-translate>จัดการปฏิทิน</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <p class="text-blue-100 text-sm" data-translate>จำนวนห้องทั้งหมด</p>
                <svg class="w-8 h-8 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <p class="text-3xl font-bold">{{ $room->total_rooms }}</p>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <p class="text-green-100 text-sm" data-translate>ห้องว่าง</p>
                <svg class="w-8 h-8 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold">{{ $room->available_rooms ?? $room->total_rooms }}</p>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <p class="text-amber-100 text-sm" data-translate>ราคา/คืน</p>
                <svg class="w-8 h-8 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold">฿{{ number_format($room->base_price) }}</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="flex items-center justify-between mb-2">
                <p class="text-purple-100 text-sm" data-translate>ผู้เข้าพักสูงสุด</p>
                <svg class="w-8 h-8 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <p class="text-3xl font-bold">{{ $room->max_adults + $room->max_children }}</p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Room Images -->
            @if($room->main_image_url || ($room->images && count($room->images) > 0))
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span data-translate>รูปภาพห้องพัก</span>
                    </h2>
                </div>
                <div class="p-6">
                    @if($room->main_image_url)
                    <div class="mb-4">
                        <img src="{{ $room->main_image_url }}" alt="{{ $room->name }}" class="w-full h-96 object-cover rounded-xl shadow-lg">
                    </div>
                    @endif
                    @if($room->images && count($room->images) > 0)
                    <div class="grid grid-cols-4 gap-4">
                        @foreach($room->images as $image)
                        <img src="{{ $image }}" alt="Room image" class="w-full h-24 object-cover rounded-lg hover:scale-105 transition cursor-pointer">
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Room Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span data-translate>ข้อมูลห้องพัก</span>
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ชื่อห้อง</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $room->name }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ขนาดห้อง</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                @if($room->size_sqm)
                                    {{ $room->size_sqm }} <span data-translate>ตร.ม.</span>
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ผู้ใหญ่สูงสุด</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $room->max_adults }} <span data-translate>คน</span></p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>เด็กสูงสุด</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $room->max_children }} <span data-translate>คน</span></p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ราคาปกติ</p>
                            <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">฿{{ number_format($room->base_price) }}</p>
                        </div>
                        @if($room->weekend_price)
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ราคาวันหยุด</p>
                            <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">฿{{ number_format($room->weekend_price) }}</p>
                        </div>
                        @endif
                        @if($room->view_type)
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ประเภทวิว</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ ucfirst($room->view_type) }}</p>
                        </div>
                        @endif
                        @if($room->bed_type)
                        <div class="space-y-1">
                            <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>ประเภทเตียง</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $room->bed_type }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Description -->
            @if($room->description)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span data-translate>คำอธิบาย</span>
                    </h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ $room->description }}</p>
                </div>
            </div>
            @endif

            <!-- Amenities -->
            @if($room->amenities && count($room->amenities) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-6 h-6 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                        <span data-translate>สิ่งอำนวยความสะดวก</span>
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($room->amenities as $amenity)
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">{{ $amenity }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column (1/3) -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span data-translate>สถานะ</span>
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" data-translate>สถานะห้อง</span>
                        @if($room->is_active)
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-sm font-semibold" data-translate>เปิดใช้งาน</span>
                        @else
                        <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-full text-sm font-semibold" data-translate>ปิดใช้งาน</span>
                        @endif
                    </div>
                    @if($room->is_popular)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" data-translate>ห้องยอดนิยม</span>
                        <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded-full text-sm font-semibold">⭐</span>
                    </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" data-translate>จำนวนห้องทั้งหมด</span>
                        <span class="text-gray-900 dark:text-white font-semibold">{{ $room->total_rooms }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" data-translate>ห้องที่ว่าง</span>
                        <span class="text-gray-900 dark:text-white font-semibold">{{ $room->available_rooms ?? $room->total_rooms }}</span>
                    </div>
                </div>
            </div>

            <!-- Pricing Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span data-translate>ราคา</span>
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" data-translate>ราคาปกติ/คืน</span>
                        <span class="text-blue-600 dark:text-blue-400 font-bold text-xl">฿{{ number_format($room->base_price) }}</span>
                    </div>
                    @if($room->weekend_price)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" data-translate>ราคาวันหยุด/คืน</span>
                        <span class="text-blue-600 dark:text-blue-400 font-bold text-xl">฿{{ number_format($room->weekend_price) }}</span>
                    </div>
                    @endif
                    @if($room->special_price)
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" data-translate>ราคาพิเศษ</span>
                        <span class="text-green-600 dark:text-green-400 font-bold text-xl">฿{{ number_format($room->special_price) }}</span>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                        </svg>
                        <span data-translate>การจัดการ</span>
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    <a href="{{ route('admin.hotels.rooms.edit', [$hotel->id, $room->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-xl transition font-semibold">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span data-translate>แก้ไขข้อมูล</span>
                    </a>
                    <a href="{{ route('admin.hotels.rooms.availability', [$hotel->id, $room->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-xl transition font-semibold">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span data-translate>จัดการปฏิทิน</span>
                    </a>
                    <button onclick="deleteRoom()"
                            class="w-full flex items-center justify-center px-4 py-3 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-xl transition font-semibold">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span data-translate>ลบห้องพัก</span>
                    </button>
                </div>
            </div>

            <!-- Timestamps -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span data-translate>ข้อมูลเวลา</span>
                    </h3>
                </div>
                <div class="p-6 space-y-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>สร้างเมื่อ</p>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $room->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400" data-translate>อัพเดทล่าสุด</p>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $room->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * ลบห้องพัก
 */
function deleteRoom() {
    if (confirm('คุณแน่ใจหรือว่าต้องการลบห้องพักนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('admin.hotels.rooms.destroy', [$hotel->id, $room->id]) }}';
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
