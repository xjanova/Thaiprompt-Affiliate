@extends('layouts.admin-v3')

@section('title', 'แก้ไขใบสมัครงาน')

@section('content')
<div class="space-y-6">
    <!-- Header with Gradient -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-xl font-bold">
                        {{ strtoupper(substr($application->first_name, 0, 1)) }}{{ strtoupper(substr($application->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold">แก้ไขใบสมัครงาน</h1>
                        <p class="text-purple-100">{{ $application->first_name }} {{ $application->last_name }}</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.hrm.recruitment.applications.show', $application) }}"
               class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    <!-- Current Status Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">สถานะปัจจุบัน</p>
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
            <div class="text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ตำแหน่งที่สมัคร</p>
                <p class="font-semibold text-gray-900 dark:text-white">{{ $application->jobPosting->job_title ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <form action="{{ route('admin.hrm.recruitment.applications.update', $application) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Status Update Section -->
            <div class="border-l-4 border-purple-500 pl-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-tasks text-purple-500"></i>
                    อัปเดตสถานะ
                </h2>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-info-circle text-purple-500"></i> สถานะใบสมัคร <span class="text-red-500">*</span>
                </label>
                <select name="application_status"
                        id="application_status"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('application_status') border-red-500 @enderror"
                        required>
                    <option value="new" {{ old('application_status', $application->application_status) == 'new' ? 'selected' : '' }}>ใหม่</option>
                    <option value="under_review" {{ old('application_status', $application->application_status) == 'under_review' ? 'selected' : '' }}>กำลังพิจารณา</option>
                    <option value="shortlisted" {{ old('application_status', $application->application_status) == 'shortlisted' ? 'selected' : '' }}>ผ่านเข้ารอบ</option>
                    <option value="interview_scheduled" {{ old('application_status', $application->application_status) == 'interview_scheduled' ? 'selected' : '' }}>นัดสัมภาษณ์</option>
                    <option value="interviewed" {{ old('application_status', $application->application_status) == 'interviewed' ? 'selected' : '' }}>สัมภาษณ์แล้ว</option>
                    <option value="offer_extended" {{ old('application_status', $application->application_status) == 'offer_extended' ? 'selected' : '' }}>เสนอตำแหน่ง</option>
                    <option value="hired" {{ old('application_status', $application->application_status) == 'hired' ? 'selected' : '' }}>จ้างแล้ว</option>
                    <option value="rejected" {{ old('application_status', $application->application_status) == 'rejected' ? 'selected' : '' }}>ไม่ผ่าน</option>
                    <option value="withdrawn" {{ old('application_status', $application->application_status) == 'withdrawn' ? 'selected' : '' }}>ถอนตัว</option>
                </select>
                @error('application_status')
                    <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Interview Section -->
            <div class="border-l-4 border-blue-500 pl-4 mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-calendar-check text-blue-500"></i>
                    ข้อมูลการสัมภาษณ์
                </h2>
            </div>

            <div class="space-y-6">
                <!-- Interview Date -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar text-blue-500"></i> วันที่และเวลาสัมภาษณ์
                    </label>
                    <input type="datetime-local"
                           name="interview_date"
                           value="{{ old('interview_date', $application->interview_date ? \Carbon\Carbon::parse($application->interview_date)->format('Y-m-d\TH:i') : '') }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <i class="fas fa-info-circle"></i> ใช้สำหรับนัดวันสัมภาษณ์ (ถ้ามี)
                    </p>
                </div>

                <!-- Interview Notes -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-sticky-note text-blue-500"></i> บันทึกการสัมภาษณ์
                    </label>
                    <textarea name="interview_notes"
                              rows="5"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                              placeholder="บันทึกผลการสัมภาษณ์ ความประทับใจ ข้อสังเกต...">{{ old('interview_notes', $application->interview_notes) }}</textarea>
                </div>
            </div>

            <!-- Review Section -->
            <div class="border-l-4 border-green-500 pl-4 mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-comment-dots text-green-500"></i>
                    การพิจารณา
                </h2>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-comments text-green-500"></i> ความเห็นของผู้พิจารณา
                </label>
                <textarea name="reviewer_comments"
                          rows="5"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                          placeholder="ความเห็น ข้อเสนอแนะ การประเมินผู้สมัคร...">{{ old('reviewer_comments', $application->reviewer_comments) }}</textarea>
            </div>

            <!-- Rejection Section (shown when status is rejected) -->
            <div id="rejection_section" class="{{ $application->application_status == 'rejected' ? '' : 'hidden' }}">
                <div class="border-l-4 border-red-500 pl-4">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-times-circle text-red-500"></i>
                        เหตุผลที่ไม่ผ่าน
                    </h2>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-exclamation-triangle text-red-500"></i> เหตุผลที่ไม่รับ
                    </label>
                    <textarea name="rejection_reason"
                              rows="4"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                              placeholder="ระบุเหตุผลที่ไม่รับผู้สมัครท่านนี้...">{{ old('rejection_reason', $application->rejection_reason) }}</textarea>
                </div>
            </div>

            <!-- Application Info (Read-only) -->
            <div class="border-l-4 border-yellow-500 pl-4 mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-yellow-500"></i>
                    ข้อมูลผู้สมัคร (อ่านอย่างเดียว)
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 dark:bg-gray-700 p-6 rounded-lg">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">ชื่อ-นามสกุล</p>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-user text-yellow-500"></i> {{ $application->first_name }} {{ $application->last_name }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">อีเมล</p>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-envelope text-yellow-500"></i> {{ $application->email }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">โทรศัพท์</p>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-phone text-yellow-500"></i> {{ $application->phone }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันที่สมัคร</p>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-calendar text-yellow-500"></i>
                        {{ $application->application_date ? \Carbon\Carbon::parse($application->application_date)->format('d/m/Y H:i') : 'N/A' }}
                    </p>
                </div>
                @if($application->expected_salary)
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">เงินเดือนที่คาดหวัง</p>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-money-bill-wave text-yellow-500"></i> ฿{{ number_format($application->expected_salary) }}
                    </p>
                </div>
                @endif
                @if($application->available_start_date)
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">วันที่สามารถเริ่มงาน</p>
                    <p class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-clock text-yellow-500"></i>
                        {{ \Carbon\Carbon::parse($application->available_start_date)->format('d/m/Y') }}
                    </p>
                </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.hrm.recruitment.applications.show', $application) }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 transform hover:scale-105 flex items-center gap-2 shadow-lg">
                    <i class="fas fa-save"></i>
                    บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Show/hide rejection section based on status
    document.getElementById('application_status').addEventListener('change', function() {
        const rejectionSection = document.getElementById('rejection_section');
        if (this.value === 'rejected') {
            rejectionSection.classList.remove('hidden');
        } else {
            rejectionSection.classList.add('hidden');
        }
    });
</script>
@endsection
