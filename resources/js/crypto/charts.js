/**
 * Crypto Chart Components
 * Premium Chart.js implementations for trading and portfolio analytics
 */

import Chart from 'chart.js/auto';

/**
 * Trading Dashboard Component
 * Manages price charts, order book, and market watch
 */
export function tradingDashboard() {
    return {
        selectedCrypto: 'BTC',
        selectedPair: 'BTC/THB',
        timeframe: '1H',
        currentPrice: 0,
        priceChange24h: 0,
        volume24h: 0,
        high24h: 0,
        low24h: 0,

        // Trading state
        tradeType: 'buy',
        amount: '',
        total: '',
        balance: 0,

        // Charts
        priceChart: null,
        orderBookChart: null,

        // Live data
        marketData: [],
        orderBook: { bids: [], asks: [] },
        recentTrades: [],

        init() {
            console.log('Trading dashboard initialized');
            this.initCharts();
            this.loadMarketData();
            this.startLiveUpdates();
        },

        /**
         * Initialize all charts
         */
        initCharts() {
            this.initPriceChart();
            this.initOrderBookChart();
        },

        /**
         * Initialize price chart (candlestick/line)
         */
        initPriceChart() {
            const ctx = document.getElementById('priceChart');
            if (!ctx) return;

            // Generate sample OHLC data
            const data = this.generatePriceData(100);

            this.priceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: this.selectedPair,
                        data: data.prices,
                        borderColor: 'rgb(249, 115, 22)',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: 'rgb(249, 115, 22)',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgb(249, 115, 22)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: (context) => {
                                    return `฿${context.parsed.y.toLocaleString('th-TH', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    })}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.6)',
                                maxRotation: 0,
                                autoSkipPadding: 20,
                            }
                        },
                        y: {
                            position: 'right',
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.6)',
                                callback: (value) => {
                                    return '฿' + value.toLocaleString('th-TH');
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 750,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        },

        /**
         * Initialize order book chart
         */
        initOrderBookChart() {
            const ctx = document.getElementById('orderBookChart');
            if (!ctx) return;

            const orderBookData = this.generateOrderBookData();

            this.orderBookChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: orderBookData.labels,
                    datasets: [{
                        label: 'Buy Orders',
                        data: orderBookData.bids,
                        backgroundColor: 'rgba(34, 197, 94, 0.6)',
                        borderColor: 'rgb(34, 197, 94)',
                        borderWidth: 1,
                    }, {
                        label: 'Sell Orders',
                        data: orderBookData.asks,
                        backgroundColor: 'rgba(239, 68, 68, 0.6)',
                        borderColor: 'rgb(239, 68, 68)',
                        borderWidth: 1,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            callbacks: {
                                label: (context) => {
                                    return `${context.dataset.label}: ${context.parsed.x.toFixed(8)} BTC`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: false,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.6)',
                            }
                        },
                        y: {
                            stacked: false,
                            grid: {
                                display: false,
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.6)',
                                callback: (value) => {
                                    return '฿' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Generate sample price data
         */
        generatePriceData(points) {
            const labels = [];
            const prices = [];
            let basePrice = 2500000; // 2.5M THB for BTC
            const now = new Date();

            for (let i = points; i >= 0; i--) {
                const time = new Date(now - (i * 60000)); // 1 minute intervals
                labels.push(time.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }));

                // Random walk with slight upward trend
                const change = (Math.random() - 0.48) * 50000;
                basePrice += change;
                prices.push(basePrice);
            }

            this.currentPrice = prices[prices.length - 1];
            this.priceChange24h = ((prices[prices.length - 1] - prices[0]) / prices[0] * 100);

            return { labels, prices };
        },

        /**
         * Generate sample order book data
         */
        generateOrderBookData() {
            const labels = [];
            const bids = [];
            const asks = [];

            for (let i = 10; i > 0; i--) {
                const price = 2500000 - (i * 1000);
                labels.push(price);
                bids.push(Math.random() * 5);
                asks.push(0);
            }

            for (let i = 1; i <= 10; i++) {
                const price = 2500000 + (i * 1000);
                labels.push(price);
                bids.push(0);
                asks.push(Math.random() * 5);
            }

            return { labels, bids, asks };
        },

        /**
         * Load market data from API
         */
        async loadMarketData() {
            try {
                const response = await fetch('/user/crypto-wallet/prices');
                if (response.ok) {
                    const data = await response.json();
                    this.marketData = Object.values(data);
                }
            } catch (error) {
                console.error('Failed to load market data:', error);
            }
        },

        /**
         * Start live price updates
         */
        startLiveUpdates() {
            // Update every 5 seconds
            setInterval(() => {
                this.updatePriceChart();
                this.loadMarketData();
            }, 5000);
        },

        /**
         * Update price chart with new data
         */
        updatePriceChart() {
            if (!this.priceChart) return;

            const data = this.priceChart.data;
            const lastPrice = data.datasets[0].data[data.datasets[0].data.length - 1];
            const newPrice = lastPrice + (Math.random() - 0.5) * 10000;

            // Add new data point
            data.labels.push(new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }));
            data.datasets[0].data.push(newPrice);

            // Remove old data point if too many
            if (data.labels.length > 100) {
                data.labels.shift();
                data.datasets[0].data.shift();
            }

            this.currentPrice = newPrice;
            this.priceChart.update('none'); // Update without animation
        },

        /**
         * Calculate total based on amount
         */
        calculateTotal() {
            if (this.amount && this.currentPrice) {
                this.total = (parseFloat(this.amount) * this.currentPrice).toFixed(2);
            }
        },

        /**
         * Calculate amount based on total
         */
        calculateAmount() {
            if (this.total && this.currentPrice) {
                this.amount = (parseFloat(this.total) / this.currentPrice).toFixed(8);
            }
        },

        /**
         * Set quick amount (25%, 50%, 75%, 100%)
         */
        setQuickAmount(percentage) {
            if (this.tradeType === 'buy') {
                // Use THB balance
                this.total = (this.balance * percentage / 100).toFixed(2);
                this.calculateAmount();
            } else {
                // Use crypto balance
                this.amount = (this.balance * percentage / 100).toFixed(8);
                this.calculateTotal();
            }
        },

        /**
         * Execute trade
         */
        async executeTrade() {
            if (!this.amount || !this.total) {
                alert('กรุณากรอกจำนวนที่ต้องการซื้อ/ขาย');
                return;
            }

            const endpoint = this.tradeType === 'buy'
                ? '/user/crypto-wallet/exchange/buy'
                : '/user/crypto-wallet/exchange/sell';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        currency_code: this.selectedCrypto,
                        amount: parseFloat(this.amount),
                        expected_total: parseFloat(this.total)
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert(`${this.tradeType === 'buy' ? 'ซื้อ' : 'ขาย'}สำเร็จ!`);
                    this.amount = '';
                    this.total = '';
                    this.loadMarketData();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + result.message);
                }
            } catch (error) {
                console.error('Trade failed:', error);
                alert('ไม่สามารถดำเนินการได้ กรุณาลองใหม่อีกครั้ง');
            }
        }
    };
}

/**
 * Portfolio Manager Component
 * Manages portfolio analytics and asset allocation charts
 */
export function portfolioManager() {
    return {
        allocationChart: null,
        performanceChart: null,

        init() {
            console.log('Portfolio manager initialized');
            this.initCharts();
        },

        /**
         * Initialize portfolio charts
         */
        initCharts() {
            this.initAllocationChart();
            this.initPerformanceChart();
        },

        /**
         * Initialize asset allocation donut chart
         */
        initAllocationChart() {
            const ctx = document.getElementById('allocationChart');
            if (!ctx) return;

            const colors = [
                'rgb(251, 146, 60)',  // Orange
                'rgb(59, 130, 246)',  // Blue
                'rgb(16, 185, 129)',  // Green
                'rgb(139, 92, 246)',  // Purple
                'rgb(239, 68, 68)',   // Red
                'rgb(236, 72, 153)',  // Pink
                'rgb(245, 158, 11)',  // Amber
                'rgb(20, 184, 166)',  // Teal
            ];

            this.allocationChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['BTC', 'ETH', 'USDT', 'BNB', 'XRP', 'ADA', 'SOL', 'DOT'],
                    datasets: [{
                        data: [35, 25, 15, 10, 5, 4, 3, 3],
                        backgroundColor: colors,
                        borderColor: '#1f2937',
                        borderWidth: 3,
                        hoverOffset: 10,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgb(139, 92, 246)',
                            borderWidth: 1,
                            padding: 12,
                            callbacks: {
                                label: (context) => {
                                    return `${context.label}: ${context.parsed}%`;
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        },

        /**
         * Initialize performance analytics chart
         */
        initPerformanceChart() {
            const ctx = document.getElementById('performanceChart');
            if (!ctx) return;

            const data = this.generatePerformanceData(30);

            this.performanceChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Portfolio Value',
                        data: data.values,
                        borderColor: 'rgb(168, 85, 247)',
                        backgroundColor: 'rgba(168, 85, 247, 0.1)',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 8,
                        pointHoverBackgroundColor: 'rgb(168, 85, 247)',
                        pointHoverBorderColor: '#fff',
                        pointHoverBorderWidth: 3,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: 'rgb(168, 85, 247)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: (context) => {
                                    return `฿${context.parsed.y.toLocaleString('th-TH', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    })}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.6)',
                                maxRotation: 0,
                                autoSkipPadding: 15,
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)',
                                drawBorder: false,
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.6)',
                                callback: (value) => {
                                    return '฿' + (value / 1000).toFixed(0) + 'K';
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        },

        /**
         * Generate performance data
         */
        generatePerformanceData(days) {
            const labels = [];
            const values = [];
            let baseValue = 100000;

            for (let i = days; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('th-TH', { day: '2-digit', month: 'short' }));

                // Trending upward with volatility
                const change = (Math.random() - 0.45) * 5000;
                baseValue += change;
                values.push(baseValue);
            }

            return { labels, values };
        }
    };
}

// Make functions globally available for Alpine.js
window.tradingDashboard = tradingDashboard;
window.portfolioManager = portfolioManager;
