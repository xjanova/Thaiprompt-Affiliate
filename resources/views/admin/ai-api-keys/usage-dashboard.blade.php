@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('content')
<div x-data="aiUsageDashboard()" x-init="init()" class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📊 {{ $pageTitle }}</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">การใช้ AI ของระบบดูดวง — token / requests / failed ตามจริงต่อคีย์</p>
        </div>
        <a href="{{ route('admin.ai-api-keys.index') }}"
           class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center gap-2 w-fit">
            ← กลับไปจัดการคีย์
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🔧 ตัวกรอง</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่เริ่ม</label>
                <input type="date" x-model="start"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วันที่สิ้นสุด</label>
                <input type="date" x-model="end"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Quick range
                </label>
                <div class="flex flex-wrap gap-2">
                    <button @click="setRange(1)" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg">วันนี้</button>
                    <button @click="setRange(7)" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg">7 วัน</button>
                    <button @click="setRange(30)" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg">30 วัน</button>
                    <button @click="setRange(90)" class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg">90 วัน</button>
                </div>
            </div>
        </div>

        {{-- Keys filter --}}
        <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    เลือกคีย์ที่จะแสดง (ไม่เลือก = ทั้งหมด)
                </label>
                <div class="flex gap-2">
                    <button @click="selectAllKeys()" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">เลือกทั้งหมด</button>
                    <span class="text-gray-300">|</span>
                    <button @click="clearKeys()" class="text-xs text-red-600 dark:text-red-400 hover:underline">ล้าง</button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-64 overflow-y-auto p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                @foreach($allKeys as $key)
                <label class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    <input type="checkbox" :value="{{ $key->id }}" x-model="selectedKeyIds"
                           class="rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm text-gray-700 dark:text-gray-200 truncate">
                        <span class="font-medium">{{ $key->provider }}</span>/{{ $key->name }}
                        @if($key->model)
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $key->model }}</span>
                        @endif
                        @if($key->purpose && $key->purpose !== 'any')
                        <span class="text-xs px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded">{{ $key->purpose }}</span>
                        @endif
                    </span>
                </label>
                @endforeach
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button @click="fetchData()"
                    :disabled="loading"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white rounded-lg transition flex items-center gap-2">
                <svg class="w-4 h-4" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span x-text="loading ? 'กำลังโหลด...' : 'โหลดข้อมูล'"></span>
            </button>
            <span class="text-sm text-gray-500 dark:text-gray-400" x-show="summary">
                ช่วง: <span x-text="summary?.date_range?.start"></span> ถึง <span x-text="summary?.date_range?.end"></span>
                (<span x-text="summary?.date_range?.days"></span> วัน)
            </span>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4" x-show="summary">
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl p-5 text-white shadow">
            <div class="text-blue-100 text-sm font-medium">Total Tokens</div>
            <div class="text-3xl font-bold mt-2" x-text="formatNumber(summary?.total_tokens || 0)"></div>
            <div class="text-blue-100 text-xs mt-1">รวม input + output</div>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-xl p-5 text-white shadow">
            <div class="text-emerald-100 text-sm font-medium">Total Requests</div>
            <div class="text-3xl font-bold mt-2" x-text="formatNumber(summary?.total_requests || 0)"></div>
            <div class="text-emerald-100 text-xs mt-1">ทุก AI call</div>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-amber-700 rounded-xl p-5 text-white shadow">
            <div class="text-amber-100 text-sm font-medium">Success Rate</div>
            <div class="text-3xl font-bold mt-2"><span x-text="summary?.success_rate || 100"></span>%</div>
            <div class="text-amber-100 text-xs mt-1">requests สำเร็จ</div>
        </div>
        <div class="bg-gradient-to-br from-rose-500 to-rose-700 rounded-xl p-5 text-white shadow">
            <div class="text-rose-100 text-sm font-medium">Failed Requests</div>
            <div class="text-3xl font-bold mt-2" x-text="formatNumber(summary?.failed_count || 0)"></div>
            <div class="text-rose-100 text-xs mt-1">ล้มเหลว / error</div>
        </div>
    </div>

    {{-- Line chart --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📈 Token Usage รายวัน</h2>
        <div class="relative" style="height: 400px;">
            <canvas id="usageChart"></canvas>
            <div x-show="!chartReady" class="absolute inset-0 flex items-center justify-center text-gray-400 text-center px-4">
                <div>
                    <span x-show="loading">⏳ กำลังโหลด...</span>
                    <span x-show="!loading && !chartReady && !chartError">กดปุ่ม "โหลดข้อมูล" เพื่อแสดงกราฟ</span>
                    <span x-show="!loading && !chartReady && chartError" class="text-red-500" x-text="chartError"></span>
                    <div x-show="!loading && !chartReady && chartError" class="mt-2">
                        <button @click="fetchData()" class="px-4 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">🔄 ลองใหม่</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Breakdown table --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden" x-show="breakdown && breakdown.length > 0">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📋 Breakdown ตามคีย์ (เรียงจากใช้มากสุด)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คีย์</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Model</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Purpose</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total Tokens</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Input</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Output</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Requests</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Failed</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="row in breakdown" :key="row.key_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white" x-text="row.name"></td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400" x-text="row.model"></td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 text-xs rounded bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300" x-text="row.purpose"></span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-mono font-medium text-blue-700 dark:text-blue-300" x-text="formatNumber(row.total_tokens)"></td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-gray-600 dark:text-gray-400" x-text="formatNumber(row.input_tokens)"></td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-gray-600 dark:text-gray-400" x-text="formatNumber(row.output_tokens)"></td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-gray-700 dark:text-gray-300" x-text="formatNumber(row.requests)"></td>
                            <td class="px-4 py-3 text-sm text-right font-mono" :class="row.failed > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400'" x-text="formatNumber(row.failed)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
{{-- Chart.js CDN --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function aiUsageDashboard() {
    return {
        loading: false,
        chartReady: false,
        chartError: '',
        chart: null,
        start: '',
        end: '',
        selectedKeyIds: [],
        summary: null,
        breakdown: [],

        init() {
            // ค่าเริ่มต้น: 7 วันย้อนหลัง
            const today = new Date();
            const sevenAgo = new Date();
            sevenAgo.setDate(today.getDate() - 6);
            this.end = today.toISOString().split('T')[0];
            this.start = sevenAgo.toISOString().split('T')[0];

            // load ครั้งแรก
            this.fetchData();
        },

        setRange(days) {
            const today = new Date();
            const start = new Date();
            start.setDate(today.getDate() - (days - 1));
            this.end = today.toISOString().split('T')[0];
            this.start = start.toISOString().split('T')[0];
            this.fetchData();
        },

        selectAllKeys() {
            const allCheckboxes = document.querySelectorAll('input[type="checkbox"][x-model="selectedKeyIds"]');
            this.selectedKeyIds = Array.from(allCheckboxes).map(c => parseInt(c.value));
        },

        clearKeys() {
            this.selectedKeyIds = [];
        },

        async fetchData() {
            this.loading = true;
            this.chartError = '';
            try {
                const params = new URLSearchParams();
                params.append('start', this.start);
                params.append('end', this.end);
                this.selectedKeyIds.forEach(id => params.append('key_ids[]', id));

                const url = `{{ route('admin.ai-api-keys.usage-dashboard.data') }}?${params.toString()}`;
                const resp = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await resp.json();

                if (!data.success) {
                    this.chartError = '⚠️ API ตอบกลับ success=false';
                    return;
                }

                this.summary = data.summary;
                this.breakdown = data.breakdown;
                await this.renderChart(data.chart);
            } catch (e) {
                console.error('fetchData error', e);
                this.chartError = '⚠️ โหลดข้อมูลล้มเหลว: ' + e.message;
            } finally {
                this.loading = false;
            }
        },

        // 🆕 (2026-05-17) Wait for Chart.js to load — กัน race ตอน CDN ช้า/AdBlock
        //   จากปัญหา "เห็นบ้างไม่เห็นบ้าง" — Chart undefined ตอน init เร็วเกินไป
        async waitForChartJs(timeoutMs = 5000) {
            if (typeof window.Chart !== 'undefined') return true;
            const start = Date.now();
            while (Date.now() - start < timeoutMs) {
                await new Promise(r => setTimeout(r, 100));
                if (typeof window.Chart !== 'undefined') return true;
            }
            return false;
        },

        async renderChart(chartData) {
            const ctx = document.getElementById('usageChart');
            if (!ctx) {
                this.chartError = '⚠️ ไม่พบ canvas element (DOM ไม่พร้อม)';
                return;
            }

            // กัน race: รอ Chart.js ให้พร้อมก่อน
            const ready = await this.waitForChartJs();
            if (!ready) {
                this.chartError = '⚠️ Chart.js โหลดไม่สำเร็จ (อาจถูก AdBlocker บล็อค) — refresh หน้า / ปิด AdBlocker';
                return;
            }

            // กันข้อมูลเปล่า — Chart.js render canvas เปล่าจะดูเหมือนไม่มีกราฟ
            const hasData = chartData?.datasets?.some(ds => ds.data?.some(v => v > 0));
            if (!hasData) {
                this.chartError = '📭 ไม่มีข้อมูล token usage ในช่วงเวลาที่เลือก';
                if (this.chart) { this.chart.destroy(); this.chart = null; }
                return;
            }

            if (this.chart) {
                this.chart.destroy();
            }

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
            const textColor = isDark ? '#e5e7eb' : '#374151';

            try {
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: chartData.datasets,
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: textColor, padding: 15, font: { size: 11 } },
                            },
                            tooltip: {
                                callbacks: {
                                    label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString()} tokens`,
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { color: gridColor },
                                ticks: { color: textColor },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: {
                                    color: textColor,
                                    callback: (value) => value.toLocaleString(),
                                },
                                title: {
                                    display: true,
                                    text: 'Tokens',
                                    color: textColor,
                                },
                            },
                        },
                    },
                });
                this.chartReady = true;
            } catch (e) {
                console.error('Chart render error:', e);
                this.chartError = '⚠️ สร้างกราฟล้มเหลว: ' + e.message;
            }
        },

        formatNumber(n) {
            return Number(n || 0).toLocaleString();
        },
    };
}
</script>
@endpush

@endsection
