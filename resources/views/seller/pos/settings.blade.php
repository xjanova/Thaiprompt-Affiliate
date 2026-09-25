@extends('layouts.seller-v4')

@section('title', 'ตั้งค่า POS')

@php
    // ค่าปัจจุบัน (ให้ old() ชนะเสมอเมื่อ validate ไม่ผ่าน)
    $val = fn ($key, $default = null) => old($key, $settings->{$key} ?? $default);
    $on = fn ($key, $default = false) => (bool) old($key, $settings->{$key} ?? $default);
    $payOptions = ['cash' => '💵 เงินสด', 'card' => '💳 บัตรเครดิต/เดบิต', 'qr' => '📱 QR พร้อมเพย์', 'bank_transfer' => '🏦 โอนเงิน', 'other' => '• อื่น ๆ'];
    $enabledPay = (array) old('enabled_payment_methods', $settings->enabled_payment_methods ?: ['cash', 'card', 'qr']);

    $featureToggles = [
        ['dual_screen_enabled', 'จอฝั่งลูกค้า (Dual Screen)', 'แสดงรายการและโฆษณาบนจอที่สองให้ลูกค้าเห็น', false],
        ['offline_mode_enabled', 'ใช้งานออฟไลน์', 'เครื่อง POS บันทึกการขายได้แม้เน็ตหลุด แล้วค่อยซิงก์', false],
        ['show_product_images', 'แสดงรูปสินค้า', 'แสดงภาพสินค้าในหน้าขาย', true],
        ['show_stock_levels', 'แสดงจำนวนสต็อก', 'แสดงจำนวนคงเหลือใต้สินค้าแต่ละชิ้น', true],
        ['real_time_stock_sync', 'ตัดสต็อกทันที', 'อัปเดตสต็อกหน้าร้านออนไลน์ทันทีที่ขายหน้าร้าน', true],
        ['prevent_negative_stock', 'ห้ามสต็อกติดลบ', 'ขายสินค้าที่หมดแล้วไม่ได้', true],
        ['low_stock_warning', 'เตือนสินค้าใกล้หมด', 'แจ้งเตือนเมื่อสต็อกต่ำกว่าเกณฑ์ด้านล่าง', true],
        ['require_cash_management', 'บังคับนับเงินสดเปิด–ปิดกะ', 'ให้พนักงานกรอกเงินสดตั้งต้นและเงินสดปิดกะ', false],
    ];
