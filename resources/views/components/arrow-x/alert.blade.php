{{--
    Arrow X Alert Component

    Alert/Notification box สำหรับแสดงข้อความสำคัญ

    @props
    - type: string - ประเภท alert (success/warning/error/info) [default: info]
    - variant: string - รูปแบบ (solid/soft/outline/gradient) [default: soft]
    - title: string|null - หัวข้อ [optional]
    - icon: string|null - Font Awesome icon class (auto if null) [optional]
    - dismissible: bool - สามารถปิดได้ [default: false]
    - bordered: bool - มีขอบ [default: true]
--}}

@props([
    'type' => 'info',
    'variant' => 'soft',
    'title' => null,
    'icon' => null,
    'dismissible' => false,
    'bordered' => true,
])

@php
    // Auto icon based on type
    $autoIcons = [
        'success' => 'fa-check-circle',
        'warning' => 'fa-exclamation-triangle',
        'error' => 'fa-times-circle',
        'info' => 'fa-info-circle',
    ];

    $displayIcon = $icon ?? $autoIcons[$type] ?? 'fa-info-circle';

    // Color mapping
    $typeColors = [
        'success' => 'green',
        'warning' => 'orange',
        'error' => 'red',
        'info' => 'blue',
    ];

    $color = $typeColors[$type] ?? 'blue';

    // Gradient classes
    $gradientClasses = [
        'green' => 'from-green-500 via-emerald-500 to-teal-500',
        'orange' => 'from-orange-500 via-red-500 to-pink-500',
        'red' => 'from-red-500 via-orange-500 to-yellow-500',
        'blue' => 'from-blue-500 via-cyan-500 to-teal-500',
    ];

    $gradientClass = $gradientClasses[$color];

    // Variant classes
    $variantClasses = [
        'solid' => "bg-{$color}-600 text-white",
        'soft' => "bg-{$color}-50 dark:bg-{$color}-900/20 text-{$color}-800 dark:text-{$color}-300 border border-{$color}-200 dark:border-{$color}-800",
        'outline' => "bg-transparent border-2 border-{$color}-600 text-{$color}-700 dark:text-{$color}-400",
        'gradient' => "bg-gradient-to-r {$gradientClass} text-white",
    ];

    $variantClass = $variantClasses[$variant] ?? $variantClasses['soft'];

    // Border class
    $borderClass = $bordered && $variant !== 'outline' ? "border-l-4 border-{$color}-600" : '';

    // Base classes
    $baseClasses = "relative rounded-xl p-4 transition-all duration-300";

    // Combine classes
    $classes = "{$baseClasses} {$variantClass} {$borderClass}";
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}
     x-data="{ show: true }"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform scale-95"
     x-transition:enter-end="opacity-100 transform scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 transform scale-100"
     x-transition:leave-end="opacity-0 transform scale-95">

    <div class="flex items-start gap-3">
        {{-- Icon --}}
        <div class="flex-shrink-0">
            <i class="fas {{ $displayIcon }} text-xl"></i>
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0">
            @if($title)
                <h4 class="font-bold mb-1">{{ $title }}</h4>
            @endif
            <div class="text-sm">
                {{ $slot }}
            </div>
        </div>

        {{-- Dismiss button --}}
        @if($dismissible)
            <button
                @click="show = false"
                class="flex-shrink-0 ml-auto -mt-1 -mr-1 p-1 rounded-lg hover:bg-black/10 dark:hover:bg-white/10 transition-colors"
                aria-label="ปิด">
                <i class="fas fa-times"></i>
            </button>
        @endif
    </div>
</div>
