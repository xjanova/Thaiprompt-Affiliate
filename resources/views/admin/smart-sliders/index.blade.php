@extends('layouts.admin')

@section('title', 'Smart Slider Pro')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">
                        🎨 Smart Slider Pro
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">ระบบจัดการ Slider ที่ทรงพลังที่สุด - เหนือกว่า Smart Slider 3</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.smart-sliders.create') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        สร้าง Slider ใหม่
                    </a>
                    <button onclick="document.getElementById('import-modal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 border-2 border-gray-300 dark:border-slate-600 hover:border-blue-500 dark:hover:border-blue-500 text-gray-700 dark:text-gray-300 font-semibold rounded-xl shadow hover:shadow-lg transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        นำเข้า Slider
                    </button>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Sliders</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $sliders->total() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Published</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $sliders->where('is_published', true)->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Views</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($sliders->sum(fn($s) => $s->analytics_count ?? 0)) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total Slides</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $sliders->sum('slides_count') }}</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sliders Grid --}}
        @if($sliders->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($sliders as $slider)
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group">
                {{-- Thumbnail/Preview --}}
                <div class="relative h-48 bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 overflow-hidden">
                    @if($slider->slides->first())
                        @php $firstSlide = $slider->slides->first(); @endphp
                        @if(!empty($firstSlide->background['image']))
                            <img src="{{ asset('storage/' . $firstSlide->background['image']) }}"
                                 alt="{{ $slider->name }}"
                                 class="w-full h-full object-cover">
                        @endif
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

                    {{-- Status Badge --}}
                    <div class="absolute top-4 right-4">
                        @if($slider->is_published)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full shadow-lg">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Published
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-500 text-white text-xs font-semibold rounded-full shadow-lg">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"></path>
                            </svg>
                            Draft
                        </span>
                        @endif
                    </div>

                    {{-- Quick Preview Button --}}
                    <button onclick="previewSlider({{ $slider->id }})"
                            class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-black/40">
                        <span class="px-6 py-3 bg-white/20 backdrop-blur-sm border border-white/30 text-white font-semibold rounded-xl">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                            </svg>
                        </span>
                    </button>
                </div>

                {{-- Card Content --}}
                <div class="p-6">
                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1 truncate">{{ $slider->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $slider->alias }}</p>
                    </div>

                    {{-- Stats --}}
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="text-center p-2 bg-gray-50 dark:bg-slate-700 rounded-lg">
                            <p class="text-xs text-gray-600 dark:text-gray-400">Slides</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $slider->slides_count }}</p>
                        </div>
                        <div class="text-center p-2 bg-gray-50 dark:bg-slate-700 rounded-lg">
                            <p class="text-xs text-gray-600 dark:text-gray-400">Views</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ number_format($slider->analytics_count ?? 0) }}</p>
                        </div>
                        <div class="text-center p-2 bg-gray-50 dark:bg-slate-700 rounded-lg">
                            <p class="text-xs text-gray-600 dark:text-gray-400">Type</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white capitalize">{{ $slider->type }}</p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2">
                        <a href="{{ route('admin.smart-sliders.edit', $slider) }}"
                           class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            แก้ไข
                        </a>

                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open"
                                    class="px-4 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                </svg>
                            </button>

                            <div x-show="open"
                                 @click.away="open = false"
                                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-gray-200 dark:border-slate-700 py-1 z-10"
                                 style="display: none;">
                                <a href="{{ route('admin.smart-sliders.analytics', $slider) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700">
                                    📊 Analytics
                                </a>
                                <form action="{{ route('admin.smart-sliders.duplicate', $slider) }}" method="POST" class="block">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700">
                                        📋 Duplicate
                                    </button>
                                </form>
                                <a href="{{ route('admin.smart-sliders.export', $slider) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700">
                                    💾 Export
                                </a>
                                <form action="{{ route('admin.smart-sliders.toggle-publish', $slider) }}" method="POST" class="block">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700">
                                        {{ $slider->is_published ? '👁️ Hide' : '✅ Publish' }}
                                    </button>
                                </form>
                                <hr class="my-1 border-gray-200 dark:border-slate-700">
                                <form action="{{ route('admin.smart-sliders.destroy', $slider) }}" method="POST" class="block" onsubmit="return confirm('แน่ใจหรือไม่ที่จะลบ Slider นี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                        🗑️ Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $sliders->links() }}
        </div>
        @else
        {{-- Empty State --}}
        <div class="text-center py-16">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-full mb-6">
                <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มี Slider</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นสร้าง Slider แรกของคุณได้เลย!</p>
            <a href="{{ route('admin.smart-sliders.create') }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                สร้าง Slider ใหม่
            </a>
        </div>
        @endif
    </div>
</div>

{{-- Import Modal --}}
<div id="import-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">นำเข้า Slider</h3>
        <form action="{{ route('admin.smart-sliders.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">เลือกไฟล์ JSON</label>
                <input type="file" name="file" accept=".json" required
                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-slate-700 dark:text-white">
            </div>
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                    นำเข้า
                </button>
                <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                        class="px-4 py-2 bg-gray-300 dark:bg-slate-600 hover:bg-gray-400 dark:hover:bg-slate-500 text-gray-700 dark:text-gray-300 font-semibold rounded-lg transition-colors">
                    ยกเลิก
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function previewSlider(sliderId) {
    window.open(`/sliders/${sliderId}`, '_blank');
}
</script>
@endpush
@endsection
