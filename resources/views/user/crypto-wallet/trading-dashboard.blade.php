@extends('layouts.user')

@section('title', 'Crypto Trading Dashboard')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-black text-white" x-data="tradingDashboard()">

    <!-- Header with Stats -->
    <div class="bg-gradient-to-r from-amber-600 via-orange-600 to-red-600 p-8 rounded-2xl shadow-2xl mb-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
            <div>
                <h1 class="text-4xl font-bold mb-2">Premium Trading Dashboard</h1>
                <p class="text-amber-100 text-lg">Real-time cryptocurrency exchange platform</p>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20">
                    <div class="text-xs text-amber-200 mb-1">Total Balance</div>
                    <div class="text-2xl font-bold">฿{{ number_format($totalValueTHB ?? 0, 2) }}</div>
                    <div class="text-xs text-green-400 mt-1">+12.5% ↑</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20">
                    <div class="text-xs text-amber-200 mb-1">24h Volume</div>
                    <div class="text-2xl font-bold">฿1.2M</div>
                    <div class="text-xs text-blue-400 mt-1">Trading</div>
                </div>
                <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20">
                    <div class="text-xs text-amber-200 mb-1">P&L Today</div>
                    <div class="text-2xl font-bold text-green-400">+฿8,456</div>
                    <div class="text-xs text-green-400 mt-1">+8.2% ↑</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Trading Interface -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Sidebar - Market Watch -->
        <div class="lg:col-span-3">
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-2xl border border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-700 bg-gradient-to-r from-purple-900/30 to-pink-900/30">
                    <h2 class="text-xl font-bold flex items-center">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-3 animate-pulse"></span>
                        Live Markets
                    </h2>
                </div>

                <div class="p-4 space-y-2 max-h-[600px] overflow-y-auto custom-scrollbar">
                    @foreach($currencies ?? [] as $currency)
                    <div class="group cursor-pointer p-4 rounded-xl bg-gray-800/50 hover:bg-gradient-to-r hover:from-purple-900/40 hover:to-pink-900/40 transition-all duration-300 border border-transparent hover:border-purple-500/50"
                         @click="selectCurrency('{{ $currency->code }}')">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-lg font-bold">
                                    {{ substr($currency->code, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold">{{ $currency->code }}</div>
                                    <div class="text-xs text-gray-400">{{ $currency->name }}</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-green-400">฿{{ number_format(rand(1000, 50000), 2) }}</div>
                                <div class="text-xs text-green-400">+{{ number_format(rand(1, 15), 2) }}%</div>
                            </div>
                        </div>

                        <!-- Mini Chart -->
                        <div class="mt-3 h-12 opacity-0 group-hover:opacity-100 transition-opacity">
                            <canvas class="w-full h-full"></canvas>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Center - Trading Chart & Exchange -->
        <div class="lg:col-span-6 space-y-6">

            <!-- Price Chart -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-2xl border border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-700 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <h2 class="text-2xl font-bold" x-text="selectedCurrency + '/THB'">BTC/THB</h2>
                        <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-sm font-semibold">
                            +5.2%
                        </span>
                    </div>
                    <div class="flex space-x-2">
                        <button class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition">1H</button>
                        <button class="px-4 py-2 bg-purple-600 rounded-lg text-sm">24H</button>
                        <button class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition">7D</button>
                        <button class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition">1M</button>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Chart Canvas -->
                    <div class="relative h-80 bg-gray-900/50 rounded-xl overflow-hidden">
                        <canvas id="priceChart" class="w-full h-full"></canvas>

                        <!-- Chart Overlay Info -->
                        <div class="absolute top-4 left-4 bg-black/60 backdrop-blur-md rounded-lg p-4 border border-gray-700">
                            <div class="grid grid-cols-4 gap-4 text-sm">
                                <div>
                                    <div class="text-gray-400 text-xs">Open</div>
                                    <div class="font-bold text-white">฿1,234,567</div>
                                </div>
                                <div>
                                    <div class="text-gray-400 text-xs">High</div>
                                    <div class="font-bold text-green-400">฿1,245,000</div>
                                </div>
                                <div>
                                    <div class="text-gray-400 text-xs">Low</div>
                                    <div class="font-bold text-red-400">฿1,220,000</div>
                                </div>
                                <div>
                                    <div class="text-gray-400 text-xs">Volume</div>
                                    <div class="font-bold text-blue-400">45.2M</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exchange Interface -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-2xl border border-gray-700">
                <div class="p-6">
                    <!-- Tab Buttons -->
                    <div class="flex space-x-2 mb-6">
                        <button @click="exchangeMode = 'buy'"
                                :class="exchangeMode === 'buy' ? 'bg-gradient-to-r from-green-600 to-emerald-600' : 'bg-gray-700'"
                                class="flex-1 py-3 rounded-xl font-bold transition-all duration-300 hover:shadow-lg">
                            🔥 Buy Crypto
                        </button>
                        <button @click="exchangeMode = 'sell'"
                                :class="exchangeMode === 'sell' ? 'bg-gradient-to-r from-red-600 to-pink-600' : 'bg-gray-700'"
                                class="flex-1 py-3 rounded-xl font-bold transition-all duration-300 hover:shadow-lg">
                            💎 Sell Crypto
                        </button>
                    </div>

                    <!-- Buy/Sell Form -->
                    <div x-show="exchangeMode === 'buy'" x-transition class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2 text-gray-300">Select Cryptocurrency</label>
                            <select class="w-full bg-gray-900 border border-gray-700 rounded-xl p-4 text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                @foreach($currencies ?? [] as $currency)
                                <option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-300">Amount (THB)</label>
                                <input type="number"
                                       class="w-full bg-gray-900 border border-gray-700 rounded-xl p-4 text-white text-lg font-bold focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="0.00"
                                       x-model="buyAmount">
                                <div class="text-xs text-gray-400 mt-2">Available: ฿{{ number_format($thbWallet->balance ?? 0, 2) }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-300">You'll Receive</label>
                                <input type="text" readonly
                                       class="w-full bg-gray-900/50 border border-gray-700 rounded-xl p-4 text-white text-lg font-bold"
                                       :value="calculateReceive(buyAmount)"
                                       placeholder="0.00000000">
                                <div class="text-xs text-gray-400 mt-2">Rate: 1 BTC = ฿1,234,567</div>
                            </div>
                        </div>

                        <!-- Quick Amount Buttons -->
                        <div class="flex space-x-2">
                            <button @click="buyAmount = 1000" class="flex-1 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition">฿1,000</button>
                            <button @click="buyAmount = 5000" class="flex-1 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition">฿5,000</button>
                            <button @click="buyAmount = 10000" class="flex-1 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition">฿10,000</button>
                            <button @click="buyAmount = 50000" class="flex-1 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition">฿50,000</button>
                        </div>

                        <!-- Exchange Summary -->
                        <div class="bg-gray-900/50 rounded-xl p-4 border border-gray-700">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Exchange Rate</span>
                                    <span class="font-semibold">฿1,234,567.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">Platform Fee (0.5%)</span>
                                    <span class="font-semibold">฿25.00</span>
                                </div>
                                <div class="border-t border-gray-700 pt-2 flex justify-between">
                                    <span class="font-bold">Total</span>
                                    <span class="font-bold text-lg text-green-400">฿5,025.00</span>
                                </div>
                            </div>
                        </div>

                        <button class="w-full py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                            🚀 Buy Now
                        </button>
                    </div>

                    <!-- Sell Form (Similar structure) -->
                    <div x-show="exchangeMode === 'sell'" x-transition class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2 text-gray-300">Select Cryptocurrency</label>
                            <select class="w-full bg-gray-900 border border-gray-700 rounded-xl p-4 text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                @foreach($currencies ?? [] as $currency)
                                <option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-300">Amount (Crypto)</label>
                                <input type="number"
                                       class="w-full bg-gray-900 border border-gray-700 rounded-xl p-4 text-white text-lg font-bold focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                       placeholder="0.00000000"
                                       x-model="sellAmount">
                                <div class="text-xs text-gray-400 mt-2">Available: 0.5000 BTC</div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2 text-gray-300">You'll Receive (THB)</label>
                                <input type="text" readonly
                                       class="w-full bg-gray-900/50 border border-gray-700 rounded-xl p-4 text-white text-lg font-bold"
                                       :value="calculateSell(sellAmount)"
                                       placeholder="0.00">
                                <div class="text-xs text-gray-400 mt-2">Rate: 1 BTC = ฿1,234,567</div>
                            </div>
                        </div>

                        <button class="w-full py-4 bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 rounded-xl font-bold text-lg shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:scale-105">
                            💰 Sell Now
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar - Order Book & Recent Trades -->
        <div class="lg:col-span-3 space-y-6">

            <!-- Order Book -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-2xl border border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-700 bg-gradient-to-r from-blue-900/30 to-purple-900/30">
                    <h3 class="font-bold">Order Book</h3>
                </div>
                <div class="p-4">
                    <!-- Sell Orders -->
                    <div class="space-y-1 mb-4">
                        @for($i = 0; $i < 5; $i++)
                        <div class="flex justify-between text-xs p-2 rounded bg-red-500/10 hover:bg-red-500/20 transition">
                            <span class="text-red-400">฿{{ number_format(1234567 + ($i * 100), 2) }}</span>
                            <span class="text-gray-400">{{ number_format(rand(1, 10) / 10, 4) }}</span>
                            <span class="text-gray-500">฿{{ number_format(rand(1000, 5000), 2) }}</span>
                        </div>
                        @endfor
                    </div>

                    <!-- Current Price -->
                    <div class="py-3 mb-4 text-center bg-gradient-to-r from-green-500/20 to-emerald-500/20 rounded-xl border border-green-500/50">
                        <div class="text-2xl font-bold text-green-400">฿1,234,567</div>
                        <div class="text-xs text-green-300">$41,234.56 USD</div>
                    </div>

                    <!-- Buy Orders -->
                    <div class="space-y-1">
                        @for($i = 0; $i < 5; $i++)
                        <div class="flex justify-between text-xs p-2 rounded bg-green-500/10 hover:bg-green-500/20 transition">
                            <span class="text-green-400">฿{{ number_format(1234567 - ($i * 100), 2) }}</span>
                            <span class="text-gray-400">{{ number_format(rand(1, 10) / 10, 4) }}</span>
                            <span class="text-gray-500">฿{{ number_format(rand(1000, 5000), 2) }}</span>
                        </div>
                        @endfor
                    </div>
                </div>
            </div>

            <!-- Recent Trades -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-2xl border border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-700 bg-gradient-to-r from-green-900/30 to-blue-900/30">
                    <h3 class="font-bold">Recent Trades</h3>
                </div>
                <div class="p-4 space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
                    @for($i = 0; $i < 10; $i++)
                    <div class="flex justify-between text-xs">
                        <span :class="Math.random() > 0.5 ? 'text-green-400' : 'text-red-400'">
                            ฿{{ number_format(rand(1234000, 1235000), 2) }}
                        </span>
                        <span class="text-gray-400">{{ number_format(rand(1, 100) / 100, 4) }}</span>
                        <span class="text-gray-500">{{ date('H:i:s') }}</span>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alpine.js Data -->
<script>
function tradingDashboard() {
    return {
        exchangeMode: 'buy',
        selectedCurrency: 'BTC',
        buyAmount: 0,
        sellAmount: 0,

        selectCurrency(code) {
            this.selectedCurrency = code;
        },

        calculateReceive(thb) {
            return (thb / 1234567).toFixed(8);
        },

        calculateSell(crypto) {
            return (crypto * 1234567).toFixed(2);
        }
    }
}
</script>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.3);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #f59e0b, #d97706);
    border-radius: 10px;
}
</style>
@endsection
