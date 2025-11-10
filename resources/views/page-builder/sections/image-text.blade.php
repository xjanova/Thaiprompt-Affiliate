@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$imagePosition = $settings['image_position'] ?? 'left'; // left, right
$imageSize = $settings['image_size'] ?? '50'; // percentage
$verticalAlign = $settings['vertical_align'] ?? 'center'; // top, center, bottom
$bgColor = $settings['bg_color'] ?? '#ffffff';
@endphp

<section class="py-20" style="background-color: {{ $bgColor }};">
    <div class="container mx-auto px-4">
        <div class="grid md:grid-cols-2 gap-12 items-{{ $verticalAlign }} max-w-7xl mx-auto">
            <!-- Image -->
            <div class="{{ $imagePosition === 'right' ? 'md:order-2' : 'md:order-1' }}">
                @if(!empty($content['image']))
                <div class="rounded-2xl overflow-hidden shadow-2xl transform hover:scale-105 transition-transform duration-300">
                    <img src="{{ $content['image'] }}" alt="{{ $content['image_alt'] ?? $content['title'] ?? '' }}"
                         class="w-full h-auto">
                </div>
                @endif

                @if(!empty($content['image_caption']))
                <p class="text-sm text-gray-600 text-center mt-4 italic">{{ $content['image_caption'] }}</p>
                @endif
            </div>

            <!-- Text Content -->
            <div class="{{ $imagePosition === 'right' ? 'md:order-1' : 'md:order-2' }}">
                @if(!empty($content['badge']))
                <div class="inline-block mb-4">
                    <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                        {{ $content['badge'] }}
                    </span>
                </div>
                @endif

                @if(!empty($content['title']))
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                    {{ $content['title'] }}
                </h2>
                @endif

                @if(!empty($content['subtitle']))
                <p class="text-xl text-gray-600 mb-6">
                    {{ $content['subtitle'] }}
                </p>
                @endif

                @if(!empty($content['description']))
                <div class="text-gray-700 mb-8 leading-relaxed space-y-4">
                    {!! nl2br(e($content['description'])) !!}
                </div>
                @endif

                @if(!empty($content['features']))
                <ul class="space-y-3 mb-8">
                    @foreach($content['features'] as $feature)
                    <li class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-700">{{ $feature }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif

                @if(!empty($content['cta_text']))
                <div class="flex flex-wrap gap-4">
                    <a href="{{ $content['cta_url'] ?? '#' }}"
                       class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                        {{ $content['cta_text'] }}
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>

                    @if(!empty($content['cta_secondary_text']))
                    <a href="{{ $content['cta_secondary_url'] ?? '#' }}"
                       class="inline-flex items-center gap-2 px-8 py-4 bg-white border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:border-gray-400 hover:shadow-lg transition-all duration-300">
                        {{ $content['cta_secondary_text'] }}
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
