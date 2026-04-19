@extends('layouts.admin-v3')

@section('title', 'Instructor Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">
                <i class="fas fa-chalkboard-teacher mr-3"></i>
                Instructor Dashboard
            </h1>
            <p class="text-gray-300 mt-1">จัดการคอร์สและดูสถิติผู้เรียนของคุณ</p>
        </div>
        <a href="{{ route('admin.instructor.courses.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-green-500/30 transition-all">
            <i class="fas fa-plus"></i>
            สร้างคอร์สใหม่
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        {{-- จำนวนคอร์ส --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-book-open text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ $stats['total_courses'] }}</p>
            <p class="text-sm text-gray-300">คอร์สทั้งหมด</p>
        </div>

        {{-- คอร์สเผยแพร่ --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-check-circle text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ $stats['published_courses'] }}</p>
            <p class="text-sm text-gray-300">เผยแพร่แล้ว</p>
        </div>

        {{-- แบบร่าง --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-edit text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ $stats['draft_courses'] }}</p>
            <p class="text-sm text-gray-300">แบบร่าง</p>
        </div>

        {{-- ผู้เรียน --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($stats['total_students']) }}</p>
            <p class="text-sm text-gray-300">ผู้เรียนทั้งหมด</p>
        </div>

        {{-- จบคอร์ส --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($stats['total_completions']) }}</p>
            <p class="text-sm text-gray-300">จบคอร์สแล้ว</p>
        </div>

        {{-- รายได้ --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-red-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-coins text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">฿{{ number_format($stats['total_earnings'], 2) }}</p>
            <p class="text-sm text-gray-300">รายได้รวม</p>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- คอร์สของฉัน --}}
        <div class="lg:col-span-2 bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 overflow-hidden">
            <div class="p-6 border-b border-white/10">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-white">
                        <i class="fas fa-book mr-2 text-blue-400"></i>
                        คอร์สของฉัน
                    </h2>
                    <a href="{{ route('admin.instructor.courses.index') }}" class="text-blue-400 hover:text-blue-300 text-sm">
                        ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div class="p-6">
                @forelse($myCourses->take(5) as $course)
                    <div class="flex items-center gap-4 p-4 {{ !$loop->last ? 'border-b border-white/10' : '' }} hover:bg-white/5 rounded-lg transition">
                        {{-- Thumbnail --}}
                        <div class="w-20 h-14 rounded-lg overflow-hidden bg-gray-700 flex-shrink-0">
                            @if($course->thumbnail)
                                <img src="{{ Storage::url($course->thumbnail) }}" alt="{{ $course->title }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-600 to-indigo-700">
                                    <i class="fas fa-book text-white text-xl"></i>
                                </div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <h3 class="text-white font-medium truncate">{{ $course->title }}</h3>
                            <div class="flex items-center gap-3 mt-1 text-sm text-gray-400">
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-folder"></i>
                                    {{ $course->category->name ?? 'ไม่มีหมวดหมู่' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="fas fa-signal"></i>
                                    {{ $course->difficulty_label ?? ucfirst($course->difficulty) }}
                                </span>
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="flex items-center gap-2">
                            @if($course->is_published)
                                <span class="px-3 py-1 bg-green-500/20 text-green-400 text-xs font-medium rounded-full">
                                    เผยแพร่
                                </span>
                            @else
                                <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 text-xs font-medium rounded-full">
                                    แบบร่าง
                                </span>
                            @endif
                            <a href="{{ route('admin.instructor.courses.edit', $course) }}" class="p-2 text-gray-400 hover:text-white transition">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <i class="fas fa-book-open text-4xl text-gray-500 mb-4"></i>
                        <p class="text-gray-400">คุณยังไม่มีคอร์ส</p>
                        <a href="{{ route('admin.instructor.courses.create') }}" class="inline-block mt-4 px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition">
                            สร้างคอร์สแรก
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- สถิติ Quiz --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 overflow-hidden">
            <div class="p-6 border-b border-white/10">
                <h2 class="text-xl font-bold text-white">
                    <i class="fas fa-clipboard-question mr-2 text-purple-400"></i>
                    สถิติ Quiz
                </h2>
            </div>
            <div class="p-6 space-y-4">
                {{-- Total Quizzes --}}
                <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-question-circle text-purple-400"></i>
                        </div>
                        <span class="text-gray-300">Quiz ทั้งหมด</span>
                    </div>
                    <span class="text-xl font-bold text-white">{{ $quizStats['total_quizzes'] }}</span>
                </div>

                {{-- Total Attempts --}}
                <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-pen text-blue-400"></i>
                        </div>
                        <span class="text-gray-300">ทำ Quiz ทั้งหมด</span>
                    </div>
                    <span class="text-xl font-bold text-white">{{ number_format($quizStats['total_attempts']) }}</span>
                </div>

                {{-- Pass Rate --}}
                <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-green-400"></i>
                        </div>
                        <span class="text-gray-300">อัตราผ่าน</span>
                    </div>
                    <span class="text-xl font-bold text-white">{{ number_format($quizStats['pass_rate'], 1) }}%</span>
                </div>

                {{-- Average Score --}}
                <div class="flex items-center justify-between p-4 bg-white/5 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-star text-yellow-400"></i>
                        </div>
                        <span class="text-gray-300">คะแนนเฉลี่ย</span>
                    </div>
                    <span class="text-xl font-bold text-white">{{ number_format($quizStats['avg_score'], 1) }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 overflow-hidden">
        <div class="p-6 border-b border-white/10">
            <h2 class="text-xl font-bold text-white">
                <i class="fas fa-clock mr-2 text-orange-400"></i>
                กิจกรรมล่าสุด
            </h2>
        </div>
        <div class="divide-y divide-white/10">
            @forelse($recentActivity as $activity)
                <div class="p-4 hover:bg-white/5 transition flex items-center gap-4">
                    {{-- User Avatar --}}
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                        {{ strtoupper(substr($activity->user->name ?? 'U', 0, 1)) }}
                    </div>

                    {{-- Activity Content --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-white">
                            <span class="font-medium">{{ $activity->user->name ?? 'ผู้ใช้' }}</span>
                            @if($activity->status === 'completed')
                                <span class="text-green-400">เรียนจบ</span>
                            @else
                                <span class="text-blue-400">กำลังเรียน</span>
                            @endif
                            <span class="text-gray-300">{{ $activity->article->title ?? 'คอร์ส' }}</span>
                        </p>
                        <p class="text-sm text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>

                    {{-- Progress --}}
                    @if($activity->status !== 'completed')
                        <div class="text-right">
                            <p class="text-lg font-bold text-white">{{ $activity->progress_percentage ?? 0 }}%</p>
                            <p class="text-xs text-gray-400">ความคืบหน้า</p>
                        </div>
                    @else
                        <div class="p-2 bg-green-500/20 rounded-lg">
                            <i class="fas fa-check text-green-400"></i>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-12 text-center">
                    <i class="fas fa-inbox text-4xl text-gray-500 mb-4"></i>
                    <p class="text-gray-400">ยังไม่มีกิจกรรมล่าสุด</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
