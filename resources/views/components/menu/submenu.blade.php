{{--
    Submenu Component - V3 Menu System

    แสดง submenu items (theme-agnostic)

    Props:
    - items: Array ของ submenu items
    - parentId: ID ของ parent menu
    - indent: ระยะ indent (px)

    @version 3.0.0
    @since 2025-11-15
--}}

@props([
    'items' => [],
    'parentId' => null,
    'indent' => 32,
])

<div class="submenu ml-{{ $indent / 4 }} mt-1 space-y-1">
    @foreach($items as $index => $subitem)
        @php
            $subitemId = $subitem['id'] ?? uniqid('submenu_');
            $label = $subitem['label'] ?? 'Submenu';
            $url = $subitem['url'] ?? '#';
            $route = $subitem['route'] ?? null;
            $icon = $subitem['icon'] ?? null;
        @endphp

        <a
            href="{{ $url }}"
            :class="{
                'submenu-item-active bg-primary-500/10 text-primary-600 dark:text-primary-400 border-l-2 border-primary-500':
                    activeRoute === '{{ $route }}'
            }"
            class="submenu-item block px-4 py-2 rounded-lg
                   transition-all duration-200
                   hover:bg-gray-50 dark:hover:bg-gray-700/30
                   text-sm text-gray-600 dark:text-gray-300
                   border-l-2 border-transparent"
        >
            <div class="flex items-center gap-2">
                {{-- Bullet Point --}}
                <span class="submenu-bullet w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500"></span>

                {{-- Icon (optional) --}}
                @if($icon)
                <span class="submenu-icon">{{ $icon }}</span>
                @endif

                {{-- Label --}}
                <span class="submenu-label">{{ $label }}</span>
            </div>
        </a>
    @endforeach
</div>
