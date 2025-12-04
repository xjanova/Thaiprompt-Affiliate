{{--
    หน้าแก้ไขใบประกาศนียบัตร
    ใช้ Tailwind CSS + Alpine.js ตาม V3 standards
--}}
@extends('layouts.admin-v3')

@section('title', 'แก้ไขใบประกาศนียบัตร')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 sm:p-8 text-white mb-8 shadow-xl">
        <h1 class="text-2xl sm:text-3xl font-extrabold mb-2">✏️ แก้ไขใบประกาศนียบัตร</h1>
        <p class="font-mono text-lg text-indigo-100">{{ $certificate->certificate_number }}</p>
    </div>

    <!-- Error Alert -->
    @if($errors->any())
        <div class="flex items-start gap-3 p-4 mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-red-800 dark:text-red-200">
            <span class="text-xl">⚠️</span>
            <div>
                <strong class="font-semibold">พบข้อผิดพลาด:</strong>
                <ul class="list-disc list-inside mt-2 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Form -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 sm:p-8">
        <form action="{{ route('admin.academy.certificates.update', $certificate->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Basic Info (Read-only) -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อมูลพื้นฐาน
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">นักเรียน</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $certificate->user->name }}</div>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">คอร์ส</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $certificate->article->title }}</div>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">เลขที่ใบประกาศ</div>
                        <div class="font-semibold font-mono text-gray-900 dark:text-white">{{ $certificate->certificate_number }}</div>
                    </div>

                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">รหัสยืนยัน</div>
                        <div class="font-semibold font-mono text-gray-900 dark:text-white">{{ $certificate->verification_code }}</div>
                    </div>
                </div>
            </div>

            <!-- Editable Fields -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อมูลที่แสดงบนใบประกาศ
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อนักเรียน <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="student_name" required
                               value="{{ old('student_name', $certificate->student_name) }}"
                               class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            อีเมลนักเรียน <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="student_email" required
                               value="{{ old('student_email', $certificate->student_email) }}"
                               class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อครูผู้สอน <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="instructor_name" required
                               value="{{ old('instructor_name', $certificate->instructor_name) }}"
                               class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            วันที่ออกใบประกาศ <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="issued_at" required
                               value="{{ old('issued_at', $certificate->issued_at->format('Y-m-d')) }}"
                               class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    </div>
                </div>
            </div>

            <!-- Performance Data -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อมูลผลการเรียน
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            คะแนน Quiz (%)
                        </label>
                        <input type="number" name="quiz_score"
                               value="{{ old('quiz_score', $certificate->quiz_score) }}"
                               min="0" max="100" step="0.01"
                               class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">คะแนนเฉลี่ยจาก Quiz</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชั่วโมงเรียนทั้งหมด
                        </label>
                        <input type="number" name="total_hours"
                               value="{{ old('total_hours', $certificate->total_hours) }}"
                               min="0" step="0.1"
                               class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">จำนวนชั่วโมงที่ใช้เรียน</p>
                    </div>
                </div>
            </div>

            <!-- Custom Text -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อความพิเศษ
                </h3>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ข้อความพิเศษบนใบประกาศ
                    </label>
                    <textarea name="custom_text" rows="3"
                              class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-y">{{ old('custom_text', $certificate->custom_text) }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ข้อความนี้จะปรากฏบนใบประกาศ (ถ้ามี)</p>
                </div>
            </div>

            <!-- Warning -->
            <div class="flex items-start gap-3 p-4 mb-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                <span class="text-xl">⚠️</span>
                <div>
                    <strong class="font-semibold text-amber-800 dark:text-amber-200">คำเตือน:</strong>
                    <p class="text-sm text-amber-700 dark:text-amber-300 mt-1">
                        การแก้ไขข้อมูลจะสร้าง PDF ใบประกาศใหม่ทันที และเขียนทับไฟล์เดิม
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3 justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('admin.academy.certificates.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                    <span>💾</span>
                    <span>บันทึกการแก้ไข</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
