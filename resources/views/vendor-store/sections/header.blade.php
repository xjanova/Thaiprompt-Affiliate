{{-- Section: Store Header --}}
{{-- ใช้ร่วมกันระหว่าง storefront + preview --}}
@php
    $headerStyle = $layoutSettings->header_style ?? 'gradient';
    $headerHeight = $layoutSettings->header_height ?? 200;
    $lc = $layoutSettings->layout_classes;
    $isPreview = $isPreview ?? false;
@endphp

<header class="relative overflow-hidden"
        style="min-height: {{ $headerHeight }}px;
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
    {{-- Overlay สำหรับ image header --}}
    @if($headerStyle === 'image' && $layoutSettings->header_image)
        <div class="absolute inset-0 bg-black/40"></div>
    @endif

    {{-- Background Pattern สำหรับ gradient/solid --}}
    @if($headerStyle !== 'image' && $headerStyle !== 'transparent')
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>
    @endif

    <div class="{{ $lc['container'] }} py-12 md:py-16 relative z-10">
        <div class="max-w-5xl mx-auto">
            <div class="flex flex-col md:flex-row items-center gap-8 text-white">
                {{-- Store Logo --}}
                @if($layoutSettings->show_store_logo)
                    @if($store->store_logo)
                        <div class="{{ $lc['logo_size'] }} bg-white/20 {{ $lc['backdrop_blur'] }} {{ $lc['logo_radius'] }} shadow-2xl p-3 border-4 border-white/30 flex-shrink-0">
                            <img src="{{ $store->logo_url }}" alt="{{ $store->store_name }}" class="w-full h-full object-contain">
                        </div>
                    @else
                        <div class="{{ $lc['logo_size'] }} bg-white/20 {{ $lc['backdrop_blur'] }} {{ $lc['logo_radius'] }} shadow-2xl flex items-center justify-center text-5xl md:text-6xl border-4 border-white/30 flex-shrink-0">
                            🏪
                        </div>
                    @endif
                @endif

                {{-- Store Info --}}
                <div class="flex-1 text-center md:text-left">
                    @if($store->is_verified ?? false)
                    <div class="inline-flex items-center gap-2 {{ $lc['badge'] }} text-white mb-4">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-bold">ร้านค้ายืนยันตัวตน</span>
                    </div>
                    @endif

                    @if($layoutSettings->show_store_name)
                        <h1 class="{{ $lc['header_text'] }} mb-3 {{ $headerStyle === 'transparent' ? 'text-gray-900 dark:text-white' : '' }}">
                            {{ $store->store_name }}
                        </h1>
                    @endif

                    @if($layoutSettings->show_store_description && $store->store_description)
                        <p class="text-lg md:text-xl {{ $headerStyle === 'transparent' ? 'text-gray-600 dark:text-gray-300' : 'text-white/90' }} mb-4">
                            {{ Str::limit($store->store_description, 150) }}
                        </p>
                    @endif

                    {{-- Stats --}}
                    @if($layoutSettings->show_store_stats)
                        <div class="flex flex-wrap gap-3 justify-center md:justify-start">
                            <div class="flex items-center gap-2 {{ $lc['stats_bg'] }}">
                                <span>📦</span>
                                <span class="font-bold">{{ $stats['total_products'] ?? 0 }} สินค้า</span>
                            </div>
                            <div class="flex items-center gap-2 {{ $lc['stats_bg'] }}">
                                <span>🛒</span>
                                <span class="font-bold">{{ $stats['total_sales'] ?? 0 }} ยอดขาย</span>
                            </div>
                            @if(($stats['rating_count'] ?? 0) > 0)
                                <div class="flex items-center gap-2 {{ $lc['stats_bg'] }}">
                                    <span class="text-yellow-300">⭐</span>
                                    <span class="font-bold">{{ number_format($stats['rating'] ?? 0, 1) }} ({{ $stats['rating_count'] }})</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Social Links --}}
                    @if($layoutSettings->show_social_links && $layoutSettings->social_links)
                        <div class="flex gap-2 mt-4 justify-center md:justify-start">
                            @foreach(['facebook' => '📘', 'line' => '💚', 'instagram' => '📷', 'tiktok' => '🎵', 'youtube' => '📺'] as $platform => $icon)
                                @if(!empty($layoutSettings->social_links[$platform]))
                                    <a href="{{ $platform === 'line' ? 'https://line.me/R/ti/p/' . ltrim($layoutSettings->social_links[$platform], '@') : $layoutSettings->social_links[$platform] }}"
                                       target="_blank"
                                       class="w-10 h-10 bg-white/20 hover:bg-white/30 {{ $lc['backdrop_blur'] }} {{ $lc['border_radius'] }} flex items-center justify-center transition-all transform hover:scale-110 border border-white/30">
                                        <span class="text-xl">{{ $icon }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Wave Divider --}}
    @if($lc['wave_divider'])
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                <path d="M0 48h1440V24C1440 24 1200 0 720 0S0 24 0 24v24z" fill="white" fill-opacity="0.9"/>
            </svg>
        </div>
    @endif
</header>
