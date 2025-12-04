<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $store->store_name ?? 'Preview' }} - Layout Preview</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --store-primary: {{ $layoutSettings->primary_color ?? '#6366f1' }};
            --store-secondary: {{ $layoutSettings->secondary_color ?? '#8b5cf6' }};
            --store-accent: {{ $layoutSettings->accent_color ?? '#ec4899' }};
            --store-text: {{ $layoutSettings->text_color ?? '#1f2937' }};
            --store-bg: {{ $layoutSettings->background_color ?? '#ffffff' }};
        }

        body {
            background-color: var(--store-bg);
            color: var(--store-text);
        }

        .store-header-gradient {
            background: linear-gradient(135deg, var(--store-primary), var(--store-secondary));
        }

        .store-header-solid {
            background-color: var(--store-primary);
        }

        .store-accent-bg {
            background-color: var(--store-accent);
        }

        .store-primary-text {
            color: var(--store-primary);
        }

        .store-button {
            background: linear-gradient(135deg, var(--store-primary), var(--store-secondary));
        }

        .store-button:hover {
            filter: brightness(1.1);
        }

        /* Slider Styles */
        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Product Card Styles */
        .product-card-default {
            @apply bg-white rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1;
        }

        .product-card-minimal {
            @apply bg-white rounded-lg overflow-hidden transition-all duration-300 hover:shadow-lg;
        }

        .product-card-detailed {
            @apply bg-white rounded-2xl shadow-xl overflow-hidden transition-all duration-300 hover:shadow-2xl;
        }
    </style>
