{{--
 | ตัวอย่างหน้าร้าน (ผู้ขายดูก่อนเผยแพร่) — ธีม V4 (frontend-v4) ใช้ section ชุดเดียวกับหน้าร้านจริง
 | ข้อมูลจาก Seller\StoreLayoutController@preview: $store, $layoutSettings — สินค้า/หมวด/สถิติเป็นข้อมูลตัวอย่าง
 --}}
@extends('layouts.frontend-v4')

@section('title', ($store->store_name ?? 'ตัวอย่างร้าน').' - ตัวอย่างหน้าร้าน')

@php
    $lpA = \App\Support\Shop\StoreTheme::brand($layoutSettings->primary_color ?? null, 'var(--accent1)');
    $lpB = \App\Support\Shop\StoreTheme::brand($layoutSettings->secondary_color ?? null, 'var(--accent2)');
    $lpC = \App\Support\Shop\StoreTheme::brand($layoutSettings->accent_color ?? null, 'var(--deep2)');
    $lpCss = \App\Support\Shop\StoreTheme::css($layoutSettings->custom_css ?? null);

    // ── ข้อมูลตัวอย่าง (ไม่ได้มาจากฐานข้อมูล) ──
    $lpNames = [
        'เสื้อยืดคอกลม Premium', 'กระเป๋าสะพาย Urban', 'รองเท้าผ้าใบ Sport', 'นาฬิกาข้อมือ Classic',
        'แว่นตากันแดด Retro', 'หมวกแก๊ป Minimal', 'เข็มขัดหนังแท้', 'กางเกงขาสั้น Cool',
        'เสื้อเชิ้ต Modern', 'สร้อยคอ Silver', 'ต่างหู Crystal', 'แหวนเงินแท้',
    ];
    $lpPrices = [590, 1290, 1890, 2490, 790, 390, 990, 690, 1190, 1590, 450, 1290];
    $lpDummy = collect();
    foreach ($lpNames as $i => $name) {
        $hasSale = $i % 3 === 0;
        $lpDummy->push((object) [
            'name' => $name,
            'slug' => 'demo-product-'.($i + 1),
            'price' => $lpPrices[$i],
            'sale_price' => $hasSale ? (int) round($lpPrices[$i] * 0.7) : null,
            'discount_percent' => $hasSale ? 30 : 0,
            'primary_image_url' => null,
            'rating_average' => $i % 2 === 0 ? 4.8 : 0,
            'sales_count' => 20 + $i * 13,
        ]);
    }
    $lpFeatured = $lpDummy->take(max(1, (int) ($layoutSettings->featured_products_count ?? 8)));
    $lpProducts = new \Illuminate\Pagination\LengthAwarePaginator($lpDummy, $lpDummy->count(), 12, 1);
    $lpCategories = collect([
        (object) ['name' => 'เสื้อผ้า', 'slug' => 'clothing', 'icon' => '👕'],
        (object) ['name' => 'รองเท้า', 'slug' => 'shoes', 'icon' => '👟'],
        (object) ['name' => 'กระเป๋า', 'slug' => 'bags', 'icon' => '👜'],
        (object) ['name' => 'เครื่องประดับ', 'slug' => 'jewelry', 'icon' => '💍'],
        (object) ['name' => 'อิเล็กทรอนิกส์', 'slug' => 'electronics', 'icon' => '📱'],
        (object) ['name' => 'อื่นๆ', 'slug' => 'others', 'icon' => '📦'],
    ]);
    $lpStats = ['total_products' => 156, 'total_sales' => 1284, 'rating' => 4.8, 'rating_count' => 342];
    $lpSectionMap = [
        'header' => 'vendor-store.sections.header',
        'slider' => 'vendor-store.sections.slider',
        'promotion' => 'vendor-store.sections.promotion',
        'featured_products' => 'vendor-store.sections.featured-products',
        'categories' => 'vendor-store.sections.categories',
        'all_products' => 'vendor-store.sections.all-products',
        'footer' => 'vendor-store.sections.footer',
    ];
@endphp

@if($lpCss !== '')
    @push('styles')
    <style>{!! $lpCss !!}</style>
    @endpush
@endif

@section('content')
<x-theme-v4.shop-kit />

<div style="position:sticky; top:0; z-index:40; display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:10px; padding:10px 16px; color:var(--on-accent, #fff); background:linear-gradient(135deg, var(--deep1), var(--deep2)); box-shadow:var(--card-shadow-sm); font-size:13px; font-weight:700;">
    <i class="fas fa-eye"></i> โหมดตัวอย่างหน้าร้าน — สินค้าและตัวเลขเป็นข้อมูลตัวอย่าง
    @if($layoutSettings->custom_js)
        <span style="font-weight:600; opacity:.92;">· โค้ด JavaScript เพิ่มเติมจะไม่ถูกรันบนหน้าร้านสาธารณะเพื่อความปลอดภัยของลูกค้า</span>
    @endif
</div>

<main style="flex:1; padding-bottom:40px; --store-a: {{ $lpA }}; --store-b: {{ $lpB }}; --store-c: {{ $lpC }};">
    @if(! $store)
        <section class="sf-wrap" style="padding-top:24px;">
            <div class="tp-card" style="text-align:center; padding:36px 16px;">
                <div style="font-size:44px;" aria-hidden="true">🏪</div>
                <h1 style="margin:10px 0 6px; font-size:20px; font-weight:800; color:var(--ink);">ยังไม่มีร้านค้า</h1>
                <p class="tp-muted" style="margin:0;">สร้างร้านค้าก่อน แล้วกลับมาดูตัวอย่างหน้าร้านได้ที่นี่</p>
            </div>
        </section>
    @else
    @foreach($layoutSettings->getOrderedSections() as $section)
        @if(isset($lpSectionMap[$section]))
            @include($lpSectionMap[$section], [
                'store' => $store,
                'layoutSettings' => $layoutSettings,
                'products' => $lpProducts,
                'categories' => $lpCategories,
                'featuredProducts' => $lpFeatured,
                'stats' => $lpStats,
                'isPreview' => true,
            ])
        @endif
    @endforeach

    @include('vendor-store.sections.product-detail-preview', [
        'store' => $store,
        'layoutSettings' => $layoutSettings,
    ])
    @endif
</main>
@endsection
