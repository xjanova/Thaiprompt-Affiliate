@props(['height' => 'h-10', 'class' => '', 'showText' => true, 'shrinkOnScroll' => false])

@php
    $logo = \App\Models\Setting::get('logo');
    $appName = \App\Models\Setting::get('app_name', 'TP-Affiliate');
    $appNameShort = mb_substr($appName, 0, 2);

    $heightClass = $height;
    $maxHeight = match($height) {
        'h-6' => '24px',
        'h-8' => '32px',
        'h-10' => '40px',
        'h-12' => '48px',
        'h-16' => '64px',
        default => '40px'
    };
@endphp

<div class="flex items-center gap-2 {{ $class }}" x-data="{ logoError: false }">
    @if($logo)
        <div x-show="!logoError" class="flex items-center">
            <img
                src="{{ asset($logo) }}"
                alt="{{ $appName }}"
                class="object-contain transition-all duration-300 ease-in-out {{ $heightClass }}"
                @error="logoError = true"
                onerror="this.style.display='none'; this.parentElement.parentElement.querySelector('.logo-fallback').style.display='flex';"
                style="max-height: {{ $maxHeight }}; width: auto;"
            />
        </div>
        <div class="logo-fallback flex items-center gap-2" style="display: none;">
            <div class="flex items-center justify-center {{ $heightClass }} aspect-square rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 shadow-md">
                <span class="text-white font-bold text-sm md:text-base">{{ $appNameShort }}</span>
            </div>
            @if($showText)
                <span class="text-xl md:text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">{{ $appName }}</span>
            @endif
        </div>
    @else
        <div class="flex items-center gap-2">
            <div class="flex items-center justify-center {{ $heightClass }} aspect-square rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 shadow-md ring-2 ring-indigo-200 dark:ring-indigo-800">
                <span class="text-white font-bold text-sm md:text-base">{{ $appNameShort }}</span>
            </div>
            @if($showText)
                <span class="text-xl md:text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">{{ $appName }}</span>
            @endif
        </div>
    @endif
</div>
