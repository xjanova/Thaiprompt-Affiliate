@extends('layouts.admin-v3')

@section('title', 'รายละเอียดเป้าหมาย')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-700 dark:from-purple-900 dark:via-indigo-900 dark:to-purple-950 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.hrm.performance.goals.index') }}"
               class="p-2 bg-white/20 backdrop-blur-sm rounded-lg hover:bg-white/30 transition-all duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div class="flex-1">
                <h2 class="text-3xl font-bold">{{ $goal->goal_title }}</h2>
                <p class="text-purple-100 mt-1">รายละเอียดเป้าหมายประสิทธิภาพ</p>
            </div>
            <a href="{{ route('admin.hrm.performance.goals.edit', $goal) }}"
               class="inline-flex items-center px-6 py-3 bg-white text-purple-600 font-semibold rounded-xl hover:bg-purple-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                แก้ไขเป้าหมาย
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Goal Overview -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Status Header -->
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        @php
                            $statusColors = [
                                'not_started' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                'achieved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                'partially_achieved' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                'not_achieved' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            ];
                            $statusLabels = [
                                'not_started' => 'ยังไม่เริ่ม',
                                'in_progress' => 'กำลังดำเนินการ',
                                'achieved' => 'บรรลุแล้ว',
                                'partially_achieved' => 'บรรลุบางส่วน',
                                'not_achieved' => 'ไม่บรรลุ',
                                'cancelled' => 'ยกเลิก'
                            ];
                            $priorityColors = [
                                'low' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                            ];
                            $priorityLabels = [
                                'low' => 'ต่ำ',
                                'medium' => 'ปานกลาง',
                                'high' => 'สูง',
                                'critical' => 'วิกฤต'
                            ];
                        @endphp
                        <span class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-full {{ $statusColors[$goal->status] }}">
                            @if($goal->status == 'achieved')
                                <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                            {{ $statusLabels[$goal->status] }}
                        </span>
                        <span class="px-4 py-2 text-sm font-bold rounded-full {{ $priorityColors[$goal->priority] }}">
                            ระดับความสำคัญ: {{ $priorityLabels[$goal->priority] }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    @php
                        $progress = $goal->progress_percentage ?? 0;
                    @endphp
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">ความคืบหน้า</span>
                            <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($progress, 0) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                            <div class="bg-gradient-to-r from-purple-500 via-indigo-500 to-purple-600 h-4 rounded-full transition-all duration-500 ease-out relative overflow-hidden shadow-lg"
                                 style="width: {{ $progress }}%">
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        คำอธิบายเป้าหมาย
                    </h3>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $goal->goal_description }}</p>
                </div>

                @if($goal->measurement_criteria)
                    <div class="px-6 pb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            เกณฑ์การวัด
                        </h3>
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $goal->measurement_criteria }}</p>
                    </div>
                @endif

                @if($goal->notes)
                    <div class="px-6 pb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                            หมายเหตุ
                        </h3>
                        <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ $goal->notes }}</p>
                    </div>
                @endif
            </div>

            <!-- Achievement Badge -->
            @if($goal->status == 'achieved')
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-xl shadow-lg p-8">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-20 h-20 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mr-6 shadow-xl animate-pulse">
                            <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-green-800 dark:text-green-300 mb-2">บรรลุเป้าหมายแล้ว!</h3>
                            <p class="text-green-600 dark:text-green-400">ยินดีด้วย! คุณทำได้อย่างยอดเยี่ยม ผลงานนี้แสดงให้เห็นถึงความมุ่งมั่นและความสามารถของคุณ</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Measurement Values -->
            @if($goal->target_value || $goal->current_value)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        ค่าการวัดผล
                    </h3>
                    <div class="grid grid-cols-2 gap-6">
                        @if($goal->target_value)
                            <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ค่าเป้าหมาย</div>
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $goal->target_value }}</div>
                            </div>
                        @endif
                        @if($goal->current_value)
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">ค่าปัจจุบัน</div>
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $goal->current_value }}</div>
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
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $goal->employee->first_name }} {{ $goal->employee->last_name }}</div>
                    </div>
                    @if($goal->employee->email)
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">อีเมล</div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $goal->employee->email }}</div>
                        </div>
                    @endif
                    @if($goal->employee->position)
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">ตำแหน่ง</div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $goal->employee->position }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        ไทม์ไลน์
                    </h3>
                </div>
                <div class="p-4 space-y-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">วันเริ่มต้น</div>
                            <div class="font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($goal->start_date)->format('d/m/Y') }}
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ \Carbon\Carbon::parse($goal->start_date)->diffForHumans() }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">วันเป้าหมาย</div>
                            <div class="font-semibold text-gray-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($goal->target_date)->format('d/m/Y') }}
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ \Carbon\Carbon::parse($goal->target_date)->diffForHumans() }})
                                </span>
                            </div>
                        </div>
                    </div>

                    @php
                        $totalDays = \Carbon\Carbon::parse($goal->start_date)->diffInDays(\Carbon\Carbon::parse($goal->target_date));
                        $daysElapsed = \Carbon\Carbon::parse($goal->start_date)->diffInDays(now());
                        $timeProgress = min(100, ($totalDays > 0) ? ($daysElapsed / $totalDays) * 100 : 0);
                    @endphp

                    <div class="pt-2">
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                            <span>ใช้เวลาไปแล้ว</span>
                            <span>{{ number_format($timeProgress, 0) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-2 rounded-full transition-all duration-500"
                                 style="width: {{ $timeProgress }}%"></div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $daysElapsed }} / {{ $totalDays }} วัน
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category & Type -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        หมวดหมู่
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    @php
                        $categoryLabels = [
                            'productivity' => 'ผลผลิต',
                            'quality' => 'คุณภาพ',
                            'learning' => 'การเรียนรู้',
                            'behavior' => 'พฤติกรรม',
                            'project' => 'โครงการ',
                            'other' => 'อื่นๆ'
                        ];
                        $typeLabels = [
                            'individual' => 'บุคคล',
                            'team' => 'ทีม',
                            'departmental' => 'แผนก',
                            'organizational' => 'องค์กร'
                        ];
                    @endphp
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">ประเภท</div>
                        <span class="inline-block px-4 py-2 bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300 rounded-lg font-semibold">
                            {{ $typeLabels[$goal->goal_type] ?? $goal->goal_type }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">หมวดหมู่</div>
                        <span class="inline-block px-4 py-2 bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 rounded-lg font-semibold">
                            {{ $categoryLabels[$goal->category] ?? $goal->category }}
                        </span>
                    </div>
                    @if($goal->weight_percentage)
                        <div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">น้ำหนักเป้าหมาย</div>
                            <span class="inline-block px-4 py-2 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 rounded-lg font-semibold">
                                {{ $goal->weight_percentage }}%
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Assigned By -->
            @if($goal->assignedBy)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            มอบหมายโดย
                        </h3>
                    </div>
                    <div class="p-4">
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $goal->assignedBy->name }}</div>
                        @if($goal->assignedBy->email)
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $goal->assignedBy->email }}</div>
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
                        <span>สร้างเมื่อ: {{ $goal->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex items-center text-gray-600 dark:text-gray-400">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>อัปเดต: {{ $goal->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="space-y-3">
                <a href="{{ route('admin.hrm.performance.goals.edit', $goal) }}"
                   class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    แก้ไขเป้าหมาย
                </a>

                <form action="{{ route('admin.hrm.performance.goals.destroy', $goal) }}" method="POST"
                      onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบเป้าหมายนี้?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        ลบเป้าหมาย
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.animate-shimmer {
    animation: shimmer 2s infinite;
}
</style>
@endsection
