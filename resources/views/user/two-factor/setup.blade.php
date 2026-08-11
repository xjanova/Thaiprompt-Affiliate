@extends('layouts.user-v4')

@section('title', 'ตั้งค่าการยืนยันตัวตนแบบ 2 ชั้น (2FA)')

@section('content')
<div style="display:flex; flex-direction:column; gap:18px;">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:0; overflow:hidden; position:relative;">
        {{-- ภาพประกอบหัวเรื่อง (เจนเอง เก็บที่ public/images/art) --}}
        <x-art.hero-art image="usr-security" />
        <div style="padding:20px 24px; background:linear-gradient(120deg, color-mix(in srgb, #5689b8 18%, transparent), transparent 70%);">
            <div style="display:flex; align-items:center; gap:14px;">
                <span class="tp-tile" style="width:54px; height:54px; border-radius:16px; font-size:24px; background:#5689b8;"><span style="color:#fff;">🔐</span></span>
                <div>
                    <h1 style="font-size:clamp(20px,4vw,26px); font-weight:800; margin:0;">ความปลอดภัยบัญชี</h1>
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">การยืนยันตัวตนแบบ 2 ชั้น (Two-Factor Authentication)</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── สถานะ 2FA ─────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:18px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:12px;">
                <span class="tp-tile" style="width:46px; height:46px; border-radius:14px; font-size:20px; background:#5689b8;"><span style="color:#fff;">@if($status['enabled'])✅@else🔓@endif</span></span>
                <div>
                    <div style="font-size:18px; font-weight:800; color:var(--ink);">สถานะ 2FA</div>
                    <div style="font-size:13px;">
                        @if($status['enabled'])
                            <span style="color:#5aa07e; font-weight:700;">เปิดใช้งานแล้ว</span>
                        @else
                            <span style="color:#e08a3c; font-weight:700;">ยังไม่ได้เปิดใช้งาน</span>
                        @endif
                    </div>
                </div>
            </div>
            @if($status['enabled'])
                <form action="{{ route('user.two-factor.disable') }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะปิดการใช้งาน 2FA?')">
                    @csrf
                    <button type="submit" class="tp-btn" style="background:#d9534f; border-color:#d9534f; color:#fff;">ปิดการใช้งาน</button>
                </form>
            @endif
        </div>

        @if($status['enabled'])
            <div style="border-radius:14px; box-shadow:var(--inset-sm); padding:20px;">
                <div style="display:flex; flex-direction:column; gap:14px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                        <span style="color:var(--ink); font-weight:600;">วิธีการยืนยันหลัก:</span>
                        <span style="padding:6px 14px; border-radius:10px; box-shadow:var(--inset-sm); font-weight:700; color:#5689b8;">
                            @if($status['preferred_method'] === 'authenticator')🔐 Google Authenticator
                            @elseif($status['preferred_method'] === 'sms')📱 SMS
                            @elseif($status['preferred_method'] === 'line')💚 LINE
                            @elseif($status['preferred_method'] === 'email')📧 Email
                            @endif
                        </span>
                    </div>
                    @if(!empty($status['backup_methods']))
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                            <span style="color:var(--ink); font-weight:600;">วิธีสำรอง:</span>
                            <div style="display:flex; gap:8px;">
                                @foreach($status['backup_methods'] as $method)
                                    <span style="padding:4px 10px; border-radius:8px; box-shadow:var(--inset-sm); font-size:13px;">
                                        @if($method === 'sms')📱 SMS @elseif($method === 'line')💚 LINE @elseif($method === 'email')📧 Email @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                        <span style="color:var(--ink); font-weight:600;">การยืนยันทั้งหมด:</span>
                        <span style="padding:6px 14px; border-radius:10px; box-shadow:var(--inset-sm); font-weight:700; color:var(--ink);">{{ $status['total_verifications'] ?? 0 }} ครั้ง</span>
                    </div>
                    @if($status['last_verified_at'])
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
                            <span style="color:var(--ink); font-weight:600;">ยืนยันล่าสุด:</span>
                            <span style="padding:6px 14px; border-radius:10px; box-shadow:var(--inset-sm); font-size:13px; color:var(--ink);">{{ \Carbon\Carbon::parse($status['last_verified_at'])->diffForHumans() }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if(!$status['enabled'])
        {{-- ── เปิดใช้งาน 2FA ─────────────────────────────────── --}}
        <div class="tp-card" style="padding:24px;" x-data="authenticatorSetup()">
            <div class="tp-section-h" style="margin-bottom:8px;">🚀 เปิดใช้งาน 2FA ด้วย Google Authenticator</div>
            <p style="color:var(--ink2); margin:0 0 24px;">เพิ่มความปลอดภัยให้กับบัญชีของคุณด้วยการยืนยันตัวตนแบบ 2 ชั้น (TOTP) ผ่าน Google Authenticator</p>

            {{-- Step 1 --}}
            <div style="margin-bottom:28px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <div style="width:32px; height:32px; background:#5689b8; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800;">1</div>
                    <div style="font-size:16px; font-weight:800; color:var(--ink);">ดาวน์โหลดแอป Authenticator</div>
                </div>
                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; margin-left:44px;">
                    <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" style="display:flex; align-items:center; gap:12px; padding:16px; border:2px solid color-mix(in srgb, var(--ink2) 25%, transparent); border-radius:12px; text-decoration:none;">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/Google_Authenticator_for_Android_icon.svg" alt="Google Authenticator" style="width:48px; height:48px;">
                        <div><div style="font-weight:800; color:var(--ink);">Google Authenticator</div><div style="font-size:12px; color:var(--ink2);">Android / iOS</div></div>
                    </a>
                    <a href="https://apps.apple.com/app/microsoft-authenticator/id983156458" target="_blank" style="display:flex; align-items:center; gap:12px; padding:16px; border:2px solid color-mix(in srgb, var(--ink2) 25%, transparent); border-radius:12px; text-decoration:none;">
                        <svg style="width:48px; height:48px; color:#5689b8;" viewBox="0 0 24 24" fill="currentColor"><path d="M11.4 24H0V12.6h11.4V24zM24 24H12.6V12.6H24V24zM11.4 11.4H0V0h11.4v11.4zm12.6 0H12.6V0H24v11.4z"/></svg>
                        <div><div style="font-weight:800; color:var(--ink);">Microsoft Authenticator</div><div style="font-size:12px; color:var(--ink2);">Android / iOS</div></div>
                    </a>
                </div>
            </div>

            {{-- Step 2 --}}
            <div style="margin-bottom:28px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <div style="width:32px; height:32px; background:#5689b8; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800;">2</div>
                    <div style="font-size:16px; font-weight:800; color:var(--ink);">สแกน QR Code</div>
                </div>
                <div style="margin-left:44px;">
                    <div style="background:var(--surf1); border-radius:14px; box-shadow:var(--inset-sm); padding:24px; text-align:center; max-width:360px; margin:0 auto;">
                        <template x-if="!qrCode">
                            <div style="display:flex; flex-direction:column; gap:16px;">
                                <div style="width:192px; height:192px; box-shadow:var(--inset-sm); border-radius:10px; margin:0 auto; display:flex; align-items:center; justify-content:center;">
                                    <span style="color:var(--ink2);">กดปุ่มเพื่อสร้าง QR Code</span>
                                </div>
                                <button @click="generateQRCode()" :disabled="loading" class="tp-btn tp-btn-primary" style="background:#5689b8; border-color:#5689b8;">
                                    <span x-show="!loading">🔄 สร้าง QR Code</span>
                                    <span x-show="loading">⏳ กำลังสร้าง...</span>
                                </button>
                            </div>
                        </template>
                        <template x-if="qrCode">
                            <div style="display:flex; flex-direction:column; gap:16px;">
                                <div x-html="qrCode" style="width:192px; height:192px; margin:0 auto; background:#fff; border-radius:10px; padding:8px;"></div>
                                <div style="font-size:13px; color:var(--ink2);">หรือกรอก Secret Key ด้านล่างในแอป:</div>
                                <div style="box-shadow:var(--inset-sm); border-radius:10px; padding:12px; font-family:monospace; font-size:13px; word-break:break-all; user-select:all; cursor:pointer;" @click="copySecret()">
                                    <span x-text="secret"></span>
                                    <span style="font-size:11px; color:#5689b8; display:block; margin-top:4px;">คลิกเพื่อคัดลอก</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Step 3 --}}
            <div style="margin-bottom:28px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <div style="width:32px; height:32px; background:#5689b8; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:800;">3</div>
                    <div style="font-size:16px; font-weight:800; color:var(--ink);">ยืนยันรหัสจากแอป</div>
                </div>
                <div style="margin-left:44px;">
                    <form action="{{ route('user.two-factor.enable') }}" method="POST" style="display:flex; flex-direction:column; gap:16px;">
                        @csrf
                        <input type="hidden" name="preferred_method" value="authenticator">
                        <div>
                            <label style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:8px;">กรอกรหัส 6 หลักจากแอป Authenticator</label>
                            <input type="text" name="code" x-model="verificationCode" pattern="[0-9]{6}" maxlength="6" placeholder="000000" required :disabled="!qrCode"
                                   class="tp-input" style="text-align:center; font-size:24px; font-family:monospace; letter-spacing:0.5em;">
                            @error('code')<p style="color:#d9534f; font-size:13px; margin-top:8px;">{{ $message }}</p>@enderror
                        </div>
                        <div style="display:flex; gap:12px; flex-wrap:wrap;">
                            <button type="button" @click="verifyCode()" :disabled="!qrCode || verificationCode.length !== 6 || verifying" class="tp-btn" style="flex:1; min-width:140px; justify-content:center;">
                                <span x-show="!verifying">ตรวจสอบรหัส</span>
                                <span x-show="verifying">⏳ กำลังตรวจสอบ...</span>
                            </button>
                            <button type="submit" :disabled="!verified" class="tp-btn tp-btn-primary" style="flex:1; min-width:140px; justify-content:center; background:#5aa07e; border-color:#5aa07e;">🔐 เปิดใช้งาน 2FA</button>
                        </div>
                        <div x-show="verificationMessage" x-cloak :style="verified ? 'color:#5aa07e;' : 'color:#d9534f;'" style="padding:14px; border-radius:12px; box-shadow:var(--inset-sm); text-align:center; font-weight:700;">
                            <span x-text="verificationMessage"></span>
                        </div>
                    </form>
                </div>
            </div>

            <div class="tp-card" style="padding:16px; border-left:4px solid #d9a441;">
                <div style="display:flex; gap:12px;">
                    <span style="font-size:22px;">⚠️</span>
                    <div>
                        <div style="font-weight:800; color:var(--ink); margin-bottom:6px;">ข้อควรทราบ</div>
                        <ul style="margin:0; padding:0; list-style:none; font-size:13px; color:var(--ink2); display:flex; flex-direction:column; gap:4px;">
                            <li>• รหัสจะเปลี่ยนทุก 30 วินาที</li>
                            <li>• คุณจะได้รับรหัสกู้คืน (Recovery Codes) สำหรับใช้ในกรณีฉุกเฉิน</li>
                            <li>• กรุณาเก็บรหัสกู้คืนไว้ในที่ปลอดภัย</li>
                            <li>• หากเปลี่ยนโทรศัพท์ คุณต้องตั้งค่า 2FA ใหม่</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- ── ตั้งค่าขั้นสูง (เปิด 2FA แล้ว) ──────────────────── --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px;">
            <div class="tp-card" style="padding:24px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <span style="font-size:28px;">🔑</span>
                    <div style="font-size:18px; font-weight:800; color:var(--ink);">รหัสกู้คืน</div>
                </div>
                <p style="color:var(--ink2); font-size:13px; margin:0 0 16px;">ใช้รหัสกู้คืนเมื่อคุณไม่สามารถเข้าถึงวิธีการยืนยันตัวตนหลักได้</p>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <a href="{{ route('user.two-factor.recovery-codes', ['codes' => json_encode($status['recovery_codes'] ?? [])]) }}" class="tp-btn tp-btn-primary" style="justify-content:center; background:#5689b8; border-color:#5689b8;">ดูรหัสกู้คืน</a>
                    <form action="{{ route('user.two-factor.regenerate-codes') }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('คุณแน่ใจหรือไม่? รหัสเก่าจะใช้ไม่ได้อีกต่อไป')" class="tp-btn" style="width:100%; justify-content:center;">สร้างรหัสใหม่</button>
                    </form>
                </div>
            </div>

            <div class="tp-card" style="padding:24px;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
                    <span style="font-size:28px;">📱</span>
                    <div style="font-size:18px; font-weight:800; color:var(--ink);">อุปกรณ์ที่เชื่อถือ</div>
                </div>
                <p style="color:var(--ink2); font-size:13px; margin:0 0 16px;">อุปกรณ์ที่คุณเลือกให้จำการเข้าสู่ระบบไว้</p>
                @if(!empty($status['trusted_devices']) && count($status['trusted_devices']) > 0)
                    <div style="font-size:13px; color:var(--ink2); margin-bottom:14px;">จำนวนอุปกรณ์: <span style="font-weight:800; color:var(--ink);">{{ count($status['trusted_devices']) }}</span></div>
                    <form action="{{ route('user.two-factor.remove-all-devices') }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('ลบอุปกรณ์ที่เชื่อถือทั้งหมด?')" class="tp-btn" style="width:100%; justify-content:center; background:#d9534f; border-color:#d9534f; color:#fff;">ลบอุปกรณ์ทั้งหมด</button>
                    </form>
                @else
                    <div style="border-radius:10px; box-shadow:var(--inset-sm); padding:16px; text-align:center;">
                        <span style="color:var(--ink2); font-size:13px;">ยังไม่มีอุปกรณ์ที่เชื่อถือ</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ── ประโยชน์ ──────────────────────────────────────────── --}}
    <div class="tp-card" style="padding:24px;">
        <div class="tp-section-h" style="margin-bottom:16px;">✨ ประโยชน์ของ 2FA</div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:16px;">
            <div style="display:flex; gap:12px;">
                <span style="font-size:22px;">🛡️</span>
                <div><div style="font-weight:700; color:var(--ink);">ป้องกันการเข้าถึงโดยไม่ได้รับอนุญาต</div><div style="font-size:13px; color:var(--ink2); margin-top:2px;">แม้รหัสผ่านรั่วไหลก็ยังปลอดภัย</div></div>
            </div>
            <div style="display:flex; gap:12px;">
                <span style="font-size:22px;">🔔</span>
                <div><div style="font-weight:700; color:var(--ink);">แจ้งเตือนการเข้าสู่ระบบ</div><div style="font-size:13px; color:var(--ink2); margin-top:2px;">ทราบทันทีเมื่อมีการเข้าสู่ระบบ</div></div>
            </div>
            <div style="display:flex; gap:12px;">
                <span style="font-size:22px;">💎</span>
                <div><div style="font-weight:700; color:var(--ink);">ปกป้องทรัพย์สินดิจิทัล</div><div style="font-size:13px; color:var(--ink2); margin-top:2px;">ความปลอดภัยระดับธนาคาร</div></div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div style="position:fixed; bottom:16px; right:16px; background:#5aa07e; color:#fff; padding:12px 24px; border-radius:10px; box-shadow:var(--raise); z-index:60;" class="tp-toast">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div style="position:fixed; bottom:16px; right:16px; background:#d9534f; color:#fff; padding:12px 24px; border-radius:10px; box-shadow:var(--raise); z-index:60;" class="tp-toast">{{ session('error') }}</div>
