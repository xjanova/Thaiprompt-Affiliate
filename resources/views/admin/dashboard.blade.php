@extends('layouts.admin')

@section('title', 'แดชบอร์ด')

@section('content')
<div class="space-y-6">
    <!-- Welcome Header - Arrow X Style -->
    <div class="relative overflow-hidden bg-gradient-to-br from-purple-900 via-indigo-800 to-blue-900 dark:from-purple-950 dark:via-indigo-950 dark:to-blue-950 rounded-2xl shadow-2xl shadow-purple-500/20 p-8 text-white backdrop-blur-sm border border-white/10">
        <!-- Glassmorphism overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent pointer-events-none"></div>

        <!-- RGB Glow Effects -->
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse" style="animation-delay: 1s;"></div>

        <div class="relative flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 bg-gradient-to-r from-white via-purple-200 to-blue-200 bg-clip-text text-transparent">
                    สวัสดี, {{ Auth::user()->name }} 👋
                </h1>
                <p class="text-purple-200 dark:text-purple-300 text-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>ภาพรวมระบบ - {{ now()->format('d/m/Y H:i') }}</span>
                </p>
            </div>
            <div class="text-right">
                <div class="text-xs text-purple-300 mb-1">🟢 ออนไลน์</div>
                <div class="text-4xl font-bold bg-gradient-to-br from-purple-300 to-blue-300 bg-clip-text text-transparent">
                    {{ $stats['active_affiliates'] }}
                </div>
                <div class="text-xs text-purple-300 mt-1">Affiliates</div>
            </div>
        </div>
    </div>

    <!-- Main Stats Grid (4 columns) - Arrow X Style -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Users -->
        <a href="{{ route('admin.users.index') }}" class="block group">
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 dark:from-blue-700 dark:via-blue-800 dark:to-indigo-900 rounded-2xl shadow-2xl shadow-blue-500/20 p-6 text-white hover:shadow-blue-500/40 hover:scale-105 transition-all duration-300 backdrop-blur-sm border border-white/10">
                <!-- Glassmorphism overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none"></div>

                <!-- RGB Glow -->
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-blue-400 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-pulse"></div>

                <div class="relative flex items-center justify-between mb-3">
                    <div class="text-4xl drop-shadow-lg">👥</div>
                    @if($userGrowth != 0)
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-lg text-xs font-bold shadow-lg">
                            {{ $userGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($userGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="relative text-blue-100 text-sm mb-2 font-medium">ผู้ใช้ทั้งหมด</p>
                <p class="relative text-4xl font-bold bg-gradient-to-r from-white to-blue-100 bg-clip-text text-transparent">
                    {{ number_format($stats['total_users']) }}
                </p>
            </div>
        </a>

        <!-- Total Affiliates -->
        <a href="{{ route('admin.affiliates.index') }}" class="block group">
            <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 dark:from-purple-700 dark:via-purple-800 dark:to-indigo-900 rounded-2xl shadow-2xl shadow-purple-500/20 p-6 text-white hover:shadow-purple-500/40 hover:scale-105 transition-all duration-300 backdrop-blur-sm border border-white/10">
                <!-- Glassmorphism overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none"></div>

                <!-- RGB Glow -->
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-purple-400 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-pulse" style="animation-delay: 0.5s;"></div>

                <div class="relative flex items-center justify-between mb-3">
                    <div class="text-4xl drop-shadow-lg">🌐</div>
                    <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-lg text-xs font-bold shadow-lg">
                        {{ $stats['active_affiliates'] }} ใช้งาน
                    </span>
                </div>
                <p class="relative text-purple-100 text-sm mb-2 font-medium">Affiliates</p>
                <p class="relative text-4xl font-bold bg-gradient-to-r from-white to-purple-100 bg-clip-text text-transparent">
                    {{ number_format($stats['total_affiliates']) }}
                </p>
            </div>
        </a>

        <!-- Total Revenue -->
        <a href="{{ route('admin.commissions.index', ['status' => 'paid']) }}" class="block group">
            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-green-700 to-teal-800 dark:from-emerald-700 dark:via-green-800 dark:to-teal-900 rounded-2xl shadow-2xl shadow-emerald-500/20 p-6 text-white hover:shadow-emerald-500/40 hover:scale-105 transition-all duration-300 backdrop-blur-sm border border-white/10">
                <!-- Glassmorphism overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none"></div>

                <!-- RGB Glow -->
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-emerald-400 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-pulse" style="animation-delay: 1s;"></div>

                <div class="relative flex items-center justify-between mb-3">
                    <div class="text-4xl drop-shadow-lg">💰</div>
                    @if($revenueGrowth != 0)
                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-lg text-xs font-bold shadow-lg">
                            {{ $revenueGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($revenueGrowth), 1) }}%
                        </span>
                    @endif
                </div>
                <p class="relative text-emerald-100 text-sm mb-2 font-medium">รายได้ทั้งหมด</p>
                <p class="relative text-4xl font-bold bg-gradient-to-r from-white to-emerald-100 bg-clip-text text-transparent">
                    ฿{{ number_format($stats['paid_commissions'], 0) }}
                </p>
            </div>
        </a>

        <!-- Pending Commissions -->
        <a href="{{ route('admin.commissions.index', ['status' => 'pending']) }}" class="block group">
            <div class="relative overflow-hidden bg-gradient-to-br from-orange-600 via-red-600 to-pink-700 dark:from-orange-700 dark:via-red-700 dark:to-pink-800 rounded-2xl shadow-2xl shadow-orange-500/20 p-6 text-white hover:shadow-orange-500/40 hover:scale-105 transition-all duration-300 backdrop-blur-sm border border-white/10">
                <!-- Glassmorphism overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none"></div>

                <!-- RGB Glow -->
                <div class="absolute -top-10 -right-10 w-24 h-24 bg-orange-400 rounded-full mix-blend-multiply filter blur-2xl opacity-20 animate-pulse" style="animation-delay: 1.5s;"></div>

                <div class="relative flex items-center justify-between mb-3">
                    <div class="text-4xl drop-shadow-lg">⏳</div>
                    <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-lg text-xs font-bold shadow-lg">
                        {{ $stats['approved_commissions'] }} อนุมัติ
                    </span>
                </div>
                <p class="relative text-orange-100 text-sm mb-2 font-medium">รอดำเนินการ</p>
                <p class="relative text-4xl font-bold bg-gradient-to-r from-white to-orange-100 bg-clip-text text-transparent">
                    {{ number_format($stats['pending_commissions']) }}
                </p>
            </div>
        </a>
    </div>

    <!-- Crypto Rates Section - Arrow X Style -->
    @if(!empty($cryptoRates))
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
            <span class="text-3xl mr-3 bg-gradient-to-br from-yellow-400 to-orange-500 bg-clip-text text-transparent drop-shadow-lg">₿</span>
            <span class="bg-gradient-to-r from-purple-600 to-blue-600 dark:from-purple-400 dark:to-blue-400 bg-clip-text text-transparent">
                ราคาคริปโตปัจจุบัน
            </span>
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($cryptoRates as $symbol => $rate)
                <a href="{{ route('admin.crypto.currencies') }}" class="block group">
                    <div class="relative overflow-hidden bg-white/80 dark:bg-slate-800/80 backdrop-blur-md rounded-2xl shadow-xl shadow-gray-500/10 dark:shadow-gray-900/30 p-5 hover:shadow-2xl hover:shadow-purple-500/20 hover:scale-105 transition-all duration-300 border border-gray-200/50 dark:border-gray-700/50">
                        <!-- Glassmorphism overlay -->
                        <div class="absolute inset-0 bg-gradient-to-br from-white/20 dark:from-white/5 to-transparent pointer-events-none"></div>

                        <!-- RGB Glow on hover -->
                        <div class="absolute -top-8 -right-8 w-16 h-16 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>

                        <div class="relative flex items-center justify-between mb-3">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $symbol }}</span>
                            <span class="text-xs px-3 py-1.5 rounded-lg font-bold shadow-md
                                {{ $rate['change_24h'] >= 0 ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white' : 'bg-gradient-to-r from-red-500 to-pink-600 text-white' }}">
                                {{ $rate['change_24h'] >= 0 ? '↗' : '↘' }} {{ number_format(abs($rate['change_24h']), 2) }}%
                            </span>
                        </div>
                        <p class="relative text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent mb-2">
                            ฿{{ number_format($rate['price'], 2) }}
                        </p>
                        <p class="relative text-xs text-gray-500 dark:text-gray-400 font-medium">
                            Volume: <span class="text-gray-700 dark:text-gray-300 font-bold">฿{{ number_format($rate['volume_24h'], 0) }}</span>
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Quick Stats Grid (4 sections) - Arrow X Style -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Crypto Withdrawals -->
        <a href="{{ route('admin.crypto.withdrawals') }}" class="block group">
            <div class="relative overflow-hidden bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-2xl shadow-xl shadow-gray-500/10 dark:shadow-gray-900/30 p-6 hover:shadow-2xl hover:shadow-red-500/20 hover:scale-105 transition-all duration-300 border border-gray-200/50 dark:border-gray-700/50">
                <!-- Glassmorphism overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 dark:from-white/5 to-transparent pointer-events-none"></div>

                <!-- RGB Glow -->
                <div class="absolute -top-8 -right-8 w-20 h-20 bg-red-500 rounded-full mix-blend-multiply filter blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>

                <div class="relative flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center text-base">
                        <span class="text-2xl mr-2 drop-shadow-lg">💸</span> การถอนเงิน
                    </h3>
                    @if($cryptoWithdrawals['pending'] > 0)
                        <span class="px-3 py-1.5 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-lg text-xs font-bold shadow-lg animate-pulse">
                            {{ $cryptoWithdrawals['pending'] }}
                        </span>
                    @endif
                </div>
                <div class="relative space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">รอดำเนินการ</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $cryptoWithdrawals['pending'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">ต้องอนุมัติ</span>
                        <span class="font-bold text-orange-600 dark:text-orange-400">{{ $cryptoWithdrawals['requires_approval'] }}</span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">ยอดรอถอน</div>
                        <div class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                            {{ number_format($cryptoWithdrawals['total_pending_amount'], 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </a>

        <!-- KYC Verifications -->
        <a href="{{ route('admin.kyc.index') }}" class="block group">
            <div class="relative overflow-hidden bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-2xl shadow-xl shadow-gray-500/10 dark:shadow-gray-900/30 p-6 hover:shadow-2xl hover:shadow-yellow-500/20 hover:scale-105 transition-all duration-300 border border-gray-200/50 dark:border-gray-700/50">
                <!-- Glassmorphism overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 dark:from-white/5 to-transparent pointer-events-none"></div>

                <!-- RGB Glow -->
                <div class="absolute -top-8 -right-8 w-20 h-20 bg-yellow-500 rounded-full mix-blend-multiply filter blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>

                <div class="relative flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center text-base">
                        <span class="text-2xl mr-2 drop-shadow-lg">🆔</span> KYC
                    </h3>
                    @if($kycStats['pending'] > 0)
                        <span class="px-3 py-1.5 bg-gradient-to-r from-yellow-500 to-orange-600 text-white rounded-lg text-xs font-bold shadow-lg">
                            {{ $kycStats['pending'] }}
                        </span>
                    @endif
                </div>
                <div class="relative space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">รอตรวจสอบ</span>
                        <span class="font-bold text-yellow-600 dark:text-yellow-400">{{ $kycStats['pending'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">อนุมัติแล้ว</span>
                        <span class="font-bold text-green-600 dark:text-green-400">{{ $kycStats['verified'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">ปฏิเสธ</span>
                        <span class="font-bold text-red-600 dark:text-red-400">{{ $kycStats['rejected'] }}</span>
                    </div>
                </div>
            </div>
        </a>

        <!-- Crypto Transactions -->
        <a href="{{ route('admin.crypto.transactions') }}" class="block group">
            <div class="relative overflow-hidden bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-2xl shadow-xl shadow-gray-500/10 dark:shadow-gray-900/30 p-6 hover:shadow-2xl hover:shadow-blue-500/20 hover:scale-105 transition-all duration-300 border border-gray-200/50 dark:border-gray-700/50">
                <!-- Glassmorphism overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 dark:from-white/5 to-transparent pointer-events-none"></div>

                <!-- RGB Glow -->
                <div class="absolute -top-8 -right-8 w-20 h-20 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>

                <div class="relative flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center text-base">
                        <span class="text-2xl mr-2 drop-shadow-lg">🔄</span> Transactions
                    </h3>
                </div>
                <div class="relative space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">7 วันล่าสุด</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($cryptoTransactionsCount) }}</span>
                    </div>
                    @if(!empty($tradingStats))
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">Trading Pairs</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $tradingStats['active_pairs'] ?? 0 }}</span>
                        </div>
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Volume 24h</div>
                            <div class="text-2xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                                {{ number_format($tradingStats['total_volume_24h'] ?? 0, 2) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </a>

        <!-- Support Tickets -->
        <a href="{{ route('admin.tickets.index') }}" class="block group">
            <div class="relative overflow-hidden bg-white/90 dark:bg-slate-800/90 backdrop-blur-md rounded-2xl shadow-xl shadow-gray-500/10 dark:shadow-gray-900/30 p-6 hover:shadow-2xl hover:shadow-purple-500/20 hover:scale-105 transition-all duration-300 border border-gray-200/50 dark:border-gray-700/50">
                <!-- Glassmorphism overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-white/20 dark:from-white/5 to-transparent pointer-events-none"></div>

                <!-- RGB Glow -->
                <div class="absolute -top-8 -right-8 w-20 h-20 bg-purple-500 rounded-full mix-blend-multiply filter blur-xl opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>

                <div class="relative flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center text-base">
                        <span class="text-2xl mr-2 drop-shadow-lg">🎫</span> Tickets
                    </h3>
                    @if($ticketStats['new_today'] > 0)
                        <span class="px-3 py-1.5 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg text-xs font-bold shadow-lg animate-pulse">
                            {{ $ticketStats['new_today'] }} ใหม่
                        </span>
                    @endif
                </div>
                <div class="relative space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">เปิดอยู่</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ $ticketStats['open'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">ไม่มีผู้รับผิดชอบ</span>
                        <span class="font-bold text-orange-600 dark:text-orange-400">{{ $ticketStats['unassigned'] }}</span>
                    </div>
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400">🔥 Priority สูง</span>
                            <span class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $ticketStats['high_priority'] }}</span>
                        </div>
                    </div>
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
            <div style="height: 200px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Commission Status (Compact) -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-4">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3">สถานะคอมมิชชั่น</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="flex items-center justify-center" style="height: 200px;">
                    <canvas id="statusChart"></canvas>
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

    <!-- Bottom Section: Top Affiliates, Recent Activity & Recent Tickets (Compact) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
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
                @forelse($recentCommissions->take(5) as $commission)
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

        <!-- Recent Tickets -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-md p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">🎫 Tickets ล่าสุด</h3>
                <a href="{{ route('admin.tickets.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">
                    ดูทั้งหมด →
                </a>
            </div>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @forelse($recentTickets as $ticket)
                    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="block p-2 hover:bg-gray-50 dark:hover:bg-slate-700 rounded-lg transition">
                        <div class="flex items-start gap-2">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                                {{ $ticket->priority === 'critical' ? 'bg-red-100 dark:bg-red-900' : '' }}
                                {{ $ticket->priority === 'high' ? 'bg-orange-100 dark:bg-orange-900' : '' }}
                                {{ $ticket->priority === 'medium' ? 'bg-blue-100 dark:bg-blue-900' : '' }}
                                {{ $ticket->priority === 'low' ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                                <span class="text-xs font-bold
                                    {{ $ticket->priority === 'critical' ? 'text-red-600 dark:text-red-200' : '' }}
                                    {{ $ticket->priority === 'high' ? 'text-orange-600 dark:text-orange-200' : '' }}
                                    {{ $ticket->priority === 'medium' ? 'text-blue-600 dark:text-blue-200' : '' }}
                                    {{ $ticket->priority === 'low' ? 'text-gray-600 dark:text-gray-300' : '' }}">
                                    {{ strtoupper(substr($ticket->priority, 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                    #{{ $ticket->ticket_number }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                    {{ $ticket->subject }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    {{ $ticket->user->name }} • {{ $ticket->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0
                                    {{ $ticket->status === 'open' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                    {{ $ticket->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                    {{ $ticket->status === 'waiting_customer' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                    {{ $ticket->status === 'resolved' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                    {{ $ticket->status === 'closed' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : '' }}">
                                    {{ $ticket->status_label }}
                                </span>
                                @if($ticket->category)
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $ticket->category->name }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center text-gray-500 py-4 text-sm">
                        ยังไม่มี Tickets
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
            <a href="{{ route('admin.tickets.index') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg p-3 text-center transition text-white relative">
                @if($ticketStats['new_today'] > 0)
                    <span class="absolute -top-1 -right-1 flex h-5 w-5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-5 w-5 bg-red-500 text-white text-xs items-center justify-center font-bold">{{ $ticketStats['new_today'] }}</span>
                    </span>
                @endif
                <div class="text-2xl mb-1">🎫</div>
                <p class="text-xs font-semibold">Tickets</p>
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
