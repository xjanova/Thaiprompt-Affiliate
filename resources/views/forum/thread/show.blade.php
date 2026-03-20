{{--
    หน้าแสดงกระทู้
    แสดงรายละเอียดกระทู้ คอมเมนต์ และระบบไลค์
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-arrow-x')

@section('title', $thread->title . ' - ฟอรั่ม')

@section('content')
<div class="container-fluid px-4 py-6" x-data="threadShow()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6 overflow-x-auto whitespace-nowrap">
        @foreach($breadcrumbs as $index => $crumb)
            @if($crumb['url'])
            <a href="{{ $crumb['url'] }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors flex-shrink-0">
                {{ $crumb['name'] }}
            </a>
            @else
            <span class="text-gray-900 dark:text-white font-medium truncate max-w-xs">{{ $crumb['name'] }}</span>
            @endif

            @if($index < count($breadcrumbs) - 1)
            <i class="fas fa-chevron-right text-xs flex-shrink-0"></i>
            @endif
        @endforeach
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-3 space-y-6">

            {{-- Thread Header --}}
            <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
                {{-- Badges --}}
                <div class="flex items-center gap-2 flex-wrap mb-4">
                    @if($thread->is_pinned)
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-sm font-semibold rounded-full">
                        <i class="fas fa-thumbtack"></i> ปักหมุด
                    </span>
                    @endif
                    @if($thread->is_featured)
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 text-sm font-semibold rounded-full">
                        <i class="fas fa-star"></i> แนะนำ
                    </span>
                    @endif
                    @if($thread->is_locked)
                    <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-semibold rounded-full">
                        <i class="fas fa-lock"></i> ล็อค
                    </span>
                    @endif

                    <a href="{{ route('forum.category', $thread->category->slug) }}"
                       class="inline-flex items-center gap-1 px-3 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-sm font-semibold rounded-full hover:bg-purple-200 dark:hover:bg-purple-800/50 transition-colors">
                        <i class="{{ $thread->category->icon ?? 'fas fa-folder' }}"></i>
                        {{ $thread->category->name }}
                    </a>
                </div>

                {{-- Title --}}
                <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white mb-4">
                    {{ $thread->title }}
                </h1>

                {{-- Author Info --}}
                <div class="flex items-center gap-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex-shrink-0">
                        @if($thread->user->profile_photo ?? false)
                        <img src="{{ $thread->user->profile_photo }}"
                             alt="{{ $thread->user->name }}"
                             class="w-14 h-14 rounded-full object-cover border-2 border-white dark:border-gray-600 shadow">
                        @else
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold text-xl shadow">
                            {{ strtoupper(substr($thread->user->name ?? 'U', 0, 1)) }}
                        </div>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-gray-900 dark:text-white {{ $authorNameColor ?? '' }}">
                                {{ $thread->user->name ?? 'ผู้ใช้' }}
                            </span>
                            @foreach($authorTrophies as $trophy)
                            <span title="{{ $trophy->name }}: {{ $trophy->description }}"
                                  class="cursor-help text-lg">
                                {{ $trophy->icon }}
                            </span>
                            @endforeach
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <i class="fas fa-clock mr-1"></i>
                            {{ $thread->created_at->format('d M Y, H:i') }}
                            ({{ $thread->created_at->diffForHumans() }})
                        </p>
                    </div>

                    {{-- Edit/Delete buttons --}}
                    @auth
                    @if(Auth::id() === $thread->user_id)
                    <div class="ml-auto flex items-center gap-2">
                        <a href="{{ route('forum.thread.edit', $thread->slug) }}"
                           class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-edit mr-1"></i> แก้ไข
                        </a>
                        <button @click="confirmDelete()"
                                class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                            <i class="fas fa-trash mr-1"></i> ลบ
                        </button>
                    </div>
                    @endif
                    @endauth
                </div>

                {{-- Content --}}
                <div class="prose prose-lg dark:prose-invert max-w-none mt-6">
                    {!! strip_tags($thread->content, '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><code><pre><table><thead><tbody><tr><th><td><hr><del><sup><sub><span><div>') !!}
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        {{-- Like Button --}}
                        <button @click="toggleLike()"
                                :class="isLiked ? 'bg-red-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg hover:scale-105 transition-all font-semibold">
                            <i class="fas fa-heart" :class="isLiked ? 'animate-pulse' : ''"></i>
                            <span x-text="likesCount"></span>
                        </button>

                        {{-- Share --}}
                        <button @click="shareThread()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-share-alt"></i>
                            <span>แชร์</span>
                        </button>

                        {{-- Report --}}
                        @auth
                        <button @click="showReportModal = true"
                                class="inline-flex items-center gap-2 px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors">
                            <i class="fas fa-flag"></i>
                            <span>รายงาน</span>
                        </button>
                        @endauth
                    </div>

                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                        <span><i class="fas fa-eye mr-1"></i> {{ number_format($thread->views_count) }} เข้าชม</span>
                        <span><i class="fas fa-comment mr-1"></i> {{ number_format($thread->posts_count) }} คอมเมนต์</span>
                    </div>
                </div>
            </div>

            {{-- Comments Section --}}
            <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <i class="fas fa-comments text-blue-500"></i>
                    คอมเมนต์ ({{ number_format($posts->total()) }})
                </h2>

                {{-- Comment Form --}}
                @auth
                @if(!$thread->is_locked)
                <form action="{{ route('forum.post.store', $thread->id) }}" method="POST" class="mb-6">
                    @csrf
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            @if(Auth::user()->profile_photo ?? false)
                            <img src="{{ Auth::user()->profile_photo }}" class="w-10 h-10 rounded-full object-cover">
                            @else
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <textarea name="content"
                                      rows="3"
                                      placeholder="เขียนคอมเมนต์ของคุณ..."
                                      class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl focus:ring-2 focus:ring-purple-500 resize-none"
                                      required></textarea>
                            <div class="flex justify-end mt-2">
                                <button type="submit"
                                        class="px-6 py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg transition-all">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    ส่งคอมเมนต์
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                @else
                <div class="text-center py-4 text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-xl mb-6">
                    <i class="fas fa-lock mr-2"></i>
                    กระทู้นี้ถูกล็อค ไม่สามารถคอมเมนต์ได้
                </div>
                @endif
                @else
                <div class="text-center py-4 text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-xl mb-6">
                    <a href="{{ route('login') }}" class="text-purple-600 dark:text-purple-400 hover:underline font-semibold">
                        เข้าสู่ระบบ
                    </a>
                    เพื่อแสดงความคิดเห็น
                </div>
                @endauth

                {{-- Comments List --}}
                <div class="space-y-4">
                    @forelse($posts as $post)
                    @include('forum.components.post-card', ['post' => $post, 'thread' => $thread])
                    @empty
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <i class="fas fa-comment-slash text-4xl mb-2"></i>
                        <p>ยังไม่มีคอมเมนต์ เป็นคนแรกที่คอมเมนต์!</p>
                    </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($posts->hasPages())
                <div class="mt-6">
                    {{ $posts->links() }}
                </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Related Threads --}}
            @if($relatedThreads->count() > 0)
            <div class="glass-fusion-card rounded-2xl p-6 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-link text-purple-500"></i>
                    กระทู้ที่เกี่ยวข้อง
                </h3>

                <div class="space-y-3">
                    @foreach($relatedThreads as $related)
                    <a href="{{ route('forum.thread.show', $related->slug) }}"
                       class="block p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 line-clamp-2">
                            {{ $related->title }}
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $related->created_at->diffForHumans() }}
                        </p>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Report Modal --}}
    @include('forum.components.report-modal', ['type' => 'thread', 'id' => $thread->id])
</div>

@push('scripts')
<script>
function threadShow() {
    return {
        isLiked: {{ $isLiked ? 'true' : 'false' }},
        likesCount: {{ $thread->likes_count }},
        showReportModal: false,

        async toggleLike() {
            @auth
            try {
                const response = await fetch('{{ route('forum.thread.like', $thread->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                this.isLiked = data.liked;
                this.likesCount = data.likes_count;
            } catch (error) {
                console.error('Error:', error);
            }
            @else
            window.location.href = '{{ route('login') }}';
            @endauth
        },

        shareThread() {
            if (navigator.share) {
                navigator.share({
                    title: '{{ $thread->title }}',
                    url: window.location.href
                });
            } else {
                navigator.clipboard.writeText(window.location.href);
                alert('คัดลอก URL สำเร็จ!');
            }
        },

        confirmDelete() {
            if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบกระทู้นี้?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('forum.thread.destroy', $thread->slug) }}';
                form.innerHTML = '@csrf @method('DELETE')';
                document.body.appendChild(form);
                form.submit();
            }
        }
    };
}
</script>
@endpush
@endsection
