{{--
    Video Reward Coin Balance Component

    แสดงยอด Coins พร้อม Animation สวยงาม

    Usage:
    <x-video-reward.coin-balance :user="$user" />
    <x-video-reward.coin-balance :user="$user" show-stats size="lg" />

    Parameters:
    - user: User object (required)
    - size: sm | md | lg (default: md)
    - showStats: boolean - แสดงสถิติเพิ่มเติม
    - animated: boolean - เปิด animation
--}}

@props([
    'user',
    'size' => 'md',
    'showStats' => false,
    'animated' => true,
])

@php
    // ดึงข้อมูล coins ของ user
    $videoCoin = $user->videoCoin;

    // ค่าเริ่มต้นถ้ายังไม่มี coins
    $balance = $videoCoin?->balance ?? 0;
    $lifetimeEarned = $videoCoin?->lifetime_earned ?? 0;
    $lifetimeSpent = $videoCoin?->lifetime_spent ?? 0;
    $lifetimeExchanged = $videoCoin?->lifetime_exchanged ?? 0;

    // Size classes
    $sizeClasses = match($size) {
        'sm' => [
            'wrapper' => 'p-3',
            'icon' => 'w-8 h-8',
            'balance' => 'text-xl',
            'label' => 'text-xs',
        ],
        'lg' => [
            'wrapper' => 'p-6',
            'icon' => 'w-16 h-16',
            'balance' => 'text-4xl',
            'label' => 'text-base',
        ],
        default => [ // md
            'wrapper' => 'p-4',
            'icon' => 'w-12 h-12',
            'balance' => 'text-2xl',
            'label' => 'text-sm',
        ],
    };
@endphp

<div
    {{ $attributes->merge(['class' => 'relative overflow-hidden bg-gradient-to-br from-yellow-400 via-yellow-500 to-orange-500 rounded-2xl shadow-xl ' . $sizeClasses['wrapper']]) }}
    x-data="{
        balance: 0,
        targetBalance: {{ $balance }},
        animateCount() {
            const duration = 1500;
            const steps = 60;
            const increment = this.targetBalance / steps;
            let current = 0;
            const timer = setInterval(() => {
                current += increment;
                if (current >= this.targetBalance) {
                    this.balance = this.targetBalance;
                    clearInterval(timer);
                } else {
                    this.balance = Math.floor(current);
                }
            }, duration / steps);
        }
    }"
    x-init="setTimeout(() => animateCount(), 300)"
>
    {{-- Background Pattern --}}
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
            <defs>
                <pattern id="coins-pattern" patternUnits="userSpaceOnUse" width="20" height="20">
                    <circle cx="10" cy="10" r="8" fill="currentColor" opacity="0.3"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#coins-pattern)"/>
        </svg>
    </div>

    {{-- Shine Effect --}}
    @if($animated)
    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full animate-shimmer"></div>
    @endif

    {{-- Content --}}
    <div class="relative flex items-center space-x-4">
        {{-- Coin Icon --}}
        <div class="relative">
            <div class="{{ $sizeClasses['icon'] }} bg-yellow-300 rounded-full shadow-lg flex items-center justify-center {{ $animated ? 'animate-bounce-slow' : '' }}">
                {{-- Coin face --}}
                <div class="absolute inset-1 bg-gradient-to-br from-yellow-200 to-yellow-400 rounded-full flex items-center justify-center">
                    <span class="text-yellow-800 font-black {{ $size === 'lg' ? 'text-2xl' : ($size === 'sm' ? 'text-xs' : 'text-lg') }}">C</span>
                </div>
                {{-- Coin edge highlight --}}
                <div class="absolute inset-0 rounded-full ring-2 ring-yellow-200/50"></div>
            </div>
            {{-- Sparkle --}}
            @if($animated)
            <div class="absolute -top-1 -right-1 w-3 h-3 bg-white rounded-full animate-ping opacity-75"></div>
            @endif
        </div>

        {{-- Balance Info --}}
        <div class="flex-1">
            <p class="{{ $sizeClasses['label'] }} text-yellow-100 font-medium">Video Coins</p>
            <p class="{{ $sizeClasses['balance'] }} font-black text-white drop-shadow-lg" x-text="new Intl.NumberFormat('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(balance)">
                {{ number_format($balance, 2) }}
            </p>
        </div>

        {{-- Quick Action --}}
        <div class="flex flex-col space-y-1">
            <a href="{{ route('user.video-coins.index') }}"
               class="p-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors group"
               title="ดูรายละเอียด">
                <svg class="w-5 h-5 text-white group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>

    {{-- Stats Section (optional) --}}
    @if($showStats)
    <div class="relative mt-4 pt-4 border-t border-white/20 grid grid-cols-3 gap-2 text-center">
        {{-- Lifetime Earned --}}
        <div>
            <p class="text-yellow-100/80 text-xs">ได้รับรวม</p>
            <p class="font-bold text-white">{{ number_format($lifetimeEarned, 0) }}</p>
        </div>

        {{-- Lifetime Spent --}}
        <div>
            <p class="text-yellow-100/80 text-xs">ใช้ไปแล้ว</p>
            <p class="font-bold text-white">{{ number_format($lifetimeSpent, 0) }}</p>
        </div>

        {{-- Lifetime Exchanged --}}
        <div>
            <p class="text-yellow-100/80 text-xs">แลกแล้ว</p>
            <p class="font-bold text-white">{{ number_format($lifetimeExchanged, 0) }}</p>
        </div>
    </div>
    @endif
</div>

{{-- Animation styles --}}
@once
@push('styles')
<style>
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(200%); }
    }
    .animate-shimmer {
        animation: shimmer 3s infinite;
    }
    @keyframes bounce-slow {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }
    .animate-bounce-slow {
        animation: bounce-slow 2s ease-in-out infinite;
    }
</style>
@endpush
@endonce
