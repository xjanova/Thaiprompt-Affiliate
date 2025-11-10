{{-- Emergency Alert Banner Component --}}
@props(['position' => 'global'])

@php
    $banners = \App\Models\AppBanner::active()
        ->byPosition($position)
        ->where(function($q) {
            $q->whereIn('display_type', ['banner', 'popup_and_banner', 'banner_and_marquee', 'all']);
        })
        ->orderBy('priority', 'desc')
        ->orderBy('sort_order')
        ->get();

    $userId = auth()->check() ? auth()->id() : null;
    $userType = auth()->check() ? 'members' : 'all';

    // Filter banners for current user
    $banners = $banners->filter(function($banner) use ($userId, $userType) {
        return $banner->isVisibleForUser($userId, $userType);
    });
@endphp

@if($banners->count() > 0)
<div x-data="emergencyAlertBanner()" x-init="init()" class="emergency-alert-banners">
    @foreach($banners as $banner)
        @php
            $bannerId = $banner->id;
            $positionClass = $banner->getPositionClass();
            $sizeClass = $banner->getSizeClass();
            $typeColorClass = $banner->background_color ? '' : $banner->getTypeColorClass();
            $animationClass = $banner->getAnimationClass();
            $customStyles = $banner->styles;
        @endphp

        <div
            x-data="{ show: true, dismissed: false }"
            x-show="show && !dismissed"
            x-init="
                // Track view
                fetch('/admin/app-banners/{{ $bannerId }}/track-view', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

                @if($banner->auto_dismiss_seconds)
                    setTimeout(() => { show = false; }, {{ $banner->auto_dismiss_seconds * 1000 }});
                @endif

                @if($banner->sound_enabled)
                    playBannerSound('{{ $banner->sound_type }}', '{{ $banner->sound_url }}');
                @endif
            "
            x-transition:enter="transition ease-out duration-{{ $banner->animation_duration }}"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed {{ $positionClass }} left-0 right-0 z-[9998] mx-auto max-w-full"
            style="{{ $customStyles }}"
            id="emergency-banner-{{ $bannerId }}">

            <div class="relative {{ $typeColorClass }} {{ $sizeClass }} shadow-2xl border-2 {{ $banner->fullscreen ? 'min-h-screen flex items-center justify-center' : '' }}"
                 @if($banner->border_color)
                     style="border-color: {{ $banner->border_color }};"
                 @endif>

                {{-- Background Image (for promotion type) --}}
                @if($banner->image_url)
                    <div class="absolute inset-0 overflow-hidden">
                        <img src="{{ asset($banner->image_url) }}"
                             class="w-full h-full object-cover opacity-20 dark:hidden"
                             alt="{{ $banner->title }}">
                        @if($banner->image_url_dark)
                            <img src="{{ asset($banner->image_url_dark) }}"
                                 class="w-full h-full object-cover opacity-20 hidden dark:block"
                                 alt="{{ $banner->title }}">
                        @endif
                    </div>
                @endif

                <div class="relative z-10 container mx-auto flex items-center justify-between gap-4">
                    {{-- Icon --}}
                    @if($banner->icon)
                        <div class="flex-shrink-0 text-3xl md:text-4xl">
                            {{ $banner->icon }}
                        </div>
                    @endif

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-lg md:text-xl mb-1">
                            {{ app()->getLocale() === 'en' && $banner->title_en ? $banner->title_en : $banner->title }}
                        </h3>
                        @if($banner->description || $banner->description_en)
                            <p class="text-sm md:text-base opacity-90">
                                {{ app()->getLocale() === 'en' && $banner->description_en ? $banner->description_en : $banner->description }}
                            </p>
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if($banner->action_type !== 'none' && $banner->action_value)
                            <a href="{{ $banner->action_type === 'route' ? route($banner->action_value) : $banner->action_value }}"
                               @click="fetch('/admin/app-banners/{{ $bannerId }}/track-click', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });"
                               class="px-4 py-2 rounded-lg font-medium text-sm transition-all hover:scale-105"
                               @if($banner->button_color)
                                   style="background-color: {{ $banner->button_color }}; color: white;"
                               @else
                                   class="bg-white text-gray-900 hover:bg-gray-100"
                               @endif>
                                {{ app()->getLocale() === 'en' && $banner->button_text_en ? $banner->button_text_en : ($banner->button_text ?? 'Learn More') }}
                            </a>
                        @endif

                        @if($banner->secondary_button_text)
                            <button
                                @click="dismissed = true;"
                                class="px-4 py-2 rounded-lg font-medium text-sm bg-white/20 hover:bg-white/30 transition-all">
                                {{ app()->getLocale() === 'en' && $banner->secondary_button_text_en ? $banner->secondary_button_text_en : $banner->secondary_button_text }}
                            </button>
                        @endif

                        @if($banner->is_dismissible && !$banner->require_action)
                            <button @click="dismissed = true;"
                                    class="p-2 rounded-full hover:bg-white/20 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<script>
function emergencyAlertBanner() {
    return {
        init() {
            console.log('Emergency Alert Banner initialized');
        }
    };
}

function playBannerSound(type, customUrl) {
    if (customUrl && type === 'custom') {
        const audio = new Audio(customUrl);
        audio.volume = 0.5;
        audio.play().catch(e => console.debug('Sound play failed:', e));
    } else {
        // Use Web Audio API for default sounds
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            // Different frequencies for different types
            const frequencies = {
                'default': 800,
                'success': 600,
                'warning': 900,
                'error': 400
            };

            oscillator.frequency.value = frequencies[type] || 800;
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (error) {
            console.debug('Sound not available');
        }
    }
}
</script>

<style>
@keyframes animate-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes animate-slide-in {
    from { transform: translateY(-100%); }
    to { transform: translateY(0); }
}

@keyframes animate-bounce-in {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes animate-zoom-in {
    from { transform: scale(0); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

@keyframes animate-shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
    20%, 40%, 60%, 80% { transform: translateX(10px); }
}

.animate-fade-in { animation: animate-fade-in 0.3s ease-out; }
.animate-slide-in { animation: animate-slide-in 0.3s ease-out; }
.animate-bounce-in { animation: animate-bounce-in 0.5s ease-out; }
.animate-zoom-in { animation: animate-zoom-in 0.3s ease-out; }
.animate-shake { animation: animate-shake 0.5s ease-out; }
</style>
@endif
