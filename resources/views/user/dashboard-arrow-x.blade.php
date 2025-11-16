@extends('layouts.user')

@section('title', 'แดชบอร์ด')

@section('content')
{{--
/**
 * User Dashboard - Arrow X Theme
 *
 * แดชบอร์ดผู้ใช้แบบ Arrow X Theme พร้อม:
 * - Glass Fusion Effect
 * - 3D Stats Cards
 * - Animated Charts
 * - Real-time Updates
 * - Rank Progress System
 * - Activity Timeline
 * - Quick Actions
 *
 * @version 3.0.0
 * @theme Arrow X
 */
--}}

<div class="space-y-6 pb-20 lg:pb-6" x-data="userDashboard()" x-init="init()">
    {{-- Welcome Section with Profile - Arrow X Style --}}
    <div class="relative glass-fusion-card rounded-3xl p-6 md:p-8 overflow-hidden shadow-2xl"
         style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(236, 72, 153, 0.15));">

        {{-- Gradient Mesh Background --}}
        <div class="absolute inset-0 opacity-30">
            <div class="absolute top-0 left-0 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-0 left-1/2 w-96 h-96 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                {{-- Profile Avatar with 3D Effect --}}
                <div class="relative group">
                    @php
                        $rankLevel = $user->currentRank->level ?? 0;
                        $rankColor = match(true) {
                            $rankLevel >= 7 => 'from-purple-500 to-pink-500',
                            $rankLevel >= 5 => 'from-yellow-400 to-orange-500',
                            $rankLevel >= 3 => 'from-blue-500 to-cyan-500',
                            $rankLevel >= 1 => 'from-green-500 to-teal-500',
                            default => 'from-gray-400 to-gray-600',
                        };
                        $rankStars = match(true) {
                            $rankLevel >= 7 => 5,
                            $rankLevel >= 5 => 4,
                            $rankLevel >= 3 => 3,
                            $rankLevel >= 2 => 2,
                            $rankLevel >= 1 => 1,
                            default => 0,
                        };
                    @endphp

                    <div class="relative w-28 h-28 md:w-32 md:h-32 transform transition-transform duration-300 group-hover:scale-110">
                        {{-- 3D Ring Effect --}}
                        <div class="absolute inset-0 bg-gradient-to-br {{ $rankColor }} rounded-full p-1 shadow-2xl animate-spin-slow">
                            <div class="w-full h-full bg-white dark:bg-gray-800 rounded-full p-1">
                                <img src="{{ $user->profile_picture_url }}"
                                     alt="{{ $user->name }}"
                                     class="w-full h-full object-cover rounded-full ring-4 ring-white dark:ring-gray-700">
                            </div>
                        </div>

                        {{-- Rank Stars --}}
                        @if($rankStars > 0)
                            <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 flex gap-0.5">
                                @for ($i = 0; $i < $rankStars; $i++)
                                    <div class="text-yellow-400 text-base drop-shadow-lg animate-pulse" style="animation-delay: {{ $i * 100 }}ms">⭐</div>
                                @endfor
                            </div>
                        @endif

                        {{-- KYC Badge --}}
                        <div class="absolute -top-1 -right-1 z-10">
                            @if($user->kyc_status === 'approved')
                                <div class="w-9 h-9 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center border-3 border-white dark:border-gray-800 shadow-lg transform hover:scale-110 transition" title="ยืนยันตัวตนแล้ว">
                                    <i class="fas fa-check-double text-white text-sm"></i>
                                </div>
                            @elseif($user->kyc_status === 'pending')
                                <div class="w-9 h-9 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center border-3 border-white dark:border-gray-800 shadow-lg animate-pulse" title="รอการตรวจสอบ">
                                    <i class="fas fa-clock text-white text-sm"></i>
                                </div>
                            @else
                                <a href="{{ route('user.kyc.create') }}"
                                   class="w-9 h-9 bg-gradient-to-br from-red-400 to-pink-500 rounded-full flex items-center justify-center border-3 border-white dark:border-gray-800 shadow-lg hover:scale-110 transition"
                                   title="คลิกเพื่อยืนยันตัวตน">
                                    <i class="fas fa-exclamation text-white text-sm"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Welcome Text --}}
                <div class="flex-1">
                    <div class="flex flex-col md:flex-row items-start md:items-center gap-3 mb-2">
                        <h1 class="text-3xl md:text-4xl font-extrabold bg-gradient-to-r from-purple-600 via-pink-600 to-orange-600 bg-clip-text text-transparent dark:from-purple-400 dark:via-pink-400 dark:to-orange-400">
                            👋 สวัสดี, {{ $user->name }}!
                        </h1>
                        @if($user->currentRank)
                            <span class="inline-flex items-center px-4 py-1.5 bg-white/20 dark:bg-gray-800/30 backdrop-blur-md rounded-full text-sm font-semibold text-gray-900 dark:text-white border border-white/30">
                                <i class="fas fa-crown text-yellow-400 mr-2"></i>
                                {{ $user->currentRank->name }}
                            </span>
                        @endif
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 text-lg font-medium">
                        ยินดีต้อนรับสู่ Dashboard แบบ Arrow X • {{ now()->locale('th')->translatedFormat('j F Y') }}
                    </p>

                    {{-- Member Number --}}
                    @if($user->member_number)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-medium">รหัสสมาชิก:</span>
                            <span class="font-mono text-purple-600 dark:text-purple-400">{{ $user->member_number }}</span>
                        </p>
                    @endif
                </div>

                {{-- Quick Stats Summary --}}
                <div class="hidden lg:flex items-center gap-4">
                    <div class="text-center px-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_referrals'] ?? 0) }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">ลูกทีม</div>
                    </div>
                    <div class="w-px h-12 bg-gray-300 dark:bg-gray-600"></div>
                    <div class="text-center px-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">฿{{ number_format($stats['total_earnings'] ?? 0, 2) }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">รายได้สะสม</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Cards - Arrow X 3D Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Wallet Balance Card --}}
        <x-arrow-x.stats.card-3d
            :value="'฿' . number_format($stats['wallet_balance'] ?? 0, 2)"
            label="ยอดเงินคงเหลือ"
            icon="fas fa-wallet"
            gradient="from-purple-500 to-pink-600"
            :change="$stats['wallet_change'] ?? 0"
            href="{{ route('user.wallet.index') }}"
        />

        {{-- Commission Card --}}
        <x-arrow-x.stats.card-3d
            :value="'฿' . number_format($stats['pending_commission'] ?? 0, 2)"
            label="คอมมิชชั่นรอรับ"
            icon="fas fa-money-bill-wave"
            gradient="from-blue-500 to-cyan-600"
            :change="$stats['commission_change'] ?? 0"
            href="{{ route('user.commissions.index') }}"
        />

        {{-- Referrals Card --}}
        <x-arrow-x.stats.card-3d
            :value="number_format($stats['total_referrals'] ?? 0)"
            label="ลูกทีมทั้งหมด"
            icon="fas fa-users"
            gradient="from-green-500 to-teal-600"
            :change="$stats['referrals_change'] ?? 0"
            href="{{ route('user.affiliates.team') }}"
        />

        {{-- Rank Points Card --}}
        <x-arrow-x.stats.card-3d
            :value="number_format($user->rank_points ?? 0)"
            label="คะแนน Rank"
            icon="fas fa-star"
            gradient="from-orange-500 to-red-600"
            :change="$stats['points_change'] ?? 0"
            href="{{ route('user.rank.progress') }}"
        />
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column (2 cols) --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Revenue Chart --}}
            <x-arrow-x.charts.line
                id="revenue-chart"
                title="สถิติรายได้รายเดือน"
                subtitle="แสดงข้อมูล 6 เดือนล่าสุด"
                gradient="from-blue-500 to-purple-600"
                height="300"
            />

            {{-- Quick Actions --}}
            <div class="glass-fusion-card rounded-2xl p-6 shadow-lg">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                    <i class="fas fa-bolt text-yellow-500 mr-3"></i>
                    Quick Actions
                </h3>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    {{-- Withdraw --}}
                    <a href="{{ route('user.wallet.withdraw') }}"
                       class="group flex flex-col items-center p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-hand-holding-usd text-white"></i>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">ถอนเงิน</span>
                    </a>

                    {{-- Invite --}}
                    <a href="{{ route('user.affiliates.invite') }}"
                       class="group flex flex-col items-center p-4 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-plus text-white"></i>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">เชิญเพื่อน</span>
                    </a>

                    {{-- Profile --}}
                    <a href="{{ route('user.profile.edit') }}"
                       class="group flex flex-col items-center p-4 bg-gradient-to-br from-green-50 to-teal-50 dark:from-green-900/20 dark:to-teal-900/20 rounded-xl hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-500 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-edit text-white"></i>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">แก้ไขโปรไฟล์</span>
                    </a>

                    {{-- Support --}}
                    <a href="{{ route('user.support') }}"
                       class="group flex flex-col items-center p-4 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas fa-headset text-white"></i>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">ติดต่อซัพพอร์ต</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Right Column (1 col) --}}
        <div class="space-y-6">
            {{-- Rank Progress --}}
            @if($user->currentRank || $nextRank)
                <div class="glass-fusion-card rounded-2xl p-6 shadow-lg">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                        ความคืบหน้า Rank
                    </h3>

                    {{-- Current Rank --}}
                    @if($user->currentRank)
                        <div class="mb-4 p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Rank ปัจจุบัน</div>
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->currentRank->name }}</div>
                                </div>
                                <div class="text-3xl">{{ $user->currentRank->icon ?? '🏆' }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- Progress to Next Rank --}}
                    @if($nextRank)
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    ถัดไป: {{ $nextRank->name }}
                                </span>
                                <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                    {{ number_format($rankProgress) }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all duration-500"
                                     style="width: {{ $rankProgress }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                                ต้องการอีก {{ number_format($nextRank->required_points - $user->rank_points) }} คะแนน
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Activity Feed --}}
            <x-arrow-x.activity.list
                title="กิจกรรมล่าสุด"
                icon="fas fa-history"
                max-height="400"
            >
                @forelse($recentActivities ?? [] as $activity)
                    <x-arrow-x.activity.item
                        :icon="$activity->icon"
                        :iconColor="$activity->iconColor"
                        :title="$activity->title"
                        :description="$activity->description"
                        :time="$activity->created_at->diffForHumans()"
                    />
                @empty
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-2 opacity-50"></i>
                        <p class="text-sm">ยังไม่มีกิจกรรม</p>
                    </div>
                @endforelse
            </x-arrow-x.activity.list>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * User Dashboard Alpine.js Component
 *
 * จัดการ state และ logic สำหรับ Dashboard
 */
