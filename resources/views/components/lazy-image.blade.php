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
