{{--
 | ป้ายสถานะ V4 (โทนสีจากตัวแปรธีม — ใช้ได้ทั้งโหมดสว่าง/มืด)
 | ตัวแปร (ตั้งชื่อขึ้นต้น pill กันชนกับตัวแปรของหน้าแม่ที่ @include ส่งต่อมา):
 |   $pillTone ok|warn|bad|info|violet|gold|mute, $pillText, $pillIcon (ไม่บังคับ เช่น fa-check), $pillTitle (ไม่บังคับ)
--}}
@php
    $pillVar = [
        'ok' => '--w-ok',
        'warn' => '--w-warn',
        'bad' => '--w-bad',
        'info' => '--w-info',
        'violet' => '--w-violet',
        'gold' => '--accent1',
        'mute' => '--ink2',
    ][$pillTone ?? 'mute'] ?? '--ink2';
@endphp
<span class="tp-pill" @if(! empty($pillTitle)) title="{{ $pillTitle }}" @endif
      style="white-space:nowrap; background:color-mix(in srgb, var({{ $pillVar }}) 17%, transparent); color:color-mix(in srgb, var({{ $pillVar }}) 74%, var(--ink));">@if(! empty($pillIcon))<i class="fas {{ $pillIcon }}"></i>@endif{{ $pillText ?? '' }}</span>
