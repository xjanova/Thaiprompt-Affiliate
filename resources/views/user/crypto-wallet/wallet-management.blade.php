{{--
    ภาพรวมกระเป๋าคริปโตหลายใบ (ธีมโนวาเต็ม)
    CryptoWalletController::walletManagement → $wallets (ของสมาชิกคนนี้ · กระเป๋าหลักขึ้นก่อน)
    $balancesByWallet[wallet_id] = collect(getAllBalances) คีย์เป็นรหัสเหรียญ · $totalValue · $totalAssets
    - เดิม eager load relation balances ที่ไม่มีอยู่ → 500 (แก้ใน controller แล้ว)
    - ปุ่ม "แก้ชื่อ" (แค่ console.log) และ "ดูรายละเอียด" (ลิงก์ไป URL ที่ไม่มี route) ไม่เคยทำงาน — เอาออก
    - ลบกระเป๋าเป็นฟอร์ม DELETE ปกติ + โมดัลยืนยัน แทน fetch เดิม
--}}
@extends('layouts.user-nova')

@section('title', 'ภาพรวมกระเป๋าคริปโต')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/crypto-wallet.css') }}?v={{ filemtime(public_path('theme-nova/member/crypto-wallet.css')) }}">@endpush

@php
    $kpis = [
        ['กระเป๋าทั้งหมด', 'Wallets', number_format($wallets->count()), 'images/nova/cat/wallet.webp'],
        ['ใช้งานได้', 'Active', number_format($wallets->where('status', 'active')->count()), 'images/nova/wallet/card.webp'],
        ['รายการเหรียญ', 'Assets', number_format((int) $totalAssets), 'images/nova/deco/coins.webp'],
        ['มูลค่ารวม (ประมาณ)', 'Total value', '฿'.number_format((float) $totalValue, 2), 'images/nova/wallet/deposit.webp'],
    ];
@endphp

@section('content')

<div class="nvm-stack" x-data="{ create: @js($errors->has('pin') || $errors->has('pin_confirmation')), del: null }">

<section class="nvm-ph" aria-labelledby="nvm-cwm-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.crypto-wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าคริปโต</a>
            <div class="nvm-eyebrow nvm-latin">Wallet management</div>
            <h1 id="nvm-cwm-h">ภาพรวมกระเป๋าคริปโต</h1>
            <p class="nvm-ph__lead">ดูเหรียญในกระเป๋าทุกใบ ตั้งกระเป๋าหลัก หรือสร้างกระเป๋าใหม่</p>
            <div class="nvm-pills">
                <button type="button" class="nvm-btn nvm-btn--gold nvm-btn--sm" @click="create = true"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างกระเป๋าใหม่</button>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/cat/wallet.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@include('user.crypto-wallet.partials.flash', ['errorTitle' => 'ทำรายการไม่สำเร็จ'])

<section class="nvm-kpis" aria-label="สรุปกระเป๋าคริปโต">
    @foreach($kpis as [$label, $en, $value, $img])
        <div class="nvm-card nvm-kpi">
            <img class="nvm-kpi__img" src="{{ asset($img) }}" alt="" width="78" height="78" loading="lazy">
            <div class="nvm-kpi__lbl">{{ $label }}</div>
            <div class="nvm-kpi__en nvm-latin">{{ $en }}</div>
            <div class="nvm-kpi__val nvm-num">{{ $value }}</div>
        </div>
    @endforeach
</section>

