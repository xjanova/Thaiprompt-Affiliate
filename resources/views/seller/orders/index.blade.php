@extends('layouts.seller')

@section('title', 'จัดการคำสั่งซื้อ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-white">จัดการคำสั่งซื้อ</h1>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">คำสั่งซื้อทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="text-4xl">📦</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">รอดำเนินการ</p>
                    <p class="text-2xl font-bold text-orange-600">{{ number_format($stats['pending']) }}</p>
                </div>
                <div class="text-4xl">⏳</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">กำลังดำเนินการ</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($stats['processing']) }}</p>
                </div>
                <div class="text-4xl">🔄</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">จัดส่งแล้ว</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($stats['shipped']) }}</p>
                </div>
                <div class="text-4xl">🚚</div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <a href="{{ route('seller.orders.index') }}"
                   class="border-b-2 py-4 px-1 text-sm font-medium {{ !request('status') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    ทั้งหมด
                </a>
                <a href="{{ route('seller.orders.index', ['status' => 'pending']) }}"
                   class="border-b-2 py-4 px-1 text-sm font-medium {{ request('status') === 'pending' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    รอดำเนินการ
                </a>
                <a href="{{ route('seller.orders.index', ['status' => 'processing']) }}"
                   class="border-b-2 py-4 px-1 text-sm font-medium {{ request('status') === 'processing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    กำลังดำเนินการ
                </a>
                <a href="{{ route('seller.orders.index', ['status' => 'shipped']) }}"
                   class="border-b-2 py-4 px-1 text-sm font-medium {{ request('status') === 'shipped' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    จัดส่งแล้ว
                </a>
                <a href="{{ route('seller.orders.index', ['status' => 'completed']) }}"
                   class="border-b-2 py-4 px-1 text-sm font-medium {{ request('status') === 'completed' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    เสร็จสิ้น
                </a>
            </nav>
        </div>
    </div>

    <!-- Orders List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        @if($orders->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">เลขที่คำสั่งซื้อ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ลูกค้า</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สินค้า</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ยอดรวม</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($orders as $order)
                            @php
                                $sellerItems = $order->items->where('seller_id', auth()->id());
                                $sellerTotal = $sellerItems->sum('seller_earning');
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">#{{ $order->order_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $order->user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->user->email }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $sellerItems->count() }} รายการ</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">฿{{ number_format($sellerTotal, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'processing' => 'bg-blue-100 text-blue-800',
                                            'shipped' => 'bg-purple-100 text-purple-800',
                                            'delivered' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                        ];
                                        $statusNames = [
                                            'pending' => 'รอดำเนินการ',
                                            'processing' => 'กำลังดำเนินการ',
                                            'shipped' => 'จัดส่งแล้ว',
                                            'delivered' => 'ส่งสำเร็จ',
                                            'completed' => 'เสร็จสิ้น',
                                            'cancelled' => 'ยกเลิก',
                                        ];
                                    @endphp
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusNames[$order->status] ?? $order->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="{{ route('seller.orders.show', $order) }}"
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                        ดูรายละเอียด
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $orders->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📦</div>
                <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีคำสั่งซื้อ</p>
            </div>
        @endif
    </div>
</div>
@endsection
