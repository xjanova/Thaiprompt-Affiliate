{{--
    ฟอร์มที่อยู่จัดส่ง (ใช้ร่วมหน้าเพิ่ม/แก้ไข) — ชื่อช่องตรงกับ ShippingAddressController::store/update
    $address = null (เพิ่มใหม่) หรือ ShippingAddress (แก้ไข) · $action = URL ปลายทาง · $method = POST|PUT · $submitLabel
--}}
@php
    $address ??= null;
    $val = fn (string $field, $fallback = '') => old($field, $address?->{$field} ?? $fallback);
@endphp

<form method="POST" action="{{ $action }}" class="nvm-form" novalidate x-data="{ busy: false }" @submit="if (busy) { $event.preventDefault(); } busy = true" @pageshow.window="busy = false">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    @if($errors->any())
        <div class="nvm-confirm" role="alert">
            <p><b>บันทึกไม่สำเร็จ</b> กรุณาตรวจช่องที่มีข้อความสีแดง</p>
        </div>
    @endif

    <div class="nvm-os-grid2">
        <div class="nvm-field">
            <label class="nvm-label" for="recipient_name">ชื่อผู้รับ <em>*</em></label>
            <input type="text" id="recipient_name" name="recipient_name" maxlength="255" required autocomplete="name"
                   class="nvm-input @error('recipient_name') is-bad @enderror" value="{{ $val('recipient_name') }}" placeholder="ชื่อ-นามสกุล ผู้รับสินค้า">
            @error('recipient_name')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="phone_number">เบอร์โทรศัพท์ <em>*</em></label>
            <input type="tel" id="phone_number" name="phone_number" maxlength="20" required inputmode="tel" autocomplete="tel"
                   class="nvm-input @error('phone_number') is-bad @enderror" value="{{ $val('phone_number') }}" placeholder="0812345678">
            @error('phone_number')<span class="nvm-err">{{ $message }}</span>@enderror
        </div>
    </div>

    <div class="nvm-field">
        <label class="nvm-label" for="address_line_1">ที่อยู่ (บ้านเลขที่ ซอย ถนน) <em>*</em></label>
        <input type="text" id="address_line_1" name="address_line_1" maxlength="255" required autocomplete="address-line1"
               class="nvm-input @error('address_line_1') is-bad @enderror" value="{{ $val('address_line_1') }}" placeholder="บ้านเลขที่ ซอย ถนน">
        @error('address_line_1')<span class="nvm-err">{{ $message }}</span>@enderror
    </div>

    <div class="nvm-field">
        <label class="nvm-label" for="address_line_2">ที่อยู่เพิ่มเติม (ถ้ามี)</label>
        <input type="text" id="address_line_2" name="address_line_2" maxlength="255" autocomplete="address-line2"
               class="nvm-input @error('address_line_2') is-bad @enderror" value="{{ $val('address_line_2') }}" placeholder="หมู่บ้าน คอนโด อาคาร ชั้น">
        @error('address_line_2')<span class="nvm-err">{{ $message }}</span>@enderror
    </div>

    {{-- ระบบเลือกที่อยู่ (จังหวัด → อำเภอ → ตำบล → รหัสไปรษณีย์) — กรอบ nvm-addr แต่งให้เข้าธีมโนวา --}}
    <div class="nvm-addr">
        <x-thai-address-picker
            province-field="province"
            district-field="district"
            sub-district-field="sub_district"
            postal-code-field="postal_code"
            :province-value="$val('province')"
            :district-value="$val('district')"
            :sub-district-value="$val('sub_district')"
            :postal-code-value="$val('postal_code')"
        />
    </div>
    @foreach(['province', 'district', 'sub_district', 'postal_code'] as $field)
        @error($field)<span class="nvm-err">{{ $message }}</span>@enderror
    @endforeach

    <input type="hidden" name="country" value="{{ $address?->country ?: 'ประเทศไทย' }}">

    <div class="nvm-field">
        <label class="nvm-label" for="notes">หมายเหตุถึงผู้ส่ง (ถ้ามี)</label>
        <textarea id="notes" name="notes" rows="3" maxlength="500" class="nvm-input @error('notes') is-bad @enderror"
                  placeholder="เช่น จุดสังเกต ฝากไว้กับ รปภ.">{{ $val('notes') }}</textarea>
        @error('notes')<span class="nvm-err">{{ $message }}</span>@enderror
    </div>

    <label class="nvm-check" for="is_default">
        <input type="checkbox" id="is_default" name="is_default" value="1" @checked(old('is_default', $address?->is_default))>
        <span>ตั้งเป็นที่อยู่เริ่มต้น</span>
    </label>

    <div class="nvm-os-form-foot">
        <a class="nvm-btn nvm-btn--line" href="{{ route('shipping-addresses.index') }}">ยกเลิก</a>
        <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="busy ? 'กำลังบันทึก…' : @js($submitLabel)">{{ $submitLabel }}</span></button>
    </div>
</form>
