@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$columns = $settings['columns'] ?? 3;
$iconColor = $settings['icon_color'] ?? '#667eea';
$features = $content['features'] ?? [];
@endphp

<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        @if(!empty($content['title']))
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold text-gray-900 mb-4">
                {{ $content['title'] }}
            </h2>
            @if(!empty($content['subtitle']))
            <p class="text-xl text-gray-600">
                {{ $content['subtitle'] }}
            </p>
            @endif
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-{{ $columns }} gap-8">
            @foreach($features as $feature)
            <div class="text-center p-6 rounded-lg hover:shadow-xl transition-shadow duration-300">
                @if(!empty($feature['icon']))
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full mb-4"
                     style="background-color: {{ $iconColor }}20;">
                    <svg class="w-8 h-8" style="color: {{ $iconColor }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        @if($feature['icon'] === 'zap')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        @elseif($feature['icon'] === 'shield')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        @elseif($feature['icon'] === 'heart')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        @endif
                    </svg>
                </div>
                @endif

                @if(!empty($feature['title']))
                <h3 class="text-xl font-semibold text-gray-900 mb-3">
                    {{ $feature['title'] }}
                </h3>
                @endif

                @if(!empty($feature['description']))
                <p class="text-gray-600">
                    {{ $feature['description'] }}
                </p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
