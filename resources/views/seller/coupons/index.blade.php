@extends('layouts.seller-v4')

@section('title', 'คูปองร้านค้า')

@php
    // ข้อมูลเริ่มต้นของสวิตช์เปิด/ปิดคูปอง (Alpine) — กดแล้วยิง JSON ไป seller.coupons.toggle-active
    $toggleState = $coupons->getCollection()->mapWithKeys(fn ($c) => [$c->id => (bool) $c->is_active])->all();
    $discountText = function ($c) {
        return match ($c->discount_type) {
            'percentage' => 'ลด '.rtrim(rtrim(number_format((float) $c->discount_value, 2), '0'), '.').'%'.($c->max_discount ? ' สูงสุด ฿'.number_format((float) $c->max_discount, 0) : ''),
            'fixed' => 'ลด ฿'.number_format((float) $c->discount_value, 2),
            'free_shipping' => 'ส่งฟรี',
            default => $c->discount_type,
        };
    };
@endphp

@section('content')
<div x-data="couponList(@js($toggleState))" style="display:flex; flex-direction:column; gap:18px;">

    <x-seller-kit.header title="คูปองร้านค้า" icon="🎟️" crumb="ร้านค้า · การตลาด"
                         subtitle="แจกส่วนลดให้ลูกค้า ดึงลูกค้าใหม่ และกระตุ้นให้ซื้อซ้ำ">
        <a href="{{ route('seller.coupons.create') }}" class="tp-btn tp-btn-primary">＋ สร้างคูปอง</a>
    </x-seller-kit.header>

    @include('seller.marketing.partials.nav')

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px;">
        <x-seller-kit.stat label="คูปองทั้งหมด" :value="number_format((int) $stats['total'])" icon="🎟️" tone="info" />
        <x-seller-kit.stat label="เปิดใช้งาน" :value="number_format((int) $stats['active'])" icon="✅" tone="ok" />
        <x-seller-kit.stat label="ถูกใช้ไปแล้ว" :value="number_format((int) $stats['used']).' ครั้ง'" icon="🛒" tone="gold" />
    </div>

    @if($coupons->count() > 0)
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px;">
            @foreach($coupons as $coupon)
                @php
                    $expired = $coupon->expires_at && $coupon->expires_at->isPast();
                    $notStarted = $coupon->starts_at && $coupon->starts_at->isFuture();
                    $soldOut = $coupon->usage_limit && (int) $coupon->used_count >= (int) $coupon->usage_limit;
                    $usedPct = $coupon->usage_limit ? min(100, (int) $coupon->used_count / max(1, (int) $coupon->usage_limit) * 100) : null;
                @endphp
                <div class="tp-card" style="display:flex; flex-direction:column; gap:10px; position:relative; overflow:hidden;">
                    <div style="position:absolute; inset:0 auto 0 0; width:6px; background:linear-gradient(180deg, var(--accent1), var(--accent2));" aria-hidden="true"></div>
                    <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                        <div style="min-width:0;">
                            <div style="font-weight:800; font-size:14.5px; overflow-wrap:anywhere;">{{ $coupon->name ?: 'คูปอง' }}</div>
                            <div class="tp-num" style="font-size:20px; font-weight:800; color:var(--deep1); margin-top:2px;">{{ $discountText($coupon) }}</div>
                        </div>
                        {{-- สวิตช์เปิด/ปิด --}}
                        <button type="button" role="switch" :aria-checked="state[{{ (int) $coupon->id }}] ? 'true' : 'false'"
                                @click="toggle({{ (int) $coupon->id }}, @js(route('seller.coupons.toggle-active', $coupon)))"
                                :disabled="busy[{{ (int) $coupon->id }}]"
                                class="tp-inset-sm" style="flex:none; width:50px; height:28px; border-radius:99px; border:0; cursor:pointer; position:relative; padding:0;"
                                :style="{ background: state[{{ (int) $coupon->id }}] ? 'linear-gradient(135deg, var(--accent1), var(--accent2))' : 'var(--surf)' }"
                                aria-label="เปิด/ปิดคูปอง">
                            <span style="position:absolute; top:4px; width:20px; height:20px; border-radius:50%; background:var(--sl); box-shadow:var(--raise); transition:left .18s ease;"
                                  :style="{ left: state[{{ (int) $coupon->id }}] ? '26px' : '4px' }"></span>
                        </button>
                    </div>

                    <div class="tp-inset-sm" style="border-radius:12px; padding:9px 12px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
                        <code class="tp-num" style="font-weight:800; letter-spacing:.5px; overflow-wrap:anywhere;">{{ $coupon->code }}</code>
                        <button type="button" class="tp-btn tp-btn-sm" @click="copy(@js($coupon->code))">📋 คัดลอก</button>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        @if($expired)
                            <x-seller-kit.pill tone="bad">หมดอายุแล้ว</x-seller-kit.pill>
                        @elseif($notStarted)
                            <x-seller-kit.pill tone="info">เริ่ม {{ $coupon->starts_at->format('d/m/Y') }}</x-seller-kit.pill>
                        @elseif($soldOut)
                            <x-seller-kit.pill tone="warn">สิทธิ์เต็มแล้ว</x-seller-kit.pill>
                        @endif
                        <x-seller-kit.pill :tone="$coupon->is_public ? 'ok' : 'muted'">{{ $coupon->is_public ? 'แสดงหน้าร้าน' : 'ใช้รหัสเท่านั้น' }}</x-seller-kit.pill>
                        @if((float) $coupon->min_purchase > 0)<x-seller-kit.pill tone="muted">ขั้นต่ำ ฿{{ number_format((float) $coupon->min_purchase, 0) }}</x-seller-kit.pill>@endif
                    </div>

                    <div style="font-size:12px; color:var(--ink2);">
                        ใช้ไป {{ number_format((int) $coupon->used_count) }}{{ $coupon->usage_limit ? ' / '.number_format((int) $coupon->usage_limit) : '' }} ครั้ง
                        @if($coupon->expires_at) · หมดอายุ {{ $coupon->expires_at->format('d/m/Y H:i') }}@endif
                    </div>
                    @if(! is_null($usedPct))
                        <div class="tp-inset-sm" style="height:7px; border-radius:99px; overflow:hidden;">
                            <div style="height:100%; width:{{ max(2, $usedPct) }}%; border-radius:99px; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
                        </div>
                    @endif

                    <div style="display:flex; gap:8px; margin-top:auto;">
                        <a href="{{ route('seller.coupons.edit', $coupon) }}" class="tp-btn tp-btn-sm" style="flex:1;">✏️ แก้ไข</a>
                        <form method="POST" action="{{ route('seller.coupons.destroy', $coupon) }}" style="flex:1;"
                              onsubmit="return confirm('ลบคูปอง {{ $coupon->code }}? ลูกค้าที่ยังไม่ได้ใช้จะใช้ไม่ได้อีก');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tp-btn tp-btn-sm" style="width:100%; color:var(--tp-bad, #d9534f);">🗑️ ลบ</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
        @if($coupons->hasPages())
            <div>{{ $coupons->links() }}</div>
        @endif
    @else
        <div class="tp-card">
            <x-seller-kit.empty icon="🎟️" title="ยังไม่มีคูปอง" text="สร้างคูปองแรก เช่น ลด 10% สำหรับลูกค้าใหม่ หรือส่งฟรีเมื่อซื้อครบ 500 บาท">
                <a href="{{ route('seller.coupons.create') }}" class="tp-btn tp-btn-primary tp-btn-sm">＋ สร้างคูปองแรก</a>
            </x-seller-kit.empty>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // รายการคูปอง: สวิตช์เปิด/ปิด (JSON) + คัดลอกรหัส
    function couponList(initial) {
        return {
            state: initial,
            busy: {},
            async toggle(id, url) {
                if (this.busy[id]) return; // กันกดรัว
                this.busy[id] = true;
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        this.state[id] = !!data.is_active;
                        window.showNotification(data.message || 'อัปเดตแล้ว', 'success');
                    } else {
                        window.showNotification(data.message || 'เปลี่ยนสถานะไม่สำเร็จ', 'error');
                    }
                } catch (e) {
                    window.showNotification('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้ กรุณาลองใหม่', 'error');
                } finally {
                    this.busy[id] = false;
                }
            },
            copy(code) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(code).then(() => window.showNotification('คัดลอกรหัส ' + code + ' แล้ว', 'success'));
                }
            },
        };
    }
</script>
@endpush
