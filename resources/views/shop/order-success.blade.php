@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Success Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-green-100 rounded-full mb-6">
                <svg class="w-16 h-16 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-4xl font-black text-gray-900 mb-3">🎉 สั่งซื้อสำเร็จ!</h1>
            <p class="text-xl text-gray-600 mb-2">ขอบคุณสำหรับคำสั่งซื้อของคุณ</p>
            <p class="text-gray-500">
                เลขที่คำสั่งซื้อ:
                <span class="font-bold text-indigo-600">{{ $order->order_number }}</span>
            </p>
        </div>

        <!-- Order Status Card -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">สถานะคำสั่งซื้อ</h2>
                <span class="px-4 py-2 bg-yellow-100 text-yellow-800 font-semibold rounded-lg">
                    {{ $order->status_label }}
                </span>
            </div>

            @if($order->status === 'pending')
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">รอชำระเงิน</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            @if($order->payment_method === 'promptpay')
                                <p>กรุณาชำระเงินผ่าน PromptPay โดยสแกน QR Code ที่แนบมาในอีเมล</p>
                            @elseif($order->payment_method === 'bank_transfer')
                                <p>กรุณาโอนเงินเข้าบัญชีธนาคารตามรายละเอียดที่แนบมาในอีเมล</p>
                            @elseif($order->payment_method === 'credit_card')
                                <p>คำสั่งซื้อของคุณกำลังดำเนินการชำระเงิน</p>
                            @elseif($order->payment_method === 'cod')
                                <p>คุณสามารถชำระเงินเมื่อได้รับสินค้า</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-gray-500 text-sm mb-1">วิธีชำระเงิน</div>
                    <div class="font-semibold text-gray-900">
                        @if($order->payment_method === 'promptpay')
                            📱 PromptPay
                        @elseif($order->payment_method === 'bank_transfer')
                            🏦 โอนธนาคาร
                        @elseif($order->payment_method === 'credit_card')
                            💳 บัตรเครดิต
                        @elseif($order->payment_method === 'cod')
                            💵 เก็บเงินปลายทาง
                        @else
                            {{ $order->payment_method }}
                        @endif
                    </div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-gray-500 text-sm mb-1">สถานะการชำระเงิน</div>
                    <div class="font-semibold text-gray-900">
                        @if($order->payment_status === 'paid')
                            ✅ ชำระแล้ว
                        @elseif($order->payment_status === 'pending')
                            ⏳ รอชำระเงิน
                        @elseif($order->payment_status === 'failed')
                            ❌ ชำระไม่สำเร็จ
                        @else
                            {{ $order->payment_status }}
                        @endif
                    </div>
                </div>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-gray-500 text-sm mb-1">วันที่สั่งซื้อ</div>
                    <div class="font-semibold text-gray-900">
                        {{ $order->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Address -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">📍 ที่อยู่จัดส่ง</h2>
            @if($order->shippingAddress)
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="font-bold text-gray-900 mb-2">{{ $order->shippingAddress->recipient_name }}</p>
                    <p class="text-gray-700 mb-1">{{ $order->shippingAddress->phone_number }}</p>
                    <p class="text-gray-700">{{ $order->shippingAddress->full_address }}</p>
                </div>
            @elseif($order->shipping_address_snapshot)
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="font-bold text-gray-900 mb-2">{{ $order->shipping_address_snapshot['recipient_name'] ?? '' }}</p>
                    <p class="text-gray-700 mb-1">{{ $order->shipping_address_snapshot['phone_number'] ?? '' }}</p>
                    <p class="text-gray-700">
                        {{ implode(', ', array_filter([
                            $order->shipping_address_snapshot['address_line_1'] ?? '',
                            $order->shipping_address_snapshot['address_line_2'] ?? '',
                            $order->shipping_address_snapshot['sub_district'] ?? '',
                            $order->shipping_address_snapshot['district'] ?? '',
                            $order->shipping_address_snapshot['province'] ?? '',
                            $order->shipping_address_snapshot['postal_code'] ?? '',
                        ])) }}
                    </p>
                </div>
            @endif

            @if($order->customer_notes)
                <div class="mt-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                    <p class="text-sm font-semibold text-gray-700 mb-1">📝 หมายเหตุ:</p>
                    <p class="text-sm text-gray-700">{{ $order->customer_notes }}</p>
                </div>
            @endif
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">📦 รายการสินค้า</h2>

            <div class="space-y-4">
                @foreach($order->items as $item)
                <div class="flex gap-4 pb-4 border-b border-gray-200 last:border-0">
                    <!-- Product Image -->
                    <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                        @if($item->product_image)
                            <img src="{{ $item->product_image }}"
                                 alt="{{ $item->product_name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400 text-3xl">
                                📦
                            </div>
                        @endif
                    </div>

                    <!-- Product Details -->
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 mb-1">{{ $item->product_name }}</h3>
                        @if($item->product_sku)
                            <p class="text-xs text-gray-500 mb-1">SKU: {{ $item->product_sku }}</p>
                        @endif
                        @if($item->product_attributes && count($item->product_attributes) > 0)
                            <div class="flex flex-wrap gap-1 mb-2">
                                @foreach($item->product_attributes as $key => $value)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ ucfirst($key) }}: {{ $value }}
                                </span>
                                @endforeach
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                ฿{{ number_format($item->unit_price, 2) }} x {{ $item->quantity }}
                            </div>
                            <div class="text-lg font-bold text-indigo-600">
                                ฿{{ number_format($item->subtotal, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Order Summary -->
        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">💰 สรุปยอดชำระ</h2>

            <div class="space-y-3">
                <div class="flex justify-between text-gray-700">
                    <span>ยอดรวมสินค้า ({{ $order->total_items }} ชิ้น)</span>
                    <span class="font-semibold">฿{{ number_format($order->subtotal, 2) }}</span>
                </div>

                @if($order->discount_amount > 0)
                <div class="flex justify-between text-green-600">
                    <span>ส่วนลด</span>
                    <span class="font-semibold">-฿{{ number_format($order->discount_amount, 2) }}</span>
                </div>
                @endif

                <div class="flex justify-between text-gray-700">
                    <span>ค่าจัดส่ง</span>
                    <span class="font-semibold">
                        @if($order->shipping_fee == 0)
                            <span class="text-green-600">ฟรี</span>
                        @else
                            ฿{{ number_format($order->shipping_fee, 2) }}
                        @endif
                    </span>
                </div>

                @if($order->tax_amount > 0)
                <div class="flex justify-between text-gray-700">
                    <span>ภาษี</span>
                    <span class="font-semibold">฿{{ number_format($order->tax_amount, 2) }}</span>
                </div>
                @endif
            </div>

            <div class="mt-6 pt-4 border-t-2 border-gray-200">
                <div class="flex justify-between items-center">
                    <span class="text-xl font-bold text-gray-900">ยอดรวมทั้งหมด</span>
                    <span class="text-3xl font-black text-indigo-600">
                        ฿{{ number_format($order->total_amount, 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4">
            <a href="{{ route('orders.show', $order->id) }}"
               class="flex-1 px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-center font-bold text-lg rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                📋 ดูรายละเอียดคำสั่งซื้อ
            </a>
            <a href="{{ route('shop.index') }}"
               class="flex-1 px-6 py-4 border-2 border-gray-300 hover:border-indigo-600 text-gray-700 hover:text-indigo-600 text-center font-bold text-lg rounded-xl transition">
                🛍️ ช๊อปปิ้งต่อ
            </a>
        </div>

        <!-- Additional Info -->
        <div class="mt-8 text-center">
            <p class="text-gray-600 mb-2">
                เราได้ส่งอีเมลยืนยันการสั่งซื้อไปยังที่อยู่อีเมลของคุณแล้ว
            </p>
            <p class="text-sm text-gray-500">
                หากคุณมีคำถามเกี่ยวกับคำสั่งซื้อ กรุณา
                <a href="{{ route('contact') }}" class="text-indigo-600 hover:text-indigo-700 font-medium underline">
                    ติดต่อเรา
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
