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
    {{-- Premium Hero Header - seller/dashboard Style --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-red-600 dark:from-purple-800 dark:via-pink-800 dark:to-red-800 rounded-2xl shadow-2xl p-8">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
            <div class="absolute top-1/2 left-1/3 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
        </div>

        {{-- Floating Icons --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="absolute text-white/10 text-6xl bottom-10 right-40" style="animation: float 6s ease-in-out infinite; animation-delay: 0.3s">
                <i class="fas fa-users"></i>
            </div>
            <div class="absolute text-white/10 text-7xl top-20 left-1/4" style="animation: float 6s ease-in-out infinite; animation-delay: 0.5s">
                <i class="fas fa-network-wired"></i>
            </div>
        </div>

        {{-- Header Content --}}
        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                {{-- Title Section --}}
                <div class="flex-1">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="glass-fusion p-4 rounded-2xl">
                            <i class="fas fa-tachometer-alt text-4xl text-white drop-shadow-lg"></i>
                        </div>
                        <div>
                            <h1 class="text-4xl font-bold text-white drop-shadow-lg">สวัสดี, {{ Auth::user()->name }} 👋</h1>
                            <p class="text-purple-100 text-lg mt-1 flex items-center gap-2">
                                <i class="fas fa-clock"></i>
                                ภาพรวมระบบ - {{ now()->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Stats Badge --}}
                <div class="glass-fusion px-6 py-4 rounded-xl border border-white/30">
                    <div class="text-center">
                        <div class="text-xs text-purple-100 mb-1">🟢 Affiliates ออนไลน์</div>
                        <div class="text-4xl font-bold text-white drop-shadow-lg">{{ $stats['active_affiliates'] }}</div>
                        <div class="text-xs text-purple-100 mt-1">จากทั้งหมด {{ number_format($stats['total_affiliates']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Stats Grid (4 columns) - seller/dashboard Style with Theme Conditional --}}
    <div x-data="{ isClassicTheme: false }"
         x-init="
             // ฟังก์ชันตรวจจับ Classic Theme (เช็ค 2 แหล่ง)
             const checkClassic = () => {
                 // วิธีที่ 1: เช็ค body class (ที่ theme-customizer เพิ่มไว้)
                 if (document.body.classList.contains('theme-classic')) {
                     isClassicTheme = true;
                     console.log('✅ Classic Theme detected via body class');
                     return;
                 }

                 // วิธีที่ 2: เช็ค localStorage (fallback)
                 const saved = localStorage.getItem('themeSettings');
                 if (saved) {
                     try {
                         const settings = JSON.parse(saved);
                         // Classic = glassOpacity และ glassBlur เป็น 0 (ทั้ง number และ string)
                         const glassOp = parseInt(settings.glassOpacity);
                         const glassBlur = parseInt(settings.glassBlur);
                         isClassicTheme = (glassOp === 0 && glassBlur === 0);
                         console.log('🔍 Theme check - glassOpacity:', glassOp, 'glassBlur:', glassBlur, 'isClassic:', isClassicTheme);
                     } catch (e) {
                         console.error('Theme settings parse error:', e);
                         isClassicTheme = false;
                     }
                 } else {
                     isClassicTheme = false;
                     console.log('ℹ️ No theme settings found, using default (not Classic)');
                 }
             };

             // เรียกครั้งแรก
             checkClassic();

             // เช็คซ้ำทุก 500ms
             setInterval(checkClassic, 500);

             // เช็คเมื่อ localStorage เปลี่ยน
             window.addEventListener('storage', checkClassic);

             // เช็คเมื่อมี mutation ที่ body class
             const observer = new MutationObserver(checkClassic);
             observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
         ">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-bar" style="color: var(--arrow-x-primary-start)"></i>
            สถิติภาพรวม
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Users --}}
            <a href="{{ route('admin.users.index') }}" class="block group rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden"
               :class="isClassicTheme
                   ? 'bg-gradient-to-br from-blue-500 to-indigo-600 dark:from-blue-600 dark:to-indigo-800'
                   : 'bg-white dark:bg-slate-800'">
                {{-- Classic: Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10" x-show="isClassicTheme">
                    <i class="fas fa-users text-9xl text-white"></i>
                </div>
                {{-- Modern: Gradient Icon --}}
                <div class="flex items-center gap-3" x-show="!isClassicTheme">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">ผู้ใช้ทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_users']) }}</p>
                    </div>
                </div>
                {{-- Classic: Content --}}
                <div class="relative z-10" x-show="isClassicTheme">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">👥</span>
                        @if($userGrowth != 0)
                            <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">
                                {{ $userGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($userGrowth), 1) }}%
                            </span>
                        @endif
                    </div>
                    <p class="text-blue-100 text-sm mb-1">ผู้ใช้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($stats['total_users']) }}</p>
                    <p class="text-xs text-blue-100 mt-2">Total Users</p>
                </div>
            </a>

            {{-- Total MLM Members --}}
            <a href="{{ route('admin.mlm.members.index') }}" class="block group rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden"
               :class="isClassicTheme
                   ? 'bg-gradient-to-br from-purple-500 to-pink-600 dark:from-purple-600 dark:to-pink-800'
                   : 'bg-white dark:bg-slate-800'">
                {{-- Classic: Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10" x-show="isClassicTheme">
                    <i class="fas fa-network-wired text-9xl text-white"></i>
                </div>
                {{-- Modern: Gradient Icon --}}
                <div class="flex items-center gap-3" x-show="!isClassicTheme">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-pink-600 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                        <i class="fas fa-network-wired text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">MLM Members</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_affiliates']) }}</p>
                    </div>
                </div>
                {{-- Classic: Content --}}
                <div class="relative z-10" x-show="isClassicTheme">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">🌐</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">
                            {{ $stats['active_affiliates'] }} ใช้งาน
                        </span>
                    </div>
                    <p class="text-purple-100 text-sm mb-1">MLM Members</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($stats['total_affiliates']) }}</p>
                    <p class="text-xs text-purple-100 mt-2">Affiliate Network</p>
                </div>
            </a>

            {{-- Total Revenue --}}
            <a href="{{ route('admin.mlm.commissions.index', ['status' => 'paid']) }}" class="block group rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden"
               :class="isClassicTheme
                   ? 'bg-gradient-to-br from-green-500 to-emerald-700 dark:from-green-600 dark:to-emerald-900'
                   : 'bg-white dark:bg-slate-800'">
                {{-- Classic: Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10" x-show="isClassicTheme">
                    <i class="fas fa-dollar-sign text-9xl text-white"></i>
                </div>
                {{-- Modern: Gradient Icon --}}
                <div class="flex items-center gap-3" x-show="!isClassicTheme">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-600 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                        <i class="fas fa-dollar-sign text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">รายได้ทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['paid_commissions'], 0) }}</p>
                    </div>
                </div>
                {{-- Classic: Content --}}
                <div class="relative z-10" x-show="isClassicTheme">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">💰</span>
                        @if($revenueGrowth != 0)
                            <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">
                                {{ $revenueGrowth > 0 ? '↑' : '↓' }} {{ number_format(abs($revenueGrowth), 1) }}%
                            </span>
                        @endif
                    </div>
                    <p class="text-green-100 text-sm mb-1">รายได้ทั้งหมด</p>
                    <p class="text-3xl font-bold text-white">฿{{ number_format($stats['paid_commissions'], 0) }}</p>
                    <p class="text-xs text-green-100 mt-2">Total Revenue</p>
                </div>
            </a>

            {{-- Pending Commissions --}}
            <a href="{{ route('admin.mlm.commissions.index', ['status' => 'pending']) }}" class="block group rounded-2xl shadow-xl p-6 transform hover:scale-105 transition-all duration-300 cursor-pointer relative overflow-hidden"
               :class="isClassicTheme
                   ? 'bg-gradient-to-br from-orange-500 to-red-600 dark:from-orange-600 dark:to-red-800'
                   : 'bg-white dark:bg-slate-800'">
                {{-- Classic: Background Icon --}}
                <div class="absolute -right-8 -top-8 opacity-10" x-show="isClassicTheme">
                    <i class="fas fa-chart-line text-9xl text-white"></i>
                </div>
                {{-- Modern: Gradient Icon --}}
                <div class="flex items-center gap-3" x-show="!isClassicTheme">
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-400 to-red-600 rounded-lg flex items-center justify-center text-2xl shadow-lg">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-gray-500 dark:text-gray-400 text-sm">รอดำเนินการ</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['pending_commissions']) }}</p>
                    </div>
                </div>
                {{-- Classic: Content --}}
                <div class="relative z-10" x-show="isClassicTheme">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-5xl">⏳</span>
                        <span class="px-2 py-1 bg-white/20 rounded-lg text-xs font-semibold text-white">
                            {{ $stats['approved_commissions'] }} อนุมัติ
                        </span>
                    </div>
                    <p class="text-orange-100 text-sm mb-1">รอดำเนินการ</p>
                    <p class="text-3xl font-bold text-white">{{ number_format($stats['pending_commissions']) }}</p>
                    <p class="text-xs text-orange-100 mt-2">Pending</p>
                </div>
            </a>
        </div>
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
                        {{-- ใช้ component เพื่อความสอดคล้องทั้งระบบ --}}
                        <x-user-avatar :user="$commission->affiliate->user" size="md" :ring="false" class="flex-shrink-0 shadow-lg" />
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

    {{-- Quick Actions - seller/dashboard Style --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-bolt" style="color: var(--arrow-x-warning)"></i>
            การดำเนินการด่วน
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <a href="{{ route('admin.users.create') }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">➕</span>
                <span class="text-sm font-semibold text-center">เพิ่มผู้ใช้</span>
            </a>

            <a href="{{ route('admin.commissions.index', ['status' => 'pending']) }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">✅</span>
                <span class="text-sm font-semibold text-center">อนุมัติคอมมิชชั่น</span>
            </a>

            <a href="{{ route('admin.crypto.withdrawals') }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">💸</span>
                <span class="text-sm font-semibold text-center">การถอนเงิน</span>
            </a>

            <a href="{{ route('admin.tickets.index') }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl hover:scale-105 transition shadow-lg relative">
                @if($ticketStats['new_today'] > 0)
                    <span class="absolute -top-1 -right-1 flex h-5 w-5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-500 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-5 w-5 bg-red-500 text-white text-xs items-center justify-center font-bold">{{ $ticketStats['new_today'] }}</span>
                    </span>
                @endif
                <span class="text-3xl mb-2">🎫</span>
                <span class="text-sm font-semibold text-center">Tickets</span>
            </a>

            <a href="{{ route('admin.kyc.index') }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-pink-500 to-pink-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">🆔</span>
                <span class="text-sm font-semibold text-center">ตรวจสอบ KYC</span>
            </a>

            <a href="{{ route('admin.affiliates.tree') }}" class="flex flex-col items-center justify-center p-4 bg-gradient-to-br from-cyan-500 to-cyan-600 text-white rounded-xl hover:scale-105 transition shadow-lg">
                <span class="text-3xl mb-2">🌳</span>
                <span class="text-sm font-semibold text-center">Affiliate Tree</span>
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
