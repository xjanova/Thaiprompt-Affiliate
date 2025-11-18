@extends('layouts.admin-v3')

@section('title', 'Sentiment Analysis')

@section('content')
<div class="container-fluid px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
            😊 Sentiment Analysis
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            วิเคราะห์ความรู้สึกและอารมณ์จากข้อความผู้ใช้
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">ทั้งหมด</p>
            <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                {{ $statistics['total_messages'] }}
            </p>
        </div>

        <div class="bg-green-50 dark:bg-green-900 rounded-lg p-4 border border-green-200 dark:border-green-700">
            <p class="text-green-800 dark:text-green-200 text-sm font-medium">Positive</p>
            <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                {{ $statistics['positive_percentage'] }}%
            </p>
            <p class="text-xs text-green-700 dark:text-green-300 mt-1">
                {{ $statistics['positive_count'] }} messages
            </p>
        </div>

        <div class="bg-red-50 dark:bg-red-900 rounded-lg p-4 border border-red-200 dark:border-red-700">
            <p class="text-red-800 dark:text-red-200 text-sm font-medium">Negative</p>
            <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-2">
                {{ $statistics['negative_percentage'] }}%
            </p>
            <p class="text-xs text-red-700 dark:text-red-300 mt-1">
                {{ $statistics['negative_count'] }} messages
            </p>
        </div>

        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
            <p class="text-gray-800 dark:text-gray-200 text-sm font-medium">Neutral</p>
            <p class="text-3xl font-bold text-gray-600 dark:text-gray-400 mt-2">
                {{ $statistics['neutral_percentage'] }}%
            </p>
            <p class="text-xs text-gray-700 dark:text-gray-300 mt-1">
                {{ $statistics['neutral_count'] }} messages
            </p>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900 rounded-lg p-4 border border-orange-200 dark:border-orange-700">
            <p class="text-orange-800 dark:text-orange-200 text-sm font-medium">Complaints</p>
            <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                {{ $statistics['complaint_count'] }}
            </p>
            <p class="text-xs text-orange-700 dark:text-orange-300 mt-1">
                ข้อร้องเรียน
            </p>
        </div>
    </div>

    <!-- Recommendations -->
    @if(count($recommendations) > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        @foreach($recommendations as $rec)
        <div class="bg-{{ match($rec['type']) { 'warning' => 'yellow', 'danger' => 'red', 'info' => 'blue', 'success' => 'green' } }}-50 dark:bg-{{ match($rec['type']) { 'warning' => 'yellow', 'danger' => 'red', 'info' => 'blue', 'success' => 'green' } }}-900 border-l-4 border-{{ match($rec['type']) { 'warning' => 'yellow', 'danger' => 'red', 'info' => 'blue', 'success' => 'green' } }}-500 p-4 rounded">
            <p class="font-semibold text-{{ match($rec['type']) { 'warning' => 'yellow', 'danger' => 'red', 'info' => 'blue', 'success' => 'green' } }}-800 dark:text-{{ match($rec['type']) { 'warning' => 'yellow', 'danger' => 'red', 'info' => 'blue', 'success' => 'green' } }}-200">
                {{ $rec['message'] }}
            </p>
            <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                {{ $rec['action'] }}
            </p>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8 p-6">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Days -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        ช่วงเวลา
                    </label>
                    <select name="days" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        <option value="7" {{ $days === 7 ? 'selected' : '' }}>7 วันที่ผ่านมา</option>
                        <option value="14" {{ $days === 14 ? 'selected' : '' }}>14 วันที่ผ่านมา</option>
                        <option value="30" {{ $days === 30 ? 'selected' : '' }}>30 วันที่ผ่านมา</option>
                        <option value="60" {{ $days === 60 ? 'selected' : '' }}>60 วันที่ผ่านมา</option>
                        <option value="90" {{ $days === 90 ? 'selected' : '' }}>90 วันที่ผ่านมา</option>
                    </select>
                </div>

                <!-- Sentiment Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Sentiment
                    </label>
                    <select name="sentiment" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        <option value="">ทั้งหมด</option>
                        <option value="positive" {{ $filter_sentiment === 'positive' ? 'selected' : '' }}>😊 Positive</option>
                        <option value="negative" {{ $filter_sentiment === 'negative' ? 'selected' : '' }}>😠 Negative</option>
                        <option value="neutral" {{ $filter_sentiment === 'neutral' ? 'selected' : '' }}>😐 Neutral</option>
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        ประเภท
                    </label>
                    <select name="type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        <option value="">ทั้งหมด</option>
                        <option value="complaints" {{ $filter_type === 'complaints' ? 'selected' : '' }}>🗣️ ข้อร้องเรียน</option>
                        <option value="urgent" {{ $filter_type === 'urgent' ? 'selected' : '' }}>🔴 เรื่องด่วน</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-medium transition">
                        🔍 ค้นหา
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Sentiments Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Sentiment
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            ข้อความ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            ปัญหา
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Score
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($sentiments as $sentiment)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4 text-sm">
                                @if($sentiment->sentiment === 'positive')
                                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-semibold">
                                        😊 Positive
                                    </span>
                                @elseif($sentiment->sentiment === 'negative')
                                    <span class="px-3 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full text-xs font-semibold">
                                        😠 Negative
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full text-xs font-semibold">
                                        😐 Neutral
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 max-w-xs truncate">
                                {{ $sentiment->user_message }}
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
                                    <div class="w-12 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($sentiment->sentiment_score + 1) / 2 * 100 }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold">
                                        {{ round($sentiment->sentiment_score, 2) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex gap-1">
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
                                   class="text-blue-600 dark:text-blue-400 hover:underline">
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ไม่มีข้อมูล sentiment
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($sentiments->hasPages())
            <div class="bg-white dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $sentiments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
