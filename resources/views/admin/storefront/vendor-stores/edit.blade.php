@extends('layouts.admin-v4')

@section('title', 'แก้ไขร้าน ' . $store->store_name)

@php
    $bad = 'var(--tp-bad,#d9534f)';
    $lbl = 'display:block; font-size:12px; color:var(--ink2); font-weight:600; margin-bottom:6px;';
    $hint = 'font-size:11.5px; color:var(--ink2); margin-top:5px; line-height:1.5;';
    $check = 'display:flex; align-items:flex-start; gap:10px; font-size:13px; cursor:pointer; padding:12px 13px; border-radius:13px;';
    $packages = $packages ?? collect();
    // ร้านที่ถูกระงับ/ปิดถาวร/รออนุมัติ เปิดขายจากสวิตช์ไม่ได้ (ต้องเปิดคืน/อนุมัติที่หน้ารายละเอียด) → ไม่แสดงสวิตช์เปิดขาย
    $activeLocked = ! $store->is_active && in_array($store->status, ['suspended', 'closed', 'pending'], true);
    $activeLockedText = match ($store->status) {
        'suspended' => 'ร้านนี้ถูกระงับอยู่ — เปิดร้านคืนด้วยปุ่ม "ยกเลิกการระงับ" ในหน้ารายละเอียดร้าน',
        'closed' => 'ร้านนี้ปิดถาวร (ใบสมัครถูกปฏิเสธหรือบัญชีเจ้าของถูกลบ) — เปิดขายจากหน้านี้ไม่ได้',
        default => 'ร้านนี้ยังรออนุมัติ — อนุมัติที่หน้ารายละเอียดร้านหรือหน้าคำขอเปิดร้าน',
    };
    $switches = array_values(array_filter([
        $activeLocked ? null : ['is_active', 'เปิดขาย', 'ร้านรับออเดอร์ได้ (ปิดชั่วคราวได้ที่นี่ ถ้าต้องการระงับพร้อมเหตุผลใช้หน้ารายละเอียดร้าน)', (bool) $store->is_active],
        ['is_verified', 'ร้านยืนยันแล้ว', 'แสดงป้าย ✓ ยืนยันแล้ว บนหน้าร้าน', (bool) $store->is_verified],
        ['is_featured_home', 'ร้านแนะนำหน้าแรก', 'เรียงลำดับได้ที่หน้า "ร้านแนะนำ"', (bool) $store->is_featured_home],
        ['vat_registered', 'จดทะเบียน VAT', 'ระบบหัก VAT 7/107 จากยอดขายตอนแบ่งเงิน แล้วนำส่งแทนร้าน — ต้องมีเลขผู้เสียภาษี 13 หลัก', (bool) $store->vat_registered],
        ['rider_delivery_enabled', 'ส่งด้วยไรเดอร์', $store->hasPickupLocation() ? 'ร้านตั้งจุดรับของแล้ว' : 'ร้านยังไม่ตั้งจุดรับของ — เปิดไม่ได้จนกว่าร้านจะตั้งค่า', (bool) $store->rider_delivery_enabled],
    ]));
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
        <div style="min-width:0;">
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">หลังบ้าน · ร้านค้า · แก้ไข</div>
            <h1 style="font-size:clamp(20px,4vw,27px); font-weight:800; margin:4px 0 0; overflow-wrap:anywhere;">แก้ไขร้าน {{ $store->store_name }}</h1>
            <div style="font-size:12.5px; color:var(--ink2); margin-top:4px;">เจ้าของ: {{ $store->user?->name ?? '-' }} · {{ $store->user?->email }}</div>
        </div>
        <a href="{{ route('admin.storefront.vendor-stores.show', $store) }}" class="tp-btn tp-btn-sm"><i class="fas fa-arrow-left"></i> กลับหน้าร้าน</a>
    </div>

    @if($errors->any())
        <div class="tp-card" style="padding:14px 18px; border-left:4px solid {{ $bad }};">
            <div style="font-weight:700; color:{{ $bad }}; margin-bottom:6px;"><i class="fas fa-circle-exclamation"></i> บันทึกไม่สำเร็จ</div>
            <ul style="margin:0; padding-left:18px; font-size:13px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.storefront.vendor-stores.update', $store) }}"
          style="display:flex; flex-wrap:wrap; gap:16px; align-items:flex-start;"
          x-data="{ busy: false, primary: @js((string) old('primary_color', $store->primary_color ?: '#f97316')), secondary: @js((string) old('secondary_color', $store->secondary_color ?: '#ec4899')) }"
          @submit="busy = true">
        @csrf
        @method('PUT')

        <div style="flex:2 1 420px; min-width:0; display:flex; flex-direction:column; gap:16px;">
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-store" style="color:var(--accent1);"></i> ข้อมูลร้าน</div>
                <label style="{{ $lbl }}">ชื่อร้าน <span style="color:{{ $bad }};">*</span></label>
                <input type="text" name="store_name" value="{{ old('store_name', $store->store_name) }}" required maxlength="255" class="tp-input">
                <label style="{{ $lbl }} margin-top:12px;">คำอธิบายร้าน</label>
                <textarea name="store_description" rows="4" maxlength="1000" class="tp-input">{{ old('store_description', $store->store_description) }}</textarea>
            </div>

            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-percent" style="color:var(--accent1);"></i> แพ็กเกจและ GP</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr)); gap:14px;">
                    <div>
                        <label style="{{ $lbl }}">แพ็กเกจร้าน</label>
                        <select name="package_id" class="tp-input">
                            <option value="">— ไม่มีแพ็กเกจ (ใช้อัตรา GP ของร้าน) —</option>
                            @foreach($packages as $package)
                                <option value="{{ $package->id }}" @selected((string) old('package_id', $store->package_id) === (string) $package->id)>
                                    {{ $package->display_name ?: $package->package_name }} · GP {{ rtrim(rtrim(number_format((float) $package->commission_rate, 2), '0'), '.') }}%{{ $package->is_active ? '' : ' (ปิดขาย)' }}
                                </option>
                            @endforeach
                        </select>
                        <div style="{{ $hint }}">เปลี่ยนแพ็กเกจโดยแอดมินจะไม่เก็บเงินร้าน — อัตรา GP ของแพ็กเกจมีผลกับออเดอร์ใหม่ทันที</div>
                    </div>
                    <div>
                        <label style="{{ $lbl }}">อัตรา GP ของร้าน (%)</label>
                        <input type="number" name="commission_rate" step="0.01" min="0" max="100" value="{{ old('commission_rate', $store->commission_rate) }}" class="tp-input tp-num">
                        <div style="{{ $hint }}">ใช้เฉพาะร้านที่ไม่มีแพ็กเกจ · ถ้าเปิดโปรฯ GP ฟรี ระบบจะไม่เก็บ GP จนกว่าโปรฯ จะจบ</div>
                    </div>
                </div>
            </div>

            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:12px;"><i class="fas fa-palette" style="color:var(--accent1);"></i> สีหน้าร้าน</div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,200px),1fr)); gap:14px;">
                    <div>
                        <label style="{{ $lbl }}">สีหลัก</label>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <input type="color" x-model="primary" aria-label="เลือกสีหลัก" style="width:48px; height:44px; border:0; padding:0; background:transparent; cursor:pointer; flex:none;">
                            <input type="text" name="primary_color" x-model="primary" required maxlength="7" pattern="#?([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})"
                                   title="รหัสสี เช่น #f97316" class="tp-input tp-num">
                        </div>
                    </div>
                    <div>
                        <label style="{{ $lbl }}">สีรอง</label>
                        <div style="display:flex; gap:10px; align-items:center;">
                            <input type="color" x-model="secondary" aria-label="เลือกสีรอง" style="width:48px; height:44px; border:0; padding:0; background:transparent; cursor:pointer; flex:none;">
                            <input type="text" name="secondary_color" x-model="secondary" required maxlength="7" pattern="#?([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})"
                                   title="รหัสสี เช่น #ec4899" class="tp-input tp-num">
                        </div>
                    </div>
                </div>
                <div style="{{ $hint }}">รหัสสี 6 หลักแบบ #rrggbb (หรือย่อ #rgb) — เว้นว่างไม่ได้</div>
                <div style="height:44px; border-radius:13px; margin-top:12px;" :style="{ background: 'linear-gradient(90deg,' + primary + ',' + secondary + ')' }"></div>
            </div>
        </div>

        <div style="flex:1 1 280px; min-width:0; display:flex; flex-direction:column; gap:16px;">
            <div class="tp-card">
                <div class="tp-section-h" style="margin-bottom:10px;"><i class="fas fa-toggle-on" style="color:var(--accent1);"></i> สถานะและการตั้งค่า</div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    @if($activeLocked)
                        <div class="tp-well" style="{{ $check }} cursor:default;">
                            <i class="fas fa-lock" style="color:var(--ink2); margin-top:3px;"></i>
                            <span><span style="font-weight:700;">เปิดขาย: ปิดอยู่</span><br><span style="font-size:11.5px; color:var(--ink2);">{{ $activeLockedText }}</span></span>
                        </div>
                    @endif
                    @foreach($switches as [$name, $label, $desc, $value])
                        <input type="hidden" name="{{ $name }}" value="0">
                        <label class="tp-well" style="{{ $check }}">
                            <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) old($name, $value)) style="accent-color:var(--accent1); width:18px; height:18px; margin-top:2px; flex:none;">
                            <span><span style="font-weight:700;">{{ $label }}</span><br><span style="font-size:11.5px; color:var(--ink2);">{{ $desc }}</span></span>
                        </label>
                    @endforeach
                </div>

                {{-- เลขผู้เสียภาษี: บังคับ 13 หลักเมื่อจดทะเบียน VAT (ตรวจที่ controller แบบเดียวกับฝั่งผู้ขาย + บันทึกประวัติ) --}}
                <label style="{{ $lbl }} margin-top:14px;">เลขประจำตัวผู้เสียภาษี</label>
                <input type="text" name="tax_id" value="{{ old('tax_id', $store->tax_id) }}" maxlength="50" inputmode="numeric"
                       class="tp-input tp-num" placeholder="13 หลัก เช่น 0105561234567">
                @error('tax_id')<div style="font-size:12px; color:{{ $bad }}; margin-top:5px;">{{ $message }}</div>@enderror
                <div style="{{ $hint }}">จำเป็นเมื่อเปิด "จดทะเบียน VAT" · การเปลี่ยน VAT แพ็กเกจ และอัตรา GP ถูกบันทึกประวัติทุกครั้ง</div>
            </div>

            <div style="display:flex; gap:10px;">
                <a href="{{ route('admin.storefront.vendor-stores.show', $store) }}" class="tp-btn" style="flex:1;">ยกเลิก</a>
                <button type="submit" class="tp-btn tp-btn-primary" style="flex:1;" :disabled="busy">
                    <i class="fas" :class="busy ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i> บันทึก
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
