{{-- Emergency Alert Popup Component --}}
@props(['position' => 'global'])

@php
    $popups = \App\Models\AppBanner::active()
        ->byPosition($position)
        ->where(function($q) {
            $q->whereIn('display_type', ['popup', 'popup_and_banner', 'popup_and_marquee', 'all']);
        })
        ->orderBy('priority', 'desc')
        ->orderBy('sort_order')
        ->get();

    $userId = auth()->check() ? auth()->id() : null;
    $userType = auth()->check() ? 'members' : 'all';

    $popups = $popups->filter(function($popup) use ($userId, $userType) {
        return $popup->isVisibleForUser($userId, $userType);
    });
@endphp

@if($popups->count() > 0)
<div x-data="emergencyAlertPopup()" x-init="init({{ $popups->toJson() }})" class="fixed inset-0 pointer-events-none z-[9999]">
    <template x-for="(popup, index) in popups" :key="popup.id">
        <div
            x-show="popup.visible"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transform transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            :class="getPopupPositionClass(popup)"
            class="pointer-events-auto absolute max-w-sm w-full mb-4"
            style="display: none;">

            {{-- Fullscreen Overlay --}}
            <template x-if="popup.fullscreen">
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4 z-[9999]">
                    <div
                        :class="getSizeClass(popup)"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden max-w-2xl w-full transform transition-all"
                        :style="getCustomStyles(popup)">

                        {{-- Fullscreen Content --}}
                        <div class="relative">
                            {{-- Image Header --}}
                            <template x-if="popup.image_url">
                                <div class="relative h-64 overflow-hidden">
                                    <img :src="popup.image_url" :alt="popup.title"
                                         class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                </div>
                            </template>

                            {{-- Content --}}
                            <div class="p-8">
                                <div class="flex items-start gap-4 mb-4">
                                    <template x-if="popup.icon">
                                        <div class="text-5xl" x-text="popup.icon"></div>
                                    </template>
                                    <div class="flex-1">
                                        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-2" x-text="popup.title"></h2>
                                        <p class="text-gray-600 dark:text-gray-300 text-lg" x-text="popup.description"></p>
                                    </div>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex gap-3 mt-6">
                                    <template x-if="popup.action_type !== 'none' && popup.action_value">
                                        <a :href="popup.action_value"
                                           @click="trackClick(popup.id)"
                                           class="flex-1 px-6 py-3 rounded-lg font-bold text-center transition-all hover:scale-105"
                                           :style="'background-color: ' + (popup.button_color || '#3B82F6') + '; color: white;'"
                                           x-text="popup.button_text || 'Learn More'"></a>
                                    </template>

                                    <template x-if="popup.secondary_button_text">
                                        <button
                                            @click="dismissPopup(index)"
                                            class="px-6 py-3 rounded-lg font-medium border-2 border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all"
                                            x-text="popup.secondary_button_text"></button>
                                    </template>

                                    <template x-if="popup.is_dismissible && !popup.require_action">
                                        <button
                                            @click="dismissPopup(index)"
                                            class="px-6 py-3 rounded-lg font-medium bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                                            Close
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Regular Popup (Non-Fullscreen) --}}
            <template x-if="!popup.fullscreen">
                <div
                    :class="[
                        getTypeColorClass(popup),
                        popup.animation_style === 'shake' ? 'animate-shake' : '',
                        popup.animation_style === 'bounce' ? 'animate-bounce-in' : '',
                        popup.animation_style === 'zoom' ? 'animate-zoom-in' : ''
                    ]"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl border-l-4 overflow-hidden"
                    :style="getCustomStyles(popup)">

                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            {{-- Icon --}}
                            <template x-if="popup.icon">
                                <div class="flex-shrink-0 text-3xl" x-text="popup.icon"></div>
                            </template>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white" x-text="popup.title"></h4>

                                    <template x-if="popup.is_dismissible && !popup.require_action">
                                        <button
                                            @click="dismissPopup(index)"
                                            class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>

                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2" x-text="popup.description"></p>

                                {{-- Action Buttons --}}
                                <div class="flex items-center gap-2 mt-3">
                                    <template x-if="popup.action_type !== 'none' && popup.action_value">
                                        <a
                                            :href="popup.action_value"
                                            @click="trackClick(popup.id)"
                                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all hover:scale-105"
                                            :style="'background-color: ' + (popup.button_color || '#3B82F6') + '; color: white;'"
                                            x-text="popup.button_text || 'Learn More'"></a>
                                    </template>

                                    <template x-if="popup.secondary_button_text">
                                        <button
                                            @click="dismissPopup(index)"
                                            class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600"
                                            x-text="popup.secondary_button_text"></button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <template x-if="popup.auto_dismiss_seconds">
                        <div class="h-1 bg-gray-200 dark:bg-gray-700">
                            <div
                                class="h-full transition-all duration-100 ease-linear"
                                :class="getTypeProgressClass(popup)"
                                :style="'width: ' + popup.progress + '%'"></div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </template>
