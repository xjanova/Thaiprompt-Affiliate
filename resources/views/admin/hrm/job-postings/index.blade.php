@extends('layouts.admin')

@section('title', 'จัดการตำแหน่งงาน')

@section('content')
<div class="space-y-6">
    <!-- Header with Gradient -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                    <i class="fas fa-briefcase"></i>
                    จัดการตำแหน่งงาน
                </h1>
                <p class="text-purple-100">จัดการประกาศรับสมัครงานและตำแหน่งที่เปิดรับ</p>
            </div>
            <a href="{{ route('admin.hrm.recruitment.jobs.create') }}"
               class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-200 transform hover:scale-105 flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>เพิ่มตำแหน่งงาน</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Jobs -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">ตำแหน่งทั้งหมด</p>
                    <p class="text-3xl font-bold">{{ $jobPostings->total() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-briefcase text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Active Jobs -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">เปิดรับสมัคร</p>
                    <p class="text-3xl font-bold">{{ $jobPostings->where('status', 'active')->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Draft Jobs -->
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm mb-1">ฉบับร่าง</p>
                    <p class="text-3xl font-bold">{{ $jobPostings->where('status', 'draft')->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-file-alt text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Filled Jobs -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">จ้างแล้ว</p>
                    <p class="text-3xl font-bold">{{ $jobPostings->where('status', 'filled')->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-user-check text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 transition-all duration-200">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-search"></i> ค้นหา
                    </label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="ค้นหาตำแหน่งงาน, รหัส, คำอธิบาย..."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                </div>

                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-filter"></i> สถานะ
                    </label>
                    <select name="status"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <option value="">ทั้งหมด</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>ฉบับร่าง</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>เปิดรับสมัคร</option>
                        <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>พักการรับสมัคร</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>ปิดรับสมัคร</option>
                        <option value="filled" {{ request('status') == 'filled' ? 'selected' : '' }}>จ้างแล้ว</option>
                    </select>
                </div>

                <!-- Department Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-building"></i> แผนก
                    </label>
                    <select name="department_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <option value="">ทุกแผนก</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button type="submit"
                        class="px-6 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200 transform hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-search"></i>
                    ค้นหา
                </button>
                <a href="{{ route('admin.hrm.recruitment.jobs.index') }}"
                   class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-redo"></i>
                    ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    <!-- Job Postings Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden transition-all duration-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-700 dark:to-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-hashtag"></i> รหัส
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-briefcase"></i> ตำแหน่ง
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-building"></i> แผนก
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-map-marker-alt"></i> สถานที่
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-users"></i> จำนวนที่รับ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-calendar"></i> ปิดรับสมัคร
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-info-circle"></i> สถานะ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-cog"></i> การกระทำ
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($jobPostings as $job)
                    <tr class="hover:bg-purple-50 dark:hover:bg-gray-700 transition-all duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $job->job_code }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $job->job_title }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-clock"></i> {{ $job->employment_type }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $job->department->name ?? '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <i class="fas fa-map-marker-alt text-red-500"></i> {{ $job->job_location }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $job->number_of_openings }} ตำแหน่ง
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($job->application_deadline)
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ \Carbon\Carbon::parse($job->application_deadline)->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusConfig = [
                                    'draft' => ['class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', 'icon' => 'fa-file-alt', 'label' => 'ฉบับร่าง'],
                                    'active' => ['class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', 'icon' => 'fa-check-circle', 'label' => 'เปิดรับสมัคร'],
                                    'on_hold' => ['class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', 'icon' => 'fa-pause-circle', 'label' => 'พักการรับ'],
                                    'closed' => ['class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', 'icon' => 'fa-times-circle', 'label' => 'ปิดรับ'],
                                    'filled' => ['class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300', 'icon' => 'fa-user-check', 'label' => 'จ้างแล้ว'],
                                ];
                                $config = $statusConfig[$job->status] ?? $statusConfig['draft'];
                            @endphp
                            <span class="px-3 py-1 inline-flex items-center gap-1 text-xs leading-5 font-semibold rounded-full {{ $config['class'] }}">
                                <i class="fas {{ $config['icon'] }}"></i>
                                {{ $config['label'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.hrm.recruitment.jobs.show', $job) }}"
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-all duration-200 transform hover:scale-110"
                                   title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.hrm.recruitment.jobs.edit', $job) }}"
                                   class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 transition-all duration-200 transform hover:scale-110"
                                   title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.hrm.recruitment.jobs.destroy', $job) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบตำแหน่งงานนี้?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-all duration-200 transform hover:scale-110"
                                            title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fas fa-inbox text-6xl text-gray-300 dark:text-gray-600"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่พบข้อมูลตำแหน่งงาน</p>
                                <a href="{{ route('admin.hrm.recruitment.jobs.create') }}"
                                   class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200">
                                    <i class="fas fa-plus"></i> เพิ่มตำแหน่งงานใหม่
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($jobPostings->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            {{ $jobPostings->links() }}
        </div>
        @endif
    </div>
</div>

@if(session('success'))
<div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center gap-2 animate-bounce">
    <i class="fas fa-check-circle"></i>
    {{ session('success') }}
</div>
@endif
@endsection
