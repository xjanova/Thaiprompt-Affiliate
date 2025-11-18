@extends('layouts.admin-v3')

@section('title', 'แดชบอร์ด')

@push('styles')
<style>
    /* Animated Background Circles - Effect พิเศษที่จำเป็น */
    @keyframes pulse-slow {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.8; }
    }

    .bg-circle {
        position: fixed;
        border-radius: 50%;
        filter: blur(60px);
        z-index: -5;
        animation: pulse-slow 3s ease-in-out infinite;
    }

    /* Light Mode: Subtle circles */
    .bg-circle {
        opacity: 0.1;
    }

    /* Dark Mode: Vibrant circles */
    .dark .bg-circle {
        opacity: 0.3;
    }

    .bg-circle-1 {
        top: 25%;
        left: 25%;
        width: 384px;
        height: 384px;
        background: linear-gradient(135deg, rgb(34, 211, 238), rgb(37, 99, 235));
        animation-delay: 0s;
    }

    .bg-circle-2 {
        bottom: 25%;
        right: 25%;
        width: 384px;
        height: 384px;
        background: linear-gradient(135deg, rgb(236, 72, 153), rgb(168, 85, 247));
        animation-delay: 1s;
    }

    .bg-circle-3 {
        top: 50%;
        left: 50%;
        width: 384px;
        height: 384px;
        background: linear-gradient(135deg, rgb(250, 204, 21), rgb(249, 115, 22));
        animation-delay: 2s;
    }
</style>
@endpush

@section('content')
{{-- Animated Background Circles --}}
<div class="bg-circle bg-circle-1"></div>
<div class="bg-circle bg-circle-2"></div>
<div class="bg-circle bg-circle-3"></div>

