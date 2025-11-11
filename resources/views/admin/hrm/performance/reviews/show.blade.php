@extends('layouts.admin')

@section('title', 'รายละเอียดรีวิวประสิทธิภาพ')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-900 dark:via-purple-900 dark:to-pink-950 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.hrm.performance.reviews.index') }}"
               class="p-2 bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 transition-all duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div class="flex-1">
                <h2 class="text-3xl font-bold">รีวิวประสิทธิภาพ - {{ $review->employee->first_name }} {{ $review->employee->last_name }}</h2>
                <p class="text-purple-100 mt-1">{{ $review->review_period }} | {{ \Carbon\Carbon::parse($review->review_date)->format('d/m/Y') }}</p>
            </div>
            <a href="{{ route('admin.hrm.performance.reviews.edit', $review) }}"
               class="inline-flex items-center px-6 py-3 bg-white text-purple-600 font-semibold rounded-xl hover:bg-purple-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                แก้ไขรีวิว
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Overall Rating Card -->
            @if($review->overall_rating)
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl shadow-lg border-2 border-purple-200 dark:border-purple-800 p-8">
                    <div class="text-center">
                        <div class="text-sm font-semibold text-purple-600 dark:text-purple-400 mb-2">คะแนนรวม</div>
                        <div class="text-6xl font-bold text-purple-600 dark:text-purple-400 mb-4">
                            {{ number_format($review->overall_rating, 2) }}
                        </div>
                        <div class="flex items-center justify-center gap-2 mb-4">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($review->overall_rating))
                                    <svg class="w-8 h-8 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @elseif($i - 0.5 <= $review->overall_rating)
                                    <svg class="w-8 h-8 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <defs>
                                            <linearGradient id="half-{{ $i }}">
                                                <stop offset="50%" stop-color="currentColor"/>
                                                <stop offset="50%" stop-color="#D1D5DB"/>
                                            </linearGradient>
                                        </defs>
                                        <path fill="url(#half-{{ $i }})" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @else
                                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600 fill-current" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @endif
                            @endfor
                        </div>
                        @php
                            $performanceLevel = '';
                            $performanceColor = '';
                            if($review->overall_rating >= 4.5) {
                                $performanceLevel = 'ยอดเยี่ยม';
                                $performanceColor = 'text-green-600 dark:text-green-400';
                            } elseif($review->overall_rating >= 3.5) {
                                $performanceLevel = 'ดีมาก';
                                $performanceColor = 'text-blue-600 dark:text-blue-400';
                            } elseif($review->overall_rating >= 2.5) {
                                $performanceLevel = 'ดี';
                                $performanceColor = 'text-yellow-600 dark:text-yellow-400';
                            } else {
                                $performanceLevel = 'ต้องปรับปรุง';
                                $performanceColor = 'text-red-600 dark:text-red-400';
                            }
                        @endphp
                        <div class="text-xl font-bold {{ $performanceColor }}">{{ $performanceLevel }}</div>
                    </div>
                </div>
            @endif

            <!-- Rating Details -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        คะแนนประเมินรายหัวข้อ
                    </h3>
                </div>

                <div class="p-6 space-y-4">
                    @php
                        $ratings = [
                            'technical_skills_rating' => ['label' => 'ทักษะเทคนิค', 'icon' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                            'communication_rating' => ['label' => 'การสื่อสาร', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                            'teamwork_rating' => ['label' => 'การทำงานเป็นทีม', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                            'leadership_rating' => ['label' => 'ความเป็นผู้นำ', 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                            'problem_solving_rating' => ['label' => 'การแก้ปัญหา', 'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                            'productivity_rating' => ['label' => 'ผลผลิต', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                            'quality_of_work_rating' => ['label' => 'คุณภาพงาน', 'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z'],
                            'punctuality_rating' => ['label' => 'ความตรงต่อเวลา', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'initiative_rating' => ['label' => 'ความคิดริเริ่ม', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                            'adaptability_rating' => ['label' => 'การปรับตัว', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15']
                        ];
                    @endphp

                    @foreach($ratings as $field => $data)
                        @if($review->$field)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:shadow-md transition-shadow duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $data['icon'] }}"></path>
                                        </svg>
                                    </div>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $data['label'] }}</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $review->$field)
                                                <svg class="w-5 h-5 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600 fill-current" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $review->$field }}/5</span>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Evaluation Details -->
            @if($review->strengths || $review->areas_for_improvement || $review->achievements || $review->goals_for_next_period || $review->training_needs || $review->reviewer_comments)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            รายละเอียดการประเมิน
                        </h3>
                    </div>

                    <div class="p-6 space-y-6">
                        @if($review->strengths)
                            <div>
                                <h4 class="text-sm font-bold text-green-600 dark:text-green-400 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    จุดแข็ง
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $review->strengths }}</p>
                            </div>
                        @endif

                        @if($review->areas_for_improvement)
                            <div>
                                <h4 class="text-sm font-bold text-orange-600 dark:text-orange-400 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    จุดที่ควรพัฒนา
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $review->areas_for_improvement }}</p>
                            </div>
                        @endif

                        @if($review->achievements)
                            <div>
                                <h4 class="text-sm font-bold text-blue-600 dark:text-blue-400 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                    ผลงานที่โดดเด่น
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $review->achievements }}</p>
                            </div>
                        @endif

                        @if($review->goals_for_next_period)
                            <div>
                                <h4 class="text-sm font-bold text-purple-600 dark:text-purple-400 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    เป้าหมายสำหรับรอบถัดไป
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $review->goals_for_next_period }}</p>
                            </div>
                        @endif

                        @if($review->training_needs)
                            <div>
                                <h4 class="text-sm font-bold text-indigo-600 dark:text-indigo-400 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    ความต้องการอบรม
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $review->training_needs }}</p>
                            </div>
                        @endif

                        @if($review->reviewer_comments)
                            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                                <h4 class="text-sm font-bold text-purple-600 dark:text-purple-400 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                    </svg>
                                    ความคิดเห็นผู้ประเมิน
                                </h4>
                                <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $review->reviewer_comments }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Recommendations -->
            @if($review->recommend_promotion || $review->recommend_salary_increase)
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        คำแนะนำ
                    </h3>
                    <div class="space-y-3">
                        @if($review->recommend_promotion)
                            <div class="flex items-center text-green-700 dark:text-green-300">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-semibold">แนะนำให้เลื่อนตำแหน่ง</span>
                            </div>
                        @endif
                        @if($review->recommend_salary_increase)
                            <div class="flex items-center text-green-700 dark:text-green-300">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="font-semibold">
                                    แนะนำให้ปรับเงินเดือน
                                    @if($review->recommended_increase_percentage)
                                        <span class="ml-1">({{ $review->recommended_increase_percentage }}%)</span>
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Employee Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        พนักงาน
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">ชื่อ</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $review->employee->first_name }} {{ $review->employee->last_name }}</div>
                    </div>
                    @if($review->employee->email)
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">อีเมล</div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $review->employee->email }}</div>
                        </div>
                    @endif
                    @if($review->employee->position)
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">ตำแหน่ง</div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $review->employee->position }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Review Period -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        รอบการประเมิน
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">รอบ</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $review->review_period }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">ประเภท</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $review->review_type }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">วันที่ประเมิน</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($review->review_date)->format('d/m/Y') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">ช่วงเวลา</div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ \Carbon\Carbon::parse($review->period_start_date)->format('d/m/Y') }} -
                            {{ \Carbon\Carbon::parse($review->period_end_date)->format('d/m/Y') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        สถานะ
                    </h3>
                </div>
                <div class="p-4">
                    @php
                        $statusColors = [
                            'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                            'submitted' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
                        ];
                        $statusLabels = [
                            'draft' => 'แบบร่าง',
                            'completed' => 'เสร็จสมบูรณ์',
                            'submitted' => 'ส่งแล้ว'
                        ];
                    @endphp
                    <span class="inline-block px-4 py-2 text-sm font-bold rounded-lg {{ $statusColors[$review->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $statusLabels[$review->status] ?? $review->status }}
                    </span>
                </div>
            </div>

            <!-- Reviewer Info -->
            @if($review->reviewer)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            ผู้ประเมิน
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $review->reviewer->name }}</div>
                        @if($review->reviewer->email)
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $review->reviewer->email }}</div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Timestamps -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-4">
                <div class="space-y-2 text-sm">
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>สร้างเมื่อ: {{ $review->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>อัปเดต: {{ $review->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-3">
                <a href="{{ route('admin.hrm.performance.reviews.edit', $review) }}"
                   class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    แก้ไขรีวิว
                </a>

                <form action="{{ route('admin.hrm.performance.reviews.destroy', $review) }}" method="POST"
                      onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบรีวิวนี้?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        ลบรีวิว
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