<div class="nvm-cw-grid nvm-cw-grid--wide">
    @forelse($wallets as $wallet)
        @php
            $walletBalances = $balancesByWallet[$wallet->id] ?? collect();
            $firstAddress = $wallet->cryptoAddresses->first()?->address;
        @endphp
        <section class="nvm-card nvm-cw-wallet {{ $wallet->is_default ? 'is-default' : '' }}" aria-labelledby="nvm-cwm-{{ $wallet->id }}">
            <div class="nvm-cw-wallet__head">
                <div class="nvm-cw-wallet__name">
                    <span class="nvm-cw-wallet__ic"><svg class="nvm-ico"><use href="#nvm-i-{{ $wallet->wallet_type === 'custodial' ? 'lock' : 'wallet' }}"/></svg></span>
                    <div>
                        <b id="nvm-cwm-{{ $wallet->id }}">{{ $wallet->name ?: 'กระเป๋า #'.$wallet->id }}</b>
                        <small>{{ $wallet->wallet_type === 'custodial' ? 'ระบบดูแลกุญแจ (Custodial)' : 'กระเป๋าภายนอก' }}</small>
                    </div>
                </div>
                <div class="nvm-cw-wallet__chips">
                    @if($wallet->is_default)<span class="nvm-chip nvm-chip--gold">กระเป๋าหลัก</span>@endif
                    <span class="nvm-chip {{ $wallet->status === 'active' ? 'nvm-chip--ok' : 'nvm-chip--bad' }}">{{ $wallet->status === 'active' ? 'ใช้งานได้' : ($wallet->status === 'locked' ? 'ถูกล็อก' : 'ถูกระงับ') }}</span>
                </div>
            </div>

            @if($wallet->wallet_type === 'external' && $firstAddress)
                <div class="nvm-field">
                    <span class="nvm-label">ที่อยู่กระเป๋า</span>
                    <div class="nvm-copy nvm-copy--box">
                        <code>{{ $firstAddress }}</code>
                        <button type="button" @click="nvmCopy(@js($firstAddress), 'คัดลอกที่อยู่แล้ว')"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg>คัดลอก</button>
                    </div>
                </div>
            @endif

            <div class="nvm-field">
                <span class="nvm-label">เหรียญในกระเป๋า</span>
                @if($walletBalances->isNotEmpty())
                    <div class="nvm-cw-assets">
                        @foreach($walletBalances as $code => $balance)
                            <div><small>{{ $code }}</small><b class="nvm-num">{{ number_format((float) $balance['balance'], 4) }}</b></div>
                        @endforeach
                    </div>
                @else
                    <span class="nvm-help">ยังไม่มีเหรียญในกระเป๋านี้</span>
                @endif
            </div>

            <div class="nvm-cw-stats">
                <div><b>{{ $wallet->created_at?->locale('th')->diffForHumans() ?? '-' }}</b><small>สร้างเมื่อ</small></div>
                <div><b>{{ $wallet->last_activity_at ? $wallet->last_activity_at->locale('th')->diffForHumans() : '-' }}</b><small>ใช้งานล่าสุด</small></div>
                <div><b class="nvm-num nvm-cw-up">฿{{ number_format((float) $walletBalances->sum('balance_thb'), 2) }}</b><small>มูลค่า</small></div>
            </div>

            <div class="nvm-cw-wallet__acts">
                @if(! $wallet->is_default && $wallet->status === 'active')
                    <form method="POST" action="{{ route('user.crypto-wallet.wallet.set-default', $wallet->id) }}">
                        @csrf
                        <button type="submit" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-award"/></svg>ตั้งเป็นกระเป๋าหลัก</button>
                    </form>
                @endif
                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="del = @js(['name' => $wallet->name ?: 'กระเป๋า #'.$wallet->id, 'url' => route('user.crypto-wallet.wallet.delete', $wallet->id)])"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ลบกระเป๋า</button>
            </div>
        </section>
    @empty
        <section class="nvm-card nvm-box">
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/cat/wallet.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีกระเป๋าคริปโต</b>
                สร้างกระเป๋าใบแรกเพื่อเริ่มใช้งาน
                <div><button type="button" class="nvm-btn nvm-btn--gold" @click="create = true"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างกระเป๋า</button></div>
            </div>
        </section>
    @endforelse
</div>

@include('user.crypto-wallet.partials.create-wallet-modal')

{{-- ยืนยันก่อนลบ (ลบแล้วกู้คืนเองไม่ได้) --}}
<div class="nvm-modal" x-show="del" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-cwm-del-h" @click.self="del = null" @keydown.escape.window="del = null">
    <div class="nvm-card nvm-modal__box">
        <h2 id="nvm-cwm-del-h">ลบกระเป๋านี้?</h2>
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
