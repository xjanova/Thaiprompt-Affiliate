{{--
    รายละเอียดผู้ขายตลาดสด (แอดมิน)

    ตัวแปร: $seller (with user, listings, orders.buyer), $orderStats [status => count], $gpDebt (float),
            $gpDebts (WalletDebt collection), $payoutTotal (float)
    ฟอร์ม: POST admin.fresh-market.sellers.verify / sellers.suspend (reason) / sellers.activate
    หน้านี้เป็นเวอร์ชันใช้งานได้ก่อน — จะถูกสร้างใหม่ในธีม V4
--}}
@extends('layouts.admin-v3')

@section('title', $seller->shop_name.' - ตลาดสดไทยพร๊อม')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $seller->shop_name }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $seller->status_label }} • เจ้าของ {{ $seller->user?->name ?? '-' }} (#{{ $seller->user_id }}) • {{ $seller->phone }}</p>
        </div>
        <a href="{{ route('admin.fresh-market.sellers') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl">← รายการผู้ขาย</a>
    </div>

    @if (session('success'))
        <div class="p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap gap-3">
        @if (! $seller->is_verified)
            <form method="POST" action="{{ route('admin.fresh-market.sellers.verify', $seller) }}">@csrf
                <button class="px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold">ยืนยันร้าน</button>
            </form>
        @endif
        @if ($seller->is_suspended || ! $seller->is_active)
            <form method="POST" action="{{ route('admin.fresh-market.sellers.activate', $seller) }}">@csrf
                <button class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold">เปิดใช้งานร้าน</button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.fresh-market.sellers.suspend', $seller) }}" class="flex gap-2"
                  onsubmit="return confirm('ระงับร้านนี้? สินค้าจะถูกซ่อนจากผู้ซื้อทันที');">@csrf
                <input type="text" name="reason" maxlength="255" placeholder="เหตุผลที่ระงับ" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <button class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">ระงับร้าน</button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-4"><p class="text-sm text-gray-500 dark:text-gray-400">สินค้า</p><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($seller->total_listings) }}</p></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-4"><p class="text-sm text-gray-500 dark:text-gray-400">ขายสำเร็จ</p><p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($seller->total_sales) }}</p></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-4"><p class="text-sm text-gray-500 dark:text-gray-400">โอนให้ร้านแล้ว</p><p class="text-2xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($payoutTotal, 2) }}</p></div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-4"><p class="text-sm text-gray-500 dark:text-gray-400">ค่า GP ค้าง</p><p class="text-2xl font-bold text-orange-600 dark:text-orange-400">฿{{ number_format($gpDebt, 2) }}</p></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
            <h2 class="font-bold text-gray-900 dark:text-white mb-3">สินค้าล่าสุด</h2>
            <ul class="space-y-2 text-sm">
                @forelse ($seller->listings as $listing)
                    <li><a href="{{ route('admin.fresh-market.listings.show', $listing) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $listing->title }}</a>
                        <span class="text-gray-500 dark:text-gray-400">฿{{ number_format((float) $listing->price, 2) }} • เหลือ {{ $listing->quantity_available }} • {{ $listing->status }}</span></li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">ยังไม่มีสินค้า</li>
                @endforelse
            </ul>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
            <h2 class="font-bold text-gray-900 dark:text-white mb-3">ออเดอร์ล่าสุด</h2>
            <ul class="space-y-2 text-sm">
                @forelse ($seller->orders as $order)
                    <li><a href="{{ route('admin.fresh-market.orders.show', $order) }}" class="text-blue-600 dark:text-blue-400 hover:underline">#{{ $order->order_number }}</a>
                        <span class="text-gray-500 dark:text-gray-400">{{ $order->buyer?->name }} • ฿{{ number_format((float) $order->total_amount, 2) }} • {{ $order->status_label }}</span></li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">ยังไม่มีออเดอร์</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
