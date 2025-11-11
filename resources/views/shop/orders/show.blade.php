@extends('layouts.app')

@section('title', 'รายละเอียดคำสั่งซื้อ #' . $order->order_number)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50/20 to-amber-50/30 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">

    <!-- Hero Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 dark:from-orange-700 dark:via-amber-700 dark:to-yellow-700">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>

        <div class="container mx-auto px-4 py-8 relative z-10">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('orders.index') }}"
                   class="p-2 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl border border-white/30 transition-all">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg px-4 py-2 rounded-full border border-white/30">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold text-white">รายละเอียดคำสั่งซื้อ</span>
                </div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-black text-white mb-2 tracking-tight drop-shadow-lg">
                        {{ $order->order_number }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-3 text-orange-100">
                        <span class="px-4 py-2 rounded-full text-sm font-bold bg-{{ $order->status_color }}-500 text-white shadow-lg">
                            {{ $order->status_label }}
                        </span>
                        <span>{{ $order->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-orange-100 mb-1">ยอดรวมทั้งหมด</div>
                    <div class="text-4xl font-black text-white drop-shadow-lg">
                        ฿{{ number_format($order->total_amount, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Wave Divider -->
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full dark:hidden">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="rgb(249, 250, 251)"/>
            </svg>
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full hidden dark:block">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="rgb(17, 24, 39)"/>
            </svg>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8 -mt-6 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left Column - Main Details -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Order Timeline -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-amber-600 dark:from-orange-700 dark:to-amber-700 text-white px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                            </svg>
                            ติดตามสถานะพัสดุ
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="relative">
                            <!-- Timeline -->
                            <div class="space-y-6">
                                <!-- Created -->
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white shadow-lg">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="w-1 h-full bg-gradient-to-b from-green-300 to-gray-200 dark:to-gray-600 mt-2"></div>
                                    </div>
                                    <div class="flex-1 pb-8">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 mb-1">สั่งซื้อสำเร็จ</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $order->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Paid -->
                                @if($order->paid_at)
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full flex items-center justify-center text-white shadow-lg">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        <div class="w-1 h-full bg-gradient-to-b from-blue-300 to-gray-200 dark:to-gray-600 mt-2"></div>
                                    </div>
                                    <div class="flex-1 pb-8">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 mb-1">ชำระเงินแล้ว</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $order->paid_at->format('d/m/Y H:i') }}
                                        </div>
                                        @if($order->payment_reference)
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            อ้างอิง: {{ $order->payment_reference }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <!-- Shipped -->
                                @if($order->shipped_at)
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white shadow-lg">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                            </svg>
                                        </div>
                                        @if($order->delivered_at)
                                        <div class="w-1 h-full bg-gradient-to-b from-purple-300 to-gray-200 dark:to-gray-600 mt-2"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 pb-8">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 mb-1">จัดส่งสินค้าแล้ว</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $order->shipped_at->format('d/m/Y H:i') }}
                                        </div>
                                        @if($order->tracking_number)
                                        <div class="mt-2 inline-flex items-center gap-2 px-3 py-1.5 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-lg text-sm font-semibold">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                            </svg>
                                            เลขพัสดุ: {{ $order->tracking_number }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <!-- Delivered -->
                                @if($order->delivered_at)
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-500 rounded-full flex items-center justify-center text-white shadow-lg">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm9.707 5.707a1 1 0 00-1.414-1.414L9 12.586l-1.293-1.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 mb-1">จัดส่งสำเร็จ</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $order->delivered_at->format('d/m/Y H:i') }}
                                        </div>
                                        @if($order->status === 'delivered')
                                        <form action="{{ route('orders.confirm-received', $order->id) }}" method="POST" class="mt-2">
                                            @csrf
                                            <button type="submit"
                                                    class="px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold rounded-lg shadow-lg transition-all text-sm">
                                                ยืนยันรับสินค้า
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-amber-600 dark:from-orange-700 dark:to-amber-700 text-white px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
                            </svg>
                            รายการสินค้า ({{ $order->items->count() }} รายการ)
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach($order->items as $item)
                            <div class="flex gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-600">
                                <div class="w-20 h-20 bg-gray-200 dark:bg-gray-600 rounded-lg overflow-hidden flex-shrink-0">
                                    @if($item->product_image)
                                        <img src="{{ asset($item->product_image) }}"
                                             alt="{{ $item->product_name }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400">
                                            <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <div class="font-bold text-gray-900 dark:text-gray-100 mb-1">
                                        {{ $item->product_name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                        SKU: {{ $item->product_sku }}
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ $item->quantity }} x ฿{{ number_format($item->unit_price, 2) }}
                                        </div>
                                        <div class="text-lg font-bold text-orange-600 dark:text-orange-400">
                                            ฿{{ number_format($item->total, 2) }}
                                        </div>
                                    </div>

                                    <!-- Review Button -->
                                    @if($order->status === 'completed' && !$item->hasReview())
                                    <div class="mt-3">
                                        <a href="{{ route('orders.review', [$order->id, $item->id]) }}"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-yellow-500 to-amber-500 hover:from-yellow-600 hover:to-amber-600 text-white font-bold rounded-lg shadow-lg transition-all text-sm">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                            รีวิวสินค้า
                                        </a>
                                    </div>
                                    @elseif($item->hasReview())
                                    <div class="mt-3 inline-flex items-center gap-2 px-3 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-sm font-semibold">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        รีวิวแล้ว
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Summary & Address -->
            <div class="space-y-6">

                <!-- Order Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-4">
                    <div class="bg-gradient-to-r from-orange-600 to-amber-600 dark:from-orange-700 dark:to-amber-700 text-white px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                            </svg>
                            สรุปคำสั่งซื้อ
                        </h2>
                    </div>

                    <div class="p-6 space-y-3">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>ยอดรวมสินค้า</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                ฿{{ number_format($order->subtotal, 2) }}
                            </span>
                        </div>

                        @if($order->discount_amount > 0)
                        <div class="flex justify-between text-green-600 dark:text-green-400">
                            <span>ส่วนลด</span>
                            <span class="font-semibold">
                                -฿{{ number_format($order->discount_amount, 2) }}
                            </span>
                        </div>
                        @endif

                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>ค่าจัดส่ง</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                @if($order->shipping_fee > 0)
                                    ฿{{ number_format($order->shipping_fee, 2) }}
                                @else
                                    <span class="text-green-600 dark:text-green-400">ฟรี</span>
                                @endif
                            </span>
                        </div>

                        @if($order->tax_amount > 0)
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>ภาษี</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100">
                                ฿{{ number_format($order->tax_amount, 2) }}
                            </span>
                        </div>
                        @endif

                        <div class="pt-3 border-t-2 border-gray-200 dark:border-gray-600"></div>

                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900 dark:text-gray-100">ยอดรวมทั้งหมด</span>
                            <span class="text-2xl font-black text-orange-600 dark:text-orange-400">
                                ฿{{ number_format($order->total_amount, 2) }}
                            </span>
                        </div>

                        <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ $order->payment_method }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                @if($order->shippingAddress)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 text-white px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            ที่อยู่จัดส่ง
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="space-y-2 text-gray-900 dark:text-gray-100">
                            <div class="font-bold text-lg">{{ $order->shippingAddress->full_name }}</div>
                            <div class="text-gray-600 dark:text-gray-400">{{ $order->shippingAddress->phone }}</div>
                            <div class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                {{ $order->shippingAddress->address }}<br>
                                {{ $order->shippingAddress->district }} {{ $order->shippingAddress->city }}<br>
                                {{ $order->shippingAddress->province }} {{ $order->shippingAddress->postal_code }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Actions -->
                @if($order->canBeCancelled())
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-red-200 dark:border-red-800 overflow-hidden">
                    <div class="p-6">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-3">ยกเลิกคำสั่งซื้อ</h3>
                        <form action="{{ route('orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกคำสั่งซื้อนี้?')">
                            @csrf
                            <textarea name="reason"
                                      class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-red-500 dark:focus:border-red-400 focus:ring-4 focus:ring-red-100 dark:focus:ring-red-900/50 transition-all mb-3"
                                      rows="3"
                                      placeholder="เหตุผลในการยกเลิก (ถ้ามี)"></textarea>
                            <button type="submit"
                                    class="w-full px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                ยกเลิกคำสั่งซื้อ
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
