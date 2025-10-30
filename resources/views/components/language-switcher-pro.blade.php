{{-- Professional Dynamic Language Switcher --}}
@php
    use App\Models\LanguageSetting;
    use App\Models\Setting;

    $languages = LanguageSetting::getEnabled();
    $style = Setting::get('language_switcher_style') ?? 'dropdown';
    $showFlags = filter_var(Setting::get('language_switcher_show_flags') ?? 'true', FILTER_VALIDATE_BOOLEAN);
    $flagSize = (int) (Setting::get('language_switcher_flag_size') ?? 24);
    $showName = filter_var(Setting::get('language_switcher_show_name') ?? 'true', FILTER_VALIDATE_BOOLEAN);
    $currentLang = app()->getLocale();
    $currentLanguage = $languages->firstWhere('code', $currentLang) ?? $languages->first();
@endphp

<div class="language-switcher-pro" x-data="{
    open: false,
    currentLang: '{{ $currentLang }}',
    availableLanguages: @js($languages->values()->toArray()),
    style: '{{ $style }}',
    showFlags: {{ $showFlags ? 'true' : 'false' }},
    flagSize: {{ $flagSize }},
    showName: {{ $showName ? 'true' : 'false' }},

    getFlagUrl(lang) {
        // Map language code to country code for flag CDN
        const langToCountryMap = {
            'th': 'th', 'en': 'us', 'zh': 'cn', 'ja': 'jp', 'ko': 'kr',
            'vi': 'vn', 'es': 'es', 'fr': 'fr', 'de': 'de', 'pt': 'pt',
            'ru': 'ru', 'ar': 'sa', 'hi': 'in'
        };

        const countryCode = langToCountryMap[lang.code] || 'un';
        const sizeParam = this.flagSize <= 20 ? 'w20' : this.flagSize <= 40 ? 'w40' : 'w80';

        return `https://flagcdn.com/${sizeParam}/${countryCode}.png`;
    },

    async switchLanguage(langCode) {
        // Use Google Translate Widget for instant translation (like WordPress plugins)
        if (typeof window.changeGoogleTranslateLanguage === 'function') {
            window.changeGoogleTranslateLanguage(langCode);
        } else {
            // Fallback to session-based language change
            window.location.href = '/lang/' + langCode;
        }

        this.currentLang = langCode;
        this.open = false;
    },

    getCurrentLanguage() {
        return this.availableLanguages.find(lang => lang.code === this.currentLang) || this.availableLanguages[0];
    }
}">

    @if($style === 'dropdown')
        {{-- Dropdown Style --}}
        <div class="relative inline-block text-left">
            <button @click="open = !open" type="button"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                @if($showFlags)
                    <img :src="getFlagUrl(getCurrentLanguage())"
                         :alt="getCurrentLanguage().name"
                         :width="flagSize"
                         :height="flagSize * 0.75"
                         class="inline-block rounded object-cover"
                         style="min-width: 20px; min-height: 15px;">
                @endif
                @if($showName)
                    <span class="@if($showFlags) ml-2 @endif" x-text="getCurrentLanguage().native_name"></span>
                @endif
                <svg class="w-5 h-5 ml-2 -mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <div x-show="open"
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute right-0 z-50 mt-2 w-56 origin-top-right bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                 style="display: none;">
                <div class="py-1">
                    {{-- Language Options --}}
                    <template x-for="lang in availableLanguages" :key="lang.code">
                        <button @click="switchLanguage(lang.code)"
                                class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 transition-colors"
                                :class="{ 'bg-indigo-50 font-semibold text-indigo-700': currentLang === lang.code }">
                            <img x-show="{{ $showFlags ? 'true' : 'false' }}"
                                 :src="getFlagUrl(lang)"
                                 :alt="lang.name"
                                 :width="flagSize"
                                 :height="flagSize * 0.75"
                                 class="inline-block rounded object-cover"
                                 style="min-width: 20px; min-height: 15px;">
                            <span x-show="{{ $showName ? 'true' : 'false' }}"
                                  :class="{ '{{ $showFlags ? 'ml-2' : '' }}': true }"
                                  x-text="lang.native_name"></span>
                            <svg x-show="currentLang === lang.code" class="w-4 h-4 ml-auto text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>

    @elseif($style === 'flags')
        {{-- Flags Style (Horizontal) --}}
        <div class="flex items-center space-x-2">
            <template x-for="lang in availableLanguages" :key="lang.code">
                <button @click="switchLanguage(lang.code)"
                        class="flex items-center space-x-2 px-3 py-2 rounded-lg transition-all hover:bg-gray-100"
                        :class="{ 'bg-indigo-50 ring-2 ring-indigo-500': currentLang === lang.code }">
                    <img x-show="{{ $showFlags ? 'true' : 'false' }}"
                         :src="getFlagUrl(lang)"
                         :alt="lang.name"
                         :width="flagSize"
                         :height="flagSize * 0.75"
                         class="inline-block rounded object-cover transition-transform"
                         :class="{ 'scale-110': currentLang === lang.code }"
                         style="min-width: 20px; min-height: 15px;">
                    <span x-show="{{ $showName ? 'true' : 'false' }}"
                          class="text-sm font-medium"
                          :class="{ 'text-indigo-700': currentLang === lang.code }"
                          x-text="lang.native_name"></span>
                </button>
            </template>
        </div>

    @else
        {{-- Compact Style (Flags Only, No Names) --}}
        <div class="flex items-center space-x-1">
            <template x-for="lang in availableLanguages" :key="lang.code">
                <button @click="switchLanguage(lang.code)"
                        class="p-2 rounded-lg transition-all hover:scale-110 hover:bg-gray-100"
                        :class="{ 'bg-indigo-50 ring-2 ring-indigo-500 scale-110': currentLang === lang.code }"
                        :title="lang.native_name">
                    <img :src="getFlagUrl(lang)"
                         :alt="lang.name"
                         :width="flagSize"
                         :height="flagSize * 0.75"
                         class="inline-block rounded object-cover"
                         style="min-width: 20px; min-height: 15px;">
                </button>
            </template>
        </div>
    @endif
</div>

<style>
.language-switcher-pro button {
    transition: all 0.2s ease-in-out;
}

.language-switcher-pro button:hover {
    transform: translateY(-1px);
}

.language-switcher-pro .animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
