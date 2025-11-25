{{--
    หน้าบริการตามหมวดหมู่
    แสดงรายการบริการในหมวดหมู่ที่เลือก พร้อมฟิลเตอร์และการเรียงลำดับ
--}}
@extends('layouts.app')

@section('title', $category->name . ' - บริการ')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="categoryPage()">
    {{-- Breadcrumb --}}
    <nav class="mb-6 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
        <a href="{{ route('user.services.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            <i class="fas fa-home mr-1"></i>บริการทั้งหมด
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-purple-600 dark:text-purple-400 font-semibold">{{ $category->name }}</span>
    </nav>

    {{-- Category Header --}}
    <div class="mb-8 backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="relative h-48 md:h-64 overflow-hidden"
             style="background: linear-gradient(135deg, {{ $category->color }}40, {{ $category->color }}80);">
            @if($category->image_path)
                <img src="{{ asset('storage/' . $category->image_path) }}"
                     alt="{{ $category->name }}"
                     class="w-full h-full object-cover opacity-50">
            @endif

            {{-- Overlay Content --}}
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center">
                    <div class="text-6xl md:text-8xl mb-4">{{ $category->icon ?? '📦' }}</div>
                    <h1 class="text-3xl md:text-5xl font-bold text-white drop-shadow-lg">
                        {{ $category->name }}
                    </h1>
                    @if($category->description)
                        <p class="mt-2 text-lg text-white/90 max-w-2xl mx-auto px-4 drop-shadow">
                            {{ $category->description }}
                        </p>
                    @endif
                    <p class="mt-4 text-white/80">
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur rounded-full">
                            <i class="fas fa-concierge-bell"></i>
                            {{ $services->total() }} บริการ
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter & Sort --}}
    <div class="mb-6 backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-xl shadow-lg p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            {{-- Search --}}
            <div class="relative flex-1 max-w-md">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.300ms="filterServices()"
                       placeholder="ค้นหาบริการ..."
                       class="w-full pl-12 pr-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
            </div>

            {{-- Sort Options --}}
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-600 dark:text-gray-400">เรียงตาม:</span>
                <select x-model="sortBy"
                        @change="filterServices()"
                        class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white text-sm">
                    <option value="name">ชื่อ A-Z</option>
                    <option value="price_asc">ราคาต่ำ - สูง</option>
                    <option value="price_desc">ราคาสูง - ต่ำ</option>
                    <option value="rating">คะแนนสูงสุด</option>
                    <option value="popular">ยอดนิยม</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Services Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($services as $service)
        <div class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 group"
             x-show="matchesSearch('{{ addslashes($service->name) }}')"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">

            {{-- Service Image --}}
            <div class="relative h-48 overflow-hidden"
                 style="background: linear-gradient(135deg, {{ $category->color }}20, {{ $category->color }}40);">
                @if($service->image_path)
                    <img src="{{ asset('storage/' . $service->image_path) }}"
                         alt="{{ $service->name }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                @else
                    <div class="w-full h-full flex items-center justify-center text-6xl">
                        {{ $category->icon ?? '🔧' }}
                    </div>
                @endif

                {{-- Featured Badge --}}
                @if($service->is_featured)
                    <div class="absolute top-3 left-3">
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-yellow-400 to-orange-400 rounded-full text-xs font-bold text-white shadow-lg">
                            <i class="fas fa-star"></i>
                            แนะนำ
                        </span>
                    </div>
                @endif

                {{-- Active Badge --}}
                @if(!$service->is_active)
                    <div class="absolute top-3 right-3">
                        <span class="px-3 py-1 bg-red-500 text-white rounded-full text-xs font-semibold">
                            ปิดรับจอง
                        </span>
                    </div>
                @endif
            </div>

            {{-- Service Info --}}
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors line-clamp-1">
                    {{ $service->name }}
                </h3>

                @if($service->description)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                        {{ $service->description }}
                    </p>
                @endif

                {{-- Service Meta --}}
                <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400 mb-4">
                    <span class="inline-flex items-center gap-1">
                        <i class="fas fa-clock"></i>
                        {{ $service->duration_minutes ?? 60 }} นาที
                    </span>
                    @if($service->requires_location)
                        <span class="inline-flex items-center gap-1 text-blue-600 dark:text-blue-400">
                            <i class="fas fa-map-marker-alt"></i>
                            ออกให้บริการ
                        </span>
                    @endif
                </div>

                {{-- Rating & Reviews --}}
                @if($service->reviews_count > 0)
                    <div class="flex items-center gap-2 mb-4">
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star text-sm {{ $i <= round($service->average_rating ?? 0) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}"></i>
                            @endfor
                        </div>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ number_format($service->average_rating ?? 0, 1) }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            ({{ $service->reviews_count }} รีวิว)
                        </span>
                    </div>
                @endif

                {{-- Price --}}
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">เริ่มต้น</p>
                        <p class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                            ฿{{ number_format($service->base_price, 2) }}
                        </p>
                    </div>

                    {{-- PV Badge --}}
                    @if($service->pv_value > 0)
                        <div class="text-right">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-xs font-semibold">
                                <i class="fas fa-star"></i>
                                {{ number_format($service->pv_value) }} PV
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Cashback Info --}}
                @if($service->cashback_percentage > 0)
                    <div class="mb-4 p-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-center gap-2 text-sm text-green-700 dark:text-green-400">
                            <i class="fas fa-gift"></i>
                            <span>Cashback {{ number_format($service->cashback_percentage) }}%</span>
                        </div>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="flex gap-2">
                    <a href="{{ route('user.services.show', $service) }}"
                       class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-200 {{ !$service->is_active ? 'opacity-50 cursor-not-allowed' : '' }}"
                       {{ !$service->is_active ? 'onclick="event.preventDefault(); alert(\'บริการนี้ปิดรับจองชั่วคราว\')"' : '' }}>
                        <i class="fas fa-calendar-check"></i>
                        <span>จองเลย</span>
                    </a>

                    <a href="{{ route('user.services.show', $service) }}"
                       class="px-4 py-3 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-all duration-200"
                       title="ดูรายละเอียด">
                        <i class="fas fa-info-circle"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-12 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-4"
                     style="background: {{ $category->color }}20;">
                    <span class="text-4xl">{{ $category->icon ?? '📦' }}</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ไม่พบบริการ</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    ยังไม่มีบริการในหมวดหมู่ "{{ $category->name }}" ในขณะนี้
                </p>
                <a href="{{ route('user.services.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all duration-200">
                    <i class="fas fa-arrow-left"></i>
                    กลับไปหน้าบริการทั้งหมด
                </a>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($services->hasPages())
        <div class="mt-8">
            {{ $services->links() }}
        </div>
    @endif

    {{-- Related Categories --}}
    @if(isset($relatedCategories) && $relatedCategories->count() > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-th-large mr-2 text-purple-600 dark:text-purple-400"></i>
                หมวดหมู่อื่นที่น่าสนใจ
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($relatedCategories as $relatedCategory)
                    <a href="{{ route('user.services.category', $relatedCategory) }}"
                       class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all duration-200 hover:scale-105 hover:border-purple-400 dark:hover:border-purple-500 group">
                        <span class="text-3xl">{{ $relatedCategory->icon ?? '📦' }}</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white text-center group-hover:text-purple-600 dark:group-hover:text-purple-400">
                            {{ $relatedCategory->name }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            ({{ $relatedCategory->active_services_count ?? 0 }} บริการ)
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function categoryPage() {
    return {
        searchQuery: '',
        sortBy: 'name',

        matchesSearch(serviceName) {
            if (!this.searchQuery) return true;
            return serviceName.toLowerCase().includes(this.searchQuery.toLowerCase());
        },

        filterServices() {
            // สำหรับ client-side filtering (ถ้าต้องการ server-side ให้ใช้ form submit)
            // ปัจจุบันใช้ Alpine.js filter บน client
        }
    }
}
</script>
@endpush
