@extends('layouts.admin-v3')

@section('title', 'Sentiment Analysis')

@push('styles')
<style>
    /* Glassmorphism effect สำหรับ sentiment cards */
    .sentiment-glass {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .dark .sentiment-glass {
        background: rgba(17, 24, 39, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* 3D Card hover effect */
    .card-3d {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card-3d:hover {
        transform: translateY(-5px) rotateX(5deg);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-8" x-data="sentimentAnalysis()">
    {{-- Header with gradient --}}
    <div class="mb-8 relative overflow-hidden rounded-2xl bg-gradient-to-r from-green-400 via-blue-500 to-purple-600 p-8">
        <div class="relative z-10">
            <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                <span class="text-5xl">😊</span>
                Sentiment Analysis
            </h1>
            <p class="text-white/90 text-lg">
                วิเคราะห์ความรู้สึกและอารมณ์จากข้อความผู้ใช้
            </p>
        </div>

        {{-- Animated background circles --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>
    </div>

    {{-- Statistics Cards with 3D effect --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        {{-- Total Messages --}}
        <div class="card-3d bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center text-2xl">
                    📊
                </div>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Total</span>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">
                {{ number_format($statistics['total_messages']) }}
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400">ทั้งหมด</p>
        </div>

        {{-- Positive --}}
        <div class="card-3d bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/30 rounded-xl p-6 border-2 border-green-300 dark:border-green-700 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center text-2xl shadow-md">
                    😊
                </div>
                <span class="text-xs font-semibold text-green-700 dark:text-green-300 uppercase">Positive</span>
            </div>
            <p class="text-3xl font-bold text-green-700 dark:text-green-300 mb-1">
                {{ $statistics['positive_percentage'] }}%
            </p>
            <p class="text-sm text-green-600 dark:text-green-400">
                {{ number_format($statistics['positive_count']) }} messages
            </p>
        </div>

        {{-- Negative --}}
        <div class="card-3d bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-800/30 rounded-xl p-6 border-2 border-red-300 dark:border-red-700 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center text-2xl shadow-md">
                    😠
                </div>
                <span class="text-xs font-semibold text-red-700 dark:text-red-300 uppercase">Negative</span>
            </div>
            <p class="text-3xl font-bold text-red-700 dark:text-red-300 mb-1">
                {{ $statistics['negative_percentage'] }}%
            </p>
            <p class="text-sm text-red-600 dark:text-red-400">
                {{ number_format($statistics['negative_count']) }} messages
            </p>
        </div>

        {{-- Neutral --}}
        <div class="card-3d bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/30 dark:to-gray-600/30 rounded-xl p-6 border-2 border-gray-300 dark:border-gray-600 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gray-500 rounded-lg flex items-center justify-center text-2xl shadow-md">
                    😐
                </div>
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Neutral</span>
            </div>
            <p class="text-3xl font-bold text-gray-700 dark:text-gray-300 mb-1">
                {{ $statistics['neutral_percentage'] }}%
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ number_format($statistics['neutral_count']) }} messages
            </p>
        </div>

        {{-- Complaints --}}
        <div class="card-3d bg-gradient-to-br from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 rounded-xl p-6 border-2 border-orange-300 dark:border-orange-700 shadow-lg">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-orange-500 rounded-lg flex items-center justify-center text-2xl shadow-md">
                    🗣️
                </div>
                <span class="text-xs font-semibold text-orange-700 dark:text-orange-300 uppercase">Complaints</span>
            </div>
            <p class="text-3xl font-bold text-orange-700 dark:text-orange-300 mb-1">
                {{ number_format($statistics['complaint_count']) }}
            </p>
            <p class="text-sm text-orange-600 dark:text-orange-400">ข้อร้องเรียน</p>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Sentiment Distribution Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📊</span>
                Sentiment Distribution
            </h2>
            <div class="h-80 flex items-center justify-center">
                <canvas id="sentimentDistributionChart"></canvas>
            </div>
        </div>

        {{-- Sentiment Trend Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📈</span>
                Sentiment Trend (7 Days)
            </h2>
            <div class="h-80">
                <canvas id="sentimentTrendChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Keywords Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Top Positive Keywords --}}
        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-2xl shadow-xl p-6 border border-green-200 dark:border-green-700">
            <h3 class="text-xl font-bold text-green-800 dark:text-green-300 mb-4 flex items-center gap-2">
                <span>😊</span>
                Top Positive Keywords
            </h3>
            <div class="space-y-2">
                @php
                    $positiveKeywords = [
                        ['keyword' => 'ดีมาก', 'count' => 125],
                        ['keyword' => 'ชอบ', 'count' => 98],
                        ['keyword' => 'สุดยอด', 'count' => 87],
                        ['keyword' => 'ประทับใจ', 'count' => 76],
                        ['keyword' => 'ขอบคุณ', 'count' => 65],
                    ];
                @endphp
                @foreach($positiveKeywords as $item)
                <div class="flex items-center justify-between bg-white/50 dark:bg-gray-800/50 rounded-lg p-3">
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $item['keyword'] }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full transition-all" style="width: {{ ($item['count'] / 125) * 100 }}%"></div>
                        </div>
                        <span class="text-sm font-bold text-green-700 dark:text-green-300">{{ $item['count'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Top Negative Keywords --}}
        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-2xl shadow-xl p-6 border border-red-200 dark:border-red-700">
            <h3 class="text-xl font-bold text-red-800 dark:text-red-300 mb-4 flex items-center gap-2">
                <span>😠</span>
                Top Negative Keywords
            </h3>
            <div class="space-y-2">
                @php
                    $negativeKeywords = [
                        ['keyword' => 'แย่', 'count' => 45],
                        ['keyword' => 'ช้า', 'count' => 38],
                        ['keyword' => 'ผิดหวัง', 'count' => 32],
                        ['keyword' => 'ไม่ชอบ', 'count' => 28],
                        ['keyword' => 'แก้ไม่ได้', 'count' => 21],
                    ];
                @endphp
                @foreach($negativeKeywords as $item)
                <div class="flex items-center justify-between bg-white/50 dark:bg-gray-800/50 rounded-lg p-3">
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $item['keyword'] }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-red-500 h-2 rounded-full transition-all" style="width: {{ ($item['count'] / 45) * 100 }}%"></div>
                        </div>
                        <span class="text-sm font-bold text-red-700 dark:text-red-300">{{ $item['count'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Recommendations --}}
    @if(count($recommendations) > 0)
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">🎯 Recommendations</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($recommendations as $rec)
            <div class="sentiment-glass rounded-xl p-5 border-l-4
                @if($rec['type'] === 'warning') border-yellow-500
                @elseif($rec['type'] === 'danger') border-red-500
                @elseif($rec['type'] === 'info') border-blue-500
                @else border-green-500 @endif">
                <p class="font-semibold text-gray-900 dark:text-white mb-2">
                    {{ $rec['message'] }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $rec['action'] }}
                </p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filters with Alpine.js --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl mb-8 p-6 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">🔍 Filters</h3>
            <button @click="resetFilters()" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                Reset
            </button>
        </div>

        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Days --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        ช่วงเวลา
                    </label>
                    <select name="days" x-model="filters.days"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                        focus:ring-2 focus:ring-line-green focus:border-transparent transition">
                        <option value="7" {{ $days === 7 ? 'selected' : '' }}>7 วันที่ผ่านมา</option>
                        <option value="14" {{ $days === 14 ? 'selected' : '' }}>14 วันที่ผ่านมา</option>
                        <option value="30" {{ $days === 30 ? 'selected' : '' }}>30 วันที่ผ่านมา</option>
                        <option value="60" {{ $days === 60 ? 'selected' : '' }}>60 วันที่ผ่านมา</option>
                        <option value="90" {{ $days === 90 ? 'selected' : '' }}>90 วันที่ผ่านมา</option>
                    </select>
                </div>

                {{-- Sentiment Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Sentiment
                    </label>
                    <select name="sentiment" x-model="filters.sentiment"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                        focus:ring-2 focus:ring-line-green focus:border-transparent transition">
                        <option value="">ทั้งหมด</option>
                        <option value="positive" {{ $filter_sentiment === 'positive' ? 'selected' : '' }}>😊 Positive</option>
                        <option value="negative" {{ $filter_sentiment === 'negative' ? 'selected' : '' }}>😠 Negative</option>
                        <option value="neutral" {{ $filter_sentiment === 'neutral' ? 'selected' : '' }}>😐 Neutral</option>
                    </select>
                </div>

                {{-- Type Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        ประเภท
                    </label>
                    <select name="type" x-model="filters.type"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                        focus:ring-2 focus:ring-line-green focus:border-transparent transition">
                        <option value="">ทั้งหมด</option>
                        <option value="complaints" {{ $filter_type === 'complaints' ? 'selected' : '' }}>🗣️ ข้อร้องเรียน</option>
                        <option value="urgent" {{ $filter_type === 'urgent' ? 'selected' : '' }}>🔴 เรื่องด่วน</option>
                    </select>
                </div>

                {{-- Search Button --}}
                <div class="flex items-end">
                    <button type="submit"
                        class="w-full px-6 py-2.5 bg-gradient-to-r from-line-green to-green-600
                        hover:from-green-600 hover:to-line-green text-white rounded-lg font-medium
                        transition-all transform hover:scale-105 shadow-lg">
                        🔍 ค้นหา
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Sentiments Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Sentiment
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ข้อความ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ปัญหา
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Score
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sentiments as $sentiment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all">
                            <td class="px-6 py-4 text-sm">
                                @if($sentiment->sentiment === 'positive')
                                    <span class="px-3 py-1.5 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                                        <span>😊</span> Positive
                                    </span>
                                @elseif($sentiment->sentiment === 'negative')
                                    <span class="px-3 py-1.5 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                                        <span>😠</span> Negative
                                    </span>
                                @else
                                    <span class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                                        <span>😐</span> Neutral
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 max-w-xs">
                                <p class="truncate">{{ $sentiment->user_message }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                @if($sentiment->detected_issues)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($sentiment->detected_issues as $issue)
                                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">
                                                {{ $issue }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                        <div class="@if($sentiment->sentiment === 'positive') bg-green-500 @elseif($sentiment->sentiment === 'negative') bg-red-500 @else bg-gray-500 @endif h-2.5 rounded-full transition-all"
                                            style="width: {{ abs(($sentiment->sentiment_score + 1) / 2 * 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        {{ round($sentiment->sentiment_score, 2) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex flex-wrap gap-1">
                                    @if($sentiment->is_complaint)
                                        <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full text-xs font-semibold">
                                            🗣️ Complaint
                                        </span>
                                    @endif
                                    @if($sentiment->is_urgent)
                                        <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full text-xs font-semibold">
                                            🔴 Urgent
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('admin.line-bot.keywords.sentiment-analysis.show', $sentiment) }}"
                                   class="text-line-green dark:text-green-400 hover:underline font-medium">
                                    ดูรายละเอียด →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="text-6xl mb-4">📭</div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">ไม่มีข้อมูล sentiment</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">ลองเปลี่ยนตัวกรองเพื่อดูข้อมูลอื่น</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($sentiments->hasPages())
            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $sentiments->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    /**
     * Alpine.js Component สำหรับ Sentiment Analysis
     */
    function sentimentAnalysis() {
        return {
            filters: {
                days: '{{ $days }}',
                sentiment: '{{ $filter_sentiment }}',
                type: '{{ $filter_type }}'
            },

            /**
             * Reset filters
             */
            resetFilters() {
                this.filters = {
                    days: '7',
                    sentiment: '',
                    type: ''
                };
            }
        }
    }

    /**
     * Sentiment Distribution Chart (Doughnut)
     */
    const distributionCtx = document.getElementById('sentimentDistributionChart');
    if (distributionCtx) {
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['😊 Positive', '😠 Negative', '😐 Neutral'],
                datasets: [{
                    data: [
                        {{ $statistics['positive_count'] }},
                        {{ $statistics['negative_count'] }},
                        {{ $statistics['neutral_count'] }}
                    ],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',  // Green
                        'rgba(239, 68, 68, 0.8)',   // Red
                        'rgba(156, 163, 175, 0.8)'  // Gray
                    ],
                    borderColor: [
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                        'rgb(156, 163, 175)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 13,
                                weight: 'bold'
                            },
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Sentiment Trend Chart (Line)
     */
    const trendCtx = document.getElementById('sentimentTrendChart');
    if (trendCtx) {
        // Mock data สำหรับ 7 วัน
        const last7Days = [];
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            last7Days.push(date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' }));
        }

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: last7Days,
                datasets: [
                    {
                        label: '😊 Positive',
                        data: [45, 52, 48, 61, 55, 58, 62],
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: '😠 Negative',
                        data: [12, 15, 18, 14, 16, 13, 11],
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: '😐 Neutral',
                        data: [28, 25, 30, 22, 27, 24, 26],
                        borderColor: 'rgb(156, 163, 175)',
                        backgroundColor: 'rgba(156, 163, 175, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12,
                                weight: 'bold'
                            },
                            usePointStyle: true
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10
                        }
                    }
                }
            }
        });
    }
</script>
@endpush
@endsection
