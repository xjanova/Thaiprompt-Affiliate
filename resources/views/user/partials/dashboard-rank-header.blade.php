{{--
/**
 * Dashboard Premium Hero Header - V4.0 with Premium Elements
 *
 * V4.0: Premium Hero Header with dynamic rank-based gradients
 * - Premium animated orbs
 * - Floating rank icons
 * - Glass-fusion effects
 * - Dynamic gradients based on rank
 * - Full dark mode support
 *
 * @var int $rankLevel ระดับ Rank (1-8)
 * @var \App\Models\User $user
 * @var \App\Models\Rank|null $currentRank
 * @version 4.0.0 (Premium Hero)
 * @date 2025-12-25
 */
--}}

@php
    $rankLevel = $rankLevel ?? $currentRank?->level ?? 1;

    // Gradient Classes ตาม Rank
    $headerGradients = [
        1 => 'from-amber-600 via-orange-600 to-amber-700 dark:from-amber-800 dark:via-orange-800 dark:to-amber-900', // Bronze
        2 => 'from-gray-400 via-slate-400 to-gray-500 dark:from-gray-600 dark:via-slate-600 dark:to-gray-700', // Silver
        3 => 'from-yellow-500 via-amber-400 to-yellow-600 dark:from-yellow-700 dark:via-amber-600 dark:to-yellow-800', // Gold
        4 => 'from-slate-400 via-gray-300 to-slate-500 dark:from-slate-600 dark:via-gray-500 dark:to-slate-700', // Platinum
        5 => 'from-cyan-500 via-sky-400 to-blue-500 dark:from-cyan-700 dark:via-sky-600 dark:to-blue-700', // Diamond
        6 => 'from-amber-500 via-yellow-400 to-orange-500 dark:from-amber-700 dark:via-yellow-600 dark:to-orange-700', // Crown
        7 => 'from-purple-600 via-violet-500 to-indigo-600 dark:from-purple-800 dark:via-violet-700 dark:to-indigo-800', // Royal
        8 => 'from-pink-600 via-purple-500 to-indigo-600 dark:from-pink-800 dark:via-purple-700 dark:to-indigo-800', // Legend
    ];
    $headerGradient = $headerGradients[$rankLevel] ?? $headerGradients[1];

    // Rank Badge Icons
    $rankBadges = [
        1 => '🥉', // Bronze
        2 => '🥈', // Silver
        3 => '🥇', // Gold
        4 => '💎', // Platinum
        5 => '💠', // Diamond
        6 => '👑', // Crown
        7 => '🏆', // Royal
        8 => '⭐', // Legend
    ];
    $rankBadge = $rankBadges[$rankLevel] ?? $rankBadges[1];

    // FontAwesome Icons for each rank
    $rankIcons = [
        1 => 'fa-medal',
        2 => 'fa-award',
        3 => 'fa-trophy',
        4 => 'fa-gem',
        5 => 'fa-star',
        6 => 'fa-crown',
        7 => 'fa-chess-king',
        8 => 'fa-fire-alt',
    ];
    $rankIcon = $rankIcons[$rankLevel] ?? $rankIcons[1];
@endphp

