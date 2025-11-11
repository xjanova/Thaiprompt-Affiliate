@extends('layouts.admin')

@section('title', 'เทมเพลตการประเมินผลการปฏิบัติงาน')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">เทมเพลตการประเมินผลการปฏิบัติงาน</h1>
                <p class="text-purple-100">จัดการเทมเพลตสำหรับการประเมินผลการปฏิบัติงานของพนักงาน</p>
            </div>
            <a href="{{ route('admin.hrm.performance.templates.create') }}"
               class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition">
                เพิ่มเทมเพลต
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาเทมเพลต..."
                   class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
            <select name="status" class="px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg">
                <option value="">ทุกสถานะ</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                ค้นหา
            </button>
            <a href="{{ route('admin.hrm.performance.templates.index') }}" class="px-4 py-2 bg-gray-200 dark:bg-slate-600 dark:text-white rounded-lg text-center">
                ล้าง
            </a>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">ชื่อเทมเพลต</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">คำอธิบาย</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">จำนวนหัวข้อ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">วันที่สร้าง</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">การกระทำ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($templates as $template)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ $template->name ?? 'ไม่มีชื่อ' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($template->description ?? '-', 50) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $template->criteria_count ?? 0 }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $template->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                            {{ $template->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $template->created_at ? $template->created_at->format('d/m/Y') : '-' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex gap-2">
                            <a href="{{ route('admin.hrm.performance.templates.show', $template) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">ดู</a>
                            <a href="{{ route('admin.hrm.performance.templates.edit', $template) }}" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">แก้ไข</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        <div class="flex flex-col items-center">
                            <svg class="w-12 h-12 text-gray-400 dark:text-gray-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg font-medium">ไม่พบเทมเพลตการประเมิน</p>
                            <p class="text-sm mt-1">เริ่มต้นสร้างเทมเพลตการประเมินผลการปฏิบัติงาน</p>
                            <a href="{{ route('admin.hrm.performance.templates.create') }}" class="mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                สร้างเทมเพลตแรก
                            </a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($templates->hasPages())
        <div class="px-6 py-4 border-t dark:border-gray-700">
            {{ $templates->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
