{{-- Emergency Alert Marquee Component (Scrolling Ticker) --}}
@props(['position' => 'global'])

@php
    $marquees = \App\Models\AppBanner::active()
        ->byPosition($position)
        ->where(function($q) {
            $q->whereIn('display_type', ['marquee', 'popup_and_marquee', 'banner_and_marquee', 'all']);
        })
        ->orderBy('priority', 'desc')
        ->orderBy('sort_order')
        ->get();

    $userId = auth()->check() ? auth()->id() : null;
    $userType = auth()->check() ? 'members' : 'all';

    $marquees = $marquees->filter(function($marquee) use ($userId, $userType) {
        return $marquee->isVisibleForUser($userId, $userType);
    });
@endphp

@if($marquees->count() > 0)
@foreach($marquees as $marquee)
    @php
        $marqueeId = $marquee->id;
        $positionClass = $marquee->display_position === 'bottom' ? 'bottom-0' : 'top-0';
        $typeColorClass = $marquee->background_color ? '' : $marquee->getTypeColorClass();
        $customStyles = $marquee->styles;
        $scrollSpeed = $marquee->scroll_speed ?? 50;
        $scrollDirection = $marquee->scroll_direction ?? 'left';

        // Calculate animation duration based on scroll speed (1-100)
        // Higher speed = shorter duration (faster scroll)
        $animationDuration = (100 - $scrollSpeed) / 2 + 10; // 10-60 seconds
    @endphp

    <div
        x-data="{
            show: true,
            dismissed: false,
            init() {
                // Track view
                fetch('/admin/app-banners/{{ $marqueeId }}/track-view', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });

                @if($marquee->auto_dismiss_seconds)
                    setTimeout(() => { this.show = false; }, {{ $marquee->auto_dismiss_seconds * 1000 }});
                @endif

                @if($marquee->sound_enabled)
                    playBannerSound('{{ $marquee->sound_type }}', '{{ $marquee->sound_url }}');
                @endif
            }
        }"
        x-show="show && !dismissed"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform -translate-y-full"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed {{ $positionClass }} left-0 right-0 z-[9997] {{ $typeColorClass }} overflow-hidden shadow-lg"
        style="{{ $customStyles }}"
        id="emergency-marquee-{{ $marqueeId }}">

        <div class="relative py-2 md:py-3">
            {{-- Scrolling Container --}}
            <div class="marquee-container overflow-hidden">
                <div class="marquee-content flex items-center gap-8 whitespace-nowrap"
                     style="animation: marquee-scroll-{{ $scrollDirection }} {{ $animationDuration }}s linear infinite;">

                    {{-- Repeat content multiple times for seamless loop --}}
                    @for($i = 0; $i < 3; $i++)
                        <div class="flex items-center gap-4">
                            {{-- Icon --}}
                            @if($marquee->icon)
                                <span class="text-2xl md:text-3xl flex-shrink-0">{{ $marquee->icon }}</span>
                            @endif

                            {{-- Text Content --}}
                            <div class="flex items-center gap-4">
                                <span class="font-bold text-base md:text-lg">
                                    {{ app()->getLocale() === 'en' && $marquee->title_en ? $marquee->title_en : $marquee->title }}
                                </span>

                                @if($marquee->description || $marquee->description_en)
                                    <span class="text-sm md:text-base opacity-90">
                                        {{ app()->getLocale() === 'en' && $marquee->description_en ? $marquee->description_en : $marquee->description }}
                                    </span>
                                @endif

                                {{-- Action Button (Inline) --}}
                                @if($marquee->action_type !== 'none' && $marquee->action_value)
                                    <a href="{{ $marquee->action_type === 'route' ? route($marquee->action_value) : $marquee->action_value }}"
                                       onclick="event.stopPropagation(); fetch('/admin/app-banners/{{ $marqueeId }}/track-click', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });"
                                       class="inline-flex items-center px-4 py-1.5 rounded-lg font-medium text-sm transition-all hover:scale-105 bg-white/20 hover:bg-white/30 backdrop-blur-sm"
                                       @if($marquee->button_color)
                                           style="background-color: {{ $marquee->button_color }}; color: white;"
                                       @endif>
                                        {{ app()->getLocale() === 'en' && $marquee->button_text_en ? $marquee->button_text_en : ($marquee->button_text ?? 'Learn More') }}
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                @endif
                            </div>

                            {{-- Separator --}}
                            <span class="text-2xl opacity-50">•</span>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Close Button --}}
            @if($marquee->is_dismissible && !$marquee->require_action)
                <button
                    @click="dismissed = true"
                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-full hover:bg-white/20 transition-colors z-10">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            @endif
        </div>
    </div>

    {{-- Marquee Scroll Animations --}}
    <style>
        @keyframes marquee-scroll-left {
            0% { transform: translateX(0); }
            100% { transform: translateX(-33.333%); }
        }

        @keyframes marquee-scroll-right {
            0% { transform: translateX(-33.333%); }
            100% { transform: translateX(0); }
        }

        @keyframes marquee-scroll-up {
            0% { transform: translateY(0); }
            100% { transform: translateY(-33.333%); }
        }

        @keyframes marquee-scroll-down {
            0% { transform: translateY(-33.333%); }
            100% { transform: translateY(0); }
        }

        /* Pause on hover */
        .marquee-container:hover .marquee-content {
            animation-play-state: paused;
        }

        /* Smooth scrolling */
        .marquee-content {
            animation-timing-function: linear;
            will-change: transform;
        }

        /* For vertical scrolling */
        @if($scrollDirection === 'up' || $scrollDirection === 'down')
        .marquee-container {
            height: 100px;
        }
        .marquee-content {
            flex-direction: column;
        }
        @endif
    </style>
@endforeach
@endif
