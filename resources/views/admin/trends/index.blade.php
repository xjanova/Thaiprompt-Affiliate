@extends('layouts.admin')

@section('title', 'Viral Trend Detection - การตรวจจับเทรนด์ไวรัล')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-pink-600 via-rose-600 to-red-600 dark:from-pink-800 dark:via-rose-800 dark:to-red-800 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold mb-2">🔥 Viral Trend Detection</h1>
                <p class="text-pink-100 dark:text-pink-200">ติดตามและวิเคราะห์เทรนด์ไวรัลแบบเรียลไทม์</p>
                <p class="text-pink-200 dark:text-pink-300 text-sm mt-1">อัปเดตล่าสุด: {{ now()->format('d/m/Y H:i') }} น.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.trends.keywords') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl transition-all flex items-center gap-2 font-semibold">
                    <i class="fa-solid fa-hashtag"></i>
                    Keywords
                </a>
                <a href="{{ route('admin.trends.sources.index') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl transition-all flex items-center gap-2 font-semibold">
                    <i class="fa-solid fa-globe"></i>
                    Sources
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Active Trends -->
        <div class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 p-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-400 to-indigo-600 dark:from-blue-600 dark:to-indigo-800 rounded-full opacity-10 group-hover:opacity-20 transition-opacity -mr-16 -mt-16"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-700 dark:from-blue-600 dark:to-indigo-800 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                        🔥
                    </div>
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-bold">
                        ทั้งหมด
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">เทรนด์ที่ใช้งาน</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_trends']) }}</p>
            </div>
        </div>

        <!-- Rising Trends -->
        <div class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 p-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-green-400 to-emerald-600 dark:from-green-600 dark:to-emerald-800 rounded-full opacity-10 group-hover:opacity-20 transition-opacity -mr-16 -mt-16"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-800 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                        📈
                    </div>
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-bold">
                        กำลังโด่งดัง
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Rising Trends</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['rising_trends']) }}</p>
            </div>
        </div>

        <!-- Trending Keywords -->
        <div class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 p-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-yellow-400 to-orange-600 dark:from-yellow-600 dark:to-orange-800 rounded-full opacity-10 group-hover:opacity-20 transition-opacity -mr-16 -mt-16"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-700 dark:from-yellow-600 dark:to-orange-800 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                        #️⃣
                    </div>
                    <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-xs font-bold">
                        HOT
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">Trending Keywords</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['trending_keywords']) }}</p>
            </div>
        </div>

        <!-- New Trends (24h) -->
        <div class="group relative bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 p-6 overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-purple-400 to-pink-600 dark:from-purple-600 dark:to-pink-800 rounded-full opacity-10 group-hover:opacity-20 transition-opacity -mr-16 -mt-16"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-700 dark:from-purple-600 dark:to-pink-800 rounded-xl flex items-center justify-center text-2xl shadow-lg">
                        ⚡
                    </div>
                    <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full text-xs font-bold">
                        24 ชม.
                    </span>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm font-medium mb-1">เทรนด์ใหม่</p>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['new_trends_24h']) }}</p>
            </div>
        </div>
    </div>

    <!-- Rising Trends Table -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-rose-500 to-red-600 dark:from-rose-600 dark:to-red-700 rounded-xl flex items-center justify-center text-white">
                    <i class="fa-solid fa-fire text-lg"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Rising Trends</h3>
            </div>
            <span class="px-4 py-2 bg-rose-100 dark:bg-rose-900 text-rose-800 dark:text-rose-200 rounded-xl text-sm font-bold">
                กำลังโด่งดัง
            </span>
        </div>

        @if(count($rising_trends) > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                        <th class="text-left py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">หัวข้อ</th>
                        <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Keyword</th>
                        <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Viral Score</th>
                        <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Mentions</th>
                        <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Velocity</th>
                        <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">อายุ</th>
                        <th class="text-center py-3 px-4 text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider">การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($rising_trends as $trend)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <td class="py-4 px-4">
                            <div class="font-semibold text-gray-900 dark:text-white">{{ Str::limit($trend['title'], 50) }}</div>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-3 py-1 bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900 dark:to-pink-900 text-purple-800 dark:text-purple-200 rounded-full text-xs font-bold">
                                #{{ $trend['keyword'] }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="text-2xl">🔥</div>
                                <span class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($trend['viral_score'], 2) }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($trend['total_mentions']) }}</span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-bold">
                                {{ number_format($trend['velocity'], 2) }}/hr
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-bold
                                {{ $trend['status'] === 'rising' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : '' }}
                                {{ $trend['status'] === 'stable' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' : '' }}
                                {{ $trend['status'] === 'declining' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' : '' }}">
                                {{ ucfirst($trend['status']) }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $trend['age_hours'] }}h</span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <a href="{{ route('admin.trends.show', $trend['id']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg text-xs font-bold transition-all transform hover:scale-105">
                                <i class="fa-solid fa-eye"></i>
                                ดูรายละเอียด
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-12">
            <div class="text-8xl mb-4">📊</div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบ Rising Trends</h3>
            <p class="text-gray-600 dark:text-gray-400">ยังไม่มีเทรนด์ที่กำลังโด่งดังในขณะนี้</p>
        </div>
        @endif
    </div>

    <!-- Top Keywords Grid -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-yellow-500 to-orange-600 dark:from-yellow-600 dark:to-orange-700 rounded-xl flex items-center justify-center text-white">
                    <i class="fa-solid fa-hashtag text-lg"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Top Keywords</h3>
            </div>
            <a href="{{ route('admin.trends.keywords') }}" class="px-4 py-2 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900 dark:hover:bg-yellow-800 text-yellow-800 dark:text-yellow-200 rounded-xl text-sm font-bold transition-colors">
                ดูทั้งหมด →
            </a>
        </div>

        @if(count($top_keywords) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($top_keywords as $keyword)
            <div class="group bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-600 rounded-xl p-5 hover:shadow-lg transition-all duration-300 hover:-translate-y-1 border border-gray-200 dark:border-gray-600">
                <div class="flex items-start justify-between mb-3">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white">#{{ $keyword['keyword'] }}</h4>
                    <span class="px-2 py-1 bg-yellow-500 text-white rounded-full text-xs font-bold">
                        {{ number_format($keyword['trend_score'], 1) }}
                    </span>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">ความถี่:</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($keyword['frequency']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">การเติบโต:</span>
                        <span class="font-bold {{ $keyword['growth_rate'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $keyword['growth_rate'] >= 0 ? '↑' : '↓' }} {{ number_format(abs($keyword['growth_rate']), 2) }}%
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <div class="text-8xl mb-4">#️⃣</div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบ Keywords</h3>
            <p class="text-gray-600 dark:text-gray-400">ยังไม่มี Keywords ที่กำลังเป็นที่นิยม</p>
        </div>
        @endif
    </div>

    <!-- Trending Now -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-rose-600 dark:from-pink-600 dark:to-rose-700 rounded-xl flex items-center justify-center text-white">
                <i class="fa-solid fa-chart-line text-lg"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Trending Now</h3>
            <span class="px-3 py-1 bg-pink-100 dark:bg-pink-900 text-pink-800 dark:text-pink-200 rounded-full text-xs font-bold animate-pulse">
                LIVE
            </span>
        </div>

        @if(count($trending_now) > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($trending_now as $trend)
            <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-slate-700 dark:to-slate-600 rounded-xl p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-200 dark:border-gray-600">
                <div class="flex items-start justify-between mb-3">
                    <h4 class="text-base font-bold text-gray-900 dark:text-white flex-1 pr-3">
                        {{ Str::limit($trend['title'], 60) }}
                    </h4>
                    <div class="text-3xl">🔥</div>
                </div>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="px-3 py-1 bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900 dark:to-pink-900 text-purple-800 dark:text-purple-200 rounded-full text-xs font-bold">
                        #{{ $trend['keyword'] }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold
                        {{ $trend['status'] === 'rising' ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : '' }}
                        {{ $trend['status'] === 'stable' ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' : '' }}
                        {{ $trend['status'] === 'declining' ? 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200' : '' }}">
                        {{ ucfirst($trend['status']) }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Score:</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($trend['viral_score'], 2) }}</span>
                    </div>
                    <a href="{{ route('admin.trends.show', $trend['id']) }}" class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg text-xs font-bold transition-all transform hover:scale-105">
                        Details →
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <div class="text-8xl mb-4">📈</div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ไม่มีเทรนด์ในขณะนี้</h3>
            <p class="text-gray-600 dark:text-gray-400">ยังไม่พบเทรนด์ที่กำลังมาแรงในตอนนี้</p>
        </div>
        @endif
    </div>

    <!-- Trend Chart -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📊 Trend Analytics (7 วันล่าสุด)</h3>
        <div style="height: 350px;">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#9ca3af' : '#6b7280';
    const gridColor = isDark ? '#374151' : '#e5e7eb';

    // Trend Chart
    const ctx = document.getElementById('trendChart');

    // Generate mock data for the last 7 days
    const days = [];
    const viralScoreData = [];
    const mentionsData = [];
    const velocityData = [];

    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        days.push(date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' }));
        viralScoreData.push(Math.floor(Math.random() * 50) + 50);
        mentionsData.push(Math.floor(Math.random() * 500) + 200);
        velocityData.push(Math.floor(Math.random() * 30) + 10);
    }

    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: days,
                datasets: [
                    {
                        label: 'Viral Score',
                        data: viralScoreData,
                        borderColor: 'rgb(236, 72, 153)',
                        backgroundColor: 'rgba(236, 72, 153, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Total Mentions',
                        data: mentionsData,
                        borderColor: 'rgb(139, 92, 246)',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Velocity (/hr)',
                        data: velocityData,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3,
                        yAxisID: 'y'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: textColor }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1e293b' : '#ffffff',
                        titleColor: isDark ? '#ffffff' : '#000000',
                        bodyColor: isDark ? '#ffffff' : '#000000',
                        borderColor: gridColor,
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { color: textColor }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        beginAtZero: true,
                        grid: { drawOnChartArea: false },
                        ticks: { color: textColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }

    // Auto-refresh every 5 minutes
    setTimeout(function() {
        location.reload();
    }, 300000);
});
</script>
@endpush
