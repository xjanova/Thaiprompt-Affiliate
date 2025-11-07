@props(['mode' => $currentThemeMode ?? 'auto'])

<script>
    // Theme management
    window.ThemeManager = {
        currentMode: '{{ $mode }}',

        init() {
            this.applyTheme();
            this.watchSystemPreference();
        },

        applyTheme() {
            const actualMode = this.getActualMode();

            if (actualMode === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        getActualMode() {
            if (this.currentMode === 'auto') {
                return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            return this.currentMode;
        },

        watchSystemPreference() {
            if (this.currentMode === 'auto') {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                    this.applyTheme();
                    this.reloadThemeVariables();
                });
            }
        },

        setMode(mode) {
            this.currentMode = mode;
            this.applyTheme();
            this.reloadThemeVariables();
        },

        reloadThemeVariables() {
            // Reload theme CSS variables if needed
            const actualMode = this.getActualMode();

            // Trigger event for other components
            window.dispatchEvent(new CustomEvent('theme-changed', {
                detail: { mode: actualMode }
            }));
        },

        async changeTheme(themeId, mode = 'auto') {
            try {
                const response = await fetch('{{ route("user.themes.set") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        theme_id: themeId,
                        mode: mode
                    })
                });

                if (response.ok) {
                    // Reload page to apply new theme
                    window.location.reload();
                } else {
                    console.error('Failed to change theme');
                }
            } catch (error) {
                console.error('Error changing theme:', error);
            }
        }
    };

    // Initialize theme on page load
    document.addEventListener('DOMContentLoaded', () => {
        window.ThemeManager.init();
    });

    // Export for use in other scripts
    window.changeTheme = (themeId, mode) => window.ThemeManager.changeTheme(themeId, mode);
    window.setThemeMode = (mode) => window.ThemeManager.setMode(mode);
</script>
