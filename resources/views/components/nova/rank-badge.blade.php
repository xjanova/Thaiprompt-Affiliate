{{--
    เหรียญยศแบบโนวา (หน้า user/ranks/*) — แทน <x-rank-icon> ที่ใช้คลาส Tailwind ซึ่งเปลือกโนวาไม่ได้โหลด

    <x-nova.rank-badge :rank="$rank" size="sm|md|lg|xl" />
    - รูปไอคอนที่แอดมินอัปโหลด (icon_url) ก่อน ไม่มีก็ใช้อีโมจิ/ตัวอักษร (display_icon)
    - สีพื้นจาก ranks.color — รับเฉพาะรหัสสี hex กันค่าแปลกปลอมหลุดเข้า style
--}}
@props(['rank' => null, 'size' => 'md'])

@php
    $color = preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $rank?->color) ? $rank->color : '#d4a64a';
    $img = $rank?->icon_url;
@endphp

<span {{ $attributes->class(['nvm-rk-badge', 'nvm-rk-badge--'.$size]) }} style="--rk-c: {{ $color }}" aria-hidden="true">
    {{-- อีโมจิ/ตัวอักษรโชว์ไว้ก่อน ซ่อนเมื่อรูปโหลดสำเร็จ: รูปเสีย (เช่น RankSeeder ชี้ images/ranks/*.svg ที่ไม่มีในโปรเจกต์) ก็ไม่เห็นวงว่าง --}}
    <span>{{ $rank?->display_icon ?? '★' }}</span>
    @if($img)
        <img src="{{ $img }}" alt="" loading="lazy" onload="this.parentNode.classList.add('has-img')" onerror="this.remove()">
    @endif
</span>
