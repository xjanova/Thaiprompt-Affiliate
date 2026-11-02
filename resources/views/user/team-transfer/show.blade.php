{{--
    รายละเอียดคำขอย้ายทีม (ธีมโนวาเต็ม)
    - TeamTransferController::show ตรวจแล้วว่าเป็นเจ้าของคำขอ (อื่น = 403)
    - วันที่ทุกช่องอาจว่าง (ข้อมูลเก่า/แอดมินแก้มือ) → ผ่าน $thDate ที่รับ null เสมอ
--}}
@extends('layouts.user-nova')

@section('title', 'รายละเอียดคำขอย้ายทีม #'.$request->id)

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/team-recruit.css') }}?v={{ filemtime(public_path('theme-nova/member/team-recruit.css')) }}">@endpush

@php
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';
    $baht = fn ($v) => number_format((float) $v, 2).' บาท';
    $initial = fn (?string $name) => $name ? mb_substr($name, 0, 1) : '?';
    $pill = match ($request->status) {
        'pending' => 'warn',
        'approved', 'completed' => 'ok',
        'rejected', 'cancelled' => 'bad',
        default => 'glass',
    };
    $timeline = array_values(array_filter([
        ['สร้างคำขอ', $request->created_at],
        $request->approved_at ? ['แม่ทีมเดิมอนุมัติ', $request->approved_at] : null,
        $request->rejected_at ? ['แม่ทีมเดิมปฏิเสธ', $request->rejected_at] : null,
        $request->paid_at ? ['ชำระค่าธรรมเนียม', $request->paid_at] : null,
        $request->processed_at ? ['ดำเนินการย้ายทีมเสร็จ', $request->processed_at] : null,
    ]));
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-trs-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.team-transfer.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>คำขอย้ายทีมของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Request #{{ $request->id }}</div>
            <h1 id="nvm-trs-h">รายละเอียดคำขอย้ายทีม</h1>
            <p class="nvm-ph__lead">สร้างเมื่อ <span class="nvm-num">{{ $thDate($request->created_at) }}</span></p>
            <div class="nvm-pills"><span class="nvm-pill nvm-pill--{{ $pill }}">{{ $request->status_label }}</span></div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/transfer.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ทำรายการไม่สำเร็จ</b><p>{{ session('error') }}</p></div>
    </section>
@endif

