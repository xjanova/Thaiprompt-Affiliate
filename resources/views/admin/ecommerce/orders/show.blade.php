@extends('layouts.admin')

@section('title', 'รายละเอียดคำสั่งซื้อ')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📋 คำสั่งซื้อ #{{ $order->order_number }}</h1>
        <a href="{{ route('admin.ecommerce.orders.index') }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-700">
            ← กลับ
        </a>
    </div>

    <!-- Order Info -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลลูกค้า</h3>
            <div class="space-y-2 text-sm">
                <p><span class="font-semibold">ชื่อ:</span> {{ $order->user->name ?? 'ไม่ระบุ' }}</p>
                <p><span class="font-semibold">อีเมล:</span> {{ $order->user->email ?? 'ไม่ระบุ' }}</p>
                <p><span class="font-semibold">เบอร์โทร:</span> {{ $order->customer_phone ?? 'ไม่ระบุ' }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">สถานะ</h3>
            <div class="space-y-2 text-sm">
                <p><span class="font-semibold">สถานะคำสั่งซื้อ:</span> {{ $order->status }}</p>
                <p><span class="font-semibold">สถานะการชำระเงิน:</span> {{ $order->payment_status }}</p>
                <p><span class="font-semibold">วิธีชำระเงิน:</span> {{ $order->payment_method ?? 'ไม่ระบุ' }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">วันที่</h3>
            <div class="space-y-2 text-sm">
                <p><span class="font-semibold">สั่งซื้อเมื่อ:</span> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                @if($order->paid_at)
                    <p><span class="font-semibold">ชำระเมื่อ:</span> {{ $order->paid_at->format('d/m/Y H:i') }}</p>
                @endif
                @if($order->shipped_at)
                    <p><span class="font-semibold">จัดส่งเมื่อ:</span> {{ $order->shipped_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">รายการสินค้า</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สินค้า</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ราคา</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">รวม</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($order->items as $item)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->product->name ?? 'ไม่ระบุ' }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $item->product->sku ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                ฿{{ number_format($item->price, 2) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                ฿{{ number_format($item->price * $item->quantity, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-white">ยอดรวม:</td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white">฿{{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
