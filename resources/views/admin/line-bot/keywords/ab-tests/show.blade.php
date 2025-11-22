@extends('layouts.admin-v3')

@section('title', 'A/B Test: ' . $test->test_name)

@push('styles')
<style>
.winner-badge-animation {
    animation: winnerPulse 2s infinite;
}

@keyframes winnerPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.stat-card-hover {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.stat-card-hover:hover {
    transform: translateY(-4px) scale(1.02);
}
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="abTestResults(@js($test), @js($comparison), @js($summary))" x-init="init()">
    {{-- Header & Controls --}}
    <div class="mb-8">
        <a href="{{ route('admin.line-bot.keywords.ab-tests.index') }}"
           class="inline-flex items-center gap-2 text-[#06C755] hover:text-emerald-600 dark:text-green-400 dark:hover:text-green-300 font-semibold mb-4 transition-colors">
            <i class="fas fa-arrow-left"></i>
            <span>กลับ</span>
        </a>

        {{-- Header Card --}}
        <div class="relative overflow-hidden bg-gradient-to-r from-[#06C755] via-green-500 to-emerald-600 rounded-2xl p-8 shadow-2xl">
            <div class="absolute inset-0 opacity-20">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_50%,rgba(255,255,255,0.4),transparent_60%)]"></div>
            </div>

            <div class="relative z-10 flex justify-between items-start">
                <div>
                    <h1 class="text-4xl font-black text-white mb-2 flex items-center gap-3">
                        <span class="text-5xl">🧪</span>
                        <span>{{ $test->test_name }}</span>
                    </h1>
                    <p class="text-white/90 text-lg flex items-center gap-2">
                        <i class="fas fa-key"></i>
                        <span>{{ $test->keyword?->keyword }}</span>
                    </p>
                </div>

                {{-- Action Buttons --}}
                <div class="flex gap-2">
                    @if($test->status === 'planning')
                        <button @click="startTest"
                            class="px-6 py-3 bg-white/20 backdrop-blur-lg border border-white/30 text-white font-bold rounded-xl hover:bg-white/30 transition-all shadow-lg hover:shadow-2xl hover:scale-105">
                            <i class="fas fa-play mr-2"></i>เริ่ม
                        </button>
                    @elseif($test->status === 'active')
                        <button @click="completeTest"
                            class="px-6 py-3 bg-white/20 backdrop-blur-lg border border-white/30 text-white font-bold rounded-xl hover:bg-white/30 transition-all shadow-lg hover:shadow-2xl hover:scale-105">
                            <i class="fas fa-check-circle mr-2"></i>จบการทดสอบ
                        </button>
                        <button @click="pauseTest"
                            class="px-6 py-3 bg-yellow-500/80 backdrop-blur-lg text-white font-bold rounded-xl hover:bg-yellow-600/80 transition-all shadow-lg hover:shadow-2xl hover:scale-105">
                            <i class="fas fa-pause-circle mr-2"></i>หยุด
                        </button>
                    @elseif($test->status === 'completed' && $test->winner)
                        <button @click="applyWinner"
                            class="px-6 py-3 bg-white/20 backdrop-blur-lg border border-white/30 text-white font-bold rounded-xl hover:bg-white/30 transition-all shadow-lg hover:shadow-2xl hover:scale-105">
                            <i class="fas fa-rocket mr-2"></i>นำไปใช้
                        </button>
                    @endif
                    <button @click="deleteTest"
                        class="px-6 py-3 bg-red-500/80 backdrop-blur-lg text-white font-bold rounded-xl hover:bg-red-600/80 transition-all shadow-lg hover:shadow-2xl hover:scale-105">
                        <i class="fas fa-trash mr-2"></i>ลบ
                    </button>
                </div>
            </div>

            {{-- Status Badge --}}
            <div class="mt-4">
                @if($test->status === 'active')
                    <span class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-lg border border-white/30 text-white rounded-full text-sm font-bold animate-pulse">
                        <span class="w-3 h-3 bg-white rounded-full"></span>
                        Active - กำลังดำเนินการ
                    </span>
                @elseif($test->status === 'completed')
                    <span class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-lg border border-white/30 text-white rounded-full text-sm font-bold">
                        <i class="fas fa-check-circle"></i>
                        Completed - เสร็จสิ้น
                    </span>
                @elseif($test->status === 'paused')
                    <span class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-lg border border-white/30 text-white rounded-full text-sm font-bold">
                        <i class="fas fa-pause-circle"></i>
                        Paused - หยุดชั่วคราว
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-lg border border-white/30 text-white rounded-full text-sm font-bold">
                        <i class="fas fa-clipboard-list"></i>
                        Planning - ในการวางแผน
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Test Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Winning Criterion --}}
        <div class="stat-card-hover backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl p-6 border border-white/20 dark:border-gray-700/30 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">เกณฑ์ชนะ</p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">
                        {{ ucfirst(str_replace('_', ' ', $test->winning_criterion)) }}
                    </p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-trophy text-white text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Minimum Samples --}}
        <div class="stat-card-hover backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl p-6 border border-white/20 dark:border-gray-700/30 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">ตัวอย่างต่ำสุด</p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">
                        {{ $test->minimum_samples }}
                    </p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-bar text-white text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Total Interactions --}}
        <div class="stat-card-hover backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl p-6 border border-white/20 dark:border-gray-700/30 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">ทั้งหมด Interactions</p>
                    <p class="text-2xl font-black text-[#06C755] dark:text-green-400">
                        {{ $summary['total_interactions'] }}
                    </p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-[#06C755] to-emerald-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-white text-2xl"></i>
                </div>
            </div>
        </div>

        {{-- Duration --}}
        <div class="stat-card-hover backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl p-6 border border-white/20 dark:border-gray-700/30 shadow-xl">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">ระยะเวลา</p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">
                        @if($summary['duration_days'] !== null)
                            {{ $summary['duration_days'] }} วัน
                        @else
                            กำลังดำเนินการ
                        @endif
                    </p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-white text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Winner Results (if completed) --}}
    @if($test->status === 'completed' && $test->winner)
    <div class="backdrop-blur-xl bg-gradient-to-r from-green-50/80 to-emerald-50/80 dark:from-green-900/30 dark:to-emerald-900/30 rounded-2xl shadow-2xl p-8 mb-8 border-2 border-green-200 dark:border-green-700">
        <h2 class="text-3xl font-black text-green-900 dark:text-green-100 mb-6 flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center winner-badge-animation">
                <i class="fas fa-crown text-white text-xl"></i>
            </div>
            <span>ผลลัพธ์การทดสอบ</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Winner --}}
            <div class="backdrop-blur-lg bg-white/50 dark:bg-gray-800/50 rounded-xl p-6 border border-green-300 dark:border-green-600">
                <p class="text-sm font-bold text-green-700 dark:text-green-300 mb-2 flex items-center gap-2">
                    <i class="fas fa-trophy"></i>
                    ผู้ชนะ
                </p>
                <p class="text-4xl font-black text-green-900 dark:text-green-100">
                    Variant {{ strtoupper(substr($test->winner, -1)) }}
                </p>
            </div>

            {{-- Confidence --}}
            <div class="backdrop-blur-lg bg-white/50 dark:bg-gray-800/50 rounded-xl p-6 border border-blue-300 dark:border-blue-600">
                <p class="text-sm font-bold text-blue-700 dark:text-blue-300 mb-2 flex items-center gap-2">
                    <i class="fas fa-percentage"></i>
                    Confidence Level
                </p>
                <p class="text-4xl font-black text-blue-900 dark:text-blue-100">
                    {{ round($test->winner_confidence, 1) }}%
                </p>
            </div>

            {{-- Statistical Significance --}}
            <div class="backdrop-blur-lg bg-white/50 dark:bg-gray-800/50 rounded-xl p-6 border border-purple-300 dark:border-purple-600">
                <p class="text-sm font-bold text-purple-700 dark:text-purple-300 mb-2 flex items-center gap-2">
                    <i class="fas fa-chart-pie"></i>
                    สถิติมีความหมาย
                </p>
                <p class="text-4xl font-black text-purple-900 dark:text-purple-100 flex items-center gap-2">
                    @if($test->winner_confidence >= 95)
                        <i class="fas fa-check-circle text-green-500"></i>
                        ใช่
                    @else
                        <i class="fas fa-times-circle text-red-500"></i>
                        ไม่
                    @endif
                </p>
            </div>
        </div>

        {{-- Reason --}}
        @if($test->results && isset($test->results['reason']))
        <div class="mt-6 p-4 backdrop-blur-lg bg-white/50 dark:bg-gray-800/50 rounded-xl border border-green-200 dark:border-green-700">
            <p class="text-sm font-bold text-green-900 dark:text-green-100 mb-2">
                <i class="fas fa-info-circle mr-2"></i>เหตุผล:
            </p>
            <p class="text-gray-700 dark:text-gray-300">
                {{ $test->results['reason'] }}
            </p>
        </div>
        @endif
    </div>
    @endif

    {{-- Variant Comparison Cards --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        {{-- Variant A --}}
        <div class="backdrop-blur-xl bg-gradient-to-br from-blue-50/80 to-cyan-50/80 dark:from-blue-900/30 dark:to-cyan-900/30 rounded-2xl shadow-2xl p-6 border-2"
             :class="test.winner === 'variant_a' ? 'border-green-500 ring-4 ring-green-500/20' : 'border-blue-200 dark:border-blue-700'">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-black text-blue-900 dark:text-blue-100 flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center">
                        <span class="text-white font-black">A</span>
                    </div>
                    <span>Variant A ({{ $test->variant_a_percentage }}%)</span>
                </h3>
                <div x-show="test.winner === 'variant_a'" x-transition class="winner-badge-animation">
                    <span class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-full text-xs font-bold shadow-lg">
                        <i class="fas fa-crown mr-1"></i>ผู้ชนะ
                    </span>
                </div>
            </div>

            {{-- Response Preview --}}
            <div class="mb-6 p-4 bg-white/70 dark:bg-gray-900/70 rounded-xl">
                <p class="text-xs font-bold text-blue-700 dark:text-blue-300 mb-2">ข้อความตอบกลับ:</p>
                <p class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $comparison['variant_a']['response_text'] ?? 'N/A' }}
                </p>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-white/70 dark:bg-gray-900/70 rounded-xl">
                    <p class="text-xs font-bold text-blue-700 dark:text-blue-300 mb-2">Impressions</p>
                    <p class="text-3xl font-black text-blue-600 dark:text-blue-400">
                        {{ $comparison['variant_a']['impressions'] }}
                    </p>
                </div>
                <div class="text-center p-4 bg-white/70 dark:bg-gray-900/70 rounded-xl">
                    <p class="text-xs font-bold text-blue-700 dark:text-blue-300 mb-2">Interactions</p>
                    <p class="text-3xl font-black text-blue-600 dark:text-blue-400">
                        {{ $comparison['variant_a']['interactions'] }}
                    </p>
                </div>
                <div class="text-center p-4 bg-white/70 dark:bg-gray-900/70 rounded-xl">
                    <p class="text-xs font-bold text-blue-700 dark:text-blue-300 mb-2">Conversion Rate</p>
                    <p class="text-2xl font-black text-blue-600 dark:text-blue-400">
                        {{ round($comparison['variant_a']['conversion_rate'], 2) }}%
                    </p>
                </div>
                <div class="text-center p-4 bg-white/70 dark:bg-gray-900/70 rounded-xl">
                    <p class="text-xs font-bold text-blue-700 dark:text-blue-300 mb-2">Avg Response Time</p>
                    <p class="text-2xl font-black text-blue-600 dark:text-blue-400">
                        {{ round($comparison['variant_a']['avg_response_time'], 0) }}ms
                    </p>
                </div>
            </div>
        </div>

        {{-- Variant B --}}
        <div class="backdrop-blur-xl bg-gradient-to-br from-purple-50/80 to-pink-50/80 dark:from-purple-900/30 dark:to-pink-900/30 rounded-2xl shadow-2xl p-6 border-2"
             :class="test.winner === 'variant_b' ? 'border-green-500 ring-4 ring-green-500/20' : 'border-purple-200 dark:border-purple-700'">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-black text-purple-900 dark:text-purple-100 flex items-center gap-2">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                        <span class="text-white font-black">B</span>
                    </div>
                    <span>Variant B ({{ $test->variant_b_percentage }}%)</span>
                </h3>
                <div x-show="test.winner === 'variant_b'" x-transition class="winner-badge-animation">
                    <span class="px-4 py-2 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-full text-xs font-bold shadow-lg">
                        <i class="fas fa-crown mr-1"></i>ผู้ชนะ
                    </span>
                </div>
            </div>

            {{-- Response Preview --}}
            <div class="mb-6 p-4 bg-white/70 dark:bg-gray-900/70 rounded-xl">
                <p class="text-xs font-bold text-purple-700 dark:text-purple-300 mb-2">ข้อความตอบกลับ:</p>
                <p class="text-sm text-gray-900 dark:text-gray-100">
                    {{ $comparison['variant_b']['response_text'] ?? 'N/A' }}
                </p>
            </div>

            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="text-center p-4 bg-white/70 dark:bg-gray-900/70 rounded-xl">
                    <p class="text-xs font-bold text-purple-700 dark:text-purple-300 mb-2">Impressions</p>
                    <p class="text-3xl font-black text-purple-600 dark:text-purple-400">
                        {{ $comparison['variant_b']['impressions'] }}
                    </p>
                </div>
                <div class="text-center p-4 bg-white/70 dark:bg-gray-900/70 rounded-xl">
                    <p class="text-xs font-bold text-purple-700 dark:text-purple-300 mb-2">Interactions</p>
                    <p class="text-3xl font-black text-purple-600 dark:text-purple-400">
                        {{ $comparison['variant_b']['interactions'] }}
                    </p>
                </div>
                <div class="text-center p-4 bg-white/70 dark:bg-gray-900/70 rounded-xl">
                    <p class="text-xs font-bold text-purple-700 dark:text-purple-300 mb-2">Conversion Rate</p>
                    <p class="text-2xl font-black text-purple-600 dark:text-purple-400">
                        {{ round($comparison['variant_b']['conversion_rate'], 2) }}%
                    </p>
                </div>
                <div class="text-center p-4 bg-white/70 dark:bg-gray-900/70 rounded-xl">
                    <p class="text-xs font-bold text-purple-700 dark:text-purple-300 mb-2">Avg Response Time</p>
                    <p class="text-2xl font-black text-purple-600 dark:text-purple-400">
                        {{ round($comparison['variant_b']['avg_response_time'], 0) }}ms
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Conversion Rate Chart --}}
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 mb-8 border border-white/20 dark:border-gray-700/30">
        <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-6 flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                <i class="fas fa-chart-bar text-white"></i>
            </div>
            <span>Conversion Rate Comparison</span>
        </h2>

        <div class="relative h-80">
            <canvas id="conversionChart"></canvas>
        </div>
    </div>

    {{-- Description --}}
    @if($test->description)
    <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 rounded-2xl shadow-2xl p-6 border border-white/20 dark:border-gray-700/30">
        <h3 class="text-xl font-black text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-info-circle text-[#06C755]"></i>
            <span>คำอธิบาย</span>
        </h3>
        <p class="text-gray-700 dark:text-gray-300">
            {{ $test->description }}
        </p>
    </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
