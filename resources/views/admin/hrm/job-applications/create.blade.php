@extends('layouts.admin')

@section('title', 'เพิ่มใบสมัครงาน')

@section('content')
<div class="space-y-6">
    <!-- Header with Gradient -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                    <i class="fas fa-plus-circle"></i>
                    เพิ่มใบสมัครงานใหม่
                </h1>
                <p class="text-purple-100">บันทึกข้อมูลผู้สมัครงานเข้าระบบ</p>
            </div>
            <a href="{{ route('admin.hrm.recruitment.applications.index') }}"
               class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-200 flex items-center gap-2">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
        <form action="{{ route('admin.hrm.recruitment.applications.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <!-- Job Posting Selection -->
            <div class="border-l-4 border-purple-500 pl-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-briefcase text-purple-500"></i>
                    ตำแหน่งที่สมัคร
                </h2>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-briefcase text-purple-500"></i> เลือกตำแหน่งงาน <span class="text-red-500">*</span>
                </label>
                <select name="job_posting_id"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('job_posting_id') border-red-500 @enderror"
                        required>
                    <option value="">เลือกตำแหน่งที่ต้องการสมัคร</option>
                    @foreach($jobPostings as $job)
                        <option value="{{ $job->id }}" {{ old('job_posting_id') == $job->id ? 'selected' : '' }}>
                            {{ $job->job_title }} ({{ $job->job_code }})
                        </option>
                    @endforeach
                </select>
                @error('job_posting_id')
                    <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Personal Information Section -->
            <div class="border-l-4 border-blue-500 pl-4 mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-user text-blue-500"></i>
                    ข้อมูลส่วนตัว
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- First Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user text-blue-500"></i> ชื่อ <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="first_name"
                           value="{{ old('first_name') }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('first_name') border-red-500 @enderror"
                           placeholder="ชื่อ"
                           required>
                    @error('first_name')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Last Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-user text-blue-500"></i> นามสกุล <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="last_name"
                           value="{{ old('last_name') }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('last_name') border-red-500 @enderror"
                           placeholder="นามสกุล"
                           required>
                    @error('last_name')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-envelope text-blue-500"></i> อีเมล <span class="text-red-500">*</span>
                    </label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('email') border-red-500 @enderror"
                           placeholder="email@example.com"
                           required>
                    @error('email')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-phone text-blue-500"></i> เบอร์โทรศัพท์ <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="phone"
                           value="{{ old('phone') }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white transition-all duration-200 @error('phone') border-red-500 @enderror"
                           placeholder="0812345678"
                           required>
                    @error('phone')
                        <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Application Details Section -->
            <div class="border-l-4 border-green-500 pl-4 mt-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-file-alt text-green-500"></i>
                    รายละเอียดการสมัคร
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Expected Salary -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-money-bill-wave text-green-500"></i> เงินเดือนที่คาดหวัง (บาท)
                    </label>
                    <input type="number"
                           name="expected_salary"
                           value="{{ old('expected_salary') }}"
                           min="0"
                           step="0.01"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                           placeholder="เช่น 30000">
                </div>

                <!-- Available Start Date -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-calendar text-green-500"></i> วันที่สามารถเริ่มงาน
                    </label>
                    <input type="date"
                           name="available_start_date"
                           value="{{ old('available_start_date') }}"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                </div>
            </div>

            <!-- Cover Letter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-align-left text-green-500"></i> จดหมายสมัครงาน
                </label>
                <textarea name="cover_letter"
                          rows="6"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-green-500 dark:bg-gray-700 dark:text-white transition-all duration-200"
                          placeholder="แนะนำตัวและเหตุผลที่สนใจสมัครงานตำแหน่งนี้...">{{ old('cover_letter') }}</textarea>
            </div>

            <!-- Resume Upload -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-file-pdf text-green-500"></i> อัปโหลด Resume/CV
                </label>
                <div class="flex items-center justify-center w-full">
                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-all duration-200">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                            <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="font-semibold">คลิกเพื่ออัปโหลด</span> หรือลากไฟล์มาวาง
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">PDF, DOC, DOCX (สูงสุด 5MB)</p>
                        </div>
                        <input type="file" name="resume_file" class="hidden" accept=".pdf,.doc,.docx">
                    </label>
                </div>
                @error('resume_file')
                    <p class="mt-1 text-sm text-red-500"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                @enderror
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.hrm.recruitment.applications.index') }}"
                   class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    ยกเลิก
                </a>
                <button type="submit"
                        class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 transform hover:scale-105 flex items-center gap-2 shadow-lg">
                    <i class="fas fa-save"></i>
                    บันทึกใบสมัคร
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // File upload preview
    document.querySelector('input[type="file"]').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            const label = e.target.closest('label');
            const textElement = label.querySelector('.font-semibold');
            textElement.textContent = fileName;
            textElement.classList.add('text-green-600');
        }
    });
</script>
@endsection
