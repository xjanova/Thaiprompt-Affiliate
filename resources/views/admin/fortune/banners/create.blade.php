{{-- หน้าอัพโหลดแบนเนอร์ DM — ธีม V4 "นวลทองคำ" (Nuan Gold Clay) --}}
@extends('layouts.admin-v4')

@section('title', 'อัพโหลดแบนเนอร์ใหม่')

@section('content')
{{-- คอนเทนเนอร์หลัก: ผูก Alpine สำหรับพรีวิวรูปสด + นับ field --}}
<div x-data="fortuneBannerCreate()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== HEADER ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · ระบบดูดวง · แบนเนอร์ DM
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                อัพโหลดแบนเนอร์ใหม่ 🖼️
            </h1>
            <p class="tp-muted" style="font-size:12.5px; margin:6px 0 0;">
                ระบบจะ resize เป็น 1280px wide + แปลงเป็น JPEG quality 85 อัตโนมัติ
            </p>
        </div>
        <div style="display:flex; align-items:center; gap:9px;">
            {{-- ลิงก์กลับไปหน้ารายการ (ห่อ Route::has กันพัง) --}}
            @if(Route::has('admin.fortune.banners.index'))
            <a href="{{ route('admin.fortune.banners.index') }}" class="tp-btn tp-btn-sm">
                <i class="fas fa-arrow-left"></i>&nbsp; กลับรายการ
            </a>
            @endif
            {{-- ปุ่มอัพโหลด (ผูกกับฟอร์มด้วย attribute form) --}}
            <button type="submit" form="fortuneBannerForm" class="tp-btn tp-btn-primary tp-btn-sm">
                <i class="fas fa-cloud-arrow-up"></i>&nbsp; อัพโหลด
            </button>
        </div>
    </div>

    {{-- ===== แจ้งเตือน validation รวม (ถ้ามี) ===== --}}
    @if($errors->any())
    <div class="tp-card" style="padding:16px 18px; border-left:4px solid #d9534f;">
        <div style="display:flex; align-items:flex-start; gap:10px; color:#d9534f; font-weight:700;">
            <i class="fas fa-triangle-exclamation" style="margin-top:3px;"></i>
            <div>
                <div style="margin-bottom:6px;">กรอกข้อมูลไม่ครบหรือไม่ถูกต้อง กรุณาตรวจสอบรายการด้านล่าง</div>
                <ul style="margin:0; padding-left:18px; font-weight:500; font-size:12.5px; line-height:1.7;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    {{-- ===== เลย์เอาต์ 2 คอลัมน์: ฟอร์ม (ซ้าย) + พรีวิว (ขวา) ===== --}}
    <div style="display:grid; grid-template-columns:minmax(330px,1.7fr) minmax(280px,1fr); gap:18px; align-items:start;">

        {{-- ---------- คอลัมน์ฟอร์ม ---------- --}}
        <div class="tp-card" style="padding:24px;">
            {{-- ฟอร์มหลัก: คง action/method/@csrf/enctype multipart + ชื่อ field ทุกตัวตามของเดิม 100% --}}
            <form id="fortuneBannerForm"
                  action="{{ route('admin.fortune.banners.store') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf

                {{-- หัวข้อ section: ข้อมูลพื้นฐาน --}}
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-tag" style="color:var(--deep1);"></i>&nbsp; ข้อมูลพื้นฐาน
                </div>

                <div style="display:flex; flex-direction:column; gap:16px;">

                    {{-- ชื่อแบนเนอร์ (required) --}}
                    <div>
                        <label for="fb-name" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            ชื่อแบนเนอร์ <span style="color:#d9534f;">*</span>
                        </label>
                        <div class="tp-well tp-input">
                            <input type="text" id="fb-name" name="name" required maxlength="100"
                                   value="{{ old('name') }}"
                                   placeholder="เช่น: แบนเนอร์ #5 — promo สงกรานต์"
                                   x-model="form.name"
                                   style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                        </div>
                        @error('name')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                    {{-- คำอธิบาย (optional) --}}
                    <div>
                        <label for="fb-desc" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            คำอธิบาย <span class="tp-muted" style="font-weight:400;">(เว้นว่างได้)</span>
                        </label>
                        <div class="tp-well tp-input">
                            <textarea id="fb-desc" name="description" rows="2" maxlength="500"
                                      placeholder="อธิบายสั้น ๆ เกี่ยวกับแบนเนอร์นี้"
                                      x-model="form.description"
                                      style="width:100%; background:transparent; border:none; outline:none; resize:vertical; color:var(--ink); font-size:14px; line-height:1.6;">{{ old('description') }}</textarea>
                        </div>
                        @error('description')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                </div>

                {{-- เส้นคั่น --}}
                <div class="tp-divider" style="margin:22px 0;"></div>

                {{-- หัวข้อ section: ไฟล์รูป --}}
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-image" style="color:var(--deep1);"></i>&nbsp; ไฟล์รูปแบนเนอร์
                </div>

                {{-- กล่องเลือกไฟล์ (dropzone-style) — คง <input type=file> เดิม 100% --}}
                <div>
                    <label for="fb-image" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                        ไฟล์รูป <span style="color:#d9534f;">*</span>
                    </label>

                    {{-- พื้นที่ dropzone — คลิกทั้งกล่องเพื่อเปิด file picker --}}
                    <label for="fb-image" class="tp-inset"
                           style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:26px 18px; border-radius:16px; cursor:pointer; text-align:center; border:1.5px dashed var(--sd);"
                           :style="preview ? 'border-color:var(--accent1);' : ''">
                        <div style="width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; box-shadow:var(--raise); background:var(--surf); color:var(--deep1);">
                            <i class="fas fa-cloud-arrow-up"></i>
                        </div>
                        <div style="font-size:13.5px; font-weight:700; color:var(--ink);">
                            <span x-show="!filename">คลิกเพื่อเลือกรูป</span>
                            <span x-show="filename" x-text="filename" x-cloak style="font-family:'Sora',monospace;"></span>
                        </div>
                        <div class="tp-muted" style="font-size:11.5px;">
                            รองรับ PNG, JPG, JPEG • สูงสุด 10 MB • แนะนำ aspect 16:9 (เช่น 1920×1080, 1280×720)
                        </div>

                        {{-- input file จริง — name=image, accept เดิม, @change เรียก preview --}}
                        <input type="file" id="fb-image" name="image" required
                               accept="image/png,image/jpg,image/jpeg"
                               @change="onFileChange($event)"
                               style="display:none;">
                    </label>

                    @error('image')
                    <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                        <i class="fas fa-circle-exclamation"></i> {{ $message }}
                    </p>
                    @enderror
                </div>

                {{-- เส้นคั่น --}}
                <div class="tp-divider" style="margin:22px 0;"></div>

                {{-- หัวข้อ section: การตั้งค่า --}}
                <div class="tp-section-h" style="margin-bottom:16px;">
                    <i class="fas fa-sliders" style="color:var(--deep1);"></i>&nbsp; การตั้งค่า
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:16px; align-items:end;">

                    {{-- ลำดับการแสดง --}}
                    <div>
                        <label for="fb-order" style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            ลำดับการแสดง
                        </label>
                        <div class="tp-well tp-input">
                            <input type="number" id="fb-order" name="sort_order" min="0"
                                   value="{{ old('sort_order', 0) }}"
                                   style="width:100%; background:transparent; border:none; outline:none; color:var(--ink); font-size:14px;">
                        </div>
                        <p class="tp-muted" style="margin-top:6px; font-size:11.5px;">เลขน้อย = มาก่อน</p>
                        @error('sort_order')
                        <p style="margin:6px 2px 0; font-size:12px; color:#d9534f;">
                            <i class="fas fa-circle-exclamation"></i> {{ $message }}
                        </p>
                        @enderror
                    </div>

                    {{-- เปิดใช้งานทันที (toggle switch) — checkbox จริง name=is_active value=1 --}}
                    <div>
                        <label style="display:block; font-size:12.5px; font-weight:600; color:var(--ink2); margin-bottom:7px;">
                            สถานะ
                        </label>
                        <label class="tp-raise" style="display:flex; align-items:center; gap:12px; cursor:pointer; padding:11px 14px; border-radius:12px; background:var(--surf);">
                            <input type="checkbox" name="is_active" value="1" checked
                                   x-model="form.is_active"
                                   style="width:18px; height:18px; accent-color:var(--accent1); cursor:pointer;">
                            <span style="font-size:13.5px; color:var(--ink); font-weight:600;"
                                  x-text="form.is_active ? 'เปิดใช้งานทันที' : 'ปิดใช้งาน'">เปิดใช้งานทันที</span>
                            <span class="tp-pill" :class="form.is_active ? 'tp-pill-gold' : 'tp-pill-soft'"
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
                    @if(Route::has('admin.fortune.banners.index'))
                    <a href="{{ route('admin.fortune.banners.index') }}" class="tp-btn">
                        <i class="fas fa-xmark"></i>&nbsp; ยกเลิก
                    </a>
                    @endif
                    <button type="submit" class="tp-btn tp-btn-primary">
                        <i class="fas fa-cloud-arrow-up"></i>&nbsp; อัพโหลดแบนเนอร์
                    </button>
                </div>

            </form>
        </div>

        {{-- ---------- คอลัมน์พรีวิวสด ---------- --}}
        <div class="tp-card" style="padding:22px; position:sticky; top:18px;">
            <div class="tp-section-h" style="margin-bottom:16px;">
                <i class="fas fa-eye" style="color:var(--deep1);"></i>&nbsp; ตัวอย่างแบนเนอร์
            </div>

            {{-- กล่องพรีวิวรูป — แสดงรูปที่เลือกแบบสด (FileReader) --}}
            <div class="tp-inset" style="border-radius:16px; padding:14px;">
                {{-- มีรูป: แสดงรูปจริง --}}
                <template x-if="preview">
                    <div style="display:flex; flex-direction:column; gap:10px;">
                        <div style="border-radius:12px; overflow:hidden; box-shadow:var(--raise); background:var(--bg);">
                            <img :src="preview" alt="ตัวอย่างแบนเนอร์"
                                 style="display:block; width:100%; max-height:280px; object-fit:contain;">
                        </div>
                        <div class="tp-muted" style="font-size:11.5px; font-family:'Sora',monospace; word-break:break-all;"
                             x-text="filename"></div>
                    </div>
                </template>

                {{-- ยังไม่มีรูป: empty state --}}
                <template x-if="!preview">
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; padding:34px 12px; text-align:center;">
                        <i class="fas fa-image" style="font-size:30px; color:var(--ink2); opacity:.55;"></i>
                        <div class="tp-muted" style="font-size:12.5px;">
                            ยังไม่ได้เลือกรูป<br>เลือกไฟล์เพื่อดูตัวอย่างที่นี่
                        </div>
                    </div>
                </template>
            </div>

            {{-- สรุปข้อมูลพรีวิว --}}
            <div class="tp-inset-sm" style="margin-top:16px; border-radius:12px; padding:14px 15px; display:flex; flex-direction:column; gap:9px;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                    <span class="tp-muted" style="font-size:11.5px;">ชื่อ</span>
                    <span style="font-size:12.5px; font-weight:700; color:var(--ink); text-align:right; max-width:62%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                          x-text="form.name || '—'"></span>
                </div>
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                    <span class="tp-muted" style="font-size:11.5px;">สถานะ</span>
                    <span class="tp-pill" :class="form.is_active ? 'tp-pill-gold' : 'tp-pill-soft'"
                          x-text="form.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'"></span>
                </div>
            </div>

            {{-- เคล็ดลับ --}}
            <div class="tp-inset-sm" style="margin-top:14px; border-radius:12px; padding:13px 15px;">
                <div style="font-size:11.5px; color:var(--ink2); line-height:1.7;">
                    <i class="fas fa-lightbulb" style="color:var(--deep1);"></i>
                    <strong style="color:var(--ink);">เคล็ดลับ:</strong>
                    ใช้รูปอัตราส่วน 16:9 เพื่อให้แสดงผลใน DM ได้สวยที่สุด ระบบจะย่อขนาดและบีบอัดเป็น JPEG ให้อัตโนมัติ
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // ===== Alpine component: ฟอร์มอัพโหลดแบนเนอร์ DM =====
    // จัดการพรีวิวรูปสดด้วย FileReader + sync ชื่อ/สถานะ (logic ฝั่ง client ล้วน ไม่มี AJAX)
    function fortuneBannerCreate() {
        return {
            // สถานะพรีวิว
            preview: null,    // data URL ของรูปที่เลือก
            filename: null,   // ชื่อไฟล์ที่เลือก

            // สถานะฟอร์ม — ใช้ค่า old() เป็นค่าเริ่มต้นเพื่อให้พรีวิวตรงหลัง validation fail
            form: {
                name: @js(old('name', '')),
                description: @js(old('description', '')),
                is_active: true,
            },

            // อ่านไฟล์ที่เลือกแล้วแสดงพรีวิวสด (logic เดิม)
            onFileChange(e) {
                const file = e.target.files[0];
                if (!file) { this.preview = null; this.filename = null; return; }
                this.filename = file.name;
                const reader = new FileReader();
                reader.onload = (ev) => { this.preview = ev.target.result; };
                reader.readAsDataURL(file);
            },
        };
    }
</script>
@endpush
