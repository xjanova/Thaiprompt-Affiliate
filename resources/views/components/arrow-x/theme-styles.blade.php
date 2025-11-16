{{--
    Arrow X Theme Styles Component

    Inject Arrow X theme CSS variables and RGB effects into the page

    Usage:
    <x-arrow-x.theme-styles />
--}}

@php
    // ใช้ ThemeCompilerService สำหรับ compile และ cache
    $compilerService = app(\App\Services\ThemeCompilerService::class);
    $compiled = $compilerService->getCompiled();
    $compiledCss = $compiled['css'];
    $compiledJs = $compiled['js'];
@endphp

{{-- Arrow X Theme Styles --}}
<style id="arrow-x-theme-styles">
{!! $compiledCss !!}

/* Arrow X Theme - Dynamic Color Utilities (สำหรับใช้ใน Blade templates) */

/* Gradient Backgrounds */
.bg-gradient-primary { background: var(--arrow-x-primary-gradient, linear-gradient(135deg, #6366f1, #8b5cf6, #a855f7)) !important; }
.bg-gradient-secondary { background: var(--arrow-x-secondary-gradient, linear-gradient(135deg, #3b82f6, #06b6d4, #14b8a6)) !important; }

/* Solid Backgrounds */
.bg-primary { background-color: var(--arrow-x-primary-start, #6366f1) !important; }
.bg-secondary { background-color: var(--arrow-x-secondary-start, #3b82f6) !important; }
.bg-success { background-color: var(--arrow-x-success, #10b981) !important; }
.bg-warning { background-color: var(--arrow-x-warning, #f59e0b) !important; }
.bg-error { background-color: var(--arrow-x-error, #ef4444) !important; }
.bg-info { background-color: var(--arrow-x-info, #3b82f6) !important; }
.bg-accent { background-color: var(--arrow-x-accent, #ec4899) !important; }

/* Light Backgrounds */
.bg-primary-light { background-color: color-mix(in srgb, var(--arrow-x-primary-start, #6366f1) 15%, transparent) !important; }
.bg-secondary-light { background-color: color-mix(in srgb, var(--arrow-x-secondary-start, #3b82f6) 15%, transparent) !important; }
.bg-success-light { background-color: color-mix(in srgb, var(--arrow-x-success, #10b981) 15%, transparent) !important; }
.bg-warning-light { background-color: color-mix(in srgb, var(--arrow-x-warning, #f59e0b) 15%, transparent) !important; }
.bg-error-light { background-color: color-mix(in srgb, var(--arrow-x-error, #ef4444) 15%, transparent) !important; }
.bg-info-light { background-color: color-mix(in srgb, var(--arrow-x-info, #3b82f6) 15%, transparent) !important; }
.bg-accent-light { background-color: color-mix(in srgb, var(--arrow-x-accent, #ec4899) 15%, transparent) !important; }

/* Dark Mode - Light Backgrounds (เข้มขึ้น) */
.dark .bg-primary-light { background-color: color-mix(in srgb, var(--arrow-x-primary-start, #6366f1) 30%, transparent) !important; }
.dark .bg-secondary-light { background-color: color-mix(in srgb, var(--arrow-x-secondary-start, #3b82f6) 30%, transparent) !important; }
.dark .bg-success-light { background-color: color-mix(in srgb, var(--arrow-x-success, #10b981) 30%, transparent) !important; }
.dark .bg-warning-light { background-color: color-mix(in srgb, var(--arrow-x-warning, #f59e0b) 30%, transparent) !important; }
.dark .bg-error-light { background-color: color-mix(in srgb, var(--arrow-x-error, #ef4444) 30%, transparent) !important; }
.dark .bg-info-light { background-color: color-mix(in srgb, var(--arrow-x-info, #3b82f6) 30%, transparent) !important; }
.dark .bg-accent-light { background-color: color-mix(in srgb, var(--arrow-x-accent, #ec4899) 30%, transparent) !important; }

/* Text Colors */
.text-primary { color: var(--arrow-x-primary-start, #6366f1) !important; }
.text-secondary { color: var(--arrow-x-secondary-start, #3b82f6) !important; }
.text-success { color: var(--arrow-x-success, #10b981) !important; }
.text-warning { color: var(--arrow-x-warning, #f59e0b) !important; }
.text-error { color: var(--arrow-x-error, #ef4444) !important; }
.text-info { color: var(--arrow-x-info, #3b82f6) !important; }
.text-accent { color: var(--arrow-x-accent, #ec4899) !important; }

/* Border Colors */
.border-primary { border-color: var(--arrow-x-primary-start, #6366f1) !important; }
.border-secondary { border-color: var(--arrow-x-secondary-start, #3b82f6) !important; }
.border-success { border-color: var(--arrow-x-success, #10b981) !important; }
.border-warning { border-color: var(--arrow-x-warning, #f59e0b) !important; }
.border-error { border-color: var(--arrow-x-error, #ef4444) !important; }
.border-info { border-color: var(--arrow-x-info, #3b82f6) !important; }
.border-accent { border-color: var(--arrow-x-accent, #ec4899) !important; }

/* Ring Colors (for focus states) */
.ring-primary { --tw-ring-color: var(--arrow-x-primary-start, #6366f1) !important; }
.ring-secondary { --tw-ring-color: var(--arrow-x-secondary-start, #3b82f6) !important; }
.ring-success { --tw-ring-color: var(--arrow-x-success, #10b981) !important; }
.ring-warning { --tw-ring-color: var(--arrow-x-warning, #f59e0b) !important; }
.ring-error { --tw-ring-color: var(--arrow-x-error, #ef4444) !important; }
.ring-info { --tw-ring-color: var(--arrow-x-info, #3b82f6) !important; }
.ring-accent { --tw-ring-color: var(--arrow-x-accent, #ec4899) !important; }

.focus\:ring-primary:focus { --tw-ring-color: var(--arrow-x-primary-start, #6366f1) !important; }
.focus\:ring-secondary:focus { --tw-ring-color: var(--arrow-x-secondary-start, #3b82f6) !important; }
.focus\:ring-success:focus { --tw-ring-color: var(--arrow-x-success, #10b981) !important; }
.focus\:ring-warning:focus { --tw-ring-color: var(--arrow-x-warning, #f59e0b) !important; }
.focus\:ring-error:focus { --tw-ring-color: var(--arrow-x-error, #ef4444) !important; }

.focus\:border-primary:focus { border-color: var(--arrow-x-primary-start, #6366f1) !important; }
.focus\:border-secondary:focus { border-color: var(--arrow-x-secondary-start, #3b82f6) !important; }
</style>

{{-- Arrow X Theme Scripts --}}
<script id="arrow-x-theme-scripts">
{!! $compiledJs !!}
</script>