<div class="space-y-6">
    {{-- Welcome Header - Glass Fusion Style (V3: Pure Tailwind) --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-2xl p-8 relative overflow-hidden">
        {{-- Glassmorphism overlay --}}
        <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent pointer-events-none rounded-2xl"></div>

        <div class="relative flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 text-gray-900 dark:text-white drop-shadow-lg">
                    สวัสดี, {{ Auth::user()->name }} 👋
                </h1>
                <p class="text-gray-700 dark:text-white/90 text-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>ภาพรวมระบบ - {{ now()->format('d/m/Y H:i') }}</span>
                </p>
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-700 dark:text-white/90 mb-1">🟢 ออนไลน์</div>
                <div class="text-4xl font-bold text-gray-900 dark:text-white drop-shadow-lg">
                    {{ $stats['active_affiliates'] }}
                </div>
                <div class="text-xs text-gray-700 dark:text-white/90 mt-1">Affiliates</div>
            </div>
        </div>
    </div>

    {{-- Main Stats Grid (4 columns) - Glass Fusion with Blur Glow --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Users --}}
        <a href="{{ route('admin.users.index') }}" class="block group" style="perspective: 1000px;">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                {{-- Blur Glow Effect (Dynamic Theme Colors) --}}
                <div class="absolute inset-0 rounded-2xl blur-xl opacity-60 group-hover:opacity-80 transition-opacity" style="background: var(--arrow-x-secondary-gradient)"></div>

                {{-- Glass Card (V3: Pure Tailwind + Dynamic Colors) --}}
                <div class="relative bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg" style="background: var(--arrow-x-secondary-gradient)">
                            <i class="fas fa-users text-white text-xl"></i>
                        </div>
                        @if($userGrowth != 0)
                            <span class="px-2 py-1 text-white rounded-lg text-xs font-bold shadow-lg" style="background-color: var(--arrow-x-success)">
                                {{ $userGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($userGrowth), 1) }}%
                            </span>
                        @endif
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1 drop-shadow">{{ number_format($stats['total_users']) }}</h3>
                    <p class="text-sm text-gray-700 dark:text-white/90">ผู้ใช้งานทั้งหมด</p>
                </div>
            </div>
        </a>

        {{-- Total MLM Members --}}
        <a href="{{ route('admin.mlm.members.index') }}" class="block group" style="perspective: 1000px;">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                {{-- Blur Glow Effect (Dynamic Theme Colors) --}}
                <div class="absolute inset-0 rounded-2xl blur-xl opacity-60 group-hover:opacity-80 transition-opacity" style="background: var(--arrow-x-primary-gradient)"></div>

                {{-- Glass Card (V3: Dynamic Colors) --}}
                <div class="relative bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg" style="background: var(--arrow-x-primary-gradient)">
                            <i class="fas fa-network-wired text-white text-xl"></i>
                        </div>
                        <span class="px-2 py-1 text-white rounded-lg text-xs font-bold shadow-lg" style="background-color: var(--arrow-x-success)">
                            {{ $stats['active_affiliates'] }} ใช้งาน
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1 drop-shadow">{{ number_format($stats['total_affiliates']) }}</h3>
                    <p class="text-sm text-gray-700 dark:text-white/90">MLM Members</p>
                </div>
            </div>
        </a>

        {{-- Total Revenue --}}
        <a href="{{ route('admin.mlm.commissions.index', ['status' => 'paid']) }}" class="block group" style="perspective: 1000px;">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                {{-- Blur Glow Effect (Dynamic Theme Colors) --}}
                <div class="absolute inset-0 rounded-2xl blur-xl opacity-60 group-hover:opacity-80 transition-opacity" style="background-color: var(--arrow-x-success)"></div>

                {{-- Glass Card (V3: Dynamic Colors) --}}
                <div class="relative bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg" style="background-color: var(--arrow-x-success)">
                            <i class="fas fa-dollar-sign text-white text-xl"></i>
                        </div>
                        @if($revenueGrowth != 0)
                            <span class="px-2 py-1 text-white rounded-lg text-xs font-bold shadow-lg" style="background-color: var(--arrow-x-success)">
                                {{ $revenueGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($revenueGrowth), 1) }}%
                            </span>
                        @endif
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1 drop-shadow">฿{{ number_format($stats['paid_commissions'], 0) }}</h3>
                    <p class="text-sm text-gray-700 dark:text-white/90">รายได้ทั้งหมด</p>
                </div>
            </div>
        </a>

        {{-- Pending Commissions --}}
        <a href="{{ route('admin.mlm.commissions.index', ['status' => 'pending']) }}" class="block group" style="perspective: 1000px;">
            <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                {{-- Blur Glow Effect (Dynamic Theme Colors) --}}
                <div class="absolute inset-0 rounded-2xl blur-xl opacity-60 group-hover:opacity-80 transition-opacity" style="background-color: var(--arrow-x-warning)"></div>

                {{-- Glass Card (V3: Dynamic Colors) --}}
                <div class="relative bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-lg" style="background-color: var(--arrow-x-warning)">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <span class="px-2 py-1 text-white rounded-lg text-xs font-bold shadow-lg" style="background-color: var(--arrow-x-success)">
                            {{ $stats['approved_commissions'] }} อนุมัติ
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white mb-1 drop-shadow">{{ number_format($stats['pending_commissions']) }}</h3>
                    <p class="text-sm text-gray-700 dark:text-white/90">รอดำเนินการ</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Crypto Rates Section - Glass Fusion --}}
    @if(!empty($cryptoRates))
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center drop-shadow-lg">
            <span class="text-3xl mr-3">₿</span>
            ราคาคริปโตปัจจุบัน
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($cryptoRates as $symbol => $rate)
                <a href="{{ route('admin.crypto.currencies') }}" class="block group">
                    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-5 hover:shadow-2xl hover:scale-105 transition-all duration-300">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $symbol }}</span>
                            <span class="text-xs px-3 py-1.5 rounded-lg font-bold shadow-md text-white" style="background-color: var(--arrow-x-{{ $rate['change_24h'] >= 0 ? 'success' : 'error' }})">
                                {{ $rate['change_24h'] >= 0 ? '↗' : '↘' }} {{ number_format(abs($rate['change_24h']), 2) }}%
                            </span>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mb-2 drop-shadow">
                            ฿{{ number_format($rate['price'], 2) }}
                        </p>
                        <p class="text-xs text-gray-600 dark:text-white/80 font-medium">
                            Volume: <span class="text-gray-800 dark:text-white/90 font-bold">฿{{ number_format($rate['volume_24h'], 0) }}</span>
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Quick Stats Grid (4 sections) - Glass Fusion --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Crypto Withdrawals --}}
        <a href="{{ route('admin.crypto.withdrawals') }}" class="block group">
            <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center text-base">
                        <span class="text-2xl mr-2 drop-shadow-lg">💸</span> การถอนเงิน
                    </h3>
                    @if($cryptoWithdrawals['pending'] > 0)
                        <span class="px-3 py-1.5 text-white rounded-lg text-xs font-bold shadow-lg animate-pulse" style="background-color: var(--arrow-x-error)">
                            {{ $cryptoWithdrawals['pending'] }}
                        </span>
                    @endif
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-white/80 font-medium">รอดำเนินการ</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $cryptoWithdrawals['pending'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-white/80 font-medium">ต้องอนุมัติ</span>
                        <span class="font-bold" style="color: var(--arrow-x-warning)">{{ $cryptoWithdrawals['requires_approval'] }}</span>
                    </div>
                    <div class="pt-3 border-t border-gray-300 dark:border-white/30">
                        <div class="text-xs text-gray-600 dark:text-white/80 mb-1">ยอดรอถอน</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white drop-shadow">
                            {{ number_format($cryptoWithdrawals['total_pending_amount'], 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </a>

        {{-- KYC Verifications --}}
        <a href="{{ route('admin.kyc.index') }}" class="block group">
            <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center text-base">
                        <span class="text-2xl mr-2 drop-shadow-lg">🆔</span> KYC
                    </h3>
                    @if($kycStats['pending'] > 0)
                        <span class="px-3 py-1.5 text-white rounded-lg text-xs font-bold shadow-lg" style="background-color: var(--arrow-x-warning)">
                            {{ $kycStats['pending'] }}
                        </span>
                    @endif
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-white/80 font-medium">รอตรวจสอบ</span>
                        <span class="font-bold" style="color: var(--arrow-x-warning)">{{ $kycStats['pending'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-white/80 font-medium">อนุมัติแล้ว</span>
                        <span class="font-bold" style="color: var(--arrow-x-success)">{{ $kycStats['verified'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-white/80 font-medium">ปฏิเสธ</span>
                        <span class="font-bold" style="color: var(--arrow-x-error)">{{ $kycStats['rejected'] }}</span>
                    </div>
                </div>
            </div>
        </a>

        {{-- Crypto Transactions --}}
        <a href="{{ route('admin.crypto.transactions') }}" class="block group">
            <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center text-base">
                        <span class="text-2xl mr-2 drop-shadow-lg">🔄</span> Transactions
                    </h3>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-white/80 font-medium">7 วันล่าสุด</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ number_format($cryptoTransactionsCount) }}</span>
                    </div>
                    @if(!empty($tradingStats))
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-white/80 font-medium">Trading Pairs</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $tradingStats['active_pairs'] ?? 0 }}</span>
                        </div>
                        <div class="pt-3 border-t border-gray-300 dark:border-white/30">
                            <div class="text-xs text-gray-600 dark:text-white/80 mb-1">Volume 24h</div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white drop-shadow">
                                {{ number_format($tradingStats['total_volume_24h'] ?? 0, 2) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </a>

        {{-- Support Tickets --}}
        <a href="{{ route('admin.tickets.index') }}" class="block group">
            <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6 hover:shadow-2xl hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center text-base">
                        <span class="text-2xl mr-2 drop-shadow-lg">🎫</span> Tickets
                    </h3>
                    @if($ticketStats['new_today'] > 0)
                        <span class="px-3 py-1.5 text-white rounded-lg text-xs font-bold shadow-lg animate-pulse" style="background: var(--arrow-x-primary-gradient)">
                            {{ $ticketStats['new_today'] }} ใหม่
                        </span>
                    @endif
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-white/80 font-medium">เปิดอยู่</span>
                        <span class="font-bold" style="color: var(--arrow-x-info)">{{ $ticketStats['open'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-white/80 font-medium">ไม่มีผู้รับผิดชอบ</span>
                        <span class="font-bold" style="color: var(--arrow-x-warning)">{{ $ticketStats['unassigned'] }}</span>
                    </div>
                    <div class="pt-3 border-t border-gray-300 dark:border-white/30">
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-gray-600 dark:text-white/80">🔥 Priority สูง</span>
                            <span class="text-2xl font-bold" style="color: var(--arrow-x-error)">{{ $ticketStats['high_priority'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Revenue Trend --}}
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/30" style="background-color: color-mix(in srgb, var(--arrow-x-primary-start) 15%, transparent)">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 drop-shadow">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center shadow-lg" style="background: var(--arrow-x-primary-gradient)">
                        <i class="fas fa-chart-area text-white"></i>
                    </div>
                    รายได้รายเดือน
                </h3>
                <span class="text-xs px-2 py-1 text-white rounded-md font-semibold mt-2 inline-block shadow-lg" style="background-color: var(--arrow-x-success)">
                    ฿{{ number_format($monthlyRevenue->sum('total'), 0) }}
                </span>
            </div>
            <div class="p-6">
                <div class="h-[200px]">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Commission Status --}}
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/30" style="background-color: color-mix(in srgb, var(--arrow-x-success) 15%, transparent)">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 drop-shadow">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center shadow-lg" style="background-color: var(--arrow-x-success)">
                        <i class="fas fa-clipboard-list text-white"></i>
                    </div>
                    สถานะคอมมิชชั่น
                </h3>
            </div>
            <div class="p-6 grid grid-cols-2 gap-3">
                <div class="flex items-center justify-center h-[200px]">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" style="background-color: var(--arrow-x-warning)"></div>
                            <span class="text-gray-700 dark:text-white/90">รอ</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $commissionStatus['pending'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" style="background-color: var(--arrow-x-success)"></div>
                            <span class="text-gray-700 dark:text-white/90">อนุมัติ</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $commissionStatus['approved'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" style="background-color: var(--arrow-x-info)"></div>
                            <span class="text-gray-700 dark:text-white/90">จ่าย</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $commissionStatus['paid'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full" style="background-color: var(--arrow-x-error)"></div>
                            <span class="text-gray-700 dark:text-white/90">ปฏิเสธ</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $commissionStatus['rejected'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Bottom Section: Top Affiliates, Recent Activity & Recent Tickets --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Top Affiliates --}}
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/30">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 drop-shadow">
                    🏆 Top Affiliates
                </h3>
            </div>
            <div class="p-4 space-y-2">
                @forelse($topAffiliates->take(5) as $index => $mlmMember)
                    <div class="flex items-center gap-3 p-3 bg-white/10 dark:bg-white/5 backdrop-blur-sm rounded-lg border border-white/20 hover:bg-white/20 dark:hover:bg-white/10 transition-all cursor-pointer">
                        <div class="flex-shrink-0">
                            @if($index === 0)
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center shadow-lg" style="background-color: var(--arrow-x-warning)">
                                    🥇
                                </div>
                            @elseif($index === 1)
                                <div class="w-10 h-10 bg-gradient-to-br from-gray-300 to-gray-500 rounded-lg flex items-center justify-center shadow-lg">
                                    🥈
                                </div>
                            @elseif($index === 2)
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center shadow-lg" style="background-color: var(--arrow-x-warning)">
                                    🥉
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-lg" style="background: var(--arrow-x-primary-gradient)">
                                    {{ $index + 1 }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $mlmMember->user->name }}</p>
                            <p class="text-xs text-gray-600 dark:text-white/80 truncate">{{ $mlmMember->total_direct_referrals }} refs</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold" style="color: var(--arrow-x-success)">฿{{ number_format($mlmMember->total_earnings ?? 0, 0) }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-600 dark:text-white/80 py-4 text-sm">
                        ยังไม่มีข้อมูล
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/30">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 drop-shadow">
                    🔔 กิจกรรมล่าสุด
                </h3>
            </div>
            <div class="p-4 space-y-2 max-h-80 overflow-y-auto">
                @forelse($recentCommissions->take(5) as $commission)
                    <div class="flex items-start gap-3 p-3 bg-white/10 dark:bg-white/5 backdrop-blur-sm rounded-lg border border-white/20 hover:bg-white/20 dark:hover:bg-white/10 transition-all cursor-pointer">
                        <img src="{{ $commission->affiliate->user->profile_picture_url }}"
                             alt="{{ $commission->affiliate->user->name }}"
                             class="w-10 h-10 rounded-lg object-cover flex-shrink-0 shadow-lg">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                {{ $commission->affiliate->user->name }}
                            </p>
                            <p class="text-xs text-gray-600 dark:text-white/80">
                                <span class="font-semibold" style="color: var(--arrow-x-success)">฿{{ number_format($commission->amount, 2) }}</span>
                            </p>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0 text-white"
                            style="background-color: var(--arrow-x-{{
                                $commission->status === 'pending' ? 'warning' :
                                ($commission->status === 'approved' ? 'success' :
                                ($commission->status === 'paid' ? 'info' : 'error'))
                            }})">
                            {{ ucfirst($commission->status) }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-gray-600 dark:text-white/80 py-4 text-sm">
                        ยังไม่มีกิจกรรม
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Recent Tickets --}}
        <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-white/30">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2 drop-shadow">
                    🎫 Tickets ล่าสุด
                </h3>
            </div>
            <div class="p-4 space-y-2 max-h-80 overflow-y-auto">
                @forelse($recentTickets as $ticket)
                    <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="block p-3 bg-white/10 dark:bg-white/5 backdrop-blur-sm rounded-lg border border-white/20 hover:bg-white/20 dark:hover:bg-white/10 transition-all">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 rounded-lg flex items-center justify-center shadow-lg
                                {{ $ticket->priority === 'critical' ? 'bg-error' : '' }}
                                {{ $ticket->priority === 'high' ? 'bg-warning' : '' }}
                                {{ $ticket->priority === 'medium' ? 'bg-info' : '' }}
                                {{ $ticket->priority === 'low' ? 'bg-gradient-to-br from-gray-500 to-gray-600' : '' }}">
                                <span class="text-xs font-bold text-white">
                                    {{ strtoupper(substr($ticket->priority, 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">
                                    #{{ $ticket->ticket_number }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-white/80 truncate">
                                    {{ $ticket->subject }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-white/70 mt-1">
                                    {{ $ticket->user->name }} • {{ $ticket->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0 text-white"
                                    style="background-color: {{
                                        $ticket->status === 'open' ? 'var(--arrow-x-info)' :
                                        ($ticket->status === 'in_progress' ? 'var(--arrow-x-warning)' :
                                        ($ticket->status === 'waiting_customer' ? 'var(--arrow-x-accent)' :
                                        ($ticket->status === 'resolved' ? 'var(--arrow-x-success)' : '#6b7280')))
                                    }}">
                                    {{ $ticket->status_label }}
                                </span>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center text-gray-600 dark:text-white/80 py-4 text-sm">
                        ยังไม่มี Tickets
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white/85 dark:bg-white/15 backdrop-blur-xl border border-black/5 dark:border-white/30 rounded-2xl shadow-xl p-6">
        <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white drop-shadow">⚡ การกระทำด่วน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <a href="{{ route('admin.users.create') }}" class="bg-white/10 dark:bg-white/5 backdrop-blur-sm hover:bg-white/20 dark:hover:bg-white/10 rounded-lg p-4 text-center transition-all border border-white/20 text-gray-900 dark:text-white hover:scale-105">
                <div class="text-3xl mb-2">➕</div>
                <p class="text-xs font-semibold">เพิ่มผู้ใช้</p>
            </a>
            <a href="{{ route('admin.commissions.index', ['status' => 'pending']) }}" class="bg-white/10 dark:bg-white/5 backdrop-blur-sm hover:bg-white/20 dark:hover:bg-white/10 rounded-lg p-4 text-center transition-all border border-white/20 text-gray-900 dark:text-white hover:scale-105">
                <div class="text-3xl mb-2">✅</div>
                <p class="text-xs font-semibold">อนุมัติคอมมิชชั่น</p>
            </a>
            <a href="{{ route('admin.crypto.withdrawals') }}" class="bg-white/10 dark:bg-white/5 backdrop-blur-sm hover:bg-white/20 dark:hover:bg-white/10 rounded-lg p-4 text-center transition-all border border-white/20 text-gray-900 dark:text-white hover:scale-105">
                <div class="text-3xl mb-2">💸</div>
                <p class="text-xs font-semibold">การถอนเงิน</p>
            </a>
            <a href="{{ route('admin.tickets.index') }}" class="bg-white/10 dark:bg-white/5 backdrop-blur-sm hover:bg-white/20 dark:hover:bg-white/10 rounded-lg p-4 text-center transition-all border border-white/20 text-gray-900 dark:text-white hover:scale-105 relative">
                @if($ticketStats['new_today'] > 0)
                    <span class="absolute -top-1 -right-1 flex h-5 w-5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" style="background-color: var(--arrow-x-error)"></span>
                        <span class="relative inline-flex rounded-full h-5 w-5 text-white text-xs items-center justify-center font-bold" style="background-color: var(--arrow-x-error)">{{ $ticketStats['new_today'] }}</span>
                    </span>
                @endif
                <div class="text-3xl mb-2">🎫</div>
                <p class="text-xs font-semibold">Tickets</p>
            </a>
            <a href="{{ route('admin.kyc.index') }}" class="bg-white/10 dark:bg-white/5 backdrop-blur-sm hover:bg-white/20 dark:hover:bg-white/10 rounded-lg p-4 text-center transition-all border border-white/20 text-gray-900 dark:text-white hover:scale-105">
                <div class="text-3xl mb-2">🆔</div>
                <p class="text-xs font-semibold">ตรวจสอบ KYC</p>
            </a>
            <a href="{{ route('admin.affiliates.tree') }}" class="bg-white/10 dark:bg-white/5 backdrop-blur-sm hover:bg-white/20 dark:hover:bg-white/10 rounded-lg p-4 text-center transition-all border border-white/20 text-gray-900 dark:text-white hover:scale-105">
                <div class="text-3xl mb-2">🌳</div>
                <p class="text-xs font-semibold">Affiliate Tree</p>
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: {!! json_encode($monthlyRevenue->pluck('month')) !!},
            datasets: [{
                label: 'รายได้ (฿)',
                data: {!! json_encode($monthlyRevenue->pluck('total')) !!},
                borderColor: 'rgba(99, 102, 241, 0.9)',
                backgroundColor: 'rgba(99, 102, 241, 0.2)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 4,
                pointBackgroundColor: 'rgba(99, 102, 241, 0.9)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverRadius: 6
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
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
                        color: 'rgba(148, 163, 184, 0.1)'
                    },
                    ticks: {
                        color: 'rgba(100, 116, 139, 0.9)',
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
                        color: 'rgba(100, 116, 139, 0.9)',
                        maxTicksLimit: 6
                    }
                }
            }
        }
    });
}

// Status Donut Chart
const statusCtx = document.getElementById('statusChart');
if (statusCtx) {
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
                    'rgba(234, 179, 8, 0.9)',
                    'rgba(34, 197, 94, 0.9)',
                    'rgba(59, 130, 246, 0.9)',
                    'rgba(239, 68, 68, 0.9)'
                ],
                borderWidth: 2,
                borderColor: 'rgba(255, 255, 255, 0.3)'
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
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
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
