{{--
/**
 * Dashboard Rank Header - Welcome Header ที่เปลี่ยนตาม Rank
 *
 * ออกแบบให้แตกต่างกัน 8 ระดับ:
 * Level 1-2: Standard gradient
 * Level 3-4: Enhanced gradient + shine
 * Level 5-6: Premium gradient + glow
 * Level 7-8: Legendary gradient + animated effects
 *
 * @var int $rankLevel ระดับ Rank (1-8)
 * @var \App\Models\User $user
 * @var \App\Models\Rank|null $currentRank
 * @version 1.0.0
 * @date 2025-11-26
 */
--}}

@php
    $rankLevel = $rankLevel ?? $currentRank?->level ?? 1;

    // Gradient Classes ตาม Rank
    $headerGradients = [
        1 => 'from-amber-600 via-orange-600 to-amber-700', // Bronze
        2 => 'from-gray-400 via-slate-400 to-gray-500', // Silver
        3 => 'from-yellow-500 via-amber-400 to-yellow-600', // Gold
        4 => 'from-slate-400 via-gray-300 to-slate-500', // Platinum
        5 => 'from-cyan-500 via-sky-400 to-blue-500', // Diamond
        6 => 'from-amber-500 via-yellow-400 to-orange-500', // Crown
        7 => 'from-purple-600 via-violet-500 to-indigo-600', // Royal
        8 => 'from-pink-600 via-purple-500 to-indigo-600', // Legend
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

    // Additional Effects
    $hasGlow = $rankLevel >= 5;
    $hasShine = $rankLevel >= 3;
    $hasPulse = $rankLevel >= 7;
    $hasParticles = $rankLevel >= 6;
@endphp

<x-arrow-x.card-v3
    class="bg-gradient-to-br {{ $headerGradient }} overflow-hidden relative
        {{ $hasGlow ? 'shadow-2xl shadow-current/30' : '' }}
        {{ $rankLevel >= 8 ? 'ring-2 ring-pink-400/50 animate-glow-legend' : '' }}
        {{ $rankLevel >= 6 ? 'ring-2 ring-yellow-400/30' : '' }}">

    {{-- Background Pattern - เปลี่ยนตาม Rank --}}
    <div class="absolute inset-0 opacity-10">
        @if($rankLevel >= 8)
            {{-- Legend: Aurora effect --}}
            <div class="absolute inset-0 animate-spin-very-slow" style="animation-duration: 20s;">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-pink-400/30 to-transparent transform rotate-45"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-cyan-400/30 to-transparent transform -rotate-45"></div>
            </div>
        @elseif($rankLevel >= 6)
            {{-- Crown/Royal: Multiple orbs --}}
            <div class="absolute top-0 left-0 w-72 h-72 bg-white rounded-full filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 bg-white rounded-full filter blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-48 h-48 bg-white rounded-full filter blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        @elseif($rankLevel >= 4)
            {{-- Platinum/Diamond: Holographic shine --}}
            <div class="absolute top-0 left-0 w-64 h-64 bg-white rounded-full filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white rounded-full filter blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></div>
        @else
            {{-- Bronze/Silver/Gold: Standard orbs --}}
            <div class="absolute top-0 left-0 w-64 h-64 bg-white rounded-full filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-white rounded-full filter blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
        @endif
    </div>

    {{-- Shine Effect (Gold+) --}}
    @if($hasShine)
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full animate-shimmer"></div>
    </div>
    @endif

    {{-- Particle Effects (Crown+) --}}
    @if($hasParticles)
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        @for($i = 0; $i < ($rankLevel >= 8 ? 8 : 5); $i++)
            <div class="absolute w-1 h-1 bg-white rounded-full animate-sparkle"
                 style="top: {{ rand(10, 90) }}%; left: {{ rand(10, 90) }}%; animation-delay: {{ $i * 0.3 }}s;"></div>
        @endfor
    </div>
    @endif

    {{-- Main Content --}}
    <div class="relative z-10 flex flex-col md:flex-row items-center gap-6 p-2">
        {{-- Avatar with Rank Border - ใช้ component เพื่อความสอดคล้องทั้งระบบ --}}
        <div class="flex-shrink-0">
            <x-rank-avatar
                :user="$user"
                :rank-level="$rankLevel"
                size="lg"
                :show-badge="$rankLevel >= 3"
                :animate="true"
                class="drop-shadow-xl"
            />
        </div>

        {{-- Welcome Text --}}
        <div class="flex-1 text-center md:text-left text-white">
            <div class="text-white/80 text-sm mb-1">ยินดีต้อนรับกลับมา</div>
            <h1 class="text-3xl md:text-4xl font-bold mb-2 drop-shadow-lg flex items-center justify-center md:justify-start gap-3 flex-wrap
                {{ $rankLevel >= 8 ? 'animate-glow-text' : '' }}">
                {{ $user->name }}

                {{-- KYC Verified Badge --}}
                @if($user->isKycVerified())
                    <a href="{{ route('user.kyc.index') }}"
                       class="inline-flex items-center px-3 py-1 bg-green-500/90 backdrop-blur-sm text-white text-sm rounded-full shadow-lg hover:bg-green-600 transition-all"
                       title="บัญชียืนยันตัวตนแล้ว">
                        <i class="fas fa-shield-check mr-1"></i>
                        <span class="text-xs font-semibold">KYC ✓</span>
                    </a>
                @endif
            </h1>
            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                @if($currentRank)
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg rounded-full px-4 py-2 shadow-lg
                    {{ $rankLevel >= 6 ? 'ring-2 ring-white/30' : '' }}
                    {{ $rankLevel >= 8 ? 'animate-pulse-slow' : '' }}">
                    <span class="text-xl">{{ $rankBadge }}</span>
                    <span class="text-white/80 text-sm">Rank:</span>
                    <span class="font-bold
                        {{ $rankLevel >= 5 ? 'drop-shadow-lg' : '' }}">
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
                           class="inline-flex items-center gap-2 bg-yellow-500/90 backdrop-blur-lg rounded-full px-4 py-2 shadow-lg hover:bg-yellow-600 transition-all animate-pulse">
                            <i class="fas fa-hourglass-half text-white"></i>
                            <span class="text-white text-sm font-semibold">KYC รอตรวจสอบ</span>
                        </a>
                    @elseif(!$user->isKycVerified() && $kycStatus === 'not_submitted')
                        <a href="{{ route('user.kyc.index') }}"
                           class="inline-flex items-center gap-2 bg-red-500/80 backdrop-blur-lg rounded-full px-4 py-2 shadow-lg hover:bg-red-600 transition-all">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                            <span class="text-white text-sm font-semibold">ยังไม่ยืนยันตัวตน</span>
                        </a>
                    @endif
                @endif

                {{-- Virtual ID Card Link (แสดงเฉพาะ Rank 3+) --}}
                @if($rankLevel >= 3)
                <a href="{{ route('user.id-card') }}"
                   class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-lg rounded-full px-4 py-2 shadow-lg hover:bg-white/30 transition-all">
                    <i class="fas fa-id-card text-white"></i>
                    <span class="text-white text-sm font-semibold">ดูบัตร ID</span>
                </a>
                @endif
            </div>
        </div>

        {{-- Member Number with Rank Decoration --}}
        @if($user->member_number)
        <div class="flex-shrink-0 text-center md:text-right text-white">
            <div class="text-white/60 text-xs mb-1">รหัสสมาชิก</div>
            <div class="font-mono text-xl font-bold drop-shadow-lg flex items-center gap-2 justify-center md:justify-end">
                @if($rankLevel >= 6)
                    <span class="text-yellow-300">★</span>
                @endif
                {{ $user->member_number }}
                @if($rankLevel >= 6)
                    <span class="text-yellow-300">★</span>
                @endif
            </div>
            @if($rankLevel >= 5)
            <div class="text-xs text-white/50 mt-1">
                {{ $rankLevel >= 8 ? '🌟 ตำนาน' : ($rankLevel >= 7 ? '👑 รอยัล' : ($rankLevel >= 6 ? '✨ มงกุฎ' : '💎 VIP')) }}
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- Bottom Decorative Border (Rank 5+) --}}
    @if($rankLevel >= 5)
    <div class="absolute bottom-0 left-0 right-0 h-1
        {{ $rankLevel >= 8 ? 'bg-gradient-to-r from-pink-400 via-purple-400 to-cyan-400 animate-gradient-x' : '' }}
        {{ $rankLevel >= 6 && $rankLevel < 8 ? 'bg-gradient-to-r from-yellow-400 via-amber-400 to-orange-400' : '' }}
        {{ $rankLevel >= 5 && $rankLevel < 6 ? 'bg-gradient-to-r from-cyan-400 via-blue-400 to-sky-400' : '' }}
        "></div>
    @endif
