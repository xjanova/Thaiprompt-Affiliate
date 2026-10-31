@extends('layouts.user-nova')

@section('title', 'ฝากเงิน')

@php
    // ฝากเงิน — ธีมโนวาเต็ม · กรอกยอดครั้งเดียวใช้ได้ทุกช่องทาง · โอนธนาคารขั้นต่ำ ฿100 (ยอดต่ำกว่านั้น SMS ธนาคารไม่แจ้ง)
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    [$balInt, $balDec] = explode('.', number_format((float) $wallet->balance, 2));
    $gateways = array_filter($availableGateways ?? [], fn ($g) => ! empty($g['enabled']));
    $gwArt = ['promptpay' => 'qr', 'bank_transfer' => 'bank', 'stripe' => 'card', 'paypal' => 'transfer'];
    $bankMin = 100;

    // เปิดฟอร์มเดิมค้างไว้เมื่อส่งไม่ผ่าน validation · มีช่องทางเดียวเลือกให้เลย
    $initMethod = null;
    if ($errors->has('slip') && isset($gateways['bank_transfer'])) {
        $initMethod = 'bank_transfer';
    } elseif ($errors->any() && isset($gateways['promptpay'])) {
        $initMethod = 'promptpay';
    } elseif (count($gateways) === 1) {
        $initMethod = array_key_first($gateways);
    }
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-dep-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Top up</div>
            <h1 id="nvm-dep-h">ฝากเงินเข้ากระเป๋า</h1>
            <p class="nvm-ph__lead">เลือกช่องทางที่สะดวก พร้อมเพย์เข้าทันที · โอนธนาคารแนบสลิปรอตรวจไม่เกิน 24 ชม.</p>
            <div class="nvm-bal">
                <small>ยอดเงินปัจจุบัน</small>
                <b class="nvm-foil nvm-num">฿{{ $balInt }}<span>.{{ $balDec }}</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/deposit.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>ทำรายการไม่สำเร็จ</b>
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    </section>
@endif

