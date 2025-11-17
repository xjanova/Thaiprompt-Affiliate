@extends('layouts.admin-v3')

@section('title', 'Create New Page - Page Builder')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2">
            <a href="{{ route('admin.page-builder.index') }}" class="hover:text-blue-600">Page Builder</a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span>Create New Page</span>
        </div>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-white">Create New Page</h1>
    </div>

    <!-- Form -->
    <div class="max-w-4xl">
        <form action="{{ route('admin.page-builder.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-white mb-6">Page Information</h2>

                <!-- Page Name -->
                <div class="mb-6">
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        Page Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" required
                           value="{{ old('name') }}"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                           placeholder="e.g., Homepage, About Us, Wiki">
                    @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Slug -->
                <div class="mb-6">
                    <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        URL Slug
                    </label>
                    <div class="flex items-center">
                        <span class="text-gray-500 dark:text-gray-400 dark:text-gray-400 mr-2">/</span>
                        <input type="text" name="slug" id="slug"
                               value="{{ old('slug') }}"
                               class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="Leave empty to auto-generate from name">
                    </div>
                    @error('slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Page Type -->
                <div class="mb-6">
                    <label for="page_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                        Page Type <span class="text-red-500">*</span>
                    </label>
                    <select name="page_type" id="page_type" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                        <option value="">Select page type...</option>
                        @foreach($pageTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('page_type') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                    @error('page_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Active Status -->
                <div class="mb-6">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500">
                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300">
                            Activate this page immediately
                        </span>
                    </label>
                </div>

                <!-- SEO Meta Data (Optional) -->
                <div class="border-t border-gray-200 dark:border-gray-700 dark:border-gray-700 pt-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white dark:text-white mb-4">SEO Settings (Optional)</h3>

                    <div class="mb-4">
                        <label for="meta_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            Meta Title
                        </label>
                        <input type="text" name="meta_data[title]" id="meta_title"
                               value="{{ old('meta_data.title') }}"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="SEO title for search engines">
                    </div>

                    <div class="mb-4">
                        <label for="meta_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                            Meta Description
                        </label>
                        <textarea name="meta_data[description]" id="meta_description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                  placeholder="Brief description for search engines">{{ old('meta_data.description') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('admin.page-builder.index') }}"
                   class="px-6 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 dark:text-gray-300 hover:bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:hover:bg-gray-700">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl hover:shadow-lg transition-all duration-300">
                    Create Page & Start Building
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function(e) {
    const slug = e.target.value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    if (!document.getElementById('slug').value || document.getElementById('slug')._autoGenerated) {
        document.getElementById('slug').value = slug;
        document.getElementById('slug')._autoGenerated = true;
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this._autoGenerated = false;
});
</script>
@endpush
@endsection
