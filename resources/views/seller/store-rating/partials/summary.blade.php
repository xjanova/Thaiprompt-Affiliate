{{--
 | สรุปคะแนนร้าน: ค่าเฉลี่ย + กราฟการกระจาย 1–5 ดาว + คะแนนย่อย
 | ตัวแปร: $stats (StoreRating::getStoreStats)
 --}}
@php
    $avg = (float) ($stats['average'] ?? 0);
    $fullStars = (int) floor($avg);
    $subScores = [
        ['🛎️ บริการ', (float) ($stats['service_average'] ?? 0)],
        ['🚚 การจัดส่ง', (float) ($stats['shipping_average'] ?? 0)],
        ['💬 การสื่อสาร', (float) ($stats['communication_average'] ?? 0)],
    ];
@endphp

<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:16px;">
    <div class="tp-card" style="display:flex; align-items:center; gap:18px; background:linear-gradient(140deg, color-mix(in srgb, var(--accent1) 22%, var(--card-bg)), var(--card-bg) 70%);">
        <div style="text-align:center; flex:none;">
            <div class="tp-num" style="font-size:48px; font-weight:800; line-height:1; color:var(--deep1);">{{ number_format($avg, 1) }}</div>
            <div style="color:var(--accent1); font-size:18px; letter-spacing:2px; margin-top:4px;" aria-label="{{ number_format($avg, 1) }} ดาว">{{ str_repeat('★', $fullStars) }}{{ str_repeat('☆', max(0, 5 - $fullStars)) }}</div>
            <div style="font-size:12px; color:var(--ink2); margin-top:4px;">{{ number_format((int) ($stats['total'] ?? 0)) }} รีวิว</div>
        </div>
        <div style="flex:1; display:flex; flex-direction:column; gap:6px; min-width:0;">
            @for($s = 5; $s >= 1; $s--)
                @php
                    $pct = (float) ($stats['distribution_percent'][$s] ?? 0);
                @endphp
                <div style="display:flex; align-items:center; gap:8px; font-size:12px;">
                    <span class="tp-num" style="width:22px; text-align:right;">{{ $s }}★</span>
                    <div class="tp-inset-sm" style="flex:1; height:8px; border-radius:99px; overflow:hidden;">
                        <div style="height:100%; width:{{ max(0, $pct) }}%; border-radius:99px; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
                    </div>
                    <span class="tp-num" style="width:34px; color:var(--ink2);">{{ number_format((int) ($stats['distribution'][$s] ?? 0)) }}</span>
                </div>
            @endfor
        </div>
    </div>

    <div class="tp-card" style="display:flex; flex-direction:column; gap:10px; justify-content:center;">
        <div class="tp-section-h">คะแนนแยกด้าน</div>
        @foreach($subScores as [$sLabel, $sValue])
            <div>
                <div style="display:flex; justify-content:space-between; font-size:12.5px;"><span>{{ $sLabel }}</span><span class="tp-num" style="font-weight:800;">{{ $sValue > 0 ? number_format($sValue, 1) : '—' }}</span></div>
                <div class="tp-inset-sm" style="height:8px; border-radius:99px; overflow:hidden; margin-top:4px;">
                    <div style="height:100%; width:{{ max(0, min(100, $sValue / 5 * 100)) }}%; border-radius:99px; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
