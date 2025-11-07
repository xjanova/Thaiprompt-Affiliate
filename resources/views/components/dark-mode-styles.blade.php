<!-- Dark Mode CSS Variables -->
<style>
    :root {
        /* Light mode colors (default) */
        --bg-primary: #ffffff;
        --bg-secondary: #f9fafb;
        --bg-tertiary: #f3f4f6;

        --text-primary: #111827;
        --text-secondary: #6b7280;
        --text-tertiary: #9ca3af;

        --border-color: #e5e7eb;
        --border-color-hover: #d1d5db;

        --card-bg: #ffffff;
        --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);

        --input-bg: #ffffff;
        --input-border: #d1d5db;
        --input-focus: #06C755;

        --sidebar-bg: #f9fafb;
        --sidebar-border: #e5e7eb;

        --header-bg: #ffffff;
        --header-border: #e5e7eb;
    }

    /* Dark mode colors */
    .dark {
        --bg-primary: #111827;
        --bg-secondary: #1f2937;
        --bg-tertiary: #374151;

        --text-primary: #f9fafb;
        --text-secondary: #d1d5db;
        --text-tertiary: #9ca3af;

        --border-color: #374151;
        --border-color-hover: #4b5563;

        --card-bg: #1f2937;
        --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.3), 0 1px 2px 0 rgba(0, 0, 0, 0.2);

        --input-bg: #374151;
        --input-border: #4b5563;
        --input-focus: #06C755;

        --sidebar-bg: #1f2937;
        --sidebar-border: #374151;

        --header-bg: #1f2937;
        --header-border: #374151;
    }

    /* Apply CSS variables to elements */
    body {
        background-color: var(--bg-primary);
        color: var(--text-primary);
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .card, .bg-white {
        background-color: var(--card-bg) !important;
        box-shadow: var(--card-shadow);
    }

    .border, .border-gray-200, .border-gray-300 {
        border-color: var(--border-color) !important;
    }

    input, select, textarea {
        background-color: var(--input-bg) !important;
        border-color: var(--input-border) !important;
        color: var(--text-primary) !important;
    }

    input:focus, select:focus, textarea:focus {
        border-color: var(--input-focus) !important;
    }

    /* Sidebar */
    .sidebar, nav[class*="sidebar"] {
        background-color: var(--sidebar-bg) !important;
        border-color: var(--sidebar-border) !important;
    }

    /* Header */
    header, [class*="header"] {
        background-color: var(--header-bg) !important;
        border-color: var(--header-border) !important;
    }

    /* Text colors */
    .text-gray-900, .text-gray-800 {
        color: var(--text-primary) !important;
    }

    .text-gray-700, .text-gray-600 {
        color: var(--text-secondary) !important;
    }

    .text-gray-500, .text-gray-400 {
        color: var(--text-tertiary) !important;
    }

    /* Background colors for utility classes */
    .bg-gray-50 {
        background-color: var(--bg-secondary) !important;
    }

    .bg-gray-100 {
        background-color: var(--bg-tertiary) !important;
    }

    /* Table styles */
    table {
        background-color: var(--card-bg);
    }

    thead {
        background-color: var(--bg-secondary);
    }

    tbody tr {
        border-color: var(--border-color);
    }

    tbody tr:hover {
        background-color: var(--bg-tertiary);
    }

    /* Modal/Dropdown backgrounds */
    .dropdown-menu, .modal-content {
        background-color: var(--card-bg) !important;
        border-color: var(--border-color) !important;
    }

    /* Preserve primary colors */
    .bg-green-500, .bg-green-600,
    .text-green-500, .text-green-600,
    .bg-blue-500, .bg-blue-600,
    .bg-red-500, .bg-red-600,
    .bg-yellow-500, .bg-yellow-600 {
        /* Keep original colors - don't override */
    }
</style>
