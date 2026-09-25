{{--
 | หน้าร้านของผู้ขาย (/store/{slug}) ตามเลย์เอาต์ที่ผู้ขายจัดเอง — ธีม V4 (frontend-v4)
 | ข้อมูลจาก VendorStoreController@show: $store, $products (paginator), $categories, $stats, $layoutSettings, $featuredProducts
 | ลำดับส่วนตาม $layoutSettings->getOrderedSections() (header, slider, promotion, featured_products, categories, all_products, footer)
 |
 | 🔒 ความปลอดภัย: สี/ลิงก์/CSS/เนื้อหาท้ายร้านที่ผู้ขายกรอก ผ่านการล้างทุกครั้ง (StoreTheme / SafeHtml)
 |    และ "โค้ด JavaScript เพิ่มเติม" ของร้านจะไม่ถูกรันบนหน้าสาธารณะ — หน้านี้อยู่โดเมนเดียวกับระบบหลัก
 |    โค้ดของผู้ขายจะอ่าน session/คุกกี้ของผู้ซื้อและแอดมินที่เข้าชมได้
 --}}
@extends('layouts.frontend-v4')

@section('title', $layoutSettings->meta_title ?: $store->store_name)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) ($layoutSettings->meta_description ?: ($store->store_description ?: 'ร้านค้าออนไลน์บนไทยพร๊อมท์'))), 160))

@section('meta')
    @if($layoutSettings->meta_keywords)
        <meta name="keywords" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $layoutSettings->meta_keywords), 250) }}">
    @endif
@endsection

@php
    $vsA = \App\Support\Shop\StoreTheme::brand($layoutSettings->primary_color ?? null, 'var(--accent1)');
    $vsB = \App\Support\Shop\StoreTheme::brand($layoutSettings->secondary_color ?? null, 'var(--accent2)');
    $vsC = \App\Support\Shop\StoreTheme::brand($layoutSettings->accent_color ?? null, 'var(--deep2)');
    $vsCss = \App\Support\Shop\StoreTheme::css($layoutSettings->custom_css ?? null);
    $vsSectionMap = [
        'header' => 'vendor-store.sections.header',
        'slider' => 'vendor-store.sections.slider',
        'promotion' => 'vendor-store.sections.promotion',
        'featured_products' => 'vendor-store.sections.featured-products',
        'categories' => 'vendor-store.sections.categories',
        'all_products' => 'vendor-store.sections.all-products',
        'footer' => 'vendor-store.sections.footer',
    ];
@endphp

@if($vsCss !== '')
    @push('styles')
    <style>{!! $vsCss !!}</style>
    @endpush
@endif

@section('content')
<x-theme-v4.shop-kit />
<x-theme-v4.public-header active="shop" :search="true" />

<main style="flex:1; padding-bottom:40px; --store-a: {{ $vsA }}; --store-b: {{ $vsB }}; --store-c: {{ $vsC }};">
    @foreach($layoutSettings->getOrderedSections() as $section)
        @if(isset($vsSectionMap[$section]))
            @include($vsSectionMap[$section], [
                'store' => $store,
                'layoutSettings' => $layoutSettings,
                'products' => $products ?? null,
                'categories' => $categories ?? collect(),
                'featuredProducts' => $featuredProducts ?? null,
                'stats' => $stats ?? [],
                'isPreview' => false,
            ])
        @endif
    @endforeach
</main>

<x-theme-v4.public-footer />
<x-eve.widget surface="storefront" />
@endsection
