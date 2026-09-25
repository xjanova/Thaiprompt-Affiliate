{{--
 | งานไรเดอร์ทั้งหมด (admin.rider-jobs.index) — ธีม V4
 | ตัวแปรจาก Admin\RiderJobController@index: $jobs (paginator), $stats{total,pending,manual_needed,in_progress,completed,cancelled,failed,total_earnings}, $riders (อนุมัติแล้ว id/full_name), $pageTitle
 | ตัวกรอง GET: search, status (enum จริง 9 ค่า), manual_needed=1, job_type, rider_id, date_from, date_to
 | ปุ่มยกเลิก → POST admin.rider-jobs.cancel {reason, redispatch?} (รับของแล้ว = ปิดงานเป็นส่งไม่สำเร็จ)
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'งานไรเดอร์')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $jobStatuses = [
        'pending' => 'รอไรเดอร์รับงาน',
        'accepted' => 'ไรเดอร์รับงานแล้ว',
        'picking_up' => 'กำลังไปรับของ',
        'picked_up' => 'รับของแล้ว',
        'delivering' => 'กำลังจัดส่ง',
        'delivered' => 'ส่งแล้ว',
        'completed' => 'เสร็จสิ้น',
        'cancelled' => 'ยกเลิก',
        'failed' => 'ส่งไม่สำเร็จ',
    ];
    $jobTypes = [
        'fresh_market' => 'ส่งของตลาดสด',
        'shop_delivery' => 'ส่งสินค้าร้านค้า',
        'delivery' => 'ส่งของ',
        'food' => 'ส่งอาหาร',
        'document' => 'ส่งเอกสาร',
        'service' => 'ให้บริการ',
        'pickup' => 'รับของ',
    ];
    $hasFilter = request()->hasAny(['search', 'status', 'manual_needed', 'job_type', 'rider_id', 'date_from', 'date_to']);
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ไรเดอร์ · งานไรเดอร์</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">งานไรเดอร์ 📦</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ติดตามทุกงานส่ง มอบหมายไรเดอร์ ยกเลิก หรือสร้างงานใหม่ให้ออเดอร์</div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px;">
            <a href="{{ route('admin.riders.monitor') }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-satellite-dish"></i> มอนิเตอร์สด</a>
            <a href="{{ route('admin.rider-jobs.statistics') }}" class="tp-btn tp-btn-sm"><i class="fas fa-chart-column"></i> สถิติ</a>
            <a href="{{ route('admin.riders.index') }}" class="tp-btn tp-btn-sm"><i class="fas fa-users"></i> ไรเดอร์</a>
        </div>
    </div>

    @include('admin.riders.partials.flash')

    @if (($stats['manual_needed'] ?? 0) > 0)
        <a href="{{ route('admin.rider-jobs.index', ['manual_needed' => 1]) }}" class="tp-card tp-card-hover"
           style="padding:14px 18px; border-left:4px solid var(--w-bad); text-decoration:none; color:var(--ink); display:flex; align-items:center; gap:12px;">
            <span style="width:10px; height:10px; border-radius:50%; background:var(--w-bad); animation:tpPulse 1.2s infinite; flex:none;"></span>
            <span style="flex:1; font-size:13.5px;"><b class="tp-num">{{ number_format($stats['manual_needed']) }}</b> งานไม่มีไรเดอร์รับ ต้องมอบหมายเอง — กดเพื่อดูรายการ</span>
            <i class="fas fa-chevron-right" style="color:var(--ink2);"></i>
        </a>
    @endif

    {{-- ===== ตัวเลขสรุป ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:14px;">
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-layer-group', 'kpiValue' => number_format($stats['total'] ?? 0), 'kpiLabel' => 'งานทั้งหมด', 'kpiTone' => null, 'kpiHref' => route('admin.rider-jobs.index'), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-hourglass-half', 'kpiValue' => number_format($stats['pending'] ?? 0), 'kpiLabel' => 'รอไรเดอร์รับ', 'kpiTone' => 'warn', 'kpiHref' => route('admin.rider-jobs.index', ['status' => 'pending']), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-hand', 'kpiValue' => number_format($stats['manual_needed'] ?? 0), 'kpiLabel' => 'ต้องจัดเอง', 'kpiTone' => 'bad', 'kpiHref' => route('admin.rider-jobs.index', ['manual_needed' => 1]), 'kpiHint' => null, 'kpiPulse' => ($stats['manual_needed'] ?? 0) > 0])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-truck-fast', 'kpiValue' => number_format($stats['in_progress'] ?? 0), 'kpiLabel' => 'กำลังดำเนินการ', 'kpiTone' => 'violet', 'kpiHref' => null, 'kpiHint' => 'รับงาน → กำลังส่ง', 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-circle-check', 'kpiValue' => number_format($stats['completed'] ?? 0), 'kpiLabel' => 'เสร็จสิ้น', 'kpiTone' => 'ok', 'kpiHref' => route('admin.rider-jobs.index', ['status' => 'completed']), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-ban', 'kpiValue' => number_format(($stats['cancelled'] ?? 0) + ($stats['failed'] ?? 0)), 'kpiLabel' => 'ยกเลิก / ส่งไม่สำเร็จ', 'kpiTone' => 'bad', 'kpiHref' => null, 'kpiHint' => 'ยกเลิก '.number_format($stats['cancelled'] ?? 0).' · ไม่สำเร็จ '.number_format($stats['failed'] ?? 0), 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-coins', 'kpiValue' => '฿'.number_format($stats['total_earnings'] ?? 0, 2), 'kpiLabel' => 'ค่าส่งรวม (งานสำเร็จ)', 'kpiTone' => 'info', 'kpiHref' => route('admin.rider-jobs.statistics'), 'kpiHint' => null, 'kpiPulse' => false])
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-filter"></i> ตัวกรอง</div>
        <form method="GET" action="{{ route('admin.rider-jobs.index') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,170px),1fr)); gap:12px; align-items:end;">
            <label style="display:block; grid-column:1 / -1;">
                <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ค้นหา</span>
                <input type="search" name="search" value="{{ request('search') }}" class="tp-input" placeholder="เลขงาน ชื่องาน หรือชื่อไรเดอร์">
            </label>
            <label style="display:block;">
                <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะงาน</span>
                <select name="status" class="tp-input">
                    <option value="">ทุกสถานะ</option>
                    @foreach ($jobStatuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label style="display:block;">
                <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ประเภทงาน</span>
                <select name="job_type" class="tp-input">
                    <option value="">ทุกประเภท</option>
                    @foreach ($jobTypes as $value => $label)
                        <option value="{{ $value }}" @selected(request('job_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label style="display:block;">
                <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ไรเดอร์</span>
                <select name="rider_id" class="tp-input">
                    <option value="">ทุกคน</option>
                    @foreach ($riders as $riderOption)
                        <option value="{{ $riderOption->id }}" @selected((string) request('rider_id') === (string) $riderOption->id)>{{ $riderOption->full_name }}</option>
                    @endforeach
                </select>
            </label>
            <label style="display:block;">
                <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ตั้งแต่วันที่</span>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="tp-input">
            </label>
            <label style="display:block;">
                <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ถึงวันที่</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="tp-input">
            </label>
            <label style="display:flex; align-items:center; gap:8px; min-height:42px; font-size:13px; cursor:pointer;">
                <input type="checkbox" name="manual_needed" value="1" @checked(request()->boolean('manual_needed')) style="width:17px; height:17px; accent-color:var(--accent1);">
                เฉพาะงานที่ต้องจัดเอง
            </label>
            <div style="display:flex; gap:9px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
                @if ($hasFilter)
                    <a href="{{ route('admin.rider-jobs.index') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
                @endif
            </div>
        </form>
    </div>

    {{-- ===== ตารางงาน ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
            <div class="tp-section-h"><i class="fas fa-list-check"></i> รายการงาน</div>
            <span style="font-size:12px; color:var(--ink2);">ทั้งหมด {{ number_format($jobs->total()) }} งาน</span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:980px; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="text-align:left; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                        <th style="padding:10px 18px;">งาน</th>
                        <th style="padding:10px 12px;">ลูกค้า / ไรเดอร์</th>
                        <th style="padding:10px 12px;">เส้นทาง</th>
                        <th style="padding:10px 12px; text-align:right;">ค่าส่ง</th>
                        <th style="padding:10px 12px;">สถานะ</th>
                        <th style="padding:10px 12px;">สร้างเมื่อ</th>
                        <th style="padding:10px 18px; text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobs as $job)
                        @php
                            $holdsGoods = in_array($job->status, ['picked_up', 'delivering'], true);
                            $canCancel = in_array($job->status, ['pending', 'accepted', 'picking_up'], true);
                            $isManual = $job->dispatch_type === 'manual_needed' && ! $job->isTerminal();
                        @endphp
                        <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                            <td style="padding:12px 18px;">
                                <a href="{{ route('admin.rider-jobs.show', $job) }}" class="w1-link tp-num">#{{ $job->job_number }}</a>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ $job->job_type_text }}</div>
                                @if ($isManual)
                                    <div style="margin-top:4px;">@include('admin.riders.partials.pill', ['pillTone' => 'bad', 'pillText' => 'ต้องจัดเอง', 'pillIcon' => 'fa-hand', 'pillTitle' => null])</div>
                                @endif
                            </td>
                            <td style="padding:12px;">
                                <div><i class="fas fa-user" style="color:var(--ink2); width:14px;"></i> {{ $job->customer?->name ?? $job->delivery_contact_name ?? '-' }}</div>
                                <div style="margin-top:3px;">
                                    <i class="fas fa-motorcycle" style="color:var(--ink2); width:14px;"></i>
                                    @if ($job->rider)
                                        <a href="{{ route('admin.riders.show', $job->rider) }}" class="w1-link">{{ $job->rider->full_name }}</a>
                                    @else
                                        <span style="color:var(--ink2);">ยังไม่มีไรเดอร์</span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding:12px; max-width:300px;">
                                <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><i class="fas fa-store" style="color:var(--ink2); width:14px;"></i> {{ $job->pickup_contact_name ?: ($job->pickup_address ?: '-') }}</div>
                                <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--ink2);"><i class="fas fa-flag-checkered" style="width:14px;"></i> {{ $job->delivery_address ?: '-' }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">{{ number_format((float) $job->distance_km, 1) }} กม.</div>
                            </td>
                            <td style="padding:12px; text-align:right; white-space:nowrap;">
                                <div class="tp-num" style="font-weight:700;">฿{{ number_format((float) $job->total_fee, 2) }}</div>
                                <div style="font-size:11.5px; color:var(--ink2);">ไรเดอร์ ฿{{ number_format((float) $job->rider_earnings, 2) }}</div>
                                @if ((float) $job->cod_amount > 0)
                                    <div style="margin-top:3px;">@include('admin.riders.partials.pill', ['pillTone' => 'gold', 'pillText' => 'COD ฿'.number_format((float) $job->cod_amount, 2), 'pillIcon' => null, 'pillTitle' => null])</div>
                                @endif
                            </td>
                            <td style="padding:12px;">@include('admin.riders.partials.status', ['statusKind' => 'job', 'statusValue' => $job->status, 'statusLabel' => $job->status_text])</td>
                            <td style="padding:12px; white-space:nowrap; color:var(--ink2);">
                                {{ $job->created_at?->thaidate('j M Y') }}
                                <div style="font-size:11.5px;">{{ $job->created_at?->format('H:i') }} น.</div>
                            </td>
                            <td style="padding:12px 18px;">
                                <div style="display:flex; justify-content:flex-end; gap:7px;">
                                    <a href="{{ route('admin.rider-jobs.show', $job) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-eye"></i> ดู</a>
                                    @if ($canCancel)
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-bad);" title="ยกเลิกงาน"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.rider-jobs.cancel', $job), 'title' => 'ยกเลิกงาน #'.$job->job_number, 'message' => 'ไรเดอร์ยังไม่ได้รับของ งานจะถูกยกเลิกและออเดอร์กลับไปรอไรเดอร์', 'reason' => 'required', 'reasonLabel' => 'เหตุผลที่ยกเลิก', 'confirm' => 'ยกเลิกงาน', 'tone' => 'bad', 'icon' => 'fa-ban', 'checkbox' => ['name' => 'redispatch', 'label' => 'สร้างงานใหม่และหาไรเดอร์คนใหม่ทันที', 'hint' => 'ใช้เมื่อยกเลิกเพราะไรเดอร์คนเดิมมีปัญหา แต่ออเดอร์ยังต้องส่ง', 'checked' => false]]))">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @elseif ($holdsGoods)
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-bad);" title="ปิดงาน (ส่งไม่สำเร็จ)"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.rider-jobs.cancel', $job), 'title' => 'ปิดงาน #'.$job->job_number.' เป็นส่งไม่สำเร็จ', 'message' => "ไรเดอร์รับของไปแล้ว — งานจะถูกปิดเป็น \"ส่งไม่สำเร็จ\"\nต้องประสานให้ไรเดอร์นำของคืนร้าน", 'reason' => 'required', 'reasonLabel' => 'เหตุผล', 'confirm' => 'ปิดงาน (ส่งไม่สำเร็จ)', 'tone' => 'bad', 'icon' => 'fa-triangle-exclamation']))">
                                            <i class="fas fa-triangle-exclamation"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:44px 18px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-inbox" style="font-size:32px; opacity:.5; display:block; margin-bottom:10px;"></i>
                                {{ $hasFilter ? 'ไม่พบงานตามเงื่อนไขที่เลือก' : 'ยังไม่มีงานไรเดอร์ในระบบ' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($jobs->hasPages())
        <div>{{ $jobs->links() }}</div>
    @endif
</div>
@endsection
