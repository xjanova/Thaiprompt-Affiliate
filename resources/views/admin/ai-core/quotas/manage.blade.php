@extends('layouts.admin')
@section('title', 'จัดการ Quota')
@section('content')
<div class="container-fluid px-4 py-6" x-data="quotaManage()">
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.ai-core.quotas.index') }}"
               class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-cyan-600 to-blue-600 dark:from-cyan-400 dark:to-blue-400 bg-clip-text text-transparent">
                    ⚙️ จัดการ Quota
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    {{ $tenant->tenant_name }} - {{ $feature->feature_name }}
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- Current Quota --}}
            @if($quota)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📊 Quota ปัจจุบัน</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <p class="text-sm text-blue-600 dark:text-blue-400 mb-1">Quota Limit</p>
                            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ number_format($quota->quota_limit) }}</p>
                        </div>
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <p class="text-sm text-green-600 dark:text-green-400 mb-1">Used</p>
                            <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ number_format($quota->quota_used) }}</p>
                        </div>
                        <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                            <p class="text-sm text-purple-600 dark:text-purple-400 mb-1">Remaining</p>
                            <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ number_format($quota->quota_remaining) }}</p>
                        </div>
                        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl">
                            <p class="text-sm text-orange-600 dark:text-orange-400 mb-1">Bonus</p>
                            <p class="text-2xl font-bold text-orange-700 dark:text-orange-300">{{ number_format($quota->bonus_quota ?? 0) }}</p>
                        </div>
                    </div>
                    @php
                        $percentageUsed = $quota->quota_limit > 0 ? ($quota->quota_used / $quota->quota_limit) * 100 : 0;
                    @endphp
                    <div class="mt-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600 dark:text-gray-400">Usage</span>
                            <span class="font-semibold">{{ number_format($percentageUsed, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-cyan-500 to-blue-500 h-3 rounded-full transition-all duration-300" 
                                 style="width: {{ min($percentageUsed, 100) }}%"></div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Add Bonus Form --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎁 เพิ่ม Bonus Quota</h2>
                <form method="POST" action="{{ route('admin.ai-core.quotas.addBonus', ['tenant' => $tenant, 'feature' => $feature]) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">จำนวน Bonus</label>
                        <input type="number" name="amount" required min="1" placeholder="100"
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">เหตุผล</label>
                        <textarea name="reason" rows="3" placeholder="เหตุผลในการเพิ่ม bonus..."
                                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-cyan-500 text-gray-900 dark:text-white"></textarea>
                    </div>
                    <button type="submit"
                            class="w-full px-6 py-3 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                        ✅ เพิ่ม Bonus
                    </button>
                </form>
            </div>

            {{-- Reset Form --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🔄 Reset Quota</h2>
                <form method="POST" action="{{ route('admin.ai-core.quotas.reset', ['tenant' => $tenant, 'feature' => $feature]) }}" 
                      onsubmit="return confirm('คุณต้องการ reset quota หรือไม่?')" class="space-y-4">
                    @csrf
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            ⚠️ การ reset จะรีเซ็ต quota_used เป็น 0 และสร้าง period ใหม่
                        </p>
                    </div>
                    <button type="submit"
                            class="w-full px-6 py-3 bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                        🔄 Reset Quota
                    </button>
                </form>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Info --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ℹ️ ข้อมูล</h2>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">Tenant</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $tenant->tenant_name }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500 dark:text-gray-400">Feature</label>
                        <p class="text-gray-900 dark:text-white font-medium">{{ $feature->feature_name }}</p>
                    </div>
                    @if($quota)
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">Period</label>
                            <p class="text-gray-900 dark:text-white font-medium">{{ ucfirst($quota->period_type) }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-500 dark:text-gray-400">Status</label>
                            <p class="text-gray-900 dark:text-white font-medium">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                    {{ $quota->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($quota->status) }}
                                </span>
                            </p>
                        </div>
                        @if($quota->next_reset_at)
                            <div>
                                <label class="text-sm text-gray-500 dark:text-gray-400">Reset ถัดไป</label>
                                <p class="text-gray-900 dark:text-white font-medium text-sm">
                                    {{ $quota->next_reset_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl p-6 border-2 border-cyan-100 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚡ Quick Actions</h2>
                <div class="space-y-2">
                    <form method="POST" action="{{ route('admin.ai-core.quotas.resetAllExpired') }}" onsubmit="return confirm('Reset quotas ที่หมดอายุทั้งหมดหรือไม่?')">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center justify-between p-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-xl transition-colors duration-150">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">🔄 Reset Expired</span>
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
function quotaManage() {
    return {
        // Can add reactive data here if needed
    }
}
</script>
@endpush
@endsection
