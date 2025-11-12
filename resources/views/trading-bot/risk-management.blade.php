<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
            <span class="text-2xl">⚠️</span>
            {{ __('Risk Management Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Risk Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-2xl shadow-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-3xl">🛡️</span>
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-full">High Priority</span>
                    </div>
                    <p class="text-sm opacity-90 mb-1">Current Risk Level</p>
                    <p class="text-3xl font-bold">Medium</p>
                    <div class="mt-3 h-2 bg-white/20 rounded-full overflow-hidden">
                        <div class="h-full bg-white rounded-full" style="width: 60%"></div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl shadow-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-3xl">📊</span>
                    </div>
                    <p class="text-sm opacity-90 mb-1">Portfolio VaR (95%)</p>
                    <p class="text-3xl font-bold">฿2,450</p>
                    <p class="text-sm mt-2 opacity-90">Daily exposure</p>
                </div>

                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-2xl shadow-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-3xl">📉</span>
                    </div>
                    <p class="text-sm opacity-90 mb-1">Max Drawdown</p>
                    <p class="text-3xl font-bold">-12.5%</p>
                    <p class="text-sm mt-2 opacity-90">Current period</p>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-2xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-3xl">✅</span>
                    </div>
                    <p class="text-sm opacity-90 mb-1">Risk/Reward</p>
                    <p class="text-3xl font-bold">1:2.5</p>
                    <p class="text-sm mt-2 opacity-90">Avg ratio</p>
                </div>
            </div>

            <!-- Risk Allocation -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">💼 Capital Allocation</h3>
                    <div class="space-y-4">
                        @php
                            $allocations = [
                                ['name' => 'BTC/USDT Bot', 'amount' => 5000, 'percentage' => 35, 'risk' => 'Medium', 'color' => 'blue'],
                                ['name' => 'ETH/USDT Bot', 'amount' => 4000, 'percentage' => 28, 'risk' => 'Low', 'color' => 'green'],
                                ['name' => 'Arbitrage Bot', 'amount' => 3500, 'percentage' => 24, 'risk' => 'Low', 'color' => 'green'],
                                ['name' => 'Grid Trading', 'amount' => 1850, 'percentage' => 13, 'risk' => 'High', 'color' => 'red'],
                            ];
                        @endphp

                        @foreach($allocations as $alloc)
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $alloc['name'] }}</p>
                                    <p class="text-sm text-gray-500">
                                        <span class="px-2 py-0.5 bg-{{ $alloc['color'] }}-100 text-{{ $alloc['color'] }}-800 rounded text-xs">
                                            {{ $alloc['risk'] }} Risk
                                        </span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-900 dark:text-white">฿{{ number_format($alloc['amount']) }}</p>
                                    <p class="text-sm text-gray-500">{{ $alloc['percentage'] }}%</p>
                                </div>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-{{ $alloc['color'] }}-500 rounded-full transition-all duration-500"
                                     style="width: {{ $alloc['percentage'] }}%"></div>
                            </div>
                        </div>
                        @endforeach

                        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-900 dark:text-white">Total Allocated</span>
                                <span class="text-2xl font-bold text-blue-600">฿14,350</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Risk Distribution -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">🎯 Risk Distribution</h3>
                    <canvas id="riskDistributionChart" height="300"></canvas>
                    <div class="mt-6 grid grid-cols-3 gap-4 text-center">
                        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <p class="text-2xl font-bold text-green-600">52%</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Low Risk</p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <p class="text-2xl font-bold text-blue-600">35%</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Medium Risk</p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-xl">
                            <p class="text-2xl font-bold text-red-600">13%</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">High Risk</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Risk Alerts -->
            <div class="bg-gradient-to-r from-red-900 to-red-800 rounded-2xl shadow-2xl p-6 text-white">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <span class="text-2xl">🚨</span>
                    <span>Active Risk Alerts</span>
                </h3>
                <div class="space-y-3">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-4">
                        <span class="text-3xl">⚠️</span>
                        <div class="flex-1">
                            <p class="font-bold mb-1">High Volatility Detected</p>
                            <p class="text-sm opacity-90">BTC/USDT showing 15% volatility spike. Consider reducing position size.</p>
                            <p class="text-xs opacity-75 mt-2">5 minutes ago</p>
                        </div>
                        <button class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded-lg text-sm">
                            Adjust
                        </button>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-4">
                        <span class="text-3xl">📉</span>
                        <div class="flex-1">
                            <p class="font-bold mb-1">Stop Loss Triggered</p>
                            <p class="text-sm opacity-90">Grid Trading bot reached stop loss threshold. Position closed automatically.</p>
                            <p class="text-xs opacity-75 mt-2">12 minutes ago</p>
                        </div>
                        <button class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded-lg text-sm">
                            Review
                        </button>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex items-start gap-4">
                        <span class="text-3xl">💰</span>
                        <div class="flex-1">
                            <p class="font-bold mb-1">Capital Allocation Warning</p>
                            <p class="text-sm opacity-90">Total allocation exceeds 90% of available capital. Consider rebalancing.</p>
                            <p class="text-xs opacity-75 mt-2">1 hour ago</p>
                        </div>
                        <button class="px-3 py-1 bg-white/20 hover:bg-white/30 rounded-lg text-sm">
                            Rebalance
                        </button>
                    </div>
                </div>
            </div>

            <!-- Risk Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">📊 Position Limits</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Max Position Size</span>
                                <span class="text-sm font-bold">15%</span>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500" style="width: 15%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Daily Loss Limit</span>
                                <span class="text-sm font-bold text-red-600">-5%</span>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-red-500" style="width: 25%"></div>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between mb-2">
                                <span class="text-sm text-gray-600 dark:text-gray-400">Leverage Used</span>
                                <span class="text-sm font-bold text-orange-600">2.5x</span>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-orange-500" style="width: 50%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">⚡ Stop Loss Settings</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm">Global Stop Loss</span>
                            <span class="font-bold text-red-600">-10%</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm">Trailing Stop</span>
                            <span class="font-bold text-orange-600">2%</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm">Break-even Trigger</span>
                            <span class="font-bold text-green-600">+5%</span>
                        </div>
                        <button class="w-full mt-3 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold">
                            ⚙️ Configure
                        </button>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">🎯 Risk Score</h3>
                    <div class="flex items-center justify-center py-6">
                        <div class="relative w-32 h-32">
                            <svg class="transform -rotate-90 w-32 h-32">
                                <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="none" class="text-gray-200" />
                                <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="8" fill="none" stroke-linecap="round" class="text-green-500" stroke-dasharray="351" stroke-dashoffset="105" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="text-center">
                                    <p class="text-3xl font-bold text-green-600">70</p>
                                    <p class="text-xs text-gray-500">Good</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span>Diversification</span>
                            <span class="font-bold text-green-600">85/100</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Capital Safety</span>
                            <span class="font-bold text-green-600">75/100</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Risk Exposure</span>
                            <span class="font-bold text-yellow-600">60/100</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('riskDistributionChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Low Risk', 'Medium Risk', 'High Risk'],
                datasets: [{
                    data: [52, 35, 13],
                    backgroundColor: [
                        'rgb(34, 197, 94)',
                        'rgb(59, 130, 246)',
                        'rgb(239, 68, 68)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
