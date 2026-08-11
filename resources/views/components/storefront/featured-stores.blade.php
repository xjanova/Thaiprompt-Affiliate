{{--
    Featured Stores Component - สไตล์ AliExpress

    แสดงร้านค้าแนะนำในรูปแบบ carousel หรือ grid
    รองรับ Dark Mode และ Responsive

    @param Collection $stores - ร้านค้าที่จะแสดง
    @param bool $showOfficial - แสดง official store หรือไม่
--}}

@props([
    'stores' => collect(),
    'showOfficial' => true,
    'title' => 'ร้านค้าแนะนำ',
])

<div class="py-8">
    {{-- Section Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500
                       rounded-2xl flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <div>
                <h2 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white">
                    {{ $title }}
                </h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                    ร้านค้าคุณภาพที่คุณไว้วางใจได้
                </p>
            </div>
        </div>

        <a href="{{ route('storefront.stores') }}"
           class="hidden md:flex items-center gap-2 px-6 py-3
                 bg-gradient-to-r from-orange-500 to-red-500
                 hover:from-orange-600 hover:to-red-600
                 text-white font-bold rounded-xl
                 shadow-lg hover:shadow-xl
                 transform hover:scale-105 transition-all group">
            <span>ดูร้านทั้งหมด</span>
            <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>

    {{-- Stores Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        {{-- Official Store Card (Always First) --}}
        @if($showOfficial)
        <div class="group relative bg-white dark:bg-gray-800
                   rounded-3xl overflow-hidden
                   shadow-lg hover:shadow-2xl
                   transform hover:-translate-y-2
                   transition-all duration-300
                   border-2 border-orange-200 dark:border-orange-700
                   hover:border-orange-400">

            {{-- Special Badge --}}
            <div class="absolute top-0 left-0 right-0 z-20
                       bg-gradient-to-r from-orange-500 via-red-500 to-pink-500
                       text-white text-center py-2 font-bold text-sm">
                <span class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Official Store
                </span>
            </div>

            {{-- Banner — เดิมเป็นไล่เฉดส้มเปล่า ๆ ใส่ภาพตลาดไทย (เจนเอง) ให้มีอะไรให้ดู --}}
            <div class="relative h-40 mt-8 overflow-hidden">
                <div class="w-full h-full bg-gradient-to-br from-orange-400 via-red-400 to-pink-400">
                    @if(file_exists(public_path('images/art/hero-storefront.webp')))
                        <img src="{{ asset('images/art/hero-storefront.webp') }}"
                             alt="" aria-hidden="true" loading="lazy" decoding="async"
                             class="absolute inset-0 w-full h-full object-cover">
                    @endif
                    <div class="absolute inset-0 opacity-30">
                        <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 30px 30px;"></div>
                    </div>
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
            </div>

            {{-- Store Info --}}
            <div class="relative px-4 pb-4">
                {{-- Logo --}}
                <div class="relative -mt-10 mb-3 flex justify-center">
                    <div class="w-20 h-20 rounded-2xl overflow-hidden
                               ring-4 ring-white dark:ring-gray-800 shadow-lg
                               bg-gradient-to-br from-orange-500 to-red-500
                               flex items-center justify-center">
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-2">
                    TP Official Store
                </h3>

                <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-4">
                    สินค้าจากทางระบบโดยตรง รับประกันคุณภาพ 100%
                </p>

                {{-- Stats --}}
                <div class="flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400 mb-4">
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <span class="font-semibold">5.0</span>
                    </div>
                    <span>|</span>
                    <span>รับประกันคุณภาพ</span>
                </div>

                {{-- CTA --}}
                <a href="{{ route('official-shop.index') }}"
                   class="block w-full py-3 text-center
                         bg-gradient-to-r from-orange-500 to-red-500
                         hover:from-orange-600 hover:to-red-600
                         text-white font-bold rounded-xl
                         shadow-lg hover:shadow-xl
                         transform hover:scale-105
                         transition-all">
                    เข้าสู่ร้าน Official
                </a>
            </div>
        </div>
        @endif

        {{-- Other Stores --}}
        @foreach($stores as $store)
        <x-storefront.store-card :store="$store" :products="$store->products->take(3)" />
        @endforeach
    </div>

    {{-- Mobile View All Button --}}
    <div class="mt-6 md:hidden text-center">
        <a href="{{ route('storefront.stores') }}"
           class="inline-flex items-center gap-2 px-8 py-3
                 bg-gradient-to-r from-orange-500 to-red-500
                 text-white font-bold rounded-xl shadow-lg">
            ดูร้านค้าทั้งหมด
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
