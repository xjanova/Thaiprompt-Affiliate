{{--
    Arrow X Sidebar Item Component

    รายการเมนูใน sidebar พร้อม submenu support

    @props
    - icon: string - Font Awesome icon class
    - href: string|null - URL ลิงก์ [optional]
    - active: bool - สถานะ active [default: false]
    - badge: string|null - Badge text [optional]
    - badgeColor: string - สี badge (purple/blue/green/orange/red) [default: purple]
    - submenu: array|null - Submenu items [optional]

    @example
    <x-arrow-x.sidebar.item icon="fa-home" href="/dashboard" :active="true">
        หน้าแรก
    </x-arrow-x.sidebar.item>
--}}

@props([
    'icon' => 'fa-circle',
    'href' => null,
    'active' => false,
    'badge' => null,
    'badgeColor' => 'purple',
    'submenu' => null,
])

@php
    // Badge color classes
    $badgeColors = [
        'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300',
        'blue' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300',
        'green' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
        'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300',
        'red' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300',
    ];

    $badgeColorClass = $badgeColors[$badgeColor] ?? $badgeColors['purple'];

    // Active state classes
    $activeClasses = $active
        ? 'bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white shadow-lg'
        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700';

    // Base classes
    $baseClasses = "flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-200 group";

    // Tag type
    $tag = $href ? 'a' : 'div';
    $tagAttrs = $href ? ['href' => $href] : [];

    // Has submenu
    $hasSubmenu = !empty($submenu);
    $submenuId = 'submenu-' . uniqid();
@endphp

@if($hasSubmenu)
    {{-- Menu Item with Submenu --}}
    <div>
        <button
            @click="toggleSubmenu('{{ $submenuId }}')"
            class="{{ $baseClasses }} {{ $activeClasses }} w-full text-left"
        >
            {{-- Icon --}}
            <i class="fas {{ $icon }} text-lg flex-shrink-0"></i>

            {{-- Label --}}
            <span class="flex-1 font-medium truncate" x-show="!$root.collapsed || $root.open" x-transition>
                {{ $slot }}
            </span>

            {{-- Badge --}}
            @if($badge)
                <span
                    class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $badgeColorClass }}"
                    x-show="!$root.collapsed || $root.open"
                    x-transition
                >
                    {{ $badge }}
                </span>
            @endif

            {{-- Submenu Arrow --}}
            <i
                class="fas fa-chevron-down text-sm transition-transform duration-200"
                :class="{ 'rotate-180': $root.activeSubmenu === '{{ $submenuId }}' }"
                x-show="!$root.collapsed || $root.open"
            ></i>
        </button>

        {{-- Submenu Items --}}
        <div
            x-show="$root.activeSubmenu === '{{ $submenuId }}' && (!$root.collapsed || $root.open)"
            x-transition
            class="ml-6 mt-1 space-y-1"
        >
            @foreach($submenu as $item)
                <a
                    href="{{ $item['href'] ?? '#' }}"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ ($item['active'] ?? false) ? 'bg-gray-100 dark:bg-gray-700 font-semibold' : '' }}"
                >
                    <i class="fas fa-circle text-xs"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@else
    {{-- Regular Menu Item --}}
    <{{ $tag }} {{ $attributes->merge(['class' => "{$baseClasses} {$activeClasses}"]) }} @foreach($tagAttrs as $key => $value) {{ $key }}="{{ $value }}" @endforeach>
        {{-- Icon --}}
        <i class="fas {{ $icon }} text-lg flex-shrink-0 {{ $active ? 'text-white' : 'group-hover:scale-110 transition-transform' }}"></i>

        {{-- Label --}}
        <span class="flex-1 font-medium truncate" x-show="!$root.collapsed || $root.open" x-transition>
            {{ $slot }}
        </span>

        {{-- Badge --}}
        @if($badge)
            <span
                class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $active ? 'bg-white/20 text-white' : $badgeColorClass }}"
                x-show="!$root.collapsed || $root.open"
                x-transition
            >
                {{ $badge }}
            </span>
        @endif
    </{{ $tag }}>
@endif
