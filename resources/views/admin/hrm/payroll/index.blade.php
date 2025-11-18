@extends('layouts.admin-v3')

@section('title', 'จัดการเงินเดือน')

@section('content')
<div class="space-y-6">
    <div class="bg-gradient-to-r from-red-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <h1 class="text-2xl font-bold mb-1">💰 จัดการเงินเดือน</h1>
        <p class="text-red-100">จัดการและอนุมัติการจ่ายเงินเดือนพนักงาน</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">แบบร่าง</p>
            <p class="text-2xl font-bold text-gray-600">{{ $stats['draft'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">รออนุมัติ</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">อนุมัติแล้ว</p>
            <p class="text-2xl font-bold text-blue-600">{{ $stats['approved'] }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <p class="text-sm text-gray-500">จ่ายแล้ว</p>
            <p class="text-2xl font-bold text-green-600">{{ $stats['paid'] }}</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <select name="month" class="px-3 py-2 border border-gray-300 rounded-lg">
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endfor
            </select>
            <select name="year" class="px-3 py-2 border border-gray-300 rounded-lg">
                @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
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
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                ค้นหา
            </button>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <div class="flex justify-between items-center">
            <div class="text-sm text-gray-600">
                <p>รวมรายได้: <span class="font-bold">{{ number_format($totals['gross'], 2) }}</span> บาท</p>
                <p>รวมหักเงิน: <span class="font-bold">{{ number_format($totals['deductions'], 2) }}</span> บาท</p>
                <p>รวมสุทธิ: <span class="font-bold text-green-600">{{ number_format($totals['net'], 2) }}</span> บาท</p>
            </div>
            <a href="{{ route('admin.hrm.payroll.export', request()->query()) }}"
               class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Export CSV
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">พนักงาน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ช่วงเวลา</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">รวมรายได้</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">หักเงิน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สุทธิ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">การกระทำ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($payrolls as $payroll)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium">
                        <div>{{ $payroll->employee->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $payroll->employee->department->name ?? '' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $payroll->month }}/{{ $payroll->year }}</td>
                    <td class="px-6 py-4 text-sm">{{ number_format($payroll->gross_salary, 2) }}</td>
                    <td class="px-6 py-4 text-sm">{{ number_format($payroll->total_deductions, 2) }}</td>
                    <td class="px-6 py-4 text-sm font-bold">{{ number_format($payroll->net_salary, 2) }}</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full
                            {{ $payroll->status === 'draft' ? 'bg-gray-100 text-gray-800' : '' }}
                            {{ $payroll->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $payroll->status === 'approved' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $payroll->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}">
                            {{ ucfirst($payroll->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <a href="{{ route('admin.hrm.payroll.show', $payroll) }}" class="text-blue-600 hover:text-blue-800">ดูรายละเอียด</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">ไม่พบข้อมูลเงินเดือน</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($payrolls->hasPages())
        <div class="px-6 py-4 border-t">
            {{ $payrolls->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
