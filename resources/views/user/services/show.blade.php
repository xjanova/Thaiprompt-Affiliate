{{--
    รายละเอียดบริการ + ฟอร์มจอง (ธีมโนวาเต็ม) — ServiceBookingController::show
    ส่งฟอร์มไป POST user.services.store-booking ด้วยชื่อช่องเดิมทั้งหมด
    สิ่งที่แก้จากหน้าเดิม:
    - ตัวเลือกเพิ่มเติมอยู่นอก <form> และไม่มี name → ไม่เคยถูกส่ง (ลูกค้าเห็นราคารวมออปชันแต่ระบบไม่ได้บันทึก)
      ตอนนี้ย้ายเข้าฟอร์มเป็น selected_options[] (ค่า = ชื่อออปชัน ตามที่ ServicePricingService จับคู่)
    - สรุปราคาเดิมไม่รวม VAT ทั้งที่ระบบคิด VAT จริง → ใช้ยอดจาก calculateTotalPrice() ชุดเดียวกับตอนสร้างการจอง
      และเวลาเลือกออปชันจะถาม calculate-price ด้วยพารามิเตอร์ที่ endpoint ต้องการจริง (service_id · distance_km · selected_options)
    - เวลานัดขั้นต่ำเดิมคิดเป็นเวลา UTC (toISOString) ทำให้เลือกเวลาที่ผ่านไปแล้วได้ 7 ชั่วโมง → ใช้เวลาท้องถิ่นของเครื่อง
    - ช่อง "รายละเอียดบริการ" (features) ถูกตัดออก: ตาราง services ไม่มีคอลัมน์นี้ จึงไม่เคยแสดงอะไร
--}}
@extends('layouts.user-nova')

@section('title', $service->name)

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/services-nfc.css') }}?v={{ filemtime(public_path('theme-nova/member/services-nfc.css')) }}">@endpush

@php
    $user = auth()->user();
    $baht = fn ($n) => '฿'.number_format((float) $n, 2);
    $img = $service->main_image;
    $imgUrl = $img ? (preg_match('#^(https?:)?//#', $img) ? $img : asset(str_starts_with($img, '/') ? ltrim($img, '/') : 'storage/'.$img)) : null;
    $color = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $service->category?->color) ? $service->category->color : null;
    $rating = (float) ($service->average_rating ?? 0);

    // ออปชันที่มีชื่อเท่านั้น (ฝั่งเซิร์ฟเวอร์จับคู่ด้วย name)
    $options = collect(is_array($service->options) ? $service->options : [])
        ->filter(fn ($o) => is_array($o) && filled($o['name'] ?? null))
        ->values();
    $oldOptions = array_map('strval', (array) old('selected_options', []));

    $pricing = $pricing ?? [];
    $walletBalance = (float) ($user->wallet?->balance ?? 0);

    // 🪙 เหรียญ TPIX (เมื่อเปิดรับ + รู้ราคา) — เหรียญเข้ากระเป๋าก่อน แล้วชำระการจองให้อัตโนมัติ · เซิร์ฟเวอร์ตรวจเพดานซ้ำเสมอ
    $tpixSettings = app(\App\Services\TpixPay\TpixPaySettings::class);
    $tpixSpot = $tpixSettings->paymentsEnabled() ? app(\App\Services\TpixPay\TpixPriceService::class)->spot() : null;
    $tpixMax = (float) $tpixSettings->maxPerPaymentThb();

    $payments = [
        ['wallet', 'wallet', 'กระเป๋าเงิน', 'ยอดคงเหลือ '.$baht($walletBalance)],
        ['promptpay', 'qr', 'PromptPay', 'สแกนจ่ายผ่านแอปธนาคาร'],
        ['card', 'card', 'บัตรเครดิต/เดบิต', 'Visa · Mastercard'],
    ];

    $mapsKey = config('services.google_maps.api_key');

    $bookCfg = [
        'priceUrl' => route('user.services.calculate-price'),
        'serviceId' => $service->id,
        'options' => $options->map(fn ($o) => ['name' => (string) $o['name'], 'price' => (float) ($o['price'] ?? 0)])->all(),
        'selected' => $oldOptions,
        'base' => (float) ($pricing['base_price'] ?? $service->base_price),
        'distance' => (float) ($pricing['distance_price'] ?? 0),
        'taxRate' => (float) ($pricing['tax_rate'] ?? 0),
        'tax' => (float) ($pricing['tax_amount'] ?? 0),
        'total' => (float) ($pricing['total_amount'] ?? $service->base_price),
        'requiresLocation' => (bool) $service->requires_location,
        'lat' => old('customer_latitude'),
        'lng' => old('customer_longitude'),
        'address' => (string) old('customer_address', ''),
        'tpixRate' => $tpixSpot ? (float) $tpixSpot['price_thb'] : 0,
        'tpixMax' => $tpixMax,
    ];
