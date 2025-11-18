@extends('layouts.admin-v3')

@section('title', 'ปฏิทินใบลา')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold mb-1">📅 ปฏิทินใบลา</h1>
                <p class="text-orange-100">ตารางการลาที่ได้รับการอนุมัติแล้ว</p>
            </div>
            <a href="{{ route('admin.hrm.leave.index') }}"
               class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition">
                ← กลับ
            </a>
        </div>
    </div>

    <!-- Month Navigation -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-4">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.hrm.leave.calendar', ['month' => $month == 1 ? 12 : $month - 1, 'year' => $month == 1 ? $year - 1 : $year]) }}"
               class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                ← เดือนก่อนหน้า
            </a>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year + 543 }}
            </h2>
            <a href="{{ route('admin.hrm.leave.calendar', ['month' => $month == 12 ? 1 : $month + 1, 'year' => $month == 12 ? $year + 1 : $year]) }}"
               class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">
                เดือนถัดไป →
            </a>
        </div>
    </div>

    <!-- Calendar -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        @php
            $startDate = \Carbon\Carbon::create($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            $firstDayOfWeek = $startDate->dayOfWeek;
            $daysInMonth = $startDate->daysInMonth;

            // Prepare leave data for each day
            $leavesByDay = [];
            foreach($leaveRequests as $leave) {
                $current = \Carbon\Carbon::parse($leave->start_date);
                $end = \Carbon\Carbon::parse($leave->end_date);

                while($current->lessThanOrEqualTo($end)) {
                    if($current->month == $month && $current->year == $year) {
                        $day = $current->day;
                        if(!isset($leavesByDay[$day])) {
                            $leavesByDay[$day] = [];
                        }
                        $leavesByDay[$day][] = $leave;
                    }
                    $current->addDay();
                }
            }
        @endphp

        <!-- Calendar Header -->
        <div class="grid grid-cols-7 gap-2 mb-2">
            <div class="text-center font-semibold text-gray-700 dark:text-gray-300 py-2">อา</div>
            <div class="text-center font-semibold text-gray-700 dark:text-gray-300 py-2">จ</div>
            <div class="text-center font-semibold text-gray-700 dark:text-gray-300 py-2">อ</div>
            <div class="text-center font-semibold text-gray-700 dark:text-gray-300 py-2">พ</div>
            <div class="text-center font-semibold text-gray-700 dark:text-gray-300 py-2">พฤ</div>
            <div class="text-center font-semibold text-gray-700 dark:text-gray-300 py-2">ศ</div>
            <div class="text-center font-semibold text-gray-700 dark:text-gray-300 py-2">ส</div>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-2">
            @php
                // Empty cells before first day
                for($i = 0; $i < $firstDayOfWeek; $i++) {
                    echo '<div class="min-h-[100px] bg-gray-50 dark:bg-slate-700 rounded-lg"></div>';
                }

                // Days of month
                for($day = 1; $day <= $daysInMonth; $day++) {
                    $isToday = $day == now()->day && $month == now()->month && $year == now()->year;
                    $hasLeaves = isset($leavesByDay[$day]) && count($leavesByDay[$day]) > 0;

                    echo '<div class="min-h-[100px] border border-gray-200 dark:border-gray-600 rounded-lg p-2 ' . ($isToday ? 'bg-blue-50 dark:bg-blue-900' : 'bg-white dark:bg-slate-800') . '">';
                    echo '<div class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">' . $day . '</div>';

                    if($hasLeaves) {
                        echo '<div class="space-y-1">';
                        foreach($leavesByDay[$day] as $leave) {
                            $color = $leave->leaveType->color ?? '#6b7280';
                            echo '<div class="text-xs p-1 rounded truncate" style="background-color: ' . $color . '20; color: ' . $color . '" title="' . htmlspecialchars($leave->employee->full_name . ' - ' . $leave->leaveType->name) . '">';
                            echo htmlspecialchars($leave->employee->full_name);
                            echo '</div>';
                        }
                        echo '</div>';
                    }

                    echo '</div>';
                }
            @endphp
        </div>
    </div>

    <!-- Leave Legend -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">สีประเภทการลา</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @php
                $leaveTypes = \App\Models\LeaveType::active()->get();
            @endphp
            @foreach($leaveTypes as $type)
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded" style="background-color: {{ $type->color ?? '#6b7280' }}"></div>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $type->name }}</span>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Leave List for Selected Month -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-slate-700 border-b border-gray-200 dark:border-gray-600">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">รายการลาในเดือนนี้</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">พนักงาน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ประเภท</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">เริ่ม</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">สิ้นสุด</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">จำนวนวัน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">การกระทำ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($leaveRequests as $leave)
                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700">
                    <td class="px-6 py-4 text-sm">
                        <div class="font-medium text-gray-900 dark:text-white">{{ $leave->employee->full_name }}</div>
                        <div class="text-xs text-gray-500">{{ $leave->employee->department->name ?? '' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <span class="inline-block px-2 py-1 rounded text-xs" style="background-color: {{ $leave->leaveType->color ?? '#e5e7eb' }}20; color: {{ $leave->leaveType->color ?? '#6b7280' }}">
                            {{ $leave->leaveType->name }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $leave->start_date->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $leave->end_date->format('d/m/Y') }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $leave->total_days }} วัน</td>
                    <td class="px-6 py-4 text-sm">
                        <a href="{{ route('admin.hrm.leave.show', $leave) }}" class="text-blue-600 hover:text-blue-800">ดูรายละเอียด</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">ไม่พบข้อมูลใบลาในเดือนนี้</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
