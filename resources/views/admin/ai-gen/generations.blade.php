@extends('layouts.admin-v3')

@section('title', 'แกลเลอรี่ AI Gen')

@section('content')
<div x-data="aiGallery()" x-init="init()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">🎨 แกลเลอรี่ผลงาน AI</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ดูผลงานที่ผู้ใช้สร้างทั้งหมด</p>
        </div>
    </div>

    {{-- Info --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 text-sm text-blue-700 dark:text-blue-300">
        ℹ️ หน้านี้แสดงผลงาน AI ที่ผู้ใช้สร้างทั้งหมด (ข้อมูลจะแสดงเมื่อมีการใช้งานจริง)
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-purple-500 border-t-transparent"></div>
    </div>

    {{-- Empty --}}
    <div x-show="!loading && items.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-16 text-center border border-gray-200 dark:border-gray-700">
        <div class="text-7xl mb-4">🎨</div>
        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300">ยังไม่มีผลงาน</h3>
        <p class="text-sm text-gray-500 mt-2">เมื่อผู้ใช้สร้างภาพหรือวิดีโอ ผลงานจะแสดงที่นี่</p>
    </div>

    {{-- Gallery Grid --}}
    <div x-show="!loading && items.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <template x-for="item in items" :key="item.id">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden group hover:shadow-lg transition-all">
                <div class="aspect-square bg-gray-100 dark:bg-gray-700 relative">
                    <template x-if="item.result_url">
                        <img :src="item.result_url" class="w-full h-full object-cover" :alt="item.prompt">
                    </template>
                    <template x-if="!item.result_url">
                        <div class="w-full h-full flex items-center justify-center text-4xl text-gray-300">🖼️</div>
                    </template>
                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                        <span class="text-white text-sm px-3 py-1 bg-white/20 rounded-lg backdrop-blur" x-text="item.status === 'completed' ? '✅ สำเร็จ' : item.status"></span>
                    </div>
                </div>
                <div class="p-3">
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="item.prompt || '-'"></p>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-gray-400" x-text="item.user?.name || 'N/A'"></span>
                        <span class="text-xs text-gray-400" x-text="new Date(item.created_at).toLocaleDateString('th-TH')"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function aiGallery() {
    return {
        items: [], loading: true,
        async init() {
            this.loading = true;
            try {
                const res = await fetch('/admin/ai-gen/usage-logs?status=completed&per_page=50', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.success) this.items = data.data.data || [];
            } catch (e) { console.error(e); }
            this.loading = false;
        }
    }
}
</script>
@endsection
