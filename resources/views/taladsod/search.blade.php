{{--
    หน้าค้นหาสินค้า - ตลาดสดไทยพร๊อม

    ตัวแปรที่ใช้:
    - $listings: สินค้าที่ค้นหาได้ (paginated collection)
    - $categories: หมวดหมู่สินค้า (collection)
    - $settings: การตั้งค่าตลาดสด
    - $currentCategory: หมวดหมู่ปัจจุบัน (optional, object)
--}}
@extends('layouts.taladsod')

@section('title', isset($currentCategory) ? $currentCategory->name . ' - ค้นหาสินค้า' : 'ค้นหาสินค้า')

@section('content')

    <div x-data="freshMarketSearch()" @location-updated.window="lat = $event.detail.lat; lng = $event.detail.lng" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

        {{-- ===== หัวข้อหน้า ===== --}}
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">
                @if(isset($currentCategory))
                    {!! $currentCategory->icon ?? '📦' !!} {{ $currentCategory->name }}
                @elseif(request('q'))
                    <i class="fas fa-search text-green-500"></i> ผลการค้นหา "{{ request('q') }}"
                @else
                    <i class="fas fa-search text-green-500"></i> ค้นหาสินค้า
                @endif
            </h1>
            @if(isset($listings) && method_exists($listings, 'total'))
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    พบ {{ number_format($listings->total()) }} รายการ
                </p>
            @endif
        </div>

        <div class="flex flex-col lg:flex-row gap-6">

            {{-- ===== แถบกรอง (Sidebar) ===== --}}
            <aside x-data="{ filterOpen: false }" class="lg:w-72 flex-shrink-0">

                {{-- ปุ่มเปิดฟิลเตอร์บน Mobile --}}
                <button @click="filterOpen = !filterOpen"
                        class="lg:hidden w-full flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-4">
                    <span class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        <i class="fas fa-sliders text-green-500"></i> ตัวกรองสินค้า
                    </span>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="filterOpen ? 'rotate-180' : ''"></i>
                </button>

                {{-- ฟิลเตอร์ --}}
                <div :class="filterOpen ? 'block' : 'hidden lg:block'"
                     class="bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-100 dark:border-gray-700 p-5 space-y-6">

                    {{-- หมวดหมู่ --}}
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <i class="fas fa-th-large text-green-500"></i> หมวดหมู่
                        </h3>
                        <div class="space-y-1.5 max-h-48 overflow-y-auto">
                            <a href="{{ route('taladsod.search', array_merge(request()->except('category', 'page'), [])) }}"
                               class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors {{ !request('category') ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                📦 ทั้งหมด
                            </a>
                            @if(isset($categories))
                                @foreach($categories as $cat)
                                    <a href="{{ route('taladsod.search', array_merge(request()->except('page'), ['category' => $cat->slug])) }}"
                                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition-colors {{ request('category') === $cat->slug ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                        {!! $cat->icon ?? '📦' !!} {{ $cat->name }}
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    {{-- ช่วงราคา --}}
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <i class="fas fa-tag text-orange-500"></i> ช่วงราคา
                        </h3>
                        <div class="flex items-center gap-2">
                            <input type="number"
                                   x-model="minPrice"
                                   placeholder="ต่ำสุด"
                                   class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <span class="text-gray-400">-</span>
                            <input type="number"
                                   x-model="maxPrice"
                                   placeholder="สูงสุด"
                                   class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>

                    {{-- รัศมีค้นหา --}}
                    <div x-data="geolocation()">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <i class="fas fa-location-crosshairs text-blue-500"></i> รัศมีค้นหา
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">ระยะทาง</span>
                                <span class="font-semibold text-green-600 dark:text-green-400" x-text="radius + ' กม.'">10 กม.</span>
                            </div>
                            <input type="range"
                                   x-model="radius"
                                   min="1"
                                   max="50"
                                   step="1"
                                   class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-green-500">
                            <div class="flex justify-between text-xs text-gray-400">
                                <span>1 กม.</span>
                                <span>25 กม.</span>
                                <span>50 กม.</span>
                            </div>
                            <button @click="getLocation()"
                                    :disabled="loading"
                                    class="w-full px-3 py-2 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg text-sm font-medium hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors flex items-center justify-center gap-2">
                                <i class="fas fa-location-dot"></i>
                                <span x-text="loading ? 'กำลังค้นหาตำแหน่ง...' : (lat ? 'อัพเดทตำแหน่ง' : 'ใช้ตำแหน่งปัจจุบัน')"></span>
                            </button>
                            <template x-if="error">
                                <p class="text-xs text-red-500" x-text="error"></p>
                            </template>
                        </div>
                    </div>

                    {{-- สินค้าออร์แกนิค --}}
                    <div>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox"
                                   class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-green-500 focus:ring-green-500 focus:ring-offset-0">
                            <span class="text-sm text-gray-700 dark:text-gray-300 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors flex items-center gap-1.5">
                                <i class="fas fa-leaf text-green-500"></i> เฉพาะสินค้าออร์แกนิค
                            </span>
                        </label>
                    </div>

                    {{-- จัดเรียง --}}
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <i class="fas fa-sort text-purple-500"></i> จัดเรียงตาม
                        </h3>
                        <select x-model="sortBy"
                                class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="distance">ใกล้ที่สุด</option>
                            <option value="latest">ใหม่ล่าสุด</option>
                            <option value="price_low">ราคาต่ำ - สูง</option>
                            <option value="price_high">ราคาสูง - ต่ำ</option>
                            <option value="popular">ยอดนิยม</option>
                            <option value="rating">คะแนนสูงสุด</option>
                        </select>
                    </div>

                    {{-- ปุ่มค้นหา --}}
                    <button @click="search()"
                            :disabled="loading"
                            class="w-full py-3 bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white rounded-xl font-medium transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-search" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin" x-show="loading" x-cloak></i>
                        <span x-text="loading ? 'กำลังค้นหา...' : 'ค้นหาสินค้า'"></span>
                    </button>
                </div>
            </aside>

            {{-- ===== ผลการค้นหา ===== --}}
            <div class="flex-1 min-w-0">

                {{-- แถบจัดเรียงด้านบน --}}
                <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 px-4 py-3 mb-4">
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-th-large"></i>
                        <span class="hidden sm:inline">แสดงผลแบบ</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <select class="text-sm border-0 bg-transparent text-gray-600 dark:text-gray-300 focus:ring-0 cursor-pointer pr-8">
                            <option value="distance">ใกล้ที่สุด</option>
                            <option value="latest">ใหม่ล่าสุด</option>
                            <option value="price_low">ราคาต่ำ - สูง</option>
                            <option value="price_high">ราคาสูง - ต่ำ</option>
                        </select>
                    </div>
                </div>

                {{-- ตารางสินค้า --}}
                @if(isset($listings) && $listings->count() > 0)
                    <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                        @foreach($listings as $listing)
                            <a href="{{ route('taladsod.listing', $listing->id) }}"
                               class="group bg-white dark:bg-gray-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden hover:scale-[1.03] hover:-translate-y-1">

                                {{-- รูปสินค้า --}}
                                <div class="relative aspect-[4/3] overflow-hidden bg-gray-100 dark:bg-gray-700">
                                    @if($listing->image_url ?? false)
                                        <img src="{{ $listing->image_url }}"
                                             alt="{{ $listing->title }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                             loading="lazy">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-5xl bg-gradient-to-br from-green-50 to-green-100 dark:from-gray-700 dark:to-gray-600">
                                            🥬
                                        </div>
                                    @endif

                                    {{-- ป้ายระยะทาง --}}
                                    @if($listing->distance ?? false)
                                        <div class="absolute top-2 right-2 px-2 py-1 bg-black/60 backdrop-blur-sm text-white text-xs rounded-full flex items-center gap-1">
                                            <i class="fas fa-location-dot text-green-400"></i>
                                            {{ number_format($listing->distance, 1) }} กม.
                                        </div>
                                    @endif

                                    {{-- ป้ายเงินคืน --}}
                                    @if($listing->cashback_percent ?? false)
                                        <div class="absolute top-2 left-2 px-2 py-1 bg-orange-500 text-white text-xs font-bold rounded-full">
                                            คืน {{ $listing->cashback_percent }}%
                                        </div>
                                    @endif

                                    {{-- ป้ายออร์แกนิค --}}
                                    @if($listing->is_organic ?? false)
                                        <div class="absolute bottom-2 left-2 px-2 py-0.5 bg-green-500 text-white text-xs font-medium rounded-full flex items-center gap-1">
                                            <i class="fas fa-leaf"></i> ออร์แกนิค
                                        </div>
                                    @endif
                                </div>

                                {{-- ข้อมูลสินค้า --}}
                                <div class="p-3 sm:p-4">
                                    <h3 class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white line-clamp-2 mb-1 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
                                        {{ $listing->title }}
                                    </h3>
                                    <div class="flex items-baseline gap-1.5 mb-2">
                                        <span class="text-lg sm:text-xl font-bold text-green-600 dark:text-green-400">
                                            ฿{{ number_format($listing->price, 0) }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            /{{ $listing->unit ?? 'กก.' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <i class="fas fa-store text-green-500"></i>
                                            <span class="truncate">{{ $listing->seller->shop_name ?? 'ร้านค้า' }}</span>
                                        </div>
                                        @if(($listing->seller->rating_average ?? 0) > 0)
                                            <div class="flex items-center gap-0.5 flex-shrink-0">
                                                <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                                <span>{{ number_format($listing->seller->rating_average, 1) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if(method_exists($listings, 'links'))
                        <div class="mt-8">
                            {{ $listings->withQueryString()->links() }}
                        </div>
                    @endif

                @else
                    {{-- ===== สถานะไม่พบสินค้า ===== --}}
                    <div class="text-center py-20 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                        <div class="text-7xl mb-6 animate-float">🔍</div>
                        <h3 class="text-xl font-bold text-gray-700 dark:text-gray-300 mb-3">ไม่พบสินค้า</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6 leading-relaxed">
                            ลองเปลี่ยนคำค้นหาหรือเลือกหมวดหมู่อื่น หรือขยายรัศมีค้นหาให้กว้างขึ้น
                        </p>
                        <div class="flex flex-wrap items-center justify-center gap-3">
                            <a href="{{ route('taladsod.search') }}"
                               class="px-6 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-xl text-sm font-medium transition-colors">
                                <i class="fas fa-redo mr-1"></i> ค้นหาใหม่
                            </a>
                            <a href="{{ route('taladsod.home') }}"
                               class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium transition-colors">
                                <i class="fas fa-home mr-1"></i> กลับหน้าแรก
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection
