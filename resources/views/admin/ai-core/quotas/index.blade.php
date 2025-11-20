@extends('layouts.admin-v3')
@section('title', 'จัดการ Quotas')
@section('content')
<div class="container-fluid px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-cyan-600 to-blue-600 dark:from-cyan-400 dark:to-blue-400 bg-clip-text text-transparent">
            📊 จัดการ Quotas
        </h1>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-blue-100 text-sm mb-1">Total Quotas</p>
            <h3 class="text-3xl font-bold">{{ $quotas->total() }}</h3>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-green-100 text-sm mb-1">Active</p>
            <h3 class="text-3xl font-bold">{{ $quotas->where('status', 'active')->count() }}</h3>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-orange-100 text-sm mb-1">กำลังจะหมด</p>
            <h3 class="text-3xl font-bold">{{ $quotas->filter(function($q) { return $q->quota_limit > 0 && (($q->quota_used / $q->quota_limit) * 100) >= 80; })->count() }}</h3>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-red-100 text-sm mb-1">หมดแล้ว</p>
            <h3 class="text-3xl font-bold">{{ $quotas->where('quota_remaining', 0)->count() }}</h3>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Tenant</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Feature</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Period</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Usage</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($quotas as $quota)
                        @php
                            $percentageUsed = $quota->quota_limit > 0 ? ($quota->quota_used / $quota->quota_limit) * 100 : 0;
                            $color = $percentageUsed >= 90 ? 'red' : ($percentageUsed >= 80 ? 'orange' : 'green');
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $quota->tenant->tenant_name }}</div>
                                <div class="text-xs text-gray-500">{{ $quota->tenant->tenant_key }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">{{ $quota->feature->icon }}</span>
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $quota->feature->feature_name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-white">{{ ucfirst($quota->period_type) }}</div>
                                <div class="text-xs text-gray-500">{{ $quota->period_start->format('d/m/Y') }} - {{ $quota->period_end->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-2">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">{{ number_format($quota->quota_used) }} / {{ number_format($quota->quota_limit) }}</span>
                                        <span class="font-semibold text-{{ $color }}-600">{{ number_format($percentageUsed, 1) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-{{ $color }}-500 h-2 rounded-full transition-all duration-300" style="width: {{ min($percentageUsed, 100) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                    {{ $quota->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                    {{ $quota->status === 'expired' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                    {{ $quota->status === 'paused' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}">
                                    {{ ucfirst($quota->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.ai-core.quotas.manage', ['tenant_id' => $quota->tenant_id, 'feature_id' => $quota->feature_id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-cyan-100 hover:bg-cyan-200 dark:bg-cyan-900/30 dark:hover:bg-cyan-900/50 text-cyan-700 dark:text-cyan-400 rounded-lg transition-colors duration-150">
                                    ⚙️ จัดการ
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">ไม่มีข้อมูล Quotas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($quotas->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $quotas->links() }}</div>
        @endif
    </div>
</div>
@endsection
