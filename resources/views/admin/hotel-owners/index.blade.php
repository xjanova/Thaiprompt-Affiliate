@extends('layouts.admin-v3')

@section('title', 'จัดการเจ้าของโรงแรม')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Gradient Header --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-pink-600 to-rose-600 rounded-3xl shadow-2xl p-8 mb-8">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2">จัดการเจ้าของโรงแรม</h1>
                        <p class="text-purple-100">ระบบจัดการเจ้าของและผู้ดูแลโรงแรม</p>
                    </div>
                </div>
                <a href="{{ route('admin.hotel-owners.create') }}"
                   class="relative overflow-hidden group px-8 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center shadow-lg">
                    <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>เพิ่มเจ้าของโรงแรมใหม่</span>
                    </div>
                </a>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="mb-6 p-4 rounded-xl bg-green-100 dark:bg-green-900/30 border-2 border-green-500 text-green-800 dark:text-green-300">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="text-green-600 hover:text-green-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="mb-6 p-4 rounded-xl bg-red-100 dark:bg-red-900/30 border-2 border-red-500 text-red-800 dark:text-red-300">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Total Owners --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-2">
                        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-blue-100 text-sm mb-1">เจ้าของโรงแรมทั้งหมด</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['total']) }}</p>
                </div>
            </div>

            {{-- Active Owners --}}
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
                    <p class="text-green-100 text-sm mb-1">ใช้งานปกติ</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['active']) }}</p>
                </div>
            </div>

            {{-- Blocked Owners --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-2">
                        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-red-100 text-sm mb-1">ถูกบล็อก</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['blocked']) }}</p>
                </div>
            </div>

            {{-- With Hotel --}}
            <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white transform hover:scale-105 transition-all">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative">
                    <div class="flex items-center justify-between mb-2">
                        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-purple-100 text-sm mb-1">มีโรงแรมดูแล</p>
                    <p class="text-3xl font-bold">{{ number_format($stats['with_hotel']) }}</p>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <form method="GET" action="{{ route('admin.hotel-owners.index') }}">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Search --}}
                    <div class="relative md:col-span-2">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all"
                               placeholder="ค้นหาชื่อ, อีเมล, เบอร์โทร...">
                    </div>

                    {{-- Status Filter --}}
                    <div class="relative">
                        <select name="status"
                                class="w-full px-4 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all appearance-none">
                            <option value="">สถานะทั้งหมด</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>ใช้งานปกติ</option>
                            <option value="blocked" {{ request('status') == 'blocked' ? 'selected' : '' }}>ถูกบล็อก</option>
                            <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>ยืนยันอีเมลแล้ว</option>
                            <option value="unverified" {{ request('status') == 'unverified' ? 'selected' : '' }}>ยังไม่ยืนยันอีเมล</option>
                        </select>
                    </div>

                    {{-- Submit Button --}}
                    <div class="flex gap-2">
                        <button type="submit"
                                class="flex-1 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            ค้นหา
                        </button>
                        @if(request()->hasAny(['search', 'status']))
                            <a href="{{ route('admin.hotel-owners.index') }}"
                               class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold transition-all">
                                ล้าง
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- Hotel Owners Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                เจ้าของโรงแรม
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                ติดต่อ
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                โรงแรมที่ดูแล
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                สถิติ
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                จัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($hotelOwners as $owner)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                {{-- Owner Info --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            <div class="h-12 w-12 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center text-white font-bold text-lg">
                                                {{ strtoupper(substr($owner->name, 0, 2)) }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $owner->name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                ID: {{ $owner->id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Contact --}}
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-gray-300">{{ $owner->email }}</div>
                                    @if($owner->phone)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $owner->phone }}</div>
                                    @endif
                                </td>

                                {{-- Managed Hotel --}}
                                <td class="px-6 py-4">
                                    @if($owner->managedHotel)
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $owner->managedHotel->name }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $owner->managedHotel->city }}
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500 italic">ยังไม่มีโรงแรม</span>
                                    @endif
                                </td>

                                {{-- Statistics --}}
                                <td class="px-6 py-4">
                                    @if($owner->statistics)
                                        <div class="text-xs space-y-1">
                                            <div class="text-gray-700 dark:text-gray-300">
                                                <span class="font-medium">การจอง:</span> {{ number_format($owner->statistics['total_bookings']) }}
                                            </div>
                                            <div class="text-gray-700 dark:text-gray-300">
                                                <span class="font-medium">รายได้:</span> ฿{{ number_format($owner->statistics['total_revenue'], 2) }}
                                            </div>
                                            <div class="text-gray-700 dark:text-gray-300">
                                                <span class="font-medium">คะแนน:</span> ⭐ {{ $owner->statistics['average_rating'] }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4">
                                    @if($owner->blocked_at)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                            ถูกบล็อก
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            ใช้งานปกติ
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- View --}}
                                        <a href="{{ route('admin.hotel-owners.show', $owner->id) }}"
                                           class="inline-flex items-center px-3 py-2 bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-lg transition-all"
                                           title="ดูรายละเอียด">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        {{-- Edit --}}
                                        <a href="{{ route('admin.hotel-owners.edit', $owner->id) }}"
                                           class="inline-flex items-center px-3 py-2 bg-yellow-100 dark:bg-yellow-900/30 hover:bg-yellow-200 dark:hover:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 rounded-lg transition-all"
                                           title="แก้ไข">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>

                                        {{-- Delete --}}
                                        @if(!$owner->managedHotel)
                                            <form method="POST" action="{{ route('admin.hotel-owners.destroy', $owner->id) }}"
                                                  onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบเจ้าของโรงแรมนี้?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center px-3 py-2 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-700 dark:text-red-300 rounded-lg transition-all"
                                                        title="ลบ">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <p class="text-lg font-medium">ไม่พบข้อมูลเจ้าของโรงแรม</p>
                                        <p class="text-sm mt-2">ลองค้นหาด้วยคำค้นอื่น หรือเพิ่มเจ้าของโรงแรมใหม่</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($hotelOwners->hasPages())
                <div class="bg-gray-50 dark:bg-gray-900/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $hotelOwners->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
