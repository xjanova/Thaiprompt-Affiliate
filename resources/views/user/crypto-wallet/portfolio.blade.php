@extends('layouts.user-arrow-x')

@section('title', 'Portfolio Management')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900" x-data="portfolioManager()">

    <!-- Premium Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 rounded-3xl p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full filter blur-3xl"></div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-5xl font-black mb-3 bg-clip-text text-transparent bg-gradient-to-r from-white to-purple-200">
                        Portfolio Overview
                    </h1>
                    <p class="text-purple-100 text-xl">Manage your cryptocurrency investments</p>
                </div>

                <div class="mt-6 md:mt-0 text-right">
                    <div class="text-purple-200 text-sm mb-2">Total Portfolio Value</div>
                    <div class="text-6xl font-black text-white mb-2">
                        ฿{{ number_format($totalValueTHB ?? 0, 2) }}
                    </div>
                    <div class="flex items-center justify-end space-x-4 text-sm">
                        <span class="bg-green-500/30 text-green-200 px-4 py-2 rounded-full font-bold">
                            +15.8% ↑ Today
                        </span>
                        <span class="text-purple-200">
                            ≈ ${{ number_format(($totalValueTHB ?? 0) / 33, 2) }} USD
                        </span>
                    </div>
                </div>
            </div>

            <!-- Portfolio Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                    <div class="text-purple-200 text-sm mb-2">24h Change</div>
                    <div class="text-3xl font-bold text-green-300">+฿12,456</div>
                    <div class="text-xs text-green-200 mt-1">+8.2%</div>
                </div>
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                    <div class="text-purple-200 text-sm mb-2">Total Invested</div>
                    <div class="text-3xl font-bold text-white">฿145,000</div>
                    <div class="text-xs text-purple-200 mt-1">All time</div>
                </div>
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                    <div class="text-purple-200 text-sm mb-2">Total P&L</div>
                    <div class="text-3xl font-bold text-green-300">+฿23,890</div>
                    <div class="text-xs text-green-200 mt-1">+16.5%</div>
                </div>
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20">
                    <div class="text-purple-200 text-sm mb-2">Holdings</div>
                    <div class="text-3xl font-bold text-white">8</div>
                    <div class="text-xs text-purple-200 mt-1">Assets</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Portfolio Allocation - Left Column -->
        <div class="lg:col-span-1">
            <div class="bg-gray-900/80 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-purple-500/20">
                <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                    <span class="w-3 h-3 bg-purple-500 rounded-full mr-3 animate-pulse"></span>
                    Asset Allocation
                </h2>

                <!-- Donut Chart -->
                <div class="relative mb-8">
                    <canvas id="allocationChart" class="w-full h-64"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-white">8</div>
                            <div class="text-xs text-gray-400">Assets</div>
                        </div>
                    </div>
                </div>

                <!-- Asset List -->
                <div class="space-y-3">
                    @foreach($balances ?? [] as $balance)
                    <div class="flex items-center justify-between p-4 bg-gray-800/50 rounded-xl hover:bg-gray-800 transition group">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full" style="background: {{ ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#ef4444'][$loop->index % 5] }}"></div>
                            <span class="font-semibold text-white">{{ $balance['code'] ?? '' }}</span>
                        </div>
                        <span class="text-gray-300 font-mono">{{ rand(15, 45) }}%</span>
                    </div>
                    @endforeach
                </div>

                <!-- Rebalance Button -->
                <button class="mt-6 w-full py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 rounded-xl font-bold text-white shadow-lg transition-all duration-300 transition-transform hover:scale-[1.02]">
                    ⚖️ Rebalance Portfolio
                </button>
            </div>
        </div>

        <!-- Holdings & Performance - Right 2 Columns -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Performance Chart -->
            <div class="bg-gray-900/80 backdrop-blur-xl rounded-3xl p-8 shadow-2xl border border-purple-500/20">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-white">Performance Analytics</h2>
                    <div class="flex space-x-2">
                        <button class="px-4 py-2 bg-purple-600 rounded-lg text-white text-sm font-semibold">7D</button>
                        <button class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-white text-sm transition">30D</button>
                        <button class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-white text-sm transition">1Y</button>
                        <button class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-white text-sm transition">ALL</button>
                    </div>
                </div>

                <div class="h-64 bg-gray-800/30 rounded-2xl p-4">
                    <canvas id="performanceChart"></canvas>
                </div>
            </div>

            <!-- Holdings Table -->
            <div class="bg-gray-900/80 backdrop-blur-xl rounded-3xl shadow-2xl border border-purple-500/20 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-purple-900/50 to-pink-900/50 border-b border-purple-500/20">
                    <h2 class="text-2xl font-bold text-white">Your Holdings</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-800/50 text-gray-300 text-sm">
                                <th class="text-left p-4">Asset</th>
                                <th class="text-right p-4">Holdings</th>
                                <th class="text-right p-4">Value (THB)</th>
                                <th class="text-right p-4">24h Change</th>
                                <th class="text-right p-4">P&L</th>
                                <th class="text-center p-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-white">
                            @foreach($balances ?? [] as $balance)
                            <tr class="border-b border-gray-800 hover:bg-gray-800/30 transition group">
                                <td class="p-4">
                                    <div class="flex items-center space-x-3">
                                        @php
                                            $iconPath = public_path('icons/cryptocurrency/' . strtolower($balance['code'] ?? 'x') . '.svg');
                                        @endphp
                                        @if(file_exists($iconPath))
                                            <img src="{{ asset('icons/cryptocurrency/' . strtolower($balance['code']) . '.svg') }}"
                                                 alt="{{ $balance['code'] }}"
                                                 class="w-12 h-12">
                                        @else
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-lg font-bold">
                                                {{ substr($balance['code'] ?? 'X', 0, 1) }}
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-bold text-lg">{{ $balance['code'] ?? '' }}</div>
                                            <div class="text-xs text-gray-400">{{ $balance['name'] ?? '' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right p-4">
                                    <div class="font-mono font-bold">{{ number_format($balance['balance'] ?? 0, 8) }}</div>
                                    <div class="text-xs text-gray-400">${{ number_format(rand(100, 5000), 2) }}</div>
                                </td>
                                <td class="text-right p-4">
                                    <div class="font-bold text-lg">฿{{ number_format($balance['balance_thb'] ?? 0, 2) }}</div>
                                </td>
                                <td class="text-right p-4">
                                    <div class="flex items-center justify-end">
                                        @if(rand(0, 1))
                                        <span class="bg-green-500/20 text-green-400 px-3 py-1 rounded-full text-sm font-bold">
                                            +{{ rand(1, 15) }}.{{ rand(0, 9) }}% ↑
                                        </span>
                                        @else
                                        <span class="bg-red-500/20 text-red-400 px-3 py-1 rounded-full text-sm font-bold">
                                            -{{ rand(1, 8) }}.{{ rand(0, 9) }}% ↓
                                        </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right p-4">
                                    @if(rand(0, 1))
                                    <div class="text-green-400 font-bold">+฿{{ number_format(rand(100, 5000), 2) }}</div>
                                    <div class="text-xs text-green-300">+{{ rand(5, 20) }}%</div>
                                    @else
                                    <div class="text-red-400 font-bold">-฿{{ number_format(rand(100, 2000), 2) }}</div>
                                    <div class="text-xs text-red-300">-{{ rand(1, 10) }}%</div>
                                    @endif
                                </td>
                                <td class="text-center p-4">
                                    <button class="opacity-0 group-hover:opacity-100 px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-sm font-semibold transition-all transform hover:scale-110">
                                        Trade
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-3 gap-4">
                <a href="{{ route('user.crypto-wallet.deposit') }}"
                   class="bg-gradient-to-br from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 rounded-2xl p-6 text-center shadow-xl hover:shadow-2xl transition-all duration-300 transition-transform hover:scale-[1.02] group">
                    <div class="text-4xl mb-3 transform group-hover:scale-110 transition">📥</div>
                    <div class="text-white font-bold text-lg">Deposit</div>
                    <div class="text-green-100 text-sm">Add funds</div>
                </a>

                <a href="{{ route('user.crypto-wallet.withdraw') }}"
                   class="bg-gradient-to-br from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 rounded-2xl p-6 text-center shadow-xl hover:shadow-2xl transition-all duration-300 transition-transform hover:scale-[1.02] group">
                    <div class="text-4xl mb-3 transform group-hover:scale-110 transition">📤</div>
                    <div class="text-white font-bold text-lg">Withdraw</div>
                    <div class="text-pink-100 text-sm">Cash out</div>
                </a>

                <a href="{{ route('user.crypto-wallet.exchange') }}"
                   class="bg-gradient-to-br from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 rounded-2xl p-6 text-center shadow-xl hover:shadow-2xl transition-all duration-300 transition-transform hover:scale-[1.02] group">
                    <div class="text-4xl mb-3 transform group-hover:scale-110 transition">💱</div>
                    <div class="text-white font-bold text-lg">Exchange</div>
                    <div class="text-purple-100 text-sm">Swap coins</div>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function portfolioManager() {
    return {
        init() {
            this.initCharts();
        },

        initCharts() {
            // Initialize charts here
            console.log('Portfolio charts initialized');
        }
    }
}
</script>
@endsection
