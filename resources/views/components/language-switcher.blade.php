{{-- Language Switcher Component --}}
<div class="relative inline-block text-left" x-data="{ open: false }">
    <div>
        <button @click="open = !open" type="button"
                class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            @if(app()->getLocale() === 'th')
                <span class="mr-2">🇹🇭</span> ไทย
            @else
                <span class="mr-2">🇬🇧</span> English
            @endif
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
         class="absolute right-0 z-10 mt-2 origin-top-right bg-white rounded-md shadow-lg w-36 ring-1 ring-black ring-opacity-5 focus:outline-none"
         style="display: none;">
        <div class="py-1">
            {{-- English --}}
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

            {{-- Thai --}}
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
    </div>
</div>
