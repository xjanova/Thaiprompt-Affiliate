{{--
    จัดการโปรไฟล์ (ธีมโนวาเต็ม)
    - ฟอร์มหลัก PUT user.profile.update (DashboardController::updateProfile) — ชื่อ/อีเมล/เบอร์/วันเกิด/เพศ/รูป/ที่อยู่ออกใบเสร็จ
    - เปลี่ยนอีเมลต้องยืนยันตัวตน (AccountEmailChangeService) — ช่องยืนยันโผล่เมื่ออีเมลที่พิมพ์ต่างจากเดิม
    - เปลี่ยนรหัสผ่านเป็นฟอร์มแยก POST user.profile.update-password (ผ่าน turnstile + 2FA ตาม route)
    - ข้อมูลบัตรประชาชนอ่านอย่างเดียว (แก้ได้เฉพาะแอดมิน) เลขบัตรปิดบางส่วน
--}}
@extends('layouts.user-nova')

@section('title', 'จัดการโปรไฟล์')

@php
    use App\Services\AccountEmailChangeService as EmailChange;
    use Illuminate\Support\Facades\Route as RouteFacade;

    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");

    $kycVerified = $user->isKycVerified();
    $kycPending = ! $kycVerified && $user->isKycPending();

    // 🔒 วิธียืนยันเมื่อเปลี่ยนอีเมล (ส่งมาจาก DashboardController::profile — คำนวณเองถ้าไม่ได้ส่งมา)
    $emailChangeMode ??= app(EmailChange::class)->mode($user);
    $emailIsReal ??= \App\Support\AccountEmail::isDeliverable($user->email);
    $currentEmailMasked ??= $emailIsReal ? \App\Support\AccountEmail::mask((string) $user->email) : null;
    $pwMode = $emailChangeMode === EmailChange::MODE_PASSWORD;

    // Turnstile ของการเปลี่ยนรหัสผ่าน (route update-password ตรวจ point password_change)
    $pwTurnstile = config('turnstile.enabled') && config('turnstile.site_key') && config('turnstile.points.password_change');

    // รูปโปรไฟล์: ยังไม่มีรูปจริง (accessor ส่ง ui-avatars มา) หรือรูปโหลดไม่ขึ้น → ใช้ภาพกรอบทองของธีมแทน
    $avatarArt = $nvArt('profile', 'deco/lotus');
    $avatarUrl = $user->profile_picture_url;
    $hasPhoto = ! str_starts_with($avatarUrl, 'https://ui-avatars.com/');
    $usesLinePhoto = $hasPhoto && $user->line_picture_url && $avatarUrl === $user->line_picture_url;
    $pwErrorKeys = ['current_password', 'new_password', 'turnstile'];
    $trainingBadges = array_values(array_filter(\App\Services\Classroom\TrainingBadge::allFor($user)));

    // 🐛 (2026-10-29) date_of_birth cast เป็น date → ค่าเป็น "Y-m-d H:i:s" ซึ่ง input type=date ไม่รับ = ช่องว่าง
    //    กดบันทึกเรื่องอื่น วันเกิดถูกล้างเป็น null ทุกครั้ง → ส่งเป็น Y-m-d เสมอ
    $dob = old('date_of_birth', $user->date_of_birth?->format('Y-m-d'));
    $gender = old('gender', $user->gender);
    $genders = ['male' => 'ชาย', 'female' => 'หญิง', 'other' => 'อื่นๆ', 'prefer_not_to_say' => 'ไม่ระบุ'];

    // ความครบถ้วนของโปรไฟล์ (ชวนกรอกให้ครบ — ไม่ได้บังคับ)
    $checks = [
        ['อีเมลจริงที่ยืนยันแล้ว', $emailIsReal && $user->email_verified_at, '#pf-email'],
        ['เบอร์โทรศัพท์', filled($user->phone), '#pf-phone'],
        ['วันเกิด', (bool) $user->date_of_birth, '#pf-dob'],
        ['รูปโปรไฟล์', $hasPhoto, '#nvm-pf-avatar'],
        ['ที่อยู่ออกใบเสร็จ', filled($user->address) && filled($user->state), '#pf-address'],
        ['ยืนยันตัวตน (KYC)', $kycVerified, route('user.kyc.index')],
    ];
    $done = count(array_filter(array_column($checks, 1)));
    $pct = (int) round($done / count($checks) * 100);

    // 🔒 เลขบัตรปิดตรงกลาง — เจ้าของจำได้จากต้น/ท้าย คนแอบมองจอหรือ session ที่หลุดเห็นไม่ครบ
    $idDigits = preg_replace('/\D/', '', (string) $user->id_card_number);
    $idMasked = strlen($idDigits) === 13
        ? $idDigits[0].'-'.substr($idDigits, 1, 1).'xxx-xxxxx-'.substr($idDigits, 10, 2).'-'.$idDigits[12]
        : null;
    $thDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->locale('th')->translatedFormat('j F Y') : null;
    $idExpired = $user->id_card_expiry_date && \Carbon\Carbon::parse($user->id_card_expiry_date)->isPast();
