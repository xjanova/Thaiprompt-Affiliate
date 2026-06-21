@extends('layouts.admin-v4')

@section('title', 'แก้ไขหมวดหมู่การทำนาย')

@section('content')
{{-- ─────────────────────────────────────────────────────────────
     หน้าแก้ไขหมวดหมู่การทำนาย — ธีม V4 "นวลทองคำ"
     คงฟอร์มเดิม 100%: action=update + @method(PUT) + @csrf
     ทุก field name เดิม (name/slug/icon/color/description/
     prompt_context/example_questions/order/is_active) + pre-fill old()
───────────────────────────────────────────────────────────────── --}}
<div x-data="categoryEditForm()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ========== HEADER ========== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · แก้ไขหมวดหมู่
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                แก้ไข: {{ $category->name }} 🔮
            </h1>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            {{-- กลับไปหน้ารายการ --}}
            <a href="{{ route('admin.fortune.categories.index') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-arrow-left"></i> รายการหมวดหมู่
            </a>
        </div>
    </div>

    {{-- ========== ฟอร์มหลัก ========== --}}
    <form action="{{ route('admin.fortune.categories.update', $category) }}" method="POST"
          @submit="saving = true">
        @csrf
        @method('PUT')

        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:18px; align-items:start;">

            {{-- ───── คอลัมน์ซ้าย: ข้อมูลหลัก ───── --}}
            <div class="tp-card" style="padding:24px;">
                <div class="tp-section-h" style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
                    <span class="tp-tile" style="width:38px; height:38px; border-radius:12px; font-size:16px; display:inline-flex; align-items:center; justify-content:center;">
                        <i class="fas fa-tag"></i>
                    </span>
                    <span class="tp-num" style="font-size:16px; font-weight:700;">ข้อมูลหมวดหมู่</span>
                </div>

                {{-- ชื่อหมวดหมู่ --}}
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                        ชื่อหมวดหมู่ <span style="color:#d9534f;">*</span>
                    </label>
                    <div class="tp-well tp-input">
                        <input type="text" name="name" required
                               x-model="name"
                               value="{{ old('name', $category->name) }}"
                               placeholder="เช่น ความรัก, การเงิน, สุขภาพ">
                    </div>
                </div>

                {{-- Slug --}}
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                        Slug <span class="tp-muted" style="font-weight:400;">(ตัวระบุใน URL)</span>
                    </label>
                    <div class="tp-well tp-input">
                        <input type="text" name="slug"
                               value="{{ old('slug', $category->slug) }}"
                               placeholder="love, money, health">
                    </div>
                </div>

                {{-- ไอคอน + สี (สองช่องในแถวเดียว) --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            ไอคอน <span class="tp-muted" style="font-weight:400;">(emoji/class)</span>
                        </label>
                        <div class="tp-well tp-input">
                            <input type="text" name="icon"
                                   x-model="icon"
                                   value="{{ old('icon', $category->icon) }}"
                                   placeholder="❤️ หรือ fa-heart">
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            สี
                        </label>
                        <div class="tp-well tp-input">
                            <input type="text" name="color"
                                   x-model="color"
                                   value="{{ old('color', $category->color) }}"
                                   placeholder="#3B82F6">
                        </div>
                    </div>
                </div>

                {{-- ลำดับ + เปิดใช้งาน --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; align-items:end;">
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            ลำดับการแสดง <span style="color:#d9534f;">*</span>
                        </label>
                        <div class="tp-well tp-input">
                            <input type="number" name="order" required min="0"
                                   value="{{ old('order', $category->order) }}"
                                   placeholder="0">
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            สถานะ
                        </label>
                        {{-- toggle สถานะเปิดใช้งาน — คง name=is_active value=1 เดิม --}}
                        <label class="tp-inset" style="display:flex; align-items:center; gap:10px; padding:11px 14px; border-radius:12px; cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1"
                                   x-model="isActive"
                                   {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                                   style="width:18px; height:18px; accent-color:var(--accent1); cursor:pointer;">
                            <span class="tp-pill"
                                  :class="isActive ? 'tp-pill-gold' : 'tp-pill-soft'"
                                  x-text="isActive ? 'เปิดใช้งาน' : 'ปิดใช้งาน'"
                                  style="font-size:12px;">เปิดใช้งาน</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ───── คอลัมน์ขวา: เนื้อหา + AI ───── --}}
            <div style="display:flex; flex-direction:column; gap:18px;">

                {{-- พรีวิวหมวดหมู่ (live ผ่าน Alpine) --}}
                <div class="tp-card" style="padding:20px;">
                    <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px; margin-bottom:12px;">
                        ตัวอย่างการแสดงผล
                    </div>
                    <div class="tp-inset" style="display:flex; align-items:center; gap:14px; padding:16px; border-radius:14px;">
                        <span :style="`background:${tileBg()}; box-shadow:var(--raise);`"
                              style="width:52px; height:52px; border-radius:14px; display:inline-flex; align-items:center; justify-content:center; font-size:24px; color:#fff; flex-shrink:0;">
                            <span x-text="icon || '🔮'"></span>
                        </span>
                        <div style="min-width:0;">
                            <div class="tp-num" style="font-size:17px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                                 x-text="name || 'ชื่อหมวดหมู่'"></div>
                            <div style="margin-top:4px;">
                                <span class="tp-pill"
                                      :class="isActive ? 'tp-pill-gold' : 'tp-pill-soft'"
                                      x-text="isActive ? 'เปิดใช้งาน' : 'ปิดใช้งาน'"
                                      style="font-size:11px;">เปิดใช้งาน</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- เนื้อหา + บริบท AI --}}
                <div class="tp-card" style="padding:24px;">
                    <div class="tp-section-h" style="display:flex; align-items:center; gap:10px; margin-bottom:18px;">
                        <span class="tp-tile" style="width:38px; height:38px; border-radius:12px; font-size:16px; display:inline-flex; align-items:center; justify-content:center;">
                            <i class="fas fa-wand-magic-sparkles"></i>
                        </span>
                        <span class="tp-num" style="font-size:16px; font-weight:700;">เนื้อหา &amp; บริบท AI</span>
                    </div>

                    {{-- คำอธิบาย --}}
                    <div style="margin-bottom:16px;">
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            คำอธิบาย
                        </label>
                        <div class="tp-well tp-input">
                            <textarea name="description" rows="3"
                                      placeholder="คำอธิบายสั้นๆ ของหมวดหมู่">{{ old('description', $category->description) }}</textarea>
                        </div>
                    </div>

                    {{-- Prompt Context --}}
                    <div style="margin-bottom:16px;">
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            <i class="fas fa-robot" style="color:var(--deep1);"></i> Prompt Context
                        </label>
                        <div class="tp-well tp-input">
                            <textarea name="prompt_context" rows="3"
                                      placeholder="บริบทเพิ่มเติมสำหรับ AI ในหมวดหมู่นี้">{{ old('prompt_context', $category->prompt_context) }}</textarea>
                        </div>
                        <p class="tp-muted" style="margin:7px 0 0; font-size:11.5px;">
                            บริบทที่จะส่งให้ AI เพื่อให้ทำนายได้ตรงกับหมวดหมู่
                        </p>
                    </div>

                    {{-- ตัวอย่างคำถาม --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            <i class="fas fa-circle-question" style="color:var(--deep1);"></i> ตัวอย่างคำถาม
                        </label>
                        <div class="tp-well tp-input">
                            <textarea name="example_questions" rows="3"
                                      placeholder="ใส่ตัวอย่างคำถาม แยกบรรทัดละ 1 คำถาม">{{ old('example_questions', $category->example_questions) }}</textarea>
                        </div>
                        <p class="tp-muted" style="margin:7px 0 0; font-size:11.5px;">
                            ตัวอย่างคำถามสำหรับผู้ใช้ (แยกบรรทัดละ 1 คำถาม)
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========== แถบปุ่มบันทึก (sticky bottom) ========== --}}
        <div class="tp-card" style="margin-top:18px; padding:16px 20px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px;">
            <div class="tp-muted" style="font-size:12px; display:flex; align-items:center; gap:7px;">
                <i class="fas fa-circle-info"></i>
                <span>แก้ไขเมื่อ {{ $category->updated_at?->format('d/m/Y H:i') ?? '-' }} น.</span>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <a href="{{ route('admin.fortune.categories.index') }}" class="tp-btn tp-btn-sm">
                    ยกเลิก
                </a>
                <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving">
                    <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข'"></span>
                </button>
            </div>
        </div>
    </form>

    {{-- ========== โซนอันตราย: ลบหมวดหมู่ ========== --}}
    @if(Route::has('admin.fortune.categories.destroy'))
    <div class="tp-card" style="padding:20px 24px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px; box-shadow:var(--inset);">
        <div>
            <div class="tp-num" style="font-size:14px; font-weight:700; color:#d9534f;">
                <i class="fas fa-triangle-exclamation"></i> ลบหมวดหมู่นี้
            </div>
            <div class="tp-muted" style="font-size:12px; margin-top:4px;">
                การลบจะทำให้หมวดหมู่นี้หายจากระบบ (สามารถกู้คืนได้จากฐานข้อมูล)
            </div>
        </div>
        {{-- ฟอร์มลบแยก — คง action destroy + @method(DELETE) --}}
        <form action="{{ route('admin.fortune.categories.destroy', $category) }}" method="POST"
              @submit.prevent="confirmDelete($event.target)">
            @csrf
            @method('DELETE')
            <button type="submit" class="tp-btn tp-btn-sm"
                    style="color:#d9534f; border-color:#d9534f;">
                <i class="fas fa-trash-can"></i> ลบหมวดหมู่
            </button>
        </form>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
    /**
     * Alpine component สำหรับฟอร์มแก้ไขหมวดหมู่
     * - live preview (ไอคอน/ชื่อ/สี/สถานะ)
     * - กันกดบันทึกซ้ำ (saving flag)
     * - ยืนยันก่อนลบ
     */
    function categoryEditForm() {
        return {
            // ค่าเริ่มต้นจากข้อมูลเดิม (pre-fill ผ่าน old())
            name: @json(old('name', $category->name)),
            icon: @json(old('icon', $category->icon)),
            color: @json(old('color', $category->color)),
            isActive: {{ old('is_active', $category->is_active) ? 'true' : 'false' }},
            saving: false,

            /**
             * คำนวณสีพื้นไทล์พรีวิว — ใช้สีที่กรอก ถ้าว่างใช้ทองธีม
             */
            tileBg() {
                const c = (this.color || '').trim();
                // ตรวจรูปแบบสี hex/rgb/ชื่อสี อย่างหลวมๆ
                if (c) {
                    return c;
                }
                return 'linear-gradient(135deg, var(--accent1), var(--accent2))';
            },

            /**
             * ยืนยันก่อนลบหมวดหมู่ (destructive — ต้องยืนยัน)
             */
            confirmDelete(formEl) {
                if (window.confirm('ยืนยันการลบหมวดหมู่ "' + (this.name || '') + '" ?')) {
                    formEl.submit();
                } else {
                    this.$dispatch('notify', { type: 'info', message: 'ยกเลิกการลบแล้ว' });
                }
            },
        };
    }
</script>
@endpush
