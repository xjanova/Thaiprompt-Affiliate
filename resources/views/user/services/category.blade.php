{{--
    หน้าบริการตามหมวดหมู่
    แสดงรายการบริการในหมวดหมู่ที่เลือก พร้อมฟิลเตอร์และการเรียงลำดับ
    ธีม: Arrow X V3 - Glassmorphism
--}}
@extends('layouts.user-v4')

@section('title', $category->name . ' - บริการ')

@section('content')
<div x-data="categoryPage()">
    {{-- Premium Hero Header (Cyan-Blue for Service Category) --}}
    <div class="relative overflow-hidden bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 dark:from-cyan-800 dark:via-blue-800 dark:to-indigo-800 rounded-2xl shadow-2xl p-8 mb-8">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl animate-pulse" style="animation-delay: 0.5s"></div>
        </div>
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute text-white/10 text-8xl top-10 right-20" style="animation: float 6s ease-in-out infinite">
                <i class="{{ $category->icon ?? 'fas fa-concierge-bell' }}"></i>
            </div>
        </div>

        <div class="relative z-10">
            <nav class="mb-6 flex items-center gap-2 text-sm text-white/80">
                <a href="{{ route('user.services.index') }}" class="hover:text-white transition-colors">
                    <i class="fas fa-home mr-1"></i>บริการทั้งหมด
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-white font-semibold">{{ $category->name }}</span>
            </nav>

            <div class="text-center">
                <div class="glass-fusion w-24 h-24 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="{{ $category->icon ?? 'fas fa-concierge-bell' }} text-6xl text-white drop-shadow-lg"></i>
                </div>
                <h1 class="text-3xl md:text-5xl font-bold text-white drop-shadow-lg mb-2">
                    {{ $category->name }}
                </h1>
                @if($category->description)
                    <p class="text-cyan-100 mt-4 max-w-2xl mx-auto text-base md:text-lg px-4">
                        {{ $category->description }}
                    </p>
                @endif
                <p class="mt-4">
                    <span class="glass-fusion inline-flex items-center gap-2 px-4 py-2 text-white rounded-full">
                        <i class="fas fa-concierge-bell"></i>
                        {{ $services->total() }} บริการ
                    </span>
                </p>
            </div>
        </div>
    </div>

    {{-- Filter & Sort --}}
    <div class="mb-6 glass-fusion rounded-xl p-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            {{-- Search --}}
            <div class="relative flex-1 max-w-md">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-white/40"></i>
                <input type="text"
                       x-model="searchQuery"
                       @input.debounce.300ms="filterServices()"
                       placeholder="ค้นหาบริการ..."
                       class="w-full pl-12 pr-4 py-3 bg-white/10 backdrop-blur-lg border border-white/20 rounded-xl focus:ring-2 focus:ring-purple-500 text-white placeholder-white/40">
            </div>

            {{-- Sort Options --}}
            <div class="flex items-center gap-3">
                <span class="text-sm text-white/60">เรียงตาม:</span>
                <select x-model="sortBy"
                        @change="filterServices()"
                        class="px-4 py-2 bg-white/10 backdrop-blur-lg border border-white/20 rounded-lg focus:ring-2 focus:ring-purple-500 text-white text-sm">
                    <option value="name" class="text-gray-900">ชื่อ A-Z</option>
                    <option value="price_asc" class="text-gray-900">ราคาต่ำ - สูง</option>
                    <option value="price_desc" class="text-gray-900">ราคาสูง - ต่ำ</option>
                    <option value="rating" class="text-gray-900">คะแนนสูงสุด</option>
                    <option value="popular" class="text-gray-900">ยอดนิยม</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Services Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($services as $service)
        <div class="glass-fusion rounded-2xl overflow-hidden hover:scale-[1.02] transition-all duration-300 group"
             x-show="matchesSearch('{{ addslashes($service->name) }}')"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">

            {{-- Service Image --}}
            <div class="relative h-48 overflow-hidden"
                 style="background: linear-gradient(135deg, {{ $category->color ?? '#8B5CF6' }}20, {{ $category->color ?? '#8B5CF6' }}40);">
                @if($service->image_path)
                    <img src="{{ asset('storage/' . $service->image_path) }}"
                         alt="{{ $service->name }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                @else
                    <div class="w-full h-full flex items-center justify-center text-6xl text-white/30">
                        <i class="{{ $category->icon ?? 'fas fa-concierge-bell' }}"></i>
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
                <h3 class="text-xl font-bold text-white mb-2 group-hover:text-purple-400 transition-colors line-clamp-1">
                    {{ $service->name }}
                </h3>

                @if($service->description)
                    <p class="text-sm text-white/60 mb-4 line-clamp-2">
                        {{ $service->description }}
                    </p>
                @endif

                {{-- Service Meta --}}
                <div class="flex flex-wrap items-center gap-3 text-xs text-white/50 mb-4">
                    <span class="inline-flex items-center gap-1">
                        <i class="fas fa-clock"></i>
                        {{ $service->duration_minutes ?? 60 }} นาที
                    </span>
                    @if($service->requires_location)
                        <span class="inline-flex items-center gap-1 text-blue-400">
                            <i class="fas fa-map-marker-alt"></i>
                            ออกให้บริการ
                        </span>
                    @endif
                </div>

                {{-- Rating & Reviews --}}
                @if(($service->reviews_count ?? 0) > 0)
                    <div class="flex items-center gap-2 mb-4">
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star text-sm {{ $i <= round($service->average_rating ?? 0) ? 'text-yellow-400' : 'text-white/20' }}"></i>
                            @endfor
                        </div>
                        <span class="text-sm font-semibold text-white">
                            {{ number_format($service->average_rating ?? 0, 1) }}
                        </span>
                        <span class="text-xs text-white/50">
                            ({{ $service->reviews_count }} รีวิว)
                        </span>
                    </div>
                @endif

                {{-- Price --}}
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-xs text-white/50">เริ่มต้น</p>
                        <p class="text-2xl font-bold text-purple-400">
                            ฿{{ number_format($service->base_price, 2) }}
                        </p>
                    </div>

                    {{-- PV Badge --}}
                    @if(($service->pv_value ?? 0) > 0)
                        <div class="text-right">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-500/30 text-purple-300 rounded-lg text-xs font-semibold">
                                <i class="fas fa-star"></i>
                                {{ number_format($service->pv_value) }} PV
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Cashback Info --}}
                @if(($service->cashback_percentage ?? 0) > 0)
                    <div class="mb-4 p-2 bg-green-500/20 border border-green-500/30 rounded-lg">
                        <div class="flex items-center gap-2 text-sm text-green-400">
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
                       class="px-4 py-3 bg-white/10 backdrop-blur-lg text-white border border-white/20 rounded-xl hover:bg-white/20 transition-all duration-200"
                       title="ดูรายละเอียด">
                        <i class="fas fa-info-circle"></i>
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full">
            <div class="glass-fusion rounded-2xl p-12 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-4"
                     style="background: {{ $category->color ?? '#8B5CF6' }}20;">
                    <i class="{{ $category->icon ?? 'fas fa-concierge-bell' }} text-4xl text-white/50"></i>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">ไม่พบบริการ</h3>
                <p class="text-white/60 mb-4">
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
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-th-large mr-2 text-purple-400"></i>
                หมวดหมู่อื่นที่น่าสนใจ
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($relatedCategories as $relatedCategory)
                    <a href="{{ route('user.services.category', $relatedCategory) }}"
                       class="flex flex-col items-center gap-2 p-4 rounded-xl glass-fusion transition-all duration-200 hover:scale-105 group">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl text-white group-hover:scale-110 transition-transform"
                             style="background: {{ $relatedCategory->color ?? '#8B5CF6' }};">
                            <i class="{{ $relatedCategory->icon ?? 'fas fa-concierge-bell' }}"></i>
                        </div>
                        <span class="text-sm font-semibold text-white text-center group-hover:text-purple-400">
                            {{ $relatedCategory->name }}
                        </span>
                        <span class="text-xs text-white/50">
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
            // Client-side filtering พร้อมใช้งาน
            // หากต้องการ server-side ให้ใช้ form submit ไป URL พร้อม parameters
        }
    }
}
</script>
@endpush

@push('styles')
<style>
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
