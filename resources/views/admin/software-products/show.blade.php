@extends('layouts.admin')

@section('title', $softwareProduct->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 via-cyan-50 to-blue-50 dark:from-slate-900 dark:via-slate-800 dark:to-blue-900/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.software-products.index') }}"
                       class="p-2 hover:bg-white dark:hover:bg-slate-800 rounded-lg transition-colors">
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 dark:from-cyan-400 dark:via-blue-400 dark:to-indigo-400">
                            {{ $softwareProduct->name }}
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">
                            @if($softwareProduct->category)
                                <span class="text-cyan-600 dark:text-cyan-400 font-medium">{{ $softwareProduct->category->name }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.software-products.edit', $softwareProduct) }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        แก้ไข
                    </a>
                    <form action="{{ route('admin.software-products.destroy', $softwareProduct) }}" method="POST" class="inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบผลิตภัณฑ์นี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            ลบ
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Hero Image --}}
                @if($softwareProduct->main_image_url)
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg overflow-hidden">
                    <img src="{{ $softwareProduct->main_image_url }}"
                         alt="{{ $softwareProduct->name }}"
                         class="w-full h-96 object-cover">
                </div>
                @endif

                {{-- Description --}}
                @if($softwareProduct->description)
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียด</h2>
                    </div>
                    <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                        {!! nl2br(e($softwareProduct->description)) !!}
                    </div>
                </div>
                @endif

                {{-- Features --}}
                @if($softwareProduct->features && count($softwareProduct->features) > 0)
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">คุณสมบัติ</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($softwareProduct->features as $feature)
                        <div class="flex items-start gap-3 p-4 bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-xl">
                            <svg class="w-6 h-6 text-cyan-600 dark:text-cyan-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span class="text-gray-700 dark:text-gray-300">{{ $feature }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Gallery --}}
                @if($softwareProduct->gallery_images && count($softwareProduct->gallery_images) > 0)
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">แกลเลอรี่</h2>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($softwareProduct->gallery_images as $image)
                        <div class="group relative aspect-video bg-gray-100 dark:bg-slate-700 rounded-xl overflow-hidden cursor-pointer hover:shadow-xl transition-shadow"
                             onclick="openImageModal('{{ $image }}')">
                            <img src="{{ $image }}"
                                 alt="Gallery"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center">
                                <svg class="w-12 h-12 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                </svg>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Technical Specs --}}
                @if($softwareProduct->technical_specs && count($softwareProduct->technical_specs) > 0)
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-2 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ข้อมูลทางเทคนิค</h2>
                    </div>
                    <div class="space-y-3">
                        @foreach($softwareProduct->technical_specs as $key => $value)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-xl">
                            <span class="text-gray-600 dark:text-gray-400 font-medium">{{ $key }}</span>
                            <span class="text-gray-900 dark:text-white font-semibold">{{ $value }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Pricing Card --}}
                <div class="bg-gradient-to-br from-cyan-500 via-blue-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white">
                    <div class="text-center">
                        <p class="text-cyan-100 text-sm font-medium mb-2">{{ $softwareProduct->price_label ?? 'เริ่มต้น' }}</p>
                        <div class="text-5xl font-black mb-1">
                            {{ number_format($softwareProduct->base_price, 0) }}
                        </div>
                        <p class="text-cyan-100 text-lg">{{ $softwareProduct->currency }}</p>
                    </div>

                    @if($softwareProduct->demo_url || $softwareProduct->documentation_url)
                    <div class="mt-6 space-y-3">
                        @if($softwareProduct->demo_url)
                        <a href="{{ $softwareProduct->demo_url }}"
                           target="_blank"
                           class="block w-full px-6 py-3 bg-white text-blue-600 font-semibold rounded-xl text-center hover:bg-blue-50 transition-colors">
                            ทดลองใช้งาน
                        </a>
                        @endif
                        @if($softwareProduct->documentation_url)
                        <a href="{{ $softwareProduct->documentation_url }}"
                           target="_blank"
                           class="block w-full px-6 py-3 bg-white/20 backdrop-blur-sm text-white font-semibold rounded-xl text-center hover:bg-white/30 transition-colors">
                            เอกสารประกอบ
                        </a>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Status Card --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">สถานะ</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">เปิดใช้งาน</span>
                            @if($softwareProduct->is_active)
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm font-semibold rounded-full">
                                ใช่
                            </span>
                            @else
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400 text-sm font-semibold rounded-full">
                                ไม่
                            </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">แนะนำ</span>
                            @if($softwareProduct->is_featured)
                            <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-sm font-semibold rounded-full">
                                ใช่
                            </span>
                            @else
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400 text-sm font-semibold rounded-full">
                                ไม่
                            </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">กำหนดเอง</span>
                            @if($softwareProduct->is_customizable)
                            <span class="px-3 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 text-sm font-semibold rounded-full">
                                ใช่
                            </span>
                            @else
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400 text-sm font-semibold rounded-full">
                                ไม่
                            </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ต้องปรึกษา</span>
                            @if($softwareProduct->requires_consultation)
                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-sm font-semibold rounded-full">
                                ใช่
                            </span>
                            @else
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400 text-sm font-semibold rounded-full">
                                ไม่
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Meta Info --}}
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลเพิ่มเติม</h3>
                    <div class="space-y-3 text-sm">
                        @if($softwareProduct->estimated_delivery_days)
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>ส่งมอบภายใน {{ $softwareProduct->estimated_delivery_days }} วัน</span>
                        </div>
                        @endif

                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Slug: {{ $softwareProduct->slug }}</span>
                        </div>

                        @if($softwareProduct->sort_order)
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                            <span>ลำดับ: {{ $softwareProduct->sort_order }}</span>
                        </div>
                        @endif

                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>สร้าง: {{ $softwareProduct->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Tags --}}
                @if($softwareProduct->tags && count($softwareProduct->tags) > 0)
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">แท็ก</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($softwareProduct->tags as $tag)
                        <span class="px-3 py-1 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 text-sm rounded-full">
                            {{ $tag }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Image Modal --}}
<div id="imageModal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center p-4" onclick="closeImageModal()">
    <div class="max-w-6xl max-h-full">
        <img id="modalImage" src="" alt="Preview" class="max-w-full max-h-[90vh] object-contain rounded-2xl shadow-2xl">
    </div>
    <button onclick="closeImageModal()" class="absolute top-4 right-4 p-2 bg-white/20 hover:bg-white/30 rounded-full transition-colors">
        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
</div>

@push('scripts')
<script>
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}
</script>
@endpush
@endsection
