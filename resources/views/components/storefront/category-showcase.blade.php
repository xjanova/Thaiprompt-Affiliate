{{--
    Category Showcase Component - สไตล์ AliExpress

    แสดงหมวดหมู่สินค้าแบบ card grid พร้อม icons และ images
    รองรับ Dark Mode และ Responsive

    @param Collection $categories - หมวดหมู่ทั้งหมด
--}}

@props([
    'categories' => collect(),
    'title' => 'ช้อปตามหมวดหมู่',
    'limit' => 8,
])

@php
    /**
     * บริการแก้ภาพปกหมวดหมู่ 3 ชั้น (ภาพแอดมิน → โมเสกสินค้าจริง → gradient + ไอคอน)
     *
     * ⚡ ดึงข้อมูลทุกหมวดพร้อมกันครั้งเดียว (batch + cache 1 ชม.)
     *    เรียก cover() ในลูปกี่รอบก็ไม่เกิด N+1
     */
    $coverService = app(\App\Services\CategoryImageService::class);
@endphp

<div class="py-8">
    {{-- Section Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500
                       rounded-2xl flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div>
                <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white">
                    {{ $title }}
                </h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                    เลือกหมวดหมู่ที่คุณสนใจ
                </p>
            </div>
        </div>

        <a href="{{ route('storefront.index') }}"
           class="hidden md:flex items-center gap-2 px-6 py-3
                 bg-gradient-to-r from-purple-500 to-pink-500
                 hover:from-purple-600 hover:to-pink-600
                 text-white font-bold rounded-xl
                 shadow-lg hover:shadow-xl
                 transform hover:scale-105 transition-all group">
            <span>ดูทั้งหมด</span>
            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    {{-- Categories Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
        @foreach($categories->take($limit) as $index => $category)
        @php
            // กำหนดสีให้แต่ละ category
            $gradients = [
                'from-orange-400 to-red-500',
                'from-blue-400 to-indigo-500',
                'from-green-400 to-emerald-500',
                'from-purple-400 to-pink-500',
                'from-yellow-400 to-orange-500',
                'from-cyan-400 to-blue-500',
                'from-pink-400 to-rose-500',
                'from-teal-400 to-cyan-500',
            ];
            $gradient = $gradients[$index % count($gradients)];

            // ภาพปกหมวดหมู่แบบ 3 ชั้น (แก้ปัญหา "หมวดหมู่ไม่ควรมีภาพตาย")
            //   1) ภาพที่แอดมินอัปโหลด  2) โมเสกภาพสินค้าจริงในหมวด/หมวดลูก  3) gradient + ไอคอน
            // ดูรายละเอียดที่ app/Services/CategoryImageService.php
            $cover = $coverService->cover($category);
            $coverUrls = $cover['urls'] ?? [];
            $coverCount = count($coverUrls);
            $isMosaic = $coverCount > 1;

            // ถ้าไม่เจอไอคอนที่ตรง ใช้ไอคอน default ตามลำดับ
            $fallbackIcons = [
                'fa-solid fa-star',
                'fa-solid fa-fire',
                'fa-solid fa-bolt',
                'fa-solid fa-crown',
                'fa-solid fa-rocket',
                'fa-solid fa-leaf',
                'fa-solid fa-heart',
                'fa-solid fa-gift',
            ];

            // ไอคอน: คอลัมน์ icon ที่แอดมินตั้ง > จับคู่ชื่อ/slug แบบตรงตัว > substring > ไอคอนสำรองตามลำดับ
            $categoryIcon = $cover['icon'] ?? $fallbackIcons[$index % count($fallbackIcons)];
        @endphp

        <a href="{{ route('storefront.index', ['category' => $category->slug]) }}"
           class="group relative bg-white dark:bg-gray-800
                 rounded-2xl overflow-hidden
                 shadow-md hover:shadow-2xl
                 transform hover:scale-105 hover:-translate-y-2
                 transition-all duration-300
                 border border-gray-100 dark:border-gray-700
                 hover:border-transparent">

            {{-- Background Gradient (Hidden by default, shown on hover) --}}
            <div class="absolute inset-0 bg-gradient-to-br {{ $gradient }}
                       opacity-0 group-hover:opacity-100
                       transition-opacity duration-300"></div>

            {{-- Content --}}
            <div class="relative p-4 text-center">
                {{-- Category Image/Icon --}}
                <div class="relative mb-3">
                    @if($coverCount > 0)
                    {{-- ชั้น 1/2: ภาพจริง — ภาพที่แอดมินอัปโหลด หรือโมเสกภาพสินค้าที่ยังขายอยู่ --}}
                    <div x-data="{ failed: [] }" class="relative w-16 h-16 mx-auto">
                        <div x-show="failed.length < {{ $coverCount }}"
                             class="w-16 h-16 rounded-xl overflow-hidden
                                   ring-4 ring-gray-100 dark:ring-gray-700
                                   group-hover:ring-white/30 transition-all
                                   bg-gradient-to-br {{ $gradient }}
                                   @if($isMosaic) grid grid-cols-2 {{ $coverCount > 2 ? 'grid-rows-2' : 'grid-rows-1' }} gap-px @endif">
                            @foreach($coverUrls as $coverIndex => $coverUrl)
                            {{-- ระบุขนาดชัดเจน + lazy load เพื่อไม่ให้เกิด layout shift --}}
                            <img src="{{ $coverUrl }}"
                                 alt="{{ $category->name }}"
                                 loading="lazy"
                                 decoding="async"
                                 width="{{ $isMosaic ? 32 : 64 }}"
                                 height="{{ $isMosaic ? 32 : 64 }}"
                                 x-show="!failed.includes({{ $coverIndex }})"
                                 x-on:error="failed.push({{ $coverIndex }})"
                                 class="w-full h-full object-cover
                                       group-hover:scale-110 transition-transform duration-300">
                            @endforeach
                        </div>

                        {{-- ถ้าภาพเจ๊งหมดทุกใบ ตกไปใช้ไอคอนแทน (ห้ามปล่อยให้เห็นรูปแตก) --}}
                        <div x-show="failed.length >= {{ $coverCount }}" x-cloak
                             class="absolute inset-0 rounded-xl
                                   bg-gradient-to-br {{ $gradient }}
                                   group-hover:bg-white/20
                                   flex items-center justify-center
                                   shadow-lg group-hover:shadow-xl
                                   transition-all">
                            <i class="{{ $categoryIcon }} text-2xl text-white drop-shadow-lg"></i>
                        </div>
                    </div>
                    @else
                    {{-- ชั้น 3: ไม่มีทั้งภาพแอดมินและสินค้าในหมวด → gradient + ไอคอน --}}
                    <div class="w-16 h-16 mx-auto rounded-xl
                               bg-gradient-to-br {{ $gradient }}
                               group-hover:bg-white/20
                               flex items-center justify-center
                               shadow-lg group-hover:shadow-xl
                               transition-all">
                        {{-- แสดงไอคอนที่เหมาะสมตามหมวดหมู่ --}}
                        <i class="{{ $categoryIcon }} text-2xl text-white drop-shadow-lg"></i>
                    </div>
                    @endif

                    {{-- Product Count Badge --}}
                    @if($category->products_count > 0)
                    <div class="absolute -bottom-1 left-1/2 -translate-x-1/2
                               px-2 py-0.5 bg-white dark:bg-gray-900
                               group-hover:bg-white/90
                               rounded-full shadow text-xs font-bold
                               text-gray-600 dark:text-gray-400
                               group-hover:text-gray-900
                               transition-all">
                        {{ number_format($category->products_count) }}
                    </div>
                    @endif
                </div>

                {{-- Category Name --}}
                <h3 class="text-sm font-bold text-gray-900 dark:text-white
                          group-hover:text-white
                          line-clamp-2 transition-colors">
                    {{ $category->name }}
                </h3>
            </div>

            {{-- Hover Arrow --}}
            <div class="absolute bottom-2 right-2
                       w-6 h-6 bg-white/20 rounded-full
                       flex items-center justify-center
                       opacity-0 group-hover:opacity-100
                       transform translate-x-2 group-hover:translate-x-0
                       transition-all">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Mobile View All Button --}}
    <div class="mt-6 md:hidden text-center">
        <a href="{{ route('storefront.index') }}"
           class="inline-flex items-center gap-2 px-8 py-3
                 bg-gradient-to-r from-purple-500 to-pink-500
                 text-white font-bold rounded-xl
                 shadow-lg">
            ดูหมวดหมู่ทั้งหมด
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
