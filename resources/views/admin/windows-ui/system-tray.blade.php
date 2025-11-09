@extends('layouts.admin')

@section('title', 'จัดการ System Tray')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.css">
<style>
    .sortable-ghost {
        opacity: 0.4;
        background: #f3f4f6;
    }
    .icon-card {
        transition: all 0.2s;
    }
    .icon-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="systemTrayManager()">
    <!-- Header -->
    <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-600 to-cyan-600 p-8 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjEiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-30"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-3xl">
                        ⚙️
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white mb-1">จัดการ System Tray</h1>
                        <p class="text-white/90">จัดการไอคอนที่แสดงใน System Tray และควบคุมสิทธิ์การมองเห็น</p>
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
        <!-- Left: Icons Manager -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">System Tray Icons</h2>
                        <button @click="addIcon()" class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-lg hover:from-emerald-700 hover:to-teal-700 transition-all">
                            <i class="fas fa-plus mr-2"></i>เพิ่มไอคอน
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <form method="POST" action="{{ route('admin.windows-ui.system-tray.update') }}" @submit="prepareSubmit">
                        @csrf
                        @method('PUT')

                        <div id="icons-list" class="space-y-3">
                            <template x-for="(icon, index) in icons" :key="index">
                                <div class="icon-card bg-gray-50 dark:bg-slate-700 rounded-xl p-4 border border-gray-200 dark:border-slate-600">
                                    <div class="flex items-start gap-4">
                                        <!-- Drag Handle -->
                                        <div class="cursor-move text-gray-400 hover:text-gray-600 pt-2">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                            </svg>
                                        </div>

                                        <!-- Icon Preview -->
                                        <div class="text-center pt-2">
                                            <span class="text-3xl" x-text="icon.icon || '⚙️'"></span>
                                        </div>

                                        <!-- Form Fields -->
                                        <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ไอคอน (Emoji/Text)</label>
                                                <input type="text" x-model="icon.icon" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-600 dark:text-white rounded-lg" placeholder="⚙️" maxlength="20">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ/คำอธิบาย</label>
                                                <input type="text" x-model="icon.label" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-600 dark:text-white rounded-lg" placeholder="Settings" required>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">URL/Route</label>
                                                <input type="text" x-model="icon.url" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-600 dark:text-white rounded-lg" placeholder="/admin/settings" required>
                                            </div>

                                            <!-- Visibility Controls -->
                                            <div class="md:col-span-2 flex flex-wrap gap-4">
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="checkbox" x-model="icon.requires_auth" class="rounded border-gray-300 text-emerald-600">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">🔐 เฉพาะผู้ล็อกอิน</span>
                                                </label>
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="checkbox" x-model="icon.requires_guest" class="rounded border-gray-300 text-emerald-600">
                                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">👤 เฉพาะ Guest</span>
                                                </label>
                                            </div>

                                            <!-- Warning for conflicting settings -->
                                            <div x-show="icon.requires_auth && icon.requires_guest" class="md:col-span-2">
                                                <div class="p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded text-xs text-yellow-700 dark:text-yellow-300">
                                                    ⚠️ ไม่สามารถเลือกทั้ง "เฉพาะผู้ล็อกอิน" และ "เฉพาะ Guest" พร้อมกันได้
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex flex-col gap-2">
                                            <button type="button" @click="removeIcon(index)" class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <input type="hidden" name="icons" x-model="iconsJson">

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all shadow-lg">
                                <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right: Icon Suggestions & Tips -->
        <div class="space-y-6">
            <!-- System Icon Suggestions -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>🎨</span> ไอคอนแนะนำ
                </h3>
                <div class="grid grid-cols-4 gap-3 text-3xl text-center">
                    <button type="button" @click="copyIcon('⚙️')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Settings">⚙️</button>
                    <button type="button" @click="copyIcon('🔔')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Notifications">🔔</button>
                    <button type="button" @click="copyIcon('👤')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Profile">👤</button>
                    <button type="button" @click="copyIcon('📧')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Messages">📧</button>
                    <button type="button" @click="copyIcon('🌐')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Language">🌐</button>
                    <button type="button" @click="copyIcon('🌙')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Dark Mode">🌙</button>
                    <button type="button" @click="copyIcon('🔊')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Volume">🔊</button>
                    <button type="button" @click="copyIcon('📶')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Network">📶</button>
                    <button type="button" @click="copyIcon('🔋')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Battery">🔋</button>
                    <button type="button" @click="copyIcon('🕐')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Time">🕐</button>
                    <button type="button" @click="copyIcon('💰')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Wallet">💰</button>
                    <button type="button" @click="copyIcon('🏆')" class="p-3 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all" title="Achievements">🏆</button>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-3 text-center">คลิกเพื่อคัดลอก</p>

                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        💡 <strong>เคล็ดลับ:</strong> นอกจาก Emoji ยังสามารถใช้ข้อความสั้นๆ เช่น "EN", "TH", "฿" ได้ด้วย
                    </p>
                </div>
            </div>

            <!-- System Tray Info -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700 p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <span>ℹ️</span> System Tray Info
                </h3>
                <form method="POST" action="{{ route('admin.windows-ui.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความไลเซ่น</label>
                            <input type="text" name="windows_license_text" value="{{ \App\Models\WindowsUiSetting::get('windows_license_text', 'Licensed') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ข้อความลิขสิทธิ์</label>
                            <input type="text" name="windows_copyright_text" value="{{ \App\Models\WindowsUiSetting::get('windows_copyright_text', '© 2025 TP-Affiliate') }}" class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg">
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                            บันทึก
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Tips -->
            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-2xl p-6 border border-emerald-200 dark:border-emerald-800">
                <h4 class="text-lg font-bold text-emerald-900 dark:text-emerald-300 mb-3">💡 เคล็ดลับ</h4>
                <ul class="space-y-2 text-sm text-emerald-700 dark:text-emerald-300">
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>ลากไอคอนเพื่อเรียงลำดับ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>System Tray แสดงที่มุมขวาของ Taskbar</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>ควบคุมการมองเห็นด้วย requires_auth และ requires_guest</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span>•</span>
                        <span>ใช้ไอคอนที่บ่งบอกหน้าที่ชัดเจน</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
function systemTrayManager() {
    return {
        icons: @json($icons ?? []),

        init() {
            // Initialize Sortable.js
            const el = document.getElementById('icons-list');
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

        addIcon() {
            this.icons.push({
                icon: '⚙️',
                label: 'ไอคอนใหม่',
                url: '/admin/settings',
                requires_auth: false,
                requires_guest: false,
                order: this.icons.length
            });
        },

        removeIcon(index) {
            if (confirm('คุณต้องการลบไอคอนนี้ใช่หรือไม่?')) {
                this.icons.splice(index, 1);
                this.updateOrder();
            }
        },

        copyIcon(icon) {
            navigator.clipboard.writeText(icon);
            alert(`คัดลอก ${icon} แล้ว!`);
        },

        updateOrder() {
            this.icons.forEach((icon, index) => {
                icon.order = index;
            });
        },

        prepareSubmit(e) {
            this.updateOrder();
        },

        get iconsJson() {
            return JSON.stringify(this.icons);
        }
    }
}
</script>
@endpush
@endsection
