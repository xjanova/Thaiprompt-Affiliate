{{--
    สร้างคำขอย้ายทีม (ธีมโนวาเต็ม)
    - TeamTransferController::create กันแล้ว: ต้องเป็นสมาชิก MLM และไม่มีคำขอที่ค้างอยู่
    - ชื่อฟิลด์ new_sponsor_code / reason / notes ตรงกับ TeamTransferController::store
--}}
@extends('layouts.user-nova')

@section('title', 'สร้างคำขอย้ายทีม')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/team-recruit.css') }}?v={{ filemtime(public_path('theme-nova/member/team-recruit.css')) }}">@endpush

@php
    $fee = number_format((float) $transferFee, 2);
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-trc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.team-transfer.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>คำขอย้ายทีมของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">New request</div>
            <h1 id="nvm-trc-h">สร้างคำขอย้ายทีม</h1>
            <p class="nvm-ph__lead">กรอกรหัสสมาชิกของแม่ทีมที่ต้องการย้ายไป แม่ทีมเดิมจะได้รับแจ้งเพื่อพิจารณา</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/wallet/transfer.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if(session('error'))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>ส่งคำขอไม่สำเร็จ</b><p>{{ session('error') }}</p></div>
    </section>
@endif

<div class="nvm-split">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-trc-form-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Request form</small><h2 id="nvm-trc-form-h">ข้อมูลคำขอ</h2></div></div>

        {{-- ค่าเริ่มต้นส่งผ่าน @js (ปลอดภัยกับเครื่องหมายคำพูด/ขึ้นบรรทัดใหม่ในเหตุผล) --}}
        <form method="POST" action="{{ route('user.team-transfer.store') }}" class="nvm-form"
              x-data="{ code: @js((string) old('new_sponsor_code', '')), reason: @js((string) old('reason', '')), sending: false }"
              @submit="sending = true" @pageshow.window="if ($event.persisted) sending = false">
            @csrf

            <div class="nvm-field">
                <label class="nvm-label" for="new_sponsor_code">รหัสแม่ทีมใหม่ <em>*</em></label>
                <input type="text" id="new_sponsor_code" name="new_sponsor_code" class="nvm-input @error('new_sponsor_code') is-bad @enderror"
                       x-model="code" required maxlength="50" autocomplete="off" placeholder="เช่น M12345678">
                <span class="nvm-help">รหัสสมาชิกของแม่ทีมที่คุณต้องการย้ายไป (ขอได้จากแม่ทีมคนนั้น)</span>
                @error('new_sponsor_code')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>

            <div class="nvm-field">
                <label class="nvm-label" for="reason">เหตุผลในการย้าย</label>
                <textarea id="reason" name="reason" rows="4" maxlength="500" class="nvm-input @error('reason') is-bad @enderror"
                          x-model="reason" placeholder="อธิบายเหตุผลสั้นๆ (ไม่บังคับ) ช่วยให้แม่ทีมเดิมพิจารณาได้ง่ายขึ้น"></textarea>
                <span class="nvm-tr-count nvm-num" x-text="reason.length + '/500'">0/500</span>
                @error('reason')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>

            <div class="nvm-field">
                <label class="nvm-label" for="notes">หมายเหตุเพิ่มเติม</label>
                <textarea id="notes" name="notes" rows="3" maxlength="500" class="nvm-input @error('notes') is-bad @enderror" placeholder="ข้อมูลเพิ่มเติม (ไม่บังคับ)">{{ old('notes') }}</textarea>
                @error('notes')<span class="nvm-err">{{ $message }}</span>@enderror
            </div>

            <div class="nvm-filter__btns">
                <a class="nvm-btn nvm-btn--line" href="{{ route('user.team-transfer.index') }}">ยกเลิก</a>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="code.trim() === '' || sending">
                    <svg class="nvm-ico"><use href="#nvm-i-send"/></svg><span x-text="sending ? 'กำลังส่ง…' : 'ส่งคำขอย้ายทีม'">ส่งคำขอย้ายทีม</span>
                </button>
            </div>
        </form>
    </section>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-trc-cur-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Current leader</small><h2 id="nvm-trc-cur-h">แม่ทีมปัจจุบัน</h2></div></div>
            <dl class="nvm-kv">
                <dt>ชื่อแม่ทีม</dt><dd>{{ $currentSponsor?->user?->name ?? 'ยังไม่มีแม่ทีม' }}</dd>
                <dt>รหัสสมาชิก</dt><dd class="nvm-num">{{ $currentSponsor?->member_code ?? '-' }}</dd>
            </dl>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-trc-fee-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Fee</small><h2 id="nvm-trc-fee-h">ค่าธรรมเนียม</h2></div>
                <span class="nvm-chip nvm-chip--gold nvm-num">{{ $fee }} บาท</span>
            </div>
            <ul class="nvm-ky-perks">
                <li><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>ชำระหลังแม่ทีมเดิมอนุมัติแล้วเท่านั้น</li>
                <li><svg class="nvm-ico"><use href="#nvm-i-wallet"/></svg>หักจาก Wallet ของคุณ</li>
                <li><svg class="nvm-ico"><use href="#nvm-i-swap"/></svg>ยกเลิกก่อนดำเนินการ ได้เงินคืนทันที</li>
            </ul>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-trc-step-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-trc-step-h">ขั้นตอนการย้ายทีม</h2></div></div>
            <ol class="nvm-steps">
                <li>ส่งคำขอย้ายทีม</li>
                <li>รอแม่ทีมเดิมอนุมัติ (หรือปฏิเสธ)</li>
                <li>ชำระค่าธรรมเนียม {{ $fee }} บาท</li>
                <li>ทีมงานดำเนินการย้ายทีมให้</li>
                <li>เสร็จสิ้น คุณย้ายไปอยู่กับแม่ทีมใหม่แล้ว</li>
            </ol>
        </section>
    </aside>
</div>

</div>
@endsection
