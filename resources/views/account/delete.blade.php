{{--
 | ลบบัญชี (PLAY-05) — หน้าสาธารณะ ใช้เป็น "Delete account URL" ใน Google Play Console
 | ตัวแปร: $user|null, $blockers (array{code,message}[]), $confirmMethod (password|email_code|two_factor|add_email|null),
 |         $canUseEmailCode (bool), $emailMasked|null, $confirmText ('ลบบัญชี'), $supportEmail, $deletedRef|null
 | ฟอร์ม POST → route('account.delete.destroy') ช่อง confirm_text + password | email_code | two_factor_code
 | 🔒 (2026-10-29) ทุกบัญชีต้องยืนยันตัวตนก่อนลบ (เดิมบัญชี LINE/FB กดลบได้ทันที) — ดู AccountDeletionService::confirmationMethod
 --}}
@extends('layouts.frontend-v4')

@section('title', 'ลบบัญชีผู้ใช้')
@section('meta_description', 'วิธีลบบัญชีไทยพร๊อมท์ และข้อมูลที่จะถูกลบเมื่อลบบัญชี')

@section('content')
{{-- แถบหัว/ท้ายสาธารณะ (เปิด shop.nova_public = ธีมโนวา) — หน้านี้เดิมไม่มีเมนูให้กลับไปหน้าอื่น --}}
<x-theme-v4.public-header />
<div style="max-width:820px; width:100%; margin:0 auto; padding:40px 16px 60px; display:flex; flex-direction:column; gap:18px;">

    <div style="text-align:center;">
        <div class="tp-tile" style="width:64px; height:64px; border-radius:20px; font-size:30px; margin:0 auto 14px;">🗑️</div>
        <h1 style="font-size:clamp(24px,4vw,32px); font-weight:800; color:var(--ink); margin:0;">ลบบัญชีไทยพร๊อมท์</h1>
        <p class="tp-muted" style="margin:8px 0 0; font-size:14px;">คุณขอลบบัญชีและข้อมูลส่วนบุคคลได้ทุกเมื่อ ตามพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล (PDPA)</p>
    </div>

    @if($deletedRef)
        <div class="tp-card" style="padding:22px 24px; border-left:4px solid #5aa07e;">
            <div style="font-weight:800; color:var(--ink); font-size:1.05rem;">✅ ลบบัญชีเรียบร้อยแล้ว</div>
            <p style="margin:8px 0 0; color:var(--ink2); font-size:14px; line-height:1.7;">
                ข้อมูลส่วนบุคคลของคุณถูกลบ/ปกปิดแล้ว และออกจากระบบทุกอุปกรณ์เรียบร้อย
                เลขอ้างอิง: <strong style="color:var(--ink);">{{ $deletedRef }}</strong>
                (เก็บไว้ใช้อ้างอิงหากติดต่อทีมงาน)
            </p>
        </div>
    @endif

    {{-- ── วิธีลบบัญชี ── --}}
    <div class="tp-card" style="padding:22px 24px;">
        <div class="tp-section-h" style="margin-bottom:12px;">วิธีลบบัญชี</div>
        <ol style="margin:0; padding-left:20px; color:var(--ink2); font-size:14px; line-height:1.9;">
            <li><strong style="color:var(--ink);">ในแอปไทยพร๊อมท์</strong> — ไปที่ ตั้งค่า → ลบบัญชี แล้วยืนยัน</li>
            <li><strong style="color:var(--ink);">บนเว็บไซต์</strong> — เข้าสู่ระบบ แล้วใช้แบบฟอร์มด้านล่างของหน้านี้</li>
            <li><strong style="color:var(--ink);">ทางอีเมล</strong> — กรณีเข้าสู่ระบบไม่ได้ ส่งอีเมลจากอีเมลที่ใช้สมัครไปที่
                <a href="mailto:{{ $supportEmail }}?subject={{ rawurlencode('ขอลบบัญชีไทยพร๊อมท์') }}" style="color:var(--deep1); font-weight:600;">{{ $supportEmail }}</a>
                หัวข้อ "ขอลบบัญชี" ทีมงานจะยืนยันตัวตนและดำเนินการภายใน 30 วัน</li>
        </ol>
    </div>

    {{-- ── ข้อมูลที่ลบ / ที่ต้องเก็บ ── --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:14px;">
        <div class="tp-card" style="padding:20px 22px;">
            <div class="tp-section-h" style="margin-bottom:10px;">ข้อมูลที่จะถูกลบหรือปกปิดทันที</div>
            <ul style="margin:0; padding-left:18px; color:var(--ink2); font-size:13.5px; line-height:1.85;">
                <li>ชื่อ อีเมล เบอร์โทร รูปโปรไฟล์ ที่อยู่จัดส่ง</li>
                <li>ข้อมูลบัตรประชาชน/เอกสารยืนยันตัวตน (KYC) และเอกสารไรเดอร์</li>
                <li>ข้อมูลบัญชีธนาคารสำหรับรับเงิน</li>
                <li>การเชื่อมต่อ LINE / Facebook</li>
                <li>การแจ้งเตือน และ push token ของอุปกรณ์</li>
                <li>ร้านค้า/ร้านตลาดสด/โปรไฟล์ไรเดอร์ จะถูกปิดถาวร</li>
            </ul>
        </div>
        <div class="tp-card" style="padding:20px 22px;">
            <div class="tp-section-h" style="margin-bottom:10px;">ข้อมูลที่ต้องเก็บตามกฎหมาย</div>
            <ul style="margin:0; padding-left:18px; color:var(--ink2); font-size:13.5px; line-height:1.85;">
                <li>ประวัติธุรกรรมการเงิน คำสั่งซื้อ และใบกำกับภาษี เก็บ 5 ปีตามกฎหมายบัญชี/ภาษี
                    โดยไม่ผูกกับชื่อหรือช่องทางติดต่อของคุณอีกต่อไป</li>
                <li>บันทึกความปลอดภัยของระบบ (log) เก็บไม่เกิน 90 วัน</li>
            </ul>
            <p style="margin:10px 0 0; font-size:12.5px; color:var(--ink2);">การลบบัญชีย้อนกลับไม่ได้ ต้องสมัครใหม่หากต้องการใช้งานอีกครั้ง</p>
        </div>
    </div>

    {{-- ── เงื่อนไขก่อนลบ ── --}}
    <div class="tp-card" style="padding:20px 22px;">
        <div class="tp-section-h" style="margin-bottom:10px;">ก่อนลบบัญชี</div>
        <ul style="margin:0; padding-left:18px; color:var(--ink2); font-size:13.5px; line-height:1.85;">
            <li>ยอดเงินในกระเป๋าต้องเป็น 0 บาท (ถอนเงินออกก่อน) และไม่มีคำขอถอนเงินหรือรายได้ที่รอโอน</li>
            <li>ไม่มีคำสั่งซื้อ งานส่งของ (ไรเดอร์) หรือคำสั่งซื้อตลาดสดที่ยังไม่เสร็จ</li>
            <li>ไม่มีหนี้ค้างชำระกับระบบ</li>
        </ul>
    </div>

    {{-- ── ฟอร์มลบ (ต้องล็อกอิน) ── --}}
    @if($user)
        <div class="tp-card" style="padding:22px 24px;">
            <div class="tp-section-h" style="margin-bottom:6px;">ลบบัญชีของฉัน</div>
            <p style="margin:0 0 14px; font-size:13.5px; color:var(--ink2);">
                กำลังใช้งานในชื่อ <strong style="color:var(--ink);">{{ $user->name }}</strong>
            </p>

            @if(session('error'))
                <div style="padding:12px 14px; border-radius:12px; margin-bottom:12px; background:color-mix(in srgb, #d9534f 12%, transparent); color:#b43c38; font-size:13.5px;">
                    {{ session('error') }}
                </div>
            @endif

            @if(!empty($blockers))
                <div style="padding:14px 16px; border-radius:12px; background:color-mix(in srgb, #e08a3c 14%, transparent); color:var(--ink); font-size:13.5px; line-height:1.7;">
                    <div style="font-weight:700; margin-bottom:6px;">ยังลบบัญชีไม่ได้ในตอนนี้ เพราะ:</div>
                    <ul style="margin:0; padding-left:18px;">
                        @foreach($blockers as $blocker)
                            <li>{{ $blocker['message'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @elseif($confirmMethod === \App\Services\AccountDeletionService::METHOD_ADD_EMAIL)
                {{-- ไม่มีอีเมลจริงและไม่ได้เปิด 2FA → ยังยืนยันตัวตนไม่ได้ ต้องเพิ่มอีเมลก่อน --}}
                <div style="padding:14px 16px; border-radius:12px; background:color-mix(in srgb, #e08a3c 14%, transparent); color:var(--ink); font-size:13.5px; line-height:1.75;">
                    <div style="font-weight:700; margin-bottom:6px;">ต้องเพิ่มอีเมลจริงก่อนลบบัญชี</div>
                    บัญชีนี้สมัครผ่าน LINE/Facebook และยังไม่มีอีเมลจริงไว้ยืนยันตัวตน
                    เพื่อไม่ให้ผู้อื่นที่เข้าถึงเครื่องของคุณลบบัญชีได้ กรุณาเพิ่มอีเมลของคุณที่หน้าโปรไฟล์
                    แล้วกลับมาที่หน้านี้ ระบบจะส่งรหัสยืนยันการลบไปที่อีเมลนั้น
                    <div style="margin-top:6px;">หรือติดต่อทีมงานที่ <a href="mailto:{{ $supportEmail }}" style="color:var(--deep1);">{{ $supportEmail }}</a></div>
                </div>
                <div style="margin-top:14px;">
                    <a href="{{ route('user.profile') }}" class="tp-btn tp-btn-primary" style="text-decoration:none; min-height:44px;">
                        <i class="fas fa-envelope"></i> <span>ไปเพิ่มอีเมลที่หน้าโปรไฟล์</span>
                    </a>
                </div>
            @else
                @php
                    // ส่งฟอร์มแล้วผิด → เปิดวิธีเดิมที่ลูกค้าเลือกค้างไว้ (ไม่เด้งกลับไปวิธีหลัก)
                    $initialProof = match (true) {
                        $errors->has('email_code') && $canUseEmailCode => \App\Services\AccountDeletionService::METHOD_EMAIL_CODE,
                        $errors->has('password') => \App\Services\AccountDeletionService::METHOD_PASSWORD,
                        default => $confirmMethod,
                    };
                @endphp
                <form method="POST" action="{{ route('account.delete.destroy') }}"
                      style="display:flex; flex-direction:column; gap:14px;"
                      x-data="accountDeleteForm({
                          proof: @js($initialProof),
                          typed: @js((string) old('confirm_text', '')),
                          sendUrl: @js(route('account.delete.send-code')),
                          csrf: @js(csrf_token()),
                      })"
                      @submit="if (!confirm(@js('ยืนยันลบบัญชีถาวร? การลบย้อนกลับไม่ได้'))) { $event.preventDefault(); return; } busy = true">
                    @csrf

                    @if($errors->any())
                        <div style="padding:12px 14px; border-radius:12px; background:color-mix(in srgb, #d9534f 12%, transparent); color:#b43c38; font-size:13.5px;">
                            <ul style="margin:0; padding-left:18px;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <label style="display:flex; flex-direction:column; gap:6px;">
                        <span style="font-size:13px; font-weight:600; color:var(--ink);">พิมพ์คำว่า “{{ $confirmText }}” เพื่อยืนยัน</span>
                        <input type="text" name="confirm_text" class="tp-input" x-model="typed" autocomplete="off" required maxlength="50">
                    </label>

                    {{-- 🔒 ยืนยันตัวตน: รหัสผ่าน / รหัสทางอีเมล / รหัส 2FA (แสดงทีละแบบ ช่องที่ซ่อนถูก disable ไม่ส่งไปด้วย) --}}
                    <div style="padding:14px 16px; border-radius:12px; box-shadow:var(--inset-sm); display:flex; flex-direction:column; gap:10px;">
                        <div style="font-size:13px; font-weight:700; color:var(--ink);"><i class="fas fa-shield-halved" style="color:var(--deep1);"></i> ยืนยันว่าเป็นเจ้าของบัญชี</div>

                        <label x-show="proof === 'password'" style="display:flex; flex-direction:column; gap:6px;">
                            <span style="font-size:13px; font-weight:600; color:var(--ink);">รหัสผ่าน *</span>
                            <input type="password" name="password" class="tp-input" autocomplete="current-password" maxlength="255"
                                   :required="proof === 'password'" :disabled="proof !== 'password'">
                        </label>

                        @if($canUseEmailCode)
                            <div x-show="proof === 'email_code'" style="display:flex; flex-direction:column; gap:8px;">
                                <span style="font-size:13px; color:var(--ink2); line-height:1.6;">กดส่งรหัส 6 หลักไปที่อีเมล <strong style="color:var(--ink);">{{ $emailMasked }}</strong> แล้วกรอกรหัสที่ได้รับ (หมดอายุใน 10 นาที)</span>
                                <div>
                                    <button type="button" class="tp-btn" style="min-height:44px;" @click="sendCode()" :disabled="sending || cooldown > 0">
                                        <i class="fas fa-paper-plane"></i>
                                        <span x-text="sending ? 'กำลังส่ง...' : (cooldown > 0 ? ('ส่งใหม่ได้ใน ' + cooldown + ' วินาที') : (sent ? 'ส่งรหัสอีกครั้ง' : 'ส่งรหัสไปที่อีเมล'))"></span>
                                    </button>
                                </div>
                                <p x-show="notice" x-text="notice" :style="'margin:0; font-size:12.5px; color:' + (noticeOk ? '#3c8a64' : '#b43c38')"></p>
                                <input type="text" name="email_code" class="tp-input tp-num" inputmode="numeric" autocomplete="one-time-code"
                                       maxlength="6" pattern="[0-9]{6}" title="กรอกรหัสตัวเลข 6 หลักที่ได้รับทางอีเมล" placeholder="รหัส 6 หลักจากอีเมล"
                                       style="max-width:220px; letter-spacing:4px;"
                                       :required="proof === 'email_code'" :disabled="proof !== 'email_code'">
                            </div>
                        @endif

                        @if($confirmMethod === \App\Services\AccountDeletionService::METHOD_TWO_FACTOR)
                            <label x-show="proof === 'two_factor'" style="display:flex; flex-direction:column; gap:6px;">
                                <span style="font-size:13px; font-weight:600; color:var(--ink);">รหัส 6 หลักจากแอป Authenticator (หรือรหัสกู้คืน) *</span>
                                <input type="text" name="two_factor_code" class="tp-input tp-num" autocomplete="one-time-code" maxlength="50"
                                       style="max-width:260px; letter-spacing:2px;"
                                       :required="proof === 'two_factor'" :disabled="proof !== 'two_factor'">
                            </label>
                        @endif

                        {{-- สลับวิธี: บัญชีที่มีอีเมลจริงใช้ได้ทั้งรหัสผ่านและรหัสทางอีเมล --}}
                        @if($canUseEmailCode)
                            <div style="font-size:12.5px;">
                                <button type="button" x-show="proof !== 'email_code'" @click="proof = 'email_code'" style="background:none; border:0; padding:0; color:var(--deep1); cursor:pointer; text-decoration:underline;">ไม่รู้รหัสผ่าน (สมัครด้วย Google/LINE/Facebook)? ยืนยันด้วยรหัสทางอีเมลแทน</button>
                                <button type="button" x-show="proof === 'email_code'" @click="proof = 'password'" style="background:none; border:0; padding:0; color:var(--deep1); cursor:pointer; text-decoration:underline;">ยืนยันด้วยรหัสผ่านแทน</button>
                            </div>
                        @endif
                    </div>

                    <div>
                        <button type="submit" class="tp-btn" style="min-height:44px; background:#d9534f; border-color:#d9534f; color:#fff;"
                                :disabled="busy || typed.trim() !== @js($confirmText)">
                            <i class="fas fa-trash"></i> <span x-text="busy ? 'กำลังลบ...' : 'ลบบัญชีถาวร'"></span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @elseif(! $deletedRef)
        <div class="tp-card" style="padding:22px 24px; text-align:center;">
            <p style="margin:0 0 14px; color:var(--ink2); font-size:14px;">เข้าสู่ระบบเพื่อลบบัญชีของคุณ</p>
            <a href="{{ route('account.delete.login') }}" class="tp-btn tp-btn-primary" style="text-decoration:none; min-height:44px;">
                <i class="fas fa-right-to-bracket"></i> <span>เข้าสู่ระบบ</span>
            </a>
        </div>
    @endif

    <div style="text-align:center; font-size:13px;">
        <a href="{{ route('privacy-policy') }}#delete" style="color:var(--deep1);">นโยบายความเป็นส่วนตัว</a>
        <span class="tp-muted"> · </span>
        <a href="{{ url('/') }}" style="color:var(--deep1);">กลับหน้าแรก</a>
    </div>
</div>
<x-theme-v4.public-footer />
@endsection

@push('scripts')
<script>
/**
 * ฟอร์มลบบัญชี — สลับวิธียืนยันตัวตน + ส่งรหัสทางอีเมล (กันกดซ้ำ/นับถอยหลังก่อนขอใหม่)
 */
function accountDeleteForm(cfg) {
    return {
        typed: cfg.typed || '',
        busy: false,
        proof: cfg.proof,
        sending: false,
        sent: false,
        notice: '',
        noticeOk: true,
        cooldown: 0,
        timer: null,

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
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf, 'X-Requested-With': 'XMLHttpRequest' },
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
                if (res.status === 419) {
                    this.notice = 'หน้านี้เปิดค้างนานเกินไป กรุณารีเฟรชหน้าแล้วลองใหม่';
                } else if (res.status === 429 && data.success === undefined) {
                    // ชน throttle ของ route (ข้อความเดิมเป็นภาษาอังกฤษ)
                    this.notice = 'ขอรหัสบ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่';
                } else {
                    this.notice = data.message || 'ส่งรหัสไม่สำเร็จ กรุณาลองใหม่';
                }
                if (data.retry_after) this.startCooldown(data.retry_after);
            } catch (e) {
                this.noticeOk = false;
                this.notice = 'เชื่อมต่อไม่สำเร็จ กรุณาตรวจสอบอินเทอร์เน็ตแล้วลองใหม่';
            } finally {
                this.sending = false;
            }
        },
    };
}
</script>
@endpush
