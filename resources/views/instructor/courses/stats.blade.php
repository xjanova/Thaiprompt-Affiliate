@extends('layouts.admin-v3')

@section('title', 'สถิติคอร์ส - ' . $article->title)

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.instructor.courses.index') }}"
               class="p-2 text-gray-400 hover:text-white transition">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-white">
                    <i class="fas fa-chart-bar mr-3"></i>
                    สถิติคอร์ส
                </h1>
                <p class="text-gray-300 mt-1">{{ $article->title }}</p>
            </div>
        </div>
        <a href="{{ route('admin.instructor.courses.edit', $article) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg transition">
            <i class="fas fa-edit"></i>
            แก้ไขคอร์ส
        </a>
    </div>

    {{-- Stats Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        {{-- Total Students --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($stats['total_students']) }}</p>
            <p class="text-sm text-gray-300">ผู้เรียนทั้งหมด</p>
        </div>

        {{-- Completed --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-graduation-cap text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($stats['completed']) }}</p>
            <p class="text-sm text-gray-300">เรียนจบ</p>
        </div>

        {{-- In Progress --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-spinner text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($stats['in_progress']) }}</p>
            <p class="text-sm text-gray-300">กำลังเรียน</p>
        </div>

        {{-- Avg Progress --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chart-line text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($stats['avg_progress'], 1) }}%</p>
            <p class="text-sm text-gray-300">ความคืบหน้าเฉลี่ย</p>
        </div>

        {{-- Avg Time --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-5 border border-white/20">
            <div class="flex items-center justify-between mb-3">
                <div class="w-12 h-12 bg-gradient-to-br from-teal-500 to-cyan-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clock text-xl text-white"></i>
                </div>
            </div>
            <p class="text-3xl font-bold text-white">{{ number_format($stats['avg_time_spent'] / 60, 0) }}</p>
            <p class="text-sm text-gray-300">เวลาเฉลี่ย (นาที)</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Student List --}}
        <div class="lg:col-span-2 bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 overflow-hidden">
            <div class="p-6 border-b border-white/10">
                <h2 class="text-xl font-bold text-white">
                    <i class="fas fa-users mr-2 text-blue-400"></i>
                    รายชื่อผู้เรียน
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">ผู้เรียน</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-300">ความคืบหน้า</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-300">สถานะ</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-300">ใบประกาศ</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-300">เวลา</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold text-gray-300">วันที่เริ่ม</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse($students as $progress)
                            <tr class="hover:bg-white/5 transition">
                                {{-- User --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                                            {{ strtoupper(substr($progress->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-white font-medium">{{ $progress->user->name ?? 'ผู้ใช้' }}</p>
                                            <p class="text-sm text-gray-400">{{ $progress->user->email ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Progress --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-20 h-2 bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-full"
                                                 style="width: {{ $progress->progress_percentage ?? 0 }}%"></div>
                                        </div>
                                        <span class="text-white text-sm">{{ $progress->progress_percentage ?? 0 }}%</span>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 text-center">
                                    @if($progress->status === 'completed')
                                        <span class="px-3 py-1 bg-green-500/20 text-green-400 text-sm rounded-full">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            จบแล้ว
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-blue-500/20 text-blue-400 text-sm rounded-full">
                                            <i class="fas fa-spinner mr-1"></i>
                                            กำลังเรียน
                                        </span>
                                    @endif
                                </td>

                                {{-- Certificate --}}
                                <td class="px-6 py-4 text-center">
                                    @if($progress->status === 'completed')
                                        @php
                                            $hasCert = \App\Models\Certificate::where('user_id', $progress->user_id)
                                                ->where('article_id', $article->id)
                                                ->where('is_revoked', false)
                                                ->exists();
                                        @endphp
                                        @if($hasCert)
                                            <span class="px-3 py-1 bg-purple-500/20 text-purple-400 text-sm rounded-full">
                                                <i class="fas fa-certificate mr-1"></i>
                                                มีใบประกาศ
                                            </span>
                                        @else
                                            <form action="{{ route('admin.instructor.courses.issue-certificate', ['article' => $article->id, 'user' => $progress->user_id]) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-sm rounded-full transition">
                                                    <i class="fas fa-certificate mr-1"></i>
                                                    ออกใบประกาศ
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-gray-500 text-sm">-</span>
                                    @endif
                                </td>

                                {{-- Time --}}
                                <td class="px-6 py-4 text-center text-gray-300">
                                    {{ number_format(($progress->time_spent ?? 0) / 60, 0) }} นาที
                                </td>

                                {{-- Date --}}
                                <td class="px-6 py-4 text-right text-gray-400 text-sm">
                                    {{ $progress->created_at->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fas fa-users text-4xl mb-4"></i>
                                    <p>ยังไม่มีผู้เรียน</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($students->hasPages())
                <div class="p-6 border-t border-white/10">
                    {{ $students->links() }}
                </div>
            @endif
        </div>

        {{-- Quiz Stats --}}
        <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 overflow-hidden">
            <div class="p-6 border-b border-white/10">
                <h2 class="text-xl font-bold text-white">
                    <i class="fas fa-clipboard-question mr-2 text-yellow-400"></i>
                    สถิติ Quiz
                </h2>
            </div>
            <div class="p-6 space-y-4">
                @forelse($quizStats as $quizId => $quiz)
                    <div class="bg-white/5 rounded-xl p-4">
                        <h3 class="text-white font-medium mb-3">{{ $quiz['title'] }}</h3>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400">ทำทั้งหมด</p>
                                <p class="text-xl font-bold text-white">{{ number_format($quiz['total_attempts']) }}</p>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400">ผ่าน</p>
                                <p class="text-xl font-bold text-green-400">{{ number_format($quiz['passed']) }}</p>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400">คะแนนเฉลี่ย</p>
                                <p class="text-xl font-bold text-blue-400">{{ number_format($quiz['avg_score'], 1) }}%</p>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400">คะแนนสูงสุด</p>
                                <p class="text-xl font-bold text-purple-400">{{ number_format($quiz['best_score'], 1) }}%</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-clipboard-question text-4xl text-gray-500 mb-4"></i>
                        <p class="text-gray-400">ยังไม่มี Quiz</p>
                        <a href="{{ route('admin.instructor.courses.quiz', $article) }}"
                           class="inline-block mt-4 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition">
                            <i class="fas fa-plus mr-1"></i>
                            สร้าง Quiz
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Course Rewards Info --}}
    <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 p-6">
        <h2 class="text-xl font-bold text-white mb-6">
            <i class="fas fa-gift mr-2 text-pink-400"></i>
            รางวัลที่แจกไป
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            {{-- Points --}}
            <div class="bg-white/5 rounded-xl p-4 text-center">
                <i class="fas fa-star text-2xl text-yellow-400 mb-2"></i>
                <p class="text-2xl font-bold text-white">{{ number_format(($article->points_reward ?? 0) * $stats['completed']) }}</p>
                <p class="text-sm text-gray-400">แต้มทั้งหมด</p>
            </div>

            {{-- Coins --}}
            <div class="bg-white/5 rounded-xl p-4 text-center">
                <i class="fas fa-coins text-2xl text-yellow-500 mb-2"></i>
                <p class="text-2xl font-bold text-white">{{ number_format(($article->coin_reward ?? 0) * $stats['completed'], 2) }}</p>
                <p class="text-sm text-gray-400">Coins ทั้งหมด</p>
            </div>

            {{-- Money --}}
            <div class="bg-white/5 rounded-xl p-4 text-center">
                <i class="fas fa-baht-sign text-2xl text-green-400 mb-2"></i>
                <p class="text-2xl font-bold text-white">฿{{ number_format(($article->money_reward ?? 0) * $stats['completed'], 2) }}</p>
                <p class="text-sm text-gray-400">เงินทั้งหมด</p>
            </div>

            {{-- EXP --}}
            <div class="bg-white/5 rounded-xl p-4 text-center">
                <i class="fas fa-arrow-up text-2xl text-blue-400 mb-2"></i>
                <p class="text-2xl font-bold text-white">{{ number_format(($article->exp_reward ?? 0) * $stats['completed']) }}</p>
                <p class="text-sm text-gray-400">EXP ทั้งหมด</p>
            </div>

            {{-- PV --}}
            <div class="bg-white/5 rounded-xl p-4 text-center">
                <i class="fas fa-chart-line text-2xl text-purple-400 mb-2"></i>
                <p class="text-2xl font-bold text-white">{{ number_format(($article->pv_value ?? 0) * $stats['completed'], 2) }}</p>
                <p class="text-sm text-gray-400">PV ทั้งหมด</p>
            </div>
        </div>
    </div>
</div>
@endsection
