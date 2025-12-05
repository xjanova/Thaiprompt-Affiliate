{{--
    หน้ารายละเอียดคำสั่งซื้อ
--}}

@extends('layouts.user-arrow-x')

@section('title', $pageTitle ?? 'รายละเอียดคำสั่งซื้อ')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        {{-- Breadcrumb --}}
        <nav class="mb-6">
            <ol class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('user.coin-shop.index') }}" class="hover:text-yellow-600">ร้านค้า Coins</a></li>
                <li class="mx-2">/</li>
                <li><a href="{{ route('user.coin-shop.my-purchases') }}" class="hover:text-yellow-600">ประวัติการซื้อ</a></li>
                <li class="mx-2">/</li>
                <li class="text-gray-900 dark:text-white font-medium">{{ $purchase->order_number }}</li>
            </ol>
        </nav>

        {{-- Order Header --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-6">
            <div class="p-6 bg-gradient-to-r from-gray-800 to-gray-900 text-white">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-gray-400 text-sm">เลขที่ใบสั่งซื้อ</p>
                        <p class="text-2xl font-bold">{{ $purchase->order_number }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-4 py-2 bg-{{ $purchase->status_color }}-500 rounded-lg text-sm font-bold">
                            {{ $purchase->status_icon }} {{ $purchase->status_name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Product Info --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-6">
            <div class="p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลสินค้า</h3>
                <div class="flex items-start gap-4">
                    @if($purchase->product?->thumbnail)
                        <img src="{{ $purchase->product->thumbnail_url }}"
                             alt="{{ $purchase->product_name }}"
                             class="w-24 h-24 rounded-lg object-cover">
                    @else
                        <div class="w-24 h-24 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <span class="text-4xl">{{ $purchase->product_type_info['icon'] ?? '🎁' }}</span>
                        </div>
                    @endif
                    <div class="flex-1">
                        <h4 class="font-bold text-lg text-gray-900 dark:text-white">{{ $purchase->product_name }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            ประเภท: {{ $purchase->product_type_info['name'] ?? $purchase->product_type }}
                        </p>
                        @if($purchase->product_value)
                        <p class="text-sm text-green-600 dark:text-green-400 mt-1">
                            มูลค่า: {{ number_format($purchase->product_value, 0) }} {{ $purchase->product_value_unit ?? '' }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Redemption Code --}}
        @if($purchase->redemption_code)
        <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-2xl overflow-hidden mb-6">
            <div class="p-6">
                <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-3">รหัสใช้งาน</h3>
                <div class="flex items-center gap-3">
                    <code class="flex-1 px-4 py-3 bg-white dark:bg-gray-800 border-2 border-dashed border-yellow-400 rounded-lg text-center text-2xl font-mono font-bold text-gray-900 dark:text-white tracking-wider">
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
                <p class="mt-3 text-sm {{ $purchase->is_expired ? 'text-red-600' : 'text-yellow-700 dark:text-yellow-300' }}">
                    @if($purchase->is_expired)
                        หมดอายุแล้ว ({{ $purchase->expires_at->format('d/m/Y H:i') }})
                    @else
                        หมดอายุ: {{ $purchase->expires_at->format('d/m/Y H:i') }} ({{ $purchase->expires_at->diffForHumans() }})
                    @endif
                </p>
                @endif

                @if($purchase->is_usable)
                <div class="mt-4">
                    <form action="{{ route('user.coin-shop.use-item', $purchase) }}" method="POST"
                          onsubmit="return confirm('ยืนยันการใช้งานรหัสนี้?')">
                        @csrf
                        <button type="submit"
                                class="w-full py-3 bg-green-500 hover:bg-green-600 text-white font-bold rounded-lg transition">
                            ✓ ทำเครื่องหมายว่าใช้งานแล้ว
                        </button>
                    </form>
                </div>
                @elseif($purchase->status === 'used')
                <div class="mt-4 p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <p class="text-purple-800 dark:text-purple-200 text-sm">
                        ✓ ใช้งานแล้วเมื่อ {{ $purchase->used_at?->format('d/m/Y H:i') }}
                        @if($purchase->used_location)
                            • {{ $purchase->used_location }}
                        @endif
                    </p>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Tracking Info (for physical products) --}}
        @if($purchase->product_type === 'physical')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-6">
            <div class="p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลจัดส่ง</h3>
                @if($purchase->tracking_number)
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">เลขพัสดุ:</span>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $purchase->tracking_number }}</span>
                        </div>
                        @if($purchase->shipped_at)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">วันที่จัดส่ง:</span>
                            <span class="text-gray-900 dark:text-white">{{ $purchase->shipped_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @endif
                        @if($purchase->delivered_at)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">วันที่ได้รับ:</span>
                            <span class="text-green-600 dark:text-green-400">{{ $purchase->delivered_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @endif
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">กำลังเตรียมจัดส่ง...</p>
                @endif
            </div>
        </div>
        @endif

        {{-- Order Summary --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-6">
            <div class="p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">สรุปคำสั่งซื้อ</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">ราคาต่อชิ้น</span>
                        <span class="text-gray-900 dark:text-white">{{ number_format($purchase->price_coins, 2) }} Coins</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">จำนวน</span>
                        <span class="text-gray-900 dark:text-white">{{ $purchase->quantity }} ชิ้น</span>
                    </div>
                    @if($purchase->discount_coins > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">ส่วนลด</span>
                        <span class="text-green-600 dark:text-green-400">-{{ number_format($purchase->discount_coins, 2) }} Coins</span>
                    </div>
                    @endif
                    <div class="flex justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-3">
                        <span class="text-gray-900 dark:text-white">รวมทั้งหมด</span>
                        <span class="text-yellow-600 dark:text-yellow-400">{{ number_format($purchase->net_price, 2) }} Coins</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Timeline --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
            <div class="p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ไทม์ไลน์</h3>
                <div class="space-y-4">
                    {{-- Created --}}
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="w-px h-full bg-gray-200 dark:bg-gray-700"></div>
                        </div>
                        <div class="pb-4">
                            <p class="font-medium text-gray-900 dark:text-white">สั่งซื้อสำเร็จ</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $purchase->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>

                    {{-- Used (if applicable) --}}
                    @if($purchase->used_at)
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">ใช้งานแล้ว</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $purchase->used_at->format('d/m/Y H:i:s') }}</p>
                            @if($purchase->used_note)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $purchase->used_note }}</p>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Refunded (if applicable) --}}
                    @if($purchase->refunded_at)
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">คืนเงินแล้ว</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $purchase->refunded_at->format('d/m/Y H:i:s') }}</p>
                            <p class="text-sm text-orange-600 dark:text-orange-400">+{{ number_format($purchase->refunded_coins, 2) }} Coins</p>
                            @if($purchase->refund_reason)
                            <p class="text-sm text-gray-600 dark:text-gray-400">เหตุผล: {{ $purchase->refund_reason }}</p>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-6 flex flex-col sm:flex-row gap-4">
            <a href="{{ route('user.coin-shop.my-purchases') }}"
               class="flex-1 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-center font-medium rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                กลับไปประวัติการซื้อ
            </a>
            @if($purchase->product)
            <a href="{{ route('user.coin-shop.product', $purchase->product) }}"
               class="flex-1 px-6 py-3 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-center font-medium rounded-xl hover:from-yellow-500 hover:to-orange-600 transition">
                ซื้อสินค้านี้อีกครั้ง
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
