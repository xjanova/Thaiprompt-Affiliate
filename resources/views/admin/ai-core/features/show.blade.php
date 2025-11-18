@extends('layouts.admin')

@section('title', 'รายละเอียด Feature: ' . $feature->feature_name)

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.ai-core.features.index') }}"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent flex items-center gap-2">
                        <span class="text-4xl">{{ $feature->icon }}</span>
                        {{ $feature->feature_name }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Feature Key: <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-sm">{{ $feature->feature_key }}</code>
                    </p>
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('admin.ai-core.features.edit', $feature) }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    แก้ไข Feature
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Information --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">ℹ️</span>
                    ข้อมูลพื้นฐาน
                </h2>

                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">📝 ชื่อ Feature</label>
                        <p class="text-lg text-gray-900 dark:text-white mt-1">{{ $feature->feature_name }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">🔑 Feature Key</label>
                        <p class="text-lg text-gray-900 dark:text-white mt-1 font-mono bg-gray-50 dark:bg-gray-700 px-3 py-2 rounded-lg inline-block">
                            {{ $feature->feature_key }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">📂 หมวดหมู่</label>
                        <p class="text-lg text-gray-900 dark:text-white mt-1">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                {{ $feature->category === 'marketplace' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                {{ $feature->category === 'generation' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                                {{ $feature->category === 'integration' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                {{ $feature->category === 'automation' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400' : '' }}
                                {{ $feature->category === 'analytics' ? 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400' : '' }}
                                {{ $feature->category === 'trading' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                {{ $feature->category === 'platform' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' : '' }}">
                                {{ ucfirst($feature->category) }}
                            </span>
                        </p>
                    </div>

                    @if($feature->description)
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">📄 คำอธิบาย</label>
                            <p class="text-gray-700 dark:text-gray-300 mt-1 leading-relaxed">
                                {{ $feature->description }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quota Configuration --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">📊</span>
                    การจัดการ Quota
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">📈 ประเภท Quota</label>
                        <p class="text-lg text-gray-900 dark:text-white mt-1">
                            @if($feature->quota_type === 'none')
                                <span class="text-gray-500">ไม่จำกัด</span>
                            @else
                                <span class="font-semibold">{{ ucfirst($feature->quota_type) }}</span>
                            @endif
                        </p>
                    </div>

                    @if($feature->quota_type !== 'none')
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">🎯 Quota เริ่มต้น</label>
                            <p class="text-lg text-gray-900 dark:text-white mt-1 font-semibold">
                                {{ number_format($feature->default_quota_limit) }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">⚡ Quota สูงสุด</label>
                            <p class="text-lg text-gray-900 dark:text-white mt-1 font-semibold">
                                @if($feature->max_quota_allowed > 0)
                                    {{ number_format($feature->max_quota_allowed) }}
                                @else
                                    <span class="text-gray-500">ไม่จำกัด</span>
                                @endif
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">💳 อนุญาต Overage</label>
                            <p class="text-lg mt-1">
                                @if($feature->allow_overage)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        ✅ อนุญาต
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        ❌ ไม่อนุญาต
                                    </span>
                                @endif
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Pricing Configuration --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">💰</span>
                    การตั้งราคา
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">🏷️ ระดับราคา</label>
                        <p class="text-lg mt-1">
                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-semibold
                                {{ $feature->pricing_tier === 'free' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                {{ $feature->pricing_tier === 'basic' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                {{ $feature->pricing_tier === 'pro' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                                {{ $feature->pricing_tier === 'enterprise' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                                {{ strtoupper($feature->pricing_tier) }}
                            </span>
                        </p>
                    </div>

                    @if($feature->pricing_tier !== 'free')
                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">💵 ราคาพื้นฐาน</label>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                ฿{{ number_format($feature->base_price, 2) }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-500 dark:text-gray-400">📊 ราคาต่อการใช้งาน</label>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                ฿{{ number_format($feature->usage_price, 2) }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Usage Statistics --}}
            @php
                $stats = [
                    'total_tenants' => $feature->featureAccess()->count(),
                    'active_tenants' => $feature->featureAccess()->active()->count(),
                    'total_usage' => $feature->usageLogs()->count(),
                    'success_rate' => $feature->usageLogs()->count() > 0
                        ? round(($feature->usageLogs()->success()->count() / $feature->usageLogs()->count()) * 100, 2)
                        : 0,
                ];
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">📈</span>
                    สถิติการใช้งาน
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4">
                        <p class="text-sm text-blue-600 dark:text-blue-400 font-medium mb-1">Tenants ทั้งหมด</p>
                        <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                            {{ number_format($stats['total_tenants']) }}
                        </p>
                    </div>

                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4">
                        <p class="text-sm text-green-600 dark:text-green-400 font-medium mb-1">Tenants ที่ใช้งาน</p>
                        <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                            {{ number_format($stats['active_tenants']) }}
                        </p>
                    </div>

                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4">
                        <p class="text-sm text-purple-600 dark:text-purple-400 font-medium mb-1">จำนวนการใช้งาน</p>
                        <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                            {{ number_format($stats['total_usage']) }}
                        </p>
                    </div>

                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4">
                        <p class="text-sm text-orange-600 dark:text-orange-400 font-medium mb-1">อัตราสำเร็จ</p>
                        <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">
                            {{ $stats['success_rate'] }}%
                        </p>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.ai-core.analytics.feature', $feature) }}"
                       class="inline-flex items-center gap-2 text-blue-600 dark:text-blue-400 hover:underline font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        ดู Analytics โดยละเอียด
                    </a>
                </div>
            </div>
        </div>

        {{-- Right Column: Status & Actions --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">⚙️</span>
                    สถานะ
                </h2>

                <div class="space-y-4">
                    {{-- Is Enabled --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="flex items-center gap-3">
                            @if($feature->is_enabled)
                                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            @else
                                <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                            @endif
                            <div>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">เปิดใช้งาน</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $feature->is_enabled ? 'กำลังใช้งาน' : 'ปิดอยู่' }}
                                </p>
                            </div>
                        </div>
                        @if($feature->is_enabled)
                            <span class="px-3 py-1 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded-full text-xs font-semibold">
                                Active
                            </span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 rounded-full text-xs font-semibold">
                                Inactive
                            </span>
                        @endif
                    </div>

                    {{-- Is Visible --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="flex items-center gap-3">
                            @if($feature->is_visible)
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            @endif
                            <div>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">แสดงในรายการ</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $feature->is_visible ? 'แสดงต่อ Tenants' : 'ซ่อนไว้' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Requires Approval --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                        <div class="flex items-center gap-3">
                            @if($feature->requires_approval)
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                </svg>
                            @endif
                            <div>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">ต้องการอนุมัติ</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $feature->requires_approval ? 'ต้องอนุมัติ' : 'ใช้ได้ทันที' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">🕐</span>
                    ประวัติ
                </h2>

                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">สร้างเมื่อ</p>
                        <p class="text-gray-900 dark:text-white font-medium">
                            {{ $feature->created_at->format('d/m/Y H:i') }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            ({{ $feature->created_at->diffForHumans() }})
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">อัพเดทล่าสุด</p>
                        <p class="text-gray-900 dark:text-white font-medium">
                            {{ $feature->updated_at->format('d/m/Y H:i') }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            ({{ $feature->updated_at->diffForHumans() }})
                        </p>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl p-6 border-2 border-blue-100 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="text-2xl">⚡</span>
                    Quick Actions
                </h2>

                <div class="space-y-3">
                    <a href="{{ route('admin.ai-core.tenants.index', ['feature_id' => $feature->id]) }}"
                       class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors duration-150 border border-gray-200 dark:border-gray-700">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">👥 ดู Tenants</span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.ai-core.analytics.feature', $feature) }}"
                       class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors duration-150 border border-gray-200 dark:border-gray-700">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">📊 Analytics</span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>

                    <a href="{{ route('admin.ai-core.features.edit', $feature) }}"
                       class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors duration-150 border border-gray-200 dark:border-gray-700">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">✏️ แก้ไข</span>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
