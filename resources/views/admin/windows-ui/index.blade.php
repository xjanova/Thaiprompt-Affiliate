@extends('layouts.admin')

@section('title', 'จัดการ Windows UI')

@push('styles')
<style>
    /* Toggle Switch Styles */
    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
        flex-shrink: 0;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: #cbd5e1;
        transition: 0.3s;
        border-radius: 24px;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: 0.3s;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .toggle-switch input:checked + .toggle-slider {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        box-shadow: 0 0 12px rgba(99, 102, 241, 0.5);
    }

    .toggle-switch input:checked + .toggle-slider:before {
        transform: translateX(24px);
    }

    .toggle-switch input:focus + .toggle-slider {
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
    }

    .toggle-switch input:disabled + .toggle-slider {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Dark mode styles */
    .dark .toggle-slider {
        background: #475569;
    }

    .dark .toggle-switch input:checked + .toggle-slider {
        background: linear-gradient(135deg, #818cf8 0%, #a78bfa 100%);
    }

    /* Settings Tab Styles */
    .settings-tab {
        cursor: pointer;
        transition: all 0.2s;
    }

    .settings-tab.active {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
    }
</style>
@endpush

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

    <!-- Info Banner -->
    <div class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-slate-800 dark:to-slate-700 border border-blue-200 dark:border-cyan-700 rounded-xl p-6 mb-8">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-white text-2xl">
                    ℹ️
                </div>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">📝 Hard-Coded Menu System</h3>
                <p class="text-gray-700 dark:text-gray-300 mb-2">
                    ระบบเมนูได้เปลี่ยนเป็น <strong>Hard-Coded</strong> แล้ว - เมนูทั้งหมดถูก hard-code ใน component เพื่อความเรียบง่าย
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    💡 หน้านี้ใช้สำหรับปรับแต่ง <strong>รูปลักษณ์</strong> เท่านั้น (สี, ขนาด, animation) ไม่ใช่จัดการเมนู
                </p>
            </div>
        </div>
    </div>

    <!-- Settings Form - Unified -->
    <div x-data="unifiedSettingsManager()">
        <form method="POST" action="{{ route('admin.windows-ui.update') }}" id="main-settings-form">
            @csrf
            @method('PUT')

            <!-- Tab Navigation -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden mb-6">
                <div class="border-b border-gray-200 dark:border-slate-700">
                    <div class="flex flex-wrap p-2 gap-2 bg-gray-50 dark:bg-slate-900">
                        <button type="button" @click="activeTab = 'taskbar'" :class="activeTab === 'taskbar' ? 'active' : ''" class="settings-tab flex-1 min-w-[180px] px-6 py-3 rounded-xl text-sm font-semibold bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                            <i class="fas fa-desktop mr-2"></i>Taskbar & Interface
                        </button>
                        <button type="button" @click="activeTab = 'menu'" :class="activeTab === 'menu' ? 'active' : ''" class="settings-tab flex-1 min-w-[180px] px-6 py-3 rounded-xl text-sm font-semibold bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                            <i class="fas fa-align-justify mr-2"></i>Start Menu
                        </button>
                        <button type="button" @click="activeTab = 'startbutton'" :class="activeTab === 'startbutton' ? 'active' : ''" class="settings-tab flex-1 min-w-[180px] px-6 py-3 rounded-xl text-sm font-semibold bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                            <i class="fas fa-window-maximize mr-2"></i>Start Button
                        </button>
                        <button type="button" @click="activeTab = 'menurgb'" :class="activeTab === 'menurgb' ? 'active' : ''" class="settings-tab flex-1 min-w-[180px] px-6 py-3 rounded-xl text-sm font-semibold bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all">
                            <i class="fas fa-rainbow mr-2"></i>RGB Effects
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab Content Container -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden mb-20">
                <div class="p-6">

                    <!-- Tab 1: Taskbar & Interface -->
                    <div x-show="activeTab === 'taskbar'" x-transition>
                        <div class="space-y-6">

                <!-- Category: Taskbar & Interface -->
                <div class="relative">
                    <div class="absolute -left-4 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 to-cyan-500 rounded-full"></div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3 pb-2 border-b-2 border-gray-200 dark:border-slate-700">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-white text-xl">
                            🖥️
                        </div>
                        <span>Taskbar & Interface</span>
                    </h2>
                </div>

                <!-- Millennium Taskbar Settings -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                    <button type="button" @click="openSections.includes('taskbar') ? openSections = openSections.filter(s => s !== 'taskbar') : openSections.push('taskbar')" class="w-full flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="text-2xl">🖥️</span> Millennium Taskbar Settings
                        </h3>
                        <svg class="w-6 h-6 text-gray-500 transition-transform" :class="openSections.includes('taskbar') ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" x-show="openSections.includes('taskbar')">ปรับแต่งลักษณะและพฤติกรรมของ Taskbar หลัก</p>
                    <div x-show="openSections.includes('taskbar')" x-collapse>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
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
                                <label class="toggle-switch"><input type="checkbox" name="windows_taskbar_blur" value="1" {{ ($settings['windows_taskbar_blur'] ?? true) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ Blur Effect</span>
                            </label>
                        </div>
                    </div>

                    <!-- Taskbar Color Customization -->
                    <div class="mt-8 p-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-700">
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <span class="text-2xl">🎨</span> การตั้งค่าสี Taskbar
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">ปรับแต่งสีของ Taskbar ให้ตรงกับธีมของคุณ</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Background Color -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    🎨 สีพื้นหลัง
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color"
                                           name="windows_taskbar_bg_color"
                                           value="{{ $settings['windows_taskbar_bg_color'] ?? '#1e293b' }}"
                                           class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                    <input type="text"
                                           value="{{ $settings['windows_taskbar_bg_color'] ?? '#1e293b' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                           readonly>
                                </div>
                            </div>

                            <!-- Text Color -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    📝 สีข้อความ
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color"
                                           name="windows_taskbar_text_color"
                                           value="{{ $settings['windows_taskbar_text_color'] ?? '#ffffff' }}"
                                           class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                    <input type="text"
                                           value="{{ $settings['windows_taskbar_text_color'] ?? '#ffffff' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                           readonly>
                                </div>
                            </div>

                            <!-- Hover Background Color -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    👆 สีเมื่อ Hover
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color"
                                           name="windows_taskbar_hover_bg_color"
                                           value="{{ $settings['windows_taskbar_hover_bg_color'] ?? '#334155' }}"
                                           class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                    <input type="text"
                                           value="{{ $settings['windows_taskbar_hover_bg_color'] ?? '#334155' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                           readonly>
                                </div>
                            </div>

                            <!-- Active Background Color -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    ⚡ สีเมื่อ Active
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color"
                                           name="windows_taskbar_active_bg_color"
                                           value="{{ $settings['windows_taskbar_active_bg_color'] ?? '#475569' }}"
                                           class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                    <input type="text"
                                           value="{{ $settings['windows_taskbar_active_bg_color'] ?? '#475569' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                           readonly>
                                </div>
                            </div>

                            <!-- Border Color -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    🔲 สีขอบ
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color"
                                           name="windows_taskbar_border_color"
                                           value="{{ $settings['windows_taskbar_border_color'] ?? '#475569' }}"
                                           class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                    <input type="text"
                                           value="{{ $settings['windows_taskbar_border_color'] ?? '#475569' }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                           readonly>
                                </div>
                            </div>

                            <!-- Use Gradient Toggle -->
                            <div class="flex items-center">
                                <label class="flex items-center cursor-pointer">
                                    <label class="toggle-switch">
                                        <input type="checkbox"
                                               name="windows_taskbar_use_gradient"
                                               value="1"
                                               {{ ($settings['windows_taskbar_use_gradient'] ?? false) ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ใช้ Gradient</span>
                                </label>
                            </div>
                        </div>

                        <!-- Gradient Settings -->
                        <div class="mt-6 p-4 bg-white/50 dark:bg-slate-800/50 rounded-lg border border-blue-200 dark:border-blue-700">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">การตั้งค่า Gradient (ใช้เมื่อเปิด "ใช้ Gradient")</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Gradient From -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        🌈 สีเริ่มต้น Gradient
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input type="color"
                                               name="windows_taskbar_gradient_from"
                                               value="{{ $settings['windows_taskbar_gradient_from'] ?? '#1e293b' }}"
                                               class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                        <input type="text"
                                               value="{{ $settings['windows_taskbar_gradient_from'] ?? '#1e293b' }}"
                                               class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                               readonly>
                                    </div>
                                </div>

                                <!-- Gradient To -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        🌈 สีสิ้นสุด Gradient
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input type="color"
                                               name="windows_taskbar_gradient_to"
                                               value="{{ $settings['windows_taskbar_gradient_to'] ?? '#0f172a' }}"
                                               class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                        <input type="text"
                                               value="{{ $settings['windows_taskbar_gradient_to'] ?? '#0f172a' }}"
                                               class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                               readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Color Presets -->
                        <div class="mt-6">
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">🎨 ธีมสีแนะนำ</h5>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <button type="button"
                                        class="px-4 py-2 bg-gradient-to-r from-slate-700 to-slate-900 text-white rounded-lg text-xs hover:shadow-lg transition-all"
                                        onclick="applyColorPreset('#1e293b', '#ffffff', '#334155', '#475569', '#475569', false)">
                                    Dark Slate
                                </button>
                                <button type="button"
                                        class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-600 text-white rounded-lg text-xs hover:shadow-lg transition-all"
                                        onclick="applyColorPreset('#ec4899', '#ffffff', '#f472b6', '#be185d', '#be185d', true, '#ec4899', '#8b5cf6')">
                                    Purple Dream
                                </button>
                                <button type="button"
                                        class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-lg text-xs hover:shadow-lg transition-all"
                                        onclick="applyColorPreset('#0891b2', '#ffffff', '#06b6d4', '#0e7490', '#0e7490', true, '#0891b2', '#0e7490')">
                                    Ocean Blue
                                </button>
                                <button type="button"
                                        class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-lg text-xs hover:shadow-lg transition-all"
                                        onclick="applyColorPreset('#065f46', '#ffffff', '#047857', '#059669', '#059669', false)">
                                    Forest Green
                                </button>
                            </div>
                        </div>

                        <!-- Info -->
                        <div class="mt-4 p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg border border-blue-300 dark:border-blue-700">
                            <p class="text-xs text-blue-800 dark:text-blue-300">
                                💡 <strong>หมายเหตุ:</strong> หลังจากบันทึก ให้กด Ctrl+F5 (หรือ Cmd+Shift+R) เพื่อรีเฟรชหน้าและดูผลลัพธ์
                            </p>
                        </div>
                    </div>

                    </div>
                </div>

                <!-- Millennium Back Button Settings -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                    <button type="button" @click="openSections.includes('backButton') ? openSections = openSections.filter(s => s !== 'backButton') : openSections.push('backButton')" class="w-full flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="text-2xl">◀️</span> Millennium Back Button
                        </h3>
                        <svg class="w-6 h-6 text-gray-500 transition-transform" :class="openSections.includes('backButton') ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4" x-show="openSections.includes('backButton')">ปรับแต่งปุ่มย้อนกลับใน Taskbar</p>
                    <div x-show="openSections.includes('backButton')" x-collapse>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center">
                            <label class="flex items-center cursor-pointer">
                                <label class="toggle-switch"><input type="checkbox" name="millennium_back_button_enabled" value="1" {{ ($settings['millennium_back_button_enabled'] ?? true) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงปุ่มกลับ</span>
                            </label>
                        </div>
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความปุ่มกลับ</label>
                            <input type="text" name="millennium_back_button_text" value="{{ $settings['millennium_back_button_text'] ?? 'กลับ' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Millennium Center Section Settings -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">📍</span> Millennium Center Section
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">ข้อความที่แสดงในส่วนกลางของ Taskbar</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center">
                            <label class="flex items-center cursor-pointer">
                                <label class="toggle-switch"><input type="checkbox" name="millennium_center_section_enabled" value="1" {{ ($settings['millennium_center_section_enabled'] ?? true) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงส่วนกลาง</span>
                            </label>
                        </div>
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความส่วนกลาง (เว้นว่างเพื่อใช้ค่าเริ่มต้น)</label>
                            <input type="text" name="millennium_center_section_text" value="{{ $settings['millennium_center_section_text'] ?? '' }}" placeholder="เช่น: Admin Dashboard" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Category: Visual Effects -->
                <div class="relative mt-12">
                    <div class="absolute -left-4 top-0 bottom-0 w-1 bg-gradient-to-b from-purple-500 to-pink-500 rounded-full"></div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-3 pb-2 border-b-2 border-gray-200 dark:border-slate-700">
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white text-xl">
                            ✨
                        </div>
                        <span>Visual Effects & Themes</span>
                    </h2>
                </div>

                <!-- Theme Settings -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">🎨</span> Theme Settings
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">โหมดธีม</label>
                            <select name="windows_theme_mode" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="auto" {{ ($settings['windows_theme_mode'] ?? 'auto') === 'auto' ? 'selected' : '' }}>อัตโนมัติ (Auto)</option>
                                <option value="light" {{ ($settings['windows_theme_mode'] ?? 'auto') === 'light' ? 'selected' : '' }}>สว่าง (Light)</option>
                                <option value="dark" {{ ($settings['windows_theme_mode'] ?? 'auto') === 'dark' ? 'selected' : '' }}>มืด (Dark)</option>
                            </select>
                        </div>
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สี Accent</label>
                            <input type="color" name="windows_accent_color" value="{{ $settings['windows_accent_color'] ?? '#0078D4' }}" class="w-full h-10 px-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 rounded-lg">
                        </div>
                    </div>
                </div>

                <!-- Spaceship Theme -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">🚀</span> Spaceship Theme
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center">
                            <label class="flex items-center cursor-pointer">
                                <label class="toggle-switch"><input type="checkbox" name="windows_spaceship_theme" value="1" {{ ($settings['windows_spaceship_theme'] ?? true) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ธีมยานอวกาศ</span>
                            </label>
                        </div>
                        <div class="flex items-center">
                            <label class="flex items-center cursor-pointer">
                                <label class="toggle-switch"><input type="checkbox" name="windows_spaceship_stars" value="1" {{ ($settings['windows_spaceship_stars'] ?? true) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงดาวพื้นหลัง</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Content Area Width Settings -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">📏</span> ความกว้างพื้นที่ใช้งาน (Content Area Width)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
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
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">🕐</span> Clock Settings
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปแบบนาฬิกา</label>
                            <select name="millennium_clock_style" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="digital" {{ ($settings['millennium_clock_style'] ?? 'digital') === 'digital' ? 'selected' : '' }}>Digital (ดิจิทัล)</option>
                                <option value="minimal" {{ ($settings['millennium_clock_style'] ?? 'digital') === 'minimal' ? 'selected' : '' }}>Minimal (แบบย่อ)</option>
                                <option value="full" {{ ($settings['millennium_clock_style'] ?? 'digital') === 'full' ? 'selected' : '' }}>Full (แบบเต็ม)</option>
                                <option value="hidden" {{ ($settings['millennium_clock_style'] ?? 'digital') === 'hidden' ? 'selected' : '' }}>Hidden (ซ่อน)</option>
                            </select>
                        </div>
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปแบบเวลา</label>
                            <select name="millennium_clock_format" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="24h" {{ ($settings['millennium_clock_format'] ?? '24h') === '24h' ? 'selected' : '' }}>24 ชั่วโมง (13:45)</option>
                                <option value="12h" {{ ($settings['millennium_clock_format'] ?? '24h') === '12h' ? 'selected' : '' }}>12 ชั่วโมง (01:45 PM)</option>
                            </select>
                        </div>
                        <div class="flex items-center">
                            <label class="flex items-center cursor-pointer">
                                <label class="toggle-switch"><input type="checkbox" name="millennium_clock_show_seconds" value="1" {{ ($settings['millennium_clock_show_seconds'] ?? false) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงวินาที</span>
                            </label>
                        </div>
                        <div class="flex items-center">
                            <label class="flex items-center cursor-pointer">
                                <label class="toggle-switch"><input type="checkbox" name="millennium_clock_show_date" value="1" {{ ($settings['millennium_clock_show_date'] ?? false) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">แสดงวันที่</span>
                            </label>
                        </div>
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปแบบวันที่</label>
                            <select name="millennium_clock_date_format" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="short" {{ ($settings['millennium_clock_date_format'] ?? 'short') === 'short' ? 'selected' : '' }}>แบบสั้น (09/01/68)</option>
                                <option value="long" {{ ($settings['millennium_clock_date_format'] ?? 'short') === 'long' ? 'selected' : '' }}>แบบยาว (9 มกราคม 2568)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Back to Top Button Settings -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">⬆️</span> Back to Top Button (ปุ่มกลับด้านบน)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center">
                            <label class="flex items-center cursor-pointer">
                                <label class="toggle-switch"><input type="checkbox" name="millennium_back_to_top_enabled" value="1" {{ ($settings['millennium_back_to_top_enabled'] ?? true) ? 'checked' : '' }}><span class="toggle-slider"></span></label>
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">เปิดใช้ปุ่ม Back to Top</span>
                            </label>
                        </div>
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
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
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
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
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <span class="text-2xl">ℹ️</span> System Tray Info
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความไลเซ่น</label>
                            <input type="text" name="windows_license_text" value="{{ $settings['windows_license_text'] ?? 'Licensed' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-800/50 dark:to-slate-700/50 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-slate-600 transition-all duration-300 hover:shadow-md">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความลิขสิทธิ์</label>
                            <input type="text" name="windows_copyright_text" value="{{ $settings['windows_copyright_text'] ?? '© 2025 TP-Affiliate' }}" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                </div>
                <!-- End Left Column -->

                        </div>
                    </div>

                    <!-- Tab 2: Start Menu -->
                    <div x-show="activeTab === 'menu'" x-transition>
                        <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-arrows-alt-h mr-2 text-indigo-600"></i>ตำแหน่งเมนู
                                            </label>
                                            <select name="millennium_menu_position" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all">
                                                <option value="left" {{ \App\Models\WindowsUiSetting::get('millennium_menu_position', 'center') === 'left' ? 'selected' : '' }}>ซ้าย (Left)</option>
                                                <option value="center" {{ \App\Models\WindowsUiSetting::get('millennium_menu_position', 'center') === 'center' ? 'selected' : '' }}>กลาง (Center)</option>
                                                <option value="right" {{ \App\Models\WindowsUiSetting::get('millennium_menu_position', 'center') === 'right' ? 'selected' : '' }}>ขวา (Right)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-arrows-alt-h mr-2 text-indigo-600"></i>ความกว้าง
                                            </label>
                                            <div class="flex gap-2">
                                                <template x-if="widthUnit === 'px'">
                                                    <input type="number" x-model="widthValue" min="200" max="1200" class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all">
                                                </template>
                                                <template x-if="widthUnit === '%'">
                                                    <div class="flex-1 flex items-center gap-4">
                                                        <input type="range" x-model="widthValue" min="20" max="100" class="flex-1 h-3 bg-gradient-to-r from-indigo-200 to-purple-200 rounded-lg appearance-none cursor-pointer dark:from-indigo-800 dark:to-purple-800 accent-indigo-600">
                                                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 min-w-[5rem] text-center" x-text="widthValue + widthUnit"></span>
                                                    </div>
                                                </template>
                                                <select x-model="widthUnit" class="w-24 px-3 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-indigo-500">
                                                    <option value="px">px</option>
                                                    <option value="%">%</option>
                                                </select>
                                            </div>
                                            <input type="hidden" name="millennium_menu_width" x-model="widthValue">
                                            <input type="hidden" name="millennium_menu_width_unit" x-model="widthUnit">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">แนะนำ: 400-600px หรือ 30-50%</p>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-arrows-alt-v mr-2 text-indigo-600"></i>ความสูงสูงสุด
                                            </label>
                                            <div class="flex gap-2">
                                                <template x-if="heightUnit === 'px'">
                                                    <input type="number" x-model="heightValue" min="300" max="1200" class="flex-1 px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all">
                                                </template>
                                                <template x-if="heightUnit === '%' || heightUnit === 'vh'">
                                                    <div class="flex-1 flex items-center gap-4">
                                                        <input type="range" x-model="heightValue" min="50" max="100" class="flex-1 h-3 bg-gradient-to-r from-indigo-200 to-purple-200 rounded-lg appearance-none cursor-pointer dark:from-indigo-800 dark:to-purple-800 accent-indigo-600">
                                                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 min-w-[5rem] text-center" x-text="heightValue + heightUnit"></span>
                                                    </div>
                                                </template>
                                                <select x-model="heightUnit" class="w-24 px-3 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-indigo-500">
                                                    <option value="px">px</option>
                                                    <option value="%">%</option>
                                                    <option value="vh">vh</option>
                                                </select>
                                            </div>
                                            <input type="hidden" name="millennium_menu_max_height" x-model="heightValue">
                                            <input type="hidden" name="millennium_menu_max_height_unit" x-model="heightUnit">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">แนะนำ: 600px หรือ 80vh</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Additional Menu Appearance Settings -->
                                <div class="mt-6 p-6 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-700">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <i class="fas fa-paint-brush text-blue-600"></i> รูปลักษณ์เมนู
                                    </h3>
                                    <!-- Logo & Text Toggles -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                        <label class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-xl cursor-pointer border-2 border-gray-200 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-600 transition-all">
                                            <div>
                                                <span class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                                    <i class="fas fa-image text-blue-600"></i> แสดงโลโก้
                                                </span>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงโลโก้ในเมนู Start</p>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="millennium_menu_show_logo" value="1" {{ \App\Models\WindowsUiSetting::get('millennium_menu_show_logo', true) ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </label>
                                        <label class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-xl cursor-pointer border-2 border-gray-200 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-600 transition-all">
                                            <div>
                                                <span class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                                    <i class="fas fa-heading text-blue-600"></i> แสดงชื่อแอป
                                                </span>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงชื่อแอปพลิเคชัน</p>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="millennium_menu_show_app_name" value="1" {{ \App\Models\WindowsUiSetting::get('millennium_menu_show_app_name', true) ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </label>
                                        <label class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-xl cursor-pointer border-2 border-gray-200 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-600 transition-all">
                                            <div>
                                                <span class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                                    <i class="fas fa-text-height text-blue-600"></i> แสดงข้อความรอง
                                                </span>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงข้อความ subtitle</p>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="millennium_menu_show_subtitle" value="1" {{ \App\Models\WindowsUiSetting::get('millennium_menu_show_subtitle', true) ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Menu Logo Upload -->
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                                <i class="fas fa-image mr-2 text-blue-600"></i>โลโก้ในเมนู
                                            </label>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <!-- Current Logo -->
                                                @if(\App\Models\WindowsUiSetting::get('millennium_menu_logo'))
                                                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 p-4 rounded-xl border-2 border-blue-200 dark:border-blue-700">
                                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">โลโก้ปัจจุบัน:</p>
                                                    <div class="flex items-center justify-center bg-white dark:bg-slate-800 p-4 rounded-lg mb-2">
                                                        <img src="{{ asset('storage/' . \App\Models\WindowsUiSetting::get('millennium_menu_logo')) }}"
                                                             alt="Menu Logo"
                                                             class="max-h-24 max-w-full object-contain"
                                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\'%3E%3Crect fill=\'%23ddd\' width=\'100\' height=\'100\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                                                    </div>
                                                    <label class="flex items-center justify-center cursor-pointer">
                                                        <input type="checkbox" name="delete_millennium_menu_logo" value="1" class="mr-2">
                                                        <span class="text-xs text-red-600 dark:text-red-400">ลบโลโก้นี้</span>
                                                    </label>
                                                </div>
                                                @endif

                                                <!-- Upload New Logo -->
                                                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 p-4 rounded-xl border-2 border-purple-200 dark:border-purple-700">
                                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">อัพโหลดโลโก้ใหม่:</p>
                                                    <div id="menuLogoPreview" class="hidden mb-3">
                                                        <div class="flex items-center justify-center bg-white dark:bg-slate-800 p-4 rounded-lg">
                                                            <img id="menuLogoPreviewImg" src="" alt="Preview" class="max-h-24 max-w-full object-contain">
                                                        </div>
                                                        <p class="text-xs text-center text-green-600 dark:text-green-400 mt-2">✓ พร้อมบันทึก</p>
                                                    </div>
                                                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-purple-300 dark:border-purple-600 rounded-lg cursor-pointer hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-all">
                                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                            <i class="fas fa-cloud-upload-alt text-3xl text-purple-500 dark:text-purple-400 mb-2"></i>
                                                            <p class="text-xs text-gray-600 dark:text-gray-400"><span class="font-semibold">คลิกเพื่ออัพโหลด</span></p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-500">PNG, JPG, GIF (สูงสุด 2MB)</p>
                                                        </div>
                                                        <input type="file"
                                                               name="millennium_menu_logo"
                                                               id="menuLogoInput"
                                                               accept="image/*"
                                                               class="hidden"
                                                               onchange="previewImage(this, 'menuLogoPreview', 'menuLogoPreviewImg')">
                                                    </label>
                                                </div>
                                            </div>

                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                                <i class="fas fa-info-circle mr-1"></i>อัพโหลดโลโก้สำหรับแสดงในเมนู Start (ถ้าไม่อัพโหลดจะใช้โลโก้หลักของระบบ)
                                            </p>
                                        </div>
                                        <!-- Logo Size -->
                                        <div x-data="{ logoSize: {{ \App\Models\WindowsUiSetting::get('millennium_menu_logo_size', 40) }} }">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-expand-arrows-alt mr-2 text-blue-600"></i>ขนาดโลโก้
                                            </label>
                                            <div class="flex items-center gap-4">
                                                <input type="range" x-model="logoSize" min="20" max="100" class="flex-1 h-3 bg-gradient-to-r from-blue-200 to-purple-200 rounded-lg appearance-none cursor-pointer dark:from-blue-800 dark:to-purple-800 accent-blue-600">
                                                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400 min-w-[5rem] text-center" x-text="logoSize + 'px'"></span>
                                            </div>
                                            <input type="hidden" name="millennium_menu_logo_size" x-model="logoSize">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ขนาดของโลโก้ในเมนู (แนะนำ: 30-60px)</p>
                                        </div>
                                        <!-- Custom App Name -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-edit mr-2 text-blue-600"></i>ชื่อแอปที่กำหนดเอง
                                            </label>
                                            <input type="text" name="millennium_menu_app_name" value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_app_name') }}" maxlength="50" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="เว้นว่างเพื่อใช้ชื่อเริ่มต้น">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ชื่อแอปที่แสดงในเมนู (เว้นว่างใช้ชื่อระบบหลัก)</p>
                                        </div>
                                        <!-- Custom Subtitle -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-edit mr-2 text-blue-600"></i>ข้อความรองที่กำหนดเอง
                                            </label>
                                            <input type="text" name="millennium_menu_subtitle" value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_subtitle') }}" maxlength="50" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all" placeholder="เว้นว่างเพื่อใช้ '{Role} Dashboard'">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ข้อความรองในเมนู (เว้นว่างใช้ค่าเริ่มต้น)</p>
                                        </div>
                                        <!-- Menu Item Spacing -->
                                        <div x-data="{ itemSpacing: {{ \App\Models\WindowsUiSetting::get('millennium_menu_item_spacing', 8) }} }">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-arrows-alt-v mr-2 text-blue-600"></i>ระยะห่างแต่ละเมนู
                                            </label>
                                            <div class="flex items-center gap-4">
                                                <input type="range" x-model="itemSpacing" min="0" max="32" class="flex-1 h-3 bg-gradient-to-r from-blue-200 to-purple-200 rounded-lg appearance-none cursor-pointer dark:from-blue-800 dark:to-purple-800 accent-blue-600">
                                                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400 min-w-[5rem] text-center" x-text="itemSpacing + 'px'"></span>
                                            </div>
                                            <input type="hidden" name="millennium_menu_item_spacing" x-model="itemSpacing">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ระยะห่างระหว่างรายการเมนู (แนะนำ: 4-12px)</p>
                                        </div>
                                        <!-- Menu Item Padding -->
                                        <div x-data="{ menuPadding: {{ \App\Models\WindowsUiSetting::get('millennium_menu_padding', 12) }} }">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-expand-alt mr-2 text-blue-600"></i>ขนาดภายในเมนู
                                            </label>
                                            <div class="flex items-center gap-4">
                                                <input type="range" x-model="menuPadding" min="4" max="32" class="flex-1 h-3 bg-gradient-to-r from-blue-200 to-purple-200 rounded-lg appearance-none cursor-pointer dark:from-blue-800 dark:to-purple-800 accent-blue-600">
                                                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400 min-w-[5rem] text-center" x-text="menuPadding + 'px'"></span>
                                            </div>
                                            <input type="hidden" name="millennium_menu_padding" x-model="menuPadding">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ขนาด padding ภายในแต่ละรายการ (แนะนำ: 8-16px)</p>
                                        </div>
                                        <!-- Visual Preview -->
                                        <div class="flex items-center justify-center p-4 bg-white dark:bg-slate-800 rounded-lg border-2 border-gray-200 dark:border-slate-600">
                                            <div class="text-center">
                                                <div class="text-3xl mb-2">📐</div>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">ตัวอย่างจะแสดง<br>หลังบันทึก</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Menu Item Height Settings -->
                                <div class="mt-6 p-6 bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl border-2 border-purple-200 dark:border-purple-700">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <i class="fas fa-arrows-alt-v text-purple-600"></i> ความสูงของเมนู
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Main Menu Item Height -->
                                        <div x-data="{ mainHeight: {{ \App\Models\WindowsUiSetting::get('millennium_menu_item_height', 50) }} }">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-arrows-alt-v mr-2 text-purple-600"></i>ความสูงเมนูหลัก
                                            </label>
                                            <div class="flex items-center gap-4">
                                                <input type="range" x-model="mainHeight" min="30" max="100" class="flex-1 h-3 bg-gradient-to-r from-purple-200 to-pink-200 rounded-lg appearance-none cursor-pointer dark:from-purple-800 dark:to-pink-800 accent-purple-600">
                                                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400 min-w-[5rem] text-center" x-text="mainHeight + 'px'"></span>
                                            </div>
                                            <input type="hidden" name="millennium_menu_item_height" x-model="mainHeight">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">กำหนดความสูงรายการเมนูหลัก (แนะนำ: 40-70px)</p>
                                        </div>
                                        <!-- Submenu Item Height -->
                                        <div x-data="{ subHeight: {{ \App\Models\WindowsUiSetting::get('millennium_menu_subitem_height', 35) }} }">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-arrows-alt-v mr-2 text-purple-600"></i>ความสูงเมนูย่อย
                                            </label>
                                            <div class="flex items-center gap-4">
                                                <input type="range" x-model="subHeight" min="20" max="80" class="flex-1 h-3 bg-gradient-to-r from-purple-200 to-pink-200 rounded-lg appearance-none cursor-pointer dark:from-purple-800 dark:to-pink-800 accent-purple-600">
                                                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400 min-w-[5rem] text-center" x-text="subHeight + 'px'"></span>
                                            </div>
                                            <input type="hidden" name="millennium_menu_subitem_height" x-model="subHeight">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">กำหนดความสูงรายการเมนูย่อย (แนะนำ: 25-50px)</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Menu Color Settings -->
                                <div class="mt-6 p-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border-2 border-blue-200 dark:border-blue-700">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <span class="text-2xl">🎨</span> การตั้งค่าสีเมนู
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">ปรับแต่งสีของเมนู Start ให้ตรงกับธีมของคุณ</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        <!-- Background Color (Solid) -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                🎨 สีพื้นหลัง (แบบทึบ)
                                            </label>
                                            <div class="flex items-center gap-3">
                                                <input type="color"
                                                       name="millennium_menu_bg_color"
                                                       value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_bg_color', '#9333ea') }}"
                                                       class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                                <input type="text"
                                                       value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_bg_color', '#9333ea') }}"
                                                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                                       readonly>
                                            </div>
                                        </div>
                                        <!-- Text Color -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                📝 สีข้อความ
                                            </label>
                                            <div class="flex items-center gap-3">
                                                <input type="color"
                                                       name="millennium_menu_text_color"
                                                       value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_text_color', '#ffffff') }}"
                                                       class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                                <input type="text"
                                                       value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_text_color', '#ffffff') }}"
                                                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                                       readonly>
                                            </div>
                                        </div>
                                        <!-- Border Color -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                🔲 สีขอบ
                                            </label>
                                            <div class="flex items-center gap-3">
                                                <input type="color"
                                                       name="millennium_menu_border_color"
                                                       value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_border_color', '#9333ea') }}"
                                                       class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                                <input type="text"
                                                       value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_border_color', '#9333ea') }}"
                                                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                                       readonly>
                                            </div>
                                        </div>
                                        <!-- Use Gradient Toggle -->
                                        <div class="flex items-center">
                                            <label class="flex items-center cursor-pointer">
                                                <label class="toggle-switch">
                                                    <input type="checkbox"
                                                           name="millennium_menu_use_gradient"
                                                           value="1"
                                                           {{ \App\Models\WindowsUiSetting::get('millennium_menu_use_gradient', true) ? 'checked' : '' }}>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">ใช้ Gradient</span>
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Gradient Settings -->
                                    <div class="mt-6 p-4 bg-white/50 dark:bg-slate-800/50 rounded-lg border border-blue-200 dark:border-blue-700">
                                        <h5 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">การตั้งค่า Gradient (ใช้เมื่อเปิด "ใช้ Gradient")</h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <!-- Gradient From -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    🌈 สีเริ่มต้น Gradient
                                                </label>
                                                <div class="flex items-center gap-3">
                                                    <input type="color"
                                                           name="millennium_menu_gradient_from"
                                                           value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_gradient_from', '#9333ea') }}"
                                                           class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                                    <input type="text"
                                                           value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_gradient_from', '#9333ea') }}"
                                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                                           readonly>
                                                </div>
                                            </div>
                                            <!-- Gradient To -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                    🌈 สีสิ้นสุด Gradient
                                                </label>
                                                <div class="flex items-center gap-3">
                                                    <input type="color"
                                                           name="millennium_menu_gradient_to"
                                                           value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_gradient_to', '#db2777') }}"
                                                           class="w-16 h-10 rounded-lg border-2 border-gray-300 dark:border-slate-600 cursor-pointer">
                                                    <input type="text"
                                                           value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_gradient_to', '#db2777') }}"
                                                           class="flex-1 px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg text-sm font-mono"
                                                           readonly>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    </button>
                                </div>
                        </div>
                    </div>

                    <!-- Tab 3: Start Button -->
                    <div x-show="activeTab === 'startbutton'" x-transition>
                        <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-font mr-2 text-indigo-600"></i>ข้อความปุ่ม Start
                                            </label>
                                            <input type="text" name="millennium_start_button_text" value="{{ \App\Models\WindowsUiSetting::get('millennium_start_button_text', 'เริ่ม') }}" maxlength="20" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all" placeholder="เริ่ม">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ข้อความที่จะแสดงบนปุ่ม Start (สูงสุด 20 ตัวอักษร)</p>
                                        </div>
                                        <div class="space-y-3">
                                            <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-600 transition-all border-2 border-transparent hover:border-indigo-300 dark:hover:border-indigo-700">
                                                <div>
                                                    <span class="font-semibold text-gray-900 dark:text-white">🖼️ แสดงโลโก้</span>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงไอคอน/โลโก้ในปุ่ม Start</p>
                                                </div>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="millennium_start_button_show_icon" value="1" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_show_icon', true) ? 'checked' : '' }}>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </label>
                                            <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-slate-700 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-600 transition-all border-2 border-transparent hover:border-indigo-300 dark:hover:border-indigo-700">
                                                <div>
                                                    <span class="font-semibold text-gray-900 dark:text-white">📝 แสดงข้อความ</span>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงข้อความ "เริ่ม" ในปุ่ม Start</p>
                                                </div>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="millennium_start_button_show_text" value="1" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_show_text', true) ? 'checked' : '' }}>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </label>
                                        </div>
                                        <!-- Icon Settings -->
                                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border-2 border-blue-200 dark:border-blue-800">
                                            <h4 class="font-semibold text-blue-900 dark:text-blue-300 mb-3 flex items-center gap-2">
                                                <i class="fas fa-icons"></i> การตั้งค่าไอคอน
                                            </h4>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ประเภทไอคอน</label>
                                                    <select name="millennium_start_button_icon_type" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-blue-500">
                                                        <option value="default" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_icon_type', 'default') === 'default' ? 'selected' : '' }}>โลโก้เริ่มต้น</option>
                                                        <option value="upload" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_icon_type', 'default') === 'upload' ? 'selected' : '' }}>อัพโหลดรูปภาพ</option>
                                                        <option value="fontawesome" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_icon_type', 'default') === 'fontawesome' ? 'selected' : '' }}>Font Awesome Icon</option>
                                                        <option value="emoji" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_icon_type', 'default') === 'emoji' ? 'selected' : '' }}>Emoji</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                        <i class="fas fa-image mr-1"></i>อัพโหลดไอคอน
                                                    </label>

                                                    <div class="grid grid-cols-2 gap-3">
                                                        <!-- Current Icon -->
                                                        @if(\App\Models\WindowsUiSetting::get('millennium_start_button_custom_icon_path'))
                                                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-700">
                                                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ไอคอนปัจจุบัน:</p>
                                                            <div class="flex items-center justify-center bg-white dark:bg-slate-800 p-2 rounded mb-1">
                                                                <img src="{{ asset('storage/' . \App\Models\WindowsUiSetting::get('millennium_start_button_custom_icon_path')) }}"
                                                                     alt="Current Icon"
                                                                     class="h-12 w-12 object-contain"
                                                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'48\' height=\'48\'%3E%3Crect fill=\'%23ddd\' width=\'48\' height=\'48\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-size=\'10\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                                                            </div>
                                                            <label class="flex items-center justify-center cursor-pointer text-xs text-red-600 dark:text-red-400">
                                                                <input type="checkbox" name="delete_millennium_start_button_custom_icon" value="1" class="mr-1 scale-75">
                                                                ลบ
                                                            </label>
                                                        </div>
                                                        @endif

                                                        <!-- Upload New Icon -->
                                                        <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg border border-purple-200 dark:border-purple-700">
                                                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">อัพโหลดใหม่:</p>
                                                            <div id="startButtonIconPreview" class="hidden mb-2">
                                                                <div class="flex items-center justify-center bg-white dark:bg-slate-800 p-2 rounded">
                                                                    <img id="startButtonIconPreviewImg" src="" alt="Preview" class="h-12 w-12 object-contain">
                                                                </div>
                                                                <p class="text-xs text-center text-green-600 dark:text-green-400">✓ พร้อมบันทึก</p>
                                                            </div>
                                                            <label class="flex flex-col items-center justify-center h-20 border border-dashed border-purple-300 dark:border-purple-600 rounded cursor-pointer hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-all">
                                                                <i class="fas fa-upload text-lg text-purple-500 dark:text-purple-400 mb-1"></i>
                                                                <p class="text-xs text-gray-500">คลิกเพื่ออัพโหลด</p>
                                                                <input type="file"
                                                                       name="millennium_start_button_custom_icon"
                                                                       id="startButtonIconInput"
                                                                       accept="image/*"
                                                                       class="hidden"
                                                                       onchange="previewImage(this, 'startButtonIconPreview', 'startButtonIconPreviewImg')">
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">PNG, JPG, SVG (สูงสุด 2MB)</p>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Font Awesome Icon Class</label>
                                                    <input type="text" name="millennium_start_button_fontawesome_icon" value="{{ \App\Models\WindowsUiSetting::get('millennium_start_button_fontawesome_icon', 'fa-solid fa-rocket') }}" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-blue-500" placeholder="fa-solid fa-rocket">
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ตัวอย่าง: fa-solid fa-rocket, fa-brands fa-windows</p>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Emoji</label>
                                                    <input type="text" name="millennium_start_button_emoji" value="{{ \App\Models\WindowsUiSetting::get('millennium_start_button_emoji', '🚀') }}" maxlength="4" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-blue-500 text-2xl" placeholder="🚀">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตำแหน่งไอคอน</label>
                                                    <select name="millennium_start_button_icon_position" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-blue-500">
                                                        <option value="left" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_icon_position', 'left') === 'left' ? 'selected' : '' }}>ซ้าย (ก่อนข้อความ)</option>
                                                        <option value="right" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_icon_position', 'left') === 'right' ? 'selected' : '' }}>ขวา (หลังข้อความ)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Button Style Settings -->
                                        <div class="bg-gradient-to-br from-pink-50 to-rose-50 dark:from-pink-900/20 dark:to-rose-900/20 rounded-xl p-4 border-2 border-pink-200 dark:border-pink-800">
                                            <h4 class="font-semibold text-pink-900 dark:text-pink-300 mb-3 flex items-center gap-2">
                                                <i class="fas fa-palette"></i> ลักษณะปุ่ม
                                            </h4>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สไตล์ปุ่ม</label>
                                                    <select name="millennium_start_button_style" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-pink-500">
                                                        <option value="gradient" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_style', 'gradient') === 'gradient' ? 'selected' : '' }}>🌈 Gradient (ไล่สี)</option>
                                                        <option value="solid" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_style', 'gradient') === 'solid' ? 'selected' : '' }}>🎨 Solid (สีเดียว)</option>
                                                        <option value="outline" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_style', 'gradient') === 'outline' ? 'selected' : '' }}>⭕ Outline (เส้นขอบ)</option>
                                                        <option value="glass" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_style', 'gradient') === 'glass' ? 'selected' : '' }}>💎 Glass Morphism</option>
                                                        <option value="neon" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_style', 'gradient') === 'neon' ? 'selected' : '' }}>⚡ Neon (เรืองแสง)</option>
                                                        <option value="3d" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_style', 'gradient') === '3d' ? 'selected' : '' }}>🎭 3D Effect</option>
                                                    </select>
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สีหลัก</label>
                                                        <input type="color" name="millennium_start_button_color_primary" value="{{ \App\Models\WindowsUiSetting::get('millennium_start_button_color_primary', '#ec4899') }}" class="w-full h-10 border-2 border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สีรอง</label>
                                                        <input type="color" name="millennium_start_button_color_secondary" value="{{ \App\Models\WindowsUiSetting::get('millennium_start_button_color_secondary', '#3b82f6') }}" class="w-full h-10 border-2 border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สีข้อความ</label>
                                                    <input type="color" name="millennium_start_button_text_color" value="{{ \App\Models\WindowsUiSetting::get('millennium_start_button_text_color', '#ffffff') }}" class="w-full h-10 border-2 border-gray-300 dark:border-slate-600 rounded-lg cursor-pointer">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <!-- Start Button Tooltip Settings -->
                                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-4 border-2 border-purple-200 dark:border-purple-800">
                                            <h4 class="font-semibold text-purple-900 dark:text-purple-300 mb-3 flex items-center gap-2">
                                                <i class="fas fa-comment-dots"></i> กล่องคำพูดต้อนรับ (Tooltip)
                                            </h4>
                                            <label class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-lg cursor-pointer mb-3">
                                                <div>
                                                    <span class="font-medium text-gray-900 dark:text-white">เปิดใช้กล่องคำพูด</span>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงกล่องคำพูดเมื่อเข้ามาครั้งแรก</p>
                                                </div>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="millennium_start_button_tooltip_enabled" value="1" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_enabled', true) ? 'checked' : '' }}>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </label>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความในกล่องคำพูด</label>
                                                    <textarea name="millennium_start_button_tooltip_text" rows="2" maxlength="100" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-purple-500" placeholder="คลิกที่นี่เพื่อเริ่มต้น! 🚀">{{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_text', 'คลิกที่นี่เพื่อเริ่มต้น! 🚀') }}</textarea>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ข้อความที่จะแสดงในกล่องคำพูด (สูงสุด 100 ตัวอักษร)</p>
                                                </div>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ระยะเวลาแสดง (วินาที)</label>
                                                        <input type="number" name="millennium_start_button_tooltip_duration" min="3" max="30" value="{{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_duration', 8) }}" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-purple-500">
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">3-30 วินาที</p>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตำแหน่ง</label>
                                                        <select name="millennium_start_button_tooltip_position" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-purple-500">
                                                            <option value="top" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_position', 'top') === 'top' ? 'selected' : '' }}>ด้านบน</option>
                                                            <option value="bottom" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_position', 'top') === 'bottom' ? 'selected' : '' }}>ด้านล่าง</option>
                                                            <option value="left" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_position', 'top') === 'left' ? 'selected' : '' }}>ซ้าย</option>
                                                            <option value="right" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_position', 'top') === 'right' ? 'selected' : '' }}>ขวา</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">แอนิเมชั่น</label>
                                                    <select name="millennium_start_button_tooltip_animation" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-purple-500">
                                                        <option value="bounce" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_animation', 'bounce') === 'bounce' ? 'selected' : '' }}>🎪 Bounce (เด้ง)</option>
                                                        <option value="pulse" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_animation', 'bounce') === 'pulse' ? 'selected' : '' }}>💓 Pulse (เต้น)</option>
                                                        <option value="shake" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_animation', 'bounce') === 'shake' ? 'selected' : '' }}>🔔 Shake (สั่น)</option>
                                                        <option value="swing" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_animation', 'bounce') === 'swing' ? 'selected' : '' }}>🎭 Swing (แกว่ง)</option>
                                                        <option value="tada" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_animation', 'bounce') === 'tada' ? 'selected' : '' }}>🎉 Tada (ปรบมือ)</option>
                                                        <option value="flash" {{ \App\Models\WindowsUiSetting::get('millennium_start_button_tooltip_animation', 'bounce') === 'flash' ? 'selected' : '' }}>⚡ Flash (กระพริบ)</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Responsive Mode Settings -->
                                        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 border-2 border-indigo-200 dark:border-indigo-800">
                                            <h4 class="font-semibold text-indigo-900 dark:text-indigo-300 mb-3 flex items-center gap-2">
                                                <i class="fas fa-mobile-alt"></i> Responsive Mode
                                            </h4>
                                            <label class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-lg cursor-pointer mb-3">
                                                <div>
                                                    <span class="font-medium text-gray-900 dark:text-white">เปิดใช้ Responsive</span>
                                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">รวมไอคอนเป็นปุ่มเมื่อหน้าจอเล็ก</p>
                                                </div>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="millennium_taskbar_collapse_enabled" value="1" {{ \App\Models\WindowsUiSetting::get('millennium_taskbar_collapse_enabled', true) ? 'checked' : '' }}>
                                                    <span class="toggle-slider"></span>
                                                </label>
                                            </label>
                                            <div class="mb-3">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Breakpoint (px)</label>
                                                <input type="number" name="millennium_taskbar_collapse_breakpoint" min="320" max="1920" value="{{ \App\Models\WindowsUiSetting::get('millennium_taskbar_collapse_breakpoint', 768) }}" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-indigo-500">
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">หน้าจอที่แคบกว่านี้จะแสดงโหมด Responsive</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">รูปแบบเมนู</label>
                                                <select name="millennium_taskbar_collapse_style" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-indigo-500">
                                                    <option value="dropdown" {{ \App\Models\WindowsUiSetting::get('millennium_taskbar_collapse_style', 'slide-up') === 'dropdown' ? 'selected' : '' }}>Dropdown (เล็กๆ)</option>
                                                    <option value="slide-up" {{ \App\Models\WindowsUiSetting::get('millennium_taskbar_collapse_style', 'slide-up') === 'slide-up' ? 'selected' : '' }}>Slide Up (ยืดขึ้นเต็มความกว้าง)</option>
                                                    <option value="fullscreen" {{ \App\Models\WindowsUiSetting::get('millennium_taskbar_collapse_style', 'slide-up') === 'fullscreen' ? 'selected' : '' }}>Fullscreen (เต็มจอ)</option>
                                                </select>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เลือกรูปแบบการแสดงเมนูบนมือถือ</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div>

                    <!-- Tab 4: RGB Effects -->
                    <div x-show="activeTab === 'menurgb'" x-transition>
                        <div class="space-y-6">
                                <!-- Category Header -->
                                <div class="bg-gradient-to-r from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-xl p-4 mb-6 border-2 border-purple-300 dark:border-purple-700">
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        <span class="text-2xl">🌈</span>
                                        <span>การตั้งค่า RGB Effects ทั้งหมด</span>
                                    </h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                        จัดการเอฟเฟกต์ RGB สำหรับ Taskbar, Windows และ Start Menu ในที่เดียว
                                    </p>
                                </div>

                                <!-- Section 1: Taskbar RGB Border -->
                                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-700">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <span class="text-xl">🖥️</span> Taskbar RGB Border
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">แถบสีสันที่วิ่งตามขอบ Taskbar</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <label class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-xl cursor-pointer border-2 border-gray-200 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-600 transition-all">
                                            <div>
                                                <span class="font-semibold text-gray-900 dark:text-white">เปิดใช้ RGB Border</span>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงเส้นขอบสีสันรอบ Taskbar</p>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="millennium_rgb_enabled" value="1" {{ ($settings['millennium_rgb_enabled'] ?? true) ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </label>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-tachometer-alt mr-2 text-blue-600"></i>ความเร็ว (วินาที)
                                            </label>
                                            <input type="number" name="millennium_rgb_speed" min="1" max="10" value="{{ $settings['millennium_rgb_speed'] ?? 5 }}" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-blue-500">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ความเร็วในการเปลี่ยนสี (แนะนำ: 3-7)</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 2: Windows RGB Animation -->
                                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-xl p-6 border-2 border-indigo-200 dark:border-indigo-700">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <span class="text-xl">✨</span> Windows RGB Animation
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">เอฟเฟกต์แสงสีที่เปลี่ยนสีตลอดเวลาทั่วทั้งระบบ</p>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <label class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-xl cursor-pointer border-2 border-gray-200 dark:border-slate-600 hover:border-indigo-400 dark:hover:border-indigo-600 transition-all">
                                            <div>
                                                <span class="font-semibold text-gray-900 dark:text-white">เปิดใช้ RGB</span>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงเอฟเฟกต์ RGB</p>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="windows_rgb_enabled" value="1" {{ ($settings['windows_rgb_enabled'] ?? true) ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </label>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-tachometer-alt mr-2 text-indigo-600"></i>ความเร็ว (วินาที)
                                            </label>
                                            <input type="number" name="windows_rgb_speed" min="1" max="10" value="{{ $settings['windows_rgb_speed'] ?? 3 }}" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-indigo-500">
                                        </div>
                                        <label class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-xl cursor-pointer border-2 border-gray-200 dark:border-slate-600 hover:border-indigo-400 dark:hover:border-indigo-600 transition-all">
                                            <div>
                                                <span class="font-semibold text-gray-900 dark:text-white">Glow Effect</span>
                                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสงเรืองรอบ RGB</p>
                                            </div>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="windows_rgb_glow" value="1" {{ ($settings['windows_rgb_glow'] ?? true) ? 'checked' : '' }}>
                                                <span class="toggle-slider"></span>
                                            </label>
                                        </label>
                                    </div>
                                </div>

                                <!-- Section 3: Menu RGB Effects -->
                                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border-2 border-purple-200 dark:border-purple-700">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <span class="text-xl">📱</span> Start Menu RGB Effects
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">เอฟเฟกต์ RGB สำหรับ Start Menu และรายการเมนู</p>

                                    <!-- RGB Toggle Switches -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <label class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl cursor-pointer border-2 border-purple-200 dark:border-purple-800 hover:border-purple-400 dark:hover:border-purple-600 transition-all">
                                        <div>
                                            <span class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                                <span class="text-xl">✨</span> RGB Border (เมนูหลัก)
                                            </span>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงเอฟเฟค RGB วิ่งรอบเมนู Start</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="millennium_menu_rgb_enabled" value="1" {{ \App\Models\WindowsUiSetting::get('millennium_menu_rgb_enabled', true) ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                    <label class="flex items-center justify-between p-4 bg-gradient-to-r from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-xl cursor-pointer border-2 border-cyan-200 dark:border-cyan-800 hover:border-cyan-400 dark:hover:border-cyan-600 transition-all">
                                        <div>
                                            <span class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                                <span class="text-xl">🎯</span> RGB Hover (รายการเมนู)
                                            </span>
                                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงเอฟเฟค RGB เมื่อเมาส์ชี้รายการเมนู</p>
                                        </div>
                                        <label class="toggle-switch">
                                            <input type="checkbox" name="millennium_menu_item_hover_rgb" value="1" {{ \App\Models\WindowsUiSetting::get('millennium_menu_item_hover_rgb', true) ? 'checked' : '' }}>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </label>
                                </div>
                                <!-- RGB Settings -->
                                <div class="bg-gray-50 dark:bg-slate-900/50 rounded-xl p-6 border border-gray-200 dark:border-slate-700 mb-6">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                        <i class="fas fa-sliders-h text-purple-600"></i> การตั้งค่า RGB
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div x-data="{ rgbSpeed: {{ \App\Models\WindowsUiSetting::get('millennium_menu_rgb_speed', 5) }} }">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-tachometer-alt mr-2 text-purple-600"></i>ความเร็ว RGB
                                            </label>
                                            <div class="flex items-center gap-4">
                                                <input type="range" x-model="rgbSpeed" min="1" max="20" class="flex-1 h-3 bg-gradient-to-r from-purple-200 to-pink-200 rounded-lg appearance-none cursor-pointer dark:from-purple-800 dark:to-pink-800 accent-purple-600">
                                                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400 min-w-[5rem] text-center" x-text="rgbSpeed + 's'"></span>
                                            </div>
                                            <input type="hidden" name="millennium_menu_rgb_speed" x-model="rgbSpeed">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ความเร็วในการเปลี่ยนสี (แนะนำ: 3-7)</p>
                                        </div>
                                        <div x-data="{ borderWidth: {{ \App\Models\WindowsUiSetting::get('millennium_menu_rgb_border_width', 2) }} }">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-border-style mr-2 text-purple-600"></i>ความหนาขอบ RGB
                                            </label>
                                            <div class="flex items-center gap-4">
                                                <input type="range" x-model="borderWidth" min="1" max="10" class="flex-1 h-3 bg-gradient-to-r from-purple-200 to-pink-200 rounded-lg appearance-none cursor-pointer dark:from-purple-800 dark:to-pink-800 accent-purple-600">
                                                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400 min-w-[5rem] text-center" x-text="borderWidth + 'px'"></span>
                                            </div>
                                            <input type="hidden" name="millennium_menu_rgb_border_width" x-model="borderWidth">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ความหนาของเส้นขอบ RGB (แนะนำ: 2-4)</p>
                                        </div>
                                        <div x-data="{ glowSize: {{ \App\Models\WindowsUiSetting::get('millennium_menu_rgb_glow_size', 15) }} }">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                                <i class="fas fa-sun mr-2 text-purple-600"></i>ขนาด Glow RGB
                                            </label>
                                            <div class="flex items-center gap-4">
                                                <input type="range" x-model="glowSize" min="0" max="50" class="flex-1 h-3 bg-gradient-to-r from-purple-200 to-pink-200 rounded-lg appearance-none cursor-pointer dark:from-purple-800 dark:to-pink-800 accent-purple-600">
                                                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400 min-w-[5rem] text-center" x-text="glowSize + 'px'"></span>
                                            </div>
                                            <input type="hidden" name="millennium_menu_rgb_glow_size" x-model="glowSize">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ขนาดของแสงเรืองรอบขอบ (แนะนำ: 10-20)</p>
                                        </div>
                                    </div>
                                    </div>
                                </div>

                                <!-- Link to Advanced RGB Settings -->
                                <div class="text-center">
                                    <a href="{{ route('admin.windows-ui.rgb-settings') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl transition-all shadow-lg hover:shadow-xl font-semibold">
                                        <i class="fas fa-cog"></i>
                                        <span>ตั้งค่า RGB ขั้นสูง</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ปรับแต่งสี RGB แบบละเอียด และตั้งค่าขั้นสูงเพิ่มเติม</p>
                                </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <!-- Fixed Sticky Save Button -->
    <button type="button" onclick="document.getElementById('main-settings-form').submit()" 
            class="fixed bottom-8 right-8 z-50 group">
        <div class="relative">
            <!-- Main Button -->
            <div class="px-8 py-4 bg-gradient-to-r from-blue-600 to-cyan-600 dark:from-blue-500 dark:to-cyan-500 
                        hover:from-blue-700 hover:to-cyan-700 dark:hover:from-blue-600 dark:hover:to-cyan-600 
                        text-white rounded-2xl transition-all duration-300 shadow-2xl 
                        hover:shadow-[0_0_40px_rgba(59,130,246,0.6)] 
                        transform hover:-translate-y-2 hover:scale-105 
                        flex items-center gap-3 font-bold text-lg border-2 border-blue-400 dark:border-blue-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                </svg>
                <span>บันทึกการตั้งค่า</span>
            </div>
            
            <!-- Floating animation effect -->
            <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-cyan-400 rounded-2xl blur-xl opacity-30 group-hover:opacity-50 transition-opacity duration-300 -z-10"></div>
        </div>
        
        <!-- Helper text -->
        <div class="text-xs text-gray-600 dark:text-gray-400 mt-2 text-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
            คลิกเพื่อบันทึกทั้งหมด
        </div>
    </button>

</div>

@push('scripts')
<script>
    // Unified Alpine.js data manager
    function unifiedSettingsManager() {
        return {
            activeTab: 'taskbar',
            openSections: ['taskbar', 'backButton', 'centerSection', 'rgbBorder', 'rgb', 'theme', 'spaceship', 'contentWidth', 'clock', 'backToTop', 'systemTray'],
            widthValue: {{ \App\Models\WindowsUiSetting::get('millennium_menu_width', '400') }},
            widthUnit: '{{ \App\Models\WindowsUiSetting::get('millennium_menu_width_unit', 'px') }}',
            heightValue: {{ \App\Models\WindowsUiSetting::get('millennium_menu_max_height', '600') }},
            heightUnit: '{{ \App\Models\WindowsUiSetting::get('millennium_menu_max_height_unit', 'px') }}'
        };
    }

    // Image preview function
    function previewImage(input, previewContainerId, previewImgId) {
        const file = input.files[0];
        const previewContainer = document.getElementById(previewContainerId);
        const previewImg = document.getElementById(previewImgId);

        if (file) {
            // Check file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('ไฟล์ใหญ่เกินไป! กรุณาเลือกไฟล์ที่มีขนาดไม่เกิน 2MB');
                input.value = '';
                previewContainer.classList.add('hidden');
                return;
            }

            // Check file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('ประเภทไฟล์ไม่ถูกต้อง! กรุณาเลือกไฟล์รูปภาพ (JPG, PNG, GIF, SVG, WEBP)');
                input.value = '';
                previewContainer.classList.add('hidden');
                return;
            }

            // Preview the image
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        } else {
            previewContainer.classList.add('hidden');
        }
    }

    // Toggle custom width input visibility
    document.addEventListener('DOMContentLoaded', function() {
        const contentWidthMode = document.getElementById('content_width_mode');
        if (contentWidthMode) {
            contentWidthMode.addEventListener('change', function() {
                const customWidthWrapper = document.getElementById('custom_width_wrapper');
                if (this.value === 'custom') {
                    customWidthWrapper.style.display = 'block';
                } else {
                    customWidthWrapper.style.display = 'none';
                }
            });
        }
    });

    // Apply color preset function for Taskbar colors
    function applyColorPreset(bgColor, textColor, hoverColor, activeColor, borderColor, useGradient, gradientFrom, gradientTo) {
        // Set background color
        document.querySelector('input[name="windows_taskbar_bg_color"]').value = bgColor;
        document.querySelector('input[name="windows_taskbar_bg_color"]').nextElementSibling.value = bgColor;

        // Set text color
        document.querySelector('input[name="windows_taskbar_text_color"]').value = textColor;
        document.querySelector('input[name="windows_taskbar_text_color"]').nextElementSibling.value = textColor;

        // Set hover color
        document.querySelector('input[name="windows_taskbar_hover_bg_color"]').value = hoverColor;
        document.querySelector('input[name="windows_taskbar_hover_bg_color"]').nextElementSibling.value = hoverColor;

        // Set active color
        document.querySelector('input[name="windows_taskbar_active_bg_color"]').value = activeColor;
        document.querySelector('input[name="windows_taskbar_active_bg_color"]').nextElementSibling.value = activeColor;

        // Set border color
        document.querySelector('input[name="windows_taskbar_border_color"]').value = borderColor;
        document.querySelector('input[name="windows_taskbar_border_color"]').nextElementSibling.value = borderColor;

        // Set gradient toggle
        const gradientToggle = document.querySelector('input[name="windows_taskbar_use_gradient"]');
        gradientToggle.checked = useGradient;

        // Set gradient colors if provided
        if (useGradient && gradientFrom && gradientTo) {
            document.querySelector('input[name="windows_taskbar_gradient_from"]').value = gradientFrom;
            document.querySelector('input[name="windows_taskbar_gradient_from"]').nextElementSibling.value = gradientFrom;

            document.querySelector('input[name="windows_taskbar_gradient_to"]').value = gradientTo;
            document.querySelector('input[name="windows_taskbar_gradient_to"]').nextElementSibling.value = gradientTo;
        }

        // Show success message
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all';
        notification.textContent = '✅ ธีมสีถูกใช้แล้ว! อย่าลืมกดบันทึกการตั้งค่า';
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Update color text inputs when color picker changes
    document.addEventListener('DOMContentLoaded', function() {
        const colorInputs = document.querySelectorAll('input[type="color"]');
        colorInputs.forEach(input => {
            input.addEventListener('input', function() {
                const textInput = this.nextElementSibling;
                if (textInput && textInput.type === 'text') {
                    textInput.value = this.value;
                }
            });
        });
    });

    // Alpine.js data for menu settings manager
</script>
@endpush
@endsection
