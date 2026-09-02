{{--
    หน้าประวัติการซื้อ
--}}

@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'ประวัติการซื้อ')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Premium Hero Header (Blue-Indigo for Purchases) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 dark:from-blue-800 dark:via-indigo-800 dark:to-purple-800 rounded-2xl shadow-2xl p-8 mb-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icon Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-box-open"></i>
            </div>
        </div>

        {{-- Content --}}
        <div class="relative z-10">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 mb-6">
                <div class="flex items-center gap-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-receipt text-3xl text-white drop-shadow-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">
                            📦 ประวัติการซื้อ
                        </h1>
                        <p class="text-blue-100 mt-1">
                            ดูรายการสินค้าที่คุณซื้อทั้งหมด
                        </p>
                    </div>
                </div>
                <a href="{{ route('user.coin-shop.index') }}"
                   class="glass-fusion px-6 py-3 hover:bg-white/30 rounded-xl transition inline-flex items-center gap-2 text-white font-semibold">
                    <i class="fas fa-store"></i>
                    ไปร้านค้า
                </a>
            </div>

            {{-- Stats in Hero --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="glass-fusion rounded-xl p-4 text-center">
                    <p class="text-blue-100 text-xs mb-1">รายการทั้งหมด</p>
                    <p class="text-3xl font-bold text-white drop-shadow-lg">{{ number_format($stats['total_purchases']) }}</p>
                </div>
                <div class="glass-fusion rounded-xl p-4 text-center">
                    <p class="text-blue-100 text-xs mb-1">Coins ที่ใช้ไป</p>
                    <p class="text-3xl font-bold text-white drop-shadow-lg">{{ number_format($stats['total_spent'], 0) }}</p>
                </div>
                <div class="glass-fusion rounded-xl p-4 text-center">
                    <p class="text-blue-100 text-xs mb-1">ใช้งานได้</p>
                    <p class="text-3xl font-bold text-white drop-shadow-lg">{{ number_format($stats['active_items']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="tp-card rounded-xl p-4 mb-6">
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
        <div class="tp-card rounded-xl overflow-hidden hover:shadow-xl transition">
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

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