</div>

<script>
function emergencyAlertPopup() {
    return {
        popups: [],

        init(data) {
            this.popups = data.map(popup => ({
                ...popup,
                visible: true,
                progress: 100,
                timer: null
            }));

            this.popups.forEach((popup, index) => {
                // Track view
                this.trackView(popup.id);

                // Auto dismiss
                if (popup.auto_dismiss_seconds) {
                    this.startAutoDismiss(index);
                }

                // Play sound
                if (popup.sound_enabled) {
                    this.playSound(popup.sound_type, popup.sound_url);
                }
            });
        },

        getPopupPositionClass(popup) {
            const positions = {
                'top-right': 'top-4 right-4',
                'top-left': 'top-4 left-4',
                'bottom-right': 'bottom-4 right-4',
                'bottom-left': 'bottom-4 left-4',
                'center': 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2'
            };
            return positions[popup.display_position] || positions['top-right'];
        },

        getSizeClass(popup) {
            const sizes = {
                'small': 'text-sm p-4',
                'medium': 'text-base p-6',
                'large': 'text-lg p-8',
                'full': 'text-xl p-10'
            };
            return sizes[popup.size] || sizes['medium'];
        },

        getTypeColorClass(popup) {
            if (popup.background_color) return '';

            const colors = {
                'info': 'border-blue-500 bg-blue-50 dark:bg-blue-900',
                'warning': 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900',
                'success': 'border-green-500 bg-green-50 dark:bg-green-900',
                'promotion': 'border-purple-500 bg-purple-50 dark:bg-purple-900',
                'announcement': 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900'
            };
            return colors[popup.type] || colors['info'];
        },

        getTypeProgressClass(popup) {
            const colors = {
                'info': 'bg-blue-500',
                'warning': 'bg-yellow-500',
                'success': 'bg-green-500',
                'promotion': 'bg-purple-500',
                'announcement': 'bg-indigo-500'
            };
            return colors[popup.type] || colors['info'];
        },

        getCustomStyles(popup) {
            const styles = [];
            if (popup.background_color) styles.push(`background-color: ${popup.background_color}`);
            if (popup.text_color) styles.push(`color: ${popup.text_color}`);
            if (popup.border_color) styles.push(`border-color: ${popup.border_color}`);
            return styles.join('; ');
        },

        startAutoDismiss(index) {
            const popup = this.popups[index];
            const interval = 100;
            const steps = (popup.auto_dismiss_seconds * 1000) / interval;
            const decrement = 100 / steps;

            popup.timer = setInterval(() => {
                popup.progress -= decrement;
                if (popup.progress <= 0) {
                    this.dismissPopup(index);
                }
            }, interval);
        },

        dismissPopup(index) {
            const popup = this.popups[index];
            if (popup && popup.timer) {
                clearInterval(popup.timer);
            }
            if (popup) {
                popup.visible = false;
                setTimeout(() => {
                    this.popups.splice(index, 1);
                }, 300);
            }
        },

        trackView(popupId) {
            fetch(`/admin/app-banners/${popupId}/track-view`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
        },

        trackClick(popupId) {
            fetch(`/admin/app-banners/${popupId}/track-click`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
        },

        playSound(type, customUrl) {
            playBannerSound(type, customUrl);
        }
    };
}
</script>
@endif
