{{--
    หน้าแสดงรายละเอียดกระทู้ (Admin)
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.admin-v3')

@section('title', 'รายละเอียดกระทู้ - ' . $thread->title)

@section('content')
<div class="space-y-6" x-data="threadAdmin()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 text-sm text-white/60 mb-2">
                <a href="{{ route('admin.platform-revenue.forum.threads.index') }}" class="hover:text-white transition">
                    จัดการกระทู้
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-white">{{ Str::limit($thread->title, 30) }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-white">📄 รายละเอียดกระทู้</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('forum.thread.show', $thread->slug) }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-green-500/20 text-green-400 rounded-lg hover:bg-green-500/30 transition">
                <i class="fas fa-external-link-alt"></i>
                <span>ดูหน้าจริง</span>
            </a>
            <a href="{{ route('admin.platform-revenue.forum.threads.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition">
                <i class="fas fa-arrow-left"></i>
                <span>กลับ</span>
            </a>
        </div>
    </div>

    {{-- Thread Info Card --}}
    <div class="glass-card rounded-xl p-6">
        <div class="flex flex-col lg:flex-row gap-6">
            {{-- Main Content --}}
            <div class="flex-1">
                <div class="flex items-start gap-4 mb-4">
                    {{-- Author Avatar --}}
                    <img src="{{ $thread->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($thread->user->name) }}"
                         alt="{{ $thread->user->name }}"
                         class="w-12 h-12 rounded-full">

                    <div class="flex-1">
                        <h2 class="text-xl font-bold text-white mb-1">{{ $thread->title }}</h2>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-white/60">
                            <span>
                                <i class="fas fa-user mr-1"></i>
                                {{ $thread->user->name }}
                            </span>
                            <span>
                                <i class="fas fa-folder mr-1"></i>
                                {{ $thread->category->name }}
                            </span>
                            <span>
                                <i class="fas fa-clock mr-1"></i>
                                {{ $thread->created_at->thaidate('j M Y H:i') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Status Badges --}}
                <div class="flex flex-wrap gap-2 mb-4">
                    @if($thread->is_pinned)
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-500/20 text-yellow-400 rounded text-xs">
                        <i class="fas fa-thumbtack"></i> ปักหมุด
                    </span>
                    @endif
                    @if($thread->is_locked)
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-500/20 text-red-400 rounded text-xs">
                        <i class="fas fa-lock"></i> ล็อค
                    </span>
                    @endif
                    @if($thread->is_featured)
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-purple-500/20 text-purple-400 rounded text-xs">
                        <i class="fas fa-star"></i> แนะนำ
                    </span>
                    @endif
                </div>

                {{-- Content --}}
                <div class="p-4 bg-white/5 rounded-lg text-white/80 prose prose-invert max-w-none">
                    {!! $thread->content !!}
                </div>
            </div>

            {{-- Sidebar Stats --}}
            <div class="w-full lg:w-64 space-y-4">
                <div class="grid grid-cols-2 lg:grid-cols-1 gap-4">
                    <div class="p-4 bg-white/5 rounded-lg text-center">
                        <div class="text-2xl font-bold text-white">{{ number_format($thread->view_count) }}</div>
                        <div class="text-white/60 text-sm">เข้าชม</div>
                    </div>
                    <div class="p-4 bg-white/5 rounded-lg text-center">
                        <div class="text-2xl font-bold text-white">{{ $thread->posts_count ?? $thread->posts->count() }}</div>
                        <div class="text-white/60 text-sm">ความคิดเห็น</div>
                    </div>
                    <div class="p-4 bg-white/5 rounded-lg text-center">
                        <div class="text-2xl font-bold text-white">{{ $thread->likes_count ?? 0 }}</div>
                        <div class="text-white/60 text-sm">ถูกใจ</div>
                    </div>
                    <div class="p-4 bg-white/5 rounded-lg text-center">
                        <div class="text-2xl font-bold text-white">{{ $thread->reports_count ?? 0 }}</div>
                        <div class="text-white/60 text-sm">รายงาน</div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="space-y-2">
                    <h3 class="text-sm font-medium text-white/60">การจัดการ</h3>

                    <button type="button"
                            @click="togglePin"
                            :disabled="loading"
                            class="w-full flex items-center gap-2 px-4 py-2 rounded-lg transition"
                            :class="isPinned ? 'bg-yellow-500/20 text-yellow-400' : 'bg-white/10 text-white hover:bg-white/20'">
                        <i class="fas fa-thumbtack"></i>
                        <span x-text="isPinned ? 'ยกเลิกปักหมุด' : 'ปักหมุด'"></span>
                    </button>

                    <button type="button"
                            @click="toggleLock"
                            :disabled="loading"
                            class="w-full flex items-center gap-2 px-4 py-2 rounded-lg transition"
                            :class="isLocked ? 'bg-red-500/20 text-red-400' : 'bg-white/10 text-white hover:bg-white/20'">
                        <i :class="isLocked ? 'fas fa-unlock' : 'fas fa-lock'"></i>
                        <span x-text="isLocked ? 'ปลดล็อค' : 'ล็อค'"></span>
                    </button>

                    <button type="button"
                            @click="toggleFeature"
                            :disabled="loading"
                            class="w-full flex items-center gap-2 px-4 py-2 rounded-lg transition"
                            :class="isFeatured ? 'bg-purple-500/20 text-purple-400' : 'bg-white/10 text-white hover:bg-white/20'">
                        <i class="fas fa-star"></i>
                        <span x-text="isFeatured ? 'ยกเลิกแนะนำ' : 'ตั้งเป็นแนะนำ'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Posts/Comments --}}
    @if($thread->posts->count() > 0)
    <div class="glass-card rounded-xl overflow-hidden">
        <div class="px-6 py-4 bg-white/5 border-b border-white/10">
            <h3 class="text-lg font-bold text-white">
                <i class="fas fa-comments mr-2"></i>
                ความคิดเห็น ({{ $thread->posts->count() }})
            </h3>
        </div>
        <div class="divide-y divide-white/10">
            @foreach($thread->posts as $post)
            <div class="p-4 hover:bg-white/5 transition">
                <div class="flex items-start gap-3">
                    <img src="{{ $post->user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($post->user->name) }}"
                         alt="{{ $post->user->name }}"
                         class="w-10 h-10 rounded-full">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-medium text-white">{{ $post->user->name }}</span>
                            <span class="text-white/40 text-sm">{{ $post->created_at->diffForHumans() }}</span>
                            @if($post->is_best_answer)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-green-500/20 text-green-400 rounded text-xs">
                                <i class="fas fa-check"></i> คำตอบที่ดีที่สุด
                            </span>
                            @endif
                        </div>
                        <div class="text-white/80 text-sm">{!! Str::limit(strip_tags($post->content), 200) !!}</div>
                        <div class="flex items-center gap-4 mt-2 text-xs text-white/40">
                            <span><i class="fas fa-heart mr-1"></i> {{ $post->likes_count ?? 0 }}</span>
                            @if($post->reports_count ?? 0 > 0)
                            <span class="text-red-400"><i class="fas fa-flag mr-1"></i> {{ $post->reports_count }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Danger Zone --}}
    <div class="glass-card rounded-xl p-6 border border-red-500/30">
        <h3 class="text-lg font-bold text-red-400 mb-2">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            พื้นที่อันตราย
        </h3>
        <p class="text-white/60 text-sm mb-4">
            การลบกระทู้จะลบทุกความคิดเห็นด้วย และไม่สามารถกู้คืนได้
        </p>
        <form action="{{ route('admin.platform-revenue.forum.threads.delete', $thread) }}" method="POST"
              onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบกระทู้นี้? การกระทำนี้ไม่สามารถยกเลิกได้')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition">
                <i class="fas fa-trash"></i>
                ลบกระทู้นี้
            </button>
        </form>
    </div>
</div>

<style>
.glass-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>

@push('scripts')
<script>
function threadAdmin() {
    return {
        isPinned: {{ $thread->is_pinned ? 'true' : 'false' }},
        isLocked: {{ $thread->is_locked ? 'true' : 'false' }},
        isFeatured: {{ $thread->is_featured ? 'true' : 'false' }},
        loading: false,

        async togglePin() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.platform-revenue.forum.threads.pin", $thread) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.isPinned = data.is_pinned;
                }
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        },

        async toggleLock() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.platform-revenue.forum.threads.lock", $thread) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.isLocked = data.is_locked;
                }
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        },

        async toggleFeature() {
            this.loading = true;
            try {
                const response = await fetch('{{ route("admin.platform-revenue.forum.threads.feature", $thread) }}', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.isFeatured = data.is_featured;
                }
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        }
    };
}
</script>
@endpush
@endsection
