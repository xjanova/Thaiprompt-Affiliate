{{--
    ประวัติธุรกรรม TPIX (ธีมโนวาเต็ม)
    - controller กรองเฉพาะรายการของสมาชิกเอง + ตรวจรูปแบบวันที่ก่อนส่งเข้า MySQL
    - ตัวกรองติดไปกับลิงก์หน้าถัดไป (withQueryString)
--}}
@extends('layouts.user-nova')

@section('title', 'ประวัติธุรกรรม TPIX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/tpix-tokens.css') }}?v={{ filemtime(public_path('theme-nova/member/tpix-tokens.css')) }}">@endpush

@php
    $typeOptions = [
        'deposit' => 'ฝาก',
        'withdrawal' => 'ถอน',
        'transfer_in' => 'รับจากสมาชิก',
        'transfer_out' => 'ส่งให้สมาชิก',
        'exchange_buy' => 'ซื้อเหรียญ',
        'exchange_sell' => 'ขายเหรียญ',
        'payment' => 'ชำระเงิน',
        'refund' => 'คืนเงิน',
    ];
    $statusOptions = [
        'pending' => 'รอดำเนินการ',
        'confirming' => 'รอยืนยันบนเครือข่าย',
        'confirmed' => 'ยืนยันแล้ว',
        'completed' => 'สำเร็จ',
        'failed' => 'ไม่สำเร็จ',
        'cancelled' => 'ยกเลิกแล้ว',
    ];
    $hasFilter = request()->hasAny(['type', 'status', 'date_from', 'date_to']);
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-tpt-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.tpix.wallet') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กลับกระเป๋า</a>
            <div class="nvm-eyebrow nvm-latin">TPIX history</div>
            <h1 id="nvm-tpt-h">ประวัติธุรกรรม TPIX</h1>
            <p class="nvm-ph__lead">รายการเข้าออกทั้งหมดของกระเป๋า TPIX</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<form class="nvm-card nvm-box" method="GET" action="{{ route('user.tpix.transactions') }}" aria-label="ตัวกรองรายการ">
    @if($errors->any())
        <p class="nvm-err">{{ $errors->first() }}</p>
    @endif
    <div class="nvm-filter">
        <div class="nvm-field">
            <label class="nvm-label" for="tpt-type">ประเภท</label>
            <select id="tpt-type" class="nvm-input" name="type">
                <option value="">ทั้งหมด</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="tpt-status">สถานะ</label>
            <select id="tpt-status" class="nvm-input" name="status">
                <option value="">ทั้งหมด</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="tpt-from">ตั้งแต่วันที่</label>
            <input id="tpt-from" class="nvm-input" type="date" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="nvm-field">
            <label class="nvm-label" for="tpt-to">ถึงวันที่</label>
            <input id="tpt-to" class="nvm-input" type="date" name="date_to" value="{{ request('date_to') }}">
        </div>
        <div class="nvm-filter__btns">
            <button type="submit" class="nvm-btn nvm-btn--gold"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>กรองรายการ</button>
            @if($hasFilter)
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.tpix.transactions') }}">ล้างตัวกรอง</a>
            @endif
        </div>
    </div>
</form>

<section class="nvm-card nvm-box" aria-labelledby="nvm-tpt-l-h">
    <div class="nvm-sec-h">
        <div><small class="nvm-latin">Transactions</small><h2 id="nvm-tpt-l-h">รายการ</h2></div>
        <span class="nvm-chip nvm-num">{{ number_format($transactions->total()) }} รายการ</span>
    </div>
    @if($transactions->isNotEmpty())
        <div class="nvm-txs nvm-tks">
            @foreach($transactions as $transaction)
                @include('user.tpix.partials.tx-row')
            @endforeach
        </div>
        @if($transactions->hasPages())
            <div class="nvm-pager nvm-gap-t">{{ $transactions->withQueryString()->links() }}</div>
        @endif
    @else
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>{{ $hasFilter ? 'ไม่พบรายการตามตัวกรองนี้' : 'ยังไม่มีธุรกรรม' }}</b>
        </div>
    @endif
</section>

</div>
@endsection
