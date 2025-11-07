@props(['theme' => $currentTheme ?? null, 'mode' => $currentThemeMode ?? 'auto'])

@php
    // ถ้าไม่มี theme ให้ใช้ default
    if (!$theme) {
        $theme = \App\Models\Theme::getDefault();
    }

    // กำหนด mode ที่จะใช้
    $actualMode = $mode === 'auto' ? 'light' : $mode;

    // Generate CSS variables
    $cssVariables = $theme ? $theme->getCssVariables($actualMode) : [];
@endphp

<style id="theme-variables">
    :root {
        @foreach($cssVariables as $key => $value)
        {{ $key }}: {{ $value }};
        @endforeach
    }

    /* Dark mode styles */
    @if($mode === 'auto')
    @media (prefers-color-scheme: dark) {
        :root {
            @if($theme && $theme->dark_colors)
                @php
                    $darkVariables = $theme->getCssVariables('dark');
                @endphp
                @foreach($darkVariables as $key => $value)
                {{ $key }}: {{ $value }};
                @endforeach
            @endif
        }
    }
    @endif

    /* Theme is in dark mode */
    @if($mode === 'dark')
    :root {
        @if($theme && $theme->dark_colors)
            @php
                $darkVariables = $theme->getCssVariables('dark');
            @endphp
            @foreach($darkVariables as $key => $value)
            {{ $key }}: {{ $value }};
            @endforeach
        @endif
    }
    @endif

    /* Dark mode when .dark class is applied to html element (for localStorage toggle) */
    html.dark {
        @if($theme && $theme->dark_colors)
            @php
                $darkVariables = $theme->getCssVariables('dark');
            @endphp
            @foreach($darkVariables as $key => $value)
            {{ $key }}: {{ $value }};
            @endforeach
        @else
            /* Fallback dark colors if no theme is set */
            --color-bg-primary: #111827;
            --color-bg-secondary: #1F2937;
            --color-bg-tertiary: #374151;
            --color-text-primary: #F9FAFB;
            --color-text-secondary: #D1D5DB;
            --color-text-tertiary: #9CA3AF;
            --color-gray-50: #1F2937;
            --color-gray-100: #111827;
            --color-gray-200: #374151;
            --color-gray-800: #F3F4F6;
            --color-gray-900: #F9FAFB;
        @endif
    }

    /* Custom CSS from theme */
    @if($theme && $theme->custom_css && isset($theme->custom_css[$actualMode]))
    {!! $theme->custom_css[$actualMode] !!}
    @endif

    /* Base styles using theme variables */
    body {
        background-color: var(--color-bg-primary, #FFFFFF);
        color: var(--color-text-primary, #111827);
        font-family: var(--font-family-primary, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif);
    }

    /* Sidebar styles */
    .sidebar {
        background-color: var(--color-bg-secondary, #F9FAFB);
        border-right: var(--border-width-thin, 1px) solid var(--color-gray-200, #E5E7EB);
    }

    .sidebar-menu-item {
        color: var(--color-text-secondary, #6B7280);
        transition: all var(--animation-duration-base, 300ms) var(--animation-easing, cubic-bezier(0.4, 0, 0.2, 1));
    }

    .sidebar-menu-item:hover,
    .sidebar-menu-item.active {
        background-color: var(--color-primary, #06C755);
        color: #FFFFFF;
        border-radius: var(--border-radius-md, 0.5rem);
    }

    /* Header styles */
    .header {
        background-color: var(--color-bg-primary, #FFFFFF);
        border-bottom: var(--border-width-thin, 1px) solid var(--color-gray-200, #E5E7EB);
        box-shadow: var(--shadow-sm, 0 1px 2px 0 rgba(0, 0, 0, 0.05));
    }

    /* Card styles */
    .card {
        background-color: var(--color-bg-primary, #FFFFFF);
        border-radius: var(--border-radius-lg, 0.75rem);
        box-shadow: var(--shadow-base, 0 1px 3px 0 rgba(0, 0, 0, 0.1));
    }

    /* Button styles */
    .btn-primary {
        background-color: var(--color-primary, #06C755);
        color: #FFFFFF;
        border-radius: var(--border-radius-md, 0.5rem);
        padding: var(--spacing-2, 0.5rem) var(--spacing-4, 1rem);
        font-weight: var(--font-weight-medium, 500);
        transition: all var(--animation-duration-base, 300ms) var(--animation-easing, cubic-bezier(0.4, 0, 0.2, 1));
    }

    .btn-primary:hover {
        background-color: var(--color-primary-dark, #00B900);
        box-shadow: var(--shadow-md, 0 4px 6px -1px rgba(0, 0, 0, 0.1));
    }

    .btn-secondary {
        background-color: var(--color-secondary, #00B2FF);
        color: #FFFFFF;
        border-radius: var(--border-radius-md, 0.5rem);
        padding: var(--spacing-2, 0.5rem) var(--spacing-4, 1rem);
        font-weight: var(--font-weight-medium, 500);
    }

    .btn-secondary:hover {
        background-color: var(--color-secondary-dark, #0099E5);
    }

    /* Link styles */
    a {
        color: var(--color-primary, #06C755);
        transition: color var(--animation-duration-fast, 150ms) var(--animation-easing, cubic-bezier(0.4, 0, 0.2, 1));
    }

    a:hover {
        color: var(--color-primary-dark, #00B900);
    }

    /* Form input styles */
    input, select, textarea {
        background-color: var(--color-bg-primary, #FFFFFF);
        border: var(--border-width-thin, 1px) solid var(--color-gray-300, #D1D5DB);
        border-radius: var(--border-radius-base, 0.375rem);
        color: var(--color-text-primary, #111827);
        padding: var(--spacing-2, 0.5rem) var(--spacing-3, 0.75rem);
    }

    input:focus, select:focus, textarea:focus {
        border-color: var(--color-primary, #06C755);
        outline: none;
        box-shadow: 0 0 0 3px rgba(6, 199, 85, 0.1);
    }

    /* Badge styles */
    .badge {
        border-radius: var(--border-radius-full, 9999px);
        padding: var(--spacing-1, 0.25rem) var(--spacing-3, 0.75rem);
        font-size: var(--font-size-xs, 0.75rem);
        font-weight: var(--font-weight-semibold, 600);
    }

    .badge-success {
        background-color: var(--color-success, #10B981);
        color: #FFFFFF;
    }

    .badge-warning {
        background-color: var(--color-warning, #F59E0B);
        color: #FFFFFF;
    }

    .badge-error {
        background-color: var(--color-error, #EF4444);
        color: #FFFFFF;
    }

    .badge-info {
        background-color: var(--color-info, #3B82F6);
        color: #FFFFFF;
    }

    /* Table styles */
    .table {
        background-color: var(--color-bg-primary, #FFFFFF);
        border-radius: var(--border-radius-lg, 0.75rem);
        overflow: hidden;
    }

    .table thead {
        background-color: var(--color-bg-secondary, #F9FAFB);
    }

    .table th {
        color: var(--color-text-primary, #111827);
        font-weight: var(--font-weight-semibold, 600);
        padding: var(--spacing-3, 0.75rem) var(--spacing-4, 1rem);
    }

    .table td {
        color: var(--color-text-secondary, #6B7280);
        padding: var(--spacing-3, 0.75rem) var(--spacing-4, 1rem);
        border-top: var(--border-width-thin, 1px) solid var(--color-gray-200, #E5E7EB);
    }

    .table tr:hover {
        background-color: var(--color-bg-tertiary, #F3F4F6);
    }
</style>
