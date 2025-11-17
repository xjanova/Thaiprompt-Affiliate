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
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-info-circle mr-2 text-blue-500"></i>ข้อมูลพื้นฐาน
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อ Theme</label>
                        <input type="text" name="theme_name" value="{{ $themeSetting->theme_name }}" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ชื่อแบรนด์ (แสดงใน Sidebar)</label>
                        <input type="text" name="brand_name" value="{{ $themeSetting->brand_name }}" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tagline แบรนด์ (ไม่บังคับ)</label>
                        <input type="text" name="brand_tagline" value="{{ $themeSetting->brand_tagline }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
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
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-ruler-combined mr-2 text-green-500"></i>ขนาดและรูปแบบ Layout
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sidebar Width (px)</label>
                        <input type="number" name="sidebar_width" value="{{ $themeSetting->sidebar_width }}" min="200" max="400" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                        <span class="text-xs text-gray-500 dark:text-gray-400">200-400px</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Navbar Height (px)</label>
                        <input type="number" name="navbar_height" value="{{ $themeSetting->navbar_height }}" min="50" max="100" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                        <span class="text-xs text-gray-500 dark:text-gray-400">50-100px</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Footer Height (px)</label>
                        <input type="number" name="footer_height" value="{{ $themeSetting->footer_height }}" min="60" max="150" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                        <span class="text-xs text-gray-500 dark:text-gray-400">60-150px</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Layout Type</label>
                        <select name="layout_type" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-purple-500">
                            <option value="fluid" {{ $themeSetting->layout_type === 'fluid' ? 'selected' : '' }}>Fluid</option>
                            <option value="fixed" {{ $themeSetting->layout_type === 'fixed' ? 'selected' : '' }}>Fixed</option>
                            <option value="boxed" {{ $themeSetting->layout_type === 'boxed' ? 'selected' : '' }}>Boxed</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Opacity --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10"
                 x-data="{
                     globalOpacity: {{ $themeSetting->global_opacity }},
                     sidebarOpacity: {{ $themeSetting->sidebar_opacity }},
                     navbarOpacity: {{ $themeSetting->navbar_opacity }},
                     cardOpacity: {{ $themeSetting->card_opacity }},
                     modalOpacity: {{ $themeSetting->modal_opacity }}
                 }">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-adjust mr-2 text-yellow-500"></i>ความโปร่งใส (Opacity)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Global Opacity</label>
                        <input type="range" name="global_opacity" x-model="globalOpacity" min="0" max="100" class="w-full accent-purple-500">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>โปร่งใส</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400" x-text="globalOpacity + '%'"></span>
                            <span>ทึบ</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sidebar Opacity</label>
                        <input type="range" name="sidebar_opacity" x-model="sidebarOpacity" min="0" max="100" class="w-full accent-purple-500">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>โปร่งใส</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400" x-text="sidebarOpacity + '%'"></span>
                            <span>ทึบ</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Navbar Opacity</label>
                        <input type="range" name="navbar_opacity" x-model="navbarOpacity" min="0" max="100" class="w-full accent-purple-500">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>โปร่งใส</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400" x-text="navbarOpacity + '%'"></span>
                            <span>ทึบ</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Card Opacity</label>
                        <input type="range" name="card_opacity" x-model="cardOpacity" min="0" max="100" class="w-full accent-purple-500">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>โปร่งใส</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400" x-text="cardOpacity + '%'"></span>
                            <span>ทึบ</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Modal Opacity</label>
                        <input type="range" name="modal_opacity" x-model="modalOpacity" min="0" max="100" class="w-full accent-purple-500">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>โปร่งใส</span>
                            <span class="font-bold text-purple-600 dark:text-purple-400" x-text="modalOpacity + '%'"></span>
                            <span>ทึบ</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card Styling --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10"
                 x-data="{
                     cardBlur: {{ $themeSetting->card_blur_intensity }},
                     cardBorderWidth: {{ $themeSetting->card_border_width }},
                     cardBorderRadius: {{ $themeSetting->card_border_radius }}
                 }">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-magic mr-2 text-pink-500"></i>การตกแต่ง Card
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Blur Intensity</label>
                        <input type="range" name="card_blur_intensity" x-model="cardBlur" min="0" max="20" class="w-full accent-pink-500">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>ชัด</span>
                            <span class="font-bold text-pink-600 dark:text-pink-400" x-text="cardBlur + 'px'"></span>
                            <span>เบลอ</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Border Width</label>
                        <input type="range" name="card_border_width" x-model="cardBorderWidth" min="0" max="10" class="w-full accent-pink-500">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>บาง</span>
                            <span class="font-bold text-pink-600 dark:text-pink-400" x-text="cardBorderWidth + 'px'"></span>
                            <span>หนา</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Border Radius</label>
                        <input type="range" name="card_border_radius" x-model="cardBorderRadius" min="0" max="50" class="w-full accent-pink-500">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>แหลม</span>
                            <span class="font-bold text-pink-600 dark:text-pink-400" x-text="cardBorderRadius + 'px'"></span>
                            <span>มน</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Shadow Intensity</label>
                        <select name="card_shadow_intensity" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-pink-500">
                            <option value="none" {{ $themeSetting->card_shadow_intensity === 'none' ? 'selected' : '' }}>None</option>
                            <option value="sm" {{ $themeSetting->card_shadow_intensity === 'sm' ? 'selected' : '' }}>Small</option>
                            <option value="md" {{ $themeSetting->card_shadow_intensity === 'md' ? 'selected' : '' }}>Medium</option>
                            <option value="lg" {{ $themeSetting->card_shadow_intensity === 'lg' ? 'selected' : '' }}>Large</option>
                            <option value="xl" {{ $themeSetting->card_shadow_intensity === 'xl' ? 'selected' : '' }}>Extra Large</option>
                            <option value="2xl" {{ $themeSetting->card_shadow_intensity === '2xl' ? 'selected' : '' }}>2X Large</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Language Settings --}}
            <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-white/20 dark:border-white/10">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    <i class="fas fa-language mr-2 text-blue-500"></i>ภาษา
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ภาษาหลัก</label>
                        <select name="default_language" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="th" {{ $themeSetting->default_language === 'th' ? 'selected' : '' }}>ไทย (TH)</option>
                            <option value="en" {{ $themeSetting->default_language === 'en' ? 'selected' : '' }}>English (EN)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">RTL Mode</label>
                        <div class="flex items-center gap-3 h-[42px]">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="rtl_enabled" value="1" {{ $themeSetting->rtl_enabled ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">เปิดใช้ RTL</span>
                            </label>
                        </div>
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
