@extends('layouts.seller')

@section('title', 'ถอนเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-600 via-rose-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <a href="{{ route('seller.wallet.index') }}" class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg transition">
                    ← กลับ
                </a>
                <h1 class="text-3xl md:text-4xl font-bold">💸 ถอนเงิน</h1>
            </div>
            <p class="text-red-100">ถอนเงินจากกระเป๋าของร้านค้า</p>

            <!-- Current Balance -->
            <div class="mt-6 bg-white bg-opacity-20 rounded-xl p-4 backdrop-blur-sm">
                <p class="text-red-100 text-sm mb-1">ยอดเงินที่สามารถถอนได้</p>
                <p class="text-3xl font-bold">฿{{ number_format($balance, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Withdrawal Notice -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex gap-3">
            <span class="text-2xl">ℹ️</span>
            <div>
                <h3 class="font-bold text-blue-900 mb-2">การถอนเงินสำหรับร้านค้า</h3>
                <p class="text-blue-800 text-sm">
                    ฟีเจอร์การถอนเงินสำหรับร้านค้ากำลังอยู่ในขั้นตอนการพัฒนา
                    กรุณาติดต่อฝ่ายสนับสนุนเพื่อทำการถอนเงิน
                </p>
            </div>
        </div>
    </div>

    <!-- Contact Support -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6">ติดต่อฝ่ายสนับสนุน</h2>

        <div class="space-y-4">
            <a href="mailto:support@thaiprompt.online" class="flex items-center gap-4 p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl hover:shadow-md transition">
                <div class="w-12 h-12 bg-indigo-600 rounded-full flex items-center justify-center text-white text-xl">
                    📧
                </div>
                <div>
                    <p class="font-semibold text-gray-900">อีเมล</p>
                    <p class="text-sm text-gray-600">support@thaiprompt.online</p>
                </div>
            </a>

            <a href="{{ route('user.tickets.index') }}" class="flex items-center gap-4 p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl hover:shadow-md transition">
                <div class="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center text-white text-xl">
                    🎫
                </div>
                <div>
                    <p class="font-semibold text-gray-900">ส่งตั๋วสนับสนุน</p>
                    <p class="text-sm text-gray-600">เปิดตั๋วสนับสนุนเพื่อขอถอนเงิน</p>
                </div>
            </a>
        </div>
    </div>
</div>
@endsection
