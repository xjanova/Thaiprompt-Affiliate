@extends('layouts.admin')

@section('title', 'รายละเอียดใบสมัครงาน')

@section('content')
<div class="space-y-6">
    <!-- Header with Gradient -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center text-2xl font-bold">
                        {{ strtoupper(substr($application->first_name, 0, 1)) }}{{ strtoupper(substr($application->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold">{{ $application->first_name }} {{ $application->last_name }}</h1>
                        <p class="text-purple-100">สมัครตำแหน่ง: {{ $application->jobPosting->job_title ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.hrm.recruitment.applications.edit', $application) }}"
                   class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    <span>แก้ไข</span>
                </a>
                <a href="{{ route('admin.hrm.recruitment.applications.index') }}"
                   class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับ</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Status Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">สถานะการสมัคร</p>
                @php
                    $statusConfig = [
                        'new' => ['class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300', 'icon' => 'fa-star', 'label' => 'ใหม่'],
                        'under_review' => ['class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', 'icon' => 'fa-search', 'label' => 'กำลังพิจารณา'],
                        'shortlisted' => ['class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', 'icon' => 'fa-check', 'label' => 'ผ่านเข้ารอบ'],
                        'interview_scheduled' => ['class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300', 'icon' => 'fa-calendar-check', 'label' => 'นัดสัมภาษณ์'],
                        'interviewed' => ['class' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300', 'icon' => 'fa-user-check', 'label' => 'สัมภาษณ์แล้ว'],
                        'offer_extended' => ['class' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300', 'icon' => 'fa-hand-holding-heart', 'label' => 'เสนอตำแหน่ง'],
                        'hired' => ['class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300', 'icon' => 'fa-user-tie', 'label' => 'จ้างแล้ว'],
                        'rejected' => ['class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', 'icon' => 'fa-times', 'label' => 'ไม่ผ่าน'],
                        'withdrawn' => ['class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', 'icon' => 'fa-sign-out-alt', 'label' => 'ถอนตัว'],
                    ];
                    $config = $statusConfig[$application->application_status] ?? $statusConfig['new'];
                @endphp
                <span class="px-4 py-2 inline-flex items-center gap-2 text-base font-semibold rounded-full {{ $config['class'] }}">
                    <i class="fas {{ $config['icon'] }}"></i>
                    {{ $config['label'] }}
                </span>
            </div>
            @if($application->resume_path)
                <a href="{{ asset('storage/' . $application->resume_path) }}"
                   target="_blank"
                   class="px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 flex items-center gap-2 shadow-lg transform hover:scale-105">
                    <i class="fas fa-download"></i>
                    ดาวน์โหลด Resume
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Contact Information -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-l-4 border-purple-500 pl-3">
                    <i class="fas fa-address-card text-purple-500"></i>
                    ข้อมูลติดต่อ
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-purple-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">ชื่อ</p>
                        <p class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-user text-purple-500"></i>
                            {{ $application->first_name }}
                        </p>
                    </div>
                    <div class="p-4 bg-blue-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">นามสกุล</p>
                        <p class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-user text-blue-500"></i>
                            {{ $application->last_name }}
                        </p>
                    </div>
                    <div class="p-4 bg-green-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">อีเมล</p>
                        <p class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-envelope text-green-500"></i>
                            <a href="mailto:{{ $application->email }}" class="hover:text-green-600">{{ $application->email }}</a>
                        </p>
                    </div>
                    <div class="p-4 bg-yellow-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">โทรศัพท์</p>
                        <p class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-phone text-yellow-500"></i>
                            <a href="tel:{{ $application->phone }}" class="hover:text-yellow-600">{{ $application->phone }}</a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Cover Letter -->
            @if($application->cover_letter)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-l-4 border-blue-500 pl-3">
                    <i class="fas fa-file-alt text-blue-500"></i>
                    จดหมายสมัครงาน
                </h2>
                <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300 whitespace-pre-line bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    {{ $application->cover_letter }}
                </div>
            </div>
            @endif

            <!-- Interview Information -->
            @if($application->interview_date || $application->interview_notes)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-l-4 border-purple-500 pl-3">
                    <i class="fas fa-calendar-check text-purple-500"></i>
                    ข้อมูลการสัมภาษณ์
                </h2>
                <div class="space-y-4">
                    @if($application->interview_date)
                    <div class="p-4 bg-purple-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">วันที่สัมภาษณ์</p>
                        <p class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-calendar text-purple-500"></i>
                            {{ \Carbon\Carbon::parse($application->interview_date)->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    @endif
                    @if($application->interview_notes)
                    <div class="p-4 bg-blue-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">บันทึกการสัมภาษณ์</p>
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $application->interview_notes }}</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Reviewer Comments -->
            @if($application->reviewer_comments)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-l-4 border-green-500 pl-3">
                    <i class="fas fa-comment-dots text-green-500"></i>
                    ความเห็นของผู้พิจารณา
                </h2>
                <div class="p-4 bg-green-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $application->reviewer_comments }}</p>
                </div>
            </div>
            @endif

            <!-- Rejection Reason -->
            @if($application->rejection_reason && $application->application_status == 'rejected')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 border-l-4 border-red-500 pl-3">
                    <i class="fas fa-times-circle text-red-500"></i>
                    เหตุผลที่ไม่ผ่าน
                </h2>
                <div class="p-4 bg-red-50 dark:bg-gray-700 rounded-lg">
                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $application->rejection_reason }}</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Job Information Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-briefcase text-purple-500"></i>
                    ตำแหน่งที่สมัคร
                </h2>
                <div class="space-y-4">
                    @if($application->jobPosting)
                    <div class="p-4 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-gray-700 dark:to-gray-700 rounded-lg">
                        <p class="font-bold text-gray-900 dark:text-white text-lg mb-2">{{ $application->jobPosting->job_title }}</p>
                        <div class="space-y-2 text-sm text-gray-700 dark:text-gray-300">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-hashtag text-purple-500"></i>
                                <span>{{ $application->jobPosting->job_code }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-building text-blue-500"></i>
                                <span>{{ $application->jobPosting->department->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-map-marker-alt text-red-500"></i>
                                <span>{{ $application->jobPosting->job_location }}</span>
                            </div>
                        </div>
                        <a href="{{ route('admin.hrm.recruitment.jobs.show', $application->jobPosting) }}"
                           class="mt-3 block text-center px-4 py-2 bg-gradient-to-r from-purple-500 to-indigo-500 text-white rounded-lg hover:from-purple-600 hover:to-indigo-600 transition-all duration-200">
                            <i class="fas fa-eye"></i> ดูรายละเอียดตำแหน่ง
                        </a>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Application Details -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    รายละเอียดการสมัคร
                </h2>
                <div class="space-y-4">
                    <!-- Application Date -->
                    <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-calendar text-blue-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">วันที่สมัคร</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ $application->application_date ? \Carbon\Carbon::parse($application->application_date)->format('d/m/Y H:i') : 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <!-- Expected Salary -->
                    @if($application->expected_salary)
                    <div class="flex items-start gap-3 p-3 bg-green-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-money-bill-wave text-green-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">เงินเดือนที่คาดหวัง</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                ฿{{ number_format($application->expected_salary) }}
                            </p>
                        </div>
                    </div>
                    @endif

                    <!-- Available Start Date -->
                    @if($application->available_start_date)
                    <div class="flex items-start gap-3 p-3 bg-yellow-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-clock text-yellow-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">วันที่สามารถเริ่มงาน</p>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($application->available_start_date)->format('d/m/Y') }}
                            </p>
                        </div>
                    </div>
                    @endif

                    <!-- Reviewed By -->
                    @if($application->reviewedBy)
                    <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-user-tie text-purple-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">พิจารณาโดย</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $application->reviewedBy->name }}</p>
                            @if($application->reviewed_date)
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($application->reviewed_date)->format('d/m/Y H:i') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Resume -->
                    @if($application->resume_path)
                    <div class="flex items-start gap-3 p-3 bg-pink-50 dark:bg-gray-700 rounded-lg">
                        <i class="fas fa-file-pdf text-pink-500 mt-1"></i>
                        <div class="flex-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">เอกสารแนบ</p>
                            <p class="font-semibold text-gray-900 dark:text-white">Resume</p>
                            <a href="{{ asset('storage/' . $application->resume_path) }}"
                               target="_blank"
                               class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                <i class="fas fa-download"></i> ดาวน์โหลด
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-gray-700 dark:to-gray-700 rounded-xl shadow-lg p-6 transition-all duration-200">
                <h3 class="font-bold text-gray-900 dark:text-white mb-3">
                    <i class="fas fa-bolt text-yellow-500"></i> การกระทำด่วน
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('admin.hrm.recruitment.applications.edit', $application) }}"
                       class="w-full px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-edit"></i>
                        แก้ไขข้อมูล / เปลี่ยนสถานะ
                    </a>
                    @if($application->email)
                    <a href="mailto:{{ $application->email }}"
                       class="w-full px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-envelope"></i>
                        ส่งอีเมล
                    </a>
                    @endif
                    @if($application->phone)
                    <a href="tel:{{ $application->phone }}"
                       class="w-full px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all duration-200 flex items-center justify-center gap-2">
                        <i class="fas fa-phone"></i>
                        โทรหา
                    </a>
                    @endif
                    <form action="{{ route('admin.hrm.recruitment.applications.destroy', $application) }}"
                          method="POST"
                          onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบใบสมัครนี้?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="w-full px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition-all duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-trash"></i>
                            ลบใบสมัคร
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
