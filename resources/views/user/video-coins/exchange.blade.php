@extends('layouts.user')

@section('title', 'แลก Coins เป็นเงิน')

@section('content')
<div class="container mx-auto px-4 py-6 space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <a href="{{ route('user.video-coins.index') }}"
           class="p-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left text-gray-600 dark:text-gray-400"></i>
        </a>
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white">
                แลก Coins เป็นเงิน
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                แลก Video Coins เป็นเงินบาทเข้ากระเป๋าเงิน
            </p>
        </div>
    </div>

    {{-- Current Balance --}}
    <div class="bg-gradient-to-br from-yellow-400 via-yellow-500 to-orange-500 rounded-2xl shadow-xl p-6 text-white">
        <p class="text-yellow-100 text-sm mb-1">ยอด Coins ของคุณ</p>
        <p class="text-4xl font-black">
            {{ number_format($videoCoin->balance, 2) }}
            <span class="text-xl font-normal text-yellow-100">VC</span>
        </p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 px-4 py-3 rounded-xl">
            <i class="fas fa-exclamation-circle mr-2"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Exchange Rates --}}
    @if($exchangeRates->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-exchange-alt mr-2 text-emerald-500"></i>
                    เลือกจำนวนที่ต้องการแลก
                </h2>
            </div>

            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($exchangeRates as $rate)
                    @php
                        $canExchange = $videoCoin->balance >= $rate->coins_amount;
                    @endphp
                    <form action="{{ route('user.video-coins.exchange.submit') }}" method="POST">
                        @csrf
                        <input type="hidden" name="exchange_rate_id" value="{{ $rate->id }}">
                        <button type="submit"
                                @if(!$canExchange) disabled @endif
                                class="w-full border-2 rounded-2xl p-6 text-left transition-all
                                    {{ $canExchange
                                        ? 'border-gray-200 dark:border-gray-700 hover:border-emerald-500 dark:hover:border-emerald-400 hover:shadow-lg'
                                        : 'border-gray-100 dark:border-gray-800 opacity-50 cursor-not-allowed' }}">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-3xl font-black text-yellow-500">C</span>
                                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ number_format($rate->coins_amount, 0) }}
                                    </span>
                                </div>
                                <i class="fas fa-arrow-right text-gray-400 text-xl"></i>
                                <div class="flex items-center gap-1">
                                    <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ number_format($rate->money_amount, 0) }}
                                    </span>
                                    <span class="text-lg text-gray-500 dark:text-gray-400">฿</span>
                                </div>
                            </div>

                            <div class="text-sm text-gray-500 dark:text-gray-400 space-y-1">
                                <p>อัตรา: 1 Coin = {{ number_format($rate->rate, 4) }} บาท</p>
                                @if($rate->min_level)
                                    <p><i class="fas fa-star mr-1 text-yellow-500"></i>ต้องมี Level {{ $rate->min_level }}+</p>
                                @endif
                            </div>

                            @if($canExchange)
                                <div class="mt-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white text-center rounded-xl font-medium transition-colors">
                                    <i class="fas fa-exchange-alt mr-2"></i>
                                    แลกเลย
                                </div>
                            @else
                                <div class="mt-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-center rounded-xl font-medium">
                                    <i class="fas fa-lock mr-2"></i>
                                    Coins ไม่พอ
                                </div>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center">
            <div class="w-20 h-20 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                <i class="fas fa-exchange-alt text-gray-400 dark:text-gray-500 text-3xl"></i>
            </div>
            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีอัตราแลกเปลี่ยน</p>
        </div>
    @endif

    {{-- Exchange History --}}
    @if($exchangeHistory->count() > 0)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-history mr-2 text-blue-500"></i>
                    ประวัติการแลก
                </h2>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($exchangeHistory as $exchange)
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center
                                @switch($exchange->status)
                                    @case('completed')
                                        bg-green-100 dark:bg-green-900/30
                                        @break
                                    @case('pending')
                                        bg-yellow-100 dark:bg-yellow-900/30
                                        @break
                                    @case('rejected')
                                        bg-red-100 dark:bg-red-900/30
                                        @break
                                    @default
                                        bg-gray-100 dark:bg-gray-900/30
                                @endswitch
                            ">
                                <i class="fas
                                    @switch($exchange->status)
                                        @case('completed')
                                            fa-check text-green-600 dark:text-green-400
                                            @break
                                        @case('pending')
                                            fa-clock text-yellow-600 dark:text-yellow-400
                                            @break
                                        @case('rejected')
                                            fa-times text-red-600 dark:text-red-400
                                            @break
                                        @default
                                            fa-exchange-alt text-gray-600 dark:text-gray-400
                                    @endswitch
                                "></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ number_format($exchange->coins_amount, 0) }} Coins
                                    <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                                    {{ number_format($exchange->money_amount, 0) }} บาท
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $exchange->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                @switch($exchange->status)
                                    @case('completed')
                                        bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400
                                        @break
                                    @case('pending')
                                        bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400
                                        @break
                                    @case('rejected')
                                        bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400
                                        @break
                                    @default
                                        bg-gray-100 dark:bg-gray-900/30 text-gray-600 dark:text-gray-400
                                @endswitch
                            ">
                                @switch($exchange->status)
                                    @case('completed')
                                        สำเร็จ
                                        @break
                                    @case('pending')
                                        รอดำเนินการ
                                        @break
                                    @case('rejected')
                                        ปฏิเสธ
                                        @break
                                    @default
                                        {{ $exchange->status }}
                                @endswitch
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
