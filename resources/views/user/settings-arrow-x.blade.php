{{--
    ตั้งค่าบัญชี (ธีมโนวาเต็ม) — SettingsController::index ส่งมา: $user, $settings
      $settings['preferences']['language']           ภาษา (บันทึกได้จริงผ่าน PUT user.settings.update)
      $settings['security']['two_factor_enabled']    สถานะ 2FA จาก two_factor_user_settings
      $settings['security']['active_sessions_count'] จำนวนอุปกรณ์ที่เข้าสู่ระบบ

    หมายเหตุ: สวิตช์แจ้งเตือน/ความเป็นส่วนตัว/ธีม/เขตเวลา/สกุลเงินแบบเดิมถูกถอดออก — ไม่มีคอลัมน์รองรับ บันทึกแล้วไม่ติด
    การแจ้งเตือนทางอีเมลอยู่ที่ user.email.preferences · โหมดมืดสลับได้ทันทีด้วย $store.nvmTheme (จำในเครื่อง)
--}}
@extends('layouts.user-nova')

@section('title', 'การตั้งค่า')

@push('styles')<link rel="stylesheet" href="{{ asset('theme-nova/member/user-misc.css') }}?v={{ filemtime(public_path('theme-nova/member/user-misc.css')) }}">@endpush

@php
    $nvR = fn ($name) => \Illuminate\Support\Facades\Route::has($name);
    $lang = old('preferred_language', $settings['preferences']['language'] ?? ($user->preferred_language ?: 'th'));
    $twoFa = (bool) ($settings['security']['two_factor_enabled'] ?? false);
    $twoFaMethod = [
        'authenticator' => 'แอป Authenticator', 'google2fa' => 'แอป Authenticator', 'sms' => 'SMS',
        'email' => 'อีเมล', 'line' => 'LINE',
    ][$settings['security']['two_factor_method'] ?? ''] ?? null;
    $sessCount = (int) ($settings['security']['active_sessions_count'] ?? 1);

    // ลิงก์ไปหน้าตั้งค่าที่ทำงานจริง: [route, ภาพ, หัวข้อ, คำอธิบาย]
    $links = array_values(array_filter([
        ['user.profile', 'profile', 'โปรไฟล์และรหัสผ่าน', 'แก้ไขชื่อ รูปโปรไฟล์ อีเมล และเปลี่ยนรหัสผ่าน'],
        ['user.email.preferences', 'mail', 'การแจ้งเตือนทางอีเมล', 'เลือกอีเมลที่อยากได้รับ เช่น คอมมิชชั่น ทีม ข่าวสาร'],
        ['user.notifications.index', 'bell', 'การแจ้งเตือน', 'ดูและจัดการการแจ้งเตือนทั้งหมดในระบบ'],
        ['user.kyc.index', 'idcard', 'ยืนยันตัวตน (KYC)', 'ยืนยันตัวตนเพื่อถอนเงินและใช้งานได้ครบ'],
    ], fn ($l) => $nvR($l[0])));
@endphp

@section('content')

<div class="nvm-stack" x-data="{ tab: 'general' }">

<section class="nvm-ph" aria-labelledby="nvm-um-set-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ route('user.profile') }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>โปรไฟล์</a>
            <div class="nvm-eyebrow nvm-latin">Settings</div>
            <h1 id="nvm-um-set-h">การตั้งค่า</h1>
            <p class="nvm-ph__lead">ปรับแต่งภาษา การแสดงผล และความปลอดภัยของบัญชี</p>
            <div class="nvm-pills">
                @if($twoFa)
                    <span class="nvm-pill nvm-pill--ok"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>เปิด 2FA แล้ว</span>
                @else
                    <span class="nvm-pill nvm-pill--warn"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ยังไม่เปิด 2FA</span>
                @endif
                <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-user"/></svg>เข้าสู่ระบบอยู่ {{ number_format($sessCount) }} อุปกรณ์</span>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ asset('images/nova/account/key.webp') }}" alt="" width="200" height="200">
    </div>
</section>

@if($errors->any())
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt"><b>บันทึกไม่สำเร็จ</b>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    </section>
@endif

<nav class="nvm-tabs" aria-label="หมวดการตั้งค่า">
    <button type="button" :class="{ 'is-on': tab === 'general' }" @click="tab = 'general'"><svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>ทั่วไป</button>
    <button type="button" :class="{ 'is-on': tab === 'security' }" @click="tab = 'security'"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ความปลอดภัย</button>
    <button type="button" :class="{ 'is-on': tab === 'more' }" @click="tab = 'more'"><svg class="nvm-ico"><use href="#nvm-i-menu"/></svg>ตั้งค่าอื่นๆ</button>
</nav>

