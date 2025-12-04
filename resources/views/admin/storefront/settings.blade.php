{{--
    Admin Storefront Settings - หน้าตั้งค่า Storefront

    จัดการการตั้งค่าต่างๆ ของหน้า Storefront
    - Theme Colors (สี Primary, Secondary)
    - Background Settings (รูปฉากหลัง)
    - Category Settings (รูปของหมวดหมู่)
    - Banner Management

    รองรับ Dark Mode และ Responsive
--}}

@extends('layouts.admin')

@section('title', 'ตั้งค่า Storefront')

@section('content')
<div class="container mx-auto px-4 py-8">
    {{-- Page Header --}}
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-store-alt text-orange-500 mr-3"></i>
                    ตั้งค่า Storefront
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    จัดการหน้าตา สี ธีม และการแสดงผลของหน้าร้านค้า
                </p>
            </div>
            <a href="{{ route('storefront.index') }}"
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg transition-colors">
                <i class="fas fa-external-link-alt"></i>
                ดู Storefront
            </a>
        </div>
    </div>

    {{-- Settings Tabs --}}
    <div x-data="{ activeTab: 'theme' }" class="space-y-6">
        {{-- Tab Navigation --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-2">
            <div class="flex flex-wrap gap-2">
                <button @click="activeTab = 'theme'"
                        :class="activeTab === 'theme' ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-lg font-medium transition-all">
                    <i class="fas fa-palette mr-2"></i>
                    ธีมและสี
                </button>
                <button @click="activeTab = 'banners'"
                        :class="activeTab === 'banners' ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-lg font-medium transition-all">
                    <i class="fas fa-images mr-2"></i>
                    แบนเนอร์ ({{ $banners->count() }})
                </button>
                <button @click="activeTab = 'categories'"
                        :class="activeTab === 'categories' ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-lg font-medium transition-all">
                    <i class="fas fa-th-large mr-2"></i>
                    หมวดหมู่ ({{ $categories->count() }})
                </button>
                <button @click="activeTab = 'layout'"
                        :class="activeTab === 'layout' ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-lg font-medium transition-all">
                    <i class="fas fa-columns mr-2"></i>
                    เลย์เอาต์
                </button>
            </div>
        </div>

        {{-- Theme & Colors Tab --}}
        <div x-show="activeTab === 'theme'" x-cloak>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-palette text-orange-500 mr-2"></i>
                    ธีมและสี
                </h2>

                <form action="{{ route('admin.storefront.update-theme') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- Primary Colors --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                สี Primary
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color"
                                       name="primary_color"
                                       value="{{ setting('storefront_primary_color', '#F97316') }}"
                                       class="w-12 h-12 rounded-lg cursor-pointer border-0">
                                <input type="text"
                                       name="primary_color_hex"
                                       value="{{ setting('storefront_primary_color', '#F97316') }}"
                                       class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                       placeholder="#F97316">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                สี Secondary
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color"
                                       name="secondary_color"
                                       value="{{ setting('storefront_secondary_color', '#EF4444') }}"
                                       class="w-12 h-12 rounded-lg cursor-pointer border-0">
                                <input type="text"
                                       name="secondary_color_hex"
                                       value="{{ setting('storefront_secondary_color', '#EF4444') }}"
                                       class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                       placeholder="#EF4444">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                สี Accent
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color"
                                       name="accent_color"
                                       value="{{ setting('storefront_accent_color', '#EC4899') }}"
                                       class="w-12 h-12 rounded-lg cursor-pointer border-0">
                                <input type="text"
                                       name="accent_color_hex"
                                       value="{{ setting('storefront_accent_color', '#EC4899') }}"
                                       class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                       placeholder="#EC4899">
                            </div>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div class="p-6 rounded-xl bg-gradient-to-r from-orange-500 via-red-500 to-pink-500 text-white">
                        <h3 class="font-bold text-lg mb-2">ตัวอย่าง Gradient</h3>
                        <p class="text-white/80">นี่คือตัวอย่างสีที่จะแสดงใน Storefront</p>
                        <button type="button" class="mt-4 px-4 py-2 bg-white text-orange-600 font-bold rounded-lg">
                            ปุ่มตัวอย่าง
                        </button>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            บันทึกการตั้งค่าสี
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Banners Tab --}}
        <div x-show="activeTab === 'banners'" x-cloak>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-images text-orange-500 mr-2"></i>
                        จัดการแบนเนอร์
                    </h2>
                    <a href="{{ route('admin.storefront.banners.create') }}"
                       class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        เพิ่มแบนเนอร์
                    </a>
                </div>

                @if($banners->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($banners as $banner)
                    <div class="relative group rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700">
                        @if($banner->image_url)
                        <img src="{{ $banner->image_url }}"
                             alt="{{ $banner->title }}"
                             class="w-full h-40 object-cover">
                        @else
                        <div class="w-full h-40 bg-gradient-to-r {{ $banner->gradient ?? 'from-orange-400 to-red-400' }} flex items-center justify-center text-white">
                            <span class="text-lg font-bold">{{ $banner->title ?? 'No Image' }}</span>
                        </div>
                        @endif

                        {{-- Overlay Actions --}}
                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                            <a href="{{ route('admin.storefront.banners.edit', $banner) }}"
                               class="px-3 py-2 bg-white text-gray-900 rounded-lg font-medium hover:bg-gray-100">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.storefront.banners.destroy', $banner) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('ยืนยันการลบแบนเนอร์นี้?')"
                                        class="px-3 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>

                        {{-- Status Badge --}}
                        <div class="absolute top-2 right-2">
                            <span class="px-2 py-1 text-xs font-bold rounded-full {{ $banner->is_active ? 'bg-green-500 text-white' : 'bg-gray-500 text-white' }}">
                                {{ $banner->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        {{-- Info --}}
                        <div class="p-3 bg-white dark:bg-gray-800">
                            <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ $banner->title ?? 'No Title' }}</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Order: {{ $banner->sort_order }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-12">
                    <i class="fas fa-images text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มีแบนเนอร์</p>
                    <a href="{{ route('admin.storefront.banners.create') }}"
                       class="inline-block mt-4 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg">
                        เพิ่มแบนเนอร์แรก
                    </a>
                </div>
                @endif
            </div>
        </div>

        {{-- Categories Tab --}}
        <div x-show="activeTab === 'categories'" x-cloak>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-th-large text-orange-500 mr-2"></i>
                        จัดการหมวดหมู่
                    </h2>
                    <a href="{{ route('admin.product-categories.index') }}"
                       class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-medium rounded-lg transition-colors">
                        <i class="fas fa-cog mr-2"></i>
                        จัดการหมวดหมู่ทั้งหมด
                    </a>
                </div>

                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    ตั้งค่าไอคอนและรูปภาพสำหรับแต่ละหมวดหมู่ที่จะแสดงใน Storefront
                </p>

                @if($categories->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($categories as $category)
                    <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-orange-500 dark:hover:border-orange-500 transition-colors">
                        <div class="flex items-center gap-4">
                            {{-- Icon/Image --}}
                            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-100 to-pink-100 dark:from-orange-900/30 dark:to-pink-900/30 flex items-center justify-center">
                                @if($category->icon)
                                <i class="{{ $category->icon }} text-2xl text-orange-600 dark:text-orange-400"></i>
                                @elseif($category->image_url)
                                <img src="{{ $category->image_url }}" alt="{{ $category->name }}" class="w-full h-full object-cover rounded-xl">
                                @else
                                <i class="fas fa-box text-2xl text-gray-400"></i>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <h4 class="font-medium text-gray-900 dark:text-white truncate">{{ $category->name }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $category->products_count ?? 0 }} สินค้า
                                </p>
                            </div>

                            <a href="{{ route('admin.product-categories.edit', $category) }}"
                               class="p-2 text-gray-400 hover:text-orange-500 transition-colors">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-12">
                    <i class="fas fa-th-large text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                    <p class="text-gray-500 dark:text-gray-400">ยังไม่มีหมวดหมู่</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Layout Tab --}}
        <div x-show="activeTab === 'layout'" x-cloak>
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                    <i class="fas fa-columns text-orange-500 mr-2"></i>
                    การตั้งค่าเลย์เอาต์
                </h2>

                <form action="{{ route('admin.storefront.update-layout') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    {{-- Products per row --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำนวนสินค้าต่อแถว
                        </label>
                        <select name="products_per_row"
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                            <option value="4" {{ setting('storefront_products_per_row', '6') == '4' ? 'selected' : '' }}>4 สินค้า</option>
                            <option value="5" {{ setting('storefront_products_per_row', '6') == '5' ? 'selected' : '' }}>5 สินค้า</option>
                            <option value="6" {{ setting('storefront_products_per_row', '6') == '6' ? 'selected' : '' }}>6 สินค้า (แนะนำ)</option>
                        </select>
                    </div>

                    {{-- Show sections --}}
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-900 dark:text-white">แสดงส่วนต่างๆ</h3>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   name="show_flash_deals"
                                   value="1"
                                   {{ setting('storefront_show_flash_deals', '1') ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                            <span class="text-gray-700 dark:text-gray-300">แสดง Flash Deals</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   name="show_featured_stores"
                                   value="1"
                                   {{ setting('storefront_show_featured_stores', '1') ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                            <span class="text-gray-700 dark:text-gray-300">แสดงร้านค้าแนะนำ</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   name="show_categories"
                                   value="1"
                                   {{ setting('storefront_show_categories', '1') ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                            <span class="text-gray-700 dark:text-gray-300">แสดงหมวดหมู่</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   name="show_pv_on_products"
                                   value="1"
                                   {{ setting('storefront_show_pv', '1') ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                            <span class="text-gray-700 dark:text-gray-300">แสดง PV ที่สินค้า</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   name="show_commission_on_products"
                                   value="1"
                                   {{ setting('storefront_show_commission', '0') ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                            <span class="text-gray-700 dark:text-gray-300">แสดงคอมมิชชั่นที่สินค้า (เฉพาะผู้ล็อกอิน)</span>
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                                class="px-6 py-3 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            บันทึกการตั้งค่า
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
