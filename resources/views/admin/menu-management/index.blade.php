@extends('layouts.admin-v3')

@section('title', $pageTitle)

@section('styles')
<style>
    /* Sortable ghost และ drag styles */
    .sortable-ghost {
        opacity: 0.4;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 0.5rem;
    }
    .sortable-drag {
        opacity: 1;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        transform: scale(1.02);
    }
    .sortable-chosen {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }

    /* Menu item hover effect */
    .menu-item-row:hover {
        transform: translateX(4px);
        transition: transform 0.2s ease;
    }

    /* Submenu indentation */
    .submenu-level-1 { padding-left: 2rem; }
    .submenu-level-2 { padding-left: 4rem; }

    /* Toggle switch styling */
    .toggle-switch {
        position: relative;
        width: 44px;
        height: 24px;
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
        background-color: #ccc;
        transition: 0.3s;
        border-radius: 24px;
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
    }
    input:checked + .toggle-slider {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }
    input:checked + .toggle-slider:before {
        transform: translateX(20px);
    }
</style>
@endsection

@section('content')
<div x-data="menuManagement()" x-init="init()" class="p-4 md:p-6 space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <span class="text-3xl">🍔</span>
                {{ $pageTitle }}
            </h1>
            <p class="mt-1 text-gray-600 dark:text-gray-400">
                จัดการสิทธิ์การเข้าถึงเมนูสำหรับผู้ใช้แต่ละประเภท
            </p>
        </div>
        <div class="flex gap-2">
            <button @click="syncFromConfig()"
                    :disabled="loading"
                    class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg flex items-center gap-2 transition disabled:opacity-50">
                <span>🔄</span>
                <span class="hidden sm:inline">ซิงค์จาก Config</span>
            </button>
            <button @click="resetRoleSettings()"
                    :disabled="loading || !selectedRoleId"
                    class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg flex items-center gap-2 transition disabled:opacity-50">
                <span>🗑️</span>
                <span class="hidden sm:inline">รีเซ็ต Role</span>
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">📊</span>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">เมนูทั้งหมด</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">✅</span>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['active'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">เปิดใช้งาน</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">👁️‍🗨️</span>
                </div>
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['hidden'] }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">ซ่อนอยู่</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-lg border border-gray-200 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Dashboard Type --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ประเภท Dashboard
                </label>
                <select x-model="dashboardType"
                        @change="loadMenus()"
                        class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    @foreach($dashboardTypes as $type => $label)
                        <option value="{{ $type }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Role Selection --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    เลือก Role
                </label>
                <select x-model="selectedRoleId"
                        @change="loadMenus()"
                        class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <option value="">-- ทั้งหมด (ไม่กรอง Role) --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->display_name ?? $role->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    ค้นหาเมนู
                </label>
                <div class="relative">
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="พิมพ์ชื่อเมนู..."
                           class="w-full px-4 py-2 pl-10 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                </div>
            </div>
        </div>

        {{-- Bulk Actions --}}
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2">
            <button @click="bulkAction('show')"
                    :disabled="selectedMenus.length === 0 || !selectedRoleId"
                    class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-sm rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                👁️ แสดงที่เลือก
            </button>
            <button @click="bulkAction('hide')"
                    :disabled="selectedMenus.length === 0 || !selectedRoleId"
                    class="px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-sm rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                🙈 ซ่อนที่เลือก
            </button>
            <button @click="bulkAction('enable')"
                    :disabled="selectedMenus.length === 0 || !selectedRoleId"
                    class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                ✅ เปิดใช้งานที่เลือก
            </button>
            <button @click="bulkAction('disable')"
                    :disabled="selectedMenus.length === 0 || !selectedRoleId"
                    class="px-3 py-1.5 bg-gray-500 hover:bg-gray-600 text-white text-sm rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                ❌ ปิดใช้งานที่เลือก
            </button>
            <span x-show="selectedMenus.length > 0" class="text-sm text-gray-600 dark:text-gray-400 ml-2 self-center">
                เลือก <span x-text="selectedMenus.length"></span> รายการ
            </span>
        </div>
    </div>

    {{-- Menu List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        {{-- Table Header --}}
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            <div class="grid grid-cols-12 gap-4 text-sm font-medium text-gray-600 dark:text-gray-300">
                <div class="col-span-1">
                    <input type="checkbox"
                           @change="toggleSelectAll($event.target.checked)"
                           class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-500 focus:ring-blue-500">
                </div>
                <div class="col-span-4 flex items-center gap-2">
                    <span>🍔</span> เมนู
                </div>
                <div class="col-span-2 text-center">สถานะระบบ</div>
                <div class="col-span-2 text-center" x-show="selectedRoleId">สถานะ Role</div>
                <div class="col-span-2 text-center">ลำดับ</div>
                <div class="col-span-1 text-center">จัดการ</div>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading" class="p-8 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
            <p class="mt-2 text-gray-600 dark:text-gray-400">กำลังโหลด...</p>
        </div>

        {{-- Menu Items (Sortable) --}}
        <div x-show="!loading" x-ref="menuList" class="divide-y divide-gray-200 dark:divide-gray-700">
            <template x-for="menu in filteredMenus" :key="menu.id">
                <div>
                    {{-- Parent Menu --}}
                    <div class="menu-item-row px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-move"
                         :data-id="menu.id"
                         :class="{ 'opacity-50': !menu.is_active }">
                        <div class="grid grid-cols-12 gap-4 items-center">
                            {{-- Checkbox --}}
                            <div class="col-span-1">
                                <input type="checkbox"
                                       :value="menu.id"
                                       x-model="selectedMenus"
                                       class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-500 focus:ring-blue-500">
                            </div>

                            {{-- Menu Info --}}
                            <div class="col-span-4 flex items-center gap-3">
                                <span class="text-xl cursor-grab">⋮⋮</span>
                                <span class="text-xl" x-text="menu.icon || '📄'"></span>
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white" x-text="menu.label"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="menu.route || menu.url"></div>
                                </div>
                                <template x-if="menu.badge">
                                    <span class="px-2 py-0.5 text-xs font-medium text-white rounded-full"
                                          :class="menu.badge_color || 'bg-blue-500'"
                                          x-text="menu.badge"></span>
                                </template>
                                <template x-if="menu.has_children">
                                    <span class="text-xs text-gray-400">(มี submenu)</span>
                                </template>
                            </div>

                            {{-- System Status --}}
                            <div class="col-span-2 flex justify-center gap-2">
                                <label class="toggle-switch" title="เปิด/ปิดเมนู">
                                    <input type="checkbox"
                                           :checked="menu.is_active"
                                           @change="toggleActive(menu)">
                                    <span class="toggle-slider"></span>
                                </label>
                                <label class="toggle-switch" title="แสดง/ซ่อนเมนู">
                                    <input type="checkbox"
                                           :checked="menu.is_visible"
                                           @change="toggleVisible(menu)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            {{-- Role Status --}}
                            <div class="col-span-2 flex justify-center gap-2" x-show="selectedRoleId">
                                <label class="toggle-switch" title="แสดงสำหรับ Role">
                                    <input type="checkbox"
                                           :checked="menu.role_setting?.is_visible ?? true"
                                           @change="updateRoleSetting(menu, 'is_visible', $event.target.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                                <label class="toggle-switch" title="เปิดใช้สำหรับ Role">
                                    <input type="checkbox"
                                           :checked="menu.role_setting?.is_enabled ?? true"
                                           @change="updateRoleSetting(menu, 'is_enabled', $event.target.checked)">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            {{-- Order --}}
                            <div class="col-span-2 text-center">
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded text-sm"
                                      x-text="menu.order"></span>
                            </div>

                            {{-- Actions --}}
                            <div class="col-span-1 flex justify-center">
                                <button @click="openEditModal(menu)"
                                        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition">
                                    ✏️
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Submenu Items --}}
                    <template x-if="menu.children && menu.children.length > 0">
                        <div class="bg-gray-50 dark:bg-gray-800/50">
                            <template x-for="child in menu.children" :key="child.id">
                                <div class="menu-item-row submenu-level-1 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700/30 transition cursor-move border-l-4 border-blue-500/30"
                                     :data-id="child.id"
                                     :data-parent-id="menu.id"
                                     :class="{ 'opacity-50': !child.is_active || child.is_divider }">
                                    <div class="grid grid-cols-12 gap-4 items-center">
                                        {{-- Checkbox --}}
                                        <div class="col-span-1">
                                            <input type="checkbox"
                                                   :value="child.id"
                                                   x-model="selectedMenus"
                                                   :disabled="child.is_divider"
                                                   class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-blue-500 focus:ring-blue-500 disabled:opacity-50">
                                        </div>

                                        {{-- Menu Info --}}
                                        <div class="col-span-4 flex items-center gap-3">
                                            <span class="text-lg cursor-grab text-gray-400">⋮⋮</span>
                                            <template x-if="child.is_divider">
                                                <div class="flex-1 border-t border-gray-300 dark:border-gray-600"></div>
                                            </template>
                                            <template x-if="!child.is_divider">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-lg" x-text="child.icon || '📄'"></span>
                                                    <div>
                                                        <div class="text-sm text-gray-700 dark:text-gray-300" x-text="child.label"></div>
                                                    </div>
                                                    <template x-if="child.badge">
                                                        <span class="px-1.5 py-0.5 text-xs font-medium text-white rounded-full"
                                                              :class="child.badge_color || 'bg-blue-500'"
                                                              x-text="child.badge"></span>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- System Status --}}
                                        <div class="col-span-2 flex justify-center gap-2">
                                            <template x-if="!child.is_divider">
                                                <div class="flex gap-2">
                                                    <label class="toggle-switch" style="transform: scale(0.8);">
                                                        <input type="checkbox"
                                                               :checked="child.is_active"
                                                               @change="toggleActive(child)">
                                                        <span class="toggle-slider"></span>
                                                    </label>
                                                    <label class="toggle-switch" style="transform: scale(0.8);">
                                                        <input type="checkbox"
                                                               :checked="child.is_visible"
                                                               @change="toggleVisible(child)">
                                                        <span class="toggle-slider"></span>
                                                    </label>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Role Status --}}
                                        <div class="col-span-2 flex justify-center gap-2" x-show="selectedRoleId">
                                            <template x-if="!child.is_divider">
                                                <div class="flex gap-2">
                                                    <label class="toggle-switch" style="transform: scale(0.8);">
                                                        <input type="checkbox"
                                                               :checked="child.role_setting?.is_visible ?? true"
                                                               @change="updateRoleSetting(child, 'is_visible', $event.target.checked)">
                                                        <span class="toggle-slider"></span>
                                                    </label>
                                                    <label class="toggle-switch" style="transform: scale(0.8);">
                                                        <input type="checkbox"
                                                               :checked="child.role_setting?.is_enabled ?? true"
                                                               @change="updateRoleSetting(child, 'is_enabled', $event.target.checked)">
                                                        <span class="toggle-slider"></span>
                                                    </label>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Order --}}
                                        <div class="col-span-2 text-center">
                                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-400 rounded text-xs"
                                                  x-text="child.order"></span>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="col-span-1 flex justify-center">
                                            <template x-if="!child.is_divider">
                                                <button @click="openEditModal(child)"
                                                        class="p-1.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition text-sm">
                                                    ✏️
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Empty State --}}
            <div x-show="filteredMenus.length === 0 && !loading" class="p-8 text-center">
                <span class="text-6xl">📭</span>
                <p class="mt-4 text-gray-600 dark:text-gray-400">ไม่พบเมนู</p>
                <button @click="syncFromConfig()"
                        class="mt-4 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                    นำเข้าเมนูจาก Config
                </button>
            </div>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div x-show="showEditModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         @click.self="showEditModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto"
             @click.stop>
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    ✏️ แก้ไขเมนู
                </h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อเมนู</label>
                    <input type="text"
                           x-model="editForm.label"
                           class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ไอคอน (Emoji หรือ Class)</label>
                    <input type="text"
                           x-model="editForm.icon"
                           class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Route Name</label>
                    <input type="text"
                           x-model="editForm.route"
                           class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL (Fallback)</label>
                    <input type="text"
                           x-model="editForm.url"
                           class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Badge</label>
                        <input type="text"
                               x-model="editForm.badge"
                               placeholder="NEW, PRO, HOT"
                               class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Badge Color</label>
                        <input type="text"
                               x-model="editForm.badge_color"
                               placeholder="bg-blue-500"
                               class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea x-model="editForm.description"
                              rows="2"
                              class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white"></textarea>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button @click="showEditModal = false"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition">
                    ยกเลิก
                </button>
                <button @click="saveEdit()"
                        :disabled="saving"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition disabled:opacity-50">
                    <span x-show="!saving">💾 บันทึก</span>
                    <span x-show="saving">กำลังบันทึก...</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg"
         :class="toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
        <span x-text="toast.message"></span>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
