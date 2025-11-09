@php
$settings = $section['settings'] ?? [];
$content = $content['content'] ?? [];
$layout = $settings['layout'] ?? 'grid'; // grid, masonry, carousel
$columns = $settings['columns'] ?? 3;
$gap = $settings['gap'] ?? '4';
$images = $content['images'] ?? [];
$enableLightbox = $settings['enable_lightbox'] ?? true;
@endphp

<section class="py-20 bg-gray-50">
    <div class="container mx-auto px-4">
        @if(!empty($content['title']))
        <div class="text-center mb-16">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                {{ $content['title'] }}
            </h2>
            @if(!empty($content['subtitle']))
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                {{ $content['subtitle'] }}
            </p>
            @endif
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-{{ $columns }} gap-{{ $gap }} max-w-7xl mx-auto">
            @foreach($images as $index => $image)
            <div class="group relative overflow-hidden rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 {{ $layout === 'masonry' && $index % 3 === 0 ? 'md:row-span-2' : '' }}">
                <div class="relative overflow-hidden">
                    <img src="{{ $image['url'] ?? '' }}"
                         alt="{{ $image['alt'] ?? $image['title'] ?? 'Gallery image ' . ($index + 1) }}"
                         class="w-full h-auto object-cover transform group-hover:scale-110 transition-transform duration-500">

                    <!-- Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        <div class="absolute bottom-0 left-0 right-0 p-6 text-white transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300">
                            @if(!empty($image['title']))
                            <h3 class="text-xl font-bold mb-2">{{ $image['title'] }}</h3>
                            @endif
                            @if(!empty($image['description']))
                            <p class="text-sm text-white/90">{{ $image['description'] }}</p>
                            @endif
                        </div>

                        @if($enableLightbox)
                        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                            <button onclick="openLightbox({{ $index }})"
                                    class="p-4 bg-white/20 backdrop-blur-md rounded-full hover:bg-white/30 transition-colors">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                </svg>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                @if(!empty($image['category']))
                <div class="absolute top-4 left-4">
                    <span class="px-3 py-1 bg-white/90 backdrop-blur-sm text-gray-900 text-xs font-semibold rounded-full">
                        {{ $image['category'] }}
                    </span>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        @if(!empty($content['cta_text']))
        <div class="text-center mt-12">
            <a href="{{ $content['cta_url'] ?? '#' }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                {{ $content['cta_text'] }}
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                </svg>
            </a>
        </div>
        @endif
    </div>
</section>

@if($enableLightbox)
<script>
function openLightbox(index) {
    // Lightbox implementation can be added here
    // Using libraries like GLightbox, Fancybox, or custom implementation
    console.log('Open lightbox for image:', index);
}
</script>
@endif
