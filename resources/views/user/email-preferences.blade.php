{{--
    การแจ้งเตือนทางอีเมล (ธีมโนวาเต็ม)
    - ฟอร์มหลักส่งทุกสวิตช์ — EmailPreferenceController::update อ่านด้วย boolean() (ไม่ติ๊ก = ปิด)
    - สวิตช์หลักปิด = ไม่ส่งอีเมลเลยทุกหมวด (EmailPreference::canReceive) แต่สวิตช์ย่อยยังจำค่าไว้
      จึงใช้ inert ไม่ใช่ disabled (disabled ไม่ถูกส่งไปกับฟอร์ม = กลายเป็นปิดทุกหมวด)
    - ปุ่มเปิด/ปิดทั้งหมด เป็นฟอร์มแยก → controller ตอบ back() + flash
--}}
@extends('layouts.user-nova')

@section('title', 'การแจ้งเตือนทางอีเมล')

@php
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");
    // [field, ไอคอน FontAwesome, สี, หัวข้อ, คำอธิบาย, ค่าตั้งต้น]
    $cats = [
        ['security_alerts', 'fa-shield-halved', '#e0675f', 'ความปลอดภัยบัญชี', 'เข้าสู่ระบบจากเครื่องใหม่ เปลี่ยนรหัสผ่าน เปลี่ยนการตั้งค่าสำคัญ', true],
        ['commission_notifications', 'fa-coins', '#3fae7d', 'ค่าคอมมิชชั่นและรายได้', 'ได้รับคอมมิชชั่น โบนัส และรายได้เข้ากระเป๋า', true],
        ['withdrawal_notifications', 'fa-building-columns', '#4a86c5', 'การถอนเงิน', 'สถานะคำขอถอน และยืนยันเมื่อโอนเข้าบัญชีแล้ว', true],
        ['system_announcements', 'fa-bullhorn', '#8a6fb0', 'ประกาศจากระบบ', 'ข่าวสำคัญ การปรับปรุงระบบ และกิจกรรมพิเศษ', true],
        ['weekly_reports', 'fa-chart-column', '#5d6cb0', 'สรุปรายสัปดาห์', 'ยอดรายได้ สถิติการขาย และคอมมิชชั่นประจำสัปดาห์', true],
        ['marketing_emails', 'fa-tags', '#d49a2a', 'โปรโมชั่นและข้อเสนอ', 'ส่วนลด ข้อเสนอพิเศษ และเคล็ดลับการขาย', false],
    ];
    $state = ['all_emails' => (bool) old('all_emails', $preference->all_emails ?? true)];
    foreach ($cats as [$field, , , , , $default]) {
        $state[$field] = (bool) old($field, $preference->{$field} ?? $default);
    }
    $lang = old('preferred_language', $preference->preferred_language ?? 'th');
    $onCount = collect($cats)->filter(fn ($c) => $state[$c[0]])->count();
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-ep-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">Email preferences</div>
            <h1 id="nvm-ep-h">การแจ้งเตือนทางอีเมล</h1>
            <p class="nvm-ph__lead">เลือกเรื่องที่อยากรู้ทางอีเมล ส่งไปที่ <b class="nvm-ep-mail">{{ auth()->user()->email }}</b></p>
            <div class="nvm-bal">
                <small>{{ $state['all_emails'] ? 'เปิดรับอยู่' : 'ปิดรับอีเมลทั้งหมด' }}</small>
                <b class="nvm-foil nvm-num">{{ $state['all_emails'] ? $onCount : 0 }}<span> / {{ count($cats) }} หมวด</span></b>
            </div>
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('mail', 'deco/bell') }}" alt="" width="200" height="200">
    </div>
</section>

@if(! $state['all_emails'])
    <section class="nvm-card nvm-alert nvm-alert--bad">
        <img class="nvm-alert__img" src="{{ asset('images/nova/deco/bell.webp') }}" alt="" width="56" height="56" loading="lazy">
        <div class="nvm-alert__txt">
            <b>ตอนนี้ปิดรับอีเมลทุกเรื่อง</b>
            <p>รวมถึงแจ้งเตือนความปลอดภัย — ถ้ามีคนเข้าบัญชีจากเครื่องอื่น คุณจะไม่ได้รับอีเมลแจ้ง</p>
        </div>
        <form method="POST" action="{{ route('user.email.preferences.enable-all') }}" x-data="{ busy: false }" @submit="busy = true">
            @csrf
            <button type="submit" class="nvm-btn nvm-btn--gold nvm-btn--sm" :disabled="busy">เปิดรับทั้งหมด</button>
        </form>
    </section>
@endif

