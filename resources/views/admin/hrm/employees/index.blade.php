@extends('layouts.admin')

@section('title', 'จัดการพนักงาน')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-blue-500 to-cyan-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">👥 จัดการพนักงาน</h1>
                <p class="text-blue-100">ระบบจัดการข้อมูลพนักงานและบุคลากร</p>
            </div>
            <a href="{{ route('admin.hrm.employees.create') }}"
               class="flex items-center gap-2 px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition-all hover:scale-105 shadow-md">
                <span class="text-2xl">➕</span>
                <span class="font-medium">เพิ่มพนักงาน</span>
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <form method="GET" action="{{ route('admin.hrm.employees.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ค้นหา</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="รหัส, ชื่อ, อีเมล, เบอร์"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">แผนก</label>
                <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ตำแหน่ง</label>
                <select name="position_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    @foreach($positions as $position)
                        <option value="{{ $position->id }}" {{ request('position_id') == $position->id ? 'selected' : '' }}>
                            {{ $position->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะการจ้าง</label>
                <select name="employment_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    <option value="active" {{ request('employment_status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="probation" {{ request('employment_status') === 'probation' ? 'selected' : '' }}>Probation</option>
                    <option value="notice_period" {{ request('employment_status') === 'notice_period' ? 'selected' : '' }}>Notice Period</option>
                    <option value="resigned" {{ request('employment_status') === 'resigned' ? 'selected' : '' }}>Resigned</option>
                    <option value="terminated" {{ request('employment_status') === 'terminated' ? 'selected' : '' }}>Terminated</option>
                    <option value="retired" {{ request('employment_status') === 'retired' ? 'selected' : '' }}>Retired</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ประเภทการจ้าง</label>
                <select name="employment_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">ทั้งหมด</option>
                    <option value="full_time" {{ request('employment_type') === 'full_time' ? 'selected' : '' }}>Full Time</option>
                    <option value="part_time" {{ request('employment_type') === 'part_time' ? 'selected' : '' }}>Part Time</option>
                    <option value="contract" {{ request('employment_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                    <option value="intern" {{ request('employment_type') === 'intern' ? 'selected' : '' }}>Intern</option>
                    <option value="freelance" {{ request('employment_type') === 'freelance' ? 'selected' : '' }}>Freelance</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    ค้นหา
                </button>
                <a href="{{ route('admin.hrm.employees.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                    ล้าง
                </a>
            </div>
        </form>
    </div>

    <!-- Export Section -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <form method="GET" action="{{ route('admin.hrm.employees.export') }}" class="flex items-center gap-4">
            <!-- Pass current filters to export -->
            <input type="hidden" name="department_id" value="{{ request('department_id') }}">
            <input type="hidden" name="employment_status" value="{{ request('employment_status') }}">

            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">จำนวนต่อหน้า:</label>
            <select name="per_page" onchange="window.location.href='{{ route('admin.hrm.employees.index') }}' + '?' + new URLSearchParams(Object.assign({}, Object.fromEntries(new URLSearchParams(window.location.search)), {per_page: this.value})).toString()"
                    class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="10" {{ request('per_page', 20) == 10 ? 'selected' : '' }}>10</option>
                <option value="20" {{ request('per_page', 20) == 20 ? 'selected' : '' }}>20</option>
                <option value="50" {{ request('per_page', 20) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('per_page', 20) == 100 ? 'selected' : '' }}>100</option>
            </select>

            <div class="flex-1"></div>

            <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                <span>📥</span>
                <span>ส่งออก CSV</span>
            </button>
        </form>
    </div>

    <!-- Employees Table -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">รหัสพนักงาน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ข้อมูลพนักงาน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">แผนก</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ตำแหน่ง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ประเภท</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">วันที่เริ่มงาน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($employees as $employee)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <code class="px-2 py-1 bg-gray-100 dark:bg-slate-700 dark:text-gray-300 rounded text-sm font-mono">
                                    {{ $employee->employee_id }}
                                </code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $employee->work_email }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $employee->mobile_phone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-white">
                                    {{ $employee->department->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-white">
                                    {{ $employee->position->title ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    {{ ucwords(str_replace('_', ' ', $employee->employment_type)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $employee->employment_status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : '' }}
                                    {{ $employee->employment_status === 'probation' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' : '' }}
                                    {{ $employee->employment_status === 'notice_period' ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300' : '' }}
                                    {{ in_array($employee->employment_status, ['resigned', 'terminated', 'retired']) ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : '' }}">
                                    {{ ucwords(str_replace('_', ' ', $employee->employment_status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $employee->hire_date ? $employee->hire_date->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.hrm.employees.show', $employee) }}"
                                       class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                       title="ดูรายละเอียด">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.hrm.employees.edit', $employee) }}"
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400"
                                       title="แก้ไข">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <span class="text-4xl mb-2">📭</span>
                                    <span>ไม่พบข้อมูลพนักงาน</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($employees->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        แสดง {{ $employees->firstItem() ?? 0 }} ถึง {{ $employees->lastItem() ?? 0 }} จากทั้งหมด {{ $employees->total() }} รายการ
                    </div>
                    <div>
                        {{ $employees->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
