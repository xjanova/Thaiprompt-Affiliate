{{--
    ฟอร์มสร้าง/แก้ไขแบนเนอร์แคมเปญแอป (ธีม V4 นวลทองคำ) + พรีวิวสดในกรอบมือถือ

    ตัวแปรจาก Admin\AppCampaignBannerController::form():
      $banner       MobileBanner (ใหม่ = ยังไม่ save)
      $isEdit       bool
      $placements   MobileBanner::PLACEMENTS
      $audiences    MobileBanner::AUDIENCES
      $ctaTypes     MobileBanner::CTA_TYPES
      $appScreens   MobileBanner::APP_SCREENS (ค่าแนะนำสำหรับ cta_value เมื่อเป็นหน้าจอแอป)
      $formAction   POST admin.app-banners.store | PUT admin.app-banners.update
      $imageUrl     URL รูปเดิม (แก้ไข)
    ฟิลด์: title, subtitle, image (ไฟล์), image_path, placement, audience, cta_type (''|screen|url), cta_label,
           cta_value, starts_at, ends_at (datetime-local), is_active (hidden 0 + checkbox 1), sort_order
--}}
@extends('layouts.admin-v4')

@section('title', $pageTitle ?? 'แบนเนอร์แคมเปญแอป')

@push('styles')
<style>
    .abf-page {
        --abf-ok: var(--tp-ok, #4f9e7e);
        --abf-bad: var(--tp-bad, #d9534f);
        --ab-on: var(--tp-on-accent, #fff);
    }
    .abf-label { display:block; font-size:12.5px; font-weight:700; color:var(--ink); margin-bottom:6px; }
    .abf-help { font-size:11.5px; color:var(--ink2); margin-top:6px; line-height:1.55; }
    .abf-err { font-size:12px; color:var(--abf-bad); margin-top:6px; font-weight:600; }
    .abf-count { font-size:11px; color:var(--ink2); float:right; font-family:var(--tp-font-num); }
    .abf-choice { position:relative; display:flex; align-items:center; gap:8px; min-height:44px; padding:8px 14px; border-radius:13px; cursor:pointer; font-size:12.5px; font-weight:700; color:var(--ink2); background:var(--surf); box-shadow:var(--raise); }
    .abf-choice input { position:absolute; opacity:0; width:1px; height:1px; }
    .abf-choice.on { color:var(--ab-on); background:linear-gradient(135deg, var(--accent1), var(--accent2)); }
    .abf-choice:focus-within { outline:2px solid var(--accent1); outline-offset:2px; }
    .abf-drop { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; min-height:120px; padding:16px; border-radius:16px; text-align:center; cursor:pointer; color:var(--ink2); background:var(--surf); box-shadow:var(--inset); }
    .abf-drop input { position:absolute; opacity:0; width:1px; height:1px; }
    .abf-switch { position:relative; display:inline-flex; align-items:center; gap:12px; cursor:pointer; min-height:44px; }
    .abf-switch input { position:absolute; opacity:0; width:1px; height:1px; }
    .abf-track { width:54px; height:30px; flex:none; border-radius:20px; position:relative; background:var(--surf); box-shadow:var(--inset-sm); transition:background .2s ease; }
    .abf-knob { position:absolute; top:3px; left:3px; width:24px; height:24px; border-radius:50%; background:var(--sl); box-shadow:var(--raise); transition:transform .2s ease; }
    .abf-switch input:checked + .abf-track { background:linear-gradient(135deg, var(--accent1), var(--accent2)); }
    .abf-switch input:checked + .abf-track .abf-knob { transform:translateX(24px); }
    .abf-switch input:focus-visible + .abf-track { outline:2px solid var(--accent1); outline-offset:2px; }
    .abf-chip { min-height:36px; }
</style>
@endpush

@section('content')
<div class="abf-page" x-data="bannerForm()" style="display:flex; flex-direction:column; gap:18px;">

    {{-- ===== Header ===== --}}
    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div>
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">
                หลังบ้าน · แอปมือถือ · <a href="{{ route('admin.app-banners.index') }}" style="color:inherit;">แบนเนอร์แคมเปญ</a>
            </div>
            <h1 class="tp-num" style="font-size:clamp(22px,4vw,28px); font-weight:800; margin:4px 0 0;">
                {{ $isEdit ? 'แก้ไขแบนเนอร์' : 'สร้างแบนเนอร์ใหม่' }} 🖼️
            </h1>
            @if($isEdit)
                <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">
                    แสดงผล {{ number_format($banner->view_count) }} ครั้ง · คลิก {{ number_format($banner->click_count) }} ครั้ง · สถานะ {{ $banner->scheduleStateLabel() }}
                </div>
            @endif
        </div>
        <a href="{{ route('admin.app-banners.index', ['placement' => $banner->position]) }}" class="tp-btn tp-btn-sm" style="min-height:44px;">
            <i class="fas fa-arrow-left"></i> กลับไปรายการ
        </a>
    </div>

    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--abf-bad);">
            <div style="font-weight:700; color:var(--abf-bad); margin-bottom:6px;"><i class="fas fa-circle-exclamation"></i> กรุณาตรวจข้อมูลอีกครั้ง</div>
            <ul style="margin:0; padding-left:18px; font-size:13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid var(--abf-bad);">
            <i class="fas fa-circle-exclamation" style="color:var(--abf-bad);"></i> {{ session('error') }}
        </div>
    @endif

    <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start;">

        {{-- ===== ฟอร์ม ===== --}}
        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" @submit="submitting = true"
              style="flex:1 1 480px; min-width:0; display:flex; flex-direction:column; gap:16px;">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            {{-- ข้อความบนแบนเนอร์ --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:14px;"><i class="fas fa-font" style="color:var(--accent2);"></i> ข้อความบนแบนเนอร์</div>

                <div style="margin-bottom:14px;">
                    <label class="abf-label" for="abf-title">หัวข้อ <span style="color:var(--abf-bad);">*</span>
                        <span class="abf-count" x-text="title.length + '/100'"></span>
                    </label>
                    <input id="abf-title" type="text" name="title" maxlength="100" required x-model="title"
                           value="{{ old('title', $banner->title) }}" class="tp-input" style="min-height:44px;" placeholder="เช่น เปิดตัวตลาดสดไทยพร้อม">
                    @error('title') <div class="abf-err">{{ $message }}</div> @enderror
                </div>

                <div>
                    <label class="abf-label" for="abf-subtitle">ข้อความรอง
                        <span class="abf-count" x-text="subtitle.length + '/150'"></span>
                    </label>
                    <input id="abf-subtitle" type="text" name="subtitle" maxlength="150" x-model="subtitle"
                           value="{{ old('subtitle', $banner->subtitle) }}" class="tp-input" style="min-height:44px;" placeholder="เช่น ผัดกะเพราร้อนๆ ส่งถึงมือ สั่งเลย">
                    @error('subtitle') <div class="abf-err">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- รูป --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:6px;"><i class="fas fa-image" style="color:var(--accent2);"></i> รูปแบนเนอร์</div>
                <div class="abf-help" style="margin:0 0 12px;">รูปไม่มีตัวหนังสือ สัดส่วน 2:1 (เช่น 1200×600) JPG/PNG/WEBP ไม่เกิน 4 MB — ข้อความไทยแอปจะวางทับให้เอง</div>

                <label class="abf-drop" style="position:relative;">
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" @change="onFile($event)">
                    <i class="fas fa-cloud-arrow-up" style="font-size:26px; color:var(--accent1);"></i>
                    <span style="font-weight:700; color:var(--ink);" x-text="fileName || @js($isEdit ? 'แตะเพื่อเปลี่ยนรูป' : 'แตะเพื่อเลือกรูป')">{{ $isEdit ? 'แตะเพื่อเปลี่ยนรูป' : 'แตะเพื่อเลือกรูป' }}</span>
                    <span style="font-size:11.5px;">หรือใส่ที่อยู่รูปด้านล่าง</span>
                </label>
                @error('image') <div class="abf-err">{{ $message }}</div> @enderror

                <div style="margin-top:12px;">
                    <label class="abf-label" for="abf-image-path">ที่อยู่รูป (ไม่บังคับ)</label>
                    <input id="abf-image-path" type="text" name="image_path" x-model="imagePath" @input="fileName = ''"
                           value="{{ old('image_path') }}" class="tp-input tp-num" style="min-height:44px;"
                           placeholder="{{ $isEdit ? $banner->image : '/images/taladsod/banner-market.webp' }}">
                    <div class="abf-help">ขึ้นต้นด้วย / (รูปในเว็บนี้) หรือ https:// — ว่างไว้ = ใช้รูปที่อัปโหลด{{ $isEdit ? ' หรือรูปเดิม' : '' }}</div>
                    @error('image_path') <div class="abf-err">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- ปุ่ม CTA --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-hand-pointer" style="color:var(--accent2);"></i> ปุ่มกด (CTA)</div>

                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px;" role="radiogroup" aria-label="ชนิดปุ่ม">
                    <label class="abf-choice" :class="ctaType === '' ? 'on' : ''">
                        <input type="radio" name="cta_type" value="" x-model="ctaType"> <i class="fas fa-ban"></i> ไม่มีปุ่ม
                    </label>
                    @foreach($ctaTypes as $key => $label)
                        <label class="abf-choice" :class="ctaType === @js($key) ? 'on' : ''">
                            <input type="radio" name="cta_type" value="{{ $key }}" x-model="ctaType">
                            <i class="fas {{ $key === 'screen' ? 'fa-mobile-screen' : 'fa-link' }}"></i> {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('cta_type') <div class="abf-err">{{ $message }}</div> @enderror

                <div x-show="ctaType !== ''" x-cloak class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                    <div>
                        <label class="abf-label" for="abf-cta-label">ข้อความบนปุ่ม <span class="abf-count" x-text="ctaLabel.length + '/40'"></span></label>
                        <input id="abf-cta-label" type="text" name="cta_label" maxlength="40" x-model="ctaLabel"
                               value="{{ old('cta_label', $banner->cta_label) }}" class="tp-input" style="min-height:44px;" placeholder="เช่น สั่งเลย">
                        @error('cta_label') <div class="abf-err">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="abf-label" for="abf-cta-value" x-text="ctaType === 'url' ? 'ลิงก์ปลายทาง' : 'หน้าจอในแอป'">ปลายทาง</label>
                        <input id="abf-cta-value" type="text" name="cta_value" maxlength="500" x-model="ctaValue" list="abf-screens"
                               value="{{ old('cta_value', $banner->cta_value) }}" class="tp-input tp-num" style="min-height:44px;"
                               :placeholder="ctaType === 'url' ? 'https://… หรือ /taladsod/start/seller' : 'เช่น taladsod'">
                        <datalist id="abf-screens">
                            @foreach($appScreens as $screen => $label)
                                <option value="{{ $screen }}">{{ $label }}</option>
                            @endforeach
                        </datalist>
                        @error('cta_value') <div class="abf-err">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div x-show="ctaType === 'screen'" x-cloak class="flex" style="flex-wrap:wrap; gap:6px; margin-top:10px;">
                    @foreach($appScreens as $screen => $label)
                        <button type="button" class="tp-btn tp-btn-sm abf-chip" :class="ctaValue === @js($screen) ? 'tp-btn-primary' : ''" @click="ctaValue = @js($screen)">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="abf-help" x-show="ctaType === 'url'" x-cloak>ลิงก์ในเว็บนี้ใส่แค่ path เช่น /taladsod/start/seller ระบบเติมโดเมนให้เอง</div>
            </div>

            {{-- ตำแหน่ง + กลุ่มผู้ชม --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-location-dot" style="color:var(--accent2);"></i> แสดงที่ไหน ให้ใครเห็น</div>

                <div class="abf-label">ตำแหน่งในแอป <span style="color:var(--abf-bad);">*</span></div>
                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px;" role="radiogroup" aria-label="ตำแหน่งในแอป">
                    @foreach($placements as $key => $label)
                        <label class="abf-choice" :class="placement === @js($key) ? 'on' : ''">
                            <input type="radio" name="placement" value="{{ $key }}" x-model="placement" @checked(old('placement', $banner->position) === $key)> {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('placement') <div class="abf-err">{{ $message }}</div> @enderror

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
                    <div>
                        <label class="abf-label" for="abf-audience">กลุ่มผู้ชม</label>
                        <select id="abf-audience" name="audience" x-model="audience" class="tp-input" style="min-height:44px;">
                            @foreach($audiences as $key => $label)
                                <option value="{{ $key }}" @selected(old('audience', $banner->audience ?: 'all') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('audience') <div class="abf-err">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="abf-label" for="abf-sort">ลำดับ (น้อย = แสดงก่อน)</label>
                        <input id="abf-sort" type="number" name="sort_order" min="0" max="9999"
                               value="{{ old('sort_order', $isEdit ? $banner->sort_order : '') }}" class="tp-input tp-num" style="min-height:44px;"
                               placeholder="ว่าง = ต่อท้าย" inputmode="numeric">
                        @error('sort_order') <div class="abf-err">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- ตารางเวลา --}}
            <div class="tp-card" style="padding:20px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-calendar-days" style="color:var(--accent2);"></i> ตารางเวลา</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
                    <div>
                        <label class="abf-label" for="abf-starts">เริ่มแสดง</label>
                        <input id="abf-starts" type="datetime-local" name="starts_at"
                               value="{{ old('starts_at', $banner->start_date?->format('Y-m-d\TH:i')) }}" class="tp-input tp-num" style="min-height:44px;">
                        <div class="abf-help">ว่าง = แสดงทันที</div>
                        @error('starts_at') <div class="abf-err">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="abf-label" for="abf-ends">สิ้นสุด</label>
                        <input id="abf-ends" type="datetime-local" name="ends_at"
                               value="{{ old('ends_at', $banner->end_date?->format('Y-m-d\TH:i')) }}" class="tp-input tp-num" style="min-height:44px;">
                        <div class="abf-help">ว่าง = ไม่มีกำหนดสิ้นสุด</div>
                        @error('ends_at') <div class="abf-err">{{ $message }}</div> @enderror
                    </div>
                </div>

                <label class="abf-switch" style="margin-top:12px;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" x-model="isActive" @checked((bool) old('is_active', $banner->is_active))>
                    <span class="abf-track"><span class="abf-knob"></span></span>
                    <span style="font-size:13.5px; font-weight:700;" x-text="isActive ? 'เปิดแสดงในแอป' : 'ปิดไว้ก่อน (บันทึกเป็นแบบร่าง)'">เปิดแสดงในแอป</span>
                </label>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:10px; justify-content:flex-end;">
                <a href="{{ route('admin.app-banners.index', ['placement' => $banner->position]) }}" class="tp-btn" style="min-height:44px;">ยกเลิก</a>
                <button type="submit" class="tp-btn tp-btn-primary" style="min-height:44px; padding:0 24px;" :disabled="submitting">
                    <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="submitting ? 'กำลังบันทึก...' : @js($isEdit ? 'บันทึกการแก้ไข' : 'สร้างแบนเนอร์')">{{ $isEdit ? 'บันทึกการแก้ไข' : 'สร้างแบนเนอร์' }}</span>
                </button>
            </div>
        </form>

        {{-- ===== พรีวิวสด ===== --}}
        <div style="flex:0 1 340px; min-width:0; position:sticky; top:84px;">
            <div class="tp-card" style="padding:18px;">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-mobile-screen" style="color:var(--accent1);"></i> พรีวิวในแอป</div>
                @include('admin.app-banners._phone')
                <div style="font-size:11.5px; color:var(--ink2); margin-top:12px; text-align:center;">เปลี่ยนข้อความ/รูปในฟอร์มแล้วดูผลได้ทันที</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bannerForm() {
    return {
        title: @js((string) old('title', $banner->title ?? '')),
        subtitle: @js((string) old('subtitle', $banner->subtitle ?? '')),
        ctaType: @js((string) old('cta_type', $banner->cta_type ?? '')),
        ctaLabel: @js((string) old('cta_label', $banner->cta_label ?? '')),
        ctaValue: @js((string) old('cta_value', $banner->cta_value ?? '')),
        placement: @js((string) old('placement', $banner->position ?: 'home')),
        audience: @js((string) old('audience', $banner->audience ?: 'all')),
        isActive: @js((bool) old('is_active', $banner->is_active ?? true)),
        imagePath: @js((string) old('image_path', '')),
        existingImage: @js($imageUrl),
        objectUrl: '',
        fileName: '',
        labels: @js($placements),
        current: 0,
        submitting: false,

        get placementLabel() {
            return this.labels[this.placement] || '';
        },

        get previewImage() {
            if (this.objectUrl) return this.objectUrl;
            const typed = (this.imagePath || '').trim();
            if (typed.startsWith('https://') || (typed.startsWith('/') && !typed.startsWith('//'))) return typed;
            return this.existingImage || '';
        },

        get slides() {
            return [{
                title: this.title,
                subtitle: this.subtitle,
                image_url: this.previewImage,
                cta_label: this.ctaType ? this.ctaLabel : '',
            }];
        },

        go(i) {
            this.current = i;
        },

        onFile(event) {
            const file = event.target.files && event.target.files[0];
            if (this.objectUrl) URL.revokeObjectURL(this.objectUrl);
            this.objectUrl = file ? URL.createObjectURL(file) : '';
            this.fileName = file ? file.name : '';
        },
    };
}
</script>
@endpush
