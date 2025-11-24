@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <li>
                    <a href="{{ route('home') }}" class="text-gray-500 hover:text-indigo-600 transition">
                        หน้าแรก
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                <li>
                    <a href="{{ route('shop.index') }}" class="text-gray-500 hover:text-indigo-600 transition">
                        ร้านค้า
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                <li>
                    <a href="{{ route('cart.index') }}" class="text-gray-500 hover:text-indigo-600 transition">
                        ตะกร้าสินค้า
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                <li class="text-gray-700 font-medium">ชำระเงิน</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 mb-2">💳 ชำระเงิน</h1>
            <p class="text-gray-600">กรอกข้อมูลการจัดส่งและเลือกวิธีการชำระเงิน</p>
        </div>

        @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        <form action="{{ route('checkout.process') }}" method="POST" id="checkoutForm">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column - Shipping & Payment -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Shipping Address Section -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-900">📍 ที่อยู่จัดส่ง</h2>
                            <a href="{{ route('shipping-addresses.create') }}"
                               class="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                                + เพิ่มที่อยู่ใหม่
                            </a>
                        </div>

                        @if($addresses->count() > 0)
                            <div class="space-y-3">
                                @foreach($addresses as $address)
                                <div class="relative">
                                    <input type="radio"
                                           name="shipping_address_id"
                                           id="address-{{ $address->id }}"
                                           value="{{ $address->id }}"
                                           {{ $address->is_default ? 'checked' : '' }}
                                           class="peer sr-only"
                                           required>
                                    <label for="address-{{ $address->id }}"
                                           class="flex items-start p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="font-bold text-gray-900">{{ $address->recipient_name }}</span>
                                                @if($address->is_default)
                                                <span class="px-2 py-1 bg-indigo-100 text-indigo-700 text-xs font-semibold rounded">
                                                    ค่าเริ่มต้น
                                                </span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-gray-600 mb-1">{{ $address->phone_number }}</p>
                                            <p class="text-sm text-gray-700">{{ $address->full_address }}</p>
                                        </div>
                                        <div class="ml-4">
                                            <div class="w-5 h-5 border-2 border-gray-300 rounded-full peer-checked:border-indigo-600 peer-checked:bg-indigo-600 flex items-center justify-center">
                                                <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="currentColor" viewBox="0 0 12 12">
                                                    <path d="M10 3L4.5 8.5 2 6"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @error('shipping_address_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        @else
                            <div class="text-center py-8 bg-gray-50 rounded-xl">
                                <p class="text-gray-600 mb-4">คุณยังไม่มีที่อยู่จัดส่ง</p>
                                <a href="{{ route('shipping-addresses.create') }}"
                                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                                    + เพิ่มที่อยู่จัดส่ง
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Payment Method Section -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">💰 วิธีการชำระเงิน</h2>

                        <div class="space-y-3">
                            <!-- PromptPay -->
                            <div class="relative">
                                <input type="radio"
                                       name="payment_method"
                                       id="payment-promptpay"
                                       value="promptpay"
                                       class="peer sr-only"
                                       required>
                                <label for="payment-promptpay"
                                       class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition">
                                    <div class="text-3xl mr-4">📱</div>
                                    <div class="flex-1">
                                        <div class="font-bold text-gray-900">PromptPay</div>
                                        <p class="text-sm text-gray-600">สแกน QR Code เพื่อชำระเงิน</p>
                                    </div>
                                    <div class="w-5 h-5 border-2 border-gray-300 rounded-full peer-checked:border-indigo-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>

                            <!-- Bank Transfer -->
                            <div class="relative">
                                <input type="radio"
                                       name="payment_method"
                                       id="payment-bank"
                                       value="bank_transfer"
                                       class="peer sr-only">
                                <label for="payment-bank"
                                       class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition">
                                    <div class="text-3xl mr-4">🏦</div>
                                    <div class="flex-1">
                                        <div class="font-bold text-gray-900">โอนเงินผ่านธนาคาร</div>
                                        <p class="text-sm text-gray-600">โอนเงินเข้าบัญชีธนาคาร</p>
                                    </div>
                                    <div class="w-5 h-5 border-2 border-gray-300 rounded-full peer-checked:border-indigo-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>

                            <!-- Credit Card -->
                            <div class="relative">
                                <input type="radio"
                                       name="payment_method"
                                       id="payment-credit"
                                       value="credit_card"
                                       class="peer sr-only">
                                <label for="payment-credit"
                                       class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition">
                                    <div class="text-3xl mr-4">💳</div>
                                    <div class="flex-1">
                                        <div class="font-bold text-gray-900">บัตรเครดิต/เดบิต</div>
                                        <p class="text-sm text-gray-600">ชำระด้วยบัตรเครดิตหรือเดบิต</p>
                                    </div>
                                    <div class="w-5 h-5 border-2 border-gray-300 rounded-full peer-checked:border-indigo-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>

                            <!-- Cash on Delivery -->
                            <div class="relative">
                                <input type="radio"
                                       name="payment_method"
                                       id="payment-cod"
                                       value="cod"
                                       class="peer sr-only">
                                <label for="payment-cod"
                                       class="flex items-center p-4 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition">
                                    <div class="text-3xl mr-4">💵</div>
                                    <div class="flex-1">
                                        <div class="font-bold text-gray-900">เก็บเงินปลายทาง (COD)</div>
                                        <p class="text-sm text-gray-600">ชำระเงินเมื่อได้รับสินค้า</p>
                                    </div>
                                    <div class="w-5 h-5 border-2 border-gray-300 rounded-full peer-checked:border-indigo-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>
                        @error('payment_method')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Customer Notes -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">📝 หมายเหตุ (ถ้ามี)</h2>
                        <textarea name="customer_notes"
                                  rows="4"
                                  class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-600 focus:outline-none transition"
                                  placeholder="ระบุข้อความถึงผู้ขาย เช่น เวลาที่สะดวกรับสินค้า หรือคำขอพิเศษ..."></textarea>
                        @error('customer_notes')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Right Column - Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg p-6 sticky top-4">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">สรุปคำสั่งซื้อ</h2>

                        <!-- Cart Items Preview -->
                        <div class="mb-6 max-h-64 overflow-y-auto space-y-3">
                            @foreach($cartItems as $item)
                            @php
                                $product = $item->product;
                            @endphp
                            <div class="flex gap-3 pb-3 border-b border-gray-200">
                                <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                    @if($product->main_image_url)
                                        <img src="{{ $product->main_image_url }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-gray-400 text-2xl">
                                            📦
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 line-clamp-2">
                                        {{ $product->name }}
                                    </p>
                                    <div class="flex justify-between items-center mt-1">
                                        <span class="text-xs text-gray-500">x{{ $item->quantity }}</span>
                                        <span class="text-sm font-bold text-indigo-600">
                                            ฿{{ number_format($product->price * $item->quantity, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Totals -->
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-gray-700">
                                <span>ยอดรวมสินค้า</span>
                                <span class="font-semibold">฿{{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-700">
                                <span>ค่าจัดส่ง</span>
                                <span class="font-semibold">
                                    @if($shippingFee == 0)
                                        <span class="text-green-600">ฟรี</span>
                                    @else
                                        ฿{{ number_format($shippingFee, 2) }}
                                    @endif
                                </span>
                            </div>

                            @if(isset($cashbackPreview) && $cashbackPreview['total_cashback'] > 0)
                            <div class="flex justify-between text-green-600 bg-green-50 -mx-6 px-6 py-2">
                                <div class="flex items-center">
                                    <span class="mr-1">💰</span>
                                    <span class="font-semibold">Cashback ที่จะได้รับ</span>
                                </div>
                                <span class="font-bold">฿{{ number_format($cashbackPreview['total_cashback'], 2) }}</span>
                            </div>
                            @endif
                        </div>

                        <!-- Cashback Details (if available) -->
                        @if(isset($cashbackPreview) && $cashbackPreview['total_cashback'] > 0)
                        <div class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl">
                            <div class="flex items-start mb-3">
                                <span class="text-2xl mr-2">🎁</span>
                                <div class="flex-1">
                                    <h3 class="font-bold text-green-800 mb-1">รายละเอียด Cashback</h3>
                                    <p class="text-xs text-green-700">เงินคืนจะถูกเพิ่มในกระเป๋าเงินทันทีหลังชำระเงิน</p>
                                </div>
                            </div>

                            @if(isset($cashbackPreview['breakdown']) && count($cashbackPreview['breakdown']) > 0)
                            <div class="space-y-2">
                                @foreach($cashbackPreview['breakdown'] as $item)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-700">
                                        @if($item['type'] === 'global')
                                            🌐 {{ $item['product_name'] ?? 'Global Cashback' }}
                                        @else
                                            📦 {{ $item['product_name'] }}
                                        @endif
                                    </span>
                                    <span class="font-semibold text-green-600">฿{{ number_format($item['cashback'], 2) }}</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif

                        <!-- PV (Point Value) ที่จะได้รับ -->
                        @if(isset($pvPreview) && $pvPreview['total_pv'] > 0)
                        <div class="mb-6 p-4 bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-xl">
                            <div class="flex items-start mb-3">
                                <span class="text-2xl mr-2">⭐</span>
                                <div class="flex-1">
                                    <h3 class="font-bold text-purple-800 mb-1">PV ที่จะได้รับ</h3>
                                    <p class="text-xs text-purple-700">คะแนน PV สำหรับระบบ MLM (ใช้สำหรับคำนวณค่าคอมมิชชั่น)</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-black text-purple-600">{{ number_format($pvPreview['total_pv'], 0) }}</p>
                                    <p class="text-xs text-purple-700">PV</p>
                                </div>
                            </div>

                            @if(isset($pvPreview['breakdown']) && count($pvPreview['breakdown']) > 0)
                            <div class="space-y-2">
                                @foreach($pvPreview['breakdown'] as $item)
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-700">
                                        📦 {{ \Illuminate\Support\Str::limit($item['product_name'], 30) }}
                                        @if($item['quantity'] > 1)
                                            <span class="text-purple-600 font-semibold"> x{{ $item['quantity'] }}</span>
                                        @endif
                                    </span>
                                    <span class="font-semibold text-purple-600">{{ number_format($item['total_pv'], 0) }} PV</span>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            <div class="mt-3 pt-3 border-t border-purple-200">
                                <p class="text-xs text-purple-700">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    PV จะถูกบันทึกในบัญชีของคุณทันทีหลังชำระเงิน และจะถูกใช้สำหรับคำนวณค่าคอมมิชชั่น MLM ให้กับสายงานของคุณ
                                </p>
                            </div>
                        </div>
                        @endif

                        <!-- Grand Total -->
                        <div class="pt-4 border-t-2 border-gray-200 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900">ยอดรวมทั้งหมด</span>
                                <span class="text-3xl font-black text-indigo-600">
                                    ฿{{ number_format($total, 2) }}
                                </span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                                class="w-full px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-center font-bold text-lg rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                            ⚡ ยืนยันคำสั่งซื้อ
                        </button>

                        <!-- Back to Cart -->
                        <a href="{{ route('cart.index') }}"
                           class="block w-full text-center px-6 py-3 mt-3 border-2 border-gray-300 hover:border-indigo-600 text-gray-700 hover:text-indigo-600 font-semibold rounded-xl transition">
                            ← กลับไปที่ตะกร้า
                        </a>

                        <!-- Security Notice -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-start gap-2 text-sm text-gray-600">
                                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"/>
                                </svg>
                                <p>การชำระเงินของคุณได้รับการปกป้องด้วยระบบรักษาความปลอดภัยระดับสูง</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom radio button styling */
input[type="radio"]:checked + label .w-5 {
    border-color: rgb(79 70 229);
    background-color: rgb(79 70 229);
}

input[type="radio"]:checked + label .w-5 svg {
    display: block;
}
</style>

<script>
// Form validation before submit
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const shippingAddress = document.querySelector('input[name="shipping_address_id"]:checked');
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');

    if (!shippingAddress) {
        e.preventDefault();
        alert('กรุณาเลือกที่อยู่จัดส่ง');
        return false;
    }

    if (!paymentMethod) {
        e.preventDefault();
        alert('กรุณาเลือกวิธีการชำระเงิน');
        return false;
    }
});
</script>
@endsection
