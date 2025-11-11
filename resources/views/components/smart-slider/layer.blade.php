@props(['layer'])

@php
    $position = $layer->position ?? [];
    $style = $layer->style ?? [];
    $responsive = $layer->responsive ?? [];
    $animation = $layer->animation ?? [];
    $mode = $position['mode'] ?? 'default';

    // Build inline styles
    $inlineStyles = [];

    if ($mode === 'absolute') {
        $inlineStyles[] = 'position: absolute';
        $inlineStyles[] = 'left: ' . ($position['x'] ?? 0) . 'px';
        $inlineStyles[] = 'top: ' . ($position['y'] ?? 0) . 'px';
        $inlineStyles[] = 'z-index: ' . ($position['z_index'] ?? 1);

        if (!empty($position['width'])) {
            $inlineStyles[] = 'width: ' . $position['width'] . (is_numeric($position['width']) ? 'px' : '');
        }
        if (!empty($position['height'])) {
            $inlineStyles[] = 'height: ' . $position['height'] . (is_numeric($position['height']) ? 'px' : '');
        }
    }

    // Typography
    if (!empty($style['font_family'])) {
        $inlineStyles[] = 'font-family: ' . $style['font_family'];
    }
    if (!empty($style['font_size'])) {
        $inlineStyles[] = 'font-size: ' . $style['font_size'] . 'px';
    }
    if (!empty($style['font_weight'])) {
        $inlineStyles[] = 'font-weight: ' . $style['font_weight'];
    }
    if (!empty($style['line_height'])) {
        $inlineStyles[] = 'line-height: ' . $style['line_height'];
    }
    if (!empty($style['color'])) {
        $inlineStyles[] = 'color: ' . $style['color'];
    }

    // Background
    if (!empty($style['background_color']) && $style['background_color'] !== 'transparent') {
        $inlineStyles[] = 'background-color: ' . $style['background_color'];
    }

    // Padding
    if (!empty($style['padding'])) {
        $padding = is_array($style['padding']) ? implode('px ', $style['padding']) . 'px' : $style['padding'];
        $inlineStyles[] = 'padding: ' . $padding;
    }

    // Margin
    if (!empty($style['margin'])) {
        $margin = is_array($style['margin']) ? implode('px ', $style['margin']) . 'px' : $style['margin'];
        $inlineStyles[] = 'margin: ' . $margin;
    }

    // Border
    if (!empty($style['border']['width']) && $style['border']['width'] > 0) {
        $inlineStyles[] = 'border: ' . $style['border']['width'] . 'px ' .
                         ($style['border']['style'] ?? 'solid') . ' ' .
                         ($style['border']['color'] ?? '#000000');
    }
    if (!empty($style['border']['radius'])) {
        $inlineStyles[] = 'border-radius: ' . $style['border']['radius'] . 'px';
    }

    // Box Shadow
    if (!empty($style['shadow']['blur']) && $style['shadow']['blur'] > 0) {
        $inlineStyles[] = 'box-shadow: ' .
                         ($style['shadow']['x'] ?? 0) . 'px ' .
                         ($style['shadow']['y'] ?? 0) . 'px ' .
                         ($style['shadow']['blur'] ?? 0) . 'px ' .
                         ($style['shadow']['color'] ?? 'rgba(0,0,0,0.2)');
    }

    $styleAttr = implode('; ', $inlineStyles);

    // Animation classes
    $animationClass = '';
    if (!empty($animation['animation_in'])) {
        $animationClass = 'animate__animated animate__' . $animation['animation_in'];
        if (!empty($animation['delay'])) {
            $styleAttr .= '; animation-delay: ' . $animation['delay'] . 'ms';
        }
        if (!empty($animation['duration'])) {
            $styleAttr .= '; animation-duration: ' . $animation['duration'] . 'ms';
        }
    }

    // Responsive visibility classes
    $responsiveClasses = [];
    if (!empty($responsive['desktop']['visible']) && !$responsive['desktop']['visible']) {
        $responsiveClasses[] = 'hidden lg:block';
    }
    if (!empty($responsive['tablet']['visible']) && !$responsive['tablet']['visible']) {
        $responsiveClasses[] = 'hidden md:hidden lg:block';
    }
    if (!empty($responsive['mobile']['visible']) && !$responsive['mobile']['visible']) {
        $responsiveClasses[] = 'hidden md:block';
    }

    $allClasses = trim($animationClass . ' ' . implode(' ', $responsiveClasses));
@endphp

<div class="smart-slide-layer layer-{{ $layer->type }} {{ $allClasses }}"
     data-layer-id="{{ $layer->id }}"
     style="{{ $styleAttr }}">

    @if($layer->link_url)
    <a href="{{ $layer->link_url }}" target="{{ $layer->link_target }}" class="block">
    @endif

    @switch($layer->type)
        @case('heading')
            <h2>{!! nl2br(e($layer->content)) !!}</h2>
            @break

        @case('text')
            <p>{!! nl2br(e($layer->content)) !!}</p>
            @break

        @case('image')
            <img src="{{ $layer->getContentUrl() }}"
                 alt="{{ $layer->content ?? '' }}"
                 style="@if(!empty($style['width'])) width: {{ $style['width'] }}{{ is_numeric($style['width']) ? 'px' : '' }}; @endif
                        @if(!empty($style['height'])) height: {{ $style['height'] }}{{ is_numeric($style['height']) ? 'px' : '' }}; @endif
                        object-fit: {{ $style['object_fit'] ?? 'cover' }};">
            @break

        @case('button')
            <button class="inline-block transition-all duration-300 hover:opacity-90">
                {{ $layer->content }}
            </button>
            @break

        @case('video_youtube')
            @php
                $videoId = '';
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\?]+)/', $layer->content, $matches)) {
                    $videoId = $matches[1];
                }
            @endphp
            @if($videoId)
            <div class="video-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                <iframe src="https://www.youtube.com/embed/{{ $videoId }}"
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen></iframe>
            </div>
            @endif
            @break

        @case('video_vimeo')
            @php
                $videoId = '';
                if (preg_match('/vimeo\.com\/(\d+)/', $layer->content, $matches)) {
                    $videoId = $matches[1];
                }
            @endphp
            @if($videoId)
            <div class="video-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                <iframe src="https://player.vimeo.com/video/{{ $videoId }}"
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
                        frameborder="0"
                        allow="autoplay; fullscreen; picture-in-picture"
                        allowfullscreen></iframe>
            </div>
            @endif
            @break

        @case('video_upload')
            <video controls style="width: 100%; height: auto;">
                <source src="{{ $layer->getContentUrl() }}" type="video/mp4">
            </video>
            @break

        @case('html')
            {!! $layer->content !!}
            @break
    @endswitch

    @if($layer->link_url)
    </a>
    @endif

    {{-- Render child layers --}}
    @if($layer->children->count() > 0)
        <div class="layer-children">
            @foreach($layer->children as $childLayer)
                @include('components.smart-slider.layer', ['layer' => $childLayer])
            @endforeach
        </div>
    @endif
</div>

@once
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
@endpush
@endonce
