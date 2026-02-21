@extends('layouts.admin-v3')

@section('title', 'AI Gen Dashboard')

@section('content')
<div x-data="aiGenDashboard()" x-init="init()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">✨ AI Gen Dashboard</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ภาพรวมระบบสร้างภาพและวิดีโอด้วย AI</p>
        </div>
        <button @click="refresh()" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition">
            <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            รีเฟรช
        </button>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-5 text-white shadow-lg">
            <p class="text-purple-200 text-xs font-medium uppercase">Providers ใช้งาน</p>
            <p class="text-3xl font-bold mt-1" x-text="stats.active_providers ?? '-'">-</p>
            <p class="text-purple-200 text-xs mt-1">จาก <span x-text="stats.total_providers ?? 0"></span> ทั้งหมด</p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-emerald-700 rounded-2xl p-5 text-white shadow-lg">
            <p class="text-green-200 text-xs font-medium uppercase">Subscriptions</p>
            <p class="text-3xl font-bold mt-1" x-text="stats.active_subscriptions ?? '-'">-</p>
            <p class="text-green-200 text-xs mt-1">ที่ใช้งานอยู่</p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-5 text-white shadow-lg">
            <p class="text-blue-200 text-xs font-medium uppercase">การสร้างทั้งหมด</p>
            <p class="text-3xl font-bold mt-1" x-text="(stats.total_generations ?? 0).toLocaleString()">-</p>
            <p class="text-blue-200 text-xs mt-1">สำเร็จ <span x-text="stats.completed_generations ?? 0"></span></p>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-orange-700 rounded-2xl p-5 text-white shadow-lg">
            <p class="text-amber-200 text-xs font-medium uppercase">Free Quota ใช้ไป</p>
            <p class="text-3xl font-bold mt-1" x-text="(stats.free_quota_usage ?? 0).toLocaleString()">-</p>
            <p class="text-amber-200 text-xs mt-1">จ่ายเงิน <span x-text="stats.paid_usage ?? 0"></span></p>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <a href="{{ route('admin.ai-gen.providers.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-purple-400 hover:shadow-lg transition-all group text-center">
            <div class="text-2xl mb-1 group-hover:scale-110 transition-transform">🤖</div>
            <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Providers</div>
        </a>
        <a href="{{ route('admin.ai-gen.packages.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-green-400 hover:shadow-lg transition-all group text-center">
            <div class="text-2xl mb-1 group-hover:scale-110 transition-transform">📦</div>
            <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Packages</div>
        </a>
        <a href="{{ route('admin.ai-gen.quotas.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-amber-400 hover:shadow-lg transition-all group text-center">
            <div class="text-2xl mb-1 group-hover:scale-110 transition-transform">🎁</div>
            <div class="text-xs font-medium text-gray-700 dark:text-gray-300">Free Quotas</div>
        </a>
        <a href="{{ route('admin.ai-gen.settings') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-blue-400 hover:shadow-lg transition-all group text-center">
            <div class="text-2xl mb-1 group-hover:scale-110 transition-transform">⚙️</div>
            <div class="text-xs font-medium text-gray-700 dark:text-gray-300">ตั้งค่า</div>
        </a>
        <a href="{{ route('admin.ai-gen.promotions.index') }}" class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:border-pink-400 hover:shadow-lg transition-all group text-center">
            <div class="text-2xl mb-1 group-hover:scale-110 transition-transform">🎉</div>
            <div class="text-xs font-medium text-gray-700 dark:text-gray-300">โปรโมชั่น</div>
        </a>
    </div>

    {{-- Success Ratio --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 text-center">
            <div class="text-4xl mb-2">✅</div>
            <div class="text-2xl font-bold text-green-600" x-text="(stats.completed_generations ?? 0).toLocaleString()">0</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">สำเร็จ</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 text-center">
            <div class="text-4xl mb-2">❌</div>
            <div class="text-2xl font-bold text-red-600" x-text="(stats.failed_generations ?? 0).toLocaleString()">0</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ล้มเหลว</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 text-center">
            <div class="text-4xl mb-2">📊</div>
            <div class="text-2xl font-bold text-blue-600" x-text="successRate + '%'">0%</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">อัตราสำเร็จ</div>
        </div>
    </div>
</div>

<script>
function aiGenDashboard() {
    return {
        stats: {},
        loading: false,
        get successRate() {
            const t = this.stats.total_generations || 0;
            return t === 0 ? 0 : Math.round((this.stats.completed_generations || 0) / t * 100);
        },
        async init() { await this.loadStats(); },
        async refresh() { await this.loadStats(); },
        async loadStats() {
            this.loading = true;
            try {
                const res = await fetch('/admin/ai-gen/dashboard', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.success) this.stats = data.data;
            } catch (e) { console.error(e); }
            this.loading = false;
        }
    }
}
</script>
@endsection
