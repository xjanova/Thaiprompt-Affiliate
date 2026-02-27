@extends('layouts.storefront')

@section('title', $layoutSettings->meta_title ?? $store->store_name)

@section('meta')
<meta name="description" content="{{ $layoutSettings->meta_description ?? $store->store_description ?? 'ร้านค้าออนไลน์' }}">
@if($layoutSettings->meta_keywords)
<meta name="keywords" content="{{ $layoutSettings->meta_keywords }}">
@endif
@endsection

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

    {{-- Render sections ตามลำดับที่ user กำหนด --}}
    @php
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
                'isPreview' => false,
            ])
        @endif
    @endforeach

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
