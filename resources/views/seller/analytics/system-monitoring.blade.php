@extends('layouts.seller-v4')

{{--
 | ตรวจสอบระบบเซิร์ฟเวอร์แบบเรียลไทม์ — เฉพาะแอดมิน (route อยู่ใต้ middleware role:admin,super_admin — SELLER-24)
 | (2026-09-25) GAP-21: เลิกใช้ grid ของ Bootstrap + <style> 138 บรรทัด + Chart.js CDN
 |   → การ์ด V4 + แท่งกราฟ CSS (.tp-spark) + Alpine poll ทุก 5 วินาที (หยุดเมื่อแท็บไม่ได้เปิดอยู่)
 --}}

@section('title', 'ตรวจสอบระบบแบบเรียลไทม์')

@php
    $initial = [
        'metrics' => $metrics,
        'history' => [],
    ];
    $statusTone = ['good' => 'ok', 'warning' => 'warn', 'critical' => 'bad', 'danger' => 'bad'];
@endphp

@section('content')
<div x-data="sysMonitor(@js($initial), @js(route('seller.analytics.system-monitoring.api-metrics')))" style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="ตรวจสอบระบบแบบเรียลไทม์" icon="🖥️" crumb="เฉพาะผู้ดูแลระบบ · วิเคราะห์"
                         subtitle="ภาระ CPU หน่วยความจำ ดิสก์ และการเชื่อมต่อของเซิร์ฟเวอร์">
        <span class="tp-pill" :style="live ? 'color:var(--tp-ok, #4f9a74); background:color-mix(in srgb, var(--tp-ok, #4f9a74) 16%, transparent);' : 'color:var(--ink2); background:color-mix(in srgb, var(--ink2) 14%, transparent);'">
            <span style="width:7px; height:7px; border-radius:50%; background:currentColor;" :style="{ animation: live ? 'tpPulse 1.4s infinite' : 'none' }"></span>
            <span x-text="live ? 'LIVE · อัปเดตทุก 5 วินาที' : 'หยุดชั่วคราว'"></span>
        </span>
        <button type="button" class="tp-btn tp-btn-sm" @click="toggle()" x-text="live ? '⏸ หยุด' : '▶ เริ่มใหม่'"></button>
    </x-seller-kit.header>

    <div class="tp-card" style="border-left:4px solid var(--tp-warn, #e0a52e); padding:12px 16px; font-size:12.5px; color:var(--ink2);">
        🔒 หน้านี้แสดงข้อมูลเซิร์ฟเวอร์ทั้งระบบ เปิดให้เฉพาะผู้ดูแลระบบเท่านั้น
    </div>

    {{-- การ์ดตัวชี้วัดหลัก --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:14px;">
        <template x-for="card in cards" :key="card.key">
            <div class="tp-card" style="display:flex; flex-direction:column; gap:6px;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">
                    <span style="font-size:12.5px; font-weight:700; color:var(--ink2);" x-text="card.icon + ' ' + card.label"></span>
                    <span class="tp-pill" :style="pillStyle(card.status)" x-text="statusText(card.status)"></span>
                </div>
                <div class="tp-num" style="font-size:30px; font-weight:800;" x-text="card.value"></div>
                <template x-if="card.pct !== null">
                    <div class="tp-inset-sm" style="height:8px; border-radius:99px; overflow:hidden;">
                        <div :style="'height:100%; border-radius:99px; transition:width .5s ease; background:linear-gradient(90deg, var(--accent1), var(--accent2)); width:' + Math.min(100, Math.max(2, card.pct)) + '%'"></div>
                    </div>
                </template>
                <div style="font-size:11.5px; color:var(--ink2);" x-text="card.detail"></div>
            </div>
        </template>
    </div>

    {{-- กราฟย้อนหลัง (แท่ง CSS) --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
        <template x-for="chart in charts" :key="chart.key">
            <div class="tp-card">
                <div class="tp-section-h" x-text="chart.title"></div>
                <div class="tp-spark" style="height:90px; margin-top:12px;">
                    <template x-for="(v, i) in series(chart.key)" :key="i">
                        <i :style="'height:' + barHeight(chart.key, v) + '%; animation:none;'" :title="v"></i>
                    </template>
                </div>
                <div style="font-size:11px; color:var(--ink2); margin-top:6px;" x-text="history.length ? ('ล่าสุด ' + history[history.length - 1].timestamp) : 'กำลังเก็บข้อมูล…'"></div>
            </div>
        </template>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">
        {{-- ฐานข้อมูล + แคช --}}
        <div class="tp-card">
            <div class="tp-section-h">🗄️ ฐานข้อมูลและแคช</div>
            <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:8px; margin-top:12px;">
                @foreach([
                    ['ขนาดฐานข้อมูล', $metrics['database']['size'] ?? '—'],
                    ['จำนวนตาราง', number_format((int) ($metrics['database']['tables'] ?? 0))],
                    ['Cache driver', strtoupper((string) ($metrics['cache']['driver'] ?? '—'))],
                    ['สถานะแคช', strtoupper((string) ($metrics['cache']['status'] ?? '—'))],
                ] as [$dLabel, $dValue])
                    <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px;">
                        <div style="font-size:11px; color:var(--ink2);">{{ $dLabel }}</div>
                        <div class="tp-num" style="font-weight:800; font-size:14px;">{{ $dValue }}</div>
                    </div>
                @endforeach
                @if(isset($metrics['cache']['hit_rate']))
                    <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px;"><div style="font-size:11px; color:var(--ink2);">Cache hit rate</div><div class="tp-num" style="font-weight:800; font-size:14px;">{{ $metrics['cache']['hit_rate'] }}%</div></div>
                @endif
                @if(isset($metrics['database']['queries_per_second']))
                    <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px;"><div style="font-size:11px; color:var(--ink2);">Queries/วินาที</div><div class="tp-num" style="font-weight:800; font-size:14px;">{{ $metrics['database']['queries_per_second'] }}</div></div>
                @endif
            </div>
        </div>

        {{-- แอปพลิเคชัน --}}
        <div class="tp-card">
            <div class="tp-section-h">🚀 ข้อมูลแอปพลิเคชัน</div>
            <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:8px; margin-top:12px;">
                @foreach([
                    ['PHP', $appInfo['php_version'] ?? '—'],
                    ['Laravel', $appInfo['laravel_version'] ?? '—'],
                    ['Environment', strtoupper((string) ($appInfo['environment'] ?? '—'))],
                    ['Debug mode', ! empty($appInfo['debug_mode']) ? 'เปิด ⚠️' : 'ปิด'],
                    ['Session driver', strtoupper((string) ($appInfo['session_driver'] ?? '—'))],
                    ['Queue driver', strtoupper((string) ($appInfo['queue_driver'] ?? '—'))],
                ] as [$aLabel, $aValue])
                    <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px;">
                        <div style="font-size:11px; color:var(--ink2);">{{ $aLabel }}</div>
                        <div class="tp-num" style="font-weight:800; font-size:14px;">{{ $aValue }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @if(! empty($network))
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div class="tp-section-h" style="padding:16px 18px;">🌐 เครือข่าย</div>
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:480px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            @foreach(['Interface', 'รับ', 'ส่ง', 'รวม'] as $h)
                                <th style="padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase;">{{ $h }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($network as $iface)
                            <tr style="border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                                <td style="padding:11px 14px; font-weight:700;">{{ $iface['interface'] }}</td>
                                <td style="padding:11px 14px;" class="tp-num">{{ $iface['received'] }}</td>
                                <td style="padding:11px 14px;" class="tp-num">{{ $iface['transmitted'] }}</td>
                                <td style="padding:11px 14px;" class="tp-num">{{ $iface['total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // ตรวจสอบระบบแบบเรียลไทม์ — poll ทุก 5 วินาที และหยุดเมื่อแท็บถูกซ่อน (กันเขียน history ถี่เกินไป)
    function sysMonitor(initial, url) {
        return {
            metrics: initial.metrics || {},
            history: initial.history || [],
            live: true,
            timer: null,
            busy: false,
            charts: [
                { key: 'cpu', title: '📈 CPU (%)' },
                { key: 'memory', title: '💾 หน่วยความจำ (%)' },
                { key: 'connections', title: '🔌 การเชื่อมต่อ' },
            ],
            get cards() {
                const m = this.metrics || {};
                const cpu = m.cpu || {}, mem = m.memory || {}, disk = m.disk || {}, con = m.connections || {};
                return [
                    { key: 'cpu', icon: '⚡', label: 'CPU', value: Number(cpu.percentage || 0).toFixed(1) + '%', pct: Number(cpu.percentage || 0), status: cpu.status || 'good', detail: 'Load ' + (cpu.load_1min ?? '-') + ' · ' + (cpu.cpu_cores ?? '-') + ' cores' },
                    { key: 'mem', icon: '💾', label: 'หน่วยความจำ', value: Number(mem.percentage || 0).toFixed(1) + '%', pct: Number(mem.percentage || 0), status: mem.status || 'good', detail: 'ใช้ ' + (mem.used ?? '-') + ' / ' + (mem.total ?? '-') },
                    { key: 'disk', icon: '💿', label: 'ดิสก์', value: Number(disk.percentage || 0).toFixed(1) + '%', pct: Number(disk.percentage || 0), status: disk.status || 'good', detail: 'ใช้ ' + (disk.used ?? '-') + ' / ' + (disk.total ?? '-') },
                    { key: 'con', icon: '🔌', label: 'การเชื่อมต่อ', value: String(con.total ?? 0), pct: null, status: 'good', detail: 'Session ' + (con.sessions ?? 0) + ' · DB ' + (con.database ?? 0) },
                ];
            },
            init() {
                this.poll();
                this.start();
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden) { this.stop(); } else if (this.live) { this.poll(); this.start(); }
                });
            },
            start() { this.stop(); this.timer = setInterval(() => this.poll(), 5000); },
            stop() { if (this.timer) { clearInterval(this.timer); this.timer = null; } },
            toggle() { this.live = !this.live; if (this.live) { this.poll(); this.start(); } else { this.stop(); } },
            async poll() {
                if (this.busy) return;
                this.busy = true;
                try {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data && data.success) {
                        this.metrics = data.metrics || this.metrics;
                        this.history = Array.isArray(data.history) ? data.history.slice(-30) : this.history;
                    }
                } catch (e) {
                    // เงียบไว้ รอบถัดไปจะลองใหม่เอง
                } finally {
                    this.busy = false;
                }
            },
            series(key) { return this.history.map((h) => Number(h[key] || 0)); },
            barHeight(key, v) {
                const max = key === 'connections' ? Math.max(1, ...this.series(key)) : 100;
                return Math.max(3, Math.min(100, (v / max) * 100));
            },
            statusText(s) { return { good: 'ปกติ', warning: 'เฝ้าระวัง', critical: 'วิกฤต', danger: 'อันตราย' }[s] || s; },
            pillStyle(s) {
                const c = { good: 'var(--tp-ok, #4f9a74)', warning: 'var(--tp-warn, #c98a1b)', critical: 'var(--tp-bad, #d9534f)', danger: 'var(--tp-bad, #d9534f)' }[s] || 'var(--ink2)';
                return 'color:' + c + '; background:color-mix(in srgb, ' + c + ' 16%, transparent);';
            },
        };
    }
</script>
@endpush
