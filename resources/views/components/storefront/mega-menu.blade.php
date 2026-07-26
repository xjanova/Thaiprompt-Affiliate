{{--
    Mega Menu Component - สไตล์ AliExpress

    แสดงหมวดหมู่สินค้าแบบ mega menu พร้อม hover effect
    รองรับ Dark Mode และ Mobile Responsive

    @param Collection $categories - หมวดหมู่ทั้งหมด
--}}

@props([
    'categories' => collect(),
])

@php
    /**
     * บริการแก้ภาพปกหมวดหมู่ 3 ชั้น (ภาพแอดมิน → ภาพสินค้าจริง → ไอคอน)
     *
     * ⚡ batch + cache 1 ชม. — เรียกในลูปกี่รอบก็ไม่เกิด N+1
     *    และเป็นแหล่งข้อมูล "สินค้ายอดนิยม" ที่กรอง publicVisible + inStock มาแล้ว
     *    (ของเดิมใช้ $category->products ซึ่ง lazy load ทุกหมวด + ไม่กรองสินค้าที่ถูกซ่อน/บล็อก)
     */
    $coverService = app(\App\Services\CategoryImageService::class);
    $noImageFallback = asset('images/no-image.png');
@endphp

<div x-data="megaMenu()"
     x-init="init()"
     class="relative z-50"
     @mouseenter="isOpen = true"
     @mouseleave="isOpen = false; activeCategory = null">

    {{-- Trigger Button --}}
    <button type="button"
            class="flex items-center gap-2 px-6 py-3
                   bg-gradient-to-r from-orange-500 via-red-500 to-pink-500
                   hover:from-orange-600 hover:via-red-600 hover:to-pink-600
                   text-white font-bold rounded-xl
                   shadow-lg hover:shadow-xl
                   transition-all duration-300"
            :class="isOpen && 'rounded-b-none'">
        {{-- ไอค่อน Grid เหมือนหมวดหมู่ --}}
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
        </svg>
        <span>หมวดหมู่สินค้า</span>
        <svg class="w-4 h-4 transition-transform duration-300"
             :class="isOpen && 'rotate-180'"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Mega Menu Dropdown --}}
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform -translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute top-full left-0 w-[900px] max-w-[calc(100vw-2rem)]
                bg-white dark:bg-gray-900
                rounded-b-2xl rounded-tr-2xl
                shadow-2xl border border-gray-100 dark:border-gray-800
                overflow-hidden"
         @click.away="isOpen = false">

        <div class="flex min-h-[400px] max-h-[70vh]">
            {{-- Categories List (Left Side) --}}
            <div class="w-64 bg-gray-50 dark:bg-gray-800/50 border-r border-gray-100 dark:border-gray-700 overflow-y-auto custom-scrollbar">
                @foreach($categories as $category)
                @php
                    // ภาพปก 3 ชั้นของหมวดนี้ (ใช้ภาพแรกเป็น thumbnail ในลิสต์ซ้าย)
                    $cover = $coverService->cover($category);
                    $coverUrl = $cover['urls'][0] ?? null;
                    $categoryIcon = $cover['icon'] ?? null;
                @endphp
                <div class="relative"
                     @mouseenter="activeCategory = {{ $category->id }}">
                    <a href="{{ route('storefront.index', ['category' => $category->slug]) }}"
                       class="flex items-center justify-between px-5 py-3.5
                              text-gray-700 dark:text-gray-300
                              hover:bg-gradient-to-r hover:from-orange-50 hover:to-pink-50
                              dark:hover:from-orange-900/20 dark:hover:to-pink-900/20
                              hover:text-orange-600 dark:hover:text-orange-400
                              transition-all duration-200 group"
                       :class="activeCategory === {{ $category->id }} && 'bg-gradient-to-r from-orange-50 to-pink-50 dark:from-orange-900/20 dark:to-pink-900/20 text-orange-600 dark:text-orange-400'">

                        <div class="flex items-center gap-3">
                            {{-- Category Icon / Cover --}}
                            <div class="relative w-10 h-10 rounded-xl overflow-hidden
                                       bg-gradient-to-br from-orange-100 to-pink-100
                                       dark:from-orange-900/30 dark:to-pink-900/30
                                       flex items-center justify-center
                                       group-hover:scale-110 transition-transform"
                                 @if($coverUrl) x-data="{ imageOk: true }" @endif>
                                @if($coverUrl)
                                    {{-- ภาพจริงจากหมวด (ระบุขนาดชัดเจน + lazy load กัน layout shift) --}}
                                    <img src="{{ $coverUrl }}"
                                         alt="{{ $category->name }}"
                                         loading="lazy"
                                         decoding="async"
                                         width="40" height="40"
                                         x-show="imageOk"
                                         x-on:error="imageOk = false"
                                         class="w-full h-full object-cover">

                                    {{-- ภาพโหลดไม่ขึ้น → ตกไปใช้ไอคอน (ห้ามเห็นรูปแตก) --}}
                                    <span x-show="!imageOk" x-cloak
                                          class="absolute inset-0 flex items-center justify-center">
                                        @if($categoryIcon)
                                            <i class="{{ $categoryIcon }} text-orange-600 dark:text-orange-400"></i>
                                        @else
                                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                        @endif
                                    </span>
                                @elseif($categoryIcon)
                                    <i class="{{ $categoryIcon }} text-orange-600 dark:text-orange-400"></i>
                                @else
                                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                @endif
                            </div>

                            <span class="font-medium">{{ $category->name }}</span>
                        </div>

                        @if($category->children && $category->children->count() > 0)
                        <svg class="w-4 h-4 opacity-50 group-hover:opacity-100 group-hover:translate-x-1 transition-all"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        @endif
                    </a>
                </div>
                @endforeach
            </div>

            {{-- Subcategories & Featured Products (Right Side) --}}
            <div class="flex-1 p-6 overflow-y-auto custom-scrollbar">
                @foreach($categories as $category)
                @php
                    // สินค้าตัวอย่างของหมวด (ใหม่ล่าสุด สูงสุด 4 ชิ้น รวมหมวดลูกหลาน)
                    // มาจาก batch เดียวกับภาพปก จึงไม่ยิงคิวรีเพิ่มต่อหมวด
                    $featuredProducts = $coverService->products($category);
                @endphp
                <div x-show="activeCategory === {{ $category->id }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="space-y-6">

                    {{-- Category Header --}}
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ $category->name }}
                            </h3>
                            @if($category->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ Str::limit($category->description, 80) }}
                            </p>
                            @endif
                        </div>

                        <a href="{{ route('storefront.index', ['category' => $category->slug]) }}"
                           class="flex items-center gap-1 px-4 py-2
                                  bg-gradient-to-r from-orange-500 to-pink-500
                                  hover:from-orange-600 hover:to-pink-600
                                  text-white text-sm font-semibold rounded-lg
                                  shadow hover:shadow-lg transition-all">
                            ดูทั้งหมด
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>

                    {{-- Subcategories Grid --}}
                    @if($category->children && $category->children->count() > 0)
                    <div class="grid grid-cols-3 gap-3">
                        @foreach($category->children->take(9) as $child)
                        @php
                            // ภาพปกของหมวดย่อย (ดึงจาก batch เดียวกัน ไม่มีคิวรีเพิ่ม)
                            $childCover = $coverService->cover($child);
                            $childCoverUrl = $childCover['urls'][0] ?? null;
                            $childIcon = $childCover['icon'] ?? null;
                        @endphp
                        <a href="{{ route('storefront.index', ['category' => $child->slug]) }}"
                           class="flex items-center gap-2 p-3
                                  bg-gray-50 dark:bg-gray-800
                                  hover:bg-gradient-to-r hover:from-orange-50 hover:to-pink-50
                                  dark:hover:from-orange-900/20 dark:hover:to-pink-900/20
                                  rounded-xl border border-gray-100 dark:border-gray-700
                                  hover:border-orange-200 dark:hover:border-orange-700
                                  transition-all duration-200 group">

                            @if($childCoverUrl)
                            {{-- ภาพจริงของหมวดย่อย + ตกไปใช้ไอคอนถ้าโหลดไม่ขึ้น --}}
                            <div class="relative w-10 h-10 rounded-lg overflow-hidden shrink-0
                                       bg-gradient-to-br from-orange-100 to-pink-100
                                       dark:from-orange-900/30 dark:to-pink-900/30"
                                 x-data="{ imageOk: true }">
                                <img src="{{ $childCoverUrl }}"
                                     alt="{{ $child->name }}"
                                     loading="lazy"
                                     decoding="async"
                                     width="40" height="40"
                                     x-show="imageOk"
                                     x-on:error="imageOk = false"
                                     class="w-full h-full object-cover">

                                <span x-show="!imageOk" x-cloak
                                      class="absolute inset-0 flex items-center justify-center">
                                    @if($childIcon)
                                        <i class="{{ $childIcon }} text-orange-500"></i>
                                    @else
                                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                    @endif
                                </span>
                            </div>
                            @else
                            <div class="w-10 h-10 rounded-lg shrink-0 bg-gradient-to-br from-orange-100 to-pink-100
                                       dark:from-orange-900/30 dark:to-pink-900/30
                                       flex items-center justify-center">
                                @if($childIcon)
                                    <i class="{{ $childIcon }} text-orange-500"></i>
                                @else
                                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                @endif
                            </div>
                            @endif

                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300
                                        group-hover:text-orange-600 dark:group-hover:text-orange-400
                                        transition-colors">
                                {{ $child->name }}
                            </span>
                        </a>
                        @endforeach
                    </div>
                    @endif

                    {{-- Featured Products --}}
                    @if(count($featuredProducts) > 0)
                    <div>
                        <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            สินค้ายอดนิยมในหมวดนี้
                        </h4>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            @foreach($featuredProducts as $product)
                            <a href="{{ route('shop.show', $product['slug'] !== '' ? $product['slug'] : $product['id']) }}"
                               class="group">
                                <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-800 mb-2
                                           ring-2 ring-transparent group-hover:ring-orange-500 transition-all"
                                     x-data="{ usedFallback: false }">
                                    {{-- ภาพโหลดไม่ขึ้น → สลับเป็นภาพ no-image ในเครื่อง (ไม่พึ่งโฮสต์ภายนอกที่ตายแล้ว) --}}
                                    <img src="{{ $product['image'] }}"
                                         alt="{{ $product['name'] }}"
                                         loading="lazy"
                                         decoding="async"
                                         width="100" height="100"
                                         x-on:error="if (! usedFallback) { usedFallback = true; $el.src = '{{ $noImageFallback }}'; }"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2 group-hover:text-orange-600 dark:group-hover:text-orange-400">
                                    {{ $product['name'] }}
                                </p>
                                <p class="text-sm font-bold text-orange-600 dark:text-orange-400 mt-1">
                                    ฿{{ number_format($product['price'], 2) }}
                                </p>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach

                {{-- Default State (No category selected) --}}
                <div x-show="!activeCategory" class="flex flex-col items-center justify-center h-full text-center py-12">
                    <div class="w-20 h-20 rounded-full
                               bg-gradient-to-br from-orange-100 to-pink-100
                               dark:from-orange-900/30 dark:to-pink-900/30
                               flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                        เลือกหมวดหมู่เพื่อดูรายละเอียด
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        เลื่อนเมาส์ไปที่หมวดหมู่ทางซ้าย
                    </p>
                </div>
            </div>
        </div>

        {{-- Bottom Banner --}}
        <div class="bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4 text-white">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"/>
                        </svg>
                        <span class="text-sm font-semibold">ส่งฟรีทั่วไทย</span>
                    </div>
                    <div class="w-px h-4 bg-white/30"></div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-semibold">รับประกันคุณภาพ</span>
                    </div>
                    <div class="w-px h-4 bg-white/30"></div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-semibold">เปลี่ยนคืนได้ 7 วัน</span>
                    </div>
                </div>

                <a href="{{ route('storefront.index') }}"
                   class="px-6 py-2 bg-white text-orange-600 font-bold rounded-lg
                         hover:bg-orange-50 transition-colors shadow-lg">
                    ดูสินค้าทั้งหมด
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #f97316, #ec4899);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #ea580c, #db2777);
}
</style>

<script>
/**
 * Mega Menu Alpine Component
 *
 * จัดการการแสดงผล mega menu และ hover states
 */
function megaMenu() {
    return {
        isOpen: false,
        activeCategory: null,

        /**
         * เริ่มต้น component
         */
        init() {
            console.log('Mega Menu initialized');
        },

        /**
         * เปิด/ปิด menu
         */
        toggle() {
            this.isOpen = !this.isOpen;
            if (!this.isOpen) {
                this.activeCategory = null;
            }
        },

        /**
         * ปิด menu
         */
        close() {
            this.isOpen = false;
            this.activeCategory = null;
        }
    };
}
</script>
