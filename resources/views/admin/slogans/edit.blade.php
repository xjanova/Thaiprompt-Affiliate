@extends('layouts.admin-v3')

@section('title', 'แก้ไขคำขวัญ')

@section('content')
<div class="max-w-4xl mx-auto" x-data="sloganForm()">
    <!-- Header Section -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6 border border-white/20 dark:border-white/10">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.slogans.index') }}"
                   class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        ✏️ แก้ไขคำขวัญ
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        แก้ไขเนื้อหาและการตั้งค่าคำขวัญ
                    </p>
                </div>
            </div>

            <!-- Delete Button -->
            <form action="{{ route('admin.slogans.destroy', $slogan) }}" method="POST" class="inline-block"
                  onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบคำขวัญนี้?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    ลบ
                </button>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-4 border border-white/20 dark:border-white/10 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($slogan->display_count) }}</p>
            <p class="text-xs text-gray-600 dark:text-gray-400">แสดงแล้ว</p>
        </div>
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-4 border border-white/20 dark:border-white/10 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $slogan->priority }}/10</p>
            <p class="text-xs text-gray-600 dark:text-gray-400">Priority</p>
        </div>
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-4 border border-white/20 dark:border-white/10 text-center">
            <p class="text-2xl font-bold {{ $slogan->is_active ? 'text-green-500' : 'text-gray-400' }}">
                {{ $slogan->is_active ? 'เปิด' : 'ปิด' }}
            </p>
            <p class="text-xs text-gray-600 dark:text-gray-400">สถานะ</p>
        </div>
    </div>

    <!-- Preview Card -->
    <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 mb-6 border border-white/20 dark:border-white/10">
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-4">ตัวอย่างการแสดงผล</h3>
        <div class="bg-gradient-to-r from-pink-500/10 to-rose-500/10 dark:from-pink-500/20 dark:to-rose-500/20 rounded-xl p-6 border border-pink-200/50 dark:border-pink-500/30">
            <div class="flex items-start gap-4">
                <span class="text-4xl" x-text="formData.icon">{{ $slogan->icon }}</span>
                <div class="flex-1">
                    <p class="text-lg text-gray-800 dark:text-gray-200 font-medium leading-relaxed"
                       x-text="formData.content || 'เนื้อหาคำขวัญจะแสดงที่นี่...'">
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-2"
                       x-show="formData.author"
                       x-text="'— ' + formData.author">
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.slogans.update', $slogan) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Content -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 border border-white/20 dark:border-white/10">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📝 เนื้อหาคำขวัญ</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        เนื้อหา <span class="text-red-500">*</span>
                    </label>
                    <textarea name="content" rows="3" required
                              x-model="formData.content"
                              placeholder="กรอกคำขวัญหรือคำคมที่ต้องการ..."
                              class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent resize-none"
                              >{{ old('content', $slogan->content) }}</textarea>
                    @error('content')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ผู้แต่ง/แหล่งที่มา
                    </label>
                    <input type="text" name="author" value="{{ old('author', $slogan->author) }}"
                           x-model="formData.author"
                           placeholder="ระบุผู้แต่งหรือแหล่งที่มา (ถ้ามี)"
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    @error('author')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div class="glass-fusion dark:bg-gray-800 rounded-xl shadow-md p-6 border border-white/20 dark:border-white/10">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚙️ การตั้งค่า</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        หมวดหมู่ <span class="text-red-500">*</span>
                    </label>
                    <select name="category" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                        @foreach($categories as $key => $name)
                            <option value="{{ $key }}" {{ old('category', $slogan->category) == $key ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Priority -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ลำดับความสำคัญ <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center gap-4">
                        <input type="range" name="priority" min="1" max="10" value="{{ old('priority', $slogan->priority) }}"
                               x-model="formData.priority"
                               class="flex-1 h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-pink-500">
                        <span class="w-12 text-center text-lg font-bold text-pink-600 dark:text-pink-400"
                              x-text="formData.priority">{{ $slogan->priority }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        ยิ่งสูงยิ่งมีโอกาสแสดงมากขึ้น
                    </p>
                    @error('priority')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Icon -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ไอคอน <span class="text-red-500">*</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($icons as $icon)
                            <label class="cursor-pointer">
                                <input type="radio" name="icon" value="{{ $icon }}"
                                       x-model="formData.icon"
                                       {{ old('icon', $slogan->icon) == $icon ? 'checked' : '' }}
                                       class="hidden peer">
                                <span class="block w-12 h-12 text-2xl flex items-center justify-center rounded-lg border-2 border-gray-200 dark:border-gray-600 peer-checked:border-pink-500 peer-checked:bg-pink-50 dark:peer-checked:bg-pink-900/30 transition-all">
                                    {{ $icon }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('icon')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Is Active -->
                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1"
                               {{ old('is_active', $slogan->is_active) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-300 text-pink-600 focus:ring-pink-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            เปิดใช้งาน
                        </span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('admin.slogans.index') }}"
               class="px-6 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                ยกเลิก
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 text-white rounded-lg transition-all duration-200">
                บันทึกการแก้ไข
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function sloganForm() {
    return {
        formData: {
            content: @json(old('content', $slogan->content)),
            author: @json(old('author', $slogan->author) ?? ''),
            icon: @json(old('icon', $slogan->icon)),
            priority: {{ old('priority', $slogan->priority) }},
        }
    }
}
</script>
@endpush
