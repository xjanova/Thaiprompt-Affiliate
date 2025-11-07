@props([
    'label' => '',
    'name' => '',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'helpText' => '',
    'tooltip' => '',
    'icon' => null,
    'rows' => 4,
    'min' => null,
    'max' => null,
    'step' => null,
    'options' => [], // For select
    'multiple' => false,
])

<div class="space-y-2">
    <!-- Label with Tooltip -->
    <div class="flex items-center justify-between">
        <label for="{{ $name }}" class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
            @if($icon)
                <span class="text-gray-400">{!! $icon !!}</span>
            @endif
            {{ $label }}
            @if($required)
                <span class="text-red-500 text-base">*</span>
            @endif
        </label>

        @if($tooltip)
            <button type="button"
                    x-data="{ showTooltip: false }"
                    @mouseenter="showTooltip = true"
                    @mouseleave="showTooltip = false"
                    class="relative text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div x-show="showTooltip"
                     x-transition
                     class="absolute right-0 bottom-full mb-2 w-64 p-3 bg-gray-900 text-white text-xs rounded-lg shadow-lg z-10">
                    {{ $tooltip }}
                    <div class="absolute top-full right-4 w-2 h-2 bg-gray-900 transform rotate-45 -mt-1"></div>
                </div>
            </button>
        @endif
    </div>

    <!-- Input Field -->
    @if($type === 'textarea')
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            {{ $required ? 'required' : '' }}
            placeholder="{{ $placeholder }}"
            class="w-full px-4 py-3 text-base border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all resize-none"
        >{{ $value }}</textarea>
    @elseif($type === 'select')
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            {{ $multiple ? 'multiple' : '' }}
            {{ $required ? 'required' : '' }}
            class="w-full px-4 py-3 text-base border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
        >
            @foreach($options as $optValue => $optLabel)
                <option value="{{ $optValue }}" {{ $value == $optValue ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>
    @else
        <input
            type="{{ $type }}"
            id="{{ $name }}"
            name="{{ $name }}"
            value="{{ $value }}"
            {{ $required ? 'required' : '' }}
            placeholder="{{ $placeholder }}"
            @if($min !== null) min="{{ $min }}" @endif
            @if($max !== null) max="{{ $max }}" @endif
            @if($step !== null) step="{{ $step }}" @endif
            class="w-full px-4 py-3 text-base border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
        />
    @endif

    <!-- Help Text -->
    @if($helpText)
        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-start gap-2">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <span>{{ $helpText }}</span>
        </p>
    @endif

    <!-- Error Message -->
    @error($name)
        <p class="text-sm text-red-600 dark:text-red-400 flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ $message }}
        </p>
    @enderror
</div>
