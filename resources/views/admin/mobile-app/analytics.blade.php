@extends('layouts.admin-v3')

@section('title', 'Device Analytics')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-4xl">📊</span>
                Device Analytics
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-2">
                สถิติเครื่องที่ลงทะเบียนแอพมือถือ
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex gap-3">
            <a href="{{ route('admin.mobile-app.analytics.export') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition">
                <span>📥</span>
                <span>Export CSV</span>
            </a>
            <a href="{{ route('admin.mobile-app.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition">
                <span>←</span>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center text-2xl">
                    📱
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เครื่องทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($totalDevices) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center text-2xl">
                    ✅
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active (7 วัน)</p>
                    <p class="text-3xl font-bold text-green-500">
                        {{ number_format($activeDevices) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center text-2xl">
                    📈
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Retention Rate</p>
                    <p class="text-3xl font-bold text-purple-500">
                        {{ $retentionRate }}%
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <div class="flex gap-2">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center text-xl mb-1">

                        </div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format($byPlatform['ios'] ?? 0) }}
                        </p>
                        <p class="text-xs text-gray-500">iOS</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center text-xl mb-1">
                            🤖
                        </div>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format($byPlatform['android'] ?? 0) }}
                        </p>
                        <p class="text-xs text-gray-500">Android</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- DAU Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                📅 Daily Active Users (30 วัน)
            </h3>
            <div class="h-64" x-data="dauChart()" x-init="init()">
                <canvas x-ref="chart"></canvas>
            </div>
        </div>

        {{-- New Devices Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                📈 เครื่องใหม่ต่อวัน (30 วัน)
            </h3>
            <div class="h-64" x-data="newDevicesChart()" x-init="init()">
                <canvas x-ref="chart"></canvas>
            </div>
        </div>
    </div>

    {{-- App Version Distribution --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            📦 App Version Distribution
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            @forelse($byAppVersion as $version)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 text-center">
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        v{{ $version->app_version }}
                    </p>
                    <p class="text-2xl font-bold text-blue-500">
                        {{ number_format($version->count) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $totalDevices > 0 ? round(($version->count / $totalDevices) * 100, 1) : 0 }}%
                    </p>
                </div>
            @empty
                <div class="col-span-5 text-center text-gray-500 dark:text-gray-400 py-8">
                    ยังไม่มีข้อมูล
                </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Devices Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                🕐 เครื่องที่ลงทะเบียนล่าสุด
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Device
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Platform
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            App Version
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            User
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Last Active
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Registered
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recentDevices as $device)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $device->device_brand }} {{ $device->device_model }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ Str::limit($device->device_id, 20) }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium
                                    {{ $device->platform === 'ios' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' }}">
                                    {{ $device->platform === 'ios' ? '' : '🤖' }}
                                    {{ strtoupper($device->platform) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                v{{ $device->app_version }}
                            </td>
                            <td class="px-6 py-4">
                                @if($device->user)
                                    <div class="text-gray-900 dark:text-white">{{ $device->user->name }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                {{ $device->last_active_at?->diffForHumans() ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                {{ $device->created_at->format('d/m/Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ยังไม่มีเครื่องลงทะเบียน
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function dauChart() {
        return {
            init() {
                const ctx = this.$refs.chart.getContext('2d');
                const data = @json($dailyActiveUsers);

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(d => d.date),
                        datasets: [{
                            label: 'Active Users',
                            data: data.map(d => d.count),
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        }
    }

    function newDevicesChart() {
        return {
            init() {
                const ctx = this.$refs.chart.getContext('2d');
                const data = @json($newDevicesPerDay);

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(d => d.date),
                        datasets: [{
                            label: 'New Devices',
                            data: data.map(d => d.count),
                            backgroundColor: '#10B981',
                            borderRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
        }
    }
</script>
@endpush
@endsection