@endphp

@section('content')

{{-- ขอบเขต Alpine ครอบทั้งหน้า: ปุ่มเปลี่ยนรูปอยู่บนฮีโร่ แต่ส่งไปกับฟอร์มหลัก (input ผูก form="nvm-pf-form") --}}
<div class="nvm-pf-page"
     x-data="nvmProfile(@js($hasPhoto ? $avatarUrl : $avatarArt), @js($avatarArt), @js($errors->any() && ! $errors->hasAny($pwErrorKeys)))"
     @pageshow.window="busy = false" @beforeunload.window="warnUnsaved($event)">

<section class="nvm-ph nvm-pf-hero" aria-labelledby="nvm-pf-h">
    <div class="nvm-ph__in">
        <div class="nvm-pf-av" id="nvm-pf-avatar">
            <label class="nvm-pf-av__pick">
                <span class="nvm-pf-av__img {{ $hasPhoto ? '' : 'is-art' }}" :class="{ 'is-art': ! preview && current === fallback }">
                    <img :src="preview || current" src="{{ $hasPhoto ? $avatarUrl : $avatarArt }}" alt="รูปโปรไฟล์ของ {{ $user->name }}" width="132" height="132" referrerpolicy="no-referrer" x-on:error="current = fallback">
                </span>
                <span class="nvm-pf-av__cam" aria-hidden="true"><svg class="nvm-ico"><use href="#nvm-i-camera"/></svg></span>
                <span class="nvm-sr">เปลี่ยนรูปโปรไฟล์</span>
                <input type="file" name="profile_picture" form="nvm-pf-form" class="nvm-sr" accept="image/jpeg,image/png,image/gif,image/webp" x-ref="avatar" @change="pickAvatar($event)">
            </label>
            <button type="button" class="nvm-pf-av__undo" x-show="preview" x-cloak @click="clearAvatar()">ใช้รูปเดิม</button>
            <small x-show="! preview">{{ $usesLinePhoto ? 'รูปจาก LINE · แตะเพื่อเปลี่ยน' : 'แตะเพื่อเปลี่ยน · ไม่เกิน 5MB' }}</small>
        </div>

        <div class="nvm-ph__txt">
            <div class="nvm-eyebrow nvm-latin">My profile</div>
            <h1 id="nvm-pf-h">{{ $user->name }}</h1>
            <p class="nvm-ph__lead">จัดการโปรไฟล์ ที่อยู่ออกใบเสร็จ และความปลอดภัยของบัญชี</p>
            <div class="nvm-pills">
                @if($kycVerified)
                    <a class="nvm-pill nvm-pill--ok" href="{{ route('user.kyc.index') }}"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg>ยืนยันตัวตนแล้ว</a>
                @elseif($kycPending)
                    <a class="nvm-pill nvm-pill--warn" href="{{ route('user.kyc.index') }}"><svg class="nvm-ico"><use href="#nvm-i-clock"/></svg>KYC รอตรวจสอบ</a>
                @else
                    <a class="nvm-pill nvm-pill--bad" href="{{ route('user.kyc.index') }}"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>ยังไม่ยืนยันตัวตน</a>
                @endif
                {{-- 🎓 ป้ายผ่านการอบรม (ห้องเรียน) ข้างป้ายยืนยันตัวตน --}}
                @if(RouteFacade::has('user.classroom.index'))
                    @foreach($trainingBadges as $trainingBadge)
                        <a class="nvm-pill nvm-pill--glass" href="{{ route('user.classroom.index') }}"><x-training-badge :badge="$trainingBadge" :size="17" label /></a>
                    @endforeach
                @endif
                @if($user->line_user_id)
                    <span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-line"/></svg>LINE: {{ $user->line_display_name ?: 'เชื่อมต่อแล้ว' }}</span>
                @elseif(RouteFacade::has('line.link'))
                    <a class="nvm-pill nvm-pill--glass" href="{{ route('line.link') }}"><svg class="nvm-ico"><use href="#nvm-i-line"/></svg>เชื่อมต่อ LINE</a>
                @endif
            </div>
            @if($user->member_number)
                <div class="nvm-copy">
                    <small class="nvm-latin">Member</small>
                    <code>{{ $user->member_number }}</code>
                    <button type="button" x-data @click="nvmCopy(@js((string) $user->member_number), 'คัดลอกรหัสสมาชิกแล้ว')" aria-label="คัดลอกรหัสสมาชิก"><svg class="nvm-ico"><use href="#nvm-i-copy"/></svg></button>
                </div>
            @endif
        </div>

        <div class="nvm-pf-gauge" role="img" aria-label="โปรไฟล์ครบ {{ $pct }} เปอร์เซ็นต์">
            <svg viewBox="0 0 120 120" aria-hidden="true">
                <defs>
                    <linearGradient id="nvm-pf-gold" x1="0" y1="0" x2="1" y2="1">
                        <stop offset="0" stop-color="#fff1c7"/><stop offset=".5" stop-color="#f0c96a"/><stop offset="1" stop-color="#b8862b"/>
                    </linearGradient>
                </defs>
                <circle class="nvm-pf-gauge__track" cx="60" cy="60" r="52"/>
                @if($pct > 0)
                    <circle class="nvm-pf-gauge__val" cx="60" cy="60" r="52" pathLength="100" stroke-dasharray="{{ $pct }} 100"/>
                @endif
            </svg>
            <div class="nvm-pf-gauge__txt">
                <b class="nvm-foil nvm-num">{{ $pct }}<span>%</span></b>
                <small>ครบ {{ $done }}/{{ count($checks) }}</small>
            </div>
        </div>
    </div>
