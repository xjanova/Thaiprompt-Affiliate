@extends('layouts.admin-v3')

@section('title', __('Icon Manager'))

@section('content')
<div class="container mx-auto px-4 py-6" x-data="iconManager()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('Icon Manager') }}</h1>
                <p class="text-gray-600 mt-1">{{ __('Manage system and custom icons') }}</p>
            </div>
            <div class="flex gap-3">
                <button @click="$refs.uploadFile.click()" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    {{ __('Upload Icon') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Category Tabs -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex gap-4 px-6" aria-label="Tabs">
                @foreach($categories as $key => $label)
                    <a href="{{ route('admin.icons.index', ['category' => $key]) }}"
                       class="py-4 px-1 border-b-2 font-medium text-sm {{ $category === $key ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $label }}
                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $category === $key ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600' }}">
                            {{ count(\App\Helpers\IconHelper::list($key)) }}
                        </span>
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Icon Grid -->
        <div class="p-6">
            @if(empty($icons))
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No icons') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Upload your first icon to get started.') }}</p>
                </div>
            @else
                <div class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 gap-4">
                    @foreach($icons as $icon)
                        <div class="group relative border rounded-lg p-3 hover:shadow-lg transition-shadow bg-gray-50 hover:bg-white"
                             x-data="{ showActions: false }"
                             @mouseenter="showActions = true"
                             @mouseleave="showActions = false">

                            <!-- Icon Preview -->
                            <div class="aspect-square flex items-center justify-center mb-2">
                                @if($icon['extension'] === 'svg')
                                    <x-icon :name="$icon['name']" :category="$category" type="inline" size="2xl" />
                                @else
                                    <img src="{{ $icon['url'] }}" alt="{{ $icon['name'] }}" class="max-w-full max-h-full object-contain">
                                @endif
                            </div>

                            <!-- Icon Name -->
                            <div class="text-xs text-center text-gray-700 font-medium truncate" title="{{ $icon['name'] }}">
                                {{ $icon['name'] }}
                            </div>

                            <!-- File Extension Badge -->
                            <div class="text-center mt-1">
                                <span class="inline-block px-2 py-0.5 text-xs rounded bg-gray-200 text-gray-700">
                                    {{ strtoupper($icon['extension']) }}
                                </span>
                            </div>

                            <!-- Actions (shown on hover) -->
                            <div x-show="showActions"
                                 x-transition
                                 class="absolute top-2 right-2 flex gap-1">
                                <button @click="copyUrl('{{ $icon['url'] }}')"
                                        class="p-1.5 bg-white rounded shadow-lg hover:bg-gray-100"
                                        title="{{ __('Copy URL') }}">
                                    <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>

                                @if($category === 'custom' || auth()->user()->hasRole('super-admin'))
                                    <button @click="deleteIcon('{{ $icon['filename'] }}')"
                                            class="p-1.5 bg-white rounded shadow-lg hover:bg-red-50"
                                            title="{{ __('Delete') }}">
                                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Usage Guide -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-blue-900 mb-2">{{ __('How to Use Icons') }}</h3>
                <div class="text-sm text-blue-800 space-y-2">
                    <p><strong>{{ __('In Blade Templates:') }}</strong></p>
                    <pre class="bg-blue-100 p-2 rounded text-xs overflow-x-auto"><code>&lt;x-icon name="icon-name" category="system" size="md" /&gt;</code></pre>

                    <p class="mt-3"><strong>{{ __('Using Helper:') }}</strong></p>
                    <pre class="bg-blue-100 p-2 rounded text-xs overflow-x-auto"><code>&lt;img src="@{{ App\Helpers\IconHelper::url('icon-name', 'system') }}" /&gt;</code></pre>

                    <p class="mt-3"><strong>{{ __('Inline SVG:') }}</strong></p>
                    <pre class="bg-blue-100 p-2 rounded text-xs overflow-x-auto"><code>@{!! App\Helpers\IconHelper::inline('icon-name', 'system') !!}</code></pre>

                    <p class="mt-3"><strong>{{ __('Supported Formats:') }}</strong> SVG, PNG, JPG, WebP</p>
                    <p><strong>{{ __('Max Size:') }}</strong> 2MB</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Upload File Input -->
    <input type="file" x-ref="uploadFile" @change="uploadIcon($event)" accept=".svg,.png,.jpg,.jpeg,.webp" class="hidden">
</div>

@push('scripts')
<script>
function iconManager() {
    return {
        uploadIcon(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('icon', file);
            formData.append('category', '{{ $category }}');

            // Ask for custom name (optional)
            const customName = prompt('{{ __("Enter a name for this icon (optional):") }}');
            if (customName) {
                formData.append('name', customName);
            }

            fetch('/admin/icons/upload', {
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
                    alert(data.message || '{{ __("Failed to upload icon") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __("An error occurred") }}');
            });

            // Reset file input
            event.target.value = '';
        },

        copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                this.showMessage('{{ __("URL copied to clipboard!") }}', 'success');
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        },

        deleteIcon(filename) {
            if (!confirm('{{ __("Are you sure you want to delete this icon?") }}')) {
                return;
            }

            fetch('/admin/icons', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    name: filename,
                    category: '{{ $category }}'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || '{{ __("Failed to delete icon") }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __("An error occurred") }}');
            });
        },

        showMessage(message, type = 'success') {
            const div = document.createElement('div');
            div.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
            div.textContent = message;
            document.body.appendChild(div);

            setTimeout(() => {
                div.style.opacity = '0';
                div.style.transition = 'opacity 0.3s';
                setTimeout(() => div.remove(), 300);
            }, 3000);
        }
    }
}
</script>
@endpush
@endsection
