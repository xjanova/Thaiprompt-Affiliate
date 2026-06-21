{{-- หน้าเพิ่มหมวดหมู่การทำนาย — ธีม V4 "นวลทองคำ" (Nuan Gold Clay) --}}
@extends('layouts.admin-v4')

@section('title', 'เพิ่มหมวดหมู่การทำนาย')

@section('content')
{{-- คอนเทนเนอร์หลัก: ผูก Alpine สำหรับพรีวิวสด + auto-slug + นับตัวอักษร --}}
<div x-data="fortuneCategoryCreate()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · หมวดหมู่การทำนาย
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                เพิ่มหมวดหมู่การทำนาย 🔮
            </h1>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            {{-- ลิงก์กลับไปหน้ารายการ (ห่อ Route::has กันพัง) --}}
            @if(Route::has('admin.fortune.categories.index'))
            <a href="{{ route('admin.fortune.categories.index') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-arrow-left"></i>&nbsp; กลับรายการ
            </a>
            @endif
            {{-- ปุ่มบันทึก (ผูกกับฟอร์มด้วย attribute form) --}}
            <button type="submit" form="fortuneCategoryForm" class="tp-btn tp-btn-primary tp-btn-sm">
                <i class="fas fa-floppy-disk"></i>&nbsp; บันทึกหมวดหมู่
            </button>
        </div>
    </div>

    {{-- ===== แจ้งเตือน validation รวม (ถ้ามี) ===== --}}
    @if($errors->any())
    <div class="tp-card" style="padding:16px 18px; border-left:4px solid #d9534f;">
        <div style="display:flex; align-items:center; gap:10px; color:#d9534f; font-weight:700;">
            <i class="fas fa-triangle-exclamation"></i>
            <span>กรอกข้อมูลไม่ครบหรือไม่ถูกต้อง กรุณาตรวจสอบช่องที่มีเครื่องหมายด้านล่าง</span>
        </div>
    </div>
    @endif

    {{-- ===== เลย์เอาต์ 2 คอลัมน์: ฟอร์ม (ซ้าย) + พรีวิว (ขวา) ===== --}}
    <div style="display:grid; grid-template-columns:minmax(330px,1.7fr) minmax(280px,1fr); gap:18px; align-items:start;">

        {{-- ---------- คอลัมน์ฟอร์ม ---------- --}}
        <div class="tp-card" style="padding:24px;">
            {{-- ฟอร์มหลัก: คง action/method/@csrf/ชื่อ field ทุกตัวตามของเดิม 100% --}}
            <form id="fortuneCategoryForm" action="{{ route('admin.fortune.categories.store') }}" method="POST">
                @csrf

                {{-- หัวข้อ section: ข้อมูลพื้นฐาน --}}
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-tag" style="color:var(--deep1);"></i>&nbsp; ข้อมูลพื้นฐาน
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px;">

                    {{-- ชื่อหมวดหมู่ (required) --}}
                    <div>
                        <label for="fc-name" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            ชื่อหมวดหมู่ <span style="color:#d9534f;">*</span>
                        </label>
                        <div class="tp-well tp-input">
                            <input type="text" id="fc-name" name="name" required
                                   value="{{ old('name') }}" placeholder="ความรัก"
                                   x-model="form.name"
                                   style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                        </div>
                        @error('name')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                    {{-- Slug (auto-gen ได้จากปุ่ม) --}}
                    <div>
                        <label for="fc-slug" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            Slug <span class="tp-muted" style="font-weight:400;">(เว้นว่างได้)</span>
                        </label>
                        <div style="display:flex; gap:8px; align-items:stretch;">
                            <div class="tp-well tp-input" style="flex:1;">
                                <input type="text" id="fc-slug" name="slug"
                                       value="{{ old('slug') }}" placeholder="khwam-rak"
                                       x-model="form.slug"
                                       style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                            </div>
                            {{-- ปุ่มสร้าง slug จากชื่อ (client-side ล้วน) --}}
                            <button type="button" class="tp-icon-btn" title="สร้าง slug จากชื่อ" @click="generateSlug()">
                                <i class="fas fa-wand-magic-sparkles" style="color:var(--deep1);"></i>
                            </button>
                        </div>
                        @error('slug')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                    {{-- Icon (Emoji) --}}
                    <div>
                        <label for="fc-icon" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            ไอคอน (Emoji)
                        </label>
                        <div class="tp-well tp-input">
                            <input type="text" id="fc-icon" name="icon"
                                   value="{{ old('icon') }}" placeholder="💕" maxlength="50"
                                   x-model="form.icon"
                                   style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                        </div>
                        {{-- ตัวเลือก emoji ยอดนิยม (เติมลง field) --}}
                        <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
                            <template x-for="e in emojiPresets" :key="e">
                                <button type="button" class="tp-raise"
                                        @click="form.icon = e"
                                        style="border:none; cursor:pointer; width:34px; height:34px; border-radius:9px; background:var(--surf); font-size:16px;"
                                        x-text="e"></button>
                            </template>
                        </div>
                        @error('icon')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                    {{-- สี (Hex) (required) --}}
                    <div>
                        <label for="fc-color" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            สีประจำหมวด (Hex) <span style="color:#d9534f;">*</span>
                        </label>
                        <div style="display:flex; gap:8px; align-items:stretch;">
                            {{-- color picker จริง — sync กับ field hex ผ่าน Alpine --}}
                            <input type="color" :value="normalizedColor" @input="form.color = $event.target.value"
                                   title="เลือกสี"
                                   style="width:46px; height:46px; padding:0; border:none; border-radius:11px; box-shadow:var(--inset-sm); cursor:pointer; background:transparent;">
                            <div class="tp-well tp-input" style="flex:1;">
                                {{-- ช่องเก็บค่าจริงที่ส่งไป backend คือ name=color --}}
                                <input type="text" id="fc-color" name="color" required
                                       value="{{ old('color', '#C9A24B') }}" placeholder="#C9A24B" maxlength="20"
                                       x-model="form.color"
                                       style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px; font-family:'Sora',monospace;">
                            </div>
                        </div>
                        @error('color')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                </div>

                {{-- เส้นคั่น --}}
                <div class="tp-divider" style="margin:22px 0;"></div>

                {{-- หัวข้อ section: คำอธิบาย & AI --}}
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-robot" style="color:var(--deep1);"></i>&nbsp; คำอธิบาย & บริบท AI
                </div>

                <div style="display:flex; flex-direction:column; gap:16px;">

                    {{-- คำอธิบาย --}}
                    <div>
                        <label for="fc-desc" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            คำอธิบาย
                        </label>
                        <div class="tp-well tp-input">
                            <textarea id="fc-desc" name="description" rows="3"
                                      placeholder="อธิบายสั้น ๆ เกี่ยวกับหมวดหมู่นี้"
                                      x-model="form.description"
                                      style="width:100%; background:transparent; border:none; outline:none; resize:vertical; color:var(--ink); font-size:14px; line-height:1.6;">{{ old('description') }}</textarea>
                        </div>
                        @error('description')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                    {{-- Prompt Context --}}
                    <div>
                        <label for="fc-prompt" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            Prompt Context <span class="tp-muted" style="font-weight:400;">(บริบทสำหรับ AI)</span>
                        </label>
                        <div class="tp-well tp-input">
                            <textarea id="fc-prompt" name="prompt_context" rows="3"
                                      placeholder="บริบทเพิ่มเติมสำหรับ AI ในหมวดหมู่นี้"
                                      style="width:100%; background:transparent; border:none; outline:none; resize:vertical; color:var(--ink); font-size:14px; line-height:1.6;">{{ old('prompt_context') }}</textarea>
                        </div>
                        <p class="tp-muted" style="margin-top:6px; font-size:11.5px;">
                            บริบทที่จะส่งให้ AI เพื่อให้ทำนายได้ตรงกับหมวดหมู่
                        </p>
                        @error('prompt_context')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                    {{-- ตัวอย่างคำถาม --}}
                    <div>
                        <label for="fc-examples" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            ตัวอย่างคำถาม <span class="tp-muted" style="font-weight:400;">(บรรทัดละ 1 คำถาม)</span>
                        </label>
                        <div class="tp-well tp-input">
                            <textarea id="fc-examples" name="example_questions" rows="3"
                                      placeholder="ใส่ตัวอย่างคำถาม แยกบรรทัดละ 1 คำถาม"
                                      style="width:100%; background:transparent; border:none; outline:none; resize:vertical; color:var(--ink); font-size:14px; line-height:1.6;">{{ old('example_questions') }}</textarea>
                        </div>
                        <p class="tp-muted" style="margin-top:6px; font-size:11.5px;">
                            ตัวอย่างคำถามสำหรับผู้ใช้ (แยกบรรทัดละ 1 คำถาม)
                        </p>
                        @error('example_questions')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                </div>

                {{-- เส้นคั่น --}}
                <div class="tp-divider" style="margin:22px 0;"></div>

                {{-- หัวข้อ section: การตั้งค่า --}}
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-sliders" style="color:var(--deep1);"></i>&nbsp; การตั้งค่า
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; align-items:end;">

                    {{-- ลำดับ (required) --}}
                    <div>
                        <label for="fc-order" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            ลำดับการแสดง <span style="color:#d9534f;">*</span>
                        </label>
                        <div class="tp-well tp-input">
                            <input type="number" id="fc-order" name="order" required min="0"
                                   value="{{ old('order', 0) }}"
                                   style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                        </div>
                        @error('order')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                    {{-- เปิดใช้งาน (checkbox switch) --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            สถานะ
                        </label>
                        {{-- toggle switch แบบ V4 — checkbox จริง name=is_active value=1 --}}
                        <label class="tp-raise" style="display:flex; align-items:center; gap:12px; cursor:pointer; padding:11px 14px; border-radius:12px; background:var(--surf);">
                            <input type="checkbox" name="is_active" value="1"
                                   x-model="form.is_active"
                                   {{ old('is_active', true) ? 'checked' : '' }}
                                   style="width:18px; height:18px; accent-color:var(--accent1); cursor:pointer;">
                            <span style="font-size:13.5px; color:var(--ink); font-weight:600;"
                                  x-text="form.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'">เปิดใช้งาน</span>
                            <span class="tp-pill" :class="form.is_active ? 'tp-pill-gold' : ''"
                                  style="margin-left:auto;"
                                  x-text="form.is_active ? 'Active' : 'Off'"></span>
                        </label>
                        @error('is_active')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                </div>

                {{-- ปุ่ม action ล่าง --}}
                <div class="tp-divider" style="margin:24px 0 20px;"></div>
                <div style="display:flex; flex-wrap:wrap; justify-content:flex-end; gap:10px;">
                    @if(Route::has('admin.fortune.categories.index'))
                    <a href="{{ route('admin.fortune.categories.index') }}" class="tp-btn">
                        <i class="fas fa-xmark"></i>&nbsp; ยกเลิก
                    </a>
                    @endif
                    <button type="submit" class="tp-btn tp-btn-primary">
                        <i class="fas fa-floppy-disk"></i>&nbsp; บันทึกหมวดหมู่
                    </button>
                </div>

            </form>
        </div>

        {{-- ---------- คอลัมน์พรีวิวสด ---------- --}}
        <div class="tp-card" style="padding:22px; position:sticky; top:18px;">
            <div class="tp-section-h" style="margin-bottom:16px;">
                <i class="fas fa-eye" style="color:var(--deep1);"></i>&nbsp; ตัวอย่างหมวดหมู่
            </div>

            {{-- การ์ดพรีวิว — ไอคอน/สี/ชื่อ อัปเดตสดจากฟอร์ม --}}
            <div class="tp-inset" style="border-radius:16px; padding:20px; display:flex; flex-direction:column; gap:14px;">
                <div style="display:flex; align-items:center; gap:14px;">
                    {{-- ไทล์ไอคอน — พื้นใช้สีที่เลือก --}}
                    <div style="width:58px; height:58px; border-radius:16px; display:flex; align-items:center; justify-content:center; font-size:28px; box-shadow:var(--raise);"
                         :style="`background:${normalizedColor}22; border:1.5px solid ${normalizedColor}55;`">
                        <span x-text="form.icon || '🔮'"></span>
                    </div>
                    <div style="min-width:0;">
                        <div class="tp-num" style="font-size:18px; font-weight:800; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"
                             x-text="form.name || 'ชื่อหมวดหมู่'"></div>
                        <div class="tp-muted" style="font-size:11.5px; font-family:'Sora',monospace;"
                             x-text="form.slug || 'slug-อัตโนมัติ'"></div>
                    </div>
                </div>

                {{-- คำอธิบายพรีวิว --}}
                <div class="tp-muted" style="font-size:12.5px; line-height:1.6; min-height:20px;"
                     x-text="form.description || 'คำอธิบายหมวดหมู่จะแสดงตรงนี้…'"></div>

                {{-- แถบสี + สถานะ --}}
                <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                    <span style="display:inline-flex; align-items:center; gap:7px; font-size:11.5px; color:var(--ink2);">
                        <span style="width:16px; height:16px; border-radius:5px; box-shadow:var(--inset-sm);"
                              :style="`background:${normalizedColor};`"></span>
                        <span style="font-family:'Sora',monospace;" x-text="form.color || '#------'"></span>
                    </span>
                    <span class="tp-pill" :class="form.is_active ? 'tp-pill-gold' : 'tp-pill-soft'"
                          x-text="form.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'"></span>
                </div>
            </div>

            {{-- เคล็ดลับ --}}
            <div class="tp-inset-sm" style="margin-top:16px; border-radius:12px; padding:13px 15px;">
                <div style="font-size:11.5px; color:var(--ink2); line-height:1.7;">
                    <i class="fas fa-lightbulb" style="color:var(--deep1);"></i>
                    <strong style="color:var(--ink);">เคล็ดลับ:</strong>
                    ตั้งชื่อให้สั้น กระชับ และเลือกสีที่ต่างจากหมวดอื่นเพื่อให้ผู้ใช้แยกแยะได้ง่าย
                    หากเว้น Slug ว่าง ระบบจะสร้างให้อัตโนมัติ
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // ===== Alpine component: ฟอร์มเพิ่มหมวดหมู่การทำนาย =====
    // จัดการพรีวิวสด + auto-slug + sync color picker (logic ฝั่ง client ล้วน ไม่มี AJAX)
    function fortuneCategoryCreate() {
        return {
            // สถานะฟอร์ม — ใช้ค่า old() เป็นค่าเริ่มต้นเพื่อให้พรีวิวตรงหลัง validation fail
            form: {
                name: @js(old('name', '')),
                slug: @js(old('slug', '')),
                icon: @js(old('icon', '')),
                color: @js(old('color', '#C9A24B')),
                description: @js(old('description', '')),
                is_active: @js((bool) old('is_active', true)),
            },

            // emoji ยอดนิยมสำหรับหมวดหมู่ดูดวง
            emojiPresets: ['💕', '💰', '💼', '🏠', '🩺', '📚', '⭐', '🔮', '🌙', '🍀'],

            // คืนค่าสีที่ใช้ได้กับ <input type=color> เสมอ (กันค่าที่ยังไม่ใช่ hex สมบูรณ์)
            get normalizedColor() {
                const c = (this.form.color || '').trim();
                // ต้องเป็น #RRGGBB เท่านั้นถึงจะใช้กับ color picker ได้ ไม่งั้น fallback สีทอง
                return /^#[0-9a-fA-F]{6}$/.test(c) ? c : '#C9A24B';
            },

            // สร้าง slug จากชื่อ (รองรับทั้งอังกฤษและไทย — ไทยจะ fallback เป็น timestamp ถ้าว่าง)
            generateSlug() {
                const name = (this.form.name || '').trim();
                if (!name) {
                    this.$dispatch('notify', { type: 'warning', message: 'กรุณากรอกชื่อหมวดหมู่ก่อนสร้าง Slug' });
                    return;
                }
                let slug = name
                    .toLowerCase()
                    .replace(/[^a-z0-9฀-๿\s-]/g, '') // คงตัวอักษร/ตัวเลข/ไทย/ช่องว่าง/ขีด
                    .replace(/\s+/g, '-')                       // ช่องว่าง -> ขีด
                    .replace(/-+/g, '-')                        // ยุบขีดซ้ำ
                    .replace(/^-+|-+$/g, '');                   // ตัดขีดหัวท้าย
                if (!slug) {
                    slug = 'category-' + Date.now();
                }
                this.form.slug = slug;
                this.$dispatch('notify', { type: 'success', message: 'สร้าง Slug เรียบร้อย: ' + slug });
            },
        };
    }
</script>
@endpush
