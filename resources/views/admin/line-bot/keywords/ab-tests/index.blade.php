@extends('layouts.admin-v3')

@section('title', 'A/B Test - LINE Bot Keywords')

@section('content')
<div class="container-fluid px-4 py-6" x-data="abTestManager()" x-init="init()">
    {{-- Header with LINE Green Gradient --}}
    <div class="mb-8 relative overflow-hidden bg-gradient-to-r from-[#06C755] via-green-500 to-emerald-600 rounded-2xl p-8 shadow-2xl">
        {{-- Pattern Background --}}
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.3),transparent_50%)]"></div>
        </div>

        <div class="relative z-10 flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-black text-white mb-2 flex items-center gap-3">
                    <span class="text-5xl">🧪</span>
                    <span>A/B Test Management</span>
                </h1>
                <p class="text-white/90 text-lg">
                    ทดสอบตัวแปรคีย์เวิร์ดเพื่อหาผู้ชนะที่ดีที่สุด
                </p>
            </div>
            <a href="{{ route('admin.line-bot.keywords.ab-tests.create') }}"
               class="group relative overflow-hidden px-8 py-4 bg-white/20 backdrop-blur-lg border border-white/30 text-white font-bold rounded-xl hover:bg-white/30 transition-all shadow-lg hover:shadow-2xl hover:scale-105">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span>สร้าง A/B Test</span>
                </span>
            </a>
        </div>
    </div>

    {{-- Statistics Cards with Glassmorphism --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Tests --}}
        <div class="group perspective-1000">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-3xl">🧪</span>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">ทั้งหมด</p>
                            <h3 class="text-3xl font-black text-gray-900 dark:text-white" x-text="stats.total_tests">
                                {{ $stats['total_tests'] }}
                            </h3>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-full"></div>
                </div>
            </div>
        </div>

        {{-- Active Tests --}}
        <div class="group perspective-1000">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                <div class="absolute inset-0 bg-gradient-to-br from-[#06C755] to-emerald-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-14 h-14 bg-gradient-to-br from-[#06C755] to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-3xl">🟢</span>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active</p>
                            <h3 class="text-3xl font-black text-[#06C755] dark:text-green-400" x-text="stats.active_tests">
                                {{ $stats['active_tests'] }}
                            </h3>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-[#06C755] to-emerald-600 rounded-full"></div>
                </div>
            </div>
        </div>

        {{-- Completed Tests --}}
        <div class="group perspective-1000">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-3xl">✅</span>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">เสร็จสิ้น</p>
                            <h3 class="text-3xl font-black text-green-600 dark:text-green-400" x-text="stats.completed_tests">
                                {{ $stats['completed_tests'] }}
                            </h3>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full"></div>
                </div>
            </div>
        </div>

        {{-- Average Confidence --}}
        <div class="group perspective-1000">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105 group-hover:rotate-y-2">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition-opacity"></div>
                <div class="relative backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                            <span class="text-3xl">📊</span>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Confidence</p>
                            <h3 class="text-3xl font-black text-purple-600 dark:text-purple-400">
                                <span x-text="stats.avg_confidence">{{ $stats['avg_confidence'] }}</span>%
                            </h3>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-purple-500 to-pink-600 rounded-full"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Recommendations Alert --}}
    @if(count($recommendations) > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8" x-show="showRecommendations" x-transition>
        @foreach($recommendations as $rec)
        <div class="relative overflow-hidden backdrop-blur-xl
                    @if($rec['type'] === 'warning') bg-yellow-50/80 dark:bg-yellow-900/30 border-l-4 border-yellow-500
                    @elseif($rec['type'] === 'info') bg-blue-50/80 dark:bg-blue-900/30 border-l-4 border-blue-500
                    @else bg-green-50/80 dark:bg-green-900/30 border-l-4 border-green-500
                    @endif
                    rounded-xl p-6 shadow-lg hover:shadow-xl transition-all">
            <div class="relative z-10">
                <h4 class="font-bold text-lg mb-2
                           @if($rec['type'] === 'warning') text-yellow-800 dark:text-yellow-200
                           @elseif($rec['type'] === 'info') text-blue-800 dark:text-blue-200
                           @else text-green-800 dark:text-green-200
                           @endif">
                    <i class="@if($rec['type'] === 'warning') fas fa-exclamation-triangle
                              @elseif($rec['type'] === 'info') fas fa-info-circle
                              @else fas fa-check-circle
                              @endif mr-2"></i>
                    {{ $rec['message'] }}
                </h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $rec['action'] }}
                </p>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Filter & Search Card --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 mb-8 border border-white/20 dark:border-gray-700/30">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Filter by Status --}}
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-filter mr-2 text-[#06C755]"></i>สถานะ
                    </label>
                    <select name="filter"
                            class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all">
                        <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>ทั้งหมด</option>
                        <option value="active" {{ $filter === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ $filter === 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                    </select>
                </div>

                {{-- Filter by Keyword --}}
                <div>
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-2">
                        <i class="fas fa-key mr-2 text-[#06C755]"></i>คีย์เวิร์ด
                    </label>
                    <select name="keyword_id"
                            class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-700 rounded-xl bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#06C755] focus:border-transparent transition-all">
                        <option value="">ทั้งหมด</option>
                        @foreach($keywords as $keyword)
                            <option value="{{ $keyword->id }}" {{ $selected_keyword_id === (string)$keyword->id ? 'selected' : '' }}>
                                {{ $keyword->keyword }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Search Button --}}
                <div class="flex items-end">
                    <button type="submit"
                            class="w-full px-6 py-3 bg-gradient-to-r from-[#06C755] to-emerald-600 text-white rounded-xl font-bold shadow-lg hover:shadow-2xl hover:scale-105 transition-all">
                        <i class="fas fa-search mr-2"></i>ค้นหา
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Tests Table with Glassmorphism --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl overflow-hidden border border-white/20 dark:border-gray-700/30">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ชื่อการทดสอบ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            คีย์เวิร์ด
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Interactions
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            ผู้ชนะ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Confidence
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($tests as $test)
                        <tr class="hover:bg-white/80 dark:hover:bg-gray-700/50 transition-all">
                            <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">
                                <i class="fas fa-flask mr-2 text-[#06C755]"></i>
                                {{ $test->test_name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 rounded-full text-xs font-semibold">
                                    {{ $test->keyword?->keyword }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($test->status === 'active')
                                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-[#06C755] to-emerald-600 text-white rounded-full text-xs font-bold shadow-lg animate-pulse">
                                        <span class="w-2 h-2 bg-white rounded-full"></span>
                                        Active
                                    </span>
                                @elseif($test->status === 'completed')
                                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 rounded-full text-xs font-bold">
                                        <i class="fas fa-check-circle"></i>
                                        Completed
                                    </span>
                                @elseif($test->status === 'paused')
                                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200 rounded-full text-xs font-bold">
                                        <i class="fas fa-pause-circle"></i>
                                        Paused
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-400 rounded-full text-xs font-bold">
                                        <i class="fas fa-clipboard-list"></i>
                                        Planning
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl font-black text-gray-900 dark:text-white">
                                        {{ $test->results_records()->count() ?? 0 }}
                                    </span>
                                    <i class="fas fa-chart-line text-blue-500"></i>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($test->winner)
                                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-full text-xs font-bold shadow-lg">
                                        <i class="fas fa-crown"></i>
                                        {{ ucfirst(str_replace('_', ' ', $test->winner)) }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($test->winner_confidence)
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-green-400 to-emerald-600 rounded-full transition-all duration-500"
                                                 style="width: {{ min($test->winner_confidence, 100) }}%"></div>
                                        </div>
                                        <span class="text-sm font-black text-gray-900 dark:text-white min-w-[45px]">
                                            {{ round($test->winner_confidence, 1) }}%
                                        </span>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('admin.line-bot.keywords.ab-tests.show', $test) }}"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg font-semibold hover:shadow-lg hover:scale-105 transition-all">
                                    <i class="fas fa-eye"></i>
                                    ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                        <i class="fas fa-flask text-4xl text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">
                                        ไม่มีการทดสอบ A/B
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tests->hasPages())
            <div class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $tests->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
/**
 * A/B Test Manager Component
 *
 * จัดการการแสดงผลและ interaction ของหน้า A/B Tests
 */
function abTestManager() {
    return {
        // State
        stats: {
            total_tests: {{ $stats['total_tests'] }},
            active_tests: {{ $stats['active_tests'] }},
            completed_tests: {{ $stats['completed_tests'] }},
            avg_confidence: {{ $stats['avg_confidence'] }}
        },
        showRecommendations: true,

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('A/B Test Manager initialized');
            this.animateStats();
        },

        /**
         * Animate stats counter
         */
        animateStats() {
            // Animate numbers on load
            this.$nextTick(() => {
                console.log('Stats animated');
            });
        }
    };
}
</script>

<style>
.perspective-1000 {
    perspective: 1000px;
}

.rotate-y-2 {
    transform: rotateY(2deg);
}

/* Smooth transitions */
* {
    transition-property: transform, opacity, shadow;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
@endpush
@endsection
