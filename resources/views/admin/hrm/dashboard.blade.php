@extends('layouts.admin')

@section('title', 'HRM Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <h1 class="text-2xl font-bold mb-1">📊 HRM Dashboard</h1>
        <p class="text-purple-100">ภาพรวมระบบบริหารทรัพยากรบุคคล</p>
    </div>

    <!-- Key Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Employees -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">พนักงานทั้งหมด</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total_employees'] }}</p>
                </div>
                <div class="text-3xl">👥</div>
            </div>
        </div>

        <!-- Active Employees -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">พนักงานที่ทำงาน</p>
                    <p class="text-2xl font-bold text-green-600">{{ $stats['active_employees'] }}</p>
                </div>
                <div class="text-3xl">✅</div>
            </div>
        </div>

        <!-- On Probation -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ทดลองงาน</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $stats['on_probation'] }}</p>
                </div>
                <div class="text-3xl">⏳</div>
            </div>
        </div>

        <!-- New Hires This Month -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เข้าใหม่เดือนนี้</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $stats['new_hires_this_month'] }}</p>
                </div>
                <div class="text-3xl">🆕</div>
            </div>
        </div>

        <!-- Average Tenure -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">อายุงานเฉลี่ย</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $stats['average_tenure_years'] }} ปี</p>
                </div>
                <div class="text-3xl">📅</div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Attendance Today -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">📋</div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เข้างานวันนี้</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['attendance_today'] }} คน</p>
                </div>
            </div>
        </div>

        <!-- Pending Leaves -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">🏖️</div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">ใบลารออนุมัติ</p>
                    <p class="text-xl font-bold text-orange-600">{{ $stats['pending_leaves'] }} รายการ</p>
                </div>
            </div>
            <a href="{{ route('admin.hrm.leave.index', ['status' => 'pending']) }}"
               class="text-sm text-blue-600 hover:text-blue-800 mt-2 inline-block">
                ดูทั้งหมด →
            </a>
        </div>

        <!-- Pending Payrolls -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
            <div class="flex items-center gap-3">
                <div class="text-3xl">💰</div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">เงินเดือนรออนุมัติ</p>
                    <p class="text-xl font-bold text-red-600">{{ $stats['pending_payrolls'] }} รายการ</p>
                </div>
            </div>
            <a href="{{ route('admin.hrm.payroll.index', ['status' => 'pending']) }}"
               class="text-sm text-blue-600 hover:text-blue-800 mt-2 inline-block">
                ดูทั้งหมด →
            </a>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Employees by Department -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">พนักงานตามแผนก</h3>
            <div class="space-y-3">
                @forelse($employeesByDepartment as $dept)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $dept->name }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $dept->total }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full"
                                 style="width: {{ ($dept->total / $stats['active_employees']) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>

        <!-- Employees by Type -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ประเภทการจ้างงาน</h3>
            <div class="space-y-3">
                @forelse($employeesByType as $type)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ ucwords(str_replace('_', ' ', $type->employment_type)) }}</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $type->total }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full"
                                 style="width: {{ ($type->total / $stats['active_employees']) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Headcount Trend -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">แนวโน้มจำนวนพนักงาน (12 เดือนที่ผ่านมา)</h3>
        <div class="overflow-x-auto">
            <div class="flex items-end justify-between gap-2 min-w-max" style="height: 200px;">
                @foreach($headcountTrend as $month)
                    <div class="flex flex-col items-center flex-1">
                        <span class="text-xs font-semibold mb-1 text-gray-900 dark:text-white">{{ $month['headcount'] }}</span>
                        <div class="w-full bg-indigo-500 rounded-t transition-all hover:bg-indigo-600"
                             style="height: {{ $month['headcount'] > 0 ? ($month['headcount'] / max(array_column($headcountTrend, 'headcount'))) * 180 : 5 }}px; min-height: 5px;">
                        </div>
                        <span class="text-xs text-gray-600 dark:text-gray-400 mt-2 whitespace-nowrap">{{ $month['month'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Upcoming Events -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Upcoming Birthdays -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>🎂</span> วันเกิดที่จะถึง (30 วัน)
            </h3>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse($upcomingBirthdays as $employee)
                    <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-slate-700 rounded">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->full_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($employee->date_of_birth)->format('d M') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center text-sm py-4">ไม่มีวันเกิดในช่วงนี้</p>
                @endforelse
            </div>
        </div>

        <!-- Upcoming Anniversaries -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>🎉</span> ครบรอบการทำงาน (30 วัน)
            </h3>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse($upcomingAnniversaries as $employee)
                    @php
                        $years = \Carbon\Carbon::parse($employee->hire_date)->diffInYears(now());
                    @endphp
                    <div class="flex items-center gap-3 p-2 bg-gray-50 dark:bg-slate-700 rounded">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $employee->full_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ \Carbon\Carbon::parse($employee->hire_date)->format('d M') }} - {{ $years }} ปี
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center text-sm py-4">ไม่มีครบรอบในช่วงนี้</p>
                @endforelse
            </div>
        </div>

        <!-- Expiring Documents -->
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span>📄</span> เอกสารใกล้หมดอายุ (30 วัน)
            </h3>
            <div class="space-y-3 max-h-64 overflow-y-auto">
                @forelse($expiringDocuments as $doc)
                    <div class="flex items-center gap-3 p-2 bg-red-50 dark:bg-red-900/20 rounded border border-red-200 dark:border-red-800">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $doc->employee->full_name }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $doc->document_type }}</p>
                            <p class="text-xs text-red-600 dark:text-red-400">
                                หมดอายุ: {{ \Carbon\Carbon::parse($doc->expiry_date)->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center text-sm py-4">ไม่มีเอกสารที่ใกล้หมดอายุ</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">เมนูด่วน</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <a href="{{ route('admin.hrm.employees.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                <span class="text-3xl">👥</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">พนักงาน</span>
            </a>
            <a href="{{ route('admin.hrm.departments.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition">
                <span class="text-3xl">🏢</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">แผนก</span>
            </a>
            <a href="{{ route('admin.hrm.positions.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition">
                <span class="text-3xl">💼</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">ตำแหน่ง</span>
            </a>
            <a href="{{ route('admin.hrm.attendance.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition">
                <span class="text-3xl">📋</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">เข้างาน</span>
            </a>
            <a href="{{ route('admin.hrm.leave.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition">
                <span class="text-3xl">🏖️</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">ใบลา</span>
            </a>
            <a href="{{ route('admin.hrm.payroll.index') }}"
               class="flex flex-col items-center gap-2 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition">
                <span class="text-3xl">💰</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">เงินเดือน</span>
            </a>
        </div>
    </div>
</div>
@endsection
