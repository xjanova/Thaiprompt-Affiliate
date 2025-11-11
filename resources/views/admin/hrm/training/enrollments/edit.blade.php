@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('admin.hrm.training.enrollments.show', $enrollment) }}"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 shadow-lg transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent dark:from-blue-400 dark:to-cyan-400">
                        ✏️ แก้ไขการลงทะเบียน
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        {{ $enrollment->employee->first_name }} {{ $enrollment->employee->last_name }} - {{ $enrollment->trainingCourse->course_name }}
                    </p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.hrm.training.enrollments.update', $enrollment) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Basic Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ข้อมูลพื้นฐาน</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Training Course -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            หลักสูตรฝึกอบรม <span class="text-red-500">*</span>
                        </label>
                        <select name="training_course_id"
                                required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('training_course_id') border-red-500 @enderror">
                            <option value="">เลือกหลักสูตร</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" {{ old('training_course_id', $enrollment->training_course_id) == $course->id ? 'selected' : '' }}>
                                    {{ $course->course_name }} ({{ $course->course_code }}) - {{ $course->course_duration_hours }} ชม.
                                </option>
                            @endforeach
                        </select>
                        @error('training_course_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Employee -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            พนักงาน <span class="text-red-500">*</span>
                        </label>
                        <select name="employee_id"
                                required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('employee_id') border-red-500 @enderror">
                            <option value="">เลือกพนักงาน</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id', $enrollment->employee_id) == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_id }}) - {{ $employee->position }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Training Start Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันเริ่มต้น <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               name="training_start_date"
                               value="{{ old('training_start_date', $enrollment->training_start_date) }}"
                               required
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('training_start_date') border-red-500 @enderror">
                        @error('training_start_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Training End Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันสิ้นสุด <span class="text-red-500">*</span>
                        </label>
                        <input type="date"
                               name="training_end_date"
                               value="{{ old('training_end_date', $enrollment->training_end_date) }}"
                               required
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('training_end_date') border-red-500 @enderror">
                        @error('training_end_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Enrollment Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ประเภทการลงทะเบียน <span class="text-red-500">*</span>
                        </label>
                        <select name="enrollment_type"
                                required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('enrollment_type') border-red-500 @enderror">
                            <option value="">เลือกประเภท</option>
                            <option value="mandatory" {{ old('enrollment_type', $enrollment->enrollment_type) == 'mandatory' ? 'selected' : '' }}>⚠️ บังคับ</option>
                            <option value="voluntary" {{ old('enrollment_type', $enrollment->enrollment_type) == 'voluntary' ? 'selected' : '' }}>✨ สมัครใจ</option>
                            <option value="recommended" {{ old('enrollment_type', $enrollment->enrollment_type) == 'recommended' ? 'selected' : '' }}>💡 แนะนำ</option>
                        </select>
                        @error('enrollment_type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Enrollment Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            สถานะ <span class="text-red-500">*</span>
                        </label>
                        <select name="enrollment_status"
                                required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('enrollment_status') border-red-500 @enderror">
                            <option value="">เลือกสถานะ</option>
                            <option value="enrolled" {{ old('enrollment_status', $enrollment->enrollment_status) == 'enrolled' ? 'selected' : '' }}>ลงทะเบียนแล้ว</option>
                            <option value="in_progress" {{ old('enrollment_status', $enrollment->enrollment_status) == 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                            <option value="completed" {{ old('enrollment_status', $enrollment->enrollment_status) == 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                            <option value="failed" {{ old('enrollment_status', $enrollment->enrollment_status) == 'failed' ? 'selected' : '' }}>ไม่ผ่าน</option>
                            <option value="cancelled" {{ old('enrollment_status', $enrollment->enrollment_status) == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                            <option value="no_show" {{ old('enrollment_status', $enrollment->enrollment_status) == 'no_show' ? 'selected' : '' }}>ไม่มาเข้าร่วม</option>
                        </select>
                        @error('enrollment_status')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Completion Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วันที่เสร็จสิ้น
                        </label>
                        <input type="date"
                               name="completion_date"
                               value="{{ old('completion_date', $enrollment->completion_date) }}"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('completion_date') border-red-500 @enderror">
                        @error('completion_date')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
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
                    <!-- Attendance Percentage -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เปอร์เซ็นต์การเข้าร่วม (%)
                        </label>
                        <div class="relative">
                            <input type="number"
                                   name="attendance_percentage"
                                   value="{{ old('attendance_percentage', $enrollment->attendance_percentage) }}"
                                   min="0"
                                   max="100"
                                   step="0.01"
                                   placeholder="0-100"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('attendance_percentage') border-red-500 @enderror">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500">
                                %
                            </div>
                        </div>
                        @error('attendance_percentage')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @if(old('attendance_percentage', $enrollment->attendance_percentage))
                        <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full transition-all duration-300"
                                 style="width: {{ old('attendance_percentage', $enrollment->attendance_percentage) }}%"></div>
                        </div>
                        @endif
                    </div>

                    <!-- Final Score -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            คะแนนสอบ (%)
                        </label>
                        <div class="relative">
                            <input type="number"
                                   name="final_score"
                                   value="{{ old('final_score', $enrollment->final_score) }}"
                                   min="0"
                                   max="100"
                                   step="0.01"
                                   placeholder="0-100"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('final_score') border-red-500 @enderror">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500">
                                %
                            </div>
                        </div>
                        @error('final_score')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        @if(old('final_score', $enrollment->final_score))
                        <div class="mt-2 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-full transition-all duration-300"
                                 style="width: {{ old('final_score', $enrollment->final_score) }}%"></div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Feedback -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ความคิดเห็น</h2>
                </div>

                <div class="space-y-6">
                    <!-- Employee Feedback -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความคิดเห็นจากผู้เข้าร่วม
                        </label>
                        <textarea name="feedback"
                                  rows="4"
                                  placeholder="ความคิดเห็นหรือข้อเสนอแนะจากผู้เข้าร่วมฝึกอบรม..."
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('feedback') border-red-500 @enderror">{{ old('feedback', $enrollment->feedback) }}</textarea>
                        @error('feedback')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Trainer Comments -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ความคิดเห็นจากผู้ฝึกสอน
                        </label>
                        <textarea name="trainer_comments"
                                  rows="4"
                                  placeholder="ความคิดเห็นหรือข้อสังเกตจากผู้ฝึกสอน..."
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('trainer_comments') border-red-500 @enderror">{{ old('trainer_comments', $enrollment->trainer_comments) }}</textarea>
                        @error('trainer_comments')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-2">💡 คำแนะนำ</h3>
                        <ul class="space-y-1 text-sm text-white/90">
                            <li>• เมื่อเปลี่ยนสถานะเป็น "เสร็จสิ้น" ควรกรอกวันที่เสร็จสิ้นด้วย</li>
                            <li>• คะแนนและเปอร์เซ็นต์การเข้าร่วมควรอยู่ระหว่าง 0-100</li>
                            <li>• ความคิดเห็นจากทั้งสองฝ่ายช่วยในการปรับปรุงหลักสูตร</li>
                            <li>• ตรวจสอบข้อมูลให้ครบถ้วนก่อนบันทึก</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('admin.hrm.training.enrollments.show', $enrollment) }}"
                   class="px-8 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition-all duration-200">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    💾 บันทึกการเปลี่ยนแปลง
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-update progress bars when values change
document.addEventListener('DOMContentLoaded', function() {
    const attendanceInput = document.querySelector('input[name="attendance_percentage"]');
    const scoreInput = document.querySelector('input[name="final_score"]');

    if (attendanceInput) {
        attendanceInput.addEventListener('input', function() {
            const value = Math.min(100, Math.max(0, this.value));
            const progressBar = this.closest('div').parentElement.querySelector('.h-2 > div');
            if (progressBar) {
                progressBar.style.width = value + '%';
            }
        });
    }

    if (scoreInput) {
        scoreInput.addEventListener('input', function() {
            const value = Math.min(100, Math.max(0, this.value));
            const progressBar = this.closest('div').parentElement.querySelector('.h-2 > div');
            if (progressBar) {
                progressBar.style.width = value + '%';
            }
        });
    }
});
</script>
@endsection
