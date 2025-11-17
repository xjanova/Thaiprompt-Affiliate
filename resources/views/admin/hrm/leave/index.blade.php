@extends('layouts.admin-v3')

@section('title', 'จัดการใบลา')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-orange-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
        <h1 class="text-2xl font-bold mb-1">🏖️ จัดการใบลา</h1>
        <p class="text-orange-100">อนุมัติและติดตามคำขอลาของพนักงาน</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">รออนุมัติ</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">อนุมัติแล้ว</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">ไม่อนุมัติ</p>
            <p class="text-2xl font-bold text-red-600">{{ $stats['rejected'] }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">ทุกสถานะ</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
            <select name="department_id" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">ทุกแผนก</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
            <select name="leave_type_id" class="px-3 py-2 border border-gray-300 rounded-lg">
                <option value="">ทุกประเภท</option>
                @foreach($leaveTypes as $type)
                    <option value="{{ $type->id }}" {{ request('leave_type_id') == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                ค้นหา
            </button>
            <a href="{{ route('admin.hrm.leave.calendar') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-center hover:bg-blue-700">
                ปฏิทิน
            </a>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">พนักงาน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">เริ่ม</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สิ้นสุด</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">จำนวนวัน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">การกระทำ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($leaveRequests as $leave)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium">
                        <div>{{ $leave->employee->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $leave->employee->department->name ?? '' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $leave->leaveType->name ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $leave->start_date->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-sm">{{ $leave->end_date->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-sm">{{ $leave->days }} วัน</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full
                            {{ $leave->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $leave->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $leave->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucfirst($leave->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <a href="{{ route('admin.hrm.leave.show', $leave) }}" class="text-blue-600 hover:text-blue-800">ดูรายละเอียด</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">ไม่พบข้อมูลใบลา</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($leaveRequests->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $leaveRequests->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
