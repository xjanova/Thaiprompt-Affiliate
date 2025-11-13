@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
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
                    <a href="{{ route('orders.show', $order->id) }}" class="text-gray-500 hover:text-indigo-600 transition">
                        คำสั่งซื้อ #{{ $order->order_number }}
                    </a>
                </li>
                <li class="text-gray-400">/</li>
                <li class="text-gray-700 dark:text-gray-300 font-medium">ชำระเงิน</li>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-2">💳 ชำระเงิน</h1>
            <p class="text-gray-600 dark:text-gray-400">คำสั่งซื้อ #{{ $order->order_number }} - ยอดชำระ ฿{{ number_format($order->total_amount, 2) }}</p>
        </div>

        @if(session('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif

        @if(session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ session('success') }}</p>
                </div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 gap-8">
            <!-- Payment Method -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">วิธีการชำระเงิน</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $order->payment_method }}</p>
                </div>

                <form method="POST" action="{{ route('checkout.payment.process', $order->id) }}" class="p-6">
                    @csrf

                    <!-- Wallet Payment -->
                    @if($order->payment_method === 'wallet')
                    <div class="space-y-4">
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <span class="text-3xl mr-3">👛</span>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">ชำระด้วย Wallet</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">ยอดเงินคงเหลือ: <span class="font-semibold text-indigo-600">฿{{ number_format($walletBalance, 2) }}</span></p>
                                    </div>
                                </div>
                            </div>

                            @if($walletBalance < $order->total_amount)
                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 mb-3">
                                <p class="text-sm text-red-800 dark:text-red-300">⚠️ ยอดเงินในกระเป๋าไม่เพียงพอ กรุณาเติมเงินก่อนชำระเงิน</p>
                            </div>
                            @endif

                            <div class="bg-white dark:bg-gray-900 rounded-lg p-4 mb-3">
                                <div class="flex justify-between mb-2">
                                    <span class="text-gray-600 dark:text-gray-400">ยอดที่ต้องชำระ:</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($order->total_amount, 2) }}</span>
                                </div>
                                <div class="flex justify-between mb-2">
                                    <span class="text-gray-600 dark:text-gray-400">คงเหลือหลังชำระ:</span>
                                    <span class="font-semibold {{ $walletBalance - $order->total_amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ฿{{ number_format($walletBalance - $order->total_amount, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <button type="submit"
                                @if($walletBalance < $order->total_amount) disabled @endif
                                class="w-full py-3 px-4 rounded-lg font-semibold text-white transition
                                    {{ $walletBalance >= $order->total_amount
                                        ? 'bg-indigo-600 hover:bg-indigo-700'
                                        : 'bg-gray-400 cursor-not-allowed' }}">
                            {{ $walletBalance >= $order->total_amount ? 'ยืนยันการชำระเงิน' : 'ยอดเงินไม่เพียงพอ' }}
                        </button>

                        @if($walletBalance < $order->total_amount)
                        <a href="{{ route('wallet.topup') }}" class="block w-full py-3 px-4 rounded-lg font-semibold text-center text-white bg-green-600 hover:bg-green-700 transition">
                            เติมเงิน Wallet
                        </a>
                        @endif
                    </div>

                    <!-- PromptPay Payment -->
                    @elseif($order->payment_method === 'promptpay')
                    <div class="space-y-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <span class="text-3xl mr-3">📱</span>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">ชำระด้วย PromptPay</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">สแกน QR Code เพื่อชำระเงิน</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-900 rounded-lg p-4 mb-3 text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">กดปุ่มด้านล่างเพื่อสร้าง QR Code</p>
                                <p class="text-2xl font-bold text-indigo-600">฿{{ number_format($order->total_amount, 2) }}</p>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">
                            สร้าง QR Code PromptPay
                        </button>
                    </div>

                    <!-- Credit Card Payment -->
                    @elseif($order->payment_method === 'credit_card')
                    <div class="space-y-4">
                        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <span class="text-3xl mr-3">💳</span>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">ชำระด้วยบัตรเครดิต/เดบิต</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Visa, Mastercard, JCB</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-white bg-purple-600 hover:bg-purple-700 transition">
                            ไปยังหน้าชำระเงิน
                        </button>
                    </div>

                    <!-- Bank Transfer Payment -->
                    @elseif($order->payment_method === 'bank_transfer')
                    <div class="space-y-4">
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <span class="text-3xl mr-3">🏦</span>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">โอนเงินผ่านธนาคาร</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">โอนเงินไปยังบัญชีธนาคารของเรา</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-white bg-green-600 hover:bg-green-700 transition">
                            ดูรายละเอียดบัญชี
                        </button>
                    </div>

                    <!-- Cash on Delivery -->
                    @elseif($order->payment_method === 'cash_on_delivery')
                    <div class="space-y-4">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <span class="text-3xl mr-3">💵</span>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">เก็บเงินปลายทาง</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">ชำระเงินเมื่อได้รับสินค้า</p>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-900 rounded-lg p-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    คำสั่งซื้อของคุณจะถูกจัดส่ง คุณสามารถชำระเงินเมื่อได้รับสินค้าแล้ว
                                </p>
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-gray-900 dark:text-white">ยอดที่ต้องชำระปลายทาง:</span>
                                    <span class="text-2xl font-bold text-yellow-600">฿{{ number_format($order->total_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-white bg-yellow-600 hover:bg-yellow-700 transition">
                            ยืนยันคำสั่งซื้อ
                        </button>
                    </div>

                    <!-- PaySolutions -->
                    @elseif($order->payment_method === 'paysolutions')
                    <div class="space-y-4">
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <span class="text-3xl mr-3">💳</span>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">PaySolutions</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">QR, Card, E-Wallet, Installment</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 px-4 rounded-lg font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition">
                            ชำระเงินผ่าน PaySolutions
                        </button>
                    </div>
                    @endif

                    <!-- Cancel Link -->
                    <div class="mt-4 text-center">
                        <a href="{{ route('orders.show', $order->id) }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            ยกเลิกและกลับไปดูคำสั่งซื้อ
                        </a>
                    </div>
                </form>
            </div>

            <!-- Order Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">สรุปคำสั่งซื้อ</h2>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Items -->
                    <div class="space-y-3">
                        @foreach($order->items as $item)
                        <div class="flex items-center gap-4">
                            @if($item->product_image)
                            <img src="{{ Storage::url($item->product_image) }}"
                                 alt="{{ $item->product_name }}"
                                 class="w-16 h-16 rounded-lg object-cover">
                            @else
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                <span class="text-gray-400">📦</span>
                            </div>
                            @endif
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $item->product_name }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">จำนวน: {{ $item->quantity }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($item->total, 2) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Totals -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">ยอดรวมสินค้า:</span>
                            <span class="font-medium text-gray-900 dark:text-white">฿{{ number_format($order->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">ค่าจัดส่ง:</span>
                            <span class="font-medium text-gray-900 dark:text-white">฿{{ number_format($order->shipping_fee, 2) }}</span>
                        </div>
                        @if($order->cashback_amount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-green-600 dark:text-green-400">💰 Cashback:</span>
                            <span class="font-medium text-green-600 dark:text-green-400">฿{{ number_format($order->cashback_amount, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-2">
                            <span class="text-gray-900 dark:text-white">รวมทั้งหมด:</span>
                            <span class="text-indigo-600 dark:text-indigo-400">฿{{ number_format($order->total_amount, 2) }}</span>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">ที่อยู่จัดส่ง</h4>
                        @php
                            $address = $order->shipping_address_snapshot ?? $order->shippingAddress;
                        @endphp
                        @if(is_array($address))
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $address['recipient_name'] ?? '' }}</p>
                            <p>{{ $address['phone'] ?? '' }}</p>
                            <p>{{ $address['address_line1'] ?? '' }}</p>
                            @if(isset($address['address_line2']) && $address['address_line2'])
                            <p>{{ $address['address_line2'] }}</p>
                            @endif
                            <p>{{ $address['district'] ?? '' }} {{ $address['city'] ?? '' }} {{ $address['province'] ?? '' }} {{ $address['postal_code'] ?? '' }}</p>
                        </div>
                        @else
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $address->recipient_name }}</p>
                            <p>{{ $address->phone }}</p>
                            <p>{{ $address->address_line1 }}</p>
                            @if($address->address_line2)
                            <p>{{ $address->address_line2 }}</p>
                            @endif
                            <p>{{ $address->district }} {{ $address->city }} {{ $address->province }} {{ $address->postal_code }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Transaction Info -->
                    @if($transaction)
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>Transaction ID:</span>
                            <span class="font-mono">{{ $transaction->transaction_id }}</span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>หมดอายุ:</span>
                            <span>{{ $transaction->expired_at->format('d/m/Y H:i') }} น.</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
