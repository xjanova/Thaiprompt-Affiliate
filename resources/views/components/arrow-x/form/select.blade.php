{{--
    Arrow X Form Select Component

    Select dropdown แบบ modern พร้อม validation states

    @props
    - label: string|null - Label ของ select [optional]
    - name: string - ชื่อ select field
    - options: array - ตัวเลือก (key => value pairs)
    - selected: string|null - ค่าที่เลือก [optional]
    - placeholder: string|null - Placeholder text [optional]
    - error: string|null - ข้อความ error [optional]
    - help: string|null - ข้อความช่วยเหลือ [optional]
    - required: bool - จำเป็นต้องเลือก [default: false]
    - disabled: bool - ปิดการใช้งาน [default: false]
    - size: string - ขนาด (sm/md/lg) [default: md]
--}}

@props([
    'label' => null,
    'name' => '',
    'options' => [],
    'selected' => null,
    'placeholder' => 'เลือก...',
    'error' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
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

    // Base select classes
    $selectBaseClasses = "w-full rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-2 focus:outline-none focus:ring-4 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed appearance-none cursor-pointer";

    $selectClasses = "{$selectBaseClasses} {$sizeClass} {$stateClasses}";
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

    {{-- Select wrapper --}}
    <div class="relative">
        {{-- Select field --}}
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            class="{{ $selectClasses }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
        >
            @if($placeholder)
                <option value="" @if(!$selected) selected @endif disabled>{{ $placeholder }}</option>
            @endif

            @foreach($options as $key => $value)
                <option value="{{ $key }}" @if(old($name, $selected) == $key) selected @endif>
                    {{ $value }}
                </option>
            @endforeach
        </select>

        {{-- Dropdown arrow icon --}}
        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 pointer-events-none">
            <i class="fas fa-chevron-down"></i>
        </div>
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
