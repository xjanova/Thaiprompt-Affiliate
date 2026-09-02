{{--
    หน้าค้นหาบริการ (Service Discovery)
    ระดับ Enterprise - เทียบเท่า Grab/Lineman
    ธีม: Arrow X V3 - Glassmorphism

    Features:
    - Search bar with autocomplete
    - Category quick-access with Font Awesome icons
    - Featured services carousel
    - Filter by price, rating, distance
    - Sort options
    - Responsive design
    - Dark mode support
--}}
@extends('layouts.user-v4')

@section('title', $pageTitle ?? 'ค้นหาบริการ')

@section('content')
<div x-data="serviceDiscovery()">

    {{-- Premium Hero Header (Cyan-Blue-Indigo for Services) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 dark:from-cyan-800 dark:via-blue-800 dark:to-indigo-800 rounded-2xl shadow-2xl p-8 mb-6">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="fas fa-concierge-bell"></i>
            </div>
        </div>

        <div class="relative z-10 px-0 py-6 md:py-10">
            {{-- Hero Text --}}
            <div class="text-center mb-8">
                <div class="flex justify-center items-center gap-4 mb-4">
                    <div class="glass-fusion p-4 rounded-2xl">
                        <i class="fas fa-search text-4xl text-white drop-shadow-lg"></i>
                    </div>
                </div>
                <h1 class="text-3xl md:text-4xl lg:text-5xl font-black text-white mb-4 drop-shadow-lg">
                    🔍 บริการถึงที่ ง่าย ครบ จบในแอพเดียว
                </h1>
                <p class="text-base md:text-lg text-cyan-100 max-w-2xl mx-auto">
                    นวด ตัดผม ซ่อมแอร์ ทำความสะอาด สอนพิเศษ และอีกมากมาย
                    <br class="hidden md:block">มีผู้ให้บริการมืออาชีพพร้อมบริการคุณ
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
                                   class="w-full pl-12 pr-4 py-4 md:py-5 text-lg bg-white/90 dark:bg-gray-800/90 backdrop-blur-lg border-0 rounded-xl md:rounded-l-xl md:rounded-r-none shadow-2xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-4 focus:ring-purple-300/50">

                            {{-- Search Suggestions Dropdown --}}
                            <div x-show="showSuggestions && suggestions.length > 0"
                                 x-cloak
                                 @click.away="showSuggestions = false"
                                 class="tp-card absolute top-full left-0 right-0 mt-2 rounded-xl overflow-hidden z-50 border border-gray-200 dark:border-gray-700">
                                <template x-for="suggestion in suggestions" :key="suggestion.id">
                                    <a :href="suggestion.url"
                                       class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <span class="w-10 h-10 rounded-lg flex items-center justify-center text-white" :style="'background: ' + suggestion.color">
                                            <i :class="suggestion.icon || 'fas fa-concierge-bell'"></i>
                                        </span>
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
                                class="hidden md:flex items-center gap-2 px-6 py-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur-lg text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-700 transition-colors">
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
    </div>

    {{-- Category Quick Access --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-white">
                <i class="fas fa-th-large mr-2 text-purple-400"></i>
                หมวดหมู่บริการ
            </h2>
            <a href="#" class="text-purple-400 hover:text-purple-300 text-sm font-semibold">
                ดูทั้งหมด <i class="fas fa-chevron-right ml-1"></i>
            </a>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-3">
            @foreach($categories->take(16) as $category)
            <a href="{{ route('user.services.index', ['category' => $category->id]) }}"
               class="group flex flex-col items-center p-4 rounded-2xl glass-fusion hover:scale-105 transition-all duration-200 {{ ($filters['category'] ?? null) == $category->id ? 'ring-2 ring-purple-500' : '' }}">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform overflow-hidden">
                    @if($category->icon && str_ends_with($category->icon, '.svg'))
                        {{-- SVG Icon --}}
                        <img src="{{ asset($category->icon) }}" alt="{{ $category->name }}" class="w-full h-full object-cover">
                    @else
                        {{-- Font Awesome Icon (fallback) --}}
                        <div class="w-full h-full flex items-center justify-center text-xl"
                             style="background: {{ $category->color ?? '#8B5CF6' }}; color: white;">
                            <i class="{{ $category->icon ?? 'fa-solid fa-concierge-bell' }}"></i>
                        </div>
                    @endif
                </div>
                <span class="text-xs font-semibold text-white text-center line-clamp-2">{{ $category->name }}</span>
                <span class="text-xs text-white/60">({{ $category->services_count ?? 0 }})</span>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Featured Services Carousel --}}
    @if($featuredServices->count() > 0)
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-white">
                <i class="fas fa-star mr-2 text-yellow-400"></i>
                บริการแนะนำ
            </h2>
        </div>

        <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide">
            @foreach($featuredServices as $service)
            <a href="{{ route('user.services.show', $service) }}"
               class="snap-start flex-shrink-0 w-72 md:w-80 rounded-2xl overflow-hidden glass-fusion hover:scale-[1.02] transition-all duration-300 group">
                {{-- Image --}}
                <div class="relative h-40 overflow-hidden bg-gradient-to-br from-purple-400/20 to-pink-400/20">
                    @if($service->image_path)
                        <img src="{{ asset('storage/' . $service->image_path) }}"
                             alt="{{ $service->name }}"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center"
                             style="background: {{ $service->category->color ?? '#8B5CF6' }}20;">
                            @if($service->category->icon && str_ends_with($service->category->icon, '.svg'))
                                <img src="{{ asset($service->category->icon) }}" alt="{{ $service->category->name }}" class="w-16 h-16 opacity-80">
                            @else
                                <i class="{{ $service->category->icon ?? 'fa-solid fa-concierge-bell' }} text-6xl text-white/50"></i>
                            @endif
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
                        <span class="px-3 py-1 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-full shadow-lg font-bold text-purple-600">
                            เริ่ม ฿{{ number_format($service->base_price, 0) }}
                        </span>
                    </div>
                </div>

                {{-- Content --}}
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h3 class="font-bold text-white group-hover:text-purple-300 transition-colors line-clamp-2">
                            {{ $service->name }}
                        </h3>
                    </div>

                    <p class="text-sm text-white/70 line-clamp-2 mb-3">
                        {{ $service->description }}
                    </p>

                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full"
                              style="background: {{ $service->category->color ?? '#8B5CF6' }}30; color: {{ $service->category->color ?? '#8B5CF6' }}">
                            @if($service->category->icon && str_ends_with($service->category->icon, '.svg'))
                                <img src="{{ asset($service->category->icon) }}" alt="" class="w-4 h-4">
                            @else
                                <i class="{{ $service->category->icon ?? 'fa-solid fa-tag' }}"></i>
                            @endif
                            {{ $service->category->name ?? '' }}
                        </span>
                        <div class="flex items-center gap-1 text-yellow-400">
                            <i class="fas fa-star text-sm"></i>
                            <span class="font-semibold text-sm text-white">{{ number_format($service->average_rating ?? 0, 1) }}</span>
                        </div>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Filters & Sort --}}
    <div class="glass-fusion rounded-2xl p-4 md:p-6 mb-6 sticky top-0 z-40">
        <form action="{{ route('user.services.index') }}" method="GET">
            <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">

            <div class="flex flex-wrap items-center gap-3">
                {{-- Category Filter --}}
                <div class="relative">
                    <select name="category"
                            @change="$el.form.submit()"
                            class="appearance-none px-4 py-2.5 pr-10 bg-white/10 backdrop-blur-lg border border-white/20 rounded-xl text-sm font-medium text-white focus:ring-2 focus:ring-purple-500">
                        <option value="" class="text-gray-900">ทุกหมวดหมู่</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ ($filters['category'] ?? '') == $category->id ? 'selected' : '' }} class="text-gray-900">
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-white/60 pointer-events-none"></i>
                </div>

                {{-- Price Range --}}
                <div class="relative">
                    <button type="button"
                            @click="showPriceFilter = !showPriceFilter"
                            class="flex items-center gap-2 px-4 py-2.5 bg-white/10 backdrop-blur-lg border border-white/20 rounded-xl text-sm font-medium text-white hover:bg-white/20 transition-colors">
                        <i class="fas fa-baht-sign"></i>
                        <span x-text="priceFilterLabel">ช่วงราคา</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>

                    {{-- Price Dropdown --}}
                    <div x-show="showPriceFilter"
                         x-cloak
                         @click.away="showPriceFilter = false"
                         class="tp-card absolute top-full left-0 mt-2 w-64 p-4 rounded-xl border border-gray-200 dark:border-gray-700 z-50">
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs font-medium text-gray-600 dark:text-gray-400">ราคาต่ำสุด</label>
                                <input type="number" name="min_price" value="{{ $filters['min_price'] ?? '' }}"
                                       class="w-full mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white"
                                       placeholder="฿0">
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600 dark:text-gray-400">ราคาสูงสุด</label>
                                <input type="number" name="max_price" value="{{ $filters['max_price'] ?? '' }}"
                                       class="w-full mt-1 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white"
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
                            class="appearance-none px-4 py-2.5 pr-10 bg-white/10 backdrop-blur-lg border border-white/20 rounded-xl text-sm font-medium text-white focus:ring-2 focus:ring-purple-500">
                        <option value="" class="text-gray-900">คะแนนทั้งหมด</option>
                        <option value="4.5" {{ ($filters['min_rating'] ?? '') == '4.5' ? 'selected' : '' }} class="text-gray-900">⭐ 4.5+</option>
                        <option value="4" {{ ($filters['min_rating'] ?? '') == '4' ? 'selected' : '' }} class="text-gray-900">⭐ 4.0+</option>
                        <option value="3.5" {{ ($filters['min_rating'] ?? '') == '3.5' ? 'selected' : '' }} class="text-gray-900">⭐ 3.5+</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-white/60 pointer-events-none"></i>
                </div>

                {{-- Spacer --}}
                <div class="flex-1"></div>

                {{-- Clear Filters --}}
                @if(array_filter($filters ?? []))
                    <a href="{{ route('user.services.index') }}"
                       class="px-4 py-2.5 text-sm font-medium text-white/70 hover:text-red-400 transition-colors">
                        <i class="fas fa-times mr-1"></i> ล้างตัวกรอง
                    </a>
                @endif

                {{-- Sort --}}
                <div class="relative">
                    <select name="sort"
                            @change="$el.form.submit()"
                            class="appearance-none px-4 py-2.5 pr-10 bg-white/10 backdrop-blur-lg border border-white/20 rounded-xl text-sm font-medium text-white focus:ring-2 focus:ring-purple-500">
                        <option value="featured" {{ ($filters['sort'] ?? 'featured') == 'featured' ? 'selected' : '' }} class="text-gray-900">แนะนำ</option>
                        <option value="popular" {{ ($filters['sort'] ?? '') == 'popular' ? 'selected' : '' }} class="text-gray-900">ยอดนิยม</option>
                        <option value="rating" {{ ($filters['sort'] ?? '') == 'rating' ? 'selected' : '' }} class="text-gray-900">คะแนนสูง</option>
                        <option value="price_low" {{ ($filters['sort'] ?? '') == 'price_low' ? 'selected' : '' }} class="text-gray-900">ราคาต่ำ-สูง</option>
                        <option value="price_high" {{ ($filters['sort'] ?? '') == 'price_high' ? 'selected' : '' }} class="text-gray-900">ราคาสูง-ต่ำ</option>
                        <option value="newest" {{ ($filters['sort'] ?? '') == 'newest' ? 'selected' : '' }} class="text-gray-900">ใหม่ล่าสุด</option>
                    </select>
                    <i class="fas fa-sort absolute right-3 top-1/2 transform -translate-y-1/2 text-white/60 pointer-events-none"></i>
                </div>
            </div>
        </form>
    </div>

    {{-- Results Header --}}
    <div class="flex items-center justify-between mb-6">
        <p class="text-white/80">
            @if($services->total() > 0)
                พบ <strong class="text-white">{{ number_format($services->total()) }}</strong> บริการ
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
                    :class="viewMode === 'grid' ? 'bg-purple-500/30 text-purple-300' : 'text-white/50 hover:text-white'"
                    class="p-2 rounded-lg transition-colors">
                <i class="fas fa-th-large"></i>
            </button>
            <button type="button"
                    @click="viewMode = 'list'"
                    :class="viewMode === 'list' ? 'bg-purple-500/30 text-purple-300' : 'text-white/50 hover:text-white'"
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
                     class="group rounded-2xl overflow-hidden glass-fusion hover:scale-[1.02] transition-all duration-300">
                    <a href="{{ route('user.services.show', $service) }}" class="block">
                        {{-- Image --}}
                        <div class="relative h-44 overflow-hidden bg-gradient-to-br from-purple-400/10 to-pink-400/10">
                            @if($service->image_path)
                                <img src="{{ asset('storage/' . $service->image_path) }}"
                                     alt="{{ $service->name }}"
                                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                     loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center"
                                     style="background: {{ $service->category->color ?? '#8B5CF6' }}20;">
                                    @if($service->category->icon && str_ends_with($service->category->icon, '.svg'))
                                        <img src="{{ asset($service->category->icon) }}" alt="{{ $service->category->name }}" class="w-20 h-20 opacity-60">
                                    @else
                                        <i class="{{ $service->category->icon ?? 'fa-solid fa-concierge-bell' }} text-6xl text-white/30"></i>
                                    @endif
                                </div>
                            @endif

                            {{-- Category Badge --}}
                            <div class="absolute top-3 left-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm rounded-full text-xs font-semibold shadow"
                                      style="color: {{ $service->category->color ?? '#8B5CF6' }}">
                                    @if($service->category->icon && str_ends_with($service->category->icon, '.svg'))
                                        <img src="{{ asset($service->category->icon) }}" alt="" class="w-4 h-4">
                                    @else
                                        <i class="{{ $service->category->icon ?? 'fa-solid fa-tag' }}"></i>
                                    @endif
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
                            <h3 class="font-bold text-white mb-2 group-hover:text-purple-300 transition-colors line-clamp-2 min-h-[48px]">
                                {{ $service->name }}
                            </h3>

                            @if($service->description)
                                <p class="text-sm text-white/60 line-clamp-2 mb-3 min-h-[40px]">
                                    {{ $service->description }}
                                </p>
                            @endif

                            {{-- Meta --}}
                            <div class="flex items-center gap-3 text-xs text-white/50 mb-3">
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
                            <div class="flex items-center justify-between pt-3 border-t border-white/10">
                                <div>
                                    <p class="text-xs text-white/50">เริ่มต้น</p>
                                    <p class="text-xl font-black text-purple-400">
                                        ฿{{ number_format($service->base_price, 0) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="flex items-center gap-1 text-yellow-400">
                                        <i class="fas fa-star"></i>
                                        <span class="font-bold text-white">{{ number_format($service->average_rating ?? 0, 1) }}</span>
                                    </div>
                                    <p class="text-xs text-white/50">({{ $service->reviews_count ?? 0 }} รีวิว)</p>
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
                     class="group flex gap-4 p-4 rounded-2xl glass-fusion hover:scale-[1.01] transition-all">
                    {{-- Image --}}
                    <div class="w-32 h-32 md:w-40 md:h-40 flex-shrink-0 rounded-xl overflow-hidden bg-gradient-to-br from-purple-400/10 to-pink-400/10">
                        @if($service->image_path)
                            <img src="{{ asset('storage/' . $service->image_path) }}"
                                 alt="{{ $service->name }}"
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        @else
                            <div class="w-full h-full flex items-center justify-center"
                                 style="background: {{ $service->category->color ?? '#8B5CF6' }}20;">
                                @if($service->category->icon && str_ends_with($service->category->icon, '.svg'))
                                    <img src="{{ asset($service->category->icon) }}" alt="{{ $service->category->name }}" class="w-16 h-16 opacity-60">
                                @else
                                    <i class="{{ $service->category->icon ?? 'fa-solid fa-concierge-bell' }} text-5xl text-white/30"></i>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium mb-2"
                                      style="background: {{ $service->category->color ?? '#8B5CF6' }}30; color: {{ $service->category->color ?? '#8B5CF6' }}">
                                    @if($service->category->icon && str_ends_with($service->category->icon, '.svg'))
                                        <img src="{{ asset($service->category->icon) }}" alt="" class="w-4 h-4">
                                    @else
                                        <i class="{{ $service->category->icon ?? 'fa-solid fa-tag' }}"></i>
                                    @endif
                                    {{ $service->category->name ?? '' }}
                                </span>
                                <h3 class="font-bold text-lg text-white group-hover:text-purple-300 transition-colors">
                                    <a href="{{ route('user.services.show', $service) }}">
                                        {{ $service->name }}
                                    </a>
                                </h3>
                                <p class="text-sm text-white/60 line-clamp-2 mt-1">
                                    {{ $service->description }}
                                </p>
                            </div>

                            <div class="text-right flex-shrink-0">
                                <p class="text-xs text-white/50">เริ่มต้น</p>
                                <p class="text-2xl font-black text-purple-400">
                                    ฿{{ number_format($service->base_price, 0) }}
                                </p>
                                <div class="flex items-center gap-1 text-yellow-400 justify-end mt-1">
                                    <i class="fas fa-star text-sm"></i>
                                    <span class="font-semibold text-white">{{ number_format($service->average_rating ?? 0, 1) }}</span>
                                    <span class="text-xs text-white/50">({{ $service->reviews_count ?? 0 }})</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-4">
                            <div class="flex items-center gap-4 text-sm text-white/50">
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
            <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-gradient-to-br from-purple-500/30 to-pink-500/30 flex items-center justify-center">
                <i class="fas fa-search text-5xl text-purple-400"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">ไม่พบบริการที่ค้นหา</h3>
            <p class="text-white/60 max-w-md mx-auto mb-6">
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
        <div class="p-6 rounded-2xl glass-fusion">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center mb-4">
                <i class="fas fa-shield-alt text-2xl text-white"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">ปลอดภัย มั่นใจ</h3>
            <p class="text-sm text-white/60">
                ผู้ให้บริการผ่านการตรวจสอบแล้ว พร้อมประกันความเสียหาย
            </p>
        </div>

        <div class="p-6 rounded-2xl glass-fusion">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center mb-4">
                <i class="fas fa-map-marked-alt text-2xl text-white"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">ติดตามแบบเรียลไทม์</h3>
            <p class="text-sm text-white/60">
                ดูตำแหน่งผู้ให้บริการแบบเรียลไทม์ พร้อมแจ้งเตือนทุกขั้นตอน
            </p>
        </div>

        <div class="p-6 rounded-2xl glass-fusion">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center mb-4">
                <i class="fas fa-headset text-2xl text-white"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">สนับสนุน 24/7</h3>
            <p class="text-sm text-white/60">
                ทีมงานพร้อมให้ความช่วยเหลือคุณตลอด 24 ชั่วโมง
            </p>
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

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
