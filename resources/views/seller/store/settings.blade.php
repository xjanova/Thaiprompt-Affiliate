@extends('layouts.seller-v4')

@section('title', 'ตั้งค่าร้านค้า - ' . ($store->store_name ?? 'ร้านค้าของคุณ'))

@push('styles')
    @include('seller.partials.v4-styles')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        .ss-banner { position:relative; height:220px; overflow:hidden; border-radius:18px; box-shadow:var(--inset); background:var(--surf); cursor:grab; touch-action:none; user-select:none; }
        .ss-banner.dragging { cursor:grabbing; }
        .ss-banner img { position:absolute; top:0; left:0; width:100%; height:auto; pointer-events:none; -webkit-user-drag:none; }
        .ss-map { height:300px; border-radius:18px; overflow:hidden; box-shadow:var(--inset); background:var(--surf); position:relative; z-index:0; }
        .ss-anchor { scroll-margin-top:90px; }
    </style>
@endpush

@php
    use App\Support\Seller\SellerUi;

    $primaryColor = SellerUi::colorOr(old('primary_color', $store->primary_color), SellerUi::STORE_PRIMARY_DEFAULT);
    $secondaryColor = SellerUi::colorOr(old('secondary_color', $store->secondary_color), SellerUi::STORE_SECONDARY_DEFAULT);
    $riderEnabled = (bool) old('rider_delivery_enabled', $store->rider_delivery_enabled);
    $vatRegistered = (bool) old('vat_registered', $store->vat_registered);
    $mapConfig = [
        'lat' => is_numeric(old('pickup_latitude', $store->pickup_latitude)) ? (float) old('pickup_latitude', $store->pickup_latitude) : null,
        'lng' => is_numeric(old('pickup_longitude', $store->pickup_longitude)) ? (float) old('pickup_longitude', $store->pickup_longitude) : null,
        'enabled' => $riderEnabled,
    ];
    $bannerY = (int) old('banner_position_y', $store->banner_position_y ?? 0);
    $sections = [
        ['basic', '🏪 ข้อมูลร้าน'], ['contact', '📞 ติดต่อ'], ['business', '🏢 ธุรกิจ'], ['tax', '🧾 ภาษี'],
        ['sales', '💳 การขาย'], ['rider', '🛵 ไรเดอร์'], ['social', '📱 โซเชียล'],
    ];
@endphp

