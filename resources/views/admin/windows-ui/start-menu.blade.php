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
                        <p class="text-white/90">จัดการรายการเมนู Start, ไอคอน, ขนาด, ตำแหน่ง และ RGB Effects</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Menu Items Manager -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">รายการเมนู</h2>
                        <button @click="addMenuItem()" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all">
                            <i class="fas fa-plus mr-2"></i>เพิ่มรายการ
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <form method="POST" action="{{ route('admin.windows-ui.start-menu.update') }}" @submit="prepareSubmit">
                        @csrf
                        @method('PUT')

                        <div id="menu-items-list" class="space-y-3">
                            <template x-for="(item, index) in menuItems" :key="index">
                                <div class="menu-item-card bg-gray-50 dark:bg-slate-700 rounded-xl p-4 border border-gray-200 dark:border-slate-600">
                                    <div class="flex items-start gap-4">
                                        <!-- Drag Handle -->
                                        <div class="cursor-move text-gray-400 hover:text-gray-600 pt-2">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                            </svg>
                                        </div>

                                        <!-- Form Fields -->
                                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ไอคอน (Emoji)</label>
                                                <input type="text" x-model="item.icon" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-600 dark:text-white rounded-lg" placeholder="🏠" maxlength="10">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อเมนู</label>
                                                <input type="text" x-model="item.label" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-600 dark:text-white rounded-lg" placeholder="หน้าแรก" required>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL/Route</label>
                                                <input type="text" x-model="item.url" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-600 dark:text-white rounded-lg" placeholder="/admin/dashboard" required>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="checkbox" x-model="item.has_submenu" class="rounded border-gray-300 text-indigo-600">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">มี Submenu</span>
                                                </label>
                                            </div>

                                            <!-- Submenu Items -->
                                            <div x-show="item.has_submenu" class="md:col-span-2 pl-4 border-l-2 border-indigo-300 dark:border-indigo-700">
                                                <div class="mb-2 flex items-center justify-between">
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Submenu Items</span>
                                                    <button type="button" @click="addSubmenuItem(index)" class="text-sm px-2 py-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded">
                                                        + เพิ่ม
                                                    </button>
                                                </div>
                                                <div class="space-y-2">
                                                    <template x-for="(subitem, subindex) in item.submenu" :key="subindex">
                                                        <div class="flex gap-2">
                                                            <input type="text" x-model="subitem.label" class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-600 dark:text-white rounded" placeholder="ชื่อ Submenu">
                                                            <input type="text" x-model="subitem.url" class="flex-1 px-2 py-1 text-sm border border-gray-300 dark:border-slate-600 dark:bg-slate-600 dark:text-white rounded" placeholder="URL">
                                                            <button type="button" @click="removeSubmenuItem(index, subindex)" class="px-2 py-1 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex flex-col gap-2">
                                            <button type="button" @click="removeMenuItem(index)" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <input type="hidden" name="items" x-model="menuItemsJson">

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg">
                                <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Menu Settings -->
        <div class="space-y-6">
            <!-- Size & Position Settings -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>📐</span> ขนาด & ตำแหน่ง
                </h3>
                <form method="POST" action="{{ route('admin.windows-ui.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ตำแหน่งเมนู</label>
                            <select name="millennium_menu_position" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                                <option value="left">ซ้าย</option>
                                <option value="center" selected>กลาง</option>
                                <option value="right">ขวา</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความกว้าง (px)</label>
                            <input type="number" name="millennium_menu_width" min="300" max="800" value="400" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ความสูงสูงสุด (px)</label>
                            <input type="number" name="millennium_menu_max_height" min="400" max="1000" value="600" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            บันทึก
                        </button>
                    </div>
                </form>
            </div>

            <!-- RGB Settings Preview -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🌈</span> RGB Effects
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    ตั้งค่า RGB Border Animation ที่หน้าหลัก Windows UI หรือที่
                </p>
                <a href="{{ route('admin.windows-ui.rgb-settings') }}" class="block w-full text-center px-4 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:from-purple-700 hover:to-pink-700">
                    ไปที่ RGB Settings →
                </a>
            </div>

            <!-- Quick Tips -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-2xl p-6 border border-blue-200 dark:border-blue-800">
                <h4 class="text-lg font-bold text-blue-900 dark:text-blue-300 mb-3">💡 เคล็ดลับ</h4>
                <ul class="space-y-2 text-sm text-blue-700 dark:text-blue-300">
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>ลากรายการเพื่อเรียงลำดับใหม่</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>ใช้ Emoji เป็นไอคอน (🏠 📊 👥 ⚙️)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>สามารถสร้าง Submenu ได้</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>URL สามารถใช้ Route name หรือ path ได้</span>
                    </li>
                </ul>
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

        init() {
            // Initialize Sortable.js
            const el = document.getElementById('menu-items-list');
            if (el) {
                Sortable.create(el, {
                    animation: 150,
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
