@extends('layouts.user')

@section('title', $product->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-green-50 via-teal-50 to-blue-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Breadcrumb -->
        <nav class="mb-6 flex items-center gap-2 text-sm text-gray-600">
            <a href="{{ route('user.shop.index') }}" class="hover:text-green-600">ร้านค้า</a>
            <span>›</span>
            @if($product->category)
            <a href="{{ route('user.shop.index', ['category' => $product->category->id]) }}" class="hover:text-green-600">
                {{ $product->category->name }}
            </a>
            <span>›</span>
            @endif
            <span class="text-gray-800 font-semibold">{{ $product->name }}</span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Product Images -->
            <div class="space-y-4">
                <!-- Main Image -->
                <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <div class="relative" style="padding-top: 100%;">
                        @if($product->main_image_url)
                            <img id="mainImage"
                                 src="{{ asset($product->main_image_url) }}"
                                 alt="{{ $product->name }}"
                                 class="absolute inset-0 w-full h-full object-contain p-4">
                        @else
                            <div class="absolute inset-0 w-full h-full flex items-center justify-center text-9xl">
                                📦
                            </div>
                        @endif

                        <!-- Badges -->
                        <div class="absolute top-4 right-4 flex flex-col gap-2">
                            @if($product->is_featured)
                            <span class="bg-yellow-500 text-white text-sm font-bold px-3 py-1 rounded-full shadow-lg">
                                ⭐ แนะนำ
                            </span>
                            @endif

                            @if($product->discount_percentage)
                            <span class="bg-red-500 text-white text-sm font-bold px-3 py-1 rounded-full shadow-lg">
                                ลด {{ $product->discount_percentage }}%
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Thumbnail Images -->
                @if($product->images->count() > 0)
                <div class="grid grid-cols-5 gap-2">
                    @foreach($product->images as $image)
                    <div class="bg-white rounded-lg shadow overflow-hidden cursor-pointer hover:ring-4 hover:ring-green-300 transition-all"
                         onclick="document.getElementById('mainImage').src='{{ asset($image->image_url) }}'">
                        <div class="relative" style="padding-top: 100%;">
                            <img src="{{ asset($image->image_url) }}"
                                 alt="{{ $product->name }}"
                                 class="absolute inset-0 w-full h-full object-cover">
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Product Details -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-2xl p-8">
                    <!-- Category -->
                    @if($product->category)
                    <div class="text-sm text-gray-500 mb-2">
                        {{ $product->category->name }}
                    </div>
                    @endif

                    <!-- Product Name -->
                    <h1 class="text-3xl font-bold text-gray-800 mb-4">
                        {{ $product->name }}
                    </h1>

                    <!-- Rating & Views -->
                    <div class="flex items-center gap-4 mb-6">
                        @if($product->rating_count > 0)
                        <div class="flex items-center gap-2">
                            <div class="flex text-yellow-400 text-lg">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= floor($product->rating_average))
                                        ⭐
                                    @else
                                        ☆
                                    @endif
                                @endfor
                            </div>
                            <span class="text-sm text-gray-600">
                                {{ number_format($product->rating_average, 1) }} ({{ $product->rating_count }} รีวิว)
                            </span>
                        </div>
                        @endif

                        <div class="text-sm text-gray-600">
                            👁️ {{ number_format($product->view_count) }} ครั้ง
                        </div>

                        @if($product->sales_count > 0)
                        <div class="text-sm text-gray-600">
                            🛍️ ขายแล้ว {{ number_format($product->sales_count) }} ชิ้น
                        </div>
                        @endif
                    </div>

                    <!-- Price -->
                    <div class="bg-gradient-to-r from-green-50 to-teal-50 rounded-xl p-6 mb-6">
                        <div class="flex items-end gap-3 mb-2">
                            <span class="text-4xl font-bold text-green-600">
                                ฿{{ number_format($product->price, 2) }}
                            </span>
                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                            <span class="text-xl text-gray-400 line-through mb-1">
                                ฿{{ number_format($product->compare_at_price, 2) }}
                            </span>
                            @endif
                        </div>

                        @if($product->discount_percentage)
                        <div class="text-sm text-red-600 font-semibold">
                            ประหยัด ฿{{ number_format($product->compare_at_price - $product->price, 2) }}
                        </div>
                        @endif
                    </div>

                    <!-- Stock Status -->
                    <div class="mb-6">
                        @if($product->isInStock())
                        <div class="flex items-center gap-2 text-green-600 font-semibold">
                            <span class="text-2xl">✓</span>
                            <span>มีสินค้า</span>
                            @if($product->track_inventory)
                            <span class="text-gray-600 text-sm">(เหลือ {{ $product->stock_quantity }} ชิ้น)</span>
                            @endif
                        </div>
                        @else
                        <div class="flex items-center gap-2 text-red-600 font-semibold">
                            <span class="text-2xl">✗</span>
                            <span>สินค้าหมด</span>
                        </div>
                        @endif
                    </div>

                    <!-- Short Description -->
                    @if($product->short_description)
                    <div class="mb-6">
                        <p class="text-gray-700 leading-relaxed">
                            {{ $product->short_description }}
                        </p>
                    </div>
                    @endif

                    <!-- SKU & Brand -->
                    <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
                        @if($product->sku)
                        <div>
                            <span class="text-gray-600">รหัสสินค้า:</span>
                            <span class="font-semibold">{{ $product->sku }}</span>
                        </div>
                        @endif
                        @if($product->brand)
                        <div>
                            <span class="text-gray-600">แบรนด์:</span>
                            <span class="font-semibold">{{ $product->brand }}</span>
                        </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-4">
                        @if($product->isInStock())
                        <button class="flex-1 bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-bold py-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg">
                            🛒 สั่งซื้อเลย
                        </button>
                        <button class="px-6 bg-white border-2 border-green-600 text-green-600 hover:bg-green-50 font-bold py-4 rounded-xl transition-all duration-300">
                            💚 เพิ่มในรายการโปรด
                        </button>
                        @else
                        <button disabled class="flex-1 bg-gray-400 text-white font-bold py-4 rounded-xl cursor-not-allowed">
                            สินค้าหมด
                        </button>
                        @endif
                    </div>

                    <!-- Commission Info -->
                    @if($product->commission_rate > 0)
                    <div class="mt-6 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl p-4 border-2 border-yellow-300">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">💰</span>
                            <span class="font-bold text-gray-800">รับค่าคอมมิชชั่น</span>
                        </div>
                        <div class="text-sm text-gray-700">
                            <span class="font-semibold text-orange-600">{{ $product->commission_rate }}%</span>
                            จากยอดขายสินค้านี้
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Product Description & Details -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="lg:col-span-2 space-y-8">
                <!-- Description -->
                <div class="bg-white rounded-2xl shadow-2xl p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">📝 รายละเอียดสินค้า</h2>
                    <div class="prose max-w-none text-gray-700">
                        {!! nl2br(e($product->description ?? 'ไม่มีรายละเอียด')) !!}
                    </div>
                </div>

                <!-- Reviews -->
                @if($product->approvedReviews->count() > 0)
                <div class="bg-white rounded-2xl shadow-2xl p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">⭐ รีวิวจากลูกค้า</h2>
                    <div class="space-y-6">
                        @foreach($product->approvedReviews as $review)
                        <div class="border-b border-gray-200 pb-6 last:border-0">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-teal-500 rounded-full flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($review->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-800">{{ $review->user->name ?? 'ผู้ใช้' }}</div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex text-yellow-400 text-sm">
                                            @for($i = 1; $i <= 5; $i++)
                                                @if($i <= $review->rating)
                                                    ⭐
                                                @else
                                                    ☆
                                                @endif
                                            @endfor
                                        </div>
                                        <span class="text-xs text-gray-500">
                                            {{ $review->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @if($review->comment)
                            <p class="text-gray-700 leading-relaxed">{{ $review->comment }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Product Specifications -->
                @if($product->attributes && count($product->attributes) > 0)
                <div class="bg-white rounded-2xl shadow-2xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">📋 คุณสมบัติ</h3>
                    <div class="space-y-3">
                        @foreach($product->attributes as $key => $value)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $key }}:</span>
                            <span class="font-semibold text-gray-800">{{ $value }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Related Products -->
                @if($relatedProducts->count() > 0)
                <div class="bg-white rounded-2xl shadow-2xl p-6">
                    <h3 class="text-xl font-bold text-gray-800 mb-4">🔗 สินค้าที่เกี่ยวข้อง</h3>
                    <div class="space-y-4">
                        @foreach($relatedProducts as $related)
                        <a href="{{ route('user.shop.show', $related->slug) }}"
                           class="block group">
                            <div class="flex gap-3 hover:bg-gray-50 rounded-lg p-2 transition-all">
                                <div class="w-20 h-20 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                                    @if($related->main_image_url)
                                    <img src="{{ asset($related->main_image_url) }}"
                                         alt="{{ $related->name }}"
                                         class="w-full h-full object-cover">
                                    @else
                                    <div class="w-full h-full flex items-center justify-center text-2xl">
                                        📦
                                    </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-sm text-gray-800 line-clamp-2 group-hover:text-green-600 transition-colors">
                                        {{ $related->name }}
                                    </h4>
                                    <div class="text-sm font-bold text-green-600 mt-1">
                                        ฿{{ number_format($related->price, 2) }}
                                    </div>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Back to Shop -->
        <div class="text-center">
            <a href="{{ route('user.shop.index') }}"
               class="inline-block bg-gradient-to-r from-green-600 to-teal-600 hover:from-green-700 hover:to-teal-700 text-white font-bold px-8 py-3 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg">
                ← กลับไปหน้าร้านค้า
            </a>
        </div>
    </div>
</div>
@endsection
