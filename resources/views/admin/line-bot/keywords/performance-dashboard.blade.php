@extends('layouts.admin-v3')

@section('title', 'ผลการทำงาน Keywords')

@section('content')
<div class="container-fluid px-4 py-8">
    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                📊 ผลการทำงาน Keywords
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                วิเคราะห์ประสิทธิภาพ keywords อย่างละเอียด
            </p>
        </div>
        <a href="{{ route('admin.line-bot.keywords.performance.export') }}"
            class="px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2m0 0v-8m0 8l-4-2m4 2l4-2"></path>
            </svg>
            Export Report
        </a>
    </div>

    {{-- Key Metrics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        {{-- Total Keywords --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">รวม Keywords</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                        {{ $metrics['totalKeywords'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center text-2xl">
                    🔑
                </div>
            </div>
        </div>

        {{-- Used Keywords --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Keywords ที่ใช้</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                        {{ $metrics['usedKeywords'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center text-2xl">
                    ✅
                </div>
            </div>
        </div>

        {{-- Unused Keywords --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Keywords ไม่ใช้</p>
                    <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                        {{ $metrics['unusedKeywords'] }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center text-2xl">
                    ❌
                </div>
            </div>
        </div>

        {{-- Coverage Percentage --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Coverage</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                        {{ $metrics['coveragePercentage'] }}%
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center text-2xl">
                    📈
                </div>
            </div>
        </div>

        {{-- Avg Response Time Savings --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">เร็วขึ้น</p>
                    <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">
                        95%
                    </p>
                </div>
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center text-2xl">
                    ⚡
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        {{-- Top Keywords by Usage --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                🏆 Top Keywords by Usage
            </h2>
            <div class="h-80">
                <canvas id="topKeywordsChart"></canvas>
            </div>
        </div>

        {{-- Category Performance --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                📂 Performance by Category
            </h2>
            <div class="space-y-4">
                @foreach($categoryPerformance as $category => $data)
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <div class="flex justify-between items-center mb-2">
                        <h3 class="font-semibold text-gray-900 dark:text-white capitalize">
                            {{ $category }}
                        </h3>
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                            {{ $data['matches'] }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span>Keywords: {{ $data['keywords'] }}</span>
                        <span>Avg: {{ $data['avgMatches'] }}</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-2 rounded-full"
                            style="width: {{ min(($data['matches'] / 100) * 100, 100) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Effectiveness Scores --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            ⭐ Effectiveness Scores (Top 15)
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3">
            @foreach($metrics['effectivenessScores'] as $keyword => $score)
            <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-700 dark:to-gray-600 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm truncate">
                        {{ $keyword }}
                    </h3>
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                        {{ $score }}
                    </span>
                </div>
                <div class="w-full bg-gray-300 dark:bg-gray-700 rounded-full h-1.5">
                    <div class="bg-gradient-to-r from-blue-400 to-purple-600 h-1.5 rounded-full"
                        style="width: {{ $score }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Keyword vs AI Comparison --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        {{-- Match Rate Comparison --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                🎯 Keyword vs AI Comparison
            </h2>
            <div class="h-80">
                <canvas id="comparisonChart"></canvas>
            </div>
        </div>

        {{-- Response Time Comparison --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                ⏱️ Response Time Comparison
            </h2>
            <div class="space-y-4">
                <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-900 dark:text-white font-semibold">Keyword Response</span>
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            10-50 ms
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        ✅ ตัวเลือกที่เร็วที่สุด
                    </p>
                </div>

                <div class="bg-orange-50 dark:bg-orange-900 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-900 dark:text-white font-semibold">AI Response</span>
                        <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            1-3 seconds
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        ❌ ช้า แต่ยืดหยุ่นมากขึ้น
                    </p>
                </div>

                <div class="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-900 dark:text-white font-semibold">ประหยัด</span>
                        <span class="text-2xl font-bold text-green-600 dark:text-green-400">
                            95%+ เร็วขึ้น
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        🚀 การปรับปรุงความเร็ว
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Keywords Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            📋 Detailed Keyword Performance
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Keyword
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Times Matched
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Category
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Priority
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Last Matched
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Score
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($keywords as $keyword)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $keyword->keyword }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                {{ $keyword->times_matched }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 capitalize">
                            {{ $keyword->category }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex items-center gap-2">
                                <div class="w-16 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                    <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-2 rounded-full"
                                        style="width: {{ $keyword->priority }}%"></div>
                                </div>
                                <span class="text-sm">{{ $keyword->priority }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            @if($keyword->last_matched_at)
                                {{ $keyword->last_matched_at->diffForHumans() }}
                            @else
                                ไม่เคยใช้
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold
                                @if($metrics['effectivenessScores'][$keyword->keyword] >= 80)
                                    bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200
                                @elseif($metrics['effectivenessScores'][$keyword->keyword] >= 60)
                                    bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200
                                @else
                                    bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200
                                @endif">
                                {{ $metrics['effectivenessScores'][$keyword->keyword] ?? 0 }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            ไม่มี keywords ที่เปิดใช้งาน
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Trend Analysis Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
            📈 30-Day Trend Analysis
        </h2>
        <div class="h-80">
            <canvas id="trendChart"></canvas>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Top Keywords Chart
    fetch('{{ route("admin.line-bot.keywords.performance.chart-data") }}')
        .then(r => r.json())
        .then(data => {
            const ctx = document.getElementById('topKeywordsChart').getContext('2d');
            new Chart(ctx, {
                type: 'horizontalBar',
                data: {
                    labels: Object.keys(data.topKeywords),
                    datasets: [{
                        label: 'Matches',
                        data: Object.values(data.topKeywords),
                        backgroundColor: [
                            '#3b82f6', '#8b5cf6', '#ec4899', '#f97316', '#eab308',
                            '#22c55e', '#06b6d4', '#0ea5e9', '#6366f1', '#a855f7'
                        ],
                        borderRadius: 5,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });

    // Comparison Chart
    fetch('{{ route("admin.line-bot.keywords.performance.comparison-data") }}')
        .then(r => r.json())
        .then(data => {
            const ctx = document.getElementById('comparisonChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Keyword Matches', 'AI Fallback'],
                    datasets: [{
                        data: [
                            data.comparison.keyword_matches,
                            data.comparison.ai_fallback
                        ],
                        backgroundColor: [
                            '#22c55e',
                            '#ef4444'
                        ],
                        borderColor: ['#16a34a', '#dc2626'],
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });

    // Trend Chart
    fetch('{{ route("admin.line-bot.keywords.performance.trend-data") }}')
        .then(r => r.json())
        .then(data => {
            const dates = Object.keys(data.data);
            const matches = Object.values(data.data).map(d => d.matches);
            const noMatches = Object.values(data.data).map(d => d.no_matches);

            const ctx = document.getElementById('trendChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Keyword Matches',
                            data: matches,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4,
                            fill: true,
                        },
                        {
                            label: 'AI Fallback',
                            data: noMatches,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
</script>
@endpush
@endsection
