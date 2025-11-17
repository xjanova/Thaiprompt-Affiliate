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
    {{-- Notification Button พร้อม SVG Icon (V3 Standards) --}}
    <button
        @click="open = !open"
        aria-label="การแจ้งเตือน"
        :aria-expanded="open.toString()"
        aria-haspopup="true"
        class="relative p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-purple-500"
    >
        {{-- SVG Bell Icon แทน Font Awesome --}}
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        {{-- Badge Count --}}
        @if($count > 0)
            <span class="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center bg-red-500 text-white text-xs font-bold rounded-full animate-pulse" aria-live="polite" aria-label="{{ $count }} รายการที่ยังไม่ได้อ่าน">
                {{ $count > 9 ? '9+' : $count }}
            </span>
        @endif
    </button>

    {{-- Dropdown พร้อม Accessibility --}}
    <div
        x-show="open"
        @click.away="open = false"
        @keydown.escape.window="open = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        role="menu"
        aria-orientation="vertical"
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
                            {{-- Icon พร้อม SVG (V3 Standards) --}}
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br {{ $notification['color'] ?? 'from-purple-500 to-pink-500' }} flex items-center justify-center text-white" aria-hidden="true">
                                    {{-- แสดง emoji หรือ SVG bell --}}
                                    @if(isset($notification['emoji']))
                                        <span class="text-xl">{{ $notification['emoji'] }}</span>
                                    @else
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
                                        </svg>
                                    @endif
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
                    {{-- SVG Bell Slash Icon แทน Font Awesome --}}
                    <svg class="w-16 h-16 mx-auto mb-2 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4M3 3l18 18"/>
                    </svg>
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
