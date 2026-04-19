@extends('layouts.admin-v3')

@section('title', 'คอร์สของฉัน - Instructor')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-white">
                <i class="fas fa-book-open mr-3"></i>
                คอร์สของฉัน
            </h1>
            <p class="text-gray-300 mt-1">จัดการคอร์สที่คุณสร้าง</p>
        </div>
        <a href="{{ route('admin.instructor.courses.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-green-500/30 transition-all">
            <i class="fas fa-plus"></i>
            สร้างคอร์สใหม่
        </a>
    </div>

    {{-- Filters --}}
    <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 p-6">
        <form method="GET" action="{{ route('admin.instructor.courses.index') }}" class="flex flex-col md:flex-row gap-4">
            {{-- Search --}}
            <div class="flex-1">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="ค้นหาคอร์ส..."
                           class="w-full pl-10 pr-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-500/50">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            {{-- Status Filter --}}
            <select name="status"
                    class="px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                    onchange="this.form.submit()">
                <option value="" class="bg-gray-800">ทุกสถานะ</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }} class="bg-gray-800">เผยแพร่แล้ว</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }} class="bg-gray-800">แบบร่าง</option>
            </select>

            {{-- Category Filter --}}
            <select name="category"
                    class="px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50"
                    onchange="this.form.submit()">
                <option value="" class="bg-gray-800">ทุกหมวดหมู่</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }} class="bg-gray-800">
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="px-6 py-3 bg-purple-500 hover:bg-purple-600 text-white rounded-xl transition">
                <i class="fas fa-filter mr-2"></i>
                กรอง
            </button>
        </form>
    </div>

    {{-- Course List --}}
    <div class="bg-white/10 backdrop-blur-xl rounded-2xl border border-white/20 overflow-hidden">
        @if($courses->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-white/10">
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">คอร์ส</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-300">หมวดหมู่</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-300">ระดับ</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-300">Quiz</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-300">ผู้เรียน</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-300">สถานะ</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-300">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @foreach($courses as $course)
                            <tr class="hover:bg-white/5 transition">
                                {{-- Course --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        {{-- Thumbnail --}}
                                        <div class="w-16 h-12 rounded-lg overflow-hidden bg-gray-700 flex-shrink-0">
                                            @if($course->thumbnail)
                                                <img src="{{ Storage::url($course->thumbnail) }}" alt="{{ $course->title }}" class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-600 to-indigo-700">
                                                    <i class="fas fa-book text-white"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <h3 class="text-white font-medium">{{ Str::limit($course->title, 40) }}</h3>
                                            <p class="text-sm text-gray-400">{{ Str::limit($course->excerpt, 50) }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Category --}}
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-purple-500/20 text-purple-300 text-sm rounded-full">
                                        {{ $course->category->name ?? '-' }}
                                    </span>
                                </td>

                                {{-- Level --}}
                                <td class="px-6 py-4 text-center">
                                    <span class="text-white">Level {{ $course->course_level ?? 1 }}</span>
                                    <p class="text-xs text-gray-400">{{ $course->difficulty_label ?? ucfirst($course->difficulty) }}</p>
                                </td>

                                {{-- Quiz Count --}}
                                <td class="px-6 py-4 text-center">
                                    <span class="text-white font-medium">{{ $course->quizzes_count ?? 0 }}</span>
                                    <p class="text-xs text-gray-400">แบบทดสอบ</p>
                                </td>

                                {{-- Students --}}
                                <td class="px-6 py-4 text-center">
                                    <span class="text-white font-medium">{{ $course->user_progress_count ?? 0 }}</span>
                                    <p class="text-xs text-gray-400">ผู้เรียน</p>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 text-center">
                                    @if($course->is_published)
                                        <span class="px-3 py-1 bg-green-500/20 text-green-400 text-sm rounded-full">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            เผยแพร่
                                        </span>
                                    @elseif(isset($course->metadata['pending_approval']) && $course->metadata['pending_approval'])
                                        <span class="px-3 py-1 bg-yellow-500/20 text-yellow-400 text-sm rounded-full">
                                            <i class="fas fa-clock mr-1"></i>
                                            รออนุมัติ
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-gray-500/20 text-gray-400 text-sm rounded-full">
                                            <i class="fas fa-edit mr-1"></i>
                                            แบบร่าง
                                        </span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.instructor.courses.stats', $course) }}"
                                           class="p-2 text-blue-400 hover:text-blue-300 transition"
                                           title="สถิติ">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <a href="{{ route('admin.instructor.courses.quiz', $course) }}"
                                           class="p-2 text-purple-400 hover:text-purple-300 transition"
                                           title="จัดการ Quiz">
                                            <i class="fas fa-clipboard-question"></i>
                                        </a>
                                        <a href="{{ route('admin.instructor.courses.edit', $course) }}"
                                           class="p-2 text-yellow-400 hover:text-yellow-300 transition"
                                           title="แก้ไข">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if(!$course->is_published && !isset($course->metadata['pending_approval']))
                                            <form action="{{ route('admin.instructor.courses.submit-approval', $course) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        class="p-2 text-green-400 hover:text-green-300 transition"
                                                        title="ส่งขออนุมัติ"
                                                        onclick="return confirm('ต้องการส่งคอร์สนี้เพื่อขออนุมัติหรือไม่?')">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($courses->hasPages())
                <div class="p-6 border-t border-white/10">
                    {{ $courses->withQueryString()->links() }}
                </div>
            @endif
        @else
            <div class="p-12 text-center">
                <i class="fas fa-book-open text-6xl text-gray-500 mb-6"></i>
                <h3 class="text-xl font-semibold text-white mb-2">ยังไม่มีคอร์ส</h3>
                <p class="text-gray-400 mb-6">เริ่มสร้างคอร์สแรกของคุณเลย!</p>
                <a href="{{ route('admin.instructor.courses.create') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl transition">
                    <i class="fas fa-plus"></i>
                    สร้างคอร์สใหม่
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
