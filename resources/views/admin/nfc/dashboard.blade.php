{{--
    NFC System Dashboard - Admin Panel

    Dashboard สำหรับดูภาพรวมระบบบัตร NFC
    - สถิติรวมทั้งหมด
    - กราฟแสดงแนวโน้ม 7 วัน
    - แจ้งเตือนบัตรยอดเงินต่ำ
    - ธุรกรรมล่าสุด

    @version 1.0.0
    @since 2025-11-22
--}}

@extends('layouts.admin')

@section('title', 'NFC Dashboard')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="nfcDashboard()">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 bg-clip-text text-transparent">
                    📊 NFC System Dashboard
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    ภาพรวมระบบบัตร NFC Tap-to-Pay
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.nfc-cards.index') }}"
                   class="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 border border-gray-200 dark:border-gray-700">
                    <i class="fas fa-credit-card mr-2"></i>
                    จัดการบัตร
                </a>
                <a href="{{ route('admin.nfc-cards.create') }}"
                   class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                    <i class="fas fa-plus-circle mr-2"></i>
                    ออกบัตรใหม่
                </a>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
        {{-- Total Cards --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-credit-card text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-3xl font-bold mb-1">{{ number_format($totalCards) }}</h3>
                <p class="text-blue-100 text-sm">บัตรทั้งหมด</p>
            </div>
        </div>

        {{-- Active Cards --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-3xl font-bold mb-1">{{ number_format($activeCards) }}</h3>
                <p class="text-green-100 text-sm">บัตรใช้งาน</p>
            </div>
        </div>

        {{-- Blocked Cards --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-ban text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-3xl font-bold mb-1">{{ number_format($blockedCards) }}</h3>
                <p class="text-red-100 text-sm">บัตรถูกบล็อก</p>
            </div>
        </div>

        {{-- Total Balance --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-wallet text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-bold mb-1">฿{{ number_format($totalBalance, 2) }}</h3>
                <p class="text-purple-100 text-sm">ยอดเงินรวม</p>
            </div>
        </div>

        {{-- Today's Transactions --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-orange-600 dark:from-orange-600 dark:to-orange-700 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-exchange-alt text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-3xl font-bold mb-1">{{ number_format($todayTransactions) }}</h3>
                <p class="text-orange-100 text-sm">ธุรกรรมวันนี้</p>
            </div>
        </div>

        {{-- Today's Volume --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-teal-500 to-teal-600 dark:from-teal-600 dark:to-teal-700 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all duration-300 hover:scale-105">
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-white/10 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-2xl font-bold mb-1">฿{{ number_format($todayVolume, 2) }}</h3>
                <p class="text-teal-100 text-sm">ยอดเงินวันนี้</p>
            </div>
        </div>
    </div>

    {{-- 7-Day Trend Chart --}}
    <div class="mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-chart-area text-blue-500 mr-2"></i>
                    แนวโน้ม 7 วันล่าสุด
                </h2>
                <div class="flex gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">จำนวนธุรกรรม</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">ยอดเงิน (฿)</span>
                    </div>
                </div>
            </div>

            <div class="relative h-80">
                <canvas id="weeklyTrendChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Two Columns Layout --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
        {{-- Low Balance Alerts --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                    แจ้งเตือนยอดเงินต่ำ
                </h2>
                <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 rounded-full text-sm font-semibold">
                    {{ $lowBalanceCards->count() }} บัตร
                </span>
            </div>

            @if($lowBalanceCards->isEmpty())
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-4">
                        <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">ไม่มีบัตรที่มียอดเงินต่ำ</p>
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ผู้ใช้</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">บัตร</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ยอดเงิน</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($lowBalanceCards as $card)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-3">
                                                @if($card->user->avatar)
                                                    <img src="{{ asset($card->user->avatar) }}"
                                                         alt="{{ $card->user->name }}"
                                                         class="w-8 h-8 rounded-full ring-2 ring-gray-200 dark:ring-gray-600">
                                                @else
                                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                                                        {{ substr($card->user->name, 0, 1) }}
                                                    </div>
                                                @endif
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $card->user->name }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $card->user->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $card->card_name }}</p>
                                                <p class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $card->masked_card_number }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold
                                                {{ $card->balance < 100 ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400' }}">
                                                ฿{{ number_format($card->balance, 2) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <a href="{{ route('admin.nfc-cards.show', $card) }}"
                                               class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                                                ดูรายละเอียด
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Recent Transactions --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-history text-purple-500 mr-2"></i>
                    ธุรกรรมล่าสุด
                </h2>
                <a href="{{ route('admin.nfc.transactions') }}"
                   class="text-blue-600 dark:text-blue-400 hover:underline text-sm">
                    ดูทั้งหมด →
                </a>
            </div>

            @if($recentTransactions->isEmpty())
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                        <i class="fas fa-receipt text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มีธุรกรรม</p>
                </div>
            @else
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">บัตร</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ประเภท</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวน</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentTransactions as $transaction)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                        <td class="px-4 py-4">
                                            <span class="text-xs font-mono text-gray-600 dark:text-gray-400">
                                                {{ Str::limit($transaction->transaction_id, 12) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            @if($transaction->card)
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $transaction->card->card_name }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $transaction->user?->name }}
                                                    </p>
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            @php
                                                $typeConfig = [
                                                    'payment' => ['icon' => 'fa-shopping-cart', 'color' => 'blue', 'label' => 'ชำระเงิน'],
                                                    'topup' => ['icon' => 'fa-plus-circle', 'color' => 'green', 'label' => 'เติมเงิน'],
                                                    'refund' => ['icon' => 'fa-undo', 'color' => 'yellow', 'label' => 'คืนเงิน'],
                                                    'transfer_in' => ['icon' => 'fa-arrow-down', 'color' => 'teal', 'label' => 'รับโอน'],
                                                    'transfer_out' => ['icon' => 'fa-arrow-up', 'color' => 'orange', 'label' => 'โอนออก'],
                                                ];
                                                $config = $typeConfig[$transaction->type] ?? ['icon' => 'fa-question', 'color' => 'gray', 'label' => $transaction->type];
                                            @endphp
                                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold
                                                bg-{{ $config['color'] }}-100 dark:bg-{{ $config['color'] }}-900/30
                                                text-{{ $config['color'] }}-600 dark:text-{{ $config['color'] }}-400">
                                                <i class="fas {{ $config['icon'] }}"></i>
                                                {{ $config['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <span class="text-sm font-semibold {{ in_array($transaction->type, ['payment', 'transfer_out']) ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                {{ in_array($transaction->type, ['payment', 'transfer_out']) ? '-' : '+' }}฿{{ number_format($transaction->amount, 2) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @php
                                                $statusConfig = [
                                                    'completed' => ['color' => 'green', 'icon' => 'fa-check-circle', 'label' => 'สำเร็จ'],
                                                    'pending' => ['color' => 'yellow', 'icon' => 'fa-clock', 'label' => 'รอดำเนินการ'],
                                                    'failed' => ['color' => 'red', 'icon' => 'fa-times-circle', 'label' => 'ล้มเหลว'],
                                                ];
                                                $statusConf = $statusConfig[$transaction->status] ?? ['color' => 'gray', 'icon' => 'fa-question', 'label' => $transaction->status];
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold
                                                bg-{{ $statusConf['color'] }}-100 dark:bg-{{ $statusConf['color'] }}-900/30
                                                text-{{ $statusConf['color'] }}-600 dark:text-{{ $statusConf['color'] }}-400">
                                                <i class="fas {{ $statusConf['icon'] }}"></i>
                                                {{ $statusConf['label'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="text-sm text-gray-900 dark:text-white">
                                                {{ $transaction->created_at->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $transaction->created_at->format('H:i') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    /**
     * NFC Dashboard - Alpine.js Component
     */
    function nfcDashboard() {
        return {
            init() {
                this.initWeeklyTrendChart();
            },

            /**
             * สร้างกราฟแนวโน้ม 7 วัน
             */
            initWeeklyTrendChart() {
                const ctx = document.getElementById('weeklyTrendChart');
                if (!ctx) return;

                // ข้อมูลจาก backend
                const weeklyData = @json($weeklyStats);

                // เตรียมข้อมูลสำหรับกราฟ
                const labels = weeklyData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('th-TH', { month: 'short', day: 'numeric' });
                });

                const transactionCounts = weeklyData.map(item => item.count);
                const volumes = weeklyData.map(item => parseFloat(item.volume));

                // สร้างกราฟ
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'จำนวนธุรกรรม',
                                data: transactionCounts,
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                yAxisID: 'y',
                            },
                            {
                                label: 'ยอดเงิน (฿)',
                                data: volumes,
                                borderColor: 'rgb(168, 85, 247)',
                                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                yAxisID: 'y1',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: false,
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: 'rgba(255, 255, 255, 0.1)',
                                borderWidth: 1,
                                displayColors: true,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            if (context.datasetIndex === 1) {
                                                label += '฿' + context.parsed.y.toLocaleString('th-TH', {minimumFractionDigits: 2});
                                            } else {
                                                label += context.parsed.y.toLocaleString('th-TH');
                                            }
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'จำนวนธุรกรรม',
                                    color: 'rgb(59, 130, 246)',
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                },
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'ยอดเงิน (฿)',
                                    color: 'rgb(168, 85, 247)',
                                },
                                grid: {
                                    drawOnChartArea: false,
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '฿' + value.toLocaleString('th-TH');
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)',
                                }
                            }
                        }
                    }
                });
            }
        };
    }
</script>
@endpush
@endsection
