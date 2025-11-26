@extends('layouts.user-arrow-x')

@section('title', 'แดชบอร์ด')

@section('content')
{{--
/**
 * User Dashboard - Version 3.3 (Arrow X Theme - ลดความฟุ้ง)
 *
 * อัพเกรดใช้ Arrow X Theme Components:
 * - <x-arrow-x.alert-v3> สำหรับ alerts
 * - <x-arrow-x.card-v3> สำหรับ cards
 * - <x-arrow-x.stats.card-3d> สำหรับ stat cards
 * - Glassmorphism effects
 * - Gradient effects (ลด animations ที่รบกวนสายตา)
 * - Full dark mode support
 * - Rank-based styling แบบ subtle
 *
 * @version 3.3.0
 * @date 2025-11-26
 */
--}}

@php
    $rankLevel = $currentRank?->level ?? 1;
@endphp

{{-- Rank-based Background Effects --}}
@include('user.partials.dashboard-rank-effects', ['rankLevel' => $rankLevel])

<div class="space-y-6 relative z-10">

    {{-- ======================================
        1. KYC Alert (ถ้ายังไม่ทำ KYC) - ใช้ Arrow X Alert
    ====================================== --}}
    @if($showKycAlert)
    <x-arrow-x.alert-v3
        type="warning"
        title="ยืนยันตัวตนเพื่อปลดล็อกฟีเจอร์ทั้งหมด"
        dismissible>
        <div class="space-y-4">
            <p class="text-gray-700 dark:text-gray-300">
                ทำการยืนยันตัวตน (KYC) เพื่อถอนเงิน และใช้งานฟีเจอร์เต็มรูปแบบ
            </p>
            <a href="{{ route('user.kyc.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl hover:scale-[1.02]">
                <i class="fas fa-id-card"></i>
                <span>ยืนยันตัวตนตอนนี้</span>
            </a>
        </div>
    </x-arrow-x.alert-v3>
    @endif

    {{-- ======================================
        1.5 Retention Status Bar (แถบเตือนสถานะรักษายอด)
    ====================================== --}}
    @if($retentionStatus && $retentionStatistics)
        <x-retention-status-bar
            :retentionStatus="$retentionStatus"
            :statistics="$retentionStatistics"
        />
    @endif

    {{-- ======================================
        2. Welcome Section - Rank-based Header 🆕
    ====================================== --}}
    @include('user.partials.dashboard-rank-header', [
        'rankLevel' => $rankLevel,
        'user' => $user,
        'currentRank' => $currentRank,
        'kycStatus' => $kycStatus ?? null,
    ])

    {{-- ======================================
        3. Stats Cards (4 การ์ดหลัก) - ใช้ Arrow X 3D Stats Cards
    ====================================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Wallet Balance --}}
        <x-arrow-x.stats.card-3d
            :value="'฿' . number_format($stats['wallet_balance'], 2)"
            label="ยอดคงเหลือ"
            icon="fas fa-wallet"
            gradient="from-green-500 to-emerald-600"
            :change="$stats['wallet_change'] != 0 ? $stats['wallet_change'] : null"
            href="{{ route('user.wallet.index') }}"
        />

        {{-- Pending Commission --}}
        <x-arrow-x.stats.card-3d
            :value="'฿' . number_format($stats['pending_commission'], 2)"
            label="คอมมิชชั่นรอรับ"
            icon="fas fa-money-bill-wave"
            gradient="from-blue-500 to-cyan-600"
            :change="$stats['commission_change'] != 0 ? $stats['commission_change'] : null"
            href="{{ route('user.commissions') }}"
        />

        {{-- Total Referrals --}}
        <x-arrow-x.stats.card-3d
            :value="number_format($stats['total_referrals'])"
            label="ทีมทั้งหมด"
            icon="fas fa-users"
            gradient="from-purple-500 to-pink-600"
            :change="$stats['referrals_change'] != 0 ? $stats['referrals_change'] : null"
            href="{{ route('user.mlm.team') }}"
        />

        {{-- Rank Points --}}
        @php
            $rankCardGradients = [
                1 => 'from-amber-600 to-orange-600',
                2 => 'from-gray-400 to-slate-500',
                3 => 'from-yellow-400 to-amber-500',
                4 => 'from-slate-300 to-gray-400',
                5 => 'from-cyan-400 to-blue-500',
                6 => 'from-yellow-400 to-orange-500',
                7 => 'from-purple-500 to-violet-600',
                8 => 'from-pink-500 to-purple-600',
            ];
            $rankCardGradient = $rankCardGradients[$rankLevel] ?? 'from-yellow-500 to-orange-600';
        @endphp
        <x-arrow-x.stats.card-3d
            :value="number_format($stats['rank_points'])"
            label="คะแนน Rank"
            icon="fas fa-star"
            :gradient="$rankCardGradient"
            href="{{ route('user.ranks.progress') }}"
        />
    </div>

    {{-- ======================================
        4. Main Content Grid
    ====================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column (2 cols) - Chart & Quick Actions --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Revenue Chart - ใช้ Arrow X Card --}}
            <x-arrow-x.card-v3 class="p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-600 dark:text-blue-400"></i>
                    <span>รายได้ 12 เดือนย้อนหลัง</span>
                </h2>
                <div class="h-80">
                    <canvas id="revenueChart"></canvas>
                </div>
            </x-arrow-x.card-v3>

            {{-- Quick Actions - ใช้ Arrow X Card --}}
            <x-arrow-x.card-v3 class="p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-bolt text-yellow-500"></i>
                    <span>เมนูด่วน</span>
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    {{-- Deposit --}}
                    <a href="{{ route('user.wallet.deposit') }}"
                       class="group flex flex-col items-center p-4 glass-neu rounded-xl hover:shadow-lg transition-all duration-200 hover:scale-[1.02]">
                        <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-full flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform duration-200">
                            <i class="fas fa-plus text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white text-center">เติมเงิน</span>
                    </a>

                    {{-- Withdraw --}}
                    <a href="{{ route('user.wallet.withdraw') }}"
                       class="group flex flex-col items-center p-4 glass-neu rounded-xl hover:shadow-lg transition-all duration-200 hover:scale-[1.02]">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform duration-200">
                            <i class="fas fa-hand-holding-usd text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white text-center">ถอนเงิน</span>
                    </a>

                    {{-- Invite --}}
                    <a href="{{ route('user.mlm.referral') }}"
                       class="group flex flex-col items-center p-4 glass-neu rounded-xl hover:shadow-lg transition-all duration-200 hover:scale-[1.02]">
                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-full flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform duration-200">
                            <i class="fas fa-user-plus text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white text-center">เชิญเพื่อน</span>
                    </a>

                    {{-- Profile --}}
                    <a href="{{ route('user.profile') }}"
                       class="group flex flex-col items-center p-4 glass-neu rounded-xl hover:shadow-lg transition-all duration-200 hover:scale-[1.02]">
                        <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-red-600 rounded-full flex items-center justify-center mb-3 shadow-lg group-hover:scale-110 transition-transform duration-200">
                            <i class="fas fa-user-edit text-white text-xl"></i>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white text-center">โปรไฟล์</span>
                    </a>
                </div>
            </x-arrow-x.card-v3>
        </div>

        {{-- Right Column (1 col) - Rank Progress & Activities --}}
        <div class="space-y-6">

            {{-- Rank Progress - ใช้ Arrow X Card --}}
            @if($currentRank || $nextRank)
            @php
                $rankBadges = [
                    1 => '🥉', 2 => '🥈', 3 => '🥇', 4 => '💎',
                    5 => '💠', 6 => '👑', 7 => '🏆', 8 => '⭐',
                ];
                $rankBadge = $rankBadges[$rankLevel] ?? '🥉';

                $progressGradients = [
                    1 => 'from-amber-500 to-orange-500',
                    2 => 'from-gray-400 to-slate-500',
                    3 => 'from-yellow-400 to-amber-500',
                    4 => 'from-slate-300 via-purple-300 to-pink-300',
                    5 => 'from-cyan-400 via-blue-400 to-sky-500',
                    6 => 'from-yellow-400 via-amber-400 to-orange-400',
                    7 => 'from-purple-500 via-violet-500 to-indigo-500',
                    8 => 'from-pink-500 via-purple-500 to-cyan-500',
                ];
                $progressGradient = $progressGradients[$rankLevel] ?? 'from-purple-500 to-pink-500';
            @endphp
            <x-arrow-x.card-v3 class="p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <span class="text-2xl">{{ $rankBadge }}</span>
                    <span>ความคืบหน้า Rank</span>
                    @if($rankLevel >= 5)
                        <span class="ml-auto text-xs px-2 py-1 bg-gradient-to-r {{ $progressGradient }} text-white rounded-full shadow">
                            Level {{ $rankLevel }}
                        </span>
                    @endif
                </h2>

                {{-- Current Rank --}}
                @if($currentRank)
                <div class="mb-6 p-4 glass-neu rounded-xl">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Rank ปัจจุบัน</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="text-2xl">{{ $rankBadge }}</span>
                        <span>{{ $currentRank->name_th ?? $currentRank->name }}</span>
                        @if($rankLevel >= 8)
                            <span class="text-xs bg-pink-500/20 text-pink-600 dark:text-pink-400 px-2 py-0.5 rounded-full">
                                ตำนาน
                            </span>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Next Rank Progress --}}
                @if($nextRank)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                            <i class="fas fa-arrow-up text-green-500"></i>
                            ถัดไป: {{ $nextRank->name_th ?? $nextRank->name }}
                        </span>
                        <span class="text-sm font-bold text-purple-600 dark:text-purple-400">
                            {{ number_format($rankProgress, 1) }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden mb-2 shadow-inner">
                        <div class="h-full bg-gradient-to-r {{ $progressGradient }} rounded-full transition-all duration-500 shadow-lg"
                             style="width: {{ $rankProgress }}%">
                        </div>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1">
                        <i class="fas fa-info-circle"></i>
                        ต้องการอีก <span class="font-bold">{{ number_format($pointsNeeded) }}</span> คะแนน
                    </p>
                </div>
                @else
                <div class="text-center py-8 glass-neu rounded-xl">
                    <div class="text-6xl mb-3">👑</div>
                    <p class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                        คุณอยู่ที่ Rank สูงสุดแล้ว!
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        ยินดีด้วย! คุณคือสุดยอดสมาชิก
                    </p>
                </div>
                @endif

                {{-- Virtual ID Card Link --}}
                @if($rankLevel >= 2)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('user.id-card') }}"
                       class="flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r {{ $progressGradient }} text-white font-semibold rounded-xl hover:opacity-90 transition-all shadow-lg hover:shadow-xl">
                        <i class="fas fa-id-card"></i>
                        <span>ดูบัตรประจำตัว VIP</span>
                    </a>
                </div>
                @endif
            </x-arrow-x.card-v3>
            @endif

            {{-- Recent Activities - ใช้ Arrow X Card --}}
            <x-arrow-x.card-v3 class="p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-history text-blue-600 dark:text-blue-400"></i>
                    <span>กิจกรรมล่าสุด</span>
                </h2>

                <div class="space-y-4 max-h-96 overflow-y-auto custom-scrollbar">
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

                    <div class="flex items-start gap-3 p-3 glass-neu rounded-xl hover:shadow-md transition-shadow duration-200">
                        <div class="flex-shrink-0 w-10 h-10 {{ $statusInfo['bg'] }} rounded-full flex items-center justify-center shadow-md">
                            <i class="{{ $statusInfo['icon'] }} {{ $statusInfo['color'] }}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 font-mono">฿{{ number_format($activity['amount'], 2) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-500 flex items-center gap-1">
                                <i class="far fa-clock"></i>
                                {{ $activity['created_at']->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12 glass-neu rounded-xl">
                        <i class="fas fa-inbox text-5xl text-gray-300 dark:text-gray-600 mb-3"></i>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีกิจกรรม</p>
                    </div>
                    @endforelse
                </div>
            </x-arrow-x.card-v3>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<script>
/**
 * Dashboard Chart.js Initialization
 *
 * แสดงกราฟรายได้ 12 เดือนย้อนหลัง พร้อม dark mode support
 */
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    const chartData = @json($chartData);
    const isDark = document.documentElement.classList.contains('dark');

    // Chart instance
    const chart = new Chart(ctx, {
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

    // อัพเดท chart เมื่อเปลี่ยน theme
    window.addEventListener('user-theme-changed', (event) => {
        const newIsDark = event.detail.isDark;

        // อัพเดทสี
        chart.data.datasets[0].borderColor = newIsDark ? 'rgb(147, 51, 234)' : 'rgb(139, 92, 246)';
        chart.data.datasets[0].backgroundColor = newIsDark ? 'rgba(147, 51, 234, 0.1)' : 'rgba(139, 92, 246, 0.1)';
        chart.data.datasets[0].pointBackgroundColor = newIsDark ? 'rgb(147, 51, 234)' : 'rgb(139, 92, 246)';

        // อัพเดท grid และ ticks
        chart.options.scales.y.grid.color = newIsDark ? 'rgba(75, 85, 99, 0.2)' : 'rgba(229, 231, 235, 1)';
        chart.options.scales.y.ticks.color = newIsDark ? '#9ca3af' : '#6b7280';
        chart.options.scales.x.ticks.color = newIsDark ? '#9ca3af' : '#6b7280';

        // อัพเดท tooltip
        chart.options.plugins.tooltip.backgroundColor = newIsDark ? 'rgba(17, 24, 39, 0.95)' : 'rgba(255, 255, 255, 0.95)';
        chart.options.plugins.tooltip.titleColor = newIsDark ? '#fff' : '#111827';
        chart.options.plugins.tooltip.bodyColor = newIsDark ? '#d1d5db' : '#6b7280';
        chart.options.plugins.tooltip.borderColor = newIsDark ? 'rgba(75, 85, 99, 0.5)' : 'rgba(209, 213, 219, 0.5)';

        // Redraw chart
        chart.update();
    });
});
</script>
@endpush

@endsection
