{{-- ฟอร์มเพิ่ม/แก้ไขหมวดหมู่ฝัน — ธีม V4 "นวลทองคำ" --}}
@extends('layouts.admin-v4')

@section('title', $pageTitle)

@section('content')
{{-- คอนเทนเนอร์หลัก เรียงแนวตั้ง --}}
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ส่วนหัว: eyebrow + ชื่อหน้า ทางซ้าย / ปุ่มกลับทางขวา --}}
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:.78rem; letter-spacing:.04em; margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · ทำนายฝัน · หมวดหมู่
            </div>
            <h1 class="tp-num" style="font-size:1.6rem; margin:0;">{{ $pageTitle }}</h1>
        </div>
        <a href="{{ route('admin.fortune.horoscope-public.dream.categories') }}" class="tp-btn">
            <i class="fas fa-arrow-left"></i> กลับ
        </a>
    </div>

    {{-- กล่องแจ้งข้อผิดพลาดจาก validation --}}
    @if($errors->any())
        <div class="tp-card" style="border-left:4px solid #d9534f;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px;">
                <i class="fas fa-circle-exclamation" style="color:#d9534f;"></i>
                <strong style="color:#d9534f;">กรุณาตรวจสอบข้อมูล</strong>
            </div>
            <ul style="margin:0; padding-left:20px; color:var(--ink2); font-size:.9rem; line-height:1.7;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ฟอร์มหลัก — คง action/method/@csrf/@method เดิม 100% --}}
    <form action="{{ $category
            ? route('admin.fortune.horoscope-public.dream.categories.update', $category)
            : route('admin.fortune.horoscope-public.dream.categories.store') }}"
          method="POST"
          x-data="dreamCategoryForm('{{ old('color_hex', $category?->color_hex ?? '#818cf8') }}')">
        @csrf
        @if($category)
            @method('PUT')
        @endif

        {{-- การ์ดข้อมูลหมวดหมู่ --}}
        <div class="tp-card" style="margin-bottom:18px;">
            <div class="tp-section-h" style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
                <i class="fas fa-folder-open" style="color:var(--accent1);"></i>
                <span>ข้อมูลหมวดหมู่</span>
            </div>

            {{-- กริดฟิลด์ 2 คอลัมน์บนจอใหญ่ --}}
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:18px;">

                {{-- ชื่อหมวดหมู่ (ไทย) --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:.82rem; font-weight:600; margin-bottom:8px;">
                        ชื่อหมวดหมู่ (ไทย) <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well">
                        <input type="text" name="name_th" value="{{ old('name_th', $category?->name_th) }}"
                               placeholder="เช่น สัตว์, คน, ธรรมชาติ"
                               class="tp-input" required>
                    </div>
                </div>

                {{-- Slug (URL) --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:.82rem; font-weight:600; margin-bottom:8px;">
                        Slug (URL)
                    </label>
                    <div class="tp-well">
                        <input type="text" name="slug" value="{{ old('slug', $category?->slug) }}"
                               placeholder="สร้างอัตโนมัติถ้าปล่อยว่าง"
                               class="tp-input">
                    </div>
                </div>

                {{-- ไอคอน (Emoji) --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:.82rem; font-weight:600; margin-bottom:8px;">
                        ไอคอน (Emoji)
                    </label>
                    <div class="tp-well">
                        <input type="text" name="icon" value="{{ old('icon', $category?->icon) }}"
                               placeholder="🐍"
                               class="tp-input">
                    </div>
                </div>

                {{-- สี (hex) — color picker + ช่องแสดงค่าซิงค์ผ่าน Alpine --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:.82rem; font-weight:600; margin-bottom:8px;">
                        สี (hex) <span style="color:#d9534f;">*</span>
                    </label>
                    <div style="display:flex; gap:10px; align-items:stretch;">
                        <input type="color" name="color_hex" x-model="colorHex"
                               style="width:48px; height:44px; border:none; border-radius:12px; box-shadow:var(--inset-sm); background:var(--surf); cursor:pointer; padding:4px;">
                        <div class="tp-well" style="flex:1;">
                            <input type="text" class="tp-input" x-model="colorHex" readonly
                                   style="font-family:monospace;">
                        </div>
                    </div>
                </div>

                {{-- ลำดับ --}}
                <div>
                    <label class="tp-muted" style="display:block; font-size:.82rem; font-weight:600; margin-bottom:8px;">
                        ลำดับ <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well">
                        <input type="number" name="sort_order" value="{{ old('sort_order', $category?->sort_order ?? 0) }}"
                               min="0" class="tp-input" required>
                    </div>
                </div>
            </div>

            {{-- คำอธิบาย --}}
            <div style="margin-top:18px;">
                <label class="tp-muted" style="display:block; font-size:.82rem; font-weight:600; margin-bottom:8px;">
                    คำอธิบาย
                </label>
                <div class="tp-well">
                    <textarea name="description_th" rows="2"
                              placeholder="อธิบายหมวดหมู่สั้นๆ"
                              class="tp-input" style="resize:vertical;">{{ old('description_th', $category?->description_th) }}</textarea>
                </div>
            </div>

            {{-- เปิดใช้งาน — hidden 0 + checkbox 1 คงค่าเดิม --}}
            <div class="tp-inset-sm" style="margin-top:18px; padding:14px 16px; border-radius:14px;">
                <label style="display:flex; align-items:center; gap:12px; cursor:pointer; margin:0;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           style="width:20px; height:20px; accent-color:var(--accent1); cursor:pointer;"
                           {{ old('is_active', $category?->is_active ?? true) ? 'checked' : '' }}>
                    <span style="font-weight:600; color:var(--ink);">
                        <i class="fas fa-toggle-on" style="color:var(--accent1); margin-right:6px;"></i>เปิดใช้งาน
                    </span>
                </label>
            </div>
        </div>

        {{-- แถวปุ่ม: ยกเลิก / บันทึก --}}
        <div style="display:flex; justify-content:flex-end; gap:12px; flex-wrap:wrap;">
            <a href="{{ route('admin.fortune.horoscope-public.dream.categories') }}" class="tp-btn">
                <i class="fas fa-xmark"></i> ยกเลิก
            </a>
            <button type="submit" class="tp-btn tp-btn-primary">
                <i class="fas fa-floppy-disk"></i> {{ $category ? 'บันทึก' : 'เพิ่มหมวดหมู่' }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
{{-- scripts stack: ตรรกะ Alpine ซิงค์ค่าสี hex ระหว่าง color picker กับช่องข้อความ --}}
<script>
    // คอมโพเนนต์ Alpine สำหรับฟอร์มหมวดหมู่ฝัน
    function dreamCategoryForm(initialColor) {
        return {
            // ค่าสีปัจจุบัน (ผูกกับทั้ง input color และ input text)
            colorHex: initialColor || '#818cf8',
        };
    }
</script>
@endpush
