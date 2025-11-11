@props(['slider'])

@php
    // Load slider data if ID or alias passed
    if (is_string($slider) || is_numeric($slider)) {
        $slider = is_numeric($slider)
            ? \App\Models\SmartSlider::find($slider)
            : \App\Models\SmartSlider::findByAlias($slider);
    }

    if (!$slider || !$slider->is_published) {
        return;
    }

    $slider->load(['slides' => function($query) {
        $query->active()->ordered()->with('layers');
    }]);

    $settings = $slider->settings ?? [];
    $controls = $slider->controls ?? [];
    $animations = $slider->animations ?? [];

    $sliderId = 'smart-slider-' . $slider->id;
@endphp

@if($slider->slides->count() > 0)
<!-- Smart Slider: {{ $slider->name }} -->
<div class="smart-slider-wrapper" data-slider-id="{{ $slider->id }}" id="{{ $sliderId }}-wrapper">
    <!-- Swiper Container -->
    <div class="swiper {{ $sliderId }}"
         style="width: 100%;
                @if($slider->responsive_mode !== 'fullwidth' && $slider->responsive_mode !== 'fullpage')
                max-width: {{ $slider->width }}px;
                @endif
                height: {{ $slider->height }}px;">

        <!-- Slides Wrapper -->
        <div class="swiper-wrapper">
            @foreach($slider->slides as $slide)
            <div class="swiper-slide" data-slide-id="{{ $slide->id }}">
                <!-- Slide Background -->
                <div class="slide-background" style="
                    position: absolute;
                    inset: 0;
                    @if(!empty($slide->background))
                        @if($slide->background['type'] === 'image' && !empty($slide->background['image']))
                            background-image: url('{{ asset('storage/' . $slide->background['image']) }}');
                            background-size: {{ $slide->background['size'] ?? 'cover' }};
                            background-position: {{ $slide->background['position'] ?? 'center' }};
                        @elseif($slide->background['type'] === 'color')
                            background-color: {{ $slide->background['color'] ?? '#ffffff' }};
                        @elseif($slide->background['type'] === 'gradient')
                            @php
                                $gradient = $slide->background['gradient'];
                                $type = $gradient['type'] ?? 'linear';
                                $angle = $gradient['angle'] ?? 135;
                                $colors = $gradient['colors'] ?? ['#667eea', '#764ba2'];
                            @endphp
                            background: linear-gradient({{ $angle }}deg, {{ implode(', ', $colors) }});
                        @endif
                    @endif
                ">
                    @if(!empty($slide->background) && $slide->background['type'] === 'video')
                        @if(!empty($slide->background['video']))
                        <video autoplay muted loop playsinline style="width: 100%; height: 100%; object-fit: cover;">
                            <source src="{{ asset('storage/' . $slide->background['video']) }}" type="video/mp4">
                        </video>
                        @endif
                    @endif
                </div>

                <!-- Slide Link Overlay (if link exists) -->
                @if($slide->link_url)
                <a href="{{ $slide->link_url }}"
                   target="{{ $slide->link_target }}"
                   class="absolute inset-0 z-10"
                   onclick="trackSliderClick({{ $slider->id }}, {{ $slide->id }})"></a>
                @endif

                <!-- Layers Container -->
                <div class="slide-layers-container" style="position: relative; width: 100%; height: 100%; z-index: 20;">
                    @foreach($slide->layers as $layer)
                        @include('components.smart-slider.layer', ['layer' => $layer])
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <!-- Navigation Arrows -->
        @if($controls['arrows']['enabled'] ?? true)
        <div class="swiper-button-prev" style="color: {{ $controls['arrows']['color'] ?? '#ffffff' }};"></div>
        <div class="swiper-button-next" style="color: {{ $controls['arrows']['color'] ?? '#ffffff' }};"></div>
        @endif

        <!-- Pagination Bullets -->
        @if($controls['bullets']['enabled'] ?? true)
        <div class="swiper-pagination"></div>
        @endif

        <!-- Progress Bar -->
        @if($controls['progress_bar']['enabled'] ?? false)
        <div class="swiper-scrollbar"></div>
        @endif
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<style>
    .smart-slider-wrapper {
        width: 100%;
        margin: 0 auto;
    }

    @if($slider->responsive_mode === 'fullwidth')
    .smart-slider-wrapper {
        max-width: 100% !important;
    }
    @endif

    @if($slider->responsive_mode === 'fullpage')
    .smart-slider-wrapper {
        width: 100vw !important;
        height: 100vh !important;
    }
    .smart-slider-wrapper .swiper {
        height: 100vh !important;
    }
    @endif

    .{{ $sliderId }} .swiper-slide {
        position: relative;
        overflow: hidden;
    }

    /* Navigation Styles */
    .{{ $sliderId }} .swiper-button-prev,
    .{{ $sliderId }} .swiper-button-next {
        @if(($controls['arrows']['position'] ?? 'inside') === 'outside')
        margin-left: -50px;
        margin-right: -50px;
        @endif
    }

    /* Pagination Styles */
    .{{ $sliderId }} .swiper-pagination {
        @if(($controls['bullets']['position'] ?? 'bottom') === 'top')
        top: 10px;
        bottom: auto;
        @endif
    }

    /* Responsive */
    @media (max-width: 768px) {
        .{{ $sliderId }} {
            height: {{ $slider->height * 0.7 }}px !important;
        }
    }

    @media (max-width: 480px) {
        .{{ $sliderId }} {
            height: {{ $slider->height * 0.5 }}px !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const swiper_{{ $sliderId }} = new Swiper('.{{ $sliderId }}', {
        // Slide Animation
        effect: '{{ $animations['slide_animation'] ?? 'slide' }}',

        // Duration
        speed: {{ $settings['animation_duration'] ?? 800 }},

        // Autoplay
        @if($settings['autoplay'] ?? true)
        autoplay: {
            delay: {{ $settings['slide_duration'] ?? 5000 }},
            disableOnInteraction: false,
            pauseOnMouseEnter: {{ ($settings['pause_on_hover'] ?? true) ? 'true' : 'false' }},
        },
        @endif

        // Loop
        loop: {{ ($settings['loop'] ?? true) ? 'true' : 'false' }},

        // Navigation arrows
        @if($controls['arrows']['enabled'] ?? true)
        navigation: {
            nextEl: '.{{ $sliderId }} .swiper-button-next',
            prevEl: '.{{ $sliderId }} .swiper-button-prev',
        },
        @endif

        // Pagination
        @if($controls['bullets']['enabled'] ?? true)
        pagination: {
            el: '.{{ $sliderId }} .swiper-pagination',
            clickable: true,
            dynamicBullets: true,
        },
        @endif

        // Scrollbar
        @if($controls['progress_bar']['enabled'] ?? false)
        scrollbar: {
            el: '.{{ $sliderId }} .swiper-scrollbar',
            draggable: true,
        },
        @endif

        // Keyboard control
        keyboard: {
            enabled: {{ ($settings['keyboard_navigation'] ?? true) ? 'true' : 'false' }},
        },

        // Touch/Swipe
        touchEventsTarget: 'container',
        simulateTouch: {{ ($settings['touch_swipe'] ?? true) ? 'true' : 'false' }},

        // Track slide changes
        on: {
            slideChange: function() {
                const activeSlide = this.slides[this.activeIndex];
                if (activeSlide) {
                    const slideId = activeSlide.getAttribute('data-slide-id');
                    trackSlideChange({{ $slider->id }}, slideId);
                }
            },
            init: function() {
                // Track initial view
                trackSliderView({{ $slider->id }});
            }
        }
    });
});

// Analytics tracking functions
function trackSliderView(sliderId) {
    fetch(`/sliders/${sliderId}/track-view`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    });
}

function trackSliderClick(sliderId, slideId) {
    fetch(`/sliders/${sliderId}/track-click`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ slide_id: slideId })
    });
}

function trackSlideChange(sliderId, slideId) {
    fetch(`/sliders/${sliderId}/track-slide-change`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ slide_id: slideId })
    });
}
</script>
@endpush

@endif
