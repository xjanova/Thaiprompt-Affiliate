<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                🎛️ {{ __('Advanced Bot Configuration') }}: {{ $bot->name }}
            </h2>
            <a href="{{ route('trading-bot.show', $bot) }}" class="text-gray-600 dark:text-gray-400 hover:underline">
                ← Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- TradingView Chart Integration -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl shadow-2xl p-6 border border-slate-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="text-2xl">📈</span>
                        <span>Live Chart - {{ $bot->trading_pair }}</span>
                    </h3>
                    <div class="flex gap-2">
                        <button onclick="toggleChartTheme()" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm">
                            🎨 Theme
                        </button>
                        <button onclick="fullscreenChart()" class="px-3 py-1 bg-slate-700 hover:bg-slate-600 text-white rounded-lg text-sm">
                            ⛶ Fullscreen
                        </button>
                    </div>
                </div>

                <!-- TradingView Widget -->
                <div id="tradingview_widget" class="rounded-xl overflow-hidden" style="height: 600px;"></div>

                <div class="mt-4 flex items-center justify-between text-sm text-gray-400">
                    <div class="flex items-center gap-4">
                        <span>🟢 Live Data</span>
                        <span>|</span>
                        <span>Exchange: {{ $bot->account->exchange->name }}</span>
                    </div>
                    <a href="https://www.tradingview.com" target="_blank" class="text-blue-400 hover:text-blue-300">
                        Powered by TradingView
                    </a>
                </div>
            </div>

            <!-- Advanced Settings Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Risk Management -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">⚠️</span>
                        <span>Risk Management</span>
                    </h3>

                    <form action="{{ route('trading-bot.update-risk', $bot) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Max Position Size (% of Capital)
                            </label>
                            <input type="range" name="max_position_size" min="1" max="100"
                                   value="{{ old('max_position_size', $bot->position_size_percentage) }}"
                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                                   oninput="this.nextElementSibling.value = this.value + '%'">
                            <output class="text-sm font-bold text-blue-600">{{ $bot->position_size_percentage }}%</output>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Stop Loss (%)
                            </label>
                            <div class="relative">
                                <input type="number" name="stop_loss" step="0.1"
                                       value="{{ old('stop_loss', $bot->strategy->stop_loss_percentage) }}"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                                <span class="absolute right-3 top-2.5 text-red-600">🛑</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Take Profit (%)
                            </label>
                            <div class="relative">
                                <input type="number" name="take_profit" step="0.1"
                                       value="{{ old('take_profit', $bot->strategy->take_profit_percentage) }}"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                                <span class="absolute right-3 top-2.5 text-green-600">🎯</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Trailing Stop (%)
                            </label>
                            <input type="number" name="trailing_stop" step="0.1"
                                   value="{{ old('trailing_stop', 0) }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-700 hover:to-orange-700 text-white py-3 rounded-lg font-semibold shadow-lg">
                            💾 Update Risk Settings
                        </button>
                    </form>
                </div>

                <!-- Trading Parameters -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">⚙️</span>
                        <span>Trading Parameters</span>
                    </h3>

                    <form action="{{ route('trading-bot.update-params', $bot) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Timeframe
                            </label>
                            <select name="timeframe" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="1m" {{ $bot->timeframe === '1m' ? 'selected' : '' }}>1 Minute</option>
                                <option value="5m" {{ $bot->timeframe === '5m' ? 'selected' : '' }}>5 Minutes</option>
                                <option value="15m" {{ $bot->timeframe === '15m' ? 'selected' : '' }}>15 Minutes</option>
                                <option value="1h" {{ $bot->timeframe === '1h' ? 'selected' : '' }}>1 Hour</option>
                                <option value="4h" {{ $bot->timeframe === '4h' ? 'selected' : '' }}>4 Hours</option>
                                <option value="1d" {{ $bot->timeframe === '1d' ? 'selected' : '' }}>1 Day</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Max Concurrent Trades
                            </label>
                            <input type="number" name="max_trades" min="1" max="50"
                                   value="{{ old('max_trades', 5) }}"
                                   class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Order Type
                            </label>
                            <select name="order_type" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700 dark:text-white">
                                <option value="market">Market Order (Fast)</option>
                                <option value="limit">Limit Order (Better Price)</option>
                                <option value="stop_limit">Stop Limit</option>
                            </select>
                        </div>

                        <div>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="enable_dca" value="1"
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Enable DCA (Dollar Cost Averaging)
                                </span>
                            </label>
                        </div>

                        <div>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="enable_martingale" value="1"
                                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Enable Martingale Strategy
                                </span>
                            </label>
                            <p class="text-xs text-yellow-600 mt-1 ml-8">⚠️ High Risk - Use with Caution</p>
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white py-3 rounded-lg font-semibold shadow-lg">
                            💾 Update Trading Parameters
                        </button>
                    </form>
                </div>

            </div>

            <!-- AI & Indicators -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <span class="text-2xl">🤖</span>
                    <span>AI & Technical Indicators</span>
                </h3>

                <form action="{{ route('trading-bot.update-indicators', $bot) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                        <!-- Indicator Toggles -->
                        <div class="space-y-3">
                            <h4 class="font-semibold text-gray-700 dark:text-gray-300">Trend Indicators</h4>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="indicators[]" value="EMA" checked class="rounded">
                                <span class="text-sm">EMA (Exponential Moving Average)</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="indicators[]" value="SMA" class="rounded">
                                <span class="text-sm">SMA (Simple Moving Average)</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="indicators[]" value="MACD" checked class="rounded">
                                <span class="text-sm">MACD</span>
                            </label>
                        </div>

                        <div class="space-y-3">
                            <h4 class="font-semibold text-gray-700 dark:text-gray-300">Momentum Indicators</h4>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="indicators[]" value="RSI" checked class="rounded">
                                <span class="text-sm">RSI (Relative Strength Index)</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="indicators[]" value="Stochastic" class="rounded">
                                <span class="text-sm">Stochastic Oscillator</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="indicators[]" value="CCI" class="rounded">
                                <span class="text-sm">CCI (Commodity Channel Index)</span>
                            </label>
                        </div>

                        <div class="space-y-3">
                            <h4 class="font-semibold text-gray-700 dark:text-gray-300">Volatility Indicators</h4>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="indicators[]" value="BB" checked class="rounded">
                                <span class="text-sm">Bollinger Bands</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="indicators[]" value="ATR" class="rounded">
                                <span class="text-sm">ATR (Average True Range)</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="indicators[]" value="Keltner" class="rounded">
                                <span class="text-sm">Keltner Channels</span>
                            </label>
                        </div>
                    </div>

                    <!-- AI Model Selection -->
                    <div class="mt-6 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">🤖 AI Enhancement</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">AI Model</label>
                                <select name="ai_model" class="w-full px-4 py-2 border rounded-lg dark:bg-gray-700">
                                    <option value="none">None (Rule-based Only)</option>
                                    <option value="lstm">LSTM Neural Network</option>
                                    <option value="transformer">Transformer Model</option>
                                    <option value="random_forest">Random Forest</option>
                                    <option value="ensemble">Ensemble (All Models)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Confidence Threshold</label>
                                <input type="range" name="ai_confidence" min="0" max="100" value="70"
                                       class="w-full" oninput="this.nextElementSibling.value = this.value + '%'">
                                <output class="text-sm font-bold text-purple-600">70%</output>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-3 rounded-lg font-semibold shadow-lg">
                            💾 Update AI & Indicators
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    @push('scripts')
    <!-- TradingView Widget Script -->
    <script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
    <script type="text/javascript">
        let tvWidget;
        let currentTheme = 'dark';

        function initTradingView() {
            const symbol = "{{ str_replace('/', '', $bot->trading_pair) }}";
            const exchange = "{{ strtoupper($bot->account->exchange->code) }}";

            tvWidget = new TradingView.widget({
                "width": "100%",
                "height": "100%",
                "symbol": `${exchange}:${symbol}`,
                "interval": "{{ $bot->timeframe }}",
                "timezone": "Asia/Bangkok",
                "theme": currentTheme,
                "style": "1",
                "locale": "en",
                "toolbar_bg": "#1e293b",
                "enable_publishing": false,
                "allow_symbol_change": true,
                "container_id": "tradingview_widget",
                "studies": [
                    "MASimple@tv-basicstudies",
                    "RSI@tv-basicstudies",
                    "MACD@tv-basicstudies"
                ],
                "show_popup_button": true,
                "popup_width": "1000",
                "popup_height": "650",
                "support_host": "https://www.tradingview.com"
            });
        }

        function toggleChartTheme() {
            currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
            initTradingView();
        }

        function fullscreenChart() {
            const element = document.getElementById('tradingview_widget');
            if (element.requestFullscreen) {
                element.requestFullscreen();
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initTradingView();
        });
    </script>
    @endpush
</x-app-layout>
