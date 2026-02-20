@extends('layouts.admin')

@section('title', 'คำถามที่รอแอดมินตอบ')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="savedQuestions()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                📝 คำถามที่รอแอดมินตอบ
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                คำถามที่ AI "จันทรา" ตอบไม่ได้ จะถูกบันทึกไว้ที่นี่เพื่อให้แอดมินมาตอบกลับ
            </p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-blue-100 text-sm">คำถามทั้งหมด</span>
                <span class="text-2xl">📋</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['total']) }}</div>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-orange-100 text-sm">รอตอบ</span>
                <span class="text-2xl">⏳</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['pending']) }}</div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-5 text-white">
            <div class="flex items-center justify-between mb-2">
                <span class="text-green-100 text-sm">ตอบแล้ว</span>
                <span class="text-2xl">✅</span>
            </div>
            <div class="text-2xl font-bold">{{ number_format($stats['replied']) }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="ค้นหาคำถาม, ชื่อผู้ใช้..."
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>รอตอบ</option>
                    <option value="replied" {{ request('status') === 'replied' ? 'selected' : '' }}>ตอบแล้ว</option>
                </select>
            </div>
            <button type="submit"
                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                ค้นหา
            </button>
        </form>
    </div>

    {{-- Questions List --}}
    <div class="space-y-4">
        @forelse($questions as $q)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 border-l-4 {{ $q->is_replied ? 'border-green-500' : 'border-orange-500' }}">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    {{-- Question Info --}}
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-1 text-xs rounded-full {{ $q->is_replied ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' }}">
                                {{ $q->is_replied ? '✅ ตอบแล้ว' : '⏳ รอตอบ' }}
                            </span>
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $q->reason_label }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $q->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <div class="mb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                👤 {{ $q->user_name ?: $q->platform_user_id }}
                            </span>
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 mb-3">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">คำถาม:</p>
                            <p class="text-gray-900 dark:text-white">{{ $q->question }}</p>
                        </div>

                        @if($q->ai_response)
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 mb-3">
                                <p class="text-sm font-medium text-blue-500 dark:text-blue-400 mb-1">AI ตอบ:</p>
                                <p class="text-gray-700 dark:text-gray-300 text-sm">{{ $q->ai_response }}</p>
                            </div>
                        @endif

                        @if($q->is_replied)
                            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                                <div class="flex items-center justify-between mb-1">
                                    <p class="text-sm font-medium text-green-500 dark:text-green-400">
                                        แอดมินตอบ ({{ $q->replied_at?->diffForHumans() }}):
                                    </p>
                                    <span class="px-2 py-0.5 text-xs rounded-full {{ $q->is_sent_to_user ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' }}">
                                        {{ $q->is_sent_to_user ? '📨 ส่งถึงผู้ใช้แล้ว' : '⚠️ ยังไม่ได้ส่ง' }}
                                    </span>
                                </div>
                                <p class="text-gray-700 dark:text-gray-300">{{ $q->admin_reply }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex md:flex-col gap-2 shrink-0">
                        @if(!$q->is_replied)
                            <button @click="openReply({{ $q->id }}, '{{ addslashes($q->question) }}')"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition">
                                ✏️ ตอบ
                            </button>
                        @elseif(!$q->is_sent_to_user)
                            <form method="POST" action="{{ route('admin.fortune.saved-questions.resend', $q) }}">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition w-full">
                                    📨 ส่งซ้ำ
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.fortune.saved-questions.destroy', $q) }}"
                              onsubmit="return confirm('ลบคำถามนี้?')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm transition w-full">
                                🗑️ ลบ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-12 text-center">
                <div class="text-4xl mb-4">📭</div>
                <p class="text-gray-500 dark:text-gray-400">ยังไม่มีคำถามที่รอตอบ</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $questions->links() }}
    </div>

    {{-- Reply Modal --}}
    <div x-show="showReplyModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @keydown.escape.window="showReplyModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-lg mx-4"
             @click.outside="showReplyModal = false">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                ✏️ ตอบคำถาม
            </h3>

            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 mb-4">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">คำถาม:</p>
                <p class="text-gray-900 dark:text-white" x-text="replyQuestion"></p>
            </div>

            <form :action="'/admin/fortune/saved-questions/' + replyId + '/reply'" method="POST">
                @csrf
                <textarea name="admin_reply" rows="4"
                          placeholder="พิมพ์คำตอบ..."
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white mb-4"
                          required></textarea>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="showReplyModal = false"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        ส่งคำตอบ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function savedQuestions() {
    return {
        showReplyModal: false,
        replyId: null,
        replyQuestion: '',
        openReply(id, question) {
            this.replyId = id;
            this.replyQuestion = question;
            this.showReplyModal = true;
        }
    }
}
</script>
@endpush
@endsection
