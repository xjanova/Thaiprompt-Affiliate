@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="{{ route('admin.learning-center.article', $article->slug) }}"
                   class="p-2 bg-white dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200">
                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div class="flex-1">
                    <h1 class="text-4xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                        <svg class="w-10 h-10 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/>
                        </svg>
                        แบบทดสอบ
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                        </svg>
                        {{ $article->title }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 p-4 rounded-lg animate-fade-in">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="text-green-700 dark:text-green-400 font-medium">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-lg animate-fade-in">
            <div class="flex items-center">
                <svg class="w-6 h-6 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="text-red-700 dark:text-red-400 font-medium">{{ session('error') }}</p>
            </div>
        </div>
        @endif

        <!-- Quizzes List -->
        @if($quizzes->count() > 0)
        <div class="space-y-6">
            @foreach($quizzes as $quizData)
                @php
                    $quiz = $quizData['quiz'];
                    $bestScore = $quizData['best_score'];
                    $attempts = $quizData['attempts'];
                    $hasAttemptsLeft = $quizData['has_attempts_left'];
                    $passed = $quizData['passed'];
                @endphp

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden transform hover:scale-[1.02] transition-all duration-300">
                    <div class="md:flex">
                        <!-- Left Section - Quiz Info -->
                        <div class="md:w-2/3 p-8">
                            <!-- Quiz Header -->
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                        {{ $quiz->title }}
                                    </h2>
                                    @if($quiz->description)
                                    <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $quiz->description }}</p>
                                    @endif
                                </div>

                                <!-- Status Badge -->
                                @if($passed)
                                <div class="ml-4">
                                    <div class="px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <span class="font-bold">ผ่านแล้ว</span>
                                    </div>
                                </div>
                                @endif
                            </div>

                            <!-- Quiz Details Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <!-- Questions Count -->
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold">คำถาม</span>
                                    </div>
                                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ $quiz->questions_count }}</p>
                                </div>

                                <!-- Time Limit -->
                                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-100 dark:border-purple-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-xs text-purple-600 dark:text-purple-400 font-semibold">เวลา</span>
                                    </div>
                                    <p class="text-lg font-bold text-purple-700 dark:text-purple-300">
                                        {{ $quiz->time_limit ? $quiz->time_limit . ' นาที' : 'ไม่จำกัด' }}
                                    </p>
                                </div>

                                <!-- Passing Score -->
                                <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-100 dark:border-green-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-xs text-green-600 dark:text-green-400 font-semibold">คะแนนผ่าน</span>
                                    </div>
                                    <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ $quiz->passing_score }}%</p>
                                </div>

                                <!-- Attempts -->
                                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 border border-orange-100 dark:border-orange-800">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-xs text-orange-600 dark:text-orange-400 font-semibold">ความพยายาม</span>
                                    </div>
                                    <p class="text-lg font-bold text-orange-700 dark:text-orange-300">
                                        {{ $attempts }}{{ $quiz->max_attempts ? ' / ' . $quiz->max_attempts : '' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Additional Info Badges -->
                            <div class="flex flex-wrap gap-2">
                                @if($quiz->is_required)
                                <span class="inline-flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-sm font-semibold rounded-full">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                                    </svg>
                                    บังคับทำ
                                </span>
                                @endif

                                @if($quiz->randomize_questions)
                                <span class="inline-flex items-center px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 text-sm font-semibold rounded-full">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 3a1 1 0 000 2h11a1 1 0 100-2H3zM3 7a1 1 0 000 2h7a1 1 0 100-2H3zM3 11a1 1 0 100 2h4a1 1 0 100-2H3zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z"/>
                                    </svg>
                                    สุ่มคำถาม
                                </span>
                                @endif
                            </div>
                        </div>

                        <!-- Right Section - Score & Actions -->
                        <div class="md:w-1/3 bg-gradient-to-br from-indigo-600 to-purple-600 p-8 text-white flex flex-col justify-between">
                            <!-- Best Score -->
                            @if($attempts > 0)
                            <div class="mb-6">
                                <p class="text-indigo-100 text-sm font-semibold mb-3">คะแนนที่ดีที่สุดของคุณ</p>
                                <div class="relative">
                                    <div class="flex items-center justify-center">
                                        <div class="relative">
                                            <!-- Circular Progress -->
                                            <svg class="w-32 h-32 transform -rotate-90">
                                                <circle cx="64" cy="64" r="56" stroke="rgba(255,255,255,0.2)" stroke-width="8" fill="none"/>
                                                <circle cx="64" cy="64" r="56" stroke="white" stroke-width="8" fill="none"
                                                        stroke-dasharray="351.86"
                                                        stroke-dashoffset="{{ 351.86 - (351.86 * $bestScore / 100) }}"
                                                        stroke-linecap="round"
                                                        class="transition-all duration-1000"/>
                                            </svg>
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <div class="text-center">
                                                    <p class="text-4xl font-bold">{{ number_format($bestScore, 0) }}%</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Text -->
                                    <div class="text-center mt-4">
                                        @if($passed)
                                        <p class="text-green-200 font-bold flex items-center justify-center gap-2">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            ผ่านเกณฑ์แล้ว
                                        </p>
                                        @else
                                        <p class="text-yellow-200 font-bold">ยังไม่ผ่านเกณฑ์</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @else
                            <div class="mb-6 text-center">
                                <div class="w-32 h-32 mx-auto mb-4 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm">
                                    <svg class="w-16 h-16 text-white/60" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <p class="text-indigo-100 text-lg font-semibold">ยังไม่เคยทำแบบทดสอบนี้</p>
                                <p class="text-indigo-200 text-sm mt-2">เริ่มทำแบบทดสอบเพื่อดูคะแนนของคุณ</p>
                            </div>
                            @endif

                            <!-- Action Button -->
                            @if($hasAttemptsLeft)
                            <a href="{{ route('admin.quiz.show', $quiz->id) }}"
                               class="w-full inline-flex items-center justify-center px-6 py-4 bg-white hover:bg-gray-100 text-indigo-600 font-bold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                                @if($attempts > 0)
                                    ทำอีกครั้ง
                                @else
                                    เริ่มทำแบบทดสอบ
                                @endif
                            </a>
                            @else
                            <div class="w-full px-6 py-4 bg-white/10 text-white text-center font-bold rounded-xl backdrop-blur-sm">
                                <svg class="w-6 h-6 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/>
                                </svg>
                                ใช้ครั้งหมดแล้ว
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center border border-gray-100 dark:border-gray-700">
            <svg class="w-24 h-24 mx-auto text-gray-400 dark:text-gray-600 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">ยังไม่มีแบบทดสอบ</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">บทความนี้ยังไม่มีแบบทดสอบที่เปิดใช้งาน</p>
            <a href="{{ route('admin.learning-center.article', $article->slug) }}"
               class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปที่บทความ
            </a>
        </div>
        @endif
    </div>
</div>

<style>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}

/* Circular progress animation */
circle {
    transition: stroke-dashoffset 1s ease-in-out;
}
</style>
@endsection
