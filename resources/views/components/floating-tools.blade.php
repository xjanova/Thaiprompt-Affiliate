<!-- Floating Action Button Tools -->
@php
    $darkModeEnabled = \App\Models\Setting::get('floating_dark_mode_enabled', true);
    $scrollTopEnabled = \App\Models\Setting::get('floating_scroll_top_enabled', true);
    $chatBotEnabled = \App\Models\Setting::get('floating_chatbot_enabled', false);
    $languageEnabled = \App\Models\Setting::get('floating_language_enabled', false);

    $fabIcon = \App\Models\Setting::get('floating_fab_icon', '⚙️');
    $fabPosition = \App\Models\Setting::get('floating_fab_position', 'bottom-right'); // bottom-right, bottom-left
@endphp

<div
    x-data="floatingTools()"
    x-init="init()"
    class="fixed {{ $fabPosition === 'bottom-left' ? 'left-6' : 'right-6' }} bottom-6 z-50"
    @mouseenter="expanded = true"
    @mouseleave="expanded = false"
>
    <!-- Tools Menu (Hidden by default, shows on hover) -->
    <div
        x-show="expanded"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="mb-4 space-y-3"
        style="display: none;"
    >
        @if($darkModeEnabled)
        <!-- Dark Mode Toggle -->
        <button
            @click="toggleDarkMode()"
            class="flex items-center justify-center w-12 h-12 rounded-full shadow-lg transition-all duration-200 hover:scale-110 group"
            :class="isDark ? 'bg-gray-800 text-yellow-400' : 'bg-white text-gray-700'"
            title="สลับโหมดมืด/สว่าง"
        >
            <!-- Sun Icon -->
            <svg x-show="isDark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <!-- Moon Icon -->
            <svg x-show="!isDark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>
        @endif

        @if($scrollTopEnabled)
        <!-- Scroll to Top -->
        <button
            x-show="showScrollTop"
            @click="scrollToTop()"
            class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow-lg transition-all duration-200 hover:scale-110 text-gray-700 hover:text-green-600"
            title="เลื่อนขึ้นด้านบน"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
            </svg>
        </button>
        @endif

        @if($chatBotEnabled)
        <!-- AI ChatBot -->
        <button
            @click="openChatBot()"
            class="flex items-center justify-center w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full shadow-lg transition-all duration-200 hover:scale-110 text-white"
            title="AI Assistant"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
            </svg>
        </button>
        @endif

        @if($languageEnabled)
        <!-- Language Switcher -->
        <button
            @click="toggleLanguage()"
            class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow-lg transition-all duration-200 hover:scale-110 text-gray-700 hover:text-blue-600"
            title="เปลี่ยนภาษา"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
            </svg>
        </button>
        @endif
    </div>

    <!-- Main FAB Button -->
    <button
        class="flex items-center justify-center w-14 h-14 rounded-full shadow-2xl transition-all duration-300 hover:scale-110"
        :class="expanded ? 'bg-red-500 text-white rotate-45' : 'bg-gradient-to-r from-green-500 to-green-600 text-white'"
    >
        <span x-show="!expanded" class="text-2xl">{{ $fabIcon }}</span>
        <svg x-show="expanded" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

<script>
function floatingTools() {
    return {
        expanded: false,
        isDark: false,
        showScrollTop: false,

        init() {
            // Check dark mode
            const darkMode = localStorage.getItem('darkMode');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.isDark = darkMode === 'dark' || (!darkMode && systemPrefersDark);

            // Scroll event listener
            window.addEventListener('scroll', () => {
                this.showScrollTop = window.scrollY > 300;
            });
        },

        toggleDarkMode() {
            this.isDark = !this.isDark;
            localStorage.setItem('darkMode', this.isDark ? 'dark' : 'light');

            if (this.isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        },

        openChatBot() {
            alert('AI ChatBot - Coming Soon!\nระบบแชทบอทจะเปิดให้บริการเร็วๆ นี้');
            // TODO: Open chatbot modal/window
        },

        toggleLanguage() {
            // Get current language
            const currentLang = document.documentElement.lang || 'th';
            const newLang = currentLang === 'th' ? 'en' : 'th';

            // Redirect to language switch route
            window.location.href = `/lang/${newLang}`;
        }
    }
}
</script>
