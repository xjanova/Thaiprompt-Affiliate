{{--
    Arrow X Sidebar Component

    Sidebar navigation แบบ modern พร้อม collapsible และ nested menus

    @props
    - title: string - ชื่อแบรนด์/โลโก้ [default: 'Arrow X']
    - logo: string|null - URL โลโก้ [optional]
    - width: string - ความกว้าง (sm/md/lg) [default: md]
    - collapsible: bool - สามารถย่อขยายได้ [default: true]
    - backdrop: bool - แสดง backdrop บน mobile [default: true]
    - glassmorph: bool - เปิดใช้ glassmorphism [default: true]

    @slots
    - menu: รายการเมนู (ใช้กับ arrow-x.sidebar.item)
    - footer: ส่วนท้าย sidebar

    @example
    <x-arrow-x.sidebar title="My App">
        <x-slot:menu>
            <x-arrow-x.sidebar.item icon="fa-home" href="/dashboard">หน้าแรก</x-arrow-x.sidebar.item>
        </x-slot:menu>
    </x-arrow-x.sidebar>
--}}

@props([
    'title' => 'Arrow X',
    'logo' => null,
    'width' => 'md',
    'collapsible' => true,
    'backdrop' => true,
    'glassmorph' => true,
])

@php
    // Width classes
    $widthClasses = [
        'sm' => 'w-64',
        'md' => 'w-72',
        'lg' => 'w-80',
    ];

    $widthClass = $widthClasses[$width] ?? $widthClasses['md'];

    // Glassmorphism
    $glassClasses = $glassmorph
        ? 'backdrop-blur-xl bg-white/95 dark:bg-gray-800/95 border-r border-white/20 dark:border-gray-700/50'
        : 'bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700';
@endphp

<div
    x-data="{
        open: false,
        collapsed: false,
        activeSubmenu: null,
        toggleSubmenu(id) {
            this.activeSubmenu = this.activeSubmenu === id ? null : id;
        }
    }"
    {{ $attributes }}
>
    {{-- Mobile Backdrop --}}
    @if($backdrop)
        <div
            x-show="open"
            @click="open = false"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm z-40 lg:hidden"
            x-cloak
        ></div>
    @endif

    {{-- Sidebar --}}
    <aside
        :class="{ 'translate-x-0': open, '-translate-x-full lg:translate-x-0': !open, 'w-20': collapsed && !open, '{{ $widthClass }}': !collapsed || open }"
        class="fixed top-0 left-0 z-50 h-screen {{ $glassClasses }} shadow-2xl transition-all duration-300 flex flex-col"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 dark:border-gray-700">
            {{-- Logo & Title --}}
            <div class="flex items-center gap-3 min-w-0">
                @if($logo)
                    <img src="{{ $logo }}" alt="{{ $title }}" class="h-8 w-8 flex-shrink-0 rounded-lg">
                @else
                    <div class="h-8 w-8 flex-shrink-0 rounded-lg bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 flex items-center justify-center text-white font-bold text-sm">
                        {{ substr($title, 0, 2) }}
                    </div>
                @endif

                <h2
                    x-show="!collapsed || open"
                    x-transition
                    class="font-bold text-lg text-gray-900 dark:text-white truncate"
                >
                    {{ $title }}
                </h2>
            </div>

            {{-- Collapse Toggle (Desktop) --}}
            @if($collapsible)
                <button
                    @click="collapsed = !collapsed"
                    class="hidden lg:flex p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition-colors"
                    x-show="!open"
                >
                    <i class="fas fa-bars" x-show="!collapsed"></i>
                    <i class="fas fa-arrow-right" x-show="collapsed"></i>
                </button>
            @endif

            {{-- Close Button (Mobile) --}}
            <button
                @click="open = false"
                class="lg:hidden p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400"
            >
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Menu --}}
        <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-1 custom-scrollbar">
            {{ $menu ?? $slot }}
        </nav>

        {{-- Footer --}}
        @if(isset($footer))
            <div class="border-t border-gray-200 dark:border-gray-700 p-4">
                {{ $footer }}
            </div>
        @endif
    </aside>

    {{-- Mobile Toggle Button --}}
    <button
        @click="open = !open"
        class="fixed top-4 left-4 z-30 lg:hidden p-3 rounded-xl bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white shadow-lg"
    >
        <i class="fas fa-bars"></i>
    </button>
</div>

<style>
    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.3);
        border-radius: 3px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(156, 163, 175, 0.5);
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.1);
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.2);
    }
</style>
