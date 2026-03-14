{{--
    หน้ารายละเอียดสินค้า - ตลาดสดไทยพร๊อม

    ตัวแปรที่ใช้:
    - $listing: ข้อมูลสินค้า (model)
    - $relatedListings: สินค้าที่เกี่ยวข้อง (collection)
--}}
@extends('layouts.taladsod')

@section('title', ($listing->title ?? 'รายละเอียดสินค้า') . ' - ตลาดสดไทยพร๊อม')

@section('meta')
    <meta property="og:title" content="{{ $listing->title ?? 'สินค้า' }} - ตลาดสดไทยพร๊อม">
    <meta property="og:description" content="{{ Str::limit($listing->description ?? '', 150) }}">
    @if($listing->image_url ?? false)
        <meta property="og:image" content="{{ $listing->image_url }}">
    @endif
@endsection

@section('content')

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

        {{-- Breadcrumb --}}
        <nav class="mb-6 text-sm">
            <ol class="flex items-center gap-2 text-gray-500 dark:text-gray-400 flex-wrap">
                <li><a href="{{ route('taladsod.home') }}" class="hover:text-green-600 dark:hover:text-green-400 transition-colors">หน้าแรก</a></li>
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li><a href="{{ route('taladsod.search') }}" class="hover:text-green-600 dark:hover:text-green-400 transition-colors">ค้นหาสินค้า</a></li>
                @if($listing->category ?? false)
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li><a href="{{ route('taladsod.search', ['category' => $listing->category->slug ?? '']) }}" class="hover:text-green-600 dark:hover:text-green-400 transition-colors">{{ $listing->category->name ?? 'หมวดหมู่' }}</a></li>
                @endif
                <li><i class="fas fa-chevron-right text-xs"></i></li>
                <li class="text-gray-900 dark:text-white font-medium truncate max-w-[200px]">{{ $listing->title ?? 'สินค้า' }}</li>
            </ol>
        </nav>

        <div class="grid lg:grid-cols-2 gap-6 lg:gap-10">

            {{-- ===== แกลเลอรีรูปภาพ ===== --}}
            <div x-data="imageGallery({{ json_encode($listing->images ?? [$listing->image_url ?? '']) }})">
                {{-- รูปหลัก --}}
                <div class="relative aspect-square rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-700 shadow-lg mb-4 group cursor-pointer"
                     @click="openLightbox()">
                    <template x-if="currentImage">
                        <img :src="currentImage"
                             :alt="'{{ $listing->title ?? 'สินค้า' }}'"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    </template>
                    <template x-if="!currentImage">
                        <div class="w-full h-full flex items-center justify-center text-8xl bg-gradient-to-br from-green-50 to-green-100 dark:from-gray-700 dark:to-gray-600">
                            🥬
                        </div>
                    </template>

                    {{-- ป้ายมุมซ้ายบน --}}
                    <div class="absolute top-3 left-3 flex flex-col gap-2">
                        @if($listing->is_organic ?? false)
                            <span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full flex items-center gap-1 shadow-md">
                                <i class="fas fa-leaf"></i> ออร์แกนิค
                            </span>
                        @endif
                        @if($listing->is_fresh ?? true)
                            <span class="px-3 py-1 bg-teal-500 text-white text-xs font-bold rounded-full flex items-center gap-1 shadow-md">
                                <i class="fas fa-check-circle"></i> สดใหม่วันนี้
                            </span>
                        @endif
                    </div>

                    {{-- ป้ายเงินคืน --}}
                    @if($listing->cashback_percent ?? false)
                        <div class="absolute top-3 right-3 px-3 py-1.5 bg-orange-500 text-white text-sm font-bold rounded-full shadow-md">
                            💰 คืน {{ $listing->cashback_percent }}%
                        </div>
                    @endif

                    {{-- ปุ่มนำทาง --}}
                    <template x-if="images.length > 1">
                        <div>
                            <button @click.stop="prev()" class="absolute left-3 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/40 hover:bg-black/60 backdrop-blur-sm text-white rounded-full flex items-center justify-center transition-colors opacity-0 group-hover:opacity-100">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button @click.stop="next()" class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/40 hover:bg-black/60 backdrop-blur-sm text-white rounded-full flex items-center justify-center transition-colors opacity-0 group-hover:opacity-100">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </template>

                    {{-- ไอคอนขยาย --}}
                    <div class="absolute bottom-3 right-3 w-8 h-8 bg-black/40 backdrop-blur-sm text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <i class="fas fa-expand text-sm"></i>
                    </div>
                </div>

                {{-- รูปย่อย --}}
                <template x-if="images.length > 1">
                    <div class="flex gap-2 overflow-x-auto scrollbar-hide pb-2">
                        <template x-for="(image, index) in images" :key="index">
                            <button @click="goTo(index)"
                                    class="flex-shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border-2 transition-all"
                                    :class="currentIndex === index ? 'border-green-500 ring-2 ring-green-200 dark:ring-green-800' : 'border-transparent opacity-60 hover:opacity-100'">
                                <img :src="image" alt="" class="w-full h-full object-cover">
                            </button>
                        </template>
                    </div>
                </template>

                {{-- Lightbox --}}
                <div x-show="showLightbox"
                     x-cloak
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @keydown.escape.window="closeLightbox()"
                     class="fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4">
                    <button @click="closeLightbox()" class="absolute top-4 right-4 w-10 h-10 bg-white/20 hover:bg-white/30 text-white rounded-full flex items-center justify-center transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                    <button @click="prev()" class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-full flex items-center justify-center transition-colors">
                        <i class="fas fa-chevron-left text-lg"></i>
                    </button>
                    <button @click="next()" class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-full flex items-center justify-center transition-colors">
                        <i class="fas fa-chevron-right text-lg"></i>
                    </button>
                    <img :src="currentImage" alt="" class="max-w-full max-h-[85vh] object-contain rounded-lg">
                </div>
            </div>

            {{-- ===== ข้อมูลสินค้า ===== --}}
            <div class="space-y-6">

                {{-- ชื่อสินค้าและราคา --}}
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-3">
                        {{ $listing->title ?? 'ชื่อสินค้า' }}
                    </h1>
                    <div class="flex items-baseline gap-3 mb-4">
                        <span class="text-3xl sm:text-4xl font-extrabold text-green-600 dark:text-green-400">
                            ฿{{ number_format($listing->price ?? 0, 0) }}
                        </span>
                        <span class="text-base sm:text-lg text-gray-500 dark:text-gray-400">
                            /{{ $listing->unit ?? 'กก.' }}
                        </span>
                    </div>

                    {{-- ป้ายต่างๆ --}}
                    <div class="flex flex-wrap gap-2">
                        @if($listing->is_organic ?? false)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm font-medium rounded-full border border-green-200 dark:border-green-800">
                                <i class="fas fa-leaf"></i> ออร์แกนิค
                            </span>
                        @endif
                        @if($listing->is_fresh ?? true)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-teal-50 dark:bg-teal-900/30 text-teal-700 dark:text-teal-400 text-sm font-medium rounded-full border border-teal-200 dark:border-teal-800">
                                <i class="fas fa-check-circle"></i> สดใหม่
                            </span>
                        @endif
                        @if($listing->cashback_percent ?? false)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 text-sm font-medium rounded-full border border-orange-200 dark:border-orange-800">
                                💰 เงินคืน {{ $listing->cashback_percent }}%
                            </span>
                        @endif
                        @if($listing->quantity ?? false)
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-sm font-medium rounded-full border border-blue-200 dark:border-blue-800">
                                <i class="fas fa-box"></i> คงเหลือ {{ $listing->quantity }} {{ $listing->unit ?? 'กก.' }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- คำอธิบาย --}}
                @if($listing->description ?? false)
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">รายละเอียดสินค้า</h2>
                        <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-300 leading-relaxed">
                            {!! nl2br(e($listing->description)) !!}
                        </div>
                    </div>
                @endif

                {{-- การ์ดผู้ขาย --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 sm:p-5 shadow-sm">
                    <div class="flex items-center gap-4">
                        {{-- อวาตาร์ --}}
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-md ring-2 ring-green-100 dark:ring-green-900/50 flex-shrink-0">
                            @if($listing->seller->avatar_url ?? false)
                                <img src="{{ $listing->seller->avatar_url }}" alt="" class="w-full h-full rounded-full object-cover">
                            @else
                                <span class="text-xl text-white font-bold">
                                    {{ mb_substr($listing->seller->shop_name ?? 'ร', 0, 1) }}
                                </span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                                {{ $listing->seller->shop_name ?? 'ร้านค้า' }}
                            </h3>
                            <div class="flex items-center gap-3 mt-1">
                                {{-- ดาวรีวิว --}}
                                <div class="flex items-center gap-0.5">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($listing->seller->rating ?? 0))
                                            <i class="fas fa-star text-yellow-400 text-xs"></i>
                                        @else
                                            <i class="far fa-star text-gray-300 dark:text-gray-600 text-xs"></i>
                                        @endif
                                    @endfor
                                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">{{ number_format($listing->seller->rating ?? 0, 1) }}</span>
                                </div>
                                {{-- ระยะทาง --}}
                                @if($listing->distance ?? false)
                                    <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                        <i class="fas fa-location-dot text-green-500"></i>
                                        {{ number_format($listing->distance, 1) }} กม.
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== ปุ่มดำเนินการ ===== --}}
                <div class="space-y-3" x-data="{ showOrderForm: false, quantity: 1, deliveryType: 'pickup', ordering: false }">
                    {{-- ปุ่มสั่งซื้อ --}}
                    @auth
                        <button @click="showOrderForm = !showOrderForm"
                                class="w-full py-4 bg-green-500 hover:bg-green-600 text-white text-lg font-bold rounded-2xl transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] flex items-center justify-center gap-2">
                            🛒 สั่งซื้อ
                        </button>

                        {{-- ฟอร์มสั่งซื้อ --}}
                        <div x-show="showOrderForm" x-transition class="p-4 bg-green-50 dark:bg-green-900/20 rounded-2xl space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">จำนวน</label>
                                <input type="number" x-model.number="quantity" min="1" max="{{ $listing->quantity_available }}"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วิธีรับสินค้า</label>
                                <select x-model="deliveryType"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                    <option value="pickup">รับเอง</option>
                                    <option value="rider">ส่งโดยไรเดอร์</option>
                                </select>
                            </div>
                            <form method="POST" action="{{ route('taladsod.order.store') }}" @submit="ordering = true">
                                @csrf
                                <input type="hidden" name="listing_id" value="{{ $listing->id }}">
                                <input type="hidden" name="quantity" :value="quantity">
                                <input type="hidden" name="delivery_type" :value="deliveryType">
                                <button type="submit" :disabled="ordering"
                                        class="w-full py-3 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-bold rounded-xl transition-all">
                                    <span x-show="!ordering">✅ ยืนยันสั่งซื้อ ฿<span x-text="({{ $listing->price }} * quantity).toLocaleString()"></span></span>
                                    <span x-show="ordering">กำลังสั่งซื้อ...</span>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login', ['redirect' => url()->current()]) }}"
                           class="w-full py-4 bg-green-500 hover:bg-green-600 text-white text-lg font-bold rounded-2xl transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] flex items-center justify-center gap-2">
                            🛒 เข้าสู่ระบบเพื่อสั่งซื้อ
                        </a>
                    @endauth

                    <div class="grid grid-cols-2 gap-3">
                        {{-- ปุ่มแชท LINE --}}
                        @if($listing->seller->line_url ?? false)
                            <a href="{{ $listing->seller->line_url }}" target="_blank" rel="noopener"
                               class="py-3 bg-[#06C755] hover:bg-[#05A847] text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md flex items-center justify-center gap-2 text-sm sm:text-base">
                                💬 แชทผ่าน LINE
                            </a>
                        @else
                            <a href="{{ config('services.line.fresh_market_add_friend_url', '#') }}" target="_blank" rel="noopener"
                               class="py-3 bg-[#06C755] hover:bg-[#05A847] text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md flex items-center justify-center gap-2 text-sm sm:text-base">
                                💬 เพิ่มเพื่อน LINE
                            </a>
                        @endif

                        {{-- ปุ่มโทร --}}
                        @if($listing->seller->phone ?? false)
                            <a href="tel:{{ $listing->seller->phone }}"
                               class="py-3 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-xl transition-all shadow-sm hover:shadow-md flex items-center justify-center gap-2 text-sm sm:text-base">
                                📞 โทร
                            </a>
                        @else
                            <span class="py-3 bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 font-medium rounded-xl flex items-center justify-center gap-2 text-sm sm:text-base cursor-not-allowed">
                                📞 ไม่มีเบอร์โทร
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== สินค้าที่เกี่ยวข้อง ===== --}}
        <section class="mt-12 sm:mt-16">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mb-6">
                <span class="text-green-500">🌱</span> สินค้าที่เกี่ยวข้อง
            </h2>

            @if(isset($relatedListings) && $relatedListings->count() > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    @foreach($relatedListings as $related)
                        <a href="{{ route('taladsod.listing', $related->id) }}"
                           class="group bg-white dark:bg-gray-800 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden hover:scale-[1.03] hover:-translate-y-1">

                            {{-- รูปสินค้า --}}
                            <div class="relative aspect-[4/3] overflow-hidden bg-gray-100 dark:bg-gray-700">
                                @if($related->image_url ?? false)
                                    <img src="{{ $related->image_url }}"
                                         alt="{{ $related->title }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                         loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-5xl bg-gradient-to-br from-green-50 to-green-100 dark:from-gray-700 dark:to-gray-600">
                                        🥬
                                    </div>
                                @endif

                                @if($related->cashback_percent ?? false)
                                    <div class="absolute top-2 left-2 px-2 py-1 bg-orange-500 text-white text-xs font-bold rounded-full">
                                        คืน {{ $related->cashback_percent }}%
                                    </div>
                                @endif
                            </div>

                            {{-- ข้อมูลสินค้า --}}
                            <div class="p-3 sm:p-4">
                                <h3 class="text-sm sm:text-base font-semibold text-gray-900 dark:text-white line-clamp-2 mb-1 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">
                                    {{ $related->title }}
                                </h3>
                                <div class="flex items-baseline gap-1.5 mb-2">
                                    <span class="text-lg sm:text-xl font-bold text-green-600 dark:text-green-400">
                                        ฿{{ number_format($related->price, 0) }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        /{{ $related->unit ?? 'กก.' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-store text-green-500"></i>
                                    <span class="truncate">{{ $related->seller->shop_name ?? 'ร้านค้า' }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
                    <div class="text-5xl mb-3">🌿</div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ยังไม่มีสินค้าที่เกี่ยวข้อง</p>
                </div>
            @endif
        </section>
    </div>

@endsection
