@extends('layouts.admin-v3')

@section('title', 'บันทึกเข้างาน')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-yellow-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
        <h1 class="text-2xl font-bold mb-1">📋 บันทึกการเข้างาน</h1>
        <p class="text-yellow-100">ติดตามและจัดการการเข้างานของพนักงาน</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">รวมทั้งหมด</p>
            <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">เข้างาน</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['present'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">ขาดงาน</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['absent'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">สาย</p>
            <p class="text-2xl font-bold text-orange-600">{{ $stats['late'] }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="date" name="date" value="{{ $date }}"
                   class="px-3 py-2 border border-gray-300 rounded-lg">
            <select name="department_id" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">ทุกแผนก</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">ทุกสถานะ</option>
                <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                <option value="leave" {{ request('status') == 'leave' ? 'selected' : '' }}>Leave</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                ค้นหา
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">พนักงาน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">แผนก</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">เข้างาน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ออกงาน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">หมายเหตุ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($attendances as $attendance)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium">
                        {{ $attendance->employee->full_name }}
                    </td>
                    <td class="px-6 py-4 text-sm">
                        {{ $attendance->employee->department->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $attendance->date->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-sm">{{ $attendance->check_in_time ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $attendance->check_out_time ?? '-' }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full
                            {{ $attendance->status === 'present' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $attendance->status === 'absent' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $attendance->status === 'leave' ? 'bg-blue-100 text-blue-800' : '' }}">
                            {{ ucfirst($attendance->status) }}
                            @if($attendance->is_late) (สาย) @endif
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $attendance->remarks ?? '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">ไม่พบข้อมูลการเข้างาน</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($attendances->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $attendances->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
