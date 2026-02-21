@extends('layouts.admin-v3')

@section('title', 'จัดการ Subscriptions')

@section('content')
<div x-data="aiSubs()" x-init="init()" class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">💳 จัดการ Subscriptions</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">ดู subscription ที่ผู้ใช้ซื้อทั้งหมด</p>
        </div>
    </div>

    {{-- Loading --}}
    <div x-show="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-indigo-500 border-t-transparent"></div>
    </div>

    {{-- Info --}}
    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-4 text-sm text-indigo-700 dark:text-indigo-300">
        ℹ️ Subscriptions จะปรากฏเมื่อผู้ใช้ซื้อ Package แล้ว ข้อมูลนี้ดึงจากระบบอัตโนมัติ
    </div>

    {{-- Empty --}}
    <div x-show="!loading && subs.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-16 text-center border border-gray-200 dark:border-gray-700">
        <div class="text-7xl mb-4">💳</div>
        <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300">ยังไม่มี Subscription</h3>
        <p class="text-sm text-gray-500 mt-2">เมื่อผู้ใช้ซื้อ Package ข้อมูลจะแสดงที่นี่</p>
    </div>

    {{-- Table --}}
    <div x-show="!loading && subs.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="text-left px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">ผู้ใช้</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Package</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">เครดิตคงเหลือ</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">สถานะ</th>
                        <th class="text-right px-6 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">หมดอายุ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="sub in subs" :key="sub.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-6 py-3">
                                <div class="font-medium text-sm text-gray-900 dark:text-white" x-text="sub.user?.name || 'N/A'"></div>
                                <div class="text-xs text-gray-500" x-text="sub.user?.email || ''"></div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300" x-text="sub.package?.name || 'N/A'"></td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-sm font-medium text-blue-600" x-text="'🖼️ ' + (sub.image_credits_remaining || 0)"></span>
                                <span class="text-sm font-medium text-pink-600 ml-2" x-text="'🎬 ' + (sub.video_credits_remaining || 0)"></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="sub.status === 'active' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'"
                                    x-text="sub.status === 'active' ? '✅ ใช้งานอยู่' : sub.status">
                                </span>
                            </td>
                            <td class="px-6 py-3 text-right text-xs text-gray-500" x-text="sub.expires_at ? new Date(sub.expires_at).toLocaleDateString('th-TH') : 'ไม่มีวันหมดอายุ'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function aiSubs() {
    return {
        subs: [], loading: true,
        async init() {
            this.loading = true;
            // Subscriptions ยังไม่มี dedicated endpoint แยก, ใช้ data จาก packages page
            this.loading = false;
        }
    }
}
</script>
@endsection
