{{--
    Store Card Component - สไตล์ AliExpress

    แสดงการ์ดร้านค้าพร้อมข้อมูลร้านและสินค้าตัวอย่าง
    รองรับ Dark Mode และ Responsive

    @param VendorStore|null $store - ร้านค้า
    @param bool $isOfficial - เป็นร้านของระบบหรือไม่
--}}

@props([
    'store' => null,
    'isOfficial' => false,
    'products' => collect(),
    // จำนวนสินค้าจริงของร้าน (ถ้าผู้เรียกส่งมา) — ห้ามเดาจาก $products เพราะเป็นแค่ตัวอย่างไม่กี่ชิ้น
    'productCount' => null,
])

@php
    // รูปสำรองเมื่อไม่มีรูป หรือรูปปลายทางโหลดไม่ขึ้น
    $fallbackImage = asset('images/no-image.png');
    $storeName = $store->store_name ?? ($isOfficial ? 'Official Store' : 'ร้านค้า');

    // นับสินค้าแบบซื่อสัตย์:
    // - ใช้ค่าที่ผู้เรียกส่งมา หรือ products_count จาก withCount() เท่านั้น
    // - $products ที่ส่งเข้ามาเป็นชุด "ตัวอย่าง" (มักถูก take(3)) จึงนับไม่ได้ → ซ่อนสถิติไปเลย
    $realProductCount = $productCount ?? ($store->products_count ?? null);
@endphp

