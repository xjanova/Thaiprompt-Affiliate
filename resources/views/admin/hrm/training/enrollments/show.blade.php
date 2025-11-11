@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.hrm.training.enrollments.index') }}"
                       class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 shadow-lg transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent dark:from-blue-400 dark:to-cyan-400">
                            📋 รายละเอียดการลงทะเบียน
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">ข้อมูลการเข้าฝึกอบรม</p>
                    </div>
                </div>
                <a href="{{ route('admin.hrm.training.enrollments.edit', $enrollment) }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    แก้ไข
                </a>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="mb-8">
            @php
                $statusConfig = [
                    'enrolled' => ['label' => 'ลงทะเบียนแล้ว', 'gradient' => 'from-blue-500 to-indigo-500', 'icon' => '📝'],
                    'in_progress' => ['label' => 'กำลังดำเนินการ', 'gradient' => 'from-yellow-500 to-orange-500', 'icon' => '⚡'],
                    'completed' => ['label' => 'เสร็จสิ้น', 'gradient' => 'from-green-500 to-emerald-500', 'icon' => '✅'],
                    'failed' => ['label' => 'ไม่ผ่าน', 'gradient' => 'from-red-500 to-pink-500', 'icon' => '❌'],
                    'cancelled' => ['label' => 'ยกเลิก', 'gradient' => 'from-gray-500 to-gray-600', 'icon' => '🚫'],
                    'no_show' => ['label' => 'ไม่มาเข้าร่วม', 'gradient' => 'from-orange-500 to-red-500', 'icon' => '⚠️'],
                ];
                $status = $statusConfig[$enrollment->enrollment_status] ?? ['label' => $enrollment->enrollment_status, 'gradient' => 'from-gray-500 to-gray-600', 'icon' => '📌'];
            @endphp
            <div class="bg-gradient-to-r {{ $status['gradient'] }} rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-white/20 rounded-xl flex items-center justify-center text-3xl">
                            {{ $status['icon'] }}
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold">สถานะ: {{ $status['label'] }}</h2>
                            <p class="text-white/80 mt-1">
                                ลงทะเบียนเมื่อ {{ \Carbon\Carbon::parse($enrollment->enrollment_date)->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>
                    @if($enrollment->enrollment_status == 'completed' && $enrollment->final_score)
                    <div class="text-right">
                        <div class="text-4xl font-bold">{{ $enrollment->final_score }}%</div>
                        <div class="text-white/80 text-sm">คะแนนสอบ</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Employee & Course Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ข้อมูลพนักงาน</h2>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center gap-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-xl">{{ substr($enrollment->employee->first_name, 0, 1) }}{{ substr($enrollment->employee->last_name, 0, 1) }}</span>
                            </div>
                            <div>
                                <div class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ $enrollment->employee->first_name }} {{ $enrollment->employee->last_name }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    รหัสพนักงาน: {{ $enrollment->employee->employee_id }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    ตำแหน่ง: {{ $enrollment->employee->position }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Details -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">หลักสูตร</h2>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                                {{ $enrollment->trainingCourse->course_name }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                รหัส: {{ $enrollment->trainingCourse->course_code }}
                            </p>
                            <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                                {{ $enrollment->trainingCourse->course_description }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">ระยะเวลา</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $enrollment->trainingCourse->course_duration_hours }} ชั่วโมง
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">วิธีการ</p>
                                @php
                                    $methodConfig = [
                                        'classroom' => ['label' => 'ในห้องเรียน', 'icon' => '🏫'],
                                        'online' => ['label' => 'ออนไลน์', 'icon' => '💻'],
                                        'hybrid' => ['label' => 'ผสมผสาน', 'icon' => '🔄'],
                                        'on_the_job' => ['label' => 'ในงาน', 'icon' => '👷'],
                                        'workshop' => ['label' => 'เวิร์คช็อป', 'icon' => '🛠️'],
                                        'seminar' => ['label' => 'สัมมนา', 'icon' => '🎤'],
                                    ];
                                    $method = $methodConfig[$enrollment->trainingCourse->training_method] ?? ['label' => $enrollment->trainingCourse->training_method, 'icon' => '📚'];
                                @endphp
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $method['icon'] }} {{ $method['label'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Progress & Results -->
                @if($enrollment->enrollment_status == 'completed' || $enrollment->enrollment_status == 'in_progress')
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-500 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ผลการเรียน</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Attendance -->
                        @if($enrollment->attendance_percentage !== null)
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">การเข้าร่วม</span>
                                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $enrollment->attendance_percentage }}%</span>
                            </div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full transition-all duration-500"
                                     style="width: {{ $enrollment->attendance_percentage }}%"></div>
                            </div>
                        </div>
                        @endif

                        <!-- Final Score -->
                        @if($enrollment->final_score !== null)
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">คะแนนสอบ</span>
                                <span class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $enrollment->final_score }}%</span>
                            </div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-full transition-all duration-500"
                                     style="width: {{ $enrollment->final_score }}%"></div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Completion Date -->
                    @if($enrollment->completion_date)
                    <div class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">วันที่เสร็จสิ้น</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($enrollment->completion_date)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Feedback & Comments -->
                @if($enrollment->feedback || $enrollment->trainer_comments)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ความคิดเห็น</h2>
                    </div>

                    @if($enrollment->feedback)
                    <div class="mb-4">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">ความคิดเห็นจากผู้เข้าร่วม</h3>
                        <p class="text-gray-900 dark:text-gray-100 leading-relaxed">{{ $enrollment->feedback }}</p>
                    </div>
                    @endif

                    @if($enrollment->trainer_comments)
                    <div class="{{ $enrollment->feedback ? 'pt-4 border-t border-gray-200 dark:border-gray-700' : '' }}">
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">ความคิดเห็นจากผู้ฝึกสอน</h3>
                        <p class="text-gray-900 dark:text-gray-100 leading-relaxed">{{ $enrollment->trainer_comments }}</p>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Training Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">ข้อมูลการฝึกอบรม</h3>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400">วันที่เริ่มต้น</p>
                                <p class="text-gray-900 dark:text-gray-100 font-medium">
                                    {{ \Carbon\Carbon::parse($enrollment->training_start_date)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400">วันที่สิ้นสุด</p>
                                <p class="text-gray-900 dark:text-gray-100 font-medium">
                                    {{ \Carbon\Carbon::parse($enrollment->training_end_date)->format('d/m/Y') }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400">ประเภท</p>
                                @php
                                    $typeConfig = [
                                        'mandatory' => ['label' => 'บังคับ', 'icon' => '⚠️'],
                                        'voluntary' => ['label' => 'สมัครใจ', 'icon' => '✨'],
                                        'recommended' => ['label' => 'แนะนำ', 'icon' => '💡'],
                                    ];
                                    $type = $typeConfig[$enrollment->enrollment_type] ?? ['label' => $enrollment->enrollment_type, 'icon' => '📝'];
                                @endphp
                                <p class="text-gray-900 dark:text-gray-100 font-medium">
                                    {{ $type['icon'] }} {{ $type['label'] }}
                                </p>
                            </div>
                        </div>

                        @if($enrollment->enrolledBy)
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400">ลงทะเบียนโดย</p>
                                <p class="text-gray-900 dark:text-gray-100 font-medium">
                                    {{ $enrollment->enrolledBy->name }}
                                </p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-4">การดำเนินการ</h3>
                    <div class="space-y-3">
                        <a href="{{ route('admin.hrm.training.courses.show', $enrollment->trainingCourse) }}"
                           class="flex items-center gap-3 p-3 bg-white/20 hover:bg-white/30 rounded-xl transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <span>ดูหลักสูตร</span>
                        </a>
                        <a href="{{ route('admin.hrm.training.enrollments.edit', $enrollment) }}"
                           class="flex items-center gap-3 p-3 bg-white/20 hover:bg-white/30 rounded-xl transition-all duration-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>แก้ไขข้อมูล</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
