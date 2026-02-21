@extends('layouts.admin-v3')

@section('title', 'จัดการ Free Quotas')

@section('content')
<div x-data="aiQuotas()" x-init="init()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🎁 จัดการ Free Quotas</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">กำหนดโควตาฟรีสำหรับผู้ใช้แต่ละระดับ</p>
        </div>
        <button @click="openAdd()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white rounded-xl font-medium shadow-lg transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            เพิ่ม Quota
        </button>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-amber-500 border-t-transparent"></div>
    </div>

    {{-- Empty --}}
    <div x-show="!loading && quotas.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
        <div class="text-6xl mb-4">🎁</div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">ยังไม่มี Free Quota</h3>
    </div>

    {{-- Quotas Table --}}
    <div x-show="!loading && quotas.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ชื่อ</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ภาพ/วัน</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ภาพ/เดือน</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">วิดีโอ/วัน</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">วิดีโอ/เดือน</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สำหรับ</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="q in quotas" :key="q.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white" x-text="q.name"></div>
                                <div class="text-xs text-gray-500" x-text="q.description || ''"></div>
                                <span x-show="q.is_default" class="text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 px-2 py-0.5 rounded-full">Default</span>
                            </td>
                            <td class="px-4 py-4 text-center font-medium text-gray-900 dark:text-white" x-text="q.free_image_daily"></td>
                            <td class="px-4 py-4 text-center font-medium text-gray-900 dark:text-white" x-text="q.free_image_monthly"></td>
                            <td class="px-4 py-4 text-center font-medium text-gray-900 dark:text-white" x-text="q.free_video_daily"></td>
                            <td class="px-4 py-4 text-center font-medium text-gray-900 dark:text-white" x-text="q.free_video_monthly"></td>
                            <td class="px-4 py-4 text-center"><span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400" x-text="q.role || 'user'"></span></td>
                            <td class="px-4 py-4 text-center"><span class="w-2 h-2 inline-block rounded-full" :class="q.is_active ? 'bg-green-500' : 'bg-red-400'"></span></td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openEdit(q)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm mr-2">✏️</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal --}}
    <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="editing ? '✏️ แก้ไข Quota' : '➕ เพิ่ม Quota'"></h3>
            </div>
            <form @submit.prevent="save()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ *</label>
                    <input type="text" x-model="form.name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <input type="text" x-model="form.description" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภาพ/วัน *</label><input type="number" x-model="form.free_image_daily" min="0" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500"></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ภาพ/เดือน *</label><input type="number" x-model="form.free_image_monthly" min="0" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500"></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วิดีโอ/วัน *</label><input type="number" x-model="form.free_video_daily" min="0" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500"></div>
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">วิดีโอ/เดือน *</label><input type="number" x-model="form.free_video_monthly" min="0" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สำหรับ Role</label>
                        <select x-model="form.role" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500"><option value="user">User</option><option value="admin">Admin</option></select>
                    </div>
                    <div class="flex flex-col justify-end gap-2">
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="form.is_active" class="rounded text-amber-600"><span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้</span></label>
                        <label class="flex items-center gap-2"><input type="checkbox" x-model="form.is_default" class="rounded text-yellow-500"><span class="text-sm text-gray-700 dark:text-gray-300">Default</span></label>
                    </div>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">ยกเลิก</button>
                    <button type="submit" :disabled="saving" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 text-white rounded-xl transition disabled:opacity-50 font-medium">💾 บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50 max-w-sm">
        <div class="rounded-xl shadow-2xl p-4 flex items-center gap-3" :class="toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
            <span x-text="toast.message" class="text-sm font-medium"></span>
        </div>
    </div>
</div>

<script>
function aiQuotas() {
    return {
        quotas: [], loading: true, saving: false, showModal: false, editing: null,
        toast: { show: false, message: '', type: 'success' },
        form: { name: '', description: '', free_image_daily: 5, free_image_monthly: 100, free_video_daily: 1, free_video_monthly: 10, role: 'user', is_active: true, is_default: false },

        async init() { await this.load(); },
        async load() {
            this.loading = true;
            try {
                const res = await fetch('/admin/ai-gen/quotas', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.success) this.quotas = data.data;
            } catch (e) { this.showToast('โหลดข้อมูลไม่สำเร็จ', 'error'); }
            this.loading = false;
        },
        openAdd() {
            this.editing = null;
            this.form = { name: '', description: '', free_image_daily: 5, free_image_monthly: 100, free_video_daily: 1, free_video_monthly: 10, role: 'user', is_active: true, is_default: false };
            this.showModal = true;
        },
        openEdit(q) { this.editing = q; this.form = { ...q }; this.showModal = true; },
        async save() {
            this.saving = true;
            try {
                const url = this.editing ? `/admin/ai-gen/quotas/${this.editing.id}` : '/admin/ai-gen/quotas';
                const res = await fetch(url, {
                    method: this.editing ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(this.form)
                });
                const data = await res.json();
                if (data.success) { this.showModal = false; await this.load(); this.showToast('บันทึกสำเร็จ', 'success'); }
                else this.showToast(data.error || 'เกิดข้อผิดพลาด', 'error');
            } catch (e) { this.showToast('เกิดข้อผิดพลาด', 'error'); }
            this.saving = false;
        },
        showToast(msg, type = 'success') { this.toast = { show: true, message: msg, type }; setTimeout(() => this.toast.show = false, 3000); }
    }
}
</script>
@endsection
