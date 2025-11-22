{{--
    Product Detail Page (V3)

    หน้ารายละเอียดสินค้าแบบครบฟีเจอร์
    - Image Gallery with Lightbox
    - Product Info (PV, Commission, Cashback)
    - Quantity Selector & Add to Cart
    - Tabs (Description, Specs, Reviews)
    - Related Products
    - Seller Info

    @extends layouts.user-arrow-x
--}}

@extends('layouts.user-arrow-x')

@section('title', $product->name)

@section('content')
<div class="container-fluid px-4 py-6" x-data="productDetailComponent({{ $product->id }})">

    {{-- Breadcrumb --}}
    <nav class="mb-6">
        <ol class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <li><a href="{{ route('home') }}" class="hover:text-purple-600 dark:hover:text-purple-400">หน้าแรก</a></li>
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li><a href="{{ route('marketplace.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400">ร้านค้า</a></li>
            @if($product->category)
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li><a href="{{ route('marketplace.category', $product->category->slug) }}" class="hover:text-purple-600 dark:hover:text-purple-400">{{ $product->category->name }}</a></li>
            @endif
            <li><i class="fas fa-chevron-right text-xs"></i></li>
            <li class="text-gray-900 dark:text-white font-medium">{{ Str::limit($product->name, 30) }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        {{-- Left Column - Image Gallery --}}
        <div class="space-y-4">
            {{-- Main Image --}}
            <div class="glass-fusion-card rounded-2xl overflow-hidden backdrop-blur-xl
                        bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30
                        shadow-2xl aspect-square relative group">

                {{-- Badges --}}
                <div class="absolute top-4 left-4 z-10 flex flex-col gap-2">
                    @if($product->is_new ?? false)
                    <span class="px-3 py-1 bg-gradient-to-r from-blue-500 to-purple-600
                                 text-white text-xs font-bold rounded-full shadow-lg">
                        <i class="fas fa-sparkles mr-1"></i>ใหม่
                    </span>
                    @endif

                    @if($product->discount_percentage ?? 0 > 0)
                    <span class="px-3 py-1 bg-gradient-to-r from-red-500 to-pink-600
                                 text-white text-xs font-bold rounded-full shadow-lg">
                        -{{ $product->discount_percentage }}%
                    </span>
                    @endif
                </div>

                {{-- Wishlist & Share --}}
                <div class="absolute top-4 right-4 z-10 flex gap-2">
                    <button @click="toggleWishlist()"
                            class="w-10 h-10 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md
                                   rounded-full flex items-center justify-center
                                   hover:scale-110 transition-all shadow-lg"
                            :class="{ 'text-red-500': isInWishlist, 'text-gray-400 dark:text-gray-500': !isInWishlist }">
                        <i class="fas fa-heart"></i>
                    </button>

                    <button @click="shareProduct()"
                            class="w-10 h-10 bg-white/90 dark:bg-gray-800/90 backdrop-blur-md
                                   text-gray-600 dark:text-gray-300 rounded-full
                                   flex items-center justify-center
                                   hover:scale-110 transition-all shadow-lg">
                        <i class="fas fa-share-alt"></i>
                    </button>
                </div>

                {{-- Main Image Display --}}
                <img :src="currentImage"
                     alt="{{ $product->name }}"
                     class="w-full h-full object-cover cursor-zoom-in"
                     @click="openLightbox()">

                {{-- Zoom Button --}}
                <button @click="openLightbox()"
                        class="absolute bottom-4 right-4 px-4 py-2
                               bg-black/60 backdrop-blur-md text-white rounded-lg
                               opacity-0 group-hover:opacity-100 transition-opacity">
                    <i class="fas fa-search-plus mr-2"></i>ซูม
                </button>
            </div>

            {{-- Thumbnail Gallery --}}
            @if(count($product->images ?? []) > 1)
            <div class="grid grid-cols-5 gap-3">
                @foreach($product->images as $index => $image)
                <button @click="currentImage = '{{ $image->url }}'; currentImageIndex = {{ $index }}"
                        class="aspect-square rounded-lg overflow-hidden border-2 transition-all"
                        :class="currentImage === '{{ $image->url }}' ? 'border-purple-600 ring-4 ring-purple-600/20' : 'border-gray-200 dark:border-gray-700 hover:border-purple-400'">
                    <img src="{{ $image->url }}"
                         alt="รูปที่ {{ $index + 1 }}"
                         class="w-full h-full object-cover">
                </button>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Right Column - Product Info --}}
        <div class="space-y-6">
            {{-- Product Name --}}
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-3">
                    {{ $product->name }}
                </h1>

                {{-- Rating & Sales --}}
                <div class="flex items-center gap-4 mb-4">
                    @if($product->average_rating ?? 0 > 0)
                    <div class="flex items-center gap-2">
                        <div class="flex items-center text-yellow-400">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($product->average_rating))
                                <i class="fas fa-star"></i>
                                @elseif($i - 0.5 <= $product->average_rating)
                                <i class="fas fa-star-half-alt"></i>
                                @else
                                <i class="far fa-star"></i>
                                @endif
                            @endfor
                        </div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ number_format($product->average_rating, 1) }}
                        </span>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            ({{ number_format($product->reviews_count ?? 0) }} รีวิว)
                        </span>
                    </div>
                    @endif

                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-shopping-bag mr-1"></i>
                        ขายแล้ว {{ number_format($product->sold_count ?? 0) }} ชิ้น
                    </div>
                </div>

                {{-- Category --}}
                @if($product->category)
                <a href="{{ route('marketplace.category', $product->category->slug) }}"
                   class="inline-block px-3 py-1 bg-purple-100 dark:bg-purple-900/30
                          text-purple-700 dark:text-purple-300 text-sm font-medium rounded-lg
                          hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">
                    <i class="fas fa-tag mr-1"></i>{{ $product->category->name }}
                </a>
                @endif
            </div>

            {{-- Price Section --}}
            <div class="glass-fusion-card rounded-2xl p-6 backdrop-blur-xl
                        bg-gradient-to-br from-purple-50 to-pink-50
                        dark:from-purple-900/20 dark:to-pink-900/20
                        border border-purple-200 dark:border-purple-700/30">

                <div class="flex items-baseline gap-4 mb-4">
                    @if($product->sale_price && $product->sale_price < $product->price)
                    <span class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600
                                 bg-clip-text text-transparent">
                        ฿{{ number_format($product->sale_price, 2) }}
                    </span>
                    <span class="text-xl text-gray-400 dark:text-gray-500 line-through">
                        ฿{{ number_format($product->price, 2) }}
                    </span>
                    <span class="px-3 py-1 bg-red-500 text-white text-sm font-bold rounded-full">
                        ประหยัด ฿{{ number_format($product->price - $product->sale_price, 2) }}
                    </span>
                    @else
                    <span class="text-4xl font-bold bg-gradient-to-r from-purple-600 to-pink-600
                                 bg-clip-text text-transparent">
                        ฿{{ number_format($product->price, 2) }}
                    </span>
                    @endif
                </div>

                {{-- MLM Benefits (ถ้า login) --}}
                @auth
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @if(($product->pv_value ?? 0) > 0)
                    <div class="flex items-center gap-2 px-3 py-2
                                bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <i class="fas fa-coins text-blue-600 dark:text-blue-400"></i>
                        <div class="text-sm">
                            <div class="font-medium text-blue-700 dark:text-blue-300">{{ number_format($product->pv_value) }} PV</div>
                            <div class="text-xs text-blue-600 dark:text-blue-400">Point Value</div>
                        </div>
                    </div>
                    @endif

                    @if(($product->commission_rate ?? 0) > 0 && auth()->user()->mlmMember)
                    <div class="flex items-center gap-2 px-3 py-2
                                bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <i class="fas fa-percent text-green-600 dark:text-green-400"></i>
                        <div class="text-sm">
                            <div class="font-medium text-green-700 dark:text-green-300">{{ $product->commission_rate }}%</div>
                            <div class="text-xs text-green-600 dark:text-green-400">Commission</div>
                        </div>
                    </div>
                    @endif

                    @if(($product->customer_cashback ?? 0) > 0)
                    <div class="flex items-center gap-2 px-3 py-2
                                bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <i class="fas fa-gift text-orange-600 dark:text-orange-400"></i>
                        <div class="text-sm">
                            <div class="font-medium text-orange-700 dark:text-orange-300">฿{{ number_format($product->customer_cashback) }}</div>
                            <div class="text-xs text-orange-600 dark:text-orange-400">Cashback</div>
                        </div>
                    </div>
                    @endif
                </div>
                @endauth
            </div>

            {{-- Stock Status --}}
            <div class="flex items-center gap-3">
                @if($product->stock_quantity > 0)
                <span class="text-green-600 dark:text-green-400 font-medium">
                    <i class="fas fa-check-circle mr-1"></i>มีสินค้าพร้อมส่ง
                </span>
                @if($product->stock_quantity <= 10)
                <span class="text-orange-500 dark:text-orange-400 text-sm">
                    (เหลือ {{ $product->stock_quantity }} ชิ้น)
                </span>
                @endif
                @else
                <span class="text-red-600 dark:text-red-400 font-medium">
                    <i class="fas fa-times-circle mr-1"></i>สินค้าหมด
                </span>
                @endif
            </div>

            {{-- Quantity Selector --}}
            @if($product->stock_quantity > 0)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    จำนวน
                </label>
                <div class="flex items-center gap-3">
                    <div class="flex items-center border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <button @click="quantity = Math.max(1, quantity - 1)"
                                class="px-4 py-3 bg-gray-50 dark:bg-gray-700
                                       hover:bg-gray-100 dark:hover:bg-gray-600
                                       text-gray-700 dark:text-gray-300 transition-colors">
                            <i class="fas fa-minus"></i>
                        </button>

                        <input type="number"
                               x-model="quantity"
                               min="1"
                               :max="{{ $product->stock_quantity }}"
                               class="w-20 px-4 py-3 text-center font-bold
                                      bg-white dark:bg-gray-800
                                      text-gray-900 dark:text-white
                                      border-0 focus:ring-0">

                        <button @click="quantity = Math.min({{ $product->stock_quantity }}, quantity + 1)"
                                class="px-4 py-3 bg-gray-50 dark:bg-gray-700
                                       hover:bg-gray-100 dark:hover:bg-gray-600
                                       text-gray-700 dark:text-gray-300 transition-colors">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        สูงสุด {{ $product->stock_quantity }} ชิ้น
                    </span>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-3">
                <button @click="addToCart()"
                        :disabled="isAddingToCart"
                        class="flex-1 px-6 py-4
                               bg-gradient-to-r from-purple-600 via-pink-600 to-red-500
                               hover:from-purple-700 hover:via-pink-700 hover:to-red-600
                               text-white font-bold rounded-xl
                               shadow-lg hover:shadow-2xl
                               transform hover:scale-105 active:scale-95
                               transition-all duration-300
                               disabled:opacity-50 disabled:cursor-not-allowed
                               flex items-center justify-center gap-2">
                    <i class="fas text-lg"
                       :class="isAddingToCart ? 'fa-spinner fa-spin' : 'fa-cart-plus'"></i>
                    <span x-text="isAddingToCart ? 'กำลังเพิ่ม...' : 'เพิ่มลงตะกร้า'"></span>
                </button>

                <button @click="buyNow()"
                        class="px-6 py-4 bg-gradient-to-r from-orange-500 to-red-500
                               hover:from-orange-600 hover:to-red-600
                               text-white font-bold rounded-xl
                               shadow-lg hover:shadow-2xl
                               transform hover:scale-105 active:scale-95
                               transition-all duration-300">
                    <i class="fas fa-bolt mr-2"></i>ซื้อเลย
                </button>
            </div>
            @else
            <button disabled
                    class="w-full px-6 py-4 bg-gray-300 dark:bg-gray-700
                           text-gray-500 dark:text-gray-400 font-bold rounded-xl
                           cursor-not-allowed">
                <i class="fas fa-ban mr-2"></i>สินค้าหมด
            </button>
            @endif

            {{-- Seller Info --}}
            @if($product->seller)
            <div class="glass-fusion-card rounded-xl p-4 backdrop-blur-xl
                        bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30">
                <div class="flex items-center gap-3">
                    @if($product->seller->logo_url)
                    <img src="{{ $product->seller->logo_url }}"
                         alt="{{ $product->seller->name }}"
                         class="w-12 h-12 rounded-full object-cover">
                    @else
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500
                                flex items-center justify-center text-white font-bold">
                        {{ substr($product->seller->name, 0, 1) }}
                    </div>
                    @endif

                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            {{ $product->seller->name }}
                        </h3>
                        @if($product->seller->average_rating ?? 0 > 0)
                        <div class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400">
                            <i class="fas fa-star text-yellow-400"></i>
                            <span>{{ number_format($product->seller->average_rating, 1) }}</span>
                        </div>
                        @endif
                    </div>

                    <a href="{{ route('marketplace.store', $product->seller->slug) }}"
                       class="px-4 py-2 bg-gray-100 dark:bg-gray-700
                              text-gray-700 dark:text-gray-300 text-sm font-medium
                              rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600
                              transition-colors">
                        เยี่ยมชมร้าน
                    </a>
                </div>
            </div>
            @endif

            {{-- Shipping & Guarantee --}}
            <div class="space-y-3 text-sm">
                <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-truck text-purple-600 dark:text-purple-400 w-5"></i>
                    <span>จัดส่งฟรีเมื่อซื้อครบ ฿{{ number_format($product->free_shipping_threshold ?? 500) }}</span>
                </div>

                <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-shield-alt text-green-600 dark:text-green-400 w-5"></i>
                    <span>รับประกันสินค้า 7 วัน</span>
                </div>

                <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-undo text-blue-600 dark:text-blue-400 w-5"></i>
                    <span>เปลี่ยน-คืนสินค้าภายใน 14 วัน</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Section --}}
    <div class="glass-fusion-card rounded-2xl overflow-hidden backdrop-blur-xl
                bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/30
                shadow-2xl mb-12"
         x-data="{ activeTab: 'description' }">

        {{-- Tab Headers --}}
        <div class="flex border-b border-gray-200 dark:border-gray-700">
            <button @click="activeTab = 'description'"
                    class="px-6 py-4 font-semibold transition-colors relative"
                    :class="activeTab === 'description' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                <i class="fas fa-file-alt mr-2"></i>รายละเอียด

                <div x-show="activeTab === 'description'"
                     class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-purple-600 to-pink-600"></div>
            </button>

            <button @click="activeTab = 'specs'"
                    class="px-6 py-4 font-semibold transition-colors relative"
                    :class="activeTab === 'specs' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                <i class="fas fa-list mr-2"></i>สเปค

                <div x-show="activeTab === 'specs'"
                     class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-purple-600 to-pink-600"></div>
            </button>

            <button @click="activeTab = 'reviews'"
                    class="px-6 py-4 font-semibold transition-colors relative"
                    :class="activeTab === 'reviews' ? 'text-purple-600 dark:text-purple-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'">
                <i class="fas fa-star mr-2"></i>รีวิว
                <span class="ml-1 px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-xs rounded-full">
                    {{ $product->reviews_count ?? 0 }}
                </span>

                <div x-show="activeTab === 'reviews'"
                     class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-purple-600 to-pink-600"></div>
            </button>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">
            {{-- Description Tab --}}
            <div x-show="activeTab === 'description'" x-transition>
                <div class="prose dark:prose-invert max-w-none">
                    {!! $product->description ?? '<p class="text-gray-500 dark:text-gray-400">ไม่มีรายละเอียด</p>' !!}
                </div>
            </div>

            {{-- Specs Tab --}}
            <div x-show="activeTab === 'specs'" x-transition>
                @if($product->specifications ?? null)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($product->specifications as $key => $value)
                    <div class="flex py-3 border-b border-gray-200 dark:border-gray-700">
                        <span class="w-1/3 font-medium text-gray-700 dark:text-gray-300">{{ $key }}</span>
                        <span class="w-2/3 text-gray-900 dark:text-white">{{ $value }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500 dark:text-gray-400">ไม่มีข้อมูลสเปค</p>
                @endif
            </div>

            {{-- Reviews Tab --}}
            <div x-show="activeTab === 'reviews'" x-transition>
                @if($product->reviews && $product->reviews->count() > 0)
                <div class="space-y-6">
                    @foreach($product->reviews as $review)
                    <div class="pb-6 border-b border-gray-200 dark:border-gray-700 last:border-0">
                        <div class="flex items-start gap-4">
                            <img src="{{ $review->user->avatar_url ?? asset('images/default-avatar.png') }}"
                                 alt="{{ $review->user->name }}"
                                 class="w-12 h-12 rounded-full object-cover">

                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">
                                        {{ $review->user->name }}
                                    </h4>

                                    <div class="flex items-center text-yellow-400">
                                        @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star text-xs {{ $i <= $review->rating ? '' : 'text-gray-300 dark:text-gray-600' }}"></i>
                                        @endfor
                                    </div>

                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $review->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                <p class="text-gray-700 dark:text-gray-300 mb-3">
                                    {{ $review->comment }}
                                </p>

                                @if($review->images && count($review->images) > 0)
                                <div class="flex gap-2">
                                    @foreach($review->images as $image)
                                    <img src="{{ $image->url }}"
                                         alt="รีวิว"
                                         class="w-20 h-20 rounded-lg object-cover cursor-pointer hover:opacity-80 transition-opacity">
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-12">
                    <i class="fas fa-star text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มีรีวิวสำหรับสินค้านี้</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Related Products --}}
    @if(isset($relatedProducts) && $relatedProducts->count() > 0)
    <div class="mb-12">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <i class="fas fa-boxes mr-2 text-purple-600"></i>สินค้าที่เกี่ยวข้อง
            </h2>

            <a href="{{ route('marketplace.category', $product->category->slug) }}"
               class="text-purple-600 dark:text-purple-400 hover:underline">
                ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($relatedProducts->take(4) as $relatedProduct)
            <x-ecommerce.product-card-premium
                :product="$relatedProduct"
                :showPv="auth()->check()"
                :showCommission="auth()->check() && optional(auth()->user()->mlmMember)->exists()"
            />
            @endforeach
        </div>
    </div>
    @endif

    {{-- Lightbox Modal --}}
    <div x-show="lightboxOpen"
         x-transition
         @keydown.escape.window="lightboxOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90"
         style="display: none;">

        <button @click="lightboxOpen = false"
                class="absolute top-4 right-4 w-12 h-12
                       bg-white/10 backdrop-blur-md text-white rounded-full
                       hover:bg-white/20 transition-all">
            <i class="fas fa-times text-xl"></i>
        </button>

        <img :src="currentImage"
             alt="Zoomed"
             class="max-w-full max-h-full object-contain">
    </div>
</div>

@push('scripts')
<script>
/**
 * Product Detail Component - จัดการหน้ารายละเอียดสินค้า
 */
function productDetailComponent(productId) {
    return {
        productId: productId,
        quantity: 1,
        currentImage: '{{ $product->primary_image_url ?? asset("images/placeholder.png") }}',
        currentImageIndex: 0,
        isInWishlist: false,
        isAddingToCart: false,
        lightboxOpen: false,

        init() {
            this.checkWishlist();
        },

        checkWishlist() {
            const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            this.isInWishlist = wishlist.includes(this.productId);
        },

        toggleWishlist() {
            let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');

            if (this.isInWishlist) {
                wishlist = wishlist.filter(id => id !== this.productId);
                this.$dispatch('notify', {
                    message: 'ลบออกจากรายการโปรดแล้ว',
                    type: 'info'
                });
            } else {
                wishlist.push(this.productId);
                this.$dispatch('notify', {
                    message: 'เพิ่มในรายการโปรดแล้ว',
                    type: 'success'
                });
            }

            localStorage.setItem('wishlist', JSON.stringify(wishlist));
            this.isInWishlist = !this.isInWishlist;
        },

        async addToCart() {
            this.isAddingToCart = true;

            try {
                const response = await fetch('/api/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        product_id: this.productId,
                        quantity: this.quantity
                    })
                });

                if (response.ok) {
                    this.$dispatch('cart-updated');
                    this.$dispatch('notify', {
                        message: `เพิ่ม ${this.quantity} ชิ้นลงตะกร้าแล้ว`,
                        type: 'success'
                    });
                } else {
                    throw new Error('ไม่สามารถเพิ่มสินค้าได้');
                }
            } catch (error) {
                console.error('Add to cart error:', error);
                this.$dispatch('notify', {
                    message: 'เกิดข้อผิดพลาด กรุณาลองใหม่',
                    type: 'error'
                });
            } finally {
                this.isAddingToCart = false;
            }
        },

        buyNow() {
            // เพิ่มลงตะกร้าแล้วไปหน้า checkout
            this.addToCart().then(() => {
                window.location.href = '/checkout';
            });
        },

        shareProduct() {
            if (navigator.share) {
                navigator.share({
                    title: '{{ $product->name }}',
                    text: 'ดูสินค้านี้สิ!',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href);
                this.$dispatch('notify', {
                    message: 'คัดลอกลิงก์แล้ว',
                    type: 'success'
                });
            }
        },

        openLightbox() {
            this.lightboxOpen = true;
        }
    };
}
</script>
@endpush
@endsection
