<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
                <span class="text-2xl">🌐</span>
                {{ __('Multi-Exchange Dashboard') }}
            </h2>
            <div class="flex gap-2">
                <button onclick="refreshAllData()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm">
                    🔄 Refresh All
                </button>
                <button onclick="toggleAutoRefresh()" id="autoRefreshBtn" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm">
                    ⚡ Auto: ON
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Market Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-3xl">💹</span>
                        <span class="text-sm opacity-80">Total Volume</span>
                    </div>
                    <p class="text-3xl font-bold">฿{{ number_format($totalVolume ?? 0, 2) }}</p>
                    <p class="text-sm opacity-80 mt-1">24h</p>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-3xl">📊</span>
                        <span class="text-sm opacity-80">Active Bots</span>
                    </div>
                    <p class="text-3xl font-bold">{{ $activeBots ?? 0 }}</p>
                    <p class="text-sm opacity-80 mt-1">Running</p>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-3xl">🎯</span>
                        <span class="text-sm opacity-80">Total Profit</span>
                    </div>
                    <p class="text-3xl font-bold">฿{{ number_format($totalProfit ?? 0, 2) }}</p>
                    <p class="text-sm opacity-80 mt-1">All Time</p>
                </div>

                <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-3xl">⚡</span>
                        <span class="text-sm opacity-80">Opportunities</span>
                    </div>
                    <p class="text-3xl font-bold">{{ $arbitrageOpportunities ?? 0 }}</p>
                    <p class="text-sm opacity-80 mt-1">Arbitrage</p>
                </div>
            </div>

            <!-- Exchange Comparison -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <span class="text-2xl">📈</span>
                    <span>Exchange Comparison</span>
                </h3>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    @foreach($exchanges as $exchange)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-lg transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                @if($exchange->logo_url)
                                    <img src="{{ $exchange->logo_url }}" alt="{{ $exchange->name }}" class="w-12 h-12 rounded-full">
                                @else
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                        {{ substr($exchange->name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-white">{{ $exchange->name }}</h4>
                                    <span class="text-xs px-2 py-1 rounded-full {{ $exchange->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $exchange->is_active ? '🟢 Online' : '🔴 Offline' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Price for BTC/USDT -->
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">BTC/USDT:</span>
                                <span class="font-bold" id="price-{{ $exchange->id }}-BTCUSDT">Loading...</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">ETH/USDT:</span>
                                <span class="font-bold" id="price-{{ $exchange->id }}-ETHUSDT">Loading...</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">24h Volume:</span>
                                <span class="font-bold text-blue-600">$1.2B</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Fee:</span>
                                <span class="font-bold">{{ $exchange->trading_fee_percentage }}%</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="#" class="text-blue-600 hover:text-blue-700 text-sm font-semibold">
                                View Full Markets →
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Real-time Arbitrage Opportunities -->
            <div class="bg-gradient-to-br from-purple-900 to-indigo-900 rounded-2xl shadow-2xl p-6 text-white">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold flex items-center gap-2">
                        <span class="text-2xl">🔄</span>
                        <span>Arbitrage Opportunities</span>
                        <span class="px-3 py-1 bg-green-500 rounded-full text-sm animate-pulse">LIVE</span>
                    </h3>
                    <span class="text-sm opacity-80">Updated: <span id="lastUpdate">Just now</span></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-purple-700">
                                <th class="px-4 py-3 text-left text-sm font-semibold">Pair</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Buy From</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Sell To</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Profit %</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Net Profit</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody id="arbitrageTable">
                            <tr class="border-b border-purple-800/50">
                                <td colspan="6" class="px-4 py-8 text-center text-gray-300">
                                    <div class="animate-spin inline-block w-8 h-8 border-4 border-white/20 border-t-white rounded-full"></div>
                                    <p class="mt-2">Scanning for opportunities...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Performance Charts -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">📊 Performance Across Exchanges</h3>
                <canvas id="performanceChart" height="100"></canvas>
            </div>

            <!-- Active Bots Grid -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <span class="text-2xl">🤖</span>
                    <span>Active Bots Monitor</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($runningBots ?? [] as $bot)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-lg transition-all">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="font-bold text-gray-900 dark:text-white">{{ $bot->name }}</h4>
                            <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
                        </div>

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Pair:</span>
                                <span class="font-medium">{{ $bot->trading_pair }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Exchange:</span>
                                <span class="font-medium">{{ $bot->account->exchange->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">P&L:</span>
                                <span class="font-bold {{ $bot->total_profit_loss >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $bot->total_profit_loss >= 0 ? '+' : '' }}฿{{ number_format($bot->total_profit_loss, 2) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Trades:</span>
                                <span class="font-medium">{{ $bot->total_trades ?? 0 }}</span>
                            </div>
                        </div>

                        <div class="mt-3 flex gap-2">
                            <a href="{{ route('trading-bot.show', $bot) }}" class="flex-1 px-3 py-2 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-lg text-center text-xs font-semibold hover:bg-blue-200">
                                View
                            </a>
                            <form action="{{ route('trading-bot.stop', $bot) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full px-3 py-2 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded-lg text-xs font-semibold hover:bg-red-200">
                                    Stop
                                </button>
                            </form>
                        </div>
                    </div>
                    @endforeach

                    @if(count($runningBots ?? []) === 0)
                    <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                        <span class="text-6xl mb-4 block">🤖</span>
                        <p class="text-lg">No active bots running</p>
                        <a href="{{ route('trading-bot.create') }}" class="mt-4 inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Create Your First Bot
                        </a>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let autoRefresh = true;
        let refreshInterval;

        // Performance Chart
        const ctx = document.getElementById('performanceChart').getContext('2d');
        const performanceChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00', '24:00'],
                datasets: [
                    {
                        label: 'Binance',
                        data: [100, 105, 110, 108, 115, 120, 125],
                        borderColor: 'rgb(255, 193, 7)',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'Bitkub',
                        data: [100, 103, 107, 105, 112, 118, 122],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    },
                    {
                        label: 'KuCoin',
                        data: [100, 104, 108, 107, 113, 119, 124],
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: function(value) {
                                return '฿' + value;
                            }
                        }
                    }
                }
            }
        });

        function refreshAllData() {
            console.log('Refreshing all data...');
            // Implement AJAX refresh
            location.reload();
        }

        function toggleAutoRefresh() {
            autoRefresh = !autoRefresh;
            const btn = document.getElementById('autoRefreshBtn');

            if (autoRefresh) {
                btn.textContent = '⚡ Auto: ON';
                btn.classList.remove('bg-gray-600');
                btn.classList.add('bg-green-600');
                startAutoRefresh();
            } else {
                btn.textContent = '⏸ Auto: OFF';
                btn.classList.remove('bg-green-600');
                btn.classList.add('bg-gray-600');
                stopAutoRefresh();
            }
        }

        function startAutoRefresh() {
            refreshInterval = setInterval(() => {
                refreshAllData();
            }, 30000); // 30 seconds
        }

        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        }

        // Start auto refresh on load
        document.addEventListener('DOMContentLoaded', function() {
            if (autoRefresh) {
                startAutoRefresh();
            }
        });
    </script>
    @endpush
</x-app-layout>
