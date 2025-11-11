@extends('layouts.admin')

@section('title', 'คอร์สของฉัน - แดชบอร์ดผู้สอน')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 dark:from-indigo-800 dark:via-purple-800 dark:to-pink-800 rounded-2xl shadow-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold mb-2">📚 คอร์สของฉัน</h1>
                <p class="text-indigo-100 dark:text-indigo-200">จัดการและติดตามประสิทธิภาพคอร์สทั้งหมดของคุณ</p>
            </div>
            <a href="{{ route('admin.articles.create') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-xl transition-all flex items-center gap-2 font-semibold">
                <i class="fa-solid fa-plus"></i>
                สร้างคอร์สใหม่
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-64">
                <div class="relative">
                    <input type="text" id="searchCourse" placeholder="ค้นหาคอร์ส..." class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <i class="fa-solid fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>
            <select class="px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                <option value="">ทุกหมวดหมู่</option>
                <option value="programming">Programming</option>
                <option value="design">Design</option>
                <option value="marketing">Marketing</option>
            </select>
            <select class="px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-slate-700 dark:text-white rounded-lg focus:ring-2 focus:ring-purple-500">
                <option value="">เรียงตาม</option>
                <option value="students">นักเรียนมากสุด</option>
                <option value="views">วิวมากสุด</option>
                <option value="rating">คะแนนสูงสุด</option>
                <option value="recent">ล่าสุด</option>
            </select>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 dark:from-purple-700 dark:to-purple-900 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">📚</div>
            <div class="text-2xl font-bold">{{ $courses->count() }}</div>
            <div class="text-purple-100 dark:text-purple-200 text-sm">คอร์สทั้งหมด</div>
        </div>
        <div class="bg-gradient-to-br from-pink-500 to-pink-700 dark:from-pink-700 dark:to-pink-900 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">👥</div>
            <div class="text-2xl font-bold">{{ number_format($courses->sum('progress_records_count')) }}</div>
            <div class="text-pink-100 dark:text-pink-200 text-sm">นักเรียนทั้งหมด</div>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-700 dark:to-blue-900 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">⭐</div>
            <div class="text-2xl font-bold">4.8</div>
            <div class="text-blue-100 dark:text-blue-200 text-sm">คะแนนเฉลี่ย</div>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-700 dark:from-green-700 dark:to-green-900 rounded-xl shadow-lg p-6 text-white">
            <div class="text-3xl mb-2">👁️</div>
            <div class="text-2xl font-bold">{{ number_format($courses->sum('views') ?? 0) }}</div>
            <div class="text-green-100 dark:text-green-200 text-sm">วิวทั้งหมด</div>
        </div>
    </div>

    <!-- Courses Grid -->
    @if($courses->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($courses as $course)
        <div class="group bg-white dark:bg-slate-800 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden">
            <!-- Course Image -->
            <div class="relative h-48 bg-gradient-to-br from-purple-400 via-pink-500 to-indigo-500 overflow-hidden">
                @if($course->featured_image)
                <img src="{{ $course->featured_image }}" alt="{{ $course->title }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300">
                @else
                <div class="w-full h-full flex items-center justify-center text-6xl">
                    📚
                </div>
                @endif
                <div class="absolute top-3 right-3">
                    <span class="px-3 py-1 bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm rounded-full text-xs font-bold text-gray-900 dark:text-white">
                        {{ $course->category ? $course->category->name : 'ทั่วไป' }}
                    </span>
                </div>
            </div>

            <!-- Course Content -->
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                    {{ $course->title }}
                </h3>

                <!-- Stats Row -->
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="text-center">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($course->progress_records_count) }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">นักเรียน</div>
                    </div>
                    <div class="text-center">
                        <div class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($course->views ?? 0) }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">วิว</div>
                    </div>
                    <div class="text-center">
                        <div class="text-sm font-bold text-yellow-500">⭐ 4.8</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400">คะแนน</div>
                    </div>
                </div>

                <!-- Progress Bar -->
                @php
                $totalEnrolled = $course->progress_records_count;
                $completed = \App\Models\UserArticleProgress::where('article_id', $course->id)
                    ->where('status', 'completed')
                    ->count();
                $completionRate = $totalEnrolled > 0 ? round(($completed / $totalEnrolled) * 100, 1) : 0;
                @endphp

                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">อัตราการจบคอร์ส</span>
                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400">{{ $completionRate }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-purple-500 to-pink-600 h-2 rounded-full transition-all duration-500" style="width: {{ $completionRate }}%"></div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2">
                    <a href="{{ route('admin.articles.edit', $course->id) }}" class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-700 dark:hover:bg-purple-600 text-white rounded-lg text-center text-sm font-semibold transition-colors">
                        <i class="fa-solid fa-edit mr-1"></i>
                        แก้ไข
                    </a>
                    <a href="{{ route('admin.instructor.course-analytics', $course->id) }}" class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-gray-900 dark:text-white rounded-lg text-center text-sm font-semibold transition-colors">
                        <i class="fa-solid fa-chart-line mr-1"></i>
                        สถิติ
                    </a>
                </div>

                <!-- Created Date -->
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <span>สร้างเมื่อ {{ $course->created_at->format('d/m/Y') }}</span>
                        <span>{{ $course->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    @if($courses instanceof \Illuminate\Pagination\LengthAwarePaginator && $courses->hasPages())
    <div class="mt-6">
        {{ $courses->links() }}
    </div>
    @endif

    @else
    <!-- Empty State -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-12 text-center">
        <div class="text-8xl mb-6">📚</div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">ยังไม่มีคอร์ส</h3>
        <p class="text-gray-600 dark:text-gray-400 mb-6 max-w-md mx-auto">
            เริ่มต้นสร้างคอร์สแรกของคุณและแชร์ความรู้กับนักเรียนทั่วโลก
        </p>
        <a href="{{ route('admin.articles.create') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg transition-all transform hover:scale-105">
            <i class="fa-solid fa-plus"></i>
            สร้างคอร์สใหม่
        </a>
    </div>
    @endif

    <!-- Course Analytics Summary -->
    @if($courses->count() > 0)
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📊 สถิติรวมทั้งหมด</h3>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                        <th class="text-left py-3 px-4 font-bold text-gray-900 dark:text-white">คอร์ส</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-900 dark:text-white">หมวดหมู่</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-900 dark:text-white">นักเรียน</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-900 dark:text-white">จบแล้ว</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-900 dark:text-white">อัตราจบ</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-900 dark:text-white">วิว</th>
                        <th class="text-center py-3 px-4 font-bold text-gray-900 dark:text-white">การกระทำ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($courses as $course)
                    @php
                    $totalEnrolled = $course->progress_records_count;
                    $completed = \App\Models\UserArticleProgress::where('article_id', $course->id)
                        ->where('status', 'completed')
                        ->count();
                    $completionRate = $totalEnrolled > 0 ? round(($completed / $totalEnrolled) * 100, 1) : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                        <td class="py-4 px-4">
                            <div class="font-semibold text-gray-900 dark:text-white">{{ Str::limit($course->title, 50) }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">สร้างเมื่อ {{ $course->created_at->format('d/m/Y') }}</div>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full text-xs font-semibold">
                                {{ $course->category ? $course->category->name : 'ทั่วไป' }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($totalEnrolled) }}</span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($completed) }}</span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex flex-col items-center">
                                <div class="w-full max-w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-1">
                                    <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-2 rounded-full" style="width: {{ $completionRate }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">{{ $completionRate }}%</span>
                            </div>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($course->views ?? 0) }}</span>
                        </td>
                        <td class="py-4 px-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.articles.edit', $course->id) }}" class="px-3 py-1 bg-purple-100 hover:bg-purple-200 dark:bg-purple-900 dark:hover:bg-purple-800 text-purple-800 dark:text-purple-200 rounded-lg text-xs font-semibold transition-colors">
                                    <i class="fa-solid fa-edit"></i>
                                </a>
                                <a href="{{ route('admin.instructor.course-analytics', $course->id) }}" class="px-3 py-1 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:hover:bg-blue-800 text-blue-800 dark:text-blue-200 rounded-lg text-xs font-semibold transition-colors">
                                    <i class="fa-solid fa-chart-line"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('searchCourse').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    // Add search functionality here
    console.log('Searching for:', searchTerm);
});
</script>
@endpush
