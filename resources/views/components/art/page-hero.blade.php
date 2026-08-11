{{--
 |----------------------------------------------------------------------------
 | <x-art.page-hero> — แถบหัวหน้า (Hero) พร้อมภาพประกอบ
 |----------------------------------------------------------------------------
 | ใช้ภาพจาก public/images/art/{image}.webp (เจนเองด้วยเว็บแอป — ไม่เสียเครดิต)
 |
 | @param string      $image     ชื่อไฟล์ใน public/images/art (ไม่ต้องใส่ .webp)
 | @param string|null $eyebrow   ข้อความเล็กเหนือหัวเรื่อง
 | @param string      $title     หัวเรื่อง (HTML ได้)
 | @param string|null $subtitle  คำโปรย
 | @param string      $tone      light = ตัวหนังสือเข้มบนภาพสว่าง / dark = ตัวหนังสือขาวบนภาพมืด
 | @param string      $height    sm | md | lg
 | @param string      $align     left | center
 | @param string      $focus     ตำแหน่งโฟกัสภาพ (object-position) เช่น "center", "right center"
 |
 | @example
 | <x-art.page-hero image="cos-daily" tone="dark" title="ดวงประจำวัน"
 |                  subtitle="อ่านพลังดาวประจำวันของคุณ">
 |     <x-slot:actions><a href="#" class="...">เริ่มเลย</a></x-slot:actions>
 | </x-art.page-hero>
 |
 | @tip ถ้าไฟล์ภาพยังไม่มี component จะ fallback เป็นพื้นไล่เฉดอัตโนมัติ (ไม่มีรูปแตก)
 --}}
@props([
    'image'    => null,
    'eyebrow'  => null,
    'title'    => null,
    'subtitle' => null,
    'tone'     => 'light',
    'height'   => 'md',
    'align'    => 'left',
    'focus'    => 'center',
    'rounded'  => true,
])

{{-- ดาร์กโหมด: layout บางตัว (landing/app/wiki) สลับ .dark ที่ <html>
     ม่านสีครีมของโหมดสว่างจะกลายเป็นแผ่นขาวโพลนบนพื้นมืด จึงต้องมีชุดทับไว้ --}}
