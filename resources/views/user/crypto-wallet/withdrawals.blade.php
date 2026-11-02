{{--
    ประวัติคำขอถอนเหรียญ (ธีมโนวาเต็ม)
    CryptoWalletController::withdrawals → $withdrawals (paginate 20 · ของสมาชิกคนนี้เท่านั้น)
    ยกเลิก: DELETE user.crypto-wallet.withdrawal.cancel (controller กรอง user_id + ต้องยังรอดำเนินการ) — ยืนยันในโมดัลก่อน
--}}
@extends('layouts.user-nova')

@section('title', 'ประวัติการถอนเหรียญ')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@section('content')

<div class="nvm-stack" x-data="{ cancel: null }">

<section class="nvm-ph" aria-labelledby="nvm-cwwl-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าคริปโต</a>
            <div class="nvm-eyebrow nvm-latin">Withdrawal requests</div>
            <h1 id="nvm-cwwl-h">ประวัติการถอนเหรียญ</h1>
            <p class="nvm-ph__lead">คำขอถอนเหรียญทั้งหมด ยกเลิกได้ระหว่างที่ยังรอดำเนินการ</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>{{ number_format($withdrawals->total()) }} คำขอ</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash')

@forelse($withdrawals as $withdrawal)
    @php $wCode = $withdrawal->currency?->code ?? ''; @endphp
    <section class="nvm-card nvm-cw-item" aria-labelledby="nvm-cwwl-{{ $withdrawal->id }}">
        <div class="nvm-cw-item__head">
            <div class="nvm-cw-cell">
                @include('user.crypto-wallet.partials.coin', ['code' => $wCode])
                <div>
                    <b id="nvm-cwwl-{{ $withdrawal->id }}" class="nvm-num">{{ number_format((float) $withdrawal->amount, 8) }} {{ $wCode }}</b>
                    <small class="nvm-num">เลขที่คำขอ {{ $withdrawal->request_id }} · {{ $withdrawal->created_at?->format('d/m/Y H:i') }}</small>
                </div>
            </div>
            @include('user.crypto-wallet.partials.status', ['status' => $withdrawal->status])
        </div>

        <dl class="nvm-kv">
            <dt>ที่อยู่ปลายทาง</dt><dd class="nvm-cw-mono">{{ $withdrawal->to_address }}</dd>
            <dt>เครือข่าย</dt><dd>{{ strtoupper($withdrawal->network) }}</dd>
            <dt>จำนวนถอน</dt><dd class="nvm-num">{{ number_format((float) $withdrawal->amount, 8) }}</dd>
            <dt>ค่าธรรมเนียมเครือข่าย</dt><dd class="nvm-num nvm-cw-down">-{{ number_format((float) $withdrawal->network_fee, 8) }}</dd>
            @if($withdrawal->platform_fee > 0)
                <dt>ค่าธรรมเนียมแพลตฟอร์ม</dt><dd class="nvm-num nvm-cw-down">-{{ number_format((float) $withdrawal->platform_fee, 8) }}</dd>
            @endif
            <dt class="nvm-kv__total">จำนวนที่ได้รับ</dt><dd class="nvm-kv__total nvm-num">{{ number_format((float) $withdrawal->net_amount, 8) }} {{ $wCode }}</dd>
        </dl>

        @if($withdrawal->status === 'rejected' && $withdrawal->rejection_reason)
            <div class="nvm-status is-bad">
                <svg class="nvm-ico"><use href="#nvm-i-x"/></svg>
                <span>เหตุผลที่ถูกปฏิเสธ<small>{{ $withdrawal->rejection_reason }}</small></span>
            </div>
        @endif

        @php $explorerUrl = $withdrawal->cryptoTransaction?->tx_hash ? $withdrawal->cryptoTransaction->getExplorerUrl() : ''; @endphp
        @if($withdrawal->isPending() || $explorerUrl)
            <div class="nvm-cw-item__acts">
                @if($withdrawal->isPending())
                    <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="cancel = @js(route('user.crypto-wallet.withdrawal.cancel', $withdrawal->id))"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยกเลิกคำขอ</button>
                @endif
                @if($explorerUrl)
                    <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ $explorerUrl }}" target="_blank" rel="noopener noreferrer"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ดูใน Block Explorer</a>
                @endif
            </div>
        @endif
    </section>
@empty
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีประวัติการถอน</b>
            เมื่อส่งคำขอถอนเหรียญ สถานะจะแสดงที่นี่
            <div><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.crypto-wallet.withdraw') }}"><svg class="nvm-ico"><use href="#nvm-i-up"/></svg>ถอนเหรียญ</a></div>
        </div>
    </section>
@endforelse

@if($withdrawals->hasPages())
    <div class="nvm-pager">{{ $withdrawals->links() }}</div>
@endif

{{-- ยืนยันก่อนยกเลิกคำขอ --}}
<div class="nvm-modal" x-show="cancel" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-cwwl-c-h" @click.self="cancel = null" @keydown.escape.window="cancel = null">
    <div class="nvm-card nvm-modal__box">
        <h2 id="nvm-cwwl-c-h">ยกเลิกคำขอถอน?</h2>
        <p>ยกเลิกแล้วต้องส่งคำขอใหม่หากต้องการถอนอีกครั้ง</p>
        <form method="POST" :action="cancel || ''" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @method('DELETE')
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="cancel = null">ไม่ยกเลิก</button>
                <button type="submit" class="nvm-btn nvm-btn--bad" :disabled="busy"><span x-text="busy ? 'กำลังยกเลิก…' : 'ยืนยันยกเลิก'">ยืนยันยกเลิก</span></button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