<div class="nvm-split" x-data="nvmDeposit({ method: @js($initMethod), amount: @js((string) old('amount', '')), min: {{ $bankMin }}, scroll: @js($errors->any()) })" @pageshow.window="busy = false">
    <div class="nvm-stack">
        {{-- ── 1) เลือกช่องทาง ─────────────────────────────── --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-gw-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Step 1</small><h2 id="nvm-gw-h">เลือกช่องทางการชำระเงิน</h2></div></div>
            @if(count($gateways))
                <div class="nvm-opts" role="radiogroup" aria-labelledby="nvm-gw-h">
                    @foreach($gateways as $key => $gateway)
                        <label class="nvm-opt" @if($key === 'bank_transfer') :class="{ 'is-off': low }" :title="low ? 'ยอดต่ำกว่า ฿{{ $bankMin }} กรุณาใช้พร้อมเพย์' : ''" @endif>
                            <input type="radio" name="nvm_method" value="{{ $key }}" x-model="method" @change="reveal()" @if($key === 'bank_transfer') :disabled="low" @endif>
                            <img src="{{ asset('images/nova/wallet/'.($gwArt[$key] ?? 'deposit').'.webp') }}" alt="" width="44" height="44" loading="lazy">
                            <span>
                                <b>{{ $gateway['name'] }}</b>
                                <small>{{ $gateway['description'] }}</small>
                                @if($key === 'promptpay')
                                    <span class="nvm-chip nvm-chip--ok">เข้าทันที</span>
                                @elseif($key === 'bank_transfer')
                                    <span class="nvm-chip nvm-chip--warn">รออนุมัติ</span> <span class="nvm-chip nvm-chip--bad">ขั้นต่ำ ฿{{ $bankMin }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="nvm-note is-bad" x-show="low" x-cloak>
                    <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                    <span><b>ยอดต่ำกว่า ฿{{ $bankMin }}</b> — ใช้ได้เฉพาะพร้อมเพย์ (ระบบแจ้งเตือน SMS ธนาคารไม่รองรับยอดต่ำกว่านี้)</span>
                </p>
            @else
                <div class="nvm-empty">
                    <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                    <b>ยังไม่เปิดช่องทางฝากเงิน</b>
                    กรุณาติดต่อฝ่ายสนับสนุน
                </div>
            @endif
        </section>

        {{-- ── 2) พร้อมเพย์ ───────────────────────────────── --}}
        @isset($gateways['promptpay'])
            <section class="nvm-card nvm-box" x-show="method === 'promptpay'" x-cloak x-ref="f_promptpay" aria-labelledby="nvm-pp-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Step 2 · PromptPay</small><h2 id="nvm-pp-h">ชำระผ่านพร้อมเพย์</h2></div></div>
                <form method="POST" action="{{ route('user.wallet.deposit.promptpay') }}" class="nvm-form" @submit="busy = true">
                    @csrf
                    <div class="nvm-field">
                        <label class="nvm-label" for="pp-amount">จำนวนเงิน (บาท) <em>*</em></label>
                        <div class="nvm-amount"><span>฿</span><input id="pp-amount" type="number" name="amount" step="0.01" min="1" inputmode="decimal" required class="nvm-input @error('amount') is-bad @enderror" placeholder="0.00" x-model="amount"></div>
                        <div class="nvm-picks">
                            @foreach([100, 300, 500, 1000, 2000] as $preset)
                                <button type="button" :class="{ 'is-on': Number(amount) === {{ $preset }} }" @click="amount = '{{ $preset }}'">฿{{ number_format($preset) }}</button>
                            @endforeach
                        </div>
                    </div>
                    <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg><span>กดยืนยันแล้วจะได้ QR Code สำหรับสแกนจ่ายจากแอปธนาคาร เงินเข้ากระเป๋าอัตโนมัติ</span></p>
                    <div class="nvm-filter__btns">
                        <button type="button" class="nvm-btn nvm-btn--line" @click="method = null">ยกเลิก</button>
                        <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-qr"/></svg><span x-text="busy ? 'กำลังสร้าง QR…' : 'สร้าง QR Code'">สร้าง QR Code</span></button>
                    </div>
                </form>
            </section>
        @endisset

        {{-- ── 2) โอนผ่านธนาคาร ───────────────────────────── --}}
        @isset($gateways['bank_transfer'])
            <section class="nvm-card nvm-box" x-show="method === 'bank_transfer'" x-cloak x-ref="f_bank_transfer" aria-labelledby="nvm-bt-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Step 2 · Bank transfer</small><h2 id="nvm-bt-h">โอนผ่านธนาคาร + แนบสลิป</h2></div></div>
                <form method="POST" action="{{ route('user.wallet.deposit.bank-transfer') }}" enctype="multipart/form-data" class="nvm-form" @submit="submitBank($event)">
                    @csrf
                    <div class="nvm-field">
                        <label class="nvm-label" for="bt-amount">จำนวนเงิน (บาท) <em>*</em></label>
                        <div class="nvm-amount"><span>฿</span><input id="bt-amount" type="number" name="amount" step="0.01" min="{{ $bankMin }}" inputmode="decimal" required class="nvm-input @error('amount') is-bad @enderror" placeholder="ขั้นต่ำ {{ $bankMin }}" x-model="amount"></div>
                        <span class="nvm-help">ขั้นต่ำ ฿{{ $bankMin }}@isset($gateways['promptpay']) — ยอดน้อยกว่านี้ใช้<button type="button" class="nvm-link" @click="method = 'promptpay'; reveal()">พร้อมเพย์</button>แทน@endisset</span>
                    </div>
                    <div class="nvm-field">
                        <label class="nvm-label" for="bt-slip">สลิปการโอนเงิน <em>*</em></label>
                        <input id="bt-slip" type="file" name="slip" accept="image/*" required class="nvm-input nvm-file @error('slip') is-bad @enderror">
                        <span class="nvm-help">ถ่ายหรือแคปให้เห็นยอดเงิน วันเวลา และเลขอ้างอิงชัดเจน</span>
                    </div>
                    <div class="nvm-field">
                        <label class="nvm-label" for="bt-note">หมายเหตุ (ถ้ามี)</label>
                        <textarea id="bt-note" name="note" rows="3" class="nvm-input" placeholder="เช่น โอนจากบัญชีชื่ออะไร">{{ old('note') }}</textarea>
                    </div>
                    <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg><span>แอดมินตรวจสลิปและอนุมัติภายใน 24 ชั่วโมง ดูสถานะได้ที่ประวัติธุรกรรม</span></p>
                    <div class="nvm-filter__btns">
                        <button type="button" class="nvm-btn nvm-btn--line" @click="method = null">ยกเลิก</button>
                        <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="busy ? 'กำลังส่ง…' : 'ส่งคำขอฝากเงิน'">ส่งคำขอฝากเงิน</span></button>
                    </div>
                </form>
            </section>
        @endisset

        {{-- Stripe / PayPal ยังไม่เปิดใช้ (ตามระบบเดิม) --}}
        @foreach(['stripe' => 'Stripe', 'paypal' => 'PayPal'] as $key => $label)
            @isset($gateways[$key])
                <section class="nvm-card nvm-alert nvm-alert--info" x-show="method === @js($key)" x-cloak x-ref="f_{{ $key }}">
                    <img class="nvm-alert__img" src="{{ asset('images/nova/wallet/'.$gwArt[$key].'.webp') }}" alt="" width="58" height="58" loading="lazy">
                    <div class="nvm-alert__txt">
                        <b>ชำระด้วย {{ $label }} — อยู่ระหว่างพัฒนา</b>
                        <p>กรุณาเลือกช่องทางอื่นไปก่อน</p>
                    </div>
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="method = null">กลับ</button>
                </section>
            @endisset
        @endforeach
    </div>

    {{-- ── คำแนะนำ ─────────────────────────────────────────── --}}
    <aside class="nvm-card nvm-box" aria-labelledby="nvm-tip-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Tips</small><h2 id="nvm-tip-h">ก่อนฝากเงิน</h2></div></div>
        <ol class="nvm-steps">
            <li>ตรวจจำนวนเงินให้ถูกต้องก่อนกดยืนยัน</li>
            <li>พร้อมเพย์: สแกน QR จากแอปธนาคาร ยอดเข้ากระเป๋าอัตโนมัติ</li>
            <li>โอนธนาคาร: แนบสลิปที่ชัด เห็นยอดและเวลาโอน</li>
            <li>ทุกรายการถูกบันทึกในประวัติธุรกรรม มีปัญหาติดต่อฝ่ายสนับสนุนได้เลย</li>
        </ol>
        @if($nvR('user.wallet.transactions'))
            <a class="nvm-btn nvm-btn--line nvm-btn--block nvm-gap-t" href="{{ route('user.wallet.transactions') }}"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ดูประวัติธุรกรรม</a>
        @endif
    </aside>
