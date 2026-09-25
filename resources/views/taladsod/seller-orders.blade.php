{{--
    ออเดอร์ของร้าน (ฝั่งผู้ขาย) - ตลาดสดไทยพร๊อม

    ตัวแปร:
    - $seller        FreshMarketSeller
    - $orders        paginator ของ FreshMarketOrder (with buyer, listing, riderJob)
    - $statusFilter  สถานะที่กรองอยู่ (null = ทั้งหมด)
    - $statusCounts  [order_status => จำนวน]
    - $statuses      [order_status => ป้ายภาษาไทย]
    หน้านี้เป็นเวอร์ชันใช้งานได้ก่อน — จะถูกสร้างใหม่ในธีม V4
--}}
@extends('layouts.taladsod')

@section('title', 'ออเดอร์ร้าน - ตลาดสดไทยพร๊อม')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">🏪 ออเดอร์ร้าน {{ $seller->shop_name }}</h1>
            <a href="{{ route('taladsod.seller.dashboard') }}" class="text-sm text-green-600 dark:text-green-400 hover:underline font-medium">
                ← แผงควบคุมผู้ขาย
            </a>
        </div>

        @include('taladsod.partials.flash')

        {{-- แท็บสถานะ --}}
        <div class="flex gap-2 overflow-x-auto pb-4 mb-2">
            <a href="{{ route('taladsod.seller.orders') }}"
               class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium {{ $statusFilter === null ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700' }}">
                ทั้งหมด
            </a>
            @foreach ($statuses as $key => $label)
                <a href="{{ route('taladsod.seller.orders', ['status' => $key]) }}"
                   class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium {{ $statusFilter === $key ? 'bg-green-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700' }}">
                    {{ $label }}
                    @if (($statusCounts[$key] ?? 0) > 0)
                        <span class="ml-1 text-xs opacity-80">({{ $statusCounts[$key] }})</span>
                    @endif
                </a>
            @endforeach
        </div>

        @forelse ($orders as $order)
            <a href="{{ route('taladsod.seller.orders.show', $order) }}"
               class="block mb-3 p-4 bg-white dark:bg-gray-800 rounded-2xl shadow border border-gray-100 dark:border-gray-700 hover:shadow-lg transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900 dark:text-white">#{{ $order->order_number }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                            {{ $order->riderItemsSummary() }} • {{ $order->buyer?->name ?? 'ผู้ซื้อ' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            {{ $order->delivery_type === 'rider' ? '🏍️ ส่งด้วยไรเดอร์' : '🏪 ลูกค้ามารับเอง' }}
                            • {{ $order->payment_method === 'cod' ? 'เก็บเงินปลายทาง' : 'ชำระผ่าน Wallet แล้ว' }}
                            • {{ $order->created_at?->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-bold text-green-600 dark:text-green-400">฿{{ number_format((float) $order->total_amount, 2) }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">รับจริง ฿{{ number_format((float) $order->seller_earning, 2) }}</p>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                            {{ $order->status_label }}
                        </span>
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center py-16 text-gray-500 dark:text-gray-400">ยังไม่มีออเดอร์ในสถานะนี้</div>
        @endforelse

        <div class="mt-6">{{ $orders->links() }}</div>
    </div>
@endsection
