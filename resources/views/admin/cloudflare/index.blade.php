@extends('layouts.admin-v3')

@section('title', 'Cloudflare Management')

@section('content')
<div class="container-fluid px-4 py-6" x-data="cloudflareManager()">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 via-orange-600 to-yellow-500 dark:from-orange-600 dark:via-orange-700 dark:to-yellow-600 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl backdrop-blur-sm flex items-center justify-center bg-white/20">
                        <!-- Cloudflare Logo -->
                        <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16.5 7.5c-.3 0-.6.1-.9.2l-.2-.5c-.3-.8-1.1-1.3-2-1.3h-.8c-.1 0-.2.1-.2.2l-.1.6c-.1.4-.5.7-.9.7h-1.5c-.4 0-.8-.3-.9-.7l-.1-.6c0-.1-.1-.2-.2-.2h-.8c-.9 0-1.7.5-2 1.3l-.2.5c-.3-.1-.6-.2-.9-.2-1.4 0-2.5 1.1-2.5 2.5S3.1 12.5 4.5 12.5c.3 0 .6-.1.9-.2l.2.5c.3.8 1.1 1.3 2 1.3h8.8c.9 0 1.7-.5 2-1.3l.2-.5c.3.1.6.2.9.2 1.4 0 2.5-1.1 2.5-2.5S17.9 7.5 16.5 7.5z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">Cloudflare Management</h1>
                        <p class="text-orange-100">จัดการ CDN, Cache, DNS, Security และอื่นๆ</p>
                    </div>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-3">
                @if($isConfigured)
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        เชื่อมต่อแล้ว
                    </span>
                @else
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold bg-red-100 text-red-800">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        ยังไม่ได้ตั้งค่า
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if(!$isConfigured)
    <!-- Not Configured Warning -->
    <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 p-4 rounded-xl">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="ml-3">
                <h4 class="text-yellow-800 dark:text-yellow-200 font-semibold mb-1">ยังไม่ได้ตั้งค่า Cloudflare</h4>
                <p class="text-yellow-700 dark:text-yellow-300 text-sm">
                    กรุณาเพิ่ม <code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">CLOUDFLARE_ZONE_ID</code> และ
                    <code class="bg-yellow-100 dark:bg-yellow-800 px-1 rounded">CLOUDFLARE_API_TOKEN</code> ใน .env
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Purge Cache -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-pink-500 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded-full">Quick Action</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Purge All Cache</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">ล้าง cache ทั้งหมดของ Cloudflare</p>
            <button @click="purgeAllCache()"
                    :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                    class="w-full px-4 py-2 bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!loading">Purge Cache ทั้งหมด</span>
                <span x-show="loading" class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    กำลังดำเนินการ...
                </span>
            </button>
        </div>

        <!-- Development Mode -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <span class="text-xs font-medium {{ $developmentMode === 'on' ? 'text-green-600 bg-green-100 dark:bg-green-900/50' : 'text-gray-500 bg-gray-100 dark:bg-slate-700' }} px-2 py-1 rounded-full">
                    {{ $developmentMode === 'on' ? 'ON' : 'OFF' }}
                </span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Development Mode</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">ปิด cache ชั่วคราว (3 ชม.)</p>
            <button @click="toggleDevMode()"
                    :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                    class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-cyan-500 hover:from-blue-600 hover:to-cyan-600 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                {{ $developmentMode === 'on' ? 'ปิด' : 'เปิด' }} Development Mode
            </button>
        </div>

        <!-- Under Attack Mode -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium {{ $securityLevel === 'under_attack' ? 'text-red-600 bg-red-100 dark:bg-red-900/50' : 'text-gray-500 bg-gray-100 dark:bg-slate-700' }} px-2 py-1 rounded-full">
                    {{ $securityLevel === 'under_attack' ? 'ACTIVE' : 'INACTIVE' }}
                </span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Under Attack Mode</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">ป้องกัน DDoS แบบเข้มงวด</p>
            @if($securityLevel === 'under_attack')
                <button @click="disableUnderAttack()"
                        :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                        class="w-full px-4 py-2 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    ปิด Under Attack Mode
                </button>
            @else
                <button @click="enableUnderAttack()"
                        :disabled="loading || !{{ $isConfigured ? 'true' : 'false' }}"
                        class="w-full px-4 py-2 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white rounded-lg font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    เปิด Under Attack Mode
                </button>
            @endif
        </div>

        <!-- Security Level -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6 hover:shadow-xl transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-green-600 bg-green-100 dark:bg-green-900/50 px-2 py-1 rounded-full uppercase">
                    {{ $securityLevel ?? 'medium' }}
                </span>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Security Level</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">ระดับความปลอดภัยปัจจุบัน</p>
            <a href="{{ route('admin.cloudflare.security') }}"
               class="w-full inline-block text-center px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-600 hover:to-emerald-600 text-white rounded-lg font-medium transition-all">
                จัดการ Security
            </a>
        </div>
    </div>

    <!-- Zone Info & Quick Links -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Zone Info -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                </svg>
                ข้อมูล Zone
            </h3>
            @if($zoneInfo)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Domain</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $zoneInfo['name'] ?? '-' }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Zone ID</p>
                        <p class="text-sm font-mono text-gray-900 dark:text-white truncate">{{ $zoneInfo['id'] ?? '-' }}</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($zoneInfo['status'] ?? '') === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $zoneInfo['status'] ?? '-' }}
                        </span>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Plan</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $zoneInfo['plan']['name'] ?? 'Free' }}</p>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">ไม่สามารถดึงข้อมูล Zone ได้</p>
                </div>
            @endif
        </div>

        <!-- Quick Links -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Links
            </h3>
            <div class="space-y-3">
                {{-- One-Click Optimization (Featured) --}}
                <a href="{{ route('admin.cloudflare.optimization') }}" class="flex items-center p-3 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-lg hover:from-purple-600 hover:to-indigo-600 transition-all shadow-lg">
                    <svg class="w-5 h-5 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span class="text-white font-semibold">One-Click Optimize</span>
                    <span class="ml-auto text-xs bg-white/20 text-white px-2 py-0.5 rounded-full">NEW</span>
                </a>

                {{-- Auto Under Attack Mode --}}
                <a href="{{ route('admin.cloudflare.auto-under-attack') }}" class="flex items-center p-3 bg-gradient-to-r from-red-600 to-rose-600 rounded-lg hover:from-red-700 hover:to-rose-700 transition-all shadow-lg">
                    <svg class="w-5 h-5 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span class="text-white font-semibold">Auto Under Attack</span>
                    <span class="ml-auto text-xs bg-white/20 text-white px-2 py-0.5 rounded-full">NEW</span>
                </a>
                <a href="{{ route('admin.cloudflare.cache') }}" class="flex items-center p-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                    <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">จัดการ Cache</span>
                </a>
                <a href="{{ route('admin.cloudflare.dns') }}" class="flex items-center p-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                    <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">จัดการ DNS</span>
                </a>
                <a href="{{ route('admin.cloudflare.security') }}" class="flex items-center p-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">จัดการ Security</span>
                </a>
                <a href="{{ route('admin.cloudflare.analytics') }}" class="flex items-center p-3 bg-gray-50 dark:bg-slate-700 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                    <svg class="w-5 h-5 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">Analytics</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Result Message -->
    <div x-show="message"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         :class="messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 border-green-500' : 'bg-red-50 dark:bg-red-900/30 border-red-500'"
         class="fixed bottom-4 right-4 max-w-sm border-l-4 p-4 rounded-xl shadow-lg">
        <div class="flex items-center">
            <template x-if="messageType === 'success'">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </template>
            <template x-if="messageType === 'error'">
                <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            </template>
            <span :class="messageType === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'" x-text="message"></span>
        </div>
    </div>
