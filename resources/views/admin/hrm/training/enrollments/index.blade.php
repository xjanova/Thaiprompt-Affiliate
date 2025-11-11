@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent dark:from-blue-400 dark:to-cyan-400">
                        👥 การลงทะเบียนเข้าฝึกอบรม
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">จัดการการลงทะเบียนพนักงานเข้าหลักสูตรฝึกอบรม</p>
                </div>
                <a href="{{ route('admin.hrm.training.enrollments.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    ลงทะเบียนใหม่
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">ทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $enrollments->total() }}</p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">เสร็จสิ้น</p>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">
                            {{ \App\Models\TrainingEnrollment::where('enrollment_status', 'completed')->count() }}
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">กำลังดำเนินการ</p>
                        <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                            {{ \App\Models\TrainingEnrollment::where('enrollment_status', 'in_progress')->count() }}
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">ลงทะเบียน</p>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                            {{ \App\Models\TrainingEnrollment::where('enrollment_status', 'enrolled')->count() }}
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">ไม่ผ่าน</p>
                        <p class="text-3xl font-bold text-red-600 dark:text-red-400 mt-1">
                            {{ \App\Models\TrainingEnrollment::where('enrollment_status', 'failed')->count() }}
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-pink-500 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-8 border border-gray-200 dark:border-gray-700">
            <form method="GET" action="{{ route('admin.hrm.training.enrollments.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">🔍 ค้นหาพนักงาน</label>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="ค้นหาชื่อพนักงาน..."
                               class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200">
                    </div>

                    <!-- Course Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">📚 หลักสูตร</label>
                        <select name="training_course_id"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200">
                            <option value="">ทั้งหมด</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ request('training_course_id') == $course->id ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">📊 สถานะ</label>
                        <select name="enrollment_status"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200">
                            <option value="">ทั้งหมด</option>
                            <option value="enrolled" {{ request('enrollment_status') == 'enrolled' ? 'selected' : '' }}>ลงทะเบียนแล้ว</option>
                            <option value="in_progress" {{ request('enrollment_status') == 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                            <option value="completed" {{ request('enrollment_status') == 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                            <option value="failed" {{ request('enrollment_status') == 'failed' ? 'selected' : '' }}>ไม่ผ่าน</option>
                            <option value="cancelled" {{ request('enrollment_status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                            <option value="no_show" {{ request('enrollment_status') == 'no_show' ? 'selected' : '' }}>ไม่มาเข้าร่วม</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.hrm.training.enrollments.index') }}"
                       class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200">
                        ล้างค่า
                    </a>
                    <button type="submit"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                        ค้นหา
                    </button>
                </div>
            </form>
        </div>

        <!-- Enrollments List -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-gray-700 dark:to-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">พนักงาน</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">หลักสูตร</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">วันที่</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ประเภท</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ความคืบหน้า</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($enrollments as $enrollment)
                        <tr class="hover:bg-blue-50 dark:hover:bg-gray-700 transition-all duration-150">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                                        <span class="text-white font-semibold text-sm">{{ substr($enrollment->employee->first_name, 0, 1) }}{{ substr($enrollment->employee->last_name, 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $enrollment->employee->first_name }} {{ $enrollment->employee->last_name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $enrollment->employee->employee_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $enrollment->trainingCourse->course_name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $enrollment->trainingCourse->course_code }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">เริ่ม:</span>
                                        {{ \Carbon\Carbon::parse($enrollment->training_start_date)->format('d/m/Y') }}
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">สิ้นสุด:</span>
                                        {{ \Carbon\Carbon::parse($enrollment->training_end_date)->format('d/m/Y') }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $typeConfig = [
                                        'mandatory' => ['label' => 'บังคับ', 'color' => 'red', 'icon' => '⚠️'],
                                        'voluntary' => ['label' => 'สมัครใจ', 'color' => 'green', 'icon' => '✨'],
                                        'recommended' => ['label' => 'แนะนำ', 'color' => 'blue', 'icon' => '💡'],
                                    ];
                                    $type = $typeConfig[$enrollment->enrollment_type] ?? ['label' => $enrollment->enrollment_type, 'color' => 'gray', 'icon' => '📝'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $type['color'] }}-100 dark:bg-{{ $type['color'] }}-900 text-{{ $type['color'] }}-800 dark:text-{{ $type['color'] }}-200">
                                    {{ $type['icon'] }} {{ $type['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusConfig = [
                                        'enrolled' => ['label' => 'ลงทะเบียนแล้ว', 'color' => 'blue'],
                                        'in_progress' => ['label' => 'กำลังดำเนินการ', 'color' => 'yellow'],
                                        'completed' => ['label' => 'เสร็จสิ้น', 'color' => 'green'],
                                        'failed' => ['label' => 'ไม่ผ่าน', 'color' => 'red'],
                                        'cancelled' => ['label' => 'ยกเลิก', 'color' => 'gray'],
                                        'no_show' => ['label' => 'ไม่มาเข้าร่วม', 'color' => 'orange'],
                                    ];
                                    $status = $statusConfig[$enrollment->enrollment_status] ?? ['label' => $enrollment->enrollment_status, 'color' => 'gray'];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $status['color'] }}-100 dark:bg-{{ $status['color'] }}-900 text-{{ $status['color'] }}-800 dark:text-{{ $status['color'] }}-200">
                                    @if($enrollment->enrollment_status == 'in_progress')
                                        <span class="w-2 h-2 bg-{{ $status['color'] }}-500 rounded-full mr-2 animate-pulse"></span>
                                    @endif
                                    {{ $status['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($enrollment->enrollment_status == 'completed')
                                <div class="flex flex-col items-center">
                                    <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                        {{ $enrollment->final_score ?? 'N/A' }}
                                        @if($enrollment->final_score)
                                            <span class="text-sm">%</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">คะแนน</div>
                                </div>
                                @elseif($enrollment->enrollment_status == 'in_progress')
                                <div class="flex flex-col items-center">
                                    <div class="w-full max-w-[100px]">
                                        <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full transition-all duration-300"
                                                 style="width: {{ $enrollment->attendance_percentage ?? 50 }}%"></div>
                                        </div>
                                        <div class="text-xs text-center text-gray-600 dark:text-gray-400 mt-1">
                                            {{ $enrollment->attendance_percentage ?? 50 }}%
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="text-center text-gray-400 dark:text-gray-500 text-sm">-</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.hrm.training.enrollments.show', $enrollment) }}"
                                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800 transition-all duration-200"
                                       title="ดูรายละเอียด">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.hrm.training.enrollments.edit', $enrollment) }}"
                                       class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400 hover:bg-yellow-200 dark:hover:bg-yellow-800 transition-all duration-200"
                                       title="แก้ไข">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.hrm.training.enrollments.destroy', $enrollment) }}" method="POST" class="inline-block" onsubmit="return confirm('คุณต้องการลบการลงทะเบียนนี้ใช่หรือไม่?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-800 transition-all duration-200"
                                                title="ลบ">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-24 h-24 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">ไม่พบการลงทะเบียน</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">ลองเปลี่ยนเงื่อนไขการค้นหา หรือลงทะเบียนใหม่</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($enrollments->hasPages())
            <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $enrollments->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
<div class="fixed bottom-4 right-4 z-50 animate-slide-in-up">
    <div class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center space-x-3">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
</div>
@endif
@endsection
