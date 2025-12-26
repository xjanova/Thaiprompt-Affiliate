@extends('layouts.admin')

@section('title', 'จัดการ Label Templates - POS')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                🎨 จัดการ Label Templates
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                ออกแบบและจัดการ templates สำหรับพิมพ์ฉลาก
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.pos.labels.index') }}"
               class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>
                กลับ
            </a>
            <a href="{{ route('admin.pos.labels.templates.create') }}"
               class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition shadow-lg">
                <i class="fas fa-plus mr-2"></i>
                สร้าง Template ใหม่
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Templates ทั้งหมด</p>
                    <p class="text-3xl font-bold mt-1">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="fas fa-layer-group text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">ฉลากสินค้า</p>
                    <p class="text-3xl font-bold mt-1">{{ $stats['product_labels'] }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="fas fa-tag text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">ใบปะสินค้า</p>
                    <p class="text-3xl font-bold mt-1">{{ $stats['shipping_labels'] }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="fas fa-box text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-lg p-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Templates ของฉัน</p>
                    <p class="text-3xl font-bold mt-1">{{ $stats['my_templates'] }}</p>
                </div>
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="fas fa-user text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Templates Grid --}}
    @if($templates->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($templates as $template)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition group">
                    {{-- Template Preview/Thumbnail --}}
                    <div class="h-48 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 p-4 flex items-center justify-center relative">
                        @if($template->thumbnail_path)
                            <img src="{{ asset($template->thumbnail_path) }}"
                                 alt="{{ $template->name }}"
                                 class="max-h-full max-w-full object-contain">
                        @else
                            <div class="text-center">
                                <i class="fas fa-file-alt text-6xl text-gray-400 dark:text-gray-500 mb-2"></i>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $template->paper_width }} x {{ $template->paper_height }} {{ $template->paper_unit }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ $template->columns }}x{{ $template->rows }} ฉลาก
                                </p>
                            </div>
                        @endif

                        {{-- Badges --}}
                        <div class="absolute top-2 right-2 flex gap-2">
                            @if($template->is_system)
                                <span class="px-2 py-1 bg-blue-500 text-white text-xs font-semibold rounded">
                                    System
                                </span>
                            @endif
                            @if(!$template->is_active)
                                <span class="px-2 py-1 bg-gray-500 text-white text-xs font-semibold rounded">
                                    ปิดใช้งาน
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Template Info --}}
                    <div class="p-4">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1 truncate">
                            {{ $template->name }}
                        </h3>

                        @if($template->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                {{ $template->description }}
                            </p>
                        @endif

                        {{-- Template Details --}}
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-layer-group w-5 text-gray-500"></i>
                                <span>{{ $template->pos_category_name }}</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-print w-5 text-gray-500"></i>
                                <span>{{ $template->printer_type_name }}</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-chart-line w-5 text-gray-500"></i>
                                <span>ใช้งาน {{ number_format($template->usage_count) }} ครั้ง</span>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex gap-2">
                            <a href="{{ route('admin.pos.labels.templates.designer', $template) }}"
                               class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition text-center">
                                <i class="fas fa-paint-brush mr-1"></i>
                                Designer
                            </a>

                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open"
                                        class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>

                                <div x-show="open"
                                     @click.away="open = false"
                                     class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-10 border border-gray-200 dark:border-gray-700">
                                    <a href="{{ route('admin.pos.labels.templates.edit', $template) }}"
                                       class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        <i class="fas fa-edit mr-2"></i> แก้ไข
                                    </a>

                                    <form action="{{ route('admin.pos.labels.templates.duplicate', $template) }}"
                                          method="POST"
                                          class="block">
                                        @csrf
                                        <button type="submit"
                                                class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                            <i class="fas fa-copy mr-2"></i> ทำสำเนา
                                        </button>
                                    </form>

                                    @if(!$template->is_system)
                                        <hr class="my-1 border-gray-200 dark:border-gray-700">
                                        <form action="{{ route('admin.pos.labels.templates.destroy', $template) }}"
                                              method="POST"
                                              onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบ template นี้?')"
                                              class="block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                                <i class="fas fa-trash mr-2"></i> ลบ
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $templates->links() }}
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-12 text-center">
            <i class="fas fa-layer-group text-6xl text-gray-400 dark:text-gray-500 mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                ยังไม่มี Template
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                เริ่มสร้าง template แรกของคุณเพื่อออกแบบฉลากสินค้า
            </p>
            <a href="{{ route('admin.pos.labels.templates.create') }}"
               class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                <i class="fas fa-plus mr-2"></i>
                สร้าง Template ใหม่
            </a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Alpine.js สำหรับ dropdown menu
</script>
@endpush