<div class="nvm-split">
    <div class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-trs-flow-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Transfer</small><h2 id="nvm-trs-flow-h">รายละเอียดการย้าย</h2></div></div>
            <div class="nvm-tr-flow">
                <div class="nvm-tr-person is-old">
                    <span class="nvm-avatar">{{ $initial($request->oldSponsor?->user?->name) }}</span>
                    <div>
                        <small>แม่ทีมเดิม</small>
                        <b>{{ $request->oldSponsor?->user?->name ?? 'ไม่พบข้อมูล' }}</b>
                        <code>{{ $request->oldSponsor?->member_code ?? '-' }}</code>
                    </div>
                </div>
                <span class="nvm-tr-flow__arrow" aria-hidden="true"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg></span>
                <div class="nvm-tr-person is-new">
                    <span class="nvm-avatar">{{ $initial($request->newSponsor?->user?->name) }}</span>
                    <div>
                        <small>แม่ทีมใหม่</small>
                        <b>{{ $request->newSponsor?->user?->name ?? 'ไม่พบข้อมูล' }}</b>
                        <code>{{ $request->newSponsor?->member_code ?? '-' }}</code>
                    </div>
                </div>
            </div>

            @if($request->reason)
                <div class="nvm-tr-quote"><small>เหตุผลในการย้าย</small><p>{{ $request->reason }}</p></div>
            @endif
            @if($request->notes)
                <div class="nvm-tr-quote"><small>หมายเหตุ</small><p>{{ $request->notes }}</p></div>
            @endif
            @if($request->status === 'rejected' && $request->rejection_reason)
                <div class="nvm-tr-quote is-bad">
                    <small>เหตุผลที่ปฏิเสธ@if($request->rejecter) · โดย {{ $request->rejecter->name }}@endif</small>
                    <p>{{ $request->rejection_reason }}</p>
                </div>
            @endif
            @if($request->admin_notes)
                <div class="nvm-tr-quote is-info">
                    <small>หมายเหตุจากทีมงาน@if($request->processor) · โดย {{ $request->processor->name }}@endif</small>
                    <p>{{ $request->admin_notes }}</p>
                </div>
            @endif
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-trs-tl-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Timeline</small><h2 id="nvm-trs-tl-h">ความคืบหน้า</h2></div></div>
            <ol class="nvm-tr-timeline">
                @foreach($timeline as [$label, $at])
                    <li>
                        <span class="nvm-tr-timeline__dot"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg></span>
                        <div><b>{{ $label }}</b><small class="nvm-num">{{ $thDate($at) }}</small></div>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-trs-pay-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Payment</small><h2 id="nvm-trs-pay-h">ค่าธรรมเนียม</h2></div></div>
            <dl class="nvm-kv">
                <dt>ยอดที่ต้องชำระ</dt><dd class="nvm-num">{{ $baht($request->transfer_fee) }}</dd>
                <dt>สถานะ</dt>
                <dd>
                    @if($request->paid_at)
                        <span class="nvm-chip nvm-chip--ok"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ชำระแล้ว</span>
                    @else
                        <span class="nvm-chip nvm-chip--warn"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>ยังไม่ชำระ</span>
                    @endif
                </dd>
                @if($request->paid_at)
                    <dt>ชำระเมื่อ</dt><dd class="nvm-num">{{ $thDate($request->paid_at) }}</dd>
                @endif
            </dl>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-trs-act-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Actions</small><h2 id="nvm-trs-act-h">การจัดการ</h2></div></div>

            @if($request->status === 'completed')
                <div class="nvm-status">
                    <svg class="nvm-ico"><use href="#nvm-i-check"/></svg>
                    <span>ย้ายทีมเสร็จสมบูรณ์<small>คุณอยู่ในทีมของแม่ทีมใหม่แล้ว</small></span>
                </div>
            @elseif($request->canBePaid() || $request->canBeCancelled())
                <div class="nvm-stack">
                    @if($request->canBePaid())
                        <form method="POST" action="{{ route('user.team-transfer.pay', $request) }}" x-data @submit="if (! confirm(@js('ยืนยันชำระค่าธรรมเนียม '.$baht($request->transfer_fee).' จาก Wallet?'))) $event.preventDefault()">
                            @csrf
                            <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ชำระ {{ $baht($request->transfer_fee) }}</button>
                        </form>
                    @endif
                    @if($request->canBeCancelled())
                        <form method="POST" action="{{ route('user.team-transfer.cancel', $request) }}" x-data @submit="if (! confirm(@js('ต้องการยกเลิกคำขอนี้ใช่หรือไม่?'.($request->paid_at ? ' เงินจะถูกคืนเข้า Wallet ของคุณ' : '')))) $event.preventDefault()">
                            @csrf
                            <button type="submit" class="nvm-btn nvm-btn--line nvm-btn--block"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยกเลิกคำขอ</button>
                        </form>
                    @endif
                </div>
            @else
                <p class="nvm-desc">ไม่มีรายการที่ต้องทำในขั้นตอนนี้</p>
            @endif

            @if($request->status === 'pending')
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>รอแม่ทีมเดิมพิจารณา เมื่ออนุมัติแล้วจะชำระค่าธรรมเนียมได้ที่หน้านี้</p>
            @elseif($request->status === 'paid' || $request->status === 'processing')
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>ชำระแล้ว รอทีมงานดำเนินการย้ายทีมให้</p>
            @endif
        </section>
    </aside>
</div>

</div>
@endsection
