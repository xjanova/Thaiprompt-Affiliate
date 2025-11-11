@extends('layouts.admin')

@section('title', 'รายละเอียดผู้เข้าชม - Cookie Analytics')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-cyan-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 py-8 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header Section -->
        <div class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center text-white font-bold shadow-lg text-xl">
                            {{ substr($visitor->session_id ?? 'U', 0, 1) }}
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-400 dark:to-cyan-400 bg-clip-text text-transparent">
                                รายละเอียดผู้เข้าชม
                            </h1>
                            <p class="mt-1 text-slate-600 dark:text-slate-400">
                                Session: <span class="font-mono text-sm">{{ Str::limit($visitor->session_id, 24) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.cookie-analytics.visitors') }}"
                   class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg shadow-sm hover:shadow-md transition-all duration-200 text-slate-700 dark:text-slate-200 hover:scale-105">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    กลับไปรายการ
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">

                <!-- User Info Card -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-up transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            ข้อมูลผู้ใช้
                        </h2>
                        @if($visitor->user)
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-semibold rounded-full">
                            สมาชิก
                        </span>
                        @else
                        <span class="px-3 py-1 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-full">
                            ผู้เยี่ยมชม
                        </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">ชื่อผู้ใช้</label>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                    {{ $visitor->user ? $visitor->user->name : 'Guest User' }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">อีเมล</label>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                    {{ $visitor->user ? $visitor->user->email : 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">IP Address</label>
                                <p class="text-sm font-mono font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                    {{ $visitor->ip_address ?? 'Unknown' }}
                                </p>
                            </div>
                            <div>
                                <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">User ID</label>
                                <p class="text-sm font-mono font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                    {{ $visitor->user_id ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Device & Browser Info -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-up transition-all duration-300" style="animation-delay: 0.1s">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        อุปกรณ์และเบราว์เซอร์
                    </h2>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                    @if($visitor->device_type == 'desktop')
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    @elseif($visitor->device_type == 'mobile')
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">ประเภทอุปกรณ์</label>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1 capitalize">
                                        {{ $visitor->device_type ?? 'Unknown' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">เบราว์เซอร์</label>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                        {{ $visitor->browser ?? 'Unknown Browser' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">ระบบปฏิบัติการ</label>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                        {{ $visitor->os ?? 'Unknown OS' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start space-x-3">
                                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">ความละเอียดหน้าจอ</label>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                        {{ $visitor->screen_resolution ?? 'Unknown' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location & Geolocation -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-up transition-all duration-300" style="animation-delay: 0.2s">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        ที่ตั้งและพื้นที่ภูมิศาสตร์
                    </h2>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">ประเทศ</p>
                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                {{ $visitor->country ?? 'Unknown' }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-cyan-50 to-cyan-100 dark:from-cyan-900/20 dark:to-cyan-800/20 rounded-lg">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">เมือง</p>
                            <p class="text-lg font-bold text-cyan-600 dark:text-cyan-400">
                                {{ $visitor->city ?? 'Unknown' }}
                            </p>
                        </div>
                        <div class="text-center p-4 bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-lg">
                            <p class="text-xs text-slate-600 dark:text-slate-400 mb-1">ภาษา</p>
                            <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                {{ $visitor->language ?? 'Unknown' }}
                            </p>
                        </div>
                    </div>

                    <!-- Map Placeholder -->
                    <div class="bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 rounded-lg h-64 flex items-center justify-center border border-slate-300 dark:border-slate-600">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto text-slate-400 dark:text-slate-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            <p class="text-slate-500 dark:text-slate-400 font-medium">แผนที่ภูมิศาสตร์</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Map integration placeholder</p>
                        </div>
                    </div>
                </div>

                <!-- Tracking Timeline -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-up transition-all duration-300" style="animation-delay: 0.3s">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-6 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        ไทม์ไลน์การติดตาม
                    </h2>

                    <div class="space-y-4">
                        <!-- Timeline Item -->
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border-l-4 border-blue-500">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">เซสชั่นเริ่มต้น</h3>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $visitor->created_at ? $visitor->created_at->format('H:i:s') : '-' }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    ผู้เข้าชมเริ่มเซสชั่นใหม่จาก {{ $visitor->referrer_domain ?? 'Direct' }}
                                </p>
                            </div>
                        </div>

                        @if($visitor->page_views > 1)
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 bg-cyan-50 dark:bg-cyan-900/20 rounded-lg p-4 border-l-4 border-cyan-500">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">เข้าชมหลายหน้า</h3>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $visitor->page_views }} หน้า
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    ผู้เข้าชมเข้าดูทั้งหมด {{ $visitor->page_views }} หน้าในเซสชั่นนี้
                                </p>
                            </div>
                        </div>
                        @endif

                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border-l-4 border-green-500">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">กิจกรรมล่าสุด</h3>
                                    <span class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ $visitor->last_activity_at ? $visitor->last_activity_at->diffForHumans() : '-' }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400">
                                    ใช้เวลาทั้งหมด {{ gmdate('i:s', $visitor->session_duration ?? 0) }} นาที
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="space-y-6">

                <!-- Session Stats -->
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-right transition-all duration-300">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        สถิติเซสชั่น
                    </h2>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg">
                            <span class="text-sm text-slate-700 dark:text-slate-300">จำนวนหน้าที่เข้าชม</span>
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $visitor->page_views ?? 0 }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-cyan-50 to-cyan-100 dark:from-cyan-900/20 dark:to-cyan-800/20 rounded-lg">
                            <span class="text-sm text-slate-700 dark:text-slate-300">ระยะเวลา</span>
                            <span class="text-lg font-bold text-cyan-600 dark:text-cyan-400">{{ gmdate('i:s', $visitor->session_duration ?? 0) }}</span>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 rounded-lg">
                            <span class="text-sm text-slate-700 dark:text-slate-300">Bounce Rate</span>
                            <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $visitor->page_views > 1 ? '0%' : '100%' }}</span>
                        </div>
                    </div>
                </div>

                <!-- UTM Parameters -->
                @if($visitor->utm_source || $visitor->utm_medium || $visitor->utm_campaign)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-right transition-all duration-300" style="animation-delay: 0.1s">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        UTM Parameters
                    </h2>

                    <div class="space-y-3">
                        @if($visitor->utm_source)
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">Source</label>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                {{ $visitor->utm_source }}
                            </p>
                        </div>
                        @endif

                        @if($visitor->utm_medium)
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">Medium</label>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                {{ $visitor->utm_medium }}
                            </p>
                        </div>
                        @endif

                        @if($visitor->utm_campaign)
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">Campaign</label>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                {{ $visitor->utm_campaign }}
                            </p>
                        </div>
                        @endif

                        @if($visitor->utm_term)
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">Term</label>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                {{ $visitor->utm_term }}
                            </p>
                        </div>
                        @endif

                        @if($visitor->utm_content)
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">Content</label>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1">
                                {{ $visitor->utm_content }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Interests Detected -->
                @if($visitor->interests_detected && count($visitor->interests_detected) > 0)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-right transition-all duration-300" style="animation-delay: 0.2s">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                        ความสนใจที่ตรวจพบ
                    </h2>

                    <div class="flex flex-wrap gap-2">
                        @foreach($visitor->interests_detected as $interest)
                        <span class="px-3 py-1.5 bg-gradient-to-r from-yellow-100 to-orange-100 dark:from-yellow-900/30 dark:to-orange-900/30 text-yellow-700 dark:text-yellow-400 text-xs font-semibold rounded-full shadow-sm">
                            {{ $interest }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Referrer Info -->
                @if($visitor->referrer_domain)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6 border border-slate-200 dark:border-slate-700 animate-slide-right transition-all duration-300" style="animation-delay: 0.3s">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        แหล่งที่มา
                    </h2>

                    <div class="space-y-3">
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">Referrer Domain</label>
                            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100 mt-1 break-all">
                                {{ $visitor->referrer_domain }}
                            </p>
                        </div>

                        @if($visitor->referrer_url)
                        <div>
                            <label class="text-xs text-slate-500 dark:text-slate-400 font-medium">Full URL</label>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 break-all">
                                {{ Str::limit($visitor->referrer_url, 80) }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

            </div>
        </div>

    </div>
</div>

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slide-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slide-right {
    from { opacity: 0; transform: translateX(20px); }
    to { opacity: 1; transform: translateX(0); }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out forwards;
}

.animate-slide-up {
    animation: slide-up 0.6s ease-out forwards;
}

.animate-slide-right {
    animation: slide-right 0.6s ease-out forwards;
}
</style>
@endsection
