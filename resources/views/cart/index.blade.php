@extends('layouts.app')

@section('title', 'ตะกร้าสินค้า')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">ตะกร้าสินค้า</h1>

    @if(isset($cart) && $cart->items->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow">
                    @foreach($cart->items as $item)
                        <div class="p-6 border-b last:border-b-0">
                            <div class="flex items-center space-x-4">
                                <img src="{{ $item->product->featured_image ?? asset('images/placeholder.jpg') }}"
                                     alt="{{ $item->product->name }}"
                                     class="w-24 h-24 object-cover rounded">

                                <div class="flex-1">
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="text-lg font-semibold text-gray-900 hover:text-indigo-600">
                                        {{ $item->product->name }}
                                    </a>
                                    <p class="text-sm text-gray-500 mt-1">{{ $item->product->vendor->name }}</p>

                                    <div class="flex items-center mt-2">
                                        <p class="text-lg font-bold text-gray-900">฿{{ number_format($item->product->getFinalPrice(), 2) }}</p>
                                        @if($item->product->hasDiscount())
                                            <span class="ml-2 text-sm text-gray-400 line-through">฿{{ number_format($item->product->price, 2) }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4">
                                    <!-- Quantity -->
                                    <form action="{{ route('cart.update', $item->id) }}" method="POST" class="flex items-center space-x-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="{{ $item->product->stock_quantity }}"
                                            class="w-16 rounded-md border-gray-300 text-center"
                                            onchange="this.form.submit()">
                                    </form>

                                    <!-- Subtotal -->
                                    <div class="w-24 text-right">
                                        <p class="text-lg font-bold text-gray-900">฿{{ number_format($item->subtotal, 2) }}</p>
                                    </div>

                                    <!-- Remove -->
                                    <form action="{{ route('cart.remove', $item->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <form action="{{ route('cart.clear') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-700 text-sm">ล้างตะกร้าทั้งหมด</button>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                    <h2 class="text-lg font-semibold mb-4">สรุปรายการ</h2>

                    <div class="space-y-3 border-b pb-4 mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">ยอดรวมสินค้า ({{ $cart->items->count() }} รายการ)</span>
                            <span class="font-medium">฿{{ number_format($cart->getTotal(), 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">ค่าจัดส่ง</span>
                            <span class="font-medium text-green-600">ฟรี</span>
                        </div>
                    </div>

                    <div class="flex justify-between text-lg font-bold mb-6">
                        <span>ยอดรวมทั้งหมด</span>
                        <span class="text-indigo-600">฿{{ number_format($cart->getTotal(), 2) }}</span>
                    </div>

                    <a href="{{ route('checkout.index') }}" class="block w-full bg-indigo-600 text-white text-center py-3 rounded-lg font-semibold hover:bg-indigo-700 transition mb-3">
                        ดำเนินการชำระเงิน
                    </a>

                    <a href="{{ route('products.index') }}" class="block w-full text-center py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        เลือกซื้อสินค้าเพิ่ม
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">ตะกร้าสินค้าว่างเปล่า</h3>
            <p class="mt-2 text-sm text-gray-500">เริ่มเลือกซื้อสินค้าเพื่อเพิ่มลงในตะกร้า</p>
            <div class="mt-6">
                <a href="{{ route('products.index') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    เลือกซื้อสินค้า
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
