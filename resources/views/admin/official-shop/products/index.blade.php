{{--
    Official Shop Products List - Admin Panel
    หน้าแสดงรายการสินค้าร้านของระบบ (Premium V3)
--}}

@extends('layouts.admin-v3')

@section('title', 'สินค้า Official Shop')

@section('content')
<div class="space-y-6" x-data="productManager()">
    {{-- Alert Messages --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20
                border-l-4 border-green-500 rounded-xl shadow-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                <span class="text-green-800 dark:text-green-200 font-medium">{{ session('success') }}</span>
            </div>
            <button @click="show = false" class="text-green-500 hover:text-green-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="bg-gradient-to-r from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20
                border-l-4 border-red-500 rounded-xl shadow-lg p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                <span class="text-red-800 dark:text-red-200 font-medium">{{ session('error') }}</span>
            </div>
            <button @click="show = false" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    @endif

    {{-- Header --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8">
        <div class="absolute inset-0 bg-black/10" style="background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.15) 1px, transparent 0); background-size: 40px 40px;"></div>

        <div class="relative flex flex-col md:flex-row md:justify-between md:items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-2xl p-4 ring-4 ring-amber-400/30">
                    <i class="fas fa-boxes text-4xl text-white"></i>
                </div>
                <div class="text-white">
                    <h1 class="text-3xl font-bold mb-1">สินค้า Official Shop</h1>
                    <p class="text-white/90 text-sm">จัดการสินค้าร้านทางการของระบบ</p>
                </div>
            </div>

            <a href="{{ route('admin.official-shop.products.create') }}"
               class="px-6 py-3 bg-white text-purple-600 font-bold rounded-xl shadow-lg
                      hover:bg-purple-50 transition-all flex items-center gap-2 self-start md:self-auto">
                <i class="fas fa-plus"></i>
                เพิ่มสินค้าใหม่
            </a>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fas fa-boxes text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ทั้งหมด</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-check text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <i class="fas fa-star text-amber-600 dark:text-amber-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['featured']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">แนะนำ</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['out_of_stock']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">สินค้าหมด</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 p-6">
        <form method="GET" action="{{ route('admin.official-shop.products.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            {{-- Search --}}
            <div class="md:col-span-2">
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="ค้นหาสินค้า..."
                           class="w-full pl-10 pr-4 py-3 bg-gray-100 dark:bg-gray-700
                                  border-2 border-transparent focus:border-purple-500
                                  rounded-xl transition-colors">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            {{-- Category --}}
            <div>
                <select name="category"
                        class="w-full py-3 px-4 bg-gray-100 dark:bg-gray-700
                               border-2 border-transparent focus:border-purple-500
                               rounded-xl transition-colors">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div>
                <select name="status"
                        class="w-full py-3 px-4 bg-gray-100 dark:bg-gray-700
                               border-2 border-transparent focus:border-purple-500
                               rounded-xl transition-colors">
                    <option value="">ทุกสถานะ</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                    <option value="featured" {{ request('status') === 'featured' ? 'selected' : '' }}>สินค้าแนะนำ</option>
                </select>
            </div>

            {{-- Actions --}}
            <div class="flex gap-2">
                <button type="submit"
                        class="flex-1 py-3 px-4 bg-gradient-to-r from-purple-500 to-pink-500
                               text-white font-bold rounded-xl hover:shadow-lg transition-all">
                    <i class="fas fa-search mr-2"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.official-shop.products.index') }}"
                   class="py-3 px-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                          font-bold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    {{-- Products Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        @if($products->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                            สินค้า
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                            หมวดหมู่
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                            ราคา
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                            สต็อก
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($products as $product)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
                                    @if($product->main_image_url)
                                    <img src="{{ $product->main_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 text-xl"></i>
                                    </div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate max-w-xs">
                                        {{ $product->name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">SKU: {{ $product->sku ?? '-' }}</p>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if($product->is_featured)
                                        <span class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs rounded-full">
                                            <i class="fas fa-star mr-1"></i>แนะนำ
                                        </span>
                                        @endif
                                        @if($product->pv_value > 0)
                                        <span class="px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-xs rounded-full">
                                            PV: {{ $product->pv_value }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-700 dark:text-gray-300">
                                {{ $product->category->name ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <p class="font-bold text-green-600 dark:text-green-400">฿{{ number_format($product->price, 2) }}</p>
                            @if($product->compare_at_price && $product->compare_at_price > $product->price)
                            <p class="text-sm text-gray-400 line-through">฿{{ number_format($product->compare_at_price, 2) }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($product->track_inventory)
                                @if($product->stock_status === 'out_of_stock')
                                <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-semibold rounded-full">
                                    หมด
                                </span>
                                @elseif($product->stock_status === 'low_stock')
                                <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-xs font-semibold rounded-full">
                                    {{ $product->stock_quantity }} เหลือน้อย
                                </span>
                                @else
                                <span class="text-gray-700 dark:text-gray-300">{{ $product->stock_quantity }}</span>
                                @endif
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Active Toggle --}}
                                <form action="{{ route('admin.official-shop.products.toggle-active', $product) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" title="{{ $product->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}"
                                            class="w-10 h-6 rounded-full relative transition-colors
                                                   {{ $product->is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                                        <span class="absolute top-1 w-4 h-4 bg-white rounded-full shadow transition-transform
                                                     {{ $product->is_active ? 'left-5' : 'left-1' }}"></span>
                                    </button>
                                </form>

                                {{-- Featured Toggle --}}
                                <form action="{{ route('admin.official-shop.products.toggle-featured', $product) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" title="{{ $product->is_featured ? 'นำออกจากแนะนำ' : 'เพิ่มเป็นแนะนำ' }}"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors
                                                   {{ $product->is_featured ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-400' }}">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.official-shop.products.edit', $product) }}"
                                   class="w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400
                                          flex items-center justify-center hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                                   title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('official-shop.show', $product->slug) }}" target="_blank"
                                   class="w-9 h-9 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400
                                          flex items-center justify-center hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors"
                                   title="ดูหน้าร้าน">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <button @click="confirmDelete({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                        class="w-9 h-9 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400
                                               flex items-center justify-center hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                                        title="ลบ">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
            {{ $products->links() }}
        </div>
        @else
        <div class="text-center py-16">
            <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <i class="fas fa-box-open text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยังไม่มีสินค้า Official Shop</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">เริ่มต้นเพิ่มสินค้าแรกของร้านทางการ</p>
            <a href="{{ route('admin.official-shop.products.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500
                      text-white font-bold rounded-xl hover:shadow-lg transition-all">
                <i class="fas fa-plus"></i>
                เพิ่มสินค้าใหม่
            </a>
        </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         x-transition>
        <div @click.away="showDeleteModal = false"
             class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 w-full max-w-md">
            <div class="text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยืนยันการลบสินค้า</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    คุณต้องการลบสินค้า "<span class="font-semibold text-gray-700 dark:text-gray-300" x-text="deleteProductName"></span>" ใช่หรือไม่?
                </p>
                <div class="flex gap-3">
                    <button @click="showDeleteModal = false"
                            class="flex-1 py-3 px-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                                   font-bold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                        ยกเลิก
                    </button>
                    <form :action="deleteUrl" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full py-3 px-4 bg-red-500 text-white font-bold rounded-xl hover:bg-red-600 transition-all">
                            ลบสินค้า
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function productManager() {
    return {
        showDeleteModal: false,
        deleteUrl: '',
        deleteProductName: '',

        confirmDelete(productId, productName) {
            this.deleteUrl = '{{ route("admin.official-shop.products.destroy", "") }}/' + productId;
            this.deleteProductName = productName;
            this.showDeleteModal = true;
        }
    };
}
</script>
@endpush
@endsection
