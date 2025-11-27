{{--
    หน้าสถานะการลงทะเบียนผู้ให้บริการ - Arrow X V3 Theme
    แสดงสถานะการตรวจสอบและอนุมัติ
--}}
@extends('layouts.provider-arrow-x')

@section('title', 'สถานะการลงทะเบียน')

@section('page-title', 'สถานะการลงทะเบียน')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="glass-fusion border border-white/20 rounded-2xl shadow-xl overflow-hidden">
        {{-- Header --}}
        <div class="p-6 text-center
            @if($provider->verification_status === 'pending')
                bg-gradient-to-r from-yellow-500/80 to-orange-500/80
            @elseif($provider->verification_status === 'approved')
                bg-gradient-to-r from-green-500/80 to-emerald-500/80
            @else
                bg-gradient-to-r from-red-500/80 to-pink-500/80
            @endif
            text-white border-b border-white/20">

            @if($provider->verification_status === 'pending')
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4">
                    <i class="fas fa-hourglass-half text-4xl animate-pulse"></i>
                </div>
                <h1 class="text-2xl font-bold mb-2">กำลังตรวจสอบข้อมูล</h1>
                <p class="text-white/90">ทีมงานกำลังตรวจสอบข้อมูลของคุณ</p>
            @elseif($provider->verification_status === 'approved')
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4">
                    <i class="fas fa-check-circle text-4xl"></i>
                </div>
                <h1 class="text-2xl font-bold mb-2">อนุมัติแล้ว!</h1>
                <p class="text-white/90">ยินดีด้วย คุณได้รับการอนุมัติเป็นผู้ให้บริการแล้ว</p>
            @else
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4">
                    <i class="fas fa-times-circle text-4xl"></i>
                </div>
                <h1 class="text-2xl font-bold mb-2">ไม่ผ่านการตรวจสอบ</h1>
                <p class="text-white/90">กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง</p>
            @endif
        </div>

        {{-- Content --}}
        <div class="p-6 space-y-6">
            {{-- Status Timeline --}}
            <div class="space-y-4">
                <h3 class="font-bold text-white">สถานะการสมัคร</h3>

                <div class="relative">
                    {{-- Timeline Line --}}
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-white/20"></div>

                    {{-- Step 1: Submitted --}}
                    <div class="relative flex items-start gap-4 pb-6">
                        <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center z-10">
                            <i class="fas fa-check text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-white">ส่งใบสมัคร</p>
                            <p class="text-sm text-white/60">
                                {{ $provider->created_at->thaidate('j F Y H:i น.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Step 2: Reviewing --}}
                    <div class="relative flex items-start gap-4 pb-6">
                        @if($provider->verification_status === 'pending')
                            <div class="w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center z-10 animate-pulse">
                                <i class="fas fa-search text-white text-sm"></i>
                            </div>
                        @elseif(in_array($provider->verification_status, ['approved', 'rejected']))
                            <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center z-10">
                                <i class="fas fa-check text-white text-sm"></i>
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-white/30 flex items-center justify-center z-10">
                                <span class="text-white text-sm">2</span>
                            </div>
                        @endif
                        <div>
                            <p class="font-semibold text-white">กำลังตรวจสอบ</p>
                            <p class="text-sm text-white/60">
                                @if($provider->verification_status === 'pending')
                                    ทีมงานกำลังตรวจสอบข้อมูลของคุณ
                                @else
                                    ตรวจสอบเสร็จสิ้นแล้ว
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Step 3: Result --}}
                    <div class="relative flex items-start gap-4">
                        @if($provider->verification_status === 'approved')
                            <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center z-10">
                                <i class="fas fa-check text-white text-sm"></i>
                            </div>
                        @elseif($provider->verification_status === 'rejected')
                            <div class="w-8 h-8 rounded-full bg-red-500 flex items-center justify-center z-10">
                                <i class="fas fa-times text-white text-sm"></i>
                            </div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-white/30 flex items-center justify-center z-10">
                                <span class="text-white text-sm">3</span>
                            </div>
                        @endif
                        <div>
                            <p class="font-semibold text-white">
                                @if($provider->verification_status === 'approved')
                                    อนุมัติแล้ว
                                @elseif($provider->verification_status === 'rejected')
                                    ไม่ผ่านการตรวจสอบ
                                @else
                                    รอผลการตรวจสอบ
                                @endif
                            </p>
                            <p class="text-sm text-white/60">
                                @if($provider->verification_status === 'approved')
                                    {{ $provider->verified_at?->thaidate('j F Y H:i น.') ?? 'ผ่านการอนุมัติแล้ว' }}
                                @elseif($provider->verification_status === 'rejected')
                                    @if($provider->rejection_reason)
                                        เหตุผล: {{ $provider->rejection_reason }}
                                    @else
                                        กรุณาติดต่อทีมงาน
                                    @endif
                                @else
                                    ปกติใช้เวลา 1-2 วันทำการ
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Provider Info Summary --}}
            <div class="border-t border-white/20 pt-6">
                <h3 class="font-bold text-white mb-4">ข้อมูลที่ลงทะเบียน</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-white/60">ชื่อที่แสดง</span>
                        <span class="font-semibold text-white">{{ $provider->display_name ?? $provider->name }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-white/60">เบอร์โทร</span>
                        <span class="font-semibold text-white">{{ $provider->phone }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-white/60">หมวดหมู่</span>
                        <span class="font-semibold text-white">
                            {{ $provider->categories->pluck('name')->join(', ') ?: '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-white/60">พื้นที่บริการ</span>
                        <span class="font-semibold text-white">
                            {{ $provider->areas->count() }} พื้นที่
                        </span>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="border-t border-white/20 pt-6 space-y-3">
                @if($provider->verification_status === 'approved')
                    <a href="{{ route('provider.bookings.index') }}"
                       class="block w-full py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 text-white font-bold rounded-xl text-center transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-play mr-2"></i>
                        เริ่มรับงาน
                    </a>
                @elseif($provider->verification_status === 'rejected')
                    <a href="{{ route('provider.register') }}"
                       class="block w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white font-bold rounded-xl text-center transition-all duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-redo mr-2"></i>
                        สมัครใหม่
                    </a>
                @endif

                <a href="{{ route('user.dashboard') }}"
                   class="block w-full py-3 bg-white/10 border border-white/30 text-white font-semibold rounded-xl text-center hover:bg-white/20 transition-all duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับหน้าหลัก
                </a>
            </div>

            {{-- Help --}}
            @if($provider->verification_status === 'pending')
                <div class="p-4 bg-blue-500/20 border border-blue-400/30 rounded-xl">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-blue-300 text-xl mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-blue-200">ต้องการความช่วยเหลือ?</p>
                            <p class="text-sm text-blue-200/80">
                                หากรอนานเกิน 2 วันทำการ หรือต้องการสอบถามข้อมูลเพิ่มเติม
                                กรุณาติดต่อ <a href="#" class="underline hover:text-white">ฝ่ายสนับสนุน</a>
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
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
