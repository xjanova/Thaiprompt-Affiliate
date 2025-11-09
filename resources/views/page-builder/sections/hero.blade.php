@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$bgGradientFrom = $settings['gradient_from'] ?? '#667eea';
$bgGradientTo = $settings['gradient_to'] ?? '#764ba2';
$textAlign = $settings['text_align'] ?? 'center';
$paddingY = $settings['padding_y'] ?? 'py-32';
@endphp

<section class="relative overflow-hidden {{ $paddingY }}"
         style="background: linear-gradient(135deg, {{ $bgGradientFrom }} 0%, {{ $bgGradientTo }} 100%);">
    <!-- Animated Background -->
    <div class="absolute inset-0 opacity-20">
        <div class="absolute inset-0 bg-gradient-to-br from-white to-transparent"></div>
    </div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto text-{{ $textAlign }}">
            @if(!empty($content['title']))
            <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
                {{ $content['title'] }}
            </h1>
            @endif

            @if(!empty($content['subtitle']))
            <p class="text-xl md:text-2xl text-white/90 mb-10">
                {{ $content['subtitle'] }}
            </p>
            @endif

            @if(!empty($content['cta_primary_text']) || !empty($content['cta_secondary_text']))
            <div class="flex flex-wrap gap-4 {{ $textAlign === 'center' ? 'justify-center' : ($textAlign === 'right' ? 'justify-end' : 'justify-start') }}">
                @if(!empty($content['cta_primary_text']))
                <a href="{{ $content['cta_primary_url'] ?? '#' }}"
                   class="px-8 py-4 bg-white text-gray-900 rounded-lg font-semibold hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                    {{ $content['cta_primary_text'] }}
                </a>
                @endif

                @if(!empty($content['cta_secondary_text']))
                <a href="{{ $content['cta_secondary_url'] ?? '#' }}"
                   class="px-8 py-4 bg-transparent border-2 border-white text-white rounded-lg font-semibold hover:bg-white hover:text-gray-900 transition-all duration-300">
                    {{ $content['cta_secondary_text'] }}
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</section>
