@extends('layouts.admin-v3')

@section('title', 'บันทึกกิจกรรม Keywords')

@push('styles')
<style>
    /* Timeline styles */
    .timeline {
        position: relative;
        padding-left: 2rem;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, #06C755, #3b82f6, #8b5cf6);
    }

    .timeline-item {
        position: relative;
        padding-bottom: 2rem;
    }

    .timeline-dot {
        position: absolute;
        left: -2.5rem;
        top: 0.5rem;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 0 0 3px rgba(6, 199, 85, 0.3);
    }

    .dark .timeline-dot {
        border-color: #1f2937;
    }

    /* Glassmorphism */
    .glass-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
    }

    .dark .glass-card {
        background: rgba(17, 24, 39, 0.5);
    }

    /* 3D card hover */
    .card-3d:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-8" x-data="activityLogs()">
    {{-- Header with gradient --}}
    <div class="mb-8 relative overflow-hidden rounded-2xl bg-gradient-to-r from-line-green via-blue-500 to-purple-600 p-8">
        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2 flex items-center gap-3">
                    <span class="text-5xl">📊</span>
                    บันทึกกิจกรรม Keywords
                </h1>
                <p class="text-white/90 text-lg">ติดตามการใช้งาน keywords และการตอบสนองของระบบ</p>
            </div>
            <button @click="exportCSV()"
                class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-md text-white rounded-xl font-medium transition-all transform hover:scale-105 border border-white/30">
                📥 Export CSV
            </button>
        </div>
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32"></div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="card-3d bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center text-3xl shadow-lg">📋</div>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</span>
            </div>
            <p class="text-4xl font-bold text-gray-900 dark:text-white mb-2">{{ number_format($stats['total_logs']) }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">รวมบันทึกทั้งหมด</p>
        </div>

        <div class="card-3d bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center text-3xl shadow-lg">✅</div>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Matches</span>
            </div>
            <p class="text-4xl font-bold text-green-600 dark:text-green-400 mb-2">{{ number_format($stats['total_matches']) }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Keyword Matches</p>
        </div>

        <div class="card-3d bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-orange-600 rounded-xl flex items-center justify-center text-3xl shadow-lg">🤖</div>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">AI</span>
            </div>
            <p class="text-4xl font-bold text-orange-600 dark:text-orange-400 mb-2">{{ number_format($stats['total_no_match']) }}</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">AI Fallback</p>
        </div>

        <div class="card-3d bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 border border-gray-200 dark:border-gray-700 transition-all">
            <div class="flex items-center justify-between mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-400 to-purple-600 rounded-xl flex items-center justify-center text-3xl shadow-lg">📈</div>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rate</span>
            </div>
            <p class="text-4xl font-bold text-purple-600 dark:text-purple-400 mb-2">{{ $stats['match_rate'] }}%</p>
            <p class="text-sm text-gray-600 dark:text-gray-400">Match Rate</p>
        </div>
    </div>

    {{-- Daily Activity Chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-200 dark:border-gray-700">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <span>📊</span>
            กิจกรรมรายวัน (30 วันที่ผ่านมา)
        </h2>
        <div class="h-80">
            <canvas id="dailyActivityChart"></canvas>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 mb-8 border border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>🔍</span>
                ตัวกรอง
            </h3>
            <button @click="resetFilters()" class="text-sm text-blue-600 dark:text-blue-400 hover:underline font-medium">
                ⟲ รีเซ็ต
            </button>
        </div>

        <form method="GET" action="{{ route('admin.line-bot.keywords.activity.index') }}">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Keyword</label>
                    <select name="keyword" x-model="filters.keyword" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-line-green">
                        <option value="">-- ทั้งหมด --</option>
                        @foreach($keywords as $kw)
                        <option value="{{ $kw }}" @selected(request('keyword') == $kw)>{{ $kw }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท</label>
                    <select name="action_type" x-model="filters.action_type" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-line-green">
                        <option value="">-- ทั้งหมด --</option>
                        <option value="matched" @selected(request('action_type') == 'matched')>Keyword Match</option>
                        <option value="no_match" @selected(request('action_type') == 'no_match')>No Match (AI)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">หมวดหมู่</label>
                    <select name="category" x-model="filters.category" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-line-green">
                        <option value="">-- ทั้งหมด --</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected(request('category') == $cat)>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full px-6 py-2.5 bg-gradient-to-r from-line-green to-green-600 hover:from-green-600 hover:to-line-green text-white rounded-lg font-medium transition-all transform hover:scale-105 shadow-lg">
                        🔍 ค้นหา
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Activity Timeline --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
            <span>📜</span>
            Activity Timeline
        </h2>

        @if($logs->count() > 0)
        <div class="timeline">
            @foreach($logs as $log)
            <div class="timeline-item">
                <div class="timeline-dot @if($log->action_type === 'matched') bg-green-500 @else bg-orange-500 @endif"></div>

                <div class="card-3d bg-gray-50 dark:bg-gray-700 rounded-xl p-5 border border-gray-200 dark:border-gray-600 transition-all">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            {{-- Avatar --}}
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-600 flex items-center justify-center text-white font-bold text-sm">
                                {{ substr($log->line_user_id ?? 'U', 0, 1) }}
                            </div>

                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    @if($log->action_type === 'matched')
                                        <span class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full text-sm font-bold">
                                            ✅ {{ $log->keyword_name }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded-full text-sm font-bold">
                                            🤖 AI Fallback
                                        </span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 flex items-center gap-2">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ \Carbon\Carbon::parse($log->timestamp)->format('d/m/Y H:i:s') }}
                                    <span class="text-gray-400">•</span>
                                    <span class="text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($log->timestamp)->diffForHumans() }}</span>
                                </p>
                            </div>
                        </div>

                        @if($log->category)
                        <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full text-xs font-semibold">
                            {{ $log->category }}
                        </span>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                        <p class="text-gray-900 dark:text-gray-100 text-sm leading-relaxed">
                            "{{ $log->user_message }}"
                        </p>
                    </div>

                    @if($log->response_type)
                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                        Response: {{ $log->response_type }}
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $logs->links() }}
        </div>
        @else
        <div class="text-center py-16">
            <div class="text-6xl mb-4">📭</div>
            <p class="text-xl text-gray-500 dark:text-gray-400 font-medium">ไม่มีบันทึกกิจกรรม</p>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">กิจกรรมจะปรากฏที่นี่เมื่อมีการใช้งาน keywords</p>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    /**
     * Alpine.js Component สำหรับ Activity Logs
     */
    function activityLogs() {
        return {
            filters: {
                keyword: '{{ request("keyword") }}',
                action_type: '{{ request("action_type") }}',
                category: '{{ request("category") }}'
            },

            resetFilters() {
                this.filters = { keyword: '', action_type: '', category: '' };
            },

            exportCSV() {
                window.location.href = '{{ route("admin.line-bot.keywords.activity.export") }}?days={{ request("days", 30) }}';
            }
        }
    }

    /**
     * Daily Activity Chart
     */
    fetch('{{ route("admin.line-bot.keywords.activity.daily-chart") }}')
        .then(r => r.json())
        .then(data => {
            const ctx = document.getElementById('dailyActivityChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map((dataset, index) => ({
                        ...dataset,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { padding: 15, font: { size: 12, weight: 'bold' }, usePointStyle: true }
                        }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        });
</script>
@endpush
@endsection