@endif

@push('scripts')
<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.tp-toast').forEach(el => {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        });
    }, 5000);

    /**
     * Alpine.js component สำหรับตั้งค่า Google Authenticator
     */
    function authenticatorSetup() {
        return {
            loading: false,
            qrCode: null,
            secret: null,
            verificationCode: '',
            verifying: false,
            verified: false,
            verificationMessage: '',

            /**
             * สร้าง QR Code สำหรับ Google Authenticator
             */
            async generateQRCode() {
                this.loading = true;
                try {
                    const response = await fetch('{{ route("user.two-factor.generate-authenticator") }}', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.qrCode = data.qr_code_svg;
                        this.secret = data.secret;
                    } else {
                        alert(data.message || 'เกิดข้อผิดพลาดในการสร้าง QR Code');
                    }
                } catch (error) {
                    console.error('Error generating QR code:', error);
                    alert('เกิดข้อผิดพลาดในการสร้าง QR Code');
                } finally {
                    this.loading = false;
                }
            },

            /**
             * ตรวจสอบรหัสจาก Google Authenticator
             */
            async verifyCode() {
                if (this.verificationCode.length !== 6) return;

                this.verifying = true;
                this.verificationMessage = '';

                try {
                    const response = await fetch('{{ route("user.two-factor.verify-authenticator-setup") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            code: this.verificationCode
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.verified = true;
                        this.verificationMessage = '✅ ' + data.message;
                    } else {
                        this.verified = false;
                        this.verificationMessage = '❌ ' + data.message;
                    }
                } catch (error) {
                    console.error('Error verifying code:', error);
                    this.verificationMessage = '❌ เกิดข้อผิดพลาดในการตรวจสอบรหัส';
                } finally {
                    this.verifying = false;
                }
            },

            /**
             * คัดลอก Secret Key
             */
            async copySecret() {
                if (!this.secret) return;

                try {
                    await navigator.clipboard.writeText(this.secret);
                    alert('คัดลอก Secret Key เรียบร้อยแล้ว');
                } catch (error) {
                    // Fallback for browsers without clipboard API
                    const textArea = document.createElement('textarea');
                    textArea.value = this.secret;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('คัดลอก Secret Key เรียบร้อยแล้ว');
                }
            }
        };
    }
</script>
@endpush
@endsection
