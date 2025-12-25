@extends('layouts.app')

@section('title', $product->name . ' - Marketplace')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-purple-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">

    {{-- Breadcrumb --}}
    <div class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-xl border-b border-white/20 dark:border-slate-700/20">
        <div class="container mx-auto px-4 py-4">
            <nav class="flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-400">
                <a href="{{ route('home') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">🏠 หน้าหลัก</a>
                <span>/</span>
                <a href="{{ route('marketplace.v3.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition">Marketplace</a>
                <span>/</span>
                <span class="text-slate-900 dark:text-white">{{ $product->name }}</span>
            </nav>
        </div>
    </div>

    {{-- Product Details --}}
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-16">

                {{-- Left: Product Image/Gallery --}}
                <div class="relative">
                    <div class="sticky top-4">
                        <div class="relative bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-3xl overflow-hidden border-2 border-white/50 dark:border-slate-700/50 shadow-2xl">
                            @php
                                // กำหนดสีตาม type
                                $typeColors = [
                                    'chatbot' => 'from-purple-600 to-pink-500',
                                    'trading' => 'from-green-600 to-emerald-500',
                                    'software' => 'from-blue-600 to-cyan-500',
                                ];
                                $gradient = $typeColors[$type] ?? 'from-blue-600 to-cyan-500';

                                $imageUrl = match($type) {
                                    'chatbot' => $product->avatar_url ?? '/images/default-bot.png',
                                    'trading' => $product->image_url ?? '/images/default-trading.png',
                                    'software' => $product->main_image_url ?? '/images/default-software.png',
                                    default => '/images/default-software.png',
                                };
                            @endphp

                            <div class="relative w-full h-96 bg-gradient-to-br {{ $gradient }}">
                                @if($imageUrl && !str_contains($imageUrl, 'default'))
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt="{{ $product->name }}"
                                        class="w-full h-full object-cover"
                                    >
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <div class="text-9xl opacity-80">
                                            @if($type === 'chatbot') 🤖
                                            @elseif($type === 'trading') 📈
                                            @else 💻
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Product Info --}}
                <div>
                    {{-- Type Badge --}}
                    <div class="inline-block px-4 py-2 bg-gradient-to-r {{ $gradient }} text-white rounded-full font-bold text-sm mb-4 shadow-lg">
                        @if($type === 'chatbot') 🤖 AI Chatbot
                        @elseif($type === 'trading') 📈 Trading Bot
                        @else 💻 ซอฟต์แวร์
                        @endif
                    </div>

                    {{-- Product Name --}}
                    <h1 class="text-4xl lg:text-5xl font-black text-slate-900 dark:text-white mb-6">
                        {{ $product->name }}
                    </h1>

                    {{-- Description --}}
                    <div class="prose prose-lg dark:prose-invert max-w-none mb-8">
                        <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                            @if($type === 'chatbot')
                                {{ $product->description }}
                            @elseif($type === 'trading')
                                {{ $product->description }}
                            @else
                                {{ $product->description ?? $product->short_description }}
                            @endif
                        </p>
                    </div>

                    {{-- Price Section --}}
                    <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-3xl p-8 border-2 border-white/50 dark:border-slate-700/50 shadow-2xl mb-8">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <div class="text-sm text-slate-500 dark:text-slate-400 mb-2">ราคา</div>
                                <div class="text-4xl font-black bg-gradient-to-r {{ $gradient }} bg-clip-text text-transparent">
                                    @if($type === 'chatbot')
                                        ฿{{ number_format($product->rental_price_per_month, 0) }}/เดือน
                                    @elseif($type === 'trading')
                                        ฿{{ number_format($product->price, 0) }}
                                    @else
                                        {{ $product->price_label ?? '฿' . number_format($product->base_price, 0) }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- CTA Buttons --}}
                        <div class="space-y-4">
                            @if($type === 'chatbot')
                                <a href="{{ route('ai-bots.rent', $product->id) }}"
                                   class="block w-full px-8 py-5 bg-gradient-to-r {{ $gradient }} hover:shadow-2xl text-white rounded-2xl font-black text-lg text-center shadow-xl hover:scale-105 transition-all duration-300">
                                    🚀 เช่า Chatbot นี้
                                </a>
                            @elseif($type === 'trading')
                                <a href="{{ route('trading-bots.purchase', $product->id) }}"
                                   class="block w-full px-8 py-5 bg-gradient-to-r {{ $gradient }} hover:shadow-2xl text-white rounded-2xl font-black text-lg text-center shadow-xl hover:scale-105 transition-all duration-300">
                                    💰 ซื้อ Trading Bot
                                </a>
                            @else
                                <a href="{{ route('software.products.show', $product->slug) }}"
                                   class="block w-full px-8 py-5 bg-gradient-to-r {{ $gradient }} hover:shadow-2xl text-white rounded-2xl font-black text-lg text-center shadow-xl hover:scale-105 transition-all duration-300">
                                    🛒 ดูรายละเอียดเพิ่มเติม
                                </a>
                            @endif

                            <a href="{{ route('marketplace.v3.index') }}"
                               class="block w-full px-8 py-5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-900 dark:text-white rounded-2xl font-bold text-lg text-center shadow-lg hover:scale-105 transition-all duration-300">
                                ← กลับสู่ Marketplace
                            </a>
                        </div>
                    </div>

                    {{-- Additional Info --}}
                    @if($type === 'software' && isset($product->features))
                        <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-3xl p-8 border-2 border-white/50 dark:border-slate-700/50 shadow-2xl">
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-4">
                                ✨ ฟีเจอร์เด่น
                            </h3>
                            <ul class="space-y-3">
                                @foreach($product->features ?? [] as $feature)
                                    <li class="flex items-start gap-3 text-slate-700 dark:text-slate-300">
                                        <span class="text-green-500 text-xl">✓</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Related Products --}}
            @if(isset($relatedProducts) && $relatedProducts->count() > 0)
                <section class="mt-16">
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-8 flex items-center gap-3">
                        <span>🔗</span>
                        <span>สินค้าที่เกี่ยวข้อง</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach($relatedProducts as $related)
                            @include('marketplace.unified._product-card', ['product' => $related])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>
</div>
@endsection
