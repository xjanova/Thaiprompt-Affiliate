@extends('layouts.user')

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
    <div class="bg-white rounded-2xl shadow-xl p-6">
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
                    <h2 class="text-xl font-bold text-gray-800">สถานะ 2FA</h2>
                    <p class="text-sm text-gray-600">
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
                        <span class="text-gray-700 font-medium">วิธีการยืนยันหลัก:</span>
                        <span class="px-4 py-2 bg-white rounded-lg font-semibold text-blue-600 shadow">
                            @if($status['preferred_method'] === 'sms')
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
                            <span class="text-gray-700 font-medium">วิธีสำรอง:</span>
                            <div class="flex gap-2">
                                @foreach($status['backup_methods'] as $method)
                                    <span class="px-3 py-1 bg-white rounded-lg text-sm shadow">
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
                        <span class="text-gray-700 font-medium">การยืนยันทั้งหมด:</span>
                        <span class="px-4 py-2 bg-white rounded-lg font-semibold shadow">
                            {{ $status['total_verifications'] ?? 0 }} ครั้ง
                        </span>
                    </div>

                    @if($status['last_verified_at'])
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700 font-medium">ยืนยันล่าสุด:</span>
                            <span class="px-4 py-2 bg-white rounded-lg text-sm shadow">
                                {{ \Carbon\Carbon::parse($status['last_verified_at'])->diffForHumans() }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if(!$status['enabled'])
        <!-- Enable 2FA Form -->
        <div class="bg-white rounded-2xl shadow-xl p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span>🚀</span> เปิดใช้งาน 2FA
            </h2>
            <p class="text-gray-600 mb-6">
                เพิ่มความปลอดภัยให้กับบัญชีของคุณด้วยการยืนยันตัวตนแบบ 2 ชั้น คุณจะต้องยืนยันตัวตนทุกครั้งที่เข้าสู่ระบบจากอุปกรณ์ใหม่
            </p>

            <form action="{{ route('user.two-factor.enable') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">เลือกวิธีการยืนยันตัวตน</label>
                    <div class="grid md:grid-cols-3 gap-4">
                        <!-- SMS Option -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="preferred_method" value="sms" required class="peer sr-only">
                            <div class="border-2 border-gray-200 rounded-xl p-6 text-center hover:border-blue-400 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                <div class="text-4xl mb-3">📱</div>
                                <div class="font-bold text-gray-800 mb-1">SMS</div>
                                <div class="text-sm text-gray-600">รับรหัสผ่าน SMS</div>
                            </div>
                            <div class="absolute top-2 right-2 w-6 h-6 bg-blue-600 rounded-full hidden peer-checked:flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </label>

                        <!-- LINE Option -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="preferred_method" value="line" required class="peer sr-only">
                            <div class="border-2 border-gray-200 rounded-xl p-6 text-center hover:border-green-400 peer-checked:border-green-600 peer-checked:bg-green-50 transition-all">
                                <div class="text-4xl mb-3">💚</div>
                                <div class="font-bold text-gray-800 mb-1">LINE</div>
                                <div class="text-sm text-gray-600">รับรหัสผ่าน LINE</div>
                            </div>
                            <div class="absolute top-2 right-2 w-6 h-6 bg-green-600 rounded-full hidden peer-checked:flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </label>

                        <!-- Email Option (Disabled - Coming Soon) -->
                        <div class="relative opacity-50 cursor-not-allowed" title="Email verification coming soon">
                            <div class="border-2 border-gray-300 bg-gray-100 rounded-xl p-6 text-center">
                                <div class="text-4xl mb-3">📧</div>
                                <div class="font-bold text-gray-500 mb-1">Email</div>
                                <div class="text-sm text-gray-400">เร็วๆ นี้</div>
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="bg-gray-700 text-white text-xs px-3 py-1 rounded-full">เร็วๆ นี้</span>
                            </div>
                        </div>
                    </div>
                    @error('preferred_method')
                        <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                    <div class="flex gap-3">
                        <span class="text-2xl">⚠️</span>
                        <div class="flex-1">
                            <h3 class="font-bold text-yellow-800 mb-1">ข้อควรทราบ</h3>
                            <ul class="text-sm text-yellow-700 space-y-1">
                                <li>• คุณจะได้รับรหัสกู้คืน (Recovery Codes) สำหรับใช้ในกรณีฉุกเฉิน</li>
                                <li>• กรุณาเก็บรหัสกู้คืนไว้ในที่ปลอดภัย</li>
                                <li>• คุณสามารถเปลี่ยนวิธีการยืนยันได้ในภายหลัง</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl">
                    🔐 เปิดใช้งาน 2FA
                </button>
            </form>
        </div>
    @else
        <!-- Advanced Settings (When 2FA is Enabled) -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Recovery Codes -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">🔑</span>
                    <h3 class="text-xl font-bold text-gray-800">รหัสกู้คืน</h3>
                </div>
                <p class="text-gray-600 mb-4 text-sm">
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
                                class="w-full px-4 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-semibold transition-colors">
                            สร้างรหัสใหม่
                        </button>
                    </form>
                </div>
            </div>

            <!-- Trusted Devices -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">📱</span>
                    <h3 class="text-xl font-bold text-gray-800">อุปกรณ์ที่เชื่อถือ</h3>
                </div>
                <p class="text-gray-600 mb-4 text-sm">
                    อุปกรณ์ที่คุณเลือกให้จำการเข้าสู่ระบบไว้
                </p>
                @if(!empty($status['trusted_devices']) && count($status['trusted_devices']) > 0)
                    <div class="space-y-2 mb-4">
                        <div class="text-sm text-gray-600">
                            จำนวนอุปกรณ์: <span class="font-bold text-gray-800">{{ count($status['trusted_devices']) }}</span>
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
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <span class="text-gray-500 text-sm">ยังไม่มีอุปกรณ์ที่เชื่อถือ</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Benefits -->
    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl shadow-xl p-6 border border-indigo-100">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span>✨</span> ประโยชน์ของ 2FA
        </h3>
        <div class="grid md:grid-cols-3 gap-4">
            <div class="flex gap-3">
                <span class="text-2xl">🛡️</span>
                <div>
                    <div class="font-semibold text-gray-800">ป้องกันการเข้าถึงโดยไม่ได้รับอนุญาต</div>
                    <div class="text-sm text-gray-600 mt-1">แม้รหัสผ่านรั่วไหลก็ยังปลอดภัย</div>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="text-2xl">🔔</span>
                <div>
                    <div class="font-semibold text-gray-800">แจ้งเตือนการเข้าสู่ระบบ</div>
                    <div class="text-sm text-gray-600 mt-1">ทราบทันทีเมื่อมีการเข้าสู่ระบบ</div>
                </div>
            </div>
            <div class="flex gap-3">
                <span class="text-2xl">💎</span>
                <div>
                    <div class="font-semibold text-gray-800">ปกป้องทรัพย์สินดิจิทัล</div>
                    <div class="text-sm text-gray-600 mt-1">ความปลอดภัยระดับธนาคาร</div>
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
</script>
@endpush
@endsection
