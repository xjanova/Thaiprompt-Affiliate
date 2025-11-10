@extends('layouts.seller')

@section('title', 'กระเป๋าเงิน')

@section('content')
<div class="space-y-6 pb-20 lg:pb-6">
    <!-- Wallet Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">💳 กระเป๋าเงินของฉัน</h1>
                    <p class="text-indigo-100">จัดการการเงินของร้านค้า</p>
                </div>
            </div>

            <!-- Balance Display -->
            <div class="bg-white bg-opacity-20 rounded-xl p-6 backdrop-blur-sm">
                <p class="text-indigo-100 text-sm mb-2">ยอดเงินคงเหลือ</p>
                <p class="text-5xl md:text-6xl font-bold">฿{{ number_format($balance, 2) }}</p>
                <p class="text-indigo-100 text-sm mt-2">THB</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <a href="{{ route('seller.wallet.withdraw') }}" class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
            <div class="text-4xl mb-3">💸</div>
            <p class="text-gray-800 font-semibold">ถอนเงิน</p>
            <p class="text-xs text-gray-500 mt-1">Withdraw</p>
        </a>

        <a href="{{ route('seller.reports.sales') }}" class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
            <div class="text-4xl mb-3">📊</div>
            <p class="text-gray-800 font-semibold">รายงาน</p>
            <p class="text-xs text-gray-500 mt-1">Reports</p>
        </a>

        <a href="{{ route('seller.dashboard') }}" class="bg-white rounded-xl shadow-md hover:shadow-xl p-6 text-center transition transform hover:scale-105">
            <div class="text-4xl mb-3">🏠</div>
            <p class="text-gray-800 font-semibold">แดชบอร์ด</p>
            <p class="text-xs text-gray-500 mt-1">Dashboard</p>
        </a>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <span>📝</span>
            <span>ธุรกรรมล่าสุด</span>
        </h2>

        @if(empty($transactions) || count($transactions) === 0)
            <div class="text-center py-12">
                <span class="text-6xl block mb-4">💼</span>
                <p class="text-gray-500 text-lg font-medium mb-2">ยังไม่มีธุรกรรม</p>
                <p class="text-gray-400 text-sm">ธุรกรรมทั้งหมดจะแสดงที่นี่</p>
            </div>
        @else
            <div class="divide-y divide-gray-200">
                @foreach($transactions as $transaction)
                    <div class="py-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xl">
                                {{ $transaction->type === 'income' ? '📥' : '📤' }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $transaction->description }}</p>
                                <p class="text-sm text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold {{ $transaction->type === 'income' ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->type === 'income' ? '+' : '-' }}฿{{ number_format($transaction->amount, 2) }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $transaction->status }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
