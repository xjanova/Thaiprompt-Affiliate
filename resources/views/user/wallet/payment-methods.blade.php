@extends('layouts.user-nova')

@section('title', 'บัญชีรับเงิน')

@php
    // บัญชีรับเงิน (ใช้ตอนถอนเงิน) — ธีมโนวาเต็ม
    // 🐛 (2026-10-29) เดิมเช็ค $method->status === 'active' แต่ตารางใช้ is_active → ทุกบัญชีขึ้น "ปิดใช้งาน"
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $maskNo = fn ($no) => '•••• '.substr(preg_replace('/[^0-9A-Za-z]/', '', (string) $no), -4);
    $banks = ['ธนาคารกรุงเทพ', 'ธนาคารกสิกรไทย', 'ธนาคารไทยพาณิชย์', 'ธนาคารกรุงไทย', 'ธนาคารกรุงศรีอยุธยา', 'ธนาคารทหารไทยธนชาต',
        'ธนาคารออมสิน', 'ธนาคารอาคารสงเคราะห์', 'ธนาคารเพื่อการเกษตรและสหกรณ์การเกษตร', 'ธนาคารเกียรตินาคินภัทร', 'ธนาคารซีไอเอ็มบีไทย',
        'ธนาคารทิสโก้', 'ธนาคารยูโอบี', 'ธนาคารแลนด์ แอนด์ เฮ้าส์', 'ธนาคารไอซีบีซี (ไทย)'];
    $types = [
        'promptpay' => ['qr', 'พร้อมเพย์', 'เบอร์มือถือหรือเลขบัตรประชาชน'],
        'bank_transfer' => ['bank', 'บัญชีธนาคาร', 'โอนเข้าบัญชีออมทรัพย์/กระแสรายวัน'],
        'paypal' => ['transfer', 'PayPal', 'อีเมลบัญชี PayPal'],
    ];
    $activeCount = $paymentMethods->where('is_active', true)->count();
@endphp

@section('content')

<div x-data="{ open: @js($errors->any() || $paymentMethods->isEmpty()), type: @js(old('type', 'promptpay')), busy: false }" @pageshow.window="busy = false" class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-pmp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Payout accounts</div>
            <h1 id="nvm-pmp-h">บัญชีรับเงิน</h1>
            <p class="nvm-ph__lead">บัญชีที่ใช้รับเงินตอนถอน · ชื่อบัญชีต้องตรงกับชื่อที่ยืนยันตัวตน (KYC)</p>
            <div class="nvm-bal">
                <small>บัญชีที่ใช้งานได้</small>
                <b class="nvm-foil nvm-num">{{ $activeCount }}<span> บัญชี</span></b>
            </div>
            <div class="nvm-gap-t">
                <button type="button" class="nvm-btn nvm-btn--gold" @click="open = true; $nextTick(() => $refs.form.scrollIntoView({ behavior: 'smooth', block: 'start' }))"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>เพิ่มบัญชีรับเงิน</button>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/bank.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>เพิ่มบัญชีไม่สำเร็จ</b>
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    </section>
@endif

