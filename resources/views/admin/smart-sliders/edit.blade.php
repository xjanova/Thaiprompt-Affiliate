@extends('layouts.admin-v3')

@section('title', 'แก้ไข: ' . $smartSlider->name)

@push('styles')
<style>
    .slide-item {
        animation: slideIn 0.3s ease-out;
    }
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    .layer-handle {
        cursor: move;
    }
    .transition-effect-preview {
        transition: all 0.5s ease-in-out;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-purple-50 to-pink-50 dark:from-slate-900 dark:via-purple-900/20 dark:to-pink-900/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.smart-sliders.show', $smartSlider) }}"
                       class="p-2 hover:glass-fusion dark:hover:bg-slate-800 rounded-xl transition-colors">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </div>
                            <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 dark:from-purple-400 dark:via-pink-400 dark:to-rose-400">
                                แก้ไข Slider
                            </h1>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 ml-16">{{ $smartSlider->name }}</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button"
                            onclick="previewSlider()"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        ดูตัวอย่าง
                    </button>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.smart-sliders.update', $smartSlider) }}" method="POST" x-data="sliderEditor()">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Main Editor Area --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Basic Settings --}}
                    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-8" border border-white/20 dark:border-white/10>
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">การตั้งค่าพื้นฐาน</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    ชื่อ Slider <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       name="name"
                                       value="{{ old('name', $smartSlider->name) }}"
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white">
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    คำอธิบาย
                                </label>
                                <textarea name="description"
                                          rows="2"
                                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white">{{ old('description', $smartSlider->description) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    ประเภท <span class="text-red-500">*</span>
                                </label>
                                <select name="type"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white">
                                    <option value="simple" {{ old('type', $smartSlider->type) == 'simple' ? 'selected' : '' }}>Simple</option>
                                    <option value="block" {{ old('type', $smartSlider->type) == 'block' ? 'selected' : '' }}>Block</option>
                                    <option value="carousel" {{ old('type', $smartSlider->type) == 'carousel' ? 'selected' : '' }}>Carousel</option>
                                    <option value="showcase" {{ old('type', $smartSlider->type) == 'showcase' ? 'selected' : '' }}>Showcase</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    โหมด Responsive <span class="text-red-500">*</span>
                                </label>
                                <select name="responsive_mode"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white">
                                    <option value="auto" {{ old('responsive_mode', $smartSlider->responsive_mode) == 'auto' ? 'selected' : '' }}>Auto</option>
                                    <option value="fullwidth" {{ old('responsive_mode', $smartSlider->responsive_mode) == 'fullwidth' ? 'selected' : '' }}>Full Width</option>
                                    <option value="boxed" {{ old('responsive_mode', $smartSlider->responsive_mode) == 'boxed' ? 'selected' : '' }}>Boxed</option>
                                    <option value="fullpage" {{ old('responsive_mode', $smartSlider->responsive_mode) == 'fullpage' ? 'selected' : '' }}>Full Page</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    ความกว้าง (px) <span class="text-red-500">*</span>
                                </label>
                                <input type="number"
                                       name="width"
                                       value="{{ old('width', $smartSlider->width) }}"
                                       required
                                       min="100"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">
                                    ความสูง (px) <span class="text-red-500">*</span>
                                </label>
                                <input type="number"
                                       name="height"
                                       value="{{ old('height', $smartSlider->height) }}"
                                       required
                                       min="100"
                                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-purple-500 dark:bg-slate-700 dark:text-white">
                            </div>
                        </div>
                    </div>

                    {{-- Slides Management --}}
                    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-8" border border-white/20 dark:border-white/10>
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการสลายด์</h2>
                            </div>
                            <span class="px-4 py-2 bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400 rounded-xl font-semibold">
                                {{ $smartSlider->slides->count() }} สลายด์
                            </span>
                        </div>

                        @if($smartSlider->slides->count() > 0)
                            <div class="space-y-4 mb-6">
                                @foreach($smartSlider->slides->sortBy('order') as $index => $slide)
                                <div class="group relative bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 hover:shadow-lg transition-all slide-item">
                                    {{-- Drag Handle --}}
                                    <div class="absolute left-2 top-1/2 -translate-y-1/2 layer-handle opacity-0 group-hover:opacity-100 transition-opacity">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                        </svg>
                                    </div>

                                    <div class="flex items-start gap-4 ml-6">
                                        {{-- Slide Number --}}
                                        <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                            {{ $index + 1 }}
                                        </div>

                                        {{-- Thumbnail --}}
                                        <div class="flex-shrink-0 w-40 h-24 bg-gray-200 dark:bg-gray-700 dark:bg-slate-700 rounded-xl overflow-hidden">
                                            @if(!empty($slide->background['image']))
                                                <img src="{{ asset('storage/' . $slide->background['image']) }}"
                                                     alt="{{ $slide->title }}"
                                                     class="w-full h-full object-cover">
                                            @elseif(!empty($slide->background['color']))
                                                <div class="w-full h-full" style="background: {{ $slide->background['color'] }}"></div>
                                            @else
                                                <div class="w-full h-full bg-gradient-to-br from-purple-400 to-pink-400"></div>
                                            @endif
                                        </div>

                                        {{-- Slide Details --}}
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                                {{ $slide->title ?? 'สลายด์ที่ ' . ($index + 1) }}
                                            </h3>
                                            @if($slide->description)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 mb-3 line-clamp-2">{{ $slide->description }}</p>
                                            @endif

                                            <div class="flex flex-wrap gap-2">
                                                <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-sm rounded-full">
                                                    {{ $slide->layers->count() }} layers
                                                </span>
                                                @if($slide->duration)
                                                    <span class="px-3 py-1 bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400 text-sm rounded-full">
                                                        {{ $slide->duration }}ms
                                                    </span>
                                                @endif
                                                @if($slide->link_url)
                                                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-sm rounded-full">
                                                        มีลิงก์
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Layer Preview --}}
                                            @if($slide->layers->count() > 0)
                                                <div class="mt-4">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-2">Layers:</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        @foreach($slide->layers->sortBy('order') as $layer)
                                                            <span class="px-2 py-1 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-700 text-gray-600 dark:text-gray-400 dark:text-gray-300 text-xs rounded">
                                                                @if($layer->type === 'text')
                                                                    📝 {{ Str::limit($layer->content, 20) }}
                                                                @elseif($layer->type === 'image')
                                                                    🖼️ รูปภาพ
                                                                @elseif($layer->type === 'button')
                                                                    🔘 ปุ่ม
                                                                @else
                                                                    {{ $layer->type }}
                                                                @endif
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex-shrink-0 flex flex-col gap-2">
                                            <button type="button"
                                                    class="p-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition-colors"
                                                    title="แก้ไข">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </button>
                                            <button type="button"
                                                    class="p-2 bg-pink-600 hover:bg-pink-700 text-white rounded-xl transition-colors"
                                                    title="ทำสำเนา">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                            </button>
                                            <button type="button"
                                                    class="p-2 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors"
                                                    title="ลบ">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12 mb-6">
                                <svg class="w-16 h-16 mx-auto text-purple-300 dark:text-purple-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400">ยังไม่มีสลายด์</p>
                            </div>
                        @endif

                        <button type="button"
                                onclick="addSlide()"
                                class="w-full px-6 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            เพิ่มสลายด์ใหม่
                        </button>
                    </div>

                    {{-- Animation & Effects --}}
                    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-8" border border-white/20 dark:border-white/10>
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">เอฟเฟกต์และแอนิเมชัน</h2>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <button type="button"
                                    class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl hover:shadow-lg transition-all text-left">
                                <div class="text-3xl mb-2">⬅️</div>
                                <div class="font-semibold text-gray-900 dark:text-white">Slide Left</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">เลื่อนจากซ้าย</div>
                            </button>

                            <button type="button"
                                    class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl hover:shadow-lg transition-all text-left">
                                <div class="text-3xl mb-2">➡️</div>
                                <div class="font-semibold text-gray-900 dark:text-white">Slide Right</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">เลื่อนจากขวา</div>
                            </button>

                            <button type="button"
                                    class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl hover:shadow-lg transition-all text-left">
                                <div class="text-3xl mb-2">⬆️</div>
                                <div class="font-semibold text-gray-900 dark:text-white">Slide Up</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">เลื่อนขึ้น</div>
                            </button>

                            <button type="button"
                                    class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl hover:shadow-lg transition-all text-left">
                                <div class="text-3xl mb-2">🌀</div>
                                <div class="font-semibold text-gray-900 dark:text-white">Fade</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">จางหาย</div>
                            </button>

                            <button type="button"
                                    class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl hover:shadow-lg transition-all text-left">
                                <div class="text-3xl mb-2">🔄</div>
                                <div class="font-semibold text-gray-900 dark:text-white">Zoom</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">ซูมเข้า/ออก</div>
                            </button>

                            <button type="button"
                                    class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl hover:shadow-lg transition-all text-left">
                                <div class="text-3xl mb-2">💫</div>
                                <div class="font-semibold text-gray-900 dark:text-white">Ken Burns</div>
                                <div class="text-xs text-gray-600 dark:text-gray-400 dark:text-gray-400">เคลื่อนไหว</div>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Sidebar --}}
                <div class="space-y-6">
                    {{-- Quick Actions --}}
                    <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ตัวเลือก</h3>
                        <label class="flex items-center gap-3 p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl cursor-pointer hover:shadow transition-all">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $smartSlider->is_published) ? 'checked' : '' }} class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                            <span class="text-gray-700 dark:text-gray-300 dark:text-gray-300 font-medium">เผยแพร่ Slider</span>
                        </label>
                    </div>

                    {{-- Settings Preview --}}
                    <div class="bg-gradient-to-br from-purple-500 via-pink-500 to-rose-500 rounded-2xl shadow-2xl p-8 text-white">
                        <h3 class="text-2xl font-bold mb-6">ตัวอย่างการตั้งค่า</h3>
                        <div class="space-y-4">
                            <div class="p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                                <p class="text-sm text-purple-100 mb-1">ประเภท</p>
                                <p class="font-semibold capitalize">{{ $smartSlider->type }}</p>
                            </div>
                            <div class="p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                                <p class="text-sm text-purple-100 mb-1">ขนาด</p>
                                <p class="font-semibold">{{ $smartSlider->width }} × {{ $smartSlider->height }}px</p>
                            </div>
                            <div class="p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                                <p class="text-sm text-purple-100 mb-1">จำนวนสลายด์</p>
                                <p class="font-semibold">{{ $smartSlider->slides->count() }} สลายด์</p>
                            </div>
                        </div>
                    </div>

                    {{-- Help Card --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100">คำแนะนำ</h3>
                        </div>
                        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>ลากสลายด์เพื่อจัดเรียง</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>แต่ละสลายด์สามารถมีหลาย Layer</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>ใช้ Animation เพื่อความน่าสนใจ</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Submit Buttons --}}
            <div class="flex gap-4 mt-8">
                <button type="submit"
                        class="flex-1 px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    บันทึกการเปลี่ยนแปลง
                </button>
                <a href="{{ route('admin.smart-sliders.show', $smartSlider) }}"
                   class="px-8 py-4 bg-gray-300 dark:bg-slate-700 hover:bg-gray-400 dark:hover:bg-slate-600 text-gray-700 dark:text-gray-300 dark:text-gray-300 font-bold rounded-xl transition-colors">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function sliderEditor() {
    return {
        slides: [],
    }
}

function addSlide() {
    alert('ฟีเจอร์เพิ่มสลายด์จะพร้อมใช้งานเร็วๆ นี้!');
}

function previewSlider() {
    alert('ฟีเจอร์ดูตัวอย่างจะพร้อมใช้งานเร็วๆ นี้!');
}

// GSAP Animation
document.addEventListener('DOMContentLoaded', function() {
    gsap.from('.slide-item', {
        opacity: 0,
        y: 20,
        duration: 0.5,
        stagger: 0.1,
        ease: 'power2.out'
    });
});
</script>
@endpush
@endsection
