{{--
    หน้าประวัติการซื้อ
--}}

@extends('layouts.user-arrow-x')

@section('title', $pageTitle ?? 'ประวัติการซื้อ')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <span class="text-3xl">📦</span>
                    ประวัติการซื้อ
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    ดูรายการสินค้าที่คุณซื้อทั้งหมด
                </p>
            </div>
            <a href="{{ route('user.coin-shop.index') }}"
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-yellow-400 to-orange-500 text-white font-semibold rounded-xl hover:from-yellow-500 hover:to-orange-600 transition">
                🛒 ไปร้านค้า
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"/>
                        <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">รายการทั้งหมด</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_purchases']) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                    <span class="text-yellow-600 dark:text-yellow-400 font-bold">C</span>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Coins ที่ใช้ไป</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_spent'], 0) }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ใช้งานได้</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active_items']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            {{-- Status Filter --}}
            <select onchange="window.location.href='{{ route('user.coin-shop.my-purchases') }}?status=' + this.value + '{{ $currentType ? '&type=' . $currentType : '' }}'"
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="">ทุกสถานะ</option>
                @foreach(\App\Models\CoinPurchase::STATUSES as $key => $status)
                    <option value="{{ $key }}" {{ $currentStatus === $key ? 'selected' : '' }}>
                        {{ $status['icon'] }} {{ $status['name'] }}
                    </option>
                @endforeach
            </select>

            {{-- Type Filter --}}
            <select onchange="window.location.href='{{ route('user.coin-shop.my-purchases') }}?type=' + this.value + '{{ $currentStatus ? '&status=' . $currentStatus : '' }}'"
                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                <option value="">ทุกประเภท</option>
                @foreach(\App\Models\CoinShopProduct::TYPES as $key => $typeInfo)
                    <option value="{{ $key }}" {{ $currentType === $key ? 'selected' : '' }}>
                        {{ $typeInfo['icon'] }} {{ $typeInfo['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Purchases List --}}
    @if($purchases->count() > 0)
    <div class="space-y-4">
        @foreach($purchases as $purchase)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition">
            <div class="p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                    {{-- Product Image --}}
                    <div class="flex-shrink-0">
                        @if($purchase->product?->thumbnail)
                            <img src="{{ $purchase->product->thumbnail_url }}"
                                 alt="{{ $purchase->product_name }}"
                                 class="w-16 h-16 sm:w-20 sm:h-20 rounded-lg object-cover">
                        @else
                            <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <span class="text-2xl sm:text-3xl">{{ $purchase->product_type_info['icon'] ?? '🎁' }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Product Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 text-xs font-medium bg-{{ $purchase->status_color }}-100 dark:bg-{{ $purchase->status_color }}-900 text-{{ $purchase->status_color }}-800 dark:text-{{ $purchase->status_color }}-200 rounded-full">
                                {{ $purchase->status_icon }} {{ $purchase->status_name }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                #{{ $purchase->order_number }}
                            </span>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                            {{ $purchase->product_name }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            จำนวน {{ $purchase->quantity }} ชิ้น • {{ $purchase->created_at->format('d/m/Y H:i') }}
                        </p>

                        {{-- Redemption Code (if exists and usable) --}}
                        @if($purchase->redemption_code && $purchase->is_usable)
                        <div class="mt-2">
                            <span class="inline-flex items-center px-2 py-1 bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200 text-sm font-mono rounded">
                                รหัส: {{ $purchase->redemption_code }}
                            </span>
                        </div>
                        @endif

                        {{-- Expiry Warning --}}
                        @if($purchase->expires_at && $purchase->is_usable)
                            @if($purchase->expires_at->isPast())
                                <p class="mt-1 text-xs text-red-500">หมดอายุแล้ว</p>
                            @elseif($purchase->expires_at->diffInDays(now()) <= 7)
                                <p class="mt-1 text-xs text-orange-500">
                                    หมดอายุใน {{ $purchase->expires_at->diffForHumans() }}
                                </p>
                            @endif
                        @endif
                    </div>

                    {{-- Price & Actions --}}
                    <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-center gap-3">
                        <div class="text-right">
                            <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">
                                {{ number_format($purchase->total_coins, 0) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Coins</p>
                        </div>
                        <a href="{{ route('user.coin-shop.purchase-detail', $purchase) }}"
                           class="px-4 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                            ดูรายละเอียด
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-8">
        {{ $purchases->withQueryString()->links() }}
    </div>
    @else
    {{-- Empty State --}}
    <div class="text-center py-16">
        <div class="text-6xl mb-4">📦</div>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีประวัติการซื้อ</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            เริ่มช้อปปิ้งด้วย Coins ของคุณเลย!
        </p>
        <a href="{{ route('user.coin-shop.index') }}"
           class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-yellow-400 to-orange-500 text-white font-semibold rounded-lg hover:from-yellow-500 hover:to-orange-600 transition">
            🛒 ไปร้านค้า
        </a>
    </div>
    @endif
</div>
@endsection
