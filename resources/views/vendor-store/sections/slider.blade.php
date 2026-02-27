{{-- Section: Banner Slider --}}
@if($layoutSettings->slider_enabled && !empty($layoutSettings->slider_images))
    <section class="relative -mt-6 z-20">
        <div class="{{ $layoutSettings->layout_classes['container'] }}">
            <div class="{{ $layoutSettings->layout_classes['border_radius'] }} overflow-hidden shadow-2xl">
                <div class="swiper-container" id="store-slider">
                    <div class="swiper-wrapper">
                        @foreach($layoutSettings->slider_images as $slide)
                            @if(!empty($slide['image']))
                                <div class="swiper-slide">
                                    <a href="{{ $slide['link'] ?? '#' }}" class="block relative">
                                        <img src="{{ str_starts_with($slide['image'], 'http') ? $slide['image'] : Storage::url($slide['image']) }}"
                                             alt="{{ $slide['title'] ?? 'Banner' }}"
                                             class="w-full h-[250px] md:h-[400px] object-cover">
                                        @if(!empty($slide['title']))
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/30">
                                                <div class="text-center text-white px-4">
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
            </div>
        </div>
    </section>
@endif
