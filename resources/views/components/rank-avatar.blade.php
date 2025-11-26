{{--
/**
 * Rank Avatar Component - อวาต้าร์พร้อมกรอบตาม Rank
 *
 * แสดงรูป Avatar ของผู้ใช้พร้อมกรอบสวยงามตาม Rank Level
 * รองรับทั้งกรอบ Custom (Admin อัพโหลด) และ Fallback (CSS-based)
 *
 * @param \App\Models\User|null $user - ผู้ใช้ (ถ้าไม่ระบุจะใช้ auth()->user())
 * @param int|null $rankLevel - Level ของ Rank (1-8, ถ้าไม่ระบุจะดึงจาก user)
 * @param \App\Models\Rank|null $rank - Model Rank (optional)
 * @param string $size - ขนาด: xs, sm, md, lg, xl, 2xl
 * @param string|null $src - URL รูป Avatar (ถ้าไม่ระบุจะดึงจาก user)
 * @param bool $showBadge - แสดง badge rank หรือไม่
 * @param bool $animate - เปิด animation หรือไม่
 * @param string $class - CSS class เพิ่มเติม
 *
 * @example
 * <x-rank-avatar :user="$user" size="lg" />
 * <x-rank-avatar :rank-level="5" :src="$avatarUrl" size="xl" />
 *
 * @version 1.0.0
 * @date 2025-11-26
 */
--}}

@props([
    'user' => null,
    'rankLevel' => null,
    'rank' => null,
    'size' => 'md',
    'src' => null,
    'showBadge' => true,
    'animate' => true,
    'class' => '',
])

@php
    // ดึงข้อมูล User และ Rank
    $currentUser = $user ?? auth()->user();
    $currentRank = $rank ?? $currentUser?->currentRank;
    $level = $rankLevel ?? $currentRank?->level ?? 1;

    // ดึง URL Avatar
    $avatarUrl = $src ?? $currentUser?->profile_picture_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($currentUser?->name ?? 'U') . '&background=random';

    // ดึง Custom Frame URL
    $customFrameUrl = $currentRank?->avatar_frame_url;
    $hasCustomFrame = !empty($customFrameUrl);

    // Animation type
    $frameAnimation = $currentRank?->frame_animation ?? 'none';
    if (!$animate) {
        $frameAnimation = 'none';
    }

    // Size classes
    $sizeClasses = [
        'xs' => 'w-8 h-8',
        'sm' => 'w-12 h-12',
        'md' => 'w-16 h-16',
        'lg' => 'w-24 h-24',
        'xl' => 'w-32 h-32',
        '2xl' => 'w-40 h-40',
    ];
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];

    // Frame sizes (slightly larger than avatar)
    $frameSizeClasses = [
        'xs' => 'w-10 h-10',
        'sm' => 'w-14 h-14',
        'md' => 'w-20 h-20',
        'lg' => 'w-28 h-28',
        'xl' => 'w-36 h-36',
        '2xl' => 'w-44 h-44',
    ];
    $frameSizeClass = $frameSizeClasses[$size] ?? $frameSizeClasses['md'];

    // Badge sizes
    $badgeSizeClasses = [
        'xs' => 'text-xs w-4 h-4',
        'sm' => 'text-sm w-5 h-5',
        'md' => 'text-base w-6 h-6',
        'lg' => 'text-lg w-8 h-8',
        'xl' => 'text-xl w-10 h-10',
        '2xl' => 'text-2xl w-12 h-12',
    ];
    $badgeSizeClass = $badgeSizeClasses[$size] ?? $badgeSizeClasses['md'];

    // Rank Badges
    $rankBadges = [
        1 => '🥉', 2 => '🥈', 3 => '🥇', 4 => '💎',
        5 => '💠', 6 => '👑', 7 => '🏆', 8 => '⭐',
    ];
    $rankBadge = $rankBadges[$level] ?? '🏅';

    // Animation class mapping
    $animationClasses = [
        'none' => '',
        'pulse' => 'animate-pulse-frame',
        'glow' => 'animate-glow-frame',
        'sparkle' => 'animate-sparkle-frame',
        'rotate' => 'animate-rotate-frame',
    ];
    $animationClass = $animationClasses[$frameAnimation] ?? '';
@endphp

<div {{ $attributes->merge(['class' => "rank-avatar-container relative inline-flex items-center justify-center {$class}"]) }}>
    {{-- Avatar Container with Frame --}}
    <div class="relative {{ $frameSizeClass }} flex items-center justify-center">

        {{-- Custom Frame (ถ้า Admin อัพโหลดไว้) --}}
        @if($hasCustomFrame)
            <img src="{{ $customFrameUrl }}"
                 alt="Rank Frame"
                 class="absolute inset-0 w-full h-full object-contain z-10 pointer-events-none {{ $animationClass }}"
                 loading="lazy">
        @else
            {{-- Fallback Frame (CSS-based) --}}
            @include('components.rank-avatar-frames.level-' . $level, [
                'size' => $size,
                'animate' => $animate,
            ])
        @endif

        {{-- Avatar Image --}}
        <div class="{{ $sizeClass }} rounded-full overflow-hidden z-0 relative">
            <img src="{{ $avatarUrl }}"
                 alt="{{ $currentUser?->name ?? 'User' }}"
                 class="w-full h-full object-cover">
        </div>
    </div>

    {{-- Rank Badge (optional) --}}
    @if($showBadge && $level >= 2)
        <div class="absolute -bottom-1 -right-1 {{ $badgeSizeClass }} flex items-center justify-center z-20
            {{ $level >= 5 ? 'animate-bounce-slow' : '' }}">
            <span class="drop-shadow-lg">{{ $rankBadge }}</span>
        </div>
    @endif
</div>

{{-- Avatar Frame Animations --}}
@once
@push('styles')
<style>
    /* Frame Animations */
    @keyframes pulse-frame {
        0%, 100% { opacity: 0.9; transform: scale(1); }
        50% { opacity: 1; transform: scale(1.02); }
    }

    @keyframes glow-frame {
        0%, 100% { filter: drop-shadow(0 0 5px currentColor); }
        50% { filter: drop-shadow(0 0 15px currentColor); }
    }

    @keyframes sparkle-frame {
        0%, 100% { filter: brightness(1) saturate(1); }
        25% { filter: brightness(1.2) saturate(1.1); }
        50% { filter: brightness(1.1) saturate(1.2); }
        75% { filter: brightness(1.3) saturate(1.1); }
    }

    @keyframes rotate-frame {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes bounce-slow {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }

    .animate-pulse-frame {
        animation: pulse-frame 2s ease-in-out infinite;
    }

    .animate-glow-frame {
        animation: glow-frame 2s ease-in-out infinite;
    }

    .animate-sparkle-frame {
        animation: sparkle-frame 1.5s ease-in-out infinite;
    }

    .animate-rotate-frame {
        animation: rotate-frame 20s linear infinite;
    }

    .animate-bounce-slow {
        animation: bounce-slow 2s ease-in-out infinite;
    }
</style>
@endpush
@endonce
