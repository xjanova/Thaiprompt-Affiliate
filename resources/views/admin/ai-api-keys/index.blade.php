@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div x-data="aiApiKeysDashboard()" x-init="init()" class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $pageTitle }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">จัดการ API Keys สำหรับ AI Providers ทั้งหมด</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="refreshStats()"
                    class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center gap-2">
                <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                รีเฟรช
            </button>
            <a href="{{ route('admin.ai-api-keys.usage-dashboard') }}"
               class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                </svg>
                📊 Usage Dashboard
            </a>
            <a href="{{ route('admin.ai-api-keys.create') }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                เพิ่ม API Key
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {{-- Total Keys --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="summary.total_keys">{{ $summary['total_keys'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Keys ทั้งหมด</p>
                </div>
            </div>
        </div>

        {{-- Active Keys --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="summary.active_keys">{{ $summary['active_keys'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Active</p>
                </div>
            </div>
        </div>

        {{-- Available Keys --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="summary.available_keys">{{ $summary['available_keys'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">พร้อมใช้</p>
                </div>
            </div>
        </div>

        {{-- Tokens Today --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400" x-text="formatNumber(summary.tokens_used_today)">{{ number_format($summary['tokens_used_today']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tokens วันนี้</p>
                </div>
            </div>
        </div>

        {{-- Tokens Month --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-cyan-600 dark:text-cyan-400" x-text="formatNumber(summary.tokens_used_month)">{{ number_format($summary['tokens_used_month']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tokens เดือนนี้</p>
                </div>
            </div>
        </div>

        {{-- Requests Today --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-rose-100 dark:bg-rose-900/30 rounded-lg">
                    <svg class="w-6 h-6 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-rose-600 dark:text-rose-400" x-text="formatNumber(summary.requests_today)">{{ number_format($summary['requests_today']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Requests วันนี้</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Providers Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($providers as $provider => $stats)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr($provider, 0, 2)) }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $stats['name'] }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stats['total_keys'] }} keys</p>
                    </div>
                </div>
                <a href="{{ route('admin.ai-api-keys.provider', $provider) }}"
                   class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>

            {{-- Stats --}}
            <div class="p-6 space-y-4">
                {{-- Status Badges --}}
                <div class="flex flex-wrap gap-2">
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                        {{ $stats['active_keys'] }} active
                    </span>
                    <span class="px-2 py-1 text-xs rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                        {{ $stats['available_keys'] }} พร้อมใช้
                    </span>
                </div>

                {{-- Token Usage --}}
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Tokens วันนี้</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($stats['tokens_used_today']) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Tokens เดือนนี้</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($stats['tokens_used_month']) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Requests วันนี้</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($stats['requests_today']) }}</span>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="flex gap-2 pt-2">
                    <a href="{{ route('admin.ai-api-keys.create', $provider) }}"
                       class="flex-1 px-3 py-2 text-sm text-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        + เพิ่ม Key
                    </a>
                    <a href="{{ route('admin.ai-api-keys.provider', $provider) }}"
                       class="flex-1 px-3 py-2 text-sm text-center bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        จัดการ
                    </a>
                </div>
            </div>
        </div>
        @endforeach

        {{-- Add New Provider Card --}}
        @foreach(array_diff_key($allProviders, $providers) as $provider => $name)
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-6 flex flex-col items-center justify-center text-center hover:border-blue-500 dark:hover:border-blue-400 transition-colors cursor-pointer"
             onclick="window.location.href='{{ route('admin.ai-api-keys.create', $provider) }}'">
            <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <h3 class="font-medium text-gray-600 dark:text-gray-300">{{ $name }}</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">เพิ่ม API Key</p>
        </div>
        @endforeach
    </div>

    {{-- Rotation Modes Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">โหมดการวนใช้ API Keys</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            @foreach($rotationModes as $mode => $description)
            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <h4 class="font-medium text-gray-900 dark:text-white mb-1">{{ ucfirst(str_replace('_', ' ', $mode)) }}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
function aiApiKeysDashboard() {
    return {
        loading: false,
        summary: @json($summary),

        init() {
            // Auto refresh every 30 seconds
            setInterval(() => this.refreshStats(), 30000);
        },

        async refreshStats() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('admin.ai-api-keys.stats') }}');
                const data = await response.json();
                if (data.success) {
                    this.summary = data.data.summary;
                }
            } catch (error) {
                console.error('Error refreshing stats:', error);
            } finally {
                this.loading = false;
            }
        },

        formatNumber(num) {
            return new Intl.NumberFormat('th-TH').format(num || 0);
        }
    };
}
</script>
@endpush
@endsection
