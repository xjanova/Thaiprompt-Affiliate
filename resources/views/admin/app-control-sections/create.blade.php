@extends('layouts.admin-v3')

@section('title', 'สร้างเซคชั่นใหม่')

@section('content')
<div x-data="{ activeTab: 'basic' }" class="container-fluid px-4 py-6">

    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-600 to-indigo-600 dark:from-cyan-600 dark:via-blue-700 dark:to-indigo-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">สร้างเซคชั่นใหม่</h1>
                    <p class="text-cyan-100 dark:text-blue-200">ออกแบบเซคชั่น UI สำหรับแอพพลิเคชั่น</p>
                </div>
            </div>
            <a href="{{ route('admin.app-control-sections.index') }}" class="px-6 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold border border-white/30">
                <i class="fas fa-arrow-left mr-2"></i>กลับ
            </a>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden mb-6">
        <div class="border-b border-gray-200 dark:border-slate-700">
            <nav class="flex overflow-x-auto -mb-px">
                <button @click="activeTab = 'basic'"
                        :class="{ 'border-cyan-500 text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/20': activeTab === 'basic', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== 'basic' }"
                        class="px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 whitespace-nowrap">
                    <i class="fas fa-info-circle mr-2"></i>ข้อมูลพื้นฐาน
                </button>
                <button @click="activeTab = 'style'"
                        :class="{ 'border-purple-500 text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20': activeTab === 'style', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== 'style' }"
                        class="px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 whitespace-nowrap">
                    <i class="fas fa-palette mr-2"></i>สไตล์ & สี
                </button>
                <button @click="activeTab = 'layout'"
                        :class="{ 'border-pink-500 text-pink-600 dark:text-pink-400 bg-pink-50 dark:bg-pink-900/20': activeTab === 'layout', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== 'layout' }"
                        class="px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 whitespace-nowrap">
                    <i class="fas fa-ruler-combined mr-2"></i>ขนาด & ระยะ
                </button>
                <button @click="activeTab = 'animation'"
                        :class="{ 'border-orange-500 text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/20': activeTab === 'animation', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== 'animation' }"
                        class="px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 whitespace-nowrap">
                    <i class="fas fa-magic mr-2"></i>Animation
                </button>
            </nav>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.app-control-sections.store') }}" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden p-8">
        @csrf

        <div class="space-y-6">
            <!-- Basic Info Tab -->
            <div x-show="activeTab === 'basic'" x-transition>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-key text-cyan-500 mr-1"></i>Section ID *
                        </label>
                        <input type="text" name="section_id" required value="{{ old('section_id') }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500 @error('section_id') border-red-500 @enderror">
                        @error('section_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-tag text-blue-500 mr-1"></i>ชื่อเซคชั่น (TH) *
                        </label>
                        <input type="text" name="section_name" required value="{{ old('section_name') }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-tag text-indigo-500 mr-1"></i>ชื่อเซคชั่น (EN)
                        </label>
                        <input type="text" name="section_name_en" value="{{ old('section_name_en') }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-sort text-purple-500 mr-1"></i>ลำดับ *
                        </label>
                        <input type="number" name="sort_order" required min="0" value="{{ old('sort_order', 0) }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-mobile-alt text-green-500 mr-1"></i>แพลตฟอร์ม *
                        </label>
                        <select name="platform" required class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500">
                            <option value="all">ทั้งหมด</option>
                            <option value="android">Android</option>
                            <option value="ios">iOS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-th-large text-orange-500 mr-1"></i>Layout Type
                        </label>
                        <input type="text" name="layout_type" value="{{ old('layout_type') }}"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500"
                               placeholder="grid, list, carousel">
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-align-left text-gray-500 mr-1"></i>คำอธิบาย (TH)
                    </label>
                    <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-cyan-500">{{ old('description') }}</textarea>
                </div>

                <div class="mt-6 flex items-center justify-between p-4 bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">สถานะ</h4>
                        <div class="flex gap-6 mt-2">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_visible" value="1" checked class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">มองเห็น</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" checked class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">ใช้งาน</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Style Tab -->
            <div x-show="activeTab === 'style'" x-transition>
                <div class="space-y-6">
                    <!-- Background Colors -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-fill-drip text-purple-600 mr-2"></i>สีพื้นหลัง
                        </h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium mb-2">Light Mode</label>
                                <div class="flex gap-2">
                                    <input type="color" name="background_color" value="{{ old('background_color', '#FFFFFF') }}" class="h-12 w-20 rounded cursor-pointer">
                                    <input type="text" name="background_color" value="{{ old('background_color') }}" class="flex-1 px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="#FFFFFF">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Dark Mode</label>
                                <div class="flex gap-2">
                                    <input type="color" name="background_dark_color" value="{{ old('background_dark_color', '#1E293B') }}" class="h-12 w-20 rounded cursor-pointer">
                                    <input type="text" name="background_dark_color" value="{{ old('background_dark_color') }}" class="flex-1 px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="#1E293B">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Border Colors -->
                    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-border-style text-blue-600 mr-2"></i>ขอบ (Border)
                        </h3>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium mb-2">สีขอบ (Light)</label>
                                <div class="flex gap-2">
                                    <input type="color" name="border_color" value="{{ old('border_color') }}" class="h-12 w-20 rounded cursor-pointer">
                                    <input type="text" name="border_color" value="{{ old('border_color') }}" class="flex-1 px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">สีขอบ (Dark)</label>
                                <div class="flex gap-2">
                                    <input type="color" name="border_dark_color" value="{{ old('border_dark_color') }}" class="h-12 w-20 rounded cursor-pointer">
                                    <input type="text" name="border_dark_color" value="{{ old('border_dark_color') }}" class="flex-1 px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">ความหนา (px)</label>
                                <input type="number" name="border_width" min="0" max="20" value="{{ old('border_width', 1) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                            </div>
                        </div>
                    </div>

                    <!-- Border Radius & Elevation -->
                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-2"><i class="fas fa-circle text-purple-500 mr-1"></i>Border Radius</label>
                            <input type="number" name="border_radius" min="0" max="100" value="{{ old('border_radius', 12) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2"><i class="fas fa-layer-group text-orange-500 mr-1"></i>Elevation (Shadow)</label>
                            <input type="number" name="elevation" min="0" max="24" value="{{ old('elevation', 2) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2"><i class="fas fa-adjust text-gray-500 mr-1"></i>Opacity</label>
                            <input type="number" name="opacity" min="0" max="1" step="0.1" value="{{ old('opacity', 1) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Layout Tab -->
            <div x-show="activeTab === 'layout'" x-transition>
                <div class="space-y-6">
                    <!-- Size -->
                    <div class="bg-gradient-to-r from-pink-50 to-rose-50 dark:from-pink-900/20 dark:to-rose-900/20 rounded-xl p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-expand-arrows-alt text-pink-600 mr-2"></i>ขนาด
                        </h3>
                        <div class="grid md:grid-cols-4 gap-6">
                            <div>
                                <label class="block text-sm font-medium mb-2">Min Height</label>
                                <input type="number" name="min_height" min="0" value="{{ old('min_height') }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="0">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Max Height</label>
                                <input type="number" name="max_height" min="0" value="{{ old('max_height') }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Min Width</label>
                                <input type="number" name="min_width" min="0" value="{{ old('min_width') }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Max Width</label>
                                <input type="number" name="max_width" min="0" value="{{ old('max_width') }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                            </div>
                        </div>
                    </div>

                    <!-- Padding & Margin -->
                    <div class="grid md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-6">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                                <i class="fas fa-arrows-alt text-blue-600 mr-2"></i>Padding
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <input type="number" name="padding_top" min="0" value="{{ old('padding_top') }}" class="px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="Top">
                                <input type="number" name="padding_bottom" min="0" value="{{ old('padding_bottom') }}" class="px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="Bottom">
                                <input type="number" name="padding_left" min="0" value="{{ old('padding_left') }}" class="px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="Left">
                                <input type="number" name="padding_right" min="0" value="{{ old('padding_right') }}" class="px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="Right">
                            </div>
                        </div>

                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6">
                            <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                                <i class="fas fa-arrows-alt-v text-purple-600 mr-2"></i>Margin
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <input type="number" name="margin_top" value="{{ old('margin_top') }}" class="px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="Top">
                                <input type="number" name="margin_bottom" value="{{ old('margin_bottom') }}" class="px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="Bottom">
                                <input type="number" name="margin_left" value="{{ old('margin_left') }}" class="px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="Left">
                                <input type="number" name="margin_right" value="{{ old('margin_right') }}" class="px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="Right">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Animation Tab -->
            <div x-show="activeTab === 'animation'" x-transition>
                <div class="bg-gradient-to-r from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            <i class="fas fa-magic text-orange-600 mr-2"></i>Animation Settings
                        </h3>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="animation_enabled" value="1" class="sr-only peer">
                            <div class="relative w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                            <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">เปิด Animation</span>
                        </label>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-2">Animation Type</label>
                            <input type="text" name="animation_type" value="{{ old('animation_type') }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" placeholder="fade, slide, scale">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">Duration (ms)</label>
                            <input type="number" name="animation_duration" min="100" max="2000" value="{{ old('animation_duration', 300) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-slate-700">
                <a href="{{ route('admin.app-control-sections.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-800 dark:text-gray-200 rounded-xl transition-all duration-200 font-medium">
                    <i class="fas fa-times mr-2"></i>ยกเลิก
                </a>
                <button type="submit" class="px-8 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                    <i class="fas fa-save mr-2"></i>บันทึกเซคชั่น
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
