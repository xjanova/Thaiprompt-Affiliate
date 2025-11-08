@extends('layouts.app')

@section('title', 'ชำระเงิน')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-purple-800 py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                💳 ชำระเงิน
            </h1>
            <p class="text-purple-200 text-lg">กรุณาเลือกวิธีการชำระเงิน</p>
        </div>

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('tarot.cart.processCheckout') }}" method="POST">
            @csrf

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Payment Method Selection -->
                <div class="lg:col-span-2">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 mb-6">
                        <h2 class="text-2xl font-bold text-white mb-6">เลือกช่องทางชำระเงิน</h2>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <!-- Wallet -->
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="wallet" required class="peer sr-only">
                                <div class="p-6 bg-white/5 border-2 border-white/20 rounded-lg peer-checked:border-yellow-400 peer-checked:bg-white/10 hover:bg-white/10 transition-all">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-3xl">💰</span>
                                        <span class="text-lg font-bold text-white">กระเป๋าเงิน</span>
                                    </div>
                                    <p class="text-purple-200 text-sm">ใช้เงินในกระเป๋าของคุณ</p>
                                    @auth
                                    <p class="text-yellow-300 text-sm mt-2">คงเหลือ: ฿{{ number_format(auth()->user()->wallet_balance ?? 0, 0) }}</p>
                                    @endauth
                                </div>
                            </label>

                            <!-- PromptPay -->
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="promptpay" required class="peer sr-only">
                                <div class="p-6 bg-white/5 border-2 border-white/20 rounded-lg peer-checked:border-yellow-400 peer-checked:bg-white/10 hover:bg-white/10 transition-all">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-3xl">📱</span>
                                        <span class="text-lg font-bold text-white">พร้อมเพย์</span>
                                    </div>
                                    <p class="text-purple-200 text-sm">สแกน QR Code ชำระเงิน</p>
                                </div>
                            </label>

                            <!-- Credit Card -->
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="credit_card" required class="peer sr-only">
                                <div class="p-6 bg-white/5 border-2 border-white/20 rounded-lg peer-checked:border-yellow-400 peer-checked:bg-white/10 hover:bg-white/10 transition-all">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-3xl">💳</span>
                                        <span class="text-lg font-bold text-white">บัตรเครดิต</span>
                                    </div>
                                    <p class="text-purple-200 text-sm">Visa, Mastercard, JCB</p>
                                </div>
                            </label>

                            <!-- Bank Transfer -->
                            <label class="relative cursor-pointer">
                                <input type="radio" name="payment_method" value="bank_transfer" required class="peer sr-only">
                                <div class="p-6 bg-white/5 border-2 border-white/20 rounded-lg peer-checked:border-yellow-400 peer-checked:bg-white/10 hover:bg-white/10 transition-all">
                                    <div class="flex items-center gap-3 mb-2">
                                        <span class="text-3xl">🏦</span>
                                        <span class="text-lg font-bold text-white">โอนเงิน</span>
                                    </div>
                                    <p class="text-purple-200 text-sm">โอนผ่านธนาคาร</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
                        <h2 class="text-2xl font-bold text-white mb-4">รายการสั่งซื้อ</h2>

                        <div class="space-y-4">
                            @foreach($cartItems as $item)
                            <div class="flex justify-between items-start pb-4 border-b border-white/10">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span>{{ $item->category->icon ?? '🔮' }}</span>
                                        <span class="text-white font-semibold">{{ $item->category->name_th }}</span>
                                    </div>
                                    <p class="text-purple-200 text-sm">{{ $item->spreadType->name_th }} ({{ $item->spreadType->card_count }} ใบ)</p>
                                </div>
                                <div class="text-right ml-4">
                                    <span class="text-yellow-300 font-bold">
                                        @if($item->price > 0)
                                            ฿{{ number_format($item->price, 0) }}
                                        @else
                                            ฟรี
                                        @endif
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 sticky top-6">
                        <h3 class="text-xl font-bold text-white mb-4">สรุปคำสั่งซื้อ</h3>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-purple-200">
                                <span>รายการ:</span>
                                <span>{{ $cartItems->count() }} รายการ</span>
                            </div>
                            <div class="flex justify-between text-purple-200">
                                <span>ยอดรวม:</span>
                                <span class="font-semibold">฿{{ number_format($cartTotal, 0) }}</span>
                            </div>
                            <div class="border-t border-white/20 pt-3"></div>
                            <div class="flex justify-between text-white text-2xl font-bold">
                                <span>ยอดชำระ:</span>
                                <span class="text-yellow-300">
                                    @if($cartTotal > 0)
                                        ฿{{ number_format($cartTotal, 0) }}
                                    @else
                                        ฟรี
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Confirm Button -->
                        <button type="submit" class="block w-full text-center px-6 py-4 bg-gradient-to-r from-green-400 to-green-600 text-white text-lg font-bold rounded-lg shadow-xl hover:scale-105 transition-all mb-3">
                            ✓ ยืนยันการชำระเงิน
                        </button>

                        <!-- Back to Cart -->
                        <a href="{{ route('tarot.cart.index') }}" class="block w-full text-center px-6 py-3 bg-white/10 text-white font-semibold rounded-lg border border-white/30 hover:bg-white/20 transition-all">
                            ← กลับไปตะกร้า
                        </a>

                        <!-- Security Notice -->
                        <div class="mt-6 p-4 bg-white/5 rounded-lg border border-white/10">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <p class="text-white text-sm font-semibold mb-1">การชำระเงินปลอดภัย</p>
                                    <p class="text-purple-200 text-xs">ข้อมูลของคุณได้รับการเข้ารหัส SSL 256-bit</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
