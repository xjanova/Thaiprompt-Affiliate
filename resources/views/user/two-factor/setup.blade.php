@extends('layouts.user-arrow-x')

@section('title', 'ตั้งค่าการยืนยันตัวตนแบบ 2 ชั้น (2FA)')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                <span class="text-3xl">🔐</span>
            </div>
            <div>
                <h1 class="text-3xl font-bold">ความปลอดภัยบัญชี</h1>
                <p class="text-blue-100 mt-1">การยืนยันตัวตนแบบ 2 ชั้น (Two-Factor Authentication)</p>
            </div>
        </div>
    </div>

    <!-- Status Card -->
    <x-arrow-x.card-v3 class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                    <span class="text-2xl">
                        @if($status['enabled'])
                            ✅
                        @else
                            🔓
                        @endif
                    </span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">สถานะ 2FA</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @if($status['enabled'])
                            <span class="text-green-600 font-semibold">เปิดใช้งานแล้ว</span>
                        @else
                            <span class="text-orange-600 font-semibold">ยังไม่ได้เปิดใช้งาน</span>
                        @endif
                    </p>
                </div>
            </div>

            @if($status['enabled'])
                <form action="{{ route('user.two-factor.disable') }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะปิดการใช้งาน 2FA?')">
                    @csrf
                    <button type="submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-colors shadow-lg">
                        ปิดการใช้งาน
                    </button>
                </form>
            @endif
        </div>

        @if($status['enabled'])
            <!-- Current Settings -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">วิธีการยืนยันหลัก:</span>
                        <span class="px-4 py-2 bg-white dark:bg-gray-800 rounded-lg font-semibold text-blue-600 shadow">
                            @if($status['preferred_method'] === 'authenticator')
                                🔐 Google Authenticator
                            @elseif($status['preferred_method'] === 'sms')
                                📱 SMS
                            @elseif($status['preferred_method'] === 'line')
                                💚 LINE
                            @elseif($status['preferred_method'] === 'email')
                                📧 Email
                            @endif
                        </span>
                    </div>

                    @if(!empty($status['backup_methods']))
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">วิธีสำรอง:</span>
                            <div class="flex gap-2">
                                @foreach($status['backup_methods'] as $method)
                                    <span class="px-3 py-1 bg-white dark:bg-gray-800 rounded-lg text-sm shadow">
                                        @if($method === 'sms')
                                            📱 SMS
                                        @elseif($method === 'line')
                                            💚 LINE
                                        @elseif($method === 'email')
                                            📧 Email
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 dark:text-gray-300 font-medium">การยืนยันทั้งหมด:</span>
                        <span class="px-4 py-2 bg-white dark:bg-gray-800 rounded-lg font-semibold shadow">
                            {{ $status['total_verifications'] ?? 0 }} ครั้ง
                        </span>
                    </div>

                    @if($status['last_verified_at'])
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">ยืนยันล่าสุด:</span>
                            <span class="px-4 py-2 bg-white dark:bg-gray-800 rounded-lg text-sm shadow">
                                {{ \Carbon\Carbon::parse($status['last_verified_at'])->diffForHumans() }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </x-arrow-x.card-v3>

    @if(!$status['enabled'])
        <!-- Enable 2FA Form - Google Authenticator -->
        <x-arrow-x.card-v3 class="p-6" x-data="authenticatorSetup()">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
                <span>🚀</span> เปิดใช้งาน 2FA ด้วย Google Authenticator
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                เพิ่มความปลอดภัยให้กับบัญชีของคุณด้วยการยืนยันตัวตนแบบ 2 ชั้น (TOTP) ผ่าน Google Authenticator
            </p>

            <!-- Step 1: ดาวน์โหลดแอป -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">ดาวน์โหลดแอป Authenticator</h3>
                </div>
                <div class="grid md:grid-cols-2 gap-4 ml-11">
                    <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2"
                       target="_blank"
                       class="flex items-center gap-3 p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-green-500 transition-colors">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/Google_Authenticator_for_Android_icon.svg"
                             alt="Google Authenticator" class="w-12 h-12">
                        <div>
                            <div class="font-bold text-gray-800 dark:text-white">Google Authenticator</div>
                            <div class="text-sm text-gray-500">Android / iOS</div>
                        </div>
                    </a>
                    <a href="https://apps.apple.com/app/microsoft-authenticator/id983156458"
                       target="_blank"
                       class="flex items-center gap-3 p-4 border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:border-blue-500 transition-colors">
                        <svg class="w-12 h-12 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.4 24H0V12.6h11.4V24zM24 24H12.6V12.6H24V24zM11.4 11.4H0V0h11.4v11.4zm12.6 0H12.6V0H24v11.4z"/>
                        </svg>
                        <div>
                            <div class="font-bold text-gray-800 dark:text-white">Microsoft Authenticator</div>
                            <div class="text-sm text-gray-500">Android / iOS</div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Step 2: สแกน QR Code -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">สแกน QR Code</h3>
                </div>
                <div class="ml-11">
                    <!-- QR Code Container -->
                    <div class="bg-white dark:bg-gray-900 border-2 border-gray-200 dark:border-gray-700 rounded-xl p-6 text-center max-w-sm mx-auto">
                        <template x-if="!qrCode">
                            <div class="space-y-4">
                                <div class="w-48 h-48 bg-gray-100 dark:bg-gray-800 rounded-lg mx-auto flex items-center justify-center">
                                    <span class="text-gray-400">กดปุ่มเพื่อสร้าง QR Code</span>
                                </div>
                                <button @click="generateQRCode()"
                                        :disabled="loading"
                                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-lg font-semibold transition-colors">
                                    <span x-show="!loading">🔄 สร้าง QR Code</span>
                                    <span x-show="loading">⏳ กำลังสร้าง...</span>
                                </button>
                            </div>
                        </template>
                        <template x-if="qrCode">
                            <div class="space-y-4">
                                <div x-html="qrCode" class="w-48 h-48 mx-auto [&>svg]:w-full [&>svg]:h-full"></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    หรือกรอก Secret Key ด้านล่างในแอป:
                                </div>
                                <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-3 font-mono text-sm break-all select-all cursor-pointer"
                                     @click="copySecret()">
                                    <span x-text="secret"></span>
                                    <span class="text-xs text-blue-600 block mt-1">คลิกเพื่อคัดลอก</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Step 3: ยืนยันรหัส -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">3</div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">ยืนยันรหัสจากแอป</h3>
                </div>
                <div class="ml-11">
                    <form action="{{ route('user.two-factor.enable') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="preferred_method" value="authenticator">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                กรอกรหัส 6 หลักจากแอป Authenticator
                            </label>
                            <input type="text"
                                   name="code"
                                   x-model="verificationCode"
                                   pattern="[0-9]{6}"
                                   maxlength="6"
                                   placeholder="000000"
                                   required
                                   :disabled="!qrCode"
                                   class="w-full px-4 py-4 text-center text-2xl font-mono tracking-[0.5em] border-2 border-gray-300 dark:border-gray-600 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:bg-gray-800 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                            @error('code')
                                <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Verify Button -->
                        <div class="flex gap-3">
                            <button type="button"
                                    @click="verifyCode()"
                                    :disabled="!qrCode || verificationCode.length !== 6 || verifying"
                                    class="flex-1 px-6 py-3 bg-gray-200 hover:bg-gray-300 disabled:bg-gray-100 text-gray-700 rounded-lg font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!verifying">ตรวจสอบรหัส</span>
                                <span x-show="verifying">⏳ กำลังตรวจสอบ...</span>
                            </button>
                            <button type="submit"
                                    :disabled="!verified"
                                    class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 disabled:from-gray-400 disabled:to-gray-500 text-white rounded-lg font-semibold transition-all disabled:cursor-not-allowed">
                                🔐 เปิดใช้งาน 2FA
                            </button>
                        </div>

                        <!-- Verification Status -->
                        <div x-show="verificationMessage"
                             :class="verified ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'"
                             class="p-4 rounded-xl border text-center font-semibold">
                            <span x-text="verificationMessage"></span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-4">
                <div class="flex gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div class="flex-1">
                        <h3 class="font-bold text-yellow-800 dark:text-yellow-200 mb-1">ข้อควรทราบ</h3>
                        <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                            <li>• รหัสจะเปลี่ยนทุก 30 วินาที</li>
                            <li>• คุณจะได้รับรหัสกู้คืน (Recovery Codes) สำหรับใช้ในกรณีฉุกเฉิน</li>
                            <li>• กรุณาเก็บรหัสกู้คืนไว้ในที่ปลอดภัย</li>
                            <li>• หากเปลี่ยนโทรศัพท์ คุณต้องตั้งค่า 2FA ใหม่</li>
                        </ul>
                    </div>
                </div>
            </div>
        </x-arrow-x.card-v3>
    @else
        <!-- Advanced Settings (When 2FA is Enabled) -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Recovery Codes -->
            <x-arrow-x.card-v3 class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">🔑</span>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white">รหัสกู้คืน</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4 text-sm">
                    ใช้รหัสกู้คืนเมื่อคุณไม่สามารถเข้าถึงวิธีการยืนยันตัวตนหลักได้
                </p>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('user.two-factor.recovery-codes', ['codes' => json_encode($status['recovery_codes'] ?? [])]) }}"
                       class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold text-center transition-colors">
                        ดูรหัสกู้คืน
                    </a>
                    <form action="{{ route('user.two-factor.regenerate-codes') }}" method="POST">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('คุณแน่ใจหรือไม่? รหัสเก่าจะใช้ไม่ได้อีกต่อไป')"
                                class="w-full px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 dark:text-gray-300 rounded-lg font-semibold transition-colors">
                            สร้างรหัสใหม่
                        </button>
                    </form>
                </div>
            </x-arrow-x.card-v3>

            <!-- Trusted Devices -->
            <x-arrow-x.card-v3 class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">📱</span>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white">อุปกรณ์ที่เชื่อถือ</h3>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4 text-sm">
                    อุปกรณ์ที่คุณเลือกให้จำการเข้าสู่ระบบไว้
                </p>
                @if(!empty($status['trusted_devices']) && count($status['trusted_devices']) > 0)
                    <div class="space-y-2 mb-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            จำนวนอุปกรณ์: <span class="font-bold text-gray-800 dark:text-white">{{ count($status['trusted_devices']) }}</span>
                        </div>
                    </div>
                    <form action="{{ route('user.two-factor.remove-all-devices') }}" method="POST">
                        @csrf
                        <button type="submit"
                                onclick="return confirm('ลบอุปกรณ์ที่เชื่อถือทั้งหมด?')"
                                class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-colors">
                            ลบอุปกรณ์ทั้งหมด
                        </button>
                    </form>
                @else
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4 text-center">
                        <span class="text-gray-500 dark:text-gray-400 text-sm">ยังไม่มีอุปกรณ์ที่เชื่อถือ</span>
                    </div>
                @endif
            </x-arrow-x.card-v3>
        </div>
    @endif

    <!-- Benefits -->
    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl shadow-xl p-6 border border-indigo-100">
        <h3 class="text-xl font-bold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
            <span>✨</span> ประโยชน์ของ 2FA
        </h3>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="flex gap-3">
                <span class="text-2xl">🛡️</span>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-white">ป้องกันการเข้าถึงโดยไม่ได้รับอนุญาต</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">แม้รหัสผ่านรั่วไหลก็ยังปลอดภัย</div>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="text-2xl">🔔</span>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-white">แจ้งเตือนการเข้าสู่ระบบ</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">ทราบทันทีเมื่อมีการเข้าสู่ระบบ</div>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="text-2xl">💎</span>
                <div>
                    <div class="font-semibold text-gray-800 dark:text-white">ปกป้องทรัพย์สินดิจิทัล</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">ความปลอดภัยระดับธนาคาร</div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg animate-bounce">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg animate-bounce">
        {{ session('error') }}
    </div>
@endif

@push('scripts')
<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.animate-bounce').forEach(el => {
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
