{{--
    หน้าบังคับเชื่อมต่อ LINE สำหรับยืนยันตัวตน
    จะแสดงเมื่อผู้ใช้ยังไม่ได้เชื่อมต่อ LINE

    @see app/Http/Middleware/RequireLineUid.php
--}}
@extends('layouts.user-arrow-x')

@section('title', 'เชื่อมต่อ LINE เพื่อยืนยันตัวตน')

@section('page-title', 'เชื่อมต่อ LINE')

@section('content')

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush

<div class="max-w-4xl mx-auto py-8 px-4">
    {{-- Premium Hero Header (Green-Emerald for LINE Connection) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 dark:from-green-800 dark:via-emerald-800 dark:to-teal-800 rounded-2xl shadow-2xl p-8 mb-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icon Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fab fa-line"></i>
            </div>
        </div>

        {{-- Content --}}
        <div class="relative z-10 text-center">
            <div class="inline-flex items-center justify-center gap-4 mb-6">
                <div class="glass-fusion p-4 rounded-2xl">
                    <svg class="w-12 h-12 text-white drop-shadow-lg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg mb-2">
                เชื่อมต่อ LINE เพื่อยืนยันตัวตน
            </h1>
            <p class="text-green-100 text-lg">
                กรุณาเชื่อมต่อบัญชี LINE เพื่อใช้งานระบบได้อย่างครบถ้วน
            </p>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white/10 dark:bg-gray-800/50 backdrop-blur-xl border border-white/20 dark:border-gray-700/50 rounded-3xl shadow-2xl overflow-hidden">

        {{-- Content --}}
        <div class="p-6 md:p-8">
            {{-- Why LINE is required --}}
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-shield-alt text-emerald-500"></i>
                    ทำไมต้องเชื่อมต่อ LINE?
                </h2>
                <div class="space-y-3">
                    {{-- Reason 1: Verification --}}
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-purple-500/10 to-pink-500/10 dark:from-purple-900/30 dark:to-pink-900/30 rounded-xl border border-purple-200/50 dark:border-purple-700/30">
                        <div class="w-10 h-10 bg-purple-500/30 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user-check text-purple-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">ยืนยันตัวตน</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">LINE UID ช่วยยืนยันว่าคุณเป็นเจ้าของบัญชีจริง เพิ่มความปลอดภัย</p>
                        </div>
                    </div>

                    {{-- Reason 2: Notifications --}}
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-green-500/10 to-emerald-500/10 dark:from-green-900/30 dark:to-emerald-900/30 rounded-xl border border-green-200/50 dark:border-green-700/30">
                        <div class="w-10 h-10 bg-green-500/30 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-bell text-green-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">รับการแจ้งเตือน</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">รับแจ้งเตือนคอมมิชชั่น ยอดขาย และกิจกรรมสำคัญผ่าน LINE ทันที</p>
                        </div>
                    </div>

                    {{-- Reason 3: Quick Support --}}
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-blue-500/10 to-cyan-500/10 dark:from-blue-900/30 dark:to-cyan-900/30 rounded-xl border border-blue-200/50 dark:border-blue-700/30">
                        <div class="w-10 h-10 bg-blue-500/30 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-headset text-blue-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">รับความช่วยเหลือ</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">ติดต่อทีมงานและรับความช่วยเหลือผ่าน LINE OA ได้สะดวก</p>
                        </div>
                    </div>

                    {{-- Reason 4: Referral Tracking --}}
                    <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-orange-500/10 to-amber-500/10 dark:from-orange-900/30 dark:to-amber-900/30 rounded-xl border border-orange-200/50 dark:border-orange-700/30">
                        <div class="w-10 h-10 bg-orange-500/30 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-users text-orange-400"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">ติดตามสายงาน</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">ระบบจะแจ้งเตือนเมื่อมีสมาชิกใหม่ในทีมของคุณ</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Current Status --}}
            <div class="p-4 bg-red-500/10 dark:bg-red-900/20 border border-red-300/50 dark:border-red-700/30 rounded-xl mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-red-500/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-500 dark:text-red-400 text-xl"></i>
                    </div>
                    <div>
                        <p class="font-bold text-red-700 dark:text-red-300">ยังไม่ได้เชื่อมต่อ LINE</p>
                        <p class="text-sm text-red-600 dark:text-red-400">
                            บัญชีของคุณ ({{ auth()->user()->email }}) ยังไม่ได้เชื่อมต่อกับ LINE
                        </p>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="space-y-4">
                {{-- Connect LINE Button --}}
                <a href="{{ route('line.link') }}"
                   class="w-full flex items-center justify-center gap-3 px-8 py-4 bg-gradient-to-r from-green-500 to-emerald-500 hover:from-green-400 hover:to-emerald-400 text-white font-bold text-lg rounded-2xl shadow-xl hover:shadow-2xl hover:shadow-green-500/30 transition-all duration-300 transform hover:scale-[1.02] group">
                    <svg class="w-7 h-7 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/>
                    </svg>
                    เชื่อมต่อ LINE ตอนนี้
                </a>

                {{-- Alternative: Add Friend QR --}}
                @php
                    $lineSettings = \App\Models\LineOaSetting::getActive();
                @endphp
                @if($lineSettings && $lineSettings->line_id)
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">หรือเพิ่มเพื่อน LINE OA ด้านล่าง</p>
                    <div class="inline-block p-4 bg-white rounded-xl shadow-lg">
                        <img src="https://qr-official.line.me/gs/M_{{ $lineSettings->messaging_channel_id ?? $lineSettings->line_id }}_GW.png"
                             alt="LINE QR Code"
                             class="w-40 h-40 mx-auto"
                             onerror="this.parentElement.style.display='none'">
                    </div>
                    <a href="https://line.me/R/ti/p/{{ $lineSettings->line_id }}"
                       target="_blank"
                       class="mt-3 inline-flex items-center gap-2 text-green-600 dark:text-green-400 hover:underline">
                        <i class="fas fa-external-link-alt"></i>
                        เปิดใน LINE App
                    </a>
                </div>
                @endif

                {{-- Secondary Actions --}}
                <div class="flex items-center justify-center gap-4 text-sm pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('user.dashboard') }}"
                       class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        กลับหน้า Dashboard
                    </a>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <a href="{{ route('home') }}"
                       class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors flex items-center gap-2">
                        <i class="fas fa-home"></i>
                        หน้าหลัก
                    </a>
                </div>
            </div>
        </div>

        {{-- Footer Note --}}
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                <i class="fas fa-lock mr-1"></i>
                ข้อมูล LINE ของคุณจะถูกเก็บรักษาอย่างปลอดภัยและใช้เพื่อการยืนยันตัวตนเท่านั้น
            </p>
        </div>
    </div>

    {{-- Help Section --}}
    <div class="mt-6 text-center">
        <p class="text-gray-500 dark:text-gray-400 text-sm">
            มีปัญหาในการเชื่อมต่อ LINE?
            <a href="{{ route('home') }}#contact" class="text-emerald-600 dark:text-emerald-400 hover:underline">ติดต่อเรา</a>
        </p>
    </div>
</div>
@endsection