<div class="nvm-split">
    <div class="nvm-stack">
        {{-- ── ฟอร์มเพิ่มบัญชี ── ช่องของประเภทที่ไม่ได้เลือกถูก disable (ไม่ถูกส่งไปเลย) --}}
        <section class="nvm-card nvm-box" x-show="open" x-cloak x-ref="form" aria-labelledby="nvm-pmf-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">New account</small><h2 id="nvm-pmf-h">เพิ่มบัญชีรับเงิน</h2></div></div>
            <form method="POST" action="{{ route('user.wallet.payment-method.store') }}" class="nvm-form" @submit="busy = true">
                @csrf
                <div class="nvm-field">
                    <span class="nvm-label" id="nvm-pmt-lbl">ประเภทบัญชี <em>*</em></span>
                    <div class="nvm-opts" role="radiogroup" aria-labelledby="nvm-pmt-lbl">
                        @foreach($types as $key => [$img, $label, $hint])
                            <label class="nvm-opt">
                                <input type="radio" name="type" value="{{ $key }}" x-model="type" required>
                                <img src="{{ asset('images/nova/wallet/'.$img.'.webp') }}" alt="" width="44" height="44" loading="lazy">
                                <span><b>{{ $label }}</b><small>{{ $hint }}</small></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="nvm-field">
                    <label class="nvm-label" for="pm-name">ชื่อเรียกบัญชีนี้ <em>*</em></label>
                    <input id="pm-name" type="text" name="name" required maxlength="255" class="nvm-input @error('name') is-bad @enderror" value="{{ old('name') }}" placeholder="เช่น บัญชีหลัก, บัญชีเงินเดือน">
                </div>

                <template x-if="type === 'bank_transfer'">
                    <div class="nvm-field">
                        <label class="nvm-label" for="pm-bank">ธนาคาร <em>*</em></label>
                        <select id="pm-bank" name="bank_name" required class="nvm-input @error('bank_name') is-bad @enderror">
                            <option value="">— เลือกธนาคาร —</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank }}" @selected(old('bank_name') === $bank)>{{ $bank }}</option>
                            @endforeach
                        </select>
                    </div>
                </template>

                <template x-if="type === 'promptpay' || type === 'bank_transfer'">
                    <div class="nvm-stack">
                        <div class="nvm-field">
                            <label class="nvm-label" for="pm-acc-name">ชื่อเจ้าของบัญชี <em>*</em></label>
                            <input id="pm-acc-name" type="text" name="account_name" required maxlength="255" class="nvm-input @error('account_name') is-bad @enderror" value="{{ old('account_name', auth()->user()->name) }}">
                            <span class="nvm-help">ต้องตรงกับชื่อที่ยืนยันตัวตน ไม่งั้นทีมงานโอนให้ไม่ได้</span>
                        </div>
                        <div class="nvm-field">
                            <label class="nvm-label" for="pm-acc-no" x-text="type === 'promptpay' ? 'เบอร์มือถือ / เลขบัตรประชาชน (พร้อมเพย์) *' : 'เลขที่บัญชี *'">เลขที่บัญชี *</label>
                            <input id="pm-acc-no" type="text" name="account_number" required maxlength="50" inputmode="numeric" autocomplete="off" class="nvm-input nvm-num @error('account_number') is-bad @enderror" value="{{ old('account_number') }}" :placeholder="type === 'promptpay' ? '08x-xxx-xxxx' : 'xxx-x-xxxxx-x'">
                        </div>
                    </div>
                </template>

                <template x-if="type === 'paypal'">
                    <div class="nvm-field">
                        <label class="nvm-label" for="pm-paypal">อีเมล PayPal <em>*</em></label>
                        <input id="pm-paypal" type="email" name="paypal_email" required maxlength="255" class="nvm-input @error('paypal_email') is-bad @enderror" value="{{ old('paypal_email') }}" placeholder="you@example.com">
                    </div>
                </template>

                <label class="nvm-check">
                    <input type="checkbox" name="is_default" value="1" @checked(old('is_default'))>
                    <span>ตั้งเป็นบัญชีค่าเริ่มต้นตอนถอนเงิน</span>
                </label>

                <div class="nvm-filter__btns">
                    @if($paymentMethods->isNotEmpty())
                        <button type="button" class="nvm-btn nvm-btn--line" @click="open = false">ยกเลิก</button>
                    @endif
                    <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="busy ? 'กำลังบันทึก…' : 'บันทึกบัญชี'">บันทึกบัญชี</span></button>
                </div>
            </form>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-pml-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">My accounts</small><h2 id="nvm-pml-h">บัญชีของฉัน</h2></div></div>
            @if($paymentMethods->isEmpty())
                <div class="nvm-empty">
                    <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                    <b>ยังไม่มีบัญชีรับเงิน</b>
                    เพิ่มอย่างน้อย 1 บัญชีก่อนถอนเงิน
                </div>
            @else
                <div class="nvm-pms">
                    @foreach($paymentMethods as $method)
                        @php
                            [$pmIcon, $pmType] = match ($method->type) {
                                'promptpay' => ['qr', 'PromptPay'],
                                'bank_transfer' => ['bank', 'Bank account'],
                                'paypal' => ['send', 'PayPal'],
                                default => ['cash', 'Account'],
                            };
                        @endphp
                        <div class="nvm-pm {{ $method->is_active ? '' : 'is-off' }}">
                            <div class="nvm-pm__top">
                                <span class="nvm-pm__type nvm-latin"><svg class="nvm-ico"><use href="#nvm-i-{{ $pmIcon }}"/></svg>{{ $pmType }}</span>
                                @if($method->is_default)
                                    <span class="nvm-pill nvm-pill--gold">ค่าเริ่มต้น</span>
                                @elseif(! $method->is_active)
                                    <span class="nvm-pill">ปิดใช้งาน</span>
                                @endif
                            </div>
                            <div class="nvm-pm__name">{{ $method->name }}</div>
                            @if($method->type === 'bank_transfer')
                                <div class="nvm-pm__sub">{{ $method->bank_name }} · {{ $method->account_name }}</div>
                                <div class="nvm-pm__no nvm-num">{{ $maskNo($method->account_number) }}</div>
                            @elseif($method->type === 'promptpay')
                                <div class="nvm-pm__sub">พร้อมเพย์ · {{ $method->account_name }}</div>
                                <div class="nvm-pm__no nvm-num">{{ $maskNo($method->account_number) }}</div>
                            @elseif($method->type === 'paypal')
                                <div class="nvm-pm__sub">{{ $method->paypal_email }}</div>
                            @endif
                            <div class="nvm-pm__acts" x-data="{ ask: false, busy: false }">
                                @if(! $method->is_default && $method->is_active)
                                    <form method="POST" action="{{ route('user.wallet.payment-method.set-default', $method->id) }}" x-show="! ask" @submit="busy = true">
                                        @csrf
                                        <button type="submit" :disabled="busy">ตั้งเป็นค่าเริ่มต้น</button>
                                    </form>
                                @endif
                                {{-- ลบต้องยืนยันอีกครั้ง --}}
                                <button type="button" x-show="! ask" @click="ask = true" class="is-bad">ลบ</button>
                                <form method="POST" action="{{ route('user.wallet.payment-method.delete', $method->id) }}" x-show="ask" x-cloak @submit="busy = true">
                                    @csrf
                                    @method('DELETE')
                                    <span>ลบบัญชีนี้?</span>
                                    <button type="submit" class="is-bad" :disabled="busy" x-text="busy ? 'กำลังลบ…' : 'ยืนยันลบ'">ยืนยันลบ</button>
                                    <button type="button" @click="ask = false" :disabled="busy">ไม่ใช่</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-pmi-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Good to know</small><h2 id="nvm-pmi-h">ควรรู้</h2></div></div>
        <ol class="nvm-steps">
            <li>บัญชีรับเงินใช้สำหรับถอนเงินเท่านั้น มีได้หลายบัญชี</li>
            <li>ชื่อเจ้าของบัญชีต้องตรงกับชื่อที่ยืนยันตัวตน (KYC)</li>
            <li>บัญชีค่าเริ่มต้นจะถูกเลือกไว้ให้ตอนถอนเงิน</li>
            <li>ลบบัญชีได้ตลอด — คำขอถอนที่ยื่นไปแล้วยังโอนตามข้อมูลเดิม</li>
        </ol>
        @if($nvR('user.wallet.withdraw') && $activeCount > 0)
            <a class="nvm-btn nvm-btn--line nvm-btn--sm nvm-gap-t" href="{{ route('user.wallet.withdraw') }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ไปหน้าถอนเงิน</a>
        @endif
    </aside>
</div>

</div>
@endsection
