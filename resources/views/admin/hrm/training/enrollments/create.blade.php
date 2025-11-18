@extends('layouts.admin-v3')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('admin.hrm.training.enrollments.index') }}"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 shadow-lg transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent dark:from-blue-400 dark:to-cyan-400">
                        ➕ ลงทะเบียนฝึกอบรม
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">ลงทะเบียนพนักงานเข้าหลักสูตรฝึกอบรม</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.hrm.training.enrollments.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Enrollment Information -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ข้อมูลการลงทะเบียน</h2>
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
                                <option value="{{ $course->id }}" {{ old('training_course_id') == $course->id ? 'selected' : '' }}>
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
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
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
                               value="{{ old('training_start_date') }}"
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
                               value="{{ old('training_end_date') }}"
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
                            <option value="mandatory" {{ old('enrollment_type') == 'mandatory' ? 'selected' : '' }}>⚠️ บังคับ</option>
                            <option value="voluntary" {{ old('enrollment_type') == 'voluntary' ? 'selected' : '' }}>✨ สมัครใจ</option>
                            <option value="recommended" {{ old('enrollment_type') == 'recommended' ? 'selected' : '' }}>💡 แนะนำ</option>
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
                            <option value="enrolled" {{ old('enrollment_status', 'enrolled') == 'enrolled' ? 'selected' : '' }}>ลงทะเบียนแล้ว</option>
                            <option value="in_progress" {{ old('enrollment_status') == 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                            <option value="completed" {{ old('enrollment_status') == 'completed' ? 'selected' : '' }}>เสร็จสิ้น</option>
                            <option value="failed" {{ old('enrollment_status') == 'failed' ? 'selected' : '' }}>ไม่ผ่าน</option>
                            <option value="cancelled" {{ old('enrollment_status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                            <option value="no_show" {{ old('enrollment_status') == 'no_show' ? 'selected' : '' }}>ไม่มาเข้าร่วม</option>
                        </select>
                        @error('enrollment_status')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Info Card -->
            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-2xl shadow-lg p-6 text-white">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-2">💡 หมายเหตุ</h3>
                        <ul class="space-y-1 text-sm text-white/90">
                            <li>• วันที่สิ้นสุดต้องมากกว่าวันที่เริ่มต้น</li>
                            <li>• ตรวจสอบความพร้อมของพนักงานก่อนลงทะเบียน</li>
                            <li>• หลังจากลงทะเบียน สามารถแก้ไขข้อมูลได้ในภายหลัง</li>
                            <li>• สามารถเพิ่มข้อมูลเพิ่มเติม เช่น คะแนน เปอร์เซ็นต์การเข้าร่วม ได้ในหน้าแก้ไข</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('admin.hrm.training.enrollments.index') }}"
                   class="px-8 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition-all duration-200">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-700 hover:to-cyan-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200">
                    💾 บันทึกการลงทะเบียน
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
