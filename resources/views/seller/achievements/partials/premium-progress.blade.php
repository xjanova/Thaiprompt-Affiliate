{{--
 | ความคืบหน้าสู่ร้าน Premium (จาก PremiumStoreService::getPremiumEligibilityReport)
 | ตัวแปร: $premiumReport, $showCriteria (bool)
 --}}
@php
    $report = $premiumReport ?? [];
    $progress = max(0, min(100, (float) ($report['progress'] ?? 0)));
    $criteriaNames = ['rating' => '⭐ คะแนนรีวิว', 'reviews' => '💬 จำนวนรีวิว', 'sales' => '🛒 ยอดขาย', 'followers' => '👥 ผู้ติดตาม', 'products' => '📦 จำนวนสินค้า', 'age' => '📅 อายุร้าน', 'trophies' => '🏆 แต้ม Trophy'];
    $showCriteria = $showCriteria ?? false;
@endphp

<div class="tp-card" style="display:flex; flex-direction:column; gap:12px; background:linear-gradient(140deg, color-mix(in srgb, var(--accent1) 20%, var(--card-bg)), var(--card-bg) 70%);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap;">
        <div>
            <div class="tp-section-h">💎 เส้นทางสู่ร้าน Premium</div>
            <div style="font-size:13px; margin-top:4px; line-height:1.6;">{{ $report['message'] ?? '' }}</div>
        </div>
        <x-seller-kit.pill :tone="! empty($report['eligible']) ? 'ok' : 'warn'">{{ ! empty($report['eligible']) ? 'ผ่านเกณฑ์แล้ว' : 'ยังไม่ผ่านเกณฑ์' }}</x-seller-kit.pill>
    </div>
    <div>
        <div style="display:flex; justify-content:space-between; font-size:12.5px;">
            <span>คะแนนร้าน <strong class="tp-num">{{ number_format((float) ($report['score'] ?? 0), 1) }}</strong></span>
            <span style="color:var(--ink2);">เกณฑ์ {{ number_format((float) ($report['min_score'] ?? 0), 0) }}</span>
        </div>
        <div class="tp-inset-sm" style="height:12px; border-radius:99px; overflow:hidden; margin-top:6px;">
            <div style="height:100%; width:{{ max(2, $progress) }}%; border-radius:99px; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
        </div>
    </div>

    @if($showCriteria && ! empty($report['criteria']))
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px;">
            @foreach($report['criteria'] as $key => $c)
                <div class="tp-inset-sm" style="border-radius:12px; padding:10px 12px;">
                    <div style="display:flex; justify-content:space-between; font-size:12.5px;">
                        <span>{{ $criteriaNames[$key] ?? $key }}</span>
                        <span class="tp-num" style="font-weight:800;">{{ number_format((float) ($c['score'] ?? 0), 0) }}</span>
                    </div>
                    <div style="height:6px; border-radius:99px; overflow:hidden; margin-top:6px; background:color-mix(in srgb, var(--ink2) 15%, transparent);">
                        <div style="height:100%; width:{{ max(0, min(100, (float) ($c['score'] ?? 0))) }}%; border-radius:99px; background:linear-gradient(90deg, var(--accent1), var(--accent2));"></div>
                    </div>
                    <div style="font-size:11px; color:var(--ink2); margin-top:4px;">ค่าปัจจุบัน {{ is_numeric($c['value'] ?? null) ? number_format((float) $c['value'], (float) $c['value'] == (int) $c['value'] ? 0 : 2) : ($c['value'] ?? '—') }} · น้ำหนัก {{ number_format((float) ($c['weight'] ?? 0) * 100, 0) }}%</div>
                </div>
            @endforeach
        </div>
    @endif

    @if(! empty($report['tips']))
        <div style="display:flex; flex-direction:column; gap:6px;">
            @foreach($report['tips'] as $tip)
                <div style="font-size:12.5px; line-height:1.6;">💡 {{ $tip }}</div>
            @endforeach
        </div>
    @endif
</div>
