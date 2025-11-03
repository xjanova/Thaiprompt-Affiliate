@props([
    'src' => '',
    'alt' => '',
    'class' => '',
    'width' => null,
    'height' => null,
    'eager' => true
])

@php
    // ปิดการใช้งาน lazy loading เพื่อแก้ปัญหาการแสดงผลไม่ครบ
    $loadingAttr = 'eager';
    $decoding = 'sync';

    // Check if WebP version exists
    $webpService = app(\App\Services\WebPService::class);
    $displaySrc = $webpService->getPublicUrl($src);

    // Determine if we should show picture element with fallback
    $isWebP = str_ends_with($displaySrc, '.webp');
    $extension = pathinfo($src, PATHINFO_EXTENSION);
    $originalSrc = $src;

    // For SVG and GIF, use original source
    if (in_array(strtolower($extension), ['svg', 'gif'])) {
        $displaySrc = $src;
        $isWebP = false;
    }
@endphp

@if($isWebP)
    {{-- Use picture element for WebP with fallback --}}
    <picture {{ $attributes->merge(['class' => $class]) }}>
        <source srcset="{{ $displaySrc }}" type="image/webp">
        <img
            src="{{ $originalSrc }}"
            alt="{{ $alt }}"
            loading="{{ $loadingAttr }}"
            decoding="{{ $decoding }}"
            @if($width) width="{{ $width }}" @endif
            @if($height) height="{{ $height }}" @endif
        >
    </picture>
@else
    {{-- Use regular img tag for SVG, GIF, or non-WebP images --}}
    <img
        src="{{ $displaySrc }}"
        alt="{{ $alt }}"
        loading="{{ $loadingAttr }}"
        decoding="{{ $decoding }}"
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
        {{ $attributes->merge(['class' => $class]) }}
    >
@endif
