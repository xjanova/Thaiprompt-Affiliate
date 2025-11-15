{{--
    Arrow X Navbar Notification Component

    Notification dropdown พร้อมจำนวนแจ้งเตือน

    @props
    - count: int - จำนวนแจ้งเตือนที่ยังไม่อ่าน [default: 0]
    - notifications: array - รายการแจ้งเตือน [optional]

    @example
    <x-arrow-x.navbar.notification :count="5" :notifications="$notifications" />
--}}

@props([
    'count' => 0,
    'notifications' => [],
])

<div x-data="{ open: false }" class="relative">
    {{-- Notification Button --}}
    <button
        @click="open = !open"
        class="relative p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors"
    >
        <i class="fas fa-bell text-xl"></i>

        {{-- Badge Count --}}
        @if($count > 0)
            <span class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center bg-red-500 text-white text-xs font-bold rounded-full animate-pulse">
                {{ $count > 9 ? '9+' : $count }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        x-cloak
    >
        {{-- Header --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-bold text-gray-900 dark:text-white">แจ้งเตือน</h3>
            @if($count > 0)
                <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-xs font-semibold rounded-full">
                    {{ $count }} ใหม่
                </span>
            @endif
        </div>

        {{-- Notification List --}}
        <div class="max-h-96 overflow-y-auto">
            @if(!empty($notifications))
                @foreach($notifications as $notification)
                    <a
                        href="{{ $notification['url'] ?? '#' }}"
                        class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                    >
                        <div class="flex gap-3">
                            {{-- Icon --}}
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br {{ $notification['color'] ?? 'from-purple-500 to-pink-500' }} flex items-center justify-center text-white">
                                    <i class="fas {{ $notification['icon'] ?? 'fa-bell' }}"></i>
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $notification['title'] ?? '' }}
                                </p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    {{ $notification['message'] ?? '' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    {{ $notification['time'] ?? '' }}
                                </p>
                            </div>

                            {{-- Unread Indicator --}}
                            @if($notification['unread'] ?? false)
                                <div class="flex-shrink-0">
                                    <span class="h-2 w-2 bg-blue-500 rounded-full inline-block"></span>
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            @else
                <div class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-bell-slash text-4xl mb-2"></i>
                    <p class="text-sm">ไม่มีการแจ้งเตือน</p>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        @if(!empty($notifications))
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 text-center">
                <a href="#" class="text-sm text-purple-600 dark:text-purple-400 hover:underline font-semibold">
                    ดูทั้งหมด
                </a>
            </div>
        @endif
    </div>
</div>
