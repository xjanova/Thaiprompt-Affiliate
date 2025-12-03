@extends('layouts.admin-v3')

@section('title', 'สถิติฟอรั่ม')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-white">📈 สถิติฟอรั่ม</h1>
        <p class="text-white/60 mt-1">ภาพรวมและการวิเคราะห์ข้อมูลฟอรั่มชุมชน</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="glass-card rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-blue-400">{{ number_format($stats['total_categories']) }}</div>
            <div class="text-white/60 text-sm mt-1">หมวดหมู่</div>
        </div>
        <div class="glass-card rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-green-400">{{ number_format($stats['total_threads']) }}</div>
            <div class="text-white/60 text-sm mt-1">กระทู้</div>
        </div>
        <div class="glass-card rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-purple-400">{{ number_format($stats['total_posts']) }}</div>
            <div class="text-white/60 text-sm mt-1">ความคิดเห็น</div>
        </div>
        <div class="glass-card rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-yellow-400">{{ number_format($stats['active_users']) }}</div>
            <div class="text-white/60 text-sm mt-1">ผู้ใช้งาน</div>
        </div>
        <div class="glass-card rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-cyan-400">{{ number_format($weeklyThreads) }}</div>
            <div class="text-white/60 text-sm mt-1">กระทู้/สัปดาห์</div>
        </div>
        <div class="glass-card rounded-xl p-4 text-center">
            <div class="text-3xl font-bold text-red-400">{{ number_format($stats['pending_reports']) }}</div>
            <div class="text-white/60 text-sm mt-1">รายงานรอ</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Threads --}}
        <div class="glass-card rounded-xl p-6">
            <h2 class="text-lg font-bold text-white mb-4">🔥 กระทู้ยอดนิยม</h2>
            <div class="space-y-3">
                @forelse($topThreads as $thread)
                    <div class="flex items-center gap-3 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition">
                        <div class="text-lg font-bold text-white/40 w-6">{{ $loop->iteration }}</div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('forum.thread.show', $thread) }}" target="_blank"
                               class="text-white hover:text-blue-400 truncate block">
                                {{ Str::limit($thread->title, 40) }}
                            </a>
                        </div>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-blue-400">👁️ {{ number_format($thread->view_count) }}</span>
                            <span class="text-green-400">💬 {{ $thread->posts_count }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-white/40 text-center py-4">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>

        {{-- Top Contributors --}}
        <div class="glass-card rounded-xl p-6">
            <h2 class="text-lg font-bold text-white mb-4">🏆 ผู้มีส่วนร่วมสูงสุด</h2>
            <div class="space-y-3">
                @forelse($topContributors as $user)
                    <div class="flex items-center gap-3 p-3 bg-white/5 rounded-lg hover:bg-white/10 transition">
                        <div class="text-lg font-bold text-white/40 w-6">{{ $loop->iteration }}</div>
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white font-bold">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-white truncate">{{ $user->name }}</div>
                        </div>
                        <div class="flex items-center gap-4 text-sm">
                            <span class="text-blue-400">📝 {{ $user->forum_threads_count }}</span>
                            <span class="text-green-400">💬 {{ $user->forum_posts_count }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-white/40 text-center py-4">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>
@endsection
