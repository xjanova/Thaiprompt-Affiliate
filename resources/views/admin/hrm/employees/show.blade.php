@extends('layouts.admin-v3')

@section('title', 'รายละเอียดพนักงาน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">{{ $employee->full_name }}</h1>
                <p class="text-blue-100">{{ $employee->position->title ?? '-' }} • {{ $employee->department->name ?? '-' }}</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.hrm.employees.edit', $employee) }}"
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                    แก้ไข
                </a>
                <a href="{{ route('admin.hrm.employees.index') }}"
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                    ← กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- Employee Info Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Personal Information -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>👤</span> ข้อมูลส่วนตัว
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">รหัสพนักงาน</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->employee_id }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">วัน/เดือน/ปีเกิด</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $employee->date_of_birth ? $employee->date_of_birth->format('d/m/Y') : '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">เพศ</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $employee->gender ? ucfirst($employee->gender) : '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">สัญชาติ</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->nationality ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">เลขบัตรประชาชน</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->id_card_number ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Employment Information -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>💼</span> ข้อมูลการจ้างงาน
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">วันที่เริ่มงาน</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $employee->hire_date ? $employee->hire_date->format('d/m/Y') : '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">ประเภทการจ้าง</dt>
                    <dd>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ ucwords(str_replace('_', ' ', $employee->employment_type)) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">สถานะ</dt>
                    <dd>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            {{ $employee->employment_status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $employee->employment_status === 'probation' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ in_array($employee->employment_status, ['resigned', 'terminated']) ? 'bg-red-100 text-red-800' : '' }}">
                            {{ ucwords(str_replace('_', ' ', $employee->employment_status)) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">ผู้จัดการ</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $employee->manager->full_name ?? '-' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">เงินเดือนพื้นฐาน</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ number_format($employee->basic_salary, 2) }} บาท
                    </dd>
                </div>
            </dl>
        </div>

        <!-- Contact Information -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📞</span> ข้อมูลการติดต่อ
            </h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">อีเมลที่ทำงาน</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white break-all">{{ $employee->work_email }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">อีเมลส่วนตัว</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white break-all">{{ $employee->personal_email ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">เบอร์โทรศัพท์</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->mobile_phone }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500 dark:text-gray-400">ที่อยู่</dt>
                    <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->current_address ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Bank Information -->
    @if($employee->bank_name || $employee->bank_account_number)
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span>🏦</span> ข้อมูลธนาคาร
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">ชื่อธนาคาร</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->bank_name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">เลขที่บัญชี</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->bank_account_number ?? '-' }}</dd>
            </div>
        </div>
    </div>
    @endif

    <!-- Tabs for Additional Info -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <div x-data="{ activeTab: 'attendance' }" class="space-y-4">
            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-slate-700">
                <nav class="-mb-px flex space-x-8">
                    <button @click="activeTab = 'attendance'"
                            :class="{'border-blue-500 text-blue-600': activeTab === 'attendance', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'attendance'}"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        การเข้างาน
                    </button>
                    <button @click="activeTab = 'leave'"
                            :class="{'border-blue-500 text-blue-600': activeTab === 'leave', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'leave'}"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        ใบลา
                    </button>
                    <button @click="activeTab = 'payroll'"
                            :class="{'border-blue-500 text-blue-600': activeTab === 'payroll', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'payroll'}"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        เงินเดือน
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div x-show="activeTab === 'attendance'" class="space-y-2">
                <h4 class="font-semibold text-gray-900 dark:text-white">การเข้างานล่าสุด</h4>
                @if($employee->attendanceRecords->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">วันที่</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">เข้างาน</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ออกงาน</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($employee->attendanceRecords->take(10) as $record)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $record->date->format('d/m/Y') }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $record->check_in_time ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $record->check_out_time ?? '-' }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $record->status === 'present' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($record->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">ไม่มีข้อมูลการเข้างาน</p>
                @endif
            </div>

            <div x-show="activeTab === 'leave'" class="space-y-2">
                <h4 class="font-semibold text-gray-900 dark:text-white">ใบลาล่าสุด</h4>
                @if($employee->leaveRequests->count() > 0)
                    <div class="space-y-2">
                        @foreach($employee->leaveRequests->take(10) as $leave)
                        <div class="p-3 bg-gray-50 dark:bg-slate-700 rounded">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium">{{ $leave->leaveType->name ?? 'N/A' }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ $leave->start_date->format('d/m/Y') }} - {{ $leave->end_date->format('d/m/Y') }}
                                        ({{ $leave->days }} วัน)
                                    </p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $leave->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $leave->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $leave->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ ucfirst($leave->status) }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">ไม่มีข้อมูลใบลา</p>
                @endif
            </div>

            <div x-show="activeTab === 'payroll'" class="space-y-2">
                <h4 class="font-semibold text-gray-900 dark:text-white">เงินเดือนล่าสุด</h4>
                @if($employee->payrollRecords->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 dark:bg-slate-700">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">ช่วงเวลา</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">รวมรายได้</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">หักเงิน</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">สุทธิ</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($employee->payrollRecords->take(12) as $payroll)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $payroll->month }}/{{ $payroll->year }}</td>
                                    <td class="px-4 py-2 text-sm">{{ number_format($payroll->gross_salary, 2) }}</td>
                                    <td class="px-4 py-2 text-sm">{{ number_format($payroll->total_deductions, 2) }}</td>
                                    <td class="px-4 py-2 text-sm font-semibold">{{ number_format($payroll->net_salary, 2) }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <span class="px-2 py-1 text-xs rounded-full
                                            {{ $payroll->status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $payroll->status === 'approved' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $payroll->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                            {{ ucfirst($payroll->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">ไม่มีข้อมูลเงินเดือน</p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush
@endsection
