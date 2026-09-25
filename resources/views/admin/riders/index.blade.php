{{--
 | จัดการไรเดอร์ (admin.riders.index) — ธีม V4
 | ตัวแปรจาก Admin\RiderController@index: $riders (paginator), $stats{total,pending,approved,rejected,suspended,online,busy,needs_review}, $pageTitle
 | ตัวกรอง GET: search, status, availability, vehicle_type, needs_review=1
 | ปุ่มจัดการส่งฟอร์ม POST จริงผ่านโมดัลยืนยัน (controller ตอบ redirect back + flash)
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'จัดการไรเดอร์')

@section('content')
@include('admin.riders.partials.v4-kit')
@php
    $vehicleIcons = ['motorcycle' => 'fa-motorcycle', 'car' => 'fa-car', 'bicycle' => 'fa-bicycle', 'walk' => 'fa-person-walking'];
    $vehicleNames = \App\Services\RiderAccountService::VEHICLE_TYPES;
    $hasFilter = request()->hasAny(['search', 'status', 'availability', 'vehicle_type', 'needs_review']);
@endphp
<div x-data="{}" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== หัวเรื่อง ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ไรเดอร์ · ไรเดอร์ทั้งหมด</div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">จัดการไรเดอร์ 🛵</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">ตรวจใบสมัคร ดูเอกสาร ระงับ/ปลดระงับ และติดตามตำแหน่งไรเดอร์</div>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:9px;">
            <a href="{{ route('admin.riders.pending') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-user-clock" style="color:var(--w-warn);"></i> รออนุมัติ
                @if (($stats['pending'] ?? 0) > 0)
                    <span class="tp-pill tp-pill-gold">{{ number_format($stats['pending']) }}</span>
                @endif
            </a>
            <a href="{{ route('admin.riders.monitor') }}" class="tp-btn tp-btn-sm"><i class="fas fa-satellite-dish"></i> มอนิเตอร์สด</a>
            <a href="{{ route('admin.riders.map') }}" class="tp-btn tp-btn-sm"><i class="fas fa-map-location-dot"></i> แผนที่ไรเดอร์</a>
            <a href="{{ route('admin.riders.settings') }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-sliders"></i> ตั้งค่าค่าส่ง</a>
        </div>
    </div>

    @include('admin.riders.partials.flash')

    {{-- ===== ตัวเลขสรุป ===== --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:14px;">
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-users', 'kpiValue' => number_format($stats['total'] ?? 0), 'kpiLabel' => 'ไรเดอร์ทั้งหมด', 'kpiTone' => null, 'kpiHref' => route('admin.riders.index'), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-user-clock', 'kpiValue' => number_format($stats['pending'] ?? 0), 'kpiLabel' => 'รอตรวจสอบ', 'kpiTone' => 'warn', 'kpiHref' => route('admin.riders.pending'), 'kpiHint' => null, 'kpiPulse' => ($stats['pending'] ?? 0) > 0])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-circle-check', 'kpiValue' => number_format($stats['approved'] ?? 0), 'kpiLabel' => 'อนุมัติแล้ว', 'kpiTone' => 'ok', 'kpiHref' => route('admin.riders.index', ['status' => 'approved']), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-signal', 'kpiValue' => number_format($stats['online'] ?? 0), 'kpiLabel' => 'พร้อมรับงาน', 'kpiTone' => 'ok', 'kpiHref' => route('admin.riders.index', ['availability' => 'online']), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-motorcycle', 'kpiValue' => number_format($stats['busy'] ?? 0), 'kpiLabel' => 'กำลังส่งงาน', 'kpiTone' => 'violet', 'kpiHref' => route('admin.riders.index', ['availability' => 'busy']), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-file-circle-exclamation', 'kpiValue' => number_format($stats['needs_review'] ?? 0), 'kpiLabel' => 'เอกสารเปลี่ยน รอตรวจซ้ำ', 'kpiTone' => 'info', 'kpiHref' => route('admin.riders.index', ['needs_review' => 1]), 'kpiHint' => null, 'kpiPulse' => ($stats['needs_review'] ?? 0) > 0])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-ban', 'kpiValue' => number_format($stats['suspended'] ?? 0), 'kpiLabel' => 'ถูกระงับ', 'kpiTone' => 'bad', 'kpiHref' => route('admin.riders.index', ['status' => 'suspended']), 'kpiHint' => null, 'kpiPulse' => false])
        @include('admin.riders.partials.kpi', ['kpiIcon' => 'fa-circle-xmark', 'kpiValue' => number_format($stats['rejected'] ?? 0), 'kpiLabel' => 'ถูกปฏิเสธ', 'kpiTone' => 'bad', 'kpiHref' => route('admin.riders.index', ['status' => 'rejected']), 'kpiHint' => null, 'kpiPulse' => false])
    </div>

    {{-- ===== ตัวกรอง ===== --}}
    <div class="tp-card" style="padding:18px;">
        <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-filter"></i> ตัวกรอง</div>
        <form method="GET" action="{{ route('admin.riders.index') }}"
              style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; align-items:end;">
            <label style="display:block; grid-column:1 / -1; min-width:0;">
                <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ค้นหา</span>
                <input type="search" name="search" value="{{ request('search') }}" class="tp-input"
                       placeholder="ชื่อ เบอร์โทร เลขบัตร ทะเบียนรถ หรืออีเมล">
            </label>
            <label style="display:block;">
                <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะบัญชี</span>
                <select name="status" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    @foreach (['pending' => 'รอตรวจสอบ', 'approved' => 'อนุมัติแล้ว', 'rejected' => 'ถูกปฏิเสธ', 'suspended' => 'ถูกระงับ', 'inactive' => 'ไม่ใช้งาน'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label style="display:block;">
                <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">สถานะรับงาน</span>
                <select name="availability" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    @foreach (['online' => 'พร้อมรับงาน', 'busy' => 'กำลังส่งงาน', 'offline' => 'ออฟไลน์'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('availability') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label style="display:block;">
                <span style="display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;">ยานพาหนะ</span>
                <select name="vehicle_type" class="tp-input">
                    <option value="">ทั้งหมด</option>
                    @foreach ($vehicleNames as $value => $label)
                        <option value="{{ $value }}" @selected(request('vehicle_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label style="display:flex; align-items:center; gap:8px; min-height:42px; font-size:13px; cursor:pointer;">
                <input type="checkbox" name="needs_review" value="1" @checked(request()->boolean('needs_review')) style="width:17px; height:17px; accent-color:var(--accent1);">
                เฉพาะเอกสารรอตรวจซ้ำ
            </label>
            <div style="display:flex; gap:9px; flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary"><i class="fas fa-magnifying-glass"></i> ค้นหา</button>
                @if ($hasFilter)
                    <a href="{{ route('admin.riders.index') }}" class="tp-btn"><i class="fas fa-rotate-left"></i> ล้าง</a>
                @endif
            </div>
        </form>
    </div>

    {{-- ===== รายชื่อไรเดอร์ ===== --}}
    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; padding:14px 18px;">
            <div class="tp-section-h"><i class="fas fa-list"></i> รายชื่อไรเดอร์</div>
            <span style="font-size:12px; color:var(--ink2);">ทั้งหมด {{ number_format($riders->total()) }} คน</span>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%; min-width:860px; border-collapse:collapse; font-size:13.5px;">
                <thead>
                    <tr style="text-align:left; font-size:11px; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px;">
                        <th style="padding:10px 18px;">ไรเดอร์</th>
                        <th style="padding:10px 12px;">ติดต่อ / ยานพาหนะ</th>
                        <th style="padding:10px 12px;">ผลงาน</th>
                        <th style="padding:10px 12px;">สถานะ</th>
                        <th style="padding:10px 18px; text-align:right;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riders as $rider)
                        <tr class="w1-row" style="box-shadow:inset 0 1px 0 color-mix(in srgb, var(--ink2) 14%, transparent);">
                            <td style="padding:12px 18px;">
                                <div style="display:flex; align-items:center; gap:11px;">
                                    @if ($rider->profile_image)
                                        <img src="{{ route('admin.riders.document', [$rider, 'profile']) }}" alt="" loading="lazy"
                                             style="width:40px; height:40px; border-radius:50%; object-fit:cover; box-shadow:var(--raise); flex:none;">
                                    @else
                                        <span class="tp-tile" style="width:40px; height:40px; border-radius:50%; font-weight:800;">{{ mb_substr($rider->full_name ?: 'R', 0, 1) }}</span>
                                    @endif
                                    <div style="min-width:0;">
                                        <a href="{{ route('admin.riders.show', $rider) }}" class="w1-link" style="color:var(--ink);">{{ $rider->full_name }}</a>
                                        <div style="font-size:11.5px; color:var(--ink2);">#{{ $rider->id }} · สมัคร {{ $rider->created_at?->thaidate('j M Y') }}</div>
                                        @if ($rider->documents_changed_at)
                                            <div style="margin-top:4px;">@include('admin.riders.partials.pill', ['pillTone' => 'info', 'pillText' => 'เอกสารเปลี่ยน รอตรวจ', 'pillIcon' => 'fa-file-circle-exclamation', 'pillTitle' => null])</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="padding:12px;">
                                <div><a href="tel:{{ $rider->phone }}" class="w1-link">{{ $rider->phone }}</a></div>
                                <div style="font-size:11.5px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis; max-width:220px; white-space:nowrap;">{{ $rider->user?->email ?? '-' }}</div>
                                <div style="font-size:12px; margin-top:4px;">
                                    <i class="fas {{ $vehicleIcons[$rider->vehicle_type] ?? 'fa-question' }}" style="color:var(--ink2);"></i>
                                    {{ $rider->vehicle_type_text }} @if ($rider->vehicle_plate)<span class="tp-num" style="color:var(--ink2);">· {{ $rider->vehicle_plate }}</span>@endif
                                </div>
                            </td>
                            <td style="padding:12px; white-space:nowrap;">
                                <div><i class="fas fa-star" style="color:var(--accent1);"></i> <b class="tp-num">{{ number_format((float) $rider->rating, 1) }}</b> <span style="font-size:11.5px; color:var(--ink2);">({{ number_format((int) $rider->rating_count) }})</span></div>
                                <div style="font-size:12px; color:var(--ink2);">สำเร็จ <b class="tp-num" style="color:var(--ink);">{{ number_format((int) $rider->completed_jobs) }}</b> / {{ number_format((int) $rider->total_jobs) }} งาน</div>
                            </td>
                            <td style="padding:12px;">
                                <div style="display:flex; flex-direction:column; align-items:flex-start; gap:5px;">
                                    @include('admin.riders.partials.status', ['statusKind' => 'rider', 'statusValue' => $rider->status, 'statusLabel' => null])
                                    @if ($rider->status === 'approved')
                                        @include('admin.riders.partials.status', ['statusKind' => 'availability', 'statusValue' => $rider->availability, 'statusLabel' => null])
                                    @endif
                                </div>
                            </td>
                            <td style="padding:12px 18px;">
                                <div style="display:flex; justify-content:flex-end; gap:7px; flex-wrap:wrap;">
                                    <a href="{{ route('admin.riders.show', $rider) }}" class="tp-btn tp-btn-sm tp-btn-primary"><i class="fas fa-eye"></i> ดู</a>
                                    <a href="{{ route('admin.riders.locations', $rider) }}" class="tp-icon-btn" style="width:34px; height:34px;" title="ตำแหน่ง GPS"><i class="fas fa-location-crosshairs"></i></a>
                                    @if ($rider->status === 'pending')
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-ok);" title="อนุมัติ"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.riders.approve', $rider), 'title' => 'อนุมัติไรเดอร์ '.$rider->full_name, 'message' => 'ระบบจะตรวจว่ามีเอกสารที่บังคับครบก่อนอนุมัติ ไรเดอร์จะได้รับแจ้งเตือนในแอปทันที', 'reason' => 'none', 'confirm' => 'อนุมัติ', 'tone' => 'ok', 'icon' => 'fa-circle-check']))">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-bad);" title="ปฏิเสธ"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.riders.reject', $rider), 'title' => 'ปฏิเสธใบสมัคร '.$rider->full_name, 'message' => 'ไรเดอร์จะเห็นเหตุผลนี้ในแอป และแก้ไขแล้วส่งใหม่ได้', 'reason' => 'required', 'reasonLabel' => 'เหตุผลที่ไม่อนุมัติ', 'placeholder' => 'เช่น รูปบัตรประชาชนไม่ชัด กรุณาถ่ายใหม่', 'confirm' => 'ปฏิเสธ', 'tone' => 'bad', 'icon' => 'fa-circle-xmark']))">
                                            <i class="fas fa-xmark"></i>
                                        </button>
                                    @elseif ($rider->status === 'approved')
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-bad);" title="ระงับ"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.riders.suspend', $rider), 'title' => 'ระงับไรเดอร์ '.$rider->full_name, 'message' => "ไรเดอร์จะถูกบังคับออฟไลน์ทันที\nงานที่ยังไม่รับของจะคืนเข้าคิว — งานที่รับของแล้วต้องมอบหมายไรเดอร์ใหม่", 'reason' => 'required', 'reasonLabel' => 'เหตุผลที่ระงับ', 'confirm' => 'ระงับ', 'tone' => 'bad', 'icon' => 'fa-ban']))">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @elseif ($rider->status === 'suspended')
                                        <button type="button" class="tp-icon-btn" style="width:34px; height:34px; color:var(--w-ok);" title="ยกเลิกการระงับ"
                                                @click="$dispatch('w1-action', @js(['url' => route('admin.riders.toggle-active', $rider), 'title' => 'ยกเลิกการระงับ '.$rider->full_name, 'message' => 'บัญชีกลับเป็น "อนุมัติแล้ว" แต่ยังออฟไลน์ ไรเดอร์ต้องกดเปิดรับงานเอง', 'reason' => 'none', 'confirm' => 'ยกเลิกการระงับ', 'tone' => 'ok', 'icon' => 'fa-unlock']))">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:44px 18px; text-align:center; color:var(--ink2);">
                                <i class="fas fa-motorcycle" style="font-size:32px; opacity:.5; display:block; margin-bottom:10px;"></i>
                                {{ $hasFilter ? 'ไม่พบไรเดอร์ตามเงื่อนไขที่เลือก' : 'ยังไม่มีไรเดอร์สมัครเข้ามา' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($riders->hasPages())
        <div>{{ $riders->links() }}</div>
    @endif
</div>
@endsection
