{{--
    Admin Vendor Store Show - รายละเอียดร้านค้า

    แสดงรายละเอียดร้านค้าและสถิติต่างๆ

    @version 3.0
    @uses Tailwind CSS + Alpine.js
--}}

@extends('layouts.admin')

@section('title', 'รายละเอียดร้าน: ' . $store->store_name)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.storefront.vendor-stores.index') }}"
               class="p-2 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left text-gray-600 dark:text-gray-300"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $store->store_name }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    /{{ $store->store_slug }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('vendor.show', $store->store_slug) }}"
               target="_blank"
               class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                      rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                <i class="fas fa-external-link-alt mr-2"></i>
                ดูหน้าร้าน
            </a>
            <a href="{{ route('admin.storefront.vendor-stores.edit', $store) }}"
               class="px-4 py-2 bg-gradient-to-r from-orange-500 to-pink-600 text-white
                      font-semibold rounded-xl hover:shadow-lg transition">
                <i class="fas fa-edit mr-2"></i>
                แก้ไข
            </a>
        </div>
    </div>

    {{-- Store Banner & Info --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        {{-- Banner --}}
        <div class="h-48 bg-gradient-to-r from-orange-400 via-pink-500 to-purple-600 relative">
            @if($store->store_banner)
                <img src="{{ asset('storage/' . $store->store_banner) }}"
                     alt="Banner"
                     class="w-full h-full object-cover">
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
        </div>

        {{-- Store Info --}}
        <div class="p-6 -mt-16 relative">
            <div class="flex flex-col md:flex-row gap-6">
                {{-- Logo --}}
                <div class="flex-shrink-0">
                    @if($store->store_logo)
                        <img src="{{ asset('storage/' . $store->store_logo) }}"
                             alt="{{ $store->store_name }}"
                             class="w-28 h-28 rounded-2xl object-cover border-4 border-white dark:border-gray-800 shadow-xl">
                    @else
                        <div class="w-28 h-28 rounded-2xl bg-gradient-to-br from-orange-400 to-pink-500
                                    flex items-center justify-center text-white font-bold text-4xl
                                    border-4 border-white dark:border-gray-800 shadow-xl">
                            {{ strtoupper(substr($store->store_name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 pt-8 md:pt-12">
                    <div class="flex flex-wrap items-center gap-3 mb-3">
                        {{-- Status Badges --}}
                        @if($store->is_active)
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-sm font-medium rounded-full">
                                <i class="fas fa-check-circle mr-1"></i> เปิดใช้งาน
                            </span>
                        @else
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-medium rounded-full">
                                <i class="fas fa-pause-circle mr-1"></i> ปิดใช้งาน
                            </span>
                        @endif

                        @if($store->is_verified)
                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-sm font-medium rounded-full">
                                <i class="fas fa-badge-check mr-1"></i> ยืนยันแล้ว
                            </span>
                        @endif

                        @if($store->is_featured_home)
                            <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 text-sm font-medium rounded-full">
                                <i class="fas fa-star mr-1"></i> ร้านค้าแนะนำ
                            </span>
                        @endif
                    </div>

                    <p class="text-gray-600 dark:text-gray-300 mb-4">
                        {{ $store->store_description ?: 'ไม่มีคำอธิบาย' }}
                    </p>

                    {{-- Owner Info --}}
                    <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-user"></i>
                        <span>เจ้าของ: <strong class="text-gray-700 dark:text-gray-200">{{ $store->user->name ?? 'N/A' }}</strong></span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span>{{ $store->user->email ?? '' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        {{-- Products --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['products_count']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">สินค้า</div>
        </div>

        {{-- Orders --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['orders_count']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">คำสั่งซื้อ</div>
        </div>

        {{-- Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">฿{{ number_format($stats['total_revenue'], 0) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">รายได้รวม</div>
        </div>

        {{-- Rating --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">
                {{ number_format($stats['avg_rating'], 1) }} <i class="fas fa-star text-lg"></i>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">คะแนนเฉลี่ย</div>
        </div>

        {{-- Reviews --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['reviews_count']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">รีวิว</div>
        </div>
    </div>

    {{-- Settings Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Store Settings --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-cog text-orange-500"></i>
                ตั้งค่าร้านค้า
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">ค่าคอมมิชชั่น</span>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ $store->commission_rate ?? 0 }}%</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">ค่าส่งขั้นต่ำ</span>
                    <span class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($store->shipping_fee ?? 0, 0) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="text-gray-600 dark:text-gray-400">ส่งฟรีเมื่อสั่งขั้นต่ำ</span>
                    <span class="font-semibold text-gray-900 dark:text-white">฿{{ number_format($store->free_shipping_threshold ?? 0, 0) }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-600 dark:text-gray-400">รับชำระปลายทาง (COD)</span>
                    <span class="font-semibold {{ $store->enable_cod ? 'text-green-600' : 'text-gray-400' }}">
                        {{ $store->enable_cod ? 'เปิด' : 'ปิด' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Social & Contact --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <i class="fas fa-share-alt text-blue-500"></i>
                ช่องทางติดต่อ
            </h3>
            <div class="space-y-3">
                @if($store->store_email)
                <div class="flex items-center gap-3 py-2">
                    <i class="fas fa-envelope w-5 text-gray-400"></i>
                    <span class="text-gray-900 dark:text-white">{{ $store->store_email }}</span>
                </div>
                @endif
                @if($store->store_phone)
                <div class="flex items-center gap-3 py-2">
                    <i class="fas fa-phone w-5 text-gray-400"></i>
                    <span class="text-gray-900 dark:text-white">{{ $store->store_phone }}</span>
                </div>
                @endif
                @if($store->facebook_url)
                <a href="{{ $store->facebook_url }}" target="_blank" class="flex items-center gap-3 py-2 hover:text-blue-600">
                    <i class="fab fa-facebook w-5 text-blue-600"></i>
                    <span class="text-gray-900 dark:text-white">Facebook</span>
                </a>
                @endif
                @if($store->line_oa_id)
                <div class="flex items-center gap-3 py-2">
                    <i class="fab fa-line w-5 text-green-500"></i>
                    <span class="text-gray-900 dark:text-white">{{ $store->line_oa_id }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent Products --}}
    @if($store->products && $store->products->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-lg border border-gray-100 dark:border-gray-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <i class="fas fa-box text-purple-500"></i>
            สินค้าล่าสุด
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            @foreach($store->products as $product)
            <div class="group">
                <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 mb-2">
                    <img src="{{ $product->main_image_url ?? 'https://via.placeholder.com/200' }}"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                </div>
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $product->name }}</p>
                <p class="text-sm text-orange-600 dark:text-orange-400 font-bold">฿{{ number_format($product->price, 0) }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
