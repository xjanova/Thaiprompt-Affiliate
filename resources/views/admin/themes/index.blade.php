@extends('layouts.admin')

@section('title', __('Theme Management'))

@section('content')
<div class="container mx-auto px-4 py-6" x-data="themeManager()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('Theme Management') }}</h1>
                <p class="text-gray-600 mt-1">{{ __('Manage themes for your application') }}</p>
            </div>
            <div class="flex gap-3">
                <button @click="$refs.importFile.click()" class="btn btn-outline">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    {{ __('Import Theme') }}
                </button>
                <a href="{{ route('admin.themes.builder') }}" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Create New Theme') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm">{{ __('Total Themes') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $themes->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm">{{ __('Active Theme') }}</p>
                    <p class="text-lg font-bold text-gray-900">{{ $defaultTheme->display_name ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm">{{ __('Theme Presets') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ \App\Models\ThemePreset::count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-600 text-sm">{{ __('Active Users') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ \App\Models\UserTheme::distinct('user_id')->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Themes Grid -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('Installed Themes') }}</h2>
        </div>

        @if($themes->isEmpty())
            <div class="p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No themes') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('Get started by creating a new theme.') }}</p>
                <div class="mt-6">
                    <a href="{{ route('admin.themes.builder') }}" class="btn btn-primary">
                        {{ __('Create Theme') }}
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                @foreach($themes as $theme)
                    <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow" x-data="{ showActions: false }">
                        <!-- Theme Preview -->
                        <div class="relative h-48 bg-gradient-to-br from-gray-100 to-gray-200 p-4"
                             style="background: linear-gradient(135deg, {{ $theme->colors['primary'] ?? '#06C755' }} 0%, {{ $theme->colors['primary-dark'] ?? '#00B900' }} 100%)">
                            @if($theme->preview_image)
                                <img src="{{ asset($theme->preview_image) }}" alt="{{ $theme->display_name }}" class="w-full h-full object-cover">
                            @else
                                <div class="flex items-center justify-center h-full">
                                    <div class="text-center text-white">
                                        <div class="text-6xl font-bold opacity-20">{{ substr($theme->display_name, 0, 1) }}</div>
                                    </div>
                                </div>
                            @endif

                            @if($theme->is_default)
                                <div class="absolute top-2 right-2">
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
                            <div class="flex gap-2 mb-4">
                                @foreach(['primary', 'secondary', 'accent', 'success', 'warning', 'error'] as $colorKey)
                                    @if(isset($theme->colors[$colorKey]))
                                        <div class="w-6 h-6 rounded-full border-2 border-gray-200"
                                             style="background-color: {{ $theme->colors[$colorKey] }}"
                                             title="{{ ucfirst($colorKey) }}: {{ $theme->colors[$colorKey] }}">
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2">
                                @if(!$theme->is_default)
                                    <button @click="setDefault({{ $theme->id }})" class="flex-1 btn btn-sm btn-primary">
                                        {{ __('Set as Default') }}
                                    </button>
                                @endif

                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" class="btn btn-sm btn-outline px-3">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                        </svg>
                                    </button>

                                    <div x-show="open" @click.away="open = false"
                                         class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                                        <a href="{{ route('admin.themes.builder', ['id' => $theme->id]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            {{ __('Edit') }}
                                        </a>
                                        <button @click="duplicate({{ $theme->id }})" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            {{ __('Duplicate') }}
                                        </button>
                                        <button @click="exportTheme({{ $theme->id }})" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            {{ __('Export') }}
                                        </button>
                                        @if(!$theme->is_default)
                                            <hr class="my-1">
                                            <button @click="deleteTheme({{ $theme->id }})" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                {{ __('Delete') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Theme Presets Section -->
    <div class="bg-white rounded-lg shadow mt-6">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('Theme Presets') }}</h2>
            <p class="text-gray-600 text-sm mt-1">{{ __('Quick start with pre-designed themes') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
            @foreach(\App\Models\ThemePreset::all() as $preset)
                <div class="border rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                    <!-- Preset Preview -->
                    <div class="relative h-32 bg-gradient-to-br p-4"
                         style="background: linear-gradient(135deg, {{ $preset->colors['primary'] ?? '#06C755' }} 0%, {{ $preset->colors['primary-dark'] ?? '#00B900' }} 100%)">
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center text-white">
                                <div class="text-4xl font-bold opacity-20">{{ substr($preset->name, 0, 1) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Preset Info -->
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $preset->name }}</h3>
                        <p class="text-sm text-gray-600 mb-3">{{ Str::limit($preset->description ?? '', 60) }}</p>

                        <button @click="createFromPreset({{ $preset->id }})" class="w-full btn btn-sm btn-outline">
                            {{ __('Use This Preset') }}
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Hidden Import File Input -->
    <input type="file" x-ref="importFile" @change="importTheme($event)" accept=".json" class="hidden">
</div>

@push('scripts')
<script>
function themeManager() {
    return {
        setDefault(themeId) {
            if (confirm('{{ __("Set this theme as default for all users?") }}')) {
                fetch(`/admin/themes/${themeId}/set-default`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || '{{ __("Failed to set default theme") }}');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{ __("An error occurred") }}');
                });
            }
        },

        duplicate(themeId) {
            fetch(`/admin/themes/${themeId}/duplicate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || '{{ __("Failed to duplicate theme") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __("An error occurred") }}');
            });
        },

        exportTheme(themeId) {
            window.location.href = `/admin/themes/${themeId}/export`;
        },

        deleteTheme(themeId) {
            if (confirm('{{ __("Are you sure you want to delete this theme?") }}')) {
                fetch(`/admin/themes/${themeId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || '{{ __("Failed to delete theme") }}');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{ __("An error occurred") }}');
                });
            }
        },

        createFromPreset(presetId) {
            const name = prompt('{{ __("Enter a name for the new theme:") }}');
            if (name) {
                fetch('/admin/themes/from-preset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ preset_id: presetId, name: name })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `/admin/themes/builder?id=${data.theme.id}`;
                    } else {
                        alert(data.message || '{{ __("Failed to create theme") }}');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('{{ __("An error occurred") }}');
                });
            }
        },

        importTheme(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            fetch('/admin/themes/import', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || '{{ __("Failed to import theme") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __("An error occurred") }}');
            });

            // Reset file input
            event.target.value = '';
        }
    }
}
</script>
@endpush
@endsection