</x-arrow-x.card-v3>

<style>
    /* Header-specific animations */
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    @keyframes glow-legend {
        0%, 100% { box-shadow: 0 0 30px rgba(236, 72, 153, 0.3), 0 0 60px rgba(139, 92, 246, 0.2); }
        50% { box-shadow: 0 0 50px rgba(236, 72, 153, 0.5), 0 0 100px rgba(139, 92, 246, 0.3); }
    }

    @keyframes glow-text {
        0%, 100% { text-shadow: 0 0 10px rgba(255, 255, 255, 0.5); }
        50% { text-shadow: 0 0 20px rgba(255, 255, 255, 0.8), 0 0 30px rgba(255, 255, 255, 0.4); }
    }

    @keyframes gradient-x {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    @keyframes bounce-slow {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    .animate-shimmer {
        animation: shimmer 3s infinite;
    }

    .animate-glow-legend {
        animation: glow-legend 3s ease-in-out infinite;
    }

    .animate-glow-text {
        animation: glow-text 2s ease-in-out infinite;
    }

    .animate-gradient-x {
        background-size: 200% 200%;
        animation: gradient-x 3s ease infinite;
    }

    .animate-bounce-slow {
        animation: bounce-slow 2s ease-in-out infinite;
    }

    .animate-pulse-slow {
        animation: pulse 3s ease-in-out infinite;
    }

    .animate-spin-very-slow {
        animation: spin 30s linear infinite;
    }

    .animate-sparkle {
        animation: sparkle 2s ease-in-out infinite;
    }

    @keyframes sparkle {
        0%, 100% { opacity: 0; transform: scale(0); }
        50% { opacity: 1; transform: scale(1); }
    }
</style>
