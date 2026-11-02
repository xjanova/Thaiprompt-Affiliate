{{--
    คำขอย้ายทีมที่ต้องอนุมัติ (สำหรับแม่ทีมเดิม · ธีมโนวาเต็ม)
    - TeamTransferController::approvals ดึงเฉพาะคำขอที่ old_unilevel_sponsor_id = สมาชิกคนนี้
    - approve/reject ตรวจซ้ำใน MlmTeamTransferService ว่าเป็นแม่ทีมเดิมจริง
    - 🔒 (2026-11-02) ข้อความยืนยันเดิมฝังชื่อสมาชิกลง onclick="confirm('…')" ตรงๆ
      ชื่อที่มี ' ทำให้หลุดออกจากสตริง JS ได้ (XSS ใส่แม่ทีม) → ตอนนี้ส่งผ่าน @js เท่านั้น
--}}
@extends('layouts.user-nova')

@section('title', 'คำขอย้ายทีมที่ต้องอนุมัติ')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/team-recruit.css') }}?v={{ filemtime(public_path('theme-nova/member/team-recruit.css')) }}">@endpush

@php
    $thDate = fn ($d) => $d ? $d->copy()->locale('th')->translatedFormat('j M Y · H:i') : '-';
    $initial = fn (?string $name) => $name ? mb_substr($name, 0, 1) : '?';
    $chipTone = fn (string $status) => match ($status) {
        'approved', 'completed' => 'ok',
        'rejected' => 'bad',
        'paid' => 'info',
        default => '',
    };
@endphp

@section('content')
<div class="nvm-stack" x-data="{ tab: @js($activeTab ?? 'pending'), reject: null }" @keydown.escape.window="reject = null">

<section class="nvm-ph" aria-labelledby="nvm-tra-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.team-transfer.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>คำขอย้ายทีมของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Approvals</div>
            <h1 id="nvm-tra-h">คำขอย้ายทีมที่ต้องอนุมัติ</h1>
            <p class="nvm-ph__lead">ลูกทีมของคุณที่ขอย้ายไปอยู่กับแม่ทีมใหม่ พิจารณาอนุมัติหรือปฏิเสธพร้อมเหตุผล</p>
            <div class="nvm-pills">
                <span class="nvm-pill nvm-pill--{{ $pendingApprovals->total() > 0 ? 'warn' : 'glass' }}"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>รอพิจารณา {{ number_format($pendingApprovals->total()) }} รายการ</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/scale.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ทำรายการไม่สำเร็จ</b><p>{{ session('error') }}</p></div>
    </section>
@endif

