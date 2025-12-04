{{--
    หน้าแก้ไขกระทู้
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-arrow-x')

@section('title', 'แก้ไขกระทู้ - ' . $thread->title)

@section('content')
<div class="container-fluid px-4 py-6 max-w-4xl mx-auto" x-data="editThread()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('forum.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            ฟอรั่ม
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <a href="{{ route('forum.thread.show', $thread->slug) }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            {{ Str::limit($thread->title, 30) }}
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-medium">แก้ไขกระทู้</span>
    </nav>

    {{-- Header --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 via-orange-500 to-red-500 rounded-3xl p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        <div class="relative z-10 text-center">
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-edit text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-white mb-2">แก้ไขกระทู้</h1>
            <p class="text-white/90">แก้ไขเนื้อหากระทู้ของคุณ</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="glass-fusion-card rounded-2xl p-6 md:p-8 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
        <form action="{{ route('forum.thread.update', $thread->slug) }}" method="POST" @submit="submitting = true">
            @csrf
            @method('PUT')

            {{-- Category (แสดงเฉพาะ ไม่ให้แก้ไข) --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-folder mr-2 text-purple-500"></i>
                    หมวดหมู่
                </label>
                <div class="flex items-center gap-3 px-4 py-3 bg-gray-100 dark:bg-gray-700 rounded-xl">
                    <span class="text-xl">{{ $thread->category->icon ?? '📁' }}</span>
                    <span class="text-gray-900 dark:text-white">{{ $thread->category->name }}</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    ไม่สามารถเปลี่ยนหมวดหมู่ได้หลังจากสร้างกระทู้แล้ว
                </p>
            </div>

            {{-- Title --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-heading mr-2 text-blue-500"></i>
                    หัวข้อกระทู้ *
                </label>
                <input type="text"
                       name="title"
                       x-model="title"
                       placeholder="เขียนหัวข้อที่ชัดเจนและน่าสนใจ..."
                       required
                       minlength="5"
                       maxlength="255"
                       class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white placeholder-gray-400">
                <div class="flex justify-between mt-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400">ขั้นต่ำ 5 ตัวอักษร</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="title.length"></span>/255
                    </p>
                </div>
                @error('title')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Content --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-align-left mr-2 text-green-500"></i>
                    เนื้อหา *
                </label>
                <textarea name="content"
                          x-model="content"
                          rows="10"
                          placeholder="เขียนเนื้อหาของคุณที่นี่... รองรับ Markdown และ HTML พื้นฐาน"
                          required
                          minlength="20"
                          class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white placeholder-gray-400 resize-none"></textarea>
                <div class="flex justify-between mt-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400">ขั้นต่ำ 20 ตัวอักษร</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span x-text="content.length"></span> ตัวอักษร
                    </p>
                </div>
                @error('content')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Info --}}
            <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/30 rounded-xl">
                <h4 class="text-sm font-bold text-amber-600 dark:text-amber-400 mb-2 flex items-center gap-2">
                    <i class="fas fa-clock"></i>
                    ข้อมูลกระทู้
                </h4>
                <ul class="text-sm text-amber-700 dark:text-amber-300 space-y-1">
                    <li>📅 สร้างเมื่อ: {{ $thread->created_at->thaidate('j M Y เวลา H:i') }}</li>
                    @if($thread->updated_at != $thread->created_at)
                    <li>✏️ แก้ไขล่าสุด: {{ $thread->updated_at->thaidate('j M Y เวลา H:i') }}</li>
                    @endif
                    <li>👁️ เข้าชม: {{ number_format($thread->view_count) }} ครั้ง</li>
                </ul>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-4">
                <button type="submit"
                        :disabled="submitting || !isValid"
                        :class="{ 'opacity-50 cursor-not-allowed': submitting || !isValid }"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold rounded-xl hover:shadow-lg transition-all">
                    <i class="fas fa-save" :class="{ 'animate-spin': submitting }"></i>
                    <span x-text="submitting ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข'"></span>
                </button>

                <a href="{{ route('forum.thread.show', $thread->slug) }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    <span>ยกเลิก</span>
                </a>
            </div>
        </form>
    </div>

    {{-- Delete Thread --}}
    <div class="mt-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
        <h4 class="text-sm font-bold text-red-600 dark:text-red-400 mb-2 flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i>
            ลบกระทู้
        </h4>
        <p class="text-sm text-red-700 dark:text-red-300 mb-4">
            การลบกระทู้จะลบทุกความคิดเห็นในกระทู้นี้ด้วย และไม่สามารถกู้คืนได้
        </p>
        <form action="{{ route('forum.thread.destroy', $thread->slug) }}" method="POST"
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

@push('scripts')
<script>
function editThread() {
    return {
        title: @json($thread->title),
        content: @json($thread->content),
        submitting: false,

        get isValid() {
            return this.title.length >= 5 && this.content.length >= 20;
        }
    };
}
</script>
@endpush
@endsection
