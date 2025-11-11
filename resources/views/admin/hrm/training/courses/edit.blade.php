@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('admin.hrm.training.courses.show', $course) }}"
                   class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 shadow-lg transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent dark:from-blue-400 dark:to-cyan-400">
                        ✏️ แก้ไขหลักสูตรฝึกอบรม
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $course->course_name }}</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.hrm.training.courses.update', $course) }}" method="POST" class="space-y-6">
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
                    <!-- Course Name -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อหลักสูตร <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="course_name"
                               value="{{ old('course_name', $course->course_name) }}"
                               required
                               placeholder="เช่น การบริหารจัดการเวลา"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('course_name') border-red-500 @enderror">
                        @error('course_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Course Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            รหัสหลักสูตร <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="course_code"
                               value="{{ old('course_code', $course->course_code) }}"
                               required
                               placeholder="เช่น TRN-001"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('course_code') border-red-500 @enderror">
                        @error('course_code')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            หมวดหมู่ <span class="text-red-500">*</span>
                        </label>
                        <select name="course_category"
                                required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('course_category') border-red-500 @enderror">
                            <option value="">เลือกหมวดหมู่</option>
                            <option value="technical" {{ old('course_category', $course->course_category) == 'technical' ? 'selected' : '' }}>เทคนิค</option>
                            <option value="soft_skills" {{ old('course_category', $course->course_category) == 'soft_skills' ? 'selected' : '' }}>ทักษะนุ่ม</option>
                            <option value="leadership" {{ old('course_category', $course->course_category) == 'leadership' ? 'selected' : '' }}>ความเป็นผู้นำ</option>
                            <option value="compliance" {{ old('course_category', $course->course_category) == 'compliance' ? 'selected' : '' }}>การปฏิบัติตาม</option>
                            <option value="safety" {{ old('course_category', $course->course_category) == 'safety' ? 'selected' : '' }}>ความปลอดภัย</option>
                            <option value="product" {{ old('course_category', $course->course_category) == 'product' ? 'selected' : '' }}>ผลิตภัณฑ์</option>
                            <option value="sales" {{ old('course_category', $course->course_category) == 'sales' ? 'selected' : '' }}>การขาย</option>
                            <option value="other" {{ old('course_category', $course->course_category) == 'other' ? 'selected' : '' }}>อื่นๆ</option>
                        </select>
                        @error('course_category')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Duration -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ระยะเวลา (ชั่วโมง) <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               name="course_duration_hours"
                               value="{{ old('course_duration_hours', $course->course_duration_hours) }}"
                               required
                               step="0.5"
                               min="0"
                               placeholder="เช่น 8"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('course_duration_hours') border-red-500 @enderror">
                        @error('course_duration_hours')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Training Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วิธีการฝึกอบรม <span class="text-red-500">*</span>
                        </label>
                        <select name="training_method"
                                required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('training_method') border-red-500 @enderror">
                            <option value="">เลือกวิธีการ</option>
                            <option value="classroom" {{ old('training_method', $course->training_method) == 'classroom' ? 'selected' : '' }}>🏫 ในห้องเรียน</option>
                            <option value="online" {{ old('training_method', $course->training_method) == 'online' ? 'selected' : '' }}>💻 ออนไลน์</option>
                            <option value="hybrid" {{ old('training_method', $course->training_method) == 'hybrid' ? 'selected' : '' }}>🔄 ผสมผสาน</option>
                            <option value="on_the_job" {{ old('training_method', $course->training_method) == 'on_the_job' ? 'selected' : '' }}>👷 ในงาน</option>
                            <option value="workshop" {{ old('training_method', $course->training_method) == 'workshop' ? 'selected' : '' }}>🛠️ เวิร์คช็อป</option>
                            <option value="seminar" {{ old('training_method', $course->training_method) == 'seminar' ? 'selected' : '' }}>🎤 สัมมนา</option>
                        </select>
                        @error('training_method')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Max Participants -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            จำนวนผู้เข้าร่วมสูงสุด
                        </label>
                        <input type="number"
                               name="max_participants"
                               value="{{ old('max_participants', $course->max_participants) }}"
                               min="1"
                               placeholder="เช่น 30"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('max_participants') border-red-500 @enderror">
                        @error('max_participants')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Cost -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ค่าใช้จ่ายต่อคน (บาท)
                        </label>
                        <input type="number"
                               name="cost_per_participant"
                               value="{{ old('cost_per_participant', $course->cost_per_participant) }}"
                               step="0.01"
                               min="0"
                               placeholder="เช่น 5000"
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('cost_per_participant') border-red-500 @enderror">
                        @error('cost_per_participant')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            สถานะ <span class="text-red-500">*</span>
                        </label>
                        <select name="status"
                                required
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('status') border-red-500 @enderror">
                            <option value="active" {{ old('status', $course->status) == 'active' ? 'selected' : '' }}>เปิดใช้งาน</option>
                            <option value="inactive" {{ old('status', $course->status) == 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                            <option value="archived" {{ old('status', $course->status) == 'archived' ? 'selected' : '' }}>จัดเก็บ</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบาย <span class="text-red-500">*</span>
                        </label>
                        <textarea name="course_description"
                                  rows="4"
                                  required
                                  placeholder="อธิบายรายละเอียดหลักสูตร..."
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('course_description') border-red-500 @enderror">{{ old('course_description', $course->course_description) }}</textarea>
                        @error('course_description')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Objectives -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            วัตถุประสงค์
                        </label>
                        <textarea name="course_objectives"
                                  rows="3"
                                  placeholder="วัตถุประสงค์ของหลักสูตร..."
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('course_objectives') border-red-500 @enderror">{{ old('course_objectives', $course->course_objectives) }}</textarea>
                        @error('course_objectives')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Prerequisites -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ข้อกำหนดเบื้องต้น
                        </label>
                        <textarea name="prerequisites"
                                  rows="3"
                                  placeholder="ความรู้หรือทักษะที่ต้องมีก่อนเข้าร่วม..."
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('prerequisites') border-red-500 @enderror">{{ old('prerequisites', $course->prerequisites) }}</textarea>
                        @error('prerequisites')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Course Materials -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            เอกสารประกอบ
                        </label>
                        <textarea name="course_materials"
                                  rows="3"
                                  placeholder="เอกสารหรือสื่อการเรียนรู้ที่ใช้..."
                                  class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('course_materials') border-red-500 @enderror">{{ old('course_materials', $course->course_materials) }}</textarea>
                        @error('course_materials')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Certification -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">ใบรับรอง</h2>
                </div>

                <div class="space-y-6">
                    <!-- Certification Provided -->
                    <div class="flex items-center">
                        <input type="checkbox"
                               name="certification_provided"
                               id="certification_provided"
                               value="1"
                               {{ old('certification_provided', $course->certification_provided) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700">
                        <label for="certification_provided" class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            มีการออกใบรับรอง
                        </label>
                    </div>

                    <!-- Certification Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อใบรับรอง
                        </label>
                        <input type="text"
                               name="certification_name"
                               value="{{ old('certification_name', $course->certification_name) }}"
                               placeholder="เช่น ใบรับรองการผ่านการอบรม..."
                               class="w-full px-4 py-3 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('certification_name') border-red-500 @enderror">
                        @error('certification_name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('admin.hrm.training.courses.show', $course) }}"
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
@endsection
