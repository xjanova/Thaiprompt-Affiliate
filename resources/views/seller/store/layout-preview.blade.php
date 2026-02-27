{{--
    Layout Preview — ใช้ section partials ร่วมกับ storefront
    เพื่อให้ Preview ตรงกับหน้าร้านจริง 100%
--}}
@extends('layouts.storefront')

@section('title', ($store->store_name ?? 'Preview') . ' - Layout Preview')

@push('styles')
<style>
    :root {
        --store-primary: {{ $layoutSettings->primary_color ?? '#6366f1' }};
        --store-secondary: {{ $layoutSettings->secondary_color ?? '#8b5cf6' }};
        --store-accent: {{ $layoutSettings->accent_color ?? '#ec4899' }};
        --store-text: {{ $layoutSettings->text_color ?? '#1f2937' }};
        --store-bg: {{ $layoutSettings->background_color ?? '#ffffff' }};
    }

    .store-bg {
        background-color: var(--store-bg);
    }

    .store-primary-text {
        color: var(--store-primary);
    }

    .store-primary-bg {
        background-color: var(--store-primary);
    }

    .store-button {
        background: linear-gradient(135deg, var(--store-primary), var(--store-secondary));
    }

    .store-button:hover {
        filter: brightness(1.1);
    }

    .store-accent-bg {
        background-color: var(--store-accent);
    }

    .product-card-default {
        @apply bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1;
    }

    .product-card-minimal {
        @apply bg-white dark:bg-gray-800 rounded-lg overflow-hidden transition-all duration-300 hover:shadow-lg;
    }

    .product-card-detailed {
        @apply bg-white/80 dark:bg-gray-800/80 backdrop-blur-lg rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl;
    }

    /* สำหรับ Product Detail Preview */
    .store-price-gradient {
        background: linear-gradient(135deg, var(--store-primary), var(--store-secondary));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .store-price-box {
        background: linear-gradient(135deg,
            color-mix(in srgb, var(--store-primary) 10%, white),
            color-mix(in srgb, var(--store-secondary) 10%, white)
        );
        border-color: color-mix(in srgb, var(--store-primary) 30%, white);
    }
    .store-tab-active {
        border-color: var(--store-primary);
        color: var(--store-primary);
        background: color-mix(in srgb, var(--store-primary) 8%, white);
    }
    .store-thumbnail-active {
        border-color: var(--store-primary) !important;
    }
    .store-badge-featured {
        background: linear-gradient(135deg, var(--store-accent), color-mix(in srgb, var(--store-accent) 80%, #ff6600));
    }
</style>

{{-- Custom CSS จากผู้ใช้ --}}
@if($layoutSettings->custom_css)
<style>
{!! $layoutSettings->custom_css !!}
</style>
@endif
@endpush

@section('content')
<div class="min-h-screen bg-transparent">

    @php
        // ====== สร้าง dummy data สำหรับ Preview ======

        // Dummy products (ใช้ anonymous objects)
        $dummyProducts = collect();
        $productNames = [
            'เสื้อยืดคอกลม Premium', 'กระเป๋าสะพาย Urban', 'รองเท้าผ้าใบ Sport',
            'นาฬิกาข้อมือ Classic', 'แว่นตากันแดด Retro', 'หมวกแก๊ป Minimal',
            'เข็มขัดหนังแท้', 'กางเกงขาสั้น Cool', 'เสื้อเชิ้ต Modern',
            'สร้อยคอ Silver', 'ต่างหู Crystal', 'แหวนเงินแท้',
        ];
        for ($i = 0; $i < 12; $i++) {
            $price = rand(199, 2999);
            $hasSale = $i % 3 === 0;
            $salePrice = $hasSale ? (int)($price * 0.7) : null;
            $dummyProducts->push((object)[
                'name' => $productNames[$i] ?? 'สินค้าตัวอย่าง ' . ($i + 1),
                'slug' => 'demo-product-' . ($i + 1),
                'price' => $price,
                'sale_price' => $salePrice,
                'discount_percent' => $hasSale ? 30 : 0,
                'primary_image_url' => null,
                'rating_average' => $i % 2 === 0 ? round(rand(38, 50) / 10, 1) : 0,
                'sales_count' => rand(5, 500),
            ]);
        }

        // Dummy featured products (สุ่ม 8 ตัวแรก)
        $featuredProducts = $dummyProducts->take($layoutSettings->featured_products_count ?? 8);

        // Dummy paginated products (ทำเป็น LengthAwarePaginator mock)
        $products = new \Illuminate\Pagination\LengthAwarePaginator(
            $dummyProducts,
            $dummyProducts->count(),
            12,
            1
        );

        // Dummy categories
        $categories = collect([
            (object)['name' => 'เสื้อผ้า', 'slug' => 'clothing', 'icon' => '👕'],
            (object)['name' => 'รองเท้า', 'slug' => 'shoes', 'icon' => '👟'],
            (object)['name' => 'กระเป๋า', 'slug' => 'bags', 'icon' => '👜'],
            (object)['name' => 'เครื่องประดับ', 'slug' => 'jewelry', 'icon' => '💍'],
            (object)['name' => 'อิเล็กทรอนิกส์', 'slug' => 'electronics', 'icon' => '📱'],
            (object)['name' => 'อื่นๆ', 'slug' => 'others', 'icon' => '📦'],
        ]);

        // Dummy stats
        $stats = [
            'total_products' => 156,
            'total_sales' => 1284,
            'rating' => 4.8,
            'rating_count' => 342,
        ];

        // ====== Render sections ตามลำดับที่ user กำหนด ======
        $orderedSections = $layoutSettings->getOrderedSections();
        $sectionMap = [
            'header' => 'vendor-store.sections.header',
            'slider' => 'vendor-store.sections.slider',
            'promotion' => 'vendor-store.sections.promotion',
            'featured_products' => 'vendor-store.sections.featured-products',
            'categories' => 'vendor-store.sections.categories',
            'all_products' => 'vendor-store.sections.all-products',
            'footer' => 'vendor-store.sections.footer',
        ];
    @endphp

    @foreach($orderedSections as $section)
        @if(isset($sectionMap[$section]))
            @include($sectionMap[$section], [
                'store' => $store,
                'layoutSettings' => $layoutSettings,
                'products' => $products ?? null,
                'categories' => $categories ?? collect(),
                'featuredProducts' => $featuredProducts ?? null,
                'stats' => $stats ?? [],
                'isPreview' => true,
            ])
        @endif
    @endforeach

    {{-- ส่วน Preview หน้ารายละเอียดสินค้า --}}
    @include('vendor-store.sections.product-detail-preview', [
        'store' => $store,
        'layoutSettings' => $layoutSettings,
    ])

</div>
@endsection

@push('scripts')
{{-- Swiper JS สำหรับ Slider --}}
@if($layoutSettings->slider_enabled && !empty($layoutSettings->slider_images))
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        new Swiper('#store-slider', {
            loop: true,
            autoplay: {
                delay: {{ $layoutSettings->slider_autoplay_speed ?? 5000 }},
                disableOnInteraction: false,
            },
            effect: '{{ $layoutSettings->slider_effect ?? 'slide' }}',
            @if($layoutSettings->slider_show_arrows)
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            @endif
            @if($layoutSettings->slider_show_dots)
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            @endif
        });
    });
</script>
@endif

{{-- Custom JS --}}
@if($layoutSettings->custom_js)
<script>
{!! $layoutSettings->custom_js !!}
</script>
@endif
@endpush
