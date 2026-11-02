{{--
    สร้างลิงก์เชิญผู้มุ่งหวัง (ธีมโนวาเต็ม)
    - กดปุ่มเดียว → MlmProspectController::store สร้างลิงก์แล้วพาไปหน้ารายละเอียด (มี QR/ปุ่มแชร์)
    - ไม่ได้เป็นสมาชิก MLM = $notMlmMember → แสดงคำแนะนำแทนปุ่มสร้าง
--}}
@extends('layouts.user-nova')

@section('title', 'สร้างลิงก์เชิญ')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/team-recruit.css') }}?v={{ filemtime(public_path('theme-nova/member/team-recruit.css')) }}">@endpush

@php
    $notMlm = isset($notMlmMember) && $notMlmMember;
@endphp

@section('content')
<div class="nvm-stack">

<section class="nvm-ph" aria-labelledby="nvm-ppc-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.prospects.index') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ผู้มุ่งหวังของฉัน</a>
            <div class="nvm-eyebrow nvm-latin">Invitation link</div>
            <h1 id="nvm-ppc-h">สร้างลิงก์เชิญสมาชิก</h1>
            <p class="nvm-ph__lead">สร้างลิงก์เฉพาะสำหรับเชิญเพื่อนหนึ่งคนให้สมัครผ่าน LINE แล้วติดตามได้ว่าถึงขั้นไหน</p>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/mascot/welcome.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($notMlm)
    <section class="nvm-card nvm-alert" role="status">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>คุณยังไม่ได้เป็นสมาชิก MLM</b>
            <p>สมัครเป็นสมาชิก MLM ก่อน จึงจะสร้างลิงก์เชิญและเริ่มสร้างทีมได้</p>
        </div>
        <div class="nvm-alert__side"><a class="nvm-btn nvm-btn--gold nvm-btn--sm" href="{{ route('user.mlm.dashboard') }}">ไปหน้า MLM</a></div>
    </section>
@else
    <div class="nvm-split">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-ppc-go-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Ready</small><h2 id="nvm-ppc-go-h">พร้อมเริ่มต้นแล้วใช่ไหม?</h2></div></div>
            <p class="nvm-desc">กดปุ่มด้านล่าง ระบบจะสร้างลิงก์เชิญพิเศษพร้อม QR Code ให้ทันที แล้วพาไปหน้าแชร์</p>
            <form method="POST" action="{{ route('user.prospects.store') }}" class="nvm-gap-t" x-data="{ sending: false }" @submit="sending = true">
                @csrf
                <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--block" :disabled="sending"><svg class="nvm-ico"><use href="#nvm-i-plus"/></svg><span x-text="sending ? 'กำลังสร้าง…' : 'สร้างลิงก์เชิญเลย'">สร้างลิงก์เชิญเลย</span></button>
            </form>

            <ul class="nvm-ky-perks nvm-gap-t">
                <li><svg class="nvm-ico"><use href="#nvm-i-cash"/></svg>รับค่าคอมมิชชั่นเมื่อเพื่อนสมัครสำเร็จ</li>
                <li><svg class="nvm-ico"><use href="#nvm-i-history"/></svg>ติดตามความคืบหน้าแต่ละคนได้</li>
                <li><svg class="nvm-ico"><use href="#nvm-i-users"/></svg>สร้างทีมของคุณเองผ่านระบบ MLM</li>
            </ul>
        </section>

        <aside class="nvm-stack">
            <section class="nvm-card nvm-box" aria-labelledby="nvm-ppc-how-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">How it works</small><h2 id="nvm-ppc-how-h">วิธีการใช้งาน</h2></div></div>
                <ol class="nvm-steps">
                    <li>กด "สร้างลิงก์เชิญเลย" เพื่อสร้างลิงก์สำหรับเพื่อนหนึ่งคน</li>
                    <li>แชร์ลิงก์หรือ QR Code ผ่าน LINE, Facebook หรือช่องทางอื่น</li>
                    <li>เพื่อนกดลิงก์ ระบบพาไปเพิ่มเพื่อน LINE OA เพื่อเริ่มสมัคร</li>
                    <li>ติดตามสถานะได้ที่หน้า "ผู้มุ่งหวังของฉัน"</li>
                </ol>
            </section>

            <section class="nvm-card nvm-box" aria-labelledby="nvm-ppc-note-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Good to know</small><h2 id="nvm-ppc-note-h">หมายเหตุสำคัญ</h2></div></div>
                <ul class="nvm-ky-perks">
                    <li><svg class="nvm-ico"><use href="#nvm-i-user"/></svg>หนึ่งลิงก์ใช้ได้กับหนึ่งคน สร้างได้ไม่จำกัด</li>
                    <li><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>เมื่อมีคนกดลิงก์ ระบบจะล็อกผู้มุ่งหวังคนนั้นให้คุณช่วงเวลาหนึ่ง หมดเวลาแล้วต่ออายุได้</li>
                    <li><svg class="nvm-ico"><use href="#nvm-i-line"/></svg>ผู้กดลิงก์ต้องเพิ่มเพื่อน LINE OA ก่อนเริ่มสมัคร</li>
                </ul>
            </section>
        </aside>
    </div>
@endif

</div>
@endsection