<section class="nvm-card nvm-box" aria-label="คำขอย้ายทีม">
    <div class="nvm-tabs nvm-tr-tabs" role="tablist">
        <button type="button" role="tab" :class="{ 'is-on': tab === 'pending' }" :aria-selected="tab === 'pending'" @click="tab = 'pending'">รอดำเนินการ ({{ number_format($pendingApprovals->total()) }})</button>
        <button type="button" role="tab" :class="{ 'is-on': tab === 'processed' }" :aria-selected="tab === 'processed'" @click="tab = 'processed'">ดำเนินการแล้ว ({{ number_format($processedApprovals->total()) }})</button>
    </div>

    {{-- ── รอดำเนินการ ─────────────────────────────────── --}}
    <div x-show="tab === 'pending'" role="tabpanel">
        @if($pendingApprovals->count() > 0)
            <div class="nvm-tr-list">
                @foreach($pendingApprovals as $request)
                    @php $who = $request->member?->user?->name ?? 'สมาชิก'; @endphp
                    <article class="nvm-card nvm-box nvm-tr-req is-pending">
                        <div class="nvm-tr-req__head">
                            <div class="nvm-tr-who">
                                <span class="nvm-avatar">{{ $initial($request->member?->user?->name) }}</span>
                                <div>
                                    <b>{{ $who }}</b>
                                    <small>รหัส {{ $request->member?->member_code ?? '-' }} · {{ $request->created_at->locale('th')->diffForHumans() }}</small>
                                </div>
                            </div>
                            <span class="nvm-chip nvm-chip--warn"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>รอการอนุมัติ</span>
                        </div>

                        <div class="nvm-tr-person is-new">
                            <span class="nvm-avatar">{{ $initial($request->newSponsor?->user?->name) }}</span>
                            <div>
                                <small>ต้องการย้ายไปหา</small>
                                <b>{{ $request->newSponsor?->user?->name ?? 'ไม่พบข้อมูล' }}</b>
                                <code>{{ $request->newSponsor?->member_code ?? '-' }}</code>
                            </div>
                        </div>

                        @if($request->reason)
                            <div class="nvm-tr-quote"><small>เหตุผลในการย้าย</small><p>{{ $request->reason }}</p></div>
                        @endif

                        <div class="nvm-tr-acts">
                            <form method="POST" action="{{ route('user.team-transfer.approve', $request) }}" x-data @submit="if (! confirm(@js('อนุมัติให้ '.$who.' ย้ายทีมใช่หรือไม่?'))) $event.preventDefault()">
                                @csrf
                                <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>อนุมัติ</button>
                            </form>
                            <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="reject = @js(['id' => $request->id, 'name' => $who, 'action' => route('user.team-transfer.reject', $request)])"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg>ปฏิเสธ</button>
                        </div>
                    </article>
                @endforeach
            </div>
            @if($pendingApprovals->hasPages())
                <div class="nvm-pager nvm-gap-t">{{ $pendingApprovals->links() }}</div>
            @endif
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/deco/lotus.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ไม่มีคำขอที่รอดำเนินการ</b>
                ยังไม่มีลูกทีมขอย้ายทีมในขณะนี้
            </div>
        @endif
    </div>

    {{-- ── ดำเนินการแล้ว ───────────────────────────────── --}}
    <div x-show="tab === 'processed'" x-cloak role="tabpanel">
        @if($processedApprovals->count() > 0)
            <ul class="nvm-txs">
                @foreach($processedApprovals as $request)
                    <li class="nvm-tx">
                        <span class="nvm-tx__ic {{ $request->status === 'rejected' ? 'is-due' : 'is-in' }}"><svg class="nvm-ico"><use href="#nvm-i-{{ $request->status === 'rejected' ? 'x' : 'check' }}"/></svg></span>
                        <div class="nvm-tx__t nvm-tx__t--wrap">
                            <b>{{ $request->member?->user?->name ?? 'สมาชิก' }}</b>
                            <small>รหัส {{ $request->member?->member_code ?? '-' }} · ย้ายไปหา {{ $request->newSponsor?->user?->name ?? 'ไม่พบข้อมูล' }} ({{ $request->newSponsor?->member_code ?? '-' }})</small>
                            @if($request->status === 'rejected' && $request->rejection_reason)
                                <div class="nvm-tr-quote is-bad"><small>เหตุผลที่ปฏิเสธ</small><p>{{ $request->rejection_reason }}</p></div>
                            @endif
                        </div>
                        <div class="nvm-tx__r">
                            <span class="nvm-chip {{ $chipTone($request->status) ? 'nvm-chip--'.$chipTone($request->status) : '' }}">{{ $request->status_label }}</span>
                            <time class="nvm-num">{{ $thDate($request->status === 'rejected' ? $request->rejected_at : ($request->approved_at ?? $request->updated_at)) }}</time>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if($processedApprovals->hasPages())
                <div class="nvm-pager nvm-gap-t">{{ $processedApprovals->links() }}</div>
            @endif
        @else
            <div class="nvm-empty">
                <img src="{{ asset('images/nova/deco/scroll.webp') }}" alt="" width="86" height="86" loading="lazy">
                <b>ยังไม่มีคำขอที่ดำเนินการ</b>
                ยังไม่มีประวัติการอนุมัติหรือปฏิเสธคำขอ
            </div>
        @endif
    </div>
</section>

{{-- หน้าต่างกรอกเหตุผลปฏิเสธ — ฟอร์มเดียวใช้ร่วมกันทุกคำขอ (action มาจากปุ่มที่กด) --}}
<div class="nvm-modal" x-show="reject" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="nvm-tra-rej-h" @click.self="reject = null">
    <div class="nvm-card nvm-modal__box nvm-tr-modal">
        <h2 id="nvm-tra-rej-h">ปฏิเสธคำขอ</h2>
        <p class="nvm-desc" x-text="reject ? 'คำขอย้ายทีมของ ' + reject.name : ''"></p>
        <form method="POST" :action="reject ? reject.action : ''" class="nvm-form">
            @csrf
            <div class="nvm-field">
                <label class="nvm-label" for="rejection_reason">เหตุผลที่ปฏิเสธ <em>*</em></label>
                <textarea id="rejection_reason" name="rejection_reason" rows="4" required maxlength="500" class="nvm-input" placeholder="ระบุเหตุผลที่ปฏิเสธคำขอนี้"></textarea>
                <span class="nvm-help">สมาชิกจะเห็นเหตุผลที่คุณระบุ</span>
                @error('rejection_reason')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>
            <div class="nvm-filter__btns">
                <button type="button" class="nvm-btn nvm-btn--line" @click="reject = null">ยกเลิก</button>
                <button type="submit" class="nvm-btn nvm-btn--bad">ยืนยันปฏิเสธ</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
