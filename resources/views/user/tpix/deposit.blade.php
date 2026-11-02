{{--
    ฝาก TPIX (ธีมโนวาเต็ม)
    - $canReceive มาจาก controller: แสดงที่อยู่เฉพาะเมื่อระบบถือ private key ของที่อยู่นั้นจริง
      ที่อยู่ที่สุ่มขึ้นเฉย ๆ ห้ามแสดง เพราะเหรียญที่โอนเข้ามาจะกู้คืนไม่ได้
--}}
@extends('layouts.user-nova')

@section('title', 'ฝาก TPIX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/tpix-tokens.css') }}?v={{ filemtime(public_path('theme-nova/member/tpix-tokens.css')) }}">@endpush

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-tpd-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.tpix.wallet') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กลับกระเป๋า</a>
            <div class="nvm-eyebrow nvm-latin">Deposit TPIX</div>
            <h1 id="nvm-tpd-h">ฝาก TPIX</h1>
            <p class="nvm-ph__lead">รับเหรียญเข้ากระเป๋า TPIX ของคุณ</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/deposit.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tpd-addr-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Balance</small><h2 id="nvm-tpd-addr-h">ยอดคงเหลือ</h2></div></div>
        <div class="nvm-tx-big nvm-num">{{ number_format((float) $wallet->balance, 8) }}<span>TPIX</span></div>

        @if($canReceive)
            <h3 class="nvm-label nvm-gap-t">ที่อยู่รับฝากของคุณ</h3>
            <div class="nvm-copy nvm-copy--box">
                <code class="nvm-tx-mono">{{ $depositAddress->address }}</code>
                <button type="button" x-data @click="nvmCopy(@js($depositAddress->address), 'คัดลอกที่อยู่รับฝากแล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
            </div>
            <ul class="nvm-tx-ul nvm-gap-t">
                <li>รับเฉพาะเหรียญ TPIX บนเครือข่าย TPIX เท่านั้น</li>
                <li>ฝากขั้นต่ำ <span class="nvm-num">{{ number_format((float) $tpixCurrency->min_deposit, 8) }}</span> TPIX</li>
                <li>ยอดเข้ากระเป๋าหลังเครือข่ายยืนยันครบ <span class="nvm-num">{{ (int) $tpixCurrency->confirmations_required }}</span> บล็อก</li>
            </ul>
        @else
            {{-- ที่อยู่ที่ระบบสร้างตอนนี้ไม่มี private key — ห้ามแสดงให้ใครโอนเหรียญจริงเข้ามา --}}
            <div class="nvm-status is-warn nvm-gap-t">
                <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                <span>ยังไม่เปิดรับฝากเหรียญจากภายนอก<small>ระบบยังไม่มีที่อยู่รับฝากบนเชนสำหรับบัญชีของคุณ กรุณาอย่าโอนเหรียญมาที่ที่อยู่ใดจนกว่าหน้านี้จะแสดงที่อยู่ให้</small></span>
            </div>
            @if(\Illuminate\Support\Facades\Route::has('tpix-pay.topup'))
                <a class="nvm-btn nvm-btn--line nvm-btn--block nvm-w-full nvm-gap-t" href="{{ route('tpix-pay.topup') }}"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ใช้เหรียญ TPIX เติมเงินเข้ากระเป๋าเงินบาท</a>
            @endif
        @endif
    </section>

    <section class="nvm-card nvm-box" aria-labelledby="nvm-tpd-tx-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Deposits</small><h2 id="nvm-tpd-tx-h">รายการฝากล่าสุด</h2></div>
            <a href="{{ route('user.tpix.transactions', ['type' => 'deposit']) }}">ทั้งหมด<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
        </div>
        @if($recentDeposits->isNotEmpty())
            <div class="nvm-txs nvm-tks">
                @foreach($recentDeposits as $transaction)
                    @include('user.tpix.partials.tx-row')
                @endforeach
            </div>
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีรายการฝาก</b>
            </div>
        @endif
    </section>
</div>

</div>
@endsection
