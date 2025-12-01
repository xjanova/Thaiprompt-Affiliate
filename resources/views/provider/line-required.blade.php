{{--
    หน้าบังคับเชื่อมต่อ LINE สำหรับสมัครเป็นผู้ให้บริการ
    จะแสดงเมื่อผู้ใช้ยังไม่ได้เชื่อมต่อ LINE
--}}
@extends('layouts.provider-arrow-x')

@section('title', 'เชื่อมต่อ LINE เพื่อสมัครเป็นผู้ให้บริการ')

@section('page-title', 'เชื่อมต่อ LINE')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    {{-- Main Card --}}
    <div class="glass-fusion border border-white/20 rounded-3xl shadow-2xl overflow-hidden">
        {{-- Header --}}
        <div class="p-6 bg-gradient-to-r from-green-600/80 to-emerald-600/80 text-center">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-white/20 rounded-full mb-4 ring-4 ring-white/30">
                <svg class="w-14 h-14 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .195-.095.378-.246.498l-3.18 2.323 3.18 2.323c.151.12.246.303.246.498 0 .346-.281.631-.63.631-.178 0-.347-.076-.463-.199l-3.477-2.538-3.477 2.538c-.116.123-.285.199-.463.199-.349 0-.63-.285-.63-.631 0-.195.095-.378.246-.498l3.18-2.323-3.18-2.323c-.151-.12-.246-.303-.246-.498 0-.346.281-.631.63-.631.178 0 .347.076.463.199l3.477 2.538 3.477-2.538c.116-.123.285-.199.463-.199M12 2C6.477 2 2 6.145 2 11.259c0 4.017 2.892 7.445 7.017 8.497l-.244 3.176c-.036.464.464.799.928.599l3.889-1.944c.131-.066.247-.159.336-.271C18.163 20.585 22 16.324 22 11.259 22 6.145 17.523 2 12 2"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-white drop-shadow-lg mb-2">
                เชื่อมต่อ LINE ก่อนสมัคร
            </h1>
            <p class="text-white/80 text-lg">
                กรุณาเชื่อมต่อบัญชี LINE เพื่อสมัครเป็นผู้ให้บริการ
            </p>
        </div>

        {{-- Content --}}
        <div class="p-6 md:p-8">
            {{-- Why LINE is required --}}
            <div class="mb-8">
                <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-question-circle text-emerald-400"></i>
                    ทำไมต้องเชื่อมต่อ LINE?
                </h2>
                <div class="space-y-3">
                    <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl border border-white/10">
                        <div class="w-10 h-10 bg-green-500/30 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-bell text-green-300"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-white">รับการแจ้งเตือนงานใหม่</p>
                            <p class="text-sm text-white/70">แจ้งเตือนทันทีเมื่อมีงานใหม่เข้ามาผ่าน LINE</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl border border-white/10">
                        <div class="w-10 h-10 bg-blue-500/30 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-comments text-blue-300"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-white">สื่อสารกับลูกค้า</p>
                            <p class="text-sm text-white/70">แชทกับลูกค้าผ่าน LINE OA ได้สะดวก</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl border border-white/10">
                        <div class="w-10 h-10 bg-purple-500/30 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-shield-alt text-purple-300"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-white">ยืนยันตัวตน</p>
                            <p class="text-sm text-white/70">ช่วยยืนยันตัวตนและเพิ่มความน่าเชื่อถือ</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-3 bg-white/5 rounded-xl border border-white/10">
                        <div class="w-10 h-10 bg-orange-500/30 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-bolt text-orange-300"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-white">รับงานได้เร็วขึ้น</p>
                            <p class="text-sm text-white/70">ตอบรับงานได้ทันทีผ่าน LINE ไม่พลาดทุกงาน</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Current Status --}}
            <div class="p-4 bg-red-500/20 border border-red-400/30 rounded-xl mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-red-500/30 rounded-full flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-300 text-xl"></i>
                    </div>
                    <div>
                        <p class="font-bold text-red-200">ยังไม่ได้เชื่อมต่อ LINE</p>
                        <p class="text-sm text-red-200/80">
                            บัญชีของคุณยังไม่ได้เชื่อมต่อกับ LINE กรุณาเชื่อมต่อก่อนดำเนินการต่อ
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

                {{-- Secondary Actions --}}
                <div class="flex items-center justify-center gap-4 text-sm">
                    <a href="{{ route('user.dashboard') }}"
                       class="text-white/70 hover:text-white transition-colors flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        กลับหน้า Dashboard
                    </a>
                    <span class="text-white/30">|</span>
                    <a href="{{ route('home') }}"
                       class="text-white/70 hover:text-white transition-colors flex items-center gap-2">
                        <i class="fas fa-home"></i>
                        หน้าหลัก
                    </a>
                </div>
            </div>
        </div>

        {{-- Footer Note --}}
        <div class="px-6 py-4 bg-white/5 border-t border-white/10">
            <p class="text-sm text-white/60 text-center">
                <i class="fas fa-lock mr-1"></i>
                ข้อมูล LINE ของคุณจะถูกเก็บรักษาอย่างปลอดภัยและใช้เพื่อการแจ้งเตือนเท่านั้น
            </p>
        </div>
    </div>

    {{-- Help Section --}}
    <div class="mt-6 text-center">
        <p class="text-white/60 text-sm">
            มีปัญหาในการเชื่อมต่อ LINE?
            <a href="#" class="text-emerald-400 hover:underline">ติดต่อเรา</a>
        </p>
    </div>
</div>
@endsection

@push('styles')
<style>
/**
 * Glass Fusion Effect สำหรับ Cards
 */
.glass-fusion {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}
</style>
@endpush
