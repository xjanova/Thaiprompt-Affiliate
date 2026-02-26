{{--
    หน้าแจ้งเตือนฟอรั่ม
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-arrow-x')

@section('title', 'การแจ้งเตือน - ฟอรั่ม')

@section('content')
<div class="container-fluid px-4 py-6">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('forum.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            ฟอรั่ม
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-medium">การแจ้งเตือน</span>
    </nav>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <span class="text-3xl">🔔</span>
            การแจ้งเตือนฟอรั่ม
        </h1>
    </div>

    {{-- Notification List --}}
    <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
        <div class="space-y-3">
            @forelse($notifications as $notification)
            <div class="flex items-start gap-4 p-4 rounded-xl transition
                {{ $notification->read_at ? 'bg-gray-50 dark:bg-gray-700/30' : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' }}">
                {{-- Icon --}}
                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center
                    {{ $notification->read_at ? 'bg-gray-200 dark:bg-gray-600' : 'bg-blue-100 dark:bg-blue-800' }}">
                    <i class="fas fa-bell {{ $notification->read_at ? 'text-gray-500' : 'text-blue-600 dark:text-blue-300' }}"></i>
                </div>

                {{-- Content --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-gray-900 dark:text-white">
                        {{ $notification->data['message'] ?? 'คุณมีการแจ้งเตือนใหม่' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <i class="fas fa-clock mr-1"></i>
                        {{ $notification->created_at->diffForHumans() }}
                    </p>
                </div>

                {{-- Unread indicator --}}
                @if(!$notification->read_at)
                <span class="flex-shrink-0 w-2 h-2 bg-blue-500 rounded-full mt-2"></span>
                @endif
            </div>
            @empty
            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                <i class="fas fa-bell-slash text-5xl mb-4 opacity-50"></i>
                <p class="text-lg mb-2">ไม่มีการแจ้งเตือน</p>
                <p class="text-sm">เมื่อมีคนตอบกระทู้หรือไลค์โพสต์ของคุณ จะแจ้งเตือนที่นี่</p>
            </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
