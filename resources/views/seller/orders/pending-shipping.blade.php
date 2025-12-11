{{--
    หน้าคำสั่งซื้อที่รอจัดส่ง
    แสดงรายการที่ชำระเงินแล้วแต่ยังไม่มีเลขพัสดุ
--}}
@extends('layouts.seller')

@section('title', 'รอจัดส่ง')

@section('content')
<div class="space-y-6" x-data="shippingManager()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-white">📋 รอจัดส่ง</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1">คำสั่งซื้อที่ชำระเงินแล้ว รอกรอกเลขพัสดุ</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('seller.orders.shipped') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition-colors">
                <span>🚚</span>
                <span>จัดส่งแล้ว</span>
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
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-orange-100">รอจัดส่ง</p>
                    <p class="text-4xl font-bold">{{ number_format($stats['pending_shipping']) }}</p>
                    <p class="text-orange-200 text-sm mt-1">คำสั่งซื้อ</p>
                </div>
                <div class="text-6xl opacity-80">📦</div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-blue-100">มูลค่ารวม</p>
                    <p class="text-4xl font-bold">฿{{ number_format($stats['total_amount'], 0) }}</p>
                    <p class="text-blue-200 text-sm mt-1">บาท</p>
                </div>
                <div class="text-6xl opacity-80">💰</div>
            </div>
        </div>
    </div>

    {{-- Orders List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-orange-50 dark:bg-orange-900/20">
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-orange-500 rounded-full animate-pulse"></span>
                <h2 class="text-lg font-semibold text-orange-700 dark:text-orange-300">คำสั่งซื้อที่รอจัดส่ง</h2>
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
                                    <span class="bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 text-xs px-2 py-1 rounded-full font-medium">
                                        รอกรอกเลขพัสดุ
                                    </span>
                                    <span class="bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs px-2 py-1 rounded-full font-medium">
                                        ชำระเงินแล้ว
                                    </span>
                                </div>

                                {{-- Customer --}}
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    <span>👤</span>
                                    <span class="font-medium">{{ $order->user->name }}</span>
                                    <span>•</span>
                                    <span>{{ $order->user->phone ?? $order->user->email }}</span>
                                </div>

                                {{-- Address --}}
                                @if($order->shippingAddress)
                                    <div class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        <span>📍</span>
                                        <span>{{ $order->shippingAddress->full_address ?? ($order->shippingAddress->address . ', ' . $order->shippingAddress->district . ', ' . $order->shippingAddress->province . ' ' . $order->shippingAddress->postal_code) }}</span>
                                    </div>
                                @endif

                                {{-- Products --}}
                                <div class="mt-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">📦 สินค้า {{ $sellerItems->count() }} รายการ:</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($sellerItems->take(3) as $item)
                                            <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm">
                                                {{ Str::limit($item->product->name ?? 'สินค้า', 20) }} x{{ $item->quantity }}
                                            </span>
                                        @endforeach
                                        @if($sellerItems->count() > 3)
                                            <span class="text-sm text-gray-500 dark:text-gray-400">+{{ $sellerItems->count() - 3 }} รายการ</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Total --}}
                                <div class="mt-3 flex items-center gap-2">
                                    <span class="text-gray-500 dark:text-gray-400">รายได้:</span>
                                    <span class="text-xl font-bold text-green-600">฿{{ number_format($sellerTotal, 2) }}</span>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex flex-col gap-2 lg:w-48">
                                <a href="{{ route('seller.orders.tracking', $order->id) }}"
                                   class="w-full px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg font-medium text-center transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                                    <span>📝</span>
                                    <span>กรอกเลขพัสดุ</span>
                                </a>
                                <a href="{{ route('seller.orders.show', $order) }}"
                                   class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-center transition-colors">
                                    ดูรายละเอียด
                                </a>
                                <a href="{{ route('seller.orders.print', $order) }}" target="_blank"
                                   class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-center transition-colors flex items-center justify-center gap-1">
                                    <span>🖨️</span>
                                    <span>พิมพ์ใบส่งของ</span>
                                </a>
                            </div>
                        </div>

                        {{-- Time --}}
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                            <span>สั่งซื้อเมื่อ: {{ $order->created_at->format('d/m/Y H:i') }}</span>
                            <span class="text-orange-500 font-medium">รอ {{ $order->created_at->diffForHumans() }}</span>
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
                <div class="text-7xl mb-4">✅</div>
                <p class="text-xl text-gray-500 dark:text-gray-400">ไม่มีคำสั่งซื้อที่รอจัดส่ง</p>
                <p class="text-gray-400 dark:text-gray-500 mt-2">คุณจัดส่งครบทุกออเดอร์แล้ว!</p>
                <a href="{{ route('seller.orders.index') }}"
                   class="inline-block mt-4 px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                    ดูคำสั่งซื้อทั้งหมด
                </a>
            </div>
        @endif
    </div>
</div>

<script>
function shippingManager() {
    return {
        // สำหรับ feature เพิ่มเติมในอนาคต
    }
}
</script>
@endsection
