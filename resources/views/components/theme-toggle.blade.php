<!-- Theme Toggle Component -->
@php
    $currentThemeId = $currentTheme->id ?? null;
    $currentMode = $currentThemeMode ?? 'auto';
@endphp

<div x-data="themeToggle()" x-init="init()" class="relative">
    <button
        @click="toggleTheme()"
        class="flex items-center justify-center w-10 h-10 rounded-lg transition-all duration-300 hover:scale-110"
        :class="isDark ? 'bg-gray-700 text-yellow-400 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
        :title="isDark ? 'เปลี่ยนเป็นโหมดสว่าง' : 'เปลี่ยนเป็นโหมดมืด'"
    >
        <!-- Sun Icon (Light Mode) -->
        <svg x-show="!isDark"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 rotate-90 scale-0"
             x-transition:enter-end="opacity-100 rotate-0 scale-100"
             class="w-5 h-5"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>

        <!-- Moon Icon (Dark Mode) -->
        <svg x-show="isDark"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -rotate-90 scale-0"
             x-transition:enter-end="opacity-100 rotate-0 scale-100"
             class="w-5 h-5"
             fill="none"
             stroke="currentColor"
             viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
    </button>
</div>

<script>
function themeToggle() {
    return {
        isDark: false,
        currentThemeId: {{ $currentThemeId ?? 'null' }},
        currentMode: '{{ $currentMode }}',

        init() {
            // Get mode from server-side or determine from current state
            if (this.currentMode === 'dark') {
                this.isDark = true;
            } else if (this.currentMode === 'light') {
                this.isDark = false;
            } else {
                // Auto mode - check system preference
                this.isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            this.applyTheme();

            // Listen for system preference changes (for auto mode)
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (this.currentMode === 'auto') {
                    this.isDark = e.matches;
                    this.applyTheme();
                }
            });
        },

        async toggleTheme() {
            this.isDark = !this.isDark;
            const newMode = this.isDark ? 'dark' : 'light';

            // Apply theme immediately
            this.applyTheme();

            // Update current mode
            this.currentMode = newMode;

            // Update ThemeManager if available
            if (typeof window.ThemeManager !== 'undefined') {
                try {
                    await window.ThemeManager.setMode(newMode);
                } catch (error) {
                    console.error('ThemeManager error:', error);
                }
            }

            // Save preference to server
            if (this.currentThemeId) {
                try {
                    const route = window.location.pathname.startsWith('/admin')
                        ? '/admin/themes/set'
                        : '/user/themes/set';

                    const csrfToken = document.querySelector('meta[name="csrf-token"]');
                    if (!csrfToken) {
                        console.error('CSRF token not found');
                        return;
                    }

                    await fetch(route, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken.content
                        },
                        body: JSON.stringify({
                            theme_id: this.currentThemeId,
                            mode: newMode
                        })
                    });
                } catch (error) {
                    console.error('Failed to save theme preference:', error);
                }
            }
        },

        applyTheme() {
            if (this.isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }
}
</script>
