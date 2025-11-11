@extends('layouts.admin')

@section('title', 'เป้าหมายประสิทธิภาพ')

@section('content')
<div class="space-y-6">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-6 py-4 rounded-xl shadow-lg animate-fade-in-down" role="alert">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-2 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-6 py-4 rounded-xl shadow-lg animate-fade-in-down" role="alert">
            <div class="flex items-center">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Header with Actions -->
    <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-700 dark:from-purple-900 dark:via-indigo-900 dark:to-purple-950 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-3">
                    <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-3xl font-bold">เป้าหมายประสิทธิภาพ</h2>
                        <p class="text-purple-100 mt-1">จัดการและติดตามเป้าหมายของพนักงาน</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-4 mt-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2">
                        <div class="text-sm text-purple-100">เป้าหมายทั้งหมด</div>
                        <div class="text-2xl font-bold">{{ $goals->total() }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2">
                        <div class="text-sm text-purple-100">กำลังดำเนินการ</div>
                        <div class="text-2xl font-bold">{{ $goals->where('status', 'in_progress')->count() }}</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg px-4 py-2">
                        <div class="text-sm text-purple-100">บรรลุแล้ว</div>
                        <div class="text-2xl font-bold">{{ $goals->where('status', 'achieved')->count() }}</div>
                    </div>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.hrm.performance.reviews.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-white/20 backdrop-blur-sm text-white font-semibold rounded-xl hover:bg-white/30 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    รีวิวประสิทธิภาพ
                </a>
                <a href="{{ route('admin.hrm.performance.goals.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-white text-purple-600 font-semibold rounded-xl hover:bg-purple-50 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    สร้างเป้าหมายใหม่
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
        <form action="{{ route('admin.hrm.performance.goals.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">ค้นหา</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="ค้นหาชื่อเป้าหมาย, คำอธิบาย, หรือพนักงาน..."
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">สถานะ</label>
                    <select name="status" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <option value="">ทั้งหมด</option>
                        <option value="not_started" {{ request('status') == 'not_started' ? 'selected' : '' }}>ยังไม่เริ่ม</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>กำลังดำเนินการ</option>
                        <option value="achieved" {{ request('status') == 'achieved' ? 'selected' : '' }}>บรรลุแล้ว</option>
                        <option value="partially_achieved" {{ request('status') == 'partially_achieved' ? 'selected' : '' }}>บรรลุบางส่วน</option>
                        <option value="not_achieved" {{ request('status') == 'not_achieved' ? 'selected' : '' }}>ไม่บรรลุ</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ยกเลิก</option>
                    </select>
                </div>

                <!-- Employee Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">พนักงาน</label>
                    <select name="employee_id" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <option value="">ทั้งหมด</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                {{ $employee->first_name }} {{ $employee->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    กรอง
                </button>
                <a href="{{ route('admin.hrm.performance.goals.index') }}" class="inline-flex items-center px-6 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    รีเซ็ต
                </a>
            </div>
        </form>
    </div>

    <!-- Goals Grid -->
    @if($goals->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($goals as $goal)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-200 dark:border-gray-700 overflow-hidden group">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/30 dark:to-indigo-900/30 p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors duration-200">
                                    {{ $goal->goal_title }}
                                </h3>
                                <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span>{{ $goal->employee->first_name }} {{ $goal->employee->last_name }}</span>
                                </div>
                            </div>

                            <!-- Priority Badge -->
                            @php
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
                            <span class="px-3 py-1 text-xs font-bold rounded-full {{ $priorityColors[$goal->priority] }}">
                                {{ $priorityLabels[$goal->priority] }}
                            </span>
                        </div>

                        <!-- Status Badge -->
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
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-3 py-1 text-xs font-bold rounded-full {{ $statusColors[$goal->status] }}">
                                @if($goal->status == 'achieved')
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                                {{ $statusLabels[$goal->status] }}
                            </span>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-4">
                        <!-- Progress Bar -->
                        @php
                            $progress = $goal->progress_percentage ?? 0;
                        @endphp
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">ความคืบหน้า</span>
                                <span class="text-sm font-bold text-purple-600 dark:text-purple-400">{{ number_format($progress, 0) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                                <div class="bg-gradient-to-r from-purple-500 to-indigo-500 h-3 rounded-full transition-all duration-500 ease-out relative overflow-hidden"
                                     style="width: {{ $progress }}%">
                                    <div class="absolute inset-0 bg-white/30 animate-pulse"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Goal Info -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    วันเริ่มต้น
                                </div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($goal->start_date)->format('d/m/Y') }}
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    วันเป้าหมาย
                                </div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($goal->target_date)->format('d/m/Y') }}
                                </div>
                            </div>
                        </div>

                        <!-- Category & Type Tags -->
                        <div class="flex flex-wrap gap-2">
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
                            <span class="px-3 py-1 text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 rounded-full">
                                {{ $categoryLabels[$goal->category] ?? $goal->category }}
                            </span>
                            <span class="px-3 py-1 text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300 rounded-full">
                                {{ $typeLabels[$goal->goal_type] ?? $goal->goal_type }}
                            </span>
                            @if($goal->weight_percentage)
                                <span class="px-3 py-1 text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 rounded-full">
                                    น้ำหนัก {{ $goal->weight_percentage }}%
                                </span>
                            @endif
                        </div>

                        <!-- Achievement Badge -->
                        @if($goal->status == 'achieved')
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 border-2 border-green-200 dark:border-green-800 rounded-lg p-3 flex items-center">
                                <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mr-3">
                                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-green-800 dark:text-green-300">บรรลุเป้าหมายแล้ว!</div>
                                    <div class="text-xs text-green-600 dark:text-green-400">ยินดีด้วย คุณทำได้ดีมาก</div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Footer Actions -->
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex justify-between items-center border-t border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            อัปเดต: {{ $goal->updated_at->diffForHumans() }}
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.hrm.performance.goals.show', $goal) }}"
                               class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg transition-all duration-200 shadow hover:shadow-lg transform hover:-translate-y-0.5">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                ดูรายละเอียด
                            </a>
                            <a href="{{ route('admin.hrm.performance.goals.edit', $goal) }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-all duration-200 shadow hover:shadow-lg transform hover:-translate-y-0.5">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                แก้ไข
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $goals->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-12 text-center border border-gray-200 dark:border-gray-700">
            <div class="max-w-md mx-auto">
                <div class="w-24 h-24 bg-gradient-to-br from-purple-100 to-indigo-100 dark:from-purple-900/30 dark:to-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">ยังไม่มีเป้าหมาย</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">เริ่มต้นสร้างเป้าหมายประสิทธิภาพให้กับพนักงานของคุณ</p>
                <a href="{{ route('admin.hrm.performance.goals.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    สร้างเป้าหมายแรก
                </a>
            </div>
        </div>
    @endif
</div>

<style>
@keyframes fade-in-down {
    0% {
        opacity: 0;
        transform: translateY(-10px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-down {
    animation: fade-in-down 0.5s ease-out;
}
</style>
@endsection
