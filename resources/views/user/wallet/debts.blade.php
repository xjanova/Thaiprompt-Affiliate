{{--
    หนี้ค้างชำระ — ยอดคงเหลือ ความคืบหน้า และประวัติการหักอัตโนมัติ (ธีมโนวาเต็ม)
    ระบบหักหนี้จากรายได้ใหม่สูงสุด 50% ต่อยอด (DebtCollectionService::collectDebtFromEarning)
--}}
@extends('layouts.user-nova')

@section('title', 'หนี้ค้างชำระ')

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $owed = (float) ($debtSummary['total_debt'] ?? 0);
    $paid = (float) ($debtSummary['total_paid'] ?? 0);
    $count = (int) ($debtSummary['debt_count'] ?? 0);
    $hasDebt = (bool) ($debtSummary['has_active_debt'] ?? false);
    $paidPercent = ($owed + $paid) > 0 ? round($paid / ($owed + $paid) * 100, 1) : 100;
    [$oInt, $oDec] = explode('.', number_format($owed, 2));
    $statusMeta = [
        'active' => ['ค้างชำระ', 'bad'],
        'paid' => ['ชำระครบ', 'ok'],
        'waived' => ['ได้รับยกเว้น', 'gold'],
        'cancelled' => ['ยกเลิก', 'info'],
    ];
    $filter = request('status');
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-db-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Outstanding balance</div>
            <h1 id="nvm-db-h">หนี้ค้างชำระ</h1>
            <p class="nvm-ph__lead">{{ $hasDebt ? 'ระบบหักชำระจากรายได้ใหม่ให้อัตโนมัติ ไม่ต้องโอนเอง' : 'ตอนนี้คุณไม่มีหนี้ค้างชำระ' }}</p>
            <div class="nvm-bal">
                <small>{{ $hasDebt ? 'ยอดค้างชำระ · '.number_format($count).' รายการ' : 'ยอดค้างชำระ' }}</small>
                <b class="nvm-foil nvm-num">฿{{ $oInt }}<span>.{{ $oDec }}</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/scale.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-kpis">
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/scale.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">ค้างชำระ <span class="nvm-kpi__en nvm-latin">Owed</span></div>
        <div class="nvm-kpi__val nvm-num">฿{{ number_format($owed, 2) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip {{ $hasDebt ? 'nvm-chip--bad' : 'nvm-chip--ok' }}">{{ $hasDebt ? number_format($count).' รายการ' : 'ไม่มีหนี้' }}</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/deposit.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">ชำระแล้ว <span class="nvm-kpi__en nvm-latin">Paid</span></div>
        <div class="nvm-kpi__val nvm-num">฿{{ number_format($paid, 2) }}</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--ok">รวมที่ถูกหักแล้ว</span></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">ความคืบหน้า <span class="nvm-kpi__en nvm-latin">Progress</span></div>
        <div class="nvm-kpi__val nvm-num">{{ rtrim(rtrim(number_format($paidPercent, 1), '0'), '.') }}%</div>
        <div class="nvm-meter__bar" role="progressbar" aria-valuenow="{{ $paidPercent }}" aria-valuemin="0" aria-valuemax="100" aria-label="ชำระแล้ว"><i style="width: {{ min(100, $paidPercent) }}%"></i></div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="48" height="48" loading="lazy">
        <div class="nvm-kpi__lbl">วิธีหักชำระ <span class="nvm-kpi__en nvm-latin">Auto-deduct</span></div>
        <div class="nvm-kpi__val">50%</div>
        <div class="nvm-kpi__foot"><span class="nvm-chip nvm-chip--gold">สูงสุดของรายได้แต่ละยอด</span></div>
    </div>
</div>

@if($hasDebt)
    <section class="nvm-card nvm-alert nvm-alert--info">
        <img class="nvm-alert__img" src="{{ asset('images/nova/wallet/hourglass.webp') }}" alt="" width="56" height="56" loading="lazy">
        <div class="nvm-alert__txt">
            <b>หักชำระอัตโนมัติจากรายได้ใหม่</b>
            <p>ทุกครั้งที่มีรายได้เข้ากระเป๋า ระบบหักชำระหนี้ไม่เกิน 50% ของยอดนั้น จนกว่าจะครบ ส่วนที่เหลือเข้ากระเป๋าตามปกติ</p>
        </div>
    </section>
@endif

<section class="nvm-card nvm-box" aria-labelledby="nvm-dbl-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">History</small><h2 id="nvm-dbl-h">รายการหนี้</h2></div>
        <nav class="nvm-tabs" aria-label="กรองสถานะ">
            <a href="{{ route('user.wallet.debts') }}" class="{{ ! $filter ? 'is-on' : '' }}">ทั้งหมด</a>
            @foreach($statusMeta as $key => [$text])
                <a href="{{ route('user.wallet.debts', ['status' => $key]) }}" class="{{ $filter === $key ? 'is-on' : '' }}">{{ $text }}</a>
            @endforeach
        </nav>
    </div>

    @if($debts->count())
        <ul class="nvm-txs" x-data="{ open: null }">
            @foreach($debts as $debt)
                @php
                    [$stText, $stTone] = $statusMeta[$debt->status] ?? [$debt->status_label ?? $debt->status, 'info'];
                    $history = array_reverse((array) data_get($debt->metadata, 'deduction_history', []));
                    $pct = min(100, (float) $debt->paid_percentage);
                @endphp
                <li class="nvm-tx">
                    <span class="nvm-tx__ic {{ $debt->status === 'active' ? 'is-due' : 'is-in' }}"><svg class="nvm-ico"><use href="#{{ $debt->status === 'active' ? 'nvm-i-clock' : 'nvm-i-check' }}"/></svg></span>
                    <div class="nvm-tx__t">
                        <b>{{ $debt->reason ?: 'หนี้จากระบบ' }}</b>
                        <small class="nvm-num">{{ $debt->created_at->format('d/m/Y H:i') }}@if($debt->fully_paid_at) · ครบ {{ $debt->fully_paid_at->format('d/m/Y') }}@elseif($debt->waived_at) · ยกเว้น {{ $debt->waived_at->format('d/m/Y') }}@endif</small>
                        @if($debt->status === 'active' && $debt->original_amount > 0)
                            <div class="nvm-meter__bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100" aria-label="ชำระแล้ว"><i style="width: {{ $pct }}%"></i></div>
                            <small>หักแล้ว ฿{{ number_format($debt->deducted_amount, 2) }} จาก ฿{{ number_format($debt->original_amount, 2) }}</small>
                        @endif
                        @if(count($history))
                            <button type="button" class="nvm-link nvm-tx__more" @click="open = open === {{ $debt->id }} ? null : {{ $debt->id }}" :aria-expanded="open === {{ $debt->id }}">
                                <span x-text="open === {{ $debt->id }} ? 'ซ่อนประวัติการหัก' : 'ดูประวัติการหัก ({{ count($history) }})'">ดูประวัติการหัก</span>
                            </button>
                            <dl class="nvm-kv nvm-tx__hist" x-show="open === {{ $debt->id }}" x-cloak>
                                @foreach($history as $entry)
                                    <dt class="nvm-num">{{ \Carbon\Carbon::parse($entry['date'] ?? $debt->created_at)->format('d/m/Y H:i') }}</dt>
                                    <dd class="nvm-num">−฿{{ number_format((float) ($entry['amount'] ?? 0), 2) }}</dd>
                                @endforeach
                            </dl>
                        @endif
                    </div>
                    <div class="nvm-tx__r">
                        <b class="nvm-tx__amt nvm-num {{ $debt->status === 'active' ? 'is-due' : '' }}">฿{{ number_format($debt->status === 'active' ? $debt->remaining_amount : $debt->original_amount, 2) }}</b>
                        <span class="nvm-chip nvm-chip--{{ $stTone }}">{{ $stText }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
        @if($debts->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $debts->withQueryString()->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>{{ $filter ? 'ไม่มีรายการในสถานะนี้' : 'ไม่มีหนี้ค้างชำระ' }}</b>
            {{ $filter ? 'ลองเลือกสถานะอื่น' : 'กระเป๋าเงินของคุณไม่มีรายการหนี้' }}
        </div>
    @endif
</section>
@endsection
