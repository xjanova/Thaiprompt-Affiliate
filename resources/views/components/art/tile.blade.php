{{--
 |----------------------------------------------------------------------------
 | <x-art.tile> — กล่องภาพประกอบสัดส่วนคงที่ (ใช้ในการ์ด/แบนเนอร์ย่อย)
 |----------------------------------------------------------------------------
 | @param string      $image  ชื่อไฟล์ใน public/images/art (ไม่ต้องใส่ .webp)
 | @param string      $ratio  สัดส่วนกรอบ เช่น "4/3", "16/9", "1/1", "2/3"
 | @param string|null $alt    ข้อความแทนภาพ (ถ้าเว้นไว้จะถือเป็นภาพตกแต่ง)
 | @param string      $fit    cover | contain
 | @param string      $focus  object-position
 |
 | @example <x-art.tile image="svc-delivery" ratio="4/3" alt="ไรเดอร์และเดลิเวอรี่" />
 |
 | @tip ไม่มีไฟล์ = แสดงพื้นไล่เฉดนวลทอง ไม่ทำให้การ์ดพัง
 --}}
@props([
    'image' => null,
    'ratio' => '4/3',
    'alt'   => null,
    'fit'   => 'cover',
    'focus' => 'center',
])

@php
    $artRel = $image ? 'images/art/' . $image . '.webp' : null;
    $hasArt = $artRel && file_exists(public_path($artRel));
@endphp

<div style="position:relative; overflow:hidden; aspect-ratio:{{ $ratio }};
            background:linear-gradient(135deg, var(--a1soft, #f6eccf) 0%, var(--a2soft, #f4e3cc) 100%);"
     {{ $attributes->merge(['class' => 'tp-art-tile']) }}>
    @if($hasArt)
        <img src="{{ asset($artRel) }}"
             alt="{{ $alt ?? '' }}"
             @if(!$alt) aria-hidden="true" @endif
             loading="lazy"
             decoding="async"
             style="width:100%; height:100%; object-fit:{{ $fit }}; object-position:{{ $focus }}; display:block;">
    @endif
    {{ $slot }}
</div>