<div class="group relative bg-white dark:bg-gray-800
           rounded-3xl overflow-hidden
           shadow-lg hover:shadow-2xl
           transform hover:-translate-y-2
           transition-all duration-300
           border border-gray-100 dark:border-gray-700
           hover:border-purple-200 dark:hover:border-purple-700">

    {{-- Store Banner --}}
    <div class="relative h-32 overflow-hidden">
        @if($store && $store->store_banner)
        <img src="{{ $store->store_banner }}"
             alt="แบนเนอร์ร้าน {{ $storeName }}"
             width="400"
             height="128"
             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
             loading="lazy"
             decoding="async"
             onerror="this.onerror=null; this.src='{{ $fallbackImage }}';"
             style="object-position: center {{ ($store->banner_position_y ?? 50) }}%;">
        @else
        <div class="w-full h-full bg-gradient-to-br
                   {{ $isOfficial ? 'from-purple-600 via-pink-500 to-orange-500' : 'from-blue-500 via-indigo-500 to-purple-500' }}">
            {{-- Decorative Pattern --}}
            <div class="absolute inset-0 opacity-20">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 30px 30px;"></div>
            </div>
        </div>
        @endif

        {{-- Gradient Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

        {{-- Store Badge --}}
        @if($isOfficial)
        <div class="absolute top-3 left-3 z-10">
            <div class="flex items-center gap-1.5 px-3 py-1.5
                       bg-gradient-to-r from-yellow-400 to-orange-500
                       text-white text-xs font-bold rounded-full shadow-lg">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>Official Store</span>
            </div>
        </div>
        @elseif($store && $store->is_verified)
        <div class="absolute top-3 left-3 z-10">
            <div class="flex items-center gap-1.5 px-3 py-1.5
                       bg-gradient-to-r from-blue-500 to-cyan-500
                       text-white text-xs font-bold rounded-full shadow-lg">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>Verified</span>
            </div>
        </div>
        @endif

        {{-- Rating --}}
        @if($store && $store->rating_average > 0)
        <div class="absolute top-3 right-3 z-10">
            <div class="flex items-center gap-1 px-2 py-1
                       bg-white/90 backdrop-blur-sm
                       rounded-lg shadow-lg">
                <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span class="text-sm font-bold text-gray-900">{{ number_format($store->rating_average, 1) }}</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Store Info --}}
    <div class="relative px-4 pb-4">
        {{-- Store Logo (overlapping banner) --}}
        <div class="relative -mt-10 mb-3 flex justify-center">
            <div class="relative">
                <div class="w-20 h-20 rounded-2xl overflow-hidden
                           ring-4 ring-white dark:ring-gray-800
                           shadow-lg group-hover:shadow-xl
                           transition-shadow">
                    @if($store && $store->store_logo)
                    <img src="{{ $store->store_logo }}"
                         alt="โลโก้ร้าน {{ $storeName }}"
                         width="80"
                         height="80"
                         class="w-full h-full object-cover"
                         loading="lazy"
                         decoding="async"
                         onerror="this.onerror=null; this.src='{{ $fallbackImage }}';">
                    @else
                    <div class="w-full h-full bg-gradient-to-br
                               {{ $isOfficial ? 'from-purple-500 to-pink-500' : 'from-blue-500 to-indigo-500' }}
                               flex items-center justify-center">
                        @if($isOfficial)
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        @else
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Online Indicator --}}
                <div class="absolute -bottom-1 -right-1 w-5 h-5
                           bg-green-500 rounded-full
                           border-2 border-white dark:border-gray-800
                           animate-pulse"></div>
            </div>
        </div>

        {{-- Store Name --}}
        <h3 class="text-lg font-bold text-gray-900 dark:text-white text-center mb-1
                  group-hover:text-purple-600 dark:group-hover:text-purple-400
                  transition-colors">
            {{ $storeName }}
        </h3>

        {{-- Store Stats --}}
        <div class="flex items-center justify-center gap-4 text-xs text-gray-500 dark:text-gray-400 mb-4">
            @if(! is_null($realProductCount))
            <div class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span>{{ number_format($realProductCount) }} สินค้า</span>
            </div>
            @endif
            @if($store && $store->rating_count > 0)
            <div class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                </svg>
                <span>{{ number_format($store->rating_count) }} รีวิว</span>
            </div>
            @endif
        </div>

        {{-- Sample Products --}}
        @if($products->count() > 0)
        <div class="grid grid-cols-3 gap-2 mb-4">
            @foreach($products->take(3) as $product)
            <a href="{{ route('shop.show', $product->slug ?: $product->id) }}"
               class="aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700
                       ring-2 ring-transparent hover:ring-purple-400
                       transition-all block">
                <img src="{{ $product->main_image_url ?: $fallbackImage }}"
                     alt="รูปสินค้า {{ $product->name }}"
                     width="100"
                     height="100"
                     class="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                     loading="lazy"
                     decoding="async"
                     onerror="this.onerror=null; this.src='{{ $fallbackImage }}';">
            </a>
            @endforeach
        </div>
        @endif

        {{-- Visit Store Button --}}
        <a href="{{ $store ? route('store.show', $store->store_slug) : ($isOfficial ? route('official-shop.index') : '#') }}"
           class="block w-full py-3 text-center
                 {{ $isOfficial ? 'bg-gradient-to-r from-amber-500 via-purple-500 to-pink-500' : 'bg-gradient-to-r from-purple-500 to-pink-500' }}
                 hover:from-purple-600 hover:to-pink-600
                 text-white font-bold rounded-xl
                 shadow-lg hover:shadow-xl
                 transform hover:scale-105
                 transition-all
                 {{ $isOfficial ? 'ring-2 ring-amber-400/50' : '' }}">
            <span class="flex items-center justify-center gap-2">
                @if($isOfficial)
                <i class="fas fa-crown text-amber-300"></i>
                @else
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                @endif
                {{ $isOfficial ? 'เข้าสู่ร้านทางการ' : 'เยี่ยมชมร้าน' }}
            </span>
        </a>
    </div>

    {{-- Hover Glow Effect --}}
    <div class="absolute inset-0 rounded-3xl pointer-events-none
               opacity-0 group-hover:opacity-100
               transition-opacity duration-300
               shadow-[0_0_40px_rgba(168,85,247,0.3)]"></div>
</div>
