{{--
    ที่อยู่สำหรับฝากเหรียญ 1 สกุล (ธีมโนวาเต็ม)
    CryptoWalletController::depositCurrency → $wallet · $currency · $depositAddress (ของสมาชิกคนนี้) · $canReceive
    $canReceive = false เมื่อที่อยู่เป็นเลขที่ระบบสุ่มขึ้นเอง (ไม่มี private key / ไม่ได้นำเข้า) — โอนเข้าแล้วกู้คืนไม่ได้
    จึงไม่แสดงที่อยู่/QR และบอกให้รอ แทนการโชว์ที่อยู่ที่รับเหรียญจริงไม่ได้ (แนวเดียวกับหน้าฝาก TPIX)
--}}
@extends('layouts.user-nova')

@section('title', 'ฝาก '.$currency->code)

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@php
    $address = $canReceive ? (string) $depositAddress?->address : '';
    // qr_code ใน DB เป็น SVG ดิบหรือ data URI — แสดงได้เฉพาะ data:image เป็น <img> (ห้าม {!! !!} ข้อมูลจาก DB)
    $qr = $canReceive ? (string) ($depositAddress?->qr_code ?? '') : '';
    $qrSrc = str_starts_with($qr, 'data:image/') ? $qr : null;
    $eta = match ($currency->network) {
        'bitcoin' => '~60 นาที',
        'ethereum' => '~15-30 นาที',
        'bsc' => '~3-5 นาที',
        'polygon' => '~5-10 นาที',
        default => 'ขึ้นอยู่กับเครือข่าย',
    };
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-cwdc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.deposit') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>เลือกสกุลเงินอื่น</a>
            <div class="nvm-eyebrow nvm-latin">Deposit {{ $currency->code }}</div>
            <h1 id="nvm-cwdc-h">ฝาก {{ $currency->code }}</h1>
            <p class="nvm-ph__lead">{{ $currency->name }} · เครือข่าย {{ strtoupper($currency->network) }}</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/qr.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash')

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-cwdc-addr-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Deposit address</small><h2 id="nvm-cwdc-addr-h">ที่อยู่สำหรับฝาก</h2></div></div>

        @if($canReceive && $address !== '')
            <div class="nvm-qr">
                <div class="nvm-qr__box">
                    @if($qrSrc)
                        <img src="{{ $qrSrc }}" alt="QR Code ที่อยู่ฝาก {{ $currency->code }}" width="232" height="232">
                    @else
                        <div class="nvm-qr__miss">
                            <svg class="nvm-ico"><use href="#nvm-i-qr"/></svg>
                            <span>ยังไม่มี QR Code</span>
                            <small>คัดลอกที่อยู่ด้านล่างไปวางในกระเป๋าที่จะโอนแทน</small>
                        </div>
                    @endif
                    <span class="nvm-qr__tag">{{ $currency->code }} · {{ strtoupper($currency->network) }}</span>
                </div>
            </div>
            <div class="nvm-field nvm-gap-t">
                <span class="nvm-label">ที่อยู่กระเป๋า (Wallet address)</span>
                <div class="nvm-copy nvm-copy--box">
                    <code>{{ $address }}</code>
                    <button type="button" x-data @click="nvmCopy(@js($address), 'คัดลอกที่อยู่แล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
                </div>
            </div>
        @else
            <div class="nvm-status is-warn">
                <svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>
                <span>ยังเปิดรับฝาก {{ $currency->code }} ผ่านที่อยู่ส่วนตัวไม่ได้<small>ระบบยังไม่ได้สร้างที่อยู่บนบล็อกเชนที่รับเหรียญได้จริงให้บัญชีของคุณ — อย่าโอนเหรียญเข้ามาจนกว่าที่อยู่จะแสดงในหน้านี้ หากต้องการฝากด่วนติดต่อฝ่ายสนับสนุน</small></span>
            </div>
        @endif

        <dl class="nvm-kv nvm-gap-t">
            <dt>เครือข่าย</dt><dd>{{ strtoupper($currency->network) }}</dd>
            <dt>มาตรฐานโทเคน</dt><dd>{{ $currency->token_standard }}</dd>
            @if($currency->contract_address)
                <dt>Contract</dt><dd class="nvm-cw-mono">{{ $currency->contract_address }}</dd>
            @endif
        </dl>
    </section>

    <div class="nvm-stack">
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-cwdc-info-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Details</small><h2 id="nvm-cwdc-info-h">ข้อมูลการฝาก</h2></div></div>
            <dl class="nvm-kv">
                <dt>ฝากขั้นต่ำ</dt><dd class="nvm-num">{{ number_format((float) $currency->min_deposit, 8) }} {{ $currency->code }}</dd>
                <dt>การยืนยันที่ต้องการ</dt><dd class="nvm-num">{{ number_format((int) $currency->confirmations_required) }}</dd>
                <dt>เวลาโดยประมาณ</dt><dd>{{ $eta }}</dd>
                @if($depositAddress && $depositAddress->total_deposits > 0)
                    <dt>ฝากแล้ว</dt><dd class="nvm-num">{{ number_format((int) $depositAddress->total_deposits) }} ครั้ง</dd>
                    <dt class="nvm-kv__total">ยอดรวมที่ได้รับ</dt><dd class="nvm-kv__total nvm-num">{{ number_format((float) $depositAddress->total_amount_received, 8) }}</dd>
                @endif
            </dl>
        </aside>
        <aside class="nvm-card nvm-alert nvm-alert--bad" aria-labelledby="nvm-cwdc-warn-h">
            <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
            <div class="nvm-alert__txt">
                <b id="nvm-cwdc-warn-h">คำเตือนสำคัญ</b>
                <ul class="nvm-cw-list">
                    <li>ส่งเฉพาะ {{ $currency->code }} บนเครือข่าย {{ strtoupper($currency->network) }} เท่านั้น</li>
                    <li>ส่งเหรียญอื่นหรือใช้เครือข่ายผิด เหรียญจะสูญหายถาวร</li>
                    <li>ต้องส่งตั้งแต่ {{ number_format((float) $currency->min_deposit, 8) }} {{ $currency->code }} ขึ้นไป</li>
                    @if($currency->explorer_url)
                        <li>ตรวจสถานะได้ที่ <a class="nvm-link" href="{{ $currency->explorer_url }}" target="_blank" rel="noopener noreferrer">Block Explorer</a></li>
                    @endif
                </ul>
            </div>
        </aside>
    </div>
</div>

</div>
@endsection
