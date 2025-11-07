@extends('layouts.admin')

@section('title', isset($theme) ? __('Edit Theme') : __('Create Theme'))

@section('content')
<div class="container mx-auto px-4 py-6" x-data="themeBuilder()" x-init="init()">
    <form @submit.prevent="saveTheme">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ isset($theme) ? __('Edit Theme') : __('Create New Theme') }}
                    </h1>
                    <p class="text-gray-600 mt-1">{{ __('Customize your application appearance') }}</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.themes.index') }}" class="btn btn-outline">
                        {{ __('Cancel') }}
                    </a>
                    <button type="button" @click="previewTheme" class="btn btn-outline">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        {{ __('Preview') }}
                    </button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        <span x-text="saving ? '{{ __('Saving...') }}' : '{{ __('Save Theme') }}'"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Editor Panel -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('Basic Information') }}</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Theme Name') }}</label>
                            <input type="text" x-model="formData.display_name" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Slug') }}</label>
                            <input type="text" x-model="formData.name" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   pattern="[a-z0-9-]+" title="{{ __('Only lowercase letters, numbers, and hyphens') }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Description') }}</label>
                            <textarea x-model="formData.description" rows="3"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Color Settings -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">{{ __('Colors') }}</h2>
                        <div class="flex gap-2">
                            <button type="button" @click="colorMode = 'light'"
                                    :class="colorMode === 'light' ? 'btn-primary' : 'btn-outline'"
                                    class="btn btn-sm">
                                {{ __('Light Mode') }}
                            </button>
                            <button type="button" @click="colorMode = 'dark'"
                                    :class="colorMode === 'dark' ? 'btn-primary' : 'btn-outline'"
                                    class="btn btn-sm">
                                {{ __('Dark Mode') }}
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <template x-for="(colorKey, index) in colorKeys" :key="colorKey">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" x-text="colorKey.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())"></label>
                                <div class="flex gap-2">
                                    <input type="color"
                                           x-model="formData.colors[colorMode === 'light' ? 'colors' : 'dark_colors'][colorKey]"
                                           class="w-16 h-10 rounded border border-gray-300 cursor-pointer">
                                    <input type="text"
                                           x-model="formData.colors[colorMode === 'light' ? 'colors' : 'dark_colors'][colorKey]"
                                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                           pattern="#[0-9A-Fa-f]{6}">
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Preset Color Palettes -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('Quick Color Palettes') }}</label>
                        <div class="grid grid-cols-3 gap-3">
                            <button type="button" @click="applyPalette('line')" class="p-3 border rounded-lg hover:border-green-500 transition-colors">
                                <div class="flex gap-1 mb-2">
                                    <div class="w-4 h-4 rounded" style="background: #06C755"></div>
                                    <div class="w-4 h-4 rounded" style="background: #00B900"></div>
                                    <div class="w-4 h-4 rounded" style="background: #00D664"></div>
                                </div>
                                <div class="text-xs font-medium">Line OA</div>
                            </button>

                            <button type="button" @click="applyPalette('ocean')" class="p-3 border rounded-lg hover:border-blue-500 transition-colors">
                                <div class="flex gap-1 mb-2">
                                    <div class="w-4 h-4 rounded" style="background: #0891b2"></div>
                                    <div class="w-4 h-4 rounded" style="background: #0e7490"></div>
                                    <div class="w-4 h-4 rounded" style="background: #06b6d4"></div>
                                </div>
                                <div class="text-xs font-medium">Ocean Blue</div>
                            </button>

                            <button type="button" @click="applyPalette('sunset')" class="p-3 border rounded-lg hover:border-orange-500 transition-colors">
                                <div class="flex gap-1 mb-2">
                                    <div class="w-4 h-4 rounded" style="background: #f97316"></div>
                                    <div class="w-4 h-4 rounded" style="background: #ea580c"></div>
                                    <div class="w-4 h-4 rounded" style="background: #fb923c"></div>
                                </div>
                                <div class="text-xs font-medium">Sunset</div>
                            </button>

                            <button type="button" @click="applyPalette('purple')" class="p-3 border rounded-lg hover:border-purple-500 transition-colors">
                                <div class="flex gap-1 mb-2">
                                    <div class="w-4 h-4 rounded" style="background: #9333ea"></div>
                                    <div class="w-4 h-4 rounded" style="background: #7e22ce"></div>
                                    <div class="w-4 h-4 rounded" style="background: #a855f7"></div>
                                </div>
                                <div class="text-xs font-medium">Purple Dream</div>
                            </button>

                            <button type="button" @click="applyPalette('forest')" class="p-3 border rounded-lg hover:border-green-500 transition-colors">
                                <div class="flex gap-1 mb-2">
                                    <div class="w-4 h-4 rounded" style="background: #16a34a"></div>
                                    <div class="w-4 h-4 rounded" style="background: #15803d"></div>
                                    <div class="w-4 h-4 rounded" style="background: #22c55e"></div>
                                </div>
                                <div class="text-xs font-medium">Forest</div>
                            </button>

                            <button type="button" @click="applyPalette('minimal')" class="p-3 border rounded-lg hover:border-gray-500 transition-colors">
                                <div class="flex gap-1 mb-2">
                                    <div class="w-4 h-4 rounded" style="background: #334155"></div>
                                    <div class="w-4 h-4 rounded" style="background: #1e293b"></div>
                                    <div class="w-4 h-4 rounded" style="background: #475569"></div>
                                </div>
                                <div class="text-xs font-medium">Minimal</div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Typography Settings -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('Typography') }}</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Font Family') }}</label>
                            <select x-model="formData.colors.typography['font-family']" class="w-full rounded-md border-gray-300 shadow-sm">
                                <option value="'Inter', system-ui, sans-serif">Inter</option>
                                <option value="'Roboto', system-ui, sans-serif">Roboto</option>
                                <option value="'Poppins', system-ui, sans-serif">Poppins</option>
                                <option value="'Open Sans', system-ui, sans-serif">Open Sans</option>
                                <option value="system-ui, sans-serif">System UI</option>
                                <option value="'Noto Sans Thai', system-ui, sans-serif">Noto Sans Thai</option>
                                <option value="'Sarabun', system-ui, sans-serif">Sarabun</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Base Size') }}</label>
                                <input type="text" x-model="formData.colors.typography['font-size-base']"
                                       class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Line Height') }}</label>
                                <input type="text" x-model="formData.colors.typography['line-height']"
                                       class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Font Weight') }}</label>
                                <select x-model="formData.colors.typography['font-weight-normal']" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="400">400 - Normal</option>
                                    <option value="500">500 - Medium</option>
                                    <option value="600">600 - Semi-bold</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Layout Settings -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('Layout & Spacing') }}</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Border Radius') }}</label>
                            <input type="range" x-model="formData.colors.borders.radius" min="0" max="20" step="1"
                                   class="w-full">
                            <div class="text-sm text-gray-600 mt-1" x-text="`${formData.colors.borders.radius}px`"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Border Width') }}</label>
                            <input type="range" x-model="formData.colors.borders.width" min="1" max="4" step="1"
                                   class="w-full">
                            <div class="text-sm text-gray-600 mt-1" x-text="`${formData.colors.borders.width}px`"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Sidebar Width') }}</label>
                            <input type="range" x-model="formData.colors.layout['sidebar-width']" min="200" max="320" step="10"
                                   class="w-full">
                            <div class="text-sm text-gray-600 mt-1" x-text="`${formData.colors.layout['sidebar-width']}px`"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Panel -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('Live Preview') }}</h2>

                    <!-- Preview Container -->
                    <div class="border rounded-lg overflow-hidden" :style="getPreviewStyles()">
                        <!-- Header -->
                        <div class="p-4" :style="`background-color: ${getCurrentColor('primary')}; color: white;`">
                            <div class="font-semibold">{{ __('Header') }}</div>
                        </div>

                        <!-- Sidebar & Content -->
                        <div class="flex h-64">
                            <div class="w-20 p-3" :style="`background-color: ${getCurrentColor('sidebar-bg')};`">
                                <div class="space-y-2">
                                    <div class="w-full h-8 rounded" :style="`background-color: ${getCurrentColor('sidebar-item-hover')};`"></div>
                                    <div class="w-full h-8 rounded opacity-50" :style="`background-color: ${getCurrentColor('sidebar-item-hover')};`"></div>
                                    <div class="w-full h-8 rounded opacity-50" :style="`background-color: ${getCurrentColor('sidebar-item-hover')};`"></div>
                                </div>
                            </div>

                            <div class="flex-1 p-4" :style="`background-color: ${getCurrentColor('background')};`">
                                <!-- Card -->
                                <div class="rounded p-3 mb-3" :style="`background-color: ${getCurrentColor('card-bg')}; border: ${formData.colors.borders.width}px solid ${getCurrentColor('border')}; border-radius: ${formData.colors.borders.radius}px;`">
                                    <div class="text-sm font-medium mb-2" :style="`color: ${getCurrentColor('text')};`">Card Title</div>
                                    <div class="text-xs" :style="`color: ${getCurrentColor('text-secondary')};`">Card content text</div>
                                </div>

                                <!-- Button -->
                                <button class="px-3 py-1.5 text-xs text-white rounded"
                                        :style="`background-color: ${getCurrentColor('primary')}; border-radius: ${formData.colors.borders.radius}px;`">
                                    Button
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Color Summary -->
                    <div class="mt-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ __('Color Palette') }}</h3>
                        <div class="grid grid-cols-4 gap-2">
                            <template x-for="(colorKey, index) in ['primary', 'secondary', 'accent', 'success', 'warning', 'error']" :key="colorKey">
                                <div class="text-center">
                                    <div class="w-full h-10 rounded border border-gray-200 mb-1"
                                         :style="`background-color: ${getCurrentColor(colorKey)};`">
                                    </div>
                                    <div class="text-xs text-gray-600" x-text="colorKey"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function themeBuilder() {
    return {
        formData: {
            name: '',
            display_name: '',
            description: '',
            colors: {
                colors: {},
                dark_colors: {},
                typography: {
                    'font-family': "'Inter', system-ui, sans-serif",
                    'font-size-base': '16px',
                    'line-height': '1.5',
                    'font-weight-normal': '400',
                },
                spacing: {
                    'unit': '8px',
                },
                borders: {
                    'radius': '8',
                    'width': '1',
                },
                layout: {
                    'sidebar-width': '256',
                }
            }
        },
        colorMode: 'light',
        saving: false,
        colorKeys: [
            'primary', 'primary-dark', 'primary-light',
            'secondary', 'secondary-dark', 'secondary-light',
            'accent', 'success', 'warning', 'error', 'info',
            'background', 'surface', 'card-bg',
            'text', 'text-secondary', 'text-muted',
            'border', 'divider',
            'sidebar-bg', 'sidebar-text', 'sidebar-item-hover'
        ],

        init() {
            @if(isset($theme))
                this.formData = {!! json_encode([
                    'id' => $theme->id,
                    'name' => $theme->name,
                    'display_name' => $theme->display_name,
                    'description' => $theme->description,
                    'colors' => array_merge([
                        'colors' => $theme->colors ?? [],
                        'dark_colors' => $theme->dark_colors ?? [],
                        'typography' => $theme->typography ?? [],
                        'spacing' => $theme->spacing ?? [],
                        'borders' => $theme->borders ?? [],
                        'layout' => $theme->layout ?? [],
                    ], [])
                ]) !!};
            @else
                this.applyPalette('line');
            @endif
        },

        getCurrentColor(key) {
            const colors = this.colorMode === 'light'
                ? this.formData.colors.colors
                : this.formData.colors.dark_colors;
            return colors[key] || '#000000';
        },

        getPreviewStyles() {
            return `font-family: ${this.formData.colors.typography['font-family']};`;
        },

        applyPalette(palette) {
            const palettes = {
                line: {
                    primary: '#06C755', 'primary-dark': '#00B900', 'primary-light': '#00D664',
                    secondary: '#64D2FF', 'secondary-dark': '#00B8D4', 'secondary-light': '#80E0FF',
                    accent: '#FF6B6B', success: '#22C55E', warning: '#F59E0B', error: '#EF4444', info: '#3B82F6',
                    background: '#F5F7FA', surface: '#FFFFFF', 'card-bg': '#FFFFFF',
                    text: '#1F2937', 'text-secondary': '#6B7280', 'text-muted': '#9CA3AF',
                    border: '#E5E7EB', divider: '#E5E7EB',
                    'sidebar-bg': '#FFFFFF', 'sidebar-text': '#374151', 'sidebar-item-hover': '#F3F4F6'
                },
                ocean: {
                    primary: '#0891b2', 'primary-dark': '#0e7490', 'primary-light': '#06b6d4',
                    secondary: '#8b5cf6', 'secondary-dark': '#7c3aed', 'secondary-light': '#a78bfa',
                    accent: '#f43f5e', success: '#10b981', warning: '#f59e0b', error: '#ef4444', info: '#3b82f6',
                    background: '#f0f9ff', surface: '#ffffff', 'card-bg': '#ffffff',
                    text: '#0c4a6e', 'text-secondary': '#475569', 'text-muted': '#94a3b8',
                    border: '#e0f2fe', divider: '#e0f2fe',
                    'sidebar-bg': '#0c4a6e', 'sidebar-text': '#e0f2fe', 'sidebar-item-hover': '#075985'
                },
                sunset: {
                    primary: '#f97316', 'primary-dark': '#ea580c', 'primary-light': '#fb923c',
                    secondary: '#f59e0b', 'secondary-dark': '#d97706', 'secondary-light': '#fbbf24',
                    accent: '#ec4899', success: '#10b981', warning: '#eab308', error: '#dc2626', info: '#6366f1',
                    background: '#fff7ed', surface: '#ffffff', 'card-bg': '#ffffff',
                    text: '#7c2d12', 'text-secondary': '#92400e', 'text-muted': '#b45309',
                    border: '#fed7aa', divider: '#fed7aa',
                    'sidebar-bg': '#7c2d12', 'sidebar-text': '#fed7aa', 'sidebar-item-hover': '#9a3412'
                },
                purple: {
                    primary: '#9333ea', 'primary-dark': '#7e22ce', 'primary-light': '#a855f7',
                    secondary: '#ec4899', 'secondary-dark': '#db2777', 'secondary-light': '#f472b6',
                    accent: '#06b6d4', success: '#10b981', warning: '#f59e0b', error: '#ef4444', info: '#3b82f6',
                    background: '#faf5ff', surface: '#ffffff', 'card-bg': '#ffffff',
                    text: '#581c87', 'text-secondary': '#6b21a8', 'text-muted': '#9333ea',
                    border: '#e9d5ff', divider: '#e9d5ff',
                    'sidebar-bg': '#581c87', 'sidebar-text': '#e9d5ff', 'sidebar-item-hover': '#6b21a8'
                },
                forest: {
                    primary: '#16a34a', 'primary-dark': '#15803d', 'primary-light': '#22c55e',
                    secondary: '#14b8a6', 'secondary-dark': '#0d9488', 'secondary-light': '#2dd4bf',
                    accent: '#f59e0b', success: '#84cc16', warning: '#eab308', error: '#dc2626', info: '#0ea5e9',
                    background: '#f0fdf4', surface: '#ffffff', 'card-bg': '#ffffff',
                    text: '#14532d', 'text-secondary': '#166534', 'text-muted': '#16a34a',
                    border: '#bbf7d0', divider: '#bbf7d0',
                    'sidebar-bg': '#14532d', 'sidebar-text': '#bbf7d0', 'sidebar-item-hover': '#166534'
                },
                minimal: {
                    primary: '#334155', 'primary-dark': '#1e293b', 'primary-light': '#475569',
                    secondary: '#64748b', 'secondary-dark': '#475569', 'secondary-light': '#94a3b8',
                    accent: '#06b6d4', success: '#10b981', warning: '#f59e0b', error: '#ef4444', info: '#3b82f6',
                    background: '#f8fafc', surface: '#ffffff', 'card-bg': '#ffffff',
                    text: '#0f172a', 'text-secondary': '#334155', 'text-muted': '#64748b',
                    border: '#e2e8f0', divider: '#e2e8f0',
                    'sidebar-bg': '#0f172a', 'sidebar-text': '#cbd5e1', 'sidebar-item-hover': '#1e293b'
                }
            };

            if (palettes[palette]) {
                this.formData.colors.colors = { ...palettes[palette] };
                // Create dark mode version with inverted colors
                this.formData.colors.dark_colors = {
                    ...palettes[palette],
                    background: '#0f172a',
                    surface: '#1e293b',
                    'card-bg': '#1e293b',
                    text: '#f1f5f9',
                    'text-secondary': '#cbd5e1',
                    'text-muted': '#94a3b8',
                    border: '#334155',
                    divider: '#334155',
                };
            }
        },

        previewTheme() {
            // Apply theme temporarily for preview
            const cssVars = this.generateCssVars();
            const style = document.getElementById('preview-theme-style') || document.createElement('style');
            style.id = 'preview-theme-style';
            style.textContent = `:root { ${cssVars} }`;
            document.head.appendChild(style);
        },

        generateCssVars() {
            const colors = this.colorMode === 'light' ? this.formData.colors.colors : this.formData.colors.dark_colors;
            let vars = '';

            // Colors
            Object.entries(colors).forEach(([key, value]) => {
                vars += `--color-${key}: ${value}; `;
            });

            // Typography
            Object.entries(this.formData.colors.typography).forEach(([key, value]) => {
                vars += `--${key}: ${value}; `;
            });

            // Borders
            vars += `--border-radius: ${this.formData.colors.borders.radius}px; `;
            vars += `--border-width: ${this.formData.colors.borders.width}px; `;

            // Layout
            vars += `--sidebar-width: ${this.formData.colors.layout['sidebar-width']}px; `;

            return vars;
        },

        async saveTheme() {
            this.saving = true;

            try {
                const url = this.formData.id
                    ? `/admin/themes/${this.formData.id}`
                    : '/admin/themes';

                const method = this.formData.id ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: this.formData.name,
                        display_name: this.formData.display_name,
                        description: this.formData.description,
                        colors: this.formData.colors.colors,
                        dark_colors: this.formData.colors.dark_colors,
                        typography: this.formData.colors.typography,
                        spacing: this.formData.colors.spacing,
                        borders: this.formData.colors.borders,
                        shadows: this.formData.colors.shadows || {},
                        animations: this.formData.colors.animations || {},
                        layout: this.formData.colors.layout
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('{{ __("Theme saved successfully!") }}');
                    window.location.href = '/admin/themes';
                } else {
                    alert(data.message || '{{ __("Failed to save theme") }}');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('{{ __("An error occurred while saving") }}');
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endpush
@endsection