@endphp

@section('content')
<div class="nvm-stack" x-data="nvmServiceBooking(@js($bookCfg))" @pageshow.window="busy = false">

<section class="nvm-ph" aria-labelledby="nvm-sns-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ $service->category ? route('user.services.category', $service->category) : route('user.services.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>{{ $service->category->name ?? 'บริการทั้งหมด' }}</a>
            <div class="nvm-eyebrow nvm-latin">Service</div>
            <h1 id="nvm-sns-h">{{ $service->name }}</h1>
            @if($service->short_description)
                <p class="nvm-ph__lead">{{ $service->short_description }}</p>
            @endif
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--gold nvm-num">เริ่มต้น {{ $baht($service->base_price) }}</span>
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>{{ $service->duration_minutes ? $service->duration_text : '60 นาที' }}</span>
                @if($service->requires_location)
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg>บริการถึงที่</span>
                @endif
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/lamp.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>จองไม่สำเร็จ กรุณาตรวจข้อมูลอีกครั้ง</b>
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    </section>
@endif

<div class="nvm-split">
    <div class="nvm-stack">
        <section class="nvm-card nvm-sn-hero-img" aria-label="รูปบริการ">
            <div class="nvm-sn-svc__img" @if($color) style="--c: {{ $color }}" @endif>
                @if($imgUrl)
                    <img src="{{ $imgUrl }}" alt="{{ $service->name }}">
                @else
                    <img class="is-art" src="{{ asset('images/nova/deco/lotus.webp') }}" alt="">
                @endif
                @if($service->category)<span class="nvm-sn-svc__tag">{{ $service->category->name }}</span>@endif
                @if($service->is_featured)<span class="nvm-sn-svc__hot">แนะนำ</span>@endif
            </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-sns-d-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">About</small><h2 id="nvm-sns-d-h">รายละเอียดบริการ</h2></div></div>
            <div class="nvm-minis">
                <div class="nvm-mini"><small>ระยะเวลา</small><b>{{ $service->duration_minutes ? $service->duration_text : '60 นาที' }}</b></div>
                <div class="nvm-mini"><small>ราคาเริ่มต้น</small><b class="nvm-num">{{ $baht($service->base_price) }}</b><x-tpix-price :thb="$service->base_price" /></div>
                <div class="nvm-mini">
                    <small>คะแนน</small>
                    <b class="nvm-sn-stars nvm-sn-stars--lg" aria-label="คะแนน {{ number_format($rating, 1) }} จาก 5">
                        @for($i = 1; $i <= 5; $i++)<span class="{{ $i <= round($rating) ? 'is-on' : '' }}" aria-hidden="true">★</span>@endfor
                        <b class="nvm-num">{{ number_format($rating, 1) }}</b>
                    </b>
                </div>
                <div class="nvm-mini"><small>รีวิว</small><b class="nvm-num">{{ number_format($service->reviews_count ?? 0) }} รีวิว</b></div>
            </div>
            @if($service->description)
                <p class="nvm-desc nvm-gap-t">{{ $service->description }}</p>
            @endif
            @if($service->cancellation_hours)
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ยกเลิกฟรีก่อนเวลานัดอย่างน้อย {{ (int) $service->cancellation_hours }} ชั่วโมง</p>
            @endif
        </section>

        @if(($earningsSummary['cashback_total'] ?? 0) > 0 || ($earningsSummary['pv_total'] ?? 0) > 0)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-sns-e-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Rewards</small><h2 id="nvm-sns-e-h">ผลตอบแทนที่จะได้รับ</h2></div></div>
                <div class="nvm-stack">
                    @if(($earningsSummary['cashback_total'] ?? 0) > 0)
                        <div class="nvm-sn-earn">
                            <svg class="nvm-ico"><use href="#nvm-i-gift"/></svg>
                            <div><b>เงินคืน (Cashback) {{ rtrim(rtrim(number_format((float) $earningsSummary['cashback_percentage'], 2), '0'), '.') }}%</b><small>โอนเข้ากระเป๋าเงินหลังใช้บริการเสร็จสิ้น</small></div>
                            <strong class="nvm-num">{{ $baht($earningsSummary['cashback_total']) }}</strong>
                        </div>
                    @endif
                    @if(($earningsSummary['pv_total'] ?? 0) > 0)
                        <div class="nvm-sn-earn is-pv">
                            <svg class="nvm-ico"><use href="#nvm-i-award"/></svg>
                            <div><b>คะแนน PV</b><small>บันทึกเข้าบัญชีหลังชำระเงิน ใช้คำนวณค่าคอมมิชชั่น</small></div>
                            <strong class="nvm-num">{{ number_format((float) $earningsSummary['pv_total'], 0) }} PV</strong>
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </div>

    <aside class="nvm-card nvm-box nvm-sn-sticky" id="nvm-sn-book" aria-labelledby="nvm-sns-b-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Book now</small><h2 id="nvm-sns-b-h">จองบริการนี้</h2></div></div>

        <form class="nvm-form" action="{{ route('user.services.store-booking', $service) }}" method="POST" @submit="submit($event)">
            @csrf

            <div class="nvm-field">
                <label class="nvm-label" for="nvm-sns-at">วันและเวลานัดหมาย <em>*</em></label>
                <input id="nvm-sns-at" class="nvm-input nvm-num @error('scheduled_at') is-bad @enderror" type="datetime-local" name="scheduled_at" required :min="minAt" value="{{ old('scheduled_at') }}">
                @error('scheduled_at')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>

            @if($options->isNotEmpty())
                <fieldset class="nvm-field">
                    <legend class="nvm-label">ตัวเลือกเพิ่มเติม</legend>
                    <div class="nvm-opts nvm-opts--2">
                        @foreach($options as $option)
                            <label class="nvm-opt">
                                <input type="checkbox" name="selected_options[]" value="{{ $option['name'] }}" x-model="selected" @change="reprice()">
                                <span class="nvm-sn-optrow">
                                    <span><b>{{ $option['name'] }}</b>@if(filled($option['description'] ?? null))<small>{{ $option['description'] }}</small>@endif</span>
                                    <em class="nvm-num">+{{ $baht($option['price'] ?? 0) }}</em>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            @if($service->requires_location)
                <div class="nvm-sn-loc">
                    <span class="nvm-label">ที่อยู่รับบริการ <em>*</em></span>
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="locate()" :disabled="locating">
                        <svg class="nvm-ico"><use href="#nvm-i-pin"/></svg><span x-text="locating ? 'กำลังหาตำแหน่ง…' : (lat ? 'อัปเดตตำแหน่งปัจจุบัน' : 'ใช้ตำแหน่งปัจจุบัน')">ใช้ตำแหน่งปัจจุบัน</span>
                    </button>
                    <span class="nvm-chip nvm-chip--ok" x-show="lat" x-cloak><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ได้ตำแหน่ง GPS แล้ว</span>
                    <span class="nvm-err" x-show="locError" x-text="locError" x-cloak></span>
                    <input type="hidden" name="customer_latitude" :value="lat ?? ''">
                    <input type="hidden" name="customer_longitude" :value="lng ?? ''">
                    @error('customer_latitude')<span class="nvm-err">กรุณากด "ใช้ตำแหน่งปัจจุบัน" เพื่อระบุตำแหน่ง GPS</span>@enderror
                    <label class="nvm-sr" for="nvm-sns-addr">ที่อยู่เต็ม</label>
                    <textarea id="nvm-sns-addr" class="nvm-input nvm-textarea @error('customer_address') is-bad @enderror" name="customer_address" rows="3" required maxlength="500" x-model="address" placeholder="บ้านเลขที่ ซอย ถนน แขวง/ตำบล เขต/อำเภอ จังหวัด รหัสไปรษณีย์"></textarea>
                    @error('customer_address')<span class="nvm-err">{{ $message }}</span>@enderror
                    <div class="nvm-sn-2col">
                        <div class="nvm-field">
                            <label class="nvm-label" for="nvm-sns-floor">ชั้น</label>
                            <input id="nvm-sns-floor" class="nvm-input" type="text" name="floor_number" maxlength="50" value="{{ old('floor_number') }}">
                        </div>
                        <div class="nvm-field">
                            <label class="nvm-label" for="nvm-sns-room">เลขที่ห้อง</label>
                            <input id="nvm-sns-room" class="nvm-input" type="text" name="room_number" maxlength="50" value="{{ old('room_number') }}">
                        </div>
                    </div>
                    <div class="nvm-field">
                        <label class="nvm-label" for="nvm-sns-lm">จุดสังเกต / ตึก</label>
                        <input id="nvm-sns-lm" class="nvm-input" type="text" name="landmark" maxlength="255" value="{{ old('landmark') }}" placeholder="เช่น ตึก A ใกล้ร้านสะดวกซื้อ">
                    </div>
                </div>
            @endif

            <div class="nvm-sn-2col">
                <div class="nvm-field">
                    <label class="nvm-label" for="nvm-sns-cn">ชื่อผู้ติดต่อ</label>
                    <input id="nvm-sns-cn" class="nvm-input" type="text" name="contact_name" maxlength="255" value="{{ old('contact_name', $user->name) }}" autocomplete="name">
                </div>
                <div class="nvm-field">
                    <label class="nvm-label" for="nvm-sns-cp">เบอร์โทร</label>
                    <input id="nvm-sns-cp" class="nvm-input nvm-num" type="tel" name="contact_phone" maxlength="20" value="{{ old('contact_phone', $user->phone) }}" autocomplete="tel">
                    @error('contact_phone')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="nvm-field">
                <label class="nvm-label" for="nvm-sns-note">หมายเหตุถึงผู้ให้บริการ</label>
                <textarea id="nvm-sns-note" class="nvm-input nvm-textarea" name="customer_notes" rows="2" maxlength="1000" placeholder="ข้อมูลเพิ่มเติมสำหรับผู้ให้บริการ">{{ old('customer_notes') }}</textarea>
            </div>

            <fieldset class="nvm-field">
                <legend class="nvm-label">ช่องทางการชำระเงิน <em>*</em></legend>
                <div class="nvm-opts">
                    @foreach($payments as [$value, $icon, $label, $hint])
                        <label class="nvm-opt">
                            <input type="radio" name="payment_method" value="{{ $value }}" required @checked(old('payment_method') === $value)>
                            <span class="nvm-opt__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $icon }}"/></svg></span>
                            <span><b>{{ $label }}</b><small class="nvm-num">{{ $hint }}</small></span>
                        </label>
                    @endforeach
                    @if($tpixSpot)
                        <label class="nvm-opt" x-show="! tpixOver()">
                            <input type="radio" name="payment_method" value="tpix" required @checked(old('payment_method') === 'tpix') :disabled="tpixOver()">
                            <span class="nvm-opt__ic"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg></span>
                            <span>
                                <b>เหรียญ TPIX</b>
                                <small class="nvm-num" x-text="'≈ ' + (total / tpixRate).toLocaleString('en-US', { maximumFractionDigits: 4 }) + ' TPIX · 1 TPIX = {{ \App\Services\TpixPay\TpixPriceService::formatRate($tpixSpot['price_thb']) }}'">โอนเหรียญบนเชน TPIX ระบบยืนยันให้อัตโนมัติ</small>
                            </span>
                        </label>
                    @endif
                </div>
                @if($tpixSpot && $tpixMax > 0)
                    <p class="nvm-note" x-show="tpixOver()" x-cloak><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ชำระด้วยเหรียญ TPIX ได้ไม่เกิน {{ $baht($tpixMax) }} ต่อรายการ</p>
                @endif
                @error('payment_method')<span class="nvm-err">{{ $message }}</span>@enderror
            </fieldset>

            <dl class="nvm-kv" aria-live="polite">
                <dt>ค่าบริการพื้นฐาน</dt><dd class="nvm-num">{{ $baht($bookCfg['base']) }}</dd>
                <dt x-show="optionsPrice > 0" x-cloak>ตัวเลือกเพิ่มเติม</dt><dd class="nvm-num" x-show="optionsPrice > 0" x-cloak x-text="money(optionsPrice)"></dd>
                <dt x-show="distance > 0" @if($bookCfg['distance'] <= 0) x-cloak @endif>ค่าเดินทาง</dt><dd class="nvm-num" x-show="distance > 0" @if($bookCfg['distance'] <= 0) x-cloak @endif x-text="money(distance)">{{ $baht($bookCfg['distance']) }}</dd>
                <dt>ภาษีมูลค่าเพิ่ม</dt><dd class="nvm-num" x-text="money(tax)">{{ $baht($bookCfg['tax']) }}</dd>
                <dt class="nvm-kv__total">รวมทั้งหมด</dt><dd class="nvm-kv__total nvm-num" x-text="money(total)">{{ $baht($bookCfg['total']) }}</dd>
            </dl>
            <p class="nvm-note" x-show="priceStale" x-cloak><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>คำนวณราคาล่าสุดไม่สำเร็จ ยอดที่แสดงเป็นยอดประมาณ ระบบจะคิดราคาจริงตอนยืนยันการจอง</p>

            <button type="submit" class="nvm-btn nvm-btn--gold nvm-w-full" :disabled="busy || ! canSubmit()">
                <svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="busy ? 'กำลังจอง…' : 'ยืนยันการจอง'">ยืนยันการจอง</span>
            </button>
        </form>
    </aside>
