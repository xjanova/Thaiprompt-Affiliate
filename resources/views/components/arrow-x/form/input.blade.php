{{--
    Arrow X Form Input Component

    Input field แบบ modern พร้อม validation states และ icons

    @props
    - label: string|null - Label ของ input [optional]
    - type: string - ประเภท input (text/email/password/number/tel/url) [default: text]
    - name: string - ชื่อ input field
    - value: string|null - ค่าเริ่มต้น [optional]
    - placeholder: string|null - Placeholder text [optional]
    - icon: string|null - Font Awesome icon class [optional]
    - iconPosition: string - ตำแหน่ง icon (left/right) [default: left]
    - error: string|null - ข้อความ error [optional]
    - help: string|null - ข้อความช่วยเหลือ [optional]
    - required: bool - จำเป็นต้องกรอก [default: false]
    - disabled: bool - ปิดการใช้งาน [default: false]
    - readonly: bool - อ่านอย่างเดียว [default: false]
    - size: string - ขนาด (sm/md/lg) [default: md]
--}}

@props([
    'label' => null,
    'type' => 'text',
    'name' => '',
    'value' => null,
    'placeholder' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'error' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'size' => 'md',
])

@php
    // Size classes
    $sizeClasses = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-3 text-base',
        'lg' => 'px-5 py-4 text-lg',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];

    // Error state classes
    $stateClasses = $error
        ? 'border-red-500 dark:border-red-500 focus:ring-red-500 focus:border-red-500'
        : 'border-gray-300 dark:border-gray-600 focus:ring-purple-500 focus:border-purple-500';

    // Icon padding
    $iconPaddingClasses = $icon
        ? ($iconPosition === 'left' ? 'pl-11' : 'pr-11')
        : '';

    // Base input classes
    $inputBaseClasses = "w-full rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 border-2 focus:outline-none focus:ring-4 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed";

    $inputClasses = "{$inputBaseClasses} {$sizeClass} {$stateClasses} {$iconPaddingClasses}";
@endphp

<div {{ $attributes->class(['arrow-x-form-group']) }}>
    {{-- Label --}}
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{-- Input wrapper --}}
    <div class="relative">
        {{-- Left icon --}}
        @if($icon && $iconPosition === 'left')
            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500">
                <i class="fas {{ $icon }}"></i>
            </div>
        @endif

        {{-- Input field --}}
        <input
            type="{{ $type }}"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            class="{{ $inputClasses }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
        />

        {{-- Right icon --}}
        @if($icon && $iconPosition === 'right')
            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500">
                <i class="fas {{ $icon }}"></i>
            </div>
        @endif
    </div>

    {{-- Error message --}}
    @if($error)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ $error }}</span>
        </p>
    @endif

    {{-- Help text --}}
    @if($help && !$error)
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ $help }}
        </p>
    @endif
</div>
