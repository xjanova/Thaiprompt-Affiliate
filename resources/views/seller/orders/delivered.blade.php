{{--
    หน้าคำสั่งซื้อที่ส่งสำเร็จ
    แสดงรายการที่ส่งถึงลูกค้าแล้ว
--}}
@extends('layouts.seller')

@section('title', 'ส่งสำเร็จ')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">✅ ส่งสำเร็จ</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">คำสั่งซื้อที่ส่งถึงลูกค้าเรียบร้อยแล้ว</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('seller.orders.pending-shipping') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors">
                <span>📋</span>
                <span>รอจัดส่ง</span>
            </a>
            <a href="{{ route('seller.orders.shipped') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition-colors">
                <span>🚚</span>
                <span>จัดส่งแล้ว</span>
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-green-100">ส่งสำเร็จทั้งหมด</p>
                    <p class="text-4xl font-bold">{{ number_format($stats['delivered']) }}</p>
                    <p class="text-green-200 text-sm mt-1">คำสั่งซื้อ</p>
                </div>
                <div class="text-6xl opacity-80">✅</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-emerald-100">สำเร็จเดือนนี้</p>
                    <p class="text-4xl font-bold">{{ number_format($stats['completed_this_month']) }}</p>
                    <p class="text-emerald-200 text-sm mt-1">คำสั่งซื้อ</p>
                </div>
                <div class="text-6xl opacity-80">📊</div>
            </div>
        </div>
    </div>

    {{-- Orders List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-green-50 dark:bg-green-900/20">
            <div class="flex items-center gap-2">
                <span class="text-lg">🎉</span>
                <h2 class="text-lg font-semibold text-green-700 dark:text-green-300">คำสั่งซื้อที่ส่งสำเร็จ</h2>
            </div>
        </div>

        @if($orders->count() > 0)
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($orders as $order)
                    @php
                        $sellerItems = $order->items->where('seller_id', auth()->id());
                        $sellerTotal = $sellerItems->sum('seller_earning');
                    @endphp
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                            {{-- Order Info --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">#{{ $order->order_number }}</span>
                                    <span class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs px-2 py-1 rounded-full font-medium flex items-center gap-1">
                                        <span>✅</span>
                                        <span>ส่งสำเร็จ</span>
                                    </span>
                                </div>

                                {{-- Tracking Info --}}
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4 mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-2xl text-white">
                                            ✓
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                {{ $order->shippingProvider->name ?? $order->shipping_provider ?? 'ขนส่ง' }}
                                            </p>
                                            <p class="text-gray-600 dark:text-gray-400 font-mono">
                                                {{ $order->tracking_number }}
                                            </p>
                                            @if($order->delivered_at)
                                                <p class="text-green-600 dark:text-green-400 text-sm mt-1">
                                                    ส่งถึงเมื่อ {{ $order->delivered_at->format('d/m/Y H:i') }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Customer --}}
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    <span>👤</span>
                                    <span class="font-medium">{{ $order->user->name }}</span>
                                    <span>•</span>
                                    <span>{{ $order->user->phone ?? $order->user->email }}</span>
                                </div>

                                {{-- Products & Earnings --}}
                                <div class="flex items-center gap-4 text-sm">
                                    <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                        <span>📦</span>
                                        <span>{{ $sellerItems->count() }} สินค้า</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-gray-500 dark:text-gray-400">รายได้:</span>
                                        <span class="text-green-600 font-bold">฿{{ number_format($sellerTotal, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex flex-col gap-2 lg:w-48">
                                <a href="{{ route('seller.orders.tracking', $order->id) }}"
                                   class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-center transition-colors flex items-center justify-center gap-1">
                                    <span>📍</span>
                                    <span>ดูประวัติการส่ง</span>
                                </a>
                                <a href="{{ route('seller.orders.tracking', $order->id) }}?tab=chat"
                                   class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-center transition-colors flex items-center justify-center gap-1">
                                    <span>💬</span>
                                    <span>แชท</span>
                                    @if($order->has_unread_messages)
                                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                    @endif
                                </a>
                                <a href="{{ route('seller.orders.show', $order) }}"
                                   class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-center transition-colors">
                                    ดูรายละเอียด
                                </a>
                            </div>
                        </div>

                        {{-- Time --}}
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                            <span>สั่งซื้อเมื่อ: {{ $order->created_at->format('d/m/Y H:i') }}</span>
                            <span class="text-green-500 font-medium">
                                ส่งถึงใน {{ $order->shipped_at && $order->delivered_at ? $order->shipped_at->diffInDays($order->delivered_at) : '-' }} วัน
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $orders->links() }}
            </div>
        @else
            <div class="text-center py-16">
                <div class="text-7xl mb-4">📦</div>
                <p class="text-xl text-gray-500 dark:text-gray-400">ยังไม่มีคำสั่งซื้อที่ส่งสำเร็จ</p>
                <p class="text-gray-400 dark:text-gray-500 mt-2">คำสั่งซื้อที่ส่งถึงลูกค้าแล้วจะแสดงที่นี่</p>
                <a href="{{ route('seller.orders.shipped') }}"
                   class="inline-block mt-4 px-6 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition-colors">
                    ดูจัดส่งแล้ว
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
