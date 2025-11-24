@extends('layouts.app')

@section('title', 'เลือกบริการ')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="servicesBrowser()">
    {{-- Hero Section --}}
    <div class="mb-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent mb-4">
            <i class="fas fa-concierge-bell mr-3"></i>
            บริการของเรา
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
            เลือกบริการที่คุณต้องการ เรามีผู้ให้บริการมืออาชีพพร้อมให้บริการคุณ
        </p>
    </div>

    {{-- Category Filter --}}
    <div class="mb-8">
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <i class="fas fa-filter text-purple-600 dark:text-purple-400 text-xl"></i>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    เลือกตามหมวดหมู่
                </h2>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                {{-- All Categories --}}
                <button @click="selectedCategory = null"
                        :class="selectedCategory === null
                            ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white border-purple-600'
                            : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-purple-400'"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 hover:scale-105 group">
                    <span class="text-2xl">📋</span>
                    <span class="text-sm font-semibold">ทั้งหมด</span>
                    <span class="text-xs opacity-75">({{ $categories->sum('active_services_count') }} บริการ)</span>
                </button>

                {{-- Category Cards --}}
                @foreach($categories as $category)
                <button @click="selectedCategory = {{ $category->id }}"
                        :class="selectedCategory === {{ $category->id }}
                            ? 'border-purple-600 dark:border-purple-400 shadow-lg'
                            : 'border-gray-200 dark:border-gray-600 hover:border-purple-300'"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 bg-white dark:bg-gray-700 transition-all duration-200 hover:scale-105 group relative overflow-hidden">
                    {{-- Gradient Background (when selected) --}}
                    <div class="absolute inset-0 opacity-0 transition-opacity duration-200"
                         :class="selectedCategory === {{ $category->id }} ? 'opacity-10' : ''"
                         style="background: linear-gradient(135deg, {{ $category->color }}CC, {{ $category->color }}FF);"></div>

                    <span class="text-2xl relative z-10">{{ $category->icon ?? '📦' }}</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white relative z-10 text-center">
                        {{ $category->name }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 relative z-10">
                        ({{ $category->active_services_count ?? 0 }} บริการ)
                    </span>
                </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Services Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($services as $service)
        <div x-show="selectedCategory === null || selectedCategory === {{ $service->category_id }}"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             class="backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 group">

            {{-- Service Image --}}
            <div class="relative h-48 overflow-hidden bg-gradient-to-br from-purple-400/20 to-pink-400/20">
                @if($service->image_path)
                    <img src="{{ asset('storage/' . $service->image_path) }}"
                         alt="{{ $service->name }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                @else
                    <div class="w-full h-full flex items-center justify-center text-6xl">
                        {{ $service->category->icon ?? '🔧' }}
                    </div>
                @endif

                {{-- Category Badge --}}
                <div class="absolute top-3 left-3">
                    <span class="inline-flex items-center gap-1 px-3 py-1 backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 rounded-full text-xs font-semibold"
                          style="color: {{ $service->category->color }};">
                        <span>{{ $service->category->icon }}</span>
                        <span>{{ $service->category->name }}</span>
                    </span>
                </div>

                {{-- Active/Inactive Badge --}}
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
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
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
                        {{ $service->estimated_duration_minutes ?? 60 }} นาที
                    </span>
                    @if($service->requires_location)
                        <span class="inline-flex items-center gap-1">
                            <i class="fas fa-map-marker-alt"></i>
                            ต้องระบุตำแหน่ง
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1">
                        <i class="fas fa-eye"></i>
                        {{ $service->view_count ?? 0 }} ครั้ง
                    </span>
                </div>

                {{-- Price --}}
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">เริ่มต้น</p>
                        <p class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-400 dark:to-pink-400 bg-clip-text text-transparent">
                            ฿{{ number_format($service->base_price, 2) }}
                        </p>
                    </div>

                    @if($service->reviews_count > 0)
                        <div class="text-right">
                            <div class="flex items-center gap-1 text-yellow-500">
                                <i class="fas fa-star"></i>
                                <span class="font-semibold">{{ number_format($service->average_rating ?? 0, 1) }}</span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                ({{ $service->reviews_count }} รีวิว)
                            </p>
                        </div>
                    @endif
                </div>

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
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full mb-4">
                    <i class="fas fa-concierge-bell text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">ไม่พบบริการ</h3>
                <p class="text-gray-600 dark:text-gray-400">
                    ขออภัย ยังไม่มีบริการในหมวดหมู่นี้ในขณะนี้
                </p>
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

    {{-- Features Section --}}
    <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full mb-4">
                <i class="fas fa-shield-alt text-2xl text-white"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                ปลอดภัย มั่นใจ
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                ผู้ให้บริการผ่านการตรวจสอบแล้ว พร้อมประกันความเสียหาย
            </p>
        </div>

        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-full mb-4">
                <i class="fas fa-map-marked-alt text-2xl text-white"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                ติดตามแบบเรียลไทม์
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                ดูตำแหน่งผู้ให้บริการแบบเรียลไทม์ พร้อมแจ้งเตือนทุกขั้นตอน
            </p>
        </div>

        <div class="backdrop-blur-xl bg-white/80 dark:bg-gray-800/80 border border-white/20 dark:border-gray-700/50 rounded-2xl shadow-xl p-6 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-500 rounded-full mb-4">
                <i class="fas fa-headset text-2xl text-white"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                สนับสนุน 24/7
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                ทีมงานพร้อมให้ความช่วยเหลือคุณตลอด 24 ชั่วโมง
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function servicesBrowser() {
    return {
        selectedCategory: null,

        init() {
            // ถ้ามี category ใน URL query string ให้เลือกอัตโนมัติ
            const urlParams = new URLSearchParams(window.location.search);
            const categoryId = urlParams.get('category');
            if (categoryId) {
                this.selectedCategory = parseInt(categoryId);
            }
        }
    }
}
</script>
@endpush
