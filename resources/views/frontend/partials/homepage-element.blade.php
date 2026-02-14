{{--
/**
 * Homepage Element Renderer
 *
 * Render individual element ตามประเภท
 *
 * @var \App\Models\HomepageElement $element
 */
--}}

@php
    // สร้าง style สำหรับ element
    $elementStyle = collect([
        $element->font_size ? "font-size: {$element->font_size}" : null,
        $element->font_weight ? "font-weight: {$element->font_weight}" : null,
        $element->text_align ? "text-align: {$element->text_align}" : null,
        $element->color ? "color: {$element->color}" : null,
        $element->background_color ? "background-color: {$element->background_color}" : null,
        $element->padding ? "padding: {$element->padding}" : null,
        $element->margin ? "margin: {$element->margin}" : null,
        $element->border_radius ? "border-radius: {$element->border_radius}" : null,
        $element->max_width ? "max-width: {$element->max_width}" : null,
    ])->filter()->implode('; ');

    // Animation class
    $animationClass = $element->animation ? "animate-{$element->animation}" : '';
    $animationDelay = $element->animation_delay ? "animation-delay: {$element->animation_delay}ms" : '';
@endphp

<div
    class="homepage-element {{ $animationClass }}"
    style="{{ $elementStyle }}; {{ $animationDelay }}"
    @if($element->animation)
        x-data="{ shown: false }"
        x-intersect:enter="shown = true"
        x-init="setTimeout(() => { if (!shown) shown = true }, 800)"
        x-bind:class="shown ? '{{ $animationClass }}' : 'opacity-0'"
    @endif
