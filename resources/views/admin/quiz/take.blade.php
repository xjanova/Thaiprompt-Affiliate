@extends('layouts.admin-v3')

@section('title', $quiz->title . ' - Quiz')

{{--
    หน้าทำแบบทดสอบ (V3 Standards + Anti-Cheat System)

    ฟีเจอร์กันโกง:
    - ตรวจจับเปลี่ยน Tab (Tab switching detection)
    - ป้องกัน Copy/Paste
    - ปิด Right-click
    - โหมดแสดงทีละคำถาม (One question at a time)
    - สุ่มลำดับตัวเลือก (Randomize options)
    - บันทึก IP/Device
    - Fullscreen Mode
--}}

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"
     x-data="quizTaker()"
     x-init="init()"
     @visibilitychange.window="handleVisibilityChange()"
     @blur.window="handleWindowBlur()"
     @focus.window="handleWindowFocus()"
     @contextmenu.prevent
     @copy.prevent="showWarning('ไม่อนุญาตให้คัดลอกข้อความ')"
     @paste.prevent="showWarning('ไม่อนุญาตให้วางข้อความ')"
     @cut.prevent="showWarning('ไม่อนุญาตให้ตัดข้อความ')"
     @selectstart.prevent
     @keydown.prevent.ctrl.c
     @keydown.prevent.ctrl.v
     @keydown.prevent.ctrl.u
     @keydown.prevent.f12>

    <!-- Fullscreen Overlay (บังคับเปิด Fullscreen) -->
    <div x-show="!isFullscreen && requireFullscreen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-gray-900/95 z-50 flex items-center justify-center">
        <div class="text-center p-8 max-w-md">
            <div class="w-24 h-24 mx-auto mb-6 bg-indigo-600/20 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-4">กรุณาเปิดโหมดเต็มจอ</h2>
            <p class="text-gray-300 mb-6">เพื่อความยุติธรรมในการทำแบบทดสอบ กรุณาเปิดโหมดเต็มจอก่อนเริ่มทำ</p>
            <button @click="enterFullscreen()"
                    class="px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold rounded-xl shadow-lg transform hover:scale-105 transition-all duration-200">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
                เปิดโหมดเต็มจอ
            </button>
        </div>
    </div>

    <!-- Tab Switch Warning Modal -->
    <div x-show="showTabWarning"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-red-900/95 z-50 flex items-center justify-center">
        <div class="text-center p-8 max-w-md">
            <div class="w-24 h-24 mx-auto mb-6 bg-red-600/20 rounded-full flex items-center justify-center animate-pulse">
                <svg class="w-12 h-12 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-4">⚠️ ตรวจพบการออกจากหน้าข้อสอบ!</h2>
            <p class="text-red-200 mb-4">คุณออกจากหน้าข้อสอบ <span class="font-bold text-white" x-text="tabSwitchCount"></span> ครั้งแล้ว</p>
            <p class="text-red-300 mb-6 text-sm">หากออกจากหน้าข้อสอบเกิน 3 ครั้ง ระบบจะส่งข้อสอบอัตโนมัติ</p>
            <button @click="showTabWarning = false"
                    class="px-8 py-4 bg-white text-red-600 font-bold rounded-xl shadow-lg transform hover:scale-105 transition-all duration-200">
                กลับไปทำข้อสอบ
            </button>
        </div>
    </div>

    <!-- Warning Toast -->
    <div x-show="warningMessage"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform -translate-y-4"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed top-4 right-4 z-50 bg-red-600 text-white px-6 py-4 rounded-xl shadow-lg">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span x-text="warningMessage" class="font-medium"></span>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Back Link -->
        <div class="mb-4">
            <a href="{{ route('admin.learning-center.article', $quiz->article->slug) }}"
               class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปยังคอร์ส
            </a>
        </div>

        <!-- Quiz Header -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 mb-6 border border-gray-100 dark:border-gray-700">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">{{ $quiz->title }}</h1>

            @if($quiz->description)
            <p class="text-gray-600 dark:text-gray-400 mb-6">{{ $quiz->description }}</p>
            @endif

            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold">{{ $quiz->total_questions }} คำถาม</span>
                </div>

                @if($quiz->time_limit)
                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-semibold">{{ $quiz->time_limit }} นาที</span>
                </div>
                @endif

                <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                    <span class="font-semibold">คะแนนผ่าน: {{ $quiz->passing_score }}%</span>
                </div>
            </div>

            <!-- Anti-Cheat Warning -->
            <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-500 rounded-r-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h4 class="font-bold text-amber-800 dark:text-amber-300">ข้อควรทราบ</h4>
                        <ul class="text-sm text-amber-700 dark:text-amber-400 mt-1 space-y-1">
                            <li>• ห้ามออกจากหน้าข้อสอบระหว่างทำ (จะถูกบันทึก)</li>
                            <li>• ห้ามคัดลอกหรือวางข้อความ</li>
                            <li>• ห้ามใช้เครื่องมือพัฒนา (DevTools)</li>
                            <li>• ทำในโหมดเต็มจอเท่านั้น</li>
                        </ul>
                    </div>
                </div>
            </div>

            @if($previousAttempts->count() > 0)
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-r-lg">
                <p class="font-medium text-blue-800 dark:text-blue-300">
                    คุณเคยทำ Quiz นี้แล้ว {{ $previousAttempts->count() }} ครั้ง
                    @if($bestAttempt)
                    - คะแนนที่ดีที่สุด: <strong>{{ number_format($bestAttempt->percentage, 2) }}%</strong>
                    ({{ $bestAttempt->passed ? '✅ ผ่าน' : '❌ ไม่ผ่าน' }})
                    @endif
                </p>
            </div>
            @endif
        </div>

        <!-- Timer (Sticky) -->
        @if($quiz->time_limit)
        <div class="sticky top-4 z-40 mb-6">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 text-white"
                 :class="{'from-red-600 to-red-700': timeRemaining <= 60}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-lg font-medium">เวลาที่เหลือ</span>
                    </div>
                    <div class="text-4xl font-bold font-mono" x-text="formatTime(timeRemaining)"></div>
                </div>
            </div>
        </div>
        @endif

        <!-- Progress Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 mb-6 border border-gray-100 dark:border-gray-700">
            <div class="flex justify-between items-center mb-3">
                <span class="font-semibold text-gray-700 dark:text-gray-300">ความก้าวหน้า</span>
                <span class="text-gray-600 dark:text-gray-400">
                    <span x-text="answeredCount"></span> / {{ $quiz->total_questions }}
                </span>
            </div>
            <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-indigo-600 to-purple-600 rounded-full transition-all duration-500"
                     :style="'width: ' + (answeredCount / totalQuestions * 100) + '%'"></div>
            </div>

            <!-- Question Navigation (One at a time mode) -->
            <div class="flex flex-wrap gap-2 mt-4" x-show="oneQuestionMode">
                @foreach($questions as $index => $question)
                <button type="button"
                        @click="goToQuestion({{ $index }})"
                        :class="{
                            'bg-indigo-600 text-white': currentQuestion === {{ $index }},
                            'bg-green-500 text-white': answers[{{ $question->id }}] && currentQuestion !== {{ $index }},
                            'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300': !answers[{{ $question->id }}] && currentQuestion !== {{ $index }}
                        }"
                        class="w-10 h-10 rounded-lg font-bold transition-all duration-200 hover:scale-105">
                    {{ $index + 1 }}
                </button>
                @endforeach
            </div>
        </div>

        <!-- Quiz Form -->
        <form method="POST" action="{{ route('admin.quiz.submit', $quiz->id) }}" @submit.prevent="submitQuiz()">
            @csrf
            <input type="hidden" name="attempt_id" value="{{ $currentAttempt->id }}">
            <input type="hidden" name="tab_switch_count" x-model="tabSwitchCount">
            <input type="hidden" name="blur_count" x-model="blurCount">
            <input type="hidden" name="ip_address" value="{{ request()->ip() }}">
            <input type="hidden" name="user_agent" value="{{ request()->userAgent() }}">

            <!-- Questions -->
            @foreach($questions as $index => $question)
            @php
                // สุ่มลำดับตัวเลือก (Server-side)
                $shuffledOptions = $question->options->shuffle();
            @endphp
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 mb-6 border-2 transition-all duration-300"
                 :class="{
                     'border-indigo-500 ring-2 ring-indigo-500/20': currentQuestion === {{ $index }},
                     'border-gray-100 dark:border-gray-700': currentQuestion !== {{ $index }}
                 }"
                 x-show="!oneQuestionMode || currentQuestion === {{ $index }}"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-x-4"
                 x-transition:enter-end="opacity-100 transform translate-x-0">

                <div class="flex items-start justify-between mb-4">
                    <span class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg">
                        คำถามที่ {{ $index + 1 }}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 bg-amber-500 text-white text-sm font-bold rounded-lg">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ $question->points }} คะแนน
                    </span>
                </div>

                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6 select-none">
                    {{ $question->question }}
                </h3>

                @if($question->image_url)
                <div class="mb-6">
                    <img src="{{ $question->image_url }}" alt="Question Image"
                         class="max-w-full rounded-xl pointer-events-none"
                         draggable="false"
                         oncontextmenu="return false;">
                </div>
                @endif

                @if($question->type === 'multiple_choice' || $question->type === 'true_false')
                <!-- Multiple Choice / True False -->
                <div class="space-y-3">
                    @foreach($shuffledOptions as $option)
                    <label class="flex items-center p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer transition-all duration-200 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 select-none"
                           :class="{'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': answers[{{ $question->id }}] === '{{ $option->id }}'}">
                        <input type="radio"
                               name="answers[{{ $question->id }}]"
                               value="{{ $option->id }}"
                               x-model="answers[{{ $question->id }}]"
                               @change="updateProgress()"
                               class="w-5 h-5 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <span class="ml-4 text-gray-900 dark:text-white font-medium select-none">{{ $option->option_text }}</span>
                    </label>
                    @endforeach
                </div>

                @elseif($question->type === 'multiple_answer')
                <!-- Multiple Answer -->
                <div class="space-y-3">
                    @foreach($shuffledOptions as $option)
                    <label class="flex items-center p-4 border-2 border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer transition-all duration-200 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 select-none">
                        <input type="checkbox"
                               name="answers[{{ $question->id }}][]"
                               value="{{ $option->id }}"
                               @change="updateMultipleAnswer({{ $question->id }}, '{{ $option->id }}')"
                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="ml-4 text-gray-900 dark:text-white font-medium select-none">{{ $option->option_text }}</span>
                    </label>
                    @endforeach
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">* เลือกได้มากกว่า 1 ข้อ</p>

                @elseif($question->type === 'short_answer')
                <!-- Short Answer -->
                <textarea name="answers[{{ $question->id }}]"
                          x-model="answers[{{ $question->id }}]"
                          @input="updateProgress()"
                          rows="4"
                          class="w-full px-4 py-3 border-2 border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
                          placeholder="พิมพ์คำตอบของคุณที่นี่..."
                          @paste.prevent></textarea>
                @endif

                <!-- Navigation Buttons (One at a time mode) -->
                <div class="flex justify-between mt-6 pt-6 border-t border-gray-200 dark:border-gray-700" x-show="oneQuestionMode">
                    <button type="button"
                            @click="prevQuestion()"
                            :disabled="currentQuestion === 0"
                            :class="{'opacity-50 cursor-not-allowed': currentQuestion === 0}"
                            class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        ก่อนหน้า
                    </button>
                    <button type="button"
                            @click="nextQuestion()"
                            x-show="currentQuestion < totalQuestions - 1"
                            class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all duration-200">
                        ถัดไป
                        <svg class="w-5 h-5 inline ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </div>
            @endforeach

            <!-- Submit Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 border border-gray-100 dark:border-gray-700 text-center">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">พร้อมส่ง Quiz แล้วหรือยัง?</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">โปรดตรวจสอบคำตอบของคุณอีกครั้งก่อนส่ง</p>

                <!-- Unanswered Warning -->
                <div x-show="answeredCount < totalQuestions"
                     class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                    <p class="text-amber-800 dark:text-amber-300 font-medium">
                        ⚠️ คุณยังไม่ได้ตอบคำถาม <span x-text="totalQuestions - answeredCount"></span> ข้อ
                    </p>
                </div>

                <button type="submit"
                        :disabled="isSubmitting"
                        :class="{'opacity-50 cursor-not-allowed': isSubmitting}"
                        class="px-12 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white text-xl font-bold rounded-xl shadow-lg transform hover:scale-105 transition-all duration-200">
                    <svg x-show="!isSubmitting" class="w-6 h-6 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    <svg x-show="isSubmitting" class="animate-spin w-6 h-6 inline mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isSubmitting ? 'กำลังส่ง...' : 'ส่ง Quiz'"></span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
