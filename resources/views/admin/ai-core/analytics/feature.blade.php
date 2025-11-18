@extends('layouts.admin')
@section('title', 'Feature Analytics: ' . $feature->feature_name)
@section('content')
<div class="container-fluid px-4 py-6">
    <div class="mb-6">
        <a href="{{ route('admin.ai-core.analytics.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-4 inline-block">← กลับ</a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ $feature->icon }} {{ $feature->feature_name }} - Analytics
        </h1>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-blue-100 text-sm mb-1">Total Usage</p>
            <h3 class="text-3xl font-bold">{{ number_format($stats['total_usage']) }}</h3>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-green-100 text-sm mb-1">Success</p>
            <h3 class="text-3xl font-bold">{{ number_format($stats['success_count']) }}</h3>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-red-100 text-sm mb-1">Failed</p>
            <h3 class="text-3xl font-bold">{{ number_format($stats['failed_count']) }}</h3>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-purple-100 text-sm mb-1">Unique Tenants</p>
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
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">👥 Top Tenants</h2>
            <div class="space-y-3">
                @foreach($topTenants as $item)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $item->tenant->tenant_name }}</span>
                            <span class="text-sm font-bold">{{ number_format($item->usage_count) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min(($item->usage_count / $topTenants->first()->usage_count) * 100, 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
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
        datasets: [{
            label: 'Usage',
            data: @json($usageChart['data']),
            borderColor: 'rgb(59, 130, 246)',
            tension: 0.1
        }]
    },
    options: { responsive: true, maintainAspectRatio: true }
});
</script>
@endpush
@endsection