</section>

@if($errors->any() && ! $errors->hasAny($pwErrorKeys))
    <section class="nvm-card nvm-alert nvm-alert--bad" role="alert">
        <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg></span>
        <div class="nvm-alert__txt">
            <b>ยังไม่ได้บันทึก ตรวจข้อมูลอีกครั้ง</b>
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    </section>
@endif

<div class="nvm-split">
    <div class="nvm-stack">
    {{-- ── ฟอร์มโปรไฟล์หลัก ───────────────────────────────────── --}}
    <form id="nvm-pf-form" method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data" class="nvm-stack"
          @input="dirty = true" @change="dirty = true" @submit="if (busy) { $event.preventDefault(); return; } busy = true">
        @csrf
        @method('PUT')

        <section class="nvm-card nvm-box" aria-labelledby="nvm-pf-me-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Personal</small><h2 id="nvm-pf-me-h">ข้อมูลส่วนตัว</h2></div></div>

                <div class="nvm-form">
                    <div class="nvm-field">
                        <label class="nvm-label" for="pf-name">ชื่อ-นามสกุล <em>*</em></label>
                        <input id="pf-name" type="text" name="name" required maxlength="255" autocomplete="name" class="nvm-input @error('name') is-bad @enderror" value="{{ old('name', $user->name) }}">
                        @error('name')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>

                    {{-- อีเมล — 🔒 เปลี่ยนอีเมลต้องยืนยันตัวตน (AccountEmailChangeService) ช่องยืนยันโผล่เมื่ออีเมลต่างจากเดิม --}}
                    <div class="nvm-field"
                         x-data="emailChangeBox({
                             current: @js((string) $user->email),
                             initial: @js((string) old('email', $user->email)),
                             mode: @js($emailChangeMode),
                             useCode: @js((bool) $errors->has('email_code') && $pwMode),
                             sendUrl: @js(route('user.profile.email.send-code')),
                         })">
                        <label class="nvm-label" for="pf-email">อีเมล <em>*</em></label>
                        <input id="pf-email" type="email" name="email" required maxlength="255" autocomplete="email" x-model="email" class="nvm-input @error('email') is-bad @enderror" value="{{ old('email', $user->email) }}">
                        @error('email')<span class="nvm-err">{{ $message }}</span>@enderror

                        {{-- สถานะยืนยันอีเมลปัจจุบัน (อีเมลระบบของบัญชี LINE/FB ไม่แสดง) --}}
                        @if($emailIsReal && ! $user->email_verified_at)
                            <span class="nvm-help nvm-pf-warn" x-show="! changed">
                                อีเมลนี้ยังไม่ได้ยืนยัน
                                <button type="submit" form="nvm-email-verify-form" class="nvm-link">ส่งลิงก์ยืนยันอีกครั้ง</button>
                            </span>
                        @elseif($emailIsReal)
                            <span class="nvm-help" x-show="! changed"><span class="nvm-chip nvm-chip--ok">ยืนยันแล้ว</span></span>
                        @else
                            <span class="nvm-help" x-show="! changed">บัญชีนี้ยังใช้อีเมลที่ระบบตั้งให้ แนะนำให้เปลี่ยนเป็นอีเมลจริงของคุณ เพื่อใช้ยืนยันตัวตนและรับข่าวสาร</span>
                        @endif

                        {{-- ช่องยืนยันตัวตนเมื่อเปลี่ยนอีเมล --}}
                        <template x-if="changed">
                            <div class="nvm-pf-verify">
                                @if($pwMode)
                                    <p><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>การเปลี่ยนอีเมลต้องยืนยันว่าเป็นเจ้าของบัญชี หลังบันทึก ระบบจะส่งลิงก์ยืนยันไปที่อีเมลใหม่ และแจ้งเตือนไปที่อีเมลเดิม</p>
                                    <div class="nvm-field" x-show="! useCode">
                                        <label class="nvm-label" for="pf-email-pw">รหัสผ่านปัจจุบัน <em>*</em></label>
                                        <input id="pf-email-pw" type="password" name="email_password" :required="! useCode" :disabled="useCode" autocomplete="current-password" maxlength="255" placeholder="ใส่รหัสผ่านปัจจุบันเพื่อยืนยัน" class="nvm-input">
                                        <button type="button" class="nvm-link nvm-pf-alt" @click="useCode = true">ไม่รู้รหัสผ่าน (สมัครด้วย Google/LINE/Facebook)? รับรหัสยืนยันทางอีเมลเดิม {{ $currentEmailMasked }}</button>
                                    </div>
                                @else
                                    <p><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>บัญชีนี้สมัครผ่าน LINE/Facebook จึงยืนยันด้วยรหัส 6 หลักที่ส่งไปที่อีเมลใหม่ — กดส่งรหัส แล้วกรอกรหัสก่อนกดบันทึก</p>
                                @endif

                                <div class="nvm-field" x-show="mode !== 'password' || useCode">
                                    <div class="nvm-filter__btns">
                                        <button type="button" class="nvm-btn nvm-btn--line nvm-btn--sm" @click="sendCode()" :disabled="sending || cooldown > 0">
                                            <svg class="nvm-ico"><use href="#nvm-i-send"/></svg>
                                            <span x-text="sending ? 'กำลังส่ง…' : (cooldown > 0 ? ('ส่งใหม่ได้ใน ' + cooldown + ' วินาที') : (sent ? 'ส่งรหัสอีกครั้ง' : @js($pwMode ? 'ส่งรหัสไปที่อีเมลเดิม' : 'ส่งรหัสไปที่อีเมลใหม่')))">{{ $pwMode ? 'ส่งรหัสไปที่อีเมลเดิม' : 'ส่งรหัสไปที่อีเมลใหม่' }}</span>
                                        </button>
                                        @if($pwMode)
                                            <button type="button" class="nvm-link" @click="useCode = false">ใช้รหัสผ่านแทน</button>
                                        @endif
                                    </div>
                                    <span class="nvm-help" :class="noticeOk ? 'nvm-pf-ok' : 'nvm-err'" x-show="notice" x-text="notice" role="status"></span>
                                    <input type="text" name="email_code" :required="mode !== 'password' || useCode" :disabled="mode === 'password' && ! useCode"
                                           inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}"
                                           placeholder="รหัส 6 หลักจากอีเมล" title="กรอกรหัสตัวเลข 6 หลักที่ได้รับทางอีเมล"
                                           class="nvm-input nvm-pin nvm-pf-code">
                                </div>

                                @foreach(['email_password', 'email_code'] as $field)
                                    @error($field)<span class="nvm-err" role="alert">{{ $message }}</span>@enderror
                                @endforeach
                            </div>
                        </template>
                    </div>

                    <div class="nvm-pf-2">
                        <div class="nvm-field">
                            <label class="nvm-label" for="pf-phone">เบอร์โทรศัพท์</label>
                            <input id="pf-phone" type="tel" name="phone" inputmode="tel" autocomplete="tel" maxlength="13" placeholder="0812345678" class="nvm-input @error('phone') is-bad @enderror" value="{{ old('phone', $user->phone) }}">
                            @if($user->phone && $user->phone_verified)
                                <span class="nvm-help"><span class="nvm-chip nvm-chip--ok">ยืนยันเบอร์แล้ว</span> เปลี่ยนเบอร์แล้วต้องยืนยันใหม่</span>
                            @endif
                            @error('phone')<span class="nvm-err">{{ $message }}</span>@enderror
                        </div>
                        <div class="nvm-field">
                            <label class="nvm-label" for="pf-dob">วันเกิด</label>
                            <input id="pf-dob" type="date" name="date_of_birth" max="{{ now()->subDay()->format('Y-m-d') }}" autocomplete="bday" class="nvm-input @error('date_of_birth') is-bad @enderror" value="{{ $dob }}">
                            @error('date_of_birth')<span class="nvm-err">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="nvm-field">
                        <span class="nvm-label" id="pf-gender-lbl">เพศ</span>
                        <div class="nvm-pf-seg" role="radiogroup" aria-labelledby="pf-gender-lbl">
                            @foreach($genders as $val => $label)
                                <label><input type="radio" name="gender" value="{{ $val }}" @checked($gender === $val)><span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                        @error('gender')<span class="nvm-err">{{ $message }}</span>@enderror
                    </div>
                </div>
        </section>

        <section class="nvm-card nvm-box" aria-labelledby="nvm-pf-addr-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Billing address</small><h2 id="nvm-pf-addr-h">ที่อยู่ออกใบเสร็จ</h2></div></div>
            <div class="nvm-form">
                <div class="nvm-field">
                    <label class="nvm-label" for="pf-address">บ้านเลขที่ หมู่ ซอย ถนน</label>
                    <textarea id="pf-address" name="address" rows="2" maxlength="500" autocomplete="street-address" placeholder="เช่น 99/1 หมู่ 3 ถ.มิตรภาพ" class="nvm-input @error('address') is-bad @enderror">{{ old('address', $user->address) }}</textarea>
                    @error('address')<span class="nvm-err">{{ $message }}</span>@enderror
                </div>
                {{-- ระบบเลือกที่อยู่ (จังหวัด → อำเภอ → รหัสไปรษณีย์) — ไม่บังคับ: ยังไม่กรอกก็บันทึกชื่อ/เบอร์ได้ --}}
                <div class="nvm-addr">
                    <x-thai-address-picker
                        province-field="state"
                        district-field="city"
                        sub-district-field=""
                        postal-code-field="postal_code"
                        country-field="country"
                        :country-value="old('country', $user->country ?? 'TH')"
                        :province-value="old('state', $user->state ?? '')"
                        :district-value="old('city', $user->city ?? '')"
                        :sub-district-value="''"
                        :postal-code-value="old('postal_code', $user->postal_code ?? '')"
                        :show-sub-district="false"
                        :required="false"
                    />
                </div>
                @foreach(['city', 'state', 'postal_code', 'country'] as $field)
                    @error($field)<span class="nvm-err">{{ $message }}</span>@enderror
                @endforeach
            </div>
        </section>

        {{-- แถบบันทึก (ติดล่างจอเมื่อมีการแก้ไข) --}}
        <div class="nvm-card nvm-pf-save" :class="dirty ? 'is-dirty' : ''">
            <span class="nvm-help" x-show="dirty" x-cloak><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>มีการเปลี่ยนแปลงที่ยังไม่บันทึก</span>
            <span class="nvm-help" x-show="! dirty">แก้ไขข้อมูลด้านบน แล้วกดบันทึก</span>
            <div class="nvm-filter__btns">
                <a class="nvm-btn nvm-btn--line nvm-btn--sm" href="{{ route('user.profile') }}" x-show="dirty" x-cloak @click="dirty = false">ยกเลิก</a>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="busy ? 'กำลังบันทึก…' : 'บันทึกโปรไฟล์'">บันทึกโปรไฟล์</span></button>
            </div>
        </div>
    </form>

    {{-- เปลี่ยนรหัสผ่าน — 🔒 ฟอร์มแยกจากโปรไฟล์ ผ่าน turnstile + 2FA (password_change) ตาม route --}}
    @php $pwErrors = collect($pwErrorKeys)->flatMap(fn ($k) => $errors->get($k)); @endphp
    <section class="nvm-card nvm-box" id="nvm-pf-pw" aria-labelledby="nvm-pf-pw-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Password</small><h2 id="nvm-pf-pw-h">เปลี่ยนรหัสผ่าน</h2></div></div>
        @if($pwErrors->isNotEmpty())
            {{-- กลับมาจากบันทึกจะอยู่บนสุดของหน้า เลื่อนมาให้เห็นข้อผิดพลาด --}}
            <div id="nvm-pw-errors" class="nvm-pf-errs" role="alert" x-init="$el.scrollIntoView({ block: 'center' })">
                @foreach($pwErrors as $msg)<span class="nvm-err">{{ $msg }}</span>@endforeach
            </div>
        @endif
        <form id="nvm-pw-form" method="POST" action="{{ route('user.profile.update-password') }}" class="nvm-form" x-data="{ show: false, sending: false }" @submit="if (sending) { $event.preventDefault(); return; } sending = true" @pageshow.window="sending = false">
            @csrf
            <div class="nvm-field">
                <label class="nvm-label" for="pw-current">รหัสผ่านปัจจุบัน</label>
                <input id="pw-current" :type="show ? 'text' : 'password'" type="password" name="current_password" required autocomplete="current-password" class="nvm-input">
            </div>
            <div class="nvm-pf-2">
                <div class="nvm-field">
                    <label class="nvm-label" for="pw-new">รหัสผ่านใหม่</label>
                    <input id="pw-new" :type="show ? 'text' : 'password'" type="password" name="new_password" required minlength="8" autocomplete="new-password" class="nvm-input">
                    <span class="nvm-help">อย่างน้อย 8 ตัว มีตัวพิมพ์ใหญ่ ตัวพิมพ์เล็ก และตัวเลข</span>
                </div>
                <div class="nvm-field">
                    <label class="nvm-label" for="pw-confirm">ยืนยันรหัสผ่านใหม่</label>
                    <input id="pw-confirm" :type="show ? 'text' : 'password'" type="password" name="new_password_confirmation" required minlength="8" autocomplete="new-password" class="nvm-input">
                </div>
            </div>
            <label class="nvm-check"><input type="checkbox" x-model="show"> แสดงรหัสผ่าน</label>
            @if($pwTurnstile)
                <div class="cf-turnstile nvm-turnstile" data-sitekey="{{ config('turnstile.site_key') }}" data-theme="{{ config('turnstile.theme') }}" data-size="{{ config('turnstile.size') }}"></div>
            @endif
            <div class="nvm-pf-pwfoot">
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="sending"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg><span x-text="sending ? 'กำลังเปลี่ยน…' : 'เปลี่ยนรหัสผ่าน'">เปลี่ยนรหัสผ่าน</span></button>
                @if(RouteFacade::has('password.request'))
                    <p class="nvm-help">สมัครด้วย LINE/Google และไม่เคยตั้งรหัสผ่าน? ใช้<a class="nvm-link" href="{{ route('password.request') }}">ลืมรหัสผ่าน</a>เพื่อตั้งรหัสใหม่</p>
                @endif
            </div>
        </form>
    </section>
    </div>

    <aside class="nvm-stack">
        {{-- สิ่งที่ยังขาด (ครบแล้วไม่ต้องแสดง — วงแหวนบนฮีโร่บอกอยู่แล้ว) --}}
        @if($done < count($checks))
            <section class="nvm-card nvm-box" aria-labelledby="nvm-pf-done-h">
                <div class="nvm-sec-h"><div><small class="nvm-latin">Checklist</small><h2 id="nvm-pf-done-h">เติมโปรไฟล์ให้ครบ</h2></div></div>
                <ul class="nvm-pf-list">
                    @foreach($checks as [$label, $ok, $href])
                        <li class="{{ $ok ? 'is-ok' : '' }}">
                            @if($ok)
                                <span><i><svg class="nvm-ico"><use href="#nvm-i-check"/></svg></i>{{ $label }}</span>
                            @else
                                <a href="{{ $href }}"><i></i>{{ $label }}<svg class="nvm-ico" aria-hidden="true"><use href="#nvm-i-chev"/></svg></a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- ยืนยันตัวตน --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-pf-kyc-h">
            <div class="nvm-sec-h">
                <div><small class="nvm-latin">Identity</small><h2 id="nvm-pf-kyc-h">ข้อมูลบัตรประชาชน</h2></div>
                <a href="{{ route('user.kyc.index') }}">KYC<svg class="nvm-ico"><use href="#nvm-i-chev"/></svg></a>
            </div>
            @if($kycVerified && $idMasked)
                <dl class="nvm-kv">
                    <dt>เลขบัตร</dt><dd class="nvm-num">{{ $idMasked }}</dd>
                    @if($user->thai_first_name || $user->thai_last_name)<dt>ชื่อ (ไทย)</dt><dd>{{ trim($user->thai_first_name.' '.$user->thai_last_name) }}</dd>@endif
                    @if($user->english_first_name || $user->english_last_name)<dt>ชื่อ (อังกฤษ)</dt><dd>{{ trim($user->english_first_name.' '.$user->english_last_name) }}</dd>@endif
                    @if($user->id_card_birth_date)<dt>วันเกิด</dt><dd>{{ $thDate($user->id_card_birth_date) }}</dd>@endif
                    @if($user->id_card_expiry_date)<dt>บัตรหมดอายุ</dt><dd class="{{ $idExpired ? 'nvm-pf-bad' : '' }}">{{ $thDate($user->id_card_expiry_date) }}@if($idExpired) (หมดอายุแล้ว)@endif</dd>@endif
                </dl>
                <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg><span>ข้อมูลจากบัตรแก้เองไม่ได้ ถ้าไม่ถูกต้องให้ติดต่อทีมงานผ่าน<a class="nvm-link" href="{{ route('user.tickets.create') }}">ตั๋วช่วยเหลือ</a></span></p>
            @elseif($kycVerified)
                <p class="nvm-desc">ยืนยันตัวตนเรียบร้อยแล้ว ใช้งานถอนเงินและทุกฟีเจอร์ได้เต็มที่</p>
            @elseif($kycPending)
                <p class="nvm-desc">ทีมงานกำลังตรวจเอกสารของคุณ ปกติใช้เวลา 1-3 วันทำการ</p>
                <a class="nvm-btn nvm-btn--line nvm-btn--sm nvm-gap-t" href="{{ route('user.kyc.index') }}">ดูสถานะ</a>
            @else
                <img class="nvm-pf-kyc-art" src="{{ $nvArt('idcard', 'deco/scroll') }}" alt="" width="96" height="96" loading="lazy">
                <p class="nvm-desc">ยืนยันตัวตนด้วยบัตรประชาชน เพื่อถอนเงินและใช้งานได้ครบทุกฟีเจอร์</p>
                <a class="nvm-btn nvm-btn--gold nvm-btn--sm nvm-gap-t" href="{{ route('user.kyc.create') }}"><svg class="nvm-ico"><use href="#nvm-i-id"/></svg>เริ่มยืนยันตัวตน</a>
            @endif
        </section>

        {{-- ลิงก์ที่เกี่ยวข้อง --}}
        <section class="nvm-card nvm-box" aria-labelledby="nvm-pf-more-h">
            <div class="nvm-sec-h"><div><small class="nvm-latin">More settings</small><h2 id="nvm-pf-more-h">ตั้งค่าอื่นๆ</h2></div></div>
            <div class="nvm-txs">
                @if(RouteFacade::has('shipping-addresses.index'))
                    <a class="nvm-tx nvm-pf-go" href="{{ route('shipping-addresses.index') }}">
                        <span class="nvm-tx__ic" aria-hidden="true"><svg class="nvm-ico"><use href="#nvm-i-pin"/></svg></span>
                        <span class="nvm-tx__t"><b>ที่อยู่จัดส่ง</b><small>เพิ่ม แก้ไข และตั้งค่าที่อยู่เริ่มต้น</small></span>
                        <svg class="nvm-ico" aria-hidden="true"><use href="#nvm-i-chev"/></svg>
                    </a>
                @endif
                <a class="nvm-tx nvm-pf-go" href="{{ route('user.two-factor.setup') }}">
                    <span class="nvm-tx__ic" aria-hidden="true"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg></span>
                    <span class="nvm-tx__t"><b>ความปลอดภัย 2 ชั้น (2FA)</b><small>ยืนยันด้วยรหัสจากแอปก่อนถอน/โอนเงิน</small></span>
                    <svg class="nvm-ico" aria-hidden="true"><use href="#nvm-i-chev"/></svg>
                </a>
                <a class="nvm-tx nvm-pf-go" href="{{ route('user.email.preferences') }}">
                    <span class="nvm-tx__ic" aria-hidden="true"><svg class="nvm-ico"><use href="#nvm-i-bell"/></svg></span>
                    <span class="nvm-tx__t"><b>การแจ้งเตือนทางอีเมล</b><small>เลือกเรื่องที่อยากรับทางอีเมล</small></span>
                    <svg class="nvm-ico" aria-hidden="true"><use href="#nvm-i-chev"/></svg>
                </a>
                @if(RouteFacade::has('account.delete'))
                    <a class="nvm-tx nvm-pf-go nvm-pf-go--bad" href="{{ route('account.delete') }}">
                        <span class="nvm-tx__ic" aria-hidden="true"><svg class="nvm-ico"><use href="#nvm-i-x"/></svg></span>
                        <span class="nvm-tx__t"><b>ลบบัญชี</b><small>ลบข้อมูลส่วนตัวและปิดบัญชีถาวร</small></span>
                        <svg class="nvm-ico" aria-hidden="true"><use href="#nvm-i-chev"/></svg>
                    </a>
                @endif
            </div>
        </section>
    </aside>
