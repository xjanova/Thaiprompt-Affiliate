@extends('layouts.admin-v3')

@section('title', 'สถิติอีเมลขั้นสูง')

@section('content')
{{-- ระบบวิเคราะห์สถิติอีเมล --}}
<div class="space-y-6" x-data="emailAnalytics()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>📈</span>
                <span>สถิติอีเมลขั้นสูง</span>
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                วิเคราะห์และติดตามประสิทธิภาพการส่งอีเมล
            </p>
        </div>
        <a href="{{ route('admin.email.analytics.export', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
           class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Export CSV
        </a>
    </div>

    {{-- Date Filter --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-4 border border-gray-200/50 dark:border-gray-700/50">
        <form action="{{ route('admin.email.analytics') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    วันที่เริ่มต้น
                </label>
                <input type="date"
                       name="start_date"
                       value="{{ $startDate }}"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
            </div>

            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    วันที่สิ้นสุด
                </label>
                <input type="date"
                       name="end_date"
                       value="{{ $endDate }}"
                       class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all">
            </div>

            <div class="flex items-end">
                <button type="submit"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors">
                    ดูสถิติ
                </button>
            </div>
        </form>
    </div>

    {{-- Overall Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 uppercase tracking-wide">ส่งทั้งหมด</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($overallStats['total']) }}</p>
                </div>
                <div class="p-3 bg-white/20 rounded-lg">
                    <span class="text-3xl">📧</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 uppercase tracking-wide">ส่งสำเร็จ</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($overallStats['sent']) }}</p>
                    <p class="mt-1 text-sm opacity-90">{{ $overallStats['success_rate'] }}%</p>
                </div>
                <div class="p-3 bg-white/20 rounded-lg">
                    <span class="text-3xl">✅</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl p-6 text-white shadow-lg hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 uppercase tracking-wide">เปิดอ่าน</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($overallStats['opened']) }}</p>
                    <p class="mt-1 text-sm opacity-90">{{ $overallStats['open_rate'] }}%</p>
                </div>
                <div class="p-3 bg-white/20 rounded-lg">
                    <span class="text-3xl">👁️</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 uppercase tracking-wide">คลิก</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($overallStats['clicked']) }}</p>
                    <p class="mt-1 text-sm opacity-90">{{ $overallStats['click_rate'] }}%</p>
                </div>
                <div class="p-3 bg-white/20 rounded-lg">
                    <span class="text-3xl">🖱️</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white shadow-lg hover:scale-105 transition-transform duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90 uppercase tracking-wide">ตีกลับ</p>
                    <p class="mt-2 text-3xl font-bold">{{ number_format($overallStats['bounced']) }}</p>
                    <p class="mt-1 text-sm opacity-90">{{ $overallStats['bounce_rate'] }}%</p>
                </div>
                <div class="p-3 bg-white/20 rounded-lg">
                    <span class="text-3xl">↩️</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Timeline Chart --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-6 border border-gray-200/50 dark:border-gray-700/50 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 สถิติตามช่วงเวลา</h3>
        <canvas id="timelineChart" height="80"></canvas>
    </div>

    {{-- Provider Stats & Campaign Stats --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Provider Stats --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-6 border border-gray-200/50 dark:border-gray-700/50 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📡 สถิติตาม Provider</h3>
            <div class="space-y-3">
                @forelse($providerStats as $stat)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $stat->provider }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                ส่ง {{ number_format($stat->total) }} | สำเร็จ {{ $stat->success_rate }}%
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-green-600 dark:text-green-400">
                                {{ number_format($stat->sent) }}
                            </div>
                            <div class="text-xs text-red-600 dark:text-red-400">
                                ล้มเหลว {{ number_format($stat->failed) }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>

        {{-- Campaign Stats --}}
        <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-6 border border-gray-200/50 dark:border-gray-700/50 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📢 สถิติแคมเปญ</h3>
            <div class="space-y-3">
                @forelse($campaignStats as $stat)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white capitalize">{{ $stat->status }}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ number_format($stat->count) }} แคมเปญ
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                {{ number_format($stat->total_recipients) }} ผู้รับ
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-400">
                                ส่งแล้ว {{ number_format($stat->sent_count) }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Top Campaigns --}}
    <div class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm rounded-xl p-6 border border-gray-200/50 dark:border-gray-700/50 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🏆 Top 10 แคมเปญ (Open Rate สูงสุด)</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">แคมเปญ</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ผู้รับ</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ส่งถึง</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">เปิดอ่าน</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Open Rate</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Click Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($topCampaigns as $campaign)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.email.campaigns.show', $campaign) }}"
                                   class="text-sm font-semibold text-purple-600 dark:text-purple-400 hover:underline">
                                    {{ $campaign->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-white">
                                {{ number_format($campaign->total_recipients) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-white">
                                {{ number_format($campaign->delivered_count) }}
                            </td>
                            <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-white">
                                {{ number_format($campaign->opened_count) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ $campaign->open_rate }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $campaign->click_rate }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                ไม่มีข้อมูลแคมเปญ
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
/**
 * Email Analytics Component
 * วิเคราะห์สถิติอีเมล
 */
function emailAnalytics() {
    return {
        chart: null,

        // Methods
        init() {
            console.log('Email Analytics initialized');
            this.initTimelineChart();
        },

        /**
         * สร้างกราฟ Timeline
         */
        initTimelineChart() {
            const ctx = document.getElementById('timelineChart');
            if (!ctx) return;

            // ข้อมูลจาก Laravel
            const timelineData = @json($timelineData);

            // สร้างกราฟ
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: timelineData.labels,
                    datasets: [
                        {
                            label: 'ส่งทั้งหมด',
                            data: timelineData.total,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'ส่งสำเร็จ',
                            data: timelineData.sent,
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'ล้มเหลว',
                            data: timelineData.failed,
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000',
                                usePointStyle: true,
                                padding: 15
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    }
                }
            });
        }
    }
}
</script>
@endpush
@endsection
