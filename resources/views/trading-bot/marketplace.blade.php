@extends('layouts.app')

@section('title', 'Trading Bot Marketplace')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Hero Section -->
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            🤖 ระบบบอทเทรดคริปโตอัตโนมัติ
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-300">
            เทรดอัตโนมัติด้วย AI ขั้นสูง รองรับ Arbitrage และ Multi-Exchange
        </p>
    </div>

    <!-- Features Section -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg text-center">
            <div class="text-4xl mb-3">🌐</div>
            <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-white">Multi-Exchange</h3>
            <p class="text-gray-600 dark:text-gray-300 text-sm">รองรับ 5+ exchanges</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg text-center">
            <div class="text-4xl mb-3">🤖</div>
            <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-white">AI Trading</h3>
            <p class="text-gray-600 dark:text-gray-300 text-sm">LSTM, Transformer, RF</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg text-center">
            <div class="text-4xl mb-3">💹</div>
            <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-white">Arbitrage</h3>
            <p class="text-gray-600 dark:text-gray-300 text-sm">หาส่วนต่างราคาอัตโนมัติ</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg text-center">
            <div class="text-4xl mb-3">📊</div>
            <h3 class="font-bold text-lg mb-2 text-gray-900 dark:text-white">Analytics</h3>
            <p class="text-gray-600 dark:text-gray-300 text-sm">วิเคราะห์แบบเรียลไทม์</p>
        </div>
    </div>

    <!-- Packages Section -->
    <h2 class="text-3xl font-bold text-center mb-8 text-gray-900 dark:text-white">เลือกแพคเกจของคุณ</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
        @foreach($packages as $package)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl overflow-hidden transform hover:scale-105 transition-transform duration-300 {{ $package->is_featured ? 'ring-4 ring-blue-500' : '' }}">
            @if($package->is_featured)
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white text-center py-2 font-bold">
                ⭐ แนะนำ
            </div>
            @endif

            <div class="p-8">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $package->name }}</h3>
                <div class="mb-4">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white">฿{{ number_format($package->price, 0) }}</span>
                    <span class="text-gray-600 dark:text-gray-400">/{{ $package->billing_cycle === 'monthly' ? 'เดือน' : 'ปี' }}</span>
                </div>
                <p class="text-gray-600 dark:text-gray-300 mb-6">{{ $package->description }}</p>

                <ul class="space-y-3 mb-8">
                    <li class="flex items-center text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        {{ $package->max_bots == 999 ? 'ไม่จำกัด' : $package->max_bots }} บอท
                    </li>
                    <li class="flex items-center text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        {{ $package->max_exchanges }} Exchanges
                    </li>
                    <li class="flex items-center text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        {{ $package->max_strategies == 999 ? 'ไม่จำกัด' : $package->max_strategies }} กลยุทธ์
                    </li>
                    @if($package->ai_signals)
                    <li class="flex items-center text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        AI Signals
                    </li>
                    @endif
                    @if($package->advanced_analytics)
                    <li class="flex items-center text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Advanced Analytics
                    </li>
                    @endif
                    @if($package->copy_trading)
                    <li class="flex items-center text-gray-700 dark:text-gray-300">
                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Arbitrage Trading
                    </li>
                    @endif
                </ul>

                @auth
                    <form action="{{ route('trading-bot.subscribe', $package) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold py-3 px-6 rounded-lg hover:from-blue-600 hover:to-purple-700 transition-all duration-300">
                            เลือกแพคเกจนี้
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block w-full text-center bg-gray-500 text-white font-bold py-3 px-6 rounded-lg hover:bg-gray-600 transition-colors">
                        เข้าสู่ระบบเพื่อสมัคร
                    </a>
                @endauth
            </div>
        </div>
        @endforeach
    </div>

    <!-- Public Strategies Section -->
    <div class="mt-16">
        <h2 class="text-3xl font-bold text-center mb-8 text-gray-900 dark:text-white">กลยุทธ์ยอดนิยม</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($strategies->take(6) as $strategy)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-lg">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $strategy->name }}</h3>
                    @if($strategy->is_for_sale)
                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">฿{{ number_format($strategy->price, 0) }}</span>
                    @else
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">ฟรี</span>
                    @endif
                </div>

                <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">{{ Str::limit($strategy->description, 100) }}</p>

                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500 dark:text-gray-400">โดย {{ $strategy->user->name }}</span>
                    <span class="text-blue-500">{{ ucfirst($strategy->strategy_type) }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Supported Exchanges -->
    <div class="mt-16 bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-800 dark:to-gray-700 rounded-xl p-8">
        <h2 class="text-3xl font-bold text-center mb-8 text-gray-900 dark:text-white">Exchanges ที่รองรับ</h2>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-md">
                <div class="text-2xl font-bold text-yellow-500 mb-2">Bitkub</div>
                <p class="text-xs text-gray-600 dark:text-gray-400">Thailand 🇹🇭</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-md">
                <div class="text-2xl font-bold text-yellow-600 mb-2">Binance</div>
                <p class="text-xs text-gray-600 dark:text-gray-400">Global</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-md">
                <div class="text-2xl font-bold text-green-500 mb-2">KuCoin</div>
                <p class="text-xs text-gray-600 dark:text-gray-400">Global</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-md">
                <div class="text-2xl font-bold text-orange-500 mb-2">Bybit</div>
                <p class="text-xs text-gray-600 dark:text-gray-400">Derivatives</p>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center shadow-md">
                <div class="text-2xl font-bold text-blue-600 mb-2">MT4</div>
                <p class="text-xs text-gray-600 dark:text-gray-400">Forex</p>
            </div>
        </div>
    </div>
</div>
@endsection
