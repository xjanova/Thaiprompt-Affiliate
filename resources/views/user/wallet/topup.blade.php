@extends('layouts.user')

@section('title', 'เติมเงิน Wallet')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('user.wallet.index') }}" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">💰 เติมเงิน Wallet</h1>
            </div>
            <p class="text-purple-100">เลือกแพ็คเกจเติมเงินและชำระเงินผ่านระบบ Checkout</p>

            <!-- Current Balance -->
            <div class="mt-6 bg-white bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-purple-100 text-sm mb-1">ยอดเงินปัจจุบัน</p>
                <p class="text-3xl font-bold">฿{{ number_format($wallet->balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- คำอธิบาย -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-700 rounded-2xl p-6">
        <div class="flex items-start gap-4">
            <div class="text-3xl">ℹ️</div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">วิธีการเติมเงิน</h3>
                <ol class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-decimal list-inside">
                    <li>เลือกแพ็คเกจเติมเงินที่คุณต้องการ</li>
                    <li>ระบบจะเพิ่มแพ็คเกจเข้าตะกร้าสินค้า</li>
                    <li>ไปที่หน้า Checkout เพื่อชำระเงิน</li>
                    <li>เลือกช่องทางการชำระเงิน (PromptPay, โอนธนาคาร, บัตรเครดิต, ฯลฯ)</li>
                    <li>หลังชำระเงินสำเร็จ ยอดเงินจะเข้า Wallet ทันที</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Topup Packages -->
    <div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">เลือกแพ็คเกจเติมเงิน</h2>

        @if($topupPackages->isEmpty())
            <div class="text-center py-12">
                <div class="text-6xl mb-4">📦</div>
                <p class="text-gray-600 dark:text-gray-400">ไม่มีแพ็คเกจเติมเงินในขณะนี้</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($topupPackages as $package)
                    <div class="group border-2 border-gray-300 dark:border-gray-600 hover:border-purple-500 dark:hover:border-purple-400 bg-gradient-to-br from-white to-gray-50 dark:from-gray-700 dark:to-gray-800 rounded-xl p-6 cursor-pointer transition hover:shadow-2xl hover:scale-105">
                        <!-- Icon -->
                        <div class="text-center mb-4">
                            <div class="text-5xl mb-2">
                                @if($package->price >= 10000)
                                    👑
                                @elseif($package->price >= 1000)
                                    💎
                                @else
                                    🪙
                                @endif
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition">
                                {{ $package->name }}
                            </h3>
                        </div>

                        <!-- Price -->
                        <div class="text-center mb-4">
                            <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                                ฿{{ number_format($package->price, 0) }}
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                {{ $package->short_description }}
                            </p>
                        </div>

                        <!-- Button -->
                        <form action="{{ route('cart.add') }}" method="POST" class="w-full">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $package->id }}">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="redirect_to_checkout" value="true">

                            <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-lg transition transform hover:scale-105 shadow-lg">
                                🛒 เลือกแพ็คเกจนี้
                            </button>
                        </form>

                        <!-- Features (optional) -->
                        @if($package->description)
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                <p class="text-xs text-gray-600 dark:text-gray-400 text-center">
                                    {{ Str::limit($package->description, 100) }}
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Additional Info -->
    <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-200 dark:border-green-700 rounded-2xl p-6">
        <div class="flex items-start gap-4">
            <div class="text-3xl">✅</div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-2">ข้อดีของการเติมเงิน</h3>
                <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1 list-disc list-inside">
                    <li>💰 เติมเงินเข้า Wallet เพื่อใช้จ่ายในระบบ</li>
                    <li>🎮 ใช้สำหรับบันทึกคะแนนเกม (Snake.io, ฯลฯ)</li>
                    <li>🛍️ ซื้อสินค้าและบริการต่างๆ</li>
                    <li>⚡ รับเงินเข้า Wallet ทันทีหลังชำระเงินสำเร็จ</li>
                    <li>🔒 ระบบปลอดภัยและรองรับหลายช่องทางการชำระเงิน</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Wallet Topup] Page loaded');

    // เพิ่ม confirmation เมื่อเลือกแพ็คเกจ (optional)
    const forms = document.querySelectorAll('form[action="{{ route('cart.add') }}"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const productName = this.closest('.group').querySelector('h3').textContent.trim();
            const price = this.closest('.group').querySelector('.text-3xl').textContent.trim();

            console.log(`[Topup] Selected package: ${productName} - ${price}`);
        });
    });
});
</script>
@endpush
@endsection
