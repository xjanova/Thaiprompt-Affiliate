@extends('layouts.app')

@section('title', $product->name . ' - Software Products')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-purple-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-6">
            <ol class="flex items-center gap-2 text-sm">
                <li><a href="{{ route('software.products.index') }}" class="text-purple-600 hover:text-purple-700 font-semibold">Software Products</a></li>
                <li class="text-gray-400">/</li>
                @if($product->category)
                    <li><a href="{{ route('software.products.index', ['category' => $product->category->id]) }}" class="text-purple-600 hover:text-purple-700 font-semibold">{{ $product->category->name }}</a></li>
                    <li class="text-gray-400">/</li>
                @endif
                <li class="text-gray-600">{{ $product->name }}</li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Product Details -->
            <div class="lg:col-span-2">
                <!-- Main Info Card -->
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden mb-6">
                    <!-- Product Image -->
                    <div class="relative bg-gradient-to-br from-blue-50 to-purple-50 p-8">
                        <div class="absolute top-4 right-4 flex flex-col gap-2">
                            @if($product->is_featured)
                                <span class="px-4 py-2 bg-yellow-500 text-white text-sm font-bold rounded-full shadow-lg">
                                    ⭐ Featured
                                </span>
                            @endif
                            @if($product->is_customizable)
                                <span class="px-4 py-2 bg-blue-500 text-white text-sm font-bold rounded-full shadow-lg">
                                    🔧 Customizable
                                </span>
                            @endif
                        </div>

                        @if($product->main_image_url)
                            <img src="{{ $product->main_image_url }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-96 object-cover rounded-2xl shadow-2xl">
                        @else
                            <div class="w-full h-96 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-9xl shadow-2xl">
                                💻
                            </div>
                        @endif
                    </div>

                    <div class="p-8">
                        <h1 class="text-4xl font-black text-gray-800 mb-4">{{ $product->name }}</h1>

                        @if($product->category)
                            <div class="mb-4">
                                <span class="inline-block px-4 py-2 bg-purple-100 text-purple-700 text-sm font-semibold rounded-full">
                                    📁 {{ $product->category->name }}
                                </span>
                            </div>
                        @endif

                        <p class="text-lg text-gray-600 mb-6">{{ $product->short_description }}</p>

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-2xl p-6">
                            <div class="text-center">
                                <div class="text-3xl font-black text-purple-700">{{ $product->view_count ?? 0 }}</div>
                                <div class="text-sm text-gray-600">Views</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-black text-pink-700">{{ $product->quotation_count ?? 0 }}</div>
                                <div class="text-sm text-gray-600">Quotations</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-black text-blue-700">{{ $product->order_count ?? 0 }}</div>
                                <div class="text-sm text-gray-600">Orders</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                @if($product->description)
                    <div class="bg-white rounded-3xl shadow-2xl p-8 mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="text-3xl">📝</span> Description
                        </h2>
                        <div class="prose max-w-none text-gray-600">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>
                @endif

                <!-- Features -->
                @if($product->features && count($product->features) > 0)
                    <div class="bg-white rounded-3xl shadow-2xl p-8 mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="text-3xl">✨</span> Features
                        </h2>
                        <ul class="space-y-3">
                            @foreach($product->features as $feature)
                                <li class="flex items-start gap-3">
                                    <span class="text-green-500 text-xl mt-1">✓</span>
                                    <span class="text-gray-700">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Technical Specs -->
                @if($product->technical_specs && count($product->technical_specs) > 0)
                    <div class="bg-white rounded-3xl shadow-2xl p-8 mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="text-3xl">⚙️</span> Technical Specifications
                        </h2>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($product->technical_specs as $key => $value)
                                <div class="bg-gray-50 rounded-xl p-4">
                                    <dt class="text-sm font-semibold text-gray-500 mb-1">{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                                    <dd class="text-lg font-bold text-gray-800">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                <!-- Gallery Images -->
                @if($product->gallery_images && count($product->gallery_images) > 0)
                    <div class="bg-white rounded-3xl shadow-2xl p-8 mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="text-3xl">🖼️</span> Gallery
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach($product->gallery_images as $image)
                                <img src="{{ $image }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-48 object-cover rounded-xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300">
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Product Options -->
                @if($product->activeOptions && $product->activeOptions->count() > 0)
                    <div class="bg-white rounded-3xl shadow-2xl p-8 mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="text-3xl">🎛️</span> Available Options
                        </h2>
                        <div class="space-y-6">
                            @foreach($product->activeOptions as $option)
                                <div class="border-l-4 border-purple-500 pl-4">
                                    <h3 class="text-lg font-bold text-gray-800 mb-2">{{ $option->name }}</h3>
                                    @if($option->description)
                                        <p class="text-gray-600 mb-3">{{ $option->description }}</p>
                                    @endif
                                    @if($option->activeValues && $option->activeValues->count() > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($option->activeValues as $value)
                                                <span class="px-4 py-2 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold">
                                                    {{ $value->value }}
                                                    @if($value->price_modifier > 0)
                                                        <span class="text-xs">(+{{ $product->currency }} {{ number_format($value->price_modifier, 2) }})</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Related Products -->
                @if($relatedProducts && $relatedProducts->count() > 0)
                    <div class="bg-white rounded-3xl shadow-2xl p-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                            <span class="text-3xl">🔗</span> Related Products
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($relatedProducts as $relatedProduct)
                                <a href="{{ route('software.products.show', $relatedProduct->slug) }}"
                                   class="group bg-gray-50 rounded-2xl p-4 hover:bg-purple-50 transition-all duration-300 border-2 border-transparent hover:border-purple-500">
                                    <div class="flex items-center gap-4">
                                        @if($relatedProduct->main_image_url)
                                            <img src="{{ $relatedProduct->main_image_url }}"
                                                 alt="{{ $relatedProduct->name }}"
                                                 class="w-16 h-16 object-cover rounded-xl">
                                        @else
                                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center text-2xl">
                                                💻
                                            </div>
                                        @endif
                                        <div class="flex-1">
                                            <h3 class="font-bold text-gray-800 group-hover:text-purple-600 transition-colors">
                                                {{ $relatedProduct->name }}
                                            </h3>
                                            <p class="text-sm text-gray-600">{{ $relatedProduct->currency }} {{ number_format($relatedProduct->base_price, 2) }}</p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Right Column: Pricing & Actions -->
            <div class="lg:col-span-1">
                <!-- Pricing Card -->
                <div class="bg-white rounded-3xl shadow-2xl p-8 mb-6 sticky top-4">
                    <div class="text-center mb-6">
                        <div class="text-sm text-gray-500 mb-2">{{ $product->price_label ?? 'Starting from' }}</div>
                        <div class="text-5xl font-black text-purple-700 mb-2">
                            {{ $product->currency }} {{ number_format($product->base_price, 2) }}
                        </div>
                    </div>

                    @if($product->estimated_delivery_days)
                        <div class="bg-blue-50 rounded-xl p-4 mb-6">
                            <div class="flex items-center gap-2 text-blue-700">
                                <span class="text-2xl">📦</span>
                                <div>
                                    <div class="text-sm font-semibold">Estimated Delivery</div>
                                    <div class="text-lg font-bold">{{ $product->estimated_delivery_days }} days</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($product->requires_consultation)
                        <div class="bg-yellow-50 rounded-xl p-4 mb-6">
                            <div class="flex items-center gap-2 text-yellow-700">
                                <span class="text-2xl">💬</span>
                                <div>
                                    <div class="text-sm font-semibold">Consultation Required</div>
                                    <div class="text-xs">Contact us for pricing details</div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    @auth
                        <button class="w-full px-6 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-200 mb-3">
                            Request Quotation
                        </button>

                        @if($product->demo_url)
                            <a href="{{ $product->demo_url }}"
                               target="_blank"
                               class="block w-full px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-center rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 mb-3">
                                🎮 Try Demo
                            </a>
                        @endif

                        @if($product->documentation_url)
                            <a href="{{ $product->documentation_url }}"
                               target="_blank"
                               class="block w-full px-6 py-4 bg-gray-600 hover:bg-gray-700 text-white font-bold text-center rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                                📚 Documentation
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}"
                           class="block w-full px-6 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold text-center rounded-xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-200">
                            Login to Request Quotation
                        </a>
                    @endauth
                </div>

                <!-- Additional Info Card -->
                <div class="bg-white rounded-3xl shadow-2xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Additional Information</h3>
                    <div class="space-y-3">
                        @if($product->is_customizable)
                            <div class="flex items-start gap-3 text-sm">
                                <span class="text-xl">🔧</span>
                                <div>
                                    <div class="font-semibold text-gray-800">Customizable</div>
                                    <div class="text-gray-600">Can be tailored to your needs</div>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-start gap-3 text-sm">
                            <span class="text-xl">🛡️</span>
                            <div>
                                <div class="font-semibold text-gray-800">Quality Guarantee</div>
                                <div class="text-gray-600">Professional support included</div>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 text-sm">
                            <span class="text-xl">💼</span>
                            <div>
                                <div class="font-semibold text-gray-800">Business Ready</div>
                                <div class="text-gray-600">Enterprise-grade solution</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
