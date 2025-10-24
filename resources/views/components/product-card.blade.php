<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
    <a href="{{ route('products.show', $product->slug) }}">
        <div class="relative">
            <img src="{{ $product->featured_image ?? asset('images/placeholder.jpg') }}"
                 alt="{{ $product->name }}"
                 class="w-full h-48 object-cover">

            @if($product->hasDiscount())
                <span class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 text-xs font-bold rounded">
                    -{{ $product->getDiscountPercentage() }}%
                </span>
            @endif

            @if(!$product->isInStock())
                <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                    <span class="bg-white text-gray-900 px-4 py-2 rounded-lg font-semibold">สินค้าหมด</span>
                </div>
            @endif
        </div>
    </a>

    <div class="p-4">
        <a href="{{ route('products.show', $product->slug) }}">
            <h3 class="text-lg font-semibold text-gray-900 mb-2 hover:text-indigo-600 line-clamp-2">
                {{ $product->name }}
            </h3>
        </a>

        <div class="flex items-center mb-2">
            <div class="flex items-center">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 {{ $i <= ($product->average_rating ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @endfor
            </div>
            <span class="ml-2 text-sm text-gray-600">({{ $product->reviews_count ?? 0 }})</span>
        </div>

        <div class="flex items-center justify-between">
            <div>
                @if($product->hasDiscount())
                    <div class="flex items-center space-x-2">
                        <span class="text-lg font-bold text-red-600">฿{{ number_format($product->getFinalPrice(), 2) }}</span>
                        <span class="text-sm text-gray-400 line-through">฿{{ number_format($product->price, 2) }}</span>
                    </div>
                @else
                    <span class="text-lg font-bold text-gray-900">฿{{ number_format($product->price, 2) }}</span>
                @endif
            </div>

            @if($product->isInStock())
                <form action="{{ route('cart.add') }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="bg-indigo-600 text-white p-2 rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
