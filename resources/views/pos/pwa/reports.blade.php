<!DOCTYPE html>
<html lang="th" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f172a">
    <title>รายงาน - TP-POS</title>

    <link rel="manifest" href="/pos-manifest.json">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .pos-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
        }
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="pos-bg text-white antialiased">
    <div x-data="reportsApp()" x-init="init()" class="min-h-screen">
        <!-- Header -->
        <header class="glass sticky top-0 z-40 px-4 py-3 border-b border-white/10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="/pos/app" class="p-2 rounded-lg hover:bg-white/10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold">รายงาน</h1>
                        <p class="text-xs text-gray-400">Reports & Analytics</p>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="flex items-center gap-2">
                    <select
                        x-model="dateRange"
                        @change="loadData()"
                        class="px-4 py-2 rounded-xl bg-white/10 border border-white/10 text-white text-sm focus:outline-none"
                    >
                        <option value="today">วันนี้</option>
                        <option value="yesterday">เมื่อวาน</option>
                        <option value="week">7 วันล่าสุด</option>
                        <option value="month">30 วันล่าสุด</option>
                        <option value="year">ปีนี้</option>
                    </select>

                    <button
                        @click="exportReport()"
                        class="px-4 py-2 bg-green-500 hover:bg-green-600 rounded-xl font-medium flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        ส่งออก
                    </button>
                </div>
            </div>
        </header>

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4">
            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400 text-sm">ยอดขาย</span>
                    <div class="w-10 h-10 rounded-xl bg-green-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-green-400">฿<span x-text="formatNumber(summary.totalSales)">0</span></p>
                <p class="text-xs text-gray-400 mt-1">
                    <span :class="summary.salesChange >= 0 ? 'text-green-400' : 'text-red-400'">
                        <span x-text="summary.salesChange >= 0 ? '+' : ''"></span><span x-text="summary.salesChange">0</span>%
                    </span>
                    จากช่วงก่อนหน้า
                </p>
            </div>

            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400 text-sm">รายการขาย</span>
                    <div class="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-blue-400" x-text="formatNumber(summary.transactionCount)">0</p>
                <p class="text-xs text-gray-400 mt-1">รายการ</p>
            </div>

            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400 text-sm">เฉลี่ย/บิล</span>
                    <div class="w-10 h-10 rounded-xl bg-purple-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-purple-400">฿<span x-text="formatNumber(summary.averageTransaction)">0</span></p>
                <p class="text-xs text-gray-400 mt-1">ต่อรายการ</p>
            </div>

            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-400 text-sm">กำไร</span>
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-amber-400">฿<span x-text="formatNumber(summary.profit)">0</span></p>
                <p class="text-xs text-gray-400 mt-1">
                    <span x-text="summary.profitMargin">0</span>% margin
                </p>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-4">
            <!-- Sales Chart -->
            <div class="glass rounded-2xl p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    ยอดขายตามช่วงเวลา
                </h3>
                <div class="h-64">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

            <!-- Payment Methods Chart -->
            <div class="glass rounded-2xl p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    วิธีการชำระเงิน
                </h3>
                <div class="h-64">
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products & Recent Transactions -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 p-4">
            <!-- Top Products -->
            <div class="glass rounded-2xl p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                    สินค้าขายดี
                </h3>
                <div class="space-y-3">
                    <template x-for="(product, index) in topProducts" :key="index">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-white/5">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-lg flex items-center justify-center font-bold"
                                    :class="{
                                        'bg-amber-500/20 text-amber-400': index === 0,
                                        'bg-gray-500/20 text-gray-400': index === 1,
                                        'bg-orange-500/20 text-orange-400': index === 2,
                                        'bg-white/10 text-gray-400': index > 2
                                    }"
                                    x-text="index + 1">
                                </span>
                                <div>
                                    <p class="font-medium" x-text="product.name"></p>
                                    <p class="text-xs text-gray-400" x-text="product.quantity + ' ชิ้น'"></p>
                                </div>
                            </div>
                            <p class="font-bold text-green-400">฿<span x-text="formatNumber(product.total)"></span></p>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="glass rounded-2xl p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    รายการขายล่าสุด
                </h3>
                <div class="space-y-2">
                    <template x-for="tx in recentTransactions" :key="tx.id">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-white/5">
                            <div>
                                <p class="font-medium" x-text="tx.transaction_number"></p>
                                <p class="text-xs text-gray-400" x-text="formatTime(tx.created_at)"></p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-400">฿<span x-text="formatNumber(tx.total_amount)"></span></p>
                                <p class="text-xs" :class="{
                                    'text-green-400': tx.payment_method === 'cash',
                                    'text-blue-400': tx.payment_method === 'card',
                                    'text-purple-400': tx.payment_method === 'qr'
                                }" x-text="tx.payment_method"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Hourly Sales -->
        <div class="p-4">
            <div class="glass rounded-2xl p-5">
                <h3 class="font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    ยอดขายตามชั่วโมง
                </h3>
                <div class="h-48">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        function reportsApp() {
            return {
                dateRange: 'today',
                summary: {
                    totalSales: 0,
                    salesChange: 0,
                    transactionCount: 0,
                    averageTransaction: 0,
                    profit: 0,
                    profitMargin: 0
                },
                topProducts: [],
                recentTransactions: [],
                salesChart: null,
                paymentChart: null,
                hourlyChart: null,

                async init() {
                    await this.loadData();
                    this.initCharts();
                },

                async loadData() {
                    try {
                        const response = await fetch(`/pos/api/reports/today`);
                        const data = await response.json();

                        if (data.success) {
                            this.summary = {
                                totalSales: data.data.total_sales || 0,
                                salesChange: 5.2,
                                transactionCount: data.data.total_transactions || 0,
                                averageTransaction: data.data.average_transaction || 0,
                                profit: (data.data.total_sales || 0) * 0.3,
                                profitMargin: 30
                            };
                        }

                        // Load recent transactions
                        const txResponse = await fetch('/pos/api/transactions/recent');
                        const txData = await txResponse.json();
                        if (txData.success) {
                            this.recentTransactions = txData.data.slice(0, 5);
                        }

                        // Mock top products
                        this.topProducts = [
                            { name: 'กาแฟดำ', quantity: 45, total: 2250 },
                            { name: 'ลาเต้', quantity: 38, total: 2660 },
                            { name: 'คาปูชิโน่', quantity: 32, total: 2240 },
                            { name: 'มอคค่า', quantity: 28, total: 2240 },
                            { name: 'ชาไทย', quantity: 25, total: 1250 }
                        ];

                    } catch (error) {
                        console.error('Failed to load data:', error);
                    }
                },

                initCharts() {
                    // Sales Chart
                    const salesCtx = document.getElementById('salesChart').getContext('2d');
                    this.salesChart = new Chart(salesCtx, {
                        type: 'line',
                        data: {
                            labels: ['09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
                            datasets: [{
                                label: 'ยอดขาย',
                                data: [1200, 1900, 3000, 5000, 4200, 3500, 4100, 3800, 2500],
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    grid: { color: 'rgba(255,255,255,0.05)' },
                                    ticks: { color: 'rgba(255,255,255,0.5)' }
                                },
                                y: {
                                    grid: { color: 'rgba(255,255,255,0.05)' },
                                    ticks: { color: 'rgba(255,255,255,0.5)' }
                                }
                            }
                        }
                    });

                    // Payment Chart
                    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
                    this.paymentChart = new Chart(paymentCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['เงินสด', 'บัตร', 'QR', 'Wallet'],
                            datasets: [{
                                data: [45, 25, 20, 10],
                                backgroundColor: [
                                    '#22c55e',
                                    '#3b82f6',
                                    '#a855f7',
                                    '#f59e0b'
                                ],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        color: 'rgba(255,255,255,0.7)',
                                        padding: 20
                                    }
                                }
                            }
                        }
                    });

                    // Hourly Chart
                    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
                    this.hourlyChart = new Chart(hourlyCtx, {
                        type: 'bar',
                        data: {
                            labels: Array.from({length: 24}, (_, i) => `${i}:00`),
                            datasets: [{
                                label: 'ยอดขาย',
                                data: [0, 0, 0, 0, 0, 0, 0, 0, 500, 1200, 1800, 2500, 3200, 2800, 2100, 1900, 2200, 2800, 2400, 1800, 1200, 600, 200, 0],
                                backgroundColor: 'rgba(34, 197, 94, 0.5)',
                                borderColor: '#22c55e',
                                borderWidth: 1,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        color: 'rgba(255,255,255,0.5)',
                                        maxTicksLimit: 12
                                    }
                                },
                                y: {
                                    grid: { color: 'rgba(255,255,255,0.05)' },
                                    ticks: { color: 'rgba(255,255,255,0.5)' }
                                }
                            }
                        }
                    });
                },

                exportReport() {
                    alert('Export functionality coming soon!');
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('th-TH').format(num);
                },

                formatTime(dateStr) {
                    if (!dateStr) return '';
                    const date = new Date(dateStr);
                    return date.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
                }
            };
        }
    </script>
</body>
</html>
