@extends('layouts.app')

@section('content')
{{--
    หน้าสร้างแบบทดสอบใหม่
    V3: ใช้ Tailwind CSS + Alpine.js
--}}
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8"
     x-data="quizCreator()">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('admin.quiz-management.index') }}"
                   class="p-2 bg-white dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <svg class="w-10 h-10 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                        </svg>
                        สร้างแบบทดสอบใหม่
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">สร้างแบบทดสอบเพื่อประเมินความรู้ผู้เรียน</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.quiz-management.store') }}" method="POST" @submit="handleSubmit">
            @csrf

            <!-- Quiz Basic Info -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 mb-6 border border-gray-100 dark:border-gray-700">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    ข้อมูลพื้นฐาน
                </h2>

                <div class="space-y-6">
                    <!-- Article Selection -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            เลือกบทความ <span class="text-red-500">*</span>
                        </label>
                        <select name="article_id" required x-model="formData.article_id"
                                class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:text-white transition-all duration-200">
                            <option value="">-- เลือกบทความ --</option>
                            @foreach($articles as $article)
                                <option value="{{ $article->id }}" {{ $selectedArticleId == $article->id ? 'selected' : '' }}>
                                    {{ $article->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('article_id')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            ชื่อแบบทดสอบ <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="title" required x-model="formData.title"
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
                        <textarea name="description" rows="3" x-model="formData.description"
                                  class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:text-white transition-all duration-200"
                                  placeholder="อธิบายรายละเอียดของแบบทดสอบ..."></textarea>
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
                            <input type="number" name="passing_score" required min="0" max="100" x-model="formData.passing_score"
                                   class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:text-white transition-all duration-200">
                            @error('passing_score')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                เวลาจำกัด (นาที)
                            </label>
                            <input type="number" name="time_limit" min="1" x-model="formData.time_limit"
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
                            <input type="number" name="max_attempts" min="1" x-model="formData.max_attempts"
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
                            <input type="checkbox" name="randomize_questions" value="1" x-model="formData.randomize_questions"
                                   class="w-5 h-5 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">สุ่มลำดับคำถาม</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="show_results_immediately" value="1" x-model="formData.show_results_immediately"
                                   class="w-5 h-5 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">แสดงผลทันที</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="show_correct_answers" value="1" x-model="formData.show_correct_answers"
                                   class="w-5 h-5 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">แสดงเฉลย</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_required" value="1" x-model="formData.is_required"
                                   class="w-5 h-5 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">บังคับทำ</span>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="formData.is_active"
                                   class="w-5 h-5 text-indigo-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300 font-medium">เปิดใช้งาน</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Questions Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 mb-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                        </svg>
                        คำถาม <span class="text-indigo-600 dark:text-indigo-400" x-text="questions.length"></span>
                    </h2>
                    <button type="button" @click="addQuestion()"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        เพิ่มคำถาม
                    </button>
                </div>

                <!-- Questions Container -->
                <div class="space-y-6">
                    <template x-for="(question, qIndex) in questions" :key="question.id">
                        <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-gray-700 dark:to-gray-800 rounded-xl p-6 border-2 border-indigo-200 dark:border-indigo-800 transition-all duration-300">
                            <div class="flex items-start justify-between mb-4">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span class="flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full text-sm font-bold"
                                          x-text="qIndex + 1"></span>
                                    <span>คำถามที่ <span x-text="qIndex + 1"></span></span>
                                </h3>
                                <button type="button" @click="removeQuestion(qIndex)"
                                        class="p-2 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-4">
                                <!-- Question Type -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ประเภทคำถาม</label>
                                    <select x-model="question.type" :name="'questions[' + qIndex + '][type]'" required
                                            class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                        <option value="multiple_choice">ตัวเลือกเดียว</option>
                                        <option value="multiple_answer">หลายตัวเลือก</option>
                                        <option value="true_false">ถูก/ผิด</option>
                                    </select>
                                </div>

                                <!-- Question Text -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">คำถาม</label>
                                    <textarea x-model="question.question" :name="'questions[' + qIndex + '][question]'" required rows="3"
                                              class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                                              placeholder="พิมพ์คำถามที่นี่..."></textarea>
                                </div>

                                <!-- Points -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">คะแนน</label>
                                    <input type="number" x-model="question.points" :name="'questions[' + qIndex + '][points]'" required min="1"
                                           class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white">
                                </div>

                                <!-- Explanation -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">คำอธิบาย (ทางเลือก)</label>
                                    <textarea x-model="question.explanation" :name="'questions[' + qIndex + '][explanation]'" rows="2"
                                              class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                                              placeholder="อธิบายเพิ่มเติมหลังแสดงคำตอบ..."></textarea>
                                </div>

                                <!-- Options -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">ตัวเลือก</label>
                                    <div class="space-y-3">
                                        <template x-for="(option, oIndex) in question.options" :key="option.id">
                                            <div class="flex items-center gap-3 bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <input type="checkbox"
                                                       :name="'questions[' + qIndex + '][options][' + oIndex + '][is_correct]'"
                                                       value="1"
                                                       x-model="option.is_correct"
                                                       class="w-5 h-5 text-green-600 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-green-500">
                                                <input type="text"
                                                       :name="'questions[' + qIndex + '][options][' + oIndex + '][text]'"
                                                       x-model="option.text"
                                                       required
                                                       class="flex-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:text-white"
                                                       :placeholder="'ข้อที่ ' + (oIndex + 1)">
                                                <button type="button" @click="removeOption(qIndex, oIndex)"
                                                        class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors duration-200"
                                                        x-show="question.options.length > 2">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <button type="button" @click="addOption(qIndex)"
                                            class="mt-3 inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        เพิ่มตัวเลือก
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="questions.length === 0"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     class="text-center py-12">
                    <svg class="w-20 h-20 mx-auto text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">ยังไม่มีคำถาม กดปุ่ม "เพิ่มคำถาม" เพื่อเริ่มต้น</p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex gap-4">
                <a href="{{ route('admin.quiz-management.index') }}"
                   class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-all duration-200">
                    ยกเลิก
                </a>
                <button type="submit"
                        :disabled="isSubmitting"
                        :class="{'opacity-50 cursor-not-allowed': isSubmitting}"
                        class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    <svg x-show="!isSubmitting" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg x-show="isSubmitting" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isSubmitting ? 'กำลังบันทึก...' : 'สร้างแบบทดสอบ'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Alpine.js Component สำหรับสร้างแบบทดสอบ
 * V3 Standards: ใช้ Alpine.js แทน vanilla JavaScript
 */
function quizCreator() {
    return {
        // สถานะการ submit
        isSubmitting: false,

        // ข้อมูลฟอร์มพื้นฐาน
        formData: {
            article_id: '{{ $selectedArticleId ?? '' }}',
            title: '{{ old('title', '') }}',
            description: '{{ old('description', '') }}',
            passing_score: {{ old('passing_score', 70) }},
            time_limit: '{{ old('time_limit', '') }}',
            max_attempts: '{{ old('max_attempts', '') }}',
            randomize_questions: {{ old('randomize_questions') ? 'true' : 'false' }},
            show_results_immediately: {{ old('show_results_immediately', true) ? 'true' : 'false' }},
            show_correct_answers: {{ old('show_correct_answers', true) ? 'true' : 'false' }},
            is_required: {{ old('is_required') ? 'true' : 'false' }},
            is_active: {{ old('is_active', true) ? 'true' : 'false' }},
        },

        // รายการคำถาม
        questions: [],

        // Counter สำหรับ unique IDs
        questionIdCounter: 0,
        optionIdCounter: 0,

        /**
         * เพิ่มคำถามใหม่
         */
        addQuestion() {
            this.questions.push({
                id: this.questionIdCounter++,
                type: 'multiple_choice',
                question: '',
                points: 1,
                explanation: '',
                options: [
                    { id: this.optionIdCounter++, text: '', is_correct: false },
                    { id: this.optionIdCounter++, text: '', is_correct: false }
                ]
            });
        },

        /**
         * ลบคำถาม
         * @param {number} index - index ของคำถามที่จะลบ
         */
        removeQuestion(index) {
            if (confirm('ต้องการลบคำถามนี้หรือไม่?')) {
                this.questions.splice(index, 1);
            }
        },

        /**
         * เพิ่มตัวเลือกให้คำถาม
         * @param {number} qIndex - index ของคำถาม
         */
        addOption(qIndex) {
            this.questions[qIndex].options.push({
                id: this.optionIdCounter++,
                text: '',
                is_correct: false
            });
        },

        /**
         * ลบตัวเลือก
         * @param {number} qIndex - index ของคำถาม
         * @param {number} oIndex - index ของตัวเลือก
         */
        removeOption(qIndex, oIndex) {
            if (this.questions[qIndex].options.length > 2) {
                this.questions[qIndex].options.splice(oIndex, 1);
            }
        },

        /**
         * จัดการ submit ฟอร์ม
         * @param {Event} e - event object
         */
        handleSubmit(e) {
            // ตรวจสอบว่ามีคำถามอย่างน้อย 1 ข้อ
            if (this.questions.length === 0) {
                e.preventDefault();
                alert('กรุณาเพิ่มคำถามอย่างน้อย 1 ข้อ');
                return false;
            }

            // ตรวจสอบว่าทุกคำถามมีคำตอบที่ถูกต้องอย่างน้อย 1 ข้อ
            for (let i = 0; i < this.questions.length; i++) {
                const hasCorrect = this.questions[i].options.some(opt => opt.is_correct);
                if (!hasCorrect) {
                    e.preventDefault();
                    alert(`กรุณาเลือกคำตอบที่ถูกต้องสำหรับคำถามที่ ${i + 1}`);
                    return false;
                }
            }

            this.isSubmitting = true;
            return true;
        }
    };
}
</script>
@endsection
