@extends('layouts.app')

@section('title', 'Cryptocurrency Price Charts')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">📈 Cryptocurrency Price Charts</h1>
            <p class="text-gray-300">Real-time price tracking and comparison</p>
        </div>

        {{-- Market Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" x-data="marketOverview()">
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="text-gray-300 text-sm mb-2">Total Market Cap</div>
                <div class="text-3xl font-bold text-white" x-text="formatCurrency(overview.total_market_cap)">฿0</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="text-gray-300 text-sm mb-2">24h Volume</div>
                <div class="text-3xl font-bold text-white" x-text="formatCurrency(overview.total_volume_24h)">฿0</div>
            </div>
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <div class="text-gray-300 text-sm mb-2">Active Currencies</div>
                <div class="text-3xl font-bold text-white" x-text="overview.active_currencies">0</div>
            </div>
        </div>

        {{-- Currency Selection & Chart --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-8" x-data="priceChart()">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <h2 class="text-2xl font-bold text-white mb-4 md:mb-0">Price Chart</h2>

                <div class="flex flex-col md:flex-row gap-4">
                    {{-- Currency Selection --}}
                    <select x-model="selectedCurrency" @change="loadChart()"
                            class="bg-white/20 text-white rounded-lg px-4 py-2 border border-white/30 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Currency</option>
                        @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                        @endforeach
                    </select>

                    {{-- Period Selection --}}
                    <div class="flex gap-2">
                        <template x-for="period in periods" :key="period.value">
                            <button @click="selectedPeriod = period.value; loadChart()"
                                    :class="selectedPeriod === period.value ? 'bg-blue-600 text-white' : 'bg-white/20 text-gray-300 hover:bg-white/30'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                                    x-text="period.label">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6" x-show="statistics.current">
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="text-gray-400 text-xs mb-1">Current</div>
                    <div class="text-white font-bold" x-text="'฿' + statistics.current.toLocaleString()">-</div>
                </div>
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="text-gray-400 text-xs mb-1">High</div>
                    <div class="text-green-400 font-bold" x-text="'฿' + statistics.high.toLocaleString()">-</div>
                </div>
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="text-gray-400 text-xs mb-1">Low</div>
                    <div class="text-red-400 font-bold" x-text="'฿' + statistics.low.toLocaleString()">-</div>
                </div>
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="text-gray-400 text-xs mb-1">Average</div>
                    <div class="text-blue-400 font-bold" x-text="'฿' + statistics.average.toLocaleString()">-</div>
                </div>
                <div class="bg-white/5 rounded-lg p-4">
                    <div class="text-gray-400 text-xs mb-1">Change</div>
                    <div :class="statistics.change >= 0 ? 'text-green-400' : 'text-red-400'"
                         class="font-bold"
                         x-text="(statistics.change >= 0 ? '+' : '') + statistics.change.toFixed(2) + '%'">-</div>
                </div>
            </div>

            {{-- Chart Canvas --}}
            <div class="relative" style="height: 400px;">
                <canvas id="priceChart"></canvas>
                <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-lg">
                    <div class="text-white">Loading chart...</div>
                </div>
            </div>
        </div>

        {{-- Comparison Chart --}}
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20 mb-8" x-data="comparisonChart()">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <h2 class="text-2xl font-bold text-white mb-4 md:mb-0">Compare Currencies</h2>

                <div class="flex flex-col md:flex-row gap-4">
                    {{-- Multi-select currencies --}}
                    <div class="relative">
                        <button @click="showCurrencySelect = !showCurrencySelect"
                                class="bg-white/20 text-white rounded-lg px-4 py-2 border border-white/30 hover:bg-white/30 transition-all duration-200 flex items-center gap-2">
                            <span>Select Currencies (<span x-text="selectedCurrencies.length">0</span>/5)</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="showCurrencySelect" @click.away="showCurrencySelect = false"
                             class="absolute right-0 mt-2 w-64 bg-gray-800 rounded-lg shadow-xl border border-gray-700 z-10 max-h-96 overflow-y-auto">
                            @foreach($currencies as $currency)
                            <label class="flex items-center px-4 py-3 hover:bg-gray-700 cursor-pointer transition-colors">
                                <input type="checkbox"
                                       :value="{{ $currency->id }}"
                                       x-model="selectedCurrencies"
                                       @change="if(selectedCurrencies.length > 5) selectedCurrencies.pop()"
                                       class="mr-3 rounded text-blue-600 focus:ring-blue-500">
                                <span class="text-white font-medium">{{ $currency->code }}</span>
                                <span class="text-gray-400 text-sm ml-2">{{ $currency->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- Period Selection --}}
                    <div class="flex gap-2">
                        <template x-for="period in periods" :key="period.value">
                            <button @click="selectedPeriod = period.value; loadComparison()"
                                    :class="selectedPeriod === period.value ? 'bg-purple-600 text-white' : 'bg-white/20 text-gray-300 hover:bg-white/30'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                                    x-text="period.label">
                            </button>
                        </template>
                    </div>

                    <button @click="loadComparison()"
                            :disabled="selectedCurrencies.length === 0"
                            class="bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg px-6 py-2 font-medium hover:from-purple-700 hover:to-pink-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        Compare
                    </button>
                </div>
            </div>

            {{-- Comparison Chart Canvas --}}
            <div class="relative" style="height: 400px;">
                <canvas id="comparisonChart"></canvas>
                <div x-show="loading" class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-lg">
                    <div class="text-white">Loading comparison...</div>
                </div>
                <div x-show="!loading && selectedCurrencies.length === 0" class="absolute inset-0 flex items-center justify-center">
                    <div class="text-gray-400 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <div class="text-lg">Select currencies to compare</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Gainers & Losers --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="marketMovers()">
            {{-- Top Gainers --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <span class="text-green-400">▲</span> Top Gainers (24h)
                </h3>
                <div class="space-y-3">
                    <template x-for="(coin, index) in topGainers" :key="index">
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-all duration-200">
                            <div class="flex items-center gap-3">
                                <span class="text-gray-400 font-mono text-sm" x-text="'#' + (index + 1)"></span>
                                <img :src="'/icons/cryptocurrency/' + coin.code.toLowerCase() + '.svg'"
                                     :alt="coin.code"
                                     class="w-8 h-8"
                                     @error="$el.style.display='none'">
                                <div>
                                    <div class="text-white font-bold" x-text="coin.code"></div>
                                    <div class="text-gray-400 text-sm" x-text="coin.name"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-white font-medium" x-text="'฿' + coin.price_thb.toLocaleString()"></div>
                                <div class="text-green-400 font-bold" x-text="'+' + coin.change_24h.toFixed(2) + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Top Losers --}}
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-6 border border-white/20">
                <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <span class="text-red-400">▼</span> Top Losers (24h)
                </h3>
                <div class="space-y-3">
                    <template x-for="(coin, index) in topLosers" :key="index">
                        <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg hover:bg-white/10 transition-all duration-200">
                            <div class="flex items-center gap-3">
                                <span class="text-gray-400 font-mono text-sm" x-text="'#' + (index + 1)"></span>
                                <img :src="'/icons/cryptocurrency/' + coin.code.toLowerCase() + '.svg'"
                                     :alt="coin.code"
                                     class="w-8 h-8"
                                     @error="$el.style.display='none'">
                                <div>
                                    <div class="text-white font-bold" x-text="coin.code"></div>
                                    <div class="text-gray-400 text-sm" x-text="coin.name"></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-white font-medium" x-text="'฿' + coin.price_thb.toLocaleString()"></div>
                                <div class="text-red-400 font-bold" x-text="coin.change_24h.toFixed(2) + '%'"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Market Overview Component
function marketOverview() {
    return {
        overview: {
            total_market_cap: 0,
            total_volume_24h: 0,
            active_currencies: 0
        },
        init() {
            this.loadOverview();
            setInterval(() => this.loadOverview(), 60000); // Update every minute
        },
        async loadOverview() {
            try {
                const response = await fetch('/api/crypto/market-overview');
                const data = await response.json();
                if (data.success) {
                    this.overview = data.overview;
                }
            } catch (error) {
                console.error('Failed to load market overview:', error);
            }
        },
        formatCurrency(value) {
            return '฿' + value.toLocaleString('en-US', { maximumFractionDigits: 0 });
        }
    }
}

// Price Chart Component
function priceChart() {
    return {
        selectedCurrency: '',
        selectedPeriod: '24h',
        loading: false,
        chart: null,
        statistics: {
            current: 0,
            high: 0,
            low: 0,
            average: 0,
            change: 0
        },
        periods: [
            { value: '1h', label: '1H' },
            { value: '24h', label: '24H' },
            { value: '7d', label: '7D' },
            { value: '30d', label: '30D' },
            { value: '90d', label: '90D' },
            { value: '1y', label: '1Y' }
        ],
        init() {
            this.initChart();
        },
        initChart() {
            const ctx = document.getElementById('priceChart').getContext('2d');
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Price (THB)',
                        data: [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgba(255, 255, 255, 0.2)',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: '#9ca3af' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        y: {
                            ticks: {
                                color: '#9ca3af',
                                callback: (value) => '฿' + value.toLocaleString()
                            },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        }
                    }
                }
            });
        },
        async loadChart() {
            if (!this.selectedCurrency) return;

            this.loading = true;
            try {
                const response = await fetch(`/api/crypto/chart/${this.selectedCurrency}?period=${this.selectedPeriod}`);
                const data = await response.json();

                if (data.success) {
                    this.statistics = data.statistics;

                    this.chart.data.labels = data.data.map(d => new Date(d.time).toLocaleString('th-TH', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    }));
                    this.chart.data.datasets[0].data = data.data.map(d => d.price_thb);
                    this.chart.data.datasets[0].label = `${data.currency.code} Price (THB)`;
                    this.chart.update();
                }
            } catch (error) {
                console.error('Failed to load chart:', error);
            } finally {
                this.loading = false;
            }
        }
    }
}

// Comparison Chart Component
function comparisonChart() {
    return {
        selectedCurrencies: [],
        selectedPeriod: '24h',
        showCurrencySelect: false,
        loading: false,
        chart: null,
        periods: [
            { value: '1h', label: '1H' },
            { value: '24h', label: '24H' },
            { value: '7d', label: '7D' },
            { value: '30d', label: '30D' }
        ],
        init() {
            this.initChart();
        },
        initChart() {
            const ctx = document.getElementById('comparisonChart').getContext('2d');
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: { color: '#fff' }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff'
                        }
                    },
                    scales: {
                        x: {
                            ticks: { color: '#9ca3af' },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        },
                        y: {
                            ticks: {
                                color: '#9ca3af',
                                callback: (value) => value.toFixed(2) + '%'
                            },
                            grid: { color: 'rgba(255, 255, 255, 0.1)' }
                        }
                    }
                }
            });
        },
        async loadComparison() {
            if (this.selectedCurrencies.length === 0) return;

            this.loading = true;
            try {
                const params = new URLSearchParams({
                    period: this.selectedPeriod,
                    currencies: this.selectedCurrencies.join(',')
                });

                const response = await fetch(`/api/crypto/compare?${params}`);
                const data = await response.json();

                if (data.success) {
                    // Get all unique timestamps
                    const allTimestamps = [...new Set(
                        data.currencies.flatMap(c => c.data.map(d => d.timestamp))
                    )].sort();

                    this.chart.data.labels = allTimestamps.map(ts =>
                        new Date(ts).toLocaleString('th-TH', {
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })
                    );

                    this.chart.data.datasets = data.currencies.map(currency => ({
                        label: currency.code,
                        data: currency.data.map(d => d.percent_change),
                        borderColor: currency.color,
                        backgroundColor: currency.color + '20',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4
                    }));

                    this.chart.update();
                }
            } catch (error) {
                console.error('Failed to load comparison:', error);
            } finally {
                this.loading = false;
            }
        }
    }
}

// Market Movers Component
function marketMovers() {
    return {
        topGainers: [],
        topLosers: [],
        init() {
            this.loadMovers();
            setInterval(() => this.loadMovers(), 60000);
        },
        async loadMovers() {
            try {
                const response = await fetch('/api/crypto/market-overview');
                const data = await response.json();
                if (data.success) {
                    this.topGainers = data.top_gainers;
                    this.topLosers = data.top_losers;
                }
            } catch (error) {
                console.error('Failed to load market movers:', error);
            }
        }
    }
}
</script>
@endpush

@endsection
