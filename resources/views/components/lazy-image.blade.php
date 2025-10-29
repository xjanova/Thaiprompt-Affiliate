@props([
    'src' => '',
    'alt' => '',
    'class' => '',
    'width' => null,
    'height' => null,
    'eager' => false
])

@php
    $loadingAttr = $eager ? 'eager' : 'lazy';
    $decoding = 'async';
@endphp

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    loading="{{ $loadingAttr }}"
    decoding="{{ $decoding }}"
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    {{ $attributes->merge(['class' => $class]) }}
>
