{{--
    Arrow X Badge Component

    Badge/Tag สำหรับแสดง status หรือ label

    @props
    - variant: string - รูปแบบ (solid/outline/soft/gradient) [default: solid]
    - color: string - สีหลัก (purple/blue/green/orange/pink/red/gray/success/warning/error/info) [default: purple]
    - size: string - ขนาด (xs/sm/md/lg) [default: sm]
    - icon: string|null - Font Awesome icon class [optional]
    - dot: bool - แสดง dot indicator [default: false]
    - rounded: string - ความโค้งมน (md/lg/full) [default: full]
--}}

@props([
    'variant' => 'solid',
    'color' => 'purple',
    'size' => 'sm',
    'icon' => null,
    'dot' => false,
    'rounded' => 'full',
])

@php
    // Map สี status
    $statusColors = [
        'success' => 'green',
        'warning' => 'orange',
        'error' => 'red',
        'info' => 'blue',
    ];

    $mappedColor = $statusColors[$color] ?? $color;

    // กำหนด gradient classes
    $gradientClasses = [
        'purple' => 'from-purple-500 via-pink-500 to-orange-500',
        'blue' => 'from-blue-500 via-cyan-500 to-teal-500',
        'green' => 'from-green-500 via-emerald-500 to-teal-500',
        'orange' => 'from-orange-500 via-red-500 to-pink-500',
        'pink' => 'from-pink-500 via-rose-500 to-red-500',
        'red' => 'from-red-500 via-orange-500 to-yellow-500',
        'gray' => 'from-gray-500 via-gray-600 to-gray-700',
    ];

    $gradientClass = $gradientClasses[$mappedColor] ?? $gradientClasses['purple'];

    // กำหนด variant classes
    $variantClasses = [
        'solid' => "bg-{$mappedColor}-600 text-white",
        'outline' => "border-2 border-{$mappedColor}-600 text-{$mappedColor}-600 dark:text-{$mappedColor}-400",
        'soft' => "bg-{$mappedColor}-100 dark:bg-{$mappedColor}-900/30 text-{$mappedColor}-800 dark:text-{$mappedColor}-300",
        'gradient' => "bg-gradient-to-r {$gradientClass} text-white",
    ];

    $variantClass = $variantClasses[$variant] ?? $variantClasses['solid'];

    // กำหนด size classes
    $sizeClasses = [
        'xs' => 'px-2 py-0.5 text-xs',
        'sm' => 'px-2.5 py-1 text-xs',
        'md' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-4 py-2 text-base',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['sm'];

    // Rounded classes
    $roundedClasses = [
        'md' => 'rounded-md',
        'lg' => 'rounded-lg',
        'full' => 'rounded-full',
    ];

    $roundedClass = $roundedClasses[$rounded] ?? $roundedClasses['full'];

    // Base classes
    $baseClasses = "inline-flex items-center gap-1.5 font-semibold whitespace-nowrap transition-all duration-200";

    // Combine all classes
    $classes = "{$baseClasses} {$variantClass} {$sizeClass} {$roundedClass}";
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{-- Dot indicator --}}
    @if($dot)
        <span class="inline-block w-2 h-2 rounded-full bg-current animate-pulse"></span>
    @endif

    {{-- Icon --}}
    @if($icon)
        <i class="fas {{ $icon }}"></i>
    @endif

    {{-- Text --}}
    {{ $slot }}
</span>
