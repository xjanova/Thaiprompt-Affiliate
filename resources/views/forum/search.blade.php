{{--
    หน้าค้นหากระทู้
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-arrow-x')

@section('title', 'ค้นหา "' . $query . '" - ฟอรั่ม')

@section('content')
<div class="container-fluid px-4 py-6" x-data="forumSearch()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('forum.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            ฟอรั่ม
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-medium">ค้นหา</span>
    </nav>

    {{-- Search Header --}}
    <div class="glass-fusion-card rounded-2xl p-6 mb-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
        <form action="{{ route('forum.search') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text"
                       name="q"
                       value="{{ $query }}"
                       placeholder="ค้นหากระทู้..."
                       class="w-full pl-12 pr-4 py-4 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white text-lg">
            </div>
            <button type="submit"
                    class="px-8 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg transition-all">
                <i class="fas fa-search mr-2"></i>
                ค้นหา
            </button>
        </form>
    </div>

    {{-- Results --}}
    <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-search text-purple-500"></i>
                @if($query)
                    ผลการค้นหา "{{ $query }}" ({{ $threads->total() }} รายการ)
                @else
                    กรุณาใส่คำค้นหา
                @endif
            </h2>
        </div>

        @if($query)
        <div class="space-y-4">
            @forelse($threads as $thread)
            @include('forum.components.thread-card', ['thread' => $thread])
            @empty
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-search text-5xl mb-4 opacity-50"></i>
                <p class="text-lg mb-2">ไม่พบกระทู้ที่ตรงกับคำค้นหา</p>
                <p class="text-sm">ลองใช้คำค้นหาอื่นหรือค้นหาด้วยคำที่กว้างกว่านี้</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($threads->hasPages())
        <div class="mt-6">
            {{ $threads->appends(['q' => $query])->links() }}
        </div>
        @endif
        @endif
    </div>
</div>

@push('scripts')
<script>
function forumSearch() {
    return {
        // State
    };
}
</script>
@endpush
@endsection
