{{--
 | สถิติงานไรเดอร์ (admin.rider-jobs.statistics) — ธีม V4 (กราฟแท่ง CSS .tp-bars ไม่ต้องโหลด Chart.js)
 | ตัวแปรจาก Admin\RiderJobController@statistics:
 |   $stats (แถว date|month, total, completed, cancelled, failed, revenue, rider_earnings, platform_fee), $topRiders (completed_jobs_count),
 |   $period daily|monthly, $startDate, $endDate, $pageTitle
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'สถิติงานไรเดอร์')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $labelKey = $period === 'monthly' ? 'month' : 'date';
    $rows = $stats->map(fn ($row) => [
        'label' => (string) ($row->{$labelKey} ?? ''),
        'total' => (int) $row->total,
        'completed' => (int) $row->completed,
        'cancelled' => (int) $row->cancelled,
        'failed' => (int) $row->failed,
        'revenue' => round((float) $row->revenue, 2),
        'rider_earnings' => round((float) $row->rider_earnings, 2),
        'platform_fee' => round((float) $row->platform_fee, 2),
    ])->values();
    $totalJobs = $rows->sum('total');
    $totalCompleted = $rows->sum('completed');
    $totalLost = $rows->sum('cancelled') + $rows->sum('failed');
    $totalRevenue = $rows->sum('revenue');
    $totalRider = $rows->sum('rider_earnings');
    $totalPlatform = $rows->sum('platform_fee');
    $maxJobs = max(1, (int) $rows->max(fn ($r) => max($r['completed'], $r['cancelled'] + $r['failed'])));
    $maxMoney = max(1, (float) $rows->max('revenue'));
    $shortLabel = function (string $label) use ($period) {
        try {
            return $period === 'monthly'
                ? \Illuminate\Support\Carbon::createFromFormat('Y-m', $label)->thaidate('M y')
                : \Illuminate\Support\Carbon::parse($label)->format('d/m');
        } catch (\Throwable) {
            return $label;
        }
    };
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:14px;">
            <a href="{{ route('admin.rider-jobs.index') }}" class="tp-icon-btn" title="กลับหน้ารายการงาน"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · งานไรเดอร์ · สถิติ</div>
                <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">สถิติงานไรเดอร์ 📈</h1>
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">{{ \Illuminate\Support\Carbon::parse($startDate)->thaidate('j M Y') }} – {{ \Illuminate\Support\Carbon::parse($endDate)->thaidate('j M Y') }} · {{ $period === 'monthly' ? 'รายเดือน' : 'รายวัน' }}</div>
            </div>
        </div>
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <form method="GET" action="{{ route('admin.rider-jobs.statistics') }}" class="tp-card"
          style="padding:16px; display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,160px),1fr)); gap:12px; align-items:end;">
        <label style="display:block;">
            <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">แสดงแบบ</span>
            <select name="period" class="tp-input">
                <option value="daily" @selected($period === 'daily')>รายวัน</option>
                <option value="monthly" @selected($period === 'monthly')>รายเดือน</option>
            </select>
        </label>
        <label style="display:block;">
            <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ตั้งแต่วันที่</span>
            <input type="date" name="start_date" value="{{ $startDate }}" class="tp-input">
        </label>
        <label style="display:block;">
            <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ถึงวันที่</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="tp-input">
        </label>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-filter"></i> แสดงผล</button>
            <a href="{{ route('admin.rider-jobs.statistics', ['start_date' => now()->subDays(6)->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="tp-btn">7 วัน</a>
            <a href="{{ route('admin.rider-jobs.statistics', ['period' => 'monthly', 'start_date' => now()->subMonths(11)->startOfMonth()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="tp-btn">12 เดือน</a>
        </div>
    </form>

    {{-- ===== ตัวเลขสรุป ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:14px;">
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-box', 'kpiValue' => number_format($totalJobs), 'kpiLabel' => 'งานทั้งหมด', 'kpiTone' => null, 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-circle-check', 'kpiValue' => number_format($totalCompleted), 'kpiLabel' => 'สำเร็จ', 'kpiTone' => 'ok', 'kpiHref' => null, 'kpiHint' => $totalJobs > 0 ? 'อัตราสำเร็จ '.number_format($totalCompleted / $totalJobs * 100, 1).'%' : null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-ban', 'kpiValue' => number_format($totalLost), 'kpiLabel' => 'ยกเลิก / ส่งไม่สำเร็จ', 'kpiTone' => 'bad', 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-coins', 'kpiValue' => '฿'.number_format($totalRevenue, 2), 'kpiLabel' => 'ค่าส่งรวม (งานสำเร็จ)', 'kpiTone' => 'info', 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-motorcycle', 'kpiValue' => '฿'.number_format($totalRider, 2), 'kpiLabel' => 'จ่ายไรเดอร์', 'kpiTone' => 'ok', 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-building', 'kpiValue' => '฿'.number_format($totalPlatform, 2), 'kpiLabel' => 'แพลตฟอร์มได้', 'kpiTone' => null, 'kpiHref' => null, 'kpiHint' => null, 'kpiPulse' => false])
    </div>

    @if ($rows->isEmpty())
        <div class="tp-card" style="padding:40px 20px; text-align:center; color:var(--ink2);">
            <i class="fas fa-chart-column" style="font-size:30px; opacity:.5; display:block; margin-bottom:10px;"></i>
            ไม่มีงานไรเดอร์ในช่วงเวลาที่เลือก
        </div>
    @else
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,420px),1fr)); gap:16px;">
            {{-- จำนวนงาน --}}
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:14px;">
                    <div class="tp-section-h"><i class="fas fa-chart-column"></i> จำนวนงาน</div>
                    <div style="display:flex; gap:10px; font-size:11.5px; color:var(--ink2);">
                        <span><span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:linear-gradient(180deg, var(--accent1), var(--deep1));"></span> สำเร็จ</span>
                        <span><span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:linear-gradient(180deg, var(--w-bad), color-mix(in srgb, var(--w-bad) 70%, var(--ink)));"></span> ยกเลิก/ไม่สำเร็จ</span>
                    </div>
                </div>
                <div style="overflow-x:auto; padding-bottom:4px;">
                    <div class="tp-bars" style="min-width:{{ max(300, $rows->count() * 34) }}px; height:220px;">
                        @foreach ($rows as $row)
                            <div class="col" title="{{ $row['label'] }}: สำเร็จ {{ $row['completed'] }} · ยกเลิก {{ $row['cancelled'] }} · ไม่สำเร็จ {{ $row['failed'] }}">
                                <div class="stack">
                                    <i class="bar a" style="height:{{ max(2, round($row['completed'] / $maxJobs * 100)) }}%;"></i>
                                    <i class="bar" style="height:{{ max(2, round(($row['cancelled'] + $row['failed']) / $maxJobs * 100)) }}%; background:linear-gradient(180deg, var(--w-bad), color-mix(in srgb, var(--w-bad) 70%, var(--ink)));"></i>
                                </div>
                                <span class="lbl">{{ $shortLabel($row['label']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- รายได้ --}}
            <div class="tp-card" style="padding:18px;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:14px;">
                    <div class="tp-section-h"><i class="fas fa-sack-dollar"></i> ค่าส่ง (งานสำเร็จ)</div>
                    <div style="display:flex; gap:10px; font-size:11.5px; color:var(--ink2);">
                        <span><span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:linear-gradient(180deg, var(--accent2), var(--deep2));"></span> ค่าส่งรวม</span>
                        <span><span style="display:inline-block; width:10px; height:10px; border-radius:3px; background:linear-gradient(180deg, var(--w-ok), color-mix(in srgb, var(--w-ok) 70%, var(--ink)));"></span> ไรเดอร์ได้</span>
                    </div>
                </div>
                <div style="overflow-x:auto; padding-bottom:4px;">
                    <div class="tp-bars" style="min-width:{{ max(300, $rows->count() * 34) }}px; height:220px;">
                        @foreach ($rows as $row)
                            <div class="col" title="{{ $row['label'] }}: ค่าส่ง ฿{{ number_format($row['revenue'], 2) }} · ไรเดอร์ ฿{{ number_format($row['rider_earnings'], 2) }}">
                                <div class="stack">
                                    <i class="bar b" style="height:{{ max(2, round($row['revenue'] / $maxMoney * 100)) }}%;"></i>
                                    <i class="bar" style="height:{{ max(2, round($row['rider_earnings'] / $maxMoney * 100)) }}%; background:linear-gradient(180deg, var(--w-ok), color-mix(in srgb, var(--w-ok) 70%, var(--ink)));"></i>
                                </div>
                                <span class="lbl">{{ $shortLabel($row['label']) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- ตารางรายละเอียด --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div class="tp-section-h" style="padding:14px 18px;"><i class="fas fa-table"></i> ตัวเลขราย{{ $period === 'monthly' ? 'เดือน' : 'วัน' }}</div>
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:720px; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:right; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                            <th style="padding:9px 18px; text-align:left;">{{ $period === 'monthly' ? 'เดือน' : 'วันที่' }}</th>
                            <th style="padding:9px 10px;">ทั้งหมด</th>
                            <th style="padding:9px 10px;">สำเร็จ</th>
                            <th style="padding:9px 10px;">ยกเลิก</th>
                            <th style="padding:9px 10px;">ไม่สำเร็จ</th>
                            <th style="padding:9px 10px;">ค่าส่งรวม</th>
                            <th style="padding:9px 10px;">ไรเดอร์ได้</th>
                            <th style="padding:9px 18px;">แพลตฟอร์ม</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows->reverse() as $row)
                            <tr class="w1-row tp-num" style="text-align:right; box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                                <td style="padding:9px 18px; text-align:left;">{{ $period === 'monthly' ? $shortLabel($row['label']) : \Illuminate\Support\Carbon::parse($row['label'])->thaidate('j M Y') }}</td>
                                <td style="padding:9px 10px;">{{ number_format($row['total']) }}</td>
                                <td style="padding:9px 10px; color:color-mix(in srgb, var(--w-ok) 74%, var(--ink));">{{ number_format($row['completed']) }}</td>
                                <td style="padding:9px 10px;">{{ number_format($row['cancelled']) }}</td>
                                <td style="padding:9px 10px;">{{ number_format($row['failed']) }}</td>
                                <td style="padding:9px 10px;">฿{{ number_format($row['revenue'], 2) }}</td>
                                <td style="padding:9px 10px;">฿{{ number_format($row['rider_earnings'], 2) }}</td>
                                <td style="padding:9px 18px;">฿{{ number_format($row['platform_fee'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ===== ไรเดอร์ยอดเยี่ยม ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:14px 18px;"><i class="fas fa-trophy" style="color:var(--accent1);"></i> ไรเดอร์ส่งสำเร็จมากที่สุด</div>
        @if ($topRiders->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; min-width:560px; border-collapse:collapse; font-size:13px;">
                    <thead>
                        <tr style="text-align:left; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                            <th style="padding:9px 18px;">อันดับ</th>
                            <th style="padding:9px 12px;">ไรเดอร์</th>
                            <th style="padding:9px 12px; text-align:right;">งานสำเร็จช่วงนี้</th>
                            <th style="padding:9px 12px; text-align:right;">คะแนน</th>
                            <th style="padding:9px 18px; text-align:right;">รายได้สะสม</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topRiders as $index => $rider)
                            <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                                <td style="padding:10px 18px; font-size:18px;">{{ ['🥇', '🥈', '🥉'][$index] ?? '#'.($index + 1) }}</td>
                                <td style="padding:10px 12px;">
                                    <a href="{{ route('admin.riders.show', $rider) }}" class="w1-link" style="color:var(--ink);">{{ $rider->full_name }}</a>
                                    <div style="font-size:11.5px; color:var(--ink2);">{{ $rider->phone }}</div>
                                </td>
                                <td style="padding:10px 12px; text-align:right;" class="tp-num"><b>{{ number_format($rider->completed_jobs_count) }}</b> งาน</td>
                                <td style="padding:10px 12px; text-align:right;" class="tp-num"><i class="fas fa-star" style="color:var(--accent1);"></i> {{ number_format((float) $rider->rating, 1) }}</td>
                                <td style="padding:10px 18px; text-align:right;" class="tp-num">฿{{ number_format((float) $rider->total_earnings, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="padding:0 18px 18px; margin:0; font-size:13px; color:var(--ink2);">ยังไม่มีไรเดอร์ส่งงานสำเร็จในช่วงเวลาที่เลือก</p>
        @endif
    </div>
</div>
@endsection
