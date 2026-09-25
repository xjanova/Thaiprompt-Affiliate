{{--
 | ฟอร์มคูปองร้าน (ใช้ร่วม create / edit)
 | ตัวแปร: $coupon (Coupon|null), $action (url), $method ('POST'|'PUT'), $submitLabel
 | ช่องต้องตรงกับ Seller\CouponController@store/update: name, description, discount_type, discount_value,
 |   min_purchase, max_discount, usage_limit, starts_at, expires_at, is_public
 --}}
@php
    $c = $coupon ?? null;
    $fmtDate = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('Y-m-d\TH:i') : '';
    $formState = [
        'type' => old('discount_type', $c->discount_type ?? 'percentage'),
        'value' => old('discount_value', $c ? (float) $c->discount_value : 10),
        'min' => old('min_purchase', $c && (float) $c->min_purchase > 0 ? (float) $c->min_purchase : ''),
        'max' => old('max_discount', $c && $c->max_discount !== null ? (float) $c->max_discount : ''),
    ];
@endphp

<form method="POST" action="{{ $action }}" class="tp-card" style="display:flex; flex-direction:column; gap:16px;"
      x-data="couponForm(@js($formState))" @submit="saving = true">
    @csrf
    @if(($method ?? 'POST') !== 'POST')
        @method($method)
    @endif

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px;">
        <div>
            <label for="cp-name" style="font-size:12.5px; font-weight:700;">ชื่อคูปอง <span style="color:var(--tp-bad, #d9534f);">*</span></label>
            <input id="cp-name" type="text" name="name" required maxlength="255" value="{{ old('name', $c->name ?? '') }}" placeholder="เช่น ลด 10% ต้อนรับลูกค้าใหม่" class="tp-input" style="margin-top:6px;">
        </div>
        <div>
            <label for="cp-desc" style="font-size:12.5px; font-weight:700;">คำอธิบาย</label>
            <input id="cp-desc" type="text" name="description" maxlength="500" value="{{ old('description', $c->description ?? '') }}" placeholder="เงื่อนไขสั้น ๆ ที่ลูกค้าจะเห็น" class="tp-input" style="margin-top:6px;">
        </div>
    </div>

    {{-- ประเภทส่วนลด --}}
    <div>
        <div style="font-size:12.5px; font-weight:700; margin-bottom:8px;">ประเภทส่วนลด</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:10px;">
            @foreach(['percentage' => ['％', 'ลดเป็นเปอร์เซ็นต์'], 'fixed' => ['฿', 'ลดเป็นจำนวนเงิน'], 'free_shipping' => ['🚚', 'ส่งฟรี']] as $tKey => [$tIcon, $tLabel])
                <label class="tp-inset-sm" style="display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:14px; cursor:pointer;"
                       :style="{ outline: type === '{{ $tKey }}' ? '2px solid var(--accent1)' : 'none' }">
                    <input type="radio" name="discount_type" value="{{ $tKey }}" x-model="type" style="accent-color:var(--accent1);">
                    <span style="font-size:18px;" aria-hidden="true">{{ $tIcon }}</span>
                    <span style="font-size:13px; font-weight:700;">{{ $tLabel }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
        <template x-if="type !== 'free_shipping'">
            <div>
                <label for="cp-value" style="font-size:12.5px; font-weight:700;" x-text="type === 'percentage' ? 'ส่วนลด (%) *' : 'ส่วนลด (บาท) *'"></label>
                <input id="cp-value" type="number" name="discount_value" x-model.number="value" step="0.01" min="0" :max="type === 'percentage' ? 100 : null" required class="tp-input tp-num" style="margin-top:6px;">
            </div>
        </template>
        <template x-if="type === 'free_shipping'">
            <input type="hidden" name="discount_value" value="0">
        </template>
        <div>
            <label for="cp-min" style="font-size:12.5px; font-weight:700;">ยอดซื้อขั้นต่ำ (บาท)</label>
            <input id="cp-min" type="number" name="min_purchase" x-model="min" step="0.01" min="0" placeholder="ไม่กำหนด" class="tp-input tp-num" style="margin-top:6px;">
        </div>
        <template x-if="type === 'percentage'">
            <div>
                <label for="cp-max" style="font-size:12.5px; font-weight:700;">ลดสูงสุด (บาท)</label>
                <input id="cp-max" type="number" name="max_discount" x-model="max" step="0.01" min="0" placeholder="ไม่จำกัด" class="tp-input tp-num" style="margin-top:6px;">
            </div>
        </template>
        <div>
            <label for="cp-limit" style="font-size:12.5px; font-weight:700;">จำนวนสิทธิ์ทั้งหมด</label>
            <input id="cp-limit" type="number" name="usage_limit" min="1" step="1" value="{{ old('usage_limit', $c->usage_limit ?? '') }}" placeholder="ไม่จำกัด" class="tp-input tp-num" style="margin-top:6px;">
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px;">
        <div>
            <label for="cp-start" style="font-size:12.5px; font-weight:700;">เริ่มใช้ได้</label>
            <input id="cp-start" type="datetime-local" name="starts_at" value="{{ old('starts_at', $fmtDate($c->starts_at ?? null)) }}" class="tp-input" style="margin-top:6px;">
        </div>
        <div>
            <label for="cp-end" style="font-size:12.5px; font-weight:700;">หมดอายุ</label>
            <input id="cp-end" type="datetime-local" name="expires_at" value="{{ old('expires_at', $fmtDate($c->expires_at ?? null)) }}" class="tp-input" style="margin-top:6px;">
        </div>
    </div>

    <label class="tp-inset-sm" style="display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border-radius:14px; cursor:pointer;">
        <input type="hidden" name="is_public" value="0">
        <input type="checkbox" name="is_public" value="1" @checked((bool) old('is_public', $c->is_public ?? false)) style="width:18px; height:18px; margin-top:2px; accent-color:var(--accent1);">
        <span><span style="display:block; font-size:13px; font-weight:700;">แสดงคูปองให้ทุกคนเห็นที่หน้าร้าน</span><span style="display:block; font-size:11.5px; color:var(--ink2);">ไม่เลือก = ลูกค้าต้องรู้รหัสเองถึงจะใช้ได้ (เหมาะกับแจกเฉพาะกลุ่ม)</span></span>
    </label>

    {{-- ตัวอย่างคูปอง --}}
    <div class="tp-card" style="padding:14px 16px; box-shadow:var(--card-shadow-sm); background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 22%, var(--card-bg)), var(--card-bg) 75%);">
        <div style="font-size:11px; color:var(--ink2); font-weight:700;">ตัวอย่างที่ลูกค้าเห็น</div>
        <div style="font-size:18px; font-weight:800; margin-top:4px;" x-text="preview()"></div>
        <div style="font-size:12px; color:var(--ink2);" x-text="min ? ('เมื่อซื้อครบ ฿' + Number(min).toLocaleString('th-TH')) : 'ไม่มียอดขั้นต่ำ'"></div>
    </div>

    <div style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
        <a href="{{ route('seller.coupons.index') }}" class="tp-btn">ยกเลิก</a>
        <button type="submit" class="tp-btn tp-btn-primary" :disabled="saving" :style="{ opacity: saving ? .6 : 1 }">
            <span x-text="saving ? 'กำลังบันทึก…' : @js($submitLabel ?? 'บันทึกคูปอง')"></span>
        </button>
    </div>
</form>

@push('scripts')
<script>
    // ฟอร์มคูปอง: สลับช่องตามประเภทส่วนลด + แสดงตัวอย่าง
    function couponForm(state) {
        return {
            type: state.type, value: state.value, min: state.min, max: state.max, saving: false,
            preview() {
                if (this.type === 'free_shipping') return '🚚 ส่งฟรี';
                if (this.type === 'percentage') return '％ ลด ' + (Number(this.value) || 0) + '%' + (this.max ? (' สูงสุด ฿' + Number(this.max).toLocaleString('th-TH')) : '');
                return '฿ ลด ' + (Number(this.value) || 0).toLocaleString('th-TH') + ' บาท';
            },
        };
    }
</script>
@endpush