function userDashboard() {
    return {
        loading: true,
        stats: @json($stats ?? []),
        revenueChart: null,

        /**
         * เริ่มต้น component
         */
        init() {
            this.initRevenueChart();
            this.loading = false;
        },

        /**
         * สร้างกราฟรายได้
         */
        initRevenueChart() {
            const ctx = document.getElementById('revenue-chart');
            if (!ctx) return;

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            const textColor = isDark ? 'rgba(255, 255, 255, 0.7)' : 'rgba(0, 0, 0, 0.7)';

            this.revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartData['labels'] ?? []),
                    datasets: [{
                        label: 'รายได้',
                        data: @json($chartData['values'] ?? []),
                        borderColor: '#8B5CF6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointBackgroundColor: '#8B5CF6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
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
                            backgroundColor: isDark ? 'rgba(17, 24, 39, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                            titleColor: isDark ? '#fff' : '#000',
                            bodyColor: isDark ? '#fff' : '#000',
                            borderColor: '#8B5CF6',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return '฿' + context.parsed.y.toLocaleString('th-TH', {
                                        minimumFractionDigits: 2,
                                        maximumFractionDigits: 2
                                    });
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: textColor,
                                callback: function(value) {
                                    return '฿' + value.toLocaleString('th-TH');
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: textColor
                            }
                        }
                    }
                }
            });
        }
    };
}
</script>

@push('styles')
<style>
/**
 * Arrow X Dashboard Styles
 *
 * Custom styles สำหรับ Dashboard
 */

/* Blob Animation */
@keyframes blob {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

/* Spin Slow Animation */
@keyframes spin-slow {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.animate-spin-slow {
    animation: spin-slow 8s linear infinite;
}

/* Glass Fusion Card */
.glass-fusion-card {
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.25) 0%,
        rgba(255, 255, 255, 0.15) 100%
    );
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.dark .glass-fusion-card {
    background: linear-gradient(
        135deg,
        rgba(31, 41, 55, 0.8) 0%,
        rgba(17, 24, 39, 0.7) 100%
    );
    border-color: rgba(75, 85, 99, 0.5);
}
</style>
@endpush
@endsection
