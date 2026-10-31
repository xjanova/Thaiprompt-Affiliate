{{--
    คอมมิชชั่นของฉัน — รวมจากระบบ MLM และ Marketplace (ธีมโนวาเต็ม)
--}}
@extends('layouts.user-nova')

@section('title', 'คอมมิชชั่น')

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $typeLabels = [
        'direct' => 'แนะนำตรง', 'direct_referral' => 'แนะนำตรง', 'unilevel_rollup' => 'Unilevel ส่งต่อ', 'unilevel_direct' => 'Unilevel ตรง', 'unilevel_indirect' => 'Unilevel ทางอ้อม',
        'binary_pair' => 'Binary จับคู่', 'binary_matching' => 'Binary Matching', 'sponsor_bonus' => 'โบนัสผู้แนะนำ',
        'rank_bonus' => 'โบนัสตำแหน่ง', 'leadership_bonus' => 'โบนัสผู้นำ', 'matching_bonus' => 'Matching Bonus',
        'pool_bonus' => 'โบนัสกองกลาง', 'mlm_unilevel' => 'MLM Unilevel', 'mlm_binary' => 'MLM Binary', 'bonus' => 'โบนัส',
    ];
    $statusMeta = [
        'pending' => ['รอดำเนินการ', 'warn'],
        'approved' => ['อนุมัติแล้ว', 'ok'],
        'paid' => ['จ่ายแล้ว', 'gold'],
        'cancelled' => ['ยกเลิก', 'bad'],
        'rejected' => ['ปฏิเสธ', 'bad'],
        'clawback' => ['เรียกคืน', 'bad'],
    ];
    $earned = (float) ($stats['amount_earned'] ?? 0);
    [$eInt, $eDec] = explode('.', number_format($earned, 2));
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-cm-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.home'))
                <a class="nvm-ph__back" href="{{ route('user.home') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>หน้าหลัก</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Commissions</div>
            <h1 id="nvm-cm-h">คอมมิชชั่นของฉัน</h1>
            <p class="nvm-ph__lead">รายได้จากการแนะนำและยอดขาย รวมทุกระบบ (MLM และ Marketplace)</p>
            <div class="nvm-bal">
                <small>ได้รับแล้ว (อนุมัติ + จ่ายแล้ว)</small>
                <b class="nvm-foil nvm-num">฿{{ $eInt }}<span>.{{ $eDec }}</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-kpis">
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">ทั้งหมด <span class="nvm-kpi__en nvm-latin">Total</span></div>
        <div class="nvm-kpi__val nvm-num">{{ number_format($stats['total'] ?? 0) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip">รายการ</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">รอดำเนินการ <span class="nvm-kpi__en nvm-latin">Pending</span></div>
        <div class="nvm-kpi__val nvm-num">฿{{ number_format($stats['amount_pending'] ?? 0, 2) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--warn">{{ number_format($stats['pending'] ?? 0) }} รายการ</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/gift.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">อนุมัติแล้ว <span class="nvm-kpi__en nvm-latin">Approved</span></div>
        <div class="nvm-kpi__val nvm-num">{{ number_format($stats['approved'] ?? 0) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--ok">รอโอนเข้ากระเป๋า</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/deposit.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">จ่ายแล้ว <span class="nvm-kpi__en nvm-latin">Paid</span></div>
        <div class="nvm-kpi__val nvm-num">฿{{ number_format($stats['amount_paid'] ?? 0, 2) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--gold">{{ number_format($stats['paid'] ?? 0) }} รายการ</span></div>
    </div>
</div>

<section class="nvm-card nvm-box" aria-labelledby="nvm-cml-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">History</small><h2 id="nvm-cml-h">ประวัติคอมมิชชั่น</h2></div>
        @if($nvR('user.wallet.index'))
            <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>กระเป๋าเงิน</a>
        @endif
    </div>
    @if($commissions->count())
        <ul class="nvm-txs">
            @foreach($commissions as $commission)
                @php
                    [$stText, $stTone] = $statusMeta[$commission->status] ?? [$commission->status, 'info'];
                    $isMlm = $commission->source === 'mlm';
                    $type = $typeLabels[$commission->type] ?? ucfirst(str_replace('_', ' ', (string) $commission->type));
                    $void = in_array($commission->status, ['cancelled', 'rejected', 'clawback'], true);
                @endphp
                <li class="nvm-tx">
                    <span class="nvm-tx__ic {{ $void ? '' : 'is-in' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $isMlm ? 'users' : 'store' }}"/></svg></span>
                    <div class="nvm-tx__t">
                        <b>{{ $type }}</b>
                        <small class="nvm-num">{{ \Carbon\Carbon::parse($commission->created_at)->format('d/m/Y H:i') }} · {{ $isMlm ? 'MLM' : 'Marketplace' }}@if($commission->percentage) · {{ rtrim(rtrim(number_format((float) $commission->percentage, 2), '0'), '.') }}%@endif</small>
                        @if($commission->notes)<small>{{ \Illuminate\Support\Str::limit($commission->notes, 90) }}</small>@endif
                    </div>
                    <div class="nvm-tx__r">
                        <span class="nvm-tx__amt nvm-num {{ $void ? 'is-void' : 'is-in' }}">+฿{{ number_format((float) $commission->amount, 2) }}</span>
                        <span class="nvm-chip nvm-chip--{{ $stTone }}">{{ $stText }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
        @if($commissions->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $commissions->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/deco/coins.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีคอมมิชชั่น</b>
            แนะนำเพื่อนหรือขายสินค้า รายได้จะแสดงที่นี่
            @if($nvR('user.mlm.team'))
                <br><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.mlm.team') }}"><svg class="nvm-ico"><use href="#nvm-i-users"/></svg>ดูทีมของฉัน</a>
            @endif
        </div>
    @endif
</section>
@endsection
