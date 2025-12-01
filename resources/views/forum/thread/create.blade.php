{{--
    หน้าสร้างกระทู้ใหม่
    ใช้ V3 Design System: Tailwind CSS + Alpine.js
--}}

@extends('layouts.user-arrow-x')

@section('title', 'สร้างกระทู้ใหม่ - ฟอรั่ม')

@section('content')
<div class="container-fluid px-4 py-6 max-w-4xl mx-auto" x-data="createThread()">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
        <a href="{{ route('forum.index') }}" class="hover:text-purple-600 dark:hover:text-purple-400 transition-colors">
            ฟอรั่ม
        </a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-gray-900 dark:text-white font-medium">สร้างกระทู้ใหม่</span>
    </nav>

    {{-- Header --}}
    <div class="relative overflow-hidden bg-gradient-to-br from-purple-600 via-pink-600 to-orange-500 rounded-3xl p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_50%,rgba(255,255,255,0.2),transparent_50%)]"></div>
        </div>

        <div class="relative z-10 text-center">
            <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-pen-to-square text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl md:text-4xl font-black text-white mb-2">สร้างกระทู้ใหม่</h1>
            <p class="text-white/90">แบ่งปันความคิด ถามคำถาม หรือพูดคุยกับชุมชน</p>
        </div>
    </div>

    {{-- Form --}}
    <div class="glass-fusion-card rounded-2xl p-6 md:p-8 bg-white/80 dark:bg-gray-800/80 backdrop-blur-xl border border-white/20 dark:border-gray-700/30">
        <form action="{{ route('forum.thread.store') }}" method="POST" @submit="submitting = true">
            @csrf

            {{-- Category --}}
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    <i class="fas fa-folder mr-2 text-purple-500"></i>
                    หมวดหมู่ *
                </label>
                <select name="category_id"
                        x-model="categoryId"
                        required
                        class="w-full px-4 py-3 bg-gray-100 dark:bg-gray-700 border-0 rounded-xl focus:ring-2 focus:ring-purple-500 text-gray-900 dark:text-white">
                    <option value="">-- เลือกหมวดหมู่ --</option>
                    @foreach($categories as $parentId => $categoryGroup)
                        @if(is_null($parentId))
                            @foreach($categoryGroup as $cat)
                                <option value="{{ $cat->id }}"
                                        {{ ($category && $category->id === $cat->id) ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        @else
                            @php
                                $parent = \App\Models\ForumCategory::find($parentId);
                            @endphp
                            @if($parent)
                            <optgroup label="{{ $parent->name }}">
                                @foreach($categoryGroup as $cat)
                                    <option value="{{ $cat->id }}"
                                            {{ ($category && $category->id === $cat->id) ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                            @endif
                        @endif
                    @endforeach
                </select>
                @error('category_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
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

            {{-- Tips --}}
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-xl">
                <h4 class="text-sm font-bold text-blue-600 dark:text-blue-400 mb-2 flex items-center gap-2">
                    <i class="fas fa-lightbulb"></i>
                    เคล็ดลับการสร้างกระทู้ที่ดี
                </h4>
                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>✅ ใช้หัวข้อที่ชัดเจนและตรงประเด็น</li>
                    <li>✅ อธิบายรายละเอียดให้ครบถ้วน</li>
                    <li>✅ เลือกหมวดหมู่ที่เหมาะสม</li>
                    <li>✅ ใช้ภาษาสุภาพและเคารพผู้อื่น</li>
                </ul>
            </div>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row gap-4">
                <button type="submit"
                        :disabled="submitting || !isValid"
                        :class="{ 'opacity-50 cursor-not-allowed': submitting || !isValid }"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg transition-all">
                    <i class="fas fa-paper-plane" :class="{ 'animate-spin': submitting }"></i>
                    <span x-text="submitting ? 'กำลังส่ง...' : 'สร้างกระทู้'"></span>
                </button>

                <a href="{{ route('forum.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-6 py-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    <span>ยกเลิก</span>
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function createThread() {
    return {
        categoryId: '{{ $category->id ?? '' }}',
        title: '',
        content: '',
        submitting: false,

        get isValid() {
            return this.categoryId !== '' &&
                   this.title.length >= 5 &&
                   this.content.length >= 20;
        }
    };
}
</script>
@endpush
@endsection
