{{-- ฟอร์มแก้ไขราศี — ธีม V4 "นวลทองคำ" --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- คอนเทนเนอร์หลัก จัดวางแนวตั้ง เว้นช่อง 18px --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ส่วนหัว: ซ้าย eyebrow + ชื่อราศี / ขวา ปุ่มกลับ --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · จัดการ 12 ราศี
            </div>
            <h1 class="tp-num" style="font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px;">
                <span style="font-size:28px;">{{ $zodiac->symbol_emoji }}</span>
                แก้ไขราศี{{ $zodiac->name_th }}
            </h1>
            <div class="tp-muted" style="font-size:13px;margin-top:4px;">
                {{ $zodiac->name_en }}@if($zodiac->date_range_display_th) · {{ $zodiac->date_range_display_th }}@endif
            </div>
        </div>
        <div>
            <a href="{{ route('admin.fortune.horoscope-public.zodiac.index') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-arrow-left"></i> กลับ
            </a>
        </div>
    </div>

    {{-- กล่องแจ้งข้อผิดพลาดการ validate --}}
    @if($errors->any())
        <div class="tp-card" style="border-left:4px solid #d9534f;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;color:#d9534f;font-weight:700;">
                <i class="fas fa-circle-exclamation"></i> พบข้อผิดพลาด
            </div>
            <ul style="margin:0;padding-left:20px;color:var(--ink2);font-size:13px;line-height:1.8;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ฟอร์มแก้ไขราศี (PUT) --}}
    <form action="{{ route('admin.fortune.horoscope-public.zodiac.update', $zodiac) }}" method="POST"
          style="display:flex;flex-direction:column;gap:18px;">
        @csrf
        @method('PUT')

        {{-- ===== ข้อมูลหลัก ===== --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i class="fas fa-id-card" style="color:var(--accent1);"></i> ข้อมูลหลัก
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                {{-- ชื่อราศี (ไทย) --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        ชื่อราศี (ไทย) <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well">
                        <input type="text" name="name_th" value="{{ old('name_th', $zodiac->name_th) }}"
                               class="tp-input" required>
                    </div>
                </div>

                {{-- ชื่อราศี (อังกฤษ) --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        ชื่อราศี (อังกฤษ) <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well">
                        <input type="text" name="name_en" value="{{ old('name_en', $zodiac->name_en) }}"
                               class="tp-input" required>
                    </div>
                </div>

                {{-- Symbol Emoji --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        สัญลักษณ์ (Emoji) <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well">
                        <input type="text" name="symbol_emoji" value="{{ old('symbol_emoji', $zodiac->symbol_emoji) }}"
                               class="tp-input" required>
                    </div>
                </div>

                {{-- ช่วงวันเกิดแสดงผล (ไทย) --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        ช่วงวันเกิดแสดงผล (ไทย)
                    </label>
                    <div class="tp-well">
                        <input type="text" name="date_range_display_th"
                               value="{{ old('date_range_display_th', $zodiac->date_range_display_th) }}"
                               placeholder="เช่น 21 มี.ค. — 19 เม.ย." class="tp-input">
                    </div>
                </div>

                {{-- ธาตุ --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        ธาตุ <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well" style="padding:0;">
                        <select name="element" class="tp-input"
                                style="padding:10px 12px;background:transparent;border:none;width:100%;">
                            @foreach(['ไฟ', 'ดิน', 'ลม', 'น้ำ'] as $el)
                                <option value="{{ $el }}" {{ old('element', $zodiac->element) === $el ? 'selected' : '' }}>{{ $el }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- ดาวประจำราศี --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        ดาวประจำราศี <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well">
                        <input type="text" name="ruling_planet" value="{{ old('ruling_planet', $zodiac->ruling_planet) }}"
                               class="tp-input" required>
                    </div>
                </div>

                {{-- คุณภาพ (Quality) --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        คุณภาพ (Quality)
                    </label>
                    <div class="tp-well" style="padding:0;">
                        <select name="quality" class="tp-input"
                                style="padding:10px 12px;background:transparent;border:none;width:100%;">
                            <option value="">-- ไม่ระบุ --</option>
                            @foreach(['Cardinal', 'Fixed', 'Mutable'] as $q)
                                <option value="{{ $q }}" {{ old('quality', $zodiac->quality) === $q ? 'selected' : '' }}>{{ $q }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- ลำดับ --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        ลำดับ <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well">
                        <input type="number" name="sort_order" value="{{ old('sort_order', $zodiac->sort_order) }}"
                               min="0" class="tp-input" required>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== สีและ Gradient ===== --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i class="fas fa-palette" style="color:var(--accent1);"></i> สีและ Gradient
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
                {{-- สีหลัก (hex) — color picker + ช่องแสดงค่า (disabled, sync ด้วย JS) --}}
                <div x-data="{ hex: '{{ old('color_hex', $zodiac->color_hex) }}' }">
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        สีหลัก (hex) <span style="color:#d9534f;">*</span>
                    </label>
                    <div style="display:flex;gap:8px;align-items:stretch;">
                        <input type="color" name="color_hex" x-model="hex"
                               style="width:46px;height:42px;border-radius:10px;border:none;box-shadow:var(--inset-sm);cursor:pointer;padding:3px;background:var(--surf);">
                        <div class="tp-well" style="flex:1;">
                            <input type="text" class="tp-input" x-model="hex" disabled>
                        </div>
                    </div>
                </div>

                {{-- Gradient From --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        Gradient From
                    </label>
                    <div class="tp-well">
                        <input type="text" name="gradient_from" value="{{ old('gradient_from', $zodiac->gradient_from) }}"
                               placeholder="#818cf8" class="tp-input">
                    </div>
                </div>

                {{-- Gradient To --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        Gradient To
                    </label>
                    <div class="tp-well">
                        <input type="text" name="gradient_to" value="{{ old('gradient_to', $zodiac->gradient_to) }}"
                               placeholder="#6366f1" class="tp-input">
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== คำอธิบาย ===== --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
                <i class="fas fa-file-lines" style="color:var(--accent1);"></i> คำอธิบาย
            </div>

            <div style="display:flex;flex-direction:column;gap:16px;">
                {{-- คำอธิบายราศี --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        คำอธิบายราศี
                    </label>
                    <div class="tp-well">
                        <textarea name="description_th" rows="3" class="tp-input" style="resize:vertical;">{{ old('description_th', $zodiac->description_th) }}</textarea>
                    </div>
                </div>

                {{-- บุคลิกภาพ --}}
                <div>
                    <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                        บุคลิกภาพ
                    </label>
                    <div class="tp-well">
                        <textarea name="personality_th" rows="3" class="tp-input" style="resize:vertical;">{{ old('personality_th', $zodiac->personality_th) }}</textarea>
                    </div>
                </div>

                {{-- จุดแข็ง / จุดอ่อน --}}
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">
                    <div>
                        <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                            จุดแข็ง
                        </label>
                        <div class="tp-well">
                            <textarea name="strengths_th" rows="2" class="tp-input" style="resize:vertical;">{{ old('strengths_th', $zodiac->strengths_th) }}</textarea>
                        </div>
                    </div>
                    <div>
                        <label class="tp-muted" style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">
                            จุดอ่อน
                        </label>
                        <div class="tp-well">
                            <textarea name="weaknesses_th" rows="2" class="tp-input" style="resize:vertical;">{{ old('weaknesses_th', $zodiac->weaknesses_th) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== สถานะ ===== --}}
        <div class="tp-card">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                <i class="fas fa-toggle-on" style="color:var(--accent1);"></i> สถานะการแสดงผล
            </div>
            {{-- hidden กัน is_active ไม่ถูกส่งเมื่อไม่ติ๊ก --}}
            <input type="hidden" name="is_active" value="0">
            <label style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                <input type="checkbox" name="is_active" value="1"
                       style="width:20px;height:20px;cursor:pointer;accent-color:var(--accent1);"
                       {{ old('is_active', $zodiac->is_active) ? 'checked' : '' }}>
                <div>
                    <div style="font-weight:700;color:var(--ink);">เปิดใช้งาน</div>
                    <div class="tp-muted" style="font-size:12px;">แสดงราศีนี้ในหน้า frontend</div>
                </div>
            </label>
        </div>

        {{-- ===== ปุ่ม action ===== --}}
        <div style="display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;">
            <a href="{{ route('admin.fortune.horoscope-public.zodiac.index') }}" class="tp-btn">
                <i class="fas fa-xmark"></i> ยกเลิก
            </a>
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i> บันทึก
            </button>
        </div>
    </form>
</div>
@endsection
