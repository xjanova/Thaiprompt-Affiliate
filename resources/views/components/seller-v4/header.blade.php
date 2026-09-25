{{--
 | หัวหน้าเพจของแผงผู้ขาย V4 — ไอคอนทอง + ชื่อหน้า + คำอธิบาย + ปุ่มย้อนกลับ + ช่องปุ่มด้านขวา (slot)
 | ใช้งาน: <x-seller-v4.header title="..." subtitle="..." icon="📦" :back="route('...')"> ปุ่ม </x-seller-v4.header>
 --}}
@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'back' => null,
    'crumb' => 'ร้านค้าของฉัน',
])

<div class="tp-card" style="padding:20px 22px; background:linear-gradient(120deg, color-mix(in srgb, var(--accent1) 18%, transparent), transparent 72%);">
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;">
        <div style="display:flex; align-items:center; gap:13px; min-width:0; flex:1 1 280px;">
            @if($back)
                <a href="{{ $back }}" class="tp-icon-btn" aria-label="ย้อนกลับ" style="text-decoration:none; width:40px; height:40px; border-radius:12px;">
                    <i class="fas fa-arrow-left"></i>
                </a>
            @endif
            @if($icon)
                <span class="tp-tile" style="width:50px; height:50px; border-radius:15px; font-size:23px;">{{ $icon }}</span>
            @endif
            <div style="min-width:0;">
                @if($crumb)
                    <div style="font-size:11px; color:var(--ink2); font-weight:600; letter-spacing:.3px;">{{ $crumb }}</div>
                @endif
                <h1 style="font-size:clamp(19px,3.6vw,26px); font-weight:800; margin:2px 0 0; color:var(--ink); overflow-wrap:anywhere;">{{ $title }}</h1>
                @if($subtitle)
                    <div style="font-size:12.5px; color:var(--ink2); margin-top:3px;">{{ $subtitle }}</div>
                @endif
            </div>
        </div>
        @if(trim((string) $slot) !== '')
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
