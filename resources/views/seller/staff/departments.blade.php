@extends('layouts.seller-v4')

@section('title', 'แผนกของร้าน')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="แผนก" icon="🏢" crumb="ร้านค้า · พนักงาน" subtitle="จัดกลุ่มพนักงาน เช่น ฝ่ายขาย ครัว คลังสินค้า" />

    @include('seller.staff.partials.nav')

    <x-seller-kit.errors />

    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;">
        <form method="POST" action="{{ route('seller.staff.departments.store') }}" class="tp-card" style="flex:1 1 280px; display:flex; flex-direction:column; gap:12px;"
              x-data="{ saving: false }" @submit="saving = true">
            @csrf
            <div class="tp-section-h">＋ เพิ่มแผนก</div>
            <div>
                <label for="d-name" style="font-size:12.5px; font-weight:700;">ชื่อแผนก <span style="color:var(--tp-bad, #d9534f);">*</span></label>
                <input id="d-name" type="text" name="name" required maxlength="255" value="{{ old('name') }}" placeholder="เช่น ฝ่ายขาย" class="tp-input" style="margin-top:6px;">
            </div>
            <div>
                <label for="d-code" style="font-size:12.5px; font-weight:700;">รหัสแผนก</label>
                <input id="d-code" type="text" name="code" maxlength="50" value="{{ old('code') }}" placeholder="SALES (เว้นว่างได้)" class="tp-input tp-num" style="margin-top:6px;">
            </div>
            <div>
                <label for="d-desc" style="font-size:12.5px; font-weight:700;">คำอธิบาย</label>
                <textarea id="d-desc" name="description" rows="2" class="tp-input" style="margin-top:6px; resize:vertical;">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving" :style="{ opacity: saving ? .6 : 1 }">บันทึกแผนก</button>
        </form>

        <div class="tp-card" style="flex:2 1 360px; padding:0; overflow:hidden;">
            <div class="tp-section-h" style="padding:16px 18px;">🏢 แผนกทั้งหมด ({{ number_format($departments->count()) }})</div>
            @forelse($departments as $dept)
                <div style="display:flex; align-items:center; gap:12px; padding:12px 18px; border-top:1px solid color-mix(in srgb, var(--ink2) 13%, transparent);">
                    <span class="tp-tile" style="width:38px; height:38px; font-size:16px;" aria-hidden="true">🏢</span>
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700;">{{ $dept->name }}</div>
                        <div style="font-size:11.5px; color:var(--ink2);"><span class="tp-num">{{ $dept->code }}</span> · พนักงาน {{ number_format((int) $dept->employees_count) }} คน</div>
                        @if($dept->description)<div style="font-size:12px; color:var(--ink2); margin-top:2px;">{{ $dept->description }}</div>@endif
                    </div>
                    <form method="POST" action="{{ route('seller.staff.departments.destroy', $dept) }}" onsubmit="return confirm('ลบแผนก {{ addslashes($dept->name) }}? (ลบได้เฉพาะแผนกที่ไม่มีพนักงาน)');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-btn tp-btn-sm" style="color:var(--tp-bad, #d9534f);">🗑️</button>
                    </form>
                </div>
            @empty
                <x-seller-kit.empty icon="🏢" title="ยังไม่มีแผนก" text="เพิ่มแผนกแรกทางซ้าย หรือเพิ่มพนักงานได้เลย ระบบจะสร้างแผนก “ทั่วไป” ให้" />
            @endforelse
        </div>
    </div>
</div>
@endsection
