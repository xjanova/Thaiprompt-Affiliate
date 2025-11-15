{{--
    Arrow X Language Switcher Component

    Component สำหรับเปลี่ยนภาษา พร้อม flag icons

    @props
    - position: string - ตำแหน่ง (topbar/sidebar/footer) [default: topbar]
    - variant: string - รูปแบบ (dropdown/flags/text) [default: dropdown]
    - showFlags: bool - แสดง flag icons [default: true]
    - showText: bool - แสดงชื่อภาษา [default: true]
    - currentLang: string - ภาษาปัจจุบัน [default: 'th']

    @example
    <x-arrow-x.language-switcher />
--}}

@props([
    'position' => 'topbar',
    'variant' => 'dropdown',
    'showFlags' => true,
    'showText' => true,
    'currentLang' => app()->getLocale() ?? 'th',
])

@php
    // รายการภาษาที่รองรับ
    $languages = [
        'th' => ['name' => 'ไทย', 'flag' => '🇹🇭', 'nativeName' => 'ไทย'],
        'en' => ['name' => 'English', 'flag' => '🇺🇸', 'nativeName' => 'English'],
        'zh-CN' => ['name' => 'Chinese (Simplified)', 'flag' => '🇨🇳', 'nativeName' => '中文'],
        'zh-TW' => ['name' => 'Chinese (Traditional)', 'flag' => '🇹🇼', 'nativeName' => '繁體中文'],
        'ja' => ['name' => 'Japanese', 'flag' => '🇯🇵', 'nativeName' => '日本語'],
        'ko' => ['name' => 'Korean', 'flag' => '🇰🇷', 'nativeName' => '한국어'],
        'vi' => ['name' => 'Vietnamese', 'flag' => '🇻🇳', 'nativeName' => 'Tiếng Việt'],
        'id' => ['name' => 'Indonesian', 'flag' => '🇮🇩', 'nativeName' => 'Bahasa Indonesia'],
        'ms' => ['name' => 'Malay', 'flag' => '🇲🇾', 'nativeName' => 'Bahasa Melayu'],
        'es' => ['name' => 'Spanish', 'flag' => '🇪🇸', 'nativeName' => 'Español'],
        'fr' => ['name' => 'French', 'flag' => '🇫🇷', 'nativeName' => 'Français'],
        'de' => ['name' => 'German', 'flag' => '🇩🇪', 'nativeName' => 'Deutsch'],
        'ru' => ['name' => 'Russian', 'flag' => '🇷🇺', 'nativeName' => 'Русский'],
        'ar' => ['name' => 'Arabic', 'flag' => '🇸🇦', 'nativeName' => 'العربية'],
    ];

    $current = $languages[$currentLang] ?? $languages['th'];
@endphp

@if($variant === 'dropdown')
    {{-- Dropdown Style --}}
    <div x-data="{ open: false }" {{ $attributes->merge(['class' => 'relative']) }}>
        {{-- Trigger Button --}}
        <button
            @click="open = !open"
            class="flex items-center gap-2 px-3 py-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-gray-700 dark:text-gray-300"
        >
            @if($showFlags)
                <span class="text-xl">{{ $current['flag'] }}</span>
            @endif

            @if($showText)
                <span class="text-sm font-medium hidden md:inline">{{ $current['nativeName'] }}</span>
            @endif

            <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
        </button>

        {{-- Dropdown Menu --}}
        <div
            x-show="open"
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50"
            x-cloak
        >
            <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">เลือกภาษา</p>
            </div>

            <div class="py-2 max-h-96 overflow-y-auto">
                @foreach($languages as $code => $lang)
                    <a
                        href="{{ route('language.switch', $code) }}"
                        class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors {{ $currentLang === $code ? 'bg-purple-50 dark:bg-purple-900/20' : '' }}"
                    >
                        @if($showFlags)
                            <span class="text-xl flex-shrink-0">{{ $lang['flag'] }}</span>
                        @endif

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $lang['nativeName'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $lang['name'] }}</p>
                        </div>

                        @if($currentLang === $code)
                            <i class="fas fa-check text-purple-600 dark:text-purple-400"></i>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>

@elseif($variant === 'flags')
    {{-- Flag Grid Style --}}
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-2']) }}>
        @foreach($languages as $code => $lang)
            <a
                href="{{ route('language.switch', $code) }}"
                class="group relative flex items-center justify-center w-10 h-10 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all {{ $currentLang === $code ? 'bg-purple-100 dark:bg-purple-900/30 ring-2 ring-purple-500' : '' }}"
                title="{{ $lang['nativeName'] }}"
            >
                <span class="text-2xl">{{ $lang['flag'] }}</span>

                {{-- Tooltip --}}
                <div class="absolute bottom-full mb-2 hidden group-hover:block">
                    <div class="px-2 py-1 bg-gray-900 text-white text-xs rounded-lg whitespace-nowrap">
                        {{ $lang['nativeName'] }}
                    </div>
                </div>
            </a>
        @endforeach
    </div>

@else
    {{-- Text List Style --}}
    <div {{ $attributes->merge(['class' => 'space-y-1']) }}>
        @foreach($languages as $code => $lang)
            <a
                href="{{ route('language.switch', $code) }}"
                class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $currentLang === $code ? 'bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 font-semibold' : 'text-gray-700 dark:text-gray-300' }}"
            >
                @if($showFlags)
                    <span class="text-xl">{{ $lang['flag'] }}</span>
                @endif

                <span class="flex-1">{{ $lang['nativeName'] }}</span>

                @if($currentLang === $code)
                    <i class="fas fa-check text-sm"></i>
                @endif
            </a>
        @endforeach
    </div>
@endif
