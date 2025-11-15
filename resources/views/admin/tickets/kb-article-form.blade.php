@extends('layouts.admin')

@section('title', isset($article) ? 'Edit Article' : 'Create Article')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Alpine.js Language Switcher -->
        <div x-data="{ open: false, currentLang: '🇹🇭 ไทย' }" class="flex justify-end mb-6">
            <div class="relative">
                <button @click="open = !open"
                        class="flex items-center space-x-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all border border-gray-200 dark:border-gray-700">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="currentLang"></span>
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-2 z-50">
                    <a href="#" @click.prevent="currentLang = '🇹🇭 ไทย'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">🇹🇭 ไทย</a>
                    <a href="#" @click.prevent="currentLang = '🇬🇧 English'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">🇬🇧 English</a>
                    <a href="#" @click.prevent="currentLang = '🇨🇳 中文'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">🇨🇳 中文</a>
                    <a href="#" @click.prevent="currentLang = '🇯🇵 日本語'; open = false"
                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-gray-700">🇯🇵 日本語</a>
                </div>
            </div>
        </div>

        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-pink-600 to-rose-600 rounded-3xl shadow-2xl p-8 mb-8">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mr-4">
                        <svg class="w-9 h-9 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-white mb-2" data-translate>
                            {{ isset($article) ? 'แก้ไขบทความ' : 'สร้างบทความใหม่' }}
                        </h1>
                        <p class="text-purple-100" data-translate>Knowledge Base Article</p>
                    </div>
                </div>
                <a href="{{ route('admin.tickets.kb-articles.index') }}"
                   class="px-6 py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 rounded-xl transition-all text-white font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    <span data-translate>กลับ</span>
                </a>
            </div>
        </div>

        <!-- Main Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 p-8">
            <form action="{{ isset($article) ? route('admin.tickets.kb-articles.update', $article->id) : route('admin.tickets.kb-articles.store') }}" method="POST" class="space-y-6">
                @csrf
                @if(isset($article))
                    @method('PUT')
                @endif

                <!-- Title -->
                <div>
                    <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                        </div>
                        <span data-translate>หัวข้อ</span>
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <input type="text" name="title" id="title"
                           class="w-full px-5 py-4 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all @error('title') border-red-500 @enderror"
                           value="{{ old('title', $article->title ?? '') }}" required>
                    @error('title')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Content -->
                <div>
                    <label class="flex items-center text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <span data-translate>เนื้อหา</span>
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <textarea name="content" id="content" rows="15"
                              class="w-full px-5 py-4 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all font-mono text-sm @error('content') border-red-500 @enderror"
                              required>{{ old('content', $article->content ?? '') }}</textarea>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" data-translate>
                        รองรับ Markdown: **bold**, *italic*, [links](url)
                    </p>
                    @error('content')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Category & Tags Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3" data-translate>หมวดหมู่</label>
                        <select name="category_id" id="category_id"
                                class="w-full px-5 py-4 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all">
                            <option value="">เลือกหมวดหมู่</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id', $article->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3" data-translate>แท็ก (คั่นด้วยคอมม่า)</label>
                        <input type="text" name="tags" id="tags"
                               class="w-full px-5 py-4 rounded-xl border-2 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all"
                               value="{{ old('tags', isset($article) && $article->tags ? implode(', ', $article->tags) : '') }}"
                               placeholder="password, login, account">
                    </div>
                </div>

                <!-- Public Checkbox -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-5">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_public" id="is_public" value="1"
                               {{ old('is_public', $article->is_public ?? true) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-2 border-green-500 text-green-600 focus:ring-4 focus:ring-green-500/30">
                        <span class="ml-3 text-gray-900 dark:text-gray-100 font-medium" data-translate>
                            เปิดเผยต่อสาธารณะ (ทุกคนเห็นได้)
                        </span>
                    </label>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ route('admin.tickets.kb-articles.index') }}"
                       class="px-8 py-3 border-2 border-gray-300 dark:border-gray-600 rounded-xl text-gray-700 dark:text-gray-300 font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
                       data-translate>
                        ยกเลิก
                    </a>
                    <button type="submit"
                            class="relative overflow-hidden group px-8 py-3 bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-2xl transition-all transform hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-700 via-pink-700 to-rose-700 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="relative flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                            </svg>
                            <span data-translate>{{ isset($article) ? 'อัพเดทบทความ' : 'สร้างบทความ' }}</span>
                        </div>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<!-- Alpine.js -->
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection

@push('scripts')
<script>
// Convert tags to array
document.querySelector('form').addEventListener('submit', function(e) {
    const tagsInput = document.getElementById('tags');
    if (tagsInput.value) {
        const tagsArray = tagsInput.value.split(',').map(tag => tag.trim()).filter(tag => tag);
        tagsArray.forEach((tag, index) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `tags[${index}]`;
            input.value = tag;
            this.appendChild(input);
        });
        tagsInput.removeAttribute('name');
    }
});
</script>
@endpush
