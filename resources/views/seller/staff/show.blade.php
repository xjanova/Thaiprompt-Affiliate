@extends('layouts.seller-v4')

@section('title', 'พนักงาน - '.$employee->full_name)

{{-- (2026-09-25) SELLER-10: หน้านี้เดิมไม่มี view (StaffController@show → 500) --}}

@php
    $types = ['full_time' => 'พนักงานประจำ', 'part_time' => 'พาร์ทไทม์', 'contract' => 'สัญญาจ้าง', 'intern' => 'นักศึกษาฝึกงาน', 'freelance' => 'ฟรีแลนซ์'];
    $permNames = ['sales' => 'ขายสินค้า', 'refund' => 'คืนเงิน', 'discount' => 'ให้ส่วนลด', 'reports' => 'ดูรายงาน', 'inventory' => 'จัดการสินค้า', 'drawer' => 'เปิดลิ้นชักเงิน'];
    $assign = $employee->posAssignment;
    $attendance = $employee->attendanceRecords ?? collect();
    $serviceMonths = $employee->hire_date ? (int) floor(abs($employee->hire_date->diffInMonths(now()))) : null;
    $serviceText = is_null($serviceMonths) ? '—' : ($serviceMonths >= 12 ? intdiv($serviceMonths, 12).' ปี '.($serviceMonths % 12).' เดือน' : $serviceMonths.' เดือน');
    $rows = [
        ['รหัสพนักงาน', $employee->employee_id],
        ['ชื่อ (อังกฤษ)', $employee->full_name],
        ['ชื่อเล่น', $employee->nickname ?: '—'],
        ['เบอร์โทร', $employee->mobile_phone ?: '—'],
        ['อีเมล', $employee->personal_email ?: '—'],
    ];
    $jobRows = [
        ['แผนก', $employee->department->name ?? '—'],
        ['ตำแหน่ง', $employee->position->title ?? '—'],
        ['ประเภทการจ้าง', $types[$employee->employment_type] ?? $employee->employment_type],
        ['วันเริ่มงาน', optional($employee->hire_date)->format('d/m/Y') ?? '—'],
        ['อายุงาน', $serviceText],
        ['เงินเดือน', '฿'.number_format((float) $employee->basic_salary, 2)],
    ];
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:11px 14px; font-size:13px; color:var(--ink); white-space:nowrap;';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header :title="$employee->full_name_th ?: $employee->full_name" icon="🪪" crumb="ร้านค้า · พนักงาน"
                         :subtitle="($employee->position->title ?? 'พนักงาน').' · '.($employee->department->name ?? 'ทั่วไป')">
        @include('seller.staff.partials.status', ['status' => $employee->employment_status])
        <a href="{{ route('seller.staff.edit', $employee) }}" class="tp-btn tp-btn-primary tp-btn-sm">✏️ แก้ไข</a>
        <a href="{{ route('seller.staff.index') }}" class="tp-btn tp-btn-sm">← พนักงานทั้งหมด</a>
    </x-seller-kit.header>

    @include('seller.staff.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="tp-tile" style="width:56px; height:56px; border-radius:50%; font-size:22px;" aria-hidden="true">{{ mb_strtoupper(mb_substr($employee->first_name_th ?: $employee->first_name, 0, 1)) }}</span>
                <div>
                    <div style="font-weight:800; font-size:15px;">{{ $employee->full_name_th ?: $employee->full_name }}</div>
                    <div class="tp-num" style="font-size:12px; color:var(--ink2);">{{ $employee->employee_id }}</div>
                </div>
            </div>
            @foreach($rows as [$rLabel, $rValue])
                <div style="display:flex; justify-content:space-between; gap:10px; font-size:13px;"><span style="color:var(--ink2);">{{ $rLabel }}</span><span style="text-align:right; overflow-wrap:anywhere;">{{ $rValue }}</span></div>
            @endforeach
        </div>

        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">💼 การจ้างงาน</div>
            @foreach($jobRows as [$rLabel, $rValue])
                <div style="display:flex; justify-content:space-between; gap:10px; font-size:13px;"><span style="color:var(--ink2);">{{ $rLabel }}</span><span class="tp-num" style="text-align:right;">{{ $rValue }}</span></div>
            @endforeach
        </div>

        <div class="tp-card" style="display:flex; flex-direction:column; gap:10px;">
            <div class="tp-section-h">🔑 เครื่อง POS</div>
            @if($assign)
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">รหัสพนักงานบนเครื่อง</span><span class="tp-num" style="font-weight:700;">{{ $assign->staff_code }}</span></div>
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">สถานะ</span><x-seller-kit.pill :tone="$assign->is_active ? 'ok' : 'muted'">{{ $assign->is_active ? 'ใช้งานได้' : 'ปิดอยู่' }}</x-seller-kit.pill></div>
                <div style="display:flex; justify-content:space-between; font-size:13px;"><span style="color:var(--ink2);">PIN</span><span>{{ $assign->pin_code ? 'ตั้งแล้ว (ซ่อน)' : 'ยังไม่ได้ตั้ง' }}</span></div>
                <div>
                    <div style="font-size:12px; color:var(--ink2); margin-bottom:6px;">สิทธิ์</div>
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        @forelse((array) ($assign->pos_permissions ?? []) as $perm)
                            <span class="tp-pill tp-pill-soft">{{ $permNames[$perm] ?? $perm }}</span>
                        @empty
                            <span style="font-size:12px; color:var(--ink2);">ยังไม่กำหนด</span>
                        @endforelse
                    </div>
                </div>
            @else
                <x-seller-kit.empty icon="🔑" title="ยังไม่ได้ตั้งค่า POS" text="ตั้งรหัส PIN ในหน้าแก้ไข เพื่อให้พนักงานเข้าเครื่อง POS ได้">
                    <a href="{{ route('seller.staff.edit', $employee) }}" class="tp-btn tp-btn-sm">ตั้งค่า PIN</a>
                </x-seller-kit.empty>
            @endif
        </div>
    </div>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        <div class="tp-section-h" style="padding:16px 18px;">🕘 การลงเวลาล่าสุด (10 วัน)</div>
        @if($attendance->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:620px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">วันที่</th>
                            <th style="{{ $th }}">เข้างาน</th>
                            <th style="{{ $th }}">ออกงาน</th>
                            <th style="{{ $th }} text-align:right;">ชั่วโมงทำงาน</th>
                            <th style="{{ $th }} text-align:center;">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendance as $rec)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }}" class="tp-num">{{ optional($rec->date)->format('d/m/Y') }}</td>
                                <td style="{{ $td }}" class="tp-num">{{ optional($rec->check_in)->format('H:i') ?? '—' }}</td>
                                <td style="{{ $td }}" class="tp-num">{{ optional($rec->check_out)->format('H:i') ?? '—' }}</td>
                                <td style="{{ $td }} text-align:right;" class="tp-num">{{ $rec->work_hours !== null ? number_format((float) $rec->work_hours, 1) : '—' }}</td>
                                <td style="{{ $td }} text-align:center;">
                                    @if($rec->is_late)<x-seller-kit.pill tone="warn">สาย {{ (int) $rec->late_minutes }} นาที</x-seller-kit.pill>@endif
                                    @if($rec->is_early_leave)<x-seller-kit.pill tone="info">ออกก่อน {{ (int) $rec->early_leave_minutes }} นาที</x-seller-kit.pill>@endif
                                    @if(! $rec->is_late && ! $rec->is_early_leave)<span style="color:var(--ink2);">ปกติ</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <x-seller-kit.empty icon="🕘" title="ยังไม่มีการลงเวลา" text="เมื่อพนักงานลงเวลาด้วย PIN ที่เครื่อง POS ข้อมูลจะแสดงที่นี่" />
        @endif
    </div>

    <form method="POST" action="{{ route('seller.staff.destroy', $employee) }}" class="tp-card" style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px; border-left:4px solid var(--tp-bad, #d9534f);"
          onsubmit="return confirm('ลบพนักงาน {{ addslashes($employee->full_name) }} ออกจากร้าน? (ระบบเก็บประวัติไว้)');">
        @csrf
        @method('DELETE')
        <div style="font-size:12.5px; color:var(--ink2);">ลบพนักงานออกจากรายชื่อร้าน — ประวัติการลงเวลายังถูกเก็บไว้</div>
        <button type="submit" class="tp-btn tp-btn-sm" style="color:var(--tp-bad, #d9534f);">🗑️ ลบพนักงาน</button>
    </form>
</div>
@endsection
