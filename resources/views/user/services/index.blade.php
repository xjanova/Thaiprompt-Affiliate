{{--
    หน้าค้นหาบริการ (Service Discovery)
    ระดับ Enterprise - เทียบเท่า Grab/Lineman

    Features:
    - Search bar with autocomplete
    - Category quick-access
    - Featured services carousel
    - Filter by price, rating, distance
    - Sort options
    - Responsive design
    - Dark mode support
--}}
@extends('layouts.app')

@section('title', $pageTitle ?? 'ค้นหาบริการ')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"
     x-data="serviceDiscovery()">

    {{-- Hero Section with Search --}}
    <div class="relative bg-gradient-to-br from-purple-600 via-pink-600 to-orange-500 dark:from-purple-800 dark:via-pink-800 dark:to-orange-700 overflow-hidden">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                <defs>
                    <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                        <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect width="100" height="100" fill="url(#grid)"/>
            </svg>
        </div>

        {{-- Floating Icons Animation --}}
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 text-4xl opacity-20 animate-bounce" style="animation-delay: 0s;">💆</div>
            <div class="absolute top-32 right-20 text-4xl opacity-20 animate-bounce" style="animation-delay: 0.5s;">✂️</div>
            <div class="absolute bottom-20 left-20 text-4xl opacity-20 animate-bounce" style="animation-delay: 1s;">🔧</div>
            <div class="absolute bottom-10 right-10 text-4xl opacity-20 animate-bounce" style="animation-delay: 1.5s;">🧹</div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
            {{-- Hero Text --}}
            <div class="text-center mb-8">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white mb-4 drop-shadow-lg">
                    บริการถึงที่ ง่าย ครบ จบในแอพเดียว
                </h1>
                <p class="text-lg md:text-xl text-white/90 max-w-2xl mx-auto">
                    นวด ตัดผม ซ่อมแอร์ ทำความสะอาด สอนพิเศษ และอีกมากมาย
                    <br>มีผู้ให้บริการมืออาชีพพร้อมบริการคุณ
                </p>
            </div>

            {{-- Search Box --}}
            <div class="max-w-3xl mx-auto">
                <form action="{{ route('user.services.index') }}" method="GET" class="relative">
                    <div class="flex flex-col md:flex-row gap-3">
                        {{-- Search Input --}}
                        <div class="flex-1 relative">
                            <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-xl"></i>
                            <input type="text"
                                   name="q"
                                   value="{{ $filters['q'] ?? '' }}"
                                   placeholder="ค้นหาบริการ... เช่น นวด, ตัดผม, ล้างแอร์"
                                   autocomplete="off"
                                   x-model="searchQuery"
                                   @input.debounce.300ms="searchSuggestions()"
                                   @focus="showSuggestions = true"
                                   @keydown.escape="showSuggestions = false"
                                   class="w-full pl-12 pr-4 py-4 md:py-5 text-lg bg-white dark:bg-gray-800 border-0 rounded-xl md:rounded-l-xl md:rounded-r-none shadow-2xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-4 focus:ring-purple-300/50">

                            {{-- Search Suggestions Dropdown --}}
                            <div x-show="showSuggestions && suggestions.length > 0"
                                 x-cloak
                                 @click.away="showSuggestions = false"
                                 class="absolute top-full left-0 right-0 mt-2 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden z-50 border border-gray-200 dark:border-gray-700">
                                <template x-for="suggestion in suggestions" :key="suggestion.id">
                                    <a :href="suggestion.url"
                                       class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <span class="text-2xl" x-text="suggestion.icon"></span>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white" x-text="suggestion.name"></p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="suggestion.category"></p>
                                        </div>
                                        <span class="ml-auto font-bold text-purple-600 dark:text-purple-400" x-text="'฿' + suggestion.price"></span>
                                    </a>
                                </template>
                            </div>
                        </div>

                        {{-- Location Button (Optional) --}}
                        <button type="button"
                                @click="getCurrentLocation()"
                                :disabled="gettingLocation"
                                class="hidden md:flex items-center gap-2 px-6 py-4 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <i :class="gettingLocation ? 'fas fa-spinner fa-spin' : 'fas fa-crosshairs'" class="text-lg"></i>
                            <span class="hidden lg:inline" x-text="currentLocation ? 'ใกล้ฉัน' : 'หาตำแหน่ง'"></span>
                        </button>

                        {{-- Search Button --}}
                        <button type="submit"
                                class="px-8 py-4 md:py-5 bg-gradient-to-r from-orange-500 to-pink-500 hover:from-orange-600 hover:to-pink-600 text-white font-bold rounded-xl md:rounded-l-none md:rounded-r-xl shadow-lg hover:shadow-xl transition-all duration-200 text-lg">
                            <i class="fas fa-search mr-2"></i>
                            <span>ค้นหา</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Quick Stats --}}
            <div class="flex flex-wrap justify-center gap-6 mt-8 text-white/90">
                <div class="flex items-center gap-2">
                    <i class="fas fa-concierge-bell text-2xl"></i>
                    <span><strong>{{ number_format($stats['total_services'] ?? 0) }}</strong> บริการ</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-users text-2xl"></i>
                    <span><strong>{{ number_format($stats['total_providers'] ?? 0) }}</strong> ผู้ให้บริการ</span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle text-2xl"></i>
                    <span><strong>{{ number_format($stats['total_bookings'] ?? 0) }}</strong> การจอง</span>
                </div>
            </div>
        </div>

        {{-- Wave Bottom --}}
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 120" class="w-full h-16 md:h-24">
                <path fill="currentColor" class="text-gray-50 dark:text-gray-900"
                      d="M0,64L48,69.3C96,75,192,85,288,80C384,75,480,53,576,48C672,43,768,53,864,64C960,75,1056,85,1152,80C1248,75,1344,53,1392,42.7L1440,32L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z">
                </path>
            </svg>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Category Quick Access --}}
        <div class="mb-10">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-th-large mr-2 text-purple-600"></i>
                    หมวดหมู่บริการ
                </h2>
                <a href="#" class="text-purple-600 dark:text-purple-400 hover:underline text-sm font-semibold">
                    ดูทั้งหมด <i class="fas fa-chevron-right ml-1"></i>
                </a>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
                @foreach($categories->take(16) as $category)
                <a href="{{ route('user.services.index', ['category' => $category->id]) }}"
                   class="group flex flex-col items-center p-4 rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600 hover:shadow-xl transition-all duration-200 hover:-translate-y-1 {{ ($filters['category'] ?? null) == $category->id ? 'ring-2 ring-purple-500 border-purple-500' : '' }}">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl mb-2 group-hover:scale-110 transition-transform"
                         style="background: {{ $category->color }}15;">
                        {{ $category->icon ?? '📦' }}
                    </div>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 text-center line-clamp-2">{{ $category->name }}</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">({{ $category->services_count ?? 0 }})</span>
                </a>
                @endforeach
            </div>
        </div>

        {{-- Featured Services Carousel --}}
        @if($featuredServices->count() > 0)
        <div class="mb-10">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-star mr-2 text-yellow-500"></i>
                    บริการแนะนำ
                </h2>
            </div>

            <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide">
                @foreach($featuredServices as $service)
                <a href="{{ route('user.services.show', $service) }}"
                   class="snap-start flex-shrink-0 w-72 md:w-80 rounded-2xl overflow-hidden bg-white dark:bg-gray-800 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 group border border-gray-100 dark:border-gray-700">
                    {{-- Image --}}
                    <div class="relative h-40 overflow-hidden bg-gradient-to-br from-purple-400/20 to-pink-400/20">
                        @if($service->image_path)
                            <img src="{{ asset('storage/' . $service->image_path) }}"
                                 alt="{{ $service->name }}"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-6xl">
                                {{ $service->category->icon ?? '🔧' }}
                            </div>
                        @endif

                        {{-- Featured Badge --}}
                        <div class="absolute top-3 left-3">
                            <span class="px-3 py-1 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold rounded-full shadow-lg">
                                <i class="fas fa-star mr-1"></i> แนะนำ
                            </span>
                        </div>

                        {{-- Price Badge --}}
                        <div class="absolute bottom-3 right-3">
                            <span class="px-3 py-1 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-full shadow-lg font-bold text-purple-600 dark:text-purple-400">
                                เริ่ม ฿{{ number_format($service->base_price, 0) }}
                            </span>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors line-clamp-2">
                                {{ $service->name }}
                            </h3>
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-3">
                            {{ $service->description }}
                        </p>

                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-full"
                                  style="color: {{ $service->category->color ?? '#666' }}">
                                {{ $service->category->icon ?? '' }} {{ $service->category->name ?? '' }}
                            </span>
                            <div class="flex items-center gap-1 text-yellow-500">
                                <i class="fas fa-star text-sm"></i>
                                <span class="font-semibold text-sm">{{ number_format($service->average_rating ?? 0, 1) }}</span>
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Filters & Sort --}}
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-lg p-4 md:p-6 mb-6 sticky top-0 z-40">
            <form action="{{ route('user.services.index') }}" method="GET">
                <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">

                <div class="flex flex-wrap items-center gap-3">
                    {{-- Category Filter --}}
                    <div class="relative">
                        <select name="category"
                                @change="$el.form.submit()"
                                class="appearance-none px-4 py-2.5 pr-10 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-purple-500">
                            <option value="">ทุกหมวดหมู่</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ ($filters['category'] ?? '') == $category->id ? 'selected' : '' }}>
                                    {{ $category->icon }} {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>

                    {{-- Price Range --}}
                    <div class="relative">
                        <button type="button"
                                @click="showPriceFilter = !showPriceFilter"
                                class="flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:border-purple-400 transition-colors">
                            <i class="fas fa-baht-sign"></i>
                            <span x-text="priceFilterLabel">ช่วงราคา</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>

                        {{-- Price Dropdown --}}
                        <div x-show="showPriceFilter"
                             x-cloak
                             @click.away="showPriceFilter = false"
                             class="absolute top-full left-0 mt-2 w-64 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 z-50">
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">ราคาต่ำสุด</label>
                                    <input type="number" name="min_price" value="{{ $filters['min_price'] ?? '' }}"
                                           class="w-full mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm"
                                           placeholder="฿0">
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">ราคาสูงสุด</label>
                                    <input type="number" name="max_price" value="{{ $filters['max_price'] ?? '' }}"
                                           class="w-full mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm"
                                           placeholder="฿∞">
                                </div>
                                <button type="submit" class="w-full py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium text-sm transition-colors">
                                    ใช้ตัวกรอง
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Rating Filter --}}
                    <div class="relative">
                        <select name="min_rating"
                                @change="$el.form.submit()"
                                class="appearance-none px-4 py-2.5 pr-10 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-purple-500">
                            <option value="">คะแนนทั้งหมด</option>
                            <option value="4.5" {{ ($filters['min_rating'] ?? '') == '4.5' ? 'selected' : '' }}>⭐ 4.5+</option>
                            <option value="4" {{ ($filters['min_rating'] ?? '') == '4' ? 'selected' : '' }}>⭐ 4.0+</option>
                            <option value="3.5" {{ ($filters['min_rating'] ?? '') == '3.5' ? 'selected' : '' }}>⭐ 3.5+</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>

                    {{-- Spacer --}}
                    <div class="flex-1"></div>

                    {{-- Clear Filters --}}
                    @if(array_filter($filters ?? []))
                        <a href="{{ route('user.services.index') }}"
                           class="px-4 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                            <i class="fas fa-times mr-1"></i> ล้างตัวกรอง
                        </a>
                    @endif

                    {{-- Sort --}}
                    <div class="relative">
                        <select name="sort"
                                @change="$el.form.submit()"
                                class="appearance-none px-4 py-2.5 pr-10 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-purple-500">
                            <option value="featured" {{ ($filters['sort'] ?? 'featured') == 'featured' ? 'selected' : '' }}>แนะนำ</option>
                            <option value="popular" {{ ($filters['sort'] ?? '') == 'popular' ? 'selected' : '' }}>ยอดนิยม</option>
                            <option value="rating" {{ ($filters['sort'] ?? '') == 'rating' ? 'selected' : '' }}>คะแนนสูง</option>
                            <option value="price_low" {{ ($filters['sort'] ?? '') == 'price_low' ? 'selected' : '' }}>ราคาต่ำ-สูง</option>
                            <option value="price_high" {{ ($filters['sort'] ?? '') == 'price_high' ? 'selected' : '' }}>ราคาสูง-ต่ำ</option>
                            <option value="newest" {{ ($filters['sort'] ?? '') == 'newest' ? 'selected' : '' }}>ใหม่ล่าสุด</option>
                        </select>
                        <i class="fas fa-sort absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>
            </form>
        </div>

        {{-- Results Header --}}
        <div class="flex items-center justify-between mb-6">
            <p class="text-gray-600 dark:text-gray-400">
                @if($services->total() > 0)
                    พบ <strong class="text-gray-900 dark:text-white">{{ number_format($services->total()) }}</strong> บริการ
                    @if($filters['q'] ?? null)
                        สำหรับ "{{ $filters['q'] }}"
                    @endif
                @else
                    ไม่พบบริการ
                @endif
            </p>

            {{-- View Toggle --}}
            <div class="flex items-center gap-2">
                <button type="button"
                        @click="viewMode = 'grid'"
                        :class="viewMode === 'grid' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-600' : 'text-gray-400 hover:text-gray-600'"
                        class="p-2 rounded-lg transition-colors">
                    <i class="fas fa-th-large"></i>
                </button>
                <button type="button"
                        @click="viewMode = 'list'"
                        :class="viewMode === 'list' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-600' : 'text-gray-400 hover:text-gray-600'"
                        class="p-2 rounded-lg transition-colors">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>

        {{-- Services Grid --}}
        @if($services->count() > 0)
            <div :class="viewMode === 'grid' ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6' : 'space-y-4'">
                @foreach($services as $service)
                    {{-- Grid View Card --}}
                    <div x-show="viewMode === 'grid'"
                         class="group rounded-2xl overflow-hidden bg-white dark:bg-gray-800 shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 dark:border-gray-700">
                        <a href="{{ route('user.services.show', $service) }}" class="block">
                            {{-- Image --}}
                            <div class="relative h-44 overflow-hidden bg-gradient-to-br from-purple-400/10 to-pink-400/10">
                                @if($service->image_path)
                                    <img src="{{ asset('storage/' . $service->image_path) }}"
                                         alt="{{ $service->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-6xl">
                                        {{ $service->category->icon ?? '🔧' }}
                                    </div>
                                @endif

                                {{-- Category Badge --}}
                                <div class="absolute top-3 left-3">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-full text-xs font-semibold shadow"
                                          style="color: {{ $service->category->color ?? '#666' }}">
                                        {{ $service->category->icon ?? '' }}
                                        {{ $service->category->name ?? 'อื่นๆ' }}
                                    </span>
                                </div>

                                {{-- Featured Badge --}}
                                @if($service->is_featured)
                                    <div class="absolute top-3 right-3">
                                        <span class="px-2 py-1 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold rounded-full shadow-lg">
                                            <i class="fas fa-star"></i>
                                        </span>
                                    </div>
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="p-4">
                                <h3 class="font-bold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors line-clamp-2 min-h-[48px]">
                                    {{ $service->name }}
                                </h3>

                                @if($service->description)
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mb-3 min-h-[40px]">
                                        {{ $service->description }}
                                    </p>
                                @endif

                                {{-- Meta --}}
                                <div class="flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500 mb-3">
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-clock"></i>
                                        {{ $service->duration_minutes ?? 60 }} นาที
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-eye"></i>
                                        {{ number_format($service->view_count ?? 0) }}
                                    </span>
                                </div>

                                {{-- Price & Rating --}}
                                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                                    <div>
                                        <p class="text-xs text-gray-400">เริ่มต้น</p>
                                        <p class="text-xl font-black text-purple-600 dark:text-purple-400">
                                            ฿{{ number_format($service->base_price, 0) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <div class="flex items-center gap-1 text-yellow-500">
                                            <i class="fas fa-star"></i>
                                            <span class="font-bold">{{ number_format($service->average_rating ?? 0, 1) }}</span>
                                        </div>
                                        <p class="text-xs text-gray-400">({{ $service->reviews_count ?? 0 }} รีวิว)</p>
                                    </div>
                                </div>
                            </div>
                        </a>

                        {{-- Quick Book Button --}}
                        <div class="px-4 pb-4">
                            <a href="{{ route('user.services.show', $service) }}"
                               class="block w-full py-2.5 text-center bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl">
                                <i class="fas fa-calendar-check mr-1"></i>
                                จองเลย
                            </a>
                        </div>
                    </div>

                    {{-- List View Card --}}
                    <div x-show="viewMode === 'list'" x-cloak
                         class="group flex gap-4 p-4 rounded-2xl bg-white dark:bg-gray-800 shadow-lg hover:shadow-xl transition-all border border-gray-100 dark:border-gray-700">
                        {{-- Image --}}
                        <div class="w-32 h-32 md:w-40 md:h-40 flex-shrink-0 rounded-xl overflow-hidden bg-gradient-to-br from-purple-400/10 to-pink-400/10">
                            @if($service->image_path)
                                <img src="{{ asset('storage/' . $service->image_path) }}"
                                     alt="{{ $service->name }}"
                                     class="w-full h-full object-cover"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-5xl">
                                    {{ $service->category->icon ?? '🔧' }}
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full text-xs font-medium mb-2"
                                          style="color: {{ $service->category->color ?? '#666' }}">
                                        {{ $service->category->icon ?? '' }} {{ $service->category->name ?? '' }}
                                    </span>
                                    <h3 class="font-bold text-lg text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                        <a href="{{ route('user.services.show', $service) }}">
                                            {{ $service->name }}
                                        </a>
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mt-1">
                                        {{ $service->description }}
                                    </p>
                                </div>

                                <div class="text-right flex-shrink-0">
                                    <p class="text-xs text-gray-400">เริ่มต้น</p>
                                    <p class="text-2xl font-black text-purple-600 dark:text-purple-400">
                                        ฿{{ number_format($service->base_price, 0) }}
                                    </p>
                                    <div class="flex items-center gap-1 text-yellow-500 justify-end mt-1">
                                        <i class="fas fa-star text-sm"></i>
                                        <span class="font-semibold">{{ number_format($service->average_rating ?? 0, 1) }}</span>
                                        <span class="text-xs text-gray-400">({{ $service->reviews_count ?? 0 }})</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-4">
                                <div class="flex items-center gap-4 text-sm text-gray-400">
                                    <span><i class="fas fa-clock mr-1"></i> {{ $service->duration_minutes ?? 60 }} นาที</span>
                                    <span><i class="fas fa-eye mr-1"></i> {{ number_format($service->view_count ?? 0) }}</span>
                                </div>
                                <a href="{{ route('user.services.show', $service) }}"
                                   class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-semibold rounded-xl transition-all shadow-lg hover:shadow-xl">
                                    <i class="fas fa-calendar-check mr-1"></i> จองเลย
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($services->hasPages())
                <div class="mt-10">
                    {{ $services->links() }}
                </div>
            @endif
        @else
            {{-- Empty State --}}
            <div class="text-center py-20 px-4">
                <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 flex items-center justify-center">
                    <i class="fas fa-search text-5xl text-purple-400"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ไม่พบบริการที่ค้นหา</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
                    ลองเปลี่ยนคำค้นหา หรือล้างตัวกรองเพื่อดูบริการทั้งหมด
                </p>
                <a href="{{ route('user.services.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-semibold hover:shadow-xl transition-all">
                    <i class="fas fa-redo"></i>
                    ดูบริการทั้งหมด
                </a>
            </div>
        @endif

        {{-- Features Section --}}
        <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-6 rounded-2xl bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 border border-purple-100 dark:border-purple-800">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center mb-4">
                    <i class="fas fa-shield-alt text-2xl text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ปลอดภัย มั่นใจ</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    ผู้ให้บริการผ่านการตรวจสอบแล้ว พร้อมประกันความเสียหาย
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border border-blue-100 dark:border-blue-800">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center mb-4">
                    <i class="fas fa-map-marked-alt text-2xl text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">ติดตามแบบเรียลไทม์</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    ดูตำแหน่งผู้ให้บริการแบบเรียลไทม์ พร้อมแจ้งเตือนทุกขั้นตอน
                </p>
            </div>

            <div class="p-6 rounded-2xl bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border border-green-100 dark:border-green-800">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center mb-4">
                    <i class="fas fa-headset text-2xl text-white"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">สนับสนุน 24/7</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    ทีมงานพร้อมให้ความช่วยเหลือคุณตลอด 24 ชั่วโมง
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function serviceDiscovery() {
    return {
        searchQuery: '{{ $filters['q'] ?? '' }}',
        showSuggestions: false,
        suggestions: [],
        viewMode: 'grid',
        showPriceFilter: false,
        currentLocation: null,
        gettingLocation: false,

        get priceFilterLabel() {
            const min = '{{ $filters['min_price'] ?? '' }}';
            const max = '{{ $filters['max_price'] ?? '' }}';
            if (min && max) return `฿${min} - ฿${max}`;
            if (min) return `฿${min}+`;
            if (max) return `สูงสุด ฿${max}`;
            return 'ช่วงราคา';
        },

        init() {
            // โหลด view mode จาก localStorage
            const savedView = localStorage.getItem('serviceViewMode');
            if (savedView) this.viewMode = savedView;

            // บันทึก view mode เมื่อเปลี่ยน
            this.$watch('viewMode', (value) => {
                localStorage.setItem('serviceViewMode', value);
            });
        },

        async searchSuggestions() {
            if (this.searchQuery.length < 2) {
                this.suggestions = [];
                return;
            }

            try {
                const response = await fetch(`/api/v1/services/search-suggestions?q=${encodeURIComponent(this.searchQuery)}`);
                const data = await response.json();
                if (data.success) {
                    this.suggestions = data.suggestions;
                    this.showSuggestions = true;
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        },

        async getCurrentLocation() {
            if (!navigator.geolocation) {
                alert('เบราว์เซอร์ไม่รองรับการหาตำแหน่ง');
                return;
            }

            this.gettingLocation = true;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.currentLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    this.gettingLocation = false;

                    // อาจส่ง location ไปกับ search
                    console.log('Location:', this.currentLocation);
                },
                (error) => {
                    console.error('Location error:', error);
                    this.gettingLocation = false;
                    alert('ไม่สามารถหาตำแหน่งได้');
                }
            );
        }
    }
}
</script>
@endpush

@push('styles')
<style>
    /* Hide scrollbar but keep functionality */
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    /* Line clamp utilities */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush
