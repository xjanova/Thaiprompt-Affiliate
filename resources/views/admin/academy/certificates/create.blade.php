{{--
    หน้าสร้างใบประกาศนียบัตร
    ใช้ Tailwind CSS + Alpine.js ตาม V3 standards
--}}
@extends('layouts.admin-v3')

@section('title', 'สร้างใบประกาศนียบัตร')

@section('content')
<div x-data="certificateForm()" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 sm:p-8 text-white mb-8 shadow-xl">
        <h1 class="text-2xl sm:text-3xl font-extrabold mb-2">➕ สร้างใบประกาศนียบัตร</h1>
        <p class="text-indigo-100">สร้างใบประกาศนียบัตรด้วยตนเอง (Manual)</p>
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
        <form action="{{ route('admin.academy.certificates.store') }}" method="POST">
            @csrf

            <!-- Student Selection -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อมูลนักเรียน
                </h3>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            เลือกนักเรียน <span class="text-red-500">*</span>
                        </label>
                        <select name="user_id" required x-model="selectedUser" @change="fillUserData()"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                            <option value="">-- เลือกนักเรียน --</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}"
                                        data-name="{{ $user->name }}"
                                        data-email="{{ $user->email }}"
                                        {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} ({{ $user->email }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เลือกผู้ใช้ที่จะรับใบประกาศ</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                ชื่อที่แสดงบนใบประกาศ
                            </label>
                            <input type="text" name="student_name" x-model="studentName"
                                   value="{{ old('student_name') }}"
                                   placeholder="จะใช้ชื่อจากระบบถ้าไม่ระบุ"
                                   class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                อีเมลที่แสดงบนใบประกาศ
                            </label>
                            <input type="email" name="student_email" x-model="studentEmail"
                                   value="{{ old('student_email') }}"
                                   placeholder="จะใช้อีเมลจากระบบถ้าไม่ระบุ"
                                   class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Course Selection -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อมูลคอร์ส
                </h3>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        เลือกคอร์ส <span class="text-red-500">*</span>
                    </label>
                    <select name="article_id" required
                            class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        <option value="">-- เลือกคอร์ส --</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ old('article_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เลือกคอร์สที่นักเรียนเรียนจบ</p>
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
                               value="{{ old('quiz_score') }}"
                               min="0" max="100" step="0.01"
                               placeholder="เช่น 85.5"
                               class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">คะแนนเฉลี่ยจาก Quiz (ถ้ามี)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชั่วโมงเรียนทั้งหมด
                        </label>
                        <input type="number" name="total_hours"
                               value="{{ old('total_hours') }}"
                               min="0" step="0.1"
                               placeholder="เช่น 12.5"
                               class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">จำนวนชั่วโมงที่ใช้เรียน</p>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white pb-3 mb-4 border-b-2 border-gray-100 dark:border-gray-700">
                    ข้อมูลเพิ่มเติม (ถ้ามี)
                </h3>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        ข้อความพิเศษ
                    </label>
                    <textarea name="custom_text" rows="3"
                              placeholder="ข้อความพิเศษที่จะแสดงบนใบประกาศ เช่น ยินดีด้วย! คุณได้รับรางวัลผู้เรียนดีเด่น"
                              class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-y">{{ old('custom_text') }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ข้อความนี้จะปรากฏบนใบประกาศ (ถ้ามี)</p>
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
                    <span>สร้างใบประกาศ</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js component สำหรับฟอร์มสร้างใบประกาศ
 * ใช้ Tailwind CSS + Alpine.js ตาม V3 standards
 */
function certificateForm() {
    return {
        selectedUser: '{{ old('user_id', '') }}',
        studentName: '{{ old('student_name', '') }}',
        studentEmail: '{{ old('student_email', '') }}',

        /**
         * เติมข้อมูลผู้ใช้จาก dropdown
         */
        fillUserData() {
            const select = document.querySelector('select[name="user_id"]');
            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption.value) {
                this.studentName = selectedOption.getAttribute('data-name') || '';
                this.studentEmail = selectedOption.getAttribute('data-email') || '';
            } else {
                this.studentName = '';
                this.studentEmail = '';
            }
        }
    }
}
</script>
@endpush
@endsection
