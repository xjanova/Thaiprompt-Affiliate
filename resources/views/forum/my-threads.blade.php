{{--
    หน้ากระทู้ของฉัน
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-arrow-x')

@section('title', 'กระทู้ของฉัน - ฟอรั่ม')

@section('content')
<div class="container-fluid px-4 py-6">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('forum.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            ฟอรั่ม
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-medium">กระทู้ของฉัน</span>
    </nav>

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="text-3xl">📝</span>
            กระทู้ของฉัน
        </h1>
        <a href="{{ route('forum.thread.create') }}"
           class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i>
            สร้างกระทู้ใหม่
        </a>
    </div>

    {{-- Thread List --}}
    <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
        <div class="space-y-4">
            @forelse($threads as $thread)
            @include('forum.components.thread-card', ['thread' => $thread])
            @empty
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-pen-nib text-5xl mb-4 opacity-50"></i>
                <p class="text-lg mb-2">คุณยังไม่มีกระทู้</p>
                <p class="text-sm mb-4">เริ่มสร้างกระทู้แรกของคุณเลย!</p>
                <a href="{{ route('forum.thread.create') }}"
                   class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl transition">
                    <i class="fas fa-plus mr-2"></i>
                    สร้างกระทู้ใหม่
                </a>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($threads->hasPages())
        <div class="mt-6">
            {{ $threads->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
