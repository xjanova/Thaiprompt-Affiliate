@extends('layouts.app')

@section('title', 'Marketplace - ศูนย์รวมสินค้าและบริการ')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-purple-50 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900">

    {{-- Hero Section --}}
    <section class="relative py-20 lg:py-28 overflow-hidden">
        {{-- Background Decorations --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-cyan-500/10 rounded-full blur-3xl"></div>
        </div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center max-w-4xl mx-auto">
                {{-- Title with Gradient --}}
                <h1 class="text-5xl lg:text-7xl font-black mb-6 bg-gradient-to-r from-blue-600 via-cyan-500 to-purple-600 dark:from-blue-400 dark:via-cyan-400 dark:to-purple-400 bg-clip-text text-transparent">
                    🛍️ Marketplace
                </h1>
                <p class="text-xl lg:text-2xl text-slate-700 dark:text-slate-300 mb-8 font-medium">
                    ศูนย์รวมสินค้าและบริการครบวงจร
                </p>

                {{-- Stats with Glassmorphism --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-3xl mx-auto mb-12">
                    <div class="bg-white/80 dark:bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                        <div class="text-4xl font-black bg-gradient-to-r from-blue-600 to-cyan-500 bg-clip-text text-transparent">
                            {{ $stats['total_products'] ?? 0 }}+
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400 font-semibold mt-2">สินค้าทั้งหมด</div>
                    </div>
                    <div class="bg-white/80 dark:bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                        <div class="text-4xl font-black bg-gradient-to-r from-purple-600 to-pink-500 bg-clip-text text-transparent">
                            {{ $stats['total_chatbots'] ?? 0 }}+
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400 font-semibold mt-2">AI Chatbots</div>
                    </div>
                    <div class="bg-white/80 dark:bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/20 shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">
                        <div class="text-4xl font-black bg-gradient-to-r from-green-600 to-emerald-500 bg-clip-text text-transparent">
                            {{ $stats['total_software'] ?? 0 }}+
                        </div>
                        <div class="text-sm text-slate-600 dark:text-slate-400 font-semibold mt-2">ซอฟต์แวร์</div>
                    </div>
                </div>

                {{-- Search Bar with Glassmorphism --}}
                <div class="max-w-2xl mx-auto">
                    <form action="{{ route('marketplace.v3.search') }}" method="GET" class="relative">
                        <div class="relative group">
                            <input
                                type="text"
                                name="q"
                                placeholder="🔍 ค้นหาสินค้าและบริการ..."
                                value="{{ $search ?? '' }}"
                                class="w-full px-8 py-5 bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl border-2 border-white/50 dark:border-slate-600/50 rounded-2xl text-lg font-semibold text-slate-900 dark:text-white placeholder-slate-500 dark:placeholder-slate-400
                                       focus:outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                                       transition-all duration-300 shadow-xl hover:shadow-2xl"
                            >
                            <button
                                type="submit"
                                class="absolute right-3 top-1/2 -translate-y-1/2 px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
                            >
                                ค้นหา
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    {{-- Main Content --}}
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8">

                {{-- Sidebar Filters --}}
                <aside class="lg:w-80 flex-shrink-0" x-data="{ showFilters: true }">
                    <div class="sticky top-4">
                        {{-- Mobile Toggle --}}
                        <button
                            @click="showFilters = !showFilters"
                            class="lg:hidden w-full mb-4 px-6 py-4 bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl border-2 border-white/50 dark:border-slate-700/50 font-bold text-slate-900 dark:text-white shadow-lg hover:shadow-xl transition-all"
                        >
                            <span x-show="!showFilters">📋 แสดงตัวกรอง</span>
                            <span x-show="showFilters">✖️ ซ่อนตัวกรอง</span>
                        </button>

                        <div x-show="showFilters" x-collapse class="space-y-6">
                            {{-- Categories --}}
                            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                    📂 หมวดหมู่
                                </h3>
                                <div class="space-y-2">
                                    @foreach($categories as $cat)
                                        <a href="{{ route('marketplace.v3.category', $cat['id']) }}"
                                           class="block px-4 py-3 rounded-xl font-semibold transition-all duration-300
                                                  {{ ($category ?? 'all') === $cat['id']
                                                      ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-lg scale-105'
                                                      : 'bg-slate-100 dark:bg-slate-700/50 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600/50 hover:scale-102' }}">
                                            <div class="flex items-center justify-between">
                                                <span>
                                                    <i class="fas {{ $cat['icon'] }} mr-2"></i>
                                                    {{ $cat['name'] }}
                                                </span>
                                                @if($cat['count'])
                                                    <span class="px-2 py-1 bg-white/20 rounded-full text-xs font-bold">
                                                        {{ $cat['count'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Sort Options --}}
                            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-2xl p-6 border-2 border-white/50 dark:border-slate-700/50 shadow-xl">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                    🔄 เรียงตาม
                                </h3>
                                <div class="space-y-2">
                                    @php
                                        $sortOptions = [
                                            'popular' => ['icon' => 'fa-fire', 'label' => 'ยอดนิยม'],
                                            'newest' => ['icon' => 'fa-clock', 'label' => 'ใหม่ล่าสุด'],
                                            'price_low' => ['icon' => 'fa-arrow-down', 'label' => 'ราคาต่ำ → สูง'],
                                            'price_high' => ['icon' => 'fa-arrow-up', 'label' => 'ราคาสูง → ต่ำ'],
                                        ];
                                    @endphp
                                    @foreach($sortOptions as $key => $option)
                                        <a href="{{ route('marketplace.v3.index', ['sort' => $key, 'category' => $category ?? 'all']) }}"
                                           class="block px-4 py-3 rounded-xl font-semibold transition-all duration-300
                                                  {{ ($sort ?? 'popular') === $key
                                                      ? 'bg-gradient-to-r from-purple-600 to-pink-500 text-white shadow-lg scale-105'
                                                      : 'bg-slate-100 dark:bg-slate-700/50 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600/50 hover:scale-102' }}">
                                            <i class="fas {{ $option['icon'] }} mr-2"></i>
                                            {{ $option['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

                {{-- Products Grid --}}
                <main class="flex-1">
                    <div class="mb-6">
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2">
                            @if(isset($search) && $search)
                                🔍 ผลการค้นหา: "{{ $search }}"
                            @elseif(isset($category) && $category !== 'all')
                                @php
                                    $currentCat = collect($categories)->firstWhere('id', $category);
                                @endphp
                                {{ $currentCat['name'] ?? 'สินค้าทั้งหมด' }}
                            @else
                                สินค้าทั้งหมด
                            @endif
                        </h2>
                        <p class="text-slate-600 dark:text-slate-400">
                            พบ {{ $products->total() }} รายการ
                        </p>
                    </div>

                    @if($products->count() > 0)
                        {{-- Product Cards Grid --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-8">
                            @foreach($products as $product)
                                @include('marketplace.unified._product-card', ['product' => $product])
                            @endforeach
                        </div>

                        {{-- Pagination --}}
                        <div class="mt-8">
                            {{ $products->links() }}
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="text-center py-20">
                            <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-3xl p-12 max-w-md mx-auto border-2 border-white/50 dark:border-slate-700/50 shadow-2xl">
                                <div class="text-8xl mb-6">🔍</div>
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-4">
                                    ไม่พบสินค้า
                                </h3>
                                <p class="text-slate-600 dark:text-slate-400 mb-6">
                                    ลองค้นหาด้วยคำค้นอื่น หรือเลือกหมวดหมู่อื่น
                                </p>
                                <a href="{{ route('marketplace.v3.index') }}"
                                   class="inline-block px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-500 hover:from-blue-700 hover:to-cyan-600 text-white rounded-xl font-bold shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                                    🏠 กลับหน้าหลัก
                                </a>
                            </div>
                        </div>
                    @endif
                </main>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
/**
 * Marketplace Index Page Scripts
 * ใช้ Alpine.js สำหรับ filter interactions
 */
</script>
@endpush
