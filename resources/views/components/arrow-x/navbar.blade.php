{{--
    Arrow X Navbar Component

    Navbar แบบ modern พร้อม search, notifications, และ user menu

    @props
    - sticky: bool - ติดด้านบน [default: true]
    - glassmorph: bool - เปิดใช้ glassmorphism [default: true]
    - shadow: string - ความเข้มเงา (none/sm/md/lg/xl) [default: lg]
    - showSearch: bool - แสดง search bar [default: true]

    @slots
    - left: เนื้อหาด้านซ้าย
    - center: เนื้อหากลาง
    - right: เนื้อหาด้านขวา (notifications, user menu)

    @example
    <x-arrow-x.navbar>
        <x-slot:left>
            <h1>Dashboard</h1>
        </x-slot:left>
        <x-slot:right>
            <x-arrow-x.navbar.notification />
            <x-arrow-x.navbar.user-menu />
        </x-slot:right>
    </x-arrow-x.navbar>
--}}

@props([
    'sticky' => true,
    'glassmorph' => true,
    'shadow' => 'lg',
    'showSearch' => true,
])

@php
    // Shadow classes
    $shadowClasses = [
        'none' => '',
        'sm' => 'shadow-sm',
        'md' => 'shadow-md',
        'lg' => 'shadow-lg',
        'xl' => 'shadow-xl',
    ];

    $shadowClass = $shadowClasses[$shadow] ?? $shadowClasses['lg'];

    // Glassmorphism
    $glassClasses = $glassmorph
        ? 'backdrop-blur-xl bg-white/95 dark:bg-gray-800/95 border-b border-white/20 dark:border-gray-700/50'
        : 'bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700';

    // Sticky class
    $stickyClass = $sticky ? 'sticky top-0' : '';
@endphp

<nav {{ $attributes->merge(['class' => "z-40 {$stickyClass} {$glassClasses} {$shadowClass} transition-all duration-300"]) }}>
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16 gap-4">
            {{-- Left Section --}}
            <div class="flex items-center gap-4 flex-shrink-0">
                {{ $left ?? '' }}
            </div>

            {{-- Center Section (Search) --}}
            <div class="flex-1 max-w-2xl">
                @if($showSearch)
                    <div class="relative" x-data="{ searchFocused: false }">
                        <input
                            type="search"
                            placeholder="ค้นหา..."
                            @focus="searchFocused = true"
                            @blur="searchFocused = false"
                            class="w-full px-4 py-2 pl-10 pr-4 rounded-xl bg-gray-100 dark:bg-gray-700 border-2 border-transparent focus:border-purple-500 focus:bg-white dark:focus:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-200 focus:outline-none"
                        />
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"></i>
                    </div>
                @else
                    {{ $center ?? '' }}
                @endif
            </div>

            {{-- Right Section --}}
            <div class="flex items-center gap-2 flex-shrink-0">
                {{ $right ?? $slot }}
            </div>
        </div>
    </div>
</nav>
