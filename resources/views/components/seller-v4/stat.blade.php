{{--
 | การ์ดสถิติเล็กของแผงผู้ขาย V4
 | color = ค่าสี CSS (ใช้ค่าคงที่ใน App\Support\Seller\SellerUi) — ไม่ส่ง = สีทองของธีม
 --}}
@props([
    'label',
    'value',
    'icon' => null,
    'color' => null,
    'hint' => null,
    'href' => null,
])

@php
    $tone = $color ?: 'var(--deep1)';
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif class="tp-card {{ $href ? 'tp-card-hover' : '' }}"
    style="padding:16px 17px; min-width:0; display:block; text-decoration:none; color:var(--ink);">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
        <div style="font-size:12px; color:var(--ink2); font-weight:700;">{{ $label }}</div>
        @if($icon)
            <span style="width:36px; height:36px; border-radius:11px; display:grid; place-items:center; font-size:17px; flex:none; background:color-mix(in srgb, {{ $tone }} 16%, transparent);">{{ $icon }}</span>
        @endif
    </div>
    <div class="tp-num" style="font-size:clamp(20px,3.2vw,25px); font-weight:800; margin-top:8px; color:{{ $tone }}; overflow-wrap:anywhere;">{{ $value }}</div>
    @if($hint)
        <div style="font-size:11px; color:var(--ink2); margin-top:3px;">{{ $hint }}</div>
    @endif
</{{ $tag }}>
