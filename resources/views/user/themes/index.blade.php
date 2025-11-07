@extends('layouts.user')

@section('title', __('Theme Settings'))

@section('content')
<div class="container mx-auto px-4 py-6" x-data="themeSelector()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">{{ __('Theme Settings') }}</h1>
        <p class="text-gray-600 mt-1">{{ __('Customize your viewing experience') }}</p>
    </div>

    <!-- Current Theme Card -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">{{ __('Current Theme') }}</h2>
                <p class="text-gray-600 text-sm" x-text="currentTheme.display_name"></p>
            </div>

            <!-- Theme Mode Selector -->
            <div class="flex gap-2 bg-gray-100 rounded-lg p-1">
                <button @click="setMode('light')"
                        :class="currentMode === 'light' ? 'bg-white shadow' : 'text-gray-600'"
                        class="px-4 py-2 rounded-md transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ __('Light') }}</span>
                </button>

                <button @click="setMode('dark')"
                        :class="currentMode === 'dark' ? 'bg-white shadow' : 'text-gray-600'"
                        class="px-4 py-2 rounded-md transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ __('Dark') }}</span>
                </button>

                <button @click="setMode('auto')"
                        :class="currentMode === 'auto' ? 'bg-white shadow' : 'text-gray-600'"
                        class="px-4 py-2 rounded-md transition-all duration-200 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <span class="text-sm font-medium">{{ __('Auto') }}</span>
                </button>
            </div>
        </div>

        <!-- Current Theme Preview -->
        <div class="border rounded-lg overflow-hidden h-40" :style="getThemePreviewStyle(currentTheme)">
            <div class="h-12 flex items-center px-4" :style="`background-color: ${currentTheme.colors?.primary || '#06C755'}; color: white;`">
                <span class="font-semibold">{{ __('Preview') }}</span>
            </div>
            <div class="flex h-28">
                <div class="w-20 p-2" :style="`background-color: ${currentTheme.colors?.['sidebar-bg'] || '#FFFFFF'};`">
                    <div class="space-y-2">
                        <div class="h-6 rounded" :style="`background-color: ${currentTheme.colors?.['sidebar-item-hover'] || '#F3F4F6'};`"></div>
                        <div class="h-6 rounded opacity-50" :style="`background-color: ${currentTheme.colors?.['sidebar-item-hover'] || '#F3F4F6'};`"></div>
                    </div>
                </div>
                <div class="flex-1 p-4" :style="`background-color: ${currentTheme.colors?.background || '#F5F7FA'};`">
                    <div class="rounded p-2 mb-2" :style="`background-color: ${currentTheme.colors?.['card-bg'] || '#FFFFFF'};`">
                        <div class="text-xs font-medium">{{ __('Card Content') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Themes -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('Available Themes') }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($themes as $theme)
                <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-all cursor-pointer"
                     :class="currentTheme.id === {{ $theme->id }} ? 'ring-2 ring-indigo-500' : ''"
                     @click="selectTheme({{ $theme->id }})">

                    <!-- Theme Preview -->
                    <div class="relative h-40 bg-gradient-to-br from-gray-100 to-gray-200"
                         style="background: linear-gradient(135deg, {{ $theme->colors['primary'] ?? '#06C755' }} 0%, {{ $theme->colors['primary-dark'] ?? '#00B900' }} 100%)">
                        @if($theme->preview_image)
                            <img src="{{ asset($theme->preview_image) }}" alt="{{ $theme->display_name }}" class="w-full h-full object-cover">
                        @else
                            <div class="flex items-center justify-center h-full">
                                <div class="text-center text-white">
                                    <div class="text-5xl font-bold opacity-20">{{ substr($theme->display_name, 0, 1) }}</div>
                                </div>
                            </div>
                        @endif

                        @if($currentTheme->id === $theme->id)
                            <div class="absolute top-2 right-2">
                                <span class="px-3 py-1 bg-indigo-500 text-white text-xs font-semibold rounded-full flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ __('Active') }}
                                </span>
                            </div>
                        @endif

                        @if($theme->is_default)
                            <div class="absolute top-2 left-2">
                                <span class="px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
                                    {{ __('Default') }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- Theme Info -->
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $theme->display_name }}</h3>
                        <p class="text-sm text-gray-600 mb-3">{{ Str::limit($theme->description ?? __('No description'), 60) }}</p>

                        <!-- Color Palette Preview -->
                        <div class="flex gap-2">
                            @foreach(['primary', 'secondary', 'accent', 'success', 'warning', 'error'] as $colorKey)
                                @if(isset($theme->colors[$colorKey]))
                                    <div class="w-6 h-6 rounded-full border-2 border-gray-200"
                                         style="background-color: {{ $theme->colors[$colorKey] }}"
                                         title="{{ ucfirst($colorKey) }}: {{ $theme->colors[$colorKey] }}">
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Theme Info Section -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-blue-900 mb-2">{{ __('About Themes') }}</h3>
                <ul class="text-sm text-blue-800 space-y-1">
                    <li>• {{ __('Themes control the appearance of the entire application') }}</li>
                    <li>• {{ __('You can switch between Light, Dark, or Auto mode') }}</li>
                    <li>• {{ __('Auto mode follows your system preferences') }}</li>
                    <li>• {{ __('Your theme preference is saved automatically') }}</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function themeSelector() {
    return {
        currentTheme: @json($currentTheme),
        currentMode: '{{ $currentMode ?? 'auto' }}',

        init() {
            console.log('Theme Selector initialized', this.currentTheme);
        },

        getThemePreviewStyle(theme) {
            return `font-family: ${theme.typography?.['font-family'] || "'Inter', system-ui, sans-serif"};`;
        },

        async setMode(mode) {
            try {
                const response = await fetch('/user/themes/set', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        theme_id: this.currentTheme.id,
                        mode: mode
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.currentMode = mode;

                    // Apply theme immediately via ThemeManager
                    if (typeof window.ThemeManager !== 'undefined') {
                        window.ThemeManager.setMode(mode);
                    } else {
                        // Fallback: reload page
                        window.location.reload();
                    }
                } else {
                    alert(data.message || '{{ __("Failed to update theme mode") }}');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('{{ __("An error occurred") }}');
            }
        },

        async selectTheme(themeId) {
            if (this.currentTheme.id === themeId) {
                return; // Already selected
            }

            try {
                const response = await fetch('/user/themes/set', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        theme_id: themeId,
                        mode: this.currentMode
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Update current theme
                    const selectedTheme = @json($themes).find(t => t.id === themeId);
                    if (selectedTheme) {
                        this.currentTheme = selectedTheme;
                    }

                    // Apply theme immediately via ThemeManager
                    if (typeof window.ThemeManager !== 'undefined') {
                        window.ThemeManager.changeTheme(themeId, this.currentMode);
                    } else {
                        // Fallback: reload page
                        window.location.reload();
                    }

                    // Show success message
                    this.showSuccessMessage();
                } else {
                    alert(data.message || '{{ __("Failed to change theme") }}');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('{{ __("An error occurred") }}');
            }
        },

        showSuccessMessage() {
            // Create a temporary success message
            const message = document.createElement('div');
            message.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
            message.innerHTML = `
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ __("Theme updated successfully!") }}</span>
            `;
            document.body.appendChild(message);

            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.3s';
                setTimeout(() => message.remove(), 300);
            }, 3000);
        }
    }
}
</script>
@endpush
@endsection
