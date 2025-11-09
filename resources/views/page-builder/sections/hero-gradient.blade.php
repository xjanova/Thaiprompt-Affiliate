@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$bgGradientFrom = $settings['gradient_from'] ?? '#667eea';
$bgGradientTo = $settings['gradient_to'] ?? '#764ba2';
$gradientDirection = $settings['gradient_direction'] ?? '135deg';
$textAlign = $settings['text_align'] ?? 'center';
$paddingY = $settings['padding_y'] ?? 'py-32';
$hasAnimation = $settings['has_animation'] ?? true;
@endphp

<section class="relative overflow-hidden {{ $paddingY }}"
         style="background: linear-gradient({{ $gradientDirection }}, {{ $bgGradientFrom }} 0%, {{ $bgGradientTo }} 100%);">

    @if($hasAnimation)
    <!-- Animated Background Gradients -->
    <div class="absolute inset-0 overflow-hidden opacity-30">
        <div class="absolute w-96 h-96 bg-gradient-to-r from-white to-transparent rounded-full -top-20 -left-20 animate-pulse blur-3xl"></div>
        <div class="absolute w-80 h-80 bg-gradient-to-r from-white to-transparent rounded-full top-40 right-20 animate-pulse blur-3xl" style="animation-delay: 1s;"></div>
        <div class="absolute w-64 h-64 bg-gradient-to-r from-white to-transparent rounded-full bottom-20 left-1/3 animate-pulse blur-3xl" style="animation-delay: 2s;"></div>
    </div>

    <!-- Grid Pattern Overlay -->
    <div class="absolute inset-0 opacity-10"
         style="background-image: linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px); background-size: 50px 50px;">
    </div>
    @endif

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto text-{{ $textAlign }}">
            @if(!empty($content['badge_text']))
            <div class="inline-block mb-6">
                <span class="px-4 py-2 bg-white/20 backdrop-blur-sm text-white rounded-full text-sm font-semibold border border-white/30">
                    {{ $content['badge_text'] }}
                </span>
            </div>
            @endif

            @if(!empty($content['title']))
            <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 leading-tight">
                {{ $content['title'] }}
            </h1>
            @endif

            @if(!empty($content['subtitle']))
            <p class="text-xl md:text-2xl text-white/90 mb-10 leading-relaxed">
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
                   class="px-8 py-4 bg-white/10 backdrop-blur-sm border-2 border-white/30 text-white rounded-xl font-semibold hover:bg-white/20 hover:border-white/50 transition-all duration-300">
                    {{ $content['cta_secondary_text'] }}
                </a>
                @endif
            </div>
            @endif

            @if(!empty($content['trust_badges']))
            <div class="mt-12 flex flex-wrap gap-6 {{ $textAlign === 'center' ? 'justify-center' : ($textAlign === 'right' ? 'justify-end' : 'justify-start') }}">
                @foreach($content['trust_badges'] as $badge)
                <div class="flex items-center gap-2 text-white/90">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-sm font-medium">{{ $badge }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</section>