{{-- Premium Hero Header with Dynamic Rank Gradients --}}
<div class="relative overflow-hidden bg-gradient-to-br {{ $headerGradient }} rounded-2xl shadow-2xl p-8 {{ $rankLevel >= 6 ? 'ring-1 ring-white/20' : '' }}">
    {{-- Premium Animated Background Orbs --}}
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
    </div>

    {{-- Floating Rank Icon --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
            <i class="fas {{ $rankIcon }}"></i>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
        {{-- Avatar with Rank Border --}}
        <div class="flex-shrink-0">
            <x-rank-avatar
                :user="$user"
                :rank-level="$rankLevel"
                size="xl"
                :show-badge="$rankLevel >= 3"
                :animate="false"
                class="drop-shadow-2xl"
            />
        </div>

        {{-- Welcome Text --}}
        <div class="flex-1 text-center md:text-left text-white">
            <div class="text-white/80 text-sm mb-1">ยินดีต้อนรับกลับมา</div>
            <h1 class="text-3xl md:text-4xl font-bold mb-2 drop-shadow-lg flex items-center justify-center md:justify-start gap-3 flex-wrap">
                {{ $user->name }}

                {{-- KYC Verified Badge --}}
                @if($user->isKycVerified())
                    <a href="{{ route('user.kyc.index') }}"
                       class="inline-flex items-center px-3 py-1 glass-fusion-badge bg-green-500/30 text-white text-sm rounded-full shadow-lg hover:bg-green-500/50 transition-all"
                       title="บัญชียืนยันตัวตนแล้ว">
                        <i class="fas fa-shield-check mr-1"></i>
                        <span class="text-xs font-semibold">KYC ✓</span>
                    </a>
                @endif
            </h1>
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                @if($currentRank)
                <div class="inline-flex items-center gap-2 glass-fusion rounded-full px-4 py-2 shadow-lg">
                    <span class="text-xl">{{ $rankBadge }}</span>
                    <span class="text-white/80 text-sm">Rank:</span>
                    <span class="font-bold">
                        {{ $currentRank->name_th ?? $currentRank->name }}
                    </span>
                    @if($rankLevel >= 4)
                        <span class="text-xs bg-white/20 rounded-full px-2 py-0.5">Lv.{{ $rankLevel }}</span>
                    @endif
                </div>
                @endif

                {{-- KYC Status Badge --}}
                @if(isset($kycStatus))
                    @if($user->isKycPending())
                        <a href="{{ route('user.kyc.index') }}"
                           class="inline-flex items-center gap-2 glass-fusion-badge bg-yellow-500/30 rounded-full px-4 py-2 shadow-lg hover:bg-yellow-500/50 transition-all">
                            <i class="fas fa-hourglass-half text-white"></i>
                            <span class="text-white text-sm font-semibold">KYC รอตรวจสอบ</span>
                        </a>
                    @elseif(!$user->isKycVerified() && $kycStatus === 'not_submitted')
                        <a href="{{ route('user.kyc.index') }}"
                           class="inline-flex items-center gap-2 glass-fusion-badge bg-red-500/30 rounded-full px-4 py-2 shadow-lg hover:bg-red-500/50 transition-all">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                            <span class="text-white text-sm font-semibold">ยังไม่ยืนยันตัวตน</span>
                        </a>
                    @endif
                @endif

                {{-- Virtual ID Card Link (แสดงเฉพาะ Rank 3+) --}}
                @if($rankLevel >= 3)
                <a href="{{ route('user.id-card') }}"
                   class="inline-flex items-center gap-2 glass-fusion rounded-full px-4 py-2 shadow-lg hover:bg-white/30 transition-all">
                    <i class="fas fa-id-card text-white"></i>
                    <span class="text-white text-sm font-semibold">ดูบัตร ID</span>
                </a>
                @endif
            </div>
        </div>

        {{-- Member Number --}}
        @if($user->member_number)
        <div class="flex-shrink-0 text-center md:text-right text-white">
            <div class="text-white/60 text-xs mb-1">รหัสสมาชิก</div>
            <div class="font-mono text-xl font-bold drop-shadow-lg flex items-center gap-2 justify-center md:justify-end">
                {{ $user->member_number }}
            </div>
            @if($rankLevel >= 5)
            <div class="text-xs text-white/50 mt-1">
                {{ $rankLevel >= 8 ? 'ตำนาน' : ($rankLevel >= 7 ? 'รอยัล' : ($rankLevel >= 6 ? 'มงกุฎ' : 'VIP')) }}
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- Bottom Decorative Border (Rank 5+) --}}
    @if($rankLevel >= 5)
    <div class="absolute bottom-0 left-0 right-0 h-1
        {{ $rankLevel >= 8 ? 'bg-gradient-to-r from-pink-400 via-purple-400 to-cyan-400' : '' }}
        {{ $rankLevel >= 6 && $rankLevel < 8 ? 'bg-gradient-to-r from-yellow-400 via-amber-400 to-orange-400' : '' }}
        {{ $rankLevel >= 5 && $rankLevel < 6 ? 'bg-gradient-to-r from-cyan-400 via-blue-400 to-sky-400' : '' }}
        "></div>
    @endif
</div>

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.glass-fusion-badge {
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
