{{--
    Arrow X Card - Info Card Component

    Card สำหรับแสดงข้อมูลทั่วไปแบบ modern และ responsive

    @props
    - title: string|null - ชื่อหัวข้อ [optional]
    - subtitle: string|null - คำบรรยายย่อย [optional]
    - padding: string - ขนาด padding (sm/md/lg) [default: md]
    - shadow: string - ความเข้มเงา (none/sm/md/lg/xl) [default: lg]
    - glassmorph: bool - เปิดใช้ glassmorphism effect [default: true]
    - hover: bool - เปิดใช้ hover effect [default: true]
--}}

@props([
    'title' => null,
    'subtitle' => null,
    'padding' => 'md',
    'shadow' => 'lg',
    'glassmorph' => true,
    'hover' => true,
])

@php
    // กำหนด padding classes
    $paddingClasses = [
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-8',
    ];

    $paddingClass = $paddingClasses[$padding] ?? $paddingClasses['md'];

    // กำหนด shadow classes
    $shadowClasses = [
        'none' => '',
        'sm' => 'shadow-sm',
        'md' => 'shadow-md',
        'lg' => 'shadow-lg',
        'xl' => 'shadow-xl',
    ];

    $shadowClass = $shadowClasses[$shadow] ?? $shadowClasses['lg'];

    // Glassmorphism classes
    $glassClasses = $glassmorph
        ? 'backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50'
        : 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700';

    // Hover effect classes
    $hoverClasses = $hover
        ? 'hover:shadow-2xl hover:-translate-y-1 transition-all duration-300'
        : 'transition-shadow duration-300';
@endphp

<div {{ $attributes->merge(['class' => "relative rounded-2xl overflow-hidden {$shadowClass} {$glassClasses} {$hoverClasses}"]) }}>
    {{-- Header (if title provided) --}}
    @if($title)
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                {{ $title }}
            </h3>
            @if($subtitle)
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    @endif

    {{-- Body --}}
    <div class="{{ $paddingClass }}">
        {{ $slot }}
    </div>
</div>