@endphp

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="ตั้งค่า POS" icon="⚙️" crumb="ร้านค้า · POS"
                         subtitle="ใบเสร็จ ภาษี ส่วนลด วิธีชำระเงิน และการทำงานของหน้าขาย" />

    @include('seller.pos.partials.nav')

    <x-seller-kit.errors />

    <form method="POST" action="{{ route('seller.pos.settings.update') }}" style="display:flex; flex-direction:column; gap:16px;"
          x-data="{ saving: false }" @submit="saving = true">
        @csrf
        @method('PUT')

        {{-- การแสดงผลร้าน + ใบเสร็จ --}}
        <div class="tp-card" style="display:flex; flex-direction:column; gap:14px;">
            <div class="tp-section-h">🧾 ร้านค้าและใบเสร็จ</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <div>
                    <label for="s-name" style="font-size:12.5px; font-weight:700;">ชื่อร้านบนใบเสร็จ</label>
                    <input id="s-name" type="text" name="store_display_name" maxlength="255" value="{{ $val('store_display_name', '') }}" class="tp-input" style="margin-top:6px;">
                </div>
                <div>
                    <label for="s-color" style="font-size:12.5px; font-weight:700;">สีธีมเครื่อง POS</label>
                    <input id="s-color" type="color" name="theme_color" value="{{ $val('theme_color') ?: '#e6b347' }}" class="tp-input" style="margin-top:6px; height:44px; padding:4px;">
                </div>
                <div>
                    <label for="s-header" style="font-size:12.5px; font-weight:700;">ข้อความหัวใบเสร็จ</label>
                    <input id="s-header" type="text" name="receipt_header" maxlength="255" value="{{ $val('receipt_header', '') }}" placeholder="ขอบคุณที่ใช้บริการ" class="tp-input" style="margin-top:6px;">
                </div>
                <div>
                    <label for="s-footer" style="font-size:12.5px; font-weight:700;">ข้อความท้ายใบเสร็จ</label>
                    <input id="s-footer" type="text" name="receipt_footer" maxlength="255" value="{{ $val('receipt_footer', '') }}" placeholder="โปรดเก็บใบเสร็จไว้เป็นหลักฐาน" class="tp-input" style="margin-top:6px;">
                </div>
                <div>
                    <label for="s-size" style="font-size:12.5px; font-weight:700;">ขนาดกระดาษใบเสร็จ</label>
                    <select id="s-size" name="receipt_size" class="tp-input" style="margin-top:6px;">
                        <option value="58mm" @selected($val('receipt_size') === '58mm')>58 มม.</option>
                        <option value="80mm" @selected($val('receipt_size', '80mm') === '80mm')>80 มม.</option>
                    </select>
                </div>
                <div>
                    <label for="s-copies" style="font-size:12.5px; font-weight:700;">จำนวนสำเนา</label>
                    <input id="s-copies" type="number" name="receipt_copies" min="1" max="5" value="{{ $val('receipt_copies', 1) }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
            </div>
            <label style="display:flex; align-items:center; gap:10px; font-size:13px; font-weight:700; cursor:pointer;">
                <input type="hidden" name="auto_print_receipt" value="0">
                <input type="checkbox" name="auto_print_receipt" value="1" @checked($on('auto_print_receipt')) style="width:18px; height:18px; accent-color:var(--accent1);">
                เปิดหน้าพิมพ์ใบเสร็จอัตโนมัติหลังขายเสร็จ
            </label>
        </div>

        {{-- ภาษี + ค่าบริการ --}}
        <div class="tp-card" style="display:flex; flex-direction:column; gap:14px;">
            <div class="tp-section-h">🏛️ ภาษีและค่าบริการ</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; align-items:center; gap:10px; font-size:13px; font-weight:700; cursor:pointer;">
                    <input type="hidden" name="tax_enabled" value="0">
                    <input type="checkbox" name="tax_enabled" value="1" @checked($on('tax_enabled')) style="width:18px; height:18px; accent-color:var(--accent1);">
                    คิดภาษีมูลค่าเพิ่ม (VAT)
                </label>
                <label style="display:flex; align-items:center; gap:10px; font-size:13px; font-weight:700; cursor:pointer;">
                    <input type="hidden" name="tax_inclusive" value="0">
                    <input type="checkbox" name="tax_inclusive" value="1" @checked($on('tax_inclusive', true)) style="width:18px; height:18px; accent-color:var(--accent1);">
                    ราคาสินค้ารวม VAT แล้ว
                </label>
                <div>
                    <label for="s-tax" style="font-size:12.5px; font-weight:700;">อัตรา VAT (%)</label>
                    <input id="s-tax" type="number" name="tax_percentage" step="0.01" min="0" max="100" value="{{ $val('tax_percentage', 7) }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
                <div>
                    <label for="s-taxid" style="font-size:12.5px; font-weight:700;">เลขประจำตัวผู้เสียภาษี</label>
                    <input id="s-taxid" type="text" name="tax_id_number" maxlength="50" inputmode="numeric" value="{{ $val('tax_id_number', '') }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
                <label style="display:flex; align-items:center; gap:10px; font-size:13px; font-weight:700; cursor:pointer;">
                    <input type="hidden" name="service_charge_enabled" value="0">
                    <input type="checkbox" name="service_charge_enabled" value="1" @checked($on('service_charge_enabled')) style="width:18px; height:18px; accent-color:var(--accent1);">
                    เก็บค่าบริการ (Service charge)
                </label>
                <div>
                    <label for="s-sc" style="font-size:12.5px; font-weight:700;">ค่าบริการ (%)</label>
                    <input id="s-sc" type="number" name="service_charge_percentage" step="0.01" min="0" max="100" value="{{ $val('service_charge_percentage', 0) }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
            </div>
        </div>

        {{-- ส่วนลด + วิธีชำระเงิน --}}
        <div class="tp-card" style="display:flex; flex-direction:column; gap:14px;">
            <div class="tp-section-h">💳 ส่วนลดและวิธีชำระเงิน</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:14px;">
                <label style="display:flex; align-items:center; gap:10px; font-size:13px; font-weight:700; cursor:pointer;">
                    <input type="hidden" name="allow_discounts" value="0">
                    <input type="checkbox" name="allow_discounts" value="1" @checked($on('allow_discounts', true)) style="width:18px; height:18px; accent-color:var(--accent1);">
                    ให้พนักงานใส่ส่วนลดได้
                </label>
                <div>
                    <label for="s-maxdisc" style="font-size:12.5px; font-weight:700;">ส่วนลดสูงสุด (% ของยอด)</label>
                    <input id="s-maxdisc" type="number" name="max_discount_percentage" step="0.01" min="0" max="100" value="{{ $val('max_discount_percentage', 100) }}" class="tp-input tp-num" style="margin-top:6px;">
                </div>
            </div>
            <div>
                <div style="font-size:12.5px; font-weight:700; margin-bottom:8px;">วิธีชำระเงินที่รับในหน้าขาย</div>
                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    @foreach($payOptions as $pKey => $pLabel)
                        <label class="tp-inset-sm" style="display:flex; align-items:center; gap:8px; padding:9px 14px; border-radius:12px; font-size:13px; font-weight:700; cursor:pointer;">
                            <input type="checkbox" name="enabled_payment_methods[]" value="{{ $pKey }}" @checked(in_array($pKey, $enabledPay, true)) style="width:17px; height:17px; accent-color:var(--accent1);">
                            {{ $pLabel }}
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ฟีเจอร์เครื่อง POS --}}
        <div class="tp-card" style="display:flex; flex-direction:column; gap:14px;">
            <div class="tp-section-h">🧩 การทำงานของหน้าขาย</div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:10px;">
                @foreach($featureToggles as [$fKey, $fLabel, $fHint, $fDefault])
                    <label class="tp-inset-sm" style="display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border-radius:14px; cursor:pointer;">
                        <input type="hidden" name="{{ $fKey }}" value="0">
                        <input type="checkbox" name="{{ $fKey }}" value="1" @checked($on($fKey, $fDefault)) style="width:18px; height:18px; margin-top:2px; accent-color:var(--accent1);">
                        <span>
                            <span style="display:block; font-size:13px; font-weight:700;">{{ $fLabel }}</span>
                            <span style="display:block; font-size:11.5px; color:var(--ink2); margin-top:2px;">{{ $fHint }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <div style="max-width:240px;">
                <label for="s-lowstock" style="font-size:12.5px; font-weight:700;">เกณฑ์สินค้าใกล้หมด (ชิ้น)</label>
                <input id="s-lowstock" type="number" name="low_stock_threshold" min="0" value="{{ $val('low_stock_threshold', 10) }}" class="tp-input tp-num" style="margin-top:6px;">
            </div>
        </div>

        <div class="tp-card" style="display:flex; justify-content:flex-end; flex-wrap:wrap; gap:10px;">
            <a href="{{ route('seller.pos.index') }}" class="tp-btn">ยกเลิก</a>
            <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving" :style="{ opacity: saving ? .6 : 1 }">
                <span x-text="saving ? 'กำลังบันทึก…' : '💾 บันทึกการตั้งค่า'"></span>
            </button>
        </div>
    </form>
</div>
@endsection
