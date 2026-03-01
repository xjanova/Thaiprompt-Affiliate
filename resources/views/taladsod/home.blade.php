{{--
    หน้าแรก ตลาดสดไทยพร้อม

    ตัวแปรที่ใช้:
    - $settings: การตั้งค่าตลาดสด
    - $categories: หมวดหมู่สินค้า (collection)
    - $featuredListings: สินค้าแนะนำ (collection)
    - $latestListings: สินค้าล่าสุด (collection)
    - $topSellers: ร้านค้ายอดนิยม (collection)
    - $serviceProviders: ช่างบริการ (collection)
--}}
@extends('layouts.taladsod')

@section('title', 'หน้าแรก - ตลาดสดใกล้บ้านคุณ')

@section('meta_description', 'ตลาดสดออนไลน์ ค้นหาอาหารสดจากผู้ขายใกล้บ้านคุณ ผักสด ผลไม้ เนื้อสัตว์ อาหารทะเล ส่งตรงจากเกษตรกรไทย')

@section('content')

    {{-- ===== Hero Section ===== --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-green-600 via-green-500 to-teal-500 dark:from-green-800 dark:via-green-700 dark:to-teal-700">
        {{-- รูปแบบพื้นหลัง --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-10 left-10 text-8xl">&#x1F96C;</div>
            <div class="absolute top-20 right-20 text-7xl">&#x1F34E;</div>
            <div class="absolute bottom-10 left-1/4 text-6xl">&#x1F969;</div>
            <div class="absolute bottom-20 right-1/3 text-8xl">&#x1F990;</div>
            <div class="absolute top-1/3 left-1/2 text-5xl">&#x1F33D;</div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-28">
            <div class="text-center max-w-3xl mx-auto">
                {{-- หัวข้อหลัก --}}
                <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-extrabold text-white leading-tight mb-4 sm:mb-6">
                    ตลาดสดใกล้บ้านคุณ
                </h1>
                <p class="text-lg sm:text-xl text-green-50 mb-8 sm:mb-10 leading-relaxed max-w-2xl mx-auto">
                    ค้นหาอาหารสดจากผู้ขายในละแวกใกล้เคียง สดใหม่ทุกวัน ส่งตรงจากเกษตรกรและชาวสวนไทย
                </p>

                {{-- ช่องค้นหาขนาดใหญ่ --}}
                <div x-data="geolocation()" class="max-w-2xl mx-auto">
                    <form action="{{ route('taladsod.search') }}" method="GET">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1 relative">
                                <input type="text"
                                       name="q"
                                       placeholder="ค้นหาสินค้า... ผักสด ผลไม้ เนื้อหมู กุ้งสด"
                                       class="w-full pl-12 pr-4 py-4 text-base sm:text-lg rounded-2xl border-0 bg-white/95 dark:bg-gray-800/95 text-gray-900 dark:text-gray-100 shadow-xl focus:ring-4 focus:ring-white/30 placeholder-gray-400 dark:placeholder-gray-500">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-green-500 text-lg"></i>
                                </div>
                                {{-- ส่งพิกัดที่ซ่อนไว้ --}}
                                <input type="hidden" name="lat" :value="lat">
                                <input type="hidden" name="lng" :value="lng">
                            </div>
                            <div class="flex gap-3">
                                <button type="button"
                                        @click="getLocation()"
                                        :disabled="loading"
                                        class="flex-shrink-0 px-5 py-4 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-2xl font-medium transition-all border border-white/30 flex items-center gap-2"
                                        :class="{ 'animate-pulse': loading }">
                                    <span>&#x1F4CD;</span>
                                    <span class="hidden sm:inline" x-text="loading ? 'กำลังค้นหา...' : 'ค้นหาใกล้ตัว'">ค้นหาใกล้ตัว</span>
                                    <span class="sm:hidden"><i class="fas fa-location-crosshairs"></i></span>
                                </button>
                                <button type="submit"
                                        class="flex-shrink-0 px-8 py-4 bg-orange-500 hover:bg-orange-600 text-white rounded-2xl font-bold text-base sm:text-lg transition-all shadow-lg hover:shadow-xl hover:scale-105">
                                    ค้นหา
                                </button>
                            </div>
                        </div>
                    </form>
                    {{-- สถานะ GPS --}}
                    <div class="mt-3 text-sm text-green-100">
                        <template x-if="lat">
                            <span><i class="fas fa-check-circle text-green-300"></i> พบตำแหน่งของคุณแล้ว</span>
                        </template>
                        <template x-if="error">
                            <span class="text-yellow-200"><i class="fas fa-exclamation-triangle"></i> <span x-text="error"></span></span>
                        </template>
                    </div>
                </div>

                {{-- สถิติ --}}
                <div class="mt-10 flex flex-wrap justify-center gap-6 sm:gap-10 text-white/90">
                    <div class="text-center">
                        <div class="text-2xl sm:text-3xl font-bold">{{ $settings->total_listings ?? '1,000+' }}</div>
                        <div class="text-sm text-green-100">สินค้า</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl sm:text-3xl font-bold">{{ $settings->total_sellers ?? '500+' }}</div>
                        <div class="text-sm text-green-100">ร้านค้า</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl sm:text-3xl font-bold">{{ $settings->total_provinces ?? '77' }}</div>
                        <div class="text-sm text-green-100">จังหวัด</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- คลื่นด้านล่าง --}}
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 60" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full">
                <path d="M0 60L60 50C120 40 240 20 360 15C480 10 600 20 720 25C840 30 960 30 1080 25C1200 20 1320 10 1380 5L1440 0V60H0Z"
                      class="fill-amber-50 dark:fill-gray-900"/>
            </svg>
        </div>
    </section>

    {{-- ===== หมวดหมู่สินค้า ===== --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                <span class="text-green-500">&#x1F4E6;</span> หมวดหมู่สินค้า
            </h2>
            <a href="{{ route('taladsod.search', ['view' => 'categories']) }}"
               class="text-sm text-green-600 dark:text-green-400 hover:underline font-medium">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        {{-- การ์ดหมวดหมู่ - เลื่อนแนวนอน --}}
        <div class="flex gap-4 overflow-x-auto scrollbar-hide pb-4 -mx-4 px-4">
            @php
                // หมวดหมู่เริ่มต้นกรณีไม่มีข้อมูลจาก Database
                $defaultCategories = collect([
                    (object)['slug' => 'vegetables', 'name' => 'ผักสด', 'icon' => '&#x1F96C;', 'color' => 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800'],
                    (object)['slug' => 'fruits', 'name' => 'ผลไม้', 'icon' => '&#x1F34E;', 'color' => 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800'],
                    (object)['slug' => 'meat', 'name' => 'เนื้อสัตว์', 'icon' => '&#x1F969;', 'color' => 'bg-pink-50 dark:bg-pink-900/30 border-pink-200 dark:border-pink-800'],
                    (object)['slug' => 'seafood', 'name' => 'อาหารทะเล', 'icon' => '&#x1F990;', 'color' => 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800'],
                    (object)['slug' => 'eggs-dairy', 'name' => 'ไข่และนม', 'icon' => '&#x1F95A;', 'color' => 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800'],
                    (object)['slug' => 'herbs', 'name' => 'สมุนไพร', 'icon' => '&#x1F33F;', 'color' => 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-800'],
                    (object)['slug' => 'rice-grains', 'name' => 'ข้าวและธัญพืช', 'icon' => '&#x1F33E;', 'color' => 'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-800'],
                    (object)['slug' => 'dried-foods', 'name' => 'ของแห้ง', 'icon' => '&#x1F36C;', 'color' => 'bg-orange-50 dark:bg-orange-900/30 border-orange-200 dark:border-orange-800'],
                    (object)['slug' => 'ready-to-eat', 'name' => 'อาหารปรุงสำเร็จ', 'icon' => '&#x1F372;', 'color' => 'bg-rose-50 dark:bg-rose-900/30 border-rose-200 dark:border-rose-800'],
                    (object)['slug' => 'beverages', 'name' => 'เครื่องดื่ม', 'icon' => '&#x1F9C3;', 'color' => 'bg-cyan-50 dark:bg-cyan-900/30 border-cyan-200 dark:border-cyan-800'],
                ]);
                $displayCategories = isset($categories) && $categories->count() > 0 ? $categories : $defaultCategories;
            @endphp

            @foreach($displayCategories as $category)
                <a href="{{ route('taladsod.search', ['category' => $category->slug]) }}"
                   class="flex-shrink-0 w-28 sm:w-32 group">
                    <div class="flex flex-col items-center gap-2 p-4 rounded-2xl border {{ $category->color ?? 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800' }} hover:shadow-lg transition-all duration-300 group-hover:scale-105 group-hover:-translate-y-1">
                        <span class="text-4xl sm:text-5xl">{!! $category->icon ?? '&#x1F4E6;' !!}</span>
                        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 text-center leading-tight">{{ $category->name }}</span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ===== สินค้าแนะนำ ===== --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                <span class="text-orange-500">&#x2B50;</span> สินค้าแนะนำ
            </h2>
            <a href="{{ route('taladsod.search', ['sort' => 'featured']) }}"
               class="text-sm text-green-600 dark:text-green-400 hover:underline font-medium">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        @if(isset($featuredListings) && $featuredListings->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($featuredListings as $listing)
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
                                    &#x1F96C;
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
                                    &#x0E3F;{{ number_format($listing->price, 0) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    /{{ $listing->unit ?? 'กก.' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-store text-green-500"></i>
                                <span class="truncate">{{ $listing->seller->shop_name ?? 'ร้านค้า' }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            {{-- สถานะว่างเปล่า --}}
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                <div class="text-6xl mb-4">&#x1F33F;</div>
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">ยังไม่มีสินค้าแนะนำ</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าแนะนำจะแสดงที่นี่เร็วๆ นี้</p>
            </div>
        @endif
    </section>

    {{-- ===== สินค้าล่าสุด ===== --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                <span class="text-green-500">&#x1F331;</span> สินค้าล่าสุด
            </h2>
            <a href="{{ route('taladsod.search', ['sort' => 'latest']) }}"
               class="text-sm text-green-600 dark:text-green-400 hover:underline font-medium">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        @if(isset($latestListings) && $latestListings->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($latestListings as $listing)
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
                                    &#x1F34E;
                                </div>
                            @endif

                            {{-- ป้ายสินค้าใหม่ --}}
                            <div class="absolute top-2 left-2 px-2 py-0.5 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full">
                                ใหม่
                            </div>

                            {{-- ป้ายระยะทาง --}}
                            @if($listing->distance ?? false)
                                <div class="absolute top-2 right-2 px-2 py-1 bg-black/60 backdrop-blur-sm text-white text-xs rounded-full flex items-center gap-1">
                                    <i class="fas fa-location-dot text-green-400"></i>
                                    {{ number_format($listing->distance, 1) }} กม.
                                </div>
                            @endif

                            {{-- ป้ายเงินคืน --}}
                            @if($listing->cashback_percent ?? false)
                                <div class="absolute bottom-2 right-2 px-2 py-1 bg-orange-500 text-white text-xs font-bold rounded-full">
                                    คืน {{ $listing->cashback_percent }}%
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
                                    &#x0E3F;{{ number_format($listing->price, 0) }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    /{{ $listing->unit ?? 'กก.' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-store text-green-500"></i>
                                <span class="truncate">{{ $listing->seller->shop_name ?? 'ร้านค้า' }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            {{-- สถานะว่างเปล่า --}}
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                <div class="text-6xl mb-4">&#x1F33E;</div>
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">ยังไม่มีสินค้าล่าสุด</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">สินค้าใหม่จะแสดงที่นี่เร็วๆ นี้</p>
            </div>
        @endif
    </section>

    {{-- ===== ร้านค้ายอดนิยม ===== --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                <span class="text-yellow-500">&#x1F3C6;</span> ร้านค้ายอดนิยม
            </h2>
        </div>

        @if(isset($topSellers) && $topSellers->count() > 0)
            <div class="flex gap-4 sm:gap-6 overflow-x-auto scrollbar-hide pb-4 -mx-4 px-4">
                @foreach($topSellers as $seller)
                    <div class="flex-shrink-0 w-44 sm:w-52 bg-white dark:bg-gray-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden hover:scale-[1.03] hover:-translate-y-1 text-center p-5">
                        {{-- อวาตาร์ --}}
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full mx-auto mb-3 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-lg ring-4 ring-green-100 dark:ring-green-900/50">
                            @if($seller->avatar_url ?? false)
                                <img src="{{ $seller->avatar_url }}" alt="{{ $seller->shop_name }}" class="w-full h-full rounded-full object-cover">
                            @else
                                <span class="text-2xl sm:text-3xl text-white font-bold">
                                    {{ mb_substr($seller->shop_name, 0, 1) }}
                                </span>
                            @endif
                        </div>

                        {{-- ชื่อร้าน --}}
                        <h3 class="font-semibold text-sm sm:text-base text-gray-900 dark:text-white mb-1 line-clamp-1">
                            {{ $seller->shop_name }}
                        </h3>

                        {{-- ดาวรีวิว --}}
                        <div class="flex items-center justify-center gap-0.5 mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($seller->rating ?? 0))
                                    <i class="fas fa-star text-yellow-400 text-xs"></i>
                                @elseif($i - 0.5 <= ($seller->rating ?? 0))
                                    <i class="fas fa-star-half-alt text-yellow-400 text-xs"></i>
                                @else
                                    <i class="far fa-star text-gray-300 dark:text-gray-600 text-xs"></i>
                                @endif
                            @endfor
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">{{ number_format($seller->rating ?? 0, 1) }}</span>
                        </div>

                        {{-- ยอดขาย --}}
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-shopping-bag text-green-500 mr-1"></i>
                            ขายแล้ว {{ number_format($seller->total_sales ?? 0) }} รายการ
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- สถานะว่างเปล่า --}}
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                <div class="text-6xl mb-4">&#x1F3EA;</div>
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">ยังไม่มีร้านค้ายอดนิยม</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">ร้านค้ายอดนิยมจะแสดงที่นี่เร็วๆ นี้</p>
            </div>
        @endif
    </section>

    {{-- ===== ตลาดช่าง (Service Providers) ===== --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
        {{-- หัวข้อ Section --}}
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                <span class="text-blue-500">&#x1F527;</span> ตลาดช่าง — บริการถึงบ้าน
            </h2>
            <a href="{{ route('taladsod.search', ['type' => 'service']) }}"
               class="text-sm text-blue-600 dark:text-blue-400 hover:underline font-medium">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        {{-- หมวดหมู่บริการ --}}
        <div class="flex gap-3 overflow-x-auto scrollbar-hide pb-4 -mx-4 px-4 mb-6">
            @php
                $serviceCategories = [
                    (object)['slug' => 'plumbing', 'name' => 'ช่างประปา', 'icon' => '&#x1F6B0;', 'color' => 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800'],
                    (object)['slug' => 'electrical', 'name' => 'ช่างไฟฟ้า', 'icon' => '&#x26A1;', 'color' => 'bg-yellow-50 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800'],
                    (object)['slug' => 'aircon', 'name' => 'ช่างแอร์', 'icon' => '&#x2744;', 'color' => 'bg-cyan-50 dark:bg-cyan-900/30 border-cyan-200 dark:border-cyan-800'],
                    (object)['slug' => 'beauty', 'name' => 'ช่างเสริมสวย', 'icon' => '&#x1F487;', 'color' => 'bg-pink-50 dark:bg-pink-900/30 border-pink-200 dark:border-pink-800'],
                    (object)['slug' => 'massage', 'name' => 'นวดแผนไทย', 'icon' => '&#x1F486;', 'color' => 'bg-purple-50 dark:bg-purple-900/30 border-purple-200 dark:border-purple-800'],
                    (object)['slug' => 'cleaning', 'name' => 'ทำความสะอาด', 'icon' => '&#x1F9F9;', 'color' => 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800'],
                    (object)['slug' => 'repair', 'name' => 'ซ่อมบำรุง', 'icon' => '&#x1F6E0;', 'color' => 'bg-orange-50 dark:bg-orange-900/30 border-orange-200 dark:border-orange-800'],
                    (object)['slug' => 'gardening', 'name' => 'จัดสวน', 'icon' => '&#x1F333;', 'color' => 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-800'],
                ];
            @endphp

            @foreach($serviceCategories as $svc)
                <a href="{{ route('taladsod.search', ['type' => 'service', 'category' => $svc->slug]) }}"
                   class="flex-shrink-0 w-24 sm:w-28 group">
                    <div class="flex flex-col items-center gap-2 p-3 rounded-2xl border {{ $svc->color }} hover:shadow-lg transition-all duration-300 group-hover:scale-105 group-hover:-translate-y-1">
                        <span class="text-3xl sm:text-4xl">{!! $svc->icon !!}</span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300 text-center leading-tight">{{ $svc->name }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- รายชื่อช่าง/ผู้ให้บริการ --}}
        @if(isset($serviceProviders) && $serviceProviders->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($serviceProviders as $provider)
                    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden hover:scale-[1.03] hover:-translate-y-1 p-4 sm:p-5 text-center">
                        {{-- รูปโปรไฟล์ --}}
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full mx-auto mb-3 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg ring-4 ring-blue-100 dark:ring-blue-900/50">
                            @if($provider->profile_photo ?? $provider->avatar ?? false)
                                <img src="{{ $provider->profile_photo ?? $provider->avatar }}"
                                     alt="{{ $provider->display_name ?? $provider->name }}"
                                     class="w-full h-full rounded-full object-cover">
                            @else
                                <span class="text-2xl sm:text-3xl text-white font-bold">
                                    {{ mb_substr($provider->display_name ?? $provider->name, 0, 1) }}
                                </span>
                            @endif
                        </div>

                        {{-- ชื่อ --}}
                        <h3 class="font-semibold text-sm sm:text-base text-gray-900 dark:text-white mb-1 line-clamp-1">
                            {{ $provider->display_name ?? $provider->name }}
                        </h3>

                        {{-- หมวดหมู่ --}}
                        @if($provider->categories && $provider->categories->count() > 0)
                            <p class="text-xs text-blue-600 dark:text-blue-400 mb-2 line-clamp-1">
                                {{ $provider->categories->pluck('name')->implode(', ') }}
                            </p>
                        @endif

                        {{-- ดาวรีวิว --}}
                        <div class="flex items-center justify-center gap-0.5 mb-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($provider->rating ?? 0))
                                    <i class="fas fa-star text-yellow-400 text-xs"></i>
                                @elseif($i - 0.5 <= ($provider->rating ?? 0))
                                    <i class="fas fa-star-half-alt text-yellow-400 text-xs"></i>
                                @else
                                    <i class="far fa-star text-gray-300 dark:text-gray-600 text-xs"></i>
                                @endif
                            @endfor
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">{{ number_format($provider->rating ?? 0, 1) }}</span>
                        </div>

                        {{-- สถิติ --}}
                        <div class="flex items-center justify-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                            <span><i class="fas fa-briefcase text-blue-500 mr-1"></i>{{ number_format($provider->total_bookings ?? 0) }} งาน</span>
                            @if($provider->isAlsoRider())
                                <span class="px-1.5 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-medium rounded-full">
                                    &#x1F6F5; ส่งของได้
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- สถานะว่างเปล่า --}}
            <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                <div class="text-6xl mb-4">&#x1F527;</div>
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">ยังไม่มีช่างบริการ</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">ช่างบริการจะแสดงที่นี่เร็วๆ นี้</p>
                <a href="{{ route('taladsod.landing.rider') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-all text-sm font-medium">
                    <i class="fas fa-user-plus"></i> สมัครเป็นช่างบริการ
                </a>
            </div>
        @endif
    </section>

    {{-- ===== CTA Section ===== --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <div class="bg-gradient-to-br from-green-500 via-green-600 to-teal-600 dark:from-green-700 dark:via-green-800 dark:to-teal-800 rounded-3xl overflow-hidden shadow-2xl relative">
            {{-- รูปแบบพื้นหลัง --}}
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-5 right-10 text-7xl">&#x1F33D;</div>
                <div class="absolute bottom-5 left-10 text-6xl">&#x1F96C;</div>
            </div>

            <div class="relative p-8 sm:p-12 lg:p-16">
                <div class="grid lg:grid-cols-2 gap-8 items-center">
                    {{-- ข้อความ --}}
                    <div class="text-center lg:text-left">
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-white mb-4">
                            มีของสดขาย? มาขายกับเรา!
                        </h2>
                        <p class="text-green-50 text-base sm:text-lg mb-6 leading-relaxed">
                            เปิดร้านฟรี ไม่มีค่าธรรมเนียมรายเดือน เข้าถึงลูกค้าในละแวกใกล้เคียง เพิ่มยอดขายได้ทันที
                        </p>
                        <ul class="text-green-50 text-sm sm:text-base space-y-2 mb-8">
                            <li class="flex items-center gap-2 justify-center lg:justify-start">
                                <i class="fas fa-check-circle text-green-300"></i> เปิดร้านฟรี ไม่มีค่าใช้จ่าย
                            </li>
                            <li class="flex items-center gap-2 justify-center lg:justify-start">
                                <i class="fas fa-check-circle text-green-300"></i> ระบบจัดการสินค้าง่ายๆ
                            </li>
                            <li class="flex items-center gap-2 justify-center lg:justify-start">
                                <i class="fas fa-check-circle text-green-300"></i> ลูกค้าค้นหาเจอง่ายด้วย GPS
                            </li>
                            <li class="flex items-center gap-2 justify-center lg:justify-start">
                                <i class="fas fa-check-circle text-green-300"></i> รับเงินคืนจากการแนะนำเพื่อน
                            </li>
                        </ul>
                    </div>

                    {{-- ปุ่ม CTA --}}
                    <div class="flex flex-col sm:flex-row lg:flex-col gap-4 items-center">
                        <a href="{{ route('taladsod.register-seller') }}"
                           class="w-full sm:w-auto lg:w-full text-center px-8 py-4 bg-orange-500 hover:bg-orange-600 text-white text-lg font-bold rounded-2xl transition-all shadow-lg hover:shadow-xl hover:scale-105 flex items-center justify-center gap-2">
                            <i class="fas fa-store"></i> ขายของกับเรา
                        </a>
                        <a href="{{ config('services.line.fresh_market_add_friend_url', env('LINE_FRESH_MARKET_ADD_FRIEND_URL', '#')) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="w-full sm:w-auto lg:w-full text-center px-8 py-4 bg-[#06C755] hover:bg-[#05b34d] text-white text-lg font-bold rounded-2xl transition-all shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                            <i class="fab fa-line text-xl"></i> เพิ่มเพื่อน LINE
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
