{{--
    Arrow X Card - Stat Card Component

    Card สำหรับแสดงสถิติแบบ modern พร้อม icon และ trend indicator

    @props
    - title: string - ชื่อหัวข้อสถิติ
    - value: string|number - ค่าสถิติ
    - icon: string - Font Awesome icon class (e.g., 'fa-users')
    - trend: string|null - แนวโน้ม (up/down/neutral) [optional]
    - trendValue: string|null - ค่าแนวโน้ม เช่น '+12%' [optional]
    - color: string - สีหลัก (purple/blue/green/orange/pink/red) [default: purple]
    - glassmorph: bool - เปิดใช้ glassmorphism effect [default: true]
--}}

@props([
    'title' => 'Statistic',
    'value' => '0',
    'icon' => 'fa-chart-line',
    'trend' => null,
    'trendValue' => null,
    'color' => 'purple',
    'glassmorph' => true,
])

@php
    // กำหนด color classes
    $colorClasses = [
        'purple' => 'from-purple-500 via-pink-500 to-orange-500',
        'blue' => 'from-blue-500 via-cyan-500 to-teal-500',
        'green' => 'from-green-500 via-emerald-500 to-teal-500',
        'orange' => 'from-orange-500 via-red-500 to-pink-500',
        'pink' => 'from-pink-500 via-rose-500 to-red-500',
        'red' => 'from-red-500 via-orange-500 to-yellow-500',
    ];

    $gradientClass = $colorClasses[$color] ?? $colorClasses['purple'];

    // กำหนด trend icon และสี
    $trendIcons = [
        'up' => 'fa-arrow-up',
        'down' => 'fa-arrow-down',
        'neutral' => 'fa-minus',
    ];

    $trendColors = [
        'up' => 'text-green-500 dark:text-green-400',
        'down' => 'text-red-500 dark:text-red-400',
        'neutral' => 'text-gray-500 dark:text-gray-400',
    ];

    $trendIcon = $trendIcons[$trend] ?? '';
    $trendColor = $trendColors[$trend] ?? '';

    // Glassmorphism classes
    $glassClasses = $glassmorph
        ? 'backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50'
        : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';
@endphp

<div {{ $attributes->merge(['class' => "relative rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 {$glassClasses}"]) }}>
    {{-- Gradient accent bar --}}
    <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r {{ $gradientClass }}"></div>

    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            {{-- Icon with gradient background --}}
            <div class="flex items-center justify-center w-14 h-14 rounded-xl bg-gradient-to-br {{ $gradientClass }} text-white shadow-lg">
                <i class="fas {{ $icon }} text-2xl"></i>
            </div>

            {{-- Trend indicator --}}
            @if($trend && $trendValue)
                <div class="flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700/50">
                    <i class="fas {{ $trendIcon }} text-xs {{ $trendColor }}"></i>
                    <span class="text-sm font-semibold {{ $trendColor }}">{{ $trendValue }}</span>
                </div>
            @endif
        </div>

        {{-- Title --}}
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">
            {{ $title }}
        </h3>

        {{-- Value --}}
        <div class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ $value }}
        </div>

        {{-- Optional slot for additional content --}}
        @if($slot->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
