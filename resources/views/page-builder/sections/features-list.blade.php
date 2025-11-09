@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$layout = $settings['layout'] ?? 'two-column'; // two-column, single-column
$iconStyle = $settings['icon_style'] ?? 'gradient'; // gradient, solid, outline
$features = $content['features'] ?? [];
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

        <div class="{{ $layout === 'single-column' ? 'max-w-4xl mx-auto' : 'max-w-6xl mx-auto' }}">
            @foreach($features as $index => $feature)
            <div class="mb-8 {{ $layout === 'two-column' ? 'grid md:grid-cols-2 gap-8 items-start' : '' }}">
                @if($layout === 'two-column' && $index % 2 === 1)
                <!-- Image on right -->
                <div class="order-2">
                @else
                <div class="{{ $layout === 'two-column' ? 'order-1' : '' }}">
                @endif
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition-shadow duration-300">
                        <div class="flex items-start gap-4">
                            @if(!empty($feature['icon']))
                            <div class="flex-shrink-0">
                                @if($iconStyle === 'gradient')
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white text-2xl">
                                    {{ $feature['icon'] }}
                                </div>
                                @elseif($iconStyle === 'solid')
                                <div class="w-12 h-12 rounded-xl bg-blue-600 flex items-center justify-center text-white text-2xl">
                                    {{ $feature['icon'] }}
                                </div>
                                @else
                                <div class="w-12 h-12 rounded-xl border-2 border-blue-600 flex items-center justify-center text-blue-600 text-2xl">
                                    {{ $feature['icon'] }}
                                </div>
                                @endif
                            </div>
                            @endif

                            <div class="flex-1">
                                @if(!empty($feature['title']))
                                <h3 class="text-2xl font-bold text-gray-900 mb-3">
                                    {{ $feature['title'] }}
                                </h3>
                                @endif

                                @if(!empty($feature['description']))
                                <p class="text-gray-600 mb-4 leading-relaxed">
                                    {{ $feature['description'] }}
                                </p>
                                @endif

                                @if(!empty($feature['items']))
                                <ul class="space-y-2">
                                    @foreach($feature['items'] as $item)
                                    <li class="flex items-start gap-2">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-gray-700">{{ $item }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                                @endif

                                @if(!empty($feature['link_url']))
                                <a href="{{ $feature['link_url'] }}" class="inline-flex items-center gap-2 mt-4 text-blue-600 font-semibold hover:text-blue-700 transition-colors">
                                    {{ $feature['link_text'] ?? 'เรียนรู้เพิ่มเติม' }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if($layout === 'two-column' && !empty($feature['image']))
                <div class="{{ $index % 2 === 1 ? 'order-1' : 'order-2' }}">
                    <div class="rounded-2xl overflow-hidden shadow-lg">
                        <img src="{{ $feature['image'] }}" alt="{{ $feature['title'] ?? '' }}" class="w-full h-auto">
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