</head>
<body class="min-h-screen">
    {{-- Store Header --}}
    @php
        $headerStyle = $layoutSettings->header_style ?? 'gradient';
        $headerHeight = $layoutSettings->header_height ?? 200;
    @endphp

    <header class="relative overflow-hidden"
            style="height: {{ $headerHeight }}px;
                   @if($headerStyle === 'image' && $layoutSettings->header_image)
                       background-image: url('{{ Storage::url($layoutSettings->header_image) }}');
                       background-size: cover;
                       background-position: center;
                   @elseif($headerStyle === 'solid')
                       background-color: {{ $layoutSettings->primary_color ?? '#6366f1' }};
                   @elseif($headerStyle === 'transparent')
                       background: transparent;
                   @else
                       background: linear-gradient(135deg, {{ $layoutSettings->primary_color ?? '#6366f1' }}, {{ $layoutSettings->secondary_color ?? '#8b5cf6' }});
                   @endif
            ">
        {{-- Overlay for image header --}}
        @if($headerStyle === 'image' && $layoutSettings->header_image)
            <div class="absolute inset-0 bg-black/40"></div>
        @endif

        {{-- Header Content --}}
        <div class="relative z-10 h-full flex items-center justify-center">
            <div class="text-center text-white px-4">
                @if($layoutSettings->show_store_logo && $store->store_logo)
                    <img src="{{ $store->logo_url }}"
                         alt="{{ $store->store_name }}"
                         class="w-20 h-20 md:w-24 md:h-24 rounded-2xl shadow-xl mx-auto mb-4 border-4 border-white/30 object-cover">
                @endif

                @if($layoutSettings->show_store_name)
                    <h1 class="text-3xl md:text-5xl font-bold mb-2 drop-shadow-lg">
                        {{ $store->store_name ?? 'ชื่อร้านค้า' }}
                    </h1>
                @endif

                @if($layoutSettings->show_store_description && $store->store_description)
                    <p class="text-lg text-white/90 max-w-2xl mx-auto">
                        {{ Str::limit($store->store_description, 150) }}
                    </p>
                @endif

                @if($layoutSettings->show_store_stats)
                    <div class="flex items-center justify-center gap-6 mt-4">
                        <div class="text-center">
                            <span class="block text-2xl font-bold">150</span>
                            <span class="text-sm text-white/80">สินค้า</span>
                        </div>
                        <div class="w-px h-8 bg-white/30"></div>
                        <div class="text-center">
                            <span class="block text-2xl font-bold">4.8</span>
                            <span class="text-sm text-white/80">คะแนน</span>
                        </div>
                        <div class="w-px h-8 bg-white/30"></div>
                        <div class="text-center">
                            <span class="block text-2xl font-bold">1.2K</span>
                            <span class="text-sm text-white/80">ผู้ติดตาม</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </header>

    {{-- Banner Slider --}}
    @if($layoutSettings->slider_enabled && !empty($layoutSettings->slider_images))
        <section class="relative">
            <div class="swiper-container overflow-hidden" id="store-slider">
                <div class="swiper-wrapper">
                    @foreach($layoutSettings->slider_images as $slide)
                        @if(!empty($slide['image']))
                            <div class="swiper-slide">
                                <a href="{{ $slide['link'] ?? '#' }}" class="block relative">
                                    <img src="{{ str_starts_with($slide['image'], 'http') ? $slide['image'] : Storage::url($slide['image']) }}"
                                         alt="{{ $slide['title'] ?? 'Banner' }}"
                                         class="w-full h-[300px] md:h-[400px] object-cover">
                                    @if(!empty($slide['title']))
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                            <div class="text-center text-white">
                                                <h3 class="text-2xl md:text-4xl font-bold drop-shadow-lg">{{ $slide['title'] }}</h3>
                                                @if(!empty($slide['subtitle']))
                                                    <p class="text-lg mt-2 drop-shadow">{{ $slide['subtitle'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
                @if($layoutSettings->slider_show_arrows)
                    <div class="swiper-button-prev !text-white !bg-black/30 !rounded-full !w-10 !h-10 after:!text-lg"></div>
                    <div class="swiper-button-next !text-white !bg-black/30 !rounded-full !w-10 !h-10 after:!text-lg"></div>
                @endif
                @if($layoutSettings->slider_show_dots)
                    <div class="swiper-pagination"></div>
                @endif
            </div>
        </section>
    @endif

    {{-- Main Content --}}
    <main class="max-w-7xl mx-auto px-4 py-8">
        {{-- Featured Products Section --}}
        @if($layoutSettings->show_featured_products)
            <section class="mb-12">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl md:text-3xl font-bold store-primary-text flex items-center gap-2">
                        <span>⭐</span>
                        {{ $layoutSettings->featured_title ?? 'สินค้าแนะนำ' }}
                    </h2>
                    <a href="#" class="store-button text-white px-4 py-2 rounded-lg text-sm transition">
                        ดูทั้งหมด →
                    </a>
                </div>

                {{-- Product Grid --}}
                @php
                    $productsPerRow = $layoutSettings->products_per_row ?? 4;
                    $gridCols = match($productsPerRow) {
                        2 => 'grid-cols-1 sm:grid-cols-2',
                        3 => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3',
                        4 => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
                        5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
                        6 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6',
                        default => 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
                    };
                    $productCardStyle = $layoutSettings->product_card_style ?? 'default';
                @endphp

                <div class="grid {{ $gridCols }} gap-4 md:gap-6">
                    @for($i = 0; $i < ($layoutSettings->featured_products_count ?? 8); $i++)
                        <div class="product-card-{{ $productCardStyle }}">
                            {{-- Product Image --}}
                            <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 relative overflow-hidden group">
                                <div class="absolute inset-0 flex items-center justify-center text-6xl text-gray-300">
                                    📦
                                </div>
                                {{-- Discount Badge --}}
                                @if($i % 3 === 0)
                                    <div class="absolute top-2 left-2 store-accent-bg text-white text-xs font-bold px-2 py-1 rounded">
                                        -{{ rand(10, 50) }}%
                                    </div>
                                @endif
                            </div>
                            {{-- Product Info --}}
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-800 line-clamp-2 mb-2">
                                    สินค้าตัวอย่าง {{ $i + 1 }}
                                </h3>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-lg font-bold store-primary-text">
                                        ฿{{ number_format(rand(99, 999)) }}
                                    </span>
                                    @if($i % 2 === 0)
                                        <span class="text-sm text-gray-400 line-through">
                                            ฿{{ number_format(rand(1000, 2000)) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 text-sm text-gray-500">
                                    <span class="text-yellow-400">★</span>
                                    <span>{{ number_format(rand(40, 50) / 10, 1) }}</span>
                                    <span class="text-gray-300">|</span>
                                    <span>ขายแล้ว {{ rand(10, 999) }}</span>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </section>
        @endif

        {{-- Categories Section --}}
        @if($layoutSettings->show_categories)
            <section class="mb-12">
                <h2 class="text-2xl md:text-3xl font-bold store-primary-text mb-6 flex items-center gap-2">
                    <span>📁</span>
                    {{ $layoutSettings->categories_title ?? 'หมวดหมู่สินค้า' }}
                </h2>

                @php
                    $categoriesStyle = $layoutSettings->categories_style ?? 'grid';
                @endphp

                @if($categoriesStyle === 'grid')
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach(['เสื้อผ้า', 'รองเท้า', 'กระเป๋า', 'เครื่องประดับ', 'อิเล็กทรอนิกส์', 'อื่นๆ'] as $cat)
                            <a href="#" class="group bg-white rounded-xl p-4 text-center shadow-md hover:shadow-xl transition border border-gray-100 hover:border-transparent hover:ring-2" style="--tw-ring-color: var(--store-primary)">
                                <div class="w-16 h-16 mx-auto mb-3 rounded-full flex items-center justify-center text-3xl" style="background: linear-gradient(135deg, var(--store-primary)22, var(--store-secondary)22)">
                                    @if($cat === 'เสื้อผ้า') 👕
                                    @elseif($cat === 'รองเท้า') 👟
                                    @elseif($cat === 'กระเป๋า') 👜
                                    @elseif($cat === 'เครื่องประดับ') 💍
                                    @elseif($cat === 'อิเล็กทรอนิกส์') 📱
                                    @else 📦
                                    @endif
                                </div>
                                <span class="font-medium text-gray-700 group-hover:store-primary-text transition">{{ $cat }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-wrap gap-3">
                        @foreach(['เสื้อผ้า', 'รองเท้า', 'กระเป๋า', 'เครื่องประดับ', 'อิเล็กทรอนิกส์', 'อื่นๆ'] as $cat)
                            <a href="#" class="px-4 py-2 bg-white rounded-full shadow hover:shadow-md transition border hover:store-primary-text" style="border-color: var(--store-primary)33">
                                {{ $cat }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- All Products Section --}}
        <section class="mb-12">
            <h2 class="text-2xl md:text-3xl font-bold store-primary-text mb-6 flex items-center gap-2">
                <span>🛍️</span>
                สินค้าทั้งหมด
            </h2>

            <div class="grid {{ $gridCols ?? 'grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4' }} gap-4 md:gap-6">
                @for($i = 0; $i < 12; $i++)
                    <div class="product-card-{{ $productCardStyle ?? 'default' }}">
                        <div class="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 relative overflow-hidden">
                            <div class="absolute inset-0 flex items-center justify-center text-6xl text-gray-300">
                                📦
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-800 line-clamp-2 mb-2">
                                สินค้า {{ $i + 1 }}
                            </h3>
                            <span class="text-lg font-bold store-primary-text">
                                ฿{{ number_format(rand(99, 999)) }}
                            </span>
                        </div>
                    </div>
                @endfor
            </div>
        </section>
    </main>

    {{-- Footer --}}
    @if($layoutSettings->show_footer)
        <footer class="border-t border-gray-200" style="background-color: {{ $layoutSettings->footer_bg_color ?? '#f9fafb' }}">
            <div class="max-w-7xl mx-auto px-4 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    {{-- Store Info --}}
                    <div>
                        <h3 class="font-bold text-xl mb-4 store-primary-text">{{ $store->store_name ?? 'ร้านค้า' }}</h3>
                        <p class="text-gray-600">{{ $store->store_description ?? 'คำอธิบายร้านค้า' }}</p>
                    </div>

                    {{-- Contact Info --}}
                    @if($layoutSettings->show_contact_info)
                        <div>
                            <h3 class="font-bold text-lg mb-4">ติดต่อเรา</h3>
                            <ul class="space-y-2 text-gray-600">
                                @if($store->store_email)
                                    <li class="flex items-center gap-2">
                                        <span>📧</span>
                                        {{ $store->store_email }}
                                    </li>
                                @endif
                                @if($store->store_phone)
                                    <li class="flex items-center gap-2">
                                        <span>📞</span>
                                        {{ $store->store_phone }}
                                    </li>
                                @endif
                                @if($store->store_address)
                                    <li class="flex items-center gap-2">
                                        <span>📍</span>
                                        {{ $store->store_address }}
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    {{-- Social Links --}}
                    @if($layoutSettings->show_social_links && $layoutSettings->social_links)
                        <div>
                            <h3 class="font-bold text-lg mb-4">ติดตามเรา</h3>
                            <div class="flex gap-3">
                                @if(!empty($layoutSettings->social_links['facebook']))
                                    <a href="{{ $layoutSettings->social_links['facebook'] }}" target="_blank"
                                       class="w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center hover:scale-110 transition">
                                        📘
                                    </a>
                                @endif
                                @if(!empty($layoutSettings->social_links['line']))
                                    <a href="https://line.me/ti/p/{{ $layoutSettings->social_links['line'] }}" target="_blank"
                                       class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center hover:scale-110 transition">
                                        💚
                                    </a>
                                @endif
                                @if(!empty($layoutSettings->social_links['instagram']))
                                    <a href="{{ $layoutSettings->social_links['instagram'] }}" target="_blank"
                                       class="w-10 h-10 bg-gradient-to-br from-purple-600 to-pink-500 text-white rounded-full flex items-center justify-center hover:scale-110 transition">
                                        📸
                                    </a>
                                @endif
                                @if(!empty($layoutSettings->social_links['tiktok']))
                                    <a href="{{ $layoutSettings->social_links['tiktok'] }}" target="_blank"
                                       class="w-10 h-10 bg-black text-white rounded-full flex items-center justify-center hover:scale-110 transition">
                                        🎵
                                    </a>
                                @endif
                                @if(!empty($layoutSettings->social_links['youtube']))
                                    <a href="{{ $layoutSettings->social_links['youtube'] }}" target="_blank"
                                       class="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center hover:scale-110 transition">
                                        📺
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Custom Footer Content --}}
                @if($layoutSettings->footer_content)
                    <div class="mt-8 pt-8 border-t border-gray-200 text-gray-600">
                        {!! $layoutSettings->footer_content !!}
                    </div>
                @endif

                {{-- Copyright --}}
                <div class="mt-8 pt-8 border-t border-gray-200 text-center text-gray-500 text-sm">
                    <p>© {{ date('Y') }} {{ $store->store_name ?? 'ร้านค้า' }}. สงวนลิขสิทธิ์.</p>
                    <p class="mt-1">Powered by <span class="store-primary-text font-semibold">TP-Affiliate</span></p>
                </div>
            </div>
        </footer>
    @endif

    {{-- Custom CSS --}}
    @if($layoutSettings->custom_css)
        <style>
            {!! $layoutSettings->custom_css !!}
        </style>
    @endif

    {{-- Swiper JS for Slider --}}
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
</body>
</html>
