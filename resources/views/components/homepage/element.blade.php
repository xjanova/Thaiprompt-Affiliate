{{--
/**
 * Homepage Element Component
 *
 * แสดง element จาก Homepage Manager
 * รองรับประเภท: heading, text, image, button, video, icon, html, spacer, container
 *
 * @var \App\Models\HomepageElement $element
 */
--}}

@php
    // สร้าง style string
    $styles = [];

    // ตำแหน่ง
    if ($element->position_type === 'absolute') {
        $styles[] = 'position: absolute';
        $styles[] = "left: {$element->position_x}{$element->position_x_unit}";
        $styles[] = "top: {$element->position_y}{$element->position_y_unit}";
    }

    // ขนาด
    if ($element->width) {
        $styles[] = "width: {$element->width}";
    }
    if ($element->height) {
        $styles[] = "height: {$element->height}";
    }
    if ($element->max_width) {
        $styles[] = "max-width: {$element->max_width}";
    }

    // สี
    $styles[] = "color: {$element->color}";
    if ($element->background_color) {
        $styles[] = "background-color: {$element->background_color}";
    }

    // ตัวอักษร
    if ($element->font_family) {
        $styles[] = "font-family: {$element->font_family}";
    }
    if ($element->font_size) {
        $styles[] = "font-size: {$element->font_size}";
    }
    if ($element->font_weight) {
        $styles[] = "font-weight: {$element->font_weight}";
    }
    if ($element->line_height) {
        $styles[] = "line-height: {$element->line_height}";
    }
    if ($element->letter_spacing) {
        $styles[] = "letter-spacing: {$element->letter_spacing}";
    }
    if ($element->text_align) {
        $styles[] = "text-align: {$element->text_align}";
    }
    if ($element->text_transform) {
        $styles[] = "text-transform: {$element->text_transform}";
    }

    // ขอบและมุม
    if ($element->border_width && $element->border_style) {
        $styles[] = "border: {$element->border_width} {$element->border_style} {$element->border_color}";
    }
    if ($element->border_radius) {
        $styles[] = "border-radius: {$element->border_radius}";
    }

    // Padding และ Margin
    if ($element->padding) {
        $styles[] = "padding: {$element->padding}";
    }
    if ($element->margin) {
        $styles[] = "margin: {$element->margin}";
    }

    // เงา
    if ($element->box_shadow) {
        $styles[] = "box-shadow: {$element->box_shadow}";
    }
    if ($element->text_shadow) {
        $styles[] = "text-shadow: {$element->text_shadow}";
    }

    $styleString = implode('; ', $styles);

    // Animation class
    $animationClass = '';
    if ($element->animation && $element->animation !== 'none') {
        $animationClass = "animate-{$element->animation}";
    }
@endphp

@switch($element->type)
    @case('heading')
        <{{ $element->settings['tag'] ?? 'h2' }}
            class="{{ $animationClass }}"
            style="{{ $styleString }}; animation-delay: {{ $element->animation_delay }}ms;">
            @if($element->link_url)
                <a href="{{ $element->link_url }}" target="{{ $element->link_target }}">
                    {!! $element->content !!}
                </a>
            @else
                {!! $element->content !!}
            @endif
        </{{ $element->settings['tag'] ?? 'h2' }}>
        @break

    @case('text')
        <p class="{{ $animationClass }}"
           style="{{ $styleString }}; animation-delay: {{ $element->animation_delay }}ms;">
            {!! nl2br(e($element->content)) !!}
        </p>
        @break

    @case('image')
        <div class="{{ $animationClass }}"
             style="{{ $styleString }}; animation-delay: {{ $element->animation_delay }}ms;">
            @if($element->link_url)
                <a href="{{ $element->link_url }}" target="{{ $element->link_target }}">
                    <img src="{{ $element->image_url }}"
                         alt="{{ $element->name ?? '' }}"
                         class="w-full h-auto"
                         loading="lazy">
                </a>
            @else
                <img src="{{ $element->image_url }}"
                     alt="{{ $element->name ?? '' }}"
                     class="w-full h-auto"
                     loading="lazy">
            @endif
        </div>
        @break

    @case('button')
        <a href="{{ $element->link_url ?? '#' }}"
           target="{{ $element->link_target }}"
           class="{{ $animationClass }} inline-block px-6 py-3 font-semibold rounded-lg transition-all hover:opacity-90"
           style="{{ $styleString }}; animation-delay: {{ $element->animation_delay }}ms;">
            @if($element->icon_class)
                <i class="{{ $element->icon_class }} mr-2"></i>
            @endif
            {{ $element->content }}
        </a>
        @break

    @case('video')
        <div class="{{ $animationClass }}"
             style="{{ $styleString }}; animation-delay: {{ $element->animation_delay }}ms;">
            @if(str_contains($element->video_url ?? '', 'youtube'))
                <iframe src="{{ $element->video_url }}"
                        class="w-full aspect-video"
                        frameborder="0"
                        allowfullscreen>
                </iframe>
            @else
                <video controls class="w-full">
                    <source src="{{ $element->video_url }}" type="video/mp4">
                </video>
            @endif
        </div>
        @break

    @case('icon')
        <div class="{{ $animationClass }}"
             style="{{ $styleString }}; animation-delay: {{ $element->animation_delay }}ms;">
            <i class="{{ $element->icon_class }}"></i>
        </div>
        @break

    @case('html')
        <div class="{{ $animationClass }}"
             style="{{ $styleString }}; animation-delay: {{ $element->animation_delay }}ms;">
            {!! $element->content !!}
        </div>
        @break

    @case('spacer')
        <div style="{{ $styleString }}"></div>
        @break

    @case('container')
        <div class="{{ $animationClass }}"
             style="{{ $styleString }}; animation-delay: {{ $element->animation_delay }}ms;">
            {!! $element->content !!}
        </div>
        @break

    @default
        {{-- Unknown element type --}}
        @if(config('app.debug'))
            <div class="p-4 bg-yellow-100 text-yellow-800 rounded">
                Unknown element type: {{ $element->type }}
            </div>
        @endif
@endswitch
