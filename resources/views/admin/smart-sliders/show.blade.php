@extends('layouts.admin-v3')

@section('title', $smartSlider->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-purple-50 to-pink-50 dark:from-slate-900 dark:via-purple-900/20 dark:to-pink-900/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.smart-sliders.index') }}"
                       class="p-2 hover:glass-fusion dark:hover:bg-slate-800 rounded-xl transition-colors">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-purple-600 via-pink-600 to-rose-600 dark:from-purple-400 dark:via-pink-400 dark:to-rose-400">
                                {{ $smartSlider->name }}
                            </h1>
                        </div>
                        @if($smartSlider->description)
                        <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400 ml-16">{{ $smartSlider->description }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.smart-sliders.edit', $smartSlider) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        แก้ไข Slider
                    </a>
                    <form action="{{ route('admin.smart-sliders.destroy', $smartSlider) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบ Slider นี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            ลบ
                        </button>
                    </form>
                </div>
            </div>

            {{-- Status Badge --}}
            <div class="flex items-center gap-3 ml-16">
                @if($smartSlider->is_published)
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-xl font-semibold">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    เผยแพร่แล้ว
                </span>
                @else
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100/50 dark:bg-gray-800/50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 dark:text-gray-400 rounded-xl font-semibold">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"></path>
                    </svg>
                    ฉบับร่าง
                </span>
                @endif
                <span class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-xl font-semibold capitalize">
                    {{ $smartSlider->type }}
                </span>
                <span class="px-4 py-2 bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400 rounded-xl font-semibold">
                    {{ $smartSlider->width }} × {{ $smartSlider->height }}
                </span>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-purple-500 transform hover:scale-105 transition-transform" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 font-medium">สลายด์ทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $smartSlider->slides->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-pink-500 transform hover:scale-105 transition-transform" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 font-medium">Layers</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">
                            {{ $smartSlider->slides->sum(function($slide) { return $slide->layers->count(); }) }}
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-pink-100 to-rose-100 dark:from-pink-900/30 dark:to-rose-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-blue-500 transform hover:scale-105 transition-transform" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 font-medium">การดู</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-orange-500 transform hover:scale-105 transition-transform" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 font-medium">คลิก</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">0</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Slider Preview --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
                    <div class="p-6 bg-gradient-to-r from-purple-500 to-pink-500">
                        <h2 class="text-2xl font-bold text-white">ตัวอย่าง Slider</h2>
                        <p class="text-purple-100 mt-1">{{ $smartSlider->width }}px × {{ $smartSlider->height }}px</p>
                    </div>
                    <div class="p-8 bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 dark:bg-slate-900">
                        <div class="relative mx-auto glass-fusion dark:bg-slate-800 rounded-xl shadow-2xl overflow-hidden" border border-white/20 dark:border-white/10
                             style="max-width: {{ $smartSlider->width }}px; aspect-ratio: {{ $smartSlider->width }}/{{ $smartSlider->height }};">
                            @if($smartSlider->slides->count() > 0)
                                @php $firstSlide = $smartSlider->slides->first(); @endphp
                                @if(!empty($firstSlide->background['image']))
                                    <img src="{{ asset('storage/' . $firstSlide->background['image']) }}"
                                         alt="Preview"
                                         class="w-full h-full object-cover">
                                @elseif(!empty($firstSlide->background['color']))
                                    <div class="w-full h-full"
                                         style="background: {{ $firstSlide->background['color'] }};">
                                    </div>
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-purple-500 via-pink-500 to-rose-500"></div>
                                @endif

                                {{-- Preview Layers --}}
                                @foreach($firstSlide->layers as $layer)
                                    <div class="absolute" style="{{ $layer->position ? 'left:' . ($layer->position['left'] ?? 0) . 'px; top:' . ($layer->position['top'] ?? 0) . 'px;' : '' }}">
                                        @if($layer->type === 'text')
                                            <div style="{{ $layer->style ? 'font-size:' . ($layer->style['fontSize'] ?? '16') . 'px; color:' . ($layer->style['color'] ?? '#000') . ';' : '' }}">
                                                {{ $layer->content }}
                                            </div>
                                        @elseif($layer->type === 'image')
                                            <img src="{{ $layer->content }}" alt="Layer">
                                        @endif
                                    </div>
                                @endforeach

                                <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-black/60 to-transparent">
                                    @if($firstSlide->title)
                                        <h3 class="text-2xl font-bold text-white mb-2">{{ $firstSlide->title }}</h3>
                                    @endif
                                    @if($firstSlide->description)
                                        <p class="text-white/90">{{ $firstSlide->description }}</p>
                                    @endif
                                </div>
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/20 dark:to-pink-900/20 flex items-center justify-center">
                                    <div class="text-center">
                                        <svg class="w-24 h-24 mx-auto text-purple-300 dark:text-purple-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400">ยังไม่มีสลายด์</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Slides List --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-8" border border-white/20 dark:border-white/10>
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">สลายด์ทั้งหมด</h2>
                        </div>
                        <span class="px-4 py-2 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-xl font-semibold">
                            {{ $smartSlider->slides->count() }} สลายด์
                        </span>
                    </div>

                    @if($smartSlider->slides->count() > 0)
                        <div class="space-y-4">
                            @foreach($smartSlider->slides->sortBy('order') as $index => $slide)
                            <div class="group relative bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-4 hover:shadow-lg transition-all">
                                <div class="flex items-center gap-4">
                                    {{-- Slide Number --}}
                                    <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                        {{ $index + 1 }}
                                    </div>

                                    {{-- Thumbnail --}}
                                    <div class="flex-shrink-0 w-32 h-20 bg-gray-200 dark:bg-gray-700 dark:bg-slate-700 rounded-xl overflow-hidden">
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

                                    {{-- Slide Info --}}
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                                            {{ $slide->title ?? 'สลายด์ที่ ' . ($index + 1) }}
                                        </h3>
                                        @if($slide->description)
                                            <p class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400 truncate">{{ $slide->description }}</p>
                                        @endif
                                        <div class="flex items-center gap-3 mt-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                                {{ $slide->layers->count() }} layers
                                            </span>
                                            @if($slide->duration)
                                                <span class="text-xs text-gray-500 dark:text-gray-400 dark:text-gray-400">
                                                    {{ $slide->duration }}ms
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button class="p-2 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-purple-300 dark:text-purple-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 dark:text-gray-400 mb-4">ยังไม่มีสลายด์</p>
                            <a href="{{ route('admin.smart-sliders.edit', $smartSlider) }}"
                               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl transition-colors">
                                เริ่มสร้างสลายด์
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Settings Card --}}
                <div class="bg-gradient-to-br from-purple-500 via-pink-500 to-rose-500 rounded-2xl shadow-2xl p-8 text-white">
                    <h3 class="text-2xl font-bold mb-6">การตั้งค่า</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="font-medium">ประเภท</span>
                            <span class="px-3 py-1 glass-fusion rounded-xl font-semibold capitalize">
                                {{ $smartSlider->type }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="font-medium">ขนาด</span>
                            <span class="px-3 py-1 glass-fusion rounded-xl font-semibold">
                                {{ $smartSlider->width }} × {{ $smartSlider->height }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 glass-fusion backdrop-blur-sm rounded-xl" border border-white/20 dark:border-white/10>
                            <span class="font-medium">Responsive</span>
                            <span class="px-3 py-1 glass-fusion rounded-xl font-semibold capitalize">
                                {{ $smartSlider->responsive_mode }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <a href="{{ route('admin.smart-sliders.edit', $smartSlider) }}"
                           class="block w-full px-6 py-3 glass-fusion text-purple-600 font-semibold rounded-xl text-center hover:bg-purple-50 transition-colors">
                            แก้ไข Slider
                        </a>
                        <a href="{{ route('admin.smart-sliders.export', $smartSlider) }}"
                           class="block w-full px-6 py-3 glass-fusion backdrop-blur-sm text-white font-semibold rounded-xl text-center hover:glass-fusion transition-colors">
                            ส่งออก JSON
                        </a>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">การดำเนินการ</h3>
                    <div class="space-y-2">
                        <form action="{{ route('admin.smart-sliders.toggle-publish', $smartSlider) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-3 {{ $smartSlider->is_published ? 'bg-gray-100/50 dark:bg-gray-800/50 dark:bg-slate-700 text-gray-700 dark:text-gray-300 dark:text-gray-300' : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' }} rounded-xl font-semibold hover:shadow transition-all text-left">
                                {{ $smartSlider->is_published ? '🔒 ซ่อน Slider' : '✅ เผยแพร่ Slider' }}
                            </button>
                        </form>

                        <form action="{{ route('admin.smart-sliders.duplicate', $smartSlider) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full px-4 py-3 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-xl font-semibold hover:shadow transition-all text-left">
                                📋 ทำสำเนา
                            </button>
                        </form>

                        <a href="{{ route('admin.smart-sliders.analytics', $smartSlider) }}"
                           class="block w-full px-4 py-3 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 rounded-xl font-semibold hover:shadow transition-all text-left">
                            📊 ดูสถิติ
                        </a>
                    </div>
                </div>

                {{-- Meta Info --}}
                <div class="glass-fusion dark:bg-slate-800 rounded-2xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูล</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400 dark:text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>สร้าง: {{ $smartSlider->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400 dark:text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>อัปเดต: {{ $smartSlider->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Animation
document.addEventListener('DOMContentLoaded', function() {
    gsap.from('.grid > div', {
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
