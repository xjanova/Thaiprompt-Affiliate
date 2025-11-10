@php
$settings = $section['settings'] ?? [];
$height = $settings['height'] ?? '80';
@endphp

<div style="height: {{ $height }}px;"></div>
