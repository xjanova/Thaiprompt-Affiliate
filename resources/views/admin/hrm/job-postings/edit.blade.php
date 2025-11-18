@extends('layouts.admin-v3')

@section('title', 'แก้ไขตำแหน่งงาน')

@section('content')
<div class="space-y-6">
    <!-- Header with Gradient -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                    <i class="fas fa-edit"></i>
                    แก้ไขตำแหน่งงาน
                </h1>
                <p class="text-purple-100">แก้ไขข้อมูลประกาศรับสมัครงาน - {{ $job->job_title }}</p>
            </div>
            <a href="{{ route('admin.hrm.recruitment.jobs.show', $job) }}"
               class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <form action="{{ route('admin.hrm.recruitment.jobs.update', $job) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <!-- Basic Information Section -->
            <div class="border-l-4 border-purple-500 pl-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-purple-500"></i>
                    ข้อมูลพื้นฐาน
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Job Title -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-briefcase text-purple-500"></i> ชื่อตำแหน่งงาน <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="job_title"
                           value="{{ old('job_title', $job->job_title) }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('job_title') border-red-500 @enderror"
                           placeholder="เช่น Senior Software Engineer"
                           required>
                    @error('job_title')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Job Code -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-hashtag text-purple-500"></i> รหัสตำแหน่ง <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="job_code"
                           value="{{ old('job_code', $job->job_code) }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('job_code') border-red-500 @enderror"
                           placeholder="เช่น JOB-2024-001"
                           required>
                    @error('job_code')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Department -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-building text-purple-500"></i> แผนก <span class="text-red-500">*</span>
                    </label>
                    <select name="department_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('department_id') border-red-500 @enderror"
                            required>
                        <option value="">เลือกแผนก</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $job->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Position -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user-tie text-purple-500"></i> ตำแหน่ง
                    </label>
                    <select name="position_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <option value="">เลือกตำแหน่ง (ถ้ามี)</option>
                        @foreach($positions as $position)
                            <option value="{{ $position->id }}" {{ old('position_id', $job->position_id) == $position->id ? 'selected' : '' }}>
                                {{ $position->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Employment Type -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-clock text-purple-500"></i> ประเภทการจ้างงาน <span class="text-red-500">*</span>
                    </label>
                    <select name="employment_type"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('employment_type') border-red-500 @enderror"
                            required>
                        <option value="">เลือกประเภท</option>
                        <option value="full_time" {{ old('employment_type', $job->employment_type) == 'full_time' ? 'selected' : '' }}>Full Time (เต็มเวลา)</option>
                        <option value="part_time" {{ old('employment_type', $job->employment_type) == 'part_time' ? 'selected' : '' }}>Part Time (พาร์ทไทม์)</option>
                        <option value="contract" {{ old('employment_type', $job->employment_type) == 'contract' ? 'selected' : '' }}>Contract (สัญญาจ้าง)</option>
                        <option value="intern" {{ old('employment_type', $job->employment_type) == 'intern' ? 'selected' : '' }}>Intern (ฝึกงาน)</option>
                        <option value="freelance" {{ old('employment_type', $job->employment_type) == 'freelance' ? 'selected' : '' }}>Freelance (อิสระ)</option>
                    </select>
                    @error('employment_type')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Job Location -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-map-marker-alt text-purple-500"></i> สถานที่ทำงาน <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="job_location"
                           value="{{ old('job_location', $job->job_location) }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('job_location') border-red-500 @enderror"
                           placeholder="เช่น กรุงเทพมหานคร"
                           required>
                    @error('job_location')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Number of Openings -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-users text-purple-500"></i> จำนวนที่รับ <span class="text-red-500">*</span>
                    </label>
                    <input type="number"
                           name="number_of_openings"
                           value="{{ old('number_of_openings', $job->number_of_openings) }}"
                           min="1"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('number_of_openings') border-red-500 @enderror"
                           required>
                    @error('number_of_openings')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Salary Section -->
            <div class="border-l-4 border-green-500 pl-4 mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-money-bill-wave text-green-500"></i>
                    ข้อมูลเงินเดือน
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Salary Min -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-dollar-sign text-green-500"></i> เงินเดือนขั้นต่ำ (บาท)
                    </label>
                    <input type="number"
                           name="salary_range_min"
                           value="{{ old('salary_range_min', $job->salary_range_min) }}"
                           min="0"
                           step="0.01"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                           placeholder="เช่น 30000">
                </div>

                <!-- Salary Max -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-dollar-sign text-green-500"></i> เงินเดือนสูงสุด (บาท)
                    </label>
                    <input type="number"
                           name="salary_range_max"
                           value="{{ old('salary_range_max', $job->salary_range_max) }}"
                           min="0"
                           step="0.01"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                           placeholder="เช่น 50000">
                </div>
            </div>

            <!-- Job Details Section -->
            <div class="border-l-4 border-blue-500 pl-4 mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-file-alt text-blue-500"></i>
                    รายละเอียดงาน
                </h2>
            </div>

            <div class="space-y-6">
                <!-- Job Description -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-align-left text-blue-500"></i> คำอธิบายงาน <span class="text-red-500">*</span>
                    </label>
                    <textarea name="job_description"
                              rows="5"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('job_description') border-red-500 @enderror"
                              placeholder="อธิบายลักษณะงานและความรับผิดชอบโดยละเอียด..."
                              required>{{ old('job_description', $job->job_description) }}</textarea>
                    @error('job_description')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Requirements -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-clipboard-list text-blue-500"></i> คุณสมบัติที่ต้องการ <span class="text-red-500">*</span>
                    </label>
                    <textarea name="requirements"
                              rows="5"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('requirements') border-red-500 @enderror"
                              placeholder="ระบุคุณสมบัติ ทักษะ และประสบการณ์ที่ต้องการ..."
                              required>{{ old('requirements', $job->requirements) }}</textarea>
                    @error('requirements')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Responsibilities -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-tasks text-blue-500"></i> หน้าที่ความรับผิดชอบ <span class="text-red-500">*</span>
                    </label>
                    <textarea name="responsibilities"
                              rows="5"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('responsibilities') border-red-500 @enderror"
                              placeholder="อธิบายหน้าที่และความรับผิดชอบในตำแหน่งนี้..."
                              required>{{ old('responsibilities', $job->responsibilities) }}</textarea>
                    @error('responsibilities')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Benefits -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-gift text-blue-500"></i> สวัสดิการและผลประโยชน์
                    </label>
                    <textarea name="benefits"
                              rows="4"
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                              placeholder="ระบุสวัสดิการและผลประโยชน์ที่จะได้รับ...">{{ old('benefits', $job->benefits) }}</textarea>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="border-l-4 border-orange-500 pl-4 mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-cog text-orange-500"></i>
                    ข้อมูลเพิ่มเติม
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Application Deadline -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar text-orange-500"></i> วันปิดรับสมัคร
                    </label>
                    <input type="date"
                           name="application_deadline"
                           value="{{ old('application_deadline', $job->application_deadline ? \Carbon\Carbon::parse($job->application_deadline)->format('Y-m-d') : '') }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-info-circle text-orange-500"></i> สถานะ <span class="text-red-500">*</span>
                    </label>
                    <select name="status"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-orange-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('status') border-red-500 @enderror"
                            required>
                        <option value="draft" {{ old('status', $job->status) == 'draft' ? 'selected' : '' }}>ฉบับร่าง</option>
                        <option value="active" {{ old('status', $job->status) == 'active' ? 'selected' : '' }}>เปิดรับสมัคร</option>
                        <option value="on_hold" {{ old('status', $job->status) == 'on_hold' ? 'selected' : '' }}>พักการรับสมัคร</option>
                        <option value="closed" {{ old('status', $job->status) == 'closed' ? 'selected' : '' }}>ปิดรับสมัคร</option>
                        <option value="filled" {{ old('status', $job->status) == 'filled' ? 'selected' : '' }}>จ้างแล้ว</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.hrm.recruitment.jobs.show', $job) }}"
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
@endsection
