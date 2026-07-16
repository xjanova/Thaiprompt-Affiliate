@extends('layouts.user-v4')

@section('title', 'ยืนยันตัวตน 2FA')

@section('content')
<div style="min-height:70vh; display:flex; align-items:center; justify-content:center; padding:32px 8px;">
    <div style="max-width:440px; width:100%; display:flex; flex-direction:column; gap:24px;">
        {{-- Header --}}
        <div style="text-align:center;">
            <div style="margin:0 auto; width:88px; height:88px; background:linear-gradient(135deg, #5689b8, #7c5cbf); border-radius:20px; display:flex; align-items:center; justify-content:center; box-shadow:var(--raise);">
                <span style="font-size:44px;">🔐</span>
            </div>
            <h2 style="margin:20px 0 0; font-size:28px; font-weight:800; color:var(--ink);">ยืนยันตัวตน</h2>
            <p style="margin:8px 0 0; font-size:13px; color:var(--ink2);">กรอกรหัส 6 หลักจาก Google Authenticator</p>
        </div>

        {{-- ── ฟอร์มยืนยัน ────────────────────────────────────── --}}
        <div class="tp-card" style="padding:32px;">
            <form id="verifyForm" style="display:flex; flex-direction:column; gap:20px;">
                @csrf
                <input type="hidden" name="action" value="{{ $action ?? 'general' }}">
                <input type="hidden" name="redirect" value="{{ $redirect ?? route('user.home') }}">

                <div>
                    <label for="code" style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:8px;">รหัสจาก Authenticator</label>
                    <input type="text" id="code" name="code" maxlength="6" pattern="[0-9]{6}" required autocomplete="off" inputmode="numeric"
                           class="tp-input" style="text-align:center; font-size:24px; font-weight:800; letter-spacing:0.4em;" placeholder="000000">
                    <p style="margin:8px 0 0; font-size:11px; color:var(--ink2); text-align:center;">เปิดแอป Google Authenticator แล้วกรอกรหัส 6 หลักที่แสดง</p>
                </div>

                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="remember_device" name="remember_device" style="width:16px; height:16px;">
                    <label for="remember_device" style="font-size:13px; color:var(--ink);">จำอุปกรณ์นี้ไว้ (ไม่ต้องยืนยันอีกเป็นเวลา 30 วัน)</label>
                </div>

                <button type="submit" id="verifyButton" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; font-size:16px; background:#5689b8; border-color:#5689b8;">
                    <span id="verifyButtonText">ยืนยัน</span>
                    <svg id="verifyButtonSpinner" class="hidden animate-spin" style="height:20px; width:20px; color:#fff;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>

                <div id="errorMessage" class="hidden" style="padding:14px; border-radius:10px; box-shadow:var(--inset-sm); border-left:4px solid #d9534f;">
                    <div style="display:flex; gap:10px;">
                        <span style="font-size:20px;">❌</span>
                        <p style="font-size:13px; color:#d9534f; margin:0;" id="errorText"></p>
                    </div>
                </div>
            </form>

            <div style="margin-top:24px; text-align:center; border-top:1px solid color-mix(in srgb, var(--ink2) 15%, transparent); padding-top:20px;">
                <div style="font-size:13px; color:var(--ink2);">
                    <p style="font-weight:700; margin:0 0 8px;">💡 ไม่มีรหัส?</p>
                    <ul style="text-align:left; margin:0; padding:0; list-style:none; font-size:11px; display:flex; flex-direction:column; gap:4px;">
                        <li>• เปิดแอป Google Authenticator หรือ Microsoft Authenticator</li>
                        <li>• หารหัสของ {{ config('app.name', 'Thaiprompt') }}</li>
                        <li>• รหัสจะเปลี่ยนทุก 30 วินาที รอรหัสใหม่ถ้าใกล้หมดเวลา</li>
                    </ul>
                </div>
            </div>

            <div style="margin-top:20px; text-align:center;">
                <button type="button" id="useRecoveryCodeButton" style="font-size:13px; color:#5689b8; font-weight:700; background:none; border:none; cursor:pointer;">ใช้รหัสกู้คืนแทน</button>
            </div>
        </div>

        {{-- ── ฟอร์มรหัสกู้คืน (ซ่อนไว้) ───────────────────────── --}}
        <div id="recoveryCodeForm" class="hidden tp-card" style="padding:32px;">
            <form id="recoveryForm" style="display:flex; flex-direction:column; gap:20px;">
                @csrf
                <input type="hidden" name="action" value="{{ $action ?? 'general' }}">
                <input type="hidden" name="redirect" value="{{ $redirect ?? route('user.home') }}">

                <div style="text-align:center; margin-bottom:4px;">
                    <span style="font-size:36px;">🔑</span>
                    <h3 style="margin:12px 0 0; font-size:19px; font-weight:800; color:var(--ink);">ใช้รหัสกู้คืน</h3>
                    <p style="margin:8px 0 0; font-size:13px; color:var(--ink2);">กรอกรหัสกู้คืนที่คุณได้เก็บไว้</p>
                </div>

                <div>
                    <label for="recovery_code" style="display:block; font-size:13px; font-weight:600; color:var(--ink); margin-bottom:8px;">รหัสกู้คืน</label>
                    <input type="text" id="recovery_code" name="recovery_code" required autocomplete="off" class="tp-input" placeholder="xxxx-xxxx-xxxx">
                </div>

                <button type="submit" id="recoveryButton" class="tp-btn tp-btn-primary" style="width:100%; justify-content:center; font-size:16px; background:#7c5cbf; border-color:#7c5cbf;">
                    <span id="recoveryButtonText">ยืนยันด้วยรหัสกู้คืน</span>
                </button>

                <button type="button" id="backToCodeButton" style="width:100%; padding:8px; font-size:13px; color:var(--ink2); background:none; border:none; cursor:pointer;">← กลับไปใช้รหัสยืนยันปกติ</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * สคริปต์สำหรับหน้ายืนยันตัวตน 2FA ด้วย Google Authenticator
 */
