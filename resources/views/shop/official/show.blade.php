{{-- resources/views/shop/official/show.blade.php --}}

@extends('layouts.user-arrow-x')

@section('title', $product->name . ' - ร้านของระบบ')

@section('content')
<div x-data="officialProductManager()" x-init="init()" class="min-h-screen pb-12">

    {{-- ========================================
         Hero Section - Product Header
         ======================================== --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-pink-500 to-orange-500 py-8">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(255,255,255,0.3),transparent_50%)]"></div>
        </div>

        {{-- Breadcrumb --}}
        <div class="container-fluid px-4 relative z-10">
            <div class="max-w-7xl mx-auto">
                <nav class="flex items-center gap-2 text-sm text-white/80 mb-4">
                    <a href="{{ route('home') }}" class="hover:text-white transition-colors">
                        <i class="fas fa-home"></i>
                    </a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <a href="{{ route('official-shop.index') }}" class="hover:text-white transition-colors">
                        ร้านของระบบ
                    </a>
                    @if($product->category)
                    <i class="fas fa-chevron-right text-xs"></i>
                    <a href="{{ route('official-shop.category', $product->category->slug) }}" class="hover:text-white transition-colors">
                        {{ $product->category->name }}
                    </a>
                    @endif
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-white font-semibold">{{ Str::limit($product->name, 50) }}</span>
                </nav>
            </div>
        </div>
    </div>

    {{-- ========================================
         Product Detail Section
         ======================================== --}}
    <div class="container-fluid px-4 -mt-4 relative z-20">
        <div class="max-w-7xl mx-auto">

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">

                {{-- ========== Left: Product Images ========== --}}
                <div>
                    {{-- Main Image --}}
                    <div class="relative bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden border-2 border-purple-200 dark:border-purple-700 mb-4">
                        <div class="aspect-square relative">
                            {{-- Official Badge --}}
                            <div class="absolute top-4 left-4 z-10">
                                <div class="px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white text-sm font-bold rounded-full shadow-lg flex items-center gap-2">
                                    <i class="fas fa-shield-check"></i>
                                    <span>ร้านทางการ</span>
                                </div>
                            </div>

                            {{-- Featured Badge --}}
                            @if($product->is_featured)
                            <div class="absolute top-4 right-4 z-10">
                                <div class="px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500 text-white text-sm font-bold rounded-full shadow-lg flex items-center gap-2">
                                    <i class="fas fa-star"></i>
                                    <span>สินค้าแนะนำ</span>
                                </div>
                            </div>
                            @endif

                            <img src="{{ $product->main_image_url ?? 'https://via.placeholder.com/800' }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover">
                        </div>
                    </div>

                    {{-- Thumbnail Images --}}
                    @if($product->images && $product->images->count() > 0)
                    <div class="grid grid-cols-4 gap-4">
                        <div class="aspect-square bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border-2 border-purple-500 dark:border-purple-600 cursor-pointer">
                            <img src="{{ $product->main_image_url }}" alt="Main" class="w-full h-full object-cover">
                        </div>
                        @foreach($product->images->take(3) as $image)
                        <div class="aspect-square bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border-2 border-gray-200 dark:border-gray-700 hover:border-purple-500 dark:hover:border-purple-600 cursor-pointer transition-colors">
                            <img src="{{ $image->url }}" alt="Image {{ $loop->iteration }}" class="w-full h-full object-cover">
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- ========== Right: Product Info ========== --}}
                <div>
                    {{-- Product Name --}}
                    <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-4">
                        {{ $product->name }}
                    </h1>

                    {{-- Rating & Reviews --}}
                    <div class="flex items-center gap-4 mb-6">
                        <div class="flex items-center gap-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($product->rating_average))
                                    <i class="fas fa-star text-yellow-400 text-xl"></i>
                                @else
                                    <i class="far fa-star text-gray-300 text-xl"></i>
                                @endif
                            @endfor
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ number_format($product->rating_average, 1) }}
                            </span>
                        </div>
                        <span class="text-gray-500 dark:text-gray-400">
                            ({{ number_format($product->rating_count) }} รีวิว)
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">|</span>
                        <span class="text-gray-600 dark:text-gray-400">
                            <i class="fas fa-eye mr-1"></i>
                            {{ number_format($product->view_count) }} ครั้ง
                        </span>
                    </div>

                    {{-- Price --}}
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-2xl p-6 mb-6 border-2 border-purple-200 dark:border-purple-700">
                        <div class="flex items-end gap-4 mb-4">
                            <div class="text-5xl font-black bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                ฿{{ number_format($product->price, 2) }}
                            </div>
                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                            <div class="text-2xl text-gray-400 line-through mb-2">
                                ฿{{ number_format($product->compare_at_price, 2) }}
                            </div>
                            @endif
                        </div>

                        {{-- Discount Badge --}}
                        @if($product->compare_at_price && $product->compare_at_price > $product->price)
                        @php
                            $discount = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                        @endphp
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-full font-bold">
                            <i class="fas fa-tag"></i>
                            <span>ประหยัด {{ $discount }}%</span>
                        </div>
                        @endif
                    </div>

                    {{-- Commission & Cashback Info --}}
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        {{-- Commission --}}
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl p-4 border-2 border-green-200 dark:border-green-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-percentage text-white"></i>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">คอมมิชชั่น MLM</div>
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                        {{ number_format($product->commission_rate, 0) }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Cashback --}}
                        @if(isset($cashbackInfo) && $cashbackInfo['total_cashback'] > 0)
                        <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-2xl p-4 border-2 border-blue-200 dark:border-blue-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-gift text-white"></i>
                                </div>
                                <div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Cashback</div>
                                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                        ฿{{ number_format($cashbackInfo['total_cashback'], 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Stock Status --}}
                    <div class="mb-6">
                        @if($product->stock_status === 'in_stock' && $product->stock_quantity > 0)
                            <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                                <i class="fas fa-check-circle"></i>
                                <span class="font-semibold">พร้อมส่ง ({{ number_format($product->stock_quantity) }} ชิ้น)</span>
                            </div>
                        @elseif($product->stock_status === 'on_backorder')
                            <div class="flex items-center gap-2 text-yellow-600 dark:text-yellow-400">
                                <i class="fas fa-clock"></i>
                                <span class="font-semibold">สั่งซื้อล่วงหน้า</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                                <i class="fas fa-times-circle"></i>
                                <span class="font-semibold">สินค้าหมด</span>
                            </div>
                        @endif
                    </div>

                    {{-- Quantity Selector --}}
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            จำนวน
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                <button @click="decreaseQuantity()"
                                        class="px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number"
                                       x-model="quantity"
                                       min="1"
                                       max="{{ $product->stock_quantity }}"
                                       class="w-20 text-center py-3 bg-transparent border-none focus:outline-none font-semibold">
                                <button @click="increaseQuantity()"
                                        class="px-4 py-3 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <span class="text-gray-600 dark:text-gray-400">
                                สูงสุด {{ number_format($product->stock_quantity) }} ชิ้น
                            </span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="grid grid-cols-2 gap-4 mb-8">
                        <button @click="addToCart()"
                                :disabled="!canAddToCart"
                                :class="canAddToCart ? 'bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700' : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'"
                                class="px-8 py-4 text-white rounded-xl font-bold text-lg transition-all transform hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-cart-plus"></i>
                            <span>ใส่ตะกร้า</span>
                        </button>
                        <button @click="buyNow()"
                                :disabled="!canAddToCart"
                                :class="canAddToCart ? 'bg-gradient-to-r from-orange-500 to-red-500 hover:from-orange-600 hover:to-red-600' : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'"
                                class="px-8 py-4 text-white rounded-xl font-bold text-lg transition-all transform hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <i class="fas fa-bolt"></i>
                            <span>ซื้อเลย</span>
                        </button>
                    </div>

                    {{-- Product Features --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border-2 border-gray-200 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            รับประกันคุณภาพ
                        </h3>
                        <ul class="space-y-2 text-gray-600 dark:text-gray-400">
                            <li class="flex items-center gap-2">
                                <i class="fas fa-shield-check text-purple-500"></i>
                                <span>สินค้าจากทางระบบโดยตรง</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-truck text-purple-500"></i>
                                <span>จัดส่งฟรีทั่วประเทศ</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-undo text-purple-500"></i>
                                <span>รับประกันเปลี่ยนคืนภายใน 7 วัน</span>
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-headset text-purple-500"></i>
                                <span>บริการลูกค้า 24/7</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- ========================================
                 Product Details Tabs
                 ======================================== --}}
            <div x-data="{ tab: 'description' }" class="mb-12">
                {{-- Tabs Header --}}
                <div class="flex gap-2 mb-6 border-b-2 border-gray-200 dark:border-gray-700">
                    <button @click="tab = 'description'"
                            :class="tab === 'description' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-600 dark:text-gray-400'"
                            class="px-6 py-3 border-b-2 font-semibold transition-colors">
                        รายละเอียด
                    </button>
                    <button @click="tab = 'specifications'"
                            :class="tab === 'specifications' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-600 dark:text-gray-400'"
                            class="px-6 py-3 border-b-2 font-semibold transition-colors">
                        ข้อมูลจำเพาะ
                    </button>
                    <button @click="tab = 'reviews'"
                            :class="tab === 'reviews' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-600 dark:text-gray-400'"
                            class="px-6 py-3 border-b-2 font-semibold transition-colors">
                        รีวิว ({{ $product->rating_count }})
                    </button>
                </div>

                {{-- Tabs Content --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-lg">
                    {{-- Description Tab --}}
                    <div x-show="tab === 'description'" x-transition>
                        <div class="prose dark:prose-invert max-w-none">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>

                    {{-- Specifications Tab --}}
                    <div x-show="tab === 'specifications'" x-transition>
                        <table class="w-full">
                            <tbody>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-3 font-semibold text-gray-700 dark:text-gray-300">SKU</td>
                                    <td class="py-3 text-gray-600 dark:text-gray-400">{{ $product->sku }}</td>
                                </tr>
                                @if($product->brand)
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-3 font-semibold text-gray-700 dark:text-gray-300">ยี่ห้อ</td>
                                    <td class="py-3 text-gray-600 dark:text-gray-400">{{ $product->brand }}</td>
                                </tr>
                                @endif
                                @if($product->weight)
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-3 font-semibold text-gray-700 dark:text-gray-300">น้ำหนัก</td>
                                    <td class="py-3 text-gray-600 dark:text-gray-400">{{ number_format($product->weight / 1000, 2) }} กก.</td>
                                </tr>
                                @endif
                                @if($product->dimensions)
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="py-3 font-semibold text-gray-700 dark:text-gray-300">ขนาด</td>
                                    <td class="py-3 text-gray-600 dark:text-gray-400">{{ $product->dimensions }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    {{-- Reviews Tab --}}
                    <div x-show="tab === 'reviews'" x-transition>
                        @if($product->approvedReviews->count() > 0)
                            <div class="space-y-6">
                                @foreach($product->approvedReviews as $review)
                                <div class="border-b border-gray-200 dark:border-gray-700 pb-6 last:border-0">
                                    <div class="flex items-start gap-4">
                                        <img src="{{ $review->user->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($review->user->name) }}"
                                             alt="{{ $review->user->name }}"
                                             class="w-12 h-12 rounded-full">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="font-semibold text-gray-900 dark:text-white">{{ $review->user->name }}</span>
                                                <div class="flex items-center">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        @if($i <= $review->rating)
                                                            <i class="fas fa-star text-yellow-400 text-sm"></i>
                                                        @else
                                                            <i class="far fa-star text-gray-300 text-sm"></i>
                                                        @endif
                                                    @endfor
                                                </div>
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $review->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <p class="text-gray-600 dark:text-gray-400">{{ $review->comment }}</p>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <i class="fas fa-comments text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีรีวิว</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ========================================
                 Related Products
                 ======================================== --}}
            @if($relatedProducts && $relatedProducts->count() > 0)
            <div class="mb-12">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8">
                    สินค้าที่เกี่ยวข้อง
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $related)
                    <div class="group perspective-1000">
                        <div class="relative transform-gpu transition-all duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl blur-xl opacity-0 group-hover:opacity-30 transition-opacity"></div>

                            <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border border-purple-100 dark:border-purple-800">
                                <div class="aspect-square bg-gray-100 dark:bg-gray-700">
                                    <img src="{{ $related->main_image_url ?? 'https://via.placeholder.com/300' }}"
                                         alt="{{ $related->name }}"
                                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                         loading="lazy">
                                </div>

                                <div class="p-4">
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                                        {{ $related->name }}
                                    </h3>
                                    <div class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                                        ฿{{ number_format($related->price, 2) }}
                                    </div>
                                    <a href="{{ route('official-shop.show', $related->slug) }}"
                                       class="mt-3 block w-full px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-lg text-center font-semibold transition-all">
                                        ดูรายละเอียด
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
function officialProductManager() {
    return {
        quantity: 1,
        maxQuantity: {{ $product->stock_quantity }},

        get canAddToCart() {
            return this.quantity > 0 && this.quantity <= this.maxQuantity && '{{ $product->stock_status }}' === 'in_stock';
        },

        init() {
            console.log('Official Product Manager initialized');
        },

        increaseQuantity() {
            if (this.quantity < this.maxQuantity) {
                this.quantity++;
            }
        },

        decreaseQuantity() {
            if (this.quantity > 1) {
                this.quantity--;
            }
        },

        async addToCart() {
            if (!this.canAddToCart) return;

            try {
                const response = await fetch('/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        product_id: {{ $product->id }},
                        quantity: this.quantity
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.$dispatch('notify', {
                        message: 'เพิ่มสินค้าลงตะกร้าสำเร็จ',
                        type: 'success'
                    });
                    this.$dispatch('cart-updated', { count: data.cart_count });
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error:', error);
                this.$dispatch('notify', {
                    message: error.message || 'ไม่สามารถเพิ่มสินค้าลงตะกร้าได้',
                    type: 'error'
                });
            }
        },

        async buyNow() {
            await this.addToCart();
            // Redirect to cart
            setTimeout(() => {
                window.location.href = '/cart';
            }, 500);
        }
    };
}

window.officialProductManager = officialProductManager;
</script>
@endpush

@push('styles')
<style>
.perspective-1000 {
    perspective: 1000px;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
@endpush
@endsection
