@extends('layouts.admin')

@section('title', 'จัดการ Taskbar Apps')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.css">
<style>
    .sortable-ghost {
        opacity: 0.4;
        background: #f3f4f6;
    }
    .app-card {
        transition: all 0.2s;
    }
    .app-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="taskbarAppsManager()">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-600 to-indigo-600 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-3xl">
                        📱
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">จัดการ Taskbar Apps</h1>
                        <p class="text-white/90">จัดการแอปพลิเคชันที่แสดงใน Taskbar</p>
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
        <!-- Left: Apps Manager -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Taskbar Apps</h2>
                        <button @click="addApp()" class="px-4 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-lg hover:from-cyan-700 hover:to-blue-700 transition-all">
                            <i class="fas fa-plus mr-2"></i>เพิ่มแอป
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <form method="POST" action="{{ route('admin.windows-ui.taskbar-apps.update') }}" @submit="prepareSubmit">
                        @csrf
                        @method('PUT')

                        <div id="apps-list" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <template x-for="(app, index) in apps" :key="index">
                                <div class="app-card bg-gradient-to-br from-gray-50 to-gray-100 dark:from-slate-700 dark:to-slate-600 rounded-xl p-4 border border-gray-200 dark:border-slate-600 shadow-sm">
                                    <!-- Drag Handle -->
                                    <div class="cursor-move text-gray-400 hover:text-gray-600 mb-3 flex justify-center">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                        </svg>
                                    </div>

                                    <!-- Preview -->
                                    <div class="text-center mb-4">
                                        <div class="inline-block p-3 bg-white dark:bg-slate-800 rounded-xl shadow-lg">
                                            <span class="text-4xl" x-text="app.icon || '📱'"></span>
                                        </div>
                                    </div>

                                    <!-- Form Fields -->
                                    <div class="space-y-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ไอคอน (Emoji)</label>
                                            <input type="text" x-model="app.icon" class="w-full px-3 py-2 text-center text-2xl border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg" placeholder="📱" maxlength="10">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">ชื่อแอป</label>
                                            <input type="text" x-model="app.label" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg" placeholder="Dashboard" required>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">URL/Route</label>
                                            <input type="text" x-model="app.url" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg" placeholder="/admin/dashboard" required>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="mt-4 flex justify-center">
                                        <button type="button" @click="removeApp(index)" class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-all">
                                            <i class="fas fa-trash mr-2"></i>ลบ
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <input type="hidden" name="apps" x-model="appsJson">

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-xl hover:from-cyan-700 hover:to-blue-700 transition-all shadow-lg">
                                <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Settings & Tips -->
        <div class="space-y-6">
            <!-- App Icon Suggestions -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎨</span> ไอคอนแนะนำ
                </h3>
                <div class="grid grid-cols-4 gap-3 text-3xl text-center">
                    <button type="button" @click="copyIcon('📊')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Dashboard">📊</button>
                    <button type="button" @click="copyIcon('👥')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Users">👥</button>
                    <button type="button" @click="copyIcon('💰')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Finance">💰</button>
                    <button type="button" @click="copyIcon('📈')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Analytics">📈</button>
                    <button type="button" @click="copyIcon('⚙️')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Settings">⚙️</button>
                    <button type="button" @click="copyIcon('📝')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Content">📝</button>
                    <button type="button" @click="copyIcon('🛒')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Shop">🛒</button>
                    <button type="button" @click="copyIcon('🎯')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Goals">🎯</button>
                    <button type="button" @click="copyIcon('📦')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Products">📦</button>
                    <button type="button" @click="copyIcon('📧')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Messages">📧</button>
                    <button type="button" @click="copyIcon('🔔')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Notifications">🔔</button>
                    <button type="button" @click="copyIcon('📱')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="App">📱</button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3 text-center">คลิกเพื่อคัดลอก</p>
            </div>

            <!-- Taskbar Settings -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎛️</span> การตั้งค่า Taskbar
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    ตั้งค่า Taskbar เพิ่มเติมได้ที่หน้าหลัก Windows UI
                </p>
                <a href="{{ route('admin.windows-ui.index') }}" class="block w-full text-center px-4 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-lg hover:from-cyan-700 hover:to-blue-700">
                    ไปที่ Windows UI Settings →
                </a>
            </div>

            <!-- Quick Tips -->
            <div class="bg-gradient-to-br from-cyan-50 to-blue-50 dark:from-cyan-900/20 dark:to-blue-900/20 rounded-2xl p-6 border border-cyan-200 dark:border-cyan-800">
                <h4 class="text-lg font-bold text-cyan-900 dark:text-cyan-300 mb-3">💡 เคล็ดลับ</h4>
                <ul class="space-y-2 text-sm text-cyan-700 dark:text-cyan-300">
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>ลากการ์ดแอปเพื่อเรียงลำดับ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>แอปจะแสดงที่ Millennium Taskbar</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>ใช้ Emoji ที่เกี่ยวข้องกับหน้าที่ของแอป</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>แอปที่ใช้บ่อยควรอยู่ด้านซ้าย</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
function taskbarAppsManager() {
    return {
        apps: @json($apps ?? []),

        init() {
            // Initialize Sortable.js
            const el = document.getElementById('apps-list');
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

        addApp() {
            this.apps.push({
                icon: '📱',
                label: 'แอปใหม่',
                url: '/admin/dashboard',
                order: this.apps.length
            });
        },

        removeApp(index) {
            if (confirm('คุณต้องการลบแอปนี้ใช่หรือไม่?')) {
                this.apps.splice(index, 1);
                this.updateOrder();
            }
        },

        copyIcon(icon) {
            navigator.clipboard.writeText(icon);
            alert(`คัดลอก ${icon} แล้ว!`);
        },

        updateOrder() {
            this.apps.forEach((app, index) => {
                app.order = index;
            });
        },

        prepareSubmit(e) {
            this.updateOrder();
        },

        get appsJson() {
            return JSON.stringify(this.apps);
        }
    }
}
</script>
@endpush
@endsection
