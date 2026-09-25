{{--
 | ยืนยันสั่งซื้อตลาดสด (taladsod.checkout) — ตะกร้าของร้านเดียว → 1 ออเดอร์ — ธีม V4 (frontend-v4)
 | Controller: FreshMarket\CartController@checkout
 | ตัวแปร: $shopCart (ตะกร้าร้านเดียว), $shop (FreshMarketSeller|null), $settings, $paymentMethods [wallet|cod], $riderEnabled, $pickupAvailable,
 |         $deliveryBaseRate, $deliveryPerKm, $maxCodAmount, $walletBalance, $savedAddresses [{id, recipient_name, full_address, latitude, longitude, is_default}]
 | ส่งฟอร์ม POST taladsod.checkout.store: seller_id, delivery_type (pickup|rider), payment_method (wallet|cod),
 |   buyer_latitude + buyer_longitude + delivery_address (บังคับเมื่อ rider), delivery_notes
 | ค่าส่ง: GET taladsod.cart.quote?seller_id&latitude&longitude → {data:{available, code, message, distance_km, total_fee, estimated_duration_minutes, subtotal, grand_total}}
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ยืนยันสั่งซื้อ · ตลาดสด')

@section('meta')
    <meta name="robots" content="noindex">
@endsection

@php
    $ui = \App\Support\TaladsodWebUi::class;
    $sellerId = (int) $shopCart['seller_id'];
    $sInfo = $shopCart['seller'] ?? [];
    $subtotal = (float) $shopCart['subtotal'];
    $canCheckout = (bool) $shopCart['can_checkout'];
    $walletOn = in_array('wallet', $paymentMethods, true);
    $codOn = in_array('cod', $paymentMethods, true);
    // ร้านเคลื่อนที่: ใช้เฉพาะตำแหน่งสาธารณะตอนร้านเปิด ($shopPublic) — ห้ามใช้ตำแหน่งล่าสุดตอนร้านปิด (อาจเป็นบ้านของร้าน)
    $pickupPoint = ($shop && ! $shop->isMobileShop()) ? $shop->pickupPoint() : null;
    $shopPublic = $shop ? $shop->publicLocation() : null;
    $defaultAddress = collect($savedAddresses ?? [])->first(fn ($a) => $a['latitude'] !== null) ?? null;

    $oldDelivery = old('delivery_type');
    $defaultDelivery = in_array($oldDelivery, ['pickup', 'rider'], true) ? $oldDelivery : 'pickup';
    if (! $riderEnabled) {
        $defaultDelivery = 'pickup';
    }
    $oldPay = old('payment_method');
    $defaultPay = in_array($oldPay, $paymentMethods, true)
        ? $oldPay
        : (($walletOn && $walletBalance >= $subtotal) || ! $codOn ? ($walletOn ? 'wallet' : 'cod') : 'cod');

    $coCfg = [
        'sellerId' => $sellerId,
        'subtotal' => round($subtotal, 2),
        'delivery' => $defaultDelivery,
        'payment' => $defaultPay,
        'riderEnabled' => (bool) $riderEnabled,
        'walletOn' => $walletOn,
        'codOn' => $codOn,
        'walletBalance' => round((float) $walletBalance, 2),
        'maxCod' => round((float) $maxCodAmount, 2),
        'canCheckout' => $canCheckout,
        'quoteUrl' => route('taladsod.cart.quote'),
        'lat' => old('buyer_latitude', $defaultAddress['latitude'] ?? null),
        'lng' => old('buyer_longitude', $defaultAddress['longitude'] ?? null),
        'address' => old('delivery_address', $defaultAddress['full_address'] ?? ''),
        'addresses' => array_values($savedAddresses ?? []),
    ];
@endphp

@section('content')
<x-theme-v4.shop-kit />
@include('taladsod.partials.kit')
<x-theme-v4.leaflet />
<x-theme-v4.public-header active="taladsod" />

<main class="ts-scope ts-has-bottom-bar" style="flex:1; padding-bottom:44px;" x-data="tsCheckout({{ \Illuminate\Support\Js::from($coCfg) }})" x-on:ts-pin="onPin($event.detail)">
    <div class="sf-wrap">
        @include('taladsod.partials.nav', ['active' => 'cart'])

        <nav class="sf-breadcrumb" aria-label="เส้นทางหน้า" style="margin:4px 0 12px;">
            <a href="{{ route('taladsod.cart') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> กลับไปตะกร้า</a>
        </nav>
        <h1 class="sf-h1" style="margin-bottom:16px;">ยืนยันสั่งซื้อ</h1>

        @if($errors->any())
            <div class="sf-note sf-note-err" style="margin-bottom:14px;" role="alert">
                @foreach($errors->all() as $message)
                    <div><i class="fas fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</div>
                @endforeach
            </div>
        @endif

        @unless($canCheckout)
            <div class="sf-note sf-note-warn" style="margin-bottom:14px;" role="alert">
                <i class="fas fa-moon" aria-hidden="true"></i>
                {{ empty($sInfo['is_open']) ? ($sInfo['closed_message'] ?? 'ร้านปิดอยู่ — สั่งได้เมื่อร้านเปิด') : 'มีสินค้าที่สั่งไม่ได้ในตะกร้า กรุณากลับไปแก้ที่ตะกร้าก่อน' }}
            </div>
        @endunless

        <form method="POST" action="{{ route('taladsod.checkout.store') }}" x-ref="form" x-on:submit="submit($event)">
            @csrf
            <input type="hidden" name="seller_id" value="{{ $sellerId }}">
            <input type="hidden" name="delivery_type" :value="delivery" value="{{ $defaultDelivery }}">
            <input type="hidden" name="payment_method" :value="payment" value="{{ $defaultPay }}">

            <div class="sf-2col">
                <div class="sf-stack">
                    {{-- ════════ วิธีรับสินค้า ════════ --}}
                    <section class="tp-card ts-stack" aria-labelledby="co-del-h">
                        <h2 id="co-del-h" class="ts-h2"><span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:14px;">1</span> วิธีรับสินค้า</h2>
                        <div class="ts-grid" style="--ts-min:220px; gap:10px;">
                            <button type="button" class="ts-choice" :class="delivery === 'pickup' ? 'is-on' : ''" x-on:click="setDelivery('pickup')" :aria-pressed="delivery === 'pickup' ? 'true' : 'false'">
                                <span class="ind"><i class="fas fa-check" aria-hidden="true"></i></span>
                                <span class="name">
                                    <i class="fas fa-person-walking" style="color:var(--accent2);" aria-hidden="true"></i> รับเองที่ร้าน
                                    <span class="ts-muted ts-small" style="display:block; font-weight:600;">ไม่มีค่าส่ง</span>
                                </span>
                            </button>
                            <button type="button" class="ts-choice" :class="{ 'is-on': delivery === 'rider', 'is-off': !riderEnabled }" x-on:click="setDelivery('rider')" :disabled="!riderEnabled" :aria-pressed="delivery === 'rider' ? 'true' : 'false'">
                                <span class="ind"><i class="fas fa-check" aria-hidden="true"></i></span>
                                <span class="name">
                                    <i class="fas fa-motorcycle" style="color:var(--accent2);" aria-hidden="true"></i> ไรเดอร์ส่งถึงบ้าน
                                    <span class="ts-muted ts-small" style="display:block; font-weight:600;">{{ $riderEnabled ? 'เริ่ม ฿'.$ui::money($deliveryBaseRate).' + ฿'.$ui::money($deliveryPerKm).'/กม.' : 'ยังไม่เปิดให้บริการ' }}</span>
                                </span>
                            </button>
                        </div>

                        {{-- รับเอง: จุดรับของ --}}
                        <div x-show="delivery === 'pickup'" class="tp-inset" style="border-radius:16px; padding:14px;">
                            <b style="font-size:13.5px;"><i class="fas fa-store" style="color:var(--ts-ok);" aria-hidden="true"></i> รับที่ร้าน {{ $sInfo['shop_name'] ?? '' }}</b>
                            @if($shop && $shop->isMobileShop())
                                <p class="ts-muted" style="margin:6px 0 0; font-size:13px;">
                                    ร้านเคลื่อนที่ — ตอนนี้อยู่ที่ {{ $shopPublic['label'] ?? 'จุดที่ร้านปักหมุดไว้' }}
                                    <br>ถ้าร้านย้ายจุดขาย หน้าออเดอร์จะแสดงตำแหน่งล่าสุดให้
                                </p>
                            @elseif($pickupPoint)
                                <p class="ts-muted" style="margin:6px 0 0; font-size:13px; overflow-wrap:anywhere;">{{ $pickupPoint['address'] }}</p>
                            @endif
                            @php
                                $mapPoint = $shopPublic ?? $pickupPoint;
                                $mapUrl = $mapPoint ? \App\Support\RiderWebUi::mapUrl($mapPoint['latitude'], $mapPoint['longitude']) : null;
                            @endphp
                            @if($mapUrl)
                                <a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="tp-btn tp-btn-sm" style="margin-top:10px;"><i class="fas fa-map" aria-hidden="true"></i> เปิดแผนที่ร้าน</a>
                            @endif
                        </div>

                        {{-- ไรเดอร์: ที่อยู่ + หมุด --}}
                        <div x-show="delivery === 'rider'" x-cloak class="ts-stack" style="gap:12px;">
                            <template x-if="addresses.length > 0">
                                <div>
                                    <span class="ts-label">ที่อยู่ที่บันทึกไว้</span>
                                    <div class="sf-scroll" style="padding-bottom:4px;">
                                        <template x-for="a in addresses" :key="a.id">
                                            <button type="button" class="sf-chip" :class="pickedAddress === a.id ? 'is-on' : ''" x-on:click="useAddress(a)" style="max-width:260px;">
                                                <i class="fas" :class="a.is_default ? 'fa-house' : 'fa-location-dot'" aria-hidden="true"></i>
                                                <span style="overflow:hidden; text-overflow:ellipsis;" x-text="a.recipient_name + ' · ' + a.full_address"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <div x-data="tsPinMap({ id: 'dropoff', lat: {{ \Illuminate\Support\Js::from($coCfg['lat']) }}, lng: {{ \Illuminate\Support\Js::from($coCfg['lng']) }}, kind: 'home', defaultLat: {{ \Illuminate\Support\Js::from($shopPublic['latitude'] ?? ($pickupPoint['latitude'] ?? null)) }}, defaultLng: {{ \Illuminate\Support\Js::from($shopPublic['longitude'] ?? ($pickupPoint['longitude'] ?? null)) }} })">
                                <div class="ts-row" style="justify-content:space-between; margin-bottom:8px;">
                                    <span class="ts-label" style="margin:0;">ปักหมุดจุดส่ง <span class="req">*</span></span>
                                    <button type="button" class="ts-btn3d sm ts-tone-info" x-on:click="locate()" :disabled="locating">
                                        <i class="fas" :class="locating ? 'fa-circle-notch ts-spin' : 'fa-location-crosshairs'" aria-hidden="true"></i> ใช้ตำแหน่งปัจจุบัน
                                    </button>
                                </div>
                                <div class="ts-map sm" x-ref="map" aria-label="แผนที่ปักหมุดจุดส่ง"></div>
                                <p class="ts-help">แตะแผนที่หรือลากหมุดให้ตรงหน้าบ้าน — ใช้คำนวณค่าส่งและให้ไรเดอร์นำทาง (ไม่แสดงต่อสาธารณะ)</p>
                                <p class="ts-err" x-show="error" x-text="error" x-cloak></p>
                            </div>
                            <input type="hidden" name="buyer_latitude" :value="lat ?? ''" :disabled="delivery !== 'rider'">
                            <input type="hidden" name="buyer_longitude" :value="lng ?? ''" :disabled="delivery !== 'rider'">

                            <div>
                                <label class="ts-label" for="co-address">ที่อยู่จัดส่ง <span class="req">*</span></label>
                                <textarea id="co-address" name="delivery_address" class="tp-input" rows="2" maxlength="500" x-model="address" :disabled="delivery !== 'rider'"
                                          placeholder="บ้านเลขที่ ซอย ถนน จุดสังเกต เช่น ตึกสีเหลืองข้างร้านยา"></textarea>
                                @error('delivery_address')<p class="ts-err">{{ $message }}</p>@enderror
                                @error('buyer_latitude')<p class="ts-err">{{ $message }}</p>@enderror
                            </div>

                            <div class="sf-note" :class="quote && quote.available ? 'sf-note-ok' : (quoteError ? 'sf-note-err' : 'sf-note-info')">
                                <template x-if="quoting"><span><i class="fas fa-circle-notch ts-spin" aria-hidden="true"></i> กำลังคำนวณค่าส่ง...</span></template>
                                <template x-if="!quoting && quote && quote.available">
                                    <span>ค่าส่ง <b>฿<span x-text="money(quote.total_fee)"></span></b> · ระยะ <span x-text="window.ts.distance(quote.distance_km)"></span> · ประมาณ <span x-text="quote.estimated_duration_minutes"></span> นาที</span>
                                </template>
                                <template x-if="!quoting && quoteError"><span x-text="quoteError"></span></template>
                                <template x-if="!quoting && !quote && !quoteError"><span>ปักหมุดจุดส่งเพื่อดูค่าส่ง</span></template>
                            </div>
                        </div>

                        <div>
                            <label class="ts-label" for="co-notes">โน้ตถึงร้าน/ไรเดอร์ <span class="ts-muted" style="font-weight:600;">(ไม่บังคับ)</span></label>
                            <input id="co-notes" type="text" name="delivery_notes" class="tp-input" maxlength="500" value="{{ old('delivery_notes') }}" placeholder="เช่น โทรก่อนถึง ฝากไว้ที่ป้อมยาม">
                        </div>
                    </section>

                    {{-- ════════ วิธีชำระเงิน ════════ --}}
                    <section class="tp-card ts-stack" aria-labelledby="co-pay-h">
                        <h2 id="co-pay-h" class="ts-h2"><span class="tp-tile" style="width:32px; height:32px; border-radius:10px; font-size:14px;">2</span> วิธีชำระเงิน</h2>
                        @if(! $walletOn && ! $codOn)
                            <div class="sf-note sf-note-err">ขณะนี้ตลาดสดยังไม่เปิดรับชำระเงิน กรุณาลองใหม่ภายหลัง</div>
                        @endif
                        <div class="ts-grid" style="--ts-min:220px; gap:10px;">
                            @if($walletOn)
                                <button type="button" class="ts-choice" :class="payment === 'wallet' ? 'is-on' : ''" x-on:click="payment = 'wallet'" :aria-pressed="payment === 'wallet' ? 'true' : 'false'">
                                    <span class="ind"><i class="fas fa-check" aria-hidden="true"></i></span>
                                    <span class="name">
                                        <i class="fas fa-wallet" style="color:var(--ts-info);" aria-hidden="true"></i> กระเป๋าเงิน
                                        <span class="ts-muted ts-small" style="display:block; font-weight:600;">คงเหลือ ฿{{ $ui::money($walletBalance) }} · ระบบถือเงินไว้จนได้รับของ</span>
                                    </span>
                                </button>
                            @endif
                            @if($codOn)
                                <button type="button" class="ts-choice" :class="payment === 'cod' ? 'is-on' : ''" x-on:click="payment = 'cod'" :aria-pressed="payment === 'cod' ? 'true' : 'false'">
                                    <span class="ind"><i class="fas fa-check" aria-hidden="true"></i></span>
                                    <span class="name">
                                        <i class="fas fa-money-bill-wave" style="color:var(--ts-ok);" aria-hidden="true"></i> เก็บเงินปลายทาง
                                        <span class="ts-muted ts-small" style="display:block; font-weight:600;" x-text="delivery === 'rider' ? 'จ่ายเงินสดให้ไรเดอร์ (ไม่เกิน ฿' + money(maxCod) + ')' : 'จ่ายเงินสดตอนรับของที่ร้าน'"></span>
                                    </span>
                                </button>
                            @endif
                        </div>
                        <div class="sf-note sf-note-warn" x-show="walletShort" x-cloak>
                            ยอดในกระเป๋าไม่พอ (ขาดอีก ฿<span x-text="money(grandTotal - walletBalance)"></span>)
                            @if(\Illuminate\Support\Facades\Route::has('user.wallet.topup'))
                                — <a href="{{ route('user.wallet.topup') }}" class="ts-link">เติมเงิน</a>
                            @endif
                            หรือเลือกเก็บเงินปลายทาง
                        </div>
                        <div class="sf-note sf-note-warn" x-show="codTooHigh" x-cloak>
                            ยอดเก็บปลายทางเกิน ฿<span x-text="money(maxCod)"></span> — กรุณาจ่ายด้วยกระเป๋าเงินหรือเลือกรับเองที่ร้าน
                        </div>
                    </section>
                </div>

                {{-- ════════ สรุปออเดอร์ ════════ --}}
                <aside class="sf-sticky" aria-label="สรุปออเดอร์">
                    <div class="tp-card ts-stack">
                        <div class="ts-row" style="gap:10px; flex-wrap:nowrap;">
                            <span class="ts-avatar" style="width:42px; height:42px; border-radius:14px; font-size:16px;">
                                @if(! empty($sInfo['shop_image']))<img src="{{ $sInfo['shop_image'] }}" alt="">@else{{ $ui::initial($sInfo['shop_name'] ?? '') }}@endif
                            </span>
                            <b style="font-size:15px; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $sInfo['shop_name'] ?? 'ร้านตลาดสด' }}</b>
                        </div>
                        <div>
                            @foreach($shopCart['items'] as $line)
                                <div class="ts-line" style="padding:9px 0;">
                                    <span class="ts-num" style="min-width:26px; color:var(--deep1);">{{ (int) $line['quantity'] }}×</span>
                                    <div style="flex:1; min-width:0;">
                                        <div style="font-size:13.5px; font-weight:700; overflow-wrap:anywhere;">{{ $line['title'] }}</div>
                                        @if($line['options_label'] !== '')
                                            <div class="ts-muted ts-small">{{ $line['options_label'] }}</div>
                                        @endif
                                        @if($line['note'])
                                            <div class="ts-muted ts-small">“{{ $line['note'] }}”</div>
                                        @endif
                                        @if(! $line['is_valid'])
                                            <div class="ts-err" style="margin:2px 0 0;">{{ $line['issue']['message'] ?? 'สั่งไม่ได้' }}</div>
                                        @endif
                                    </div>
                                    <b class="ts-num" style="font-size:13.5px;">฿{{ $ui::money($line['line_total']) }}</b>
                                </div>
                            @endforeach
                        </div>
                        <div>
                            <div class="ts-kv"><span>ยอดสินค้า</span><b>฿{{ $ui::money($subtotal) }}</b></div>
                            <div class="ts-kv"><span>ค่าส่ง</span><b x-text="delivery === 'pickup' ? 'ฟรี (รับเอง)' : (quote && quote.available ? '฿' + money(quote.total_fee) : 'รอปักหมุด')">ฟรี (รับเอง)</b></div>
                        </div>
                        <div class="sf-total"><span class="ts-muted">ยอดที่ต้องจ่าย</span><span class="tp-num">฿<span x-text="money(grandTotal)">{{ $ui::money($subtotal) }}</span></span></div>
                        <button type="submit" class="ts-btn3d ts-tone-gold lg block" :disabled="!canSubmit || submitting">
                            <i class="fas" :class="submitting ? 'fa-circle-notch ts-spin' : 'fa-circle-check'" aria-hidden="true"></i>
                            <span x-text="submitting ? 'กำลังสั่งซื้อ...' : 'ยืนยันสั่งซื้อ'">ยืนยันสั่งซื้อ</span>
                        </button>
                        <p class="ts-help" style="margin:0; text-align:center;" x-text="blockText" x-show="blockText"></p>
                        <p class="ts-help" style="margin:0; text-align:center;">ร้านต้องกดรับออเดอร์ก่อน — ถ้าร้านไม่รับภายในเวลาที่กำหนด ระบบยกเลิกและคืนเงินให้อัตโนมัติ</p>
                    </div>
                </aside>
            </div>

            {{-- แถบยืนยันติดล่างจอ (มือถือ) --}}
            <div class="ts-bottom-bar">
                <div style="flex:1; min-width:0;">
                    <div class="ts-muted ts-small">ยอดที่ต้องจ่าย</div>
                    <div class="ts-money" style="font-size:21px;">฿<span x-text="money(grandTotal)">{{ $ui::money($subtotal) }}</span></div>
                </div>
                <button type="submit" class="ts-btn3d ts-tone-gold" :disabled="!canSubmit || submitting">
                    <i class="fas" :class="submitting ? 'fa-circle-notch ts-spin' : 'fa-circle-check'" aria-hidden="true"></i> ยืนยันสั่งซื้อ
                </button>
            </div>
        </form>
    </div>
</main>

<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
    /**
     * หน้ายืนยันสั่งซื้อ: เลือกวิธีรับ/จ่าย + หมุดจุดส่ง + ค่าส่งจริงจากเซิร์ฟเวอร์ + กันกดซ้ำ
     * ยอดที่แสดงเป็นตัวอย่าง — เซิร์ฟเวอร์คำนวณยอดจริงอีกครั้งตอนสั่ง
     */
    window.tsCheckout = function (cfg) {
        let quoteTimer = null;
        let quoteSeq = 0;
        const num = (v) => { const n = parseFloat(v); return isFinite(n) ? n : null; };

        return {
            delivery: cfg.delivery, payment: cfg.payment,
            riderEnabled: cfg.riderEnabled, walletBalance: cfg.walletBalance, maxCod: cfg.maxCod,
            lat: num(cfg.lat), lng: num(cfg.lng), address: cfg.address || '',
            addresses: cfg.addresses || [], pickedAddress: null,
            quote: null, quoteError: '', quoting: false, submitting: false,

            money(n) { return window.ts.money(n || 0); },

            init() {
                if (this.delivery === 'rider' && this.lat !== null) { this.requestQuote(); }
            },

            get deliveryFee() { return this.delivery === 'rider' && this.quote && this.quote.available ? Number(this.quote.total_fee) : 0; },
            get grandTotal() { return Math.round((cfg.subtotal + this.deliveryFee) * 100) / 100; },
            get walletShort() { return this.payment === 'wallet' && this.walletBalance < this.grandTotal; },
            get codTooHigh() { return this.payment === 'cod' && this.delivery === 'rider' && this.maxCod > 0 && this.grandTotal > this.maxCod; },

            get blockText() {
                if (!cfg.canCheckout) { return 'ยังสั่งซื้อจากร้านนี้ไม่ได้ตอนนี้'; }
                if (!cfg.walletOn && !cfg.codOn) { return 'ยังไม่เปิดรับชำระเงิน'; }
                if (this.delivery === 'rider') {
                    if (this.lat === null) { return 'กรุณาปักหมุดจุดส่ง'; }
                    if (this.address.trim() === '') { return 'กรุณากรอกที่อยู่จัดส่ง'; }
                    if (this.quoting) { return 'กำลังคำนวณค่าส่ง...'; }
                    if (!this.quote || !this.quote.available) { return this.quoteError || 'ยังคำนวณค่าส่งไม่ได้'; }
                }
                if (this.walletShort) { return 'ยอดในกระเป๋าไม่พอ'; }
                if (this.codTooHigh) { return 'ยอดเก็บปลายทางเกินกำหนด'; }
                return '';
            },
            get canSubmit() { return this.blockText === ''; },

            setDelivery(d) {
                if (d === 'rider' && !this.riderEnabled) { return; }
                this.delivery = d;
                if (d === 'rider') {
                    // แผนที่เพิ่งแสดง → ให้ Leaflet คำนวณขนาดใหม่ (รอให้กล่องแสดงผลจริงก่อน)
                    [60, 400].forEach((ms) => setTimeout(() => window.dispatchEvent(new CustomEvent('ts-map-refresh')), ms));
                    if (this.lat !== null && !this.quote) { this.requestQuote(); }
                }
            },

            useAddress(a) {
                this.pickedAddress = a.id;
                this.address = a.full_address || '';
                if (a.latitude !== null && a.longitude !== null) {
                    window.dispatchEvent(new CustomEvent('ts-pin-set', { detail: { id: 'dropoff', lat: a.latitude, lng: a.longitude } }));
                } else {
                    window.ts.notify('ที่อยู่นี้ยังไม่ได้ปักหมุด กรุณาปักหมุดบนแผนที่', 'info');
                }
            },

            onPin(d) {
                if (!d || d.id !== 'dropoff') { return; }
                this.lat = d.lat;
                this.lng = d.lng;
                this.requestQuote();
            },

            requestQuote() {
                clearTimeout(quoteTimer);
                quoteTimer = setTimeout(() => this.fetchQuote(), 450);
            },

            async fetchQuote() {
                if (this.lat === null || this.lng === null) { return; }
                const seq = ++quoteSeq;
                this.quoting = true;
                this.quoteError = '';
                const r = await window.ts.get(cfg.quoteUrl, { seller_id: cfg.sellerId, latitude: this.lat, longitude: this.lng });
                if (seq !== quoteSeq) { return; }
                this.quoting = false;
                if (r.data && r.data.available) {
                    this.quote = r.data;
                } else {
                    this.quote = null;
                    this.quoteError = r.message || 'คำนวณค่าส่งไม่สำเร็จ';
                }
            },

            submit(e) {
                if (this.submitting || !this.canSubmit) {
                    e.preventDefault();
                    if (!this.submitting && this.blockText) { window.ts.notify(this.blockText, 'error'); }
                    return;
                }
                this.submitting = true;
            }
        };
    };
</script>
@endpush
