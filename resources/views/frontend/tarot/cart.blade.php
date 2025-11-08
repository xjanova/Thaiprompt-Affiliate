@extends('layouts.app')

@section('title', 'ตะกร้าคำทำนาย')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-900 via-indigo-900 to-purple-800 py-12">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">
                🛒 ตะกร้าคำทำนาย
            </h1>
            <p class="text-purple-200 text-lg">รายการคำทำนายที่คุณเลือก</p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if($cartItems->isEmpty())
            <!-- Empty Cart -->
            <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl p-12 text-center">
                <div class="text-6xl mb-4">🛒</div>
                <h3 class="text-2xl font-bold text-white mb-4">ตะกร้าของคุณว่างเปล่า</h3>
                <p class="text-purple-200 mb-6">เลือกหมวดหมู่ทำนายที่คุณสนใจแล้วเพิ่มเข้าตะกร้า</p>
                <a href="{{ route('tarot.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-yellow-400 to-pink-500 text-white font-bold rounded-lg hover:scale-105 transition-transform">
                    <span>🔮</span>
                    <span>เริ่มทำนาย</span>
                </a>
            </div>
        @else
            <!-- Cart Items -->
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Items List -->
                <div class="lg:col-span-2 space-y-4">
                    @foreach($cartItems as $item)
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-2xl">{{ $item->category->icon ?? '🔮' }}</span>
                                    <h3 class="text-xl font-bold text-white">{{ $item->category->name_th }}</h3>
                                </div>
                                <p class="text-purple-200 text-sm mb-2">
                                    <span class="font-semibold">รูปแบบ:</span> {{ $item->spreadType->name_th }}
                                    ({{ $item->spreadType->card_count }} ใบ)
                                </p>
                                <p class="text-purple-200 text-sm mb-2">
                                    <span class="font-semibold">คำถาม:</span> {{ $item->question }}
                                </p>
                            </div>
                            <div class="text-right ml-4">
                                <p class="text-2xl font-bold text-yellow-300">
                                    @if($item->price > 0)
                                        ฿{{ number_format($item->price, 0) }}
                                    @else
                                        <span class="text-green-300">ฟรี</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Remove Button -->
                        <form action="{{ route('tarot.cart.remove', $item->id) }}" method="POST" class="mt-4">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-300 text-sm flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span>ลบออก</span>
                            </button>
                        </form>
                    </div>
                    @endforeach

                    <!-- Clear Cart -->
                    <div class="flex justify-between items-center pt-4">
                        <form action="{{ route('tarot.cart.clear') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-red-400 hover:text-red-300 text-sm">
                                ล้างตะกร้าทั้งหมด
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-6 sticky top-6">
                        <h3 class="text-xl font-bold text-white mb-4">สรุปรายการ</h3>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-purple-200">
                                <span>จำนวนรายการ:</span>
                                <span class="font-semibold">{{ $cartItems->count() }} รายการ</span>
                            </div>
                            <div class="border-t border-white/20 pt-3"></div>
                            <div class="flex justify-between text-white text-xl font-bold">
                                <span>ยอดรวม:</span>
                                <span class="text-yellow-300">
                                    @if($cartTotal > 0)
                                        ฿{{ number_format($cartTotal, 0) }}
                                    @else
                                        ฟรี
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Checkout Button -->
                        <a href="{{ route('tarot.cart.checkout') }}" class="block w-full text-center px-6 py-4 bg-gradient-to-r from-yellow-400 via-pink-500 to-purple-600 text-white text-lg font-bold rounded-lg shadow-xl hover:scale-105 transition-all">
                            ดำเนินการชำระเงิน
                        </a>

                        <!-- Continue Shopping -->
                        <a href="{{ route('tarot.index') }}" class="block w-full text-center mt-3 px-6 py-3 bg-white/10 text-white font-semibold rounded-lg border border-white/30 hover:bg-white/20 transition-all">
                            เลือกรายการเพิ่ม
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
