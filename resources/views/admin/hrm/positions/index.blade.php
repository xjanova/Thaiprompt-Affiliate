@extends('layouts.admin-v3')

@section('title', 'จัดการตำแหน่ง')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">💼 จัดการตำแหน่ง</h1>
                <p class="text-green-100">จัดการตำแหน่งงานในองค์กร</p>
            </div>
            <a href="{{ route('admin.hrm.positions.create') }}"
               class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition">
                เพิ่มตำแหน่ง
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา..."
                   class="px-3 py-2 border border-gray-300 rounded-lg">
            <select name="department_id" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">ทุกแผนก</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                ค้นหา
            </button>
            <a href="{{ route('admin.hrm.positions.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-center">
                ล้าง
            </a>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">รหัส</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ตำแหน่ง</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">แผนก</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ระดับ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">เงินเดือน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">การกระทำ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($positions as $position)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">{{ $position->position_code }}</td>
                    <td class="px-6 py-4 text-sm font-medium">{{ $position->position_name }}</td>
                    <td class="px-6 py-4 text-sm">{{ $position->department->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $position->level ? ucfirst($position->level) : '-' }}</td>
                    <td class="px-6 py-4 text-sm">
                        @if($position->min_salary && $position->max_salary)
                            {{ number_format($position->min_salary) }} - {{ number_format($position->max_salary) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $position->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $position->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <div class="flex gap-2">
                            <a href="{{ route('admin.hrm.positions.show', $position) }}" class="text-blue-600 hover:text-blue-800">ดู</a>
                            <a href="{{ route('admin.hrm.positions.edit', $position) }}" class="text-green-600 hover:text-green-800">แก้ไข</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">ไม่พบข้อมูลตำแหน่ง</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($positions->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $positions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
