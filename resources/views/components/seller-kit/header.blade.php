{{--
 | หัวหน้าเพจมาตรฐานของแผงร้านค้า V4 (seller-v4)
 | ใช้: <x-seller-kit.header title="..." subtitle="..." crumb="ร้านค้า · POS" icon="🏪"> ปุ่มด้านขวา </x-seller-kit.header>
 | สีทั้งหมดมาจาก CSS variables ของธีม V4 (สลับโหมดมืดได้เอง)
 --}}
@props(['title', 'subtitle' => null, 'crumb' => null, 'icon' => null])

<div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:14px;">
    <div style="min-width:0; flex:1 1 260px;">
        @if($crumb)
            <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.4px;">{{ $crumb }}</div>
        @endif
        <h1 style="font-size:clamp(21px,4vw,28px); font-weight:800; margin:4px 0 0; color:var(--ink); line-height:1.25; overflow-wrap:anywhere;">
            @if($icon)<span aria-hidden="true">{{ $icon }}</span> @endif{{ $title }}
        </h1>
        @if($subtitle)
            <div style="font-size:12.5px; color:var(--ink2); margin-top:5px; line-height:1.6;">{{ $subtitle }}</div>
        @endif
    </div>
    @if(trim((string) $slot) !== '')
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
            {{ $slot }}
        </div>
    @endif
</div>