document.addEventListener('DOMContentLoaded', function() {
    const verifyForm = document.getElementById('verifyForm');
    const recoveryForm = document.getElementById('recoveryForm');
    const verifyButton = document.getElementById('verifyButton');
    const useRecoveryCodeButton = document.getElementById('useRecoveryCodeButton');
    const backToCodeButton = document.getElementById('backToCodeButton');
    const recoveryCodeForm = document.getElementById('recoveryCodeForm');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const codeInput = document.getElementById('code');

    // Auto-focus on code input
    codeInput.focus();

    // Format code input (numbers only)
    codeInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');

        // Auto-submit when 6 digits entered
        if (this.value.length === 6) {
            verifyForm.dispatchEvent(new Event('submit'));
        }
    });

    // Verify form submission
    verifyForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        // ตรวจสอบว่ามีรหัส 6 หลักก่อนส่ง
        if (codeInput.value.length !== 6) {
            errorText.textContent = 'กรุณากรอกรหัส 6 หลัก';
            errorMessage.classList.remove('hidden');
            return;
        }

        verifyButton.disabled = true;
        document.getElementById('verifyButtonText').textContent = 'กำลังตรวจสอบ...';
        document.getElementById('verifyButtonSpinner').classList.remove('hidden');
        errorMessage.classList.add('hidden');

        try {
            const formData = new FormData(verifyForm);
            const response = await fetch('{{ route("user.two-factor.verify-code") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Success - redirect
                window.location.href = data.redirect || '{{ route("user.home") }}';
            } else {
                // Show error
                errorText.textContent = data.message || 'รหัสไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
                errorMessage.classList.remove('hidden');
                codeInput.value = '';
                codeInput.focus();
            }
        } catch (error) {
            errorText.textContent = 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
            errorMessage.classList.remove('hidden');
        } finally {
            verifyButton.disabled = false;
            document.getElementById('verifyButtonText').textContent = 'ยืนยัน';
            document.getElementById('verifyButtonSpinner').classList.add('hidden');
        }
    });

    // Recovery form submission
    recoveryForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        const recoveryButton = document.getElementById('recoveryButton');
        recoveryButton.disabled = true;
        document.getElementById('recoveryButtonText').textContent = 'กำลังตรวจสอบ...';

        try {
            const formData = new FormData(recoveryForm);
            const response = await fetch('{{ route("user.two-factor.verify-recovery-code") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // แสดงคำเตือนถ้ามี
                if (data.warning) {
                    alert('⚠️ ' + data.warning);
                }
                window.location.href = data.redirect || '{{ route("user.home") }}';
            } else {
                alert('❌ ' + (data.message || 'รหัสกู้คืนไม่ถูกต้อง'));
            }
        } catch (error) {
            alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
        } finally {
            recoveryButton.disabled = false;
            document.getElementById('recoveryButtonText').textContent = 'ยืนยันด้วยรหัสกู้คืน';
        }
    });

    // Toggle recovery code form
    useRecoveryCodeButton.addEventListener('click', function() {
        verifyForm.parentElement.classList.add('hidden');
        recoveryCodeForm.classList.remove('hidden');
        document.getElementById('recovery_code').focus();
    });

    backToCodeButton.addEventListener('click', function() {
        recoveryCodeForm.classList.add('hidden');
        verifyForm.parentElement.classList.remove('hidden');
        codeInput.focus();
    });
});
</script>
@endpush
@endsection
