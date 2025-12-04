@extends('layouts.admin')

@section('title', 'จัดการ Quiz - ' . $article->title)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.instructor.courses.edit', $article) }}"
               class="p-2 text-gray-400 hover:text-white transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white">
                    <i class="fas fa-clipboard-question mr-3"></i>
                    จัดการ Quiz
                </h1>
                <p class="text-gray-300 mt-1">{{ $article->title }}</p>
            </div>
        </div>
        <a href="{{ route('admin.quiz-management.create') }}?article_id={{ $article->id }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700 text-white font-semibold rounded-xl shadow-lg transition">
            <i class="fas fa-plus"></i>
            สร้าง Quiz ใหม่
        </a>
    </div>

    {{-- Quiz List --}}
    <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 overflow-hidden">
        @if($article->quizzes->count() > 0)
            <div class="divide-y divide-white/10">
                @foreach($article->quizzes as $quiz)
                    <div class="p-6 hover:bg-white/5 transition">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            {{-- Quiz Info --}}
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-semibold text-white">{{ $quiz->title }}</h3>
                                    @if($quiz->is_active)
                                        <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full">เปิดใช้งาน</span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-500/20 text-gray-400 text-xs rounded-full">ปิดใช้งาน</span>
                                    @endif
                                </div>
                                @if($quiz->description)
                                    <p class="text-gray-400 mb-3">{{ $quiz->description }}</p>
                                @endif
                                <div class="flex flex-wrap gap-4 text-sm text-gray-300">
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-question-circle text-purple-400"></i>
                                        {{ $quiz->questions->count() }} คำถาม
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-check-circle text-green-400"></i>
                                        ผ่านขั้นต่ำ {{ $quiz->passing_score ?? 70 }}%
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-clock text-blue-400"></i>
                                        {{ $quiz->time_limit ? $quiz->time_limit . ' นาที' : 'ไม่จำกัดเวลา' }}
                                    </span>
                                    @if($quiz->max_attempts)
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-redo text-yellow-400"></i>
                                            ทำได้ {{ $quiz->max_attempts }} ครั้ง
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.quiz-management.edit', $quiz->id) }}"
                                   class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition">
                                    <i class="fas fa-edit mr-1"></i>
                                    แก้ไข
                                </a>
                                <a href="{{ route('admin.quiz-management.attempts', $quiz->id) }}"
                                   class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    ดูผลลัพธ์
                                </a>
                            </div>
                        </div>

                        {{-- Questions Preview --}}
                        @if($quiz->questions->count() > 0)
                            <div class="mt-4 pt-4 border-t border-white/10">
                                <h4 class="text-sm font-semibold text-gray-300 mb-3">รายการคำถาม:</h4>
                                <div class="space-y-2">
                                    @foreach($quiz->questions->take(5) as $index => $question)
                                        <div class="flex items-start gap-3 p-3 bg-white/5 rounded-lg">
                                            <span class="w-6 h-6 bg-purple-500/30 text-purple-300 rounded-full flex items-center justify-center text-sm flex-shrink-0">
                                                {{ $index + 1 }}
                                            </span>
                                            <div class="flex-1">
                                                <p class="text-white">{{ Str::limit($question->question, 100) }}</p>
                                                <div class="flex items-center gap-2 mt-1 text-xs text-gray-400">
                                                    <span class="px-2 py-0.5 bg-white/10 rounded">{{ $question->question_type === 'multiple_choice' ? 'หลายตัวเลือก' : ($question->question_type === 'true_false' ? 'ถูก/ผิด' : $question->question_type) }}</span>
                                                    <span>{{ $question->points ?? 1 }} คะแนน</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                    @if($quiz->questions->count() > 5)
                                        <p class="text-center text-gray-400 text-sm py-2">
                                            และอีก {{ $quiz->questions->count() - 5 }} คำถาม...
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-12 text-center">
                <i class="fas fa-clipboard-question text-6xl text-gray-500 mb-6"></i>
                <h3 class="text-xl font-semibold text-white mb-2">ยังไม่มี Quiz</h3>
                <p class="text-gray-400 mb-6">สร้าง Quiz เพื่อทดสอบความเข้าใจของผู้เรียน</p>
                <a href="{{ route('admin.quiz-management.create') }}?article_id={{ $article->id }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-600 hover:from-yellow-600 hover:to-orange-700 text-white font-semibold rounded-xl transition">
                    <i class="fas fa-plus"></i>
                    สร้าง Quiz แรก
                </a>
            </div>
        @endif
    </div>

    {{-- Tips --}}
    <div class="bg-blue-500/10 border border-blue-500/20 rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-blue-300 mb-3">
            <i class="fas fa-lightbulb mr-2"></i>
            เคล็ดลับการสร้าง Quiz
        </h3>
        <ul class="space-y-2 text-blue-200 text-sm">
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-400 mt-1"></i>
                <span>สร้างคำถามที่ครอบคลุมเนื้อหาสำคัญของคอร์ส</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-400 mt-1"></i>
                <span>ตั้งคะแนนผ่านที่เหมาะสม (แนะนำ 70%)</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-400 mt-1"></i>
                <span>ใส่คำอธิบายเฉลยเพื่อช่วยให้ผู้เรียนเข้าใจ</span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fas fa-check text-blue-400 mt-1"></i>
                <span>หากตั้งค่า "ต้องผ่าน Quiz" ในคอร์ส ผู้เรียนต้องทำ Quiz ผ่านจึงจะนับว่าจบคอร์ส</span>
            </li>
        </ul>
    </div>
</div>
@endsection
