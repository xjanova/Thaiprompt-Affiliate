@extends('layouts.user-arrow-x')

@section('title', 'หน้าหลัก')

@section('content')
{{--
/**
 * User Home - Hub Navigation (App-Like Interface)
 *
 * หน้าหลักหลังล็อกอินแบบ mobile app
 * - 8 Hub Navigation Cards
 * - Quick Actions
 * - Balance Display
 * - Welcome Section
 *
 * @version 1.0.0
 * @date 2025-11-26
 */
--}}

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush

<div x-data="hubNavigation()" class="min-h-screen pb-24 lg:pb-8">

    {{-- ======================================
        Background Effects
    ====================================== --}}
    <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10">
        {{-- Aurora Glow 1 --}}
        <div class="absolute top-0 right-0 w-96 h-96 bg-gradient-to-br from-purple-500/20 to-pink-500/10 rounded-full filter blur-3xl animate-pulse"></div>
        {{-- Aurora Glow 2 --}}
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-blue-500/20 to-cyan-500/10 rounded-full filter blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        {{-- Floating Particles --}}
        <div class="absolute top-1/4 left-1/4 w-2 h-2 bg-purple-400 rounded-full opacity-30 animate-bounce" style="animation-delay: 0.5s;"></div>
        <div class="absolute top-1/3 right-1/3 w-3 h-3 bg-pink-400 rounded-full opacity-20 animate-bounce" style="animation-delay: 1s;"></div>
        <div class="absolute bottom-1/4 right-1/4 w-2 h-2 bg-blue-400 rounded-full opacity-30 animate-bounce" style="animation-delay: 1.5s;"></div>
    </div>

    {{-- ======================================
        Premium Hero Header (Compact for Home Hub)
    ====================================== --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 dark:from-purple-800 dark:via-pink-800 dark:to-blue-800 rounded-2xl shadow-2xl p-6 mb-6">
        {{-- Animated Background Orbs --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-64 h-64 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>

        {{-- Floating Icon Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-6xl top-5 right-10" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-rocket"></i>
            </div>
        </div>

        {{-- Content --}}
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                {{-- Welcome Section --}}
                <div class="flex-1">
                    {{-- Greeting Badge --}}
                    <div class="inline-flex items-center px-3 py-1 mb-2 glass-fusion rounded-full">
                        <span class="text-sm text-white" x-text="greeting"></span>
                    </div>

                    {{-- User Name --}}
                    <h1 class="text-2xl md:text-3xl font-bold text-white drop-shadow-lg mb-1">
                        {{ $user->name }}
                    </h1>

                    {{-- Subtitle --}}
                    <p class="text-purple-100 text-sm">
                        เลือกบริการที่คุณต้องการ ✨
                    </p>
                </div>

                {{-- Avatar with Glow --}}
                <a href="{{ route('user.profile') }}"
                   class="relative flex-shrink-0 group">
                    {{-- Avatar Container with glass-fusion --}}
                    <div class="relative w-16 h-16 p-0.5 glass-fusion rounded-full">
                        <div class="w-full h-full bg-white/20 dark:bg-gray-900/50 rounded-full flex items-center justify-center overflow-hidden">
                            {{-- ใช้ profile_picture_url accessor ที่รองรับ fallback อัตโนมัติ --}}
                            <img src="{{ $user->profile_picture_url }}"
                                 alt="{{ $user->name }}"
                                 class="w-full h-full object-cover"
                                 onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name={{ urlencode(substr($user->name, 0, 1)) }}&background=6366f1&color=fff&size=200';">
                        </div>
                    </div>

                    {{-- Notification Badge --}}
                    @if($unreadNotifications > 0)
                    <div class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center border-2 border-white">
                        <span class="text-xs font-bold text-white">{{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}</span>
                    </div>
                    @endif
                </a>
            </div>
        </div>
    </div>

    {{-- ======================================
        2. Balance Card (Glassmorphism)
    ====================================== --}}
    <div class="mb-6">
        <div class="relative overflow-hidden p-5 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-600 shadow-xl">
            {{-- Background Pattern --}}
            <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -mr-16 -mt-16"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-white/10 rounded-full -ml-12 -mb-12"></div>

            <div class="relative z-10 flex items-center justify-between">
                {{-- Balance Info --}}
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-white/80 text-sm">💳 ยอดเงินคงเหลือ</span>
                    </div>
                    <div class="text-3xl md:text-4xl font-bold text-white">
                        ฿{{ number_format($walletBalance, 2) }}
                    </div>
                    @if($walletGrowth != 0)
                    <div class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full {{ $walletGrowth > 0 ? 'bg-white/20 text-white' : 'bg-red-500/30 text-red-100' }}">
                        <i class="fas {{ $walletGrowth > 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} text-xs"></i>
                        <span class="text-xs font-semibold">{{ abs($walletGrowth) }}%</span>
                    </div>
                    @endif
                </div>

                {{-- Top Up Button --}}
                <a href="{{ route('user.wallet.deposit') }}"
                   class="flex items-center gap-2 px-5 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl text-white font-semibold transition-all duration-200 hover:scale-105">
                    <i class="fas fa-plus"></i>
                    <span>เติมเงิน</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ======================================
        3. Hub Navigation Cards (8 Hubs - 2 columns)
    ====================================== --}}
    <div class="mb-6">
        <div class="grid grid-cols-2 gap-4">
            @foreach($hubs as $hub)
            <a href="{{ $hub['route'] }}"
               class="group relative overflow-hidden p-4 rounded-2xl transition-all duration-300 hover:scale-[1.02] hover:shadow-xl"
               style="background: linear-gradient(135deg, {{ $hub['gradient_start'] }}15, {{ $hub['gradient_end'] }}10); border: 1px solid {{ $hub['gradient_start'] }}30;">

                {{-- Shine Effect --}}
                <div class="absolute top-0 right-0 w-20 h-20 bg-gradient-to-br from-white/10 to-transparent rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-500"></div>

                {{-- Icon Container with Glow --}}
                <div class="relative mb-3">
                    {{-- Glow Behind Icon --}}
                    <div class="absolute inset-0 rounded-2xl blur-xl opacity-50"
                         style="background: linear-gradient(135deg, {{ $hub['gradient_start'] }}, {{ $hub['gradient_end'] }});"></div>

                    {{-- Icon Box --}}
                    <div class="relative w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg"
                         style="background: linear-gradient(135deg, {{ $hub['gradient_start'] }}, {{ $hub['gradient_end'] }});">
                        <span class="text-2xl">{{ $hub['icon'] }}</span>
                    </div>
                </div>

                {{-- Badge (if any) --}}
                @if($hub['badge'])
                <div class="absolute top-3 right-3 px-2 py-0.5 rounded-full text-white text-xs font-bold"
                     style="background: linear-gradient(135deg, #EF4444, #EC4899);">
                    {{ $hub['badge'] }}
                </div>
                @endif

                {{-- Title --}}
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1 line-clamp-1">
                    {{ $hub['title'] }}
                </h3>

                {{-- Subtitle --}}
                <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2">
                    {{ $hub['subtitle'] }}
                </p>
            </a>
            @endforeach
        </div>
    </div>

    {{-- ======================================
        4. Quick Actions
    ====================================== --}}
    <div class="mb-6">
        <div class="p-4 rounded-2xl bg-white/60 dark:bg-gray-800/60 backdrop-blur-xl border border-gray-200/50 dark:border-gray-700/50">
            {{-- Section Header --}}
            <div class="flex items-center gap-2 mb-4">
                <span class="text-lg">⚡</span>
                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">การดำเนินการด่วน</span>
            </div>

            {{-- Quick Actions Grid - แถวแรก --}}
            <div class="grid grid-cols-4 gap-3 mb-3">
                {{-- Dashboard (Stats & Charts) --}}
                <a href="{{ route('user.dashboard') }}"
                   class="flex flex-col items-center gap-2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-lg">
                        <span class="text-xl">📊</span>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 text-center">แดชบอร์ด</span>
                </a>

                {{-- Transfer --}}
                <a href="{{ route('user.wallet.transfer') }}"
                   class="flex flex-col items-center gap-2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 dark:from-gray-600 dark:to-gray-800 shadow-lg">
                        <span class="text-xl">💸</span>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 text-center">โอนเงิน</span>
                </a>

                {{-- Top Up --}}
                <a href="{{ route('user.wallet.deposit') }}"
                   class="flex flex-col items-center gap-2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 dark:from-gray-600 dark:to-gray-800 shadow-lg">
                        <span class="text-xl">➕</span>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 text-center">เติมเงิน</span>
                </a>

                {{-- History --}}
                <a href="{{ route('user.wallet.transactions') }}"
                   class="flex flex-col items-center gap-2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 dark:from-gray-600 dark:to-gray-800 shadow-lg">
                        <span class="text-xl">📋</span>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 text-center">ประวัติ</span>
                </a>
            </div>

            {{-- Quick Actions Grid - แถวที่สอง --}}
            <div class="grid grid-cols-4 gap-3">
                {{-- QR Scan --}}
                <a href="#" @click.prevent="showComingSoon('สแกน QR')"
                   class="flex flex-col items-center gap-2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 dark:from-gray-600 dark:to-gray-800 shadow-lg">
                        <span class="text-xl">📷</span>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 text-center">สแกน QR</span>
                </a>

                {{-- Withdraw --}}
                <a href="{{ route('user.wallet.withdraw') }}"
                   class="flex flex-col items-center gap-2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 dark:from-gray-600 dark:to-gray-800 shadow-lg">
                        <span class="text-xl">💵</span>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 text-center">ถอนเงิน</span>
                </a>

                {{-- Invite Friends --}}
                <a href="{{ route('user.mlm.referral') }}"
                   class="flex flex-col items-center gap-2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 dark:from-gray-600 dark:to-gray-800 shadow-lg">
                        <span class="text-xl">👥</span>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 text-center">เชิญเพื่อน</span>
                </a>

                {{-- Profile --}}
                <a href="{{ route('user.profile') }}"
                   class="flex flex-col items-center gap-2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                    <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-gray-700 to-gray-900 dark:from-gray-600 dark:to-gray-800 shadow-lg">
                        <span class="text-xl">👤</span>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400 text-center">โปรไฟล์</span>
                </a>
            </div>
        </div>
    </div>

    {{-- ======================================
        5. Stats Summary Cards
    ====================================== --}}
    <div class="mb-6">
        <div class="grid grid-cols-2 gap-4">
            {{-- Pending Commission --}}
            <a href="{{ route('user.commissions') }}"
               class="p-4 rounded-2xl bg-white/60 dark:bg-gray-800/60 backdrop-blur-xl border border-gray-200/50 dark:border-gray-700/50 hover:shadow-lg transition-all duration-200 hover:scale-[1.02]">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 bg-blue-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-blue-500"></i>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">รอดำเนินการ</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">฿{{ number_format($pendingCommission, 2) }}</div>
            </a>

            {{-- Total Referrals --}}
            <a href="{{ route('user.mlm.team') }}"
               class="p-4 rounded-2xl bg-white/60 dark:bg-gray-800/60 backdrop-blur-xl border border-gray-200/50 dark:border-gray-700/50 hover:shadow-lg transition-all duration-200 hover:scale-[1.02]">
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 bg-purple-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-purple-500"></i>
                    </div>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">ผู้ใช้ที่แนะนำ</div>
                <div class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalReferrals) }} คน</div>
            </a>
        </div>
    </div>

    {{-- ======================================
        6. Share Referral Link Button
    ====================================== --}}
    <div class="mb-6">
        <a href="{{ route('user.mlm.referral') }}"
           class="flex items-center justify-center gap-3 w-full p-4 rounded-2xl bg-gradient-to-r from-purple-500 via-pink-500 to-blue-500 text-white font-semibold shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-[1.01]">
            <i class="fas fa-share-alt"></i>
            <span>แชร์ลิงก์แนะนำ</span>
        </a>
    </div>

    {{-- ======================================
        7. Recent Activities
    ====================================== --}}
    @if(count($recentActivities) > 0)
    <div>
        <div class="p-4 rounded-2xl bg-white/60 dark:bg-gray-800/60 backdrop-blur-xl border border-gray-200/50 dark:border-gray-700/50">
            {{-- Section Header --}}
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="text-lg">📊</span>
                    <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">คอมมิชชั่นล่าสุด</span>
                </div>
                <a href="{{ route('user.commissions') }}"
                   class="text-xs text-purple-600 dark:text-purple-400 hover:underline">
                    ดูทั้งหมด
                </a>
            </div>

            {{-- Activities List --}}
            <div class="space-y-3">
                @foreach($recentActivities as $activity)
                @php
                    $statusColors = [
                        'pending' => ['bg' => 'bg-yellow-500/20', 'text' => 'text-yellow-600 dark:text-yellow-400', 'label' => 'รอดำเนินการ'],
                        'approved' => ['bg' => 'bg-green-500/20', 'text' => 'text-green-600 dark:text-green-400', 'label' => 'อนุมัติแล้ว'],
                        'paid' => ['bg' => 'bg-blue-500/20', 'text' => 'text-blue-600 dark:text-blue-400', 'label' => 'จ่ายแล้ว'],
                        'rejected' => ['bg' => 'bg-red-500/20', 'text' => 'text-red-600 dark:text-red-400', 'label' => 'ปฏิเสธ'],
                    ];
                    $status = $statusColors[$activity['status']] ?? $statusColors['pending'];
                @endphp
                <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $activity['description'] ?? 'คอมมิชชั่น' }}
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs {{ $status['bg'] }} {{ $status['text'] }} px-2 py-0.5 rounded-full">
                                {{ $status['label'] }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $activity['created_at']->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold {{ $activity['amount'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $activity['amount'] >= 0 ? '+' : '' }}฿{{ number_format(abs($activity['amount']), 2) }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
/**
 * Hub Navigation Alpine.js Component
 *
 * จัดการ greeting ตามเวลาและ interactions
 */
function hubNavigation() {
    return {
        greeting: '',

        init() {
            this.setGreeting();
        },

        /**
         * ตั้งค่าข้อความทักทายตามเวลา
         */
        setGreeting() {
            const hour = new Date().getHours();

            if (hour >= 5 && hour < 12) {
                this.greeting = 'สวัสดีตอนเช้า ☀️';
            } else if (hour >= 12 && hour < 17) {
                this.greeting = 'สวัสดีตอนบ่าย 🌤️';
            } else if (hour >= 17 && hour < 21) {
                this.greeting = 'สวัสดีตอนเย็น 🌅';
            } else {
                this.greeting = 'สวัสดีตอนกลางคืน 🌙';
            }
        },

        /**
         * แสดง toast สำหรับฟีเจอร์ที่กำลังพัฒนา
         */
        showComingSoon(feature) {
            // ใช้ Alpine toast หรือ alert
            if (window.toast) {
                window.toast.show('info', `${feature} กำลังพัฒนา`);
            } else {
                alert(`${feature} กำลังพัฒนา`);
            }
        }
    }
}
</script>
@endpush

@push('styles')
<style>
/* Slow spin animation for avatar glow */
@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin-slow {
    animation: spin-slow 8s linear infinite;
}

/* Line clamp utilities */
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endpush
@endsection
