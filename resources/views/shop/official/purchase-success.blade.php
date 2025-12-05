{{--
    Official Shop - Purchase Success Page
    หน้าแสดงผลเมื่อซื้อสินค้าด้วย Coins สำเร็จ
--}}

@extends('layouts.storefront')

@section('title', 'ซื้อสำเร็จ - Official Shop')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-12">
    <div class="container mx-auto px-4">
        {{-- Success Card --}}
        <div class="max-w-2xl mx-auto">
            {{-- Success Animation --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24
                           bg-gradient-to-br from-green-400 to-emerald-500
                           rounded-full shadow-2xl shadow-green-500/30
                           animate-bounce">
                    <i class="fas fa-check text-white text-4xl"></i>
                </div>
            </div>

            {{-- Main Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden
                       border border-gray-100 dark:border-gray-700">
                {{-- Header --}}
                <div class="bg-gradient-to-r from-green-500 via-emerald-500 to-teal-500 p-8 text-center">
                    <h1 class="text-3xl font-bold text-white mb-2">ซื้อสำเร็จ!</h1>
                    <p class="text-green-100">ขอบคุณที่ซื้อสินค้าจาก Official Shop</p>
                </div>

                {{-- Order Details --}}
                <div class="p-8">
                    {{-- Order Number --}}
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl mb-6">
                        <span class="text-gray-600 dark:text-gray-400">เลขที่คำสั่งซื้อ</span>
                        <span class="font-bold text-gray-900 dark:text-white font-mono">{{ $order->order_number }}</span>
                    </div>

                    {{-- Items --}}
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4">รายการสินค้า</h3>
                    <div class="space-y-4 mb-6">
                        @foreach($order->items as $item)
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                            @if($item->product && $item->product->main_image_url)
                            <img src="{{ $item->product->main_image_url }}"
                                 alt="{{ $item->product_name }}"
                                 class="w-16 h-16 object-cover rounded-lg">
                            @else
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-box text-gray-400 text-xl"></i>
                            </div>
                            @endif
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $item->product_name }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">x{{ $item->quantity }}</p>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center gap-1 text-yellow-600 dark:text-yellow-400 font-bold">
                                    <i class="fas fa-coins"></i>
                                    <span>{{ number_format($item->subtotal_coins ?? $item->price_coins * $item->quantity, 0) }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Total --}}
                    <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                        <div class="flex items-center justify-between text-lg">
                            <span class="font-semibold text-gray-700 dark:text-gray-300">Coins ที่ใช้</span>
                            <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400 font-bold text-xl">
                                <i class="fas fa-coins"></i>
                                <span>{{ number_format($order->coins_used, 0) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Status Badge --}}
                    <div class="mt-6 p-4 bg-green-50 dark:bg-green-900/30 rounded-xl border border-green-200 dark:border-green-700">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-white"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-green-800 dark:text-green-200">คำสั่งซื้อสำเร็จ</p>
                                <p class="text-sm text-green-600 dark:text-green-400">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-4 mt-8">
                        <a href="{{ route('official-shop.index') }}"
                           class="flex-1 px-6 py-4 bg-gradient-to-r from-amber-500 to-orange-500
                                 hover:from-amber-600 hover:to-orange-600
                                 text-white font-bold rounded-xl text-center
                                 shadow-lg hover:shadow-xl transition-all">
                            <i class="fas fa-store mr-2"></i>
                            กลับไป Official Shop
                        </a>
                        <a href="{{ route('user.orders.index') }}"
                           class="flex-1 px-6 py-4 bg-gray-100 dark:bg-gray-700
                                 hover:bg-gray-200 dark:hover:bg-gray-600
                                 text-gray-700 dark:text-gray-200 font-bold rounded-xl text-center
                                 transition-all">
                            <i class="fas fa-list mr-2"></i>
                            ดูประวัติคำสั่งซื้อ
                        </a>
                    </div>
                </div>
            </div>

            {{-- Additional Info --}}
            <div class="mt-8 text-center">
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    <i class="fas fa-envelope mr-2"></i>
                    รายละเอียดคำสั่งซื้อจะถูกส่งไปยังอีเมลของคุณ
                </p>
                <div class="flex items-center justify-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                    <a href="{{ route('home') }}" class="hover:text-amber-500 transition-colors">
                        <i class="fas fa-home mr-1"></i> หน้าหลัก
                    </a>
                    <span>|</span>
                    <a href="{{ route('user.coin-shop.index') }}" class="hover:text-amber-500 transition-colors">
                        <i class="fas fa-coins mr-1"></i> Coin Shop
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