>
    @switch($element->type)
        @case('heading')
            @php
                $headingLevel = $element->settings['heading_level'] ?? 'h2';
            @endphp
            <{{ $headingLevel }} class="homepage-heading">
                {!! $element->content !!}
            </{{ $headingLevel }}>
            @break

        @case('text')
            <p class="homepage-text">
                {!! nl2br(e($element->content)) !!}
            </p>
            @break

        @case('button')
            <a href="{{ $element->link_url ?? '#' }}"
               target="{{ $element->link_target ?? '_self' }}"
               class="inline-block homepage-button hover:opacity-90 transition-all transform hover:-translate-y-0.5"
               style="{{ $elementStyle }}">
                {!! $element->content !!}
            </a>
            @break

        @case('image')
            <img src="{{ $element->content }}"
                 alt="{{ $element->settings['alt'] ?? '' }}"
                 class="homepage-image max-w-full h-auto rounded-lg"
                 @if($element->settings['width'] ?? false) width="{{ $element->settings['width'] }}" @endif
                 @if($element->settings['height'] ?? false) height="{{ $element->settings['height'] }}" @endif
            >
            @break

        @case('video')
            @if(str_contains($element->content, 'youtube') || str_contains($element->content, 'youtu.be'))
                @php
                    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $element->content, $matches);
                    $videoId = $matches[1] ?? '';
                @endphp
                <div class="aspect-video rounded-xl overflow-hidden shadow-lg">
                    <iframe src="https://www.youtube.com/embed/{{ $videoId }}"
                            class="w-full h-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            @else
                <video controls class="w-full rounded-xl shadow-lg">
                    <source src="{{ $element->content }}" type="video/mp4">
                </video>
            @endif
            @break

        @case('icon')
            <i class="{{ $element->content ?? 'fas fa-star' }} text-4xl"></i>
            @break

        @case('divider')
            <hr class="border-t border-gray-300 dark:border-gray-600 my-8" style="width: {{ $element->settings['width'] ?? '100%' }}">
            @break

        @case('spacer')
            <div style="height: {{ $element->settings['height'] ?? '40px' }}"></div>
            @break

        @case('container')
        @case('html')
            <div class="homepage-container">
                {!! $element->content !!}
            </div>
            @break

        @case('countdown')
            <div x-data="countdownTimer('{{ $element->settings['target_date'] ?? now()->addDays(7)->toISOString() }}')"
                 class="flex items-center justify-center gap-4">
                <div class="text-center">
                    <div class="text-4xl font-bold" x-text="days">00</div>
                    <div class="text-sm text-gray-500">วัน</div>
                </div>
                <span class="text-2xl">:</span>
                <div class="text-center">
                    <div class="text-4xl font-bold" x-text="hours">00</div>
                    <div class="text-sm text-gray-500">ชั่วโมง</div>
                </div>
                <span class="text-2xl">:</span>
                <div class="text-center">
                    <div class="text-4xl font-bold" x-text="minutes">00</div>
                    <div class="text-sm text-gray-500">นาที</div>
                </div>
                <span class="text-2xl">:</span>
                <div class="text-center">
                    <div class="text-4xl font-bold" x-text="seconds">00</div>
                    <div class="text-sm text-gray-500">วินาที</div>
                </div>
            </div>
            @break

        @case('testimonial')
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                <div class="flex items-center mb-4">
                    <img src="{{ $element->settings['avatar'] ?? 'https://ui-avatars.com/api/?name=User' }}"
                         class="w-12 h-12 rounded-full mr-4" alt="Avatar">
                    <div>
                        <div class="font-semibold">{{ $element->settings['name'] ?? 'ลูกค้า' }}</div>
                        <div class="text-sm text-gray-500">{{ $element->settings['title'] ?? '' }}</div>
                    </div>
                </div>
                <p class="text-gray-600 dark:text-gray-300 italic">"{{ $element->content }}"</p>
                @if($element->settings['rating'] ?? false)
                    <div class="mt-3 flex text-yellow-400">
                        @for($i = 0; $i < ($element->settings['rating'] ?? 5); $i++)
                            <i class="fas fa-star"></i>
                        @endfor
                    </div>
                @endif
            </div>
            @break

        @case('pricing')
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl border-2 {{ $element->settings['featured'] ? 'border-indigo-500' : 'border-gray-200 dark:border-gray-700' }}">
                @if($element->settings['featured'] ?? false)
                    <div class="text-center mb-4">
                        <span class="px-3 py-1 bg-indigo-500 text-white text-sm rounded-full">แนะนำ</span>
                    </div>
                @endif
                <h3 class="text-2xl font-bold text-center mb-2">{{ $element->settings['plan_name'] ?? 'Plan' }}</h3>
                <div class="text-center mb-6">
                    <span class="text-4xl font-bold">฿{{ number_format($element->settings['price'] ?? 0) }}</span>
                    <span class="text-gray-500">/{{ $element->settings['period'] ?? 'เดือน' }}</span>
                </div>
                <ul class="space-y-3 mb-8">
                    @foreach(($element->settings['features'] ?? []) as $feature)
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-2"></i>
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
                <a href="{{ $element->link_url ?? '#' }}" class="block w-full py-3 text-center bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
                    {{ $element->settings['button_text'] ?? 'เลือกแพ็คเกจ' }}
                </a>
            </div>
            @break

        @case('faq')
            <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                <button @click="open = !open" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <span class="font-semibold">{{ $element->settings['question'] ?? $element->content }}</span>
                    <i class="fas fa-chevron-down transition-transform" :class="open && 'rotate-180'"></i>
                </button>
                <div x-show="open" x-collapse class="px-4 pb-4 text-gray-600 dark:text-gray-300">
                    {{ $element->settings['answer'] ?? '' }}
                </div>
            </div>
            @break

        @case('social_links')
            <div class="flex items-center justify-center gap-4">
                @foreach(($element->settings['links'] ?? []) as $social)
                    <a href="{{ $social['url'] ?? '#' }}" target="_blank"
                       class="w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-indigo-500 hover:text-white transition">
                        <i class="fab fa-{{ $social['platform'] ?? 'link' }}"></i>
                    </a>
                @endforeach
            </div>
            @break

        @default
            {{-- Fallback: render content as HTML --}}
            <div class="homepage-element-default">
                {!! $element->content !!}
            </div>
    @endswitch
</div>
