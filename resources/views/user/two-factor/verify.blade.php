{{--
    ยืนยันตัวตน 2 ชั้นก่อนทำรายการสำคัญ (ธีมโนวาเต็ม)
    มาจาก RequireTwoFactor / TwoFactorController::requireFreshVerification — action บอกว่ากำลังจะทำอะไร, redirect ผ่าน safeRedirect แล้ว
    ส่งรหัสผ่าน fetch → verify-code / verify-recovery-code (ตอบ JSON, ผิด = 422, ถี่เกิน = 429)
--}}
@extends('layouts.user-nova')

@section('title', 'ยืนยันตัวตน 2 ชั้น')

@php
    $nvArt = fn (string $name, string $fallback) => is_file(public_path("images/nova/account/{$name}.webp"))
        ? asset("images/nova/account/{$name}.webp") : asset("images/nova/{$fallback}.webp");
    $action = (string) ($action ?? 'general');
    // ชื่อรายการที่กำลังจะทำ — ให้ผู้ใช้รู้ว่ารหัสนี้ใช้ยืนยันอะไร (กันหลงกรอกให้หน้าปลอม)
    $purpose = match (true) {
        $action === 'withdrawal' => 'ถอนเงิน',
        $action === 'transfer' => 'โอนเงิน',
        $action === 'payment_method_change' => 'เปลี่ยนบัญชีรับเงิน',
        $action === 'password_change' => 'เปลี่ยนรหัสผ่าน',
        $action === 'profile_change' => 'แก้ไขข้อมูลส่วนตัว',
        $action === 'manage_2fa' => 'เปลี่ยนการตั้งค่า 2FA',
        str_starts_with($action, 'nfc') => 'จัดการบัตร NFC',
        default => null,
    };
    // ปุ่มย้อนกลับ: หน้าก่อนหน้าในเว็บนี้เท่านั้น (referer จากเว็บอื่น/หน้านี้เอง → หน้าแรก)
    $back = url()->previous();
    if (parse_url($back, PHP_URL_HOST) !== request()->getHost() || str_starts_with($back, url()->current())) {
        $back = route('user.home');
    }
@endphp

@section('content')

<section class="nvm-ph" aria-labelledby="nvm-2fv-h">
    <div class="nvm-ph__in">
        <div class="nvm-ph__txt">
            <a class="nvm-ph__back" href="{{ $back }}"><svg class="nvm-ico"><use href="#nvm-i-arrow"/></svg>ย้อนกลับ</a>
            <div class="nvm-eyebrow nvm-latin">Verify it's you</div>
            <h1 id="nvm-2fv-h">ยืนยันตัวตน</h1>
            <p class="nvm-ph__lead">{{ $purpose ? 'ก่อน'.$purpose.' ' : '' }}กรอกรหัส 6 หลักจากแอป Authenticator ในมือถือของคุณ</p>
            @if($purpose)
                <div class="nvm-pills"><span class="nvm-pill nvm-pill--glass"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>รายการ: {{ $purpose }}</span></div>
            @endif
        </div>
        <img class="nvm-ph__art" src="{{ $nvArt('shield', 'deco/crystal') }}" alt="" width="200" height="200">
    </div>
</section>

