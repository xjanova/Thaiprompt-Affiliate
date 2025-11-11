@extends('layouts.admin')

@section('title', 'จัดการใบสมัครงาน')

@section('content')
<div class="space-y-6">
    <!-- Header with Gradient -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                    <i class="fas fa-file-alt"></i>
                    จัดการใบสมัครงาน
                </h1>
                <p class="text-purple-100">ตรวจสอบและจัดการผู้สมัครงานทั้งหมด</p>
            </div>
            <a href="{{ route('admin.hrm.recruitment.applications.create') }}"
               class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg transition-all duration-200 transform hover:scale-105 flex items-center gap-2">
                <i class="fas fa-plus"></i>
                <span>เพิ่มใบสมัคร</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total Applications -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">ใบสมัครทั้งหมด</p>
                    <p class="text-3xl font-bold">{{ $applications->total() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-file-alt text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- New Applications -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">ใบสมัครใหม่</p>
                    <p class="text-3xl font-bold">{{ $applications->where('application_status', 'new')->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-star text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Under Review -->
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm mb-1">กำลังพิจารณา</p>
                    <p class="text-3xl font-bold">{{ $applications->where('application_status', 'under_review')->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-search text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Shortlisted -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">ผ่านเข้ารอบ</p>
                    <p class="text-3xl font-bold">{{ $applications->where('application_status', 'shortlisted')->count() }}</p>
                </div>
                <div class="bg-white/20 rounded-full p-4">
                    <i class="fas fa-check-circle text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Hired -->
        <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-all duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-pink-100 text-sm mb-1">จ้างแล้ว</p>
                    <p class="text-3xl font-bold">{{ $applications->where('application_status', 'hired')->count() }}</p>
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
                           placeholder="ค้นหาชื่อ, อีเมล, เบอร์โทร..."
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
                        <option value="new" {{ request('status') == 'new' ? 'selected' : '' }}>ใหม่</option>
                        <option value="under_review" {{ request('status') == 'under_review' ? 'selected' : '' }}>กำลังพิจารณา</option>
                        <option value="shortlisted" {{ request('status') == 'shortlisted' ? 'selected' : '' }}>ผ่านเข้ารอบ</option>
                        <option value="interview_scheduled" {{ request('status') == 'interview_scheduled' ? 'selected' : '' }}>นัดสัมภาษณ์</option>
                        <option value="interviewed" {{ request('status') == 'interviewed' ? 'selected' : '' }}>สัมภาษณ์แล้ว</option>
                        <option value="offer_extended" {{ request('status') == 'offer_extended' ? 'selected' : '' }}>เสนอตำแหน่ง</option>
                        <option value="hired" {{ request('status') == 'hired' ? 'selected' : '' }}>จ้างแล้ว</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>ไม่ผ่าน</option>
                        <option value="withdrawn" {{ request('status') == 'withdrawn' ? 'selected' : '' }}>ถอนตัว</option>
                    </select>
                </div>

                <!-- Job Posting Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-briefcase"></i> ตำแหน่งงาน
                    </label>
                    <select name="job_posting_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 dark:bg-gray-700 dark:text-white transition-all duration-200">
                        <option value="">ทุกตำแหน่ง</option>
                        @foreach($jobPostings as $job)
                            <option value="{{ $job->id }}" {{ request('job_posting_id') == $job->id ? 'selected' : '' }}>
                                {{ $job->job_title }}
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
                <a href="{{ route('admin.hrm.recruitment.applications.index') }}"
                   class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-all duration-200 flex items-center gap-2">
                    <i class="fas fa-redo"></i>
                    ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    <!-- Applications Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden transition-all duration-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-700 dark:to-gray-700">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-user"></i> ผู้สมัคร
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-briefcase"></i> ตำแหน่งที่สมัคร
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-envelope"></i> ติดต่อ
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            <i class="fas fa-calendar"></i> วันที่สมัคร
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
                    @forelse($applications as $application)
                    <tr class="hover:bg-purple-50 dark:hover:bg-gray-700 transition-all duration-200">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-400 to-indigo-400 flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($application->first_name, 0, 1)) }}{{ strtoupper(substr($application->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $application->first_name }} {{ $application->last_name }}
                                    </div>
                                    @if($application->expected_salary)
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-money-bill-wave"></i> ฿{{ number_format($application->expected_salary) }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $application->jobPosting->job_title ?? 'N/A' }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $application->jobPosting->job_code ?? '' }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                <div><i class="fas fa-envelope text-purple-500"></i> {{ $application->email }}</div>
                                <div><i class="fas fa-phone text-green-500"></i> {{ $application->phone }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                {{ $application->application_date ? \Carbon\Carbon::parse($application->application_date)->format('d/m/Y') : 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusConfig = [
                                    'new' => ['class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300', 'icon' => 'fa-star', 'label' => 'ใหม่'],
                                    'under_review' => ['class' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300', 'icon' => 'fa-search', 'label' => 'กำลังพิจารณา'],
                                    'shortlisted' => ['class' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300', 'icon' => 'fa-check', 'label' => 'ผ่านเข้ารอบ'],
                                    'interview_scheduled' => ['class' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300', 'icon' => 'fa-calendar-check', 'label' => 'นัดสัมภาษณ์'],
                                    'interviewed' => ['class' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300', 'icon' => 'fa-user-check', 'label' => 'สัมภาษณ์แล้ว'],
                                    'offer_extended' => ['class' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300', 'icon' => 'fa-hand-holding-heart', 'label' => 'เสนอตำแหน่ง'],
                                    'hired' => ['class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300', 'icon' => 'fa-user-tie', 'label' => 'จ้างแล้ว'],
                                    'rejected' => ['class' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300', 'icon' => 'fa-times', 'label' => 'ไม่ผ่าน'],
                                    'withdrawn' => ['class' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', 'icon' => 'fa-sign-out-alt', 'label' => 'ถอนตัว'],
                                ];
                                $config = $statusConfig[$application->application_status] ?? $statusConfig['new'];
                            @endphp
                            <span class="px-3 py-1 inline-flex items-center gap-1 text-xs leading-5 font-semibold rounded-full {{ $config['class'] }}">
                                <i class="fas {{ $config['icon'] }}"></i>
                                {{ $config['label'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.hrm.recruitment.applications.show', $application) }}"
                                   class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-all duration-200 transform hover:scale-110"
                                   title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.hrm.recruitment.applications.edit', $application) }}"
                                   class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 transition-all duration-200 transform hover:scale-110"
                                   title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($application->resume_path)
                                    <a href="{{ asset('storage/' . $application->resume_path) }}"
                                       target="_blank"
                                       class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300 transition-all duration-200 transform hover:scale-110"
                                       title="ดาวน์โหลด Resume">
                                        <i class="fas fa-download"></i>
                                    </a>
                                @endif
                                <form action="{{ route('admin.hrm.recruitment.applications.destroy', $application) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบใบสมัครนี้?')">
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
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <i class="fas fa-inbox text-6xl text-gray-300 dark:text-gray-600"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-lg">ไม่พบข้อมูลใบสมัครงาน</p>
                                <a href="{{ route('admin.hrm.recruitment.applications.create') }}"
                                   class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all duration-200">
                                    <i class="fas fa-plus"></i> เพิ่มใบสมัครใหม่
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($applications->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            {{ $applications->links() }}
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
