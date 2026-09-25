{{--
 | ฟอร์มแบนเนอร์หน้าร้าน (ใช้ร่วม create/edit) — ธีม V4
 | ตัวแปร: $banner (StoreBanner|null), $gradientOptions (class => label), $categories (หมวดหลัก), $action, $method ('POST'|'PUT')
 | ฟิลด์ตรงกับ StorefrontSettingsController@storeBanner/updateBanner
 --}}
@php
    $b = $banner ?? null;
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $hint = 'font-size:11.5px; color:var(--ink2); margin-top:5px;';
    $bad = 'var(--tp-bad,#d9534f)';
    $currentGradient = old('gradient', $b?->gradient ?? '');
    $location = old('location', $b?->location ?? 'homepage');
@endphp

@if($errors->any())
    <div class="tp-card" style="padding:14px 18px; border-left:4px solid {{ $bad }};">
        <div style="font-weight:700; color:{{ $bad }}; margin-bottom:6px;"><i class="fas fa-circle-exclamation"></i> บันทึกไม่สำเร็จ</div>
        <ul style="margin:0; padding-left:18px; font-size:13px;">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data"
      style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;"
      x-data="{ busy: false, location: @js($location), preview: null, removeImage: false, grad: @js((string) $currentGradient),
                title: @js((string) old('title', $b?->title ?? '')), subtitle: @js((string) old('subtitle', $b?->subtitle ?? '')), cta: @js((string) old('cta_text', $b?->cta_text ?? '')),
                pick(e) { const f = e.target.files[0]; if (f && f.size > 5 * 1024 * 1024) { alert('ขนาดไฟล์ต้องไม่เกิน 5MB'); e.target.value = ''; return; } this.preview = f ? URL.createObjectURL(f) : null; if (f) this.removeImage = false; } }"
      @submit="busy = true">
    @csrf
    @if(($method ?? 'POST') === 'PUT')
        @method('PUT')
    @endif

    {{-- ===== คอลัมน์หลัก ===== --}}
    <div style="flex:2 1 440px; min-width:0; display:flex; flex-direction:column; gap:16px;">
        {{-- ตัวอย่างสด --}}
        <div class="tp-card" style="padding:0; overflow:hidden;">
            <div style="position:relative; aspect-ratio:16/6; background:linear-gradient(120deg,var(--accent1),var(--accent2));">
                <template x-if="preview"><img :src="preview" alt="" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;"></template>
                @if($b?->image_url)
                    <img x-show="!preview && !removeImage" src="{{ $b->image_url }}" alt="" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;">
                @endif
                <div style="position:absolute; inset:0; background:linear-gradient(90deg, rgba(0,0,0,.45), transparent 70%);"></div>
                <div style="position:absolute; left:22px; top:50%; transform:translateY(-50%); color:var(--tp-on-accent,#fff); max-width:70%;">
                    <div style="font-weight:800; font-size:clamp(16px,3vw,24px); line-height:1.2;" x-text="title || 'หัวข้อแบนเนอร์'"></div>
                    <div style="font-size:13px; opacity:.9; margin-top:4px;" x-text="subtitle"></div>
                    <span x-show="cta" class="inline-block" style="margin-top:10px; padding:7px 14px; border-radius:10px; background:var(--tp-on-accent,#fff); color:var(--deep1); font-weight:700; font-size:12.5px;" x-text="cta"></span>
                </div>
            </div>
            <div style="padding:8px 14px; font-size:11.5px; color:var(--ink2);">ตัวอย่างโดยประมาณ — หน้าร้านจริงจะปรับขนาดตามจอ</div>
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-heading" style="color:var(--accent1);"></i> ข้อความบนแบนเนอร์</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr)); gap:14px;">
                <div style="grid-column:1 / -1;">
                    <label style="{{ $lbl }}">หัวข้อ</label>
                    <input type="text" name="title" x-model="title" maxlength="200" class="tp-input" placeholder="เช่น ลดสูงสุด 50% ทั้งร้าน">
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="{{ $lbl }}">คำอธิบาย</label>
                    <input type="text" name="subtitle" x-model="subtitle" maxlength="300" class="tp-input">
                </div>
                <div>
                    <label style="{{ $lbl }}">ป้าย (Badge)</label>
                    <input type="text" name="badge" value="{{ old('badge', $b?->badge) }}" maxlength="50" class="tp-input" placeholder="เช่น HOT">
                </div>
                <div>
                    <label style="{{ $lbl }}">ตัวเลขเด่น</label>
                    <input type="text" name="highlight_text" value="{{ old('highlight_text', $b?->highlight_text) }}" maxlength="50" class="tp-input" placeholder="เช่น 50%">
                </div>
                <div>
                    <label style="{{ $lbl }}">คำกำกับตัวเลขเด่น</label>
                    <input type="text" name="highlight_label" value="{{ old('highlight_label', $b?->highlight_label) }}" maxlength="50" class="tp-input" placeholder="เช่น ส่วนลดสูงสุด">
                </div>
                <div>
                    <label style="{{ $lbl }}">ข้อความปุ่ม</label>
                    <input type="text" name="cta_text" x-model="cta" maxlength="50" class="tp-input" placeholder="เช่น ช้อปเลย">
                </div>
                <div style="grid-column:1 / -1;">
                    <label style="{{ $lbl }}">ลิงก์ปุ่ม (URL เต็ม)</label>
                    <input type="url" name="cta_url" value="{{ old('cta_url', $b?->cta_url) }}" maxlength="500" class="tp-input" placeholder="https://...">
                </div>
            </div>
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-image" style="color:var(--accent1);"></i> รูปภาพ / สีพื้น</div>
            <label style="{{ $lbl }}">รูปแบนเนอร์ (JPG, PNG, WebP ไม่เกิน 5MB · แนะนำ 1600×600)</label>
            <input type="file" name="image" accept="image/*" class="tp-input" style="padding:9px 12px;" @change="pick($event)">
            @if($b?->image_url)
                <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; margin-top:10px; cursor:pointer;">
                    <input type="checkbox" name="remove_image" value="1" x-model="removeImage" style="accent-color:var(--accent1); width:16px; height:16px;"> ลบรูปปัจจุบัน (ใช้สีพื้นแทน)
                </label>
            @endif

            <div style="{{ $lbl }} margin-top:16px;">สีพื้นเมื่อไม่มีรูป (ไล่เฉดของหน้าร้าน)</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(min(100%,150px),1fr)); gap:10px;">
                <label style="cursor:pointer; display:flex; flex-direction:column; gap:4px;">
                    <input type="radio" name="gradient" value="" x-model="grad" style="position:absolute; opacity:0; pointer-events:none;">
                    <div class="tp-well" style="height:52px; border-radius:12px; display:grid; place-items:center; font-size:12px; color:var(--ink2);"
                         :style="{ boxShadow: grad === '' ? '0 0 0 3px var(--accent1)' : '' }">ไม่ใช้</div>
                    <span style="font-size:11px; color:var(--ink2);">ใช้รูปอย่างเดียว</span>
                </label>
                @foreach($gradientOptions as $value => $label)
                    {{-- สีไล่เฉดเป็น "ข้อมูล" ของหน้าร้าน (คลาส Tailwind ที่หน้าร้านใช้จริง) ไม่ใช่สีของหลังบ้าน --}}
                    <label style="cursor:pointer; display:flex; flex-direction:column; gap:4px;" title="{{ $label }}">
                        <input type="radio" name="gradient" value="{{ $value }}" x-model="grad" style="position:absolute; opacity:0; pointer-events:none;">
                        <div class="bg-gradient-to-r {{ $value }}" style="height:52px; border-radius:12px;"
                             :style="{ boxShadow: grad === @js($value) ? '0 0 0 3px var(--accent1)' : 'var(--raise)' }"></div>
                        <span style="font-size:11px; color:var(--ink2); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===== แถบข้าง ===== --}}
    <div style="flex:1 1 260px; min-width:0; display:flex; flex-direction:column; gap:16px;">
        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-sliders" style="color:var(--accent1);"></i> การแสดงผล</div>
            <label style="{{ $lbl }}">ตำแหน่งที่แสดง</label>
            <select name="location" x-model="location" class="tp-input">
                <option value="homepage">หน้าแรก</option>
                <option value="category">หน้าหมวดหมู่</option>
            </select>
            <div x-show="location === 'category'" x-cloak style="margin-top:12px;">
                <label style="{{ $lbl }}">หมวดหมู่ <span style="color:{{ $bad }};">*</span></label>
                <select name="category_slug" class="tp-input" :required="location === 'category'" :disabled="location !== 'category'">
                    <option value="">-- เลือกหมวดหมู่ --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->slug }}" @selected(old('category_slug', $b?->category_slug) === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <label style="{{ $lbl }} margin-top:12px;">ลำดับการแสดง (น้อย = แสดงก่อน)</label>
            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $b?->sort_order ?? 0) }}" class="tp-input tp-num">
            <input type="hidden" name="is_active" value="0">
            <label style="display:flex; align-items:center; gap:8px; font-size:13px; margin-top:12px; cursor:pointer;">
                <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $b?->is_active ?? true)) style="accent-color:var(--accent1); width:18px; height:18px;"> เปิดใช้งานแบนเนอร์
            </label>
        </div>

        <div class="tp-card">
            <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-calendar-days" style="color:var(--accent1);"></i> ช่วงเวลาแสดง</div>
            <label style="{{ $lbl }}">เริ่ม</label>
            <input type="datetime-local" name="start_at" value="{{ old('start_at', $b?->start_at?->format('Y-m-d\TH:i')) }}" class="tp-input">
            <label style="{{ $lbl }} margin-top:12px;">สิ้นสุด</label>
            <input type="datetime-local" name="end_at" value="{{ old('end_at', $b?->end_at?->format('Y-m-d\TH:i')) }}" class="tp-input">
            <div style="{{ $hint }}">เว้นว่าง = แสดงตลอดจนกว่าจะปิด</div>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a href="{{ route('admin.storefront.banners.index') }}" class="tp-btn" style="flex:1;">ยกเลิก</a>
            <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;" :disabled="busy">
                <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i> บันทึก
            </button>
        </div>
    </div>
</form>
