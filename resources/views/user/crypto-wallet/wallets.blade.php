{{--
    จัดการกระเป๋าคริปโต (ธีมโนวาเต็ม)
    CryptoWalletController::wallets → $wallets = กระเป๋าของสมาชิกคนนี้เท่านั้น (พร้อม cryptoAddresses.currency)
    - ตั้งเป็นกระเป๋าหลัก: POST user.crypto-wallet.wallet.set-default (SetDefaultWalletRequest ตรวจความเป็นเจ้าของ)
    - ลบ: DELETE user.crypto-wallet.wallet.delete — ต้องกดยืนยันในโมดัลก่อน · ฝั่งเซิร์ฟเวอร์ไม่ให้ลบกระเป๋าที่ยังมียอด
    - ที่อยู่ในกระเป๋าเป็นของสมาชิกเอง แสดงเต็มได้
--}}
@extends('layouts.user-nova')

@section('title', 'จัดการกระเป๋าคริปโต')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@section('content')

<div class="nvm-stack" x-data="{ create: @js($errors->has('pin') || $errors->has('pin_confirmation')), del: null }">

<section class="nvm-ph" aria-labelledby="nvm-cww-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าคริปโต</a>
            <div class="nvm-eyebrow nvm-latin">My wallets</div>
            <h1 id="nvm-cww-h">จัดการกระเป๋าคริปโต</h1>
            <p class="nvm-ph__lead">กระเป๋าทั้งหมดของคุณ เลือกกระเป๋าหลักที่ใช้ฝาก ถอน และแลกเปลี่ยน</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>{{ number_format($wallets->count()) }} กระเป๋า</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/cat/wallet.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash', ['errorTitle' => 'ทำรายการไม่สำเร็จ'])

<div class="nvm-cw-grid">
    <button type="button" class="nvm-card nvm-alert nvm-alert--ok nvm-cw-pick" @click="create = true">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg></span>
        <span class="nvm-alert__txt"><b>สร้างกระเป๋าใหม่</b><small>Custodial — ระบบดูแลกุญแจให้ ป้องกันด้วย PIN</small></span>
    </button>
    <div class="nvm-card nvm-alert nvm-alert--info nvm-cw-pick is-off" aria-disabled="true">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg></span>
        <span class="nvm-alert__txt"><b>เชื่อมต่อกระเป๋าภายนอก</b><small>MetaMask, WalletConnect</small></span>
        <span class="nvm-chip">เร็วๆ นี้</span>
    </div>
</div>

@forelse($wallets as $wallet)
    <section class="nvm-card nvm-cw-wallet {{ $wallet->is_default ? 'is-default' : '' }}" aria-labelledby="nvm-cww-{{ $wallet->id }}">
        <div class="nvm-cw-wallet__head">
            <div class="nvm-cw-wallet__name">
                <span class="nvm-cw-wallet__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $wallet->wallet_type === 'custodial' ? 'lock' : 'wallet' }}"/></svg></span>
                <div>
                    <b id="nvm-cww-{{ $wallet->id }}">{{ $wallet->name }}</b>
                    <small>{{ $wallet->wallet_type === 'custodial' ? 'ระบบดูแลกุญแจ (Custodial)' : 'กระเป๋าภายนอก' }}</small>
                </div>
            </div>
            <div class="nvm-cw-wallet__chips">
                @if($wallet->is_default)<span class="nvm-chip nvm-chip--gold">กระเป๋าหลัก</span>@endif
                @if($wallet->status === 'active')
                    <span class="nvm-chip nvm-chip--ok">ใช้งานได้</span>
                @elseif($wallet->status === 'locked')
                    <span class="nvm-chip nvm-chip--bad">ถูกล็อก</span>
                @else
                    <span class="nvm-chip nvm-chip--bad">ถูกระงับ</span>
                @endif
            </div>
        </div>

        <div>
            <div class="nvm-sec-h"><div><small class="nvm-latin">Addresses</small><h2>ที่อยู่ในกระเป๋า</h2></div></div>
            @if($wallet->cryptoAddresses->isNotEmpty())
                <ul class="nvm-txs">
                    @foreach($wallet->cryptoAddresses->take(5) as $address)
                        @php $addrCode = $address->currency?->code ?? '-'; @endphp
                        <li class="nvm-tx">
                            @include('user.crypto-wallet.partials.coin', ['code' => $addrCode])
                            <div class="nvm-tx__t">
                                <b>{{ $addrCode }}</b>
                                <small class="nvm-cw-mono">{{ $address->address }}</small>
                            </div>
                            <div class="nvm-tx__r">
                                <span class="nvm-tx__amt nvm-num">{{ number_format((float) $address->balance, 8) }}</span>
                                @if($address->balance > 0)<time class="nvm-num">≈ ฿{{ number_format((float) $address->balance_in_thb, 2) }}</time>@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="nvm-empty">
                    <b>ยังไม่มีที่อยู่ในกระเป๋านี้</b>
                    ที่อยู่จะถูกสร้างเมื่อคุณเลือกฝากเหรียญครั้งแรก
                </div>
            @endif
        </div>

        <div class="nvm-cw-stats">
            <div><b class="nvm-num">{{ number_format($wallet->cryptoAddresses->count()) }}</b><small>ที่อยู่</small></div>
            <div><b class="nvm-num">{{ number_format((int) $wallet->total_transactions) }}</b><small>ธุรกรรม</small></div>
            <div><b>{{ $wallet->last_activity_at ? $wallet->last_activity_at->locale('th')->diffForHumans() : '-' }}</b><small>ใช้งานล่าสุด</small></div>
        </div>

        <div class="nvm-cw-wallet__acts">
            @if(! $wallet->is_default && $wallet->status === 'active')
                <form method="POST" action="{{ route('user.crypto-wallet.wallet.set-default', $wallet->id) }}">
                    @csrf
                    <button type="submit" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ตั้งเป็นกระเป๋าหลัก</button>
                </form>
            @endif
            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="del = @js(['id' => $wallet->id, 'name' => $wallet->name, 'url' => route('user.crypto-wallet.wallet.delete', $wallet->id)])"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ลบกระเป๋า</button>
        </div>
    </section>
@empty
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/cat/wallet.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>คุณยังไม่มีกระเป๋าคริปโต</b>
            สร้างกระเป๋าใหม่เพื่อเริ่มฝาก ถอน และแลกเปลี่ยนเหรียญ
            <div><button type="button" class="nvm-btn nvm-btn--gold" @click="create = true"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างกระเป๋าใหม่</button></div>
        </div>
    </section>
@endforelse

@include('user.crypto-wallet.partials.create-wallet-modal')

{{-- ยืนยันก่อนลบ (ลบแล้วกู้คืนเองไม่ได้) --}}
<div class="nvm-modal" x-show="del" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-cw-del-h" @click.self="del = null" @keydown.escape.window="del = null">
    <div class="nvm-card nvm-modal__box">
        <h2 id="nvm-cw-del-h">ลบกระเป๋านี้?</h2>
        <p>กระเป๋า “<span x-text="del ? del.name : ''"></span>” จะถูกลบ ต้องไม่มียอดคงเหลือเหลืออยู่ และกู้คืนเองไม่ได้</p>
        <form method="POST" :action="del ? del.url : ''" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            @method('DELETE')
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="del = null">ยกเลิก</button>
                <button type="submit" class="nvm-btn nvm-btn--bad" :disabled="busy"><span x-text="busy ? 'กำลังลบ…' : 'ยืนยันการลบ'">ยืนยันการลบ</span></button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