</div>

@push('scripts')
<script>
function cloudflareManager() {
    return {
        loading: false,
        message: '',
        messageType: 'success',

        showMessage(msg, type = 'success') {
            this.message = msg;
            this.messageType = type;
            setTimeout(() => {
                this.message = '';
            }, 5000);
        },

        async purgeAllCache() {
            if (!confirm('คุณต้องการล้าง cache ทั้งหมดใช่หรือไม่?')) return;

            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.cloudflare.purge-all") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.showMessage(data.message || 'Purge cache สำเร็จ!', 'success');
                } else {
                    this.showMessage(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        },

        async toggleDevMode() {
            this.loading = true;
            const currentMode = '{{ $developmentMode }}';
            const newMode = currentMode === 'on' ? false : true;

            try {
                const response = await fetch('{{ route("admin.cloudflare.toggle-dev-mode") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ enabled: newMode })
                });
                const data = await response.json();

                if (data.success) {
                    this.showMessage(data.message || 'เปลี่ยนสถานะสำเร็จ!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showMessage(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        },

        async enableUnderAttack() {
            if (!confirm('คุณต้องการเปิด Under Attack Mode ใช่หรือไม่? ผู้ใช้จะต้องรอ JavaScript Challenge ก่อนเข้าถึงเว็บไซต์')) return;

            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.cloudflare.enable-under-attack") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.showMessage(data.message || 'เปิด Under Attack Mode สำเร็จ!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showMessage(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        },

        async disableUnderAttack() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.cloudflare.disable-under-attack") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.showMessage(data.message || 'ปิด Under Attack Mode สำเร็จ!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    this.showMessage(data.message || 'เกิดข้อผิดพลาด', 'error');
                }
            } catch (error) {
                this.showMessage('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
            this.loading = false;
        }
    }
}
</script>
@endpush
@endsection