<div class="nvm-split" x-data="{ s: @js($state), init0: @js($state), lang: @js($lang), lang0: @js($lang), busy: false,
        get dirty() { return this.lang !== this.lang0 || Object.keys(this.s).some(k => this.s[k] !== this.init0[k]); } }"
     @pageshow.window="busy = false">
    <form id="nvm-ep-form" method="POST" action="{{ route('user.email.preferences.update') }}" class="nvm-card nvm-box" aria-labelledby="nvm-ep-list-h" @submit="busy = true">
        @csrf
        @method('PUT')
        <div class="nvm-sec-h"><div><small class="nvm-latin">Topics</small><h2 id="nvm-ep-list-h">เรื่องที่ส่งทางอีเมล</h2></div></div>

        <label class="nvm-pref nvm-pref--master">
            <span class="nvm-pref__ic" aria-hidden="true"><i class="fa-solid fa-envelope-open-text"></i></span>
            <span class="nvm-pref__t"><b>รับอีเมลจากเรา</b><small>ปิดสวิตช์นี้ = ไม่ส่งอีเมลเลยทุกหมวด (หมวดด้านล่างยังจำที่เลือกไว้)</small></span>
            <span class="nvm-sw"><input type="checkbox" name="all_emails" value="1" x-model="s.all_emails" @checked($state['all_emails'])><i aria-hidden="true"></i></span>
        </label>

        <div class="nvm-prefs" :class="s.all_emails ? '' : 'is-muted'" :inert="! s.all_emails">
            @foreach($cats as [$field, $icon, $color, $title, $desc])
                <label class="nvm-pref" style="--pc: {{ $color }}">
                    <span class="nvm-pref__ic" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
                    <span class="nvm-pref__t">
                        <b>{{ $title }}@if($field === 'security_alerts')<span class="nvm-chip nvm-chip--ok">แนะนำให้เปิด</span>@endif</b>
                        <small>{{ $desc }}</small>
                        @if($field === 'security_alerts')
                            <small class="nvm-err" x-show="! s.security_alerts" x-cloak>ปิดแล้ว จะไม่รู้ตัวถ้ามีคนพยายามเข้าบัญชีคุณ</small>
                        @endif
                    </span>
                    <span class="nvm-sw"><input type="checkbox" name="{{ $field }}" value="1" x-model="s.{{ $field }}" @checked($state[$field])><i aria-hidden="true"></i></span>
                </label>
            @endforeach
        </div>

        <div class="nvm-ep-save">
            <span class="nvm-help" x-show="dirty" x-cloak><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>มีการเปลี่ยนแปลงที่ยังไม่บันทึก</span>
            <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="busy ? 'กำลังบันทึก…' : 'บันทึกการตั้งค่า'">บันทึกการตั้งค่า</span></button>
        </div>
    </form>

    <aside class="nvm-stack">
        <section class="nvm-card nvm-box" aria-labelledby="nvm-ep-lang-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Language</small><h2 id="nvm-ep-lang-h">ภาษาของอีเมล</h2></div></div>
            <div class="nvm-opts nvm-opts--2" role="radiogroup" aria-labelledby="nvm-ep-lang-h">
                @foreach(['th' => ['🇹🇭', 'ภาษาไทย', 'Thai'], 'en' => ['🇬🇧', 'English', 'อังกฤษ']] as $code => [$flag, $name, $sub])
                    <label class="nvm-opt">
                        {{-- อยู่นอก <form> จึงผูกกับฟอร์มหลักด้วย form="" --}}
                        <input type="radio" name="preferred_language" value="{{ $code }}" form="nvm-ep-form" x-model="lang" @checked($lang === $code)>
                        <span class="nvm-ep-flag" aria-hidden="true">{{ $flag }}</span>
                        <span><b>{{ $name }}</b><small>{{ $sub }}</small></span>
                    </label>
                @endforeach
            </div>
            <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>เปลี่ยนภาษาแล้วกด "บันทึกการตั้งค่า" ด้วย</p>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-ep-bulk-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Quick actions</small><h2 id="nvm-ep-bulk-h">ตั้งค่าแบบด่วน</h2></div></div>
            <div class="nvm-stack">
                <form method="POST" action="{{ route('user.email.preferences.enable-all') }}" x-data="{ busy: false }" @submit="busy = true">
                    @csrf
                    <button type="submit" class="nvm-btn nvm-btn--line nvm-btn--sm nvm-w-full" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>เปิดรับทุกหมวด</button>
                </form>
                @if($state['all_emails'])
                    <div x-data="{ ask: false, busy: false }" @pageshow.window="busy = false">
                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm nvm-w-full" x-show="! ask" @click="ask = true"><svg class="nvm-ico"><use href="#nvm-i-close"/></svg>หยุดรับอีเมลทั้งหมด</button>
                        <form method="POST" action="{{ route('user.email.preferences.disable-all') }}" class="nvm-confirm" x-show="ask" x-cloak @submit="busy = true">
                            @csrf
                            <p>หยุดทุกหมวด รวมถึงแจ้งเตือนความปลอดภัย? เปิดกลับได้ทุกเมื่อ และหมวดที่เลือกไว้จะกลับมาเหมือนเดิม</p>
                            <div class="nvm-filter__btns">
                                <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="ask = false" :disabled="busy">ยกเลิก</button>
                                <button type="submit" class="nvm-btn nvm-btn--bad nvm-btn--sm" :disabled="busy" x-text="busy ? 'กำลังบันทึก…' : 'ยืนยัน หยุดทั้งหมด'">ยืนยัน หยุดทั้งหมด</button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>
            <ol class="nvm-steps nvm-gap-t">
                <li>ปิดอีเมลแล้ว ยังเห็นแจ้งเตือนทุกเรื่องในหน้า "แจ้งเตือน" ของเว็บและแอป</li>
                <li>อีเมลแท้จากเราไม่เคยขอรหัสผ่าน PIN หรือรหัส OTP</li>
            </ol>
            <a class="nvm-btn nvm-btn--line nvm-btn--sm nvm-gap-t" href="{{ route('user.notifications.index') }}"><svg class="nvm-ico"><use href="#nvm-i-bell"/></svg>ดูแจ้งเตือนทั้งหมด</a>
        </section>
    </aside>
</div>
@endsection
