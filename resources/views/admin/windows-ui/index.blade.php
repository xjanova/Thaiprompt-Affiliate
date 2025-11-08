@extends('layouts.admin')

@section('title', 'จัดการ Windows UI')

@section('content')
<div class="container-fluid px-4 py-6">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-blue-500 via-cyan-600 to-teal-600 dark:from-blue-600 dark:via-cyan-700 dark:to-teal-700 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-white mb-1">จัดการ Windows UI</h1>
                    <p class="text-blue-100 dark:text-cyan-200">ปรับแต่งหน้าแรกแบบ Windows ธีมยานอวกาศ</p>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    <!-- Quick Navigation Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <a href="{{ route('admin.windows-ui.start-menu') }}" class="block group">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 p-6 border border-gray-100 dark:border-slate-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl">
                        🚀
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">Start Menu</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">จัดการรายการเมนู Start</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.windows-ui.taskbar-apps') }}" class="block group">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 p-6 border border-gray-100 dark:border-slate-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white text-2xl">
                        📱
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors">Taskbar Apps</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">จัดการแอปใน Taskbar</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.windows-ui.system-tray') }}" class="block group">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 p-6 border border-gray-100 dark:border-slate-700">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-2xl">
                        ⚙️
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">System Tray</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">จัดการไอคอน System Tray</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </div>
            </div>
        </a>
    </div>

    <!-- Settings Form -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="p-6">
            <form method="POST" action="{{ route('admin.windows-ui.update') }}">
                @csrf
                @method('PUT')

                <div class="space-y-8">
                    <!-- Taskbar Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">🖥️</span> Taskbar Settings
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตำแหน่ง Taskbar</label>
                                <select name="windows_taskbar_position" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="top" {{ ($settings['windows_taskbar_position'] ?? 'top') === 'top' ? 'selected' : '' }}>บน (Top)</option>
                                    <option value="bottom" {{ ($settings['windows_taskbar_position'] ?? 'top') === 'bottom' ? 'selected' : '' }}>ล่าง (Bottom)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความสูง (px)</label>
                                <input type="number" name="windows_taskbar_height" min="32" max="80" value="{{ $settings['windows_taskbar_height'] ?? 48 }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โปร่งแสง (%)</label>
                                <input type="number" name="windows_taskbar_transparency" min="0" max="100" value="{{ $settings['windows_taskbar_transparency'] ?? 95 }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="windows_taskbar_blur" value="1" {{ ($settings['windows_taskbar_blur'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ Blur Effect</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Start Button Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">🏁</span> Start Button Settings
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความ Start Button</label>
                                <input type="text" name="windows_start_button_text" value="{{ $settings['windows_start_button_text'] ?? 'เริ่ม' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="windows_start_button_use_logo" value="1" {{ ($settings['windows_start_button_use_logo'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ใช้โลโก้แทนข้อความ</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- RGB Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">🌈</span> RGB Animation
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="windows_rgb_enabled" value="1" {{ ($settings['windows_rgb_enabled'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ RGB Animation</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความเร็ว (วินาที)</label>
                                <input type="number" name="windows_rgb_speed" min="1" max="10" value="{{ $settings['windows_rgb_speed'] ?? 3 }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="windows_rgb_glow" value="1" {{ ($settings['windows_rgb_glow'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ Glow Effect</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">ปรับแต่งสี RGB เพิ่มเติมได้ที่ <a href="{{ route('admin.windows-ui.rgb-settings') }}" class="text-blue-600 hover:text-blue-700 underline">RGB Settings</a></p>
                        </div>
                    </div>

                    <!-- Theme Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">🎨</span> Theme Settings
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โหมดธีม</label>
                                <select name="windows_theme_mode" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="auto" {{ ($settings['windows_theme_mode'] ?? 'auto') === 'auto' ? 'selected' : '' }}>อัตโนมัติ (Auto)</option>
                                    <option value="light" {{ ($settings['windows_theme_mode'] ?? 'auto') === 'light' ? 'selected' : '' }}>สว่าง (Light)</option>
                                    <option value="dark" {{ ($settings['windows_theme_mode'] ?? 'auto') === 'dark' ? 'selected' : '' }}>มืด (Dark)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สี Accent</label>
                                <input type="color" name="windows_accent_color" value="{{ $settings['windows_accent_color'] ?? '#0078D4' }}" class="w-full h-10 px-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg">
                            </div>
                        </div>
                    </div>

                    <!-- Spaceship Theme -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">🚀</span> Spaceship Theme
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="windows_spaceship_theme" value="1" {{ ($settings['windows_spaceship_theme'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ธีมยานอวกาศ</span>
                                </label>
                            </div>
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="windows_spaceship_stars" value="1" {{ ($settings['windows_spaceship_stars'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงดาวพื้นหลัง</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- System Tray Info -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">ℹ️</span> System Tray Info
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความไลเซ่น</label>
                                <input type="text" name="windows_license_text" value="{{ $settings['windows_license_text'] ?? 'Licensed' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความลิขสิทธิ์</label>
                                <input type="text" name="windows_copyright_text" value="{{ $settings['windows_copyright_text'] ?? '© 2025 TP-Affiliate' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-slate-700">
                        <button type="submit" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-500 dark:to-cyan-500 text-white rounded-xl hover:from-blue-700 hover:to-cyan-700 dark:hover:from-blue-600 dark:hover:to-cyan-600 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                            <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
