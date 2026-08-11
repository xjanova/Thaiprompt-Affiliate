{{--
 |----------------------------------------------------------------------------
 | <x-art.empty-state> — สถานะว่าง พร้อมภาพประกอบ
 |----------------------------------------------------------------------------
 | @param string      $image     ชื่อไฟล์ใน public/images/art (ค่าเริ่มต้น empty-state)
 | @param string      $title     หัวข้อ เช่น "ยังไม่มีสินค้าแนะนำ"
 | @param string|null $subtitle  คำอธิบายเพิ่ม
 | @param string      $tone      light | dark (ให้เข้ากับพื้นหลังของหน้า)
 | @param int         $size      ความกว้างภาพ (px)
 |
 | @example
 | <x-art.empty-state title="ยังไม่มีข้อมูล" subtitle="ลองใหม่อีกครั้งภายหลัง">
 |     <x-slot:actions><a href="/">กลับหน้าแรก</a></x-slot:actions>
 | </x-art.empty-state>
 --}}
@props([
    'image'    => 'empty-state',
    'title'    => null,
    'subtitle' => null,
    'tone'     => 'light',
    'size'     => 190,
])

@php
    $artRel = $image ? 'images/art/' . $image . '.webp' : null;
    $hasArt = $artRel && file_exists(public_path($artRel));
    $isDark = $tone === 'dark';
    $inkMain = $isDark ? '#ffffff' : 'var(--ink, #33302b)';
    $inkSub  = $isDark ? 'rgba(255,255,255,.72)' : 'var(--ink2, #6b6357)';
@endphp

<div style="display:flex; flex-direction:column; align-items:center; justify-content:center;
            text-align:center; gap:6px; padding:clamp(24px,4vw,44px) 20px;"
     {{ $attributes->merge(['class' => 'tp-art-empty']) }}>

    @if($hasArt)
        {{-- ภาพ empty state ถูกคีย์พื้นขาวออกแล้ว (โปร่งใสจริง) จึงวางได้ทั้งพื้นสว่างและพื้นมืด
             ไม่ต้องใช้ mix-blend-mode ซึ่งจะทำให้เห็นเป็นกล่องสี่เหลี่ยมเวลาสีพื้นไม่ตรงกันเป๊ะ --}}
        <img src="{{ asset($artRel) }}" alt="" aria-hidden="true" loading="lazy" decoding="async"
             style="width:min({{ $size }}px, 62vw); height:auto; opacity:{{ $isDark ? '.9' : '1' }}; margin-bottom:6px;">
    @else
        <div aria-hidden="true" style="font-size:44px; line-height:1; opacity:.45;">🧺</div>
    @endif

    @if($title)
        <div style="font-size:16px; font-weight:700; color:{{ $inkMain }};">{{ $title }}</div>
    @endif

    @if($subtitle)
        <div style="font-size:13.5px; line-height:1.7; max-width:420px; color:{{ $inkSub }};">{!! $subtitle !!}</div>
    @endif

    @isset($actions)
        <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin-top:12px;">{{ $actions }}</div>
    @endisset

    {{ $slot }}
</div>