@once
<style>
    .dark .tp-art-scrim-light   { background:linear-gradient(90deg, rgba(17,24,39,.93) 0%, rgba(17,24,39,.74) 44%, rgba(17,24,39,.20) 80%, rgba(17,24,39,0) 100%) !important; }
    .dark .tp-art-scrim-light-c { background:radial-gradient(120% 100% at 50% 50%, rgba(17,24,39,.36) 0%, rgba(17,24,39,.86) 100%) !important; }
    .dark .tp-art-ink           { color:#f3f4f6 !important; text-shadow:0 2px 12px rgba(0,0,0,.45); }
    .dark .tp-art-ink2          { color:rgba(243,244,246,.80) !important; text-shadow:0 1px 8px rgba(0,0,0,.5); }
</style>
@endonce

@php
    // ตรวจว่าไฟล์ภาพมีจริงไหม — ถ้าไม่มีให้ใช้พื้นไล่เฉดแทน (กันรูปแตก)
    $artRel  = $image ? 'images/art/' . $image . '.webp' : null;
    $hasArt  = $artRel && file_exists(public_path($artRel));
    $artUrl  = $hasArt ? asset($artRel) : null;

    // ความสูงตามขนาดที่เลือก (mobile-first — คลุมด้วย clamp ให้ยืดตามจอ)
    $heights = [
        'sm' => 'clamp(150px, 26vw, 230px)',
        'md' => 'clamp(200px, 32vw, 330px)',
        'lg' => 'clamp(260px, 40vw, 430px)',
    ];
    $minH = $heights[$height] ?? $heights['md'];

    $isDark   = $tone === 'dark';
    $isCenter = $align === 'center';

    // ม่านบังภาพ (scrim) — ให้ตัวหนังสืออ่านออกเสมอ ไม่ว่าภาพจะจัดจ้านแค่ไหน
    if ($isCenter) {
        $scrim = $isDark
            ? 'radial-gradient(120% 100% at 50% 50%, rgba(10,8,26,.30) 0%, rgba(10,8,26,.72) 100%)'
            : 'radial-gradient(120% 100% at 50% 50%, rgba(255,253,247,.42) 0%, rgba(255,253,247,.86) 100%)';
    } else {
        $scrim = $isDark
            ? 'linear-gradient(90deg, rgba(10,8,26,.88) 0%, rgba(10,8,26,.66) 42%, rgba(10,8,26,.12) 78%, rgba(10,8,26,0) 100%)'
            : 'linear-gradient(90deg, rgba(255,253,247,.94) 0%, rgba(255,253,247,.74) 44%, rgba(255,253,247,.18) 80%, rgba(255,253,247,0) 100%)';
    }

    // สีตัวหนังสือ — โหมดสว่างอิงตัวแปรธีม V4 (--ink) แต่มี fallback ให้ layout อื่นที่ไม่มีตัวแปรนี้
    $inkMain = $isDark ? '#ffffff'            : 'var(--ink, #33302b)';
    $inkSub  = $isDark ? 'rgba(255,255,255,.82)' : 'var(--ink2, #6b6357)';
    $inkEye  = $isDark ? '#f0c86a'            : 'var(--deep1, #b8892a)';

    // พื้นสำรองตอนไม่มีไฟล์ภาพ
    $fallbackBg = $isDark
        ? 'linear-gradient(135deg,#1a1636 0%,#2b1f4d 55%,#3b2360 100%)'
        : 'linear-gradient(135deg,#fbf7ef 0%,#f4ead6 55%,#efe0c4 100%)';
@endphp

{{-- หมายเหตุ: วาง style ไว้ "ก่อน" $attributes เจตนา — ถ้าผู้เรียกเผลอส่ง style มา
     HTML จะยึดตัวแรก ทำให้ layout ของ component ไม่พังเพราะโดนทับ --}}
<section
    style="position:relative; overflow:hidden; min-height:{{ $minH }};
           display:flex; align-items:center;
           {{ $rounded ? 'border-radius:clamp(16px,2.2vw,26px);' : '' }}
           background:{{ $hasArt ? 'var(--surf, #f1ece3)' : $fallbackBg }};"
    {{ $attributes->merge(['class' => 'tp-art-hero']) }}>

    @if($hasArt)
        {{-- ภาพประกอบ: ใช้ <img> เพื่อให้ lazy-load + ไม่บล็อกการ paint ครั้งแรก --}}
        <img src="{{ $artUrl }}"
             alt=""
             aria-hidden="true"
             loading="lazy"
             decoding="async"
             style="position:absolute; inset:0; width:100%; height:100%;
                    object-fit:cover; object-position:{{ $focus }};">
    @endif

    {{-- ม่านบังภาพ ให้ข้อความคมชัด (โหมดสว่าง = ม่านครีม / ดาร์กโหมด = ม่านเทาเข้ม) --}}
    <div aria-hidden="true"
         class="{{ $isDark ? '' : ($isCenter ? 'tp-art-scrim-light-c' : 'tp-art-scrim-light') }}"
         style="position:absolute; inset:0; background:{{ $scrim }};"></div>

    {{-- เนื้อหา --}}
    <div style="position:relative; z-index:2; width:100%; max-width:1180px; margin:0 auto;
                padding:clamp(22px,4vw,44px) clamp(18px,3.4vw,46px);
                {{ $isCenter ? 'text-align:center;' : '' }}">
        <div style="max-width:{{ $isCenter ? '660px' : '600px' }}; {{ $isCenter ? 'margin:0 auto;' : '' }}">

            @if($eyebrow)
                <div style="font-size:12.5px; font-weight:700; letter-spacing:.5px; margin-bottom:8px;
                            color:{{ $inkEye }};">{{ $eyebrow }}</div>
            @endif

            @if($title)
                <h1 class="{{ $isDark ? '' : 'tp-art-ink' }}"
                    style="margin:0; font-size:clamp(22px,4.2vw,36px); line-height:1.2; font-weight:700;
                           letter-spacing:-.4px; color:{{ $inkMain }};
                           {{ $isDark ? 'text-shadow:0 2px 12px rgba(0,0,0,.45);' : '' }}">{!! $title !!}</h1>
            @endif

            @if($subtitle)
                <p class="{{ $isDark ? '' : 'tp-art-ink2' }}"
                   style="margin:10px 0 0; font-size:clamp(13px,1.7vw,15px); line-height:1.7; color:{{ $inkSub }};
                          {{ $isDark ? 'text-shadow:0 1px 8px rgba(0,0,0,.5);' : '' }}">{!! $subtitle !!}</p>
            @endif

            @isset($actions)
                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:18px;
                            {{ $isCenter ? 'justify-content:center;' : '' }}">{{ $actions }}</div>
            @endisset

            {{ $slot }}
        </div>
    </div>
</section>