function menuManagement() {
    return {
        // State
        loading: false,
        saving: false,
        dashboardType: '{{ $dashboardType }}',
        selectedRoleId: '{{ $selectedRoleId }}',
        searchQuery: '',
        menus: @json($menus),
        selectedMenus: [],
        sortableInstance: null,

        // Edit Modal
        showEditModal: false,
        editingMenu: null,
        editForm: {
            label: '',
            icon: '',
            route: '',
            url: '',
            badge: '',
            badge_color: '',
            description: '',
        },

        // Toast
        toast: {
            show: false,
            message: '',
            type: 'success',
        },

        // Init
        init() {
            this.initSortable();
        },

        // Initialize Sortable.js
        initSortable() {
            this.$nextTick(() => {
                if (this.$refs.menuList && typeof Sortable !== 'undefined') {
                    if (this.sortableInstance) {
                        this.sortableInstance.destroy();
                    }

                    this.sortableInstance = new Sortable(this.$refs.menuList, {
                        animation: 150,
                        handle: '.cursor-grab',
                        ghostClass: 'sortable-ghost',
                        dragClass: 'sortable-drag',
                        chosenClass: 'sortable-chosen',
                        onEnd: (evt) => {
                            this.handleSort(evt);
                        }
                    });
                }
            });
        },

        // Computed: Filtered menus
        get filteredMenus() {
            if (!this.searchQuery) {
                return this.menus;
            }
            const query = this.searchQuery.toLowerCase();
            return this.menus.filter(menu => {
                const matchParent = menu.label.toLowerCase().includes(query);
                const matchChildren = menu.children?.some(child =>
                    child.label.toLowerCase().includes(query)
                );
                return matchParent || matchChildren;
            });
        },

        // Load menus from API
        async loadMenus() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    dashboard_type: this.dashboardType,
                    role_id: this.selectedRoleId || '',
                });

                const response = await fetch(`{{ route('admin.menu-management.menus') }}?${params}`);
                const data = await response.json();

                if (data.success) {
                    this.menus = data.menus;
                    this.initSortable();
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาดในการโหลดเมนู', 'error');
            }
            this.loading = false;
        },

        // Toggle active status
        async toggleActive(menu) {
            try {
                const response = await fetch(`/admin/menu-management/${menu.id}/toggle-active`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    menu.is_active = data.is_active;
                    this.showToast(data.message, 'success');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        // Toggle visible status
        async toggleVisible(menu) {
            try {
                const response = await fetch(`/admin/menu-management/${menu.id}/toggle-visible`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    menu.is_visible = data.is_visible;
                    this.showToast(data.message, 'success');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        // Update role setting
        async updateRoleSetting(menu, field, value) {
            if (!this.selectedRoleId) return;

            try {
                const response = await fetch('{{ route('admin.menu-management.role-setting.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        menu_item_id: menu.id,
                        role_id: this.selectedRoleId,
                        is_visible: field === 'is_visible' ? value : (menu.role_setting?.is_visible ?? true),
                        is_enabled: field === 'is_enabled' ? value : (menu.role_setting?.is_enabled ?? true),
                    }),
                });
                const data = await response.json();

                if (data.success) {
                    if (!menu.role_setting) {
                        menu.role_setting = {};
                    }
                    menu.role_setting[field] = value;
                    this.showToast(data.message, 'success');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        // Handle sort
        async handleSort(evt) {
            const items = [];
            const children = this.$refs.menuList.children;

            for (let i = 0; i < children.length; i++) {
                const el = children[i].querySelector('[data-id]');
                if (el) {
                    items.push({
                        id: el.dataset.id,
                        order: i,
                        parent_id: el.dataset.parentId || null,
                    });
                }
            }

            try {
                const response = await fetch('{{ route('admin.menu-management.order.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ items }),
                });
                const data = await response.json();

                if (data.success) {
                    this.showToast(data.message, 'success');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาดในการบันทึกลำดับ', 'error');
            }
        },

        // Toggle select all
        toggleSelectAll(checked) {
            if (checked) {
                this.selectedMenus = this.filteredMenus.flatMap(menu => {
                    const ids = [menu.id];
                    if (menu.children) {
                        ids.push(...menu.children.filter(c => !c.is_divider).map(c => c.id));
                    }
                    return ids;
                });
            } else {
                this.selectedMenus = [];
            }
        },

        // Bulk action
        async bulkAction(action) {
            if (!this.selectedRoleId || this.selectedMenus.length === 0) return;

            try {
                const response = await fetch('{{ route('admin.menu-management.bulk-toggle-role') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        role_id: this.selectedRoleId,
                        menu_item_ids: this.selectedMenus,
                        action: action,
                    }),
                });
                const data = await response.json();

                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.selectedMenus = [];
                    this.loadMenus();
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
        },

        // Open edit modal
        openEditModal(menu) {
            this.editingMenu = menu;
            this.editForm = {
                label: menu.label,
                icon: menu.icon || '',
                route: menu.route || '',
                url: menu.url || '',
                badge: menu.badge || '',
                badge_color: menu.badge_color || '',
                description: menu.description || '',
            };
            this.showEditModal = true;
        },

        // Save edit
        async saveEdit() {
            if (!this.editingMenu) return;

            this.saving = true;
            try {
                const response = await fetch(`/admin/menu-management/${this.editingMenu.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(this.editForm),
                });
                const data = await response.json();

                if (data.success) {
                    Object.assign(this.editingMenu, data.menu);
                    this.showToast(data.message, 'success');
                    this.showEditModal = false;
                } else {
                    this.showToast(data.message, 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.saving = false;
        },

        // Sync from config
        async syncFromConfig() {
            if (!confirm('ต้องการซิงค์เมนูจาก config หรือไม่? การดำเนินการนี้จะลบข้อมูลเมนูเก่าทั้งหมด')) return;

            this.loading = true;
            try {
                const response = await fetch('{{ route('admin.menu-management.sync-from-config') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.loadMenus();
                } else {
                    this.showToast(data.message, 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.loading = false;
        },

        // Reset role settings
        async resetRoleSettings() {
            if (!this.selectedRoleId) return;
            if (!confirm('ต้องการรีเซ็ตการตั้งค่าเมนูสำหรับ Role นี้หรือไม่?')) return;

            this.loading = true;
            try {
                const response = await fetch('{{ route('admin.menu-management.reset-role-settings') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        role_id: this.selectedRoleId,
                        dashboard_type: this.dashboardType,
                    }),
                });
                const data = await response.json();

                if (data.success) {
                    this.showToast(data.message, 'success');
                    this.loadMenus();
                } else {
                    this.showToast(data.message, 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด', 'error');
            }
            this.loading = false;
        },

        // Show toast
        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;

            setTimeout(() => {
                this.toast.show = false;
            }, 3000);
        },
    };
}
</script>
@endsection
