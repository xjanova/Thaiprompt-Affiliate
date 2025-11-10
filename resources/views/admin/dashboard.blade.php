@extends('layouts.admin')

@section('title', 'แดชบอร์ด')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">สวัสดี, {{ Auth::user()->name }}</h1>
                <p class="text-indigo-100 text-sm">ภาพรวมระบบ - {{ now()->format('d/m/Y H:i') }}</p>
            </div>
            <div class="text-right">
                <div class="text-xs text-indigo-200">ออนไลน์</div>
                <div class="text-2xl font-bold">{{ $stats['active_affiliates'] }}</div>
            </div>
        </div>
    </div>

    <!-- Main Stats Grid (4 columns) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Users -->
        <a href="{{ route('admin.users.index') }}" class="block group">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white hover:shadow-xl hover:scale-105 transition duration-200">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-3xl">👥</div>
                    @if($userGrowth != 0)
                        <span class="px-2 py-1 bg-white bg-opacity-20 rounded-md text-xs font-semibold">
                            {{ $userGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($userGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-white text-opacity-80 text-xs mb-1">ผู้ใช้ทั้งหมด</p>
                <p class="text-3xl font-bold">{{ number_format($stats['total_users']) }}</p>
            </div>
        </a>

        <!-- Total Affiliates -->
        <a href="{{ route('admin.affiliates.index') }}" class="block group">
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-5 text-white hover:shadow-xl hover:scale-105 transition duration-200">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-3xl">🌐</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-md text-xs font-semibold">
                        {{ $stats['active_affiliates'] }} ใช้งาน
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-xs mb-1">Affiliates</p>
                <p class="text-3xl font-bold">{{ number_format($stats['total_affiliates']) }}</p>
            </div>
        </a>

        <!-- Total Revenue -->
        <a href="{{ route('admin.commissions.index', ['status' => 'paid']) }}" class="block group">
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg p-5 text-white hover:shadow-xl hover:scale-105 transition duration-200">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-3xl">💰</div>
                    @if($revenueGrowth != 0)
                        <span class="px-2 py-1 bg-white bg-opacity-20 rounded-md text-xs font-semibold">
                            {{ $revenueGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($revenueGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="text-white text-opacity-80 text-xs mb-1">รายได้ทั้งหมด</p>
                <p class="text-3xl font-bold">฿{{ number_format($stats['paid_commissions'], 0) }}</p>
            </div>
        </a>

        <!-- Pending Commissions -->
        <a href="{{ route('admin.commissions.index', ['status' => 'pending']) }}" class="block group">
            <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-xl shadow-lg p-5 text-white hover:shadow-xl hover:scale-105 transition duration-200">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-3xl">⏳</div>
                    <span class="px-2 py-1 bg-white bg-opacity-20 rounded-md text-xs font-semibold">
                        {{ $stats['approved_commissions'] }} อนุมัติ
                    </span>
                </div>
                <p class="text-white text-opacity-80 text-xs mb-1">รอดำเนินการ</p>
                <p class="text-3xl font-bold">{{ number_format($stats['pending_commissions']) }}</p>
            </div>
        </a>
    </div>

    <!-- Crypto Rates Section -->
    @if(!empty($cryptoRates))
    <div>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center">
            <span class="text-2xl mr-2">₿</span> ราคาคริปโตปัจจุบัน
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($cryptoRates as $symbol => $rate)
                <a href="{{ route('admin.crypto.currencies') }}" class="block group">
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-4 hover:shadow-lg hover:scale-105 transition duration-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-gray-900 dark:text-white">{{ $symbol }}</span>
                            <span class="text-xs px-2 py-1 rounded-full font-semibold
                                {{ $rate['change_24h'] >= 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                {{ $rate['change_24h'] >= 0 ? '+' : '' }}{{ number_format($rate['change_24h'], 2) }}%
                            </span>
                        </div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            ฿{{ number_format($rate['price'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Volume: ฿{{ number_format($rate['volume_24h'], 0) }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Quick Stats Grid (3 sections) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Crypto Withdrawals -->
        <a href="{{ route('admin.crypto.withdrawals') }}" class="block group">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-5 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center">
                        <span class="text-xl mr-2">💸</span> การถอนเงิน
                    </h3>
                    @if($cryptoWithdrawals['pending'] > 0)
                        <span class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-full text-xs font-bold">
                            {{ $cryptoWithdrawals['pending'] }}
                        </span>
                    @endif
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">รอดำเนินการ</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $cryptoWithdrawals['pending'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ต้องอนุมัติ</span>
                        <span class="font-semibold text-orange-600 dark:text-orange-400">{{ $cryptoWithdrawals['requires_approval'] }}</span>
                    </div>
                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400">ยอดรอถอน</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($cryptoWithdrawals['total_pending_amount'], 2) }}</div>
                    </div>
                </div>
            </div>
        </a>

        <!-- KYC Verifications -->
        <a href="{{ route('admin.kyc.index') }}" class="block group">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-5 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center">
                        <span class="text-xl mr-2">🆔</span> KYC
                    </h3>
                    @if($kycStats['pending'] > 0)
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-xs font-bold">
                            {{ $kycStats['pending'] }}
                        </span>
                    @endif
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">รอตรวจสอบ</span>
                        <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ $kycStats['pending'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ยืนยันแล้ว</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">{{ $kycStats['verified'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ปฏิเสธ</span>
                        <span class="font-semibold text-red-600 dark:text-red-400">{{ $kycStats['rejected'] }}</span>
                    </div>
                </div>
            </div>
        </a>

        <!-- Crypto Transactions -->
        <a href="{{ route('admin.crypto.transactions') }}" class="block group">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-5 hover:shadow-lg transition">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center">
                        <span class="text-xl mr-2">🔄</span> Transactions
                    </h3>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">7 วันล่าสุด</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($cryptoTransactionsCount) }}</span>
                    </div>
                    @if(!empty($tradingStats))
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Trading Pairs</span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $tradingStats['active_pairs'] ?? 0 }}</span>
                        </div>
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400">Volume 24h</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($tradingStats['total_volume_24h'] ?? 0, 2) }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </a>
    </div>

    <!-- Charts Row (Compact) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Revenue Trend (Compact) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">รายได้รายเดือน</h3>
                <span class="text-xs px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-md font-semibold">
                    ฿{{ number_format($monthlyRevenue->sum('total'), 0) }}
                </span>
            </div>
            <canvas id="revenueChart" height="150"></canvas>
        </div>

        <!-- Commission Status (Compact) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-4">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3">สถานะคอมมิชชั่น</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center">
                    <canvas id="statusChart" height="120"></canvas>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">รอ</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $commissionStatus['pending'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">อนุมัติ</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $commissionStatus['approved'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">จ่าย</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $commissionStatus['paid'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">ปฏิเสธ</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $commissionStatus['rejected'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Top Affiliates & Recent Activity (Compact) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Top Affiliates -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">🏆 Top Affiliates</h3>
                <a href="{{ route('admin.affiliates.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="space-y-2">
                @forelse($topAffiliates->take(5) as $index => $affiliate)
                    <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-slate-700 rounded-lg hover:shadow-sm transition">
                        <div class="flex-shrink-0">
                            @if($index === 0)
                                <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center text-sm">
                                    🥇
                                </div>
                            @elseif($index === 1)
                                <div class="w-8 h-8 bg-gradient-to-br from-gray-300 to-gray-500 rounded-full flex items-center justify-center text-sm">
                                    🥈
                                </div>
                            @elseif($index === 2)
                                <div class="w-8 h-8 bg-gradient-to-br from-orange-400 to-orange-600 rounded-full flex items-center justify-center text-sm">
                                    🥉
                                </div>
                            @else
                                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                    {{ $index + 1 }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $affiliate->user->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $affiliate->total_referrals }} refs</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-green-600 dark:text-green-400">฿{{ number_format($affiliate->total_earnings, 0) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-4 text-sm">
                        ยังไม่มีข้อมูล
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">🔔 กิจกรรมล่าสุด</h3>
                <a href="{{ route('admin.commissions.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @forelse($recentCommissions->take(8) as $commission)
                    <div class="flex items-start gap-2 p-2 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition">
                        <img src="{{ $commission->affiliate->user->profile_picture_url }}"
                             alt="{{ $commission->affiliate->user->name }}"
                             class="w-8 h-8 rounded-full object-cover flex-shrink-0 ring-2 ring-gray-200 dark:ring-gray-700">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                {{ $commission->affiliate->user->name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-semibold text-green-600 dark:text-green-400">฿{{ number_format($commission->amount, 2) }}</span>
                            </p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0
                            {{ $commission->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                            {{ $commission->status === 'approved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                            {{ $commission->status === 'paid' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                            {{ $commission->status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                            {{ ucfirst($commission->status) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-4 text-sm">
                        ยังไม่มีกิจกรรม
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions (Compact) -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl shadow-lg p-4">
        <h3 class="text-base font-bold mb-3 text-white">⚡ การกระทำด่วน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <a href="{{ route('admin.users.create') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-3 text-center transition text-white">
                <div class="text-2xl mb-1">➕</div>
                <p class="text-xs font-semibold">เพิ่มผู้ใช้</p>
            </a>
            <a href="{{ route('admin.commissions.index', ['status' => 'pending']) }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-3 text-center transition text-white">
                <div class="text-2xl mb-1">✅</div>
                <p class="text-xs font-semibold">อนุมัติคอมมิชชั่น</p>
            </a>
            <a href="{{ route('admin.crypto.withdrawals') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-3 text-center transition text-white">
                <div class="text-2xl mb-1">💸</div>
                <p class="text-xs font-semibold">การถอนเงิน</p>
            </a>
            <a href="{{ route('admin.kyc.index') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-3 text-center transition text-white">
                <div class="text-2xl mb-1">🆔</div>
                <p class="text-xs font-semibold">ตรวจสอบ KYC</p>
            </a>
            <a href="{{ route('admin.affiliates.tree') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-3 text-center transition text-white">
                <div class="text-2xl mb-1">🌳</div>
                <p class="text-xs font-semibold">Affiliate Tree</p>
            </a>
            <a href="{{ route('admin.settings.index') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-3 text-center transition text-white">
                <div class="text-2xl mb-1">⚙️</div>
                <p class="text-xs font-semibold">ตั้งค่าระบบ</p>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Revenue Chart (Compact)
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    const colors = window.getChartColors();
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyRevenue->pluck('month')) !!},
            datasets: [{
                label: 'รายได้ (฿)',
                data: {!! json_encode($monthlyRevenue->pluck('total')) !!},
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: 'rgb(99, 102, 241)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    padding: 8,
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(99, 102, 241, 0.5)',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: colors.gridColor
                    },
                    ticks: {
                        color: colors.textColor,
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: colors.textColor,
                        maxTicksLimit: 6
                    }
                }
            }
        }
    });
}

// Status Donut Chart (Compact)
const statusCtx = document.getElementById('statusChart');
if (statusCtx) {
    const colors = window.getChartColors();
    const borderColor = window.isDarkMode() ? '#1e293b' : '#fff';
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['รอ', 'อนุมัติ', 'จ่าย', 'ปฏิเสธ'],
            datasets: [{
                data: [
                    {{ $commissionStatus['pending'] }},
                    {{ $commissionStatus['approved'] }},
                    {{ $commissionStatus['paid'] }},
                    {{ $commissionStatus['rejected'] }}
                ],
                backgroundColor: [
                    'rgba(234, 179, 8, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ],
                borderWidth: 2,
                borderColor: borderColor
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: colors.tooltipBg,
                    padding: 8,
                    titleColor: '#fff',
                    bodyColor: '#fff'
                }
            },
            cutout: '65%'
        }
    });
}
</script>
@endpush
