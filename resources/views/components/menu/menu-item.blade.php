{{--
    Menu Item Component - V3 Menu System

    แสดงรายการเมนูแต่ละรายการ (theme-agnostic)

    Props:
    - item: Menu item object
    - variant: รูปแบบการแสดงผล (default, compact, icon-only)

    @version 3.0.0
    @since 2025-11-15
--}}

@props([
    'item' => null,
    'variant' => 'default',
])

@php
    // ดึงข้อมูลจาก item
    $itemId = $item['id'] ?? uniqid('menu_');
    $label = $item['label'] ?? 'Menu';
    $icon = $item['icon'] ?? null;
    $url = $item['url'] ?? '#';
    $route = $item['route'] ?? null;
    $badge = $item['badge'] ?? null;
    $badgeColor = $item['badge_color'] ?? 'bg-blue-500';
    $hasSubmenu = !empty($item['submenu']);
@endphp

<div
    class="menu-item"
    x-data="{ isHovered: false }"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
>
    @if($hasSubmenu)
        {{-- เมนูที่มี submenu --}}
        <button
            type="button"
            @click="toggleSubmenu('{{ $itemId }}')"
            :class="{
                'menu-item-active': hasActiveSubmenu($parent.menuItems.find(m => m.id === '{{ $itemId }}')),
                'menu-item-open': openSubmenus['{{ $itemId }}']
            }"
            class="menu-item-button w-full flex items-center justify-between px-4 py-3 rounded-lg
                   transition-all duration-200 group
                   hover:bg-gray-100 dark:hover:bg-gray-700/50
                   text-gray-700 dark:text-gray-200"
        >
            <div class="flex items-center gap-3">
                {{-- Icon --}}
                @if($icon)
                <span class="menu-item-icon text-xl" x-show="!isHovered || openSubmenus['{{ $itemId }}']">
                    {{ $icon }}
                </span>
                <span class="menu-item-icon text-xl" x-show="isHovered && !openSubmenus['{{ $itemId }}']" x-cloak>
                    {{ $icon }}
                </span>
                @endif

                {{-- Label --}}
                <span class="menu-item-label font-medium">
                    {{ $label }}
                </span>

                {{-- Badge --}}
                @if($badge)
                <span class="menu-item-badge px-2 py-0.5 rounded-full text-xs font-semibold text-white {{ $badgeColor }}">
                    {{ $badge }}
                </span>
                @endif
            </div>

            {{-- Submenu Arrow --}}
            <svg
                class="menu-item-arrow w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': openSubmenus['{{ $itemId }}'] }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    @else
        {{-- เมนูธรรมดา (ไม่มี submenu) --}}
        <a
            href="{{ $url }}"
            :class="{
                'menu-item-active bg-primary-500/10 text-primary-600 dark:text-primary-400': isActive($parent.menuItems.find(m => m.id === '{{ $itemId }}')),
            }"
            class="menu-item-link w-full flex items-center gap-3 px-4 py-3 rounded-lg
                   transition-all duration-200 group
                   hover:bg-gray-100 dark:hover:bg-gray-700/50
                   text-gray-700 dark:text-gray-200"
        >
            {{-- Icon --}}
            @if($icon)
            <span class="menu-item-icon text-xl">
                {{ $icon }}
            </span>
            @endif

            {{-- Label --}}
            <span class="menu-item-label font-medium flex-1">
                {{ $label }}
            </span>

            {{-- Badge --}}
            @if($badge)
            <span class="menu-item-badge px-2 py-0.5 rounded-full text-xs font-semibold text-white {{ $badgeColor }}">
                {{ $badge }}
            </span>
            @endif
        </a>
    @endif
</div>
