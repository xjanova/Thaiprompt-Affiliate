{{-- รายงานการเรียนรู้ --}}
@extends('layouts.admin-v3')
@section('title', $pageTitle ?? 'รายงานการเรียนรู้')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <nav class="flex mb-3 text-sm">
                    <a href="{{ route('admin.unified-reports.index') }}" class="text-gray-500 hover:text-indigo-600 dark:text-gray-400">ศูนย์รายงาน</a>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 dark:text-white font-medium">การเรียนรู้</span>
                </nav>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                        </svg>
                    </div>
                    {{ $pageTitle }}
                </h1>
            </div>
            <a href="{{ route('admin.unified-reports.export', ['type' => 'learning', 'period' => $period]) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </a>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">หลักสูตรทั้งหมด</p>
                    <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($report['summary']['total_courses'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">หลักสูตรที่เปิดสอน</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">การลงทะเบียน</p>
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ number_format($report['summary']['total_enrollments'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ในช่วงเวลานี้</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">เรียนจบ</p>
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400 mt-2">{{ number_format($report['summary']['completions'] ?? 0) }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ผู้เรียนที่สำเร็จ</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">อัตราสำเร็จ</p>
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-2">{{ $report['summary']['completion_rate'] ?? 0 }}%</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">เฉลี่ยทั้งหมด</p>
            </div>
        </div>

        {{-- Details --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Popular Courses --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    หลักสูตรยอดนิยม
                </h3>
                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @forelse(($report['courses'] ?? []) as $index => $course)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 flex items-center justify-center text-sm font-bold rounded-full
                                {{ $index < 3 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-gray-200 text-gray-600 dark:bg-gray-600 dark:text-gray-300' }}">
                                {{ $index + 1 }}
                            </span>
                            <span class="text-gray-700 dark:text-gray-300 truncate max-w-[200px]">{{ $course['title'] ?? 'N/A' }}</span>
                        </div>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($course['enrollments_count'] ?? 0) }} คน</span>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">ไม่มีข้อมูลหลักสูตร</p>
                    @endforelse
                </div>
            </div>

            {{-- Completion Rate by Course --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    อัตราสำเร็จตามหลักสูตร
                </h3>
                <div class="space-y-4 max-h-80 overflow-y-auto">
                    @forelse(($report['completion_rate'] ?? []) as $course)
                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-700 dark:text-gray-300 text-sm truncate max-w-[200px]">{{ $course->title ?? 'N/A' }}</span>
                            <span class="text-sm font-semibold {{ ($course->completion_rate ?? 0) >= 70 ? 'text-green-600' : (($course->completion_rate ?? 0) >= 40 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $course->completion_rate ?? 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div class="h-2 rounded-full {{ ($course->completion_rate ?? 0) >= 70 ? 'bg-green-500' : (($course->completion_rate ?? 0) >= 40 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                 style="width: {{ $course->completion_rate ?? 0 }}%"></div>
                        </div>
                        <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $course->completions ?? 0 }} สำเร็จ</span>
                            <span>จาก {{ $course->total_enrollments ?? 0 }} คน</span>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">ไม่มีข้อมูล</p>
                    @endforelse
                </div>
            </div>

            {{-- Enrollment Trends --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-gray-700 lg:col-span-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    แนวโน้มการลงทะเบียน
                </h3>
                @if(count($report['enrollments'] ?? []) > 0)
                <div class="h-64" x-data="{
                    init() {
                        const ctx = this.$refs.canvas.getContext('2d');
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: @json(collect($report['enrollments'])->pluck('date')),
                                datasets: [{
                                    label: 'การลงทะเบียน',
                                    data: @json(collect($report['enrollments'])->pluck('count')),
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true }
                                }
                            }
                        });
                    }
                }">
                    <canvas x-ref="canvas"></canvas>
                </div>
                @else
                <div class="h-64 flex items-center justify-center text-gray-500 dark:text-gray-400">
                    ไม่มีข้อมูลแนวโน้ม
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush
