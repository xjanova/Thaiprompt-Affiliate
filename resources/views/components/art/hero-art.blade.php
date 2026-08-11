{{--
 |----------------------------------------------------------------------------
 | <x-art.hero-art> — ชั้นภาพประกอบสำหรับ "การ์ดหัวเรื่อง" ที่มีอยู่แล้ว
 |----------------------------------------------------------------------------
 | ออกแบบมาให้แทรกเข้าไปในการ์ด hero เดิมของหน้าสมาชิก โดยไม่ต้องรื้อโครงเดิม
 |
 | วิธีใช้: ทำให้การ์ดเป็น position:relative แล้ววางแท็กนี้เป็นลูกตัวแรก
 |   <div class="tp-card" style="padding:0; overflow:hidden; position:relative;">
 |       <x-art.hero-art image="usr-wallet" />
 |       ...เนื้อหาเดิม...
 |
 | ภาพชิดขวาแล้วไล่จางหายไปทางซ้ายด้วย mask → ตัวหนังสืออ่านออกเสมอ
 | ไม่ต้องมีม่านบังภาพ จึงไม่มีปัญหาสีม่านเพี้ยนตอนสลับสว่าง/มืด
 |
 | @param string $image  ชื่อไฟล์ใน public/images/art (ไม่ต้องใส่ .webp)
 | @param float  $width  สัดส่วนความกว้างของภาพเทียบการ์ด (0-1)
 |
 | @tip ไม่มีไฟล์ = ไม่เรนเดอร์อะไรเลย การ์ดเดิมยังเหมือนเดิมทุกอย่าง
 --}}
@props([
    'image' => null,
    'width' => 0.54,
])

@php
    $artRel = $image ? 'images/art/'.$image.'.webp' : null;
    $hasArt = $artRel && file_exists(public_path($artRel));
    $pct    = max(20, min(70, (int) round(((float) $width) * 100)));
@endphp

@once
<style>
    .tp-heroart {
        position:absolute; top:0; right:0; height:100%;
        object-fit:cover; object-position:center right;
        pointer-events:none; z-index:0;
        -webkit-mask-image:linear-gradient(90deg, transparent 0%, rgba(0,0,0,.5) 34%, #000 78%);
                mask-image:linear-gradient(90deg, transparent 0%, rgba(0,0,0,.5) 34%, #000 78%);
    }
    /* ดาร์กโหมด: ภาพพื้นครีมสว่างเกินบนการ์ดสีเข้ม — หรี่ลงให้เหลือแค่พื้นผิว */
    .dark .tp-heroart { opacity:.4; }
    /* จอเล็ก: ภาพจะเบียดตัวหนังสือ → เปลี่ยนเป็นพื้นหลังจาง ๆ เต็มใบแทน */
    @media (max-width:640px) {
        .tp-heroart { width:100% !important; opacity:.20;
            -webkit-mask-image:linear-gradient(180deg, transparent 0%, #000 100%);
                    mask-image:linear-gradient(180deg, transparent 0%, #000 100%); }
        .dark .tp-heroart { opacity:.16; }
    }
</style>
@endonce

@if($hasArt)
    <img class="tp-heroart" style="width:{{ $pct }}%;"
         src="{{ asset($artRel) }}" alt="" aria-hidden="true" loading="lazy" decoding="async">
@endif
