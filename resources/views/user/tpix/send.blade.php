{{--
    ส่ง TPIX ให้สมาชิกในระบบ (ธีมโนวาเต็ม)
    - transfer_token ใช้ครั้งเดียวต่อฟอร์ม (processSend กันกดซ้ำ/ย้อนกลับแล้วส่งใหม่)
    - ปุ่มส่งปิดเองเมื่อกระเป๋าถูกระงับหรือยอดไม่ถึง 0.0001 TPIX
--}}
@extends('layouts.user-nova')

@section('title', 'ส่ง TPIX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/tpix-tokens.css') }}?v={{ filemtime(public_path('theme-nova/member/tpix-tokens.css')) }}">@endpush

@php
    // ตัดเหลือ 8 ตำแหน่งแบบเดียวกับที่ processSend รับ — ปัดขึ้นจะส่งเกินยอดจริง
    $balance = (string) ($wallet?->balance ?? '0');
    $maxAmount = rtrim(rtrim(bcadd($balance, '0', 8), '0'), '.');
    $walletActive = $wallet?->isActive() ?? false;
    $canSend = $walletActive && $tpixCurrency->is_active && bccomp($balance, '0.0001', 18) >= 0;
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-tps-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.tpix.wallet') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กลับกระเป๋า</a>
            <div class="nvm-eyebrow nvm-latin">Send TPIX</div>
            <h1 id="nvm-tps-h">ส่ง TPIX</h1>
            <p class="nvm-ph__lead">โอนให้สมาชิกในระบบ ไม่มีค่าธรรมเนียม ยอดเข้าทันที</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/transfer.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tps-f-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Transfer</small><h2 id="nvm-tps-f-h">ฟอร์มส่ง TPIX</h2></div></div>

        @if(! $wallet)
            <div class="nvm-status is-bad">
                <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                <span>ยังไม่มีกระเป๋า TPIX<small><a class="nvm-link" href="{{ route('user.tpix.wallet') }}">เปิดหน้ากระเป๋า</a> เพื่อสร้างก่อน</small></span>
            </div>
        @elseif(! $walletActive)
            <div class="nvm-status is-bad">
                <svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>
                <span>กระเป๋า TPIX ของคุณถูกระงับการใช้งาน ยังส่งไม่ได้</span>
            </div>
        @elseif(! $canSend)
            <div class="nvm-status is-bad">
                <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                <span>ยอดคงเหลือไม่พอให้ส่ง ต้องมีอย่างน้อย 0.0001 TPIX</span>
            </div>
        @endif

        <form class="nvm-form {{ $canSend ? '' : 'nvm-gap-t' }}" action="{{ route('user.tpix.send.process') }}" method="POST"
              x-data="{ amount: @js(old('amount', '')), maxAmount: @js($maxAmount), canSend: @js($canSend), submitting: false }"
              x-on:submit="submitting = true"
              x-on:pageshow.window="submitting = false">
            @csrf
            <input type="hidden" name="transfer_token" value="{{ $transferToken }}">

            <div class="nvm-field">
                <label class="nvm-label" for="tpix-recipient">ผู้รับ <em>*</em></label>
                <input id="tpix-recipient" class="nvm-input @error('recipient') is-bad @enderror" type="text" name="recipient" value="{{ old('recipient') }}" required maxlength="255"
                       autocomplete="off" autocapitalize="off" spellcheck="false" inputmode="email" placeholder="อีเมลสมาชิก หรือที่อยู่กระเป๋า TPIX">
                @error('recipient')
                    <span class="nvm-err">{{ $message }}</span>
                @else
                    <span class="nvm-help">ส่งได้เฉพาะสมาชิกในระบบ ถ้าจะส่งออกไปกระเป๋าภายนอกให้ใช้หน้าถอน</span>
                @enderror
            </div>

            <div class="nvm-field">
                <div class="nvm-tx-bar">
                    <label class="nvm-label" for="tpix-amount">จำนวนที่ต้องการส่ง <em>*</em></label>
                    @if($canSend)
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" x-on:click="amount = maxAmount">ส่งทั้งหมด</button>
                    @endif
                </div>
                <input id="tpix-amount" class="nvm-input nvm-num @error('amount') is-bad @enderror" type="number" name="amount" x-model="amount" required
                       step="0.00000001" min="0.0001" @if($canSend) max="{{ $maxAmount }}" @endif
                       inputmode="decimal" placeholder="0.00000000">
                @error('amount')
                    <span class="nvm-err">{{ $message }}</span>
                @enderror
            </div>

            <label class="nvm-check">
                <input type="checkbox" required>
                <span>ฉันตรวจสอบผู้รับและจำนวนแล้ว และเข้าใจว่าส่งแล้วเรียกคืนไม่ได้</span>
            </label>

            <button type="submit" class="nvm-btn nvm-btn--gold nvm-w-full" @disabled(! $canSend)
                    x-bind:disabled="submitting || ! canSend">
                <svg class="nvm-ico"><use href="#nvm-i-send"/></svg>
                <span x-text="submitting ? 'กำลังส่ง...' : 'ส่ง TPIX'">ส่ง TPIX</span>
            </button>
        </form>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-tps-b-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Balance</small><h2 id="nvm-tps-b-h">ยอดคงเหลือ</h2></div></div>
        <div class="nvm-tx-big nvm-num">{{ number_format((float) $balance, 8) }}<span>TPIX</span></div>
        <ol class="nvm-steps nvm-gap-t">
            <li>กรอกอีเมลสมาชิก หรือที่อยู่กระเป๋า TPIX ของผู้รับ</li>
            <li>ใส่จำนวน แล้วตรวจสอบให้ถูกต้องก่อนกดส่ง</li>
            <li>ยอดเข้ากระเป๋าผู้รับทันที ดูรายการได้ที่หน้าประวัติ</li>
        </ol>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ส่งแล้วเรียกคืนไม่ได้ ระบบอาจขอยืนยันตัวตน 2 ชั้นก่อนส่ง</p>
    </aside>
</div>

</div>
@endsection
