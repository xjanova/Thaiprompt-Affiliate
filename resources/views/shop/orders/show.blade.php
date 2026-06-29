@extends('layouts.storefront')

@section('title', 'รายละเอียดคำสั่งซื้อ #' . $order->order_number)

@section('content')
<div class="py-6">

    {{-- Breadcrumb: กลับหน้าแรก/ร้านค้า/รายการคำสั่งซื้อ ได้ง่าย (layout storefront ไม่มี navbar) --}}
    <nav class="container mx-auto px-4 mb-4" aria-label="Breadcrumb">
        <ol class="flex items-center flex-wrap gap-2 text-sm">
            <li>
                <a href="{{ route('home') }}" class="text-gray-500 hover:text-indigo-600 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>
                    หน้าแรก
                </a>
            </li>
            <li><span class="text-gray-400">/</span></li>
            <li><a href="{{ route('storefront.index') }}" class="text-gray-500 hover:text-indigo-600 transition">ร้านค้า</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li><a href="{{ route('orders.index') }}" class="text-gray-500 hover:text-indigo-600 transition">คำสั่งซื้อของฉัน</a></li>
            <li><span class="text-gray-400">/</span></li>
            <li><span class="text-gray-700 dark:text-gray-300 font-medium">#{{ $order->order_number }}</span></li>
        </ol>
    </nav>

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
                        @php
                            // รวม tracking history จากร้านค้า (เรียงตาม tracked_at จากเก่าไปใหม่)
                            $trackingEntries = $order->trackingHistory
                                ? $order->trackingHistory->sortBy('tracked_at')
                                : collect();

                            // กรอง tracking entries ที่เป็นสถานะระหว่างทาง (หลังจัดส่ง ก่อนถึง)
                            $shippingUpdates = $trackingEntries->filter(function ($entry) {
                                return in_array($entry->status, ['in_transit', 'at_sorting_center', 'out_for_delivery']);
                            });

                            // ตรวจสอบว่าอยู่ในขั้นตอน "กำลังเตรียมสินค้า"
                            $isProcessing = in_array($order->status, ['processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'completed']);
                            $processingEntry = $trackingEntries->firstWhere('status', 'processing');

                            // ตรวจสอบว่ามีขั้นตอนถัดไปหรือยัง (สำหรับแสดงเส้น connector)
                            $hasNextAfterCreated = $order->paid_at || $isProcessing || $order->shipped_at || $order->delivered_at;
                            $hasNextAfterPaid = $isProcessing || $order->shipped_at || $order->delivered_at;
                            $hasNextAfterProcessing = $order->shipped_at || $order->delivered_at;
                            $hasNextAfterShipped = $shippingUpdates->count() > 0 || $order->delivered_at;

                            // คำนวณ tracking URL
                            $trackingUrl = null;
                            if ($order->tracking_number) {
                                if ($order->shippingProviderRelation) {
                                    $trackingUrl = $order->shippingProviderRelation->getTrackingLink($order->tracking_number);
                                } elseif ($order->tracking_url) {
                                    $trackingUrl = $order->tracking_url;
                                }
                            }
                        @endphp

                        <div class="relative">
                            <!-- Timeline -->
                            <div class="space-y-0">

                                {{-- 1. สร้างคำสั่งซื้อ (แสดงเสมอ) --}}
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white shadow-lg">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V8z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        @if($hasNextAfterCreated)
                                        <div class="w-1 flex-1 bg-gradient-to-b from-green-300 to-gray-200 dark:to-gray-600 mt-2"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 pb-6">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 mb-1">สร้างคำสั่งซื้อ</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $order->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                </div>

                                {{-- 2. ชำระเงินแล้ว --}}
                                @if($order->paid_at)
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full flex items-center justify-center text-white shadow-lg">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        @if($hasNextAfterPaid)
                                        <div class="w-1 flex-1 bg-gradient-to-b from-blue-300 to-gray-200 dark:to-gray-600 mt-2"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 pb-6">
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

                                {{-- 3. กำลังเตรียมสินค้า --}}
                                @if($isProcessing)
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-500 rounded-full flex items-center justify-center text-white shadow-lg">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 5a3 3 0 015-2.236A3 3 0 0114.83 6H16a2 2 0 110 4h-5V9a1 1 0 10-2 0v1H4a2 2 0 110-4h1.17C5.06 5.687 5 5.35 5 5zm4 1V5a1 1 0 10-1 1h1zm3 0a1 1 0 10-1-1v1h1z" clip-rule="evenodd"/>
                                                <path d="M9 11H3v5a2 2 0 002 2h4v-7zM11 18h4a2 2 0 002-2v-5h-6v7z"/>
                                            </svg>
                                        </div>
                                        @if($hasNextAfterProcessing)
                                        <div class="w-1 flex-1 bg-gradient-to-b from-violet-300 to-gray-200 dark:to-gray-600 mt-2"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 pb-6">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 mb-1">กำลังเตรียมสินค้า</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            @if($processingEntry)
                                                {{ $processingEntry->tracked_at->format('d/m/Y H:i') }}
                                            @elseif($order->paid_at)
                                                {{ $order->paid_at->format('d/m/Y H:i') }}
                                            @else
                                                {{ $order->created_at->format('d/m/Y H:i') }}
                                            @endif
                                        </div>
                                        @if($processingEntry && $processingEntry->description)
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            {{ $processingEntry->description }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                {{-- 4. จัดส่งสินค้าแล้ว --}}
                                @if($order->shipped_at)
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-blue-500 rounded-full flex items-center justify-center text-white shadow-lg">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                                                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                                            </svg>
                                        </div>
                                        @if($hasNextAfterShipped)
                                        <div class="w-1 flex-1 bg-gradient-to-b from-cyan-300 to-gray-200 dark:to-gray-600 mt-2"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 pb-6">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 mb-1">จัดส่งสินค้าแล้ว</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $order->shipped_at->format('d/m/Y H:i') }}
                                        </div>
                                        @if($order->tracking_number)
                                        <div class="mt-2 space-y-2">
                                            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-cyan-50 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 rounded-lg text-sm font-semibold">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                </svg>
                                                เลขพัสดุ: {{ $order->tracking_number }}
                                            </div>

                                            @if($order->shippingProviderRelation)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                ขนส่ง: {{ $order->shippingProviderRelation->name }}
                                            </div>
                                            @endif

                                            @if($trackingUrl)
                                            <div>
                                                <a href="{{ $trackingUrl }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="inline-flex items-center gap-2 px-4 py-2.5
                                                          bg-gradient-to-r from-cyan-600 to-blue-600
                                                          hover:from-cyan-700 hover:to-blue-700
                                                          text-white font-bold rounded-xl
                                                          shadow-lg hover:shadow-xl
                                                          transform hover:scale-105
                                                          transition-all text-sm">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                                    </svg>
                                                    ติดตามพัสดุที่เว็บไซต์ขนส่ง
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                    </svg>
                                                </a>
                                            </div>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                {{-- 5. อัพเดทระหว่างจัดส่ง (จาก tracking history ของร้านค้า) --}}
                                @foreach($shippingUpdates as $update)
                                @php
                                    $updateColors = [
                                        'in_transit' => ['from-sky-500', 'to-blue-500', 'from-sky-300'],
                                        'at_sorting_center' => ['from-teal-500', 'to-cyan-500', 'from-teal-300'],
                                        'out_for_delivery' => ['from-emerald-500', 'to-green-500', 'from-emerald-300'],
                                    ];
                                    $colors = $updateColors[$update->status] ?? ['from-gray-500', 'to-gray-500', 'from-gray-300'];
                                    $updateIcons = [
                                        'in_transit' => 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0',
                                        'at_sorting_center' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
                                        'out_for_delivery' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
                                    ];
                                    $iconPath = $updateIcons[$update->status] ?? 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7';
                                    $statusLabels = [
                                        'in_transit' => 'กำลังจัดส่ง',
                                        'at_sorting_center' => 'ถึงศูนย์กระจายสินค้า',
                                        'out_for_delivery' => 'กำลังนำส่ง',
                                    ];
                                    $isLastUpdate = $loop->last && !$order->delivered_at;
                                @endphp
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-10 h-10 bg-gradient-to-br {{ $colors[0] }} {{ $colors[1] }} rounded-full flex items-center justify-center text-white shadow-md">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"/>
                                            </svg>
                                        </div>
                                        @if(!$isLastUpdate)
                                        <div class="w-1 flex-1 bg-gradient-to-b {{ $colors[2] }} to-gray-200 dark:to-gray-600 mt-2"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 pb-5">
                                        <div class="font-bold text-gray-900 dark:text-gray-100 mb-1 text-sm">
                                            {{ $update->title ?: ($statusLabels[$update->status] ?? $update->status) }}
                                        </div>
                                        @if($update->description)
                                        <div class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ $update->description }}
                                        </div>
                                        @endif
                                        @if($update->location)
                                        <div class="text-xs text-gray-500 dark:text-gray-500 flex items-center gap-1 mt-0.5">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                            </svg>
                                            {{ $update->location }}
                                        </div>
                                        @endif
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                            {{ $update->tracked_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                </div>
                                @endforeach

                                {{-- 6. จัดส่งสำเร็จ --}}
                                @if($order->delivered_at)
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-amber-500 rounded-full flex items-center justify-center text-white shadow-lg">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                        @if($order->status === 'completed')
                                        <div class="w-1 flex-1 bg-gradient-to-b from-orange-300 to-gray-200 dark:to-gray-600 mt-2"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 pb-6">
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

                                {{-- 7. สำเร็จ (ลูกค้ายืนยันรับสินค้าแล้ว) --}}
                                @if($order->status === 'completed')
                                <div class="flex gap-4">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white shadow-lg ring-4 ring-green-200 dark:ring-green-900/50">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-bold text-green-700 dark:text-green-400 mb-1">สำเร็จ</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            ยืนยันรับสินค้าเรียบร้อยแล้ว
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- สถานะถัดไปที่รอ (แสดงเป็นจุดจาง) --}}
                                @if(!$order->paid_at && $order->status === 'pending')
                                <div class="flex gap-4 opacity-40">
                                    <div class="flex flex-col items-center">
                                        <div class="w-12 h-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex-1 pb-6">
                                        <div class="font-bold text-gray-400 dark:text-gray-500 mb-1">รอชำระเงิน</div>
                                        <div class="text-xs text-gray-400 dark:text-gray-500">รอดำเนินการ</div>
                                    </div>
                                </div>
                                @endif

                            </div>
                        </div>
                    </div>
                </div>

                {{-- Realtime Tracking Panel - แสดงเมื่อมีเลขพัสดุ --}}
                @if($order->tracking_number && in_array($order->status, ['shipped', 'in_transit', 'out_for_delivery', 'delivered']))
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden"
                     x-data="trackingPanel()"
                     x-init="loadTracking()">
                    <div class="bg-gradient-to-r from-cyan-600 to-blue-600 dark:from-cyan-700 dark:to-blue-700 text-white px-6 py-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold flex items-center gap-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                                ติดตามพัสดุ Realtime
                            </h2>
                            <button @click="loadTracking(true)"
                                    :disabled="loading"
                                    class="p-2 bg-white/20 hover:bg-white/30 rounded-lg transition-all disabled:opacity-50"
                                    title="รีเฟรช">
                                <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        {{-- Loading state --}}
                        <div x-show="loading && !trackingData" class="flex items-center justify-center py-8">
                            <svg class="animate-spin w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="ml-3 text-gray-600 dark:text-gray-400">กำลังดึงข้อมูลจากขนส่ง...</span>
                        </div>

                        {{-- Tracking Data --}}
                        <div x-show="trackingData" x-cloak>
                            {{-- Provider Info --}}
                            <div class="flex items-center justify-between mb-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM3 4h1l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-gray-100" x-text="trackingData?.provider?.name || 'ขนส่ง'"></div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400" x-text="trackingData?.provider?.hotline ? 'โทร: ' + trackingData.provider.hotline : ''"></div>
                                    </div>
                                </div>
                                <div x-show="trackingData?.from_cache" class="text-xs text-gray-400 dark:text-gray-500">
                                    จาก cache
                                </div>
                            </div>

                            {{-- Status Badge --}}
                            <div class="mb-4">
                                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold"
                                      :class="getStatusBadgeClass(trackingData?.current_status)">
                                    <span x-text="trackingData?.current_status_label || 'กำลังตรวจสอบ'"></span>
                                </span>
                            </div>

                            {{-- Tracking URL --}}
                            <div x-show="trackingData?.carrier_data?.tracking_url" class="mb-4">
                                <a :href="trackingData?.carrier_data?.tracking_url"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg text-sm font-semibold hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    ดูรายละเอียดเพิ่มเติมที่เว็บขนส่ง
                                </a>
                            </div>

                            {{-- Carrier Message --}}
                            <div x-show="trackingData?.carrier_data?.message" class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <p class="text-sm text-amber-800 dark:text-amber-300" x-text="trackingData?.carrier_data?.message"></p>
                            </div>

                            {{-- Timeline from carrier --}}
                            <div x-show="trackingData?.timeline && trackingData.timeline.length > 0" class="mt-4">
                                <h4 class="font-bold text-gray-900 dark:text-gray-100 mb-3 text-sm">ประวัติการขนส่ง</h4>
                                <div class="space-y-3 max-h-64 overflow-y-auto">
                                    <template x-for="(event, index) in trackingData?.timeline || []" :key="index">
                                        <div class="flex gap-3">
                                            <div class="flex flex-col items-center">
                                                <div class="w-3 h-3 rounded-full mt-1.5 flex-shrink-0"
                                                     :style="'background-color: ' + (event.color || '#9CA3AF')"></div>
                                                <div class="w-0.5 h-full bg-gray-200 dark:bg-gray-600 mt-1"
                                                     x-show="index < (trackingData?.timeline?.length || 0) - 1"></div>
                                            </div>
                                            <div class="flex-1 pb-3">
                                                <div class="font-semibold text-sm text-gray-900 dark:text-gray-100" x-text="event.title"></div>
                                                <div x-show="event.description" class="text-xs text-gray-600 dark:text-gray-400" x-text="event.description"></div>
                                                <div x-show="event.location" class="text-xs text-gray-500 dark:text-gray-500 flex items-center gap-1 mt-0.5">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span x-text="event.location"></span>
                                                </div>
                                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5"
                                                     x-text="event.timestamp_display || event.timestamp"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Error state --}}
                            <div x-show="error" class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <p class="text-sm text-red-600 dark:text-red-400" x-text="error"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                function trackingPanel() {
                    return {
                        loading: false,
                        trackingData: null,
                        error: null,

                        async loadTracking(forceRefresh = false) {
                            this.loading = true;
                            this.error = null;

                            try {
                                const url = '{{ route("orders.tracking.realtime", $order->id) }}' + (forceRefresh ? '?refresh=1' : '');
                                const response = await fetch(url, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    }
                                });
                                const data = await response.json();

                                if (data.success) {
                                    this.trackingData = data;
                                } else {
                                    this.error = data.message || 'ไม่สามารถดึงข้อมูลติดตามได้';
                                }
                            } catch (e) {
                                this.error = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
                            } finally {
                                this.loading = false;
                            }
                        },

                        getStatusBadgeClass(status) {
                            const classes = {
                                'delivered': 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                                'out_for_delivery': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                                'in_transit': 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                                'at_sorting_center': 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300',
                                'picked_up': 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
                                'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
                                'failed_delivery': 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                                'returned': 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
                            };
                            return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                        }
                    }
                }
                </script>
                @endif

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
                                    @if(in_array($order->status, ['delivered', 'completed']) && !$item->hasReview())
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

                <!-- ปุ่มชำระเงิน (สำหรับคำสั่งซื้อที่ยังไม่ได้ชำระ) -->
                @if($order->canRetryPayment())
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border-2 border-green-300 dark:border-green-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 dark:from-green-700 dark:to-emerald-700 text-white px-6 py-4">
                        <h2 class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                            </svg>
                            รอชำระเงิน
                        </h2>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            คำสั่งซื้อนี้ยังไม่ได้ชำระเงิน กดปุ่มด้านล่างเพื่อดำเนินการชำระเงิน
                        </p>
                        <form action="{{ route('orders.retry-payment', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold text-lg rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                                </svg>
                                ชำระเงินตอนนี้
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                <!-- ยกเลิกคำสั่งซื้อ -->
                @if($order->canBeCancelled())
                <div id="cancel-section" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-red-200 dark:border-red-800 overflow-hidden">
                    <div class="p-6">
                        <h3 class="font-bold text-gray-900 dark:text-gray-100 mb-3">ยกเลิกคำสั่งซื้อ</h3>
                        @if(in_array($order->status, ['paid', 'processing']))
                            <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-xl">
                                <p class="text-sm text-amber-700 dark:text-amber-300 flex items-center gap-2">
                                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    คำสั่งซื้อนี้ชำระเงินแล้ว — หากยกเลิก ระบบจะคืนเงินเข้า Wallet อัตโนมัติ
                                </p>
                            </div>
                        @endif
                        <form action="{{ route('orders.cancel', $order->id) }}" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกคำสั่งซื้อนี้?{{ in_array($order->status, ['paid', 'processing']) ? '\n\nคำสั่งซื้อนี้ชำระเงินแล้ว ระบบจะดำเนินการคืนเงินให้อัตโนมัติ' : '' }}')">
                            @csrf
                            <textarea name="reason"
                                      required
                                      class="w-full px-4 py-3 rounded-xl border-2 {{ $errors->has('reason') ? 'border-red-500 dark:border-red-400' : 'border-gray-200 dark:border-gray-600' }} bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:border-red-500 dark:focus:border-red-400 focus:ring-4 focus:ring-red-100 dark:focus:ring-red-900/50 transition-all mb-1"
                                      rows="3"
                                      placeholder="กรุณาระบุเหตุผลในการยกเลิก">{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="text-red-500 dark:text-red-400 text-sm mb-3">{{ $message }}</p>
                            @enderror
                            @if(!$errors->has('reason'))
                                <div class="mb-3"></div>
                            @endif
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
