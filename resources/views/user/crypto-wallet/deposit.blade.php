@extends('layouts.user-arrow-x')

@section('title', 'ฝากเหรียญคริปโต')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Premium Hero Header (Green-Emerald for Deposit) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 dark:from-green-800 dark:via-emerald-800 dark:to-teal-800 rounded-2xl shadow-2xl p-8 mb-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icon Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-download"></i>
            </div>
        </div>

        {{-- Content --}}
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-arrow-down text-3xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">
                            📥 ฝากเหรียญคริปโต
                        </h1>
                        <p class="text-green-100 mt-1">
                            เลือกสกุลเงินที่ต้องการฝาก
                        </p>
                    </div>
                </div>
                <a href="{{ route('user.crypto-wallet.index') }}"
                   class="glass-fusion hover:bg-white/30 rounded-lg px-4 py-2 text-white font-medium transition-all flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden md:inline">กลับหน้าหลัก</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-6">
        <h3 class="font-bold text-blue-900 dark:text-blue-300 mb-3 flex items-center">
            <span class="text-2xl mr-2">ℹ️</span>
            วิธีการฝากเหรียญ
        </h3>
        <ol class="list-decimal list-inside space-y-2 text-blue-800 dark:text-blue-300 text-sm">
            <li>เลือกสกุลเงินที่ต้องการฝาก</li>
            <li>คัดลอกที่อยู่กระเป๋า (Wallet Address) หรือสแกน QR Code</li>
            <li>โอนเหรียญจากกระเป๋าภายนอก (Exchange, MetaMask, ฯลฯ)</li>
            <li>รอการยืนยันจาก Blockchain (ใช้เวลาขึ้นอยู่กับแต่ละเชน)</li>
            <li>เหรียญจะเข้ากระเป๋าอัตโนมัติเมื่อได้รับการยืนยันเพียงพอ</li>
        </ol>
    </div>

    <!-- Currency Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($currencies as $currency)
            <a href="{{ route('user.crypto-wallet.deposit.currency', $currency->code) }}"
               class="group bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl transition-all p-6 border-2 border-transparent hover:border-amber-500">
                <div class="flex items-center mb-4">
                    @php
                        $iconPath = public_path('icons/cryptocurrency/' . strtolower($currency->code) . '.svg');
                    @endphp
                    @if(file_exists($iconPath))
                        <img src="{{ asset('icons/cryptocurrency/' . strtolower($currency->code) . '.svg') }}"
                             alt="{{ $currency->code }}"
                             class="w-14 h-14 mr-4">
                    @else
                        <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl mr-4"
                             style="background: {{ $currency->color ?? 'linear-gradient(135deg, #F59E0B, #D97706)' }};">
                            <span class="text-white font-bold">{{ substr($currency->code, 0, 1) }}</span>
                        </div>
                    @endif
                    <div>
                        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ $currency->code }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $currency->name }}</p>
                    </div>
                </div>

                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">เครือข่าย:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-100 uppercase">{{ $currency->network }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">ฝากขั้นต่ำ:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ number_format($currency->min_deposit, 8) }} {{ $currency->code }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">การยืนยัน:</span>
                        <span class="font-medium text-gray-800 dark:text-gray-100">{{ $currency->confirmations_required }} blocks</span>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    @if($currency->deposit_enabled)
                        <span class="text-green-600 dark:text-green-400 text-sm font-medium">✓ พร้อมใช้งาน</span>
                    @else
                        <span class="text-red-600 dark:text-red-400 text-sm font-medium">✕ ปิดใช้งานชั่วคราว</span>
                    @endif

                    <span class="text-amber-600 group-hover:text-amber-700 font-medium">
                        เลือก →
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    @if($currencies->isEmpty())
        <div class="text-center py-12">
            <div class="text-6xl mb-4">🚫</div>
            <p class="text-gray-500 dark:text-gray-400">ไม่มีสกุลเงินที่สามารถฝากได้ในขณะนี้</p>
        </div>
    @endif

    <!-- Warning -->
    <div class="mt-8 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6">
        <h4 class="font-bold text-yellow-900 dark:text-yellow-300 mb-2 flex items-center">
            <span class="text-xl mr-2">⚠️</span>
            ข้อควรระวัง
        </h4>
        <ul class="list-disc list-inside space-y-1 text-yellow-800 dark:text-yellow-300 text-sm">
            <li>โปรดตรวจสอบเครือข่าย (Network) ให้ถูกต้องก่อนโอน มิฉะนั้นเหรียญอาจสูญหาย</li>
            <li>ส่งเฉพาะสกุลเงินที่ตรงกันเท่านั้น เช่น ที่อยู่ BTC รับได้เฉพาะ BTC</li>
            <li>ระบบจะเริ่มนับการยืนยันหลังจากธุรกรรมปรากฏบน Blockchain</li>
            <li>อย่าส่งเหรียญจากสัญญา Smart Contract โดยตรง อาจทำให้เหรียญสูญหาย</li>
        </ul>
    </div>
</div>
@endsection

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
