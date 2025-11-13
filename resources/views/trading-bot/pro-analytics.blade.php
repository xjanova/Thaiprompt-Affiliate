<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-2">
                <span class="text-2xl">📊</span>
                {{ __('Professional Analytics') }}: {{ $bot->name }}
            </h2>
            <div class="flex gap-2">
                <select id="timeRange" class="px-4 py-2 border rounded-lg dark:bg-gray-700">
                    <option>Last 24 Hours</option>
                    <option>Last 7 Days</option>
                    <option>Last 30 Days</option>
                    <option>All Time</option>
                </select>
                <button onclick="exportReport()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                    📥 Export
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
                    <p class="text-sm opacity-90 mb-1">Total Profit</p>
                    <p class="text-3xl font-bold">฿{{ number_format($bot->net_profit ?? 0, 2) }}</p>
                    <p class="text-sm mt-2 opacity-90">
                        <span class="text-green-200">↑ {{ number_format($performanceData['roi_percentage'] ?? 0, 2) }}%</span>
                    </p>
                </div>

                <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl shadow-xl p-6 text-white">
                    <p class="text-sm opacity-90 mb-1">Win Rate</p>
                    <p class="text-3xl font-bold">{{ number_format($performanceData['win_rate'] ?? 0, 1) }}%</p>
                    <p class="text-sm mt-2 opacity-90">{{ $performanceData['winning_trades'] ?? 0 }}/{{ $performanceData['total_trades'] ?? 0 }}</p>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
                    <p class="text-sm opacity-90 mb-1">Sharpe Ratio</p>
                    <p class="text-3xl font-bold">{{ number_format($performanceData['sharpe_ratio'] ?? 0, 2) }}</p>
                    <p class="text-sm mt-2 opacity-90">Risk Adjusted</p>
                </div>

                <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white">
                    <p class="text-sm opacity-90 mb-1">Max Drawdown</p>
                    <p class="text-3xl font-bold">{{ number_format($performanceData['max_drawdown_percentage'] ?? 0, 2) }}%</p>
                    <p class="text-sm mt-2 opacity-90">Worst Case</p>
                </div>

                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
                    <p class="text-sm opacity-90 mb-1">Profit Factor</p>
                    <p class="text-3xl font-bold">{{ number_format($performanceData['profit_factor'] ?? 0, 2) }}</p>
                    <p class="text-sm mt-2 opacity-90">Win/Loss Ratio</p>
                </div>
            </div>

            <!-- Main Chart - P&L -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">📈 Cumulative P&L</h3>
                    <div class="flex gap-2">
                        <button class="px-3 py-1 bg-blue-100 text-blue-800 rounded-lg text-sm">1D</button>
                        <button class="px-3 py-1 bg-gray-100 text-gray-800 rounded-lg text-sm">1W</button>
                        <button class="px-3 py-1 bg-gray-100 text-gray-800 rounded-lg text-sm">1M</button>
                        <button class="px-3 py-1 bg-gray-100 text-gray-800 rounded-lg text-sm">ALL</button>
                    </div>
                </div>
                <canvas id="profitLossChart" height="80"></canvas>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Win/Loss Distribution -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎯 Win/Loss Distribution</h3>
                    <canvas id="winLossChart" height="250"></canvas>
                </div>

                <!-- Trade Volume by Time -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📊 Trade Volume by Hour</h3>
                    <canvas id="volumeChart" height="250"></canvas>
                </div>

                <!-- Strategy Performance -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎯 Strategy Performance</h3>
                    <canvas id="strategyChart" height="250"></canvas>
                </div>

                <!-- Risk Metrics Radar -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">⚡ Risk Profile</h3>
                    <canvas id="riskRadarChart" height="250"></canvas>
                </div>
            </div>

            <!-- Performance Heatmap -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">🗺️ Performance Heatmap (Last 12 Weeks)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="text-left text-xs text-gray-500 pb-2">Day</th>
                                @for($week = 1; $week <= 12; $week++)
                                    <th class="text-center text-xs text-gray-500 pb-2">W{{ $week }}</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                            <tr>
                                <td class="text-xs text-gray-500 py-1">{{ $day }}</td>
                                @for($week = 1; $week <= 12; $week++)
                                    @php
                                        $value = rand(-10, 30);
                                        $bgClass = $value > 20 ? 'bg-green-600' :
                                                   ($value > 10 ? 'bg-green-400' :
                                                   ($value > 0 ? 'bg-green-200' :
                                                   ($value > -5 ? 'bg-red-200' : 'bg-red-500')));
                                    @endphp
                                    <td class="p-1">
                                        <div class="w-8 h-8 {{ $bgClass }} rounded cursor-pointer hover:ring-2 ring-blue-500 transition"
                                             title="{{ $day }} Week {{ $week }}: {{ $value > 0 ? '+' : '' }}{{ $value }}%"></div>
                                    </td>
                                @endfor
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between mt-4 text-sm">
                    <span class="text-gray-500">Less Profitable</span>
                    <div class="flex gap-2">
                        <div class="w-6 h-6 bg-red-500 rounded"></div>
                        <div class="w-6 h-6 bg-red-200 rounded"></div>
                        <div class="w-6 h-6 bg-gray-200 rounded"></div>
                        <div class="w-6 h-6 bg-green-200 rounded"></div>
                        <div class="w-6 h-6 bg-green-400 rounded"></div>
                        <div class="w-6 h-6 bg-green-600 rounded"></div>
                    </div>
                    <span class="text-gray-500">More Profitable</span>
                </div>
            </div>

            <!-- Advanced Metrics Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">📊 Advanced Metrics</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Trade Statistics</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Average Win</span>
                                <span class="font-bold text-green-600">฿{{ number_format($performanceData['average_win'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Average Loss</span>
                                <span class="font-bold text-red-600">฿{{ number_format($performanceData['average_loss'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Best Trade</span>
                                <span class="font-bold text-green-600">฿{{ number_format($performanceData['best_trade'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Worst Trade</span>
                                <span class="font-bold text-red-600">฿{{ number_format($performanceData['worst_trade'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Risk Metrics</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Sharpe Ratio</span>
                                <span class="font-bold">{{ number_format($performanceData['sharpe_ratio'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Sortino Ratio</span>
                                <span class="font-bold">{{ number_format($performanceData['sortino_ratio'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Max Drawdown</span>
                                <span class="font-bold text-red-600">{{ number_format($performanceData['max_drawdown_percentage'] ?? 0, 2) }}%</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>VaR (95%)</span>
                                <span class="font-bold">฿{{ number_format($performanceData['value_at_risk'] ?? 0, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Efficiency</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Profit Factor</span>
                                <span class="font-bold">{{ number_format($performanceData['profit_factor'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Trades/Day</span>
                                <span class="font-bold">{{ number_format($performanceData['trades_per_day'] ?? 0, 1) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Profit/Day</span>
                                <span class="font-bold">฿{{ number_format($performanceData['profit_per_day'] ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                                <span>Win Streak</span>
                                <span class="font-bold text-green-600">{{ $performanceData['max_win_streak'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // P&L Chart
        const plCtx = document.getElementById('profitLossChart').getContext('2d');
        new Chart(plCtx, {
            type: 'line',
            data: {
                labels: ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
                datasets: [{
                    label: 'Cumulative P&L',
                    data: [0, 120, 180, 150, 250, 320, 400],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: value => '฿' + value
                        }
                    }
                }
            }
        });

        // Win/Loss Chart
        const wlCtx = document.getElementById('winLossChart').getContext('2d');
        new Chart(wlCtx, {
            type: 'doughnut',
            data: {
                labels: ['Winning Trades', 'Losing Trades'],
                datasets: [{
                    data: [{{ $performanceData['winning_trades'] ?? 70 }}, {{ $performanceData['losing_trades'] ?? 30 }}],
                    backgroundColor: ['rgb(34, 197, 94)', 'rgb(239, 68, 68)']
                }]
            }
        });

        // Volume Chart
        const volCtx = document.getElementById('volumeChart').getContext('2d');
        new Chart(volCtx, {
            type: 'bar',
            data: {
                labels: ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
                datasets: [{
                    label: 'Trade Volume',
                    data: [5, 12, 19, 25, 18, 8],
                    backgroundColor: 'rgba(59, 130, 246, 0.8)'
                }]
            }
        });

        // Strategy Chart
        const stratCtx = document.getElementById('strategyChart').getContext('2d');
        new Chart(stratCtx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [
                    {
                        label: 'Actual',
                        data: [100, 115, 125, 140],
                        borderColor: 'rgb(34, 197, 94)',
                        tension: 0.4
                    },
                    {
                        label: 'Benchmark',
                        data: [100, 105, 110, 115],
                        borderColor: 'rgb(156, 163, 175)',
                        borderDash: [5, 5],
                        tension: 0.4
                    }
                ]
            }
        });

        // Risk Radar
        const radarCtx = document.getElementById('riskRadarChart').getContext('2d');
        new Chart(radarCtx, {
            type: 'radar',
            data: {
                labels: ['Return', 'Risk', 'Consistency', 'Win Rate', 'Efficiency'],
                datasets: [{
                    label: 'Current Performance',
                    data: [85, 70, 90, 75, 80],
                    backgroundColor: 'rgba(139, 92, 246, 0.2)',
                    borderColor: 'rgb(139, 92, 246)',
                    pointBackgroundColor: 'rgb(139, 92, 246)'
                }]
            },
            options: {
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        function exportReport() {
            alert('Exporting detailed analytics report...');
            // Implement export functionality
        }
    </script>
    @endpush
</x-app-layout>
