@extends('layouts.admin')

@section('title', 'รายละเอียด Tenant: ' . $tenant->tenant_name)

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.ai-core.tenants.index') }}"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-green-600 to-teal-600 dark:from-green-400 dark:to-teal-400 bg-clip-text text-transparent">
                        👥 {{ $tenant->tenant_name }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Tenant Key: <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-sm">{{ $tenant->tenant_key }}</code>
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('admin.ai-core.tenants.features', $tenant) }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    จัดการ Features
                </a>
                <a href="{{ route('admin.ai-core.tenants.edit', $tenant) }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    แก้ไข
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ℹ️ ข้อมูลพื้นฐาน</h2>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">ชื่อ Tenant</label>
                        <p class="text-lg text-gray-900 dark:text-white mt-1">{{ $tenant->tenant_name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">Tenant Key</label>
                        <p class="text-lg text-gray-900 dark:text-white mt-1 font-mono">{{ $tenant->tenant_key }}</p>
                    </div>
                    @if($tenant->description)
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">คำอธิบาย</label>
                            <p class="text-gray-700 dark:text-gray-300 mt-1">{{ $tenant->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Subscription Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">💎 Subscription</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">แผน</label>
                        <p class="text-lg mt-1">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold
                                {{ $tenant->subscription_tier === 'free' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                {{ $tenant->subscription_tier === 'basic' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                {{ $tenant->subscription_tier === 'pro' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                                {{ $tenant->subscription_tier === 'enterprise' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                                {{ strtoupper($tenant->subscription_tier) }}
                            </span>
                        </p>
                    </div>
                    @if($tenant->subscription_starts_at)
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">วันที่เริ่ม</label>
                            <p class="text-gray-900 dark:text-white mt-1">{{ $tenant->subscription_starts_at->format('d/m/Y') }}</p>
                        </div>
                    @endif
                    @if($tenant->subscription_ends_at)
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">วันหมดอายุ</label>
                            <p class="text-gray-900 dark:text-white mt-1">
                                {{ $tenant->subscription_ends_at->format('d/m/Y') }}
                                @if($tenant->subscription_ends_at->isFuture())
                                    <span class="text-xs text-green-600 dark:text-green-400">({{ $tenant->subscription_ends_at->diffForHumans() }})</span>
                                @else
                                    <span class="text-xs text-red-600 dark:text-red-400">(หมดอายุแล้ว)</span>
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Contact Info --}}
            @if($tenant->contact_name || $tenant->contact_email || $tenant->contact_phone)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📞 ข้อมูลติดต่อ</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($tenant->contact_name)
                            <div>
                                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">ชื่อ</label>
                                <p class="text-gray-900 dark:text-white mt-1">{{ $tenant->contact_name }}</p>
                            </div>
                        @endif
                        @if($tenant->contact_email)
                            <div>
                                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">อีเมล</label>
                                <p class="text-gray-900 dark:text-white mt-1">{{ $tenant->contact_email }}</p>
                            </div>
                        @endif
                        @if($tenant->contact_phone)
                            <div>
                                <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">เบอร์โทร</label>
                                <p class="text-gray-900 dark:text-white mt-1">{{ $tenant->contact_phone }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Features --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎯 Features</h2>
                <div class="grid grid-cols-2 gap-3">
                    @forelse($tenant->featureAccess()->with('feature')->active()->get() as $access)
                        <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <span class="text-xl">{{ $access->feature->icon }}</span>
                            <span class="text-sm text-gray-900 dark:text-white font-medium">{{ $access->feature->feature_name }}</span>
                        </div>
                    @empty
                        <div class="col-span-2 text-center text-gray-500 dark:text-gray-400 py-4">
                            ยังไม่มี Features
                        </div>
                    @endforelse
                </div>
                <a href="{{ route('admin.ai-core.tenants.features', $tenant) }}"
                   class="mt-4 inline-flex items-center gap-2 text-green-600 dark:text-green-400 hover:underline">
                    จัดการ Features →
                </a>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚡ สถานะ</h2>
                <div class="space-y-3">
                    <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            {{ $tenant->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                            {{ $tenant->status === 'inactive' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                            {{ $tenant->status === 'suspended' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                            {{ $tenant->status === 'expired' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                        @if($tenant->is_enabled)
                            <span class="text-green-600 dark:text-green-400">✅</span>
                        @else
                            <span class="text-gray-400">❌</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📊 สถิติ</h2>
                <div class="space-y-3">
                    <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-sm text-blue-600 dark:text-blue-400">Features</p>
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $tenant->featureAccess()->count() }}</p>
                    </div>
                    <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                        <p class="text-sm text-green-600 dark:text-green-400">Active</p>
                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $tenant->featureAccess()->active()->count() }}</p>
                    </div>
                    <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                        <p class="text-sm text-purple-600 dark:text-purple-400">Usage</p>
                        <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ number_format($tenant->usageLogs()->count()) }}</p>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-gradient-to-br from-green-50 to-teal-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl p-6 border-2 border-green-100 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚡ Quick Actions</h2>
                <div class="space-y-2">
                    <a href="{{ route('admin.ai-core.tenants.features', $tenant) }}"
                       class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors duration-150">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">🎯 จัดการ Features</span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <a href="{{ route('admin.ai-core.analytics.tenant', $tenant) }}"
                       class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors duration-150">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">📊 Analytics</span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
