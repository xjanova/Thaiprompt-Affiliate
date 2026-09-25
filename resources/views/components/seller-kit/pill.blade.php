{{--
 | ป้ายสถานะ (pill) ตามโทนสี ของแผงร้านค้า V4
 | ใช้: <x-seller-kit.pill tone="ok">สำเร็จ</x-seller-kit.pill>
 | tone: gold | ok | bad | warn | info | violet | muted
 --}}
@props(['tone' => 'muted'])

@php
    $toneColors = [
        'gold' => 'var(--deep1)',
        'ok' => 'var(--tp-ok, #4f9a74)',
        'bad' => 'var(--tp-bad, #d9534f)',
        'warn' => 'var(--tp-warn, #c98a1b)',
        'info' => 'var(--tp-info, #4f7fb0)',
        'violet' => 'var(--tp-violet, #8b6bb8)',
        'muted' => 'var(--ink2)',
    ];
    $kitColor = $toneColors[$tone] ?? $toneColors['muted'];
@endphp

<span {{ $attributes->merge(['class' => 'tp-pill']) }}
      style="color:{{ $kitColor }}; background:color-mix(in srgb, {{ $kitColor }} 16%, transparent); white-space:nowrap;">{{ $slot }}</span>
