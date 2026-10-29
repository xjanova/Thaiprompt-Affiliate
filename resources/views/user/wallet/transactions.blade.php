@extends('layouts.user-nova')

@section('title', 'ประวัติธุรกรรม')

@php
    // ประวัติธุรกรรม — ธีมโนวาเต็ม
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    [$balInt, $balDec] = explode('.', number_format((float) $wallet->balance, 2));
    $filtered = request()->hasAny(['type', 'date_from', 'date_to']);
    $typeOptions = [
        'deposit' => 'ฝากเงิน', 'withdrawal' => 'ถอนเงิน', 'transfer_in' => 'รับโอน', 'transfer_out' => 'โอนออก',
        'commission' => 'คอมมิชชั่น', 'refund' => 'คืนเงิน', 'fee' => 'ค่าธรรมเนียม', 'bonus' => 'โบนัส', 'cashback' => 'เงินคืน (Cashback)',
    ];
    $tiles = array_filter([
        ['images/nova/wallet/deposit.webp', 'ฝากเงิน', 'user.wallet.deposit'],
        ['images/nova/wallet/withdraw.webp', 'ถอนเงิน', 'user.wallet.withdraw'],
        ['images/nova/wallet/transfer.webp', 'โอนเงิน', 'user.wallet.transfer'],
        ['images/nova/wallet/hourglass.webp', 'รายการถอน', 'user.wallet.withdrawals'],
    ], fn ($t) => $nvR($t[2]));
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-txp-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            @if($nvR('user.wallet.index'))
                <a class="nvm-ph__back" href="{{ route('user.wallet.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กระเป๋าเงิน</a>
            @endif
            <div class="nvm-eyebrow nvm-latin">Statement</div>
            <h1 id="nvm-txp-h">ประวัติธุรกรรม</h1>
            <p class="nvm-ph__lead">รายการเงินเข้า-ออกทั้งหมดของกระเป๋าเงิน กรองตามประเภทหรือช่วงวันที่ได้</p>
            <div class="nvm-bal">
                <small>ยอดเงินคงเหลือปัจจุบัน</small>
                <b class="nvm-foil nvm-num">฿{{ $balInt }}<span>.{{ $balDec }}</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(count($tiles))
    <nav class="nvm-tiles nvm-tiles--4 no-print" aria-label="เมนูกระเป๋าเงิน">
        @foreach($tiles as [$img, $label, $route])
            <a class="nvm-card nvm-tile" href="{{ route($route) }}">
                <img src="{{ asset($img) }}" alt="" width="76" height="76" loading="lazy">
                <b>{{ $label }}</b>
            </a>
        @endforeach
    </nav>
@endif

<section class="nvm-kpis nvm-kpis--3" aria-label="สรุปยอด">
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/deposit.webp') }}" alt="" width="78" height="78" loading="lazy">
        <div class="nvm-kpi__lbl">รายรับทั้งหมด</div>
        <div class="nvm-kpi__en nvm-latin">Total income</div>
        <div class="nvm-kpi__val nvm-num">฿{{ number_format((float) $wallet->total_income, 2) }}</div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/wallet/withdraw.webp') }}" alt="" width="78" height="78" loading="lazy">
        <div class="nvm-kpi__lbl">รายจ่ายทั้งหมด</div>
        <div class="nvm-kpi__en nvm-latin">Total spent</div>
        <div class="nvm-kpi__val nvm-num">฿{{ number_format((float) $wallet->total_expense, 2) }}</div>
    </div>
    <div class="nvm-card nvm-kpi">
        <img class="nvm-kpi__img" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="78" height="78" loading="lazy">
        <div class="nvm-kpi__lbl">{{ $filtered ? 'รายการที่ตรงตัวกรอง' : 'รายการทั้งหมด' }}</div>
        <div class="nvm-kpi__en nvm-latin">Transactions</div>
        <div class="nvm-kpi__val nvm-num">{{ number_format($transactions->total()) }}</div>
    </div>
</section>

{{-- ── ตัวกรอง ──────────────────────────────────────────── --}}
<section class="nvm-card nvm-box no-print" aria-labelledby="nvm-flt-h">
    <div class="nvm-sec-h"><div><small class="nvm-latin">Filter</small><h2 id="nvm-flt-h">ตัวกรอง</h2></div></div>
    <form method="GET" action="{{ route('user.wallet.transactions') }}" class="nvm-filter">
        <label class="nvm-field">
            <span class="nvm-label">ประเภทธุรกรรม</span>
            <select name="type" class="nvm-input">
                <option value="">ทั้งหมด</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="nvm-field">
            <span class="nvm-label">วันที่เริ่มต้น</span>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="nvm-input">
        </label>
        <label class="nvm-field">
            <span class="nvm-label">วันที่สิ้นสุด</span>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="nvm-input">
        </label>
        <div class="nvm-filter__btns">
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>กรอง</button>
            @if($filtered)
                <a href="{{ route('user.wallet.transactions') }}" class="nvm-btn nvm-btn--line">ล้าง</a>
            @endif
        </div>
    </form>
</section>

{{-- ── รายการ ───────────────────────────────────────────── --}}
<section class="nvm-card nvm-box" aria-labelledby="nvm-list-h">
    <div class="nvm-sec-h">
        <div>
            <small class="nvm-latin">All transactions</small>
            <h2 id="nvm-list-h">รายการธุรกรรม</h2>
        </div>
        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm no-print" x-data @click="window.print()"><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>พิมพ์</button>
    </div>
    @if($transactions->isNotEmpty())
        <ul class="nvm-txs">
            @foreach($transactions as $tx)
                @include('user.wallet.partials.nova-tx', ['tx' => $tx, 'showBalance' => true])
            @endforeach
        </ul>
        @if($transactions->hasPages())
            <div class="nvm-pager no-print">{{ $transactions->withQueryString()->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>{{ $filtered ? 'ไม่พบรายการที่ตรงกับตัวกรอง' : 'ยังไม่มีรายการธุรกรรม' }}</b>
            {{ $filtered ? 'ลองเปลี่ยนประเภทหรือช่วงวันที่ใหม่' : 'เมื่อมีเงินเข้า-ออก รายการจะแสดงที่นี่' }}
            @if($filtered)
                <div><a href="{{ route('user.wallet.transactions') }}" class="nvm-btn nvm-btn--line nvm-btn--sm">ล้างตัวกรอง</a></div>
            @endif
        </div>
    @endif
</section>
@endsection
