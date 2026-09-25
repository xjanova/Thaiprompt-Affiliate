{{--
 | ตะกร้าสินค้า (เว็บ) — ธีม V4 (frontend-v4)
 | ข้อมูลจาก CartController@index: $cartItems (ShoppingCart + product), $subtotal, $shippingFee, $total
 | ปรับจำนวน: PUT /cart/{id} (JSON {quantity}) · ลบ: DELETE cart.remove · ล้างตะกร้า: DELETE cart.clear
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ตะกร้าสินค้า')

@php
    $ctLines = $cartItems->map(function ($item) {
        $product = $item->product;
        $available = $product && $item->isAvailable();
        $enough = $product && $item->hasEnoughStock();

        return [
            'id' => (int) $item->id,
            'price' => $product ? round((float) $product->price, 2) : 0,
            'qty' => (int) $item->quantity,
            'max' => $product && $product->track_inventory ? max(1, (int) $product->stock_quantity) : 99,
            'ok' => $available && $enough,
        ];
    })->values();
    $ctBlocked = $ctLines->contains(fn ($l) => ! $l['ok']);
    $ctUpdateUrl = url('/cart/__ID__');
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="cart" :search="true" />

<main style="flex:1; padding-bottom:40px;">
    <section class="sf-wrap" style="padding-top:20px;">
        <nav class="sf-breadcrumb" aria-label="เส้นทาง">
            <a href="{{ route('storefront.index') }}">ร้านค้า</a>
            <span aria-hidden="true">/</span>
            <span style="color:var(--ink); font-weight:600;">ตะกร้าสินค้า</span>
        </nav>
        <h1 class="sf-h1" style="margin-top:8px;"><i class="fas fa-cart-shopping" style="color:var(--deep1);"></i> ตะกร้าสินค้า</h1>
        @if($cartItems->count() > 0)
            <p class="tp-muted" style="margin:6px 0 0;">{{ number_format($cartItems->sum('quantity')) }} ชิ้น จาก {{ $cartItems->count() }} รายการ</p>
        @endif
    </section>

    <section class="sf-wrap" style="padding-top:16px;">
        @if($cartItems->count() > 0)
            <div class="sf-2col" x-data="tpCartPage({{ \Illuminate\Support\Js::from(['lines' => $ctLines, 'url' => $ctUpdateUrl]) }})">
                <div class="sf-stack">
                    @if($ctBlocked)
                        <div class="sf-note sf-note-err" role="alert"><i class="fas fa-triangle-exclamation"></i> มีสินค้าบางรายการหมดหรือไม่พร้อมขาย กรุณาลบหรือลดจำนวนก่อนชำระเงิน</div>
                    @endif

                    @foreach($cartItems as $item)
                        @php
                            $ctProduct = $item->product;
                            $ctImg = $ctProduct ? (\App\Services\Shop\ShopPresenter::productImages($ctProduct)[0] ?? null) : null;
                            $ctOk = $ctProduct && $item->isAvailable() && $item->hasEnoughStock();
                            $ctAttrs = is_array($item->selected_attributes) ? array_filter($item->selected_attributes, fn ($v) => is_scalar($v) && $v !== '') : [];
                        @endphp
                        <article class="tp-card" style="display:flex; gap:14px; align-items:flex-start; padding:14px; {{ $ctOk ? '' : 'outline:2px solid color-mix(in srgb, var(--sf-sale, #e0564f) 50%, transparent);' }}">
                            <a href="{{ $ctProduct ? route('shop.show', $ctProduct->slug ?: $ctProduct->id) : '#' }}" class="sf-thumb" style="width:92px; height:92px; font-size:28px;">
                                @if($ctImg)
                                    <img src="{{ $ctImg }}" alt="{{ $ctProduct->name ?? '' }}" loading="lazy" onerror="this.style.display='none';">
                                @else
                                    📦
                                @endif
                            </a>
                            <div style="flex:1; min-width:0; display:flex; flex-direction:column; gap:6px;">
                                <a href="{{ $ctProduct ? route('shop.show', $ctProduct->slug ?: $ctProduct->id) : '#' }}" style="text-decoration:none; color:var(--ink); font-weight:700; line-height:1.4; overflow-wrap:anywhere;">{{ $ctProduct->name ?? 'สินค้าที่ถูกลบแล้ว' }}</a>
                                @if($ctAttrs !== [])
                                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                                        @foreach($ctAttrs as $ak => $av)
                                            <span class="tp-pill tp-pill-soft">{{ $ak }}: {{ $av }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if(! $ctOk)
                                    <div class="sf-note sf-note-err" style="padding:8px 10px; font-size:12.5px;">
                                        {{ $ctProduct ? ($ctProduct->purchaseBlockReason() ?? ((! $ctProduct->isInStock()) ? 'สินค้าหมด' : 'สินค้าเหลือ '.max(0, (int) $ctProduct->stock_quantity).' ชิ้น กรุณาลดจำนวน')) : 'สินค้านี้ถูกลบแล้ว' }}
                                    </div>
                                @elseif($ctProduct->track_inventory && (int) $ctProduct->stock_quantity <= (int) ($ctProduct->low_stock_threshold ?? 5))
                                    <span style="font-size:12px; font-weight:700; color:var(--deep2);"><i class="fas fa-fire"></i> เหลือเพียง {{ (int) $ctProduct->stock_quantity }} ชิ้น</span>
                                @endif
                                <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; margin-top:4px;">
                                    <div class="sf-qty" role="group" aria-label="จำนวนสินค้า">
                                        <button type="button" @click="change({{ (int) $item->id }}, -1)" :disabled="busy[{{ (int) $item->id }}]" aria-label="ลดจำนวน">−</button>
                                        <input type="number" min="1" :max="line({{ (int) $item->id }}).max" inputmode="numeric" aria-label="จำนวน"
                                               :value="line({{ (int) $item->id }}).qty"
                                               @change="set({{ (int) $item->id }}, $event.target.value)">
                                        <button type="button" @click="change({{ (int) $item->id }}, 1)" :disabled="busy[{{ (int) $item->id }}]" aria-label="เพิ่มจำนวน">+</button>
                                    </div>
                                    <div style="text-align:right;">
                                        <div class="tp-num" style="font-size:18px; font-weight:800; color:var(--deep1);" x-text="money(line({{ (int) $item->id }}).price * line({{ (int) $item->id }}).qty)">฿{{ number_format((float) ($ctProduct->price ?? 0) * (int) $item->quantity, 2) }}</div>
                                        <div class="tp-muted" style="font-size:12px;">฿{{ number_format((float) ($ctProduct->price ?? 0), 2) }} / ชิ้น</div>
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('cart.remove', $item->id) }}" onsubmit="return confirm(@js('ลบสินค้านี้ออกจากตะกร้า?'))" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tp-icon-btn" style="width:44px; height:44px; color:var(--sf-sale, #e0564f);" aria-label="ลบออกจากตะกร้า" title="ลบออกจากตะกร้า"><i class="fas fa-trash-can"></i></button>
                            </form>
                        </article>
                    @endforeach

                    <div style="display:flex; flex-wrap:wrap; gap:10px; justify-content:space-between;">
                        <a href="{{ route('storefront.index') }}" class="tp-btn" style="text-decoration:none; height:46px;"><i class="fas fa-arrow-left"></i> เลือกซื้อสินค้าต่อ</a>
                        <form method="POST" action="{{ route('cart.clear') }}" onsubmit="return confirm(@js('ล้างสินค้าทั้งหมดในตะกร้า? การกระทำนี้ย้อนกลับไม่ได้'))" style="margin:0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="tp-btn" style="height:46px; color:var(--sf-sale, #e0564f);"><i class="fas fa-trash"></i> ล้างตะกร้า</button>
                        </form>
                    </div>
                </div>

                <aside class="sf-sticky">
                    <div class="tp-card sf-stack" style="gap:12px;">
                        <div class="tp-section-h">สรุปคำสั่งซื้อ</div>
                        <div class="sf-row"><span>ยอดรวมสินค้า</span><strong class="tp-num" x-text="money(subtotal())">฿{{ number_format((float) $subtotal, 2) }}</strong></div>
                        <div class="sf-row"><span>ค่าจัดส่ง</span><span>คำนวณตอนชำระเงิน</span></div>
                        <div class="sf-note sf-note-info" style="font-size:12.5px;">
                            <i class="fas fa-truck-fast"></i> ส่งฟรีเมื่อซื้อครบ ฿{{ number_format(\App\Services\ShippingService::DEFAULT_FREE_SHIPPING_THRESHOLD) }} (ตามเงื่อนไขร้าน)
                            · ร้านใกล้บ้านเลือกส่งด่วนด้วยไรเดอร์ได้
                        </div>
                        <div class="sf-total"><span style="font-weight:800; color:var(--ink);">ยอดรวม</span><span class="tp-num" x-text="money(subtotal())">฿{{ number_format((float) $total, 2) }}</span></div>
                        @if($ctBlocked)
                            <button type="button" class="sf-btn3d is-block is-disabled" disabled>ชำระเงินไม่ได้ — มีสินค้าที่สั่งไม่ได้</button>
                        @else
                            <a href="{{ route('checkout.index') }}" class="sf-btn3d is-block" style="min-height:54px; font-size:15.5px;" :class="anyBusy() && 'is-disabled'" @click="if (anyBusy()) { $event.preventDefault(); window.tpShop.notify('กำลังบันทึกจำนวนสินค้า รอสักครู่', 'info'); }"><i class="fas fa-lock"></i> ไปชำระเงิน</a>
                        @endif
                        <div style="display:flex; flex-wrap:wrap; gap:6px; justify-content:center;">
                            <span class="tp-pill tp-pill-soft"><i class="fas fa-wallet"></i> กระเป๋าเงิน</span>
                            <span class="tp-pill tp-pill-soft"><i class="fas fa-qrcode"></i> พร้อมเพย์</span>
                            <span class="tp-pill tp-pill-soft"><i class="fas fa-motorcycle"></i> เก็บเงินปลายทาง (ไรเดอร์)</span>
                        </div>
                    </div>
                </aside>
            </div>
        @else
            <div class="tp-card" style="text-align:center; padding:clamp(32px, 6vw, 60px) 16px;">
                <div style="font-size:64px;" aria-hidden="true">🛒</div>
                <h2 style="margin:12px 0 6px; font-size:22px; font-weight:800; color:var(--ink);">ตะกร้าสินค้าว่างเปล่า</h2>
                <p class="tp-muted" style="margin:0 0 18px;">ยังไม่มีสินค้าในตะกร้า เริ่มช้อปกันเลย</p>
                <div style="display:flex; flex-wrap:wrap; gap:10px; justify-content:center;">
                    <a href="{{ route('storefront.index') }}" class="sf-btn3d"><i class="fas fa-bag-shopping"></i> เริ่มช้อปปิ้ง</a>
                    <a href="{{ route('taladsod.home') }}" class="sf-btn3d is-soft"><i class="fas fa-carrot"></i> ไปตลาดสด</a>
                </div>
            </div>
        @endif
    </section>
</main>

<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /**
     * ตะกร้า: ปรับจำนวนทันทีบนหน้าจอ แล้วส่ง PUT ไปยืนยัน (หน่วง 400ms กันกดรัว) — ล้มเหลวคืนค่าเดิม
     */
    function tpCartPage(cfg) {
        const lines = {};
        (cfg.lines || []).forEach(l => { lines[l.id] = Object.assign({}, l); });

        return {
            lines: lines,
            busy: {},
            pending: {},
            timers: {},
            line(id) { return this.lines[id] || { price: 0, qty: 0, max: 1 }; },
            money(v) { return '฿' + Number(v || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            subtotal() { return Object.values(this.lines).reduce((s, l) => s + (l.ok ? l.price * l.qty : 0), 0); },
            anyBusy() { return Object.values(this.busy).some(Boolean) || Object.values(this.pending).some(Boolean); },
            change(id, delta) { this.set(id, this.line(id).qty + delta); },
            set(id, value) {
                const l = this.lines[id];
                if (!l) { return; }
                const before = l.qty;
                const next = Math.min(l.max, Math.max(1, parseInt(value, 10) || 1));
                if (next === before) { return; }
                if (!this.pending[id]) { l.saved = before; }
                l.qty = next;
                this.pending[id] = true;
                clearTimeout(this.timers[id]);
                this.timers[id] = setTimeout(() => this.save(id, l.saved), 400);
            },
            async save(id, rollback) {
                const l = this.lines[id];
                this.busy[id] = true;
                try {
                    const res = await fetch(cfg.url.replace('__ID__', id), {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.tpShop.csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ quantity: l.qty })
                    });
                    const data = await res.json().catch(() => null);
                    if (!res.ok || !data || !data.success) {
                        l.qty = rollback;
                        window.tpShop.notify((data && data.message) || 'ปรับจำนวนไม่สำเร็จ กรุณาลองใหม่', 'error');
                        return;
                    }
                    window.dispatchEvent(new CustomEvent('cart-updated'));
                } catch (e) {
                    l.qty = rollback;
                    window.tpShop.notify('เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่', 'error');
                } finally {
                    this.busy[id] = false;
                    this.pending[id] = false;
                }
            }
        };
    }
</script>
@endpush
