@extends('layouts.admin-v3')

@section('title', 'จัดการ Packages')

@section('content')
<div x-data="aiPackages()" x-init="init()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📦 จัดการ Packages</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">สร้างและจัดการแพ็คเกจสำหรับผู้ใช้</p>
        </div>
        <button @click="openAdd()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium shadow-lg transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            เพิ่ม Package
        </button>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-green-500 border-t-transparent"></div>
    </div>

    {{-- Empty --}}
    <div x-show="!loading && packages.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
        <div class="text-6xl mb-4">📦</div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300">ยังไม่มี Package</h3>
        <p class="text-sm text-gray-500 mt-1">คลิก "เพิ่ม Package" เพื่อเริ่มต้น</p>
    </div>

    {{-- Packages Grid --}}
    <div x-show="!loading && packages.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <template x-for="pkg in packages" :key="pkg.id">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all overflow-hidden relative"
                 :class="{ 'ring-2 ring-yellow-400': pkg.is_popular }">

                {{-- Popular Badge --}}
                <div x-show="pkg.is_popular" class="absolute top-3 right-3 bg-gradient-to-r from-yellow-400 to-amber-500 text-white text-xs font-bold px-3 py-1 rounded-full">⭐ ยอดนิยม</div>

                <div class="p-6">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white" x-text="pkg.name"></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="pkg.description || '-'"></p>

                    {{-- Price --}}
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-green-600" x-text="'฿' + Number(pkg.price).toLocaleString()"></span>
                        <span x-show="pkg.is_recurring" class="text-sm text-gray-500" x-text="'/' + (pkg.recurring_period === 'monthly' ? 'เดือน' : 'ปี')"></span>
                    </div>

                    {{-- Credits --}}
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 text-center">
                            <div class="text-lg font-bold text-blue-600" x-text="(pkg.image_credits || 0).toLocaleString()">0</div>
                            <div class="text-xs text-blue-500">🖼️ เครดิตภาพ</div>
                        </div>
                        <div class="bg-pink-50 dark:bg-pink-900/20 rounded-xl p-3 text-center">
                            <div class="text-lg font-bold text-pink-600" x-text="(pkg.video_credits || 0).toLocaleString()">0</div>
                            <div class="text-xs text-pink-500">🎬 เครดิตวิดีโอ</div>
                        </div>
                    </div>

                    {{-- Duration & Status --}}
                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-xs text-gray-500" x-text="pkg.duration_days ? pkg.duration_days + ' วัน' : 'ไม่มีวันหมดอายุ'"></span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium" :class="pkg.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'" x-text="pkg.is_active ? 'เปิดใช้' : 'ปิดใช้'"></span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700 flex gap-2">
                    <button @click="openEdit(pkg)" class="flex-1 px-3 py-2 text-sm bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">✏️ แก้ไข</button>
                    <button @click="deletePkg(pkg.id)" class="px-3 py-2 text-sm bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition">🗑️</button>
                </div>
            </div>
        </template>
    </div>

    {{-- Add/Edit Modal --}}
    <div x-show="showModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="editing ? '✏️ แก้ไข Package' : '➕ เพิ่ม Package'"></h3>
                <button @click="showModal = false" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"><svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form @submit.prevent="save()" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ชื่อ *</label>
                    <input type="text" x-model="form.name" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">คำอธิบาย</label>
                    <textarea x-model="form.description" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ราคา (฿) *</label>
                        <input type="number" x-model="form.price" min="0" step="0.01" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">อายุ (วัน)</label>
                        <input type="number" x-model="form.duration_days" min="1" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เครดิตภาพ *</label>
                        <input type="number" x-model="form.image_credits" min="0" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เครดิตวิดีโอ *</label>
                        <input type="number" x-model="form.video_credits" min="0" required class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2"><input type="checkbox" x-model="form.is_active" class="rounded text-green-600"><span class="text-sm text-gray-700 dark:text-gray-300">เปิดใช้</span></label>
                    <label class="flex items-center gap-2"><input type="checkbox" x-model="form.is_popular" class="rounded text-yellow-500"><span class="text-sm text-gray-700 dark:text-gray-300">ยอดนิยม</span></label>
                    <label class="flex items-center gap-2"><input type="checkbox" x-model="form.is_recurring" class="rounded text-blue-600"><span class="text-sm text-gray-700 dark:text-gray-300">รายเดือน/ปี</span></label>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">ยกเลิก</button>
                    <button type="submit" :disabled="saving" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 transition disabled:opacity-50 font-medium">
                        <span x-show="!saving">💾 บันทึก</span><span x-show="saving">กำลังบันทึก...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Toast --}}
    <div x-show="toast.show" x-transition class="fixed bottom-6 right-6 z-50 max-w-sm">
        <div class="rounded-xl shadow-2xl p-4 flex items-center gap-3" :class="toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
            <span x-text="toast.type === 'success' ? '✅' : '❌'" class="text-xl"></span>
            <span x-text="toast.message" class="text-sm font-medium"></span>
        </div>
    </div>
</div>

<script>
function aiPackages() {
    return {
        packages: [], loading: true, saving: false, showModal: false, editing: null,
        toast: { show: false, message: '', type: 'success' },
        form: { name: '', description: '', price: 0, duration_days: 30, image_credits: 0, video_credits: 0, is_active: true, is_popular: false, is_recurring: false },

        async init() { await this.load(); },
        async load() {
            this.loading = true;
            try {
                const res = await fetch('/admin/ai-gen/packages', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.success) this.packages = data.data;
            } catch (e) { this.showToast('โหลดข้อมูลไม่สำเร็จ', 'error'); }
            this.loading = false;
        },
        openAdd() {
            this.editing = null;
            this.form = { name: '', description: '', price: 0, duration_days: 30, image_credits: 0, video_credits: 0, is_active: true, is_popular: false, is_recurring: false };
            this.showModal = true;
        },
        openEdit(pkg) {
            this.editing = pkg;
            this.form = { ...pkg };
            this.showModal = true;
        },
        async save() {
            this.saving = true;
            try {
                const url = this.editing ? `/admin/ai-gen/packages/${this.editing.id}` : '/admin/ai-gen/packages';
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
        async deletePkg(id) {
            if (!confirm('ต้องการลบ Package นี้?')) return;
            try {
                const res = await fetch(`/admin/ai-gen/packages/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const data = await res.json();
                if (data.success) { await this.load(); this.showToast('ลบสำเร็จ', 'success'); }
                else this.showToast(data.error || 'ลบไม่สำเร็จ', 'error');
            } catch (e) { this.showToast('เกิดข้อผิดพลาด', 'error'); }
        },
        showToast(message, type = 'success') { this.toast = { show: true, message, type }; setTimeout(() => this.toast.show = false, 3000); }
    }
}
</script>
@endsection
