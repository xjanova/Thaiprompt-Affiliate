@extends('layouts.admin-v3')

@section('title', 'จัดการแผนก')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">🏢 จัดการแผนก</h1>
                <p class="text-purple-100">จัดการโครงสร้างแผนกขององค์กร</p>
            </div>
            <a href="{{ route('admin.hrm.departments.create') }}"
               class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition">
                เพิ่มแผนก
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <form method="GET" class="flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาแผนก..."
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                ค้นหา
            </button>
            <a href="{{ route('admin.hrm.departments.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">
                ล้าง
            </a>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">รหัส</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อแผนก</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">แผนกแม่</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ผู้จัดการ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">พนักงาน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">การกระทำ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($departments as $department)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">{{ $department->code }}</td>
                    <td class="px-6 py-4 text-sm font-medium">{{ $department->name }}</td>
                    <td class="px-6 py-4 text-sm">{{ $department->parent->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $department->manager->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $department->employees_count ?? 0 }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $department->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $department->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex gap-2">
                            <a href="{{ route('admin.hrm.departments.show', $department) }}" class="text-blue-600 hover:text-blue-800">ดู</a>
                            <a href="{{ route('admin.hrm.departments.edit', $department) }}" class="text-green-600 hover:text-green-800">แก้ไข</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">ไม่พบข้อมูลแผนก</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($departments->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $departments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