</div>

{{-- ส่งลิงก์ยืนยันอีเมลซ้ำ (ปุ่มอยู่ใต้ช่องอีเมล — ฟอร์มแยกจากฟอร์มโปรไฟล์) --}}
<form id="nvm-email-verify-form" method="POST" action="{{ route('user.profile.email.resend-verification') }}">
    @csrf
</form>

</div>
@endsection

@push('scripts')
@if($pwTurnstile)
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<script>
document.addEventListener('alpine:init', () => {
    /**
     * ฟอร์มโปรไฟล์ — พรีวิวรูป + เตือนก่อนออกจากหน้าเมื่อยังไม่บันทึก
     */
    window.Alpine.data('nvmProfile', (current, fallback, hasUnsaved) => ({
        current: current || fallback,
        fallback,
        preview: null,
        dirty: !! hasUnsaved,
        busy: false,

        pickAvatar(e) {
            const file = e.target.files[0];
            if (! file) return;
            if (! ['image/jpeg', 'image/png', 'image/gif', 'image/webp'].includes(file.type)) {
                window.showNotification('เลือกได้เฉพาะรูป JPG PNG GIF หรือ WebP', 'error');
                return this.clearAvatar();
            }
            if (file.size > 5 * 1024 * 1024) {
                window.showNotification('รูปใหญ่เกิน 5MB ลองย่อรูปหรือเลือกรูปอื่น', 'error');
                return this.clearAvatar();
            }
            const reader = new FileReader();
            reader.onload = (ev) => { this.preview = ev.target.result; };
            reader.readAsDataURL(file);
            this.dirty = true;
        },

        clearAvatar() {
            this.preview = null;
            this.$refs.avatar.value = '';
        },

        // ออกจากหน้า (ปิดแท็บ/กดลิงก์) ทั้งที่ยังไม่บันทึก → เบราว์เซอร์ถามก่อน
        warnUnsaved(e) {
            if (this.dirty && ! this.busy) {
                e.preventDefault();
                e.returnValue = '';
            }
        },
    }));

    /**
     * 🔒 ช่องยืนยันตัวตนเมื่อเปลี่ยนอีเมล — โผล่เมื่ออีเมลที่พิมพ์ต่างจากอีเมลปัจจุบัน
     *
     * mode 'password'       : รหัสผ่านปัจจุบัน หรือ (useCode) รหัส 6 หลักที่ส่งไปอีเมลเดิม
     * mode 'new_email_code' : รหัส 6 หลักที่ส่งไปอีเมลใหม่ (บัญชี LINE/Facebook ที่ระบบตั้งอีเมลให้)
     */
    window.Alpine.data('emailChangeBox', (cfg) => ({
        email: cfg.initial || '',
        current: cfg.current || '',
        mode: cfg.mode,
        useCode: !! cfg.useCode,
        sending: false,
        sent: false,
        notice: '',
        noticeOk: true,
        cooldown: 0,
        timer: null,

        get changed() {
            const typed = this.email.trim().toLowerCase();
            return typed !== '' && typed !== this.current.trim().toLowerCase();
        },

        init() {
            // รหัสผูกกับอีเมลใหม่ที่ขอ — แก้อีเมลหลังส่งรหัสแล้ว ต้องขอรหัสใหม่
            this.$watch('email', () => { this.sent = false; this.notice = ''; });
        },

        destroy() {
            clearInterval(this.timer);
        },

        startCooldown(seconds) {
            clearInterval(this.timer);
            this.cooldown = Math.max(1, parseInt(seconds, 10) || 60);
            this.timer = setInterval(() => {
                this.cooldown--;
                if (this.cooldown <= 0) { clearInterval(this.timer); this.cooldown = 0; }
            }, 1000);
        },

        async sendCode() {
            if (this.sending || this.cooldown > 0) return;
            this.sending = true;
            this.notice = '';

            try {
                const res = await fetch(cfg.sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ new_email: this.email.trim() }),
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok && data.success) {
                    this.sent = true;
                    this.noticeOk = true;
                    this.notice = data.message;
                    this.startCooldown(60);
                    return;
                }

                this.noticeOk = false;
                const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                if (res.status === 419) {
                    this.notice = 'หน้านี้เปิดค้างนานเกินไป กรุณารีเฟรชหน้าแล้วลองใหม่';
                } else if (res.status === 429 && data.success === undefined) {
                    // ชน throttle ของ route (ข้อความเดิมเป็นภาษาอังกฤษ)
                    this.notice = 'ขอรหัสบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่';
                } else {
                    this.notice = firstError || data.message || 'ส่งรหัสไม่สำเร็จ กรุณาลองใหม่';
                }
                if (data.retry_after) this.startCooldown(data.retry_after);
            } catch (e) {
                this.noticeOk = false;
                this.notice = 'เชื่อมต่อไม่สำเร็จ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่';
            } finally {
                this.sending = false;
            }
        },
    }));
});
</script>
@endpush
