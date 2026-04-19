{{--
/**
 * หน้า Page View Analytics Dashboard
 *
 * แสดงสถิติการเข้าชมเว็บไซต์:
 * - ภาพรวมผู้เข้าชม
 * - หน้าที่มีคนเข้าชมมากที่สุด
 * - แหล่งที่มาของ traffic
 * - อุปกรณ์และ browser
 *
 * @version 1.0.0
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'Page Views Analytics')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-3xl">📊</span>
                วิเคราะห์การเข้าชมเว็บ
            </h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                ติดตามพฤติกรรมผู้ใช้และวิเคราะห์ประสิทธิภาพหน้าเว็บ
            </p>
        </div>

        {{-- Period Selector --}}
        <div class="mt-4 md:mt-0 flex items-center gap-4">
            <select id="period-selector"
                    onchange="window.location.href = '?period=' + this.value"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                <option value="today" {{ $period === 'today' ? 'selected' : '' }}>วันนี้</option>
                <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>เมื่อวาน</option>
                <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>7 วันล่าสุด</option>
                <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>30 วันล่าสุด</option>
                <option value="90days" {{ $period === '90days' ? 'selected' : '' }}>90 วันล่าสุด</option>
            </select>

            <a href="{{ route('admin.analytics.page-views.export', ['period' => $period]) }}"
               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-download"></i>
                <span class="hidden sm:inline">Export CSV</span>
            </a>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Views --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Page Views ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ number_format($summary['total_views']) }}
                    </p>
                </div>
                <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                    <span class="text-2xl">👁️</span>
                </div>
            </div>
        </div>

        {{-- Unique Visitors --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">ผู้เยี่ยมชมใหม่</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ number_format($summary['unique_visitors']) }}
                    </p>
                </div>
                <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <span class="text-2xl">👥</span>
                </div>
            </div>
        </div>

        {{-- Logged-in Users --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">ผู้ใช้ที่ล็อกอิน</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ number_format($summary['unique_users']) }}
                    </p>
                </div>
                <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center">
                    <span class="text-2xl">🔐</span>
                </div>
            </div>
        </div>

        {{-- Pages per Session --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">หน้า/Session</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                        {{ $summary['avg_pages_per_session'] }}
                    </p>
                </div>
                <div class="w-14 h-14 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                    <span class="text-2xl">📄</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Daily Views Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📈</span>
                Page Views รายวัน
            </h3>
            <div class="h-64">
                <canvas id="dailyViewsChart"></canvas>
            </div>
        </div>

        {{-- Device Distribution --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📱</span>
                อุปกรณ์ที่ใช้
            </h3>
            <div class="h-64">
                <canvas id="deviceChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Tables Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Pages --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🔥</span>
                    หน้าที่มีคนเข้าชมมากที่สุด
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">หน้า</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Views</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($topPages as $page)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-xs">
                                    {{ $page->path }}
                                </div>
                                @if($page->route_name)
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $page->route_name }}
                                </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                    {{ number_format($page->views) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                ไม่มีข้อมูล
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 text-center">
                <a href="{{ route('admin.analytics.page-views.pages', ['period' => $period]) }}"
                   class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    ดูทั้งหมด →
                </a>
            </div>
        </div>

        {{-- Top Referrers --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🔗</span>
                    แหล่งที่มาของ Traffic
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">แหล่งที่มา</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Visits</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($topReferrers as $referrer)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $referrer->referrer_domain }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                    {{ number_format($referrer->visits) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                ไม่มีข้อมูล Referrer
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 text-center">
                <a href="{{ route('admin.analytics.page-views.sources', ['period' => $period]) }}"
                   class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    ดูทั้งหมด →
                </a>
            </div>
        </div>
    </div>

    {{-- Browser Stats --}}
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <span>🌐</span>
                Browser ที่ใช้
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                @foreach($browserStats as $browser)
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ number_format($browser->views) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                        {{ $browser->browser }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Quick Links --}}
    <div class="mt-8 grid grid-cols-2 sm:grid-cols-4 gap-4">
        <a href="{{ route('admin.analytics.page-views.realtime') }}"
           class="flex items-center justify-center gap-2 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
            <span>⚡</span>
            <span>Real-time</span>
        </a>
        <a href="{{ route('admin.analytics.page-views.pages', ['period' => $period]) }}"
           class="flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            <span>📄</span>
            <span>หน้าทั้งหมด</span>
        </a>
        <a href="{{ route('admin.analytics.page-views.sources', ['period' => $period]) }}"
           class="flex items-center justify-center gap-2 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
            <span>🔗</span>
            <span>Traffic Sources</span>
        </a>
        <a href="{{ route('admin.analytics.page-views.devices', ['period' => $period]) }}"
           class="flex items-center justify-center gap-2 px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
            <span>📱</span>
            <span>อุปกรณ์</span>
        </a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js defaults
    Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280';
    Chart.defaults.borderColor = document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb';

    // Daily Views Chart
    const dailyData = @json($dailyStats);
    const dailyCtx = document.getElementById('dailyViewsChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [
                {
                    label: 'Page Views',
                    data: dailyData.map(d => d.views),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Unique Visitors',
                    data: dailyData.map(d => d.unique_visitors),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                }
            }
        }
    });

    // Device Chart
    const deviceData = @json($deviceStats);
    const deviceCtx = document.getElementById('deviceChart').getContext('2d');
    new Chart(deviceCtx, {
        type: 'doughnut',
        data: {
            labels: deviceData.map(d => {
                const icons = { 'desktop': '🖥️', 'mobile': '📱', 'tablet': '📲' };
                return (icons[d.device_type] || '📟') + ' ' + (d.device_type || 'Unknown');
            }),
            datasets: [{
                data: deviceData.map(d => d.views),
                backgroundColor: [
                    '#3b82f6',
                    '#10b981',
                    '#f59e0b',
                    '#8b5cf6',
                    '#ef4444',
                ],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
});
</script>
@endpush
@endsection
