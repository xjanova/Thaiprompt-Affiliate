@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-teal-50 to-cyan-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Alpine.js Language Switcher -->
        <div x-data="{ open: false, currentLang: '🇹🇭 ไทย' }" class="flex justify-end mb-6">
            <div class="relative">
                <button @click="open = !open" class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all border border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentLang"></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                    <a href="#" @click.prevent="currentLang = '🇹🇭 ไทย'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-teal-50 dark:hover:bg-gray-700">🇹🇭 ไทย</a>
                    <a href="#" @click.prevent="currentLang = '🇬🇧 English'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-teal-50 dark:hover:bg-gray-700">🇬🇧 English</a>
                    <a href="#" @click.prevent="currentLang = '🇨🇳 中文'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-teal-50 dark:hover:bg-gray-700">🇨🇳 中文</a>
                    <a href="#" @click.prevent="currentLang = '🇯🇵 日本語'; open = false" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-teal-50 dark:hover:bg-gray-700">🇯🇵 日本語</a>
                </div>
            </div>
        </div>

        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-br from-teal-600 via-cyan-600 to-blue-600 rounded-3xl shadow-2xl p-8 mb-8">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2" data-translate>จัดการโรงแรม</h1>
                        <p class="text-teal-100" data-translate>ระบบจัดการโรงแรมและที่พัก</p>
                    </div>
                </div>
                <a href="{{ route('admin.hotels.create') }}"
                   class="relative overflow-hidden group px-8 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center shadow-lg">
                    <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span data-translate>เพิ่มโรงแรมใหม่</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Hotels -->
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-2">
                        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-blue-100 text-sm mb-1" data-translate>โรงแรมทั้งหมด</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total']) }}</p>
                </div>
            </div>

            <!-- Active Hotels -->
            <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-2">
                        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-green-100 text-sm mb-1" data-translate>เปิดใช้งาน</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['active']) }}</p>
                </div>
            </div>

            <!-- Featured Hotels -->
            <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-2">
                        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-amber-100 text-sm mb-1" data-translate>แนะนำ</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['featured']) }}</p>
                </div>
            </div>

            <!-- Total Rooms -->
            <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-2">
                        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-purple-100 text-sm mb-1" data-translate>ห้องทั้งหมด</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total_rooms']) }}</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <form method="GET" action="{{ route('admin.hotels.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div class="relative md:col-span-2">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all"
                               placeholder="ค้นหา..."
                               data-translate-placeholder>
                    </div>

                    <!-- Type Filter -->
                    <div class="relative">
                        <select name="type"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all appearance-none">
                            <option value="" data-translate>ประเภททั้งหมด</option>
                            <option value="hotel" {{ request('type') == 'hotel' ? 'selected' : '' }} data-translate>โรงแรม</option>
                            <option value="resort" {{ request('type') == 'resort' ? 'selected' : '' }} data-translate>รีสอร์ท</option>
                            <option value="hostel" {{ request('type') == 'hostel' ? 'selected' : '' }} data-translate>โฮสเทล</option>
                            <option value="villa" {{ request('type') == 'villa' ? 'selected' : '' }} data-translate>วิลล่า</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="relative">
                        <select name="status"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all appearance-none">
                            <option value="" data-translate>สถานะทั้งหมด</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }} data-translate>เปิดใช้งาน</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }} data-translate>ปิดใช้งาน</option>
                        </select>
                    </div>

                    <!-- Search Button -->
                    <button type="submit"
                            class="px-6 py-3 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span data-translate>ค้นหา</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Hotels Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-teal-600 via-cyan-600 to-blue-600 px-8 py-6">
                <h2 class="text-2xl font-bold text-white" data-translate>รายการโรงแรม</h2>
            </div>

            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>รูปภาพ</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ชื่อโรงแรม</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ที่ตั้ง</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ประเภท</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>คะแนน</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>ห้องพัก</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>สถานะ</th>
                                <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase" data-translate>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($hotels as $hotel)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <!-- Image -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-20 h-16 rounded-lg overflow-hidden shadow-md">
                                            <img src="{{ $hotel->main_image_url }}" alt="{{ $hotel->name }}"
                                                 class="w-full h-full object-cover">
                                        </div>
                                    </td>

                                    <!-- Name -->
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $hotel->name }}</span>
                                            <div class="flex items-center mt-1 space-x-2">
                                                @if($hotel->is_featured)
                                                    <span class="inline-flex px-2 py-1 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-xs font-bold rounded-lg">
                                                        ⭐ <span class="ml-1" data-translate>แนะนำ</span>
                                                    </span>
                                                @endif
                                                @if($hotel->star_rating)
                                                    <div class="flex items-center">
                                                        @for($i = 0; $i < $hotel->star_rating; $i++)
                                                            <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                                                <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                                            </svg>
                                                        @endfor
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Location -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <span class="text-sm text-gray-900 dark:text-gray-100">{{ $hotel->city }}</span>
                                        </div>
                                    </td>

                                    <!-- Type -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-3 py-1 bg-teal-100 dark:bg-teal-900/30 text-teal-800 dark:text-teal-300 rounded-full text-xs font-semibold">
                                            {{ ucfirst($hotel->type) }}
                                        </span>
                                    </td>

                                    <!-- Rating -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($hotel->rating > 0)
                                            <div class="flex items-center">
                                                <span class="inline-flex px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-semibold">
                                                    {{ number_format($hotel->rating, 1) }}
                                                </span>
                                                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">({{ $hotel->review_count }})</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-500 dark:text-gray-400" data-translate>ไม่มีรีวิว</span>
                                        @endif
                                    </td>

                                    <!-- Rooms -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $hotel->roomTypes->count() }} <span data-translate>ประเภท</span></span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ $hotel->roomTypes->sum('total_rooms') }} <span data-translate>ห้อง</span>)</span>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                   class="sr-only peer"
                                                   id="status{{ $hotel->id }}"
                                                   {{ $hotel->is_active ? 'checked' : '' }}
                                                   onchange="toggleStatus({{ $hotel->id }})">
                                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 dark:peer-focus:ring-teal-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-gradient-to-r peer-checked:from-green-500 peer-checked:to-emerald-600"></div>
                                        </label>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="{{ route('admin.hotels.show', $hotel->id) }}"
                                               class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                                               title="ดู">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </a>
                                            <a href="{{ route('admin.hotels.edit', $hotel->id) }}"
                                               class="p-2 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-colors"
                                               title="แก้ไข">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </a>
                                            <a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}"
                                               class="p-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors"
                                               title="ห้องพัก">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                                </svg>
                                            </a>
                                            <button onclick="deleteHotel({{ $hotel->id }})"
                                                    class="p-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                                    title="ลบ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                            </div>
                                            <p class="text-gray-500 dark:text-gray-400 text-lg font-medium" data-translate>ไม่พบข้อมูลโรงแรม</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $hotels->links() }}
                </div>
            </div>
        </div>

    </div>
</div>

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

@push('scripts')
<script>
function toggleStatus(hotelId) {
    if (confirm('คุณต้องการเปลี่ยนสถานะโรงแรมนี้?')) {
        fetch(`/admin/hotels/${hotelId}/toggle-status`, {
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
            }
        });
    } else {
        location.reload();
    }
}

function deleteHotel(hotelId) {
    if (confirm('คุณแน่ใจหรือว่าต้องการลบโรงแรมนี้? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/hotels/${hotelId}`;
        form.innerHTML = `
            @csrf
            @method('DELETE')
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection
