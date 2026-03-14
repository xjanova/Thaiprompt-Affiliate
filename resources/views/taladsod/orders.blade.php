{{--
    คำสั่งซื้อของฉัน - ตลาดสดไทยพร๊อม

    ตัวแปรที่ใช้:
    - $orders: คำสั่งซื้อ (paginated collection)
--}}
@extends('layouts.taladsod')

@section('title', 'คำสั่งซื้อของฉัน - ตลาดสดไทยพร๊อม')

@section('content')

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

        {{-- ===== หัวข้อ ===== --}}
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                📦 คำสั่งซื้อของฉัน
            </h1>
            <a href="{{ route('taladsod.home') }}"
               class="text-sm text-green-600 dark:text-green-400 hover:underline font-medium flex items-center gap-1">
                <i class="fas fa-arrow-left"></i> กลับหน้าแรก
            </a>
        </div>

        {{-- ===== แท็บสถานะ ===== --}}
        <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-4 mb-4">
            @php
                $statusTabs = [
                    '' => ['ทั้งหมด', 'fas fa-list'],
                    'pending' => ['รอยืนยัน', 'fas fa-clock'],
                    'confirmed' => ['ยืนยันแล้ว', 'fas fa-check'],
                    'completed' => ['สำเร็จ', 'fas fa-check-double'],
                    'cancelled' => ['ยกเลิก', 'fas fa-times'],
                ];
                $currentStatus = request('status', '');
            @endphp
            @foreach($statusTabs as $statusKey => $statusInfo)
                <a href="{{ route('taladsod.orders', $statusKey ? ['status' => $statusKey] : []) }}"
                   class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-colors {{ $currentStatus === $statusKey ? 'bg-green-500 text-white shadow-md' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-green-50 dark:hover:bg-gray-700' }}">
                    <i class="{{ $statusInfo[1] }}"></i>
                    {{ $statusInfo[0] }}
                </a>
            @endforeach
        </div>

        {{-- ===== รายการคำสั่งซื้อ ===== --}}
        @if(isset($orders) && $orders->count() > 0)
            <div class="space-y-4">
                @foreach($orders as $order)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
                        {{-- หัวออเดอร์ --}}
                        <div class="flex items-center justify-between px-4 sm:px-5 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    ออเดอร์ #{{ $order->order_number ?? $order->id }}
                                </span>
                                @php
                                    $statusConfig = [
                                        'pending' => ['รอยืนยัน', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', 'fas fa-clock'],
                                        'confirmed' => ['ยืนยันแล้ว', 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', 'fas fa-check'],
                                        'shipping' => ['กำลังจัดส่ง', 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400', 'fas fa-truck'],
                                        'completed' => ['สำเร็จ', 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'fas fa-check-double'],
                                        'cancelled' => ['ยกเลิก', 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', 'fas fa-times'],
                                    ];
                                    $orderStatus = $order->status ?? 'pending';
                                    $config = $statusConfig[$orderStatus] ?? ['ไม่ทราบ', 'bg-gray-100 text-gray-800', 'fas fa-question'];
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $config[1] }}">
                                    <i class="{{ $config[2] }}"></i> {{ $config[0] }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}
                            </div>
                        </div>

                        {{-- รายการสินค้า --}}
                        <div class="px-4 sm:px-5 py-4">
                            @if($order->items ?? false)
                                @foreach($order->items as $item)
                                    <div class="flex items-center gap-4 {{ !$loop->last ? 'mb-3 pb-3 border-b border-gray-50 dark:border-gray-700' : '' }}">
                                        {{-- รูปสินค้า --}}
                                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                                            @if($item->listing->image_url ?? false)
                                                <img src="{{ $item->listing->image_url }}" alt="{{ $item->listing->title ?? '' }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-3xl">🥬</div>
                                            @endif
                                        </div>
                                        {{-- ข้อมูลสินค้า --}}
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm sm:text-base font-medium text-gray-900 dark:text-white truncate">
                                                {{ $item->listing->title ?? 'สินค้า' }}
                                            </h3>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                จำนวน {{ $item->quantity ?? 1 }} {{ $item->listing->unit ?? 'กก.' }}
                                            </div>
                                            <div class="text-sm font-bold text-green-600 dark:text-green-400 mt-1">
                                                ฿{{ number_format($item->total ?? ($item->price * ($item->quantity ?? 1)), 0) }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                {{-- กรณีข้อมูล items ไม่ได้ load เข้ามา แสดงแบบสรุป --}}
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 rounded-xl bg-green-50 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-box text-green-500 text-xl"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $order->items_count ?? 1 }} รายการ
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            จาก {{ $order->seller->shop_name ?? 'ร้านค้า' }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- ส่วนล่าง: ยอดรวมและปุ่ม --}}
                        <div class="flex items-center justify-between px-4 sm:px-5 py-3 bg-gray-50 dark:bg-gray-700/30 border-t border-gray-100 dark:border-gray-700">
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">ยอดรวม</span>
                                <div class="text-lg font-bold text-orange-600 dark:text-orange-400">
                                    ฿{{ number_format($order->total ?? 0, 0) }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                @if(($order->status ?? 'pending') === 'pending')
                                    <button class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                                        ยกเลิก
                                    </button>
                                @endif
                                <a href="{{ route('taladsod.orders.show', $order->id) }}"
                                   class="px-4 py-2 text-sm font-medium bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                                    ดูรายละเอียด
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if(method_exists($orders, 'links'))
                <div class="mt-8">
                    {{ $orders->withQueryString()->links() }}
                </div>
            @endif

        @else
            {{-- ===== สถานะไม่มีคำสั่งซื้อ ===== --}}
            <div class="text-center py-20 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                <div class="text-7xl mb-6 animate-float">🛒</div>
                <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300 mb-3">ยังไม่มีคำสั่งซื้อ</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
                    เมื่อคุณสั่งซื้อสินค้า คำสั่งซื้อจะแสดงที่นี่ ลองค้นหาสินค้าสดใกล้บ้านคุณเลย!
                </p>
                <a href="{{ route('taladsod.search') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-xl font-medium transition-colors shadow-md hover:shadow-lg">
                    <i class="fas fa-search"></i> ค้นหาสินค้า
                </a>
            </div>
        @endif
    </div>

@endsection
