@extends('layouts.admin-v3')

@section('title', 'จัดการกระทู้ฟอรั่ม')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">📝 จัดการกระทู้ฟอรั่ม</h1>
            <p class="text-white/60 mt-1">ดูและจัดการกระทู้ทั้งหมดในฟอรั่ม</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="glass-card rounded-xl p-4">
        <form action="{{ route('admin.platform-revenue.forum.threads.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหากระทู้..."
                       class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white placeholder-white/40 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
            </div>
            <div class="w-48">
                <select name="category_id" class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    <option value="">ทุกหมวดหมู่</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select name="status" class="w-full px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    <option value="">ทุกสถานะ</option>
                    <option value="pinned" {{ request('status') == 'pinned' ? 'selected' : '' }}>ปักหมุด</option>
                    <option value="locked" {{ request('status') == 'locked' ? 'selected' : '' }}>ล็อค</option>
                    <option value="featured" {{ request('status') == 'featured' ? 'selected' : '' }}>แนะนำ</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition">
                <i class="fas fa-search mr-1"></i> ค้นหา
            </button>
        </form>
    </div>

    {{-- Threads List --}}
    <div class="glass-card rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white/5">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">กระทู้</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">หมวดหมู่</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">ผู้สร้าง</th>
                        <th class="px-6 py-4 text-center text-xs font-medium text-white/60 uppercase tracking-wider">ตอบ</th>
                        <th class="px-6 py-4 text-center text-xs font-medium text-white/60 uppercase tracking-wider">ดู</th>
                        <th class="px-6 py-4 text-left text-xs font-medium text-white/60 uppercase tracking-wider">สถานะ</th>
                        <th class="px-6 py-4 text-right text-xs font-medium text-white/60 uppercase tracking-wider">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse($threads as $thread)
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.platform-revenue.forum.threads.show', $thread) }}" class="font-medium text-white hover:text-blue-400 transition">
                                    {{ Str::limit($thread->title, 50) }}
                                </a>
                                <div class="text-sm text-white/60 mt-1">
                                    {{ $thread->created_at->diffForHumans() }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 bg-white/10 rounded text-sm text-white/80">
                                    {{ $thread->category->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                        {{ substr($thread->user->name ?? '?', 0, 1) }}
                                    </div>
                                    <span class="text-white/80">{{ $thread->user->name ?? 'ไม่ทราบ' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-white/80">{{ $thread->posts_count }}</td>
                            <td class="px-6 py-4 text-center text-white/80">{{ number_format($thread->view_count) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @if($thread->is_pinned)
                                        <span class="px-2 py-0.5 bg-yellow-500/20 text-yellow-400 rounded text-xs">📌 ปักหมุด</span>
                                    @endif
                                    @if($thread->is_locked)
                                        <span class="px-2 py-0.5 bg-red-500/20 text-red-400 rounded text-xs">🔒 ล็อค</span>
                                    @endif
                                    @if($thread->is_featured)
                                        <span class="px-2 py-0.5 bg-purple-500/20 text-purple-400 rounded text-xs">⭐ แนะนำ</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1" x-data="{ thread: {{ $thread->id }} }">
                                    <button @click="togglePin(thread)" class="p-2 {{ $thread->is_pinned ? 'text-yellow-400' : 'text-white/40' }} hover:bg-white/10 rounded transition" title="ปักหมุด">
                                        <i class="fas fa-thumbtack"></i>
                                    </button>
                                    <button @click="toggleLock(thread)" class="p-2 {{ $thread->is_locked ? 'text-red-400' : 'text-white/40' }} hover:bg-white/10 rounded transition" title="ล็อค">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    <button @click="toggleFeature(thread)" class="p-2 {{ $thread->is_featured ? 'text-purple-400' : 'text-white/40' }} hover:bg-white/10 rounded transition" title="แนะนำ">
                                        <i class="fas fa-star"></i>
                                    </button>
                                    <a href="{{ route('forum.thread.show', $thread) }}" target="_blank" class="p-2 text-white/40 hover:text-white hover:bg-white/10 rounded transition" title="ดูกระทู้">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-white/60">
                                <i class="fas fa-comments text-4xl mb-4 block"></i>
                                <p>ไม่พบกระทู้</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($threads->hasPages())
            <div class="px-6 py-4 border-t border-white/10">
                {{ $threads->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>

<script>
function togglePin(threadId) {
    fetch(`/admin/forum/threads/${threadId}/pin`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

function toggleLock(threadId) {
    fetch(`/admin/forum/threads/${threadId}/lock`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

function toggleFeature(threadId) {
    fetch(`/admin/forum/threads/${threadId}/feature`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}
</script>
@endsection
