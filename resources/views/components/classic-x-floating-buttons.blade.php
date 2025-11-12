<!-- Floating Action Buttons for Classic X Theme -->
<div
    x-data="classicXFloatingButtons()"
    x-init="init()"
    class="fixed right-6 bottom-6 z-[60] flex flex-col gap-3"
>
    <!-- Dark Mode Toggle Button -->
    <button
        @click="toggleDarkMode()"
        class="flex items-center justify-center w-14 h-14 rounded-full shadow-2xl transition-all duration-300 hover:scale-110 group"
        :class="isDark ? 'bg-gray-800 text-yellow-400 hover:bg-gray-700' : 'bg-white text-gray-700 hover:bg-gray-50'"
        title="สลับโหมดมืด/สว่าง"
    >
        <!-- Sun Icon (Show in dark mode) -->
        <svg x-show="isDark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        <!-- Moon Icon (Show in light mode) -->
        <svg x-show="!isDark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
    </button>

    <!-- Scroll to Top Button -->
    <button
        x-show="showScrollTop"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-0"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-0"
        @click="scrollToTop()"
        class="flex items-center justify-center w-14 h-14 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full shadow-2xl transition-all duration-300 hover:scale-110 hover:from-blue-600 hover:to-blue-700"
        title="เลื่อนขึ้นด้านบน"
        style="display: none;"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>
</div>

<style>
    /* Ensure buttons don't interfere with Classic X sidebar */
    @media (max-width: 768px) {
        .fixed.right-6.bottom-6 {
            right: 1rem;
            bottom: 1rem;
        }
    }
</style>

<script>
function classicXFloatingButtons() {
    return {
        isDark: false,
        showScrollTop: false,

        init() {
            // Check dark mode from localStorage and system preference
            const darkMode = localStorage.getItem('darkMode');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (darkMode === 'dark' || (!darkMode && systemPrefersDark)) {
                this.isDark = true;
                document.documentElement.classList.add('dark');
            } else {
                this.isDark = false;
                document.documentElement.classList.remove('dark');
            }

            // Scroll event listener for "Scroll to Top" button
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
        }
    }
}
</script>