</div>

</div>
@endsection

@push('scripts')
@if($service->requires_location && $mapsKey)
    <script>window.nvmSnMapsReady = function () {};</script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($mapsKey) }}&callback=nvmSnMapsReady" async defer></script>
@endif
<script>
/**
 * ฟอร์มจองบริการ — คำนวณราคาใหม่เมื่อเลือกออปชัน หาตำแหน่ง GPS และกันกดยืนยันซ้ำ
 */
function nvmServiceBooking(cfg) {
    const pad = (n) => String(n).padStart(2, '0');

    return {
        selected: cfg.selected || [],
        lat: cfg.lat || null,
        lng: cfg.lng || null,
        address: cfg.address || '',
        optionsPrice: 0,
        distance: cfg.distance,
        tax: cfg.tax,
        total: cfg.total,
        tpixRate: cfg.tpixRate,
        tpixMax: cfg.tpixMax,
        minAt: '',
        locating: false,
        locError: '',
        priceStale: false,
        busy: false,
        seq: 0,

        init() {
            // เวลานัดขั้นต่ำ = อีก 1 ชั่วโมงจากตอนนี้ ตามเวลาท้องถิ่นของเครื่อง (datetime-local ไม่มีโซนเวลา)
            const d = new Date(Date.now() + 3600000);
            this.minAt = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
            if (this.selected.length) { this.reprice(); }
        },

        money(n) {
            return '฿' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        localOptionsPrice() {
            return cfg.options.filter((o) => this.selected.includes(o.name)).reduce((sum, o) => sum + o.price, 0);
        },

        async reprice() {
            const ticket = ++this.seq;
            this.optionsPrice = this.localOptionsPrice();
            // ยอดประมาณทันที (สูตรเดียวกับเซิร์ฟเวอร์) ระหว่างรอคำตอบ
            const subtotal = cfg.base + this.distance + this.optionsPrice;
            this.tax = subtotal * cfg.taxRate;
            this.total = subtotal + this.tax;

            try {
                const res = await fetch(cfg.priceUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ service_id: cfg.serviceId, distance_km: 0, selected_options: this.selected }),
                });
                const data = await res.json();
                if (ticket !== this.seq) { return; }
                if (! res.ok || ! data.success || ! data.pricing) { throw new Error('pricing'); }
                this.optionsPrice = Number(data.pricing.options_price || 0);
                this.distance = Number(data.pricing.distance_price || 0);
                this.tax = Number(data.pricing.tax_amount || 0);
                this.total = Number(data.pricing.total_amount || 0);
                this.priceStale = false;
            } catch (e) {
                if (ticket === this.seq) { this.priceStale = true; }
            }
        },

        tpixOver() {
            return this.tpixMax > 0 && this.total > this.tpixMax;
        },

        locate() {
            this.locError = '';
            if (! navigator.geolocation) {
                this.locError = 'เบราว์เซอร์นี้หาตำแหน่งไม่ได้ กรุณาลองเบราว์เซอร์อื่น';
                return;
            }
            this.locating = true;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat = pos.coords.latitude;
                    this.lng = pos.coords.longitude;
                    this.locating = false;
                    this.reverseGeocode();
                },
                (err) => {
                    this.locating = false;
                    this.locError = err.code === 1
                        ? 'ไม่ได้รับอนุญาตให้เข้าถึงตำแหน่ง กรุณาเปิดสิทธิ์ตำแหน่งในเบราว์เซอร์'
                        : 'หาตำแหน่งไม่สำเร็จ กรุณาลองใหม่อีกครั้ง';
                },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        },

        async reverseGeocode() {
            // เติมที่อยู่ให้อัตโนมัติเมื่อโหลด Google Maps ได้ และช่องที่อยู่ยังว่าง (ไม่ทับสิ่งที่ลูกค้าพิมพ์เอง)
            if (! (window.google && google.maps && google.maps.Geocoder) || this.address.trim().length > 0) { return; }
            try {
                const res = await new google.maps.Geocoder().geocode({ location: { lat: this.lat, lng: this.lng } });
                if (res.results && res.results[0] && ! this.address.trim()) { this.address = res.results[0].formatted_address; }
            } catch (e) { /* ลูกค้าพิมพ์ที่อยู่เองได้ */ }
        },

        canSubmit() {
            if (! cfg.requiresLocation) { return true; }
            return !! (this.lat && this.lng && this.address.trim().length > 10);
        },

        submit(event) {
            if (this.busy || ! this.canSubmit()) { event.preventDefault(); return; }
            this.busy = true;
        },
    };
}
</script>
@endpush
