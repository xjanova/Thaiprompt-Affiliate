@extends('layouts.seller')

@section('title', 'รายงานยอดขาย')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('seller.dashboard') }}" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">📊 รายงานยอดขาย</h1>
            </div>
            <p class="text-blue-100">รายงานและวิเคราะห์ยอดขายของคุณ</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">💰</div>
            <p class="text-white text-opacity-80 text-sm mb-1">ยอดขายรวม</p>
            <p class="text-2xl font-bold">฿{{ number_format($salesData->sum(function($item) { return $item->order->payment_status === 'paid' ? $item->seller_earning : 0; }), 2) }}</p>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">📦</div>
            <p class="text-white text-opacity-80 text-sm mb-1">รายการทั้งหมด</p>
            <p class="text-2xl font-bold">{{ $salesData->total() }}</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">📈</div>
            <p class="text-white text-opacity-80 text-sm mb-1">ยอดขายเฉลี่ย</p>
            <p class="text-2xl font-bold">฿{{ $salesData->count() > 0 ? number_format($salesData->sum(function($item) { return $item->order->payment_status === 'paid' ? $item->seller_earning : 0; }) / $salesData->count(), 2) : '0.00' }}</p>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">✓</div>
            <p class="text-white text-opacity-80 text-sm mb-1">สำเร็จ</p>
            <p class="text-2xl font-bold">{{ $salesData->where('status', 'completed')->count() }}</p>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                <span>📝</span>
                <span>รายการขาย</span>
            </h2>
            <a href="{{ route('seller.analytics.index') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                ดู Analytics
            </a>
        </div>

        @if($salesData->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">เลขที่คำสั่งซื้อ</th>
                            <th class="text-left py-3 px-4 text-sm font-semibold text-gray-700">สินค้า</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">จำนวน</th>
                            <th class="text-right py-3 px-4 text-sm font-semibold text-gray-700">รายได้</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">สถานะ</th>
                            <th class="text-center py-3 px-4 text-sm font-semibold text-gray-700">วันที่</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salesData as $item)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-4 px-4">
                                    <a href="{{ route('seller.orders.show', $item->order_id) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                        #{{ $item->order->order_number }}
                                    </a>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="font-semibold text-gray-900">{{ $item->product->name ?? 'N/A' }}</p>
                                    @if($item->variant_name)
                                        <p class="text-xs text-gray-500">{{ $item->variant_name }}</p>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span class="inline-block px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-semibold">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-right">
                                    <p class="font-bold text-green-600">฿{{ number_format($item->seller_earning, 2) }}</p>
                                    <p class="text-xs text-gray-500">ราคา: ฿{{ number_format($item->price * $item->quantity, 2) }}</p>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    @if($item->status === 'completed')
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">สำเร็จ</span>
                                    @elseif($item->status === 'processing')
                                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">กำลังดำเนินการ</span>
                                    @elseif($item->status === 'pending')
                                        <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">รอดำเนินการ</span>
                                    @elseif($item->status === 'cancelled')
                                        <span class="inline-block px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">ยกเลิก</span>
                                    @else
                                        <span class="inline-block px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">{{ $item->status }}</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-center text-sm text-gray-600">
                                    {{ $item->created_at->format('d/m/Y') }}<br>
                                    <span class="text-xs text-gray-400">{{ $item->created_at->format('H:i') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $salesData->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <span class="text-6xl block mb-4">📊</span>
                <p class="text-gray-500 text-lg font-medium mb-2">ยังไม่มียอดขาย</p>
                <p class="text-gray-400 text-sm mb-6">เริ่มขายสินค้าเพื่อดูรายงานยอดขาย</p>
                <a href="{{ route('seller.products.index') }}" class="inline-block px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:opacity-90 transition">
                    จัดการสินค้า
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
