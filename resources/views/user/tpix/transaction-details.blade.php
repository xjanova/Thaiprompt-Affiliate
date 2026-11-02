{{--
    รายละเอียดธุรกรรม TPIX (ธีมโนวาเต็ม)
    - controller ดึงด้วย where('user_id', ผู้ใช้) + findOrFail จึงเปิดรายการของคนอื่นไม่ได้ (404)
    - ไม่แสดง metadata/บันทึกภายใน/ข้อความ error ดิบ
--}}
@extends('layouts.user-nova')

@section('title', 'รายละเอียดธุรกรรม TPIX')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/tpix-tokens.css') }}?v={{ filemtime(public_path('theme-nova/member/tpix-tokens.css')) }}">@endpush

@php
    // รายการของสมาชิกอาจเป็นเหรียญอื่น — controller กรองแค่เจ้าของ ไม่ได้กรองสกุลเงิน
    $code = $transaction->currency?->code ?? 'TPIX';
    $incoming = $transaction->is_incoming;
    // explorer_url ในแถว TPIX ยังชี้เชนเก่า เชนจริงอยู่ใน tpix_pay
    $explorerUrl = ! $transaction->tx_hash
        ? ''
        : ($code === 'TPIX'
            ? rtrim((string) config('tpix_pay.explorer_url'), '/').'/tx/'.$transaction->tx_hash
            : $transaction->getExplorerUrl());
    $rows = array_filter([
        'เลขที่รายการ' => $transaction->transaction_id,
        'ค่าธรรมเนียม' => number_format((float) $transaction->fee, 8).' '.$code,
        'รายละเอียด' => $transaction->description,
        'จากที่อยู่' => $transaction->from_address,
        'ไปยังที่อยู่' => $transaction->to_address,
        'เลขธุรกรรมบนเชน' => $transaction->tx_hash,
        'วันที่ทำรายการ' => $transaction->created_at->format('d/m/Y H:i'),
        'วันที่สำเร็จ' => ($transaction->completed_at ?? $transaction->confirmed_at)?->format('d/m/Y H:i'),
    ], fn ($value) => filled($value));
    $monoRows = ['จากที่อยู่', 'ไปยังที่อยู่', 'เลขธุรกรรมบนเชน', 'เลขที่รายการ'];
@endphp

@section('content')

<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-tpx-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.tpix.transactions') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>กลับประวัติ</a>
            <div class="nvm-eyebrow nvm-latin">Transaction</div>
            <h1 id="nvm-tpx-h">รายละเอียดธุรกรรม</h1>
            <p class="nvm-ph__lead">{{ $transaction->typeLabel() }}</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-tpx-i-h">
        <div class="nvm-sec-h">
            <div><small class="nvm-latin">Amount</small><h2 id="nvm-tpx-i-h">จำนวนและสถานะ</h2></div>
            @include('user.tpix.partials.status-pill', ['status' => $transaction->status])
        </div>
        <div class="nvm-tx-big nvm-num {{ $incoming ? 'is-in' : '' }}">{{ $incoming ? '+' : '-' }}{{ number_format((float) $transaction->amount, 8) }}<span>{{ $code }}</span></div>

        @if($transaction->tx_hash && $transaction->isPending())
            <p class="nvm-help nvm-gap-t">ยืนยันแล้ว <span class="nvm-num">{{ (int) $transaction->confirmations }}</span> จาก <span class="nvm-num">{{ (int) $transaction->confirmations_required }}</span> บล็อก</p>
        @endif

        <dl class="nvm-kv nvm-gap-t">
            @foreach($rows as $label => $value)
                <dt>{{ $label }}</dt>
                <dd class="{{ in_array($label, $monoRows, true) ? 'nvm-tx-mono' : 'nvm-num' }}">{{ $value }}</dd>
            @endforeach
        </dl>
    </section>

    @if($transaction->tx_hash)
        <aside class="nvm-card nvm-box" aria-labelledby="nvm-tpx-c-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">On chain</small><h2 id="nvm-tpx-c-h">ข้อมูลบนเครือข่าย</h2></div></div>
            @if($blockchainData)
                <dl class="nvm-kv">
                    <dt>บล็อก</dt>
                    <dd class="nvm-num">{{ $blockchainData['blockNumber'] ?? 'ยังไม่เข้าบล็อก' }}</dd>
                </dl>
            @else
                <div class="nvm-status is-warn">
                    <svg class="nvm-ico"><use href="#nvm-i-info"/></svg>
                    <span>ขณะนี้เชื่อมต่อเครือข่ายไม่ได้<small>ข้อมูลรายการด้านซ้ายยังถูกต้องตามระบบ</small></span>
                </div>
            @endif
            @if($explorerUrl)
                <a class="nvm-btn nvm-btn--line nvm-w-full nvm-gap-t" href="{{ $explorerUrl }}" target="_blank" rel="noopener noreferrer"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ดูบน Explorer</a>
            @endif
        </aside>
    @endif
</div>

</div>
@endsection
