{{--
 | การ์ดตัวเลขสรุป (KPI) แบบ V4
 | ตัวแปร: $kpiIcon (fa-*), $kpiValue (ข้อความที่จัดรูปแล้ว), $kpiLabel, $kpiTone (ok|warn|bad|info|violet|null=ทอง),
 |         $kpiHref (ไม่บังคับ — ทั้งการ์ดเป็นลิงก์), $kpiHint (ไม่บังคับ — บรรทัดเล็กใต้ชื่อ), $kpiPulse (bool — จุดกะพริบเตือน)
--}}
@php
    $kpiVar = [
        'ok' => '--w-ok',
        'warn' => '--w-warn',
        'bad' => '--w-bad',
        'info' => '--w-info',
        'violet' => '--w-violet',
    ][$kpiTone ?? ''] ?? null;
    $kpiTileStyle = $kpiVar
        ? 'background:linear-gradient(135deg, var('.$kpiVar.'), color-mix(in srgb, var('.$kpiVar.') 70%, var(--ink)));'
        : '';
    $kpiTag = ! empty($kpiHref) ? 'a' : 'div';
@endphp
<{{ $kpiTag }} @if(! empty($kpiHref)) href="{{ $kpiHref }}" @endif class="tp-card {{ ! empty($kpiHref) ? 'tp-card-hover' : '' }}"
     style="padding:16px; display:flex; align-items:center; gap:12px; text-decoration:none; color:var(--ink); position:relative; min-width:0;">
    <span class="tp-tile" style="width:42px; height:42px; font-size:17px; {{ $kpiTileStyle }}">
        <i class="fas {{ $kpiIcon ?? 'fa-chart-simple' }}"></i>
    </span>
    <span style="min-width:0;">
        <span class="tp-num" style="display:block; font-size:clamp(17px,2.1vw,23px); font-weight:800; line-height:1.15; overflow-wrap:anywhere;">{{ $kpiValue ?? '0' }}</span>
        <span style="display:block; font-size:12px; color:var(--ink2); margin-top:3px;">{{ $kpiLabel ?? '' }}</span>
        @if (! empty($kpiHint))
            <span style="display:block; font-size:11px; color:var(--ink2); opacity:.85;">{{ $kpiHint }}</span>
        @endif
    </span>
    @if (! empty($kpiPulse))
        <span style="position:absolute; top:12px; right:12px; width:9px; height:9px; border-radius:50%; background:var({{ $kpiVar ?? '--w-warn' }}); animation:tpPulse 1.4s ease-in-out infinite;"></span>
    @endif
</{{ $kpiTag }}>
