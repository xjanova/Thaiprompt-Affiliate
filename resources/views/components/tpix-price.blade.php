{{--
 | 🪙 ราคาเป็นเหรียญ TPIX คู่กับราคาบาท (ราคาจริงจากพูล TPIX DEX บนเชน 4289) — ใช้ได้ทุกหน้าสินค้า/ทุกธีม
 |
 | ตัวอย่าง:
 |   <x-tpix-price :product="$product" />                       ราคาจากสินค้า (ราคาคงที่ TPIX ใช้เฉพาะ Official Shop)
 |   <x-tpix-price :thb="$total" size="lg" />                   ยอดบาทใดๆ (ตะกร้า/แพ็กเกจ/บริการ)
 |   <x-tpix-price :thb="$price" :tpix="$fixed" size="lg" />    ระบุราคาคงที่เอง (ผู้เรียกรับผิดชอบว่าใช้ได้)
 |
 | props:
 |   thb      ราคาบาท (null = ใช้ราคาของ product)
 |   tpix     ราคา TPIX คงที่ (null = แปลงจากบาทตามราคาตลาด)
 |   product  Product (ไม่บังคับ)
 |   size     sm (ป้ายเล็กในการ์ด) | lg (หน้ารายละเอียด: มีบรรทัดอัตรา + แหล่งราคา)
 |
 | ไม่รู้ราคา (RPC ล่ม / ยังไม่มีอัตราแลกเปลี่ยน / แอดมินปิดการแสดง) → ไม่แสดงอะไรเลย
 | ราคาอ่านจากแคช 60 วิ — ไม่ยิงเชนทุกครั้งที่เรนเดอร์
 --}}
@props([
    'thb' => null,
    'tpix' => null,
    'product' => null,
    'size' => 'sm',
])

@php
    $tpxThb = $thb;
    $tpxFixed = $tpix;
    $tpxExternal = false;
    if ($product instanceof \App\Models\Product) {
        // สินค้า Lazada (affiliate/ดึงเข้ามาขาย) ไม่แสดงราคา TPIX
        $tpxExternal = \App\Services\TpixPay\TpixPriceService::isExternalProduct($product);
        $tpxThb ??= $product->price;
        $tpxFixed ??= \App\Services\TpixPay\TpixPriceService::fixedPriceFor($product);
    }
    $tpx = $tpxExternal ? null : app(\App\Services\TpixPay\TpixPriceService::class)->forDisplay($tpxThb, $tpxFixed);
@endphp

@if($tpx)
    @once
        <style>
            .tpx-price{--tpx-gold:#b7791f;--tpx-ink:#7c4a03;--tpx-bg:rgba(245,190,60,.14);--tpx-line:rgba(183,121,31,.32);--tpx-muted:#6b7280;display:inline-flex;align-items:center;gap:.35em;font-weight:700;line-height:1.2;color:var(--tpx-ink);background:var(--tpx-bg);border:1px solid var(--tpx-line);border-radius:999px;padding:.18em .6em .18em .4em;font-variant-numeric:tabular-nums;white-space:nowrap;max-width:100%}
            .tpx-price svg{width:1.05em;height:1.05em;flex:none}
            .tpx-price.tpx-sm{font-size:11.5px}
            .tpx-price.tpx-lg{font-size:14px;padding:.3em .8em .3em .5em}
            .tpx-meta{display:block;margin-top:4px;font-size:11.5px;color:var(--tpx-muted);line-height:1.45}
            .tpx-meta b{font-weight:700;color:inherit}
            @media (prefers-color-scheme: dark){:root:not(.light) .tpx-price{--tpx-ink:#fcd77f;--tpx-bg:rgba(250,204,21,.12);--tpx-line:rgba(250,204,21,.3)}:root:not(.light) .tpx-meta{--tpx-muted:#9ca3af}}
            .dark .tpx-price,[data-theme="dark"] .tpx-price{--tpx-ink:#fcd77f;--tpx-bg:rgba(250,204,21,.12);--tpx-line:rgba(250,204,21,.3)}
            .dark .tpx-meta,[data-theme="dark"] .tpx-meta{--tpx-muted:#9ca3af}
        </style>
    @endonce

    @php
        $tpxTitle = '1 TPIX = '.$tpx['rate_display'].' · ราคาจริงจาก TPIX DEX (เชน '.config('tpix_pay.chain_id').') · อัปเดต '.$tpx['updated_time'].' น.';
    @endphp

    @if($size === 'lg')
        <span {{ $attributes->merge(['class' => 'tpx-wrap']) }}>
            <span class="tpx-price tpx-lg" title="{{ $tpxTitle }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="#f5b72e"/><circle cx="12" cy="12" r="7.2" fill="none" stroke="#a86b05" stroke-width="1.4"/><path d="M8.6 8.4h6.8v1.9h-2.4V16h-2V10.3H8.6z" fill="#7c4a03"/></svg>
                <span>{{ $tpx['fixed'] ? '' : '≈ ' }}{{ $tpx['tpix'] }} TPIX</span>
            </span>
            <span class="tpx-meta">
                @if($tpx['fixed'])
                    ราคาเหรียญคงที่จากร้าน ·
                @endif
                1 TPIX = <b>{{ $tpx['rate_display'] }}</b> · ราคาจริงจาก TPIX DEX · อัปเดต {{ $tpx['updated_time'] }} น.
            </span>
        </span>
    @else
        <span {{ $attributes->merge(['class' => 'tpx-price tpx-sm']) }} title="{{ $tpxTitle }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="#f5b72e"/><circle cx="12" cy="12" r="7.2" fill="none" stroke="#a86b05" stroke-width="1.4"/><path d="M8.6 8.4h6.8v1.9h-2.4V16h-2V10.3H8.6z" fill="#7c4a03"/></svg>
            <span>{{ $tpx['fixed'] ? '' : '≈ ' }}{{ $tpx['tpix'] }} TPIX</span>
        </span>
    @endif
@endif
