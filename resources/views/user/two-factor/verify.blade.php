@extends('layouts.user-arrow-x')

@section('title', 'ยืนยันตัวตน 2FA')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <div class="mx-auto h-24 w-24 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-2xl">
                <span class="text-5xl">🔐</span>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                ยืนยันตัวตน
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                กรุณากรอกรหัส 6 หลักที่ส่งให้คุณ
            </p>
        </div>

        <!-- Verification Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8">
            <form id="verifyForm" class="space-y-6">
                @csrf
                <input type="hidden" name="action" value="{{ $action ?? 'general' }}">
                <input type="hidden" name="redirect" value="{{ $redirect ?? route('user.dashboard') }}">

                <!-- Code Input -->
                <div>
                    <label for="code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        รหัสยืนยันตัวตน
                    </label>
                    <input type="text"
                           id="code"
                           name="code"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           required
                           autocomplete="off"
                           class="w-full px-4 py-4 text-center text-2xl font-bold border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 tracking-widest"
                           placeholder="000000">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">กรอกรหัส 6 หลักที่ได้รับ</p>
                </div>

                <!-- Remember Device -->
                <div class="flex items-center">
                    <input type="checkbox"
                           id="remember_device"
                           name="remember_device"
                           class="h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                    <label for="remember_device" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                        จำอุปกรณ์นี้ไว้ (ไม่ต้องยืนยันอีกเป็นเวลา 30 วัน)
                    </label>
                </div>

                <!-- Verify Button -->
                <button type="submit"
                        id="verifyButton"
                        class="w-full flex justify-center items-center gap-2 py-4 px-6 border border-transparent rounded-xl shadow-lg text-lg font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                    <span id="verifyButtonText">ยืนยัน</span>
                    <svg id="verifyButtonSpinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>

                <!-- Error Display -->
                <div id="errorMessage" class="hidden bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <span class="text-2xl">❌</span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700" id="errorText"></p>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Resend Code -->
            <div class="mt-6 text-center border-t pt-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">ไม่ได้รับรหัส?</p>
                <button type="button"
                        id="resendButton"
                        class="px-6 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-300 rounded-lg font-semibold transition-colors">
                    <span id="resendButtonText">ส่งรหัสใหม่</span>
                    <span id="resendTimer" class="hidden">(<span id="countdown">60</span>s)</span>
                </button>
            </div>

            <!-- Recovery Code Option -->
            <div class="mt-6 text-center">
                <button type="button"
                        id="useRecoveryCodeButton"
                        class="text-sm text-blue-600 hover:text-blue-700 font-semibold">
                    ใช้รหัสกู้คืนแทน
                </button>
            </div>
        </div>

        <!-- Recovery Code Form (Hidden by default) -->
        <div id="recoveryCodeForm" class="hidden bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8">
            <form id="recoveryForm" class="space-y-6">
                @csrf
                <input type="hidden" name="action" value="{{ $action ?? 'general' }}">
                <input type="hidden" name="redirect" value="{{ $redirect ?? route('user.dashboard') }}">

                <div class="text-center mb-6">
                    <span class="text-4xl">🔑</span>
                    <h3 class="mt-3 text-xl font-bold text-gray-900 dark:text-white">ใช้รหัสกู้คืน</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">กรอกรหัสกู้คืนที่คุณได้เก็บไว้</p>
                </div>

                <div>
                    <label for="recovery_code" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        รหัสกู้คืน
                    </label>
                    <input type="text"
                           id="recovery_code"
                           name="recovery_code"
                           required
                           autocomplete="off"
                           class="w-full px-4 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="xxxx-xxxx-xxxx">
                </div>

                <button type="submit"
                        id="recoveryButton"
                        class="w-full py-4 px-6 border border-transparent rounded-xl shadow-lg text-lg font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 transition-all">
                    <span id="recoveryButtonText">ยืนยันด้วยรหัสกู้คืน</span>
                </button>

                <button type="button"
                        id="backToCodeButton"
                        class="w-full py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:text-white">
                    ← กลับไปใช้รหัสยืนยันปกติ
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const verifyForm = document.getElementById('verifyForm');
    const recoveryForm = document.getElementById('recoveryForm');
    const verifyButton = document.getElementById('verifyButton');
    const resendButton = document.getElementById('resendButton');
    const useRecoveryCodeButton = document.getElementById('useRecoveryCodeButton');
    const backToCodeButton = document.getElementById('backToCodeButton');
    const recoveryCodeForm = document.getElementById('recoveryCodeForm');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const codeInput = document.getElementById('code');

    let resendCountdown = 0;

    // Auto-focus on code input
    codeInput.focus();

    // Format code input (numbers only)
    codeInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Verify form submission
    verifyForm.addEventListener('submit', async function(e) {
        e.preventDefault();

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
                window.location.href = data.redirect || '{{ route("user.dashboard") }}';
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
                window.location.href = data.redirect || '{{ route("user.dashboard") }}';
            } else {
                alert(data.message || 'รหัสกู้คืนไม่ถูกต้อง');
            }
        } catch (error) {
            alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
        } finally {
            recoveryButton.disabled = false;
            document.getElementById('recoveryButtonText').textContent = 'ยืนยันด้วยรหัสกู้คืน';
        }
    });

    // Resend code
    resendButton.addEventListener('click', async function() {
        if (resendCountdown > 0) return;

        try {
            const formData = new FormData();
            formData.append('action', '{{ $action ?? "general" }}');

            const response = await fetch('{{ route("user.two-factor.send-code") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                alert('✅ ส่งรหัสใหม่แล้ว');
                startResendCountdown();
            } else {
                alert('❌ ' + (data.message || 'ไม่สามารถส่งรหัสได้'));
            }
        } catch (error) {
            alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
        }
    });

    function startResendCountdown() {
        resendCountdown = 60;
        resendButton.disabled = true;
        document.getElementById('resendButtonText').classList.add('hidden');
        document.getElementById('resendTimer').classList.remove('hidden');

        const interval = setInterval(() => {
            resendCountdown--;
            document.getElementById('countdown').textContent = resendCountdown;

            if (resendCountdown <= 0) {
                clearInterval(interval);
                resendButton.disabled = false;
                document.getElementById('resendButtonText').classList.remove('hidden');
                document.getElementById('resendTimer').classList.add('hidden');
            }
        }, 1000);
    }

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
