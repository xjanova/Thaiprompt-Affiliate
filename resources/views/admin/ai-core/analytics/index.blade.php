@extends('layouts.admin')
@section('title', 'AI Core Analytics')
@section('content')
<div class="container-fluid px-4 py-6">
    <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-400 dark:to-purple-400 bg-clip-text text-transparent mb-6">
        📊 AI Core Analytics
    </h1>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-blue-100 text-sm mb-1">Total Usage</p>
            <h3 class="text-3xl font-bold">{{ number_format($stats['total_usage']) }}</h3>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-green-100 text-sm mb-1">Success Rate</p>
            <h3 class="text-3xl font-bold">{{ $stats['success_rate'] }}%</h3>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-purple-100 text-sm mb-1">Total Cost</p>
            <h3 class="text-3xl font-bold">฿{{ number_format($stats['total_cost'], 2) }}</h3>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-orange-100 text-sm mb-1">Active Tenants</p>
            <h3 class="text-3xl font-bold">{{ $stats['unique_tenants'] }}</h3>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📈 Daily Usage</h2>
            <canvas id="usageChart"></canvas>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎯 Top Features</h2>
            <div class="space-y-3">
                @foreach($topFeatures as $item)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $item->feature->feature_name }}</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($item->usage_count) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ min(($item->usage_count / $topFeatures->first()->usage_count) * 100, 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Top Tenants --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">👥 Top Tenants</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($topTenants as $item)
                <a href="{{ route('admin.ai-core.analytics.tenant', $item->tenant) }}"
                   class="p-4 border border-gray-200 dark:border-gray-700 rounded-xl hover:shadow-lg transition-shadow duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ $item->tenant->tenant_name }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ number_format($item->usage_count) }} uses</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@push('scripts')
<script>
const ctx = document.getElementById('usageChart');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($usageChart['labels']),
        datasets: @json($usageChart['datasets'])
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});
</script>
@endpush
@endsection
