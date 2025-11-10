@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('home') }}" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                        หน้าแรก
                    </a>
                </li>
                <li class="text-gray-400 dark:text-gray-600">/</li>
                <li>
                    <a href="{{ route('shop.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                        ร้านค้า
                    </a>
                </li>
                <li class="text-gray-400 dark:text-gray-600">/</li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">ตะกร้าสินค้า</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 dark:text-gray-100 mb-2">🛒 ตะกร้าสินค้า</h1>
            <p class="text-gray-600 dark:text-gray-400">จัดการสินค้าในตะกร้าของคุณ</p>
        </div>

        @if(session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 dark:border-green-600 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500 dark:text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 dark:border-red-600 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500 dark:text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        @if($cartItems->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2 space-y-4">
                @foreach($cartItems as $item)
                @php
                    $product = $item->product;
                @endphp
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 hover:shadow-xl transition">
                    <div class="flex flex-col sm:flex-row gap-6">
                        <!-- Product Image -->
                        <div class="flex-shrink-0">
                            <a href="{{ route('shop.show', $product->slug) }}" class="block">
                                <div class="w-full sm:w-32 aspect-square bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 rounded-xl overflow-hidden">
                                    @if($product->main_image_url)
                                        <img src="{{ $product->main_image_url }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                            <span class="text-5xl">📦</span>
                                        </div>
                                    @endif
                                </div>
                            </a>
                        </div>

                        <!-- Product Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col h-full">
                                <!-- Product Name & Category -->
                                <div class="flex-1">
                                    <a href="{{ route('shop.show', $product->slug) }}"
                                       class="text-lg font-bold text-gray-900 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition line-clamp-2">
                                        {{ $product->name }}
                                    </a>
                                    @if($product->category)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $product->category->name }}</p>
                                    @endif
                                    @if($item->selected_attributes && count($item->selected_attributes) > 0)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($item->selected_attributes as $key => $value)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            {{ ucfirst($key) }}: {{ $value }}
                                        </span>
                                        @endforeach
                                    </div>
                                    @endif
                                    @if($product->seller)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                        ขายโดย: <span class="font-medium">{{ $product->seller->name }}</span>
                                    </p>
                                    @endif
                                </div>

                                <!-- Price & Quantity Controls -->
                                <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <!-- Quantity -->
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">จำนวน:</span>
                                        <div class="flex items-center border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                            <button onclick="updateCartQuantity({{ $item->id }}, {{ max(1, $item->quantity - 1) }})"
                                                    class="px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-600 font-bold transition text-gray-900 dark:text-gray-100"
                                                    {{ $item->quantity <= 1 ? 'disabled' : '' }}>
                                                -
                                            </button>
                                            <input type="number"
                                                   id="qty-{{ $item->id }}"
                                                   value="{{ $item->quantity }}"
                                                   min="1"
                                                   max="{{ $product->stock_quantity ?? 999 }}"
                                                   onchange="updateCartQuantity({{ $item->id }}, this.value)"
                                                   class="w-16 text-center border-x-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 py-1 font-semibold focus:outline-none">
                                            <button onclick="updateCartQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                                    class="px-3 py-1 hover:bg-gray-100 dark:hover:bg-gray-600 font-bold transition text-gray-900 dark:text-gray-100">
                                                +
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Price -->
                                    <div class="flex items-center justify-between sm:justify-end gap-4">
                                        <div class="text-right">
                                            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400">
                                                ฿{{ number_format($product->price * $item->quantity, 2) }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                ฿{{ number_format($product->price, 2) }} / ชิ้น
                                            </div>
                                        </div>

                                        <!-- Remove Button -->
                                        <form action="{{ route('cart.remove', $item->id) }}" method="POST" onsubmit="return confirm('ต้องการลบสินค้านี้ออกจากตะกร้า?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-2 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                                                    title="ลบออกจากตะกร้า">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Stock Warning -->
                                @if($product->track_inventory && $product->stock_quantity < $product->low_stock_threshold)
                                <div class="mt-2 text-sm text-orange-600 dark:text-orange-400 font-medium">
                                    ⚠️ เหลือเพียง {{ $product->stock_quantity }} ชิ้น
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach

                <!-- Clear Cart Button -->
                <div class="mt-6">
                    <form action="{{ route('cart.clear') }}" method="POST" onsubmit="return confirm('ต้องการล้างสินค้าทั้งหมดในตะกร้า?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-6 py-3 border-2 border-red-500 dark:border-red-600 text-red-500 dark:text-red-400 hover:bg-red-500 dark:hover:bg-red-600 hover:text-white font-semibold rounded-xl transition">
                            🗑️ ล้างตะกร้าสินค้า
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">สรุปคำสั่งซื้อ</h2>

                    <div class="space-y-4 mb-6">
                        <!-- Subtotal -->
                        <div class="flex justify-between text-gray-700 dark:text-gray-300">
                            <span>ยอดรวมสินค้า ({{ $cartItems->sum('quantity') }} ชิ้น)</span>
                            <span class="font-semibold">฿{{ number_format($subtotal, 2) }}</span>
                        </div>

                        <!-- Shipping -->
                        <div class="flex justify-between text-gray-700 dark:text-gray-300">
                            <span>ค่าจัดส่ง</span>
                            <span class="font-semibold">
                                @if($subtotal >= 500)
                                    <span class="text-green-600 dark:text-green-400">ฟรี</span>
                                @else
                                    ฿{{ number_format($shippingFee, 2) }}
                                @endif
                            </span>
                        </div>

                        @if($subtotal < 500 && $subtotal > 0)
                        <div class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg">
                            💡 ซื้อเพิ่มอีก <span class="font-bold text-indigo-600 dark:text-indigo-400">฿{{ number_format(500 - $subtotal, 2) }}</span> เพื่อรับการจัดส่งฟรี!
                        </div>
                        @endif
                    </div>

                    <!-- Total -->
                    <div class="pt-4 border-t-2 border-gray-200 dark:border-gray-700 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900 dark:text-gray-100">ยอดรวมทั้งหมด</span>
                            <span class="text-3xl font-black text-indigo-600 dark:text-indigo-400">
                                ฿{{ number_format($total, 2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Checkout Button -->
                    <a href="{{ route('checkout.index') }}"
                       class="block w-full px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-700 dark:to-purple-700 hover:from-indigo-700 hover:to-purple-700 dark:hover:from-indigo-600 dark:hover:to-purple-600 text-white text-center font-bold text-lg rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        ⚡ ดำเนินการชำระเงิน
                    </a>

                    <!-- Continue Shopping -->
                    <a href="{{ route('shop.index') }}"
                       class="block w-full text-center px-6 py-3 mt-3 border-2 border-gray-300 dark:border-gray-600 hover:border-indigo-600 dark:hover:border-indigo-400 text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 font-semibold rounded-xl transition">
                        ← ช๊อปปิ้งต่อ
                    </a>

                    <!-- Payment Methods -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">เรารับชำระผ่าน</p>
                        <div class="grid grid-cols-2 gap-2">
                            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-center text-xs font-medium text-gray-600 dark:text-gray-400">
                                💳 บัตรเครดิต
                            </div>
                            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-center text-xs font-medium text-gray-600 dark:text-gray-400">
                                🏦 โอนธนาคาร
                            </div>
                            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-center text-xs font-medium text-gray-600 dark:text-gray-400">
                                📱 PromptPay
                            </div>
                            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700 rounded-lg text-center text-xs font-medium text-gray-600 dark:text-gray-400">
                                💵 เก็บเงินปลายทาง
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <!-- Empty Cart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-12 text-center">
            <div class="text-8xl mb-6">🛒</div>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-3">ตะกร้าสินค้าว่างเปล่า</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-8 text-lg">คุณยังไม่มีสินค้าในตะกร้า เริ่มช๊อปปิ้งกันเลย!</p>
            <a href="{{ route('shop.index') }}"
               class="inline-block px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 dark:from-indigo-700 dark:to-purple-700 hover:from-indigo-700 hover:to-purple-700 dark:hover:from-indigo-600 dark:hover:to-purple-600 text-white font-bold text-lg rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                🛍️ เริ่มช๊อปปิ้ง
            </a>
        </div>
        @endif
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
function updateCartQuantity(itemId, quantity) {
    // Ensure quantity is valid
    quantity = parseInt(quantity);
    if (quantity < 1) quantity = 1;

    // Update the input value
    const input = document.getElementById('qty-' + itemId);
    if (input) {
        input.value = quantity;
    }

    // Send AJAX request to update cart
    fetch(`/cart/${itemId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ quantity: quantity })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to update totals
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาดในการอัพเดตจำนวนสินค้า');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการอัพเดตจำนวนสินค้า');
        location.reload();
    });
}
</script>
@endsection
