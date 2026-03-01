{{--
    แดชบอร์ดผู้ขาย - ตลาดสดไทยพร้อม

    ตัวแปรที่ใช้:
    - $seller: ข้อมูลผู้ขาย (model)
    - $recentOrders: ออเดอร์ล่าสุด (collection)
    - $stats: สถิติ (object: total_listings, total_sales, total_revenue, pending_orders, rating)
--}}
@extends('layouts.taladsod')

@section('title', 'แดชบอร์ดผู้ขาย - ตลาดสดไทยพร้อม')

@section('content')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

        {{-- ===== หัวข้อ ===== --}}
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                    &#x1F3EA; แดชบอร์ดผู้ขาย
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    ยินดีต้อนรับ, {{ $seller->shop_name ?? 'ผู้ขาย' }}
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('taladsod.create-listing') }}"
                   class="px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-xl transition-all shadow-sm hover:shadow-md flex items-center gap-2">
                    <i class="fas fa-plus"></i> ลงขายสินค้าใหม่
                </a>
                <a href="{{ route('taladsod.orders') }}"
                   class="px-5 py-2.5 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-xl transition-all shadow-sm border border-gray-200 dark:border-gray-600 flex items-center gap-2">
                    <i class="fas fa-box"></i> ดูออเดอร์ทั้งหมด
                </a>
            </div>
        </div>

        {{-- ===== การ์ดสถิติ ===== --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-5 mb-8">
            {{-- สินค้าทั้งหมด --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
                        <i class="fas fa-box-open text-green-500 text-lg"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($stats->total_listings ?? 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">สินค้าทั้งหมด</div>
            </div>

            {{-- ยอดขายทั้งหมด --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                        <i class="fas fa-shopping-cart text-blue-500 text-lg"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($stats->total_sales ?? 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">ยอดขายทั้งหมด</div>
            </div>

            {{-- รายได้ --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center">
                        <i class="fas fa-coins text-orange-500 text-lg"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                    &#x0E3F;{{ number_format($stats->total_revenue ?? 0, 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">รายได้รวม</div>
            </div>

            {{-- ออเดอร์รอดำเนินการ --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-yellow-50 dark:bg-yellow-900/30 flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-500 text-lg"></i>
                    </div>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($stats->pending_orders ?? 0) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">รอดำเนินการ</div>
            </div>

            {{-- คะแนนร้านค้า --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-shadow col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                        <i class="fas fa-star text-purple-500 text-lg"></i>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats->rating ?? 0, 1) }}</span>
                    <span class="text-sm text-gray-400">/5.0</span>
                </div>
                <div class="flex items-center gap-0.5 mt-1">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($stats->rating ?? 0))
                            <i class="fas fa-star text-yellow-400 text-xs"></i>
                        @else
                            <i class="far fa-star text-gray-300 dark:text-gray-600 text-xs"></i>
                        @endif
                    @endfor
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">คะแนนร้านค้า</span>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">

            {{-- ===== สินค้าของฉัน ===== --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-box text-green-500"></i> สินค้าของฉัน
                    </h2>
                    <a href="{{ route('taladsod.create-listing') }}"
                       class="text-sm text-green-600 dark:text-green-400 hover:underline font-medium">
                        + เพิ่มสินค้า
                    </a>
                </div>

                @if(isset($seller->listings) && $seller->listings->count() > 0)
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($seller->listings->take(5) as $listing)
                            <div class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                {{-- รูปสินค้า --}}
                                <div class="w-14 h-14 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                                    @if($listing->image_url ?? false)
                                        <img src="{{ $listing->image_url }}" alt="{{ $listing->title }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-2xl">&#x1F96C;</div>
                                    @endif
                                </div>
                                {{-- ข้อมูล --}}
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $listing->title }}</h3>
                                    <div class="flex items-center gap-3 mt-0.5">
                                        <span class="text-sm font-bold text-green-600 dark:text-green-400">&#x0E3F;{{ number_format($listing->price, 0) }}/{{ $listing->unit ?? 'กก.' }}</span>
                                        <span class="text-xs px-2 py-0.5 rounded-full {{ $listing->is_active ?? true ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                                            {{ $listing->is_active ?? true ? 'เปิดขาย' : 'ปิดขาย' }}
                                        </span>
                                    </div>
                                </div>
                                {{-- ปุ่มจัดการ --}}
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <a href="{{ route('taladsod.edit-listing', $listing->id) }}"
                                       class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors"
                                       title="แก้ไข">
                                        <i class="fas fa-pen text-xs"></i>
                                    </a>
                                    <form method="POST" action="{{ route('taladsod.delete-listing', $listing->id) }}" onsubmit="return confirm('ต้องการลบสินค้านี้หรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors"
                                                title="ลบ">
                                            <i class="fas fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10">
                        <div class="text-4xl mb-3">&#x1F4E6;</div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">ยังไม่มีสินค้า</p>
                        <a href="{{ route('taladsod.create-listing') }}"
                           class="inline-flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm rounded-lg transition-colors">
                            <i class="fas fa-plus"></i> ลงขายสินค้าแรก
                        </a>
                    </div>
                @endif
            </div>

            {{-- ===== ออเดอร์ล่าสุด ===== --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-orange-500"></i> ออเดอร์ล่าสุด
                    </h2>
                    <a href="{{ route('taladsod.orders') }}"
                       class="text-sm text-green-600 dark:text-green-400 hover:underline font-medium">
                        ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>

                @if(isset($recentOrders) && $recentOrders->count() > 0)
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($recentOrders->take(5) as $order)
                            <div class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                {{-- ไอคอนสถานะ --}}
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                                    @switch($order->status ?? 'pending')
                                        @case('pending')
                                            bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600
                                            @break
                                        @case('confirmed')
                                            bg-blue-50 dark:bg-blue-900/30 text-blue-600
                                            @break
                                        @case('completed')
                                            bg-green-50 dark:bg-green-900/30 text-green-600
                                            @break
                                        @case('cancelled')
                                            bg-red-50 dark:bg-red-900/30 text-red-600
                                            @break
                                        @default
                                            bg-gray-50 dark:bg-gray-700 text-gray-600
                                    @endswitch">
                                    @switch($order->status ?? 'pending')
                                        @case('pending')
                                            <i class="fas fa-clock"></i>
                                            @break
                                        @case('confirmed')
                                            <i class="fas fa-check"></i>
                                            @break
                                        @case('completed')
                                            <i class="fas fa-check-double"></i>
                                            @break
                                        @case('cancelled')
                                            <i class="fas fa-times"></i>
                                            @break
                                        @default
                                            <i class="fas fa-box"></i>
                                    @endswitch
                                </div>
                                {{-- ข้อมูลออเดอร์ --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">#{{ $order->order_number ?? $order->id }}</span>
                                        @php
                                            $statusLabels = [
                                                'pending' => ['รอยืนยัน', 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'],
                                                'confirmed' => ['ยืนยันแล้ว', 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
                                                'completed' => ['สำเร็จ', 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                                                'cancelled' => ['ยกเลิก', 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
                                            ];
                                            $status = $order->status ?? 'pending';
                                            $label = $statusLabels[$status] ?? ['ไม่ทราบ', 'bg-gray-50 text-gray-700'];
                                        @endphp
                                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $label[1] }}">
                                            {{ $label[0] }}
                                        </span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $order->buyer->name ?? 'ผู้ซื้อ' }} &middot; {{ $order->created_at ? $order->created_at->diffForHumans() : '' }}
                                    </div>
                                </div>
                                {{-- จำนวนเงิน --}}
                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-bold text-green-600 dark:text-green-400">
                                        &#x0E3F;{{ number_format($order->total ?? 0, 0) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10">
                        <div class="text-4xl mb-3">&#x1F4CB;</div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีออเดอร์</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
