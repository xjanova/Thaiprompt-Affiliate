@extends('layouts.user-v4')

@section('title', 'แก้ไขที่อยู่จัดส่ง')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px; max-width:760px; margin-inline:auto; width:100%;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:22px 24px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 16%, transparent), transparent 70%);">
        <div style="display:flex; align-items:center; gap:14px;">
            <span class="tp-tile" style="width:56px; height:56px; border-radius:18px; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--deep1);">
                <i class="fas fa-pen"></i>
            </span>
            <div>
                <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">แก้ไขที่อยู่ · EDIT ADDRESS</div>
                <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:3px 0 0; color:var(--ink);">แก้ไขที่อยู่จัดส่ง</h1>
                <div style="font-size:13px; color:var(--ink2); margin-top:2px;">แก้ไขข้อมูลที่อยู่จัดส่งของคุณ</div>
            </div>
        </div>
    </div>

    {{-- ── ฟอร์ม ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <form action="{{ route('shipping-addresses.update', $address->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="display:flex; flex-direction:column; gap:18px;">
                {{-- ชื่อผู้รับ --}}
                <div>
                    <label for="recipient_name" style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">ชื่อผู้รับ <span style="color:#d9534f;">*</span></label>
                    <input type="text" id="recipient_name" name="recipient_name" value="{{ old('recipient_name', $address->recipient_name) }}" required
                           style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;"
                           placeholder="ชื่อ-นามสกุล ผู้รับสินค้า">
                    @error('recipient_name')<p style="color:#d9534f; font-size:12.5px; margin-top:6px;">{{ $message }}</p>@enderror
                </div>

                {{-- เบอร์โทร --}}
                <div>
                    <label for="phone_number" style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">เบอร์โทรศัพท์ <span style="color:#d9534f;">*</span></label>
                    <input type="tel" id="phone_number" name="phone_number" value="{{ old('phone_number', $address->phone_number) }}" required
                           style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;"
                           placeholder="0812345678">
                    @error('phone_number')<p style="color:#d9534f; font-size:12.5px; margin-top:6px;">{{ $message }}</p>@enderror
                </div>

                {{-- ที่อยู่บรรทัด 1 --}}
                <div>
                    <label for="address_line_1" style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">ที่อยู่ (บ้านเลขที่ ถนน) <span style="color:#d9534f;">*</span></label>
                    <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $address->address_line_1) }}" required
                           style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;"
                           placeholder="บ้านเลขที่ ซอย ถนน">
                    @error('address_line_1')<p style="color:#d9534f; font-size:12.5px; margin-top:6px;">{{ $message }}</p>@enderror
                </div>

                {{-- ที่อยู่บรรทัด 2 --}}
                <div>
                    <label for="address_line_2" style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">ที่อยู่เพิ่มเติม (ถ้ามี)</label>
                    <input type="text" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $address->address_line_2) }}"
                           style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px;"
                           placeholder="หมู่บ้าน คอนโด อพาร์ทเมนท์">
                    @error('address_line_2')<p style="color:#d9534f; font-size:12.5px; margin-top:6px;">{{ $message }}</p>@enderror
                </div>

                {{-- ระบบเลือกที่อยู่อัจฉริยะ --}}
                <x-thai-address-picker
                    province-field="province"
                    district-field="district"
                    sub-district-field="sub_district"
                    postal-code-field="postal_code"
                    :province-value="old('province', $address->province ?? '')"
                    :district-value="old('district', $address->district ?? '')"
                    :sub-district-value="old('sub_district', $address->sub_district ?? '')"
                    :postal-code-value="old('postal_code', $address->postal_code ?? '')"
                />

                <input type="hidden" name="country" value="{{ $address->country ?? 'ประเทศไทย' }}">

                {{-- หมายเหตุ --}}
                <div>
                    <label for="notes" style="display:block; font-size:13px; font-weight:700; color:var(--ink); margin-bottom:7px;">หมายเหตุ (ถ้ามี)</label>
                    <textarea id="notes" name="notes" rows="3"
                              style="width:100%; padding:12px 14px; border-radius:12px; background:var(--surf); box-shadow:var(--inset-sm); border:1px solid transparent; color:var(--ink); font-size:14px; resize:vertical;"
                              placeholder="เช่น สถานที่ใกล้เคียง หรือข้อความเพิ่มเติม">{{ old('notes', $address->notes) }}</textarea>
                    @error('notes')<p style="color:#d9534f; font-size:12.5px; margin-top:6px;">{{ $message }}</p>@enderror
                </div>

                {{-- ตั้งเป็นค่าเริ่มต้น --}}
                <label for="is_default" style="display:flex; align-items:center; gap:11px; cursor:pointer;">
                    <input type="checkbox" id="is_default" name="is_default" value="1" {{ old('is_default', $address->is_default) ? 'checked' : '' }}
                           style="width:19px; height:19px; accent-color:var(--accent1);">
                    <span style="font-size:14px; font-weight:600; color:var(--ink);">ตั้งเป็นที่อยู่เริ่มต้น</span>
                </label>
            </div>

            {{-- ปุ่ม --}}
            <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:26px; padding-top:20px; border-top:1px solid color-mix(in srgb, var(--ink2) 18%, transparent);">
                <button type="submit"
                        class="tp-btn" style="flex:1; min-width:180px; padding:13px; border-radius:14px; background:linear-gradient(135deg, var(--accent1), var(--accent2)); color:#fff; font-weight:700; font-size:14.5px; box-shadow:var(--raise);">
                    <i class="fas fa-check"></i> บันทึกการแก้ไข
                </button>
                <a href="{{ route('shipping-addresses.index') }}"
                   class="tp-btn" style="flex:1; min-width:140px; padding:13px; border-radius:14px; background:var(--surf); box-shadow:var(--inset-sm); color:var(--ink); font-weight:600; font-size:14px; text-align:center;">
                    ← ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