@section('content')
<div class="sv4-page">

    <x-seller-v4.header title="ตั้งค่าร้านค้า" subtitle="ข้อมูลร้าน การขาย การจัดส่ง และภาษี" icon="⚙️" :back="route('seller.settings')">
        <a href="{{ route('seller.store.layout.index') }}" class="tp-btn tp-btn-sm">🎨 ปรับแต่งหน้าร้าน</a>
        @if($store->store_url ?? null)
            <a href="{{ $store->store_url }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm">🌐 ดูหน้าร้าน</a>
        @endif
    </x-seller-v4.header>

    <x-seller-v4.errors />

    <nav class="sv4-tabs" aria-label="ไปยังหัวข้อ">
        @foreach($sections as [$anchor, $label])
            <a href="#{{ $anchor }}" class="sv4-tab">{{ $label }}</a>
        @endforeach
    </nav>

    <form method="POST" action="{{ route('seller.store.update') }}" enctype="multipart/form-data"
          x-data="{ busy: false }" @submit="busy = true" style="display:flex; flex-direction:column; gap:18px;">
        @csrf
        @method('PUT')

        {{-- ── ข้อมูลร้าน ─────────────────────────────────────── --}}
        <section id="basic" class="tp-card ss-anchor" style="padding:20px;">
            <div class="sv4-h2">🏪 ข้อมูลร้าน</div>
            <div style="display:flex; flex-direction:column; gap:14px; margin-top:14px;">
                <div>
                    <label for="store_name" class="sv4-label">ชื่อร้านค้า <span class="req">*</span></label>
                    <input type="text" name="store_name" id="store_name" required maxlength="255" class="tp-input"
                           value="{{ old('store_name', $store->store_name) }}">
                    <div class="sv4-hint">เปลี่ยนชื่อร้านแล้วลิงก์หน้าร้านจะเปลี่ยนตาม</div>
                    @error('store_name')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="store_description" class="sv4-label">คำอธิบายร้านค้า</label>
                    <textarea name="store_description" id="store_description" rows="4" class="tp-input"
                              placeholder="เล่าเรื่องร้าน จุดเด่น สินค้าหลัก">{{ old('store_description', $store->store_description) }}</textarea>
                    @error('store_description')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>

                <div style="display:flex; flex-wrap:wrap; gap:18px;">
                    {{-- โลโก้ --}}
                    <div x-data="{ preview: @js($store->logo_url) }" style="flex:0 0 auto;">
                        <label for="store_logo" class="sv4-label">โลโก้ร้าน</label>
                        <label for="store_logo" style="display:grid; place-items:center; width:128px; height:128px; border-radius:22px; overflow:hidden; box-shadow:var(--inset); cursor:pointer; background:var(--surf);">
                            <img x-show="preview" :src="preview" alt="โลโก้ร้าน" style="width:100%; height:100%; object-fit:cover;">
                            <span x-show="!preview" style="font-size:12px; color:var(--ink2); text-align:center;"><span style="font-size:28px; display:block;">🏪</span>เลือกโลโก้</span>
                        </label>
                        <input type="file" name="store_logo" id="store_logo" accept="image/jpeg,image/png,image/gif,image/webp"
                               style="margin-top:8px; font-size:12px; max-width:150px; color:var(--ink2);"
                               @change="const f = $event.target.files[0]; if (f) { if (f.size > 2 * 1024 * 1024) { alert('โลโก้ต้องไม่เกิน 2MB'); $event.target.value = ''; return; } const r = new FileReader(); r.onload = (e) => preview = e.target.result; r.readAsDataURL(f); }">
                        <div class="sv4-hint">JPG/PNG/WebP ไม่เกิน 2MB (แปลงเป็น WebP ให้อัตโนมัติ)</div>
                        @error('store_logo')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>

                    {{-- แบนเนอร์ + ลากปรับตำแหน่ง --}}
                    <div x-data="bannerEditor(@js($store->banner_url), {{ $bannerY }})" style="flex:1 1 320px; min-width:0;">
                        <label for="store_banner" class="sv4-label">แบนเนอร์ร้าน</label>
                        <div class="ss-banner" x-ref="box" :class="dragging && 'dragging'"
                             @pointerdown="start($event)" @pointermove.window="move($event)" @pointerup.window="end()" @pointercancel.window="end()">
                            <img x-show="src" :src="src" x-ref="img" alt="แบนเนอร์ร้าน" :style="{ transform: 'translateY(' + y + 'px)' }" @load="clamp()">
                            <div x-show="!src" class="grid" style="position:absolute; inset:0; place-items:center; color:var(--ink2); font-size:12.5px;">📸 ยังไม่มีแบนเนอร์</div>
                            <div x-show="src" style="position:absolute; left:10px; bottom:10px;">
                                <span class="sv4-pill" style="background:rgba(0,0,0,.45); color:var(--tp-on-accent, #fff);">↕ ลากขึ้น-ลงเพื่อจัดตำแหน่ง</span>
                            </div>
                        </div>
                        <input type="hidden" name="banner_position_y" :value="Math.round(y)">
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-top:8px;">
                            <input type="file" name="store_banner" id="store_banner" accept="image/jpeg,image/png,image/gif,image/webp"
                                   @change="pick($event)" style="font-size:12px; color:var(--ink2); max-width:220px;">
                            <span class="sv4-hint tp-num" style="margin:0;">ตำแหน่ง Y: <span x-text="Math.round(y) + 'px'"></span></span>
                            <button type="button" class="tp-btn tp-btn-sm" @click="y = 0">รีเซ็ตตำแหน่ง</button>
                        </div>
                        <div class="sv4-hint">แนะนำ 1920×600 พิกเซล ไม่เกิน 4MB</div>
                        @error('store_banner')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </section>

        {{-- ── ติดต่อ ─────────────────────────────────────────── --}}
        <section id="contact" class="tp-card ss-anchor" style="padding:20px;">
            <div class="sv4-h2">📞 ข้อมูลติดต่อ</div>
            <div class="sv4-grid" style="margin-top:14px;">
                <div>
                    <label for="store_email" class="sv4-label">อีเมลร้าน</label>
                    <input type="email" name="store_email" id="store_email" maxlength="255" class="tp-input" value="{{ old('store_email', $store->store_email) }}">
                    @error('store_email')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="store_phone" class="sv4-label">เบอร์โทรร้าน</label>
                    <input type="tel" name="store_phone" id="store_phone" maxlength="20" class="tp-input tp-num" value="{{ old('store_phone', $store->store_phone) }}">
                    @error('store_phone')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div style="margin-top:14px;">
                <label for="store_address" class="sv4-label">ที่อยู่ร้าน</label>
                <textarea name="store_address" id="store_address" rows="3" class="tp-input">{{ old('store_address', $store->store_address) }}</textarea>
                @error('store_address')<div class="sv4-err">{{ $message }}</div>@enderror
            </div>
            <div class="sv4-well" style="margin-top:14px;">
                {{-- ระบบเลือกที่อยู่ไทย (จังหวัด/อำเภอ/รหัสไปรษณีย์) + ประเทศ --}}
                <x-thai-address-picker
                    province-field="store_state"
                    district-field="store_city"
                    sub-district-field=""
                    postal-code-field="store_postal_code"
                    country-field="store_country"
                    :province-value="old('store_state', $store->store_state ?? '')"
                    :district-value="old('store_city', $store->store_city ?? '')"
                    :sub-district-value="''"
                    :postal-code-value="old('store_postal_code', $store->store_postal_code ?? '')"
                    :country-value="old('store_country', $store->store_country ?? 'TH')"
                    :show-sub-district="false"
                />
            </div>
        </section>

        {{-- ── ธุรกิจ ─────────────────────────────────────────── --}}
        <section id="business" class="tp-card ss-anchor" style="padding:20px;">
            <div class="sv4-h2">🏢 ข้อมูลธุรกิจ</div>
            <div class="sv4-grid" style="margin-top:14px;">
                <div>
                    <label for="business_type" class="sv4-label">ประเภทธุรกิจ <span class="req">*</span></label>
                    <select name="business_type" id="business_type" required class="tp-input">
                        @php
                            $businessType = old('business_type', $store->business_type ?: 'individual');
                        @endphp
                        <option value="individual" @selected($businessType === 'individual')>บุคคลธรรมดา</option>
                        <option value="company" @selected($businessType === 'company')>นิติบุคคล (บริษัท/ห้างหุ้นส่วน)</option>
                    </select>
                    @error('business_type')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="company_name" class="sv4-label">ชื่อบริษัท (ถ้ามี)</label>
                    <input type="text" name="company_name" id="company_name" maxlength="255" class="tp-input" value="{{ old('company_name', $store->company_name) }}">
                    @error('company_name')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        {{-- ── ภาษี (VAT) ────────────────────────────────────── --}}
        <section id="tax" class="tp-card ss-anchor" style="padding:20px;" x-data="{ vat: {{ $vatRegistered ? 'true' : 'false' }} }">
            <div class="sv4-h2">🧾 ภาษีมูลค่าเพิ่ม (VAT)</div>
            <div class="sv4-sub">มีผลกับเงินที่ร้านได้รับทุกออเดอร์หลังบันทึก</div>

            <label class="sv4-well" style="display:flex; align-items:center; gap:12px; margin-top:14px; cursor:pointer;">
                <input type="hidden" name="vat_registered" value="0">
                <span class="sv4-switch"><input type="checkbox" name="vat_registered" value="1" x-model="vat" @checked($vatRegistered)><span></span></span>
                <span style="min-width:0;">
                    <span style="font-weight:800; font-size:13px;">ร้านจดทะเบียนภาษีมูลค่าเพิ่ม (VAT)</span>
                    <span class="sv4-hint" style="display:block; margin-top:2px;">เปิด = ระบบถอด VAT 7/107 ออกจากยอดขาย (ราคาสินค้ารวม VAT แล้ว) ก่อนโอนเงินให้ร้าน · ปิด = ไม่หัก VAT</span>
                </span>
            </label>

            <div class="sv4-grid" style="margin-top:14px;">
                <div>
                    <label for="tax_id" class="sv4-label">เลขประจำตัวผู้เสียภาษี <span class="req" x-show="vat">*</span></label>
                    <input type="text" name="tax_id" id="tax_id" maxlength="50" inputmode="numeric" class="tp-input tp-num"
                           :required="vat" value="{{ old('tax_id', $store->tax_id) }}" placeholder="13 หลัก">
                    @error('tax_id')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="sv4-note" style="margin-top:14px; --c:{{ SellerUi::INFO }};" x-show="!vat">
                💡 ร้านที่รายได้ทั้งปีเกิน 1,800,000 บาท ต้องจดทะเบียน VAT ตามกฎหมาย — เมื่อจดแล้วกลับมาเปิดสวิตช์นี้
            </div>
            <div class="sv4-note" style="margin-top:14px; --c:{{ SellerUi::WARN }};" x-show="vat" x-cloak>
                ⚠️ เปิดแล้วรายได้สุทธิต่อออเดอร์จะลดลงราว 6.54% ของยอดขาย (ส่วน VAT) — ดูตัวอย่างตัวเลขได้ที่ <a href="{{ route('seller.pricing.planner') }}" class="sv4-link">วางแผนราคา</a>
            </div>
        </section>

        {{-- ── การขาย ─────────────────────────────────────────── --}}
        <section id="sales" class="tp-card ss-anchor" style="padding:20px;">
            <div class="sv4-h2">💳 การตั้งค่าการขาย</div>
            <div class="sv4-grid" style="margin-top:14px;">
                <div>
                    <label for="minimum_order_amount" class="sv4-label">ยอดสั่งซื้อขั้นต่ำ (บาท)</label>
                    <input type="number" name="minimum_order_amount" id="minimum_order_amount" min="0" step="0.01" class="tp-input tp-num"
                           value="{{ old('minimum_order_amount', $store->minimum_order_amount) }}">
                    @error('minimum_order_amount')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="shipping_fee" class="sv4-label">ค่าจัดส่งพัสดุเริ่มต้น (บาท)</label>
                    <input type="number" name="shipping_fee" id="shipping_fee" min="0" step="0.01" class="tp-input tp-num"
                           value="{{ old('shipping_fee', $store->shipping_fee) }}">
                    @error('shipping_fee')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="free_shipping_threshold" class="sv4-label">ส่งฟรีเมื่อซื้อครบ (บาท)</label>
                    <input type="number" name="free_shipping_threshold" id="free_shipping_threshold" min="0" step="0.01" class="tp-input tp-num"
                           value="{{ old('free_shipping_threshold', $store->free_shipping_threshold) }}">
                    @error('free_shipping_threshold')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="sv4-grid" style="margin-top:14px;">
                <div x-data="{ c: @js($primaryColor) }">
                    <label for="primary_color" class="sv4-label">สีหลักของหน้าร้าน</label>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input type="color" name="primary_color" id="primary_color" x-model="c" value="{{ $primaryColor }}"
                               style="width:52px; height:42px; border:0; border-radius:12px; background:transparent; cursor:pointer; padding:0;">
                        <span class="tp-input tp-num" style="flex:1;" x-text="c">{{ $primaryColor }}</span>
                    </div>
                    @error('primary_color')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div x-data="{ c: @js($secondaryColor) }">
                    <label for="secondary_color" class="sv4-label">สีรองของหน้าร้าน</label>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input type="color" name="secondary_color" id="secondary_color" x-model="c" value="{{ $secondaryColor }}"
                               style="width:52px; height:42px; border:0; border-radius:12px; background:transparent; cursor:pointer; padding:0;">
                        <span class="tp-input tp-num" style="flex:1;" x-text="c">{{ $secondaryColor }}</span>
                    </div>
                    @error('secondary_color')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="sv4-grid" style="margin-top:14px;">
                {{-- hidden 0 ก่อน checkbox: ไม่ติ๊ก = ส่ง 0 มา → old() หลัง validation error จำค่า "ปิด" ได้ ไม่เด้งกลับเป็นค่าใน DB --}}
                <label class="sv4-well" style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                    <input type="hidden" name="enable_cod" value="0">
                    <span class="sv4-switch"><input type="checkbox" name="enable_cod" value="1" @checked((bool) old('enable_cod', $store->enable_cod))><span></span></span>
                    <span><span style="font-weight:800; font-size:13px;">รับชำระเงินปลายทาง (COD)</span>
                        <span class="sv4-hint" style="display:block; margin-top:1px;">ลูกค้าจ่ายเงินตอนรับของ</span></span>
                </label>
                <label class="sv4-well" style="display:flex; align-items:center; gap:12px; cursor:pointer;">
                    <input type="hidden" name="enable_reviews" value="0">
                    <span class="sv4-switch"><input type="checkbox" name="enable_reviews" value="1" @checked((bool) old('enable_reviews', $store->enable_reviews))><span></span></span>
                    <span><span style="font-weight:800; font-size:13px;">เปิดให้ลูกค้ารีวิวสินค้า</span>
                        <span class="sv4-hint" style="display:block; margin-top:1px;">รีวิวช่วยเพิ่มความน่าเชื่อถือ</span></span>
                </label>
            </div>
        </section>

        {{-- ── ส่งด้วยไรเดอร์ + ปักหมุดจุดรับของ ─────────────────── --}}
        <section id="rider" class="tp-card ss-anchor" style="padding:20px;" x-data="pickupMap(@js($mapConfig))">
            <div class="sv4-row" style="flex-wrap:wrap;">
                <div>
                    <div class="sv4-h2">🛵 ส่งด้วยไรเดอร์ของแพลตฟอร์ม</div>
                    <div class="sv4-sub">ลูกค้าเลือก "ส่งด้วยไรเดอร์" ได้ เมื่อสินค้าพร้อมให้กด "เรียกไรเดอร์" ในหน้าคำสั่งซื้อ</div>
                </div>
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <span style="font-size:12.5px; font-weight:700;" x-text="enabled ? 'เปิดอยู่' : 'ปิดอยู่'"></span>
                    <input type="hidden" name="rider_delivery_enabled" value="0">
                    <span class="sv4-switch"><input type="checkbox" name="rider_delivery_enabled" value="1" x-model="enabled" @checked($riderEnabled)><span></span></span>
                </label>
            </div>

            <div style="margin-top:14px;">
                <label for="pickup_address" class="sv4-label">ที่อยู่จุดรับของ <span style="font-weight:500; color:var(--ink2);">(ว่าง = ใช้ที่อยู่ร้าน)</span></label>
                <input type="text" name="pickup_address" id="pickup_address" maxlength="500" class="tp-input"
                       value="{{ old('pickup_address', $store->pickup_address) }}" placeholder="เช่น หน้าร้าน ซอยสุขุมวิท 50 ตึกสีเหลือง">
                @error('pickup_address')<div class="sv4-err">{{ $message }}</div>@enderror
            </div>

            <div style="margin-top:14px;">
                <div class="sv4-row" style="flex-wrap:wrap; margin-bottom:8px;">
                    <span class="sv4-label" style="margin:0;">📍 ปักหมุดจุดรับของ <span class="req" x-show="enabled">*</span></span>
                    <button type="button" class="tp-btn tp-btn-sm" @click="locate()" :disabled="locating">
                        <span x-show="!locating">🎯 ใช้ตำแหน่งปัจจุบัน</span>
                        <span x-show="locating" x-cloak>กำลังระบุตำแหน่ง…</span>
                    </button>
                </div>
                <div class="ss-map" x-ref="map" role="application" aria-label="แผนที่ปักหมุดจุดรับของ">
                    <div x-show="!ready" class="grid" style="position:absolute; inset:0; place-items:center; color:var(--ink2); font-size:12.5px;" x-text="mapError || 'กำลังโหลดแผนที่…'"></div>
                </div>
                <div class="sv4-hint">แตะบนแผนที่หรือลากหมุดไปยังจุดที่ไรเดอร์มารับของ</div>
                <div class="sv4-grid" style="margin-top:10px; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));">
                    <div>
                        <label for="pickup_latitude" class="sv4-label">ละติจูด</label>
                        <input type="number" step="0.0000001" min="-90" max="90" name="pickup_latitude" id="pickup_latitude" class="tp-input tp-num"
                               x-model="lat" @change="syncFromInputs()" :required="enabled">
                        @error('pickup_latitude')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="pickup_longitude" class="sv4-label">ลองจิจูด</label>
                        <input type="number" step="0.0000001" min="-180" max="180" name="pickup_longitude" id="pickup_longitude" class="tp-input tp-num"
                               x-model="lng" @change="syncFromInputs()" :required="enabled">
                        @error('pickup_longitude')<div class="sv4-err">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="sv4-err" x-show="locError" x-text="locError"></div>
                <div class="sv4-note" style="margin-top:10px; --c:{{ SellerUi::WARN }};" x-show="enabled && (!lat || !lng)" x-cloak>
                    ต้องปักหมุดจุดรับของก่อน ลูกค้าจึงจะเลือกส่งด้วยไรเดอร์ได้
                </div>
            </div>
        </section>

        {{-- ── โซเชียล ─────────────────────────────────────────── --}}
        <section id="social" class="tp-card ss-anchor" style="padding:20px;">
            <div class="sv4-h2">📱 โซเชียลมีเดีย</div>
            <div class="sv4-grid" style="margin-top:14px;">
                <div>
                    <label for="facebook_url" class="sv4-label">Facebook</label>
                    <input type="url" name="facebook_url" id="facebook_url" maxlength="255" class="tp-input" value="{{ old('facebook_url', $store->facebook_url) }}" placeholder="https://facebook.com/ร้านของคุณ">
                    @error('facebook_url')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="line_oa_id" class="sv4-label">LINE OA ID</label>
                    <input type="text" name="line_oa_id" id="line_oa_id" maxlength="255" class="tp-input" value="{{ old('line_oa_id', $store->line_oa_id) }}" placeholder="@ร้านของคุณ">
                    @error('line_oa_id')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="instagram_url" class="sv4-label">Instagram</label>
                    <input type="url" name="instagram_url" id="instagram_url" maxlength="255" class="tp-input" value="{{ old('instagram_url', $store->instagram_url) }}" placeholder="https://instagram.com/…">
                    @error('instagram_url')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="twitter_url" class="sv4-label">X (Twitter)</label>
                    <input type="url" name="twitter_url" id="twitter_url" maxlength="255" class="tp-input" value="{{ old('twitter_url', $store->twitter_url) }}" placeholder="https://x.com/…">
                    @error('twitter_url')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label for="tiktok_url" class="sv4-label">TikTok</label>
                    <input type="url" name="tiktok_url" id="tiktok_url" maxlength="255" class="tp-input" value="{{ old('tiktok_url', $store->tiktok_url) }}" placeholder="https://tiktok.com/@…">
                    @error('tiktok_url')<div class="sv4-err">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <div style="position:sticky; bottom:12px; z-index:5;">
            <div class="tp-card" style="padding:12px 14px; display:flex; flex-wrap:wrap; justify-content:flex-end; gap:10px;">
                <a href="{{ route('seller.dashboard') }}" class="tp-btn">ยกเลิก</a>
                <button type="submit" class="tp-btn tp-btn-primary" style="padding:0 24px;" :disabled="busy">
                    <span x-show="!busy">💾 บันทึกการตั้งค่า</span>
                    <span x-show="busy" x-cloak>กำลังบันทึก…</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
