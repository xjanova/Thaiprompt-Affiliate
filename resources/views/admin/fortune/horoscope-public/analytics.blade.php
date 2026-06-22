{{-- สถิติระบบดูดวงสาธารณะ — ธีม V4 "นวลทองคำ" --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
@php
    // ป้ายชื่อประเภทเลขศาสตร์ (ใช้ในลิสต์ประเภทเลขศาสตร์)
    $typeLabels = [
        'name' => '📝 วิเคราะห์ชื่อ',
        'phone' => '📱 วิเคราะห์เบอร์โทร',
        'license_plate' => '🚗 ทะเบียนรถ',
        'id_card' => '🪪 เลขบัตรประชาชน',
        'birthday' => '🎂 วันเกิด',
    ];
    // หาค่าสูงสุดของลิสต์ราศี/สัญลักษณ์ฝัน เพื่อทำแถบสัดส่วน
    $maxZodiacViews = $topZodiacs->max('total_views') ?: 1;
    $maxSymbolCount = $topDreamSymbols->max('search_count') ?: 1;
@endphp

<div class="tp-page" x-data="horoscopeAnalytics()"
     style="display:flex; flex-direction:column; gap:18px;">

    {{-- ==================== Header ==================== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div class="tp-muted" style="font-size:12.5px; letter-spacing:.04em; margin-bottom:4px;">
                หลังบ้าน · ระบบดูดวง · สถิติสาธารณะ
            </div>
            <h1 class="tp-num" style="font-size:26px; font-weight:800; color:var(--ink); display:flex; align-items:center; gap:8px;">
                <i class="fas fa-chart-line" style="color:var(--accent1);"></i>
                สถิติระบบดูดวงสาธารณะ
            </h1>
            <div class="tp-muted" style="font-size:13px; margin-top:2px;">
                ภาพรวมการใช้งานระบบดูดวง Frontend
            </div>
        </div>
        <div style="display:flex; gap:8px;">
            @if(Route::has('admin.fortune.horoscope-public.settings'))
                <a href="{{ route('admin.fortune.horoscope-public.settings') }}" class="tp-btn">
                    <i class="fas fa-gear"></i> ตั้งค่า
                </a>
            @endif
        </div>
    </div>

    {{-- ==================== KPI — ดวงรายวัน ==================== --}}
    <div>
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
            <i class="fas fa-sun" style="color:#e0a52e;"></i> ดวงรายวัน
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr)); gap:14px;">
            {{-- Predictions ทั้งหมด --}}
            <div class="tp-card tp-raise">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <span class="tp-muted" style="font-size:12.5px;">Predictions ทั้งหมด</span>
                    <i class="fas fa-scroll" style="color:var(--accent1); font-size:18px;"></i>
                </div>
                <div class="tp-num" style="font-size:28px; font-weight:800; color:var(--ink); margin-top:8px;">
                    {{ number_format($dailyStats['total_predictions']) }}
                </div>
            </div>
            {{-- วันนี้ --}}
            <div class="tp-card tp-raise">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <span class="tp-muted" style="font-size:12.5px;">สร้างวันนี้</span>
                    <i class="fas fa-calendar-day" style="color:#5689b8; font-size:18px;"></i>
                </div>
                <div class="tp-num" style="font-size:28px; font-weight:800; color:var(--ink); margin-top:8px;">
                    {{ number_format($dailyStats['generated_today']) }}
                </div>
            </div>
            {{-- Views รวม --}}
            <div class="tp-card tp-raise">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <span class="tp-muted" style="font-size:12.5px;">Views รวม</span>
                    <i class="fas fa-eye" style="color:#5aa07e; font-size:18px;"></i>
                </div>
                <div class="tp-num" style="font-size:28px; font-weight:800; color:var(--ink); margin-top:8px;">
                    {{ number_format($dailyStats['total_views']) }}
                </div>
            </div>
            {{-- ราศีเปิดใช้ --}}
            <div class="tp-card tp-raise">
                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <span class="tp-muted" style="font-size:12.5px;">ราศีเปิดใช้</span>
                    <i class="fas fa-circle-check" style="color:#5aa07e; font-size:18px;"></i>
                </div>
                <div class="tp-num" style="font-size:28px; font-weight:800; color:var(--ink); margin-top:8px;">
                    {{ $dailyStats['zodiacs_active'] }}<span class="tp-muted" style="font-size:16px; font-weight:600;">/12</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ==================== KPI — ทำนายฝัน ==================== --}}
    <div>
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
            <i class="fas fa-moon" style="color:#b79ae8;"></i> ทำนายฝัน
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px,1fr)); gap:14px;">
            <div class="tp-card">
                <span class="tp-muted" style="font-size:12px;">ทั้งหมด</span>
                <div class="tp-num" style="font-size:23px; font-weight:800; color:var(--ink); margin-top:6px;">{{ number_format($dreamStats['total_readings']) }}</div>
            </div>
            <div class="tp-card">
                <span class="tp-muted" style="font-size:12px;">วันนี้</span>
                <div class="tp-num" style="font-size:23px; font-weight:800; color:var(--ink); margin-top:6px;">{{ number_format($dreamStats['today_readings']) }}</div>
            </div>
            <div class="tp-card">
                <span class="tp-muted" style="font-size:12px;">สัปดาห์นี้</span>
                <div class="tp-num" style="font-size:23px; font-weight:800; color:var(--ink); margin-top:6px;">{{ number_format($dreamStats['week_readings']) }}</div>
            </div>
            <div class="tp-card">
                <span class="tp-muted" style="font-size:12px;">Views รวม</span>
                <div class="tp-num" style="font-size:23px; font-weight:800; color:var(--ink); margin-top:6px;">{{ number_format($dreamStats['total_views']) }}</div>
            </div>
            <div class="tp-card">
                <span class="tp-muted" style="font-size:12px;">สัญลักษณ์</span>
                <div class="tp-num" style="font-size:23px; font-weight:800; color:var(--ink); margin-top:6px;">{{ number_format($dreamStats['total_symbols']) }}</div>
            </div>
        </div>
    </div>

    {{-- ==================== KPI — เลขศาสตร์ ==================== --}}
    <div>
        <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
            <i class="fas fa-hashtag" style="color:#d6824a;"></i> เลขศาสตร์
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px,1fr)); gap:14px;">
            <div class="tp-card">
                <span class="tp-muted" style="font-size:12px;">ทั้งหมด</span>
                <div class="tp-num" style="font-size:23px; font-weight:800; color:var(--ink); margin-top:6px;">{{ number_format($numerologyStats['total_readings']) }}</div>
            </div>
            <div class="tp-card">
                <span class="tp-muted" style="font-size:12px;">วันนี้</span>
                <div class="tp-num" style="font-size:23px; font-weight:800; color:var(--ink); margin-top:6px;">{{ number_format($numerologyStats['today_readings']) }}</div>
            </div>
            <div class="tp-card">
                <span class="tp-muted" style="font-size:12px;">สัปดาห์นี้</span>
                <div class="tp-num" style="font-size:23px; font-weight:800; color:var(--ink); margin-top:6px;">{{ number_format($numerologyStats['week_readings']) }}</div>
            </div>
            <div class="tp-card">
                <span class="tp-muted" style="font-size:12px;">Views รวม</span>
                <div class="tp-num" style="font-size:23px; font-weight:800; color:var(--ink); margin-top:6px;">{{ number_format($numerologyStats['total_views']) }}</div>
            </div>
        </div>
    </div>

    {{-- ==================== กราฟการใช้งาน (CSS bars) ==================== --}}
    <div class="tp-card">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px;">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:8px;">
                <i class="fas fa-chart-column" style="color:var(--accent1);"></i>
                กราฟการใช้งาน <span x-text="chartDays"></span> วัน
            </div>
            <div style="display:flex; gap:8px;">
                <button type="button" class="tp-btn tp-btn-sm" @click="loadChart(7)"
                        :class="chartDays === 7 ? 'tp-btn-primary' : ''">7 วัน</button>
                <button type="button" class="tp-btn tp-btn-sm" @click="loadChart(30)"
                        :class="chartDays === 30 ? 'tp-btn-primary' : ''">30 วัน</button>
            </div>
        </div>

        {{-- Legend --}}
        <div style="display:flex; flex-wrap:wrap; gap:14px; margin-bottom:12px;">
            <span style="display:flex; align-items:center; gap:6px; font-size:12.5px; color:var(--ink2);">
                <i style="width:11px; height:11px; border-radius:3px; background:#b79ae8;"></i> 💭 ทำนายฝัน
            </span>
            <span style="display:flex; align-items:center; gap:6px; font-size:12.5px; color:var(--ink2);">
                <i style="width:11px; height:11px; border-radius:3px; background:#d6824a;"></i> 🔢 เลขศาสตร์
            </span>
            <span style="display:flex; align-items:center; gap:6px; font-size:12.5px; color:var(--ink2);">
                <i style="width:11px; height:11px; border-radius:3px; background:#5689b8;"></i> ⭐ ดวงรายวัน (views)
            </span>
        </div>

        {{-- แท่งกราฟ CSS — Alpine render --}}
        <div class="tp-inset" style="padding:16px 12px; border-radius:14px; overflow-x:auto;">
            <div class="tp-bars" style="min-width:520px;">
                <template x-for="(col, i) in columns" :key="i">
                    <div class="col">
                        <div class="stack" style="align-items:flex-end;">
                            <div class="bar" :style="`height:${col.h0}%; background:linear-gradient(180deg,#b79ae8,#7d5fb5);`"
                                 :title="`💭 ทำนายฝัน: ${col.v0}`"></div>
                            <div class="bar" :style="`height:${col.h1}%; background:linear-gradient(180deg,#d6824a,#a85b2c);`"
                                 :title="`🔢 เลขศาสตร์: ${col.v1}`"></div>
                            <div class="bar" :style="`height:${col.h2}%; background:linear-gradient(180deg,#5689b8,#3a6488);`"
                                 :title="`⭐ ดวงรายวัน (views): ${col.v2}`"></div>
                        </div>
                        <span class="lbl" x-text="col.label"></span>
                    </div>
                </template>
            </div>
            <div x-show="columns.length === 0" class="tp-muted" style="text-align:center; padding:30px 0;">
                <i class="fas fa-inbox" style="font-size:26px; opacity:.5;"></i>
                <div style="margin-top:8px;">ยังไม่มีข้อมูลกราฟ</div>
            </div>
        </div>
    </div>

    {{-- ==================== Top lists ==================== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px,1fr)); gap:18px;">

        {{-- ราศียอดนิยม --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
                <i class="fas fa-star" style="color:#e0a52e;"></i> ราศียอดนิยม (7 วัน)
            </div>
            @if($topZodiacs->isNotEmpty())
                <div style="display:flex; flex-direction:column; gap:12px;">
                    @foreach($topZodiacs as $idx => $item)
                        @php $zpct = ($item->total_views / $maxZodiacViews) * 100; @endphp
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-num" style="font-size:13px; font-weight:800; color:var(--accent1); width:20px; text-align:center;">{{ $idx + 1 }}</span>
                            <span style="font-size:19px;">{{ $item->zodiacSign?->symbol_emoji ?? '⭐' }}</span>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:13.5px; font-weight:600; color:var(--ink); margin-bottom:5px;">
                                    {{ $item->zodiacSign?->name_th ?? 'ไม่ระบุ' }}
                                </div>
                                <div class="tp-inset-sm" style="height:7px; border-radius:99px; overflow:hidden;">
                                    <div style="height:100%; width:{{ $zpct }}%; border-radius:99px; background:linear-gradient(90deg,var(--accent1),var(--accent2));"></div>
                                </div>
                            </div>
                            <span class="tp-num tp-muted" style="font-size:12.5px; white-space:nowrap;">{{ number_format($item->total_views) }} views</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="tp-muted" style="text-align:center; padding:30px 0;">
                    <i class="fas fa-inbox" style="font-size:26px; opacity:.5;"></i>
                    <div style="margin-top:8px;">ยังไม่มีข้อมูล</div>
                </div>
            @endif
        </div>

        {{-- สัญลักษณ์ฝันยอดนิยม --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
                <i class="fas fa-moon" style="color:#b79ae8;"></i> สัญลักษณ์ฝันยอดนิยม
            </div>
            @if($topDreamSymbols->isNotEmpty())
                <div style="display:flex; flex-direction:column; gap:12px;">
                    @foreach($topDreamSymbols as $idx => $symbol)
                        @php $spct = ($symbol->search_count / $maxSymbolCount) * 100; @endphp
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="tp-num" style="font-size:13px; font-weight:800; color:#b79ae8; width:20px; text-align:center;">{{ $idx + 1 }}</span>
                            <span style="font-size:19px;">{{ $symbol->category?->icon ?? '💭' }}</span>
                            <div style="flex:1; min-width:0;">
                                <div style="font-size:13.5px; font-weight:600; color:var(--ink); margin-bottom:5px;">
                                    ฝันเห็น{{ $symbol->keyword_th }}
                                </div>
                                <div class="tp-inset-sm" style="height:7px; border-radius:99px; overflow:hidden;">
                                    <div style="height:100%; width:{{ $spct }}%; border-radius:99px; background:linear-gradient(90deg,#b79ae8,#7d5fb5);"></div>
                                </div>
                            </div>
                            <span class="tp-num tp-muted" style="font-size:12.5px; white-space:nowrap;">{{ number_format($symbol->search_count) }} ครั้ง</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="tp-muted" style="text-align:center; padding:30px 0;">
                    <i class="fas fa-inbox" style="font-size:26px; opacity:.5;"></i>
                    <div style="margin-top:8px;">ยังไม่มีข้อมูล</div>
                </div>
            @endif
        </div>

        {{-- ประเภทเลขศาสตร์ --}}
        @if($numerologyTypes->isNotEmpty())
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
                <i class="fas fa-hashtag" style="color:#d6824a;"></i> ประเภทเลขศาสตร์
            </div>
            <div style="display:flex; flex-direction:column; gap:12px;">
                @foreach($numerologyTypes as $type)
                    @php $tpct = $numerologyStats['total_readings'] > 0 ? ($type->count / $numerologyStats['total_readings']) * 100 : 0; @endphp
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="flex:1; min-width:0; font-size:13.5px; font-weight:600; color:var(--ink);">
                            {{ $typeLabels[$type->reading_type] ?? $type->reading_type }}
                        </span>
                        <div style="width:130px; flex-shrink:0;">
                            <div class="tp-inset-sm" style="height:7px; border-radius:99px; overflow:hidden;">
                                <div style="height:100%; width:{{ $tpct }}%; border-radius:99px; background:linear-gradient(90deg,#d6824a,#a85b2c);"></div>
                            </div>
                        </div>
                        <span class="tp-num tp-muted" style="font-size:12.5px; width:44px; text-align:right;">{{ number_format($type->count) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
{{-- scripts stack: Alpine component สำหรับกราฟ CSS + AJAX โหลดข้อมูล --}}
<script>
function horoscopeAnalytics() {
    return {
        chartDays: 7,
        columns: [],

        init() {
            // วาดกราฟครั้งแรกจากข้อมูลที่ controller ส่งมา
            this.buildColumns(@json($chartData));
        },

        /**
         * โหลดข้อมูลกราฟจาก API (endpoint + payload เดิม)
         */
        async loadChart(days) {
            this.chartDays = days;
            try {
                const res = await fetch(`{{ route('admin.fortune.horoscope-public.analytics.data') }}?days=${days}`);
                const json = await res.json();
                if (json.success) {
                    this.buildColumns(json.data);
                }
            } catch (e) {
                console.error('โหลดข้อมูลกราฟผิดพลาด:', e);
                this.$dispatch('notify', { type: 'error', message: 'โหลดข้อมูลกราฟผิดพลาด' });
            }
        },

        /**
         * แปลงข้อมูล datasets → คอลัมน์แท่งกราฟ CSS (สเกลตามค่าสูงสุดทั้งกราฟ)
         */
        buildColumns(data) {
            const labels = (data && data.labels) ? data.labels : [];
            const ds = (data && data.datasets) ? data.datasets : [];
            const d0 = (ds[0] && ds[0].data) ? ds[0].data : []; // ทำนายฝัน
            const d1 = (ds[1] && ds[1].data) ? ds[1].data : []; // เลขศาสตร์
            const d2 = (ds[2] && ds[2].data) ? ds[2].data : []; // ดวงรายวัน (views)

            // หาค่าสูงสุดเพื่อ normalize ความสูงเป็นเปอร์เซ็นต์
            let max = 0;
            for (let i = 0; i < labels.length; i++) {
                max = Math.max(max, d0[i] || 0, d1[i] || 0, d2[i] || 0);
            }
            if (max <= 0) max = 1;

            const cols = [];
            for (let i = 0; i < labels.length; i++) {
                const v0 = d0[i] || 0;
                const v1 = d1[i] || 0;
                const v2 = d2[i] || 0;
                cols.push({
                    label: labels[i],
                    v0: v0, v1: v1, v2: v2,
                    // ขั้นต่ำ 2% ให้เห็นแท่งแม้ค่าเป็น 0
                    h0: Math.max(v0 / max * 100, v0 > 0 ? 4 : 1),
                    h1: Math.max(v1 / max * 100, v1 > 0 ? 4 : 1),
                    h2: Math.max(v2 / max * 100, v2 > 0 ? 4 : 1),
                });
            }
            this.columns = cols;
        },
    };
}
</script>
@endpush
