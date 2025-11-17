@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">⚙️ ตั้งค่า Header</h1>
            <p class="text-gray-600 dark:text-gray-400">ปรับแต่งรูปแบบ, สี, และเอฟเฟกต์ของ Header แบบละเอียด</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <form action="{{ route('admin.header-settings.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- 3D Effects Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-2 border-purple-200 dark:border-purple-700">
                <div class="flex items-center mb-4">
                    <h2 class="text-2xl font-bold text-purple-600 dark:text-purple-400">🎨 เอฟเฟกต์ 3D</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="header_3d_enabled" value="1"
                                   {{ \App\Models\Setting::get('header_3d_enabled', true) ? 'checked' : '' }}
                                   class="w-5 h-5 text-purple-600 rounded focus:ring-purple-500">
                            <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">เปิดใช้งานเอฟเฟกต์ 3D</span>
                        </label>
                        <p class="mt-1 ml-8 text-sm text-gray-500">เพิ่มความลึกและเงาแบบ 3 มิติให้กับ Header</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความลึก 3D (Depth)
                        </label>
                        <input type="range" name="header_3d_depth" min="0" max="10"
                               value="{{ \App\Models\Setting::get('header_3d_depth', 3) }}"
                               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                               oninput="this.nextElementSibling.textContent = this.value + 'px'">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\Setting::get('header_3d_depth', 3) }}px</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Perspective
                        </label>
                        <input type="range" name="header_3d_perspective" min="500" max="2000" step="100"
                               value="{{ \App\Models\Setting::get('header_3d_perspective', 1000) }}"
                               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                               oninput="this.nextElementSibling.textContent = this.value + 'px'">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\Setting::get('header_3d_perspective', 1000) }}px</span>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความเข้มของเงา 3D
                        </label>
                        <select name="header_3d_shadow_intensity"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="low" {{ \App\Models\Setting::get('header_3d_shadow_intensity', 'medium') === 'low' ? 'selected' : '' }}>
                                เบา (Low)
                            </option>
                            <option value="medium" {{ \App\Models\Setting::get('header_3d_shadow_intensity', 'medium') === 'medium' ? 'selected' : '' }}>
                                ปานกลาง (Medium)
                            </option>
                            <option value="high" {{ \App\Models\Setting::get('header_3d_shadow_intensity', 'medium') === 'high' ? 'selected' : '' }}>
                                เข้ม (High)
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Logo Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-2 border-blue-200 dark:border-blue-700">
                <div class="flex items-center mb-4">
                    <h2 class="text-2xl font-bold text-blue-600 dark:text-blue-400">🏷️ การตั้งค่าโลโก้</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="header_show_logo" value="1"
                                   {{ \App\Models\Setting::get('header_show_logo', true) ? 'checked' : '' }}
                                   class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">แสดงโลโก้</span>
                        </label>
                        <p class="mt-1 ml-8 text-sm text-gray-500">เปิด/ปิด การแสดงโลโก้ในส่วน Header</p>
                    </div>

                    <div class="col-span-2">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="header_logo_animated" value="1"
                                   {{ \App\Models\Setting::get('header_logo_animated', true) ? 'checked' : '' }}
                                   class="w-5 h-5 text-blue-600 rounded focus:ring-blue-500">
                            <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">เอนิเมชั่นโลโก้</span>
                        </label>
                        <p class="mt-1 ml-8 text-sm text-gray-500">เพิ่มเอฟเฟกต์เคลื่อนไหวเมื่อ hover โลโก้</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความกว้างโลโก้ (Normal)
                        </label>
                        <input type="number" name="logo_navigation_width" min="20" max="400"
                               value="{{ \App\Models\Setting::get('logo_navigation_width', 60) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความสูงโลโก้ (Normal)
                        </label>
                        <input type="number" name="logo_navigation_height" min="20" max="200"
                               value="{{ \App\Models\Setting::get('logo_navigation_height', 60) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความกว้างโลโก้ (Scrolled)
                        </label>
                        <input type="number" name="logo_navigation_scrolled_width" min="20" max="400"
                               value="{{ \App\Models\Setting::get('logo_navigation_scrolled_width', 48) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความสูงโลโก้ (Scrolled)
                        </label>
                        <input type="number" name="logo_navigation_scrolled_height" min="20" max="200"
                               value="{{ \App\Models\Setting::get('logo_navigation_scrolled_height', 48) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>
            </div>

            <!-- Windows UI Mode -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border-2 border-indigo-200 dark:border-indigo-700">
                <div class="flex items-center mb-4">
                    <h2 class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">🪟 โหมด Windows UI</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="header_windows_ui_mode" value="1"
                                   {{ \App\Models\Setting::get('header_windows_ui_mode', false) ? 'checked' : '' }}
                                   class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                            <span class="text-lg font-semibold text-gray-700 dark:text-gray-300">เปิดใช้งานโหมด Windows UI</span>
                        </label>
                        <p class="mt-1 ml-8 text-sm text-gray-500">ใช้สไตล์ Header แบบ Windows 11 (Acrylic Effect)</p>
                    </div>

                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความสูง Header (Windows UI Mode)
                        </label>
                        <input type="range" name="header_windows_ui_height" min="30" max="100"
                               value="{{ \App\Models\Setting::get('header_windows_ui_height', 48) }}"
                               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                               oninput="this.nextElementSibling.textContent = this.value + 'px'">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ \App\Models\Setting::get('header_windows_ui_height', 48) }}px</span>
                    </div>
                </div>
            </div>

            <!-- Basic Settings -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-4">⚙️ การตั้งค่าพื้นฐาน</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความสูง Header (Normal)
                        </label>
                        <input type="number" name="header_height" min="40" max="200"
                               value="{{ \App\Models\Setting::get('header_height', 64) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความสูง Header (Scrolled)
                        </label>
                        <input type="number" name="header_height_scrolled" min="40" max="200"
                               value="{{ \App\Models\Setting::get('header_height_scrolled', 56) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Position
                        </label>
                        <select name="header_position"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="relative" {{ \App\Models\Setting::get('header_position', 'relative') === 'relative' ? 'selected' : '' }}>Relative</option>
                            <option value="fixed" {{ \App\Models\Setting::get('header_position', 'relative') === 'fixed' ? 'selected' : '' }}>Fixed</option>
                            <option value="absolute" {{ \App\Models\Setting::get('header_position', 'relative') === 'absolute' ? 'selected' : '' }}>Absolute</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เงา (Shadow)
                        </label>
                        <select name="header_shadow"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="none" {{ \App\Models\Setting::get('header_shadow', 'lg') === 'none' ? 'selected' : '' }}>ไม่มี</option>
                            <option value="sm" {{ \App\Models\Setting::get('header_shadow', 'lg') === 'sm' ? 'selected' : '' }}>เล็ก</option>
                            <option value="md" {{ \App\Models\Setting::get('header_shadow', 'lg') === 'md' ? 'selected' : '' }}>ปานกลาง</option>
                            <option value="lg" {{ \App\Models\Setting::get('header_shadow', 'lg') === 'lg' ? 'selected' : '' }}>ใหญ่</option>
                            <option value="xl" {{ \App\Models\Setting::get('header_shadow', 'lg') === 'xl' ? 'selected' : '' }}>ใหญ่มาก</option>
                            <option value="2xl" {{ \App\Models\Setting::get('header_shadow', 'lg') === '2xl' ? 'selected' : '' }}>ใหญ่มากๆ</option>
                        </select>
                    </div>

                    <div class="col-span-2 space-y-3">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="header_sticky_scroll" value="1"
                                   {{ \App\Models\Setting::get('header_sticky_scroll', false) ? 'checked' : '' }}
                                   class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sticky on Scroll (ติดด้านบนเมื่อเลื่อน)</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="header_shrink_on_scroll" value="1"
                                   {{ \App\Models\Setting::get('header_shrink_on_scroll', false) ? 'checked' : '' }}
                                   class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Shrink on Scroll (ลดขนาดเมื่อเลื่อน)</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="header_blur" value="1"
                                   {{ \App\Models\Setting::get('header_blur', false) ? 'checked' : '' }}
                                   class="w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Blur Effect (เบลอพื้นหลัง)</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-4">
                <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg shadow-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 transform hover:scale-105">
                    💾 บันทึกการตั้งค่า
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
