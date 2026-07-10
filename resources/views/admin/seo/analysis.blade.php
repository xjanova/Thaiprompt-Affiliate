@extends('layouts.admin-v4')

@section('title', 'วิเคราะห์ SEO')

@php
    // ── เตรียมข้อมูลสำหรับกราฟ (คำนวณเปอร์เซ็นต์ความสมบูรณ์) ──
    $t = max(1, $analysis['total']);
    $compLabels = ['Meta Title', 'Description', 'OG Image', 'Twitter', 'Canonical', 'JSON-LD'];
    $compData = [
        (int) round($analysis['completeness']['meta_title'] / $t * 100),
        (int) round($analysis['completeness']['meta_description'] / $t * 100),
        (int) round($analysis['completeness']['og_image'] / $t * 100),
        (int) round($analysis['completeness']['twitter'] / $t * 100),
        (int) round($analysis['completeness']['canonical_url'] / $t * 100),
        (int) round($analysis['completeness']['structured_data'] / $t * 100),
    ];
    $langLabels = array_map(fn ($k) => strtoupper($k), array_keys($analysis['by_language']));
    $langData = array_values($analysis['by_language']);
    $scoreColor = $analysis['score'] >= 80 ? '#5aa07e' : ($analysis['score'] >= 50 ? '#e6b347' : '#d9534f');
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Header ── --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.seo.index') }}" class="tp-icon-btn" title="กลับ"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div class="tp-muted" style="font-size:.72rem; letter-spacing:.08em; text-transform:uppercase; margin-bottom:4px;">
                    หลังบ้าน · SEO · วิเคราะห์
                </div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; color:var(--ink); margin:0; display:flex; align-items:center; gap:10px;">
                    <i class="fas fa-chart-pie" style="color:var(--accent1);"></i> วิเคราะห์ SEO
                </h1>
            </div>
        </div>
        <a href="{{ route('admin.seo.create') }}" class="tp-btn tp-btn-primary"><i class="fas fa-plus"></i> เพิ่ม SEO Meta</a>
    </div>

    @if($analysis['total'] === 0)
        {{-- ── Empty state ── --}}
        <div class="tp-card" style="text-align:center; padding:48px 18px;">
            <i class="fas fa-chart-line" style="font-size:30px; color:var(--ink2); opacity:.4; display:block; margin-bottom:12px;"></i>
            <div style="font-size:15px; font-weight:700; color:var(--ink);">ยังไม่มีข้อมูล SEO ให้วิเคราะห์</div>
            <p class="tp-muted" style="font-size:13px; margin:8px 0 16px;">เพิ่ม SEO Meta อย่างน้อย 1 หน้าเพื่อเริ่มดูสถิติและกราฟ</p>
            <a href="{{ route('admin.seo.create') }}" class="tp-btn tp-btn-primary"><i class="fas fa-plus"></i> เพิ่ม SEO Meta แรก</a>
        </div>
    @else
    <div x-data="seoAnalysis()" x-init="init()" style="display:flex; flex-direction:column; gap:18px;">

        {{-- ── แถวบน: คะแนนสุขภาพ + KPI ── --}}
        <div style="display:grid; grid-template-columns:minmax(240px,1fr) 2fr; gap:16px; align-items:stretch;">
            {{-- Health score gauge (CSS conic) --}}
            <div class="tp-card" style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; text-align:center;">
                <div class="tp-section-h" style="font-size:13px; font-weight:700; color:var(--ink2); align-self:flex-start;">คะแนนสุขภาพ SEO</div>
                <div style="position:relative; width:150px; height:150px; border-radius:50%; background:conic-gradient({{ $scoreColor }} {{ $analysis['score'] * 3.6 }}deg, var(--sd) 0deg); box-shadow:var(--inset); display:flex; align-items:center; justify-content:center;">
                    <div style="width:112px; height:112px; border-radius:50%; background:var(--surf); box-shadow:var(--raise); display:flex; flex-direction:column; align-items:center; justify-content:center;">
                        <span class="tp-num" style="font-size:36px; font-weight:800; line-height:1; color:{{ $scoreColor }};">{{ $analysis['score'] }}</span>
                        <span class="tp-muted" style="font-size:11px;">/ 100</span>
                    </div>
                </div>
                <span class="tp-pill" style="font-size:11.5px; padding:3px 12px; background:{{ $scoreColor }}22; color:{{ $scoreColor }};">
                    {{ $analysis['score'] >= 80 ? 'ดีเยี่ยม' : ($analysis['score'] >= 50 ? 'ปานกลาง — ปรับปรุงได้' : 'ต้องปรับปรุง') }}
                </span>
            </div>

            {{-- KPI grid --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px;">
                <div class="tp-card" style="display:flex; flex-direction:column; justify-content:center; gap:4px;">
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--ink);">{{ $analysis['total'] }}</div>
                    <div class="tp-muted" style="font-size:12px;">หน้าที่ตั้งค่า SEO</div>
                </div>
                <div class="tp-card" style="display:flex; flex-direction:column; justify-content:center; gap:4px;">
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:#5aa07e;">{{ $analysis['indexed'] }}</div>
                    <div class="tp-muted" style="font-size:12px;">อนุญาต index</div>
                </div>
                <div class="tp-card" style="display:flex; flex-direction:column; justify-content:center; gap:4px;">
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:#5689b8;">{{ $analysis['structured_pct'] }}%</div>
                    <div class="tp-muted" style="font-size:12px;">มี Structured Data</div>
                </div>
                <div class="tp-card" style="display:flex; flex-direction:column; justify-content:center; gap:4px;">
                    <div class="tp-num" style="font-size:26px; font-weight:800; color:var(--ink);">{{ $analysis['configured_count'] }}/{{ $analysis['page_type_total'] }}</div>
                    <div class="tp-muted" style="font-size:12px;">หน้าสำคัญที่ครอบคลุม</div>
                </div>
            </div>
        </div>

        {{-- ── แถวกราฟ ── --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:16px;">
            {{-- Doughnut: index status --}}
            <div class="tp-card">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:12px;">
                    <i class="fas fa-eye" style="color:var(--accent1);"></i> สถานะการ Index
                </div>
                <div style="position:relative; height:240px;"><canvas id="chartIndex"></canvas></div>
            </div>
            {{-- Doughnut: by language --}}
            <div class="tp-card">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:12px;">
                    <i class="fas fa-language" style="color:var(--accent1);"></i> จำแนกตามภาษา
                </div>
                <div style="position:relative; height:240px;"><canvas id="chartLang"></canvas></div>
            </div>
        </div>

        {{-- Bar: completeness --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700; margin-bottom:12px;">
                <i class="fas fa-list-check" style="color:var(--accent1);"></i> ความสมบูรณ์ของแต่ละองค์ประกอบ (% ของหน้าที่กรอกครบ)
            </div>
            <div style="position:relative; height:300px;"><canvas id="chartComp"></canvas></div>
        </div>

        {{-- ── ตารางความครอบคลุมหน้าสำคัญ ── --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="padding:16px 18px; box-shadow:var(--inset-sm);">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:9px; font-size:14px; font-weight:700;">
                    <i class="fas fa-table-cells" style="color:var(--accent1);"></i> ความครอบคลุมหน้าสำคัญ
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table style="min-width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left;">
                            <th style="padding:11px 18px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">หน้า</th>
                            <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:center;">ไทย</th>
                            <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:center;">English</th>
                            <th style="padding:11px 16px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:center;">Index</th>
                            <th style="padding:11px 18px; font-size:11px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($analysis['coverage'] as $c)
                            <tr style="box-shadow:var(--inset-sm);">
                                <td style="padding:12px 18px; font-size:13px; color:var(--ink);">
                                    <span style="font-weight:700;">{{ $c['label'] }}</span>
                                    <span class="tp-muted" style="font-size:11px;"> · {{ $c['page_type'] }}</span>
                                </td>
                                <td style="padding:12px 16px; text-align:center;">
                                    @if($c['has_th'])<i class="fas fa-circle-check" style="color:#5aa07e;"></i>@else<i class="fas fa-circle-xmark" style="color:var(--ink2); opacity:.4;"></i>@endif
                                </td>
                                <td style="padding:12px 16px; text-align:center;">
                                    @if($c['has_en'])<i class="fas fa-circle-check" style="color:#5aa07e;"></i>@else<i class="fas fa-circle-xmark" style="color:var(--ink2); opacity:.4;"></i>@endif
                                </td>
                                <td style="padding:12px 16px; text-align:center;">
                                    @if($c['indexed'] === true)
                                        <span class="tp-pill" style="font-size:10.5px; padding:2px 8px; background:rgba(90,160,126,.16); color:#4f9e7e;">index</span>
                                    @elseif($c['indexed'] === false)
                                        <span class="tp-pill" style="font-size:10.5px; padding:2px 8px; background:rgba(217,83,79,.14); color:#d9534f;">noindex</span>
                                    @else
                                        <span class="tp-muted" style="font-size:11px;">—</span>
                                    @endif
                                </td>
                                <td style="padding:12px 18px; text-align:right;">
                                    @if($c['configured'])
                                        <span class="tp-pill tp-pill-soft" style="font-size:10.5px; padding:2px 9px;">ตั้งค่าแล้ว</span>
                                    @else
                                        <a href="{{ route('admin.seo.create') }}" class="tp-btn tp-btn-sm"><i class="fas fa-plus"></i> เพิ่ม</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@if($analysis['total'] > 0)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function seoAnalysis() {
    const GOLD = ['#e6b347', '#d98e3f', '#5689b8', '#b79ae8', '#5aa07e', '#e0a52e'];
    return {
        async init() {
            // รอ Chart.js (กัน CDN ช้า / re-skin ธีมนวลทองคำ)
            let waited = 0;
            while (typeof window.Chart === 'undefined' && waited < 12000) {
                await new Promise(r => setTimeout(r, 100)); waited += 100;
            }
            if (typeof window.Chart === 'undefined') return;

            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#e8ddc8' : '#5b5142';
            const gridColor = isDark ? 'rgba(230,179,71,0.14)' : 'rgba(154,143,124,0.18)';
            Chart.defaults.font.family = 'Anuphan, sans-serif';
            Chart.defaults.color = textColor;

            // 1) Doughnut — สถานะ index
            new Chart(document.getElementById('chartIndex'), {
                type: 'doughnut',
                data: {
                    labels: ['Indexed', 'Noindex'],
                    datasets: [{ data: [{{ $analysis['indexed'] }}, {{ $analysis['noindex'] }}], backgroundColor: ['#5aa07e', '#d9534f'], borderWidth: 0 }],
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
                    plugins: { legend: { position: 'bottom', labels: { padding: 14, font: { size: 12 } } } } },
            });

            // 2) Doughnut — ตามภาษา
            new Chart(document.getElementById('chartLang'), {
                type: 'doughnut',
                data: {
                    labels: @js($langLabels),
                    datasets: [{ data: @js($langData), backgroundColor: GOLD, borderWidth: 0 }],
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
                    plugins: { legend: { position: 'bottom', labels: { padding: 14, font: { size: 12 } } } } },
            });

            // 3) Bar — ความสมบูรณ์แต่ละองค์ประกอบ (%)
            new Chart(document.getElementById('chartComp'), {
                type: 'bar',
                data: {
                    labels: @js($compLabels),
                    datasets: [{
                        label: '% ของหน้าที่กรอกครบ',
                        data: @js($compData),
                        backgroundColor: @js($compData).map(v => v >= 80 ? '#5aa07e' : (v >= 40 ? '#e6b347' : '#d9534f')),
                        borderRadius: 8, borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => c.parsed.y + '%' } } },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: textColor, font: { size: 12 } } },
                        y: { beginAtZero: true, max: 100, grid: { color: gridColor }, ticks: { color: textColor, callback: v => v + '%' } },
                    },
                },
            });
        },
    };
}
</script>
@endpush
@endif
