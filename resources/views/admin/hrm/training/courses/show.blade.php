@extends('layouts.admin-v3')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.hrm.training.courses.index') }}"
                       class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 shadow-lg transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent dark:from-blue-400 dark:to-cyan-400">
                            📚 {{ $course->course_name }}
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">รหัส: {{ $course->course_code }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.hrm.training.courses.edit', $course) }}"
                       class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        แก้ไข
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">ผู้เข้าร่วม</p>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                            {{ $course->enrollments->count() }}
                            @if($course->max_participants)
                                <span class="text-lg text-gray-500 dark:text-gray-400">/{{ $course->max_participants }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700 transform hover:scale-105 transition-all duration-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">เสร็จสิ้น</p>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">
                            {{ $course->enrollments->where('enrollment_status', 'completed')->count() }}
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
                            {{ $course->enrollments->where('enrollment_status', 'in_progress')->count() }}
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
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">ระยะเวลา</p>
                        <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-1">{{ $course->course_duration_hours }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">ชั่วโมง</p>
                    </div>
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Course Details -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">รายละเอียด</h2>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">คำอธิบาย</h3>
                            <p class="text-gray-900 dark:text-gray-100 leading-relaxed">{{ $course->course_description }}</p>
                        </div>

                        @if($course->course_objectives)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">วัตถุประสงค์</h3>
                            <p class="text-gray-900 dark:text-gray-100 leading-relaxed">{{ $course->course_objectives }}</p>
                        </div>
                        @endif

                        @if($course->prerequisites)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">ข้อกำหนดเบื้องต้น</h3>
                            <p class="text-gray-900 dark:text-gray-100 leading-relaxed">{{ $course->prerequisites }}</p>
                        </div>
                        @endif

                        @if($course->course_materials)
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">เอกสารประกอบ</h3>
                            <p class="text-gray-900 dark:text-gray-100 leading-relaxed">{{ $course->course_materials }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Enrollments List -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ผู้เข้าร่วมหลักสูตร</h2>
                            </div>
                            <span class="px-4 py-2 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-xl font-semibold">
                                {{ $course->enrollments->count() }} คน
                            </span>
                        </div>
                    </div>

                    @if($course->enrollments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">พนักงาน</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">วันที่เริ่ม</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">สถานะ</th>
                                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">ความคืบหน้า</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($course->enrollments as $enrollment)
                                <tr class="hover:bg-blue-50 dark:hover:bg-gray-700/50 transition-all duration-150">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center mr-3">
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
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ \Carbon\Carbon::parse($enrollment->training_start_date)->format('d/m/Y') }}
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
                                            {{ $status['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($enrollment->enrollment_status == 'completed')
                                        <div class="flex items-center justify-center">
                                            <div class="text-center">
                                                <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                                    {{ $enrollment->final_score ?? 'N/A' }}
                                                    @if($enrollment->final_score)
                                                        <span class="text-sm">%</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">คะแนน</div>
                                            </div>
                                        </div>
                                        @elseif($enrollment->enrollment_status == 'in_progress')
                                        <div class="flex items-center justify-center">
                                            <div class="w-24">
                                                <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                    <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full animate-pulse"
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
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="p-12 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-600 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">ยังไม่มีผู้เข้าร่วม</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Course Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลหลักสูตร</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400">หมวดหมู่</p>
                                @php
                                    $categoryConfig = [
                                        'technical' => ['label' => 'เทคนิค', 'color' => 'blue'],
                                        'soft_skills' => ['label' => 'ทักษะนุ่ม', 'color' => 'purple'],
                                        'leadership' => ['label' => 'ความเป็นผู้นำ', 'color' => 'yellow'],
                                        'compliance' => ['label' => 'การปฏิบัติตาม', 'color' => 'red'],
                                        'safety' => ['label' => 'ความปลอดภัย', 'color' => 'orange'],
                                        'product' => ['label' => 'ผลิตภัณฑ์', 'color' => 'green'],
                                        'sales' => ['label' => 'การขาย', 'color' => 'pink'],
                                        'other' => ['label' => 'อื่นๆ', 'color' => 'gray'],
                                    ];
                                    $category = $categoryConfig[$course->course_category] ?? ['label' => $course->course_category, 'color' => 'gray'];
                                @endphp
                                <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $category['label'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400">วิธีการฝึกอบรม</p>
                                @php
                                    $methodConfig = [
                                        'classroom' => ['label' => 'ในห้องเรียน', 'icon' => '🏫'],
                                        'online' => ['label' => 'ออนไลน์', 'icon' => '💻'],
                                        'hybrid' => ['label' => 'ผสมผสาน', 'icon' => '🔄'],
                                        'on_the_job' => ['label' => 'ในงาน', 'icon' => '👷'],
                                        'workshop' => ['label' => 'เวิร์คช็อป', 'icon' => '🛠️'],
                                        'seminar' => ['label' => 'สัมมนา', 'icon' => '🎤'],
                                    ];
                                    $method = $methodConfig[$course->training_method] ?? ['label' => $course->training_method, 'icon' => '📚'];
                                @endphp
                                <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $method['icon'] }} {{ $method['label'] }}</p>
                            </div>
                        </div>

                        @if($course->max_participants)
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400">จำนวนผู้เข้าร่วมสูงสุด</p>
                                <p class="text-gray-900 dark:text-gray-100 font-medium">{{ $course->max_participants }} คน</p>
                            </div>
                        </div>
                        @endif

                        @if($course->cost_per_participant)
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400">ค่าใช้จ่ายต่อคน</p>
                                <p class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($course->cost_per_participant, 2) }} บาท</p>
                            </div>
                        </div>
                        @endif

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400">สถานะ</p>
                                @if($course->status == 'active')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                        เปิดใช้งาน
                                    </span>
                                @elseif($course->status == 'inactive')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                        <span class="w-2 h-2 bg-gray-500 rounded-full mr-2"></span>
                                        ปิดใช้งาน
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200">
                                        <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span>
                                        จัดเก็บ
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Certification -->
                @if($course->certification_provided)
                <div class="bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                        <h3 class="text-xl font-bold">มีใบรับรอง</h3>
                    </div>
                    @if($course->certification_name)
                    <p class="text-white/90">{{ $course->certification_name }}</p>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
