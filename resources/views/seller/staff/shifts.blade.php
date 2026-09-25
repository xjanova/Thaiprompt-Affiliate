@extends('layouts.seller-v4')

@section('title', 'กะการทำงาน')

@php
    // สีกะที่ผู้ขายตั้งเอง — ใช้ได้เฉพาะรหัส hex ที่ถูกต้อง
    $safeColor = fn ($c) => (is_string($c) && preg_match('/^#[0-9a-fA-F]{3,8}$/', $c)) ? $c : 'var(--accent1)';
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="กะการทำงาน" icon="🕘" crumb="ร้านค้า · พนักงาน" subtitle="กำหนดเวลาเข้า–ออกงาน เวลาพัก และเบี้ยเลี้ยงต่อกะ" />

    @include('seller.staff.partials.nav')

    <x-seller-kit.errors />

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
        <form method="POST" action="{{ route('seller.staff.shifts.store') }}" class="tp-card" style="flex:1 1 280px; display:flex; flex-direction:column; gap:12px;"
              x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <div class="tp-section-h">＋ เพิ่มกะ</div>
            <div>
                <label for="s-name" style="font-size:12.5px; font-weight:700;">ชื่อกะ <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                <input id="s-name" type="text" name="name" required maxlength="255" value="{{ old('name') }}" placeholder="เช่น กะเช้า" class="tp-input" style="margin-top:6px;">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <div>
                    <label for="s-start" style="font-size:12.5px; font-weight:700;">เริ่ม <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                    <input id="s-start" type="time" name="start_time" required value="{{ old('start_time', '08:00') }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
                <div>
                    <label for="s-end" style="font-size:12.5px; font-weight:700;">เลิก <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                    <input id="s-end" type="time" name="end_time" required value="{{ old('end_time', '17:00') }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <div>
                    <label for="s-break" style="font-size:12.5px; font-weight:700;">พัก (นาที)</label>
                    <input id="s-break" type="number" name="break_duration_minutes" min="0" value="{{ old('break_duration_minutes', 60) }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
                <div>
                    <label for="s-allow" style="font-size:12.5px; font-weight:700;">เบี้ยเลี้ยง/กะ (บาท)</label>
                    <input id="s-allow" type="number" name="daily_allowance" min="0" step="0.01" value="{{ old('daily_allowance', 0) }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
            </div>
            <div>
                <label for="s-color" style="font-size:12.5px; font-weight:700;">สีกะ</label>
                <input id="s-color" type="color" name="color" value="{{ old('color', '#e6b347') }}" class="tp-input" style="margin-top:6px; height:44px; padding:4px;">
            </div>
            <div style="font-size:11.5px; color:var(--ink2);">ถ้าเวลาเลิกน้อยกว่าเวลาเริ่ม ระบบถือว่าเป็นกะข้ามคืนให้อัตโนมัติ</div>
            <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving" :style="{ opacity: saving ? .6 : 1 }">บันทึกกะ</button>
        </form>

        <div class="tp-card" style="flex:2 1 360px; padding:0; overflow:hidden;">
            <div class="tp-section-h" style="padding:16px 18px;">🕘 กะทั้งหมด ({{ number_format($shifts->count()) }})</div>
            @forelse($shifts as $shift)
                <div style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    <span style="width:14px; height:40px; border-radius:8px; flex:none; background:{{ $safeColor($shift->color) }}; box-shadow:var(--raise);" aria-hidden="true"></span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700;">{{ $shift->name }}</div>
                        <div class="tp-num" style="font-size:12px; color:var(--ink2);">
                            {{ $shift->time_range }}@if($shift->crosses_midnight) (ข้ามคืน)@endif
                            · {{ number_format((float) $shift->working_hours, 1) }} ชม.
                            @if((float) $shift->daily_allowance > 0) · เบี้ยเลี้ยง ฿{{ number_format((float) $shift->daily_allowance, 0) }}@endif
                        </div>
                    </div>
                    <x-seller-kit.pill :tone="$shift->is_active ? 'ok' : 'muted'">{{ $shift->is_active ? 'ใช้งาน' : 'ปิด' }}</x-seller-kit.pill>
                    <form method="POST" action="{{ route('seller.staff.shifts.destroy', $shift) }}" onsubmit="return confirm('ลบกะ {{ addslashes($shift->name) }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-btn tp-btn-sm" style="color:var(--tp-bad, #d9534f);">🗑️</button>
                    </form>
                </div>
            @empty
                <x-seller-kit.empty icon="🕘" title="ยังไม่มีกะการทำงาน" text="เพิ่มกะ เช่น กะเช้า 08:00–17:00 เพื่อใช้ลงเวลาพนักงาน" />
            @endforelse
        </div>
    </div>
</div>
@endsection
