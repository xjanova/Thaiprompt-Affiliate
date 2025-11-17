@extends('layouts.admin-v3')

@section('title', 'การตั้งค่าทั่วไป - Arrow X')

@section('content')
<div class="container-fluid px-4 py-8">
    <div class="max-w-4xl mx-auto">
        {{-- Header --}}
        <div class="mb-8">
            <a href="{{ route('admin.arrow-x-theme.index') }}" class="text-purple-600 dark:text-purple-400 hover:underline mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i> กลับ
            </a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">การตั้งค่าทั่วไป</h1>
            <p class="text-gray-600 dark:text-gray-400 dark:text-gray-400">โลโก้, Favicon, ขนาด, ความโปร่งใส</p>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-4 py-3 rounded-xl mb-6">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('admin.arrow-x-theme.general-settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="{ logoPreview: null, faviconPreview: null }">
            @csrf
            @method('PUT')

            {{-- Basic Info --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ Theme</label>
                        <input type="text" name="theme_name" value="{{ $themeSetting->theme_name }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อแบรนด์ (แสดงใน Sidebar)</label>
                        <input type="text" name="brand_name" value="{{ $themeSetting->brand_name }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Theme Logo & Favicon (แยกจาก Site Logo) --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-image mr-2 text-purple-500"></i>โลโก้ธีม (Sidebar Logo)
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <i class="fas fa-info-circle mr-1"></i>โลโก้นี้จะแสดงที่มุมบนซ้ายของ Sidebar (แยกจากโลโก้เว็บไซต์หลัก)
                </p>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Theme Logo --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-rocket mr-1"></i>โลโก้ธีม (Sidebar)
                        </label>
                        <div class="relative group">
                            <div class="w-full h-48 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900 dark:to-pink-900 rounded-xl flex items-center justify-center overflow-hidden border-2 border-dashed border-purple-300 dark:border-purple-600 group-hover:border-purple-500 transition-all">
                                <img x-show="logoPreview" :src="logoPreview" alt="Logo Preview" class="max-w-full max-h-full object-contain">
                                <img x-show="!logoPreview" src="{{ $themeSetting->logo_path ? asset('storage/' . $themeSetting->logo_path) : asset('images/default-logo.png') }}" alt="Current Logo" class="max-w-full max-h-full object-contain">
                            </div>
                            <input type="file" id="logo-upload" name="logo" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" class="hidden" @change="logoPreview = URL.createObjectURL($event.target.files[0])">
                            <label for="logo-upload" class="mt-3 block w-full px-4 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-0.5 transition-all cursor-pointer text-center">
                                <i class="fas fa-upload mr-2"></i>อัพโหลดโลโก้ธีม
                            </label>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                แนะนำ: 200x200px, PNG หรือ SVG
                            </p>
                        </div>
                    </div>

                    {{-- Theme Favicon --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-star mr-1"></i>Favicon ธีม
                        </label>
                        <div class="relative group">
                            <div class="w-full h-48 bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900 dark:to-cyan-900 rounded-xl flex items-center justify-center overflow-hidden border-2 border-dashed border-blue-300 dark:border-blue-600 group-hover:border-blue-500 transition-all">
                                <img x-show="faviconPreview" :src="faviconPreview" alt="Favicon Preview" class="w-32 h-32 object-contain">
                                <img x-show="!faviconPreview" src="{{ $themeSetting->favicon_path ? asset('storage/' . $themeSetting->favicon_path) : asset('favicon.ico') }}" alt="Current Favicon" class="w-32 h-32 object-contain">
                            </div>
                            <input type="file" id="favicon-upload" name="favicon" accept="image/jpeg,image/png,image/gif,image/webp,image/x-icon" class="hidden" @change="faviconPreview = URL.createObjectURL($event.target.files[0])">
                            <label for="favicon-upload" class="mt-3 block w-full px-4 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-bold rounded-xl shadow-lg hover:shadow-2xl transform hover:-translate-y-0.5 transition-all cursor-pointer text-center">
                                <i class="fas fa-upload mr-2"></i>อัพโหลด Favicon
                            </label>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                แนะนำ: 64x64px, PNG หรือ ICO
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Layout --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Layout</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Sidebar Width (px)</label>
                        <input type="number" name="sidebar_width" value="{{ $themeSetting->sidebar_width }}" min="200" max="400" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Navbar Height (px)</label>
                        <input type="number" name="navbar_height" value="{{ $themeSetting->navbar_height }}" min="50" max="100" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Layout Type</label>
                        <select name="layout_type" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white">
                            <option value="fluid" {{ $themeSetting->layout_type === 'fluid' ? 'selected' : '' }}>Fluid</option>
                            <option value="fixed" {{ $themeSetting->layout_type === 'fixed' ? 'selected' : '' }}>Fixed</option>
                            <option value="boxed" {{ $themeSetting->layout_type === 'boxed' ? 'selected' : '' }}>Boxed</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Opacity --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ความโปร่งใส (Opacity)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Global Opacity (%)</label>
                        <input type="range" name="global_opacity" value="{{ $themeSetting->global_opacity }}" min="0" max="100" class="w-full">
                        <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">{{ $themeSetting->global_opacity }}%</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2">Card Opacity (%)</label>
                        <input type="range" name="card_opacity" value="{{ $themeSetting->card_opacity }}" min="0" max="100" class="w-full">
                        <span class="text-sm text-gray-600 dark:text-gray-400 dark:text-gray-400">{{ $themeSetting->card_opacity }}%</span>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.arrow-x-theme.index') }}" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 dark:bg-gray-700 text-gray-700 dark:text-gray-300 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    ยกเลิก
                </a>
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 via-pink-500 to-orange-500 text-white rounded-xl hover:shadow-lg transition">
                    <i class="fas fa-save mr-2"></i> บันทึก
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
