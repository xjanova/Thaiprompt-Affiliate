@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$videoUrl = $content['video_url'] ?? '';
$videoType = $settings['video_type'] ?? 'mp4'; // mp4, youtube, vimeo
$overlayOpacity = $settings['overlay_opacity'] ?? '0.5';
$overlayColor = $settings['overlay_color'] ?? '#000000';
$textAlign = $settings['text_align'] ?? 'center';
$paddingY = $settings['padding_y'] ?? 'py-32';
@endphp

<section class="relative overflow-hidden {{ $paddingY }} min-h-screen flex items-center">
    <!-- Video Background -->
    <div class="absolute inset-0 z-0">
        @if($videoType === 'youtube' && !empty($videoUrl))
            <iframe
                class="absolute top-1/2 left-1/2 min-w-full min-h-full w-auto h-auto transform -translate-x-1/2 -translate-y-1/2"
                src="https://www.youtube.com/embed/{{ $videoUrl }}?autoplay=1&mute=1&loop=1&playlist={{ $videoUrl }}&controls=0&showinfo=0&rel=0&modestbranding=1"
                frameborder="0"
                allow="autoplay; encrypted-media"
                allowfullscreen>
            </iframe>
        @elseif($videoType === 'vimeo' && !empty($videoUrl))
            <iframe
                class="absolute top-1/2 left-1/2 min-w-full min-h-full w-auto h-auto transform -translate-x-1/2 -translate-y-1/2"
                src="https://player.vimeo.com/video/{{ $videoUrl }}?autoplay=1&loop=1&muted=1&background=1"
                frameborder="0"
                allow="autoplay; fullscreen"
                allowfullscreen>
            </iframe>
        @elseif(!empty($videoUrl))
            <video
                class="absolute top-1/2 left-1/2 min-w-full min-h-full w-auto h-auto object-cover transform -translate-x-1/2 -translate-y-1/2"
                autoplay
                loop
                muted
                playsinline>
                <source src="{{ $videoUrl }}" type="video/mp4">
            </video>
        @endif

        <!-- Overlay -->
        <div class="absolute inset-0" style="background-color: {{ $overlayColor }}; opacity: {{ $overlayOpacity }};"></div>
    </div>

    <!-- Content -->
    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto text-{{ $textAlign }}">
            @if(!empty($content['title']))
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 leading-tight drop-shadow-2xl">
                {{ $content['title'] }}
            </h1>
            @endif

            @if(!empty($content['subtitle']))
            <p class="text-xl md:text-2xl text-white/95 mb-10 leading-relaxed drop-shadow-lg">
                {{ $content['subtitle'] }}
            </p>
            @endif

            @if(!empty($content['cta_primary_text']) || !empty($content['cta_secondary_text']))
            <div class="flex flex-wrap gap-4 {{ $textAlign === 'center' ? 'justify-center' : ($textAlign === 'right' ? 'justify-end' : 'justify-start') }}">
                @if(!empty($content['cta_primary_text']))
                <a href="{{ $content['cta_primary_url'] ?? '#' }}"
                   class="group px-8 py-4 bg-white text-gray-900 rounded-xl font-semibold hover:shadow-2xl transform hover:scale-105 transition-all duration-300 flex items-center gap-2">
                    {{ $content['cta_primary_text'] }}
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
                @endif

                @if(!empty($content['cta_secondary_text']))
                <a href="{{ $content['cta_secondary_url'] ?? '#' }}"
                   class="px-8 py-4 bg-white/10 backdrop-blur-md border-2 border-white text-white rounded-xl font-semibold hover:bg-white/20 transition-all duration-300">
                    {{ $content['cta_secondary_text'] }}
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- Scroll Indicator -->
    @if($settings['show_scroll_indicator'] ?? true)
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-10 animate-bounce">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    </div>
    @endif
</section>