/**
 * Alpine.js Component สำหรับทำแบบทดสอบ + ระบบกันโกง
 * V3 Standards: Tailwind CSS + Alpine.js
 */
function quizTaker() {
    return {
        // Quiz settings
        totalQuestions: {{ $quiz->total_questions }},
        timeLimit: {{ $quiz->time_limit ?? 0 }},
        timeRemaining: {{ ($quiz->time_limit ?? 0) * 60 }},
        timerInterval: null,

        // Navigation (One question at a time mode)
        oneQuestionMode: true, // เปิดโหมดแสดงทีละคำถาม
        currentQuestion: 0,

        // Answers tracking
        answers: {},
        answeredCount: 0,

        // Submission state
        isSubmitting: false,

        // Anti-cheat features
        requireFullscreen: true, // บังคับ fullscreen
        isFullscreen: false,
        tabSwitchCount: 0,
        blurCount: 0,
        showTabWarning: false,
        warningMessage: '',
        maxTabSwitches: 3, // จำนวนครั้งสูงสุดที่อนุญาตให้เปลี่ยน tab

        /**
         * เริ่มต้นระบบ
         */
        init() {
            // ตรวจสอบ fullscreen
            this.checkFullscreen();
            document.addEventListener('fullscreenchange', () => this.checkFullscreen());

            // เริ่ม timer ถ้ามี time limit
            if (this.timeLimit > 0) {
                this.startTimer();
            }

            // ป้องกัน DevTools
            this.preventDevTools();

            // Log เริ่มทำข้อสอบ
            console.log('Quiz started - Anti-cheat system active');
        },

        /**
         * เริ่มนับเวลาถอยหลัง
         */
        startTimer() {
            this.timerInterval = setInterval(() => {
                if (this.timeRemaining <= 0) {
                    clearInterval(this.timerInterval);
                    this.showWarning('หมดเวลา! กำลังส่งข้อสอบอัตโนมัติ...');
                    setTimeout(() => this.submitQuiz(), 2000);
                    return;
                }
                this.timeRemaining--;
            }, 1000);
        },

        /**
         * แปลงเวลาเป็น MM:SS
         */
        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        },

        /**
         * อัพเดทความก้าวหน้า
         */
        updateProgress() {
            let count = 0;
            for (let key in this.answers) {
                if (this.answers[key] && this.answers[key] !== '') {
                    count++;
                }
            }
            this.answeredCount = count;
        },

        /**
         * จัดการคำตอบหลายตัวเลือก
         */
        updateMultipleAnswer(questionId, optionId) {
            if (!this.answers[questionId]) {
                this.answers[questionId] = [];
            }
            const index = this.answers[questionId].indexOf(optionId);
            if (index > -1) {
                this.answers[questionId].splice(index, 1);
            } else {
                this.answers[questionId].push(optionId);
            }
            this.updateProgress();
        },

        /**
         * Navigation - ไปคำถามก่อนหน้า
         */
        prevQuestion() {
            if (this.currentQuestion > 0) {
                this.currentQuestion--;
            }
        },

        /**
         * Navigation - ไปคำถามถัดไป
         */
        nextQuestion() {
            if (this.currentQuestion < this.totalQuestions - 1) {
                this.currentQuestion++;
            }
        },

        /**
         * Navigation - ไปคำถามที่ระบุ
         */
        goToQuestion(index) {
            this.currentQuestion = index;
        },

        /**
         * ===== ANTI-CHEAT FEATURES =====
         */

        /**
         * ตรวจจับการเปลี่ยน visibility (เปลี่ยน tab)
         */
        handleVisibilityChange() {
            if (document.hidden) {
                this.tabSwitchCount++;
                this.blurCount++;

                if (this.tabSwitchCount >= this.maxTabSwitches) {
                    this.showWarning('เกินจำนวนครั้งที่อนุญาต! กำลังส่งข้อสอบอัตโนมัติ...');
                    setTimeout(() => this.submitQuiz(), 2000);
                } else {
                    this.showTabWarning = true;
                }
            }
        },

        /**
         * ตรวจจับการออกจากหน้าต่าง
         */
        handleWindowBlur() {
            this.blurCount++;
        },

        /**
         * ตรวจจับการกลับเข้ามาหน้าต่าง
         */
        handleWindowFocus() {
            // Log กลับมา
        },

        /**
         * แสดงข้อความเตือน
         */
        showWarning(message) {
            this.warningMessage = message;
            setTimeout(() => {
                this.warningMessage = '';
            }, 3000);
        },

        /**
         * ตรวจสอบ fullscreen
         */
        checkFullscreen() {
            this.isFullscreen = !!document.fullscreenElement;
        },

        /**
         * เปิด fullscreen
         */
        async enterFullscreen() {
            try {
                await document.documentElement.requestFullscreen();
                this.isFullscreen = true;
            } catch (err) {
                console.error('Fullscreen error:', err);
                // อนุญาตให้ทำต่อแม้ fullscreen ไม่ทำงาน
                this.requireFullscreen = false;
            }
        },

        /**
         * ป้องกัน DevTools
         */
        preventDevTools() {
            // ตรวจจับ DevTools ด้วย debugger
            setInterval(() => {
                const start = performance.now();
                debugger;
                const end = performance.now();
                if (end - start > 100) {
                    this.showWarning('ตรวจพบการใช้ DevTools!');
                    this.tabSwitchCount++;
                }
            }, 1000);

            // ปิด keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // F12
                if (e.key === 'F12') {
                    e.preventDefault();
                    this.showWarning('ไม่อนุญาตให้ใช้ DevTools');
                }
                // Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+Shift+C
                if (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase())) {
                    e.preventDefault();
                    this.showWarning('ไม่อนุญาตให้ใช้ DevTools');
                }
                // Ctrl+U (View Source)
                if (e.ctrlKey && e.key.toUpperCase() === 'U') {
                    e.preventDefault();
                    this.showWarning('ไม่อนุญาตให้ดู Source Code');
                }
            });
        },

        /**
         * ส่งข้อสอบ
         */
        submitQuiz() {
            if (this.isSubmitting) return;

            const unanswered = this.totalQuestions - this.answeredCount;

            if (unanswered > 0) {
                if (!confirm(`คุณยังไม่ได้ตอบคำถาม ${unanswered} ข้อ\nต้องการส่ง Quiz หรือไม่?`)) {
                    return;
                }
            } else {
                if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการส่ง Quiz?\nคุณจะไม่สามารถแก้ไขคำตอบได้อีก')) {
                    return;
                }
            }

            this.isSubmitting = true;

            // ออกจาก fullscreen
            if (document.fullscreenElement) {
                document.exitFullscreen();
            }

            // ส่งฟอร์ม
            document.querySelector('form').submit();
        }
    };
}
</script>
@endsection
