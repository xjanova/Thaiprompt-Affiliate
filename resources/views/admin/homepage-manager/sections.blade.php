@extends('layouts.admin-v3')

@section('title', $pageTitle ?? 'จัดการ Sections หน้าแรก')

@section('styles')
<style>
    /* ซ่อน scrollbar แต่ยังเลื่อนได้ */
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* Sortable styles */
    .sortable-ghost { opacity: 0.4; background: #e5e7eb; }
    .sortable-chosen { border-color: #6366f1 !important; box-shadow: 0 8px 24px rgba(99, 102, 241, 0.2); }
    .sortable-drag { opacity: 1 !important; }

    /* Card hover effects */
    .section-card {
        transition: all 0.3s ease;
    }
    .section-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.1);
    }

    /* Toast notification */
    .toast-container {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .toast {
        padding: 12px 20px;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        animation: fadeInRight 0.3s ease-out;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    @keyframes fadeInRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
    .toast.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
    .toast.error { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }
    .toast.info { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; }
    .toast.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }

    /* Status badge */
    .status-active {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    .status-inactive {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    }

    /* Section type colors */
    .type-hero { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
    .type-features { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .type-cta { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .type-content { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    .type-gallery { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); }
    .type-testimonials { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
    .type-pricing { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); }
    .type-faq { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
    .type-contact { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); }
    .type-custom { background: linear-gradient(135deg, #64748b 0%, #475569 100%); }
</style>
@endsection

@section('content')
<div x-data="sectionsManager()" x-init="init()" class="min-h-screen bg-gray-100 dark:bg-gray-900">

    {{-- Toast Container --}}
    <div class="toast-container" x-show="toasts.length > 0">
        <template x-for="(toast, index) in toasts" :key="index">
            <div class="toast" :class="toast.type" x-show="toast.show" x-transition>
                <i class="fas" :class="{
                    'fa-check-circle': toast.type === 'success',
                    'fa-exclamation-circle': toast.type === 'error',
                    'fa-info-circle': toast.type === 'info',
                    'fa-exclamation-triangle': toast.type === 'warning'
                }"></i>
                <span x-text="toast.message"></span>
            </div>
        </template>
    </div>

    {{-- Header --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-4 shadow-lg">
                        <i class="fas fa-layer-group text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">จัดการ Sections หน้าแรก</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">จัดการ sections ทั้งหมดของหน้าแรก</p>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    {{-- Search --}}
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="filterSections()" placeholder="ค้นหา section..." class="pl-10 pr-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition w-64">
                    </div>

                    {{-- Filter by Type --}}
                    <select x-model="filterType" @change="filterSections()" class="px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500">
                        <option value="">ทุกประเภท</option>
                        @foreach($sectionTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    {{-- Filter by Status --}}
                    <select x-model="filterStatus" @change="filterSections()" class="px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500">
                        <option value="">ทุกสถานะ</option>
                        <option value="active">เปิดใช้งาน</option>
                        <option value="inactive">ปิดใช้งาน</option>
                    </select>

                    {{-- Add Section Button --}}
                    <button @click="showAddModal = true" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-500/25 flex items-center font-medium">
                        <i class="fas fa-plus mr-2"></i> เพิ่ม Section
                    </button>

                    {{-- Visual Editor Link --}}
                    <a href="{{ route('admin.homepage-manager.index') }}" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition flex items-center font-medium">
                        <i class="fas fa-magic mr-2"></i> Visual Editor
                    </a>
                </div>
            </div>

            {{-- Stats Bar --}}
            <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">ทั้งหมด</span>
                        <i class="fas fa-layer-group text-indigo-500"></i>
                    </div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" x-text="stats.total"></div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">เปิดใช้งาน</span>
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1" x-text="stats.active"></div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">ปิดใช้งาน</span>
                        <i class="fas fa-times-circle text-gray-500"></i>
                    </div>
                    <div class="text-2xl font-bold text-gray-600 dark:text-gray-400 mt-1" x-text="stats.inactive"></div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Elements ทั้งหมด</span>
                        <i class="fas fa-cubes text-purple-500"></i>
                    </div>
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1" x-text="stats.elements"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Loading State --}}
        <div x-show="loading" class="flex flex-col items-center justify-center py-20">
            <div class="w-16 h-16 rounded-full border-4 border-indigo-200 border-t-indigo-600 animate-spin"></div>
            <p class="mt-4 text-gray-500 dark:text-gray-400">กำลังโหลดข้อมูล...</p>
        </div>

        {{-- Empty State --}}
        <div x-show="!loading && filteredSections.length === 0" class="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-800 rounded-2xl shadow-sm">
            <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 flex items-center justify-center mb-6">
                <i class="fas fa-layer-group text-5xl text-indigo-500"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">ไม่พบ Section</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6" x-text="searchQuery || filterType || filterStatus ? 'ลองปรับเงื่อนไขการค้นหา' : 'เริ่มต้นสร้าง section แรกของคุณ'"></p>
            <button @click="showAddModal = true" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-lg shadow-indigo-500/25">
                <i class="fas fa-plus mr-2"></i> เพิ่ม Section ใหม่
            </button>
        </div>

        {{-- Sections List --}}
        <div x-show="!loading && filteredSections.length > 0" id="sections-list" class="space-y-4">
            <template x-for="(section, index) in filteredSections" :key="section.id">
                <div class="section-card bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden" :data-id="section.id">
                    <div class="flex flex-col lg:flex-row">
                        {{-- Drag Handle & Order --}}
                        <div class="flex lg:flex-col items-center justify-center p-4 bg-gray-50 dark:bg-gray-700/50 border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-gray-700 cursor-grab" title="ลากเพื่อจัดเรียง">
                            <i class="fas fa-grip-vertical text-gray-400 text-lg"></i>
                            <span class="ml-2 lg:ml-0 lg:mt-2 text-sm font-bold text-gray-500 dark:text-gray-400" x-text="'#' + (index + 1)"></span>
                        </div>

                        {{-- Section Info --}}
                        <div class="flex-1 p-5">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                {{-- Left: Section Details --}}
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        {{-- Type Badge --}}
                                        <span class="px-3 py-1 text-xs font-bold text-white rounded-lg" :class="'type-' + section.type" x-text="section.type_label || section.type"></span>
                                        {{-- Status Badge --}}
                                        <span class="px-3 py-1 text-xs font-bold text-white rounded-lg" :class="section.is_active ? 'status-active' : 'status-inactive'" x-text="section.is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'"></span>
                                        {{-- Full Width Badge --}}
                                        <span x-show="section.is_fullwidth" class="px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg">
                                            <i class="fas fa-arrows-alt-h mr-1"></i> Full Width
                                        </span>
                                    </div>

                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2" x-text="section.name"></h3>

                                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="flex items-center">
                                            <i class="fas fa-cubes mr-1.5 text-purple-500"></i>
                                            <span x-text="(section.elements?.length || 0) + ' elements'"></span>
                                        </span>
                                        <span x-show="section.min_height" class="flex items-center">
                                            <i class="fas fa-arrows-alt-v mr-1.5 text-blue-500"></i>
                                            <span x-text="'Min: ' + section.min_height"></span>
                                        </span>
                                        <span x-show="section.background?.type" class="flex items-center">
                                            <i class="fas fa-fill-drip mr-1.5 text-pink-500"></i>
                                            <span x-text="'BG: ' + section.background.type"></span>
                                        </span>
                                        <span x-show="section.animation?.type && section.animation.type !== 'none'" class="flex items-center">
                                            <i class="fas fa-magic mr-1.5 text-yellow-500"></i>
                                            <span x-text="section.animation.type"></span>
                                        </span>
                                    </div>
                                </div>

                                {{-- Right: Actions --}}
                                <div class="flex items-center gap-2">
                                    {{-- Toggle Status --}}
                                    <button @click="toggleSection(section)" :class="section.is_active ? 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-700 text-gray-500'" class="p-3 rounded-xl hover:scale-105 transition" :title="section.is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน'">
                                        <i class="fas" :class="section.is_active ? 'fa-eye' : 'fa-eye-slash'"></i>
                                    </button>

                                    {{-- Edit --}}
                                    <button @click="editSection(section)" class="p-3 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl hover:scale-105 transition" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    {{-- Duplicate --}}
                                    <button @click="duplicateSection(section)" class="p-3 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-xl hover:scale-105 transition" title="ทำสำเนา">
                                        <i class="fas fa-copy"></i>
                                    </button>

                                    {{-- Visual Editor --}}
                                    <a :href="'{{ route('admin.homepage-manager.index') }}?section=' + section.id" class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl hover:scale-105 transition" title="เปิดใน Visual Editor">
                                        <i class="fas fa-magic"></i>
                                    </a>

                                    {{-- Delete --}}
                                    <button @click="confirmDelete(section)" class="p-3 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-xl hover:scale-105 transition" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            {{-- Elements Preview --}}
                            <div x-show="section.elements && section.elements.length > 0" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex items-center mb-2">
                                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Elements ใน Section:</span>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="element in section.elements.slice(0, 8)" :key="element.id">
                                        <span class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg flex items-center">
                                            <i class="fas mr-1.5 text-purple-500" :class="getElementIcon(element.type)"></i>
                                            <span x-text="element.name || element.type_label || element.type"></span>
                                        </span>
                                    </template>
                                    <span x-show="section.elements.length > 8" class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-lg">
                                        +<span x-text="section.elements.length - 8"></span> อื่นๆ
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Add/Edit Section Modal --}}
    <div x-show="showAddModal || showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeModal()">
        <div class="min-h-screen px-4 text-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>

            <span class="inline-block h-screen align-middle">&#8203;</span>

            <div class="inline-block w-full max-w-2xl p-6 my-8 text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-2xl rounded-2xl" @click.stop>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center mr-3">
                            <i class="fas" :class="showEditModal ? 'fa-edit' : 'fa-plus'" class="text-white"></i>
                        </div>
                        <span x-text="showEditModal ? 'แก้ไข Section' : 'เพิ่ม Section ใหม่'"></span>
                    </h3>
                    <button @click="closeModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form @submit.prevent="saveSection()">
                    <div class="space-y-5">
                        {{-- Section Name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ Section <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.name" required class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition" placeholder="เช่น Hero Section, Features, etc.">
                        </div>

                        {{-- Section Type --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภท Section <span class="text-red-500">*</span></label>
                            <select x-model="form.type" required class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                @foreach($sectionTypes as $type => $label)
                                    <option value="{{ $type }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Two Columns --}}
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Min Height --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Min Height</label>
                                <input type="text" x-model="form.min_height" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500" placeholder="เช่น 400px, 100vh">
                            </div>

                            {{-- Padding --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Padding</label>
                                <input type="text" x-model="form.padding" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500" placeholder="เช่น 60px 20px">
                            </div>
                        </div>

                        {{-- Toggles --}}
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Full Width --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Full Width</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="form.is_fullwidth" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-indigo-500 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>

                            {{-- Active Status --}}
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้งาน</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="form.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-indigo-500 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                </label>
                            </div>
                        </div>

                        {{-- Background Type --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Background Type</label>
                            <div class="grid grid-cols-4 gap-2">
                                <button type="button" @click="form.background_type = 'color'" :class="form.background_type === 'color' ? 'bg-indigo-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'" class="p-3 rounded-xl font-medium transition">Color</button>
                                <button type="button" @click="form.background_type = 'gradient'" :class="form.background_type === 'gradient' ? 'bg-indigo-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'" class="p-3 rounded-xl font-medium transition">Gradient</button>
                                <button type="button" @click="form.background_type = 'image'" :class="form.background_type === 'image' ? 'bg-indigo-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'" class="p-3 rounded-xl font-medium transition">Image</button>
                                <button type="button" @click="form.background_type = 'video'" :class="form.background_type === 'video' ? 'bg-indigo-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'" class="p-3 rounded-xl font-medium transition">Video</button>
                            </div>
                        </div>

                        {{-- Background Color --}}
                        <div x-show="form.background_type === 'color'" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สี Light Mode</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="form.background_color" class="w-12 h-12 rounded-lg cursor-pointer">
                                    <input type="text" x-model="form.background_color" class="flex-1 px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700" placeholder="#ffffff">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สี Dark Mode</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="form.background_color_dark" class="w-12 h-12 rounded-lg cursor-pointer">
                                    <input type="text" x-model="form.background_color_dark" class="flex-1 px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700" placeholder="#1f2937">
                                </div>
                            </div>
                        </div>

                        {{-- Background Gradient --}}
                        <div x-show="form.background_type === 'gradient'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gradient CSS</label>
                            <textarea x-model="form.background_gradient" rows="2" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 font-mono text-sm" placeholder="linear-gradient(135deg, #667eea 0%, #764ba2 100%)"></textarea>
                        </div>

                        {{-- Background Image --}}
                        <div x-show="form.background_type === 'image'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Image URL</label>
                            <input type="text" x-model="form.background_image" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700" placeholder="https://example.com/image.jpg">
                        </div>

                        {{-- Animation --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Animation</label>
                            <select x-model="form.animation" class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 focus:ring-2 focus:ring-indigo-500">
                                @foreach($animations as $anim => $label)
                                    <option value="{{ $anim }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" @click="closeModal()" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium">
                            ยกเลิก
                        </button>
                        <button type="submit" :disabled="saving" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition font-medium disabled:opacity-50 flex items-center">
                            <i class="fas" :class="saving ? 'fa-spinner fa-spin' : 'fa-save'" class="mr-2"></i>
                            <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึก'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showDeleteModal = false">
        <div class="min-h-screen px-4 text-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="showDeleteModal = false"></div>

            <span class="inline-block h-screen align-middle">&#8203;</span>

            <div class="inline-block w-full max-w-md p-6 my-8 text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-2xl rounded-2xl" @click.stop>
                <div class="text-center">
                    <div class="w-16 h-16 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">ยืนยันการลบ</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-2">คุณต้องการลบ Section นี้หรือไม่?</p>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg px-4 py-2 mb-4" x-text="sectionToDelete?.name"></p>
                    <p class="text-sm text-red-500">การดำเนินการนี้ไม่สามารถย้อนกลับได้</p>
                </div>

                <div class="flex items-center justify-center gap-3 mt-6">
                    <button @click="showDeleteModal = false" class="px-6 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium">
                        ยกเลิก
                    </button>
                    <button @click="deleteSection()" :disabled="deleting" class="px-6 py-3 bg-red-500 text-white rounded-xl hover:bg-red-600 transition font-medium disabled:opacity-50 flex items-center">
                        <i class="fas" :class="deleting ? 'fa-spinner fa-spin' : 'fa-trash'" class="mr-2"></i>
                        <span x-text="deleting ? 'กำลังลบ...' : 'ลบ Section'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function sectionsManager() {
    return {
        // Data
        sections: [],
        filteredSections: [],
        loading: true,
        saving: false,
        deleting: false,

        // Filters
        searchQuery: '',
        filterType: '',
        filterStatus: '',

        // Stats
        stats: {
            total: 0,
            active: 0,
            inactive: 0,
            elements: 0
        },

        // Modals
        showAddModal: false,
        showEditModal: false,
        showDeleteModal: false,
        sectionToDelete: null,

        // Toast
        toasts: [],

        // Form
        form: {
            id: null,
            name: '',
            type: 'hero',
            min_height: '',
            padding: '60px 20px',
            is_fullwidth: false,
            is_active: true,
            background_type: 'color',
            background_color: '#ffffff',
            background_color_dark: '#1f2937',
            background_gradient: '',
            background_image: '',
            animation: 'none'
        },

        // Initialize
        async init() {
            await this.loadSections();
            this.initSortable();
        },

        // Load sections from API
        async loadSections() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('admin.homepage-manager.sections.data') }}');
                const data = await response.json();

                if (data.success) {
                    this.sections = data.data;
                    this.filterSections();
                    this.updateStats();
                } else {
                    this.showToast('error', data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error loading sections:', error);
                this.showToast('error', 'ไม่สามารถโหลดข้อมูลได้');
            } finally {
                this.loading = false;
            }
        },

        // Filter sections
        filterSections() {
            let result = [...this.sections];

            if (this.searchQuery) {
                const query = this.searchQuery.toLowerCase();
                result = result.filter(s =>
                    s.name.toLowerCase().includes(query) ||
                    s.type.toLowerCase().includes(query)
                );
            }

            if (this.filterType) {
                result = result.filter(s => s.type === this.filterType);
            }

            if (this.filterStatus) {
                const isActive = this.filterStatus === 'active';
                result = result.filter(s => s.is_active === isActive);
            }

            this.filteredSections = result;
        },

        // Update stats
        updateStats() {
            this.stats.total = this.sections.length;
            this.stats.active = this.sections.filter(s => s.is_active).length;
            this.stats.inactive = this.sections.filter(s => !s.is_active).length;
            this.stats.elements = this.sections.reduce((sum, s) => sum + (s.elements?.length || 0), 0);
        },

        // Initialize Sortable
        initSortable() {
            this.$nextTick(() => {
                const container = document.getElementById('sections-list');
                if (container) {
                    new Sortable(container, {
                        handle: '.cursor-grab',
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        dragClass: 'sortable-drag',
                        onEnd: async (evt) => {
                            const sectionIds = [...container.querySelectorAll('[data-id]')].map(el => parseInt(el.dataset.id));
                            await this.reorderSections(sectionIds);
                        }
                    });
                }
            });
        },

        // Reorder sections
        async reorderSections(sectionIds) {
            try {
                const response = await fetch('{{ route('admin.homepage-manager.sections.reorder') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ sections: sectionIds })
                });

                const data = await response.json();
                if (data.success) {
                    this.showToast('success', 'จัดเรียงลำดับสำเร็จ');
                    await this.loadSections();
                } else {
                    this.showToast('error', data.message);
                }
            } catch (error) {
                this.showToast('error', 'เกิดข้อผิดพลาด');
            }
        },

        // Edit section
        editSection(section) {
            this.form = {
                id: section.id,
                name: section.name,
                type: section.type,
                min_height: section.min_height || '',
                padding: section.padding || '60px 20px',
                is_fullwidth: section.is_fullwidth || false,
                is_active: section.is_active,
                background_type: section.background?.type || 'color',
                background_color: section.background?.color || '#ffffff',
                background_color_dark: section.background?.color_dark || '#1f2937',
                background_gradient: section.background?.gradient || '',
                background_image: section.background?.image || '',
                animation: section.animation?.type || 'none'
            };
            this.showEditModal = true;
        },

        // Save section
        async saveSection() {
            this.saving = true;
            try {
                const url = this.form.id
                    ? `{{ url('admin/homepage-manager/sections') }}/${this.form.id}`
                    : '{{ route('admin.homepage-manager.sections.store') }}';
                const method = this.form.id ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: this.form.name,
                        type: this.form.type,
                        min_height: this.form.min_height,
                        padding: this.form.padding,
                        is_fullwidth: this.form.is_fullwidth,
                        is_active: this.form.is_active,
                        background_type: this.form.background_type,
                        background_color: this.form.background_color,
                        background_color_dark: this.form.background_color_dark,
                        background_gradient: this.form.background_gradient,
                        background_image: this.form.background_image,
                        animation: this.form.animation
                    })
                });

                const data = await response.json();
                if (data.success) {
                    this.showToast('success', data.message);
                    this.closeModal();
                    await this.loadSections();
                } else {
                    this.showToast('error', data.message);
                }
            } catch (error) {
                this.showToast('error', 'เกิดข้อผิดพลาด');
            } finally {
                this.saving = false;
            }
        },

        // Toggle section
        async toggleSection(section) {
            try {
                const response = await fetch(`{{ url('admin/homepage-manager/sections') }}/${section.id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    section.is_active = data.is_active;
                    this.updateStats();
                    this.showToast('success', data.message);
                } else {
                    this.showToast('error', data.message);
                }
            } catch (error) {
                this.showToast('error', 'เกิดข้อผิดพลาด');
            }
        },

        // Duplicate section
        async duplicateSection(section) {
            try {
                const response = await fetch(`{{ url('admin/homepage-manager/sections') }}/${section.id}/duplicate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.showToast('success', data.message);
                    await this.loadSections();
                } else {
                    this.showToast('error', data.message);
                }
            } catch (error) {
                this.showToast('error', 'เกิดข้อผิดพลาด');
            }
        },

        // Confirm delete
        confirmDelete(section) {
            this.sectionToDelete = section;
            this.showDeleteModal = true;
        },

        // Delete section
        async deleteSection() {
            if (!this.sectionToDelete) return;

            this.deleting = true;
            try {
                const response = await fetch(`{{ url('admin/homepage-manager/sections') }}/${this.sectionToDelete.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.showToast('success', data.message);
                    this.showDeleteModal = false;
                    this.sectionToDelete = null;
                    await this.loadSections();
                } else {
                    this.showToast('error', data.message);
                }
            } catch (error) {
                this.showToast('error', 'เกิดข้อผิดพลาด');
            } finally {
                this.deleting = false;
            }
        },

        // Close modal
        closeModal() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.resetForm();
        },

        // Reset form
        resetForm() {
            this.form = {
                id: null,
                name: '',
                type: 'hero',
                min_height: '',
                padding: '60px 20px',
                is_fullwidth: false,
                is_active: true,
                background_type: 'color',
                background_color: '#ffffff',
                background_color_dark: '#1f2937',
                background_gradient: '',
                background_image: '',
                animation: 'none'
            };
        },

        // Get element icon
        getElementIcon(type) {
            const icons = {
                'heading': 'fa-heading',
                'text': 'fa-paragraph',
                'image': 'fa-image',
                'button': 'fa-square',
                'icon': 'fa-icons',
                'video': 'fa-video',
                'html': 'fa-code',
                'spacer': 'fa-arrows-alt-v',
                'divider': 'fa-minus',
                'form': 'fa-wpforms',
                'slider': 'fa-images',
                'countdown': 'fa-clock',
                'social': 'fa-share-alt',
                'map': 'fa-map-marker-alt'
            };
            return icons[type] || 'fa-cube';
        },

        // Show toast
        showToast(type, message) {
            const toast = { type, message, show: true };
            this.toasts.push(toast);
            setTimeout(() => {
                toast.show = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t !== toast);
                }, 300);
            }, 3000);
        }
    };
}
</script>
@endsection
