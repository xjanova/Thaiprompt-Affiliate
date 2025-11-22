@extends('layouts.admin-v3')

@section('title', 'Sentiment Details')

@push('styles')
<style>
    /* Gauge chart container */
    .gauge-container {
        position: relative;
        width: 200px;
        height: 200px;
        margin: 0 auto;
    }

    /* Keyword highlight animation */
    .keyword-highlight {
        animation: highlight-pulse 2s ease-in-out infinite;
    }

    @keyframes highlight-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    /* Glassmorphism card */
    .glass-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .dark .glass-card {
        background: rgba(17, 24, 39, 0.5);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* 3D card effect */
    .card-3d {
        transition: transform 0.3s ease;
    }

    .card-3d:hover {
        transform: translateY(-5px) scale(1.02);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-8" x-data="sentimentDetails()">
    {{-- Back Button --}}
    <div class="mb-6">
        <a href="{{ route('admin.line-bot.keywords.sentiment-analysis.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300
           hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600
           transition-all shadow-sm hover:shadow-md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            กลับ
        </a>
    </div>

    {{-- Header with gradient --}}
    <div class="mb-8 relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-500 via-purple-500 to-pink-500 p-8">
        <div class="relative z-10">
            <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                <span class="text-5xl">📊</span>
                Sentiment Analysis Details
            </h1>
            <p class="text-white/90 text-lg">
                รายละเอียดการวิเคราะห์ความรู้สึกของข้อความ
            </p>
        </div>

        {{-- Animated background circles --}}
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/10 rounded-full -ml-24 -mb-24"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Message Section with keyword highlights --}}
            <div class="card-3d bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>💬</span>
                    ข้อความ
                </h2>
                <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl p-6 border-l-4 border-blue-500">
                    <p class="text-lg text-gray-900 dark:text-gray-100 leading-relaxed" x-html="highlightedMessage">
                        {{ $sentiment->user_message }}
                    </p>
                    <div class="flex items-center gap-3 mt-4 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ $sentiment->created_at->format('d/m/Y H:i:s') }}
                    </div>
                </div>
            </div>

            {{-- Sentiment Score with Gauge Chart --}}
            <div class="card-3d bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <span>📈</span>
                    Sentiment Score
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Gauge Chart --}}
                    <div class="flex flex-col items-center justify-center">
                        <div class="gauge-container mb-4">
                            <canvas id="gaugeChart"></canvas>
                        </div>
                        <p class="text-center">
                            <span class="text-4xl font-bold
                                @if($sentiment->sentiment === 'positive') text-green-600 dark:text-green-400
                                @elseif($sentiment->sentiment === 'negative') text-red-600 dark:text-red-400
                                @else text-gray-600 dark:text-gray-400 @endif">
                                {{ round(($sentiment->sentiment_score + 1) / 2 * 100, 1) }}
                            </span>
                            <span class="text-lg text-gray-500 dark:text-gray-400">/100</span>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            Overall Sentiment Score
                        </p>
                    </div>

                    {{-- Score Details --}}
                    <div class="space-y-4">
                        {{-- Raw Score --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Raw Score</span>
                                <span class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ round($sentiment->sentiment_score, 3) }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="bg-gradient-to-r from-red-500 via-yellow-500 to-green-500 h-3 rounded-full transition-all"
                                    style="width: {{ ($sentiment->sentiment_score + 1) / 2 * 100 }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Range: -1 (very negative) to 1 (very positive)
                            </p>
                        </div>

                        {{-- Confidence --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Confidence</span>
                                <span class="text-lg font-bold text-green-600 dark:text-green-400">
                                    {{ round($sentiment->confidence, 1) }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="bg-gradient-to-r from-green-400 to-green-600 h-3 rounded-full transition-all"
                                    style="width: {{ min($sentiment->confidence, 100) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                How confident we are in this analysis
                            </p>
                        </div>

                        {{-- Classification --}}
                        <div class="bg-gradient-to-r
                            @if($sentiment->sentiment === 'positive') from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border-green-500
                            @elseif($sentiment->sentiment === 'negative') from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 border-red-500
                            @else from-gray-50 to-gray-100 dark:from-gray-700/20 dark:to-gray-600/20 border-gray-500 @endif
                            border-l-4 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Classification:</p>
                            <p class="text-2xl font-bold
                                @if($sentiment->sentiment === 'positive') text-green-700 dark:text-green-300
                                @elseif($sentiment->sentiment === 'negative') text-red-700 dark:text-red-300
                                @else text-gray-700 dark:text-gray-300 @endif">
                                @if($sentiment->sentiment === 'positive')
                                    😊 Positive
                                @elseif($sentiment->sentiment === 'negative')
                                    😠 Negative
                                @else
                                    😐 Neutral
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Keywords --}}
            @if($sentiment->positive_keywords || $sentiment->negative_keywords || $sentiment->neutral_keywords)
            <div class="card-3d bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🏷️</span>
                    Keywords
                </h2>

                <div class="space-y-6">
                    @if($sentiment->positive_keywords)
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-5 border border-green-200 dark:border-green-700">
                        <p class="text-sm font-semibold text-green-800 dark:text-green-300 mb-3 flex items-center gap-2">
                            <span>😊</span>
                            Positive Keywords
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($sentiment->positive_keywords as $keyword)
                                <span class="px-4 py-2 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm font-medium shadow-sm">
                                    {{ $keyword }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($sentiment->negative_keywords)
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-5 border border-red-200 dark:border-red-700">
                        <p class="text-sm font-semibold text-red-800 dark:text-red-300 mb-3 flex items-center gap-2">
                            <span>😠</span>
                            Negative Keywords
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($sentiment->negative_keywords as $keyword)
                                <span class="px-4 py-2 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full text-sm font-medium shadow-sm">
                                    {{ $keyword }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($sentiment->neutral_keywords)
                    <div class="bg-gray-50 dark:bg-gray-700/20 rounded-xl p-5 border border-gray-200 dark:border-gray-600">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-300 mb-3 flex items-center gap-2">
                            <span>😐</span>
                            Neutral Keywords
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($sentiment->neutral_keywords as $keyword)
                                <span class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full text-sm font-medium shadow-sm">
                                    {{ $keyword }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Detected Issues --}}
            @if($sentiment->detected_issues)
            <div class="card-3d bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🚨</span>
                    Detected Issues
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($sentiment->detected_issues as $issue)
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-700">
                            <p class="font-semibold text-orange-800 dark:text-orange-200">
                                {{ ucfirst($issue) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Emotions Chart --}}
            <div class="card-3d bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎭</span>
                    Emotion Analysis
                </h2>

                <div class="h-80">
                    <canvas id="emotionsChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Sentiment Status --}}
            <div class="card-3d bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>📊</span>
                    Status
                </h2>

                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sentiment:</span>
                        @if($sentiment->sentiment === 'positive')
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm font-semibold">
                                😊 Positive
                            </span>
                        @elseif($sentiment->sentiment === 'negative')
                            <span class="px-3 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full text-sm font-semibold">
                                😠 Negative
                            </span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full text-sm font-semibold">
                                😐 Neutral
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Complaint:</span>
                        @if($sentiment->is_complaint)
                            <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full text-sm font-semibold">
                                🗣️ Yes
                            </span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full text-sm font-semibold">
                                ✅ No
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Urgent:</span>
                        @if($sentiment->is_urgent)
                            <span class="px-3 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full text-sm font-semibold">
                                🔴 Yes
                            </span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full text-sm font-semibold">
                                ✅ No
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Emotions Breakdown --}}
            <div class="card-3d bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>😊</span>
                    Emotions
                </h2>

                <div class="space-y-4">
                    @php
                        $emotions = [
                            'joy' => ['label' => '😊 Joy', 'score' => $sentiment->joy_score ?? 0, 'color' => 'bg-yellow-500'],
                            'anger' => ['label' => '😠 Anger', 'score' => $sentiment->anger_score ?? 0, 'color' => 'bg-red-500'],
                            'sadness' => ['label' => '😢 Sadness', 'score' => $sentiment->sadness_score ?? 0, 'color' => 'bg-blue-500'],
                            'fear' => ['label' => '😨 Fear', 'score' => $sentiment->fear_score ?? 0, 'color' => 'bg-purple-500'],
                            'surprise' => ['label' => '😮 Surprise', 'score' => $sentiment->surprise_score ?? 0, 'color' => 'bg-pink-500'],
                        ];
                    @endphp

                    @foreach($emotions as $emotion => $data)
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $data['label'] }}
                                </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ round($data['score'], 0) }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                <div class="{{ $data['color'] }} h-3 rounded-full transition-all"
                                    style="width: {{ min($data['score'], 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Metadata --}}
            <div class="card-3d bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>ℹ️</span>
                    Information
                </h2>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                        <span class="text-gray-600 dark:text-gray-400">ID:</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ $sentiment->id }}</span>
                    </div>

                    <div class="flex justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                        <span class="text-gray-600 dark:text-gray-400">User ID:</span>
                        <span class="font-mono text-gray-900 dark:text-white text-xs truncate max-w-[150px]" title="{{ $sentiment->line_user_id }}">
                            {{ $sentiment->line_user_id }}
                        </span>
                    </div>

                    <div class="flex justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                        <span class="text-gray-600 dark:text-gray-400">Language:</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ strtoupper($sentiment->language ?? 'TH') }}</span>
                    </div>

                    @if($sentiment->primary_issue)
                    <div class="flex justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                        <span class="text-gray-600 dark:text-gray-400">Primary Issue:</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ $sentiment->primary_issue }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                        <span class="text-gray-600 dark:text-gray-400">Analyzed:</span>
                        <span class="text-gray-900 dark:text-white">{{ $sentiment->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="card-3d bg-gradient-to-br from-line-green/10 to-green-100/10 dark:from-line-green/5 dark:to-green-900/20 rounded-2xl shadow-xl p-6 border border-line-green/30">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>⚡</span>
                    Quick Actions
                </h2>

                <div class="space-y-2">
                    <button @click="markAsResolved()"
                        class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Mark as Resolved
                    </button>

                    <button @click="exportData()"
                        class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    /**
     * Alpine.js Component สำหรับ Sentiment Details
     */
    function sentimentDetails() {
        return {
            message: @json($sentiment->user_message),
            positiveKeywords: @json($sentiment->positive_keywords ?? []),
            negativeKeywords: @json($sentiment->negative_keywords ?? []),

            /**
             * Highlight keywords ในข้อความ
             */
            get highlightedMessage() {
                let msg = this.message;

                // Highlight positive keywords
                this.positiveKeywords.forEach(keyword => {
                    const regex = new RegExp(`(${keyword})`, 'gi');
                    msg = msg.replace(regex, '<span class="keyword-highlight bg-green-200 dark:bg-green-800 px-1 rounded font-semibold">$1</span>');
                });

                // Highlight negative keywords
                this.negativeKeywords.forEach(keyword => {
                    const regex = new RegExp(`(${keyword})`, 'gi');
                    msg = msg.replace(regex, '<span class="keyword-highlight bg-red-200 dark:bg-red-800 px-1 rounded font-semibold">$1</span>');
                });

                return msg;
            },

            /**
             * Mark sentiment as resolved
             */
            markAsResolved() {
                if (confirm('คุณต้องการทำเครื่องหมายว่าแก้ไขแล้วใช่หรือไม่?')) {
                    // TODO: Implement API call
                    alert('✅ ทำเครื่องหมายแล้ว');
                }
            },

            /**
             * Export sentiment data
             */
            exportData() {
                alert('📥 กำลัง export ข้อมูล...');
                // TODO: Implement export functionality
            }
        }
    }

    /**
     * Gauge Chart สำหรับ Sentiment Score
     */
    const gaugeCtx = document.getElementById('gaugeChart');
    if (gaugeCtx) {
        const score = {{ ($sentiment->sentiment_score + 1) / 2 * 100 }};
        const sentiment = '{{ $sentiment->sentiment }}';

        // สีตาม sentiment
        let color;
        if (sentiment === 'positive') {
            color = 'rgb(34, 197, 94)'; // Green
        } else if (sentiment === 'negative') {
            color = 'rgb(239, 68, 68)'; // Red
        } else {
            color = 'rgb(156, 163, 175)'; // Gray
        }

        new Chart(gaugeCtx, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [score, 100 - score],
                    backgroundColor: [color, 'rgba(229, 231, 235, 0.3)'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                circumference: 180,
                rotation: -90,
                cutout: '75%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            }
        });
    }

    /**
     * Emotions Radar Chart
     */
    const emotionsCtx = document.getElementById('emotionsChart');
    if (emotionsCtx) {
        new Chart(emotionsCtx, {
            type: 'radar',
            data: {
                labels: ['😊 Joy', '😠 Anger', '😢 Sadness', '😨 Fear', '😮 Surprise'],
                datasets: [{
                    label: 'Emotion Scores',
                    data: [
                        {{ $sentiment->joy_score ?? 0 }},
                        {{ $sentiment->anger_score ?? 0 }},
                        {{ $sentiment->sadness_score ?? 0 }},
                        {{ $sentiment->fear_score ?? 0 }},
                        {{ $sentiment->surprise_score ?? 0 }}
                    ],
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(59, 130, 246)',
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
</script>
@endpush
@endsection
