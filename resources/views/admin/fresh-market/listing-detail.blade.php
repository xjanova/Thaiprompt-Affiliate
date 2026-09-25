{{--
    รายละเอียดสินค้าตลาดสด (แอดมิน)

    ตัวแปร: $listing (with seller.user, category, orders.buyer), $isVisibleToBuyers (bool)
    ฟอร์ม: POST admin.fresh-market.listings.approve / listings.suspend (reason)
    หน้านี้เป็นเวอร์ชันใช้งานได้ก่อน — จะถูกสร้างใหม่ในธีม V4
--}}
@extends('layouts.admin-v3')

@section('title', $listing->title.' - ตลาดสดไทยพร๊อม')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $listing->title }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                ร้าน <a href="{{ route('admin.fresh-market.sellers.show', $listing->seller_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $listing->seller?->shop_name ?? '-' }}</a>
                • {{ $listing->category?->name ?? 'ไม่มีหมวดหมู่' }} • สถานะ {{ $listing->status }}
                • {{ $isVisibleToBuyers ? 'ผู้ซื้อเห็นอยู่' : 'ผู้ซื้อไม่เห็น' }}
            </p>
        </div>
        <a href="{{ route('admin.fresh-market.listings') }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl">← รายการสินค้า</a>
    </div>

    @if (session('success'))
        <div class="p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap gap-3">
        @if ($listing->status !== 'active')
            <form method="POST" action="{{ route('admin.fresh-market.listings.approve', $listing) }}">@csrf
                <button class="px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white font-semibold">อนุมัติ/เปิดขาย</button>
            </form>
        @endif
        @if ($listing->status !== 'suspended')
            <form method="POST" action="{{ route('admin.fresh-market.listings.suspend', $listing) }}" class="flex gap-2"
                  onsubmit="return confirm('ระงับสินค้านี้?');">@csrf
                <input type="text" name="reason" maxlength="255" placeholder="เหตุผลที่ระงับ" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                <button class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-semibold">ระงับสินค้า</button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-5 text-sm space-y-2">
            @if ($listing->primary_image)
                <img src="{{ $listing->primary_image }}" alt="{{ $listing->title }}" class="w-full h-48 object-cover rounded-xl">
            @endif
            <p class="text-gray-700 dark:text-gray-300">ราคา ฿{{ number_format((float) $listing->price, 2) }} / {{ $listing->unit }}</p>
            <p class="text-gray-700 dark:text-gray-300">คงเหลือ {{ number_format($listing->quantity_available) }} • ขายแล้ว {{ number_format($listing->order_count) }} ออเดอร์ • เข้าชม {{ number_format($listing->view_count) }}</p>
            <p class="text-gray-700 dark:text-gray-300">แคชแบ็ค {{ (float) $listing->cashback_percentage }}% / ฿{{ number_format((float) $listing->cashback_amount, 2) }}</p>
            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $listing->description }}</p>
        </div>
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl shadow p-5">
            <h2 class="font-bold text-gray-900 dark:text-white mb-3">ออเดอร์ล่าสุด</h2>
            <ul class="space-y-2 text-sm">
                @forelse ($listing->orders as $order)
                    <li><a href="{{ route('admin.fresh-market.orders.show', $order) }}" class="text-blue-600 dark:text-blue-400 hover:underline">#{{ $order->order_number }}</a>
                        <span class="text-gray-500 dark:text-gray-400">{{ $order->buyer?->name }} • x{{ $order->quantity }} • ฿{{ number_format((float) $order->total_amount, 2) }} • {{ $order->status_label }}</span></li>
                @empty
                    <li class="text-gray-500 dark:text-gray-400">ยังไม่มีออเดอร์</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
