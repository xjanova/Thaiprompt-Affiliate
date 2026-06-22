@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- ===== หน้า "สถิติการใช้งาน AI API" ธีม V4 นวลทองคำ (re-skin — เก็บ Chart.js) ===== --}}
{{-- container หลัก: เว้นระยะแนวตั้งสม่ำเสมอ + Alpine root เดิม --}}
<div x-data="aiUsageDashboard()" x-init="init()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header: eyebrow + ชื่อหน้า + ปุ่มกลับ ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · AI · สถิติการใช้งาน API
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-chart-line" style="color:var(--accent1);"></i> {{ $pageTitle }}
            </h1>
            <p class="tp-muted" style="margin:6px 0 0; font-size:13px; max-width:680px; line-height:1.55;">
                การใช้ AI ของระบบดูดวง — token / requests / failed ตามจริงต่อคีย์
            </p>
        </div>
        <a href="{{ route('admin.ai-api-keys.index') }}" class="tp-btn">
            <i class="fas fa-arrow-left"></i> กลับไปจัดการคีย์
        </a>
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card">
        <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:14px;">
            <i class="fas fa-sliders" style="color:var(--accent1);"></i> ตัวกรอง
        </div>

        {{-- วันที่ + quick range --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">วันที่เริ่ม</label>
                <div class="tp-well" style="padding:0;">
                    <input type="date" x-model="start" class="tp-input"
                           style="width:100%; background:transparent; border:0; padding:9px 12px;">
                </div>
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">วันที่สิ้นสุด</label>
                <div class="tp-well" style="padding:0;">
                    <input type="date" x-model="end" class="tp-input"
                           style="width:100%; background:transparent; border:0; padding:9px 12px;">
                </div>
            </div>
            <div style="grid-column:span 2 / span 2; min-width:240px;">
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">ช่วงเวลาด่วน</label>
                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                    <button @click="setRange(1)" class="tp-btn tp-btn-sm">วันนี้</button>
                    <button @click="setRange(7)" class="tp-btn tp-btn-sm">7 วัน</button>
                    <button @click="setRange(30)" class="tp-btn tp-btn-sm">30 วัน</button>
                    <button @click="setRange(90)" class="tp-btn tp-btn-sm">90 วัน</button>
                </div>
            </div>
        </div>

        {{-- Provider + Purpose dropdowns (2026-05-19) --}}
        @php
            $uniqueProviders = collect($allKeys)->pluck('provider')->unique()->filter()->sort()->values();
            $uniquePurposes = collect($allKeys)->pluck('purpose')->map(fn ($p) => $p ?: 'null')->unique()->sort()->values();
        @endphp
        <div style="margin-top:14px; display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                    🌐 Provider
                </label>
                <div class="tp-well" style="padding:0;">
                    <select x-model="filterProvider" @change="applyKeyFilters()"
                            style="width:100%; background:transparent; border:0; padding:9px 12px; color:var(--ink);">
                        <option value="">— ทุก provider —</option>
                        @foreach ($uniqueProviders as $p)
                            <option value="{{ $p }}">{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label style="display:block; font-size:12px; font-weight:600; color:var(--ink2); margin-bottom:6px;">
                    🎯 Purpose (จุดประสงค์การใช้)
                </label>
                <div class="tp-well" style="padding:0;">
                    <select x-model="filterPurpose" @change="applyKeyFilters()"
                            style="width:100%; background:transparent; border:0; padding:9px 12px; color:var(--ink);">
                        <option value="">— ทุก purpose —</option>
                        @foreach ($uniquePurposes as $p)
                            <option value="{{ $p }}">{{ $p === 'null' ? '(ไม่กำหนด)' : $p }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Keys filter --}}
        <div style="margin-top:16px;">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:8px;">
                <label style="font-size:12px; font-weight:600; color:var(--ink2);">
                    เลือกคีย์ที่จะแสดง (ไม่เลือก = ทั้งหมดที่ผ่านฟิลเตอร์)
                </label>
                <div style="display:flex; gap:8px;">
                    <button @click="selectAllVisibleKeys()" class="tp-btn tp-btn-sm">เลือกทั้งหมดที่เห็น</button>
                    <button @click="clearKeys()" class="tp-btn tp-btn-sm">ล้าง</button>
                </div>
            </div>
            <div class="tp-inset" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:10px; max-height:264px; overflow-y:auto; padding:12px; border-radius:14px;">
                @foreach ($allKeys as $key)
                    <label
                        x-show="keyMatchesFilter('{{ $key->provider }}', '{{ $key->purpose ?: 'null' }}')"
                        data-key-id="{{ $key->id }}"
                        data-provider="{{ $key->provider }}"
                        data-purpose="{{ $key->purpose ?: 'null' }}"
                        class="tp-well tp-card-hover"
                        style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:12px; cursor:pointer; box-shadow:var(--inset-sm);">
                        <input type="checkbox" :value="{{ $key->id }}" x-model="selectedKeyIds"
                               style="accent-color:var(--accent1); width:16px; height:16px; flex:0 0 auto;">
                        <span style="font-size:13px; color:var(--ink); min-width:0;">
                            <span style="font-weight:700;">{{ $key->provider }}</span>/{{ $key->name }}
                            @if ($key->model)
                                <span style="font-size:11px; color:var(--ink2); display:block;">{{ $key->model }}</span>
                            @endif
                            @if ($key->purpose && $key->purpose !== 'any')
                                <span class="tp-pill tp-pill-soft" style="font-size:11px; padding:1px 7px;">{{ $key->purpose }}</span>
                            @elseif ($key->purpose === 'any')
                                {{-- (2026-05-23) purpose='any' ถูก deprecate — Pool จะไม่ pick key นี้ --}}
                                <span class="tp-pill" style="font-size:11px; padding:1px 7px; background:rgba(217,83,79,.14); color:#d9534f;" title="purpose='any' ถูกห้ามใช้แล้ว — เปลี่ยนเป็น specific purpose">⚠️ any (deprecated)</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>
            <div style="font-size:11.5px; color:var(--ink2); margin-top:8px;">
                แสดง: <span x-text="visibleKeyCount()"></span> คีย์ / ทั้งหมด {{ count($allKeys) }}
                — เลือก: <span x-text="selectedKeyIds.length"></span> คีย์
            </div>
        </div>

        {{-- ปุ่มโหลด + ช่วงวันที่ที่ดึงจริง --}}
        <div style="margin-top:16px; display:flex; flex-wrap:wrap; align-items:center; gap:12px;">
            <button @click="fetchData()" :disabled="loading" class="tp-btn tp-btn-primary"
                    style="display:inline-flex; align-items:center; gap:8px;">
                <i class="fas fa-rotate" :class="{ 'fa-spin': loading }"></i>
                <span x-text="loading ? 'กำลังโหลด...' : 'โหลดข้อมูล'"></span>
            </button>
            <span style="font-size:12.5px; color:var(--ink2);" x-show="summary">
                ช่วง: <span x-text="summary?.date_range?.start"></span> ถึง <span x-text="summary?.date_range?.end"></span>
                (<span x-text="summary?.date_range?.days"></span> วัน)
            </span>
        </div>
    </div>

    {{-- ===== KPI สรุป ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px;" x-show="summary">
        {{-- Total Tokens --}}
        <div class="tp-card" style="display:flex; align-items:center; gap:14px;">
            <div class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto;">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;" x-text="formatNumber(summary?.total_tokens || 0)"></div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">Total Tokens (input + output)</div>
            </div>
        </div>
        {{-- Total Requests --}}
        <div class="tp-card" style="display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#5689b8; box-shadow:var(--raise);">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;" x-text="formatNumber(summary?.total_requests || 0)"></div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">Total Requests (ทุก AI call)</div>
            </div>
        </div>
        {{-- Success Rate --}}
        <div class="tp-card" style="display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#5aa07e; box-shadow:var(--raise);">
                <i class="fas fa-circle-check"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;"><span x-text="summary?.success_rate || 100"></span>%</div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">Success Rate (requests สำเร็จ)</div>
            </div>
        </div>
        {{-- Failed Requests --}}
        <div class="tp-card" style="display:flex; align-items:center; gap:14px;">
            <div style="width:46px; height:46px; border-radius:14px; font-size:20px; flex:0 0 auto; display:flex; align-items:center; justify-content:center; color:#fff; background:#d9534f; box-shadow:var(--raise);">
                <i class="fas fa-triangle-exclamation"></i>
            </div>
            <div>
                <div class="tp-num" style="font-size:26px; font-weight:800; line-height:1;" x-text="formatNumber(summary?.failed_count || 0)"></div>
                <div style="font-size:12px; color:var(--ink2); margin-top:3px;">Failed Requests (ล้มเหลว / error)</div>
            </div>
        </div>
    </div>

    {{-- ===== กราฟเส้น Token Usage รายวัน (เก็บ Chart.js + canvas เดิม) ===== --}}
    <div class="tp-card">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700;">
                <i class="fas fa-chart-area" style="color:var(--accent1);"></i> Token Usage รายวัน
            </div>
            {{-- (2026-05-19) Refresh button visible เสมอ — กัน "กราฟเห็นบ้างไม่เห็นบ้าง" --}}
            <button @click="fetchData()" :disabled="loading" class="tp-btn tp-btn-sm"
                    style="display:inline-flex; align-items:center; gap:6px;">
                <i class="fas fa-rotate" :class="{ 'fa-spin': loading }"></i>
                <span x-text="loading ? 'กำลังโหลด...' : 'รีเฟรชกราฟ'"></span>
            </button>
        </div>
        {{-- กรอบ canvas: inset พื้นเรียบ, canvas เต็มกว้าง --}}
        <div class="tp-inset" style="position:relative; height:400px; padding:12px; border-radius:14px;">
            <canvas id="usageChart"></canvas>
            <div x-show="!chartReady" style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; text-align:center; padding:0 16px; color:var(--ink2); pointer-events:none;">
                <div style="pointer-events:auto;">
                    <span x-show="loading">⏳ กำลังโหลด...</span>
                    <span x-show="!loading && !chartReady && !chartError">กดปุ่ม "โหลดข้อมูล" เพื่อแสดงกราฟ</span>
                    <span x-show="!loading && !chartReady && chartError" style="color:#d9534f;" x-text="chartError"></span>
                    <div x-show="!loading && !chartReady && chartError" style="margin-top:10px;">
                        <button @click="fetchData()" class="tp-btn tp-btn-sm tp-btn-primary">🔄 ลองใหม่</button>
                    </div>
                </div>
            </div>
        </div>
        {{-- (2026-05-19) Diagnostic strip — แสดง state ของ render เพื่อ debug --}}
        <div style="margin-top:8px; font-size:11px; color:var(--ink2); font-family:ui-monospace,monospace;" x-show="renderDiag">
            <span x-text="renderDiag"></span>
        </div>
    </div>

    {{-- ===== Breakdown ตามคีย์ ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;" x-show="breakdown && breakdown.length > 0">
        <div style="padding:16px 18px; box-shadow:var(--inset-sm);">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700;">
                <i class="fas fa-table-list" style="color:var(--accent1);"></i> Breakdown ตามคีย์ (เรียงจากใช้มากสุด)
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table style="min-width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="text-align:left;">
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">คีย์</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">Model</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">Purpose</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">Total Tokens</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">Input</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">Output</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">Requests</th>
                        <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">Failed</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in breakdown" :key="row.key_id">
                        <tr style="box-shadow:var(--inset-sm);">
                            <td style="padding:11px 16px; font-size:13px; font-weight:600; color:var(--ink);" x-text="row.name"></td>
                            <td style="padding:11px 16px; font-size:13px; color:var(--ink2);" x-text="row.model"></td>
                            <td style="padding:11px 16px; font-size:13px;">
                                <span class="tp-pill tp-pill-soft" style="font-size:11px; padding:2px 8px;" x-text="row.purpose"></span>
                            </td>
                            <td style="padding:11px 16px; font-size:13px; text-align:right; font-family:ui-monospace,monospace; font-weight:700; color:var(--deep1);" x-text="formatNumber(row.total_tokens)"></td>
                            <td style="padding:11px 16px; font-size:13px; text-align:right; font-family:ui-monospace,monospace; color:var(--ink2);" x-text="formatNumber(row.input_tokens)"></td>
                            <td style="padding:11px 16px; font-size:13px; text-align:right; font-family:ui-monospace,monospace; color:var(--ink2);" x-text="formatNumber(row.output_tokens)"></td>
                            <td style="padding:11px 16px; font-size:13px; text-align:right; font-family:ui-monospace,monospace; color:var(--ink);" x-text="formatNumber(row.requests)"></td>
                            <td style="padding:11px 16px; font-size:13px; text-align:right; font-family:ui-monospace,monospace;" :style="row.failed > 0 ? 'color:#d9534f;' : 'color:var(--ink2);'" x-text="formatNumber(row.failed)"></td>
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
    // จานสีทอง/นวลทองคำ V4 — ใช้วนสีให้ dataset ของ Chart.js (re-skin เฉพาะสี ไม่แตะ payload)
    const TP_GOLD_PALETTE = ['#e6b347', '#d98e3f', '#d6824a', '#b79ae8', '#5689b8', '#5aa07e', '#e0a52e', '#9a8f7c'];

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

        // 🆕 เลือกทั้งหมด เฉพาะที่ visible (รองรับ filter)
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

        // 🆕 (V4) ทาสีทองนวลให้ dataset ทุกเส้น — re-skin เฉพาะสี ไม่แตะ label/data จาก server
        applyGoldPalette(datasets) {
            if (!Array.isArray(datasets)) return datasets;
            datasets.forEach((ds, i) => {
                const color = TP_GOLD_PALETTE[i % TP_GOLD_PALETTE.length];
                ds.borderColor = color;
                ds.backgroundColor = color + '22'; // โปร่งใสเล็กน้อยสำหรับ fill/จุด
                ds.pointBackgroundColor = color;
                ds.tension = ds.tension ?? 0.3;
            });
            return datasets;
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

                // 🆕 (V4) ทาสีทองนวลให้ทุกเส้นก่อนวาด
                this.applyGoldPalette(chartData.datasets);

                // 4) Destroy chart เก่า — รวม "stale instance" ที่ Chart.js attach กับ canvas
                //    เคสที่ทำให้ "เห็นบ้างไม่เห็นบ้าง": new Chart() throws ถ้า canvas ถูกใช้แล้ว
                this.renderDiag = '[step 3/4] cleanup chart เก่า...';
                if (this.chart) { try { this.chart.destroy(); } catch (e) {} this.chart = null; }
                const stale = window.Chart.getChart?.(ctx);
                if (stale) { try { stale.destroy(); } catch (e) {} }

                // สีเส้น grid + ตัวอักษร — อิงธีมนวลทองคำ (อ่าน dark จาก root)
                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(230,179,71,0.14)' : 'rgba(154,143,124,0.18)';
                const textColor = isDark ? '#e8ddc8' : '#5b5142';

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
