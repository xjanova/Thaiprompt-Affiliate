{{--
    หน้ารายละเอียดสินค้า Coin Shop
--}}

@extends('layouts.user-v4')

@section('title', $pageTitle ?? $product->display_name)

@section('content')
<div class="container mx-auto px-4 py-8" x-data="{ quantity: 1, maxQty: {{ $product->max_per_user ?? 100 }} }">
    {{-- Premium Hero Header (Amber-Orange for Product) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-amber-500 via-orange-500 to-yellow-500 dark:from-amber-700 dark:via-orange-700 dark:to-yellow-700 rounded-2xl shadow-2xl p-8 mb-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icon Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-tag"></i>
            </div>
        </div>

        {{-- Content --}}
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <a href="{{ route('user.coin-shop.index') }}"
                   class="glass-fusion p-3 hover:bg-white/30 rounded-lg transition">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div class="glass-fusion p-4 rounded-2xl">
                    <i class="fas fa-box-open text-3xl text-white drop-shadow-lg"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">
                        {{ $product->display_name }}
                    </h1>
                    <p class="text-amber-100 mt-1">
                        รายละเอียดสินค้า • {{ $product->type_icon }} {{ $product->type_name }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Left: Product Image --}}
        <div>
            <div class="relative aspect-square bg-gray-100 dark:bg-gray-700 rounded-2xl overflow-hidden shadow-lg">
                @if($product->thumbnail)
                    <img src="{{ $product->thumbnail_url }}"
                         alt="{{ $product->display_name }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-{{ $product->type_color }}-100 to-{{ $product->type_color }}-200 dark:from-{{ $product->type_color }}-900 dark:to-{{ $product->type_color }}-800">
                        <span class="text-9xl">{{ $product->type_icon }}</span>
                    </div>
                @endif

                {{-- Badges --}}
                <div class="absolute top-4 left-4 flex flex-col gap-2">
                    @if($product->is_featured)
                        <span class="px-3 py-1 text-sm font-bold bg-yellow-400 text-yellow-900 rounded-full">
                            ⭐ แนะนำ
                        </span>
                    @endif
                    @if($product->is_new)
                        <span class="px-3 py-1 text-sm font-bold bg-green-500 text-white rounded-full">
                            ใหม่
                        </span>
                    @endif
                    @if($product->is_limited)
                        <span class="px-3 py-1 text-sm font-bold bg-red-500 text-white rounded-full">
                            Limited Edition
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Product Info --}}
        <div>
            {{-- Type Badge --}}
            <div class="mb-4">
                <span class="px-3 py-1 text-sm font-medium bg-{{ $product->type_color }}-100 dark:bg-{{ $product->type_color }}-900 text-{{ $product->type_color }}-800 dark:text-{{ $product->type_color }}-200 rounded-full">
                    {{ $product->type_icon }} {{ $product->type_name }}
                </span>
            </div>

            {{-- Name --}}
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ $product->display_name }}
            </h1>

            {{-- Price --}}
            <div class="mt-4">
                @if($product->has_discount)
                    <div class="flex items-center gap-3">
                        <span class="text-2xl text-gray-400 line-through">
                            {{ number_format($product->original_price_coins, 0) }} Coins
                        </span>
                        <span class="px-3 py-1 text-sm font-bold bg-orange-500 text-white rounded-full">
                            -{{ $product->discount_percent }}%
                        </span>
                    </div>
                @endif
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ number_format($product->price_coins, 0) }}
                    </span>
                    <span class="text-xl text-yellow-600 dark:text-yellow-400">Coins</span>
                </div>
                @if($product->price_money)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        มูลค่า {{ number_format($product->price_money, 0) }} บาท
                    </p>
                @endif
            </div>

            {{-- Description --}}
            @if($product->display_description)
                <div class="mt-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2">รายละเอียด</h3>
                    <p class="text-gray-600 dark:text-gray-400 whitespace-pre-line">
                        {{ $product->display_description }}
                    </p>
                </div>
            @endif

            {{-- Product Value Info --}}
            @if($product->value_amount)
                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg">
                    <div class="flex items-center gap-2 text-green-700 dark:text-green-400">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-semibold">
                            มูลค่า: {{ number_format($product->value_amount, 0) }} {{ $product->value_unit ?? '' }}
                        </span>
                    </div>
                </div>
            @endif

            {{-- Stock Info --}}
            @if($product->stock_quantity !== null)
                <div class="mt-4">
                    <span class="text-sm {{ $product->stock_quantity > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500' }}">
                        @if($product->stock_quantity > 0)
                            คงเหลือ {{ number_format($product->stock_quantity) }} ชิ้น
                        @else
                            สินค้าหมด
                        @endif
                    </span>
                </div>
            @endif

            {{-- Requirements --}}
            <div class="mt-6 space-y-2">
                @if($product->min_level > 1)
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span>ต้องมี Level {{ $product->min_level }}+ จึงจะซื้อได้</span>
                    </div>
                @endif

                @if($product->max_per_user)
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        <span>จำกัด {{ $product->max_per_user }} ชิ้น/คน</span>
                    </div>
                @endif

                @if($product->use_expire_days)
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        <span>ต้องใช้ภายใน {{ $product->use_expire_days }} วันหลังซื้อ</span>
                    </div>
                @endif
            </div>

            {{-- Purchase Form --}}
            <div class="mt-8 p-6 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                @if($canPurchase)
                    <form action="{{ route('user.coin-shop.purchase', $product) }}" method="POST">
                        @csrf

                        {{-- Quantity --}}
                        <div class="flex items-center gap-4 mb-4">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">จำนวน:</label>
                            <div class="flex items-center">
                                <button type="button"
                                        @click="quantity = Math.max(1, quantity - 1)"
                                        class="w-10 h-10 flex items-center justify-center bg-gray-200 dark:bg-gray-600 rounded-l-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                    </svg>
                                </button>
                                <input type="number"
                                       name="quantity"
                                       x-model="quantity"
                                       min="1"
                                       :max="maxQty"
                                       class="tp-card w-16 h-10 text-center border-y border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white">
                                <button type="button"
                                        @click="quantity = Math.min(maxQty, quantity + 1)"
                                        class="w-10 h-10 flex items-center justify-center bg-gray-200 dark:bg-gray-600 rounded-r-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Total --}}
                        <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">รวมทั้งหมด:</span>
                            <span class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"
                                  x-text="(quantity * {{ $product->price_coins }}).toLocaleString() + ' Coins'">
                                {{ number_format($product->price_coins, 0) }} Coins
                            </span>
                        </div>

                        {{-- Your Balance --}}
                        <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">Coins ของคุณ:</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($coinBalance, 2) }} Coins
                            </span>
                        </div>

                        {{-- After Purchase --}}
                        <div class="flex items-center justify-between py-3 border-t border-gray-200 dark:border-gray-600">
                            <span class="text-gray-700 dark:text-gray-300">คงเหลือหลังซื้อ:</span>
                            <span class="text-lg font-semibold"
                                  :class="({{ $coinBalance }} - (quantity * {{ $product->price_coins }})) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500'"
                                  x-text="({{ $coinBalance }} - (quantity * {{ $product->price_coins }})).toLocaleString() + ' Coins'">
                            </span>
                        </div>

                        {{-- Buy Button --}}
                        <button type="submit"
                                :disabled="({{ $coinBalance }} - (quantity * {{ $product->price_coins }})) < 0"
                                class="w-full mt-4 py-4 text-lg font-bold bg-gradient-to-r from-yellow-400 to-orange-500 text-white rounded-xl hover:from-yellow-500 hover:to-orange-600 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed transition shadow-lg">
                            <span x-show="({{ $coinBalance }} - (quantity * {{ $product->price_coins }})) >= 0">
                                ซื้อเลย
                            </span>
                            <span x-show="({{ $coinBalance }} - (quantity * {{ $product->price_coins }})) < 0">
                                Coins ไม่เพียงพอ
                            </span>
                        </button>
                    </form>
                @else
                    {{-- Cannot Purchase --}}
                    <div class="text-center py-4">
                        <div class="text-4xl mb-3">😔</div>
                        <p class="text-red-500 font-medium">{{ $purchaseReason }}</p>
                        <a href="{{ route('user.video-missions.index') }}"
                           class="inline-block mt-4 px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                            ไปเก็บ Coins เพิ่ม
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- User's Previous Purchases --}}
    @if($userPurchases->count() > 0)
    <div class="mt-12">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">ประวัติการซื้อของคุณ</h2>
        <div class="tp-card rounded-xl overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">วันที่</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">จำนวน</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ราคา</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($userPurchases as $purchase)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $purchase->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $purchase->quantity }}
                        </td>
                        <td class="px-4 py-3 text-sm text-yellow-600 dark:text-yellow-400 font-medium">
                            {{ number_format($purchase->total_coins, 0) }} Coins
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium bg-{{ $purchase->status_color }}-100 dark:bg-{{ $purchase->status_color }}-900 text-{{ $purchase->status_color }}-800 dark:text-{{ $purchase->status_color }}-200 rounded-full">
                                {{ $purchase->status_icon }} {{ $purchase->status_name }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Related Products --}}
    @if($relatedProducts->count() > 0)
    <div class="mt-12">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">สินค้าที่คล้ายกัน</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($relatedProducts as $relatedProduct)
                @include('user.coin-shop._product-card', ['product' => $relatedProduct])
            @endforeach
        </div>
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
