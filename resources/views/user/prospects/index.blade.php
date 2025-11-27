@extends('layouts.user-arrow-x')

@section('title', 'ผู้มุ่งหวังของฉัน')

@section('content')
{{-- แสดงข้อความแนะนำถ้าไม่ได้เป็น MLM Member --}}
@if(isset($notMlmMember) && $notMlmMember)
<div class="mb-6">
    <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-2xl shadow-xl p-8 text-white">
        <div class="flex flex-col md:flex-row items-center gap-6">
            <div class="flex-shrink-0">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            <div class="flex-1 text-center md:text-left">
                <h2 class="text-2xl font-bold mb-2">คุณยังไม่ได้เป็นสมาชิก MLM</h2>
                <p class="text-amber-100 mb-4">เพื่อใช้งานฟีเจอร์ผู้มุ่งหวังและสร้างทีมงาน กรุณาสมัครเป็นสมาชิก MLM ก่อน</p>
                <a href="{{ route('user.mlm.dashboard') }}"
                   class="inline-flex items-center px-6 py-3 bg-white text-amber-600 font-bold rounded-xl hover:bg-amber-50 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                    ไปหน้า MLM Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
@endif

<div class="space-y-6" x-data="prospectsManager()">
    {{-- Header Section with Gradient --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 dark:from-purple-700 dark:via-indigo-700 dark:to-blue-700 rounded-2xl shadow-xl p-6 md:p-8">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white flex items-center">
                    <span class="bg-white/20 rounded-lg p-2 mr-3">
                        <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </span>
                    ผู้มุ่งหวังของฉัน
                </h1>
                <p class="mt-2 text-purple-100 text-sm md:text-base">ติดตามและจัดการผู้ที่คุณเชิญให้สมัครผ่าน LINE</p>
            </div>
            <a href="{{ route('user.prospects.create') }}"
               class="inline-flex items-center justify-center px-6 py-3 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-indigo-700 dark:text-indigo-300 font-bold rounded-xl transition-all shadow-lg hover:shadow-xl hover:scale-105 transform">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                สร้างลิงก์เชิญใหม่
            </a>
        </div>
    </div>

    {{-- Stats Cards with 3D Effect --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Total --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 hover:shadow-xl transition-all hover:-translate-y-1 transform border-l-4 border-blue-500">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-3 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">ทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['total']) }}</p>
                </div>
            </div>
        </div>

        {{-- Pending --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 hover:shadow-xl transition-all hover:-translate-y-1 transform border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-gradient-to-br from-yellow-500 to-amber-500 rounded-xl p-3 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">รอดำเนินการ</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['pending']) }}</p>
                </div>
            </div>
        </div>

        {{-- In Progress --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 hover:shadow-xl transition-all hover:-translate-y-1 transform border-l-4 border-indigo-500">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-xl p-3 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">กำลังดำเนินการ</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['in_progress']) }}</p>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 hover:shadow-xl transition-all hover:-translate-y-1 transform border-l-4 border-green-500">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl p-3 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">สำเร็จ</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['completed']) }}</p>
                </div>
            </div>
        </div>

        {{-- Expired --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-5 hover:shadow-xl transition-all hover:-translate-y-1 transform border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-gradient-to-br from-red-500 to-rose-500 rounded-xl p-3 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">หมดอายุ</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['expired'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Conversion Rate Card --}}
    @if($stats['total'] > 0)
    <div class="bg-gradient-to-r from-emerald-500 to-teal-500 dark:from-emerald-600 dark:to-teal-600 rounded-xl shadow-lg p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center">
                <div class="bg-white/20 rounded-xl p-3 mr-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <p class="text-emerald-100 text-sm font-medium">อัตราการแปลง (Conversion Rate)</p>
                    <p class="text-3xl font-bold text-white">
                        {{ number_format(($stats['completed'] / $stats['total']) * 100, 1) }}%
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-6">
                <div class="text-center">
                    <p class="text-emerald-100 text-xs">เชิญแล้ว</p>
                    <p class="text-xl font-bold text-white">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="text-2xl text-white/50">→</div>
                <div class="text-center">
                    <p class="text-emerald-100 text-xs">สมัครแล้ว</p>
                    <p class="text-xl font-bold text-white">{{ number_format($stats['completed']) }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Filter & Search --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <form method="GET" action="{{ route('user.prospects.index') }}" class="flex flex-col md:flex-row md:items-end gap-4">
            {{-- Search --}}
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    ค้นหา
                </label>
                <input type="text"
                       name="search"
                       id="search"
                       value="{{ request('search') }}"
                       placeholder="ค้นหาด้วยชื่อ LINE หรือ Token..."
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 transition">
            </div>

            {{-- Status Filter --}}
            <div class="md:w-48">
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    สถานะ
                </label>
                <select name="status"
                        id="status"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 transition">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอดำเนินการ</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>สำเร็จ</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>หมดอายุ</option>
                </select>
            </div>

            {{-- Date Range --}}
            <div class="md:w-40">
                <label for="date_from" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    จากวันที่
                </label>
                <input type="date"
                       name="date_from"
                       id="date_from"
                       value="{{ request('date_from') }}"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 transition">
            </div>

            <div class="md:w-40">
                <label for="date_to" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ถึงวันที่</label>
                <input type="date"
                       name="date_to"
                       id="date_to"
                       value="{{ request('date_to') }}"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 transition">
            </div>

            {{-- Buttons --}}
            <div class="flex items-end space-x-2">
                <button type="submit"
                        class="px-6 py-3 bg-indigo-600 dark:bg-indigo-500 text-white font-semibold rounded-xl hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    กรอง
                </button>
                <a href="{{ route('user.prospects.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-500 transition-all">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    {{-- Prospects List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        @if($prospects->count() > 0)
            {{-- Desktop Table --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                วันที่สร้าง
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                ข้อมูล LINE
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                สถานะ
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                ผู้ใช้ที่สมัครแล้ว
                            </th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                การดำเนินการ
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($prospects as $prospect)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                {{-- Created Date --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <div>
                                            <div class="font-medium">{{ $prospect->created_at->format('d/m/Y') }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $prospect->created_at->format('H:i น.') }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- LINE Info --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12">
                                            @if($prospect->line_profile_picture)
                                                <img class="h-12 w-12 rounded-full ring-2 ring-green-400" src="{{ $prospect->line_profile_picture }}" alt="{{ $prospect->line_display_name }}">
                                            @else
                                                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center ring-2 ring-gray-300 dark:ring-gray-600">
                                                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $prospect->line_display_name ?? 'รอการคลิกลิงก์' }}
                                            </div>
                                            @if($prospect->line_user_id)
                                                <div class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                                    {{ Str::limit($prospect->line_user_id, 20) }}
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-400 dark:text-gray-500 italic">
                                                    ยังไม่ได้เริ่มสมัคร
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusConfig = [
                                            'pending' => ['color' => 'yellow', 'bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-800 dark:text-yellow-300', 'label' => 'รอดำเนินการ', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                                            'in_progress' => ['color' => 'indigo', 'bg' => 'bg-indigo-100 dark:bg-indigo-900/30', 'text' => 'text-indigo-800 dark:text-indigo-300', 'label' => 'กำลังดำเนินการ', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                                            'completed' => ['color' => 'green', 'bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-800 dark:text-green-300', 'label' => 'สำเร็จ', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                                            'expired' => ['color' => 'red', 'bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-800 dark:text-red-300', 'label' => 'หมดอายุ', 'icon' => 'M6 18L18 6M6 6l12 12'],
                                        ];
                                        $config = $statusConfig[$prospect->status] ?? ['bg' => 'bg-gray-100 dark:bg-gray-700', 'text' => 'text-gray-800 dark:text-gray-300', 'label' => $prospect->status, 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold {{ $config['bg'] }} {{ $config['text'] }}">
                                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}" />
                                        </svg>
                                        {{ $config['label'] }}
                                    </span>
                                    @if($prospect->expires_at && $prospect->status !== 'completed')
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            หมดอายุ: {{ \Carbon\Carbon::parse($prospect->expires_at)->format('d/m/Y') }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Registered User --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($prospect->registeredUser)
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-8 w-8 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                                {{ substr($prospect->registeredUser->name, 0, 1) }}
                                            </div>
                                            <div class="ml-3">
                                                <div class="text-gray-900 dark:text-gray-100 font-medium">{{ $prospect->registeredUser->name }}</div>
                                                <div class="text-xs text-green-600 dark:text-green-400 flex items-center">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                    สมัครเรียบร้อย
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500 italic">ยังไม่สมัคร</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <a href="{{ route('user.prospects.show', $prospect->id) }}"
                                           class="inline-flex items-center px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition text-sm font-medium">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            ดู
                                        </a>

                                        @if(in_array($prospect->status, ['pending', 'expired']))
                                            <form method="POST" action="{{ route('user.prospects.destroy', $prospect->id) }}" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบลิงก์เชิญนี้?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm font-medium">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    ลบ
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($prospects as $prospect)
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-12">
                                    @if($prospect->line_profile_picture)
                                        <img class="h-12 w-12 rounded-full ring-2 ring-green-400" src="{{ $prospect->line_profile_picture }}" alt="{{ $prospect->line_display_name }}">
                                    @else
                                        <div class="h-12 w-12 rounded-full bg-gradient-to-br from-gray-300 to-gray-400 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $prospect->line_display_name ?? 'รอการคลิกลิงก์' }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $prospect->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            </div>
                            @php
                                $statusConfig = [
                                    'pending' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-800 dark:text-yellow-300', 'label' => 'รอ'],
                                    'in_progress' => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/30', 'text' => 'text-indigo-800 dark:text-indigo-300', 'label' => 'กำลังดำเนินการ'],
                                    'completed' => ['bg' => 'bg-green-100 dark:bg-green-900/30', 'text' => 'text-green-800 dark:text-green-300', 'label' => 'สำเร็จ'],
                                    'expired' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-800 dark:text-red-300', 'label' => 'หมดอายุ'],
                                ];
                                $config = $statusConfig[$prospect->status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'label' => $prospect->status];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $config['bg'] }} {{ $config['text'] }}">
                                {{ $config['label'] }}
                            </span>
                        </div>

                        @if($prospect->registeredUser)
                            <div class="mb-3 p-2 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div class="flex items-center text-sm text-green-700 dark:text-green-300">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    สมัครแล้ว: {{ $prospect->registeredUser->name }}
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('user.prospects.show', $prospect->id) }}"
                               class="flex-1 text-center px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition text-sm">
                                ดูรายละเอียด
                            </a>
                            @if(in_array($prospect->status, ['pending', 'expired']))
                                <form method="POST" action="{{ route('user.prospects.destroy', $prospect->id) }}" class="inline" onsubmit="return confirm('ลบลิงก์เชิญนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 font-medium rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition text-sm">
                                        ลบ
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($prospects->hasPages())
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                    {{ $prospects->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="text-center py-16 px-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-indigo-100 to-purple-100 dark:from-indigo-900/30 dark:to-purple-900/30 rounded-full mb-6">
                    <svg class="w-10 h-10 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">ยังไม่มีผู้มุ่งหวัง</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md mx-auto">
                    เริ่มต้นสร้างเครือข่ายของคุณโดยการสร้างลิงก์เชิญ แล้วแชร์ให้เพื่อนของคุณ!
                </p>
                <a href="{{ route('user.prospects.create') }}"
                   class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 dark:from-indigo-500 dark:to-purple-500 text-white text-lg font-bold rounded-xl transition-all shadow-lg hover:shadow-xl hover:scale-105 transform">
                    <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    สร้างลิงก์เชิญคนแรก
                </a>
            </div>
        @endif
    </div>

    {{-- Quick Tips --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            เคล็ดลับเพิ่ม Conversion Rate
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex items-start">
                <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold mr-3">1</span>
                <div>
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">ส่งลิงก์พร้อมข้อความส่วนตัว</p>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">เพิ่มโอกาสในการตอบรับถึง 40%</p>
                </div>
            </div>
            <div class="flex items-start">
                <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold mr-3">2</span>
                <div>
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">ติดตามผู้มุ่งหวังที่ยังไม่ลงทะเบียน</p>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">ส่งข้อความเตือนภายใน 24-48 ชม.</p>
                </div>
            </div>
            <div class="flex items-start">
                <span class="flex-shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white text-sm font-bold mr-3">3</span>
                <div>
                    <p class="text-sm font-medium text-blue-900 dark:text-blue-100">ต่ออายุลิงก์ที่หมดอายุ</p>
                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-1">อย่าปล่อยให้ผู้มุ่งหวังหลุดไป</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function prospectsManager() {
    return {
        // สามารถเพิ่ม state และ methods ได้ตามต้องการ
        init() {
            // Initialize
        }
    }
}
</script>
@endsection
