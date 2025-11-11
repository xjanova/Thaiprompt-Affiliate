@extends('layouts.admin')

@section('title', 'จัดการผลิตภัณฑ์ซอฟต์แวร์')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-cyan-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-blue-900/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="p-3 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl shadow-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 dark:from-cyan-400 dark:via-blue-400 dark:to-indigo-400">
                            ผลิตภัณฑ์ซอฟต์แวร์
                        </h1>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 ml-16">จัดการและติดตามผลิตภัณฑ์ซอฟต์แวร์ทั้งหมดของคุณ</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.software-products.create') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        เพิ่มผลิตภัณฑ์ใหม่
                    </a>
                </div>
            </div>
        </div>

        {{-- Search & Filters --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 mb-8">
            <form method="GET" action="{{ route('admin.software-products.index') }}" class="flex flex-col lg:flex-row gap-4">
                <div class="flex-1">
                    <div class="relative">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="ค้นหาผลิตภัณฑ์..."
                               class="w-full pl-12 pr-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="lg:w-64">
                    <select name="category"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-cyan-500 dark:bg-slate-700 dark:text-white"
                            onchange="this.form.submit()">
                        <option value="">ทุกหมวดหมู่</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        class="px-6 py-3 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold rounded-xl transition-colors">
                    ค้นหา
                </button>
            </form>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-cyan-500 transform hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">ทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $products->total() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-cyan-100 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-green-500 transform hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">เปิดใช้งาน</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $products->where('is_active', true)->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-purple-500 transform hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">แนะนำ</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $products->where('is_featured', true)->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6 border-l-4 border-orange-500 transform hover:scale-105 transition-transform">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">กำหนดเอง</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $products->where('is_customizable', true)->count() }}</p>
                    </div>
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-100 to-amber-100 dark:from-orange-900/30 dark:to-amber-900/30 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Products Grid --}}
        @if($products->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($products as $product)
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group">
                {{-- Product Image --}}
                <div class="relative h-48 bg-gradient-to-br from-cyan-500 via-blue-500 to-indigo-500 overflow-hidden">
                    @if($product->main_image_url)
                        <img src="{{ $product->main_image_url }}"
                             alt="{{ $product->name }}"
                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-20 h-20 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @endif

                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

                    {{-- Status Badges --}}
                    <div class="absolute top-4 right-4 flex flex-col gap-2">
                        @if($product->is_featured)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-500 text-white text-xs font-semibold rounded-full shadow-lg">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            แนะนำ
                        </span>
                        @endif
                        @if($product->is_active)
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-500 text-white text-xs font-semibold rounded-full shadow-lg">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            เปิดใช้
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-500 text-white text-xs font-semibold rounded-full shadow-lg">
                            ปิด
                        </span>
                        @endif
                    </div>

                    {{-- Price Tag --}}
                    <div class="absolute bottom-4 left-4">
                        <div class="bg-white/95 dark:bg-slate-800/95 backdrop-blur-sm px-4 py-2 rounded-xl shadow-lg">
                            <p class="text-xs text-gray-600 dark:text-gray-400">เริ่มต้น</p>
                            <p class="text-2xl font-bold text-cyan-600 dark:text-cyan-400">
                                {{ number_format($product->base_price, 0) }}
                                <span class="text-sm">{{ $product->currency }}</span>
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Card Content --}}
                <div class="p-6">
                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1 line-clamp-2">{{ $product->name }}</h3>
                        @if($product->category)
                        <p class="text-sm text-cyan-600 dark:text-cyan-400 font-medium">{{ $product->category->name }}</p>
                        @endif
                    </div>

                    @if($product->short_description)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ $product->short_description }}</p>
                    @endif

                    {{-- Tags --}}
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if($product->is_customizable)
                        <span class="px-2 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 text-xs rounded-lg">
                            กำหนดเอง
                        </span>
                        @endif
                        @if($product->requires_consultation)
                        <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs rounded-lg">
                            ปรึกษา
                        </span>
                        @endif
                        @if($product->estimated_delivery_days)
                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs rounded-lg">
                            {{ $product->estimated_delivery_days }} วัน
                        </span>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2">
                        <a href="{{ route('admin.software-products.show', $product) }}"
                           class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-semibold rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            ดูรายละเอียด
                        </a>

                        <a href="{{ route('admin.software-products.edit', $product) }}"
                           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>

                        <form action="{{ route('admin.software-products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบผลิตภัณฑ์นี้?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $products->links() }}
        </div>
        @else
        {{-- Empty State --}}
        <div class="text-center py-16">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-cyan-100 to-blue-100 dark:from-cyan-900/30 dark:to-blue-900/30 rounded-full mb-6">
                <svg class="w-12 h-12 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีผลิตภัณฑ์</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นเพิ่มผลิตภัณฑ์ซอฟต์แวร์แรกของคุณเลย!</p>
            <a href="{{ route('admin.software-products.create') }}"
               class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                เพิ่มผลิตภัณฑ์ใหม่
            </a>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Animation for product cards
document.addEventListener('DOMContentLoaded', function() {
    gsap.from('.grid > div', {
        opacity: 0,
        y: 20,
        duration: 0.5,
        stagger: 0.1,
        ease: 'power2.out'
    });
});
</script>
@endpush
@endsection
