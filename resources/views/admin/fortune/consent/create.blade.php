@extends('layouts.admin-v4')

@section('title', 'อัปโหลดรูปกติกาใหม่')

@section('content')
@php
    // ขอบเขตการใช้งานรูป (ใช้ตอนไหนของขั้นตอน)
    $scopes = [
        'both' => '🔁 อ่านกติกา + ตอนยกเลิก',
        'all' => '🌐 ทุกจังหวะ (กติกา + ยกเลิก + หมดเวลา)',
        'consent' => '📜 เฉพาะตอนอ่านกติกา',
        'cancel' => '🚫 เฉพาะตอนยกเลิก (เบี้ยว)',
        'expire' => '⏰ เฉพาะตอนหมดเวลาเอง (นุ่ม)',
    ];
@endphp

<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ส่วนหัวหน้า: eyebrow + ชื่อหน้า + ปุ่มกลับ --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · กติกา
            </div>
            <h1 class="tp-num" style="font-size:26px;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-cloud-arrow-up" style="color:var(--accent1);"></i>
                อัปโหลดรูปกติกาใหม่
            </h1>
        </div>
        <a href="{{ route('admin.fortune.consent.index') }}" class="tp-btn tp-btn-sm">
            <i class="fas fa-arrow-left"></i> กลับ
        </a>
    </div>

    {{-- กล่องแสดงข้อผิดพลาดจาก validation --}}
    @if($errors->any())
        <div class="tp-card tp-inset" style="border-left:4px solid #d9534f;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;color:#d9534f;font-weight:600;">
                <i class="fas fa-triangle-exclamation"></i> พบข้อผิดพลาด
            </div>
            <ul style="margin:0;padding-left:20px;color:var(--ink2);font-size:14px;line-height:1.7;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ฟอร์มอัปโหลด: คง action/method/@csrf/multipart/field เดิม 100% --}}
    <form action="{{ route('admin.fortune.consent.store') }}" method="POST" enctype="multipart/form-data"
          x-data="{ preview: null, fileName: '' }">
        @csrf

        <div style="display:grid;grid-template-columns:1fr;gap:18px;align-items:start;">

            {{-- การ์ดข้อมูลรูป --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-circle-info" style="color:var(--accent2);"></i> ข้อมูลรูป
                </div>

                {{-- ชื่อรูป (ภายใน) --}}
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px;">
                        ชื่อรูป (ภายใน) <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input">
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                               placeholder="เช่น รูปเตือนกติกา #1"
                               style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;">
                    </div>
                </div>

                {{-- คำอธิบาย --}}
                <div style="margin-bottom:16px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px;">
                        คำอธิบาย
                    </label>
                    <div class="tp-well tp-input">
                        <input type="text" name="description" value="{{ old('description') }}" maxlength="500"
                               placeholder="คำอธิบายเพิ่มเติม (ไม่บังคับ)"
                               style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;">
                    </div>
                </div>

                {{-- ใช้รูปนี้ตอนไหน --}}
                <div style="margin-bottom:4px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px;">
                        ใช้รูปนี้ตอนไหน <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <select name="usage_scope"
                                style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;cursor:pointer;">
                            @foreach($scopes as $val => $label)
                                <option value="{{ $val }}" @selected(old('usage_scope', 'both') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- การ์ดอัปโหลดรูปภาพ + พรีวิว --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-image" style="color:var(--accent1);"></i> รูปภาพ
                </div>

                <label style="display:block;font-size:13px;font-weight:600;color:var(--ink);margin-bottom:6px;">
                    เลือกรูป (PNG/JPG, สูงสุด 10MB) <span style="color:#d9534f;">*</span>
                </label>

                {{-- พื้นที่อัปโหลด: input file ของจริงซ่อน, แสดงปุ่มสวยแทน (คง name/accept/required เดิม) --}}
                <label class="tp-well tp-inset" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:26px 16px;cursor:pointer;text-align:center;border:1.5px dashed var(--sd);">
                    <i class="fas fa-cloud-arrow-up" style="font-size:30px;color:var(--accent2);"></i>
                    <div style="font-size:14px;color:var(--ink);font-weight:600;" x-text="fileName || 'คลิกเพื่อเลือกรูป หรือลากไฟล์มาวาง'"></div>
                    <div class="tp-muted" style="font-size:12px;">รองรับ PNG / JPG ขนาดไม่เกิน 10MB</div>
                    <input type="file" name="image" accept="image/png,image/jpeg" required
                           @change="fileName = $event.target.files.length ? $event.target.files[0].name : ''; preview = $event.target.files.length ? URL.createObjectURL($event.target.files[0]) : null"
                           style="display:none;">
                </label>

                {{-- พรีวิวรูปที่เลือก --}}
                <template x-if="preview">
                    <div style="margin-top:14px;">
                        <div class="tp-muted" style="font-size:12px;margin-bottom:6px;">ตัวอย่างรูป</div>
                        <img :src="preview" class="tp-inset"
                             style="max-height:280px;width:auto;max-width:100%;border-radius:12px;display:block;">
                    </div>
                </template>
            </div>

            {{-- การ์ดตั้งค่าการแสดงผล --}}
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-sliders" style="color:var(--accent2);"></i> ตั้งค่าการแสดงผล
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;align-items:start;">

                    {{-- เปิดใช้งานทันที --}}
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px 14px;" class="tp-well tp-inset-sm">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))
                               style="width:18px;height:18px;accent-color:var(--accent1);cursor:pointer;">
                        <span style="font-size:14px;color:var(--ink);font-weight:600;">เปิดใช้งานทันที</span>
                    </label>

                    {{-- ลำดับ (sort) --}}
                    <div>
                        <label style="display:block;font-size:12px;color:var(--ink2);margin-bottom:6px;">ลำดับ (sort)</label>
                        <div class="tp-well tp-input">
                            <input type="number" name="sort_order" value="{{ old('sort_order') }}" min="0"
                                   placeholder="0"
                                   style="width:100%;background:transparent;border:0;outline:0;color:var(--ink);font-size:14px;">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ปุ่ม action --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-floppy-disk"></i> อัปโหลด
                </button>
                <a href="{{ route('admin.fortune.consent.index') }}" class="tp-btn">
                    <i class="fas fa-xmark"></i> ยกเลิก
                </a>
            </div>

        </div>
    </form>

</div>
@endsection
