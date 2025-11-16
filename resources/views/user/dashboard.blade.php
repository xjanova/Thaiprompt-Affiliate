@extends('layouts.user-arrow-x')

@section('title', 'แดชบอร์ด')

@section('content')
{{--
/**
 * User Dashboard - Version 3.0 (Redesigned)
 *
 * ออกแบบใหม่ทั้งหมดด้วย:
 * - Tailwind CSS Pure (ไม่มี Bootstrap)
 * - Alpine.js (ไม่มี jQuery)
 * - Dark Mode Support
 * - Mobile-first Responsive
 * - Error-free Guaranteed
 *
 * @version 3.0.0
 * @date 2025-11-16
 */
--}}

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- ======================================
            1. KYC Alert (ถ้ายังไม่ทำ KYC)
        ====================================== --}}
        @if($showKycAlert)
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-2xl p-6 border border-yellow-200 dark:border-yellow-800 shadow-lg">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-xl"></i>
                    </div>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                        ยืนยันตัวตนเพื่อปลดล็อกฟีเจอร์ทั้งหมด
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        ทำการยืนยันตัวตน (KYC) เพื่อถอนเงิน และใช้งานฟีเจอร์เต็มรูปแบบ
                    </p>
                    <a href="{{ route('user.kyc.create') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-xl transition-colors duration-200 shadow-lg hover:shadow-xl">
                        <i class="fas fa-id-card"></i>
                        <span>ยืนยันตัวตนตอนนี้</span>
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- ======================================
            2. Welcome Section
        ====================================== --}}
        <div class="bg-gradient-to-br from-purple-600 to-pink-600 rounded-3xl p-8 text-white shadow-2xl overflow-hidden relative">
            {{-- Background Pattern --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-0 left-0 w-64 h-64 bg-white rounded-full filter blur-3xl"></div>
                <div class="absolute bottom-0 right-0 w-64 h-64 bg-white rounded-full filter blur-3xl"></div>
            </div>

            <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
                {{-- Avatar --}}
                <div class="flex-shrink-0">
                    <div class="w-24 h-24 rounded-full bg-white/20 backdrop-blur-lg p-1">
                        <img src="{{ $user->profile_picture_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=random' }}"
                             alt="{{ $user->name }}"
                             class="w-full h-full rounded-full object-cover">
                    </div>
                </div>

                {{-- Welcome Text --}}
                <div class="flex-1 text-center md:text-left">
                    <div class="text-white/80 text-sm mb-1">ยินดีต้อนรับกลับมา</div>
                    <h1 class="text-3xl md:text-4xl font-bold mb-2">{{ $user->name }}</h1>
                    @if($currentRank)
                    <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg rounded-full px-4 py-2">
                        <span class="text-white/80 text-sm">Rank:</span>
                        <span class="font-bold">{{ $currentRank->name_th ?? $currentRank->name }}</span>
                    </div>
                    @endif
                </div>

                {{-- Member Number --}}
                @if($user->member_number)
                <div class="flex-shrink-0 text-center md:text-right">
                    <div class="text-white/60 text-xs mb-1">รหัสสมาชิก</div>
                    <div class="font-mono text-xl font-bold">{{ $user->member_number }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- ======================================
            3. Stats Cards (4 การ์ดหลัก)
        ====================================== --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Wallet Balance --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-wallet text-white text-xl"></i>
                    </div>
                    @if($stats['wallet_change'] != 0)
                    <span class="text-sm font-semibold {{ $stats['wallet_change'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $stats['wallet_change'] > 0 ? '+' : '' }}{{ number_format($stats['wallet_change'], 1) }}%
                    </span>
                    @endif
                </div>
                <div class="text-gray-600 dark:text-gray-400 text-sm mb-1">ยอดคงเหลือ</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    ฿{{ number_format($stats['wallet_balance'], 2) }}
                </div>
            </div>

            {{-- Pending Commission --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-white text-xl"></i>
                    </div>
                    @if($stats['commission_change'] != 0)
                    <span class="text-sm font-semibold {{ $stats['commission_change'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $stats['commission_change'] > 0 ? '+' : '' }}{{ number_format($stats['commission_change'], 1) }}%
                    </span>
                    @endif
                </div>
                <div class="text-gray-600 dark:text-gray-400 text-sm mb-1">คอมมิชชั่นรอรับ</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    ฿{{ number_format($stats['pending_commission'], 2) }}
                </div>
            </div>

            {{-- Total Referrals --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    @if($stats['referrals_change'] != 0)
                    <span class="text-sm font-semibold {{ $stats['referrals_change'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $stats['referrals_change'] > 0 ? '+' : '' }}{{ number_format($stats['referrals_change'], 1) }}%
                    </span>
                    @endif
                </div>
                <div class="text-gray-600 dark:text-gray-400 text-sm mb-1">ทีมทั้งหมด</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($stats['total_referrals']) }}
                </div>
            </div>

            {{-- Rank Points --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center">
                        <i class="fas fa-star text-white text-xl"></i>
                    </div>
                </div>
                <div class="text-gray-600 dark:text-gray-400 text-sm mb-1">คะแนน Rank</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($stats['rank_points']) }}
                </div>
            </div>
        </div>

        {{-- ======================================
            4. Main Content Grid
        ====================================== --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left Column (2 cols) - Chart & Quick Actions --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Revenue Chart --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-chart-line text-blue-600"></i>
                        <span>รายได้ 12 เดือนย้อนหลัง</span>
                    </h2>
                    <div class="h-80">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">เมนูด่วน</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        {{-- Deposit --}}
                        <a href="{{ route('user.wallet.deposit') }}"
                           class="group flex flex-col items-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl hover:shadow-lg transition-all duration-200">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-plus text-white"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white text-center">เติมเงิน</span>
                        </a>

                        {{-- Withdraw --}}
                        <a href="{{ route('user.wallet.withdraw') }}"
                           class="group flex flex-col items-center p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl hover:shadow-lg transition-all duration-200">
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-hand-holding-usd text-white"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white text-center">ถอนเงิน</span>
                        </a>

                        {{-- Invite --}}
                        <a href="{{ route('user.mlm.referral') }}"
                           class="group flex flex-col items-center p-4 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl hover:shadow-lg transition-all duration-200">
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-user-plus text-white"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white text-center">เชิญเพื่อน</span>
                        </a>

                        {{-- Profile --}}
                        <a href="{{ route('user.profile') }}"
                           class="group flex flex-col items-center p-4 bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-xl hover:shadow-lg transition-all duration-200">
                            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform duration-200">
                                <i class="fas fa-user-edit text-white"></i>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white text-center">โปรไฟล์</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Right Column (1 col) - Rank Progress & Activities --}}
            <div class="space-y-6">

                {{-- Rank Progress --}}
                @if($currentRank || $nextRank)
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-trophy text-yellow-500"></i>
                        <span>ความคืบหน้า Rank</span>
                    </h2>

                    {{-- Current Rank --}}
                    @if($currentRank)
                    <div class="mb-6 p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Rank ปัจจุบัน</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $currentRank->name_th ?? $currentRank->name }}</div>
                    </div>
                    @endif

                    {{-- Next Rank Progress --}}
                    @if($nextRank)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                ถัดไป: {{ $nextRank->name_th ?? $nextRank->name }}
                            </span>
                            <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                                {{ number_format($rankProgress, 1) }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden mb-2">
                            <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all duration-500"
                                 style="width: {{ $rankProgress }}%"></div>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            ต้องการอีก {{ number_format($pointsNeeded) }} คะแนน
                        </p>
                    </div>
                    @else
                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center py-4">
                        คุณอยู่ที่ Rank สูงสุดแล้ว! 🎉
                    </p>
                    @endif
                </div>
                @endif

                {{-- Recent Activities --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-history text-blue-600"></i>
                        <span>กิจกรรมล่าสุด</span>
                    </h2>

                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        @forelse($recentActivities as $activity)
                        @php
                            $iconMap = [
                                'pending' => ['icon' => 'fas fa-clock', 'color' => 'text-yellow-500', 'bg' => 'bg-yellow-100 dark:bg-yellow-900/30'],
                                'approved' => ['icon' => 'fas fa-check-circle', 'color' => 'text-green-500', 'bg' => 'bg-green-100 dark:bg-green-900/30'],
                                'paid' => ['icon' => 'fas fa-money-bill-wave', 'color' => 'text-blue-500', 'bg' => 'bg-blue-100 dark:bg-blue-900/30'],
                                'rejected' => ['icon' => 'fas fa-times-circle', 'color' => 'text-red-500', 'bg' => 'bg-red-100 dark:bg-red-900/30'],
                            ];
                            $statusInfo = $iconMap[$activity['status']] ?? $iconMap['pending'];

                            $titleMap = [
                                'pending' => 'คอมมิชชั่นรอดำเนินการ',
                                'approved' => 'คอมมิชชั่นได้รับการอนุมัติ',
                                'paid' => 'คอมมิชชั่นจ่ายแล้ว',
                                'rejected' => 'คอมมิชชั่นถูกปฏิเสธ',
                            ];
                            $title = $titleMap[$activity['status']] ?? 'กิจกรรม';
                        @endphp

                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 {{ $statusInfo['bg'] }} rounded-full flex items-center justify-center">
                                <i class="{{ $statusInfo['icon'] }} {{ $statusInfo['color'] }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">฿{{ number_format($activity['amount'], 2) }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-500">{{ $activity['created_at']->diffForHumans() }}</div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8">
                            <i class="fas fa-inbox text-4xl text-gray-300 dark:text-gray-600 mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีกิจกรรม</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<script>
/**
 * Dashboard Chart.js Initialization
 *
 * แสดงกราฟรายได้ 12 เดือนย้อนหลัง
 */
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    const chartData = @json($chartData);
    const isDark = document.documentElement.classList.contains('dark');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'รายได้ (บาท)',
                data: chartData.values,
                borderColor: isDark ? 'rgb(147, 51, 234)' : 'rgb(139, 92, 246)',
                backgroundColor: isDark
                    ? 'rgba(147, 51, 234, 0.1)'
                    : 'rgba(139, 92, 246, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: isDark ? 'rgb(147, 51, 234)' : 'rgb(139, 92, 246)',
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
                    titleColor: isDark ? '#fff' : '#111827',
                    bodyColor: isDark ? '#d1d5db' : '#6b7280',
                    borderColor: isDark ? 'rgba(75, 85, 99, 0.5)' : 'rgba(209, 213, 219, 0.5)',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return '฿' + context.parsed.y.toLocaleString('th-TH', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: isDark ? 'rgba(75, 85, 99, 0.2)' : 'rgba(229, 231, 235, 1)',
                        drawBorder: false,
                    },
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                        callback: function(value) {
                            return '฿' + value.toLocaleString('th-TH');
                        }
                    }
                },
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                    }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
