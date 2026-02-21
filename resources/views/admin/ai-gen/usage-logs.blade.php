@extends('layouts.admin-v3')

@section('title', 'บันทึกการใช้งาน AI Gen')

@section('content')
<div x-data="aiUsageLogs()" x-init="init()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📋 บันทึกการใช้งาน</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ดูประวัติการสร้างภาพ/วิดีโอของผู้ใช้ทั้งหมด</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">สถานะ</label>
                <select x-model="filters.status" @change="load()" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="completed">สำเร็จ</option>
                    <option value="failed">ล้มเหลว</option>
                    <option value="processing">กำลังสร้าง</option>
                    <option value="pending">รอดำเนินการ</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Provider</label>
                <select x-model="filters.provider_id" @change="load()" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    @foreach($providers ?? [] as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">ประเภท</label>
                <select x-model="filters.is_free_quota" @change="load()" class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">ทั้งหมด</option>
                    <option value="1">Free Quota</option>
                    <option value="0">จ่ายเงิน</option>
                </select>
            </div>
            <div class="flex items-end">
                <button @click="resetFilters()" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition">🔄 รีเซ็ต</button>
            </div>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-blue-500 border-t-transparent"></div>
    </div>

    {{-- Table --}}
    <div x-show="!loading" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ผู้ใช้</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Provider</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ประเภท</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">โควตา</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">เวลา</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="log in logs" :key="log.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-3">
                                <div class="font-medium text-sm text-gray-900 dark:text-white" x-text="log.user?.name || 'N/A'"></div>
                                <div class="text-xs text-gray-500" x-text="log.user?.email || ''"></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300" x-text="log.provider?.name || 'N/A'"></td>
                            <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300" x-text="log.generation_type || 'image'"></span></td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': log.status === 'completed',
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': log.status === 'failed',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': log.status === 'processing',
                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': log.status === 'pending'
                                    }"
                                    x-text="log.status === 'completed' ? '✅ สำเร็จ' : log.status === 'failed' ? '❌ ล้มเหลว' : log.status === 'processing' ? '⏳ กำลังสร้าง' : '🕐 รอ'">
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center"><span class="text-xs" :class="log.is_free_quota ? 'text-green-600' : 'text-amber-600'" x-text="log.is_free_quota ? '🎁 ฟรี' : '💰 จ่ายเงิน'"></span></td>
                            <td class="px-6 py-3 text-right text-xs text-gray-500" x-text="new Date(log.created_at).toLocaleString('th-TH')"></td>
                        </tr>
                    </template>
                    <tr x-show="!loading && logs.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">ไม่มีข้อมูล</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div x-show="pagination.last_page > 1" class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <span class="text-sm text-gray-500" x-text="'แสดง ' + pagination.from + '-' + pagination.to + ' จาก ' + pagination.total"></span>
            <div class="flex gap-1">
                <button @click="goPage(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-3 py-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-30 transition">ก่อนหน้า</button>
                <button @click="goPage(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-3 py-1 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-30 transition">ถัดไป</button>
            </div>
        </div>
    </div>
</div>

<script>
function aiUsageLogs() {
    return {
        logs: [], loading: true,
        filters: { status: '', provider_id: '', is_free_quota: '' },
        pagination: { current_page: 1, last_page: 1, from: 0, to: 0, total: 0 },

        async init() { await this.load(); },
        async load() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.status) params.set('status', this.filters.status);
                if (this.filters.provider_id) params.set('provider_id', this.filters.provider_id);
                if (this.filters.is_free_quota !== '') params.set('is_free_quota', this.filters.is_free_quota);
                params.set('page', this.pagination.current_page);

                const res = await fetch('/admin/ai-gen/usage-logs?' + params.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.success) {
                    this.logs = data.data.data || [];
                    this.pagination = {
                        current_page: data.data.current_page,
                        last_page: data.data.last_page,
                        from: data.data.from || 0,
                        to: data.data.to || 0,
                        total: data.data.total || 0
                    };
                }
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        resetFilters() { this.filters = { status: '', provider_id: '', is_free_quota: '' }; this.pagination.current_page = 1; this.load(); },
        goPage(p) { if (p >= 1 && p <= this.pagination.last_page) { this.pagination.current_page = p; this.load(); } }
    }
}
</script>
@endsection
