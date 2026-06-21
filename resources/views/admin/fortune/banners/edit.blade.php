@extends('layouts.admin-v4')

@section('title', 'แก้ไขแบนเนอร์: ' . $banner->name)

@section('content')
{{-- คอนเทนเนอร์หลัก ธีม V4 นวลทองคำ --}}
<div style="display:flex;flex-direction:column;gap:18px;">

    {{-- ส่วนหัว: eyebrow + ชื่อหน้า + ปุ่มกลับ --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div>
            <div class="tp-muted" style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;margin-bottom:6px;">
                หลังบ้าน · ระบบดูดวง · แบนเนอร์ DM
            </div>
            <h1 class="tp-num" style="font-size:26px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px;">
                <i class="fas fa-pen-to-square" style="color:var(--accent1);"></i>
                แก้ไขแบนเนอร์
            </h1>
        </div>
        <a href="{{ route('admin.fortune.banners.index') }}" class="tp-btn tp-btn-sm">
            <i class="fas fa-arrow-left"></i> กลับ
        </a>
    </div>

    {{-- กล่องแจ้ง error จากการ validate --}}
    @if($errors->any())
        <div class="tp-card" style="padding:16px 18px;border-left:4px solid #d9534f;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;color:#d9534f;font-weight:700;">
                <i class="fas fa-triangle-exclamation"></i> พบข้อผิดพลาด
            </div>
            <ul style="margin:0;padding-left:20px;font-size:13.5px;color:var(--ink2);line-height:1.7;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- เลย์เอาต์ 2 คอลัมน์: ฟอร์มแก้ไข + รูปปัจจุบัน --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;align-items:start;">

        {{-- ===== ฟอร์มแก้ไขแบนเนอร์ ===== --}}
        <form action="{{ route('admin.fortune.banners.update', $banner) }}"
              method="POST"
              enctype="multipart/form-data"
              x-data="bannerEditForm()"
              class="tp-card"
              style="padding:24px;display:flex;flex-direction:column;gap:18px;">
            @csrf
            @method('PUT')

            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;">
                <i class="fas fa-sliders" style="color:var(--accent1);"></i>
                รายละเอียดแบนเนอร์
            </div>

            {{-- ชื่อแบนเนอร์ --}}
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:7px;">
                    ชื่อแบนเนอร์ <span style="color:#d9534f;">*</span>
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <input type="text" name="name" required maxlength="100"
                           value="{{ old('name', $banner->name) }}"
                           placeholder="ตั้งชื่อแบนเนอร์"
                           style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                </div>
            </div>

            {{-- คำอธิบาย --}}
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:7px;">
                    คำอธิบาย
                </label>
                <div class="tp-well tp-input" style="padding:0;">
                    <textarea name="description" rows="2" maxlength="500"
                              placeholder="คำอธิบายเพิ่มเติม (ไม่บังคับ)"
                              style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;resize:vertical;">{{ old('description', $banner->description) }}</textarea>
                </div>
            </div>

            {{-- เปลี่ยนรูป (อัปโหลดใหม่ optional) --}}
            <div>
                <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:7px;">
                    เปลี่ยนรูป <span class="tp-muted" style="font-weight:400;">(ไม่บังคับ)</span>
                </label>
                <div class="tp-well tp-inset-sm"
                     style="padding:16px;border:2px dashed var(--sd);border-radius:14px;text-align:center;cursor:pointer;transition:border-color .2s;"
                     @click="$refs.fileInput.click()">
                    <input type="file" name="image" accept="image/png,image/jpg,image/jpeg"
                           x-ref="fileInput"
                           @change="onFileChange($event)"
                           style="display:none;">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                        <i class="fas fa-cloud-arrow-up" style="font-size:26px;color:var(--accent1);"></i>
                        <span style="font-size:13px;color:var(--ink2);" x-text="fileName || 'คลิกเพื่อเลือกรูปใหม่ (PNG / JPG)'"></span>
                        <span class="tp-muted" style="font-size:11.5px;">เว้นไว้ถ้าไม่ต้องการเปลี่ยนรูป · สูงสุด 10MB</span>
                    </div>
                </div>

                {{-- พรีวิวรูปใหม่ที่เพิ่งเลือก --}}
                <template x-if="preview">
                    <div class="tp-inset" style="margin-top:12px;padding:10px;border-radius:14px;">
                        <p class="tp-muted" style="font-size:11.5px;margin:0 0 7px;">พรีวิวรูปใหม่:</p>
                        <img :src="preview" style="max-width:100%;max-height:260px;border-radius:10px;display:block;">
                    </div>
                </template>
            </div>

            {{-- ลำดับ + สถานะเปิดใช้งาน --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:end;">
                <div>
                    <label style="display:block;font-size:13px;font-weight:600;color:var(--ink2);margin-bottom:7px;">
                        ลำดับการแสดง
                    </label>
                    <div class="tp-well tp-input" style="padding:0;">
                        <input type="number" name="sort_order" min="0"
                               value="{{ old('sort_order', $banner->sort_order) }}"
                               style="width:100%;background:transparent;border:0;outline:0;padding:11px 14px;color:var(--ink);font-size:14px;">
                    </div>
                </div>

                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding-bottom:11px;">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $banner->is_active))
                           style="width:20px;height:20px;accent-color:var(--accent1);cursor:pointer;">
                    <span style="font-size:14px;color:var(--ink);font-weight:600;">เปิดใช้งาน</span>
                </label>
            </div>

            {{-- ปุ่ม action --}}
            <div class="tp-divider"></div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <button type="submit" class="tp-btn tp-btn-primary">
                    <i class="fas fa-floppy-disk"></i> บันทึก
                </button>
                <a href="{{ route('admin.fortune.banners.index') }}" class="tp-btn">
                    <i class="fas fa-xmark"></i> ยกเลิก
                </a>
            </div>
        </form>

        {{-- ===== รูปปัจจุบัน + สถิติ ===== --}}
        <div class="tp-card" style="padding:24px;display:flex;flex-direction:column;gap:14px;">
            <div class="tp-section-h" style="display:flex;align-items:center;gap:8px;">
                <i class="fas fa-image" style="color:var(--accent2);"></i>
                รูปปัจจุบัน
            </div>

            <div class="tp-inset" style="padding:12px;border-radius:14px;text-align:center;">
                <img src="{{ $banner->image_url }}"
                     alt="{{ $banner->name }}"
                     style="max-width:100%;max-height:300px;border-radius:10px;display:inline-block;">
            </div>

            {{-- ข้อมูลสรุปแบนเนอร์ --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;">
                <div class="tp-tile" style="padding:12px 14px;">
                    <div class="tp-muted" style="font-size:11px;margin-bottom:4px;">ขนาดภาพ</div>
                    <div class="tp-num" style="font-size:15px;font-weight:700;">{{ $banner->width }}×{{ $banner->height }}</div>
                </div>
                <div class="tp-tile" style="padding:12px 14px;">
                    <div class="tp-muted" style="font-size:11px;margin-bottom:4px;">ขนาดไฟล์</div>
                    <div class="tp-num" style="font-size:15px;font-weight:700;">{{ round(($banner->file_size ?? 0) / 1024) }} KB</div>
                </div>
                <div class="tp-tile" style="padding:12px 14px;">
                    <div class="tp-muted" style="font-size:11px;margin-bottom:4px;">ส่งไปแล้ว</div>
                    <div class="tp-num" style="font-size:15px;font-weight:700;">{{ number_format($banner->send_count) }} ครั้ง</div>
                </div>
            </div>

            {{-- สถานะปัจจุบัน --}}
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="tp-muted" style="font-size:12.5px;">สถานะ:</span>
                @if($banner->is_active)
                    <span class="tp-pill tp-pill-gold" style="font-size:12px;">
                        <i class="fas fa-circle-check"></i> เปิดใช้งาน
                    </span>
                @else
                    <span class="tp-pill tp-pill-soft" style="font-size:12px;color:#9a8f7c;">
                        <i class="fas fa-circle-pause"></i> ปิดใช้งาน
                    </span>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // Alpine component สำหรับฟอร์มแก้ไขแบนเนอร์ — จัดการพรีวิวรูปใหม่ + ชื่อไฟล์
    function bannerEditForm() {
        return {
            preview: null,
            fileName: '',
            // เมื่อเลือกไฟล์รูปใหม่ — อ่านเป็น data URL เพื่อโชว์พรีวิว
            onFileChange(e) {
                const f = e.target.files[0];
                if (!f) {
                    this.preview = null;
                    this.fileName = '';
                    return;
                }
                this.fileName = f.name;
                const r = new FileReader();
                r.onload = (ev) => { this.preview = ev.target.result; };
                r.readAsDataURL(f);
            }
        };
    }
</script>
@endpush
