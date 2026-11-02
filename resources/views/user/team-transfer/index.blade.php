{{--
    คำขอย้ายทีมของฉัน (ธีมโนวาเต็ม)
    - TeamTransferController::index ดึงเฉพาะคำขอของสมาชิกคนนี้ (user_id) แบ่งหน้า 15 รายการ
    - ปุ่มชำระ/ยกเลิกยึด canBePaid()/canBeCancelled() ของโมเดล ชุดเดียวกับที่ service ตรวจซ้ำ
--}}
@extends('layouts.user-nova')

@section('title', 'คำขอย้ายทีมของฉัน')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/team-recruit.css') }}?v={{ filemtime(public_path('theme-nova/member/team-recruit.css')) }}">@endpush

@php
    $chipTone = fn (string $status) => match ($status) {
        'pending' => 'warn',
        'approved', 'completed' => 'ok',
        'rejected' => 'bad',
        'paid', 'processing' => 'info',
        default => '',
    };
    $baht = fn ($v) => number_format((float) $v, 2).' บาท';
    $initial = fn (?string $name) => $name ? mb_substr($name, 0, 1) : '?';
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-tr-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Team transfer</div>
            <h1 id="nvm-tr-h">คำขอย้ายทีมของฉัน</h1>
            <p class="nvm-ph__lead">ติดตามสถานะคำขอย้ายไปอยู่กับแม่ทีมใหม่ ตั้งแต่รออนุมัติจนย้ายเสร็จ</p>
            <div class="nvm-hero__actions">
                <a class="nvm-btn nvm-btn--gold" href="{{ route('user.team-transfer.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างคำขอใหม่</a>
                <a class="nvm-btn nvm-btn--ghost" href="{{ route('user.team-transfer.approvals') }}"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>คำขอที่ต้องอนุมัติ</a>
            </div>
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

@if($requests->count() > 0)
    <div class="nvm-tr-list">
        @foreach($requests as $request)
            <article class="nvm-card nvm-box nvm-tr-req {{ $request->status === 'pending' ? 'is-pending' : '' }}">
                <div class="nvm-tr-req__head">
                    <div>
                        <h3>คำขอ #{{ $request->id }}</h3>
                        <small>{{ $request->created_at->locale('th')->diffForHumans() }}</small>
                    </div>
                    <span class="nvm-chip {{ $chipTone($request->status) ? 'nvm-chip--'.$chipTone($request->status) : '' }}">{{ $request->status_label }}</span>
                </div>

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

                @if($request->status === 'rejected' && $request->rejection_reason)
                    <div class="nvm-tr-quote is-bad"><small>เหตุผลที่ปฏิเสธ</small><p>{{ $request->rejection_reason }}</p></div>
                @endif

                <div class="nvm-tr-acts">
                    <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.team-transfer.show', $request) }}"><svg class="nvm-ico"><use href="#nvm-i-search"/></svg>ดูรายละเอียด</a>
                    @if($request->canBePaid())
                        <form method="POST" action="{{ route('user.team-transfer.pay', $request) }}" x-data @submit="if (! confirm(@js('ยืนยันชำระค่าธรรมเนียม '.$baht($request->transfer_fee).' จาก Wallet?'))) $event.preventDefault()">
                            @csrf
                            <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>ชำระ {{ $baht($request->transfer_fee) }}</button>
                        </form>
                    @endif
                    @if($request->canBeCancelled())
                        <form method="POST" action="{{ route('user.team-transfer.cancel', $request) }}" x-data @submit="if (! confirm('ต้องการยกเลิกคำขอนี้ใช่หรือไม่?')) $event.preventDefault()">
                            @csrf
                            <button type="submit" class="nvm-btn nvm-btn--line nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ยกเลิกคำขอ</button>
                        </form>
                    @endif
                </div>
            </article>
        @endforeach
    </div>

    @if($requests->hasPages())
        <div class="nvm-pager">{{ $requests->links() }}</div>
    @endif
@else
    <section class="nvm-card nvm-box">
        <div class="nvm-empty">
            <img src="{{ asset('images/nova/wallet/empty.webp') }}" alt="" width="86" height="86" loading="lazy">
            <b>ยังไม่มีคำขอย้ายทีม</b>
            ต้องการย้ายไปอยู่กับแม่ทีมคนใหม่? เริ่มสร้างคำขอแรกได้เลย
            <div><a class="nvm-btn nvm-btn--gold" href="{{ route('user.team-transfer.create') }}"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg>สร้างคำขอย้ายทีม</a></div>
        </div>
    </section>
@endif

</div>
@endsection
