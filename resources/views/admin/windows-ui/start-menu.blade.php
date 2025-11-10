@extends('layouts.admin')

@section('title', 'จัดการ Start Menu')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.css">
<style>
    .sortable-ghost {
        opacity: 0.4;
        background: #f3f4f6;
    }
    .menu-item-card {
        transition: all 0.2s;
    }
    .menu-item-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

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
<div class="container-fluid px-4 py-6" x-data="startMenuManager()">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 via-purple-600 to-pink-600 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-3xl">
                        🚀
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">จัดการ Start Menu</h1>
                        <p class="text-white/90">จัดการรายการเมนู Start และการตั้งค่าต่างๆ แบบครบวงจร</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.windows-ui.index') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white rounded-xl transition-all">
                ← กลับ
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Role Tabs -->
    <div class="mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-2">
            <div class="flex gap-2">
                <a href="{{ route('admin.windows-ui.start-menu', ['role' => 'admin']) }}"
                   class="flex-1 px-4 py-3 text-center rounded-xl transition-all {{ $role === 'admin' ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600' }}">
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-2xl">👑</span>
                        <span class="font-semibold">Admin Menu</span>
                    </div>
                </a>
                <a href="{{ route('admin.windows-ui.start-menu', ['role' => 'seller']) }}"
                   class="flex-1 px-4 py-3 text-center rounded-xl transition-all {{ $role === 'seller' ? 'bg-gradient-to-r from-blue-600 to-cyan-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600' }}">
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-2xl">🏪</span>
                        <span class="font-semibold">Seller Menu</span>
                    </div>
                </a>
                <a href="{{ route('admin.windows-ui.start-menu', ['role' => 'user']) }}"
                   class="flex-1 px-4 py-3 text-center rounded-xl transition-all {{ $role === 'user' ? 'bg-gradient-to-r from-green-600 to-emerald-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-600' }}">
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-2xl">👤</span>
                        <span class="font-semibold">User Menu</span>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Menu Items Manager -->
    <div class="mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-slate-700 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/50 dark:to-purple-950/50">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">📋 รายการเมนู</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">จัดการรายการเมนู ไอคอน และ URL สำหรับแต่ละบทบาท</p>
                    </div>
                    <button @click="addMenuItem()" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                        <i class="fas fa-plus mr-2"></i>เพิ่มรายการใหม่
                    </button>
                </div>
            </div>

            <div class="p-6">
                <form method="POST" action="{{ route('admin.windows-ui.start-menu.update') }}" @submit="prepareSubmit">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="role" value="{{ $role }}">

                    <div id="menu-items-list" class="space-y-3 mb-6">
                        <template x-for="(item, index) in menuItems" :key="index">
                            <div class="menu-item-card bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-600 rounded-xl p-5 border-2 border-gray-200 dark:border-slate-600 hover:border-indigo-400 dark:hover:border-indigo-500 transition-all">
                                <div class="flex items-start gap-4">
                                    <!-- Drag Handle -->
                                    <div class="cursor-move text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 pt-2 transition-colors">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                        </svg>
                                    </div>

                                    <!-- Form Fields -->
                                    <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div x-data="{ showIconPicker: false, popularEmojis: ['📊', '👥', '🛒', '📦', '💰', '📈', '⚙️', '🏠', '🔔', '📧', '💼', '🎁', '⭐', '🏪', '🚀', '🎨', '🎓', '🔧', '📱', '💳', '🌐', '🔮', '🤖', '📝', '🔒', '🏨', '✨', '💡', '🎯', '🌈'] }">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ไอคอน</label>
                                            <div class="relative">
                                                <div class="flex gap-2">
                                                    <div class="flex items-center justify-center w-12 h-12 bg-white dark:bg-slate-800 border-2 border-indigo-300 dark:border-indigo-600 rounded-lg text-2xl shadow-sm">
                                                        <span x-text="item.icon || '📄'"></span>
                                                    </div>
                                                    <input
                                                        type="text"
                                                        x-model="item.icon"
                                                        @focus="showIconPicker = true"
                                                        class="flex-1 px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all"
                                                        placeholder="🏠 หรือพิมพ์ emoji">
                                                    <button
                                                        type="button"
                                                        @click="showIconPicker = !showIconPicker"
                                                        class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all shadow-md">
                                                        😀
                                                    </button>
                                                </div>

                                                <!-- Quick Emoji Picker -->
                                                <div
                                                    x-show="showIconPicker"
                                                    @click.outside="showIconPicker = false"
                                                    x-transition
                                                    class="absolute z-50 mt-2 p-4 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border-2 border-indigo-300 dark:border-indigo-700 w-full max-w-md">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">เลือก Emoji ยอดนิยม</span>
                                                        <button
                                                            type="button"
                                                            @click="showIconPicker = false"
                                                            class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
                                                    </div>
                                                    <div class="grid grid-cols-10 gap-1">
                                                        <template x-for="emoji in popularEmojis" :key="emoji">
                                                            <button
                                                                type="button"
                                                                @click="item.icon = emoji; showIconPicker = false"
                                                                class="p-2 text-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition-colors"
                                                                x-text="emoji"></button>
                                                        </template>
                                                    </div>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                                                        💡 หรือคัดลอก emoji จาก <a href="https://emojipedia.org" target="_blank" class="text-indigo-600 hover:underline font-medium">Emojipedia</a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ชื่อเมนู</label>
                                            <input type="text" x-model="item.label" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all" placeholder="หน้าแรก" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">URL/Route</label>
                                            <input type="text" x-model="item.url" class="w-full px-4 py-2 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all" placeholder="/admin/dashboard" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="flex items-center cursor-pointer p-3 bg-white dark:bg-slate-700 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
                                                <input type="checkbox" x-model="item.has_submenu" class="rounded border-gray-300 text-indigo-600 focus:ring-2 focus:ring-indigo-200">
                                                <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">มี Submenu</span>
                                            </label>
                                        </div>

                                        <!-- Submenu Items -->
                                        <div x-show="item.has_submenu" class="md:col-span-2 pl-6 border-l-4 border-indigo-300 dark:border-indigo-700">
                                            <div class="mb-3 flex items-center justify-between">
                                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">รายการ Submenu</span>
                                                <button type="button" @click="addSubmenuItem(index)" class="text-sm px-4 py-2 bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-800 transition-all">
                                                    <i class="fas fa-plus mr-1"></i> เพิ่ม Submenu
                                                </button>
                                            </div>
                                            <div class="space-y-2">
                                                <template x-for="(subitem, subindex) in item.submenu" :key="subindex">
                                                    <div class="flex gap-2">
                                                        <input type="text" x-model="subitem.label" class="flex-1 px-3 py-2 text-sm border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-indigo-500" placeholder="ชื่อ Submenu">
                                                        <input type="text" x-model="subitem.url" class="flex-1 px-3 py-2 text-sm border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg focus:border-indigo-500" placeholder="URL">
                                                        <button type="button" @click="removeSubmenuItem(index, subindex)" class="px-3 py-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-all">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-col gap-2">
                                        <button type="button" @click="removeMenuItem(index)" class="p-2 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="menuItems.length === 0" class="text-center py-12">
                        <div class="text-6xl mb-4">📭</div>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">ยังไม่มีรายการเมนู</p>
                        <button type="button" @click="addMenuItem()" class="px-6 py-3 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-all">
                            เพิ่มรายการแรก
                        </button>
                    </div>

                    <!-- Hidden inputs for menu items -->
                    <template x-for="(item, index) in menuItems" :key="index">
                        <div>
                            <input type="hidden" :name="`items[${index}][icon]`" x-model="item.icon">
                            <input type="hidden" :name="`items[${index}][label]`" x-model="item.label">
                            <input type="hidden" :name="`items[${index}][url]`" x-model="item.url">
                            <input type="hidden" :name="`items[${index}][order]`" x-model="item.order">
                        </div>
                    </template>

                    <div class="flex justify-between items-center">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>เคล็ดลับ:</strong> ลากรายการด้วยไอคอน <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg> เพื่อเรียงลำดับใหม่
                            </p>
                        </div>
                        <button type="submit" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                            <i class="fas fa-save mr-2"></i>บันทึกรายการเมนู
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Settings Panel with Tabs -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-slate-700 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-950/50 dark:to-pink-950/50">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">⚙️ การตั้งค่าเมนู</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">ปรับแต่งขนาด ตำแหน่ง และเอฟเฟกต์ของ Start Menu</p>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-slate-700">
            <div class="flex p-2 gap-2 bg-gray-50 dark:bg-slate-900">
                <button @click="activeTab = 'menu'" :class="activeTab === 'menu' ? 'active' : ''" class="settings-tab flex-1 px-6 py-3 rounded-xl text-sm font-semibold bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-align-justify mr-2"></i>ตำแหน่งและขนาดเมนู
                </button>
                <button @click="activeTab = 'taskbar'" :class="activeTab === 'taskbar' ? 'active' : ''" class="settings-tab flex-1 px-6 py-3 rounded-xl text-sm font-semibold bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-window-maximize mr-2"></i>Taskbar & ปุ่ม Start
                </button>
                <button @click="activeTab = 'rgb'" :class="activeTab === 'rgb' ? 'active' : ''" class="settings-tab flex-1 px-6 py-3 rounded-xl text-sm font-semibold bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300">
                    <i class="fas fa-palette mr-2"></i>RGB Effects
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Menu Position & Size Tab -->
            <div x-show="activeTab === 'menu'">
                <form method="POST" action="{{ route('admin.windows-ui.menu-settings.update') }}">
                    @csrf
                    @method('PUT')

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
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">แนะนำ: 600-800px หรือ 70-90vh</p>
                            </div>

                            <div class="pt-2">
                                <label class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl cursor-pointer border-2 border-purple-200 dark:border-purple-800 hover:border-purple-400 dark:hover:border-purple-600 transition-all">
                                    <div>
                                        <span class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                            <span class="text-xl">🌈</span> RGB Border Effect
                                        </span>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">แสดงเอฟเฟค RGB วิ่งรอบเมนู</p>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="millennium_menu_rgb_enabled" value="1" {{ \App\Models\WindowsUiSetting::get('millennium_menu_rgb_enabled', true) ? 'checked' : '' }}>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                            <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                        </button>
                    </div>
                </form>
            </div>

            <!-- Taskbar & Start Button Tab -->
            <div x-show="activeTab === 'taskbar'">
                <form method="POST" action="{{ route('admin.windows-ui.start-button-settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-map-marker-alt mr-2 text-indigo-600"></i>ตำแหน่งปุ่ม Start
                                </label>
                                <select name="windows_start_button_position" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition-all">
                                    <option value="left" {{ \App\Models\WindowsUiSetting::get('windows_start_button_position', 'center') === 'left' ? 'selected' : '' }}>ซ้าย (Left)</option>
                                    <option value="center" {{ \App\Models\WindowsUiSetting::get('windows_start_button_position', 'center') === 'center' ? 'selected' : '' }}>กลาง (Center)</option>
                                    <option value="right" {{ \App\Models\WindowsUiSetting::get('windows_start_button_position', 'center') === 'right' ? 'selected' : '' }}>ขวา (Right)</option>
                                </select>
                            </div>

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
                        </div>

                        <div class="space-y-4">
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

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl">
                            <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
                        </button>
                    </div>
                </form>
            </div>

            <!-- RGB Effects Tab -->
            <div x-show="activeTab === 'rgb'">
                <form method="POST" action="{{ route('admin.windows-ui.menu-rgb-settings.update') }}">
                    @csrf
                    @method('PUT')

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
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-tachometer-alt mr-2 text-purple-600"></i>ความเร็ว (วินาที)
                                </label>
                                <input type="number" name="millennium_menu_rgb_speed" min="1" max="20" value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_rgb_speed', 5) }}" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ความเร็วในการเปลี่ยนสี (แนะนำ: 3-7)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-border-style mr-2 text-purple-600"></i>ความหนาขอบ (px)
                                </label>
                                <input type="number" name="millennium_menu_rgb_border_width" min="1" max="10" value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_rgb_border_width', 2) }}" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ความหนาของเส้นขอบ RGB (แนะนำ: 2-4)</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    <i class="fas fa-sun mr-2 text-purple-600"></i>ขนาด Glow (px)
                                </label>
                                <input type="number" name="millennium_menu_rgb_glow_size" min="0" max="50" value="{{ \App\Models\WindowsUiSetting::get('millennium_menu_rgb_glow_size', 15) }}" class="w-full px-4 py-3 border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl focus:border-purple-500 focus:ring-2 focus:ring-purple-200 transition-all">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">ขนาดของแสงเรืองรอบขอบ (แนะนำ: 10-20)</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between items-center">
                        <a href="{{ route('admin.windows-ui.rgb-settings') }}" class="px-6 py-3 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-all">
                            <i class="fas fa-cog mr-2"></i>ตั้งค่า RGB เพิ่มเติม
                        </a>
                        <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl hover:from-purple-700 hover:to-pink-700 transition-all shadow-lg hover:shadow-xl">
                            <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า RGB
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
function startMenuManager() {
    return {
        menuItems: @json($items ?? []),
        activeTab: 'menu',

        // Menu size settings
        widthValue: {{ \App\Models\WindowsUiSetting::get('millennium_menu_width', '400') }},
        widthUnit: '{{ \App\Models\WindowsUiSetting::get('millennium_menu_width_unit', 'px') }}',
        heightValue: {{ \App\Models\WindowsUiSetting::get('millennium_menu_max_height', '600') }},
        heightUnit: '{{ \App\Models\WindowsUiSetting::get('millennium_menu_max_height_unit', 'px') }}',

        init() {
            // Initialize Sortable.js
            const el = document.getElementById('menu-items-list');
            if (el) {
                Sortable.create(el, {
                    animation: 200,
                    handle: '.cursor-move',
                    ghostClass: 'sortable-ghost',
                    onEnd: () => {
                        this.updateOrder();
                    }
                });
            }
        },

        addMenuItem() {
            this.menuItems.push({
                icon: '📄',
                label: 'รายการใหม่',
                url: '/admin/dashboard',
                has_submenu: false,
                submenu: [],
                order: this.menuItems.length
            });
        },

        removeMenuItem(index) {
            if (confirm('คุณต้องการลบรายการนี้ใช่หรือไม่?')) {
                this.menuItems.splice(index, 1);
                this.updateOrder();
            }
        },

        addSubmenuItem(parentIndex) {
            if (!this.menuItems[parentIndex].submenu) {
                this.menuItems[parentIndex].submenu = [];
            }
            this.menuItems[parentIndex].submenu.push({
                label: 'Submenu ใหม่',
                url: '/admin/dashboard'
            });
        },

        removeSubmenuItem(parentIndex, subIndex) {
            this.menuItems[parentIndex].submenu.splice(subIndex, 1);
        },

        updateOrder() {
            this.menuItems.forEach((item, index) => {
                item.order = index;
            });
        },

        prepareSubmit(e) {
            // Update order before submit
            this.updateOrder();
        },

        get menuItemsJson() {
            return JSON.stringify(this.menuItems);
        }
    }
}
</script>
@endpush
@endsection