<div class="nvm-split" x-data="nvm2faVerify">
    <section class="nvm-card nvm-box nvm-2fv" aria-labelledby="nvm-2fv-form-h">
        {{-- รหัสจากแอป --}}
        <form x-show="mode === 'code'" @submit.prevent="send()" class="nvm-form" x-ref="codeForm">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Authenticator code</small><h2 id="nvm-2fv-form-h">รหัสจากแอป</h2></div></div>
            <div class="nvm-field">
                <label class="nvm-sr" for="nvm-2fv-code">รหัส 6 หลักจากแอป Authenticator</label>
                <input id="nvm-2fv-code" x-ref="code" type="text" x-model="code" @input="code = code.replace(/\D/g, '').slice(0, 6); if (code.length === 6) send()"
                       inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" required autofocus placeholder="000000"
                       class="nvm-input nvm-otp" :class="err ? 'is-bad' : ''" :disabled="busy" aria-describedby="nvm-2fv-help">
                <span class="nvm-help" id="nvm-2fv-help">ในแอปมองหาชื่อ {{ config('app.name', 'Thaiprompt') }} — รหัสเปลี่ยนทุก 30 วินาที ถ้าใกล้หมดเวลา รอรหัสใหม่</span>
                <span class="nvm-err" x-show="err" x-cloak x-text="err" role="alert"></span>
            </div>
            @if($rememberDays)
                <label class="nvm-check">
                    <input type="checkbox" x-model="remember">
                    <span>จำอุปกรณ์นี้ {{ $rememberDays }} วัน (เฉพาะเครื่องส่วนตัว)</span>
                </label>
            @endif
            <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy || code.length !== 6">
                <svg class="nvm-ico"><use href="#nvm-i-check"/></svg><span x-text="busy ? 'กำลังตรวจรหัส…' : 'ยืนยัน'">ยืนยัน</span>
            </button>
            <button type="button" class="nvm-2fv__switch" @click="switchTo('recovery')">ใช้มือถือไม่ได้? ใช้รหัสกู้คืนแทน</button>
        </form>

        {{-- รหัสกู้คืน --}}
        <form x-show="mode === 'recovery'" x-cloak @submit.prevent="sendRecovery()" class="nvm-form">
            <div class="nvm-sec-h"><div><small class="nvm-latin">Recovery code</small><h2>ใช้รหัสกู้คืน</h2></div></div>
            <p class="nvm-desc">รหัสที่ได้ตอนเปิด 2FA (เช่น AB12-CD34) แต่ละรหัสใช้ได้ครั้งเดียว ใช้แล้วควรสร้างชุดใหม่</p>
            <div class="nvm-field">
                <label class="nvm-label" for="nvm-2fv-rec">รหัสกู้คืน</label>
                <input id="nvm-2fv-rec" x-ref="rec" type="text" x-model="rec" required autocomplete="off" autocapitalize="characters" spellcheck="false"
                       class="nvm-input nvm-otp nvm-otp--rec" :class="err ? 'is-bad' : ''" :disabled="busy" placeholder="XXXX-XXXX">
                <span class="nvm-err" x-show="err" x-cloak x-text="err" role="alert"></span>
            </div>
            <div class="nvm-card nvm-alert nvm-alert--ok" x-show="note" x-cloak role="status">
                <span class="nvm-alert__ic"><svg class="nvm-ico"><use href="#nvm-i-check"/></svg></span>
                <div class="nvm-alert__txt"><b>ยืนยันสำเร็จ</b><p x-text="note"></p></div>
                <a class="nvm-btn nvm-btn--gold nvm-btn--sm" :href="next">ไปต่อ</a>
            </div>
            <div class="nvm-filter__btns" x-show="! note">
                <button type="button" class="nvm-btn nvm-btn--line" @click="switchTo('code')" :disabled="busy">กลับไปใช้รหัสจากแอป</button>
                <button type="submit" class="nvm-btn nvm-btn--gold" :disabled="busy || ! rec.trim()"><span x-text="busy ? 'กำลังตรวจ…' : 'ยืนยันด้วยรหัสกู้คืน'">ยืนยันด้วยรหัสกู้คืน</span></button>
            </div>
        </form>
    </section>

    <aside class="nvm-card nvm-box" aria-labelledby="nvm-2fv-help-h">
        <div class="nvm-sec-h"><div><small class="nvm-latin">Need help</small><h2 id="nvm-2fv-help-h">หารหัสไม่เจอ?</h2></div></div>
        <ol class="nvm-steps">
            <li>เปิดแอป Google Authenticator หรือ Microsoft Authenticator ในมือถือที่ใช้ตอนเปิด 2FA</li>
            <li>รหัสขึ้นว่าผิดทั้งที่พิมพ์ถูก — ตั้งเวลามือถือเป็น "อัตโนมัติ" แล้วลองใหม่</li>
            <li>มือถือหายหรือลบแอปไปแล้ว ใช้รหัสกู้คืนที่จดไว้แทน</li>
            <li>ไม่มีทั้งสองอย่าง แจ้งทีมงานเพื่อยืนยันตัวตนด้วยวิธีอื่น</li>
        </ol>
        <a class="nvm-btn nvm-btn--line nvm-btn--sm nvm-gap-t" href="{{ route('user.tickets.create') }}"><svg class="nvm-ico"><use href="#nvm-i-info"/></svg>ติดต่อทีมงาน</a>
        <p class="nvm-note"><svg class="nvm-ico"><use href="#nvm-i-lock"/></svg>ทีมงานไม่เคยขอรหัส 6 หลักหรือรหัสกู้คืนของคุณ ทั้งทางแชต โทรศัพท์ หรืออีเมล</p>
    </aside>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', function () {
        var csrf = function () { var m = document.querySelector('meta[name="csrf-token"]'); return m ? m.content : ''; };
        var post = async function (url, body) {
            var res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                body: JSON.stringify(body),
            });
            return { res: res, data: await res.json().catch(function () { return null; }) };
        };
        var errText = function (r, fallback) {
            if (r.res.status === 429) return 'ลองผิดหลายครั้งเกินไป รอ 1 นาทีแล้วลองใหม่';
            if (r.res.status === 419) return 'หน้านี้เปิดค้างนานเกินไป รีเฟรชหน้าแล้วลองใหม่';
            if (r.res.status === 422 && r.data && r.data.errors) return fallback;
            return (r.data && r.data.message) || fallback;
        };

        window.Alpine.data('nvm2faVerify', function () {
            return {
                mode: 'code', code: '', rec: '', remember: false, busy: false, err: '', note: '', next: @js($redirect),
                action: @js($action),
                switchTo(m) {
                    this.mode = m; this.err = '';
                    this.$nextTick(() => (m === 'code' ? this.$refs.code : this.$refs.rec).focus());
                },
                async send() {
                    if (this.busy || this.code.length !== 6) return;
                    this.busy = true; this.err = '';
                    try {
                        var r = await post(@js(route('user.two-factor.verify-code')), { code: this.code, action: this.action, remember_device: this.remember, redirect: this.next });
                        if (r.res.ok && r.data && r.data.success) { window.location.href = r.data.redirect || this.next; return; }
                        this.err = errText(r, 'รหัสไม่ถูกต้อง ลองรหัสใหม่จากแอป');
                    } catch (e) {
                        this.err = 'เชื่อมต่อไม่ได้ ตรวจอินเทอร์เน็ตแล้วลองใหม่';
                    }
                    this.code = ''; this.busy = false;
                    this.$nextTick(() => this.$refs.code.focus());
                },
                async sendRecovery() {
                    if (this.busy || ! this.rec.trim()) return;
                    this.busy = true; this.err = '';
                    try {
                        var r = await post(@js(route('user.two-factor.verify-recovery-code')), { recovery_code: this.rec.trim(), action: this.action, redirect: this.next });
                        if (r.res.ok && r.data && r.data.success) {
                            this.next = r.data.redirect || this.next;
                            // บอกว่ารหัสนี้ใช้ไปแล้ว ก่อนพาไปต่อ (ไม่ใช้ alert ที่บล็อกหน้า)
                            if (r.data.warning) { this.note = r.data.warning + ' — ควรสร้างรหัสกู้คืนชุดใหม่ในหน้าตั้งค่า 2FA'; this.busy = false; return; }
                            window.location.href = this.next; return;
                        }
                        this.err = errText(r, 'รหัสกู้คืนไม่ถูกต้อง');
                    } catch (e) {
                        this.err = 'เชื่อมต่อไม่ได้ ตรวจอินเทอร์เน็ตแล้วลองใหม่';
                    }
                    this.busy = false;
                },
            };
        });
    });
</script>
@endpush
