@extends('layouts.user-arrow-x')

@section('title', 'ฝาก ' . $currency->code)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('user.crypto-wallet.deposit') }}" class="text-amber-600 hover:text-amber-700 font-medium mb-2 inline-block">
            ← เลือกสกุลเงินอื่น
        </a>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">ฝาก {{ $currency->code }}</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $currency->name }} ({{ strtoupper($currency->network) }})</p>
    </div>

    <!-- Deposit Address Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl p-8 mb-6">
        <div class="text-center mb-6">
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">ที่อยู่กระเป๋าสำหรับฝาก</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">สแกน QR Code หรือคัดลอกที่อยู่ด้านล่าง</p>
        </div>

        <!-- QR Code -->
        <div class="flex justify-center mb-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-lg">
                @if($depositAddress && $depositAddress->qr_code)
                    <img src="{{ $depositAddress->qr_code }}" alt="QR Code" class="w-64 h-64">
                @else
                    <div class="w-64 h-64 bg-gray-200 dark:bg-gray-700 flex items-center justify-center rounded-lg">
                        <div class="text-center">
                            <div class="text-4xl mb-2">📱</div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">QR Code จะสร้างเร็วๆ นี้</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Address -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ที่อยู่กระเป๋า (Wallet Address)</label>
            <div class="flex gap-2">
                <input type="text"
                       id="depositAddress"
                       value="{{ $depositAddress->address }}"
                       readonly
                       class="flex-1 px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg font-mono text-sm text-gray-800 dark:text-gray-100">
                <button onclick="copyAddress()"
                        class="px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-lg font-medium hover:from-amber-600 hover:to-orange-700 transition-all whitespace-nowrap">
                    📋 คัดลอก
                </button>
            </div>
            <p id="copySuccess" class="text-green-600 dark:text-green-400 text-sm mt-2 hidden">✓ คัดลอกแล้ว!</p>
        </div>

        <!-- Network Info -->
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-gray-700 dark:to-gray-600 rounded-lg p-4">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600 dark:text-gray-400">เครือข่าย:</span>
                    <span class="font-bold text-gray-800 dark:text-gray-100 ml-2 uppercase">{{ $currency->network }}</span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">Token Standard:</span>
                    <span class="font-bold text-gray-800 dark:text-gray-100 ml-2">{{ $currency->token_standard }}</span>
                </div>
                @if($currency->contract_address)
                <div class="col-span-2">
                    <span class="text-gray-600 dark:text-gray-400">Contract Address:</span>
                    <div class="font-mono text-xs text-gray-800 dark:text-gray-100 mt-1 break-all">{{ $currency->contract_address }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Deposit Info -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-6">
        <h4 class="font-bold text-blue-900 dark:text-blue-300 mb-3">ข้อมูลการฝาก</h4>
        <div class="space-y-2 text-sm text-blue-800 dark:text-blue-300">
            <div class="flex justify-between">
                <span>ฝากขั้นต่ำ:</span>
                <span class="font-semibold">{{ number_format($currency->min_deposit, 8) }} {{ $currency->code }}</span>
            </div>
            <div class="flex justify-between">
                <span>การยืนยันที่ต้องการ:</span>
                <span class="font-semibold">{{ $currency->confirmations_required }} confirmations</span>
            </div>
            <div class="flex justify-between">
                <span>เวลาโดยประมาณ:</span>
                <span class="font-semibold">
                    @if($currency->network === 'bitcoin')
                        ~60 นาที
                    @elseif($currency->network === 'ethereum')
                        ~15-30 นาที
                    @elseif($currency->network === 'bsc')
                        ~3-5 นาที
                    @elseif($currency->network === 'polygon')
                        ~5-10 นาที
                    @else
                        ขึ้นอยู่กับเครือข่าย
                    @endif
                </span>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    @if($depositAddress->total_deposits > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6">
        <h4 class="font-bold text-gray-800 dark:text-gray-100 mb-3">สถิติการฝาก</h4>
        <div class="grid grid-cols-2 gap-4 text-center">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-2xl font-bold text-amber-600">{{ $depositAddress->total_deposits }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">จำนวนครั้ง</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="text-2xl font-bold text-amber-600">{{ number_format($depositAddress->total_amount_received, 8) }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">ยอดรวม {{ $currency->code }}</div>
            </div>
        </div>
    </div>
    @endif

    <!-- Important Warning -->
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6">
        <h4 class="font-bold text-red-900 dark:text-red-300 mb-3 flex items-center">
            <span class="text-xl mr-2">⚠️</span>
            คำเตือนสำคัญ!
        </h4>
        <ul class="list-disc list-inside space-y-2 text-red-800 dark:text-red-300 text-sm">
            <li><strong>ส่งเฉพาะ {{ $currency->code }}</strong> บนเครือข่าย <strong>{{ strtoupper($currency->network) }}</strong> เท่านั้น</li>
            <li>การส่งเหรียญอื่นหรือใช้เครือข่ายผิด จะทำให้<strong>สูญหายถาวร</strong></li>
            <li>ต้องส่งมากกว่า <strong>{{ number_format($currency->min_deposit, 8) }} {{ $currency->code }}</strong> ขึ้นไป</li>
            <li>เหรียญจะเข้าอัตโนมัติหลังได้รับการยืนยันครบ</li>
            @if($currency->explorer_url)
            <li>ตรวจสอบสถานะได้ที่: <a href="{{ $currency->explorer_url }}" target="_blank" class="underline">Block Explorer</a></li>
            @endif
        </ul>
    </div>
</div>

<script>
function copyAddress() {
    const addressInput = document.getElementById('depositAddress');
    addressInput.select();
    document.execCommand('copy');

    const successMsg = document.getElementById('copySuccess');
    successMsg.classList.remove('hidden');

    setTimeout(() => {
        successMsg.classList.add('hidden');
    }, 3000);
}
</script>
@endsection