</div>

@push('scripts')
<script>
    // ฟอร์มฝากเงิน: ยอดเดียวใช้ทุกช่องทาง · ยอด < ขั้นต่ำ ปิดโอนธนาคาร · กันกดส่งซ้ำ
    document.addEventListener('alpine:init', function () {
        window.Alpine.data('nvmDeposit', function (o) {
            return {
                method: o.method,
                amount: o.amount,
                min: o.min,
                busy: false,
                get low() { var a = parseFloat(this.amount) || 0; return a > 0 && a < this.min; },
                // ไม่สลับช่องทางให้อัตโนมัติตอนยอดต่ำ — พิมพ์ 150 ต้องผ่าน 1 กับ 15 ก่อน จะเด้งออกจากฟอร์มกลางคัน
                // เลื่อนลงไปที่ฟอร์มเองเฉพาะตอนกลับมาจาก validation ไม่ผ่าน (เปิดหน้าปกติไม่กระตุกจอ)
                init() { if (this.method && o.scroll) this.reveal(); },
                reveal() { this.$nextTick(() => { var el = this.$refs['f_' + this.method]; if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }); },
                submitBank(e) {
                    if ((parseFloat(this.amount) || 0) < this.min) {
                        e.preventDefault();
                        window.showNotification('ยอดขั้นต่ำโอนผ่านธนาคาร ฿' + this.min + ' — ยอดน้อยกว่านี้ใช้พร้อมเพย์', 'error');
                        return;
                    }
                    this.busy = true;
                },
            };
        });
    });
</script>
@endpush
@endsection
