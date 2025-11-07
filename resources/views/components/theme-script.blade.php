@props(['mode' => $currentThemeMode ?? 'auto'])

@php
    // Detect if we're in admin or user context - use path segment for more reliable detection
    $isAdmin = request()->segment(1) === 'admin';
    $themeCssRoute = $isAdmin ? route('admin.themes.css') : route('user.themes.css');
    $themeSetRoute = $isAdmin ? route('admin.themes.set') : route('user.themes.set');
@endphp

<script>
    // Theme management
    window.ThemeManager = {
        currentMode: '{{ $mode }}',
        isAdmin: {{ $isAdmin ? 'true' : 'false' }},
        themeCssRoute: '{{ $themeCssRoute }}',
        themeSetRoute: '{{ $themeSetRoute }}',

        init() {
            console.log('ThemeManager Init:', {
                isAdmin: this.isAdmin,
                currentMode: this.currentMode,
                themeCssRoute: this.themeCssRoute,
                themeSetRoute: this.themeSetRoute
            });
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

        async setMode(mode) {
            this.currentMode = mode;
            this.applyTheme();
            await this.reloadThemeVariables();
        },

        async reloadThemeVariables() {
            // Reload theme CSS variables if needed
            const actualMode = this.getActualMode();

            // Fetch new CSS variables from server
            try {
                const response = await fetch(`${this.themeCssRoute}?mode=${actualMode}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const css = await response.text();

                    // Update or create dynamic style element
                    let styleElement = document.getElementById('dynamic-theme-css');
                    if (!styleElement) {
                        styleElement = document.createElement('style');
                        styleElement.id = 'dynamic-theme-css';
                        document.head.appendChild(styleElement);
                    }
                    styleElement.textContent = css;
                }
            } catch (error) {
                console.error('Failed to reload theme variables:', error);
            }

            // Trigger event for other components
            window.dispatchEvent(new CustomEvent('theme-changed', {
                detail: { mode: actualMode }
            }));
        },

        async changeTheme(themeId, mode = 'auto') {
            try {
                const response = await fetch(this.themeSetRoute, {
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