<script>
/**
 * แบนเนอร์ร้าน: พรีวิวรูปที่เลือก + ลากขึ้น-ลงเพื่อเลือกตำแหน่ง (เก็บใน banner_position_y)
 */
function bannerEditor(initialSrc, initialY) {
    return {
        src: initialSrc || null,
        y: Number(initialY) || 0,
        dragging: false,
        startY: 0,
        startPos: 0,
        pick(e) {
            const f = e.target.files && e.target.files[0];
            if (!f) return;
            if (f.size > 4 * 1024 * 1024) { alert('แบนเนอร์ต้องไม่เกิน 4MB'); e.target.value = ''; return; }
            const r = new FileReader();
            r.onload = (ev) => { this.src = ev.target.result; this.y = 0; };
            r.readAsDataURL(f);
        },
        start(e) {
            if (!this.src) return;
            this.dragging = true;
            this.startY = e.clientY;
            this.startPos = this.y;
        },
        move(e) {
            if (!this.dragging) return;
            this.y = this.startPos + (e.clientY - this.startY);
            this.clamp();
        },
        end() { this.dragging = false; },
        clamp() {
            const box = this.$refs.box, img = this.$refs.img;
            if (!box || !img) return;
            const min = Math.min(0, box.offsetHeight - img.offsetHeight);
            this.y = Math.max(min, Math.min(0, this.y));
        }
    };
}

