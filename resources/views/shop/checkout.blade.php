{{--
 | ชำระเงิน (เว็บ) — ธีม V4 (frontend-v4)
 | ข้อมูลจาก CheckoutController@index:
 |   $cartItems, $addresses (มี latitude/longitude), $hasPhysicalProducts, $subtotal, $shippingFee, $total,
 |   $cashbackPreview, $pvPreview, $earningsSummary, $paymentMethods, $walletBalance,
 |   $quote (รูปแบบเดียวกับ GET /api/v1/cart — stores[].rider / stores[].cod / summary), $promptpayEnabled
 |
 | ฟอร์ม POST checkout.process:
 |   shipping_address_id, delivery_method (parcel|rider), payment_method (wallet|promptpay|cod|credit_card|bank_transfer),
 |   coupon_code, customer_notes, idempotency_key (+ turnstile ถ้าเปิด)
 |   - wallet/promptpay/cod = กฎเดียวกับแอป (ShopCheckoutService): COD ได้เฉพาะส่งด้วยไรเดอร์ · ไรเดอร์ต้องปักหมุดที่อยู่
 |   - credit_card/bank_transfer = เส้นทางเดิม (ส่งพัสดุเท่านั้น ไม่มีคูปอง)
 | JSON: GET checkout.quote?address_id&delivery_method&coupon_code · POST checkout.address-location {address_id, latitude, longitude}
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ชำระเงิน')

@php
    $coMethods = collect($paymentMethods ?? []);
    $coCardEnabled = $coMethods->contains(fn ($m) => ($m['id'] ?? null) === 'credit_card' && ($m['enabled'] ?? false));
    $coBankEnabled = $coMethods->contains(fn ($m) => ($m['id'] ?? null) === 'bank_transfer' && ($m['enabled'] ?? false));

    $coAddresses = $addresses->map(fn ($a) => [
        'id' => (int) $a->id,
        'has_location' => $a->hasLocation(),
        'lat' => $a->hasLocation() ? (float) $a->latitude : null,
        'lng' => $a->hasLocation() ? (float) $a->longitude : null,
        'label' => (string) $a->recipient_name,
    ])->values();

    $coDefaultAddressId = old('shipping_address_id', optional($addresses->first())->id);
    $coErrorCode = session('checkout_error_code');
    $coErrorContext = (array) session('checkout_error_context', []);

    $coPayment = old('payment_method');
    if ($coPayment === 'cash_on_delivery') {
        $coPayment = 'cod';
    }

    $coConfig = [
        'quoteUrl' => route('checkout.quote'),
        'pinUrl' => route('checkout.address-location'),
        'addresses' => $coAddresses,
        'addressId' => $coDefaultAddressId ? (int) $coDefaultAddressId : null,
        'delivery' => old('delivery_method', 'parcel') === 'rider' ? 'rider' : 'parcel',
        'payment' => $coPayment,
        'coupon' => (string) old('coupon_code', ''),
        'quote' => $quote ?: null,
        'walletBalance' => round((float) $walletBalance, 2),
        'hasPhysical' => (bool) $hasPhysicalProducts,
        'promptpayEnabled' => (bool) ($promptpayEnabled ?? false),
        'fallbackTotal' => round((float) $total, 2),
        'openPinFor' => $coErrorCode === 'ADDRESS_LOCATION_REQUIRED' ? ($coErrorContext['address_id'] ?? null) : null,
    ];
    $coIdempotency = (string) \Illuminate\Support\Str::uuid();
@endphp

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.leaflet />
<x-theme-v4.public-header active="cart" />

<main style="flex:1; padding-bottom:40px;" x-data="tpCheckout({{ \Illuminate\Support\Js::from($coConfig) }})">
    <section class="sf-wrap" style="padding-top:20px;">
        <nav class="sf-breadcrumb" aria-label="เส้นทาง">
            <a href="{{ route('storefront.index') }}">ร้านค้า</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('cart.index') }}">ตะกร้าสินค้า</a>
            <span aria-hidden="true">/</span>
            <span style="color:var(--ink); font-weight:600;">ชำระเงิน</span>
        </nav>
        <h1 class="sf-h1" style="margin-top:8px;"><i class="fas fa-lock" style="color:var(--deep1);"></i> ชำระเงิน</h1>
        <p class="tp-muted" style="margin:6px 0 0;">เลือกที่อยู่ วิธีจัดส่ง และวิธีชำระเงิน</p>
    </section>

    @if(session('error'))
        <section class="sf-wrap" style="padding-top:14px;">
            <div class="sf-note sf-note-err" role="alert">
                <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
                @if($coErrorCode === 'INSUFFICIENT_BALANCE')
                    <a href="{{ route('user.wallet.topup') }}" class="tp-btn tp-btn-sm tp-btn-primary" style="text-decoration:none; margin-left:8px;">เติมเงินกระเป๋า</a>
                @endif
            </div>
        </section>
    @endif

    <form method="POST" action="{{ route('checkout.process') }}" id="checkoutForm" @submit="submit($event)" novalidate>
        @csrf
        <input type="hidden" name="idempotency_key" value="{{ $coIdempotency }}">
        <input type="hidden" name="delivery_method" :value="delivery">
        <input type="hidden" name="coupon_code" :value="couponApplied">

        <section class="sf-wrap" style="padding-top:16px;">
            <div class="sf-2col">
                <div class="sf-stack">

                    {{-- ── 1) ที่อยู่จัดส่ง ── --}}
                    @if($hasPhysicalProducts)
                        <div class="tp-card sf-stack" style="gap:12px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                                <div class="tp-section-h"><span class="tp-tile" style="width:30px; height:30px; display:inline-grid; font-size:13px; margin-right:6px;">1</span>ที่อยู่จัดส่ง</div>
                                <a href="{{ route('shipping-addresses.create') }}" class="tp-btn tp-btn-sm" style="text-decoration:none;"><i class="fas fa-plus"></i> เพิ่มที่อยู่ใหม่</a>
                            </div>

                            @forelse($addresses as $address)
                                <label class="sf-opt" :class="addressId === {{ (int) $address->id }} && 'is-on'">
                                    <input type="radio" name="shipping_address_id" value="{{ $address->id }}" class="sr-only" style="position:absolute; opacity:0; width:1px; height:1px;"
                                           :checked="addressId === {{ (int) $address->id }}" @change="setAddress({{ (int) $address->id }})">
                                    <span class="sf-radio"></span>
                                    <span style="flex:1; min-width:0;">
                                        <span style="display:flex; flex-wrap:wrap; align-items:center; gap:6px;">
                                            <strong>{{ $address->recipient_name }}</strong>
                                            @if($address->is_default)
                                                <span class="tp-pill tp-pill-gold">ค่าเริ่มต้น</span>
                                            @endif
                                            <span class="tp-pill" :style="hasPin({{ (int) $address->id }}) ? 'color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--sf-ok, #4f9e7e), var(--sf-ok2, #3b8467));' : 'color:var(--deep2); background:var(--a2soft);'">
                                                <i class="fas fa-location-dot"></i>
                                                <span x-text="hasPin({{ (int) $address->id }}) ? 'ปักหมุดแล้ว' : 'ยังไม่ปักหมุด'"></span>
                                            </span>
                                        </span>
                                        <span class="tp-muted" style="display:block; font-size:12.5px; margin-top:4px;">{{ $address->phone_number }}</span>
                                        <span style="display:block; font-size:13px; line-height:1.55; margin-top:2px; overflow-wrap:anywhere;">{{ $address->full_address }}</span>
                                        <button type="button" class="tp-btn tp-btn-sm" style="margin-top:8px;" @click.prevent="openPin({{ (int) $address->id }})">
                                            <i class="fas fa-map-pin"></i> <span x-text="hasPin({{ (int) $address->id }}) ? 'แก้ไขหมุดตำแหน่ง' : 'ปักหมุดตำแหน่ง (สำหรับไรเดอร์)'"></span>
                                        </button>
                                    </span>
                                </label>
                            @empty
                                <div style="text-align:center; padding:20px 10px; border-radius:16px; background:var(--surf); box-shadow:var(--inset-sm);">
                                    <div style="font-size:34px;" aria-hidden="true">📍</div>
                                    <p class="tp-muted" style="margin:8px 0 12px;">ยังไม่มีที่อยู่จัดส่ง</p>
                                    <a href="{{ route('shipping-addresses.create') }}" class="sf-btn3d"><i class="fas fa-plus"></i> เพิ่มที่อยู่จัดส่ง</a>
                                </div>
                            @endforelse
                            @error('shipping_address_id')
                                <div class="sf-note sf-note-err" style="padding:8px 12px;">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- ── 2) วิธีจัดส่ง ── --}}
                        <div class="tp-card sf-stack" style="gap:12px;">
                            <div class="tp-section-h"><span class="tp-tile" style="width:30px; height:30px; display:inline-grid; font-size:13px; margin-right:6px;">2</span>วิธีจัดส่ง</div>

                            <button type="button" class="sf-opt" :class="delivery === 'parcel' && 'is-on'" @click="setDelivery('parcel')">
                                <span class="sf-radio"></span>
                                <span class="tp-tile" style="width:40px; height:40px; font-size:17px;"><i class="fas fa-box"></i></span>
                                <span style="flex:1; min-width:0;">
                                    <strong style="display:block;">ส่งพัสดุ</strong>
                                    <span class="tp-muted" style="display:block; font-size:12.5px;">ส่งทั่วไทย 1-3 วันทำการ มีเลขติดตามพัสดุ</span>
                                </span>
                                <span class="tp-num" style="font-weight:800; color:var(--deep1);" x-text="parcelFee() > 0 ? money(parcelFee()) : 'ส่งฟรี'"></span>
                            </button>

                            <button type="button" class="sf-opt" :class="{ 'is-on': delivery === 'rider', 'is-disabled': !riderAvailable() || legacyPayment() }" @click="setDelivery('rider')" :aria-disabled="(!riderAvailable() || legacyPayment()).toString()">
                                <span class="sf-radio"></span>
                                <span class="tp-tile" style="width:40px; height:40px; font-size:17px; background:linear-gradient(135deg, var(--accent2), var(--deep2));"><i class="fas fa-motorcycle"></i></span>
                                <span style="flex:1; min-width:0;">
                                    <strong style="display:block;">ส่งด่วนด้วยไรเดอร์</strong>
                                    <span class="tp-muted block" style="font-size:12.5px;" x-show="riderAvailable() && !legacyPayment()">ถึงไวภายในวัน ค่าส่งตามระยะทางจริง ติดตามไรเดอร์ได้สด</span>
                                    <span class="block" style="font-size:12.5px; color:var(--deep2); font-weight:600;" x-show="legacyPayment()" x-cloak>ใช้ได้เมื่อชำระด้วยกระเป๋าเงิน พร้อมเพย์ หรือเก็บเงินปลายทาง</span>
                                    <template x-for="r in riderReasons()" :key="r">
                                        <span class="block" style="font-size:12.5px; color:var(--deep2); font-weight:600;" x-show="!legacyPayment()" x-text="r"></span>
                                    </template>
                                </span>
                                <span class="tp-num" style="font-weight:800; color:var(--deep1);" x-show="riderAvailable()" x-text="money(riderFee())"></span>
                            </button>

                            <template x-if="delivery === 'rider' && quote && quote.stores">
                                <div style="display:flex; flex-direction:column; gap:6px;">
                                    <template x-for="s in quote.stores" :key="s.key">
                                        <div class="sf-row" style="font-size:12.5px;">
                                            <span><i class="fas fa-store"></i> <span x-text="s.store_name"></span>
                                                <template x-if="s.rider && s.rider.distance_km"><span x-text="' · ' + Number(s.rider.distance_km).toFixed(1) + ' กม. · ~' + (s.rider.estimated_minutes || '-') + ' นาที'"></span></template>
                                            </span>
                                            <strong class="tp-num" x-text="money(s.shipping_fee)"></strong>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    @else
                        <div class="tp-card" style="display:flex; align-items:center; gap:14px;">
                            <span class="tp-tile" style="width:46px; height:46px; font-size:20px;"><i class="fas fa-cloud-arrow-down"></i></span>
                            <span>
                                <strong style="display:block; color:var(--ink);">สินค้าดิจิทัล — ไม่ต้องจัดส่ง</strong>
                                <span class="tp-muted" style="font-size:13px;">ได้รับทันทีหลังชำระเงินสำเร็จ</span>
                            </span>
                        </div>
                    @endif

                    {{-- ── 3) วิธีชำระเงิน ── --}}
                    <div class="tp-card sf-stack" style="gap:12px;">
                        <div class="tp-section-h"><span class="tp-tile" style="width:30px; height:30px; display:inline-grid; font-size:13px; margin-right:6px;">{{ $hasPhysicalProducts ? 3 : 1 }}</span>วิธีชำระเงิน</div>
                        <input type="hidden" name="payment_method" :value="payment || ''">

                        <button type="button" class="sf-opt" :class="payment === 'wallet' && 'is-on'" @click="setPayment('wallet')">
                            <span class="sf-radio"></span>
                            <span class="tp-tile" style="width:40px; height:40px; font-size:17px;"><i class="fas fa-wallet"></i></span>
                            <span style="flex:1; min-width:0;">
                                <strong style="display:block;">กระเป๋าเงินไทยพร๊อมท์</strong>
                                <span class="tp-muted" style="display:block; font-size:12.5px;">ยอดคงเหลือ <span class="tp-num" style="font-weight:800; color:var(--ink);" x-text="money(cfg.walletBalance)"></span> · ตัดเงินทันที</span>
                                <span x-show="payment === 'wallet' && walletShort()" x-cloak class="block" style="font-size:12.5px; color:var(--sf-sale, #e0564f); font-weight:700; margin-top:4px;">
                                    ยอดไม่พอ ขาดอีก <span class="tp-num" x-text="money(grand() - cfg.walletBalance)"></span>
                                    · <a href="{{ route('user.wallet.topup') }}" style="color:var(--deep1);">เติมเงิน</a>
                                </span>
                            </span>
                        </button>

                        <button type="button" class="sf-opt" :class="{ 'is-on': payment === 'promptpay', 'is-disabled': !cfg.promptpayEnabled }" @click="setPayment('promptpay')">
                            <span class="sf-radio"></span>
                            <span class="tp-tile" style="width:40px; height:40px; font-size:17px;"><i class="fas fa-qrcode"></i></span>
                            <span style="flex:1; min-width:0;">
                                <strong style="display:block;">พร้อมเพย์ (สแกน QR)</strong>
                                <span class="tp-muted" style="display:block; font-size:12.5px;" x-text="cfg.promptpayEnabled ? 'สแกนจ่ายผ่านแอปธนาคาร ระบบยืนยันอัตโนมัติ' : 'ยังไม่เปิดให้บริการ'"></span>
                            </span>
                        </button>

                        @if($hasPhysicalProducts)
                            <button type="button" class="sf-opt" :class="{ 'is-on': payment === 'cod', 'is-disabled': !codAvailable() }" @click="setPayment('cod')">
                                <span class="sf-radio"></span>
                                <span class="tp-tile" style="width:40px; height:40px; font-size:17px;"><i class="fas fa-money-bill-wave"></i></span>
                                <span style="flex:1; min-width:0;">
                                    <strong style="display:block;">เก็บเงินปลายทาง (จ่ายกับไรเดอร์)</strong>
                                    <span class="tp-muted block" style="font-size:12.5px;" x-show="codAvailable()">จ่ายเงินสดเมื่อไรเดอร์ส่งของถึงมือ</span>
                                    <span class="block" style="font-size:12.5px; color:var(--deep2); font-weight:600;" x-show="!codAvailable()" x-text="codReason()"></span>
                                </span>
                            </button>
                        @endif

                        @if($coCardEnabled && $hasPhysicalProducts)
                            <button type="button" class="sf-opt" :class="payment === 'credit_card' && 'is-on'" @click="setPayment('credit_card')">
                                <span class="sf-radio"></span>
                                <span class="tp-tile" style="width:40px; height:40px; font-size:17px;"><i class="fas fa-credit-card"></i></span>
                                <span style="flex:1; min-width:0;">
                                    <strong style="display:block;">บัตรเครดิต / เดบิต</strong>
                                    <span class="tp-muted" style="display:block; font-size:12.5px;">ผ่านหน้าชำระเงินที่ปลอดภัยของ Stripe · ส่งพัสดุเท่านั้น ไม่ร่วมคูปอง</span>
                                </span>
                            </button>
                        @endif
                        @if($coBankEnabled && $hasPhysicalProducts)
                            <button type="button" class="sf-opt" :class="payment === 'bank_transfer' && 'is-on'" @click="setPayment('bank_transfer')">
                                <span class="sf-radio"></span>
                                <span class="tp-tile" style="width:40px; height:40px; font-size:17px;"><i class="fas fa-building-columns"></i></span>
                                <span style="flex:1; min-width:0;">
                                    <strong style="display:block;">โอนผ่านธนาคาร</strong>
                                    <span class="tp-muted" style="display:block; font-size:12.5px;">โอนตามยอดที่ระบบกำหนด ยืนยันอัตโนมัติ · ส่งพัสดุเท่านั้น ไม่ร่วมคูปอง</span>
                                </span>
                            </button>
                        @endif
                        @error('payment_method')
                            <div class="sf-note sf-note-err" style="padding:8px 12px;">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ── คูปอง ── --}}
                    <div class="tp-card sf-stack" style="gap:10px;" x-show="!legacyPayment()">
                        <div class="tp-section-h"><i class="fas fa-ticket" style="color:var(--deep1);"></i> คูปองส่วนลด</div>
                        <div style="display:flex; gap:8px; flex-wrap:wrap;">
                            <label for="co-coupon" style="position:absolute; left:-9999px;">โค้ดคูปอง</label>
                            <input id="co-coupon" type="text" x-model="couponInput" maxlength="50" autocomplete="off" class="tp-input" placeholder="กรอกโค้ดคูปอง" style="flex:1 1 180px; height:46px; text-transform:uppercase;"
                                   @keydown.enter.prevent="applyCoupon()">
                            <button type="button" class="tp-btn tp-btn-primary" style="height:46px;" @click="applyCoupon()" :disabled="loading"><i class="fas fa-check"></i> ใช้คูปอง</button>
                            <button type="button" class="tp-btn" style="height:46px;" x-show="couponApplied" x-cloak @click="removeCoupon()"><i class="fas fa-xmark"></i> เอาออก</button>
                        </div>
                        <template x-if="quote && quote.coupon">
                            <div class="sf-note sf-note-ok"><i class="fas fa-circle-check"></i> ใช้คูปอง <strong x-text="quote.coupon.code"></strong> ลด <strong class="tp-num" x-text="money(quote.coupon.discount)"></strong></div>
                        </template>
                        <template x-if="quote && quote.coupon_error">
                            <div class="sf-note sf-note-err" x-text="quote.coupon_error.message"></div>
                        </template>
                    </div>

                    {{-- ── หมายเหตุ ── --}}
                    <div class="tp-card sf-stack" style="gap:10px;">
                        <label for="co-notes" class="tp-section-h"><i class="fas fa-note-sticky" style="color:var(--deep1);"></i> หมายเหตุถึงร้าน (ถ้ามี)</label>
                        <textarea id="co-notes" name="customer_notes" rows="3" maxlength="500" class="tp-input" placeholder="เช่น เวลาที่สะดวกรับของ จุดสังเกตหน้าบ้าน">{{ old('customer_notes') }}</textarea>
                        @error('customer_notes')
                            <div class="sf-note sf-note-err" style="padding:8px 12px;">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- ── สรุปคำสั่งซื้อ ── --}}
                <aside class="sf-sticky">
                    <div class="tp-card sf-stack" style="gap:12px;">
                        <div class="tp-section-h">สรุปคำสั่งซื้อ</div>

                        <div style="display:flex; flex-direction:column; gap:10px; max-height:260px; overflow-y:auto; padding-right:4px;">
                            @foreach($cartItems as $item)
                                @php $coImg = $item->product ? (\App\Services\Shop\ShopPresenter::productImages($item->product)[0] ?? null) : null; @endphp
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <span class="sf-thumb" style="width:52px; height:52px;">
                                        @if($coImg)<img src="{{ $coImg }}" alt="" loading="lazy">@else 📦 @endif
                                    </span>
                                    <span style="flex:1; min-width:0;">
                                        <span style="display:block; font-size:13px; font-weight:600; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item->product->name ?? 'สินค้า' }}</span>
                                        <span class="tp-muted" style="font-size:12px;">× {{ (int) $item->quantity }}</span>
                                    </span>
                                    <span class="tp-num" style="font-weight:700; font-size:13px; color:var(--ink);">฿{{ number_format((float) ($item->product->price ?? 0) * (int) $item->quantity, 2) }}</span>
                                </div>
                            @endforeach
                        </div>

                        <template x-if="quote && quote.stores && quote.stores.length > 1">
                            <div class="sf-note sf-note-info" style="font-size:12.5px;"><i class="fas fa-store"></i> สินค้ามาจาก <strong x-text="quote.stores.length"></strong> ร้าน ระบบจะแยกเป็นคำสั่งซื้อละร้าน</div>
                        </template>

                        <div class="sf-row"><span>ยอดรวมสินค้า</span><strong class="tp-num" x-text="money(sum('subtotal', {{ (float) $subtotal }}))">฿{{ number_format((float) $subtotal, 2) }}</strong></div>
                        @if($hasPhysicalProducts)
                            <div class="sf-row"><span x-text="delivery === 'rider' ? 'ค่าส่งไรเดอร์' : 'ค่าจัดส่ง'">ค่าจัดส่ง</span><strong class="tp-num" x-text="sum('shipping_fee', {{ (float) $shippingFee }}) > 0 ? money(sum('shipping_fee', {{ (float) $shippingFee }})) : 'ฟรี'"></strong></div>
                        @endif
                        <div class="sf-row" x-show="sum('discount', 0) > 0" x-cloak><span>ส่วนลดคูปอง</span><strong class="tp-num" style="color:var(--sf-ok, #4f9e7e);" x-text="'-' + money(sum('discount', 0))"></strong></div>

                        @if(($cashbackPreview['total_cashback'] ?? 0) > 0)
                            <div class="sf-note sf-note-ok" style="font-size:12.5px;"><i class="fas fa-coins"></i> รับเงินคืนเข้ากระเป๋าประมาณ <strong class="tp-num">฿{{ number_format((float) $cashbackPreview['total_cashback'], 2) }}</strong> หลังชำระเงิน</div>
                        @endif

                        {{-- คะแนนสะสม (ถ้อยคำกลาง — หน้านี้อาจเปิดในแอป) --}}
                        @if(($pvPreview['total_pv'] ?? 0) > 0)
                            <div class="sf-note sf-note-info" style="font-size:12.5px;">
                                <div style="display:flex; justify-content:space-between; gap:8px; font-weight:700;">
                                    <span><i class="fas fa-star" style="color:var(--deep2);"></i> คะแนนสะสมที่จะได้รับ</span>
                                    <span class="tp-num">{{ number_format((float) $pvPreview['total_pv'], 0) }} คะแนน</span>
                                </div>
                                @if(! empty($pvPreview['breakdown']))
                                    <div style="margin-top:6px; display:flex; flex-direction:column; gap:3px;">
                                        @foreach($pvPreview['breakdown'] as $pvLine)
                                            <div style="display:flex; justify-content:space-between; gap:8px;">
                                                <span class="tp-muted">{{ \Illuminate\Support\Str::limit($pvLine['product_name'] ?? '', 28) }}@if(($pvLine['quantity'] ?? 1) > 1) ×{{ $pvLine['quantity'] }}@endif</span>
                                                <span class="tp-num">{{ number_format((float) ($pvLine['total_pv'] ?? 0), 0) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                <div class="tp-muted" style="margin-top:6px;">คะแนนจะเข้าบัญชีหลังชำระเงินสำเร็จ</div>
                            </div>
                        @endif

                        <div class="sf-total">
                            <span style="font-weight:800; color:var(--ink);">ยอดชำระ</span>
                            <span class="tp-num" x-text="money(grand())">฿{{ number_format((float) $total, 2) }}</span>
                        </div>
                        <p x-show="loading" x-cloak class="tp-muted" style="margin:0; font-size:12px;"><i class="fas fa-spinner fa-spin"></i> กำลังคำนวณยอด...</p>

                        <x-turnstile point="checkout" />

                        <p x-show="blockReason()" x-cloak x-text="blockReason()" class="sf-note sf-note-warn" style="margin:0; font-size:12.5px;"></p>
                        <button type="submit" class="sf-btn3d is-block" style="min-height:56px; font-size:16px;" :class="(!canSubmit() || submitting) && 'is-disabled'" :disabled="submitting">
                            <i class="fas" :class="submitting ? 'fa-spinner fa-spin' : 'fa-lock'"></i>
                            <span x-text="submitting ? 'กำลังสร้างคำสั่งซื้อ...' : submitLabel()">ยืนยันคำสั่งซื้อ</span>
                        </button>
                        <a href="{{ route('cart.index') }}" class="tp-btn" style="text-decoration:none; height:46px;"><i class="fas fa-arrow-left"></i> กลับไปที่ตะกร้า</a>
                        <p class="tp-muted" style="margin:0; font-size:12px; text-align:center;"><i class="fas fa-shield-halved"></i> ข้อมูลการชำระเงินเข้ารหัสและปลอดภัย</p>
                    </div>
                </aside>
            </div>
        </section>
    </form>

    {{-- ── หน้าต่างปักหมุดตำแหน่ง ── --}}
    <div x-show="pin.open" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-label="ปักหมุดตำแหน่งที่อยู่"
         @keydown.escape.window="closePin()" class="flex"
         style="position:fixed; inset:0; z-index:80; align-items:flex-end; justify-content:center; padding:12px; background:rgba(0,0,0,.5);">
        <div class="tp-card sf-stack" style="width:min(640px, 100%); gap:12px; max-height:calc(100vh - 24px); overflow-y:auto;" @click.outside="closePin()">
            <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                <div class="tp-section-h"><i class="fas fa-map-pin" style="color:var(--deep1);"></i> ปักหมุดตำแหน่งจัดส่ง</div>
                <button type="button" class="tp-icon-btn" @click="closePin()" aria-label="ปิด"><i class="fas fa-xmark"></i></button>
            </div>
            <p class="tp-muted" style="margin:0; font-size:13px;">ลากหมุดไปยังจุดส่งของ หรือกด "ใช้ตำแหน่งปัจจุบัน" ไรเดอร์จะใช้หมุดนี้นำทาง</p>
            <div id="co-pin-map" class="sf-map" style="height:min(52vh, 380px);"></div>
            <p class="tp-muted tp-num" style="margin:0; font-size:12px;" x-show="pin.lat" x-text="'พิกัด ' + Number(pin.lat).toFixed(6) + ', ' + Number(pin.lng).toFixed(6)"></p>
            <p x-show="pin.error" x-cloak x-text="pin.error" class="sf-note sf-note-err" style="margin:0;"></p>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:10px;">
                <button type="button" class="sf-btn3d is-soft" @click="locate()" :disabled="pin.locating">
                    <i class="fas" :class="pin.locating ? 'fa-spinner fa-spin' : 'fa-location-crosshairs'"></i> ใช้ตำแหน่งปัจจุบัน
                </button>
                <button type="button" class="sf-btn3d" @click="savePin()" :disabled="pin.saving || !pin.lat">
                    <i class="fas" :class="pin.saving ? 'fa-spinner fa-spin' : 'fa-check'"></i> บันทึกหมุด
                </button>
            </div>
        </div>
    </div>
</main>

<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /**
     * หน้าชำระเงิน: เลือกที่อยู่/วิธีส่ง/วิธีจ่าย/คูปอง → ขอยอดใหม่จาก checkout.quote ทุกครั้ง (กฎเดียวกับแอป)
     */
    function tpCheckout(cfg) {
        const LEGACY = ['credit_card', 'bank_transfer'];
        const BKK = [13.7563, 100.5018];

        return {
            cfg: cfg,
            addresses: cfg.addresses || [],
            addressId: cfg.addressId,
            delivery: cfg.delivery || 'parcel',
            payment: cfg.payment || null,
            couponInput: cfg.coupon || '',
            couponApplied: cfg.coupon || '',
            quote: cfg.quote,
            loading: false,
            submitting: false,
            seq: 0,
            pin: { open: false, addressId: null, lat: null, lng: null, locating: false, saving: false, error: '' },
            map: null,
            marker: null,

            init() {
                if (!this.quote || this.couponApplied) { this.requote(); }
                if (cfg.openPinFor) { this.$nextTick(() => this.openPin(Number(cfg.openPinFor))); }
            },

            money(v) { return '฿' + Number(v || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
            legacyPayment() { return LEGACY.includes(this.payment); },
            hasPin(id) { const a = this.addresses.find(x => x.id === id); return !!(a && a.has_location); },
            stores() { return (this.quote && Array.isArray(this.quote.stores)) ? this.quote.stores : []; },
            physicalStores() { return this.stores().filter(s => !(s.rider && s.rider.reason === 'สินค้าดิจิทัลไม่ต้องจัดส่ง')); },
            riderAvailable() { return !!(this.quote && this.quote.summary && this.quote.summary.rider_available); },
            riderFee() { return this.stores().reduce((t, s) => t + Number((s.rider && s.rider.fee) || 0), 0); },
            parcelFee() { return this.stores().reduce((t, s) => t + Number(s.parcel_fee || 0), 0); },
            riderReasons() {
                if (this.riderAvailable()) { return []; }
                const out = [];
                this.physicalStores().forEach(s => {
                    if (s.rider && !s.rider.available && s.rider.reason) {
                        const msg = this.stores().length > 1 ? (s.store_name + ': ' + s.rider.reason) : s.rider.reason;
                        if (!out.includes(msg)) { out.push(msg); }
                    }
                });
                return out.length ? out : ['กำลังตรวจสอบพื้นที่ให้บริการ...'];
            },
            codAvailable() { return this.delivery === 'rider' && !!(this.quote && this.quote.summary && this.quote.summary.cod_available); },
            codReason() {
                if (this.delivery !== 'rider') { return 'ใช้ได้เมื่อเลือกส่งด่วนด้วยไรเดอร์'; }
                const s = this.stores().find(x => x.cod && !x.cod.available && x.cod.reason);
                return s ? s.cod.reason : 'เก็บเงินปลายทางใช้ไม่ได้สำหรับคำสั่งซื้อนี้';
            },
            sum(key, fallback) {
                if (!this.quote || !this.quote.summary) { return Number(fallback || 0); }
                return Number(this.quote.summary[key] || 0);
            },
            grand() { return this.quote && this.quote.summary ? Number(this.quote.summary.grand_total || 0) : Number(cfg.fallbackTotal || 0); },
            walletShort() { return this.payment === 'wallet' && Number(cfg.walletBalance) + 0.0001 < this.grand(); },

            blockReason() {
                if (cfg.hasPhysical && !this.addressId) { return 'กรุณาเลือกหรือเพิ่มที่อยู่จัดส่ง'; }
                if (!this.payment) { return 'กรุณาเลือกวิธีชำระเงิน'; }
                if (this.delivery === 'rider' && !this.riderAvailable()) { return 'ส่งด้วยไรเดอร์ไม่ได้ กรุณาเลือกส่งพัสดุ'; }
                if (this.payment === 'cod' && !this.codAvailable()) { return this.codReason(); }
                if (this.payment === 'promptpay' && !cfg.promptpayEnabled) { return 'พร้อมเพย์ยังไม่เปิดให้บริการ'; }
                if (this.walletShort()) { return 'ยอดเงินในกระเป๋าไม่พอ กรุณาเติมเงินหรือเลือกวิธีอื่น'; }
                return '';
            },
            canSubmit() { return !this.loading && this.blockReason() === ''; },
            submitLabel() {
                if (this.payment === 'wallet') { return 'ชำระ ' + this.money(this.grand()) + ' ด้วยกระเป๋าเงิน'; }
                if (this.payment === 'cod') { return 'ยืนยันสั่งซื้อ (จ่ายปลายทาง)'; }
                if (this.payment === 'promptpay') { return 'ยืนยันและรับ QR พร้อมเพย์'; }
                return 'ยืนยันคำสั่งซื้อ';
            },

            setAddress(id) { this.addressId = id; this.requote(); },
            setDelivery(m) {
                if (m === 'rider') {
                    if (this.legacyPayment()) { window.tpShop.notify('ส่งด้วยไรเดอร์ใช้ได้เมื่อชำระด้วยกระเป๋าเงิน พร้อมเพย์ หรือเก็บเงินปลายทาง', 'info'); return; }
                    if (!this.riderAvailable()) {
                        const reasons = this.riderReasons();
                        if (this.addressId && !this.hasPin(this.addressId)) { this.openPin(this.addressId); }
                        window.tpShop.notify(reasons[0] || 'ส่งด้วยไรเดอร์ไม่ได้', 'info');
                        return;
                    }
                }
                this.delivery = m;
                if (m === 'parcel' && this.payment === 'cod') { this.payment = null; }
                this.requote();
            },
            setPayment(p) {
                if (p === 'promptpay' && !cfg.promptpayEnabled) { return; }
                if (p === 'cod' && !this.codAvailable()) { window.tpShop.notify(this.codReason(), 'info'); return; }
                this.payment = p;
                if (LEGACY.includes(p)) {
                    const changed = this.delivery !== 'parcel' || this.couponApplied !== '';
                    this.delivery = 'parcel';
                    this.couponApplied = '';
                    if (changed) { this.requote(); }
                }
            },
            applyCoupon() {
                const code = (this.couponInput || '').trim().toUpperCase();
                if (!code) { window.tpShop.notify('กรุณากรอกโค้ดคูปอง', 'info'); return; }
                this.couponApplied = code;
                this.requote(true);
            },
            removeCoupon() { this.couponApplied = ''; this.couponInput = ''; this.requote(); },

            async requote(fromCoupon) {
                const mySeq = ++this.seq;
                this.loading = true;
                try {
                    const params = new URLSearchParams();
                    if (this.addressId) { params.set('address_id', this.addressId); }
                    params.set('delivery_method', this.delivery);
                    if (this.couponApplied) { params.set('coupon_code', this.couponApplied); }
                    const res = await fetch(cfg.quoteUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json().catch(() => null);
                    if (mySeq !== this.seq) { return; }
                    if (!res.ok || !data || !data.success) {
                        window.tpShop.notify((data && data.message) || 'คำนวณยอดไม่สำเร็จ กรุณาลองใหม่', 'error');
                        return;
                    }
                    this.quote = data.data;
                    if (this.quote.coupon_error && this.couponApplied) {
                        if (fromCoupon) { window.tpShop.notify(this.quote.coupon_error.message, 'error'); }
                        this.couponApplied = '';
                    } else if (fromCoupon && this.quote.coupon) {
                        window.tpShop.notify('ใช้คูปองเรียบร้อย', 'success');
                    }
                    // ไรเดอร์ใช้ไม่ได้แล้ว (เช่นเปลี่ยนที่อยู่) → กลับไปส่งพัสดุ
                    if (this.delivery === 'rider' && !this.riderAvailable()) {
                        this.delivery = 'parcel';
                        if (this.payment === 'cod') { this.payment = null; }
                        window.tpShop.notify('ที่อยู่นี้ส่งด้วยไรเดอร์ไม่ได้ เปลี่ยนเป็นส่งพัสดุให้แล้ว', 'info');
                        this.requote();
                    }
                } catch (e) {
                    if (mySeq === this.seq) { window.tpShop.notify('เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่', 'error'); }
                } finally {
                    if (mySeq === this.seq) { this.loading = false; }
                }
            },

            submit(e) {
                if (this.submitting) { e.preventDefault(); return; }
                const reason = this.blockReason();
                if (reason || this.loading) {
                    e.preventDefault();
                    window.tpShop.notify(reason || 'กำลังคำนวณยอด กรุณารอสักครู่', 'error');
                    return;
                }
                this.submitting = true;
            },

            // ── ปักหมุด ──
            openPin(id) {
                const a = this.addresses.find(x => x.id === id);
                if (!a) { return; }
                this.pin = { open: true, addressId: id, lat: a.lat, lng: a.lng, locating: false, saving: false, error: '' };
                this.$nextTick(() => setTimeout(() => this.drawMap(), 60));
            },
            closePin() { this.pin.open = false; },
            drawMap() {
                if (!window.tpMap || !window.tpMap.ready()) { this.pin.error = 'โหลดแผนที่ไม่สำเร็จ กรุณารีเฟรชหน้า'; return; }
                const lat = this.pin.lat || BKK[0];
                const lng = this.pin.lng || BKK[1];
                if (!this.map) {
                    this.map = window.tpMap.create(document.getElementById('co-pin-map'), lat, lng, this.pin.lat ? 17 : 12);
                    this.marker = window.tpMap.pin(this.map, lat, lng, 'home', true);
                    this.marker.on('dragend', () => { const p = this.marker.getLatLng(); this.pin.lat = p.lat; this.pin.lng = p.lng; });
                    this.map.on('click', (ev) => { this.marker.setLatLng(ev.latlng); this.pin.lat = ev.latlng.lat; this.pin.lng = ev.latlng.lng; });
                } else {
                    this.map.setView([lat, lng], this.pin.lat ? 17 : 12);
                    this.marker.setLatLng([lat, lng]);
                }
                this.map.invalidateSize();
                if (!this.pin.lat) { this.locate(); }
            },
            locate() {
                if (!navigator.geolocation) { this.pin.error = 'อุปกรณ์นี้ไม่รองรับการหาตำแหน่ง กรุณาลากหมุดเอง'; return; }
                this.pin.locating = true;
                this.pin.error = '';
                navigator.geolocation.getCurrentPosition((pos) => {
                    this.pin.locating = false;
                    this.pin.lat = pos.coords.latitude;
                    this.pin.lng = pos.coords.longitude;
                    if (this.map) { this.map.setView([this.pin.lat, this.pin.lng], 17); this.marker.setLatLng([this.pin.lat, this.pin.lng]); }
                }, () => {
                    this.pin.locating = false;
                    this.pin.error = 'หาตำแหน่งไม่ได้ (ไม่ได้อนุญาตหรือสัญญาณอ่อน) กรุณาลากหมุดไปยังจุดส่งของ';
                }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 30000 });
            },
            async savePin() {
                if (!this.pin.lat || this.pin.saving) { return; }
                this.pin.saving = true;
                this.pin.error = '';
                try {
                    const res = await fetch(cfg.pinUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': window.tpShop.csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ address_id: this.pin.addressId, latitude: this.pin.lat, longitude: this.pin.lng })
                    });
                    const data = await res.json().catch(() => null);
                    if (!res.ok || !data || !data.success) {
                        this.pin.error = (data && data.message) || 'บันทึกหมุดไม่สำเร็จ กรุณาลองใหม่';
                        return;
                    }
                    const a = this.addresses.find(x => x.id === this.pin.addressId);
                    if (a) { a.has_location = true; a.lat = data.data.latitude; a.lng = data.data.longitude; }
                    this.pin.open = false;
                    window.tpShop.notify(data.message || 'บันทึกตำแหน่งเรียบร้อยแล้ว', 'success');
                    this.addressId = this.pin.addressId;
                    this.requote();
                } catch (e) {
                    this.pin.error = 'เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่';
                } finally {
                    this.pin.saving = false;
                }
            }
        };
    }
</script>
@endpush
