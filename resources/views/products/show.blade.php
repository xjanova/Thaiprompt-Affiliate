@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="text-sm mb-4">
        <ol class="list-none p-0 inline-flex">
            <li class="flex items-center">
                <a href="{{ url('/') }}" class="text-indigo-600 hover:text-indigo-500">หน้าแรก</a>
                <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
            </li>
            <li class="flex items-center">
                <a href="{{ route('products.index') }}" class="text-indigo-600 hover:text-indigo-500">สินค้า</a>
                <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
            </li>
            <li class="text-gray-500">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
        <!-- Product Images -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-4">
                <img src="{{ $product->featured_image ?? asset('images/placeholder.jpg') }}"
                     alt="{{ $product->name }}"
                     class="w-full h-96 object-cover rounded-lg">
            </div>

            @if($product->gallery_images && count($product->gallery_images) > 0)
                <div class="grid grid-cols-4 gap-2 mt-4">
                    @foreach($product->gallery_images as $image)
                        <img src="{{ $image }}" alt="{{ $product->name }}"
                             class="w-full h-24 object-cover rounded cursor-pointer hover:opacity-75">
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Product Details -->
        <div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $product->name }}</h1>

                <!-- Vendor -->
                <div class="flex items-center mb-4">
                    <span class="text-sm text-gray-600">โดย</span>
                    <a href="{{ route('vendors.show', $product->vendor->slug) }}" class="ml-2 text-indigo-600 hover:text-indigo-500 font-medium">
                        {{ $product->vendor->name }}
                    </a>
                </div>

                <!-- Rating -->
                <div class="flex items-center mb-6">
                    <div class="flex items-center">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-5 h-5 {{ $i <= ($product->average_rating ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <span class="ml-2 text-sm text-gray-600">
                        ({{ $product->reviews_count ?? 0 }} รีวิว)
                    </span>
                </div>

                <!-- Price -->
                <div class="mb-6">
                    @if($product->hasDiscount())
                        <div class="flex items-center">
                            <span class="text-4xl font-bold text-red-600">฿{{ number_format($product->getFinalPrice(), 2) }}</span>
                            <span class="ml-3 text-xl text-gray-400 line-through">฿{{ number_format($product->price, 2) }}</span>
                            <span class="ml-2 px-2 py-1 bg-red-100 text-red-600 text-sm font-semibold rounded">
                                -{{ $product->getDiscountPercentage() }}%
                            </span>
                        </div>
                    @else
                        <span class="text-4xl font-bold text-gray-900">฿{{ number_format($product->price, 2) }}</span>
                    @endif
                </div>

                <!-- Stock Status -->
                <div class="mb-6">
                    @if($product->isInStock())
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            มีสินค้า ({{ $product->stock_quantity }} ชิ้น)
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">
                            สินค้าหมด
                        </span>
                    @endif
                </div>

                <!-- Add to Cart Form -->
                @if($product->isInStock())
                    <form action="{{ route('cart.add') }}" method="POST" class="mb-6">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <div class="flex items-center space-x-4 mb-4">
                            <label class="text-sm font-medium text-gray-700">จำนวน:</label>
                            <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock_quantity }}"
                                class="w-20 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="flex space-x-4">
                            <button type="submit" class="flex-1 bg-indigo-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition">
                                เพิ่มลงตะกร้า
                            </button>
                            <button type="button" class="px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </button>
                        </div>
                    </form>
                @endif

                <!-- Description -->
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold mb-3">รายละเอียดสินค้า</h3>
                    <div class="text-gray-600 prose max-w-none">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold mb-6">รีวิวจากลูกค้า</h2>

        @if(isset($product->reviews) && $product->reviews->count() > 0)
            <div class="space-y-6">
                @foreach($product->reviews as $review)
                    <div class="border-b pb-6 last:border-b-0">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <img src="{{ $review->user->avatar ?? asset('images/default-avatar.png') }}" alt="{{ $review->user->name }}" class="w-10 h-10 rounded-full">
                                <div class="ml-3">
                                    <p class="font-medium">{{ $review->user->name }}</p>
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            <span class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-gray-600">{{ $review->comment }}</p>

                        @if($review->response)
                            <div class="mt-4 ml-10 p-4 bg-gray-50 rounded">
                                <p class="text-sm font-medium text-gray-900 mb-1">ตอบกลับจากผู้ขาย:</p>
                                <p class="text-sm text-gray-600">{{ $review->response->response }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-8">ยังไม่มีรีวิวสำหรับสินค้านี้</p>
        @endif
    </div>

    <!-- Related Products -->
    @if(isset($relatedProducts) && $relatedProducts->count() > 0)
        <div class="mb-8">
            <h2 class="text-2xl font-bold mb-6">สินค้าที่เกี่ยวข้อง</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $relatedProduct)
                    @include('components.product-card', ['product' => $relatedProduct])
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
