{{--
 | ช่องข้อมูลร้าน (สมัครร้าน / ตั้งค่าร้าน) — ธีม V4
 | ใช้: @include('taladsod.partials.shop-form', ['shopData' => ?FreshMarketSeller])
 | ฟิลด์: shop_name*, shop_description, phone*, address*, sub_district, district, province, latitude*, longitude* (ปักหมุด)
 --}}
@php
    $sf = $shopData ?? null;
    $pinLat = old('latitude', $sf?->latitude !== null ? (float) $sf->latitude : null);
    $pinLng = old('longitude', $sf?->longitude !== null ? (float) $sf->longitude : null);
@endphp

<section class="tp-card ts-stack" aria-labelledby="sf-info-h">
    <h2 id="sf-info-h" class="ts-h2"><i class="fas fa-store" style="color:var(--accent2);" aria-hidden="true"></i> ข้อมูลร้าน</h2>
    <div>
        <label class="ts-label" for="sf-name">ชื่อร้าน <span class="req">*</span></label>
        <input id="sf-name" type="text" name="shop_name" class="tp-input" required maxlength="200" value="{{ old('shop_name', $sf->shop_name ?? '') }}" placeholder="เช่น กะเพราป้าแดง, ผักสวนลุงชม">
        @error('shop_name')<p class="ts-err">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="ts-label" for="sf-desc">แนะนำร้าน</label>
        <textarea id="sf-desc" name="shop_description" class="tp-input" rows="3" maxlength="1000" placeholder="ขายอะไร เด่นเรื่องอะไร เปิดกี่โมง">{{ old('shop_description', $sf->shop_description ?? '') }}</textarea>
        @error('shop_description')<p class="ts-err">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="ts-label" for="sf-phone">เบอร์โทรร้าน <span class="req">*</span></label>
        <input id="sf-phone" type="tel" name="phone" class="tp-input" required maxlength="20" inputmode="tel" autocomplete="tel" value="{{ old('phone', $sf->phone ?? auth()->user()?->phone ?? '') }}" placeholder="08x-xxx-xxxx">
        <p class="ts-help">ลูกค้าเห็นเบอร์หลังร้านรับออเดอร์แล้วเท่านั้น</p>
        @error('phone')<p class="ts-err">{{ $message }}</p>@enderror
    </div>
</section>

<section class="tp-card ts-stack" aria-labelledby="sf-addr-h">
    <h2 id="sf-addr-h" class="ts-h2"><i class="fas fa-map-location-dot" style="color:var(--accent2);" aria-hidden="true"></i> ที่อยู่และหมุดร้าน</h2>
    <div>
        <label class="ts-label" for="sf-address">ที่อยู่ร้าน <span class="req">*</span></label>
        <textarea id="sf-address" name="address" class="tp-input" rows="2" required maxlength="500" placeholder="บ้านเลขที่ ซอย ถนน หรือจุดสังเกต">{{ old('address', $sf->address ?? '') }}</textarea>
        @error('address')<p class="ts-err">{{ $message }}</p>@enderror
    </div>
    <div class="ts-grid" style="--ts-min:160px; gap:10px;">
        <div>
            <label class="ts-label" for="sf-sub">ตำบล/แขวง</label>
            <input id="sf-sub" type="text" name="sub_district" class="tp-input" maxlength="100" value="{{ old('sub_district', $sf->sub_district ?? '') }}">
        </div>
        <div>
            <label class="ts-label" for="sf-district">อำเภอ/เขต</label>
            <input id="sf-district" type="text" name="district" class="tp-input" maxlength="100" value="{{ old('district', $sf->district ?? '') }}">
        </div>
        <div>
            <label class="ts-label" for="sf-province">จังหวัด</label>
            <input id="sf-province" type="text" name="province" class="tp-input" maxlength="100" value="{{ old('province', $sf->province ?? '') }}">
        </div>
    </div>

    <div x-data="tsPinMap({ id: 'shop', lat: {{ \Illuminate\Support\Js::from($pinLat) }}, lng: {{ \Illuminate\Support\Js::from($pinLng) }}, kind: 'shop' })" class="ts-stack" style="gap:8px;">
        <div class="ts-row" style="justify-content:space-between;">
            <span class="ts-label" style="margin:0;">ปักหมุดร้าน <span class="req">*</span></span>
            <button type="button" class="ts-btn3d sm ts-tone-info" x-on:click="locate()" :disabled="locating">
                <i class="fas" :class="locating ? 'fa-circle-notch ts-spin' : 'fa-location-crosshairs'" aria-hidden="true"></i> ใช้ตำแหน่งปัจจุบัน
            </button>
        </div>
        <div class="ts-map sm" x-ref="map" aria-label="แผนที่ปักหมุดร้าน"></div>
        <input type="hidden" name="latitude" :value="lat ?? ''" value="{{ $pinLat }}">
        <input type="hidden" name="longitude" :value="lng ?? ''" value="{{ $pinLng }}">
        <p class="ts-help" style="margin:0;" x-show="lat !== null"><i class="fas fa-circle-check" style="color:var(--ts-ok);" aria-hidden="true"></i> ปักหมุดแล้ว — ลากหมุดหรือแตะแผนที่เพื่อปรับให้ตรง</p>
        <p class="ts-help" style="margin:0;" x-show="lat === null">แตะแผนที่หรือกด "ใช้ตำแหน่งปัจจุบัน" — ใช้คำนวณระยะทางและเรียกไรเดอร์มารับของ</p>
        <p class="ts-help" style="margin:0;">รถเข็น/ตลาดนัด: ปักที่จุดขายประจำหรือที่บ้านได้ — ถ้าตั้งเป็น "ร้านเคลื่อนที่" ลูกค้าจะไม่เห็นหมุดนี้ เห็นเฉพาะตำแหน่งตอนเปิดร้าน</p>
        <p class="ts-err" x-show="error" x-text="error" x-cloak></p>
        @error('latitude')<p class="ts-err">{{ $message }}</p>@enderror
    </div>
</section>
