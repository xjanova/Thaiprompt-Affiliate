@extends('layouts.admin')

@section('title', 'ธีมแอพพลิเคชั่น')

@section('content')
<div x-data="{ activeTab: 'colors' }" class="container-fluid px-4 py-6">

    <!-- Animated Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-600 to-pink-600 dark:from-indigo-600 dark:via-purple-700 dark:to-pink-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">ธีมแอพพลิเคชั่น</h1>
                    <p class="text-indigo-100 dark:text-purple-200">กำหนดสไตล์และสีของแอพพลิเคชั่น</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-l-4 border-green-500 p-4 rounded-xl shadow-md">
            <div class="flex items-center">
                <svg class="h-6 w-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-green-800 dark:text-green-300 font-semibold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <!-- Tab Navigation -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden mb-6">
        <div class="border-b border-gray-200 dark:border-slate-700">
            <nav class="flex overflow-x-auto -mb-px">
                <button @click="activeTab = 'colors'"
                        :class="{ 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20': activeTab === 'colors', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== 'colors' }"
                        class="px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 whitespace-nowrap">
                    <i class="fas fa-palette mr-2"></i>สีธีม
                </button>
                <button @click="activeTab = 'typography'"
                        :class="{ 'border-purple-500 text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20': activeTab === 'typography', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== 'typography' }"
                        class="px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 whitespace-nowrap">
                    <i class="fas fa-font mr-2"></i>ฟอนต์
                </button>
                <button @click="activeTab = 'layout'"
                        :class="{ 'border-pink-500 text-pink-600 dark:text-pink-400 bg-pink-50 dark:bg-pink-900/20': activeTab === 'layout', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== 'layout' }"
                        class="px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 whitespace-nowrap">
                    <i class="fas fa-ruler-combined mr-2"></i>เลย์เอาต์
                </button>
                <button @click="activeTab = 'images'"
                        :class="{ 'border-blue-500 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20': activeTab === 'images', 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300': activeTab !== 'images' }"
                        class="px-6 py-4 text-sm font-semibold border-b-2 transition-all duration-200 whitespace-nowrap">
                    <i class="fas fa-images mr-2"></i>รูปภาพ
                </button>
            </nav>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('admin.app-theme.update') }}" enctype="multipart/form-data" class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-gray-100 dark:border-slate-700 overflow-hidden p-8">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <!-- Colors Tab -->
            <div x-show="activeTab === 'colors'" x-transition>
                <!-- Theme Name -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-tag text-indigo-500 mr-1"></i>ชื่อธีม
                    </label>
                    <input type="text" name="theme_name" required value="{{ old('theme_name', $themeSettings->theme_name) }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500">
                </div>

                <!-- Primary Colors -->
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl p-6 border border-indigo-200 dark:border-indigo-700 mb-6">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4"><i class="fas fa-crown text-indigo-600 mr-2"></i>สีหลัก (Primary)</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        @foreach(['primary_color' => 'หลัก', 'primary_dark_color' => 'เข้ม', 'primary_light_color' => 'อ่อน'] as $field => $label)
                            <div>
                                <label class="block text-sm font-medium mb-2">{{ $label }}</label>
                                <div class="flex gap-2">
                                    <input type="color" name="{{ $field }}" value="{{ old($field, $themeSettings->$field) }}" class="h-12 w-20 rounded cursor-pointer border-2 border-gray-300">
                                    <input type="text" name="{{ $field }}" value="{{ old($field, $themeSettings->$field) }}" class="flex-1 px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Secondary Colors -->
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-700 mb-6">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4"><i class="fas fa-star text-purple-600 mr-2"></i>สีรอง (Secondary)</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        @foreach(['secondary_color' => 'หลัก', 'secondary_dark_color' => 'เข้ม', 'secondary_light_color' => 'อ่อน'] as $field => $label)
                            <div>
                                <label class="block text-sm font-medium mb-2">{{ $label }}</label>
                                <div class="flex gap-2">
                                    <input type="color" name="{{ $field }}" value="{{ old($field, $themeSettings->$field) }}" class="h-12 w-20 rounded cursor-pointer border-2 border-gray-300">
                                    <input type="text" name="{{ $field }}" value="{{ old($field, $themeSettings->$field) }}" class="flex-1 px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Background & Surface -->
                <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900/20 dark:to-slate-900/20 rounded-xl p-6 border border-gray-200 dark:border-gray-700 mb-6">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4"><i class="fas fa-fill text-gray-600 mr-2"></i>พื้นหลัง & Surface</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        @foreach(['background_color' => 'พื้นหลัง Light', 'background_dark_color' => 'พื้นหลัง Dark', 'surface_color' => 'Surface Light', 'surface_dark_color' => 'Surface Dark'] as $field => $label)
                            <div>
                                <label class="block text-sm font-medium mb-2">{{ $label }}</label>
                                <div class="flex gap-2">
                                    <input type="color" name="{{ $field }}" value="{{ old($field, $themeSettings->$field) }}" class="h-12 w-20 rounded cursor-pointer border-2 border-gray-300">
                                    <input type="text" name="{{ $field }}" value="{{ old($field, $themeSettings->$field) }}" class="flex-1 px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Text Colors -->
                <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-700 mb-6">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4"><i class="fas fa-font text-blue-600 mr-2"></i>สีตัวอักษร</h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        @foreach(['text_primary_color' => 'ตัวหนา Light', 'text_primary_dark_color' => 'ตัวหนา Dark', 'text_secondary_color' => 'ตัวบาง Light', 'text_secondary_dark_color' => 'ตัวบาง Dark'] as $field => $label)
                            <div>
                                <label class="block text-sm font-medium mb-2">{{ $label }}</label>
                                <div class="flex gap-2">
                                    <input type="color" name="{{ $field }}" value="{{ old($field, $themeSettings->$field) }}" class="h-12 w-20 rounded cursor-pointer border-2 border-gray-300">
                                    <input type="text" name="{{ $field }}" value="{{ old($field, $themeSettings->$field) }}" class="flex-1 px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Status Colors -->
                <div class="bg-gradient-to-r from-green-50 via-yellow-50 to-red-50 dark:from-green-900/20 dark:via-yellow-900/20 dark:to-red-900/20 rounded-xl p-6 border border-green-200 dark:border-green-700">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4"><i class="fas fa-traffic-light text-green-600 mr-2"></i>สีสถานะ</h3>
                    <div class="grid md:grid-cols-4 gap-6">
                        @foreach(['success_color' => ['สำเร็จ', 'green'], 'warning_color' => ['แจ้งเตือน', 'yellow'], 'error_color' => ['ผิดพลาด', 'red'], 'info_color' => ['ข้อมูล', 'blue']] as $field => $data)
                            <div>
                                <label class="block text-sm font-medium mb-2">{{ $data[0] }}</label>
                                <div class="flex gap-2">
                                    <input type="color" name="{{ $field }}" value="{{ old($field, $themeSettings->$field) }}" class="h-12 w-20 rounded cursor-pointer border-2 border-{{ $data[1] }}-300">
                                    <input type="text" name="{{ $field }}" value="{{ old($field, $themeSettings->$field) }}" class="flex-1 px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Typography Tab -->
            <div x-show="activeTab === 'typography'" x-transition>
                <div class="space-y-6">
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4"><i class="fas fa-heading text-purple-600 mr-2"></i>ฟอนต์</h3>
                        <div class="grid md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-medium mb-2">ฟอนต์ไทย</label>
                                <input type="text" name="font_family" value="{{ old('font_family', $themeSettings->font_family) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">ฟอนต์อังกฤษ</label>
                                <input type="text" name="font_family_en" value="{{ old('font_family_en', $themeSettings->font_family_en) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                            </div>
                        </div>

                        <div class="grid md:grid-cols-4 gap-6">
                            @foreach(['font_size_small' => 'เล็ก', 'font_size_base' => 'ปกติ', 'font_size_large' => 'ใหญ่', 'font_size_xlarge' => 'ใหญ่พิเศษ'] as $field => $label)
                                <div>
                                    <label class="block text-sm font-medium mb-2">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" min="8" max="50" value="{{ old($field, $themeSettings->$field) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Layout Tab -->
            <div x-show="activeTab === 'layout'" x-transition>
                <div class="space-y-6">
                    <div class="bg-gradient-to-r from-pink-50 to-rose-50 dark:from-pink-900/20 dark:to-rose-900/20 rounded-xl p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4"><i class="fas fa-circle text-pink-600 mr-2"></i>Border Radius</h3>
                        <div class="grid md:grid-cols-4 gap-6 mb-6">
                            @foreach(['border_radius_small' => 'เล็ก', 'border_radius_medium' => 'กลาง', 'border_radius_large' => 'ใหญ่', 'border_radius_xlarge' => 'ใหญ่พิเศษ'] as $field => $label)
                                <div>
                                    <label class="block text-sm font-medium mb-2">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" min="0" max="50" value="{{ old($field, $themeSettings->$field) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                                </div>
                            @endforeach
                        </div>

                        <h3 class="font-bold text-gray-900 dark:text-white mb-4 mt-6"><i class="fas fa-arrows-alt text-blue-600 mr-2"></i>Spacing</h3>
                        <div class="grid md:grid-cols-4 gap-6">
                            @foreach(['spacing_small' => 'เล็ก', 'spacing_medium' => 'กลาง', 'spacing_large' => 'ใหญ่', 'spacing_xlarge' => 'ใหญ่พิเศษ'] as $field => $label)
                                <div>
                                    <label class="block text-sm font-medium mb-2">{{ $label }}</label>
                                    <input type="number" name="{{ $field }}" min="0" max="200" value="{{ old($field, $themeSettings->$field) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                                </div>
                            @endforeach
                        </div>

                        <h3 class="font-bold text-gray-900 dark:text-white mb-4 mt-6"><i class="fas fa-magic text-orange-600 mr-2"></i>Animation</h3>
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium mb-2">Duration (ms)</label>
                                <input type="number" name="animation_duration" min="100" max="2000" value="{{ old('animation_duration', $themeSettings->animation_duration) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Curve</label>
                                <input type="text" name="animation_curve" value="{{ old('animation_curve', $themeSettings->animation_curve) }}" class="w-full px-4 py-3 border dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl" required>
                            </div>
                        </div>
                    </div>

                    <!-- Dark Mode Settings -->
                    <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900/20 dark:to-slate-900/20 rounded-xl p-6">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4"><i class="fas fa-moon text-indigo-600 mr-2"></i>Dark Mode</h3>
                        <div class="flex gap-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="dark_mode_enabled" value="1" {{ old('dark_mode_enabled', $themeSettings->dark_mode_enabled) ? 'checked' : '' }} class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                <span class="ml-3 text-sm font-medium">เปิดใช้ Dark Mode</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="dark_mode_default" value="1" {{ old('dark_mode_default', $themeSettings->dark_mode_default) ? 'checked' : '' }} class="sr-only peer">
                                <div class="relative w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                <span class="ml-3 text-sm font-medium">ใช้ Dark Mode เป็นค่าเริ่มต้น</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Images Tab -->
            <div x-show="activeTab === 'images'" x-transition>
                <div class="space-y-6">
                    @foreach(['logo_url' => 'โลโก้', 'logo_dark_url' => 'โลโก้ (Dark Mode)', 'favicon_url' => 'Favicon', 'app_icon_url' => 'ไอคอนแอพ', 'splash_screen_url' => 'Splash Screen', 'splash_screen_dark_url' => 'Splash Screen (Dark)'] as $field => $label)
                        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                <i class="fas fa-image text-blue-500 mr-1"></i>{{ $label }}
                            </label>
                            @if($themeSettings->$field)
                                <div class="mb-3">
                                    <img src="{{ asset($themeSettings->$field) }}" alt="{{ $label }}" class="h-20 rounded-lg border border-gray-300 dark:border-slate-600">
                                </div>
                            @endif
                            <input type="file" name="{{ $field }}" accept="image/*" class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl">
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-slate-700">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $themeSettings->is_active) ? 'checked' : '' }} class="sr-only peer">
                    <div class="relative w-11 h-6 bg-gray-300 peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                    <span class="ml-3 text-sm font-medium">ใช้งานธีมนี้</span>
                </label>

                <button type="submit" class="ml-auto px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                    <i class="fas fa-save mr-2"></i>บันทึกธีม
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
