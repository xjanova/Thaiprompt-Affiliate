@extends('layouts.admin-v3')

@section('title', 'รายละเอียดตำแหน่ง')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">💼 {{ $position->position_name }}</h1>
                <p class="text-green-100">รหัส: {{ $position->position_code }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.hrm.positions.edit', $position) }}"
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                    ✏️ แก้ไข
                </a>
                <a href="{{ route('admin.hrm.positions.index') }}"
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                    ← กลับ
                </a>
            </div>
        </div>
    </div>

    <!-- Basic Information -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ข้อมูลพื้นฐาน</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">ชื่อตำแหน่ง</p>
                <p class="text-base font-medium text-gray-900 dark:text-white">{{ $position->position_name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">รหัสตำแหน่ง</p>
                <p class="text-base font-medium text-gray-900 dark:text-white">{{ $position->position_code }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">แผนก</p>
                <p class="text-base font-medium text-gray-900 dark:text-white">{{ $position->department->name ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">ระดับ</p>
                <p class="text-base font-medium text-gray-900 dark:text-white">{{ $position->level ? ucfirst($position->level) : '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">ช่วงเงินเดือน</p>
                <p class="text-base font-medium text-gray-900 dark:text-white">
                    @if($position->min_salary && $position->max_salary)
                        {{ number_format($position->min_salary) }} - {{ number_format($position->max_salary) }} บาท
                    @else
                        -
                    @endif
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">สถานะ</p>
                <p class="text-base font-medium">
                    <span class="px-3 py-1 text-sm rounded-full {{ $position->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $position->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </p>
            </div>
        </div>

        @if($position->description)
        <div class="mt-6">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">คำอธิบาย</p>
            <p class="text-base text-gray-900 dark:text-white">{{ $position->description }}</p>
        </div>
        @endif
    </div>

    <!-- Responsibilities and Requirements -->
    @if($position->responsibilities || $position->requirements)
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ความรับผิดชอบและคุณสมบัติ</h3>

        @if($position->responsibilities)
        <div class="mb-6">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">ความรับผิดชอบ</p>
            <div class="text-base text-gray-900 dark:text-white whitespace-pre-line">{{ $position->responsibilities }}</div>
        </div>
        @endif

        @if($position->requirements)
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">คุณสมบัติที่ต้องการ</p>
            <div class="text-base text-gray-900 dark:text-white whitespace-pre-line">{{ $position->requirements }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Employees in this position -->
    @if(isset($position->employees) && $position->employees->count() > 0)
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">พนักงานในตำแหน่งนี้ ({{ $position->employees->count() }})</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">รหัสพนักงาน</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ชื่อ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สถานะ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($position->employees as $employee)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm">{{ $employee->employee_id }}</td>
                        <td class="px-6 py-4 text-sm font-medium">{{ $employee->full_name }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full {{ $employee->employment_status == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($employee->employment_status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('admin.hrm.employees.show', $employee) }}" class="text-blue-600 hover:text-blue-800">ดู</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Delete Button -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-red-600 mb-2">ลบตำแหน่ง</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
            การลบตำแหน่งนี้จะไม่สามารถกู้คืนได้
            @if($position->employees && $position->employees->count() > 0)
                <span class="text-red-600 font-medium">ไม่สามารถลบตำแหน่งที่มีพนักงานได้</span>
            @endif
        </p>
        @if(!$position->employees || $position->employees->count() === 0)
        <form method="POST" action="{{ route('admin.hrm.positions.destroy', $position) }}"
              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบตำแหน่งนี้?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                ลบตำแหน่ง
            </button>
        </form>
        @else
        <button type="button" disabled class="px-6 py-2 bg-gray-300 text-gray-500 rounded-lg cursor-not-allowed">
            ลบตำแหน่ง
        </button>
        @endif
    </div>
</div>
@endsection
