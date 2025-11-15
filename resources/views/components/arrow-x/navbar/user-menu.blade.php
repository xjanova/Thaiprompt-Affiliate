{{--
    Arrow X Navbar User Menu Component

    User profile dropdown menu

    @props
    - name: string - ชื่อผู้ใช้ [default: 'User']
    - avatar: string|null - URL รูปโปรไฟล์ [optional]
    - role: string|null - บทบาท/ตำแหน่ง [optional]
    - menuItems: array - รายการเมนู [optional]

    @example
    <x-arrow-x.navbar.user-menu
        name="John Doe"
        role="Admin"
        :menuItems="[
            ['label' => 'โปรไฟล์', 'icon' => 'fa-user', 'href' => '/profile'],
            ['label' => 'ออกจากระบบ', 'icon' => 'fa-sign-out-alt', 'href' => '/logout'],
        ]"
    />
--}}

@props([
    'name' => 'User',
    'avatar' => null,
    'role' => null,
    'menuItems' => [],
])

@php
    // Default menu items
    $defaultMenuItems = [
        ['label' => 'โปรไฟล์', 'icon' => 'fa-user', 'href' => '/profile'],
        ['label' => 'ตั้งค่า', 'icon' => 'fa-cog', 'href' => '/settings'],
        ['divider' => true],
        ['label' => 'ออกจากระบบ', 'icon' => 'fa-sign-out-alt', 'href' => '/logout', 'danger' => true],
    ];

    $items = !empty($menuItems) ? $menuItems : $defaultMenuItems;
@endphp

<div x-data="{ open: false }" class="relative">
    {{-- User Button --}}
    <button
        @click="open = !open"
        class="flex items-center gap-3 px-3 py-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    >
        {{-- Avatar --}}
        @if($avatar)
            <img src="{{ $avatar }}" alt="{{ $name }}" class="h-9 w-9 rounded-full object-cover border-2 border-gray-200 dark:border-gray-600">
        @else
            <div class="h-9 w-9 rounded-full bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 flex items-center justify-center text-white font-bold text-sm border-2 border-white dark:border-gray-700">
                {{ substr($name, 0, 2) }}
            </div>
        @endif

        {{-- Name (Hidden on mobile) --}}
        <div class="hidden md:block text-left">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $name }}</p>
            @if($role)
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $role }}</p>
            @endif
        </div>

        {{-- Chevron --}}
        <i class="fas fa-chevron-down text-xs text-gray-500 dark:text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
    </button>

    {{-- Dropdown Menu --}}
    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        x-cloak
    >
        {{-- User Info --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <p class="font-semibold text-gray-900 dark:text-white">{{ $name }}</p>
            @if($role)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $role }}</p>
            @endif
        </div>

        {{-- Menu Items --}}
        <div class="py-2">
            @foreach($items as $item)
                @if(isset($item['divider']) && $item['divider'])
                    <hr class="my-2 border-gray-200 dark:border-gray-700">
                @else
                    <a
                        href="{{ $item['href'] ?? '#' }}"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ isset($item['danger']) && $item['danger'] ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-gray-300' }}"
                    >
                        <i class="fas {{ $item['icon'] ?? 'fa-circle' }} w-5 text-center"></i>
                        <span class="text-sm font-medium">{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>
