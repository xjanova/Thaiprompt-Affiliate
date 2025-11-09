@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$bgGradientFrom = $settings['gradient_from'] ?? '#667eea';
$bgGradientTo = $settings['gradient_to'] ?? '#764ba2';
$textAlign = $settings['text_align'] ?? 'center';
$paddingY = $settings['padding_y'] ?? 'py-20';
@endphp

<section class="{{ $paddingY }}"
         style="background: linear-gradient(135deg, {{ $bgGradientFrom }} 0%, {{ $bgGradientTo }} 100%);">
    <div class="container mx-auto px-4">
        <div class="max-w-3xl mx-auto text-{{ $textAlign }}">
            @if(!empty($content['title']))
            <h2 class="text-4xl font-bold text-white mb-6">
                {{ $content['title'] }}
            </h2>
            @endif

            @if(!empty($content['description']))
            <p class="text-xl text-white/90 mb-8">
                {{ $content['description'] }}
            </p>
            @endif

            @if(!empty($content['cta_text']))
            <a href="{{ $content['cta_url'] ?? '#' }}"
               class="inline-block px-8 py-4 bg-white text-gray-900 rounded-lg font-semibold text-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                {{ $content['cta_text'] }}
            </a>
            @endif
        </div>
    </div>
</section>
