@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('admin.quiz-management.show', $quiz->id) }}"
                   class="p-2 bg-white dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <svg class="w-10 h-10 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                        </svg>
                        แก้ไขแบบทดสอบ
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">แก้ไขการตั้งค่าแบบทดสอบ</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.quiz-management.update', $quiz->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Quiz Basic Info -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 mb-6 border border-gray-100 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    ข้อมูลพื้นฐาน
                </h2>

                <div class="space-y-6">
                    <!-- Article (Read-only) -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            บทความ
                        </label>
                        <div class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-600 dark:text-gray-400">
                            {{ $quiz->article->title ?? 'N/A' }}
                        </div>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">ไม่สามารถเปลี่ยนบทความได้หลังจากสร้างแบบทดสอบแล้ว</p>
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อแบบทดสอบ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" required value="{{ old('title', $quiz->title) }}"
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:text-white transition-all duration-200"
                               placeholder="เช่น แบบทดสอบบทที่ 1: ความรู้พื้นฐาน">
                        @error('title')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            คำอธิบาย
                        </label>
                        <textarea name="description" rows="3"
                                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:text-white transition-all duration-200"
                                  placeholder="อธิบายรายละเอียดของแบบทดสอบ...">{{ old('description', $quiz->description) }}</textarea>
                        @error('description')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Settings Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                คะแนนผ่าน (%) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" name="passing_score" required min="0" max="100" value="{{ old('passing_score', $quiz->passing_score) }}"
                                   class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:text-white transition-all duration-200">
                            @error('passing_score')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                เวลาจำกัด (นาที)
                            </label>
                            <input type="number" name="time_limit" min="1" value="{{ old('time_limit', $quiz->time_limit) }}"
                                   class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:text-white transition-all duration-200"
                                   placeholder="ไม่จำกัด">
                            @error('time_limit')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                จำนวนครั้งสูงสุด
                            </label>
                            <input type="number" name="max_attempts" min="1" value="{{ old('max_attempts', $quiz->max_attempts) }}"
                                   class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:text-white transition-all duration-200"
                                   placeholder="ไม่จำกัด">
                            @error('max_attempts')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Toggle Options -->
                    <div class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="randomize_questions" value="1" {{ old('randomize_questions', $quiz->randomize_questions) ? 'checked' : '' }}
                                   class="w-5 h-5 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">สุ่มลำดับคำถาม</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="show_results_immediately" value="1" {{ old('show_results_immediately', $quiz->show_results_immediately) ? 'checked' : '' }}
                                   class="w-5 h-5 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">แสดงผลทันที</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="show_correct_answers" value="1" {{ old('show_correct_answers', $quiz->show_correct_answers) ? 'checked' : '' }}
                                   class="w-5 h-5 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">แสดงเฉลย</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_required" value="1" {{ old('is_required', $quiz->is_required) ? 'checked' : '' }}
                                   class="w-5 h-5 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">บังคับทำ</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $quiz->is_active) ? 'checked' : '' }}
                                   class="w-5 h-5 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">เปิดใช้งาน</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Info Notice -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-lg font-bold text-blue-900 dark:text-blue-300 mb-2">หมายเหตุ</h3>
                        <p class="text-blue-800 dark:text-blue-400">
                            การแก้ไขหน้านี้จะเปลี่ยนแปลงเฉพาะการตั้งค่าของแบบทดสอบเท่านั้น หากต้องการแก้ไขคำถามหรือตัวเลือก กรุณาติดต่อผู้ดูแลระบบ
                        </p>
                    </div>
                </div>
            </div>

            <!-- Current Questions Preview (Read-only) -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 mb-6 border border-gray-100 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                    คำถามปัจจุบัน ({{ $quiz->questions->count() }})
                </h2>

                <div class="space-y-4">
                    @foreach($quiz->questions as $index => $question)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-xl p-4 border border-gray-200 dark:border-gray-600">
                        <div class="flex items-start gap-3">
                            <div class="flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full text-sm font-bold flex-shrink-0">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-gray-900 dark:text-white font-medium line-clamp-2">{{ $question->question }}</p>
                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    <span class="px-2 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300 text-xs font-semibold rounded">
                                        {{ $question->points }} คะแนน
                                    </span>
                                    <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 text-xs font-semibold rounded">
                                        {{ $question->options->count() }} ตัวเลือก
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-4">
                <a href="{{ route('admin.quiz-management.show', $quiz->id) }}"
                   class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-200">
                    ยกเลิก
                </a>
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    บันทึกการเปลี่ยนแปลง
                </button>
            </div>

            <!-- Danger Zone -->
            <div class="mt-8 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-2xl p-6">
                <h3 class="text-xl font-bold text-red-900 dark:text-red-300 mb-4 flex items-center gap-2">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    โซนอันตราย
                </h3>
                <p class="text-red-800 dark:text-red-400 mb-4">การลบแบบทดสอบจะลบข้อมูลทั้งหมด รวมถึงคำถามและผลการทำของผู้เรียนทั้งหมด</p>
                <button type="button" onclick="confirmDelete()"
                        class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    ลบแบบทดสอบ
                </button>
            </div>
        </form>

        <!-- Delete Form (hidden) -->
        <form id="deleteForm" action="{{ route('admin.quiz-management.destroy', $quiz->id) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบแบบทดสอบนี้? การกระทำนี้ไม่สามารถย้อนกลับได้!')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

<style>
.line-clamp-2 {
    overflow: hidden;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}
</style>
@endsection
