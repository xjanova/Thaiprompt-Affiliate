{{--
 | เหรียญ Trophy 1 ใบ
 | ตัวแปร: $trophy (StoreTrophy), $achieved (bool), $achievement (StoreTrophyAchievement|null)
 --}}
@php
    $tierTone = ['bronze' => 'warn', 'silver' => 'muted', 'gold' => 'gold', 'platinum' => 'info', 'diamond' => 'violet'];
    $tone = $tierTone[$trophy->tier] ?? 'gold';
    $icon = (string) ($trophy->icon ?? '');
    $isFa = str_starts_with($icon, 'fa');
    $name = $trophy->name_th ?: $trophy->name;
    $desc = $trophy->description_th ?: $trophy->description;
@endphp

<div class="tp-card" style="display:flex; flex-direction:column; align-items:center; text-align:center; gap:8px; padding:18px 14px; {{ $achieved ? '' : 'opacity:.62;' }}">
    <div class="{{ $achieved ? 'tp-tile' : 'tp-inset' }}" style="width:62px; height:62px; border-radius:50%; font-size:28px; display:grid; place-items:center; {{ $achieved ? '' : 'filter:grayscale(1);' }}" aria-hidden="true">
        @if($isFa)
            <i class="{{ $icon }}"></i>
        @else
            {{ $icon !== '' ? $icon : '🏆' }}
        @endif
    </div>
    <div style="font-weight:800; font-size:13.5px;">{{ $name }}</div>
    <x-seller-kit.pill :tone="$tone">{{ $trophy->tier_name_th }} · {{ number_format((int) $trophy->points) }} แต้ม</x-seller-kit.pill>
    @if($desc)
        <div style="font-size:11.5px; color:var(--ink2); line-height:1.55;">{{ $desc }}</div>
    @endif
    @if($achieved && $achievement)
        <div style="font-size:11px; color:var(--ink2);">ได้รับเมื่อ {{ optional($achievement->achieved_at)->format('d/m/Y') }}</div>
    @elseif(! $achieved)
        <div style="font-size:11px; color:var(--ink2);">🔒 ยังไม่ปลดล็อก</div>
    @endif
</div>
