{{--
 |----------------------------------------------------------------------------
 | <x-art.backdrop> — ภาพประกอบพื้นหลังสำหรับ section เดิมที่มีอยู่แล้ว
 |----------------------------------------------------------------------------
 | วางเป็น "ลูกตัวแรก" ของ section ที่มี position:relative (หรือ class relative)
 | แล้วให้กล่องเนื้อหาข้างในมี class `relative` เพื่อให้ตัวหนังสือทับอยู่ด้านบน
 |
 | @param string $image    ชื่อไฟล์ใน public/images/art (ไม่ต้องใส่ .webp)
 | @param float  $opacity  ความทึบของภาพ (0-1)
 | @param string $mask     fade-bottom | vignette | edges | none
 | @param string $blend    mix-blend-mode เช่น normal, screen, soft-light
 | @param string $focus    object-position
 | @param string $tone     dark = ม่านสีเข้ม / light = ม่านสีครีม
 | @param string $height   ความสูงของแถบภาพ (ค่าเริ่มต้น 100% = เต็ม section)
 |                         ใช้กับ section ที่สูงมาก ๆ เช่น min-h-screen จะได้ไม่ครอปภาพจนเละ
 |
 | @example <x-art.backdrop image="cos-daily" tone="dark" :opacity="0.55" mask="vignette" />
 |
 | @tip ไม่มีไฟล์ = ไม่เรนเดอร์อะไรเลย (หน้าเดิมยังใช้งานได้ปกติ)
 --}}
@props([
    'image'   => null,
    'opacity' => 0.5,
    'mask'    => 'fade-bottom',
    'blend'   => 'normal',
    'focus'   => 'center',
    'tone'    => 'dark',
    'height'  => '100%',
])

@php
    $artRel = $image ? 'images/art/' . $image . '.webp' : null;
    $hasArt = $artRel && file_exists(public_path($artRel));

    // สีม่าน — ต้องเข้ากับพื้นหลังของหน้า ไม่งั้นภาพจะดูเป็นแผ่นแปะ
    $dark  = $tone === 'dark';
    $edge  = $dark ? '10,8,26' : '243,238,228';
    $masks = [
        'fade-bottom' => "linear-gradient(180deg, rgba({$edge},.25) 0%, rgba({$edge},.45) 55%, rgba({$edge},.95) 100%)",
        'vignette'    => "radial-gradient(110% 90% at 50% 42%, rgba({$edge},.12) 0%, rgba({$edge},.62) 62%, rgba({$edge},.95) 100%)",
        'edges'       => "linear-gradient(90deg, rgba({$edge},.92) 0%, rgba({$edge},.35) 26%, rgba({$edge},.35) 74%, rgba({$edge},.92) 100%)",
        'none'        => null,
    ];
    $scrim = $masks[$mask] ?? $masks['fade-bottom'];
@endphp

{{-- ดาร์กโหมด: ม่านสีครีมต้องกลายเป็นสีเข้ม ไม่งั้นจะเป็นแผ่นขาวบนหน้ามืด --}}
@once
<style>
    .dark .tp-art-bd-light { background:linear-gradient(180deg, rgba(17,24,39,.28) 0%, rgba(17,24,39,.55) 55%, rgba(17,24,39,.95) 100%) !important; }
</style>
@endonce

@if($hasArt)
    <div aria-hidden="true"
         style="position:absolute; top:0; left:0; right:0; height:{{ $height }}; overflow:hidden; pointer-events:none; z-index:0;"
         {{ $attributes->merge(['class' => 'tp-art-backdrop']) }}>
        <img src="{{ asset($artRel) }}" alt="" loading="lazy" decoding="async"
             style="width:100%; height:100%; object-fit:cover; object-position:{{ $focus }};
                    opacity:{{ $opacity }}; mix-blend-mode:{{ $blend }};">
        @if($scrim)
            <div class="{{ $dark ? '' : 'tp-art-bd-light' }}" style="position:absolute; inset:0; background:{{ $scrim }};"></div>
        @endif
    </div>
@endif
