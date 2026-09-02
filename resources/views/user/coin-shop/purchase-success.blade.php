{{--
    หน้าซื้อสินค้าสำเร็จ
--}}

@extends('layouts.user-v4')

@section('title', 'ซื้อสินค้าสำเร็จ')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        {{-- Premium Hero Header (Green-Emerald for Success) --}}
        <div class="relative overflow-hidden bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 dark:from-green-800 dark:via-emerald-800 dark:to-teal-800 rounded-2xl shadow-2xl p-8 mb-8">
            {{-- Animated Background Orbs --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
            </div>

            {{-- Floating Icon Background --}}
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute text-white/10 text-8xl top-10 right-20 animate-bounce">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>

            {{-- Content --}}
            <div class="relative z-10 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 glass-fusion rounded-full mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg mb-2">
                    ✓ ซื้อสินค้าสำเร็จ!
                </h1>
                <p class="text-green-100 text-lg">
                    ขอบคุณที่ใช้บริการร้านค้า Coins
                </p>
            </div>
        </div>

        {{-- Order Details --}}
        <div class="tp-card rounded-2xl overflow-hidden">
            {{-- Header --}}
            <div class="p-6 bg-gradient-to-r from-yellow-400 to-orange-500 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm">เลขที่ใบสั่งซื้อ</p>
                        <p class="text-xl font-bold">{{ $purchase->order_number }}</p>
                    </div>
                    <span class="px-4 py-2 bg-white/20 rounded-lg text-sm font-medium">
                        {{ $purchase->status_icon }} {{ $purchase->status_name }}
                    </span>
                </div>
            </div>

            {{-- Product Info --}}
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-start gap-4">
                    @if($purchase->product?->thumbnail)
                        <img src="{{ $purchase->product->thumbnail_url }}"
                             alt="{{ $purchase->product_name }}"
                             class="w-20 h-20 rounded-lg object-cover">
                    @else
                        <div class="w-20 h-20 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <span class="text-3xl">{{ $purchase->product_type_info['icon'] ?? '🎁' }}</span>
                        </div>
                    @endif
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 dark:text-white">{{ $purchase->product_name }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $purchase->product_type_info['name'] ?? $purchase->product_type }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            จำนวน: {{ $purchase->quantity }} ชิ้น
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ number_format($purchase->total_coins, 0) }}
                        </p>
                        <p class="text-sm text-gray-500">Coins</p>
                    </div>
                </div>
            </div>

            {{-- Redemption Code (if applicable) --}}
            @if($purchase->redemption_code)
            <div class="p-6 bg-yellow-50 dark:bg-yellow-900/30 border-b border-yellow-200 dark:border-yellow-800">
                <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">รหัสใช้งาน</h4>
                <div class="flex items-center gap-3">
                    <code class="tp-card flex-1 px-4 py-3 border-2 border-dashed border-yellow-400 rounded-lg text-center text-2xl font-mono font-bold text-gray-900 dark:text-white tracking-wider">
                        {{ $purchase->redemption_code }}
                    </code>
                    <button type="button"
                            onclick="navigator.clipboard.writeText('{{ $purchase->redemption_code }}'); alert('คัดลอกแล้ว!')"
                            class="px-4 py-3 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
                @if($purchase->expires_at)
                <p class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                    หมดอายุ: {{ $purchase->expires_at->format('d/m/Y H:i') }}
                </p>
                @endif
            </div>
            @endif

            {{-- Order Summary --}}
            <div class="p-6 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">ราคาต่อชิ้น</span>
                    <span class="text-gray-900 dark:text-white">{{ number_format($purchase->price_coins, 0) }} Coins</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">จำนวน</span>
                    <span class="text-gray-900 dark:text-white">{{ $purchase->quantity }} ชิ้น</span>
                </div>
                @if($purchase->discount_coins > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">ส่วนลด</span>
                    <span class="text-green-600 dark:text-green-400">-{{ number_format($purchase->discount_coins, 0) }} Coins</span>
                </div>
                @endif
                <div class="flex justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-3">
                    <span class="text-gray-900 dark:text-white">รวมทั้งหมด</span>
                    <span class="text-yellow-600 dark:text-yellow-400">{{ number_format($purchase->net_price, 0) }} Coins</span>
                </div>
            </div>

            {{-- Purchase Date --}}
            <div class="px-6 pb-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    วันที่ซื้อ: {{ $purchase->created_at->format('d/m/Y H:i:s') }}
                </p>
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-6 flex flex-col sm:flex-row gap-4">
            <a href="{{ route('user.coin-shop.purchase-detail', $purchase) }}"
               class="flex-1 px-6 py-3 bg-gray-800 dark:bg-gray-700 hover:bg-gray-900 dark:hover:bg-gray-600 text-white text-center font-medium rounded-xl transition">
                ดูรายละเอียดคำสั่งซื้อ
            </a>
            <a href="{{ route('user.coin-shop.index') }}"
               class="flex-1 px-6 py-3 bg-gradient-to-r from-yellow-400 to-orange-500 hover:from-yellow-500 hover:to-orange-600 text-white text-center font-medium rounded-xl transition">
                ซื้อสินค้าต่อ
            </a>
        </div>

        {{-- Note for physical products --}}
        @if($purchase->product_type === 'physical')
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-xl">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h4 class="font-medium text-blue-900 dark:text-blue-100">สำหรับสินค้าจริง</h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        ทีมงานจะติดต่อกลับเพื่อยืนยันที่อยู่จัดส่งภายใน 1-2 วันทำการ
                    </p>
                </div>
            </div>
        </div>
        @endif
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
