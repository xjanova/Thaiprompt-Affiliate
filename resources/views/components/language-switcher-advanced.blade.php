{{-- Advanced Language Switcher with Google Translate Support --}}
<div class="relative inline-block text-left" x-data="{
    open: false,
    translationEnabled: false,
    currentLang: '{{ app()->getLocale() }}',
    availableLanguages: [],
    async init() {
        // Check if translation service is enabled
        try {
            const response = await fetch('/api/translate/status');
            const data = await response.json();
            this.translationEnabled = data.enabled;
            if (data.languages) {
                this.availableLanguages = Object.values(data.languages);
            }
        } catch (error) {
            console.error('Failed to check translation status:', error);
        }
    },
    async switchLanguage(langCode) {
        if (this.translationEnabled) {
            // Use Google Translate to translate page content
            await this.translatePage(langCode);
        } else {
            // Fall back to session-based language switching
            window.location.href = '/lang/' + langCode;
        }
        this.currentLang = langCode;
        this.open = false;
    },
    async translatePage(targetLang) {
        // Get all translatable elements (elements with data-translate attribute)
        const elements = document.querySelectorAll('[data-translate]');
        const texts = Array.from(elements).map(el => el.textContent.trim());

        if (texts.length === 0) return;

        try {
            const response = await fetch('/api/translate/batch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({
                    texts: texts,
                    target_lang: targetLang,
                    source_lang: '{{ config('translate.source_language', 'th') }}'
                })
            });

            const data = await response.json();

            if (data.success && data.translations) {
                elements.forEach((el, index) => {
                    if (data.translations[index]) {
                        el.textContent = data.translations[index];
                    }
                });
            }
        } catch (error) {
            console.error('Translation failed:', error);
            // Fall back to session-based switching
            window.location.href = '/lang/' + targetLang;
        }
    },
    getLanguageInfo(code) {
        return this.availableLanguages.find(lang => lang.code === code) || null;
    }
}">
    <div>
        <button @click="open = !open" type="button"
                class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <template x-if="translationEnabled && availableLanguages.length > 0">
                <span x-text="(getLanguageInfo(currentLang)?.flag || '🌐') + ' ' + (getLanguageInfo(currentLang)?.native || currentLang.toUpperCase())"></span>
            </template>
            <template x-if="!translationEnabled || availableLanguages.length === 0">
                <span>
                    @if(app()->getLocale() === 'th')
                        <span class="mr-2">🇹🇭</span> ไทย
                    @else
                        <span class="mr-2">🇬🇧</span> English
                    @endif
                </span>
            </template>
            <svg class="w-5 h-5 ml-2 -mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="absolute right-0 z-10 mt-2 origin-top-right bg-white rounded-md shadow-lg max-h-96 overflow-y-auto ring-1 ring-black ring-opacity-5 focus:outline-none"
         style="display: none; min-width: 200px;">
        <div class="py-1">
            <!-- Translation Status Badge -->
            <div class="px-4 py-2 text-xs text-gray-500 border-b">
                <span x-show="translationEnabled" class="flex items-center">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                    Google Translate Active
                </span>
                <span x-show="!translationEnabled" class="flex items-center">
                    <span class="w-2 h-2 bg-gray-400 rounded-full mr-2"></span>
                    Session-based switching
                </span>
            </div>

            <!-- Language Options -->
            <template x-if="translationEnabled && availableLanguages.length > 0">
                <template x-for="lang in availableLanguages" :key="lang.code">
                    <button @click="switchLanguage(lang.code)"
                            class="flex items-center w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            :class="{ 'bg-gray-50 font-semibold': currentLang === lang.code }">
                        <span x-text="lang.flag" class="mr-2"></span>
                        <span x-text="lang.native"></span>
                        <svg x-show="currentLang === lang.code" class="w-4 h-4 ml-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </template>
            </template>

            <!-- Fallback to basic languages if translation not enabled -->
            <template x-if="!translationEnabled || availableLanguages.length === 0">
                <div>
                    <a href="{{ route('lang.switch', 'en') }}"
                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ app()->getLocale() === 'en' ? 'bg-gray-50 font-semibold' : '' }}">
                        <span class="mr-2">🇬🇧</span>
                        <span>English</span>
                        @if(app()->getLocale() === 'en')
                            <svg class="w-4 h-4 ml-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </a>

                    <a href="{{ route('lang.switch', 'th') }}"
                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 {{ app()->getLocale() === 'th' ? 'bg-gray-50 font-semibold' : '' }}">
                        <span class="mr-2">🇹🇭</span>
                        <span>ไทย</span>
                        @if(app()->getLocale() === 'th')
                            <svg class="w-4 h-4 ml-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @endif
                    </a>
                </div>
            </template>
        </div>
    </div>
</div>
