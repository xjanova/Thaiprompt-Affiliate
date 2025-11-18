@extends('layouts.admin-v3')

@section('title', 'AI Core Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="aiCoreDashboard()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-4xl font-bold bg-gradient-to-r from-cyan-500 via-blue-500 to-purple-600 bg-clip-text text-transparent">
                    🧠 AI Core Dashboard
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    ควบคุม AI Features ทั้งหมดแบบรวมศูนย์
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="refreshDashboard()"
                        :disabled="loading"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" :class="{'animate-spin': loading}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span x-text="loading ? 'กำลังโหลด...' : 'รีเฟรช'"></span>
                </button>
                <a href="{{ route('admin.ai-core.settings.index') }}"
                   class="px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg hover:shadow-lg transition-shadow flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    ตั้งค่า
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Features --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold">{{ $stats['total_features'] }}</div>
                    <div class="text-blue-100 text-sm">Features</div>
                </div>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-blue-100">เปิดใช้งาน</span>
                <span class="font-semibold">{{ $stats['enabled_features'] }}/{{ $stats['total_features'] }}</span>
            </div>
        </div>

        {{-- Total Tenants --}}
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold">{{ $stats['total_tenants'] }}</div>
                    <div class="text-purple-100 text-sm">Tenants</div>
                </div>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-purple-100">Active</span>
                <span class="font-semibold">{{ $stats['active_tenants'] }}/{{ $stats['total_tenants'] }}</span>
            </div>
        </div>

        {{-- Usage Today --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold">{{ number_format($stats['total_usage_today']) }}</div>
                    <div class="text-green-100 text-sm">วันนี้</div>
                </div>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-green-100">เดือนนี้</span>
                <span class="font-semibold">{{ number_format($stats['total_usage_month']) }}</span>
            </div>
        </div>

        {{-- Alerts --}}
        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
            <div class="flex items-center justify-between mb-4">
                <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold">{{ $stats['unread_alerts'] }}</div>
                    <div class="text-orange-100 text-sm">Alerts</div>
                </div>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-orange-100">Critical</span>
                <span class="font-semibold bg-red-700/50 px-2 py-0.5 rounded">{{ $stats['critical_alerts'] }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Usage Chart --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">การใช้งาน 7 วันล่าสุด</h2>
                <div class="flex gap-2">
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full text-sm">ทั้งหมด</span>
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full text-sm">สำเร็จ</span>
                    <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full text-sm">ล้มเหลว</span>
                </div>
            </div>
            <div class="h-80">
                <canvas id="usageChart"></canvas>
            </div>
        </div>

        {{-- Top Features --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Top 5 Features</h2>
            <div class="space-y-4">
                @foreach($topFeatures as $item)
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-2xl">{{ $item['feature']->icon ?? '🤖' }}</span>
                            <span class="font-medium text-gray-900 dark:text-white text-sm">
                                {{ $item['feature']->feature_name }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full transition-all duration-500"
                                     style="width: {{ min(($item['usage_count'] / ($topFeatures[0]['usage_count'] ?? 1)) * 100, 100) }}%"></div>
                            </div>
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-400 w-16 text-right">
                                {{ number_format($item['usage_count']) }}
                            </span>
                        </div>
                    </div>
                </div>
                @endforeach

                @if($topFeatures->isEmpty())
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p>ยังไม่มีข้อมูลการใช้งาน</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Alerts Section --}}
    @if($alerts->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">🔔 Alerts ล่าสุด</h2>
            <a href="{{ route('admin.ai-core.alerts.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                ดูทั้งหมด →
            </a>
        </div>
        <div class="space-y-3">
            @foreach($alerts as $alert)
            <div class="flex items-start gap-4 p-4 rounded-xl transition-colors
                        {{ $alert->severity === 'critical' ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' :
                           ($alert->severity === 'error' ? 'bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800' :
                           ($alert->severity === 'warning' ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' :
                           'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800')) }}">
                <div class="p-2 rounded-lg
                            {{ $alert->severity === 'critical' ? 'bg-red-500' :
                               ($alert->severity === 'error' ? 'bg-orange-500' :
                               ($alert->severity === 'warning' ? 'bg-yellow-500' : 'bg-blue-500')) }} text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $alert->title }}</h3>
                        @if($alert->occurrence_count > 1)
                        <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-xs rounded-full">
                            x{{ $alert->occurrence_count }}
                        </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ $alert->message }}</p>
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-500">
                        @if($alert->feature)
                        <span>📦 {{ $alert->feature->feature_name }}</span>
                        @endif
                        <span>🕐 {{ $alert->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="flex gap-2">
                    @if($alert->action_url)
                    <a href="{{ $alert->action_url }}"
                       class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded-lg transition-colors">
                        {{ $alert->action_label ?? 'ดำเนินการ' }}
                    </a>
                    @endif
                    <form action="{{ route('admin.ai-core.alerts.dismiss', $alert) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-lg transition-colors">
                            ปิด
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Recent Usage --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">การใช้งานล่าสุด</h2>
            <a href="{{ route('admin.ai-core.analytics.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                ดูรายละเอียด →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <th class="px-4 py-3">เวลา</th>
                        <th class="px-4 py-3">Feature</th>
                        <th class="px-4 py-3">Tenant</th>
                        <th class="px-4 py-3">User</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">สถานะ</th>
                        <th class="px-4 py-3">Usage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentUsage as $usage)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            {{ $usage->created_at->format('H:i:s') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">{{ $usage->feature->icon ?? '🤖' }}</span>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ Str::limit($usage->feature->feature_name, 20) }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $usage->tenant->tenant_name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $usage->user->name ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $usage->action }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded-full
                                        {{ $usage->status === 'success' ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' :
                                           ($usage->status === 'failed' ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' :
                                           'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400') }}">
                                {{ $usage->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $usage->usage_amount }} {{ $usage->usage_unit }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                            <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p>ยังไม่มีข้อมูลการใช้งาน</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function aiCoreDashboard() {
    return {
        loading: false,
        chart: null,

        init() {
            this.initChart();
        },

        initChart() {
            const ctx = document.getElementById('usageChart');
            if (!ctx) return;

            const chartData = @json($usageChart);

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets.map(dataset => ({
                        ...dataset,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        refreshDashboard() {
            this.loading = true;
            // Simulate refresh
            setTimeout(() => {
                window.location.reload();
            }, 500);
        }
    }
}
</script>
@endpush
@endsection