/**
 * แผนที่ปักหมุดจุดรับของ (Leaflet + OpenStreetMap) — หมุดลากได้ / แตะแผนที่เพื่อย้าย / ใช้ GPS ของเครื่อง
 */
function pickupMap(cfg) {
    const BKK = [13.7563, 100.5018];
    return {
        enabled: !!cfg.enabled,
        lat: cfg.lat !== null ? Number(cfg.lat).toFixed(7) : '',
        lng: cfg.lng !== null ? Number(cfg.lng).toFixed(7) : '',
        ready: false,
        locating: false,
        locError: '',
        mapError: '',
        _map: null,
        _marker: null,
        init() {
            let tries = 0;
            const boot = () => {
                if (window.L) return this.setup();
                if (++tries > 60) { this.mapError = 'โหลดแผนที่ไม่ได้ กรอกพิกัดเองด้านล่างได้'; return; }
                setTimeout(boot, 150);
            };
            boot();
        },
        setup() {
            const has = this.lat !== '' && this.lng !== '';
            const center = has ? [Number(this.lat), Number(this.lng)] : BKK;
            this._map = window.L.map(this.$refs.map, { scrollWheelZoom: false }).setView(center, has ? 16 : 11);
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(this._map);
            if (has) this.placeMarker(center);
            this._map.on('click', (e) => this.setPoint(e.latlng.lat, e.latlng.lng));
            this.ready = true;
            setTimeout(() => this._map && this._map.invalidateSize(), 200);
        },
        placeMarker(latlng) {
            if (!this._map) return;
            if (!this._marker) {
                this._marker = window.L.marker(latlng, { draggable: true }).addTo(this._map);
                this._marker.on('dragend', () => {
                    const p = this._marker.getLatLng();
                    this.setPoint(p.lat, p.lng, false);
                });
            } else {
                this._marker.setLatLng(latlng);
            }
        },
        setPoint(lat, lng, move = true) {
            this.lat = Number(lat).toFixed(7);
            this.lng = Number(lng).toFixed(7);
            this.placeMarker([Number(this.lat), Number(this.lng)]);
            if (move && this._map) this._map.setView([Number(this.lat), Number(this.lng)], Math.max(this._map.getZoom(), 16));
        },
        syncFromInputs() {
            const la = parseFloat(this.lat), ln = parseFloat(this.lng);
            if (isFinite(la) && isFinite(ln) && Math.abs(la) <= 90 && Math.abs(ln) <= 180) this.setPoint(la, ln);
        },
        locate() {
            if (!navigator.geolocation) { this.locError = 'เบราว์เซอร์นี้ไม่รองรับการระบุตำแหน่ง'; return; }
            this.locating = true;
            this.locError = '';
            navigator.geolocation.getCurrentPosition(
                (p) => { this.setPoint(p.coords.latitude, p.coords.longitude); this.locating = false; },
                () => { this.locError = 'ระบุตำแหน่งไม่ได้ กรุณาอนุญาตการเข้าถึงตำแหน่ง หรือแตะบนแผนที่แทน'; this.locating = false; },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        }
    };
}
</script>
@endpush
