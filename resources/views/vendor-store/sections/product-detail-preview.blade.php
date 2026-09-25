{{--
 | ตัวอย่างหน้ารายละเอียดสินค้า (ใช้ในหน้าพรีวิวของผู้ขายเท่านั้น) — ธีม V4
 | แสดงให้ผู้ขายเห็นว่าสีร้าน (--store-a / --store-b) ถูกใช้ในหน้าสินค้าอย่างไร — ข้อมูลเป็นตัวอย่าง ไม่ได้มาจากฐานข้อมูล
 | ตัวแปร: $store, $layoutSettings
 --}}
@php
    $pdpDemo = [
        'name' => 'เสื้อยืดคอกลม Premium Collection',
        'price' => 1290,
        'compare' => 1890,
        'desc' => 'เสื้อยืดคอกลมเนื้อผ้า Cotton 100% นุ่มสบาย ระบายอากาศได้ดี เหมาะสำหรับทุกโอกาส',
        'rating' => 4.7,
        'reviews' => 128,
        'sales' => 542,
        'tags' => ['เสื้อยืด', 'Cotton', 'Premium'],
    ];
    $pdpDiscount = (int) round(($pdpDemo['compare'] - $pdpDemo['price']) / $pdpDemo['compare'] * 100);
@endphp

<section class="sf-wrap sf-section">
    <div class="sf-section-h">
        <div>
            <div class="sf-kicker">PREVIEW</div>
            <h2 class="sf-title">ตัวอย่างหน้ารายละเอียดสินค้า</h2>
        </div>
        <span class="tp-pill tp-pill-soft" style="padding:7px 12px;"><i class="fas fa-eye"></i> ข้อมูลตัวอย่าง</span>
    </div>
    <div class="tp-card" style="padding:clamp(14px, 2.6vw, 26px);">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap:clamp(16px, 3vw, 30px); align-items:start;">
            <div style="position:relative; aspect-ratio:1/1; border-radius:22px; display:grid; place-items:center; font-size:72px; background:linear-gradient(135deg, color-mix(in srgb, var(--store-a) 18%, var(--surf)), color-mix(in srgb, var(--store-b) 18%, var(--surf))); box-shadow:var(--inset);">
                👕
                <div class="sf-badges" style="top:14px; left:14px;">
                    <span class="sf-badge sf-badge-sale" style="font-size:12px;">ลด {{ $pdpDiscount }}%</span>
                    <span class="sf-badge" style="font-size:12px; background:var(--store-a);"><i class="fas fa-star"></i> แนะนำ</span>
                </div>
            </div>
            <div class="sf-stack" style="gap:12px;">
                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                    <span class="tp-pill" style="padding:7px 12px; color:var(--store-a); background:color-mix(in srgb, var(--store-a) 14%, transparent);">เสื้อผ้า</span>
                    @foreach($pdpDemo['tags'] as $tag)
                        <span class="tp-pill" style="padding:7px 12px; color:var(--ink2); background:var(--surf); box-shadow:var(--inset-sm);">#{{ $tag }}</span>
                    @endforeach
                </div>
                <h3 class="sf-h1" style="font-size:clamp(22px, 3.6vw, 30px);">{{ $pdpDemo['name'] }}</h3>
                <div class="sf-meta" style="font-size:13px;">
                    <span><span class="sf-stars">★★★★★</span> {{ $pdpDemo['rating'] }} ({{ $pdpDemo['reviews'] }} รีวิว)</span>
                    <span>ขายแล้ว {{ number_format($pdpDemo['sales']) }}</span>
                </div>
                <div style="padding:16px; border-radius:18px; background:color-mix(in srgb, var(--store-a) 10%, var(--surf)); box-shadow:var(--inset-sm);">
                    <span class="sf-compare" style="font-size:15px;">฿{{ number_format($pdpDemo['compare'], 2) }}</span>
                    <div class="tp-num" style="font-size:36px; font-weight:800; color:var(--store-a);">฿{{ number_format($pdpDemo['price'], 2) }}</div>
                </div>
                <p class="tp-muted" style="margin:0; line-height:1.7;">{{ $pdpDemo['desc'] }}</p>
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:10px;">
                    <span class="sf-btn3d is-soft" style="cursor:default;"><i class="fas fa-cart-plus"></i> เพิ่มลงตะกร้า</span>
                    <span class="sf-btn3d" style="cursor:default; background:linear-gradient(180deg, var(--store-a), var(--store-b));"><i class="fas fa-bolt"></i> ซื้อทันที</span>
                </div>
            </div>
        </div>
    </div>
</section>
