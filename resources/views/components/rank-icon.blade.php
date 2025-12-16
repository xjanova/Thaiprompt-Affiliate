{{--
    Rank Icon Component - แสดง icon ของ Rank อย่างสอดคล้องกัน

    การใช้งาน:
    <x-rank-icon :rank="$rank" />
    <x-rank-icon :rank="$rank" size="lg" />
    <x-rank-icon :rank="$rank" size="sm" show-name />

    Props:
    - rank: Rank model (required)
    - size: 'xs'|'sm'|'md'|'lg'|'xl' (default: 'md')
    - show-name: boolean - แสดงชื่อ rank หรือไม่
    - fallback: string - emoji fallback ถ้าไม่มี icon (default: '🏅')
--}}

@props([
    'rank' => null,
    'size' => 'md',
    'showName' => false,
    'fallback' => '🏅'
])

@php
    // ขนาดตาม size
    $sizes = [
        'xs' => ['container' => 'w-6 h-6', 'text' => 'text-sm', 'img' => 'h-4 w-4', 'name' => 'text-xs'],
        'sm' => ['container' => 'w-8 h-8', 'text' => 'text-lg', 'img' => 'h-6 w-6', 'name' => 'text-sm'],
        'md' => ['container' => 'w-10 h-10', 'text' => 'text-xl', 'img' => 'h-8 w-8', 'name' => 'text-sm'],
        'lg' => ['container' => 'w-16 h-16', 'text' => 'text-3xl', 'img' => 'h-12 w-12', 'name' => 'text-base'],
        'xl' => ['container' => 'w-24 h-24', 'text' => 'text-5xl', 'img' => 'h-20 w-20', 'name' => 'text-lg'],
        '2xl' => ['container' => 'w-32 h-32', 'text' => 'text-6xl', 'img' => 'h-24 w-24', 'name' => 'text-xl'],
    ];

    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $bgColor = $rank?->color ?? '#6B7280';
@endphp

@if($rank)
    <div class="flex items-center gap-2 {{ $attributes->get('class', '') }}">
        {{-- Icon Container --}}
        <div class="flex-shrink-0 {{ $sizeClass['container'] }} rounded-xl flex items-center justify-center overflow-hidden"
             style="background: linear-gradient(135deg, {{ $bgColor }}, {{ $bgColor }}99);">

            @if($rank->icon_type === 'image' && $rank->icon_url)
                {{-- รูปภาพ --}}
                <img src="{{ $rank->icon_url }}"
                     alt="{{ $rank->name }}"
                     class="{{ $sizeClass['img'] }} object-contain"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                {{-- Fallback ถ้าโหลดรูปไม่ได้ --}}
                <span class="{{ $sizeClass['text'] }} hidden items-center justify-center">
                    {{ $rank->display_icon }}
                </span>
            @elseif($rank->icon_type === 'fontawesome')
                {{-- FontAwesome icon --}}
                <i class="{{ $rank->icon }} text-white {{ $sizeClass['text'] }}"></i>
            @elseif($rank->icon_type === 'emoji' || $rank->badge_icon)
                {{-- Emoji --}}
                <span class="{{ $sizeClass['text'] }}">{{ $rank->display_icon }}</span>
            @else
                {{-- Fallback: ตัวอักษรแรกหรือ emoji default --}}
                <span class="text-white font-bold {{ $sizeClass['text'] }}">
                    {{ $rank->display_icon ?: $fallback }}
                </span>
            @endif
        </div>

        {{-- ชื่อ Rank (optional) --}}
        @if($showName)
            <div>
                <div class="{{ $sizeClass['name'] }} font-medium text-gray-900 dark:text-gray-100">
                    {{ $rank->display_name ?? $rank->name }}
                </div>
                @if($rank->name_th && $rank->name_th !== $rank->name)
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $rank->name_th }}</div>
                @endif
            </div>
        @endif
    </div>
@else
    {{-- ไม่มี Rank --}}
    <div class="flex items-center gap-2 {{ $attributes->get('class', '') }}">
        <div class="flex-shrink-0 {{ $sizeClass['container'] }} rounded-xl flex items-center justify-center bg-gray-200 dark:bg-gray-700">
            <span class="{{ $sizeClass['text'] }}">{{ $fallback }}</span>
        </div>
        @if($showName)
            <div class="{{ $sizeClass['name'] }} text-gray-500 dark:text-gray-400">
                ไม่มียศ
            </div>
        @endif
    </div>
@endif
