{{--
    รายละเอียดธุรกรรมคริปโต (ธีมโนวาเต็ม)
    CryptoWalletController::transactionDetail → $transaction จาก $user->cryptoTransactions()->findOrFail() — ของคนอื่นได้ 404
    เดิม: strtoupper($transaction->currency) กับ relation (เป็นโมเดล ไม่ใช่สตริง) → 500 · อ่านคอลัมน์ transaction_hash
    ที่ไม่มีอยู่ (ชื่อจริง tx_hash) และลิงก์ etherscan.io ตายตัวทุกเหรียญ — ตอนนี้ใช้ getExplorerUrl() ตามเครือข่ายของเหรียญ
--}}
@extends('layouts.user-nova')

@section('title', 'รายละเอียดธุรกรรมคริปโต')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@php
    $code = $transaction->currency?->code ?? '';
    $state = match (true) {
        in_array($transaction->status, ['completed', 'confirmed'], true) => ['', 'check', 'ธุรกรรมสำเร็จ'],
        in_array($transaction->status, ['failed', 'cancelled'], true) => ['is-bad', 'x', $transaction->status === 'failed' ? 'ธุรกรรมล้มเหลว' : 'ธุรกรรมถูกยกเลิก'],
        default => ['is-warn', 'clock', 'กำลังดำเนินการ'],
    };
    $explorerUrl = $transaction->getExplorerUrl();
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-cwtd-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.transactions') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ประวัติธุรกรรม</a>
            <div class="nvm-eyebrow nvm-latin">Transaction detail</div>
            <h1 id="nvm-cwtd-h">{{ $transaction->typeLabel() }} {{ $code }}</h1>
            <div class="nvm-bal">
                <small>จำนวน</small>
                <b class="nvm-foil nvm-num">{{ $transaction->is_incoming ? '+' : '-' }}{{ number_format((float) $transaction->amount, 8) }}</b><em>{{ $code }}</em>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-cwtd-info-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Details</small><h2 id="nvm-cwtd-info-h">ข้อมูลธุรกรรม</h2></div></div>

        <div class="nvm-status {{ $state[0] }}">
            <svg class="nvm-ico"><use href="#nvm-i-{{ $state[1] }}"/></svg>
            <span>{{ $state[2] }}<small class="nvm-num">{{ $transaction->created_at?->format('d/m/Y H:i:s') }}</small></span>
        </div>

        <dl class="nvm-kv nvm-gap-t">
            <dt>รหัสธุรกรรม</dt><dd class="nvm-cw-mono">{{ $transaction->transaction_id }}</dd>
            <dt>ประเภท</dt><dd>{{ $transaction->typeLabel() }}</dd>
            <dt>สกุลเงิน</dt><dd>{{ $code }} · {{ strtoupper((string) $transaction->network) }}</dd>
            <dt>จำนวน</dt><dd class="nvm-num">{{ number_format((float) $transaction->amount, 8) }} {{ $code }}</dd>
            @if((float) $transaction->fee > 0)
                <dt>ค่าธรรมเนียมเครือข่าย</dt><dd class="nvm-num nvm-cw-down">{{ number_format((float) $transaction->fee, 8) }} {{ $code }}</dd>
            @endif
            @if($transaction->amount_thb)
                <dt>มูลค่า ณ เวลาทำรายการ</dt><dd class="nvm-num">≈ ฿{{ number_format((float) $transaction->amount_thb, 2) }}</dd>
            @endif
            @if($transaction->from_address)
                <dt>จาก</dt><dd class="nvm-cw-mono">{{ $transaction->from_address }}</dd>
            @endif
            @if($transaction->to_address)
                <dt>ถึง</dt><dd class="nvm-cw-mono">{{ $transaction->to_address }}</dd>
            @endif
            <dt>สถานะ</dt><dd>@include('user.crypto-wallet.partials.status', ['status' => $transaction->status])</dd>
            @if((int) $transaction->confirmations_required > 0)
                <dt>การยืนยัน</dt><dd class="nvm-num">{{ (int) $transaction->confirmations }} / {{ (int) $transaction->confirmations_required }}</dd>
            @endif
            @if($transaction->tx_hash)
                <dt>Tx hash</dt><dd class="nvm-cw-mono">{{ $transaction->tx_hash }}</dd>
            @endif
        </dl>

        @if((int) $transaction->confirmations_required > 0 && $transaction->isPending())
            <div class="nvm-meter">
                <div class="nvm-meter__row"><span>ความคืบหน้าการยืนยัน</span><b class="nvm-num">{{ $transaction->confirmation_progress }}%</b></div>
                <div class="nvm-cw-alloc"><div class="nvm-meter__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $transaction->confirmation_progress }}" aria-label="ความคืบหน้าการยืนยัน"><i style="--w: {{ $transaction->confirmation_progress }}%"></i></div></div>
            </div>
        @endif

        @if($transaction->description)
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>{{ $transaction->description }}</p>
        @endif
    </section>

    <aside class="nvm-stack">
        @if($explorerUrl)
            <section class="nvm-card nvm-box" aria-labelledby="nvm-cwtd-ex-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Blockchain</small><h2 id="nvm-cwtd-ex-h">ตรวจบนบล็อกเชน</h2></div></div>
                <a class="nvm-btn nvm-btn--gold nvm-btn--block" href="{{ $explorerUrl }}" target="_blank" rel="noopener noreferrer"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ดูบน Block Explorer</a>
            </section>
        @endif
        <div class="nvm-filter__btns">
            <a class="nvm-btn nvm-btn--line" href="{{ route('user.crypto-wallet.transactions') }}">กลับไปรายการ</a>
            @if($transaction->isPending())
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.crypto-wallet.transaction.detail', $transaction->id) }}"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>รีเฟรชสถานะ</a>
            @endif
        </div>
    </aside>
</div>

</div>
@endsection
