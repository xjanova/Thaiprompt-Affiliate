@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$layout = $settings['layout'] ?? 'grid'; // grid, carousel, masonry
$columns = $settings['columns'] ?? 3;
$testimonials = $content['testimonials'] ?? [];
$showRating = $settings['show_rating'] ?? true;
@endphp

<section class="py-20 bg-white">
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

        <div class="grid grid-cols-1 md:grid-cols-{{ $columns }} gap-8 max-w-7xl mx-auto">
            @foreach($testimonials as $testimonial)
            <div class="bg-gradient-to-br from-gray-50 to-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                @if($showRating && !empty($testimonial['rating']))
                <div class="flex gap-1 mb-4">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= ($testimonial['rating'] ?? 5))
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        @else
                        <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        @endif
                    @endfor
                </div>
                @endif

                @if(!empty($testimonial['text']))
                <blockquote class="text-gray-700 mb-6 leading-relaxed italic">
                    "{{ $testimonial['text'] }}"
                </blockquote>
                @endif

                <div class="flex items-center gap-4">
                    @if(!empty($testimonial['avatar']))
                    <img src="{{ $testimonial['avatar'] }}" alt="{{ $testimonial['name'] ?? '' }}"
                         class="w-12 h-12 rounded-full object-cover">
                    @else
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                        {{ substr($testimonial['name'] ?? 'U', 0, 1) }}
                    </div>
                    @endif

                    <div>
                        @if(!empty($testimonial['name']))
                        <div class="font-bold text-gray-900">{{ $testimonial['name'] }}</div>
                        @endif
                        @if(!empty($testimonial['role']))
                        <div class="text-sm text-gray-600">{{ $testimonial['role'] }}</div>
                        @endif
                        @if(!empty($testimonial['company']))
                        <div class="text-sm text-gray-500">{{ $testimonial['company'] }}</div>
                        @endif
                    </div>
                </div>

                @if(!empty($testimonial['verified']))
                <div class="mt-4 flex items-center gap-2 text-sm text-green-600">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="font-medium">ตรวจสอบแล้ว</span>
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
