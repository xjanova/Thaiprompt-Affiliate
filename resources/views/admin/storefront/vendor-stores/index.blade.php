{{--
    Admin Vendor Stores Index - จัดการร้านค้าทั้งหมด

    หน้านี้แสดงรายการร้านค้าทั้งหมดในระบบ
    Admin สามารถค้นหา, กรอง, และจัดการร้านค้าได้

    @version 3.0
    @uses Tailwind CSS + Alpine.js
--}}

@extends('layouts.admin')

@section('title', 'จัดการร้านค้าทั้งหมด')

@section('content')
<div class="space-y-6" x-data="vendorStoresManager()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-orange-500 to-pink-600 rounded-xl text-white">
                    <i class="fas fa-store-alt"></i>
                </div>
                จัดการร้านค้าทั้งหมด
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                ดูและจัดการร้านค้า Vendor ทั้งหมดในระบบ
            </p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Total Stores --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-xl">
                    <i class="fas fa-store text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ร้านค้าทั้งหมด</p>
                </div>
            </div>
        </div>

        {{-- Active Stores --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-xl">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['active']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">เปิดใช้งาน</p>
                </div>
            </div>
        </div>

        {{-- Featured Stores --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl">
                    <i class="fas fa-star text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['featured']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ร้านค้าแนะนำ</p>
                </div>
            </div>
        </div>

        {{-- Verified Stores --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-xl">
                    <i class="fas fa-badge-check text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['verified']) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ยืนยันแล้ว</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters & Search --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg border border-gray-100 dark:border-gray-700">
        <form action="{{ route('admin.storefront.vendor-stores.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            {{-- Search --}}
            <div class="flex-1">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="ค้นหาชื่อร้าน, เจ้าของร้าน..."
                           class="w-full pl-10 pr-4 py-2.5 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl
                                  text-gray-900 dark:text-white placeholder-gray-400
                                  focus:ring-2 focus:ring-orange-500">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            {{-- Status Filter --}}
            <div class="w-full md:w-48">
                <select name="status"
                        class="w-full px-4 py-2.5 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl
                               text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500">
                    <option value="">ทุกสถานะ</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                    <option value="featured" {{ request('status') === 'featured' ? 'selected' : '' }}>ร้านค้าแนะนำ</option>
                    <option value="verified" {{ request('status') === 'verified' ? 'selected' : '' }}>ยืนยันแล้ว</option>
                </select>
            </div>

            {{-- Search Button --}}
            <button type="submit"
                    class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-pink-600
                           hover:from-orange-600 hover:to-pink-700
                           text-white font-semibold rounded-xl
                           transition-all hover:scale-105 shadow-lg">
                <i class="fas fa-search mr-2"></i>
                ค้นหา
            </button>
        </form>
    </div>

    {{-- Stores Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            ร้านค้า
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            เจ้าของ
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            สินค้า
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            สถานะ
                        </th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            แนะนำ
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            จัดการ
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($stores as $store)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        {{-- Store Info --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                @if($store->store_logo)
                                    <img src="{{ asset('storage/' . $store->store_logo) }}"
                                         alt="{{ $store->store_name }}"
                                         class="w-12 h-12 rounded-xl object-cover border-2 border-gray-200 dark:border-gray-600">
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-400 to-pink-500
                                                flex items-center justify-center text-white font-bold text-lg">
                                        {{ strtoupper(substr($store->store_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <a href="{{ route('admin.storefront.vendor-stores.show', $store) }}"
                                       class="font-semibold text-gray-900 dark:text-white hover:text-orange-600 dark:hover:text-orange-400">
                                        {{ $store->store_name }}
                                    </a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        /{{ $store->store_slug }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        {{-- Owner --}}
                        <td class="px-6 py-4">
                            <div class="text-sm">
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $store->user->name ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $store->user->email ?? '' }}
                                </p>
                            </div>
                        </td>

                        {{-- Products Count --}}
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                        bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                {{ number_format($store->products_count) }}
                            </span>
                        </td>

                        {{-- Status Toggle --}}
                        <td class="px-6 py-4 text-center">
                            <button @click="toggleStatus({{ $store->id }})"
                                    :class="storeStatuses[{{ $store->id }}] ?? {{ $store->is_active ? 'true' : 'false' }}
                                           ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                                <span :class="storeStatuses[{{ $store->id }}] ?? {{ $store->is_active ? 'true' : 'false' }}
                                             ? 'translate-x-6' : 'translate-x-1'"
                                      class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"></span>
                            </button>
                        </td>

                        {{-- Featured Toggle --}}
                        <td class="px-6 py-4 text-center">
                            <button @click="toggleFeatured({{ $store->id }})"
                                    :class="storeFeatured[{{ $store->id }}] ?? {{ $store->is_featured_home ? 'true' : 'false' }}
                                           ? 'text-yellow-500' : 'text-gray-300 dark:text-gray-600'"
                                    class="text-2xl hover:scale-110 transition-transform">
                                <i class="fas fa-star"></i>
                            </button>
                        </td>

                        {{-- Actions --}}
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.storefront.vendor-stores.show', $store) }}"
                                   class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg transition"
                                   title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.storefront.vendor-stores.edit', $store) }}"
                                   class="p-2 text-orange-600 dark:text-orange-400 hover:bg-orange-100 dark:hover:bg-orange-900/30 rounded-lg transition"
                                   title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.storefront.vendor-stores.destroy', $store) }}"
                                      method="POST"
                                      onsubmit="return confirm('คุณต้องการลบร้านค้า {{ $store->store_name }} หรือไม่?')"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition"
                                            title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-full">
                                    <i class="fas fa-store-slash text-3xl text-gray-400"></i>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">ไม่พบร้านค้าในระบบ</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($stores->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $stores->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js Component สำหรับจัดการร้านค้า
 */
function vendorStoresManager() {
    return {
        storeStatuses: {},
        storeFeatured: {},

        /**
         * สลับสถานะ Active/Inactive
         *
         * @param {number} storeId
         */
        async toggleStatus(storeId) {
            try {
                const response = await fetch(`{{ url('admin/storefront/vendor-stores') }}/${storeId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.storeStatuses[storeId] = data.is_active;
                    // แสดง notification
                    if (typeof showNotification === 'function') {
                        showNotification(data.message, 'success');
                    }
                }
            } catch (error) {
                console.error('Error toggling status:', error);
            }
        },

        /**
         * สลับสถานะ Featured
         *
         * @param {number} storeId
         */
        async toggleFeatured(storeId) {
            try {
                const response = await fetch(`{{ url('admin/storefront/vendor-stores') }}/${storeId}/toggle-featured`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.storeFeatured[storeId] = data.is_featured;
                    // แสดง notification
                    if (typeof showNotification === 'function') {
                        showNotification(data.message, 'success');
                    }
                }
            } catch (error) {
                console.error('Error toggling featured:', error);
            }
        }
    };
}
</script>
@endpush
@endsection
