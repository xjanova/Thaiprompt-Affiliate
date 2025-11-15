{{--
    Arrow X Theme Styles Component

    Inject Arrow X theme CSS variables and RGB effects into the page

    Usage:
    <x-arrow-x.theme-styles />
--}}

@php
    $themeService = app(\App\Services\ThemeService::class);
    $compiledCss = $themeService->compileThemeCss();
    $compiledJs = $themeService->compileThemeJs();
@endphp

{{-- Arrow X Theme Styles --}}
<style id="arrow-x-theme-styles">
{!! $compiledCss !!}
</style>

{{-- Arrow X Theme Scripts --}}
<script id="arrow-x-theme-scripts">
{!! $compiledJs !!}
</script>
