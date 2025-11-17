{{--
    Arrow X Theme Styles Component

    Inject Arrow X theme CSS variables and RGB effects into the page

    Usage:
    <x-arrow-x.theme-styles />
--}}

@php
    // ใช้ ThemeCompilerService สำหรับ compile และ cache
    $compilerService = app(\App\Services\ThemeCompilerService::class);
    $compiled = $compilerService->compile();
    $compiledCss = $compiled['css'];
    $compiledJs = $compiled['js'];
@endphp

{{-- Arrow X Theme Styles --}}
<style id="arrow-x-theme-styles">
{!! $compiledCss !!}
</style>

{{-- Arrow X Theme Scripts --}}
<script id="arrow-x-theme-scripts">
{!! $compiledJs !!}
</script>
