{{--
 | การ์ดตัวเลขสถิติ (KPI) ของแผงร้านค้า V4
 | ใช้: <x-seller-kit.stat label="ยอดขายวันนี้" value="฿1,200" icon="💰" tone="ok" hint="12 รายการ" />
 | tone: gold (ค่าเริ่มต้น) | ok | bad | warn | info | violet | muted
 --}}
@props(['label', 'value', 'icon' => null, 'tone' => 'gold', 'hint' => null])

@php
    // สีตาม tone — ใช้ CSS variable พร้อมค่าสำรอง เพื่อให้ Theme Studio ปรับได้
    $toneColors = [
        'gold' => 'var(--deep1)',
        'ok' => 'var(--tp-ok, #5aa07e)',
        'bad' => 'var(--tp-bad, #d9534f)',
        'warn' => 'var(--tp-warn, #d08f1f)',
        'info' => 'var(--tp-info, #5689b8)',
        'violet' => 'var(--tp-violet, #8b6bb8)',
        'muted' => 'var(--ink2)',
    ];
    $kitColor = $toneColors[$tone] ?? $toneColors['gold'];
@endphp

<div class="tp-card" style="padding:16px 18px; min-width:0;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
        <div style="font-size:12px; color:var(--ink2); font-weight:600; line-height:1.4;">{{ $label }}</div>
        @if($icon)
            <span class="tp-tile" aria-hidden="true"
                  style="width:36px; height:36px; border-radius:11px; font-size:16px; background:color-mix(in srgb, {{ $kitColor }} 20%, transparent); color:{{ $kitColor }}; text-shadow:none;">{{ $icon }}</span>
        @endif
    </div>
    <div class="tp-num" style="font-size:clamp(20px,3.2vw,25px); font-weight:800; margin-top:8px; color:{{ $kitColor }}; overflow-wrap:anywhere; line-height:1.15;">{{ $value }}</div>
    @if($hint || trim((string) $slot) !== '')
        <div style="font-size:11px; color:var(--ink2); margin-top:4px; line-height:1.5;">{{ $hint }}{{ $slot }}</div>
    @endif
</div>
