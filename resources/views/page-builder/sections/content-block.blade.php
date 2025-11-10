@php
$settings = $section['settings'] ?? [];
$content = $section['content'] ?? [];
$bgColor = $settings['background_color'] ?? '#ffffff';
$paddingY = $settings['padding_y'] ?? 'py-16';
@endphp

<section class="{{ $paddingY }}" style="background-color: {{ $bgColor }};">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            @if(!empty($content['title']))
            <h2 class="text-3xl font-bold text-gray-900 mb-6">
                {{ $content['title'] }}
            </h2>
            @endif

            @if(!empty($content['content']))
            <div class="prose prose-lg max-w-none">
                {!! nl2br(e($content['content'])) !!}
            </div>
            @endif
        </div>
    </div>
</section>
