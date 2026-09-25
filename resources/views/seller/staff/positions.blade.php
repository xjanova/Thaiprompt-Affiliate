@extends('layouts.seller-v4')

@section('title', 'ตำแหน่งงาน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="ตำแหน่งงาน" icon="💼" crumb="ร้านค้า · พนักงาน" subtitle="กำหนดตำแหน่งและช่วงเงินเดือน เช่น แคชเชียร์ พนักงานขาย ผู้จัดการร้าน" />

    @include('seller.staff.partials.nav')

    <x-seller-kit.errors />

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
        <form method="POST" action="{{ route('seller.staff.positions.store') }}" class="tp-card" style="flex:1 1 280px; display:flex; flex-direction:column; gap:12px;"
              x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <div class="tp-section-h">＋ เพิ่มตำแหน่ง</div>
            <div>
                <label for="p-title" style="font-size:12.5px; font-weight:700;">ชื่อตำแหน่ง <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                <input id="p-title" type="text" name="title" required maxlength="255" value="{{ old('title') }}" placeholder="เช่น แคชเชียร์" class="tp-input" style="margin-top:6px;">
            </div>
            <div>
                <label for="p-code" style="font-size:12.5px; font-weight:700;">รหัสตำแหน่ง</label>
                <input id="p-code" type="text" name="code" maxlength="50" value="{{ old('code') }}" placeholder="CASHIER (เว้นว่างได้)" class="tp-input tp-num" style="margin-top:6px;">
            </div>
            <div>
                <label for="p-dept" style="font-size:12.5px; font-weight:700;">แผนก</label>
                <select id="p-dept" name="department_id" class="tp-input" style="margin-top:6px;">
                    <option value="">— แผนก “ทั่วไป” —</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected((string) old('department_id') === (string) $dept->id)>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <div>
                    <label for="p-min" style="font-size:12.5px; font-weight:700;">เงินเดือนต่ำสุด</label>
                    <input id="p-min" type="number" name="min_salary" min="0" step="0.01" value="{{ old('min_salary') }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
                <div>
                    <label for="p-max" style="font-size:12.5px; font-weight:700;">เงินเดือนสูงสุด</label>
                    <input id="p-max" type="number" name="max_salary" min="0" step="0.01" value="{{ old('max_salary') }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
            </div>
            <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving" :style="{ opacity: saving ? .6 : 1 }">บันทึกตำแหน่ง</button>
        </form>

        <div class="tp-card" style="flex:2 1 360px; padding:0; overflow:hidden;">
            <div class="tp-section-h" style="padding:16px 18px;">💼 ตำแหน่งทั้งหมด ({{ number_format($positions->count()) }})</div>
            @forelse($positions as $pos)
                <div style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    <span class="tp-tile" style="width:38px; height:38px; font-size:16px;" aria-hidden="true">💼</span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700;">{{ $pos->title }}</div>
                        <div style="font-size:11.5px; color:var(--ink2);">
                            <span class="tp-num">{{ $pos->code }}</span> · {{ $pos->department->name ?? 'ไม่ระบุแผนก' }} · พนักงาน {{ number_format((int) $pos->employees_count) }} คน
                        </div>
                        @if((float) $pos->min_salary > 0 || (float) $pos->max_salary > 0)
                            <div class="tp-num" style="font-size:12px; margin-top:2px;">฿{{ number_format((float) $pos->min_salary, 0) }} – ฿{{ number_format((float) $pos->max_salary, 0) }}</div>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('seller.staff.positions.destroy', $pos) }}" onsubmit="return confirm('ลบตำแหน่ง {{ addslashes($pos->title) }}? (ลบได้เฉพาะตำแหน่งที่ไม่มีพนักงาน)');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-btn tp-btn-sm" style="color:var(--tp-bad, #d9534f);">🗑️</button>
                    </form>
                </div>
            @empty
                <x-seller-kit.empty icon="💼" title="ยังไม่มีตำแหน่ง" text="เพิ่มตำแหน่งแรกทางซ้าย" />
            @endforelse
        </div>
    </div>
</div>
@endsection
