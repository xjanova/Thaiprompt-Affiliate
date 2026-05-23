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

        {{-- Provider + Purpose dropdowns (2026-05-19) --}}
        @php
            $uniqueProviders = collect($allKeys)->pluck('provider')->unique()->filter()->sort()->values();
            $uniquePurposes = collect($allKeys)->pluck('purpose')->map(fn ($p) => $p ?: 'null')->unique()->sort()->values();
        @endphp
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    🌐 Provider
                </label>
                <select x-model="filterProvider" @change="applyKeyFilters()"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">— ทุก provider —</option>
                    @foreach ($uniqueProviders as $p)
                        <option value="{{ $p }}">{{ $p }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    🎯 Purpose (จุดประสงค์การใช้)
                </label>
                <select x-model="filterPurpose" @change="applyKeyFilters()"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">— ทุก purpose —</option>
                    @foreach ($uniquePurposes as $p)
                        <option value="{{ $p }}">{{ $p === 'null' ? '(ไม่กำหนด)' : $p }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Keys filter --}}
        <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    เลือกคีย์ที่จะแสดง (ไม่เลือก = ทั้งหมดที่ผ่านฟิลเตอร์)
                </label>
                <div class="flex gap-2">
                    <button @click="selectAllVisibleKeys()" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">เลือกทั้งหมดที่เห็น</button>
                    <span class="text-gray-300">|</span>
                    <button @click="clearKeys()" class="text-xs text-red-600 dark:text-red-400 hover:underline">ล้าง</button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-64 overflow-y-auto p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                @foreach ($allKeys as $key)
                    <label
                        x-show="keyMatchesFilter('{{ $key->provider }}', '{{ $key->purpose ?: 'null' }}')"
                        data-key-id="{{ $key->id }}"
                        data-provider="{{ $key->provider }}"
                        data-purpose="{{ $key->purpose ?: 'null' }}"
                        class="flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <input type="checkbox" :value="{{ $key->id }}" x-model="selectedKeyIds"
                               class="rounded text-blue-600 focus:ring-2 focus:ring-blue-500">
                        <span class="text-sm text-gray-700 dark:text-gray-200 truncate">
                            <span class="font-medium">{{ $key->provider }}</span>/{{ $key->name }}
                            @if ($key->model)
                                <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $key->model }}</span>
                            @endif
                            @if ($key->purpose && $key->purpose !== 'any')
                                <span class="text-xs px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded">{{ $key->purpose }}</span>
                            @elseif ($key->purpose === 'any')
                                {{-- 🚫 (2026-05-23) purpose='any' ถูก deprecate — Pool จะไม่ pick key นี้ --}}
                                <span class="text-xs px-1.5 py-0.5 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 rounded" title="purpose='any' ถูกห้ามใช้แล้ว — เปลี่ยนเป็น specific purpose">⚠️ any (deprecated)</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                แสดง: <span x-text="visibleKeyCount()"></span> คีย์ / ทั้งหมด {{ count($allKeys) }}
                — เลือก: <span x-text="selectedKeyIds.length"></span> คีย์
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
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">📈 Token Usage รายวัน</h2>
            {{-- 🆕 (2026-05-19) Refresh button visible เสมอ — กัน "กราฟเห็นบ้างไม่เห็นบ้าง" --}}
            <button @click="fetchData()" :disabled="loading"
                    class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 disabled:opacity-50 flex items-center gap-1">
                <svg class="w-3 h-3" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span x-text="loading ? 'กำลังโหลด...' : 'รีเฟรชกราฟ'"></span>
            </button>
        </div>
        <div class="relative" style="height: 400px;">
            <canvas id="usageChart"></canvas>
            <div x-show="!chartReady" class="absolute inset-0 flex items-center justify-center text-gray-400 text-center px-4 pointer-events-none">
                <div class="pointer-events-auto">
                    <span x-show="loading">⏳ กำลังโหลด...</span>
                    <span x-show="!loading && !chartReady && !chartError">กดปุ่ม "โหลดข้อมูล" เพื่อแสดงกราฟ</span>
                    <span x-show="!loading && !chartReady && chartError" class="text-red-500" x-text="chartError"></span>
                    <div x-show="!loading && !chartReady && chartError" class="mt-2">
                        <button @click="fetchData()" class="px-4 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">🔄 ลองใหม่</button>
                    </div>
                </div>
            </div>
        </div>
        {{-- 🆕 (2026-05-19) Diagnostic strip — แสดง state ของ render เพื่อ debug --}}
        <div class="mt-2 text-xs text-gray-400 dark:text-gray-500 font-mono" x-show="renderDiag">
            <span x-text="renderDiag"></span>
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
        // 🆕 (2026-05-19) Filter ตาม provider + purpose
        filterProvider: '',
        filterPurpose: '',
        summary: null,
        breakdown: [],
        // 🆕 (2026-05-19) Re-entrance guard + diagnostics — กัน "เห็นบ้างไม่เห็นบ้าง"
        _rendering: false,
        _fetching: false,
        renderDiag: '',

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

        // 🆕 (2026-05-19) เช็คว่า key ตรงกับ filter ที่เลือกหรือไม่
        keyMatchesFilter(provider, purpose) {
            if (this.filterProvider && this.filterProvider !== provider) return false;
            if (this.filterPurpose && this.filterPurpose !== purpose) return false;
            return true;
        },

        // 🆕 (2026-05-19) ตอนเปลี่ยน filter — ลบ key ที่เลือกอยู่ออกถ้าไม่ match แล้ว
        applyKeyFilters() {
            const visibleKeyIds = this.getVisibleKeyIds();
            this.selectedKeyIds = this.selectedKeyIds.filter(id => visibleKeyIds.includes(id));
        },

        // 🆕 ดึง key IDs ของ checkbox ที่ visible ตาม filter
        getVisibleKeyIds() {
            const labels = document.querySelectorAll('label[data-key-id]');
            const visible = [];
            labels.forEach(lbl => {
                const p = lbl.dataset.provider;
                const pp = lbl.dataset.purpose;
                if (this.keyMatchesFilter(p, pp)) {
                    visible.push(parseInt(lbl.dataset.keyId));
                }
            });
            return visible;
        },

        visibleKeyCount() {
            return this.getVisibleKeyIds().length;
        },

        // 🆕 เลือกทั้งหมด *เฉพาะที่ visible* (รองรับ filter)
        selectAllVisibleKeys() {
            this.selectedKeyIds = this.getVisibleKeyIds();
        },

        clearKeys() {
            this.selectedKeyIds = [];
        },

        async fetchData() {
            // 🆕 (2026-05-19) Re-entrance guard — ถ้า fetch อยู่แล้ว ข้าม
            if (this._fetching) {
                this.renderDiag = `[skip] fetch กำลังทำงานอยู่`;
                return;
            }
            this._fetching = true;
            this.loading = true;
            this.chartError = '';
            try {
                const params = new URLSearchParams();
                params.append('start', this.start);
                params.append('end', this.end);

                // 🆕 (2026-05-19) ถ้า user ตั้ง filter (provider/purpose) แต่ยังไม่กดเลือก
                //   → ใช้ visible keys ทั้งหมดที่ผ่าน filter
                //   ไม่มี filter + ไม่เลือก = ทั้งหมด (เดิม)
                let keyIds = this.selectedKeyIds;
                if (keyIds.length === 0 && (this.filterProvider || this.filterPurpose)) {
                    keyIds = this.getVisibleKeyIds();
                }
                keyIds.forEach(id => params.append('key_ids[]', id));

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
                this._fetching = false;
            }
        },

        // 🆕 (2026-05-19 v2) Wait for Chart.js — เพิ่ม timeout เป็น 15s (CDN ช้าบ่อยกว่า 5s)
        //   ถ้า AdBlock บล็อค → 15s ก็ไม่ช่วย แต่ user มีเวลาเห็น diag
        async waitForChartJs(timeoutMs = 15000) {
            if (typeof window.Chart !== 'undefined') return true;
            const start = Date.now();
            while (Date.now() - start < timeoutMs) {
                await new Promise(r => setTimeout(r, 100));
                if (typeof window.Chart !== 'undefined') return true;
            }
            return false;
        },

        // 🆕 (2026-05-19) Wait for canvas to have non-zero dimensions
        //   Chart.js v4 ถ้า canvas width/height = 0 จะวาดเปล่า — กราฟไม่ขึ้น
        //   เกิดตอน parent layout ยังไม่ stable (CSS เพิ่งโหลด, transition active)
        async waitForCanvasLayout(canvas, timeoutMs = 3000) {
            const start = Date.now();
            while (Date.now() - start < timeoutMs) {
                const rect = canvas.getBoundingClientRect();
                if (rect.width > 0 && rect.height > 0) return true;
                await new Promise(r => setTimeout(r, 100));
            }
            return false;
        },

        async renderChart(chartData) {
            // 🆕 (2026-05-19) Re-entrance guard
            if (this._rendering) {
                this.renderDiag = `[skip] render กำลังทำงานอยู่`;
                return;
            }
            this._rendering = true;

            try {
                const ctx = document.getElementById('usageChart');
                if (!ctx) {
                    this.chartError = '⚠️ ไม่พบ canvas element (DOM ไม่พร้อม)';
                    this.renderDiag = '[fail] canvas not found';
                    return;
                }

                // 1) กัน race: รอ Chart.js ให้พร้อมก่อน (15s)
                this.renderDiag = '[step 1/4] รอ Chart.js โหลด...';
                const ready = await this.waitForChartJs();
                if (!ready) {
                    this.chartError = '⚠️ Chart.js โหลดไม่สำเร็จใน 15 วินาที (อาจถูก AdBlocker บล็อค) — refresh หน้า / ปิด AdBlocker';
                    this.renderDiag = '[fail] Chart.js timeout';
                    return;
                }

                // 2) รอ canvas ให้มีขนาด (กัน width=0 เพราะ layout ยังไม่ stable)
                this.renderDiag = '[step 2/4] รอ canvas layout...';
                const layoutOk = await this.waitForCanvasLayout(ctx);
                if (!layoutOk) {
                    this.chartError = '⚠️ Canvas ไม่มีขนาด (layout ผิดปกติ) — refresh หน้าใหม่';
                    this.renderDiag = '[fail] canvas dimensions = 0';
                    return;
                }

                // 3) กันข้อมูลเปล่า
                const hasData = chartData?.datasets?.some(ds => ds.data?.some(v => v > 0));
                if (!hasData) {
                    this.chartError = '📭 ไม่มีข้อมูล token usage ในช่วงเวลาที่เลือก';
                    this.renderDiag = '[done] empty data';
                    if (this.chart) { try { this.chart.destroy(); } catch (e) {} this.chart = null; }
                    const stale = window.Chart?.getChart?.(ctx);
                    if (stale) { try { stale.destroy(); } catch (e) {} }
                    return;
                }

                // 4) Destroy chart เก่า — รวม "stale instance" ที่ Chart.js attach กับ canvas
                //    เคสที่ทำให้ "เห็นบ้างไม่เห็นบ้าง": new Chart() throws ถ้า canvas ถูกใช้แล้ว
                this.renderDiag = '[step 3/4] cleanup chart เก่า...';
                if (this.chart) { try { this.chart.destroy(); } catch (e) {} this.chart = null; }
                const stale = window.Chart.getChart?.(ctx);
                if (stale) { try { stale.destroy(); } catch (e) {} }

                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
                const textColor = isDark ? '#e5e7eb' : '#374151';

                this.renderDiag = '[step 4/4] วาดกราฟ...';

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
                this.renderDiag = `[done] วาดกราฟสำเร็จ (${chartData.datasets?.length || 0} datasets)`;
            } catch (e) {
                console.error('Chart render error:', e);
                this.chartError = '⚠️ สร้างกราฟล้มเหลว: ' + e.message;
                this.renderDiag = `[fail] new Chart error: ${e.message}`;
            }
            } finally {
                this._rendering = false;
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
