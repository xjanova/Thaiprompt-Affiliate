{{--
    Arrow X Form Checkbox Component

    Checkbox แบบ modern พร้อม label และ description

    @props
    - label: string - Label ของ checkbox
    - name: string - ชื่อ checkbox field
    - value: string - ค่าของ checkbox [default: 1]
    - checked: bool - สถานะเริ่มต้น [default: false]
    - description: string|null - คำอธิบาย [optional]
    - error: string|null - ข้อความ error [optional]
    - disabled: bool - ปิดการใช้งาน [default: false]
    - color: string - สี (purple/blue/green/orange) [default: purple]
    - size: string - ขนาด (sm/md/lg) [default: md]
--}}

@props([
    'label' => '',
    'name' => '',
    'value' => '1',
    'checked' => false,
    'description' => null,
    'error' => null,
    'disabled' => false,
    'color' => 'purple',
    'size' => 'md',
])

@php
    // Size classes
    $sizeClasses = [
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];

    // Label size
    $labelSizeClasses = [
        'sm' => 'text-sm',
        'md' => 'text-base',
        'lg' => 'text-lg',
    ];

    $labelSizeClass = $labelSizeClasses[$size] ?? $labelSizeClasses['md'];

    // Color classes
    $colorClasses = "text-{$color}-600 focus:ring-{$color}-500 focus:ring-offset-2";

    // Base checkbox classes
    $checkboxBaseClasses = "rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 transition-all duration-200 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed";

    $checkboxClasses = "{$checkboxBaseClasses} {$sizeClass} {$colorClasses}";
@endphp

<div {{ $attributes->class(['arrow-x-form-group']) }}>
    <div class="flex items-start gap-3">
        {{-- Checkbox --}}
        <div class="flex items-center h-6">
            <input
                type="checkbox"
                id="{{ $name }}"
                name="{{ $name }}"
                value="{{ $value }}"
                class="{{ $checkboxClasses }}"
                @if(old($name, $checked)) checked @endif
                @if($disabled) disabled @endif
            />
        </div>

        {{-- Label and Description --}}
        <div class="flex-1">
            <label for="{{ $name }}" class="font-medium text-gray-900 dark:text-white {{ $labelSizeClass }} cursor-pointer">
                {{ $label }}
            </label>

            @if($description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </p>
            @endif

            @if($error)
                <p class="mt-1 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>{{ $error }}</span>
                </p>
            @endif
        </div>
    </div>
</div>
