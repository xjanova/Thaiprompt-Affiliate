@extends('layouts.admin')

@section('title', 'Sentiment Details')

@section('content')
<div class="container-fluid px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('admin.line-bot.keywords.sentiment-analysis.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline mb-4 inline-block">
            ← กลับ
        </a>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            📊 Sentiment Analysis Details
        </h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Message Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    💬 ข้อความ
                </h2>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border-l-4 border-blue-500">
                    <p class="text-gray-900 dark:text-gray-100">
                        {{ $sentiment->user_message }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                        {{ $sentiment->created_at->format('Y-m-d H:i:s') }}
                    </p>
                </div>
            </div>

            <!-- Sentiment Score -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    📈 Sentiment Score
                </h2>

                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Overall Score
                        </p>
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                    <div class="bg-blue-600 h-4 rounded-full" style="width: {{ ($sentiment->sentiment_score + 1) / 2 * 100 }}%"></div>
                                </div>
                            </div>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ round($sentiment->sentiment_score, 3) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            -1 (very negative) to 1 (very positive)
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Confidence
                        </p>
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                                    <div class="bg-green-600 h-4 rounded-full" style="width: {{ min($sentiment->confidence, 100) }}%"></div>
                                </div>
                            </div>
                            <span class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ round($sentiment->confidence, 1) }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Keywords -->
            @if($sentiment->positive_keywords || $sentiment->negative_keywords || $sentiment->neutral_keywords)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    🏷️ Keywords
                </h2>

                <div class="space-y-4">
                    @if($sentiment->positive_keywords)
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            😊 Positive Keywords
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($sentiment->positive_keywords as $keyword)
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm">
                                    {{ $keyword }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($sentiment->negative_keywords)
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            😠 Negative Keywords
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($sentiment->negative_keywords as $keyword)
                                <span class="px-3 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full text-sm">
                                    {{ $keyword }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($sentiment->neutral_keywords)
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            😐 Neutral Keywords
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($sentiment->neutral_keywords as $keyword)
                                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full text-sm">
                                    {{ $keyword }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Detected Issues -->
            @if($sentiment->detected_issues)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    🚨 Detected Issues
                </h2>

                <div class="flex flex-wrap gap-2">
                    @foreach($sentiment->detected_issues as $issue)
                        <span class="px-4 py-2 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full font-semibold">
                            {{ ucfirst($issue) }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Sentiment Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    📊 Status
                </h2>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">Sentiment:</span>
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

                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">Complaint:</span>
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

                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300">Urgent:</span>
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

            <!-- Emotions -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    😊 Emotions
                </h2>

                <div class="space-y-3">
                    @php
                        $emotions = [
                            'joy' => ['label' => '😊 Joy', 'score' => $sentiment->joy_score],
                            'anger' => ['label' => '😠 Anger', 'score' => $sentiment->anger_score],
                            'sadness' => ['label' => '😢 Sadness', 'score' => $sentiment->sadness_score],
                            'fear' => ['label' => '😨 Fear', 'score' => $sentiment->fear_score],
                            'surprise' => ['label' => '😮 Surprise', 'score' => $sentiment->surprise_score],
                        ];
                    @endphp

                    @foreach($emotions as $emotion => $data)
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $data['label'] }}
                            </p>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min($data['score'], 100) }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 w-8 text-right">
                                    {{ round($data['score'], 0) }}%
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    ℹ️ Information
                </h2>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">ID:</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ $sentiment->id }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">User ID:</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ $sentiment->line_user_id }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Language:</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ strtoupper($sentiment->language) }}</span>
                    </div>

                    @if($sentiment->primary_issue)
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Primary Issue:</span>
                        <span class="font-mono text-gray-900 dark:text-white">{{ $sentiment->primary_issue }}</span>
                    </div>
                    @endif

                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Analyzed:</span>
                        <span class="text-gray-900 dark:text-white">{{ $sentiment->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
