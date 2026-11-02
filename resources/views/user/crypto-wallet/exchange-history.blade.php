{{--
    ประวัติการแลกเปลี่ยน THB ↔ Crypto (ธีมโนวาเต็ม)
    CryptoExchangeController::history → $exchanges (paginate 20 · ของสมาชิกคนนี้) · $currencies (ตัวเลือกตัวกรอง)
    ตัวกรอง type / currency / status — ค่าว่าง = ทั้งหมด (controller ใช้ filled() แล้ว)
--}}
@extends('layouts.user-nova')

@section('title', 'ประวัติการแลกเปลี่ยน')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@php
    $filtered = request()->filled('type') || request()->filled('currency') || request()->filled('status');
    $dp = fn ($type) => $type === 'crypto' ? 8 : 2;
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-cwxh-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.exchange') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>หน้าแลกเปลี่ยน</a>
            <div class="nvm-eyebrow nvm-latin">Exchange history</div>
            <h1 id="nvm-cwxh-h">ประวัติการแลกเปลี่ยน</h1>
            <p class="nvm-ph__lead">รายการซื้อ-ขายเหรียญด้วยเงินบาททั้งหมดของคุณ</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>{{ number_format($exchanges->total()) }} รายการ</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/transfer.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash')

<section class="nvm-card nvm-box" aria-label="ตัวกรองการแลกเปลี่ยน">
    <form method="GET" action="{{ route('user.crypto-wallet.exchange.history') }}" class="nvm-filter">
        <div class="nvm-field">
            <label class="nvm-label" for="cw-xf-type">ประเภท</label>
            <select id="cw-xf-type" name="type" class="nvm-input">
                <option value="">ทั้งหมด</option>
                <option value="buy" @selected(request('type') === 'buy')>ซื้อเหรียญ</option>
                <option value="sell" @selected(request('type') === 'sell')>ขายเหรียญ</option>
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="cw-xf-cur">สกุลเงิน</label>
            <select id="cw-xf-cur" name="currency" class="nvm-input">
                <option value="">ทั้งหมด</option>
                @foreach($currencies as $curr)
                    <option value="{{ $curr->code }}" @selected(request('currency') === $curr->code)>{{ $curr->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="cw-xf-st">สถานะ</label>
            <select id="cw-xf-st" name="status" class="nvm-input">
                <option value="">ทั้งหมด</option>
                <option value="completed" @selected(request('status') === 'completed')>สำเร็จ</option>
                <option value="pending" @selected(request('status') === 'pending')>รอดำเนินการ</option>
                <option value="failed" @selected(request('status') === 'failed')>ล้มเหลว</option>
            </select>
        </div>
        <div class="nvm-filter__btns">
            @if($filtered)
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.crypto-wallet.exchange.history') }}">ล้าง</a>
            @endif
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>กรอง</button>
        </div>
    </form>
</section>

@forelse($exchanges as $exchange)
    @php
        $isBuy = $exchange->type === 'buy';
        $coin = $isBuy ? $exchange->to_currency_code : $exchange->from_currency_code;
    @endphp
    <section class="nvm-card nvm-cw-item" aria-labelledby="nvm-cwxh-{{ $exchange->id }}">
        <div class="nvm-cw-item__head">
            <div class="nvm-cw-cell">
                @include('user.crypto-wallet.partials.coin', ['code' => $coin])
                <div>
                    <b id="nvm-cwxh-{{ $exchange->id }}">{{ $isBuy ? 'ซื้อ' : 'ขาย' }} {{ $coin }}</b>
                    <small class="nvm-num">{{ $exchange->created_at?->format('d/m/Y H:i') }} · {{ $exchange->created_at?->locale('th')->diffForHumans() }}</small>
                </div>
            </div>
            @include('user.crypto-wallet.partials.status', ['status' => $exchange->status])
        </div>

        <div class="nvm-cw-flow">
            <div><small>จาก</small><b class="nvm-num">{{ number_format((float) $exchange->from_amount, $dp($exchange->from_currency_type)) }}</b><small>{{ $exchange->from_currency_code }}</small></div>
            <svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>
            <div><small>ไปยัง</small><b class="nvm-num is-in">{{ number_format((float) $exchange->to_amount, $dp($exchange->to_currency_type)) }}</b><small>{{ $exchange->to_currency_code }}</small></div>
        </div>

        <dl class="nvm-kv">
            <dt>อัตราแลกเปลี่ยน</dt><dd class="nvm-num">1 {{ $coin }} = ฿{{ number_format((float) $exchange->exchange_rate, 2) }}</dd>
            <dt>ค่าธรรมเนียม</dt><dd class="nvm-num nvm-cw-down">{{ number_format((float) $exchange->fee_amount, 2) }} {{ $exchange->fee_currency }} ({{ number_format((float) $exchange->fee_percentage, 2) }}%)</dd>
            <dt>เลขที่รายการ</dt><dd class="nvm-cw-mono">{{ $exchange->exchange_id }}</dd>
            <dt class="nvm-kv__total">{{ $isBuy ? 'จำนวนที่ได้รับ' : 'ได้รับหลังหักค่าธรรมเนียม' }}</dt><dd class="nvm-kv__total nvm-num">{{ number_format((float) $exchange->final_to_amount, $dp($exchange->to_currency_type)) }} {{ $exchange->to_currency_code }}</dd>
        </dl>

        @if($exchange->status === 'failed' && $exchange->error_message)
            <div class="nvm-status is-bad">
                <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>
                <span>ทำรายการไม่สำเร็จ<small>{{ $exchange->error_message }}</small></span>
            </div>
        @endif
    </section>
@empty
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>{{ $filtered ? 'ไม่พบรายการตามตัวกรอง' : 'ยังไม่มีประวัติการแลกเปลี่ยน' }}</b>
            {{ $filtered ? 'ลองเปลี่ยนหรือล้างตัวกรอง' : 'ซื้อหรือขายเหรียญครั้งแรกได้ที่หน้าแลกเปลี่ยน' }}
            <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.crypto-wallet.exchange') }}"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>เริ่มแลกเปลี่ยน</a></div>
        </div>
    </section>
@endforelse

@if($exchanges->hasPages())
    <div class="nvm-pager">{{ $exchanges->withQueryString()->links() }}</div>
@endif

</div>
@endsection
