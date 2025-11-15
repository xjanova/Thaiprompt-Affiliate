{{--
    Arrow X Button Component

    ปุ่มแบบ modern พร้อม gradient, loading state, และ RGB effects

    @props
    - variant: string - รูปแบบปุ่ม (primary/secondary/gradient/outline/ghost) [default: primary]
    - color: string - สีหลัก (purple/blue/green/orange/pink/red) [default: purple]
    - size: string - ขนาดปุ่ม (xs/sm/md/lg/xl) [default: md]
    - icon: string|null - Font Awesome icon class [optional]
    - iconPosition: string - ตำแหน่ง icon (left/right) [default: left]
    - loading: bool - แสดง loading state [default: false]
    - disabled: bool - ปิดการใช้งาน [default: false]
    - fullWidth: bool - ขยายเต็มความกว้าง [default: false]
    - href: string|null - URL สำหรับ link button [optional]
    - type: string - ประเภท button (button/submit/reset) [default: button]
--}}

@props([
    'variant' => 'primary',
    'color' => 'purple',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
    'disabled' => false,
    'fullWidth' => false,
    'href' => null,
    'type' => 'button',
])

@php
    // กำหนด gradient classes
    $gradientClasses = [
        'purple' => 'from-purple-500 via-pink-500 to-orange-500',
        'blue' => 'from-blue-500 via-cyan-500 to-teal-500',
        'green' => 'from-green-500 via-emerald-500 to-teal-500',
        'orange' => 'from-orange-500 via-red-500 to-pink-500',
        'pink' => 'from-pink-500 via-rose-500 to-red-500',
        'red' => 'from-red-500 via-orange-500 to-yellow-500',
    ];

    $gradientClass = $gradientClasses[$color] ?? $gradientClasses['purple'];

    // กำหนด variant classes
    $variantClasses = [
        'primary' => "bg-{$color}-600 hover:bg-{$color}-700 text-white shadow-lg hover:shadow-xl",
        'secondary' => "bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white shadow-lg hover:shadow-xl",
        'gradient' => "bg-gradient-to-r {$gradientClass} text-white shadow-lg hover:shadow-2xl hover:scale-105",
        'outline' => "border-2 border-{$color}-600 text-{$color}-600 dark:text-{$color}-400 hover:bg-{$color}-50 dark:hover:bg-{$color}-900/20",
        'ghost' => "text-{$color}-600 dark:text-{$color}-400 hover:bg-{$color}-50 dark:hover:bg-{$color}-900/20",
    ];

    $variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];

    // กำหนด size classes
    $sizeClasses = [
        'xs' => 'px-3 py-1.5 text-xs',
        'sm' => 'px-4 py-2 text-sm',
        'md' => 'px-6 py-3 text-base',
        'lg' => 'px-8 py-4 text-lg',
        'xl' => 'px-10 py-5 text-xl',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];

    // Full width class
    $widthClass = $fullWidth ? 'w-full' : '';

    // Disabled state
    $disabledClass = ($disabled || $loading)
        ? 'opacity-50 cursor-not-allowed pointer-events-none'
        : 'cursor-pointer';

    // Base classes
    $baseClasses = "inline-flex items-center justify-center gap-2 font-semibold rounded-xl transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-{$color}-500/50";

    // Combine all classes
    $classes = "{$baseClasses} {$variantClass} {$sizeClass} {$widthClass} {$disabledClass}";

    // Tag type
    $tag = $href ? 'a' : 'button';
    $tagAttrs = $href ? ['href' => $href] : ['type' => $type];
@endphp

<{{ $tag }} {{ $attributes->merge(['class' => $classes]) }} @foreach($tagAttrs as $key => $value) {{ $key }}="{{ $value }}" @endforeach>
    {{-- Loading spinner --}}
    @if($loading)
        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @endif

    {{-- Left icon --}}
    @if($icon && $iconPosition === 'left' && !$loading)
        <i class="fas {{ $icon }}"></i>
    @endif

    {{-- Button text --}}
    <span>{{ $slot }}</span>

    {{-- Right icon --}}
    @if($icon && $iconPosition === 'right' && !$loading)
        <i class="fas {{ $icon }}"></i>
    @endif
</{{ $tag }}>
