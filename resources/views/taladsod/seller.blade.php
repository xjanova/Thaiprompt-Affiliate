@extends('layouts.taladsod')
@section('title', $seller->shop_name . ' - ตลาดสดไทยพร้อม')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Seller Header -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-32"></div>
        <div class="px-6 pb-6 -mt-12">
            <div class="flex flex-col md:flex-row items-start md:items-end gap-4">
                <div class="w-24 h-24 rounded-full bg-white dark:bg-gray-700 border-4 border-white dark:border-gray-800 shadow-lg flex items-center justify-center overflow-hidden">
                    @if($seller->shop_image)
                        <img src="{{ $seller->shop_image }}" alt="{{ $seller->shop_name }}" class="w-full h-full object-cover">
                    @else
                        <span class="text-4xl">🏪</span>
                    @endif
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $seller->shop_name }}</h1>
                    <div class="flex items-center gap-4 mt-1 text-sm text-gray-600 dark:text-gray-400">
                        @if($seller->is_verified)
                            <span class="inline-flex items-center gap-1 text-green-600"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> ยืนยันแล้ว</span>
                        @endif
                        <span>{{ $seller->province ?? 'ไม่ระบุจังหวัด' }}</span>
                        <span>สมาชิกตั้งแต่ {{ $seller->created_at->thaidate('M Y') ?? $seller->created_at->format('M Y') }}</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="text-center px-4 py-2 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                        <div class="text-xl font-bold text-yellow-600">{{ number_format($seller->rating_average, 1) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">คะแนน ({{ $seller->rating_count }})</div>
                    </div>
                    <div class="text-center px-4 py-2 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <div class="text-xl font-bold text-green-600">{{ number_format($seller->total_sales) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">ขายแล้ว</div>
                    </div>
                </div>
            </div>
            @if($seller->shop_description)
                <p class="mt-4 text-gray-600 dark:text-gray-400">{{ $seller->shop_description }}</p>
            @endif
        </div>
    </div>

    <!-- Seller Listings -->
    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">สินค้าทั้งหมด ({{ $listings->total() }} รายการ)</h2>

    @if($listings->count() > 0)
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($listings as $listing)
                <a href="{{ route('taladsod.listing', $listing->slug) }}" class="group bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition overflow-hidden">
                    <div class="aspect-square overflow-hidden bg-gray-100 dark:bg-gray-700">
                        @if($listing->main_image_url)
                            <img src="{{ $listing->main_image_url }}" alt="{{ $listing->title }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-4xl">🥬</div>
                        @endif
                    </div>
                    <div class="p-3">
                        <h3 class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ $listing->title }}</h3>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-green-600 font-bold">฿{{ number_format($listing->price) }}/{{ $listing->unit }}</span>
                            @if($listing->is_organic)
                                <span class="text-xs bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 px-1.5 py-0.5 rounded">อินทรีย์</span>
                            @endif
                        </div>
                        @if($listing->category)
                            <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">{{ $listing->category->icon }} {{ $listing->category->name }}</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $listings->links() }}</div>
    @else
        <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl">
            <div class="text-5xl mb-3">📦</div>
            <p class="text-gray-500 dark:text-gray-400">ยังไม่มีสินค้า</p>
        </div>
    @endif
</div>
@endsection