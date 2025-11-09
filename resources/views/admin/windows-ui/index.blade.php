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

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Left Column: Settings Sections -->
                    <div class="lg:col-span-3 space-y-8">
                    <!-- Millennium Taskbar Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">🖥️</span> Millennium Taskbar Settings
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตำแหน่ง Taskbar</label>
                                <select name="windows_taskbar_position" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="top" {{ ($settings['windows_taskbar_position'] ?? 'top') === 'top' ? 'selected' : '' }}>บน (Top)</option>
                                    <option value="bottom" {{ ($settings['windows_taskbar_position'] ?? 'top') === 'bottom' ? 'selected' : '' }}>ล่าง (Bottom)</option>
                                </select>
                            </div>
                            <div x-data="{ height: {{ $settings['windows_taskbar_height'] ?? 56 }} }">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความสูง (px)</label>
                                <div class="flex items-center gap-4">
                                    <input type="range" name="windows_taskbar_height" x-model="height" min="32" max="80" step="2" class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 accent-indigo-600">
                                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 min-w-[4rem] text-center" x-text="height + 'px'"></span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">แนะนำ: 48-64px สำหรับหน้าจอทั่วไป</p>
                            </div>
                            <div x-data="{ transparency: {{ $settings['windows_taskbar_transparency'] ?? 95 }} }">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โปร่งแสง (%)</label>
                                <div class="flex items-center gap-4">
                                    <input type="range" name="windows_taskbar_transparency" x-model="transparency" min="0" max="100" class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 accent-indigo-600">
                                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 min-w-[4rem] text-center" x-text="transparency + '%'"></span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">0 = โปร่งใสสุด, 100 = ทึบสุด</p>
                            </div>
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="windows_taskbar_blur" value="1" {{ ($settings['windows_taskbar_blur'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ Blur Effect</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Millennium Back Button Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">◀️</span> Millennium Back Button
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="millennium_back_button_enabled" value="1" {{ ($settings['millennium_back_button_enabled'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงปุ่มกลับ</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความปุ่มกลับ</label>
                                <input type="text" name="millennium_back_button_text" value="{{ $settings['millennium_back_button_text'] ?? 'กลับ' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Millennium Center Section Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">📍</span> Millennium Center Section
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="millennium_center_section_enabled" value="1" {{ ($settings['millennium_center_section_enabled'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงส่วนกลาง</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความส่วนกลาง (เว้นว่างเพื่อใช้ค่าเริ่มต้น)</label>
                                <input type="text" name="millennium_center_section_text" value="{{ $settings['millennium_center_section_text'] ?? '' }}" placeholder="เช่น: Admin Dashboard" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    <!-- Millennium RGB Border Animation -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">🌈</span> Millennium RGB Border Animation
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="millennium_rgb_enabled" value="1" {{ ($settings['millennium_rgb_enabled'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ RGB Border</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความเร็ว (วินาที)</label>
                                <input type="number" name="millennium_rgb_speed" min="1" max="10" value="{{ $settings['millennium_rgb_speed'] ?? 5 }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <strong>💡 หมายเหตุ:</strong> Millennium RGB Border คือแถบสีสันที่วิ่งตามขอบ Taskbar ซึ่งแตกต่างจาก RGB Animation ทั่วไปของระบบ
                            </p>
                        </div>
                    </div>

                    <!-- Millennium Start Menu Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">📋</span> Millennium Start Menu Settings
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตำแหน่ง Start Menu</label>
                                <select name="millennium_menu_position" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="left" {{ ($settings['millennium_menu_position'] ?? 'left') === 'left' ? 'selected' : '' }}>ซ้าย (Left)</option>
                                    <option value="center" {{ ($settings['millennium_menu_position'] ?? 'left') === 'center' ? 'selected' : '' }}>กลาง (Center)</option>
                                    <option value="right" {{ ($settings['millennium_menu_position'] ?? 'left') === 'right' ? 'selected' : '' }}>ขวา (Right)</option>
                                </select>
                            </div>
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="millennium_menu_rgb_enabled" value="1" {{ ($settings['millennium_menu_rgb_enabled'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ RGB Border ใน Menu</span>
                                </label>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4" x-data="{
                            menuWidth: '{{ $settings['millennium_menu_width'] ?? '400' }}',
                            menuWidthUnit: '{{ $settings['millennium_menu_width_unit'] ?? 'px' }}',
                            menuMaxHeight: '{{ $settings['millennium_menu_max_height'] ?? '600' }}',
                            menuMaxHeightUnit: '{{ $settings['millennium_menu_max_height_unit'] ?? 'px' }}'
                        }">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ความกว้าง Menu (<span x-text="menuWidthUnit"></span>)
                                </label>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-4">
                                        <input
                                            type="number"
                                            x-model.number="menuWidth"
                                            name="millennium_menu_width"
                                            :min="menuWidthUnit === 'px' ? 300 : 20"
                                            :max="menuWidthUnit === 'px' ? 800 : 100"
                                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400 min-w-[5rem] text-center" x-text="menuWidth + menuWidthUnit"></span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" @click="menuWidthUnit = 'px'; menuWidth = 400" :class="menuWidthUnit === 'px' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300'" class="px-3 py-1 rounded text-sm font-medium">px</button>
                                        <button type="button" @click="menuWidthUnit = '%'; menuWidth = 90" :class="menuWidthUnit === '%' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300'" class="px-3 py-1 rounded text-sm font-medium">%</button>
                                    </div>
                                    <input type="hidden" name="millennium_menu_width_unit" x-model="menuWidthUnit">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ความสูงสูงสุด (<span x-text="menuMaxHeightUnit"></span>)
                                </label>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-4">
                                        <input
                                            type="number"
                                            x-model.number="menuMaxHeight"
                                            name="millennium_menu_max_height"
                                            :min="menuMaxHeightUnit === 'px' ? 400 : (menuMaxHeightUnit === '%' ? 50 : 50)"
                                            :max="menuMaxHeightUnit === 'px' ? 1000 : (menuMaxHeightUnit === '%' ? 100 : 100)"
                                            class="flex-1 px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400 min-w-[5rem] text-center" x-text="menuMaxHeight + menuMaxHeightUnit"></span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" @click="menuMaxHeightUnit = 'px'; menuMaxHeight = 600" :class="menuMaxHeightUnit === 'px' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300'" class="px-3 py-1 rounded text-sm font-medium">px</button>
                                        <button type="button" @click="menuMaxHeightUnit = '%'; menuMaxHeight = 80" :class="menuMaxHeightUnit === '%' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300'" class="px-3 py-1 rounded text-sm font-medium">%</button>
                                        <button type="button" @click="menuMaxHeightUnit = 'vh'; menuMaxHeight = 80" :class="menuMaxHeightUnit === 'vh' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300'" class="px-3 py-1 rounded text-sm font-medium">vh</button>
                                    </div>
                                    <input type="hidden" name="millennium_menu_max_height_unit" x-model="menuMaxHeightUnit">
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <strong>💡 คำแนะนำ:</strong>
                            </p>
                            <ul class="text-sm text-blue-700 dark:text-blue-300 list-disc ml-5 mt-2 space-y-1">
                                <li><strong>px:</strong> ขนาดคงที่ (เช่น 400px, 600px)</li>
                                <li><strong>%:</strong> เปอร์เซ็นต์ของหน้าจอ (เช่น 90%, 80%)</li>
                                <li><strong>vh:</strong> เปอร์เซ็นต์ของความสูงหน้าจอ (เช่น 80vh)</li>
                            </ul>
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

                    <!-- Content Area Width Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">📏</span> ความกว้างพื้นที่ใช้งาน (Content Area Width)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โหมดความกว้าง</label>
                                <select name="content_width_mode" id="content_width_mode" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="max" {{ ($settings['content_width_mode'] ?? 'container') === 'max' ? 'selected' : '' }}>เต็มความกว้างจอ (Full Width)</option>
                                    <option value="container" {{ ($settings['content_width_mode'] ?? 'container') === 'container' ? 'selected' : '' }}>ค่ามาตรฐาน (max-w-7xl / ~1280px)</option>
                                    <option value="custom" {{ ($settings['content_width_mode'] ?? 'container') === 'custom' ? 'selected' : '' }}>กำหนดเอง (Custom)</option>
                                </select>
                            </div>
                            <div id="custom_width_wrapper" style="display: {{ ($settings['content_width_mode'] ?? 'container') === 'custom' ? 'block' : 'none' }};">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความกว้างแบบกำหนดเอง (px)</label>
                                <input type="number" name="content_width_custom" min="800" max="3000" step="10" value="{{ $settings['content_width_custom'] ?? 1400 }}" placeholder="1400" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <strong>💡 คำแนะนำ:</strong>
                            </p>
                            <ul class="text-sm text-blue-700 dark:text-blue-300 list-disc ml-5 mt-2 space-y-1">
                                <li><strong>เต็มความกว้างจอ:</strong> เนื้อหาจะกางเต็มหน้าจอ (100%)</li>
                                <li><strong>ค่ามาตรฐาน:</strong> ความกว้างสูงสุด ~1280px (max-w-7xl)</li>
                                <li><strong>กำหนดเอง:</strong> กำหนดความกว้างได้ตามต้องการ (800-3000px)</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Clock Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">🕐</span> Clock Settings
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปแบบนาฬิกา</label>
                                <select name="millennium_clock_style" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="digital" {{ ($settings['millennium_clock_style'] ?? 'digital') === 'digital' ? 'selected' : '' }}>Digital (ดิจิทัล)</option>
                                    <option value="minimal" {{ ($settings['millennium_clock_style'] ?? 'digital') === 'minimal' ? 'selected' : '' }}>Minimal (แบบย่อ)</option>
                                    <option value="full" {{ ($settings['millennium_clock_style'] ?? 'digital') === 'full' ? 'selected' : '' }}>Full (แบบเต็ม)</option>
                                    <option value="hidden" {{ ($settings['millennium_clock_style'] ?? 'digital') === 'hidden' ? 'selected' : '' }}>Hidden (ซ่อน)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปแบบเวลา</label>
                                <select name="millennium_clock_format" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="24h" {{ ($settings['millennium_clock_format'] ?? '24h') === '24h' ? 'selected' : '' }}>24 ชั่วโมง (13:45)</option>
                                    <option value="12h" {{ ($settings['millennium_clock_format'] ?? '24h') === '12h' ? 'selected' : '' }}>12 ชั่วโมง (01:45 PM)</option>
                                </select>
                            </div>
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="millennium_clock_show_seconds" value="1" {{ ($settings['millennium_clock_show_seconds'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงวินาที</span>
                                </label>
                            </div>
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="millennium_clock_show_date" value="1" {{ ($settings['millennium_clock_show_date'] ?? false) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงวันที่</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปแบบวันที่</label>
                                <select name="millennium_clock_date_format" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="short" {{ ($settings['millennium_clock_date_format'] ?? 'short') === 'short' ? 'selected' : '' }}>แบบสั้น (09/01/68)</option>
                                    <option value="long" {{ ($settings['millennium_clock_date_format'] ?? 'short') === 'long' ? 'selected' : '' }}>แบบยาว (9 มกราคม 2568)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Back to Top Button Settings -->
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">⬆️</span> Back to Top Button (ปุ่มกลับด้านบน)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="millennium_back_to_top_enabled" value="1" {{ ($settings['millennium_back_to_top_enabled'] ?? true) ? 'checked' : '' }} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ปุ่ม Back to Top</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตำแหน่งปุ่ม</label>
                                <select name="millennium_back_to_top_position" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="bottom-right" {{ ($settings['millennium_back_to_top_position'] ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' }}>ล่างขวา (Bottom Right)</option>
                                    <option value="bottom-left" {{ ($settings['millennium_back_to_top_position'] ?? 'bottom-right') === 'bottom-left' ? 'selected' : '' }}>ล่างซ้าย (Bottom Left)</option>
                                    <option value="bottom-center" {{ ($settings['millennium_back_to_top_position'] ?? 'bottom-right') === 'bottom-center' ? 'selected' : '' }}>ล่างกลาง (Bottom Center)</option>
                                </select>
                            </div>
                            <div x-data="{ threshold: {{ $settings['millennium_back_to_top_threshold'] ?? 20 }} }">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    แสดงปุ่มเมื่อเลื่อนลง (%)
                                </label>
                                <div class="flex items-center gap-4">
                                    <input
                                        type="range"
                                        name="millennium_back_to_top_threshold"
                                        x-model="threshold"
                                        min="0"
                                        max="100"
                                        step="5"
                                        class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 accent-indigo-600">
                                    <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400 min-w-[4rem] text-center" x-text="threshold + '%'"></span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    ปุ่มจะปรากฏเมื่อเลื่อนลงมา <span x-text="threshold"></span>% ของหน้า
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">อนิเมชั่น</label>
                                <select name="millennium_back_to_top_animation" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="fade" {{ ($settings['millennium_back_to_top_animation'] ?? 'fade') === 'fade' ? 'selected' : '' }}>Fade (จางเข้า/ออก)</option>
                                    <option value="slide" {{ ($settings['millennium_back_to_top_animation'] ?? 'fade') === 'slide' ? 'selected' : '' }}>Slide (เลื่อนขึ้น/ลง)</option>
                                    <option value="bounce" {{ ($settings['millennium_back_to_top_animation'] ?? 'fade') === 'bounce' ? 'selected' : '' }}>Bounce (เด้ง)</option>
                                    <option value="scale" {{ ($settings['millennium_back_to_top_animation'] ?? 'fade') === 'scale' ? 'selected' : '' }}>Scale (ขยาย/หด)</option>
                                    <option value="zoom" {{ ($settings['millennium_back_to_top_animation'] ?? 'fade') === 'zoom' ? 'selected' : '' }}>Zoom (ซูมเข้า/ออก)</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <strong>💡 คำแนะนำ:</strong> ปุ่ม Back to Top จะช่วยให้ผู้ใช้สามารถกลับไปยังด้านบนของหน้าได้อย่างรวดเร็ว โดยจะปรากฏขึ้นเมื่อเลื่อนหน้าลงมาตามเปอร์เซ็นต์ที่กำหนด (แนะนำ: 20-30%)
                            </p>
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
                    </div>
                    <!-- End Left Column -->

                    <!-- Right Column: Sticky Save Button -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-4">
                            <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-slate-700 dark:to-slate-800 rounded-xl p-6 shadow-lg border border-blue-100 dark:border-slate-600">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <span class="text-2xl">💾</span> บันทึกการตั้งค่า
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                                    กดปุ่มด้านล่างเพื่อบันทึกการเปลี่ยนแปลงทั้งหมด
                                </p>
                                <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-500 dark:to-cyan-500 text-white rounded-xl hover:from-blue-700 hover:to-cyan-700 dark:hover:from-blue-600 dark:hover:to-cyan-600 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 font-bold">
                                    <i class="fas fa-save mr-2"></i>บันทึก
                                </button>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-4 text-center">
                                    ⚠️ อย่าลืมบันทึกหลังจากแก้ไข
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- End Right Column -->
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Toggle custom width input visibility
    document.getElementById('content_width_mode').addEventListener('change', function() {
        const customWidthWrapper = document.getElementById('custom_width_wrapper');
        if (this.value === 'custom') {
            customWidthWrapper.style.display = 'block';
        } else {
            customWidthWrapper.style.display = 'none';
        }
    });
</script>
@endpush
@endsection
