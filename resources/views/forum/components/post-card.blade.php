{{--
    Post/Comment Card Component
    แสดงคอมเมนต์และ replies
--}}

@php
    $postTrophies = app(\App\Services\ForumTrophyService::class)->getDisplayTrophies($post->user);
    $postNameColor = app(\App\Services\ForumTrophyService::class)->getNameColor($post->user);
@endphp

<div class="p-4 rounded-xl {{ $post->is_best_answer ? 'bg-green-50 dark:bg-green-900/20 border-2 border-green-500' : 'bg-gray-50 dark:bg-gray-700/50' }}"
     x-data="{ showReply: false, showReportModal: false }"
     id="post-{{ $post->id }}">

    {{-- Best Answer Badge --}}
    @if($post->is_best_answer)
    <div class="flex items-center gap-2 mb-3 text-green-600 dark:text-green-400 font-semibold">
        <i class="fas fa-check-circle"></i>
        <span>คำตอบที่ดีที่สุด</span>
    </div>
    @endif

    <div class="flex gap-4">
        {{-- Avatar --}}
        <div class="flex-shrink-0">
            @if($post->user->profile_photo ?? false)
            <img src="{{ $post->user->profile_photo }}"
                 alt="{{ $post->user->name }}"
                 class="w-10 h-10 rounded-full object-cover">
            @else
            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-bold">
                {{ strtoupper(substr($post->user->name ?? 'U', 0, 1)) }}
            </div>
            @endif
        </div>

        <div class="flex-1 min-w-0">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-bold text-gray-900 dark:text-white {{ $postNameColor ?? '' }}">
                        {{ $post->user->name ?? 'ผู้ใช้' }}
                    </span>
                    @foreach($postTrophies as $trophy)
                    <span title="{{ $trophy->name }}: {{ $trophy->description }}" class="cursor-help">
                        {{ $trophy->icon }}
                    </span>
                    @endforeach
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        • {{ $post->created_at->diffForHumans() }}
                    </span>
                </div>
            </div>

            {{-- Content --}}
            <div class="prose prose-sm dark:prose-invert max-w-none mb-3">
                {!! strip_tags($post->content, '<p><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><code><pre><table><thead><tbody><tr><th><td><hr><del><sup><sub><span><div>') !!}
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-4 text-sm">
                {{-- Like --}}
                <button onclick="togglePostLike({{ $post->id }}, this)"
                        class="flex items-center gap-1 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                        data-liked="{{ $post->isLikedBy(auth()->user()) ? 'true' : 'false' }}">
                    <i class="fas fa-heart"></i>
                    <span id="post-likes-{{ $post->id }}">{{ $post->likes_count }}</span>
                </button>

                {{-- Reply --}}
                @auth
                @if(!$thread->is_locked)
                <button @click="showReply = !showReply"
                        class="flex items-center gap-1 text-gray-500 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400 transition-colors">
                    <i class="fas fa-reply"></i>
                    <span>ตอบกลับ</span>
                </button>
                @endif
                @endauth

                {{-- Best Answer Button --}}
                @auth
                @if(Auth::id() === $thread->user_id && !$post->is_best_answer)
                <button onclick="markBestAnswer({{ $post->id }})"
                        class="flex items-center gap-1 text-gray-500 dark:text-gray-400 hover:text-green-500 dark:hover:text-green-400 transition-colors">
                    <i class="fas fa-check"></i>
                    <span>ตั้งเป็นคำตอบที่ดี</span>
                </button>
                @endif
                @endauth

                {{-- Report --}}
                @auth
                <button @click="showReportModal = true"
                        class="flex items-center gap-1 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors">
                    <i class="fas fa-flag"></i>
                </button>
                @endauth

                {{-- Edit/Delete --}}
                @auth
                @if(Auth::id() === $post->user_id)
                <button onclick="deletePost({{ $post->id }})"
                        class="flex items-center gap-1 text-gray-500 dark:text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors">
                    <i class="fas fa-trash"></i>
                </button>
                @endif
                @endauth
            </div>

            {{-- Reply Form --}}
            @auth
            <div x-show="showReply" x-collapse class="mt-4">
                <form action="{{ route('forum.post.store', $thread->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $post->id }}">
                    <textarea name="content"
                              rows="2"
                              placeholder="เขียนคำตอบ..."
                              class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-purple-500 resize-none text-sm"
                              required></textarea>
                    <div class="flex justify-end gap-2 mt-2">
                        <button type="button" @click="showReply = false"
                                class="px-4 py-1 text-gray-500 hover:text-gray-700 text-sm">
                            ยกเลิก
                        </button>
                        <button type="submit"
                                class="px-4 py-1 bg-purple-600 text-white rounded-lg hover:bg-purple-700 text-sm">
                            ส่ง
                        </button>
                    </div>
                </form>
            </div>
            @endauth

            {{-- Nested Replies --}}
            @if($post->replies->count() > 0)
            <div class="mt-4 pl-4 border-l-2 border-gray-200 dark:border-gray-600 space-y-4">
                @foreach($post->replies as $reply)
                @include('forum.components.post-card', ['post' => $reply, 'thread' => $thread])
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Report Modal --}}
    @auth
    <div x-show="showReportModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @click.self="showReportModal = false">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">รายงานคอมเมนต์</h3>
            <form action="{{ route('forum.post.report', $post->id) }}" method="POST"
                  onsubmit="return submitReport(event)">
                @csrf
                <select name="reason_type" required
                        class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg mb-4">
                    <option value="">-- เลือกเหตุผล --</option>
                    <option value="spam">สแปม</option>
                    <option value="harassment">คุกคาม</option>
                    <option value="inappropriate">เนื้อหาไม่เหมาะสม</option>
                    <option value="misinformation">ข้อมูลเท็จ</option>
                    <option value="hate_speech">Hate Speech</option>
                    <option value="copyright">ละเมิดลิขสิทธิ์</option>
                    <option value="other">อื่นๆ</option>
                </select>
                <textarea name="reason_detail"
                          rows="3"
                          placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)"
                          class="w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 border-0 rounded-lg mb-4 resize-none"></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showReportModal = false"
                            class="px-4 py-2 text-gray-500 hover:text-gray-700">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        ส่งรายงาน
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endauth
</div>

@once
@push('scripts')
<script>
async function togglePostLike(postId, button) {
    try {
        const response = await fetch(`/forum/post/${postId}/like`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await response.json();
        document.getElementById(`post-likes-${postId}`).textContent = data.likes_count;
        button.dataset.liked = data.liked ? 'true' : 'false';
        button.classList.toggle('text-red-500', data.liked);
    } catch (error) {
        console.error('Error:', error);
    }
}

async function markBestAnswer(postId) {
    if (!confirm('ตั้งเป็นคำตอบที่ดีที่สุดหรือไม่?')) return;

    try {
        const response = await fetch(`/forum/post/${postId}/best-answer`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await response.json();
        if (data.success) {
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function deletePost(postId) {
    if (!confirm('คุณแน่ใจหรือไม่ว่าต้องการลบคอมเมนต์นี้?')) return;

    try {
        const response = await fetch(`/forum/post/${postId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById(`post-${postId}`).remove();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

async function submitReport(event) {
    event.preventDefault();
    const form = event.target;
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await response.json();
        alert(data.message);
        form.closest('[x-data]').__x.$data.showReportModal = false;
    } catch (error) {
        console.error('Error:', error);
    }
    return false;
}
</script>
@endpush
@endonce
