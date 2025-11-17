@extends('layouts.admin-v3')

@section('title', 'บันทึกกิจกรรม Keywords')

@section('content')
<div class="container-fluid px-4 py-8">
    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                📊 บันทึกกิจกรรม Keywords
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                ติดตามการใช้งาน keywords และการตอบสนองของระบบ
            </p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('admin.line-bot.keywords.activity.export') }}" method="GET" class="inline">
                <input type="hidden" name="days" value="{{ request('days', 30) }}">
                <button type="submit"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    ดาวน์โหลด CSV
                </button>
            </form>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Total Logs --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">รวมบันทึกทั้งหมด</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">
                        {{ number_format($stats['total_logs']) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center text-2xl">
                    📋
                </div>
            </div>
        </div>

        {{-- Total Matches --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Keyword Matches</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">
                        {{ number_format($stats['total_matches']) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center text-2xl">
                    ✅
                </div>
            </div>
        </div>

        {{-- No Match (AI Fallback) --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">AI Fallback</p>
                    <p class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-2">
                        {{ number_format($stats['total_no_match']) }}
                    </p>
                </div>
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center text-2xl">
                    🤖
                </div>
            </div>
        </div>

        {{-- Match Rate --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">Match Rate</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">
                        {{ $stats['match_rate'] }}%
                    </p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center text-2xl">
                    📈
                </div>
            </div>
        </div>
    </div>

    {{-- Daily Activity Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📊 กิจกรรมรายวัน (30 วันที่ผ่านมา)</h2>
        <div class="h-80">
            <canvas id="dailyActivityChart"></canvas>
        </div>
    </div>

    {{-- Most Used Keywords --}}
    @if($stats['most_used_keyword'])
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🏆 Keyword ที่ใช้มากที่สุด</h3>
            <div class="text-center py-4">
                <p class="text-4xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                    {{ $stats['most_used_keyword']['name'] }}
                </p>
                <p class="text-gray-600 dark:text-gray-400">
                    ใช้แล้ว {{ $stats['most_used_keyword']['count'] }} ครั้ง
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">👥 ผู้ใช้และสถิติ</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">ผู้ใช้ที่ไม่ซ้ำ</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $stats['unique_users'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">บันทึกวันนี้</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $stats['today_logs'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Matches วันนี้</span>
                    <span class="text-lg font-bold text-green-600 dark:text-green-400">{{ $stats['today_matches'] }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🔍 ตัวกรอง</h3>
        <form method="GET" action="{{ route('admin.line-bot.keywords.activity.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Keyword Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Keyword
                    </label>
                    <select name="keyword" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">-- ทั้งหมด --</option>
                        @foreach($keywords as $kw)
                        <option value="{{ $kw }}" @selected(request('keyword') == $kw)>
                            {{ $kw }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Action Type Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ประเภท
                    </label>
                    <select name="action_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="matched" @selected(request('action_type') == 'matched')>Keyword Match</option>
                        <option value="no_match" @selected(request('action_type') == 'no_match')>No Match (AI)</option>
                    </select>
                </div>

                {{-- Category Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        หมวดหมู่
                    </label>
                    <select name="category" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">-- ทั้งหมด --</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected(request('category') == $cat)>
                            {{ ucfirst($cat) }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Line User ID Filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        LINE User ID
                    </label>
                    <input type="text" name="line_user_id" value="{{ request('line_user_id') }}"
                        placeholder="ค้นหา..."
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Date From --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ตั้งแต่วันที่
                    </label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>

                {{-- Date To --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ถึงวันที่
                    </label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg
                        bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500
                    dark:hover:bg-blue-600 text-white rounded-lg transition">
                    🔍 ค้นหา
                </button>
                <a href="{{ route('admin.line-bot.keywords.activity.index') }}"
                    class="px-6 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-700
                    text-gray-900 dark:text-white rounded-lg transition">
                    ⟲ รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    {{-- Activity Logs Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            วันเวลา
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            Keyword
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            ข้อความผู้ใช้
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            ประเภท
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            หมวดหมู่
                        </th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                            LINE User ID
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            <span title="{{ $log->timestamp }}">
                                {{ \Carbon\Carbon::parse($log->timestamp)->format('d/m/Y H:i') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($log->action_type === 'matched')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                    ✅ {{ $log->keyword_name ?? 'N/A' }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                    No Match
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            <span title="{{ $log->user_message }}">
                                {{ \Str::limit($log->user_message, 50) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $log->response_type ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $log->category ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                            <code class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                {{ \Str::limit($log->line_user_id, 20) }}
                            </code>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            ไม่มีบันทึกกิจกรรม
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Daily Activity Chart
    fetch('{{ route("admin.line-bot.keywords.activity.daily-chart") }}')
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById('dailyActivityChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                        },
                    },
                },
            });
        });
</script>
@endpush
@endsection