/**
 * A/B Test Results Component
 *
 * จัดการการแสดงผลและ charts สำหรับผลลัพธ์ A/B Test
 */
function abTestResults(test, comparison, summary) {
    return {
        test: test,
        comparison: comparison,
        summary: summary,
        conversionChart: null,

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('A/B Test Results initialized', this.test);
            this.$nextTick(() => {
                this.initCharts();
            });
        },

        /**
         * สร้าง Charts ทั้งหมด
         */
        initCharts() {
            this.createConversionChart();
        },

        /**
         * สร้าง Conversion Rate Chart
         */
        createConversionChart() {
            const ctx = document.getElementById('conversionChart');
            if (!ctx) return;

            const data = {
                labels: ['Variant A', 'Variant B'],
                datasets: [
                    {
                        label: 'Conversion Rate (%)',
                        data: [
                            this.comparison.variant_a.conversion_rate,
                            this.comparison.variant_b.conversion_rate
                        ],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',  // Blue
                            'rgba(168, 85, 247, 0.8)'   // Purple
                        ],
                        borderColor: [
                            'rgb(59, 130, 246)',
                            'rgb(168, 85, 247)'
                        ],
                        borderWidth: 2
                    }
                ]
            };

            this.conversionChart = new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? '#fff' : '#000',
                                font: {
                                    size: 14,
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                },
                                color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280',
                                font: {
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        /**
         * เริ่มการทดสอบ
         */
        startTest() {
            if (!confirm('เริ่มการทดสอบหรือไม่?')) return;

            fetch('{{ route("admin.line-bot.keywords.ab-tests.start", $test) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    alert('เริ่มการทดสอบสำเร็จ');
                    location.reload();
                } else {
                    alert('ข้อผิดพลาด: ' + d.message);
                }
            });
        },

        /**
         * จบการทดสอบ
         */
        completeTest() {
            if (!confirm('จบการทดสอบหรือไม่? ระบบจะวิเคราะห์ผล')) return;

            fetch('{{ route("admin.line-bot.keywords.ab-tests.complete", $test) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    alert('จบการทดสอบสำเร็จ');
                    location.reload();
                } else {
                    alert('ข้อผิดพลาด: ' + d.message);
                }
            });
        },

        /**
         * หยุดการทดสอบชั่วคราว
         */
        pauseTest() {
            const reason = prompt('เหตุผลในการหยุด (ไม่บังคับ):');
            if (reason === null) return;

            fetch('{{ route("admin.line-bot.keywords.ab-tests.pause", $test) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ reason })
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    alert('หยุดการทดสอบสำเร็จ');
                    location.reload();
                } else {
                    alert('ข้อผิดพลาด: ' + d.message);
                }
            });
        },

        /**
         * นำผู้ชนะไปใช้
         */
        applyWinner() {
            const winnerName = this.test.winner.replace('_', ' ');
            if (!confirm(`นำ ${winnerName} ไปใช้กับคีย์เวิร์ดหรือไม่?`)) return;

            fetch('{{ route("admin.line-bot.keywords.ab-tests.apply-winner", $test) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    alert('นำไปใช้สำเร็จ - คีย์เวิร์ดอัพเดทแล้ว');
                    location.reload();
                } else {
                    alert('ข้อผิดพลาด: ' + d.message);
                }
            });
        },

        /**
         * ลบการทดสอบ
         */
        deleteTest() {
            if (!confirm('ลบการทดสอบนี้หรือไม่? การกระทำนี้ไม่สามารถยกเลิกได้')) return;

            fetch('{{ route("admin.line-bot.keywords.ab-tests.destroy", $test) }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            }).then(r => r.json()).then(d => {
                if (d.success) {
                    alert('ลบสำเร็จ');
                    window.location.href = '{{ route("admin.line-bot.keywords.ab-tests.index") }}';
                } else {
                    alert('ข้อผิดพลาด: ' + d.message);
                }
            });
        }
    };
}
</script>
@endpush
@endsection
