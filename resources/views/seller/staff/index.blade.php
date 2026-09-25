@extends('layouts.seller-v4')

@section('title', 'พนักงานร้าน - '.($store->store_name ?? 'ร้านค้า'))

@php
    $th = 'padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--ink2); text-transform:uppercase; letter-spacing:.4px; white-space:nowrap;';
    $td = 'padding:12px 14px; font-size:13px; color:var(--ink);';
    $row = 'border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="พนักงานร้าน" icon="👥" crumb="ร้านค้า · พนักงาน"
                         subtitle="ระบบพนักงานฟรีทุกร้าน — เก็บข้อมูลพนักงาน แผนก กะงาน และรหัส PIN สำหรับเข้าเครื่อง POS">
        <a href="{{ route('seller.staff.create') }}" class="tp-btn tp-btn-primary">＋ เพิ่มพนักงาน</a>
    </x-seller-kit.header>

    @include('seller.staff.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
        <x-seller-kit.stat label="พนักงานทั้งหมด" :value="number_format((int) $stats['total'])" icon="👥" tone="info" />
        <x-seller-kit.stat label="ทำงานอยู่" :value="number_format((int) $stats['active'])" icon="✅" tone="ok" />
        <x-seller-kit.stat label="ทดลองงาน" :value="number_format((int) $stats['probation'])" icon="⏳" tone="warn" />
    </div>

    <form method="GET" action="{{ route('seller.staff.index') }}" class="tp-card" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:12px; align-items:end;">
        <div style="min-width:0;">
            <label for="st-q" style="font-size:12px; font-weight:700; color:var(--ink2);">ค้นหา</label>
            <input id="st-q" type="search" name="search" value="{{ request('search') }}" placeholder="ชื่อ รหัสพนักงาน หรือเบอร์โทร" class="tp-input" style="margin-top:6px;">
        </div>
        <div>
            <label for="st-dept" style="font-size:12px; font-weight:700; color:var(--ink2);">แผนก</label>
            <select id="st-dept" name="department" class="tp-input" style="margin-top:6px;">
                <option value="">ทุกแผนก</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected((string) request('department') === (string) $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="st-status" style="font-size:12px; font-weight:700; color:var(--ink2);">สถานะ</label>
            <select id="st-status" name="status" class="tp-input" style="margin-top:6px;">
                <option value="">ทุกสถานะ</option>
                <option value="active" @selected(request('status') === 'active')>ทำงานอยู่</option>
                <option value="probation" @selected(request('status') === 'probation')>ทดลองงาน</option>
                <option value="notice_period" @selected(request('status') === 'notice_period')>แจ้งลาออก</option>
                <option value="resigned" @selected(request('status') === 'resigned')>ลาออกแล้ว</option>
            </select>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;">🔍 ค้นหา</button>
            <a href="{{ route('seller.staff.index') }}" class="tp-btn">ล้าง</a>
        </div>
    </form>

    <div class="tp-card" style="padding:0; overflow:hidden;">
        @if($employees->count() > 0)
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; min-width:760px;">
                    <thead>
                        <tr style="background:color-mix(in srgb, var(--ink2) 8%, transparent);">
                            <th style="{{ $th }}">พนักงาน</th>
                            <th style="{{ $th }}">แผนก / ตำแหน่ง</th>
                            <th style="{{ $th }}">ติดต่อ</th>
                            <th style="{{ $th }} text-align:center;">สถานะ</th>
                            <th style="{{ $th }} text-align:center;">POS</th>
                            <th style="{{ $th }} text-align:right;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                            <tr style="{{ $row }}">
                                <td style="{{ $td }}">
                                    <a href="{{ route('seller.staff.show', $employee) }}" style="display:flex; align-items:center; gap:10px; text-decoration:none; color:var(--ink);">
                                        <span class="tp-tile" style="width:38px; height:38px; border-radius:50%; font-size:15px;" aria-hidden="true">{{ mb_strtoupper(mb_substr($employee->first_name_th ?: $employee->first_name, 0, 1)) }}</span>
                                        <span>
                                            <span style="display:block; font-weight:700;">{{ $employee->full_name_th ?: $employee->full_name }}</span>
                                            <span class="tp-num" style="display:block; font-size:11px; color:var(--ink2);">{{ $employee->employee_id }}@if($employee->nickname) · ({{ $employee->nickname }})@endif</span>
                                        </span>
                                    </a>
                                </td>
                                <td style="{{ $td }}">
                                    <div>{{ $employee->department->name ?? '—' }}</div>
                                    <div style="font-size:11.5px; color:var(--ink2);">{{ $employee->position->title ?? '—' }}</div>
                                </td>
                                <td style="{{ $td }}">
                                    @if($employee->mobile_phone)<div class="tp-num">{{ $employee->mobile_phone }}</div>@endif
                                    @if($employee->personal_email)<div style="font-size:11.5px; color:var(--ink2); overflow-wrap:anywhere;">{{ $employee->personal_email }}</div>@endif
                                    @if(! $employee->mobile_phone && ! $employee->personal_email)<span style="color:var(--ink2);">—</span>@endif
                                </td>
                                <td style="{{ $td }} text-align:center;">@include('seller.staff.partials.status', ['status' => $employee->employment_status])</td>
                                <td style="{{ $td }} text-align:center;">
                                    <x-seller-kit.pill :tone="($employee->posAssignment && $employee->posAssignment->is_active) ? 'ok' : 'muted'">{{ ($employee->posAssignment && $employee->posAssignment->is_active) ? '🔑 มี PIN' : 'ไม่ได้ใช้' }}</x-seller-kit.pill>
                                </td>
                                <td style="{{ $td }} text-align:right; white-space:nowrap;">
                                    <a href="{{ route('seller.staff.show', $employee) }}" class="tp-btn tp-btn-sm">ดู</a>
                                    <a href="{{ route('seller.staff.edit', $employee) }}" class="tp-btn tp-btn-sm">แก้ไข</a>
                                    <form method="POST" action="{{ route('seller.staff.destroy', $employee) }}" style="display:inline;"
                                          onsubmit="return confirm('ลบพนักงาน {{ addslashes($employee->full_name) }} ออกจากร้าน? (เก็บประวัติไว้ในระบบ)');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="tp-btn tp-btn-sm" style="color:var(--tp-bad, #d9534f);">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($employees->hasPages())
                <div style="padding:14px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">{{ $employees->withQueryString()->links() }}</div>
            @endif
        @else
            <x-seller-kit.empty icon="👥" title="ยังไม่มีพนักงาน" text="เพิ่มพนักงานคนแรก แล้วตั้งรหัส PIN ให้ใช้เข้าเครื่อง POS ได้ทันที">
                <a href="{{ route('seller.staff.create') }}" class="tp-btn tp-btn-primary tp-btn-sm">＋ เพิ่มพนักงาน</a>
            </x-seller-kit.empty>
        @endif
    </div>
</div>
@endsection
