{{--
 | ช่องข้อมูลร้าน (สมัครร้าน / ตั้งค่าร้าน) — ธีมโนวา
 | ใช้: @include('taladsod.partials.shop-form', ['shopData' => ?FreshMarketSeller])
 | ต้องมีในหน้า: @include('taladsod.partials.kit-js') + <x-theme-v4.leaflet /> (คอมโพเนนต์ tsPinMap)
 | ฟิลด์: shop_name*, shop_description, phone*, address*, sub_district, district, province, latitude*, longitude* (ปักหมุด)
 --}}
@php
    $sf = $shopData ?? null;
    $pinLat = old('latitude', $sf?->latitude !== null ? (float) $sf->latitude : null);
    $pinLng = old('longitude', $sf?->longitude !== null ? (float) $sf->longitude : null);
@endphp

<section class="nvm-card nvm-box" aria-labelledby="sf-info-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Shop info</small><h2 id="sf-info-h">ข้อมูลร้าน</h2></div></div>
    <div class="nvm-form">
        <div class="nvm-field">
            <label class="nvm-label" for="sf-name">ชื่อร้าน <em>*</em></label>
            <input id="sf-name" type="text" name="shop_name" class="nvm-input @error('shop_name') is-bad @enderror" required maxlength="200" value="{{ old('shop_name', $sf->shop_name ?? '') }}" placeholder="เช่น กะเพราป้าแดง, ผักสวนลุงชม">
            @error('shop_name')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="sf-desc">แนะนำร้าน</label>
            <textarea id="sf-desc" name="shop_description" class="nvm-input nvm-textarea" rows="3" maxlength="1000" placeholder="ขายอะไร เด่นเรื่องอะไร เปิดกี่โมง">{{ old('shop_description', $sf->shop_description ?? '') }}</textarea>
            @error('shop_description')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="sf-phone">เบอร์โทรร้าน <em>*</em></label>
            <input id="sf-phone" type="tel" name="phone" class="nvm-input @error('phone') is-bad @enderror" required maxlength="20" inputmode="tel" autocomplete="tel" value="{{ old('phone', $sf->phone ?? auth()->user()?->phone ?? '') }}" placeholder="08x-xxx-xxxx">
            <span class="nvm-help">ลูกค้าเห็นเบอร์หลังร้านรับออเดอร์แล้วเท่านั้น</span>
            @error('phone')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>
    </div>
</section>

<section class="nvm-card nvm-box" aria-labelledby="sf-addr-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Location</small><h2 id="sf-addr-h">ที่อยู่และหมุดร้าน</h2></div></div>
    <div class="nvm-form">
        <div class="nvm-field">
            <label class="nvm-label" for="sf-address">ที่อยู่ร้าน <em>*</em></label>
            <textarea id="sf-address" name="address" class="nvm-input nvm-textarea @error('address') is-bad @enderror" rows="2" required maxlength="500" placeholder="บ้านเลขที่ ซอย ถนน หรือจุดสังเกต">{{ old('address', $sf->address ?? '') }}</textarea>
            @error('address')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>
        <div class="nvm-td-cols">
            <div class="nvm-field">
                <label class="nvm-label" for="sf-sub">ตำบล/แขวง</label>
                <input id="sf-sub" type="text" name="sub_district" class="nvm-input" maxlength="100" value="{{ old('sub_district', $sf->sub_district ?? '') }}">
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="sf-district">อำเภอ/เขต</label>
                <input id="sf-district" type="text" name="district" class="nvm-input" maxlength="100" value="{{ old('district', $sf->district ?? '') }}">
            </div>
            <div class="nvm-field">
                <label class="nvm-label" for="sf-province">จังหวัด</label>
                <input id="sf-province" type="text" name="province" class="nvm-input" maxlength="100" value="{{ old('province', $sf->province ?? '') }}">
            </div>
        </div>

        <div class="nvm-field" x-data="tsPinMap({ id: 'shop', lat: {{ \Illuminate\Support\Js::from($pinLat) }}, lng: {{ \Illuminate\Support\Js::from($pinLng) }}, kind: 'shop' })">
            <div class="nvm-td-acts nvm-td-acts--split">
                <span class="nvm-label">ปักหมุดร้าน <em>*</em></span>
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-on:click="locate()" :disabled="locating">
                    <svg class="nvm-ico"><use href="#nvm-i-pin"/></svg><span x-text="locating ? 'กำลังหาตำแหน่ง…' : 'ใช้ตำแหน่งปัจจุบัน'">ใช้ตำแหน่งปัจจุบัน</span>
                </button>
            </div>
            <div class="nvm-td-map" x-ref="map" aria-label="แผนที่ปักหมุดร้าน"></div>
            <input type="hidden" name="latitude" :value="lat ?? ''" value="{{ $pinLat }}">
            <input type="hidden" name="longitude" :value="lng ?? ''" value="{{ $pinLng }}">
            <span class="nvm-help" x-show="lat !== null" @if($pinLat === null) x-cloak @endif>ปักหมุดแล้ว — ลากหมุดหรือแตะแผนที่เพื่อปรับให้ตรง</span>
            <span class="nvm-help" x-show="lat === null" @if($pinLat !== null) x-cloak @endif>แตะแผนที่หรือกด "ใช้ตำแหน่งปัจจุบัน" — ใช้คำนวณระยะทางและเรียกไรเดอร์มารับของ</span>
            <span class="nvm-help">รถเข็น/ตลาดนัด: ปักที่จุดขายประจำหรือที่บ้านได้ — ถ้าตั้งเป็น "ร้านเคลื่อนที่" ลูกค้าจะไม่เห็นหมุดนี้ เห็นเฉพาะตำแหน่งตอนเปิดร้าน</span>
            <span class="nvm-err" x-show="error" x-text="error" x-cloak></span>
            @error('latitude')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>
    </div>
</section>
