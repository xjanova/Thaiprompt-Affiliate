@extends('layouts.admin')

@section('title', 'A/B Test')

@section('content')
<div class="container-fluid px-4 py-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                🧪 A/B Test Management
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                ทดสอบตัวแปรคีย์เวิร์ดเพื่อหาผู้ชนะที่ดีที่สุด
            </p>
        </div>
        <a href="{{ route('admin.line-bot.keywords.ab-tests.create') }}"
           class="px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-semibold transition">
            + สร้าง A/B Test
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">ทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ $stats['total_tests'] }}
                    </p>
                </div>
                <div class="text-4xl">🧪</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Active</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                        {{ $stats['active_tests'] }}
                    </p>
                </div>
                <div class="text-4xl">🟢</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">เสร็จสิ้น</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                        {{ $stats['completed_tests'] }}
                    </p>
                </div>
                <div class="text-4xl">✅</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Avg Confidence</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                        {{ $stats['avg_confidence'] }}%
                    </p>
                </div>
                <div class="text-4xl">📊</div>
            </div>
        </div>
    </div>

    <!-- Recommendations -->
    @if(count($recommendations) > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        @foreach($recommendations as $rec)
        <div class="bg-{{ match($rec['type']) { 'warning' => 'yellow', 'info' => 'blue', 'success' => 'green' } }}-50 dark:bg-{{ match($rec['type']) { 'warning' => 'yellow', 'info' => 'blue', 'success' => 'green' } }}-900 border-l-4 border-{{ match($rec['type']) { 'warning' => 'yellow', 'info' => 'blue', 'success' => 'green' } }}-500 p-4 rounded">
            <p class="font-semibold text-{{ match($rec['type']) { 'warning' => 'yellow', 'info' => 'blue', 'success' => 'green' } }}-800 dark:text-{{ match($rec['type']) { 'warning' => 'yellow', 'info' => 'blue', 'success' => 'green' } }}-200">
                {{ $rec['message'] }}
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ $rec['action'] }}
            </p>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Filter & Search -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8 p-6">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Filter by Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        สถานะ
                    </label>
                    <select name="filter" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                        <option value="active" {{ $filter === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ $filter === 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                    </select>
                </div>

                <!-- Filter by Keyword -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        คีย์เวิร์ด
                    </label>
                    <select name="keyword_id" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">ทั้งหมด</option>
                        @foreach($keywords as $keyword)
                            <option value="{{ $keyword->id }}" {{ $selected_keyword_id === (string)$keyword->id ? 'selected' : '' }}>
                                {{ $keyword->keyword }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Button -->
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg font-medium transition">
                        🔍 ค้นหา
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tests Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            ชื่อการทดสอบ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            คีย์เวิร์ด
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            สถานะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Interactions
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            ผู้ชนะ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            Confidence
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($tests as $test)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $test->test_name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $test->keyword?->keyword }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($test->status === 'active')
                                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-semibold">
                                        🟢 Active
                                    </span>
                                @elseif($test->status === 'completed')
                                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-xs font-semibold">
                                        ✅ Completed
                                    </span>
                                @elseif($test->status === 'paused')
                                    <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-full text-xs font-semibold">
                                        ⏸️ Paused
                                    </span>
                                @elseif($test->status === 'planning')
                                    <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-full text-xs font-semibold">
                                        📋 Planning
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-full text-xs font-semibold">
                                        ❌ Cancelled
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ $test->results_records()->count() ?? 0 }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($test->winner)
                                    <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded text-xs font-semibold">
                                        {{ ucfirst(str_replace('_', ' ', $test->winner)) }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($test->winner_confidence)
                                    <div class="flex items-center gap-2">
                                        <div class="w-12 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ min($test->winner_confidence, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold">
                                            {{ round($test->winner_confidence, 1) }}%
                                        </span>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <a href="{{ route('admin.line-bot.keywords.ab-tests.show', $test) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:underline">
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                ไม่มีการทดสอบ A/B
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($tests->hasPages())
            <div class="bg-white dark:bg-gray-800 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $tests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
