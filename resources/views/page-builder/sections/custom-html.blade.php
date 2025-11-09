@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$containerClass = $settings['container_class'] ?? 'container mx-auto px-4';
$paddingY = $settings['padding_y'] ?? 'py-20';
$bgColor = $settings['bg_color'] ?? '';
$enableScripts = $settings['enable_scripts'] ?? false;
$enableStyles = $settings['enable_styles'] ?? false;
@endphp

<section class="{{ $paddingY }}" @if(!empty($bgColor)) style="background-color: {{ $bgColor }};" @endif>
    <div class="{{ $containerClass }}">
        @if(!empty($content['html']))
            {!! $content['html'] !!}
        @endif
    </div>
</section>

@if($enableStyles && !empty($content['custom_css']))
@push('styles')
<style>
{!! $content['custom_css'] !!}
</style>
@endpush
@endif

@if($enableScripts && !empty($content['custom_js']))
@push('scripts')
<script>
{!! $content['custom_js'] !!}
</script>
@endpush
@endif