{{-- ════ ทั่วไป: ภาษา + โหมดมืด ════ --}}
<div class="nvm-split" x-show="tab === 'general'">
    <section class="nvm-card nvm-box" aria-labelledby="nvm-um-set-lang">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Language</small><h2 id="nvm-um-set-lang">ภาษาที่ใช้แสดงผล</h2></div></div>
        <form method="POST" action="{{ route('user.settings.update') }}" class="nvm-form" x-data="{ busy: false }" @submit="busy = true" @pageshow.window="busy = false">
            @csrf
            @method('PUT')
            <div class="nvm-opts nvm-opts--2" role="radiogroup" aria-labelledby="nvm-um-set-lang">
                <label class="nvm-opt">
                    <input type="radio" name="preferred_language" value="th" @checked($lang === 'th')>
                    <span class="nvm-um-ic nvm-um-ic--txt">ไทย</span>
                    <span><b>ภาษาไทย</b><small>ค่าเริ่มต้น</small></span>
                </label>
                <label class="nvm-opt">
                    <input type="radio" name="preferred_language" value="en" @checked($lang === 'en')>
                    <span class="nvm-um-ic nvm-um-ic--txt">EN</span>
                    <span><b>English</b><small>อังกฤษ</small></span>
                </label>
            </div>
            @error('preferred_language')<span class="nvm-err">{{ $message }}</span>@enderror
            <div>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="busy ? 'กำลังบันทึก…' : 'บันทึกการตั้งค่า'">บันทึกการตั้งค่า</span></button>
            </div>
        </form>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-um-set-look">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Appearance</small><h2 id="nvm-um-set-look">รูปแบบการแสดงผล</h2></div></div>
        <label class="nvm-pref nvm-pref--master">
            <span class="nvm-pref__ic"><svg class="nvm-ico"><use :href="$store.nvmTheme && $store.nvmTheme.dark ? '#nvm-i-moon' : '#nvm-i-sun'" href="#nvm-i-sun"/></svg></span>
            <span class="nvm-pref__t"><b>โหมดมืด</b><small>สลับได้ทันที จำค่าไว้ในเครื่องนี้</small></span>
            <span class="nvm-sw">
                <input type="checkbox" :checked="$store.nvmTheme && $store.nvmTheme.dark" @change="$store.nvmTheme && $store.nvmTheme.toggle()" aria-label="เปิดหรือปิดโหมดมืด">
                <i></i>
            </span>
        </label>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ถ้าเครื่องตั้งค่าลดการเคลื่อนไหวไว้ ระบบจะลดแอนิเมชันให้อัตโนมัติ</p>
    </aside>
</div>

{{-- ════ ความปลอดภัย: 2FA + อุปกรณ์ ════ --}}
<div class="nvm-split" x-show="tab === 'security'" x-cloak>
    <section class="nvm-card nvm-box" aria-labelledby="nvm-um-set-sec">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Security</small><h2 id="nvm-um-set-sec">ความปลอดภัยของบัญชี</h2></div></div>

        <div class="nvm-status {{ $twoFa ? '' : 'is-warn' }}">
            <svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>
            <span>
                การยืนยันตัวตนแบบ 2 ชั้น (2FA) {{ $twoFa ? 'เปิดใช้งานแล้ว' : 'ยังไม่ได้เปิดใช้งาน' }}
                <small>{{ $twoFa ? ($twoFaMethod ? 'ยืนยันผ่าน '.$twoFaMethod : 'บัญชีของคุณได้รับการป้องกันเพิ่มอีกชั้น') : 'เพิ่มความปลอดภัยให้บัญชี ก่อนถอนเงินหรือเปลี่ยนข้อมูลสำคัญ' }}</small>
            </span>
        </div>
        @if($nvR('user.two-factor.setup'))
            <a class="nvm-btn {{ $twoFa ? 'nvm-btn--line' : 'nvm-btn--gold' }} nvm-gap-t" href="{{ route('user.two-factor.setup') }}"><svg class="nvm-ico"><use href="#nvm-i-gear"/></svg>{{ $twoFa ? 'จัดการ 2FA' : 'เปิดใช้ 2FA' }}</a>
        @endif

        <div class="nvm-um-row nvm-gap-t">
            <span class="nvm-um-ic"><svg class="nvm-ico"><use href="#nvm-i-user"/></svg></span>
            <span class="nvm-um-row__t"><b>อุปกรณ์ที่เข้าสู่ระบบ</b><small>มีอุปกรณ์ที่ใช้งานอยู่ <span class="nvm-num">{{ number_format($sessCount) }}</span> เครื่อง</small></span>
            @if($nvR('user.settings.sessions.delete-others'))
                <form method="POST" action="{{ route('user.settings.sessions.delete-others') }}" x-data="{ ask: false }" @submit="if (! ask) { $event.preventDefault(); ask = true; }">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="nvm-btn nvm-btn--sm" :class="ask ? 'nvm-btn--bad' : 'nvm-btn--line'">
                        <svg class="nvm-ico"><use href="#nvm-i-logout"/></svg><span x-text="ask ? 'กดอีกครั้งเพื่อยืนยัน' : 'ออกจากระบบอุปกรณ์อื่น'">ออกจากระบบอุปกรณ์อื่น</span>
                    </button>
                </form>
            @endif
        </div>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-um-set-tip">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Tips</small><h2 id="nvm-um-set-tip">เคล็ดลับความปลอดภัย</h2></div></div>
        <ol class="nvm-steps">
            <li>เปิด 2FA เพื่อกันคนอื่นเข้าบัญชีแม้รู้รหัสผ่าน</li>
            <li>หากพบกิจกรรมที่ไม่คุ้นเคย ให้ออกจากระบบอุปกรณ์อื่นทั้งหมด</li>
            <li>แล้วเปลี่ยนรหัสผ่านทันทีที่หน้าโปรไฟล์</li>
        </ol>
    </aside>
</div>

{{-- ════ ตั้งค่าอื่นๆ: ลิงก์ไปหน้าที่ทำงานจริง ════ --}}
<section x-show="tab === 'more'" x-cloak aria-label="ตั้งค่าอื่นๆ">
    <div class="nvm-um-grid">
        @foreach($links as [$route, $art, $title, $desc])
            <a class="nvm-card nvm-um-hub" href="{{ route($route) }}">
                <img src="{{ asset('images/nova/account/'.$art.'.webp') }}" alt="" width="64" height="64" loading="lazy">
                <span class="nvm-um-hub__t"><b>{{ $title }}</b><small>{{ $desc }}</small></span>
                <svg class="nvm-ico nvm-um-hub__go"><use href="#nvm-i-chev"/></svg>
            </a>
        @endforeach
    </div>
</section>

</div>
@endsection
