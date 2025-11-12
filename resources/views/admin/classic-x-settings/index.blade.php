@extends('layouts.admin')

@section('title', 'Classic X Theme Settings')

@section('content')
<div class="max-w-7xl mx-auto" x-data="classicXSettings()">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-xl p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center">
                    <i class="fas fa-palette mr-3"></i>
                    Classic X Theme Customization
                </h1>
                <p class="text-blue-100 mt-2">ปรับแต่งธีม Classic X แบบ WordPress Premium Style</p>
            </div>
            <div class="flex space-x-3">
                <button @click="resetToDefaults" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors backdrop-blur-sm">
                    <i class="fas fa-undo mr-2"></i>
                    Reset to Defaults
                </button>
                <button @click="previewTheme" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition-colors backdrop-blur-sm">
                    <i class="fas fa-eye mr-2"></i>
                    Preview
                </button>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.classic-x-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Settings -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Sidebar Settings -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-500 p-4 text-white">
                        <h3 class="text-xl font-bold flex items-center">
                            <i class="fas fa-columns mr-2"></i>
                            Sidebar Settings
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sidebar Width (px)
                                </label>
                                <input type="number" name="settings[classic_x_sidebar_width]"
                                       value="{{ $settings['classic_x_sidebar_width'] ?? 280 }}"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Collapsed Width (px)
                                </label>
                                <input type="number" name="settings[classic_x_sidebar_collapsed_width]"
                                       value="{{ $settings['classic_x_sidebar_collapsed_width'] ?? 64 }}"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sidebar Position
                            </label>
                            <select name="settings[classic_x_sidebar_position]"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                <option value="left" {{ ($settings['classic_x_sidebar_position'] ?? 'left') === 'left' ? 'selected' : '' }}>Left</option>
                                <option value="right" {{ ($settings['classic_x_sidebar_position'] ?? 'left') === 'right' ? 'selected' : '' }}>Right</option>
                            </select>
                        </div>

                        <div class="flex items-center">
                            <input type="hidden" name="settings[classic_x_sidebar_collapsible]" value="false">
                            <input type="checkbox" name="settings[classic_x_sidebar_collapsible]" value="true"
                                   {{ ($settings['classic_x_sidebar_collapsible'] ?? true) ? 'checked' : '' }}
                                   class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                            <label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Allow Sidebar to Collapse
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Color Settings -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-4 text-white">
                        <h3 class="text-xl font-bold flex items-center">
                            <i class="fas fa-palette mr-2"></i>
                            Color Settings
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Primary Color
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" name="settings[classic_x_primary_color]"
                                           value="{{ $settings['classic_x_primary_color'] ?? '#2271b1' }}"
                                           class="h-10 w-20 rounded cursor-pointer">
                                    <input type="text" value="{{ $settings['classic_x_primary_color'] ?? '#2271b1' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                           readonly>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sidebar Background
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" name="settings[classic_x_sidebar_bg]"
                                           value="{{ $settings['classic_x_sidebar_bg'] ?? '#1d2327' }}"
                                           class="h-10 w-20 rounded cursor-pointer">
                                    <input type="text" value="{{ $settings['classic_x_sidebar_bg'] ?? '#1d2327' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                           readonly>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Sidebar Text Color
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" name="settings[classic_x_sidebar_text]"
                                           value="{{ $settings['classic_x_sidebar_text'] ?? '#ffffff' }}"
                                           class="h-10 w-20 rounded cursor-pointer">
                                    <input type="text" value="{{ $settings['classic_x_sidebar_text'] ?? '#ffffff' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                           readonly>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Hover Background
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" name="settings[classic_x_sidebar_hover_bg]"
                                           value="{{ $settings['classic_x_sidebar_hover_bg'] ?? '#2c3338' }}"
                                           class="h-10 w-20 rounded cursor-pointer">
                                    <input type="text" value="{{ $settings['classic_x_sidebar_hover_bg'] ?? '#2c3338' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                           readonly>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Active Background
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" name="settings[classic_x_sidebar_active_bg]"
                                           value="{{ $settings['classic_x_sidebar_active_bg'] ?? '#0073aa' }}"
                                           class="h-10 w-20 rounded cursor-pointer">
                                    <input type="text" value="{{ $settings['classic_x_sidebar_active_bg'] ?? '#0073aa' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                           readonly>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Badge Color
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input type="color" name="settings[classic_x_badge_color]"
                                           value="{{ $settings['classic_x_badge_color'] ?? '#d63638' }}"
                                           class="h-10 w-20 rounded cursor-pointer">
                                    <input type="text" value="{{ $settings['classic_x_badge_color'] ?? '#d63638' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                           readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visual Effects -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-teal-500 p-4 text-white">
                        <h3 class="text-xl font-bold flex items-center">
                            <i class="fas fa-magic mr-2"></i>
                            Visual Effects
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Shadow Intensity
                                </label>
                                <select name="settings[classic_x_shadow_intensity]"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="light" {{ ($settings['classic_x_shadow_intensity'] ?? 'medium') === 'light' ? 'selected' : '' }}>Light</option>
                                    <option value="medium" {{ ($settings['classic_x_shadow_intensity'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="strong" {{ ($settings['classic_x_shadow_intensity'] ?? 'medium') === 'strong' ? 'selected' : '' }}>Strong</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    3D Depth Intensity
                                </label>
                                <select name="settings[classic_x_depth_intensity]"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="light" {{ ($settings['classic_x_depth_intensity'] ?? 'medium') === 'light' ? 'selected' : '' }}>Light</option>
                                    <option value="medium" {{ ($settings['classic_x_depth_intensity'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                    <option value="strong" {{ ($settings['classic_x_depth_intensity'] ?? 'medium') === 'strong' ? 'selected' : '' }}>Strong</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input type="hidden" name="settings[classic_x_enable_shadows]" value="false">
                                <input type="checkbox" name="settings[classic_x_enable_shadows]" value="true"
                                       {{ ($settings['classic_x_enable_shadows'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Enable Shadow Effects
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="settings[classic_x_enable_gradients]" value="false">
                                <input type="checkbox" name="settings[classic_x_enable_gradients]" value="true"
                                       {{ ($settings['classic_x_enable_gradients'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Enable Gradient Backgrounds
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="settings[classic_x_enable_animations]" value="false">
                                <input type="checkbox" name="settings[classic_x_enable_animations]" value="true"
                                       {{ ($settings['classic_x_enable_animations'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Enable Smooth Animations
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="settings[classic_x_enable_3d_depth]" value="false">
                                <input type="checkbox" name="settings[classic_x_enable_3d_depth]" value="true"
                                       {{ ($settings['classic_x_enable_3d_depth'] ?? true) ? 'checked' : '' }}
                                       class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                                <label class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Enable 3D Depth Effects
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Preview & Actions -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden sticky top-6">
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 p-4 text-white">
                        <h3 class="text-xl font-bold flex items-center">
                            <i class="fas fa-bolt mr-2"></i>
                            Quick Actions
                        </h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <button type="submit" class="w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl font-semibold">
                            <i class="fas fa-save mr-2"></i>
                            Save All Settings
                        </button>
                        <a href="{{ route('admin.dashboard') }}" class="block w-full px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors text-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
                    <h4 class="text-lg font-bold mb-3 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i>
                        About Classic X
                    </h4>
                    <p class="text-sm text-blue-100 leading-relaxed">
                        Classic X is a premium WordPress-inspired theme with modern enhancements including 3D depth effects, smooth animations, and professional styling.
                    </p>
                    <div class="mt-4 pt-4 border-t border-white/20">
                        <div class="text-xs space-y-2">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                30+ Customizable Settings
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                Mobile Responsive
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                Dark Mode Support
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function classicXSettings() {
    return {
        resetToDefaults() {
            if (confirm('Are you sure you want to reset all settings to defaults?')) {
                fetch('{{ route("admin.classic-x-settings.reset") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to reset settings');
                });
            }
        },

        previewTheme() {
            window.open('{{ route("admin.classic-x-settings.preview") }}', '_blank');
        }
    }
}
</script>
@endsection
