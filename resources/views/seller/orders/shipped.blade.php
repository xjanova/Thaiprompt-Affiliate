{{--
    หน้าคำสั่งซื้อที่จัดส่งแล้ว
    แสดงรายการที่มีเลขพัสดุแล้ว รอส่งถึง
--}}
@extends('layouts.seller')

@section('title', 'จัดส่งแล้ว')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">🚚 จัดส่งแล้ว</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">คำสั่งซื้อที่จัดส่งแล้ว รอส่งถึงลูกค้า</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('seller.orders.pending-shipping') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors">
                <span>📋</span>
                <span>รอจัดส่ง</span>
            </a>
            <a href="{{ route('seller.orders.delivered') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                <span>✅</span>
                <span>สำเร็จ</span>
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-purple-100">กำลังจัดส่ง</p>
                    <p class="text-4xl font-bold">{{ number_format($stats['shipped']) }}</p>
                    <p class="text-purple-200 text-sm mt-1">คำสั่งซื้อ</p>
                </div>
                <div class="text-6xl opacity-80">🚚</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-cyan-100">อยู่ระหว่างทาง</p>
                    <p class="text-4xl font-bold">{{ number_format($stats['in_transit']) }}</p>
                    <p class="text-cyan-200 text-sm mt-1">พัสดุ</p>
                </div>
                <div class="text-6xl opacity-80">📦</div>
            </div>
        </div>
    </div>

    {{-- Orders List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-purple-50 dark:bg-purple-900/20">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-purple-500 rounded-full animate-pulse"></span>
                <h2 class="text-lg font-semibold text-purple-700 dark:text-purple-300">คำสั่งซื้อที่กำลังจัดส่ง</h2>
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
                                    <span class="bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 text-xs px-2 py-1 rounded-full font-medium">
                                        กำลังจัดส่ง
                                    </span>
                                </div>

                                {{-- Tracking Info --}}
                                <div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-lg p-4 mb-3">
                                    <div class="flex items-center gap-3">
                                        @if($order->shippingProvider && $order->shippingProvider->logo)
                                            <img src="{{ asset('storage/' . $order->shippingProvider->logo) }}"
                                                 alt="{{ $order->shippingProvider->name }}"
                                                 class="w-12 h-12 object-contain rounded">
                                        @else
                                            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center text-2xl">
                                                🚚
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                {{ $order->shippingProvider->name ?? $order->shipping_provider ?? 'ขนส่ง' }}
                                            </p>
                                            <p class="text-purple-600 dark:text-purple-400 font-mono text-lg font-bold">
                                                {{ $order->tracking_number }}
                                            </p>
                                        </div>
                                    </div>

                                    @if($order->shippingProvider && $order->shippingProvider->tracking_url)
                                        <a href="{{ str_replace('{tracking_number}', $order->tracking_number, $order->shippingProvider->tracking_url) }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1 mt-3 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                            🔍 ติดตามพัสดุ
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    @endif
                                </div>

                                {{-- Customer --}}
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    <span>👤</span>
                                    <span class="font-medium">{{ $order->user->name }}</span>
                                    <span>•</span>
                                    <span>{{ $order->user->phone ?? $order->user->email }}</span>
                                </div>

                                {{-- Products --}}
                                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span>📦</span>
                                    <span>{{ $sellerItems->count() }} สินค้า</span>
                                    <span>•</span>
                                    <span class="text-green-600 font-medium">฿{{ number_format($sellerTotal, 2) }}</span>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex flex-col gap-2 lg:w-48">
                                <a href="{{ route('seller.orders.tracking', $order->id) }}"
                                   class="w-full px-4 py-3 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white rounded-lg font-medium text-center transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                                    <span>📍</span>
                                    <span>อัพเดทสถานะ</span>
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
                            <span>จัดส่งเมื่อ: {{ $order->shipped_at ? $order->shipped_at->format('d/m/Y H:i') : '-' }}</span>
                            @if($order->estimated_delivery_at)
                                <span class="text-blue-500 font-medium">คาดว่าจะถึง: {{ \Carbon\Carbon::parse($order->estimated_delivery_at)->format('d/m/Y') }}</span>
                            @endif
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
                <p class="text-xl text-gray-500 dark:text-gray-400">ไม่มีคำสั่งซื้อที่กำลังจัดส่ง</p>
                <p class="text-gray-400 dark:text-gray-500 mt-2">คำสั่งซื้อที่จัดส่งแล้วจะแสดงที่นี่</p>
                <a href="{{ route('seller.orders.pending-shipping') }}"
                   class="inline-block mt-4 px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors">
                    ดูรอจัดส่ง
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
