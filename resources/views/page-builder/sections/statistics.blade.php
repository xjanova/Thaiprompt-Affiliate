@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$bgGradientFrom = $settings['gradient_from'] ?? '#667eea';
$bgGradientTo = $settings['gradient_to'] ?? '#764ba2';
$stats = $content['stats'] ?? [];
$columns = $settings['columns'] ?? 4;
@endphp

<section class="py-20"
         style="background: linear-gradient(135deg, {{ $bgGradientFrom }} 0%, {{ $bgGradientTo }} 100%);">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-{{ $columns }} gap-8">
            @foreach($stats as $stat)
            <div class="text-center">
                <div class="text-5xl font-bold text-white mb-2" data-count="{{ $stat['value'] ?? 0 }}">
                    {{ $stat['prefix'] ?? '' }}{{ number_format($stat['value'] ?? 0) }}{{ $stat['suffix'] ?? '' }}
                </div>
                <div class="text-white/80 text-lg">
                    {{ $stat['label'] ?? '' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
